<?php

include(__DIR__ . '/webdata/init.inc.php');

Pix_Controller::addCommonHelpers();
if (!getenv('SESSION_SECRET')) {
    die("need SESSION_SECRET");
}
Pix_Controller::addDispatcher(function($uri){
    if ($uri == '/') {
        return ['index', 'index'];
    }
    if (preg_match('#^/_/(.*)#', $uri, $matches)) {
        $terms = explode('/', $matches[1]);
        if (count($terms) == 1) {
            return [$terms[0], 'index'];
        }
        return [$terms[0], $terms[1]];
    }

    if (preg_match('#^/(.+)$#', $uri, $matches)) {
        return ['user', 'show', [explode('/', $matches[1])[0]]];
    }

    return null;
});

Pix_Session::setAdapter('cookie', array('secret' => getenv('SESSION_SECRET'), 'secure' => true, 'cookie_domain' => ''));
Pix_Controller::dispatch(__DIR__ . '/webdata/');
