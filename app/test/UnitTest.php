<?php /** @noinspection ForgottenDebugOutputInspection */

namespace app\test;

use PHPUnit\Framework\TestCase;
use ReverseRegex\Generator\Scope;
use ReverseRegex\Lexer;
use ReverseRegex\Parser;
use ReverseRegex\Random\MersenneRandom;

class UnitTest extends TestCase
{
    // 每个测试方法调用前运行
    public function setUp(): void
    {
        dump('setUp');
    }

    // 每个测试方法调用后运行
    public function tearDown(): void
    {
        dump('tearDown');
    }

    // 最后一个方法调用后执行
    public static function tearDownAfterClass(): void
    {
        dump('tearDownAfterClass');
    }

    // 第一个测试方法调用前执行
    public static function setUpBeforeClass(): void
    {
        dump('setUpBeforeClass');
    }

    public function test1(): string
    {
        $this->assertEquals(1, 1);
        return 'a';
    }

    public function test2(): string
    {
        $this->assertEquals(1, 1);
        return 'b';
    }

    public function data(): array
    {
        return [
            [1, 2],
            [5, 6],
        ];
    }

    /**
     * depends:所依赖的测试，返回的数据会提供到当前的测试里, dataProvider:提供数据，返回的参数会在前面
     * @depends test1
     * @depends test2
     * @dataProvider data
     * @return void
     */
    public function test3(): void
    {
        $this->assertEquals(1, 1);
        dump(func_get_args());
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