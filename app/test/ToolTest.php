<?php

namespace app\test;

use DirectoryIterator;
use PHPUnit\Framework\TestCase;
use WpOrg\Requests\Exception;
use WpOrg\Requests\Requests;

class ToolTest extends TestCase
{
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
        file_put_contents("php://filter/write=convert.base64-encode/resource=example.txt","Hello World");
    }

    public function testOtherStream(): void
    {
        $this->assertEquals("I love PHP", file_get_contents('data://text/plain;base64,SSBsb3ZlIFBIUA=='));

        // 打印当前目录下的所有php文件名和文件大小
        $it = new DirectoryIterator("glob://*.php");
        foreach($it as $f) {
            printf("%s: %.1FK\n", $f->getFilename(), $f->getSize()/1024);
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
}
