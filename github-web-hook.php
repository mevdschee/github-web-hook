<?php

if (!deploy()) {
    http_response_code(500);
}

function deploy()
{
    if (!isValid()) {
       return false;
    }
    system('git -C ../app pull -f 2>&1 1>../log/last_git.log', $gitError);
    if ($gitError) {
        return false;
    }
    return true;
}

function isValid()
{
    $signature = @$_SERVER['HTTP_X_HUB_SIGNATURE'];
    $event = @$_SERVER['HTTP_X_GITHUB_EVENT'];
    $delivery = @$_SERVER['HTTP_X_GITHUB_DELIVERY'];
    $secret  = getenv('GITHUB_WEB_HOOK_SECRET');

    if (!isset($signature, $event, $delivery)) {
        return false;
    }

    $payload = file_get_contents('php://input');

    list ($algo, $sighash) = explode("=", $signature);

    if ($algo !== 'sha1') {
        // see https://developer.github.com/webhooks/securing/
        return false;
    }

    $hash = hash_hmac($algo, $payload, $secret);
    
    if ($hash != $sighash) {
        return false;
    }

    $data = json_decode($payload,true);

    if ($data === null) {
        return false;
    }

    return true;
}

