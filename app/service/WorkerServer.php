<?php

declare (strict_types=1);

namespace app\service;

use think\facade\Log;
use think\helper\Str;
use think\worker\Server;
use Workerman\Worker;

class WorkerServer extends Server
{
    private array $connectMap = [];
    protected $socket = 'websocket://0.0.0.0:2345';

    public function onWorkerStart($worker)
    {
        Log::write('onWorkerStart : ' . $worker->getSocketName());
        //        bind('worker', $worker); 运行在不同进程中，无法绑定进tp进程

        // 每20秒给所有人发条消息
        //        Timer::add(20, static function() use($worker){
        //            foreach($worker->connections as $connection) {
        //                $connection->send('hello');
        //            }
        //        });

        $http_worker = new Worker("http://127.0.0.1:2347");
        // 当http客户端发来数据时触发
        $http_worker->onMessage = function ($connection, $data) use ($worker) {
            $_POST = $_POST ?: $_GET;
            // 推送数据的url格式 type=publish&to=uid&content=xxxx
            $to = @$_POST['to'];
            $message = @$_POST['content'];
            $res = false;
            if ($to) {
                // 有指定uid则向uid所在socket组发送数据
                $to = explode(',', $to);
                foreach ($to as $uid) {
                    if (!empty($message)) {
                        $res = $this->connectMap[(int)$uid]->send($message);
                    }
                }
            } else {
                foreach ($worker->connections as $con) {
                    $res = $con->send('hello');
                }
            }
            $connection->send($res ? 'success' : 'error');
        };
        // 执行监听
        $http_worker->listen();
    }

    public function onWorkerReload($worker)
    {

    }

    public function onConnect($connection)
    {
        $connection->send('你好，' . spl_object_hash($connection));
    }

    public function onMessage($connection, $data)
    {
        $connection->send('receive success');
        if (is_string($data) && Str::contains($data, 'auth_uid')) {
            $uid = explode(':', $data)[1];
            if (!isset($this->connectMap[$uid])) {
                $this->connectMap[(int)$uid] = $connection;
            }
        }
    }

    public function onClose($connection)
    {

    }

    public function onError($connection, $code, $msg)
    {
        echo "error [ $code ] $msg\n";
    }
}
