<?php

namespace app\test;
require __DIR__ . '/../../vendor/autoload.php';

use PHPUnit\Framework\TestCase;
use think\App;
use think\facade\Db;

class MongoTest extends TestCase
{
    public function __construct(string $name)
    {
        parent::__construct($name);
        $http = (new App())->http;
        $http->run();
    }

    public function testFind(): void
    {
        $data = Db::connect('local_mongo')->table('user')->select();
        $this->assertNotEmpty($data);
    }

    public function testColumn(): void
    {
        $data = Db::connect('local_mongo')->table('user')->where('sex', 'x')->column('name,age', 'id');
        $this->assertNotEmpty($data);
    }

    public function testSave(): void
    {
        $data = Db::connect('local_mongo')->table('user')->where('sex', 'x')->find();
        $data['name'] = '小黑f';
        $res = Db::connect('local_mongo')->table('user')->save($data);
        $this->assertEquals(1, $res); // 未更新返回0
        $res = Db::connect('local_mongo')->table('user')->save(['name' => '小绿', 'age' => '22']);
        $this->assertEquals(1, $res);
    }

    public function testInsertAll(): void
    {
        $data = [
            ['foo' => 'bar', 'bar' => 'foo'],
            ['foo' => 'bar1', 'bar' => 'foo1'],
            ['foo' => 'bar2', 'bar' => 'foo2']
        ];
        $res = Db::connect('local_mongo')->table('user')->insertAll($data);
        $this->assertEquals(3, $res);
    }

    public function testUpdate(): void
    {
        $res = Db::connect('local_mongo')->table('user')->where('sex', 'x')->update(['name' => '小粉', 'age' => '24']);
        $this->assert(1, $res); // 返回更新数量
    }

    public function testDelete(): void
    {
        $res = Db::connect('local_mongo')->table('user')->where('sex', 'x')->delete();
        $this->assert(1, $res); // 返回更新数量
    }
}