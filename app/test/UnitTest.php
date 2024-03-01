<?php

namespace app\test;

use PHPUnit\Framework\TestCase;

class UnitTest extends TestCase
{
    public function jdd($var): void
    {
        try {
            echo json_encode($var, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
        } catch (\JsonException $e) {
        }
    }

    public function test1(): void
    {
        $this->jdd([1,23]);
        $stack = [];
        $this->assertCount(0, $stack);
    }

    public function test2(): void
    {
        $this->jdd([1,23]);
        $stack = [];
        $this->assertCount(0, $stack);
    }

    public function test3(): void
    {
        $d = shell_exec("echo 1111");
        $this->assertNotEmpty($d);
    }
}