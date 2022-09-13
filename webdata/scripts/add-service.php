<?php

include(__DIR__ . '/../init.inc.php');

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
