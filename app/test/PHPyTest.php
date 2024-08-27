<?php

namespace app\test;

use PHPUnit\Framework\TestCase;
use PyCore;
use PyDict;
use think\App;

class PHPyTest extends TestCase
{
    public function __construct(string $name)
    {
        parent::__construct($name);
        (new App())->initialize();
        echo '-------------------------------------------------';
    }

    public function testInt(): void
    {
        $dict = new PyDict();

        $n = 1000000;
        $s = microtime(true);
        while ($n--) {
            $dict['key-' . $n] = $n * 3;
        }
        echo 'dict set: ' . round(microtime(true) - $s, 6) . ' seconds' . PHP_EOL;

        $c = 0;
        $n = 1000000;
        $s = microtime(true);
        while ($n--) {
            $c += $dict['key-' . $n];
        }
        echo 'dict get: ' . round(microtime(true) - $s, 6) . ' seconds' . PHP_EOL;

    }

    public function __destruct()
    {
        echo '-------------------------------------------------';
    }
}
