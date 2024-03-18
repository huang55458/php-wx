<?php

// 事件定义文件

return [
    'bind'      => [
//        'Test' => app\event\Test::class,
    ],

    'listen'    => [
        'Test'     => [app\listener\Test::class],
        'AppInit'  => [app\listener\Refresh::class],
        'HttpRun'  => [],
        'HttpEnd'  => [],
        'LogLevel' => [],
        'LogWrite' => [],
    ],

    'subscribe' => [
    ],
];
