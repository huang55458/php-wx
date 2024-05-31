<?php

namespace app\test;

use PHPUnit\Framework\TestCase;
use ReverseRegex\Generator\Scope;
use ReverseRegex\Lexer;
use ReverseRegex\Parser;
use ReverseRegex\Random\MersenneRandom;

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
        $this->jdd([1, 23]);
        $stack = [];
        $this->assertCount(0, $stack);
    }

    public function test2(): void
    {
        $this->jdd([1, 23]);
        $stack = [];
        $this->assertCount(0, $stack);
    }

    public function test3(): void
    {
        $d = shell_exec("echo 1111");
        $this->assertNotEmpty($d);
    }

    public function testReverseRegexp(): void
    {
        $lexer = new Lexer('[\X{00FF}-\X{00FF}]');
        $parser = new Parser($lexer, new Scope(), new Scope());

        $generator = $parser->parse()->getResult();


        $random = new MersenneRandom(random_int(PHP_INT_MIN, PHP_INT_MAX));

        for ($i = 20; $i > 0; $i--) {
            $result = '';
            $generator->generate($result, $random);

            echo $result . PHP_EOL;
            $this->assertMatchesRegularExpression("/^(?:[\u4e00-\u9fa5·]{2,16})$/", $result);
        }
    }
}