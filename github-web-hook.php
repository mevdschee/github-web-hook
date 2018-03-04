<?php

if (isValid()) {
    $dir = __DIR__;
    $output = shell_exec("git -C {$dir} pull -f 2>&1");
    file_put_contents('../log/last_deploy.log', $output);
}

function isValid()
{
    $signature = @$_SERVER['HTTP_X_HUB_SIGNATURE'];
    $event = @$_SERVER['HTTP_X_GITHUB_EVENT'];
    $delivery = @$_SERVER['HTTP_X_GITHUB_DELIVERY'];

    if (!isset($signature, $event, $delivery)) {
        return false;
    }

    $payload = file_get_contents('php://input');

    if (!isSigned($signature, $payload)) {
        return false;
    }

    //$data = json_decode($payload,true);
    return true;
}

function isSigned($signature, $payload)
{
    list ($algo, $sighash) = explode("=", $signature);

    if ($algo !== 'sha1') {
        // see https://developer.github.com/webhooks/securing/
        return false;
    }

    $hash = hash_hmac($algo, $payload, getenv('GITHUB_WEB_HOOK_SECRET'));
    return ($hash === $sighash);
}