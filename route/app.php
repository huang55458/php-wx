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
use think\swoole\response\File;

Route::get('think', function () {
    return 'hello,ThinkPHP6!';
});

//  localhost/Index/hello 这种方式将无法访问（配置了下面的）
Route::get('hello/:name', 'index/hello');

// 默认访问public目录
//Route::miss(static function() {
//    return response(file_get_contents(root_path().Request::url()));
//});

Route::get('favicon.ico', static function () {
    return new File(public_path() . 'favicon.ico');
});

/**
 * css、js 文件被返回成 text/plain,手动修改代码
 * public function setAutoContentType()
 * {
 *     $mimeType = mime_content_type_f($this->file->getPathname());
 *     if ($mimeType) {
 *         $this->header['Content-Type'] = $mimeType;
 *     }
 * }
 */
Route::get('static/:path', static function (string $path) {
    $filename = public_path() . 'static/' . $path;
    if (!is_file($filename)) {
        $filename = public_path() . Request::url();
    }

    return new File($filename);
})->pattern(['path' => '.*']);
