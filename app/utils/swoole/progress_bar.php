<?php

require __DIR__ . '/../../../vendor/autoload.php';

use Swoole\Process;
use Swoole\Coroutine;
use Swoole\Coroutine\Server\Connection;

Coroutine\run(static function () {
    $server = new Swoole\Coroutine\Server('127.0.0.1', 9501);

    //收到15信号关闭服务
    Process::signal(SIGTERM, function () use ($server) {
        $server->shutdown();
    });

    //接收到新的连接请求 并自动创建一个协程
    $server->handle(function (Connection $conn) {
        while (true) {
            //接收数据
            $data = $conn->recv(2);
            if ($data === '' || $data === false) {
                // errCode: 110, errMsg: Connection timed out
                $errCode = swoole_last_error();
                $errMsg = socket_strerror($errCode);
                Coroutine::sleep(1);
                continue;
            }
            if ($data == 100) {
                printf('当前fd：%d已执行完成，连接断开' . PHP_EOL, $conn->exportSocket()->fd);
                $conn->close();
                return;
            }

            $mark = array_fill(0, round(((int)$data) / 2), '#');
            printf("progress:[%-50s]%d%%\r", implode('', $mark), $data);
        }
    });

    //开始监听端口
    $server->start();
});
