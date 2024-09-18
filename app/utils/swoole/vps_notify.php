<?php

use app\utils\SendEmail;
use think\App;
use function Swoole\Coroutine\run;

require __DIR__ . '/../../../vendor/autoload.php';
(new App())->initialize();

run(static function () {
    swoole_timer_tick(10 * 60 * 1000, static function () {
        $res_data = test_curl(param: ['url' => 'https://bwh88.net/order/get-data']);
        $res_data = array_filter($res_data['products'] ?? [], static function ($val) {
            $flag = true;

            if ((int)$val['cpu'] > 2) {
                $flag = false;
            }

            $prices = array_column($val['prices'], 'cents', 'period');
            if ($prices['Annually'] > 10000) {
                $flag = false;
            }

            if ($val['name'] === '20G KVM - PROMO' && in_array('basic', $val['tiers'], true)) {
                $flag = false;
            }

            return $flag;
        });
        if (!empty($res_data)) {
            (new SendEmail())->run('vps商品有变动');
        }
    });
});
