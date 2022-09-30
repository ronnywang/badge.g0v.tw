<?php

include(__DIR__ . '/../init.inc.php');

Service::insert([
    'service_id' => 'github',
    'data' => json_encode([
        'name' => 'Github',
        'url' => 'https://github.com/g0v',
        'start_at' => '2012-10-21',
        'end_at' => '2022-09-30',
    ]),
]);
Service::insert([
    'service_id' => 'hackpad',
    'data' => json_encode([
        'name' => 'Hackpad',
        'url' => 'https://g0v.hackpad.tw',
        'start_at' => '2013-03-21',
        'end_at' => '2022-09-29',
    ]),
]);
Service::insert([
    'service_id' => 'hackmd',
    'data' => json_encode([
        'name' => 'HackMD',
        'url' => 'https://g0v.hackmd.io',
        'start_at' => '2018-06-30',
        'end_at' => '2022-04-19',
    ]),
]);

Service::insert([
    'service_id' => 'slack',
    'data' => json_encode([
        'name' => 'Slack',
        'url' => 'https://join.g0v.tw',
        'start_at' => '2014-09-18',
        'end_at' => '2022-06-18',
    ]),
]);
