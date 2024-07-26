<?php

namespace app\test;

use DirectoryIterator;
use PHPUnit\Framework\TestCase;
use think\App;
use const JSON_ERROR_NONE;

class ToolTest extends TestCase
{
    public function __construct(string $name)
    {
        parent::__construct($name);
        $http = (new App())->http;
        $http->run();
    }

    public function testStream(): void
    {
        // 限制内存为 5 MB, php://temp 会在内存量达到预定义的限制后（默认是 2MB）存入临时文件中
        $fiveMBs = 5 * 1024 * 1024;
        $fp = fopen("php://temp/maxmemory:$fiveMBs", 'rb+');
        fwrite($fp, "hello");
        rewind($fp);
        $this->assertEquals("hello", stream_get_contents($fp));

        // php://memory 和 php://temp 是一次性的，比如：stream 流关闭后，就无法再次得到以前的内容了
        file_put_contents('php://memory', 'PHP');
        $this->assertEmpty(file_get_contents('php://memory'));

        // 没有指定过滤器 等同于：readfile("http://www.example.com");
        // 可使用 stream_get_filters() 获取可用的过滤器
        readfile("php://filter/resource=https://ipecho.net/ip");
        // 大写字母输出、ROT13 加密
        readfile("php://filter/read=string.toupper|string.rot13/resource=http://localhost:306");
        // base64编码数据写入example.txt文件
        file_put_contents("php://filter/write=convert.base64-encode/resource=example.txt", "Hello World");
    }

    public function testOtherStream(): void
    {
        $this->assertEquals("I love PHP", file_get_contents('data://text/plain;base64,SSBsb3ZlIFBIUA=='));

        // 打印当前目录下的所有php文件名和文件大小
        $it = new DirectoryIterator("glob://*.php");
        foreach ($it as $f) {
            printf("%s: %.1FK\n", $f->getFilename(), $f->getSize() / 1024);
        }
    }

    public function testPcntl(): void
    {
        $i = 1;
        $pid = pcntl_fork();
        if ($pid === -1) {
            die('could not fork');
        }
        if ($pid) { // 父进程 ,先执行
            $i = 3;
            pcntl_wait($status, []);
        } else {
            $i = 2;
        }
        $this->assertEquals(2, $i);
    }

    public function testFn(): void
    {
        $y = 1;
        $fn1 = static fn($x) => $x + $y;
        $this->assertEquals(2, $fn1(1));

        $z = 1;
        $fn = static fn($x) => static fn($y) => $x * $y + $z;
        $this->assertEquals(51, $fn(5)(10));

        // 箭头函数会自动绑定上下文变量 不能修改外部作用域的任何值
        $fn2 = static fn() => ++$y;
        $this->assertEquals(2, $fn2());
        $this->assertEquals(1, $y);

        $fn3 = static function () use (&$z) {
            $z++;
        };
        $fn3();
        $this->assertEquals(2, $z);

        $arr = [1, 2, 3];
        $arr = array_map(static fn($x) => $x + 2, $arr);
        $this->assertEquals([3, 4, 5], $arr);
    }

    public function testYield(): void
    {
        file_put_contents(sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'tmp.txt', "2\n5\n8");
        $file_handle = fopen(sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'tmp.txt', 'rb');
        function get_all_lines($file_handle): \Generator
        {
            while (!feof($file_handle)) {
                yield fgets($file_handle);
            }
        }

        $flag = true;
        $data = array();
        foreach (get_all_lines($file_handle) as $line) {
            if ($flag) {
                $flag = false;
                continue;
            }
            $data[] = $line;
        }
        $this->assertEquals(13, array_sum($data));
        fclose($file_handle);
    }

    public function testDevShm(): void
    {
        function set($key, $value): bool|int
        {
            $file_name = md5(__CLASS__) . '_' . $key;
            $path = '/dev/shm/' . $file_name;
            $value = is_array($value) ? encode_json($value) : $value;
            return file_put_contents($path, $value);
        }
        function get($key){
            $file_name = md5(__CLASS__) . '_' . $key;
            $path = '/dev/shm/' . $file_name;
            $value = file_get_contents($path);
            if (($json = decode_json($value)) !== JSON_ERROR_NONE) {
                return $json;
            }
            return $value;
        }
        function delete($key): bool
        {
            $file_name = md5(__CLASS__) . '_' . $key;
            $path = '/dev/shm/' . $file_name;
            return unlink($path);
        }

        $key = 'hello';
        $value = ['key', 'value' => 'world'];
        set($key, $value);
        $val = get($key);
        $this->assertStringContainsString(encode_json($value), encode_json($val));
        delete($key);
    }
}
