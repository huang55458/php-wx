<?php

declare (strict_types=1);

namespace app\service;

use think\facade\Log;
use WpOrg\Requests\Requests;

class ToolService extends \think\Service
{
    /**
     * 注册服务
     *
     * @return mixed
     */
    public function register()
    {
        //
    }

    /**
     * 执行服务
     *
     * @return mixed
     */
    public function boot()
    {
        //
    }

    public function getSpecifyKeyData($param, $file_path, $key)
    {
        $req = json_decode($param['data']['req'], true);
        $page_size = $req['page_size'];
        $data = test_curl('post', $param);
        $count = $data['res']['total']['count'];
        if ($count > 50000) {
            jdd('超过50000条，添加一些查询参数');
        }
        file_put_contents($file_path, implode(',', array_filter(array_column($data['res']['data'], $key))));
        if ($count > $page_size) {
            $y = floor($count / $page_size);
            for ($i = 0; $i < $y; $i++) {
                $req['page_num']++;
                $param['data']['req'] = json_encode($req, JSON_UNESCAPED_UNICODE);
                $data = test_curl('post', $param);
                Log::write('page_num :' . $req['page_num']);
                file_put_contents($file_path, ',' . implode(',', array_filter(array_column($data['res']['data'], $key))), FILE_APPEND);
            }
        }
    }

    public function getSpecifyKeyDataCopy($uri, $headers, $data, $file_path, $key)
    {
        $req = json_decode($data['req'], true);
        $page_size = $req['page_size'];
        $response = Requests::post($uri, $headers, $data, []);
        $res_data = $response->decode_body();
        $count = $res_data['res']['total']['count'];
        if ($count > 50000) {
            jdd(['超过50000条，添加一些查询参数', $data]);
        }
        file_put_contents($file_path, implode(',', array_filter(array_column($res_data['res']['data'], $key))));
        if ($count > $page_size) {
            $y = floor($count / $page_size);
            for ($i = 0; $i < $y; $i++) {
                $req['page_num']++;
                $data['req'] = json_encode($req, JSON_UNESCAPED_UNICODE);
                $response = Requests::post($uri, $headers, $data, []);
                $res_data = $response->decode_body();
                file_put_contents($file_path, ',' . implode(',', array_filter(array_column($res_data['res']['data'], $key))), FILE_APPEND);
            }
        }
    }

    public function getSpecifyKeyDataCopySpec($uri, $headers, $data, $file_path, $key)
    {
        $req = json_decode($data['req'], true);
        $page_size = $req['page_size'];
        $response = Requests::post($uri, $headers, $data, []);
        $res_data = $response->decode_body();

        $count = $res_data['res']['total']['count'];
        if ($count > 50000) {
            jdd(['超过50000条，添加一些查询参数', $data]);
        }
        file_put_contents($file_path, implode(',', array_filter(array_column(array_column($res_data['res']['data'], $key), 'vir_order_num'))));
        if ($count > $page_size) {
            $y = floor($count / $page_size);
            for ($i = 0; $i < $y; $i++) {
                $req['page_num']++;
                $data['req'] = json_encode($req, JSON_UNESCAPED_UNICODE);
                $response = Requests::post($uri, $headers, $data, []);
                $res_data = $response->decode_body();
                file_put_contents($file_path, ',' . implode(',', array_filter(array_column(array_column($res_data['res']['data'], $key), 'vir_order_num'))), FILE_APPEND);
            }
        }
    }

    public function docDateCompare($uri, $headers, $data)
    {
        $req = json_decode($data['req'], true);
        $page_size = $req['page_size'];
        $response = Requests::post($uri, $headers, $data, []);
        $res_data = $response->decode_body();

        foreach ($res_data['res']['data'] as $v) {
            if (!isset($v['Accounts|doc_date'])) {
                dump($v['settle_no'] . ' 不存在凭证日期');
                Log::write($v['settle_no'] . ' 不存在凭证日期');
                continue;
            }
            if ($v['Accounts|doc_date'] < '2024-04-01 00:00:00' || $v['Accounts|doc_date'] > '2024-04-30 23:59:59') {
                dump($v['settle_no'] . '凭证日期存在问题');
                Log::write($v['settle_no'] . '凭证日期存在问题');
            }
        }
        $count = $res_data['res']['total']['count'];
        if ($count > 50000) {
            jdd('超过50000条，添加一些查询参数');
        }
        if ($count > $page_size) {
            $y = floor($count / $page_size);
            for ($i = 0; $i < $y; $i++) {
                $req['page_num']++;
                $data['req'] = json_encode($req, JSON_UNESCAPED_UNICODE);
                $response = Requests::post($uri, $headers, $data, []);
                $res_data = $response->decode_body();
                foreach ($res_data['res']['data'] as $v) {
                    if (!isset($v['Accounts|doc_date'])) {
                        dump($v['settle_no'] . ' 不存在凭证日期');
                        Log::write($v['settle_no'] . ' 不存在凭证日期');
                        continue;
                    }
                    if ($v['Accounts|doc_date'] < '2024-04-01 00:00:00' || $v['Accounts|doc_date'] > '2024-04-30 23:59:59') {
                        dump($v['Accounts|doc_id'] . '凭证日期存在问题');
                        Log::write($v['Accounts|doc_id'] . '凭证日期存在问题');
                    }
                }
            }
        }
    }
}
