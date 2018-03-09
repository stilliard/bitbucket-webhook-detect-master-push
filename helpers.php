<?php


// Handler for .env files
function env($key) {
    static $dotenv;

    if (is_null($dotenv)) {
        $dotenv = new Dotenv\Dotenv(__DIR__);
        $dotenv->load();
    }

    return getenv($key);
}

// Handler for how to send the email
// feel free to override this to match how you plan to send email
function sendEmail($subject, $html) {

    // mail provider, ref: https://github.com/gabrielbull/omnimail
    switch (env('EMAIL_MAILER')) {
        case 'mailgun':
            checkEnvSet('EMAIL_MAILGUN_APIKEY', 'EMAIL_MAILGUN_DOMAIN');
            $mailer = new Omnimail\Mailgun(env('EMAIL_MAILGUN_APIKEY'), env('EMAIL_MAILGUN_DOMAIN'));
            break;
    }

    $email = (new Omnimail\Email())
    ->setFrom(env('EMAIL_FROM'))
    ->setSubject($subject)
    ->setHtmlBody($html);

    $addresses = array_map('trim', explode(';', env('EMAIL_TO')));
    foreach ($addresses as $address) {
        $email->addTo($address);
    }

    $mailer->send($email);
}

// Handler for errors
function error($msg) {
    echo 'FAILED: ' . $msg;
    exit;
}

// Handler for sending success back to Bitbucket
function success($msg) {
    echo 'OK';
    if (env('DEBUG_MODE')) {
        echo ': ' . $msg;
    }
    exit;
}

// Make sure an env var is set
function checkEnvSet(...$args) {
    foreach ($args as $arg) {
        if (is_null(env($arg))) {
            error("missing .env config for: {$arg}");
        }
    }
}

// Handler for parsing JSON
// uses JmesPath to return a function that can search
function parseJSON($json) {

    // read the json
    $data = @json_decode($json);
    if (! $data) {
        error('could not decode json');
    }

    // return the function to search through the data
    return function ($path) use ($data) {
        return JmesPath\Env::search($path, $data);
    };
}