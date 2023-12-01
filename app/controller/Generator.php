<?php
declare (strict_types = 1);

namespace app\controller;

use think\facade\Log;
use think\Request;

/**
 * 生成器函数测试
 * 任何包含 yield 的函数都是一个生成器函数
 */
class Generator
{
    /**
     * 显示资源列表
     *
     * @return \think\Response
     */
    public function index()
    {
        $generator = $this->gen_one_to_three();
        foreach ($generator as $value) {
            Log::info(json_encode([__LINE__, $value], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));
            echo "$value\n";
        }
    }


    function gen_one_to_three() {
        for ($i = 1; $i <= 3; $i++) {
            Log::info(json_encode([__LINE__, $i], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));
            //注意变量$i的值在不同的yield之间是保持传递的。
            yield $i;
        }
    }


    /**
     * 显示创建资源表单页.
     *
     * @return \think\Response
     */
    public function create()
    {
//        str_contains();
        $result = match(1) {
            1, 2 => [],
            3 => "three",
            default => "unknown",
        };
    }
}
