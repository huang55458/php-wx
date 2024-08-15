<?php

namespace app\test;

use PHPUnit\Framework\TestCase;
use Redis;
use think\App;
use function Co\run;

/**
 * 利用Redis简单实现延时队列
 * @link https://tech.youzan.com/queuing_delay/
 */
class DelayQueueTest extends TestCase
{
    private object $redis;
    private string $topic = 'register_email_notify';
    private int $delay = 60; // 延迟时间
    private int $ttr = 10; // 执行超时时间

    public function __construct(string $name)
    {
        parent::__construct($name);
        (new App())->initialize();
        $this->redis = new Redis();
        $this->redis->connect('127.0.0.1');
    }

    // 模拟用户注册
    public function testRegisterJob(): void
    {
        $data = [
            ['user_id' => 1, 'register_time' => date('Y-m-d H:i:s')],
            ['user_id' => 1, 'register_time' => date('Y-m-d H:i:s', strtotime('+1 minutes'))],
            ['user_id' => 2, 'register_time' => date('Y-m-d H:i:s', strtotime('+2 minutes'))],
            ['user_id' => 3, 'register_time' => date('Y-m-d H:i:s', strtotime('+3 minutes'))],
        ];
        foreach ($data as $value) {
            $this->redis->hSet($this->topic.'_job', $value['user_id'], encode_json([
                'delay' => $this->delay,
                'ttr' => $this->ttr,
                'body' => $value,
            ]));
            $this->redis->zAdd($this->topic.'_sort', [], strtotime(
                "+$this->delay seconds",
                strtotime($value['register_time'])
            ), $value['user_id']);
        }
    }

    // 监听
    public function testTimer(): void
    {
        $redis = $this->redis;
        $sort = $this->topic.'_sort';
        $list = $this->topic.'_list';
        run(static function () use ($redis, $sort, $list) {
            swoole_timer_tick(1000, static function () use ($redis, $sort, $list) {
                $end = time();
                $data = $redis->zRangeByScore($sort, 0, $end);
                if (!empty($data)) {
                    dump(['handle', $data]);
                    foreach ($data as $ignored) {
                        $redis->lPush($list, array_pop($data));
                    }
                    $redis->zRemRangeByScore($sort, 0, $end);
                }
            });
        });
    }

    // 消费list
    public function testConsumer(): void
    {
        $redis = $this->redis;
        $job = $this->topic.'_job';
        $list = $this->topic.'_list';
        run(static function () use ($redis, $job, $list) {
            swoole_timer_tick(100, static function () use ($redis, $job, $list) {
                $user_id = $redis->rPop($list);
                if (!empty($user_id)) {
                    $data = $redis->hGet($job, $user_id);
                    dump(['user_id', $user_id, '处理data', $data]);
                }
            });
        });
    }
}
