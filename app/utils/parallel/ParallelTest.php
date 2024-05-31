<?php

namespace app\utils\parallel;

use parallel\Channel;
use parallel\Runtime;


class ParallelTest
{
    private $futures = [];
    private $channel;
    private $thread_num;
    private $runs;

    /**
     * @param int $thread_num
     */
    public function setThreadNum(int $thread_num): void
    {
        $this->thread_num = $thread_num;
        for ($i = 0; $i < $thread_num; $i++) {
            $this->runs[] = new Runtime();
        }
    }

    public function start($context): void
    {
        $this->channel = Channel::make('myChannel', Channel::Infinite);
        $data = array_chunk($context, ceil(count($context) / $this->thread_num));
        foreach ($this->runs as $k => $run) {
            $this->futures[] = $run->run(static function (Channel $channel, array $item) {
                foreach ($item as $v) {
                    sleep(1);
                    echo $v . '已处理' . PHP_EOL;
                }
            }, [$this->channel, $data[$k]]);
        }
    }

    public function wait(): void
    {
        try {
            foreach ($this->futures as $future) {
                $future->value();
            }
        } catch (\Throwable $e) {
            echo $e->getMessage();
        }
        $this->channel->close();
    }

    public function emit(string $name, $value): void
    {
        $this->channel->send(['name' => $name, 'value' => $value]);
    }
}

class Task
{
    public const context = [3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20];

    // 模拟耗时1s的操作
    public static function handle(int $value): string
    {
        sleep(1);
        return $value . ' 已处理';
    }
}


$a = new ParallelTest();
$a->setThreadNum(4);
$a->start(Task::context);
//$a->wait();