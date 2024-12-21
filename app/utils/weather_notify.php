<?php

/**
 * 和风天气 Wxpusher 推送
 * 使用JSON Web Token (JWT) 进行身份验证失败，使用shell平台生成的Signature可以正常认证，怀疑是php-jwt库的问题
 */

use app\service\ToolService;
use think\App;
use WpOrg\Requests\Requests;

require __DIR__ . '/../../vendor/autoload.php';
$app = new App();
$app->initialize();

$uri = "https://devapi.qweather.com/v7/weather/7d?location=101240103&key=53a9c5e4323747098250473ef6dbfba4"; // 南昌县
$response = Requests::get($uri);
$daily = $response->decode_body()['daily'];
$time = [
    1 => '明天',
    2 => '后天',
    3 => '大后天',
];
$str = '';
foreach ($time as $key => $value) {
    if (str_contains($daily[$key]['textDay'], '雨')) {
        $str .= $value . '白天：' . $daily[$key]['textDay'] . '   ';
    }
    if (str_contains($daily[$key]['textNight'], '雨')) {
        $str .= $value . '晚上：' . $daily[$key]['textNight'] . '   ';
    }
}
if (!empty($str)) {
    (new ToolService($app))->wxpusherSPT($str);
}
