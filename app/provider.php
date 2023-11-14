<?php
use app\ExceptionHandle;
use app\Request;

// 容器Provider定义文件
return [
    'think\Request'          => Request::class,
    'think\exception\Handle' => ExceptionHandle::class,
    'Gaoming13\WechatPhpSdk\Utils\FileCache' => new Gaoming13\WechatPhpSdk\Utils\FileCache(['path' => __DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'runtime']),
];
