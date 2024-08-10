<?php

namespace app\test;

use PHPUnit\Framework\TestCase;
use Redis;
use think\App;

/**
 * @link https://github.com/phpredis/phpredis
 */
class RedisTest extends TestCase
{
    private object $redis;
    public function __construct(string $name)
    {
        parent::__construct($name);
        (new App())->initialize();
        $this->redis = new Redis();
        $this->redis->connect('127.0.0.1');
        $this->redis->setOption(Redis::OPT_SERIALIZER, Redis::SERIALIZER_JSON);
    }

    /** @noinspection ForgottenDebugOutputInspection */
    public function testString(): void
    {
        $key = 'name';
        $value = '张三';
        dump('redis set：' . $this->redis->set($key, $value));
        dump('过期时间：' . $this->redis->ttl($key));
        dump('设置过期时间是否成功：' . $this->redis->expire($key, 10));
        dump('当前过期时间：' . $this->redis->ttl($key));
        dump('当前值的类型：' . $this->redis->type($key));
        dump('重命名结果：' . $this->redis->renameNx($key, $key . '_1'));
        dump('当前keys：', $this->redis->keys('*'));
        dump('当前key是否存在：' . $this->redis->exists($key));
        dump('删除结果：' . $this->redis->del($key));
    }

    /** @noinspection ForgottenDebugOutputInspection */
    public function testHash(): void
    {
        $key = 'hash';
        $hash_key = 'a';
        $value = '1';
        dump('redis set：' . $this->redis->hSetNx($key, $hash_key, $value));
        dump('redis set：' . $this->redis->hSetNx($key, $hash_key . random_int(1, 10), $value));
        dump('redis set：' . $this->redis->hMSet($key, ['c' => 3, 'd' => 4]));
        dump('redis get：', $this->redis->hGetAll($key));
        dump('redis inc：' . $this->redis->hIncrBy($key, 'c', 1));
        dump('redis get：' . $this->redis->hGet($key, 'c'));
        dump('redis values：', $this->redis->hVals($key));
        dump('redis len：' . $this->redis->hLen($key));
        dump('redis exist：' . $this->redis->hExists($key, $hash_key));
        dump('redis h_del：' . $this->redis->hDel($key, $hash_key));
        dump('redis del：' . $this->redis->del($key));
    }

    /** @noinspection ForgottenDebugOutputInspection */
    public function testList(): void
    {
        $key = 'list';
        $value = '1';
        dump('redis set：' . $this->redis->lPush($key, $value));
        dump('redis set：' . $this->redis->rPushx($key, 2));
        dump('redis set：' . $this->redis->lInsert($key, Redis::BEFORE, '1', 0));
        dump('redis 0 index：' . $this->redis->lIndex($key, 0));
        dump('redis range：', $this->redis->lRange($key, 0, -1));
        dump('redis pop：' . $this->redis->lPop($key));
        dump('redis rem：', $this->redis->lRem($key, '1', 0));
        dump('redis range：', $this->redis->lRange($key, 0, -1));
        dump('redis len：' . $this->redis->lLen($key));
        dump('redis 0 index：' . $this->redis->lIndex($key, 0));
    }

    /** @noinspection ForgottenDebugOutputInspection */
    public function testSet(): void
    {
        $key = 'set';
        $value = [1, 1, 2, 3];
        dump('redis set：' . $this->redis->sAddArray($key, $value));
        dump('redis set：' . $this->redis->sAdd($key, 3, 4));
        dump('redis members：', $this->redis->sMembers($key));
        dump('redis is_member：' . $this->redis->sIsMember($key, 5));
        dump('redis rem：' . $this->redis->sRem($key, 1));
        dump('redis card：' . $this->redis->sCard($key));
        dump('redis diff：', $this->redis->sDiff($key, 'other_set'));
        dump('redis inter：', $this->redis->sInter($key, 'other_set'));
        dump('redis inter_store：', $this->redis->sInterStore('result', $key, 'other_set'));
    }

    /** @noinspection ForgottenDebugOutputInspection */
    public function testSortSet(): void
    {
        $key = 'sort_set';
        dump('redis set：' . $this->redis->zAdd($key, [], 1, 'a', 2, 'b'));
        dump('redis range：', $this->redis->zRange($key, 0, -1));
        dump('redis set：' . $this->redis->zAdd($key, [], 10, 'f', 20, 'k'));
        dump('redis range：', $this->redis->zRange($key, 0, -1, true));
        dump('redis count：' . $this->redis->zCount($key, 5, 15));
        dump('redis rem：', $this->redis->zRemRangeByScore($key, 5, 15));
        dump('redis pop_max：', $this->redis->zPopMax($key));
        dump('redis card：' . $this->redis->zCard($key));
        dump('redis incr：' . $this->redis->zIncrBy($key, 10, 'b'));
        dump('redis score：' . $this->redis->zScore($key, 'b'));
        dump('redis del：' . $this->redis->del($key));
    }
}
