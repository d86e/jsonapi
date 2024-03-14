<?php
// 事件定义文件
return [
    'bind'      => [],

    'listen'    => [
        'AppInit'  => [],
        'HttpRun'  => [],
        'HttpEnd'  => [],
        'LogLevel' => [],
        'LogWrite' => [],
        'RequestApi' => ['app\listener\RequestApi'],
    ],

    'subscribe' => [
        'app\subscribe\RequestApi',
    ],
];
