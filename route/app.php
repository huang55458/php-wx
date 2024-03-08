<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2018 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
use think\facade\Request;
use think\facade\Route;

Route::get('think', function () {
    return 'hello,ThinkPHP6!';
});

//  localhost/Index/hello 这种方式将无法访问（配置了下面的）
Route::get('hello/:name', 'index/hello');

// 默认访问public目录
//Route::miss(static function() {
//    return response(file_get_contents(root_path().Request::url()));
//});