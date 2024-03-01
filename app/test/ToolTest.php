<?php

namespace app\test;

use PHPUnit\Framework\TestCase;
use WpOrg\Requests\Exception;
use WpOrg\Requests\Requests;

class ToolTest extends TestCase
{
    public function jdump($var): void
    {
        try {
            dump(json_encode($var, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));
        } catch (\JsonException $e) {
        }
    }

    public function getSpecifyKeyDataCopy($uri, $headers, $data, $file_path, $key): void
    {
        try {
            $req = json_decode($data['req'], true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
        }
        $page_size = $req['page_size'];
        $response = Requests::post($uri, $headers, $data, []);
        try {
            $data = $response->decode_body();
        } catch (Exception $e) {
        }

        $count = $data['res']['total']['count'];
        if ($count > 50000) {
            $this->jdump('超过50000条，添加一些查询参数');die();
        }
        if (empty($data['res']['data'])) {
            file_put_contents($file_path, '');return;
        }
        file_put_contents($file_path, implode(',', array_filter(array_column($data['res']['data'], $key))));
        if ($count > $page_size) {
            $y = floor($count / $page_size);
            for ($i = 0; $i < $y; $i++) {
                $req['page_num']++;
                try {
                    $data['req'] = json_encode($req, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
                } catch (\JsonException $e) {
                }
                $response = Requests::post($uri, $headers, $data, []);
                $data = $response->decode_body();
                file_put_contents($file_path, ','.implode(',', array_filter(array_column($data['res']['data'], $key))), FILE_APPEND);
            }
        }
    }

    // php ./phpunit ../../app/test/ToolTest.php --filter testCompare
    public function testCompare(): void
    {
        $path1 = 'E:\app\tp6\runtime\tmp.txt';
        $path2 = 'E:\app\tp6\runtime\tmp2.txt';
        $tmp = [];
        for ($i = 1; $i <= 29; $i++) {
            $start_time = date('Y-m-d H:i:s', mktime(0, 0, 0, 2, $i, 2024));
            $end_time = date('Y-m-d H:i:s', mktime(23, 59, 59, 2, $i, 2024));
            // 资金流水
            $uri = '';
            $headers = [
                'cookie' => '',
            ];
            // "'.$start_time.'"
            $data = [
                'req' => '',
            ];
            $this->getSpecifyKeyDataCopy($uri, $headers, $data, $path1, 'Order|order_num');
            // 交易记录
            $uri = '';
            $headers = [
                'cookie' => '',
            ];
            $data = [
                'req' => '',
            ];
            $this->getSpecifyKeyDataCopy($uri, $headers, $data, $path2, 'now_order_num');
            $order_num = explode(',', file_get_contents($path1));
            $now_order_num = explode(',', file_get_contents($path2));
            if (count($now_order_num) === count($order_num)) {
                continue;
            }
            foreach ($order_num as $r => $t) {
                foreach ($now_order_num as $k => $v) {
                    if ($t == $v) {
                        unset($now_order_num[$k], $order_num[$r]);
                        continue 2;
                    }
                }
            }
            $this->jdump(["第{$i}天，","有交易记录多的运单号为：",array_values($now_order_num),"有资金流水多的运单号为：",array_values($order_num)]);
            $tmp = array_merge($tmp, array_values($now_order_num));
        }
        $this->jdump(["交易记录中多的运单号为：",$tmp]);
        $this->assertNotEmpty($tmp);
    }
}
