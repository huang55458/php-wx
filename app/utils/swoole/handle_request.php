<?php

use Joli\JoliNotif\DefaultNotifier;
use Joli\JoliNotif\Notification;
use Swoole\Coroutine;
use Swoole\Process;
use think\facade\Log;
use WpOrg\Requests\Requests;
use think\App;

$time = time();
register_shutdown_function(static function () use ($time) {
    echo '执行耗时：' . (time() - $time) . ' seconds' . PHP_EOL;
    echo '内存峰值：' . round(memory_get_peak_usage() / 1024 / 1024, 2) . ' M' . PHP_EOL;
});


require __DIR__ . '/../../../vendor/autoload.php';
(new App())->initialize();

$help = <<< EOF
php handle_request.php ids.txt 10\n
EOF;
//empty($argv[1]) && exit($help);

$file_path = $argv[1] ?? __DIR__ . '/../../../runtime/tmp.txt';
$process_num = $argv[2] ?? 6;

$ids = explode(',', file_get_contents($file_path));
empty($ids) && exit('id为空');
$total_count = count($ids);
echo '当前id数量：' . $total_count . PHP_EOL;
$ids = array_chunk($ids, 50);
$chuck_count = count($ids);
$table = new Swoole\Table($chuck_count * 4);
$table->column('value', Swoole\Table::TYPE_STRING, 1024);
$table->create();
$atomic = new Swoole\Atomic();
$lock = new Swoole\Lock(SWOOLE_MUTEX);
$handled_times = new Swoole\Atomic();
foreach ($ids as $key => $item) {
    $table->set($key, ['value' => implode(',', $item)]);
}

$fun = static function () use ($handled_times, $total_count) {
    swoole_timer_tick(2000, static function (int $timer_id) use ($handled_times, $total_count) {
        $hint = sprintf("------------------ 当前执行进度：%0.2f%s\n", $handled_times->get() / $total_count * 100, '%');
        echo $hint;
//        Coroutine::exec("echo -e '\033[1m{$hint}\033[0m'"); // 无法输出到命令行
//        $notifier = new DefaultNotifier();
//        $notification = (new Notification())->setTitle('php program progress')->setBody(round($handled_times->get() / $total_count * 100, 2) . '%');
//        $notifier->send($notification); // window 通知
    });
};

$pool = new Process\Pool($process_num);
$pool->set(['enable_coroutine' => true]);
$pool->on('WorkerStart', function (Process\Pool $pool, $workerId) use ($table, $atomic, $total_count, $lock, $handled_times, $fun) {
    if ($workerId === 0) {
        $fun();
        return;
    }
    if ($table->count() === 0) {
        if ($handled_times->get() === $total_count) {
            $pool->shutdown();
        }
        Coroutine::sleep(10);
        return;
    }
    $lock->lock();
    $head_id = $atomic->get();
    $atomic->add();
    $lock->unlock();
    $ids = $table->get($head_id)['value'] ?? '';
    $table->del($head_id);
    $ids = empty($ids) ? [] : array_chunk(explode(',', $ids), 5);

    $uri = 'http://yundan.vkj56.cn/api/Accounts/DoData/updateAccrual?doc_ids=';
    foreach ($ids as $item) {
        $response = Requests::get($uri . implode(',', $item), [], ['connect_timeout' => 20]);
        $handled_times->add(count($item));
        Log::write($response->body);
        dump($response->body);
    }
});
$pool->start();
