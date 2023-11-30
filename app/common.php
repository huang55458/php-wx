<?php
// 应用公共文件
function jdd($var, $name=null)
{
    header('Content-Type: application/json; charset=utf-8');
    if(is_scalar($name)){
        $var = [$name=>$var];
    }
    echo json_encode($var, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
    die;
}