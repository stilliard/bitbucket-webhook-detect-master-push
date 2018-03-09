<?php

error_reporting(E_ALL);
ini_set('display_errors', true);

require_once dirname(__FILE__) . '/vendor/autoload.php';
require_once dirname(__FILE__) . '/helpers.php';

// which branch is master?
$branch = env('MASTER_BRANCH');

// get json data
if (env('LOCAL_TEST_FILE')) {
    // for testing you could use a local json file
    $postData = file_get_contents(
        dirname(__FILE__) . '/' . env('LOCAL_TEST_FILE'));

} else {
    $postData = file_get_contents('php://input');
    if (! $postData) {
        error('no data provided');
    }
}
$data = parseJSON($postData);

// detect if this is a push to master
$pushedTo = $data('push.changes[0].new');
if (! $pushedTo || $pushedTo->type != 'branch' || $pushedTo->name != $branch) {
    success('not a push to master so no issue here');
}

// parse to get user
$user = $data('actor');
if (! $user) {
    error('user/actor record not defined?');
}

// repo
$repo = $data('repository');
if (! $repo) {
    error('repository record not defined?');
}

// & commit list inc links
$commits = $data('push.changes[0].commits');
if (! $commits) {
    error('no commits found?');
}

// format the subject
$subject = str_replace(
    ['{name}', '{project}', '{branch}'],
    [$user->display_name, $repo->full_name, $branch],
    env('EMAIL_SUBJECT'));

// format the email html
$html = "
    <p>
        <img style=\"width:20px;vertical-align:middle;\" src=\"{$user->links->avatar->href}\">
        <a href=\"{$user->links->html->href}\"><strong>{$user->display_name}</strong></a>
        pushed to
        <a href=\"{$repo->links->html->href}/commits/branch/{$branch}\"><strong>{$branch}</strong></a>
        on
        <a href=\"{$repo->links->html->href}\">{$repo->full_name}</a> !
    </p>
    <p>Review the changes here:</p>
    <ul>";

foreach ($commits as $commit) {
    $commit->summary->raw = trim($commit->summary->raw);
    $html .= "
        <li style=\"margin-bottom:5px;\">
            <a href=\"{$commit->links->html->href}\">{$commit->summary->raw}</a>
        </li>
    ";
}

$html .= "</ul>";

// send the email
sendEmail($subject, $html);
success('sent email');
