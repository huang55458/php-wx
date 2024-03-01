<?php

// 应用公共文件
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
