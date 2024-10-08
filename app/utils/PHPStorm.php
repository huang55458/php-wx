<?php

namespace app\utils;

use RuntimeException;
use think\response\Json;

class PHPStorm
{
    /**
     * @name PHPStorm 名字
     * @abstract 申明变量/类/方法
     * @access 指明这个变量、类、函数/方法的存取权限
     * @param String $a 变量a [定义函数或者方法的参数信息]
     * @return Json 定义函数或者方法的返回信息|
     * @throws RuntimeException 指明此函数可能抛出的错误异常,极其发生的情况
     * @author chumeng [函数作者的名字和邮箱地址]
     * @category 组织packages
     * @copyright 指明版权信息
     * @const 指明常量
     * @deprecate 指明不推荐或者是废弃的信息
     * @example 示例
     * @exclude 指明当前的注释将不进行分析，不出现在文挡中
     * @final 指明这是一个最终的类、方法、属性，禁止派生、修改。
     * @global array $_GET 指明在此函数中引用的全局变量
     * @include 指明包含的文件的信息
     * @link https://google.com
     * @module 定义归属的模块信息
     * @modulegroup 定义归属的模块组
     * @package 定义归属的包的信息
     * @see  http://baidu.com 百度的链接 [定义需要参考的函数、变量，并加入相应的超级连接]
     * @since 指明该api函数或者方法是从哪个版本开始引入的
     * @static 指明变量、类、函数是静态的。
     * @todo 指明应该改进或没有实现的地方
     * @var callable $b 定义说明变量/属性。
     * @version 1.0 定义版本信息
     */
    public function annotate(String $a, callable $b) : Json
    {
        if ($a === '' && $b()) {
            throw new RuntimeException();
        }
        return json([]);
    }
}
