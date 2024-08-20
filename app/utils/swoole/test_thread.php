<?php
use Swoole\Thread;
use Swoole\Thread\Map;

$args = Thread::getArguments();
$c = 4;

// 主线程没有线程参数，$args 为 null
if (empty($args)) {
    /**
     *  Map、ArrayList、Queue 会自动分配内存，不需要像 Table 那样固定分配
     *  底层会自动加锁，是线程安全的
     *  可传递的变量类型参考 数据类型
     *  不支持迭代器，在迭代器中删除元素会出现内存错误
     *  必须在线程创建前将 Map、ArrayList、Queue 对象作为线程参数传递给子线程
     */
    $map = new Map();
    # 主线程
    for ($i = 0; $i < $c; $i++) {
        /**
         *  string __FILE__ 线程启动后要执行的文件
         *  mixed ...$args  主线程传递给子线程的共享数据，在子线程中可使用 Swoole\Thread::getArguments() 获取
         */
        $threads[] = new Thread(__FILE__, $i, $map, 'thread');
    }
    echo 'main running ...'.PHP_EOL;
    $map->add('today', 'Tuesday');
    for ($i = 0; $i < $c; $i++) {
        // 主线程等待子线程退出, 若子线程仍在运行，join() 会阻塞
        $threads[$i]->join();
    }
} else {
    # 子线程
    echo "Thread #" . $args[0] . " start\n";
    $map = $args[1];
    echo $map['today'].PHP_EOL;
    echo json_encode($args, 128).PHP_EOL;
    while (1) {
        sleep(1);
        printf('Thread #%d : last thread modify %d,thread running ...'.PHP_EOL, $args[0], $map['thread']);
        $map['thread'] = $args[0];
    }
}
