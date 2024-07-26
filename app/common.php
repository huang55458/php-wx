<?php

// 应用公共文件
use app\cnsts\ERRNO;
use think\facade\Log;
use think\response\Json;
use think\response\View;

function jdd($var, $name = null)
{
    header('Content-Type: application/json; charset=utf-8');
    if(is_scalar($name)) {
        $var = [$name => $var];
    }
    echo json_encode($var, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
    die;
}

function test_curl($type = 'get', $param = [], $return = 'php')
{
    $ch = curl_init();
    $url = $param['url'] ?? '';
    $headers = $param['headers'] ?? [];
    $cookie = $param['cookie'] ?? '';
    $data = $param['data'] ?? [];
    if (empty($url)) {
        return [];
    }
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    if ($type === 'post') {
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    }
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_COOKIE, $cookie);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 4);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $res = curl_exec($ch);
    curl_close($ch);
    if ($return === 'php') {
        return json_decode($res, true);
    }
    return $res;
}

function export_csv($file_name, $header, $body, $footer = [], $charset = 'GBK')
{
    set_time_limit(400);
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment;filename="' . $file_name . '.xls"');
    header('Cache-Control: max-age=0');

    // If you're serving to IE over SSL, then the following may be needed
    header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
    header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT'); // always modified
    header('Cache-Control: cache, must-revalidate'); // HTTP/1.1
    header('Pragma: public'); // HTTP/1.0

    $low_cache_output = static function ($fp, $row_data) {
        static $offset = 0;
        fputcsv($fp, $row_data, "\t");
        $offset++;
        if ($offset >= 100) {
            $offset = 0;
            flush();
            ob_flush();
        }
    };
    $fp = fopen('php://output', 'wb');
    // header
    if ($header) {
        foreach ($header as $k => $v) {
            $header[$k] = iconv('UTF-8', $charset, $v);
        }
        $low_cache_output($fp, $header);
    }
    // body
    if (is_callable($body)) {
        foreach ($body() as $row_data) {
            foreach ($row_data as &$field) {
                $field = iconv('UTF-8', $charset.'//TRANSLIT', $field);
                if ((is_numeric($field) && (($field[0] == '0' && substr($field, 0, 2) !== '0.') || strlen($field) > 11))
                    || (!is_numeric($field) and strpos($field, '-') !== false and checkTime($field) === false)) {
                    $field = "\t" . $field;
                }
            }
            unset($field);
            $low_cache_output($fp, $row_data);
        }
    } else {
        foreach ($body as $row_data) {
            foreach ($row_data as &$field) {
                $field = iconv('UTF-8', $charset.'//TRANSLIT', $field);
                if ((is_numeric($field) && (($field[0] == '0' && substr($field, 0, 2) !== '0.') || strlen($field) > 11))
                    || (!is_numeric($field) and strpos($field, '-') !== false and checkTime($field) === false)) {
                    $field = "\t" . $field;
                }
            }
            unset($field);
            $low_cache_output($fp, $row_data);
        }
    }
    // footer
    if ($footer) {
        $footer_array = [];
        foreach ($footer as $k => $v) {
            $footer_array[$k] = iconv('UTF-8', $charset, $v);
        }
        $low_cache_output($fp, $footer_array);
    }

    flush();
    ob_flush();
    fclose($fp);
}

function checkTime($string)
{
    if (
        date('Y-m-d H:i:s', strtotime($string)) === $string
        or date('Y-m-d', strtotime($string)) === $string
    ) {
        return true;
    } else {
        return false;
    }
}

/**
 * 获取客户端IP地址
 * @param integer $type 返回类型 0 返回IP地址 1 返回IPV4地址数字
 * @param boolean $adv 是否进行高级模式获取（有可能被伪装）
 * @return mixed
 */
function get_ip(int $type = 0, bool $adv = false): mixed
{
    $type       =  $type ? 1 : 0;
    static $ip  =   NULL;
    if ($ip !== NULL) return $ip[$type];
    if($adv){
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $arr    =   explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $pos    =   array_search('unknown',$arr);
            if(false !== $pos) unset($arr[$pos]);
            $ip     =   trim($arr[0]);
        }elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
            $ip     =   $_SERVER['HTTP_CLIENT_IP'];
        }elseif (isset($_SERVER['REMOTE_ADDR'])) {
            $ip     =   $_SERVER['REMOTE_ADDR'];
        }
    }elseif (isset($_SERVER['REMOTE_ADDR'])) {
        $ip     =   $_SERVER['REMOTE_ADDR'];
    }
    // IP地址合法验证
    if (filter_var($ip, \FILTER_VALIDATE_IP,\FILTER_FLAG_IPV4)) {
        $long = sprintf("%u",ip2long($ip));
        $ip   = $long ? array($ip, $long) : array('0.0.0.0', 0);
    } elseif (filter_var($ip, \FILTER_VALIDATE_IP,\FILTER_FLAG_IPV6)) {
        $long = ip2long6($ip);
        $ip   = $long ? array($ip, $long) : array('::', 0);
    }
    return $ip[$type] ?? '';
}

/**
 * IPV6 地址转换为整数
 * @param $ip
 * @return string
 */
function ip2long6($ip)
{
    if(($ip_n = inet_pton($ip)) === false) return false;
    $bits = 15; // 16 x 8 bit = 128bit (ipv6)
    while ($bits >= 0)
    {
        $bin = sprintf("%08b",(ord($ip_n[$bits])));
        $ipbin = $bin.$ipbin;
        $bits--;
    }
    return $ipbin;
}

function doResponse($errno = ERRNO::SUCCESS, $errmsg = 'success', $res = [], $tpl = ""): View|Json
{
    $resp = [
        "errno"  => $errno,
        "errmsg" => $errmsg,
        "res"    => $res,
    ];
    if (empty($tpl)) {
        return \json($resp);
    }
    return view($tpl, $resp);
}

function decode_json($json) {
    try {
        return json_decode($json, true, 512, JSON_THROW_ON_ERROR);
    } catch (JsonException $e) {
        Log::error($e->getMessage());
    }
    return json_last_error();
}

function encode_json($value, $options = JSON_UNESCAPED_UNICODE): bool|string
{
    try {
        return json_encode($value, JSON_THROW_ON_ERROR | $options);
    } catch (JsonException $e) {
        Log::error($e->getMessage());
    }
    return false;
}