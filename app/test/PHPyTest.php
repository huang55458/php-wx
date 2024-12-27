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

    /**
     * @return void
     * 测试失败，chromedriver无法启动
     */
    public function testSelenium(): void
    {
        $webdriver = PyCore::import('selenium.webdriver');
        $chrome = PyCore::import('selenium.webdriver.chrome');
        $service = $chrome->service(executable_path:"/mnt/e/chromedriver-linux64/chromedriver");

        $options = $webdriver->ChromeOptions();
        $options->page_load_strategy = 'normal';
        $driver = $webdriver->Chrome(service: $service, options: $options);
        $driver.get("http://w-sas-1000-web-alpha-01.vkj56.cn:306");
        dump($driver->title);
        $driver.quit();
    }

    public function __destruct()
    {
        echo '-------------------------------------------------';
    }
}
