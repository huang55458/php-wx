<?php

namespace app\controller;

use app\BaseController;
use app\cnsts\ERRNO;
use app\service\SearchService;
use app\service\ToolService;
use finfo;
use ReverseRegex\Generator\Scope;
use ReverseRegex\Lexer;
use ReverseRegex\Parser;
use ReverseRegex\Random\MersenneRandom;
use RuntimeException;
use think\facade\Db;
use think\facade\Log;
use think\helper\Str;
use think\response\Json;
use think\response\View;
use WpOrg\Requests\Requests;

class Tool extends BaseController
{
    private string $file_path = '';

    public function hello($name = 'ThinkPHP8')
    {
        event('Test');
        return json('hello,' . $name);
    }

    /*
     * 库是以前的，php8 使用需要在这个文件加上约束（ReverseRegex\Generator\Node）
     * 获取测试数据
     */
    public function testData()
    {
        function name($arr)
        {
            $first_names = ['罗', '梁', '宋', '唐', '许', '韩', '冯', '邓', '曹', '彭', '曾', '萧', '田', '董', '袁', '潘', '于', '蒋', '蔡', '余', '杜', '叶', '程', '苏', '魏', '吕', '丁', '任', '沈', '姚', '卢', '姜', '崔', '钟', '谭', '陆', '汪', '范', '金', '石', '廖', '贾', '夏', '韦', '付', '方', '白', '邹', '孟', '熊', '秦', '邱', '江', '尹', '薛', '闫', '段', '雷', '侯', '龙', '史', '陶', '黎', '贺', '顾', '毛', '郝', '龚', '邵', '万', '钱', '严', '覃', '武', '戴', '莫', '孔', '向', '汤'];
            $second_names = ['睿', '浩', '博', '瑞', '昊', '悦', '妍', '涵', '玥', '蕊', '子', '梓', '浩', '宇', '俊', '轩', '宇', '泽', '杰', '豪', '雨', '梓', '欣', '子', '思', '涵', '萱', '怡', '彤', '琪', '浩', '宇', '子', '轩', '浩', '然', '雨', '泽', '宇', '轩', '子', '涵', '欣', '怡', '子', '涵', '梓', '涵', '雨', '涵', '可', '馨', '诗', '涵', '颖', '灵', '睿', '锐', '哲', '慧', '敦', '迪', '明', '晓', '显', '悉', '晰', '维', '学', '思', '悟', '析', '文', '书', '勤', '俊', '威', '英', '健', '壮', '焕', '挺', '秀', '伟', '武', '雄', '巍', '松', '柏', '山', '石', '婵', '娟', '姣', '妯', '婷', '姿', '媚', '婉', '妩', '倩', '兰', '达', '耀', '兴', '荣', '华', '旺', '盈', '丰', '余', '昌', '盛', '乎', '安', '静', '顺', '通', '坦', '泰', '然', '宁', '定', '和', '康'];
            $first_name = $first_names[random_int(0, count($first_names) - 1)];
            $second_name = $second_names[$i = random_int(0, count($second_names) - 1)];
            $time = random_int(1, 2);
            if ($time === 2) {
                $ii = random_int(0, count($second_names) - 1);
                while ($ii === $i) {
                    $ii = random_int(0, count($second_names) - 1);
                }
                $second_name .= $second_names[$ii];
            }
            $arr[] = [
                'key' => '姓名',
                'value' => $first_name . $second_name,
            ];
            return $arr;
        }

        $resp = [];
        $arr = [
            '电话号码' => '^1((3[0-9])|(4[5-79])|(5[0-35-9])|(6[5-7])|(7[0-8])|(8[0-9])|(9[189]))[0-9]{8}$',
            '邮箱' => '^[a-z]{3,5}\d{3,5}@gmail\.com$',
            '银行卡号' => '^[1-9]\d{9,29}$',
            '车牌号' => '^[京津沪渝冀豫云辽黑湘皖鲁新苏浙赣鄂桂甘晋蒙陕吉闽贵粤青藏川宁琼使领][A-HJ-NP-Z][A-HJ-NP-Z0-9]{4}[A-HJ-NP-Z0-9挂学警港澳]$',
            '身份证号' => '^[1-9]\d{5}(18|19|20)\d{2}(0[1-9]|10|11|12)(0[1-9]|[1-2]\d|30|31)\d{3}[0-9Xx]$',
        ];
        $resp = name($resp);
        foreach ($arr as $key => $value) {
            $result = '';
            $lexer = new Lexer($value);
            $parser = new Parser($lexer, new Scope(), new Scope());
            $random = new MersenneRandom(random_int(PHP_INT_MIN, PHP_INT_MAX));
            $generator = $parser->parse()->getResult();
            $generator->generate($result, $random);
            $resp[] = [
                'key' => $key,
                'value' => $result,
            ];
        }
        $resp[] = [
            'key' => '当前时间',
            'value' => date('Y-m-d H:i:s'),
        ];
        return json(['code' => 0, 'data' => $resp]);
    }

    public function fixCompanySupId(): Json
    {
        $this->file_path = runtime_path() . DIRECTORY_SEPARATOR . 'tmp.txt';
        $data = file_get_contents('C:\Users\Administrator\Documents\demo.json');
        $data = json_decode($data, true)['RECORDS'];
        $data = array_column($data, null, 'id');
        $err_data = [];
        foreach ($data as $key => $value) {
            $tmp = [];
            $tmp = $this->dealSupId($data, $key, $tmp);
            $tmp = array_reverse($tmp);
            $ids = implode(',', $tmp);
            if ($ids !== $value['parent_ids']) {
                $sql = "UPDATE `cmm_pro`.`company` SET `parent_ids` = '{$ids}' WHERE `id` = {$key};";
                //                $err_data[$key] = [
                //                    $value['parent_ids'] => $ids
                //                ];
                file_put_contents($this->file_path, $sql . PHP_EOL, FILE_APPEND);
                $err_data[] = $sql;
            }

        }
        return json($err_data);
    }

    private function dealSupId($data, $id, $tmp)
    {
        if (!empty($data[$id]) && !empty($data[$id]['sup_id'])) {
            $tmp[] = $data[$id]['sup_id'];
            $tmp = $this->dealSupId($data, $data[$id]['sup_id'], $tmp);
        }
        return $tmp;
    }

    public function getKey(ToolService $toolService)
    {
        $this->file_path = runtime_path() . DIRECTORY_SEPARATOR . 'tmp.txt';
//        ini_set('memory_limit','4000M');
        $url = '';
        $cookie = '';
        $req = '';
        $key = '';

        $param = [
            'url' => $url,
            'cookie' => $cookie,
            'data' => [
                'req' => $req,
            ],
        ];
        $toolService->getSpecifyKeyData($param, $this->file_path, $key);
        return 'success';
    }

    public function getKeyByDate(ToolService $toolService)
    {
        ini_set('memory_limit', '4G');
        ini_set("max_execution_time", "30000");
        $this->file_path = runtime_path() . DIRECTORY_SEPARATOR . 'tmp.txt';
        $arr = [];
        for ($i = 1; $i <= 31; $i++) {
            dump("当前第{$i}次" . PHP_EOL);
            $start_time = date('Y-m-d H:i:s', mktime(0, 0, 0, 3, $i, 2024));
            $end_time = date('Y-m-d H:i:s', mktime(23, 59, 59, 3, $i, 2024));
            $url = '';
            $cookie = '';
            // [[">=","' . $start_time . '"],["<=","' . $end_time . '"]]
            $req = '';
            $key = 'od_id';
            $param = [
                'url' => $url,
                'cookie' => $cookie,
                'data' => [
                    'req' => $req,
                ],
            ];
            $toolService->getSpecifyKeyData($param, $this->file_path, $key);
            $keys = explode(',', file_get_contents($this->file_path));
            $arr = array_merge($arr, $keys);
        }
        file_put_contents($this->file_path, array_values(array_unique(array_filter($arr))));
        dd('执行完成');
    }

    public function testExportCsv()
    {
//        $data = $this->getData();
        $data = [
            [1, 'name', 'ffffff'],
            [2, 'name', 'ffffff'],
            [3, 'name', 'ffffff'],
        ];
        export_csv('test', ['id', 'name', 'create_time'], $data);
        die();
    }

    public function fixDepartmentId()
    {
        $this->file_path = runtime_path() . DIRECTORY_SEPARATOR . 'tmp.txt';
        //        ini_set('memory_limit','4000M');
//        $url = '';
//        $cookie = '';
//        $req = '';
        $url = '';
        $cookie = '';
        $req = '';

        $tmp = [];
        $param = [
            'url' => $url,
            'cookie' => $cookie,
            'data' => [
                'req' => $req,
            ],
        ];
        $data = test_curl('post', $param);
        $data = $data['res']['data'];
        foreach ($data as $val) {
            //            if (isset($tmp[$val['no'].'-'.$val['account_name_one'].'-'.$val['account_name_two'].'-'.$val['account_name_three'].'-'.$val['account_name_four'].'-'.$val['accrual'].'-'.$val['consignor_name']])) {
            if (isset($tmp[$val['no'] . '-' . $val['account_name_one'] . '-' . $val['account_name_two'] . '-' . $val['account_name_three'] . '-' . $val['account_name_four'] . '-' . $val['accrual']])) {
                //            if (isset($tmp[$val['no'].'-'.$val['account_name_one'].'-'.$val['account_name_two'].'-'.$val['accrual'].'-'.$val['consignor_name']])) {
                //            if (isset($tmp[$val['no'].'-'.$val['account_name_one'].'-'.$val['account_name_two'].'-'.$val['accrual']])) {
                //            if (isset($tmp[$val['doc_date']])) {
                jdd($val);
            }
            //            $tmp[$val['doc_date']] = [
            //            $tmp[$val['no'].'-'.$val['account_name_one'].'-'.$val['account_name_two'].'-'.$val['accrual']] = [
            //            $tmp[$val['no'].'-'.$val['account_name_one'].'-'.$val['account_name_two'].'-'.$val['accrual'].'-'.$val['consignor_name']] = [
            //            $tmp[$val['no'].'-'.$val['account_name_one'].'-'.$val['account_name_two'].'-'.$val['account_name_three'].'-'.$val['account_name_four'].'-'.$val['accrual'].'-'.$val['consignor_name']] = [
            $tmp[$val['no'] . '-' . $val['account_name_one'] . '-' . $val['account_name_two'] . '-' . $val['account_name_three'] . '-' . $val['account_name_four'] . '-' . $val['accrual']] = [
                'id' => $val['id'],
                'consignor_name' => $val['consignor_name'],
                'accrual' => $val['accrual'],
                'department_id' => $val['department_id'],
                'consignor_id' => $val['consignor_id'],
                'no' => $val['no'],
            ];
        }
        //        jdd(count($data));
        //        $f = file_get_contents($this->file_path); // 分录只能查1000条，这样多次查询
        //        $f = json_decode($f,true);
        //        $tmp = array_merge($tmp,$f);
        //        jdd(count($tmp));
        file_put_contents($this->file_path, json_encode($tmp, 256));
        return 'success';
    }

    public function getErrorTraceId()
    {
        ini_set("max_execution_time", "30000");
        ini_set("memory_limit", "4024M");

        $start = time();
        $i = 1;
        Db::connect('local_monitor')
            ->table('error_trace')->field('id,get,post')->json(['get', 'post'])
            ->whereRaw("create_time > '2024-04-01 00:00:00'")
            ->chunk(500, function ($res) use (&$i) {
                $tmp = [];
                foreach ($res as $row) {
                    if (!empty($row['get'])) {
                        $tmp = $this->handle($row['get'], $tmp);
                    }
                    if (!empty($row['post'])) {
                        $tmp = $this->handle($row['post'], $tmp);
                    }
                }
                foreach ($tmp as $table => $item) {
                    if (empty(array_filter($item))) {
                        continue;
                    }
                    file_put_contents(runtime_path() . DIRECTORY_SEPARATOR . 'id' . DIRECTORY_SEPARATOR . "{$table}-{$i}.txt", implode(',', array_unique(array_filter($item))));
                }
                $i++;
            }, 'id');
        return json(['耗时：' => (time() - $start) . 's', '内存使用：' => memory_get_usage() / 1024 / 1024 . 'M']);
    }

    private function handle($get, $tmp)
    {
        if (!empty($get['pk_groups'])) {
            $pk = json_decode($get['pk_groups'], true);
            foreach ($pk as $category => $value) {
                foreach ($value as $table => $ids) {
                    $get[$table] = $ids;
                    if (empty($tmp[$table])) {
                        $tmp[$table] = $ids;
                    } else {
                        $tmp[$table] = array_merge($tmp[$table], $ids);
                    }
                }
            }
        } elseif (!empty($get['ids'])) {
            if (empty($tmp[$get['table']])) {
                $tmp[$get['table']] = array_map('intval', explode(',', $get['ids']));
            } else {
                $tmp[$get['table']] = array_merge($tmp[$get['table']], array_map('intval', explode(',', $get['ids'])));
            }
        }
        return $tmp;
    }

    /*
     * 一次查询，对于耗时特长的查询来说，没什么用
     */
    public function getErrorTraceId2()
    {
        ini_set("max_execution_time", "30000");
        ini_set("memory_limit", "4024M");

        $start = time();
        $cursor = Db::connect('local_monitor')->table('error_trace')
            ->field('id,get,post')
            ->whereRaw("create_time > '2024-02-01 14:30:00' and hostname = 'iZ2ze5vp3vkkcdoirik586Z' and create_time < '2024-02-02 08:30:00'")
            ->cursor();
        $tmp = [];
        foreach ($cursor as $row) {
            if (!empty($row['get']) && $row['get'] !== "[]") {
                $tmp = $this->handle(json_decode($row['get'], true), $tmp);
            }
            if (!empty($row['post']) && $row['post'] !== "[]") {
                $tmp = $this->handle(json_decode($row['post'], true), $tmp);
            }
        }
        foreach ($tmp as $table => $item) {
            if (empty(array_filter($item))) {
                continue;
            }
            file_put_contents(runtime_path() . DIRECTORY_SEPARATOR . 'ids' . DIRECTORY_SEPARATOR . "{$table}.txt", implode(',', array_unique(array_filter($item))));
        }
        return json(['耗时：' => (time() - $start) . 's', '内存使用：' => memory_get_usage() / 1024 / 1024 . 'M']);
    }

    /**
     * 合并指定目录中特定前缀文件的id至一个文件
     * @return void
     */
    public function mergeId(): void
    {
        ini_set("max_execution_time", "30000");
        ini_set("memory_limit", "4024M");
        $path = runtime_path() . DIRECTORY_SEPARATOR . 'id';
        //        $path = 'C:\Users\Administrator\Downloads\thinkphp_3.2.4\Application\Runtime';
        $prefix = 'ac_apply';
        $arr = [];
        if ($handle = opendir($path)) {
            while (false !== ($file = readdir($handle))) {
                if ($file !== "." && $file !== "..") {
                    if ($file === $prefix . '_total.txt') {
                        continue;
                    }
                    if (Str::startsWith($file, $prefix)) {
                        $file = $path . DIRECTORY_SEPARATOR . $file;
                        $arr = array_filter(array_unique(array_merge(explode(",", file_get_contents($file)), $arr)));
                    }
                }
            }
            closedir($handle);
        }
        file_put_contents($path . DIRECTORY_SEPARATOR . $prefix . '_total.txt', implode(',', $arr));
        jdd('success');
    }

    public function es($option, $environment)
    {
        switch ($environment) {
            case 'gamma8008':
                $uri = 'http://gamma.vkj56.cn:8008/api/Table/Tools/searchByOption?';
                break;
            case 'gamma8009':
                $uri = 'http://gamma.vkj56.cn:8009/api/Table/Tools/searchByOption?';
                break;
            case '306':
                $uri = 'http://w-sas-1000-web-alpha-01.vkj56.cn:306/api/Table/Tools/searchByOption?';
                break;
            default:
                return json('error');
        }
        $option = decode_json($option);
        if (empty($option) || !is_array($option)) {
            return json('error');
        }
        $option = json_encode($option, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
        $options = ['proxy' => '127.0.0.1:3456'];
        $response = Requests::get($uri . http_build_query(['option' => $option]), [], []);
        return json($response->decode_body());
    }

    public function es2()
    {
        $option = [
            'data_name' => 'Order',
            "query" => [
                'od_link_id' => 422826070,
            ],
//            "query" => [
//                [
//                    'superior.com_id' => [12495],
//                    'cor_com' => '周乐+双叶文具',
//                ],
//                ['group_id' => 1000]
//            ],
//            "filter" => [
//                'is_deserted' => 0,
//                'order_status' => 1,
////                'b_tr_state' => 0,
//                'billing_date' => [
//                    ['>=','2024-01-01 00:00:00'],
//                    ['<=','2024-01-31 23:59:59'],
//                ],
//            ],
//                'sort' => ["ol_create_time" => "desc"],
            'page_num' => 1,
            'page_size' => 100,
//            'fields' => [
//                'order_num','arr_point','mgr_name'
//            ],
        ];
        $uri = 'http://gamma.vkj56.cn:8009/api/Table/Tools/searchByOption?';
        //        $uri = 'http://w-sas-1000-web-alpha-00.vkj56.cn:3333/api/Table/Tools/searchByOption?';
        $option = json_encode($option, JSON_UNESCAPED_UNICODE);
        //        jdd($uri.http_build_query(['option' => $option]));;
        $headers = [
//            'cookie' => 'XDEBUG_SESSION=PHPSTORM;',
        ];
        $response = Requests::get($uri . http_build_query(['option' => $option]), $headers, []);
        return json($response->decode_body());
//        $data = $response->decode_body();
//        return json(array_unique(array_column($data['data_list'], 'order_num')));
    }

    public function upSingleV3()
    {
        if (PHP_SAPI !== 'cli') {
            exit('仅可cli执行');
        }
        $number = 100;  //最大线程数
        $process_count = 0;
        $this->file_path = runtime_path() . DIRECTORY_SEPARATOR . 'tmp.txt';
        $od_ids = explode(',', file_get_contents($this->file_path));
//        $uri = 'http://gamma.vkj56.cn:8008/api/Finance/DoData/upSingleV2?od_id=';
        $uri = 'http://w-sas-1000-web-alpha-00.vkj56.cn:306/api/Finance/DoData/upSingleV2?od_id=';
        $od_ids = array_chunk($od_ids, 1000);
        $child_processes = array();
        $j = 1;
        foreach ($od_ids as $od_id) {
            $pid = pcntl_fork();
            if ($pid == -1) {
                die('fork failed');
            }
            if ($pid == 0) {
                $count = count($od_id);
                $i = 1;
                foreach ($od_id as $id) {
                    $response = Requests::get($uri . $id, [], []);
                    $i++;
                    $p = round($i / $count, 3) * 100;
                    dump("当前进程{$j} 操作od_id:{$id}    " . $response->body . "        ---------process {$p}%");
                }
                exit;
            } else {
                $process_count++;
                $j++;
                if ($process_count >= $number) {
                    pcntl_wait($status);
                    $process_count--;
                }
                $child_processes[] = $pid;
            }
        }

        foreach ($child_processes as $pid) {
            pcntl_waitpid($pid, $status);
        }

        dd('执行完成');
    }

    public function upSingleV2()
    {
        ini_set("max_execution_time", "30000");
        $this->file_path = runtime_path() . DIRECTORY_SEPARATOR . 'tmp.txt';
        $od_ids = explode(',', file_get_contents($this->file_path));
//        $od_ids = [166030991];
        $count = count($od_ids);
        $uri = 'http://gamma.vkj56.cn:8008/api/Finance/DoData/upSingleV2?od_id=';
//        $uri = 'http://localhost:9018/api/Finance/DoData/upSingleV2?od_id=';
        $i = 1;
        foreach ($od_ids as $od_id) {
            $response = Requests::get($uri . $od_id, [], []);
            $p = round($i / $count, 3) * 100;
            dump("当前操作od_id:{$od_id}    " . $response->body . "        ---------process {$p}%");
            $i++;
        }
        dd('执行完成');
    }

    public function v2Roll()
    {
        ini_set("max_execution_time", "30000");
        $this->file_path = runtime_path() . DIRECTORY_SEPARATOR . 'tmp.txt';
//        $od_ids = explode(',', file_get_contents($this->file_path));
//        $tr_ids = explode(',', file_get_contents(runtime_path().DIRECTORY_SEPARATOR.'tmp2.txt'));
        $od_ids = [165126067, 164797128, 165077027, 165009228];
        $tr_ids = [442453988, 442453989, 442444783, 442444784, 442431250, 442431251, 442427512, 442427513];
        $count = count($od_ids);
//        $uri = 'http://gamma.vkj56.cn:8008/api/Finance/DoData/upSingleV2?od_id=';
        $uri = 'http://localhost:9018/api/Finance/DoData/v2Roll?od_id=';
        $i = 1;
        foreach ($od_ids as $od_id) {
            dd($uri . $od_id . '&tr_ids=' . implode(',', $tr_ids));
            $response = Requests::get($uri . $od_id . '&tr_ids=' . implode(',', $tr_ids), [], []);
            $p = round($i / $count, 3) * 100;
            dump("当前操作od_id:{$od_id}    " . $response->body . "        ---------process {$p}%");
            $i++;
        }
        dd('执行完成');
    }

    public function compare(ToolService $toolService): Json
    {
        ini_set('memory_limit', '4G');
        ini_set("max_execution_time", "30000");
        $path1 = runtime_path() . DIRECTORY_SEPARATOR . 'tmp.txt';
        $path2 = runtime_path() . DIRECTORY_SEPARATOR . 'tmp2.txt';
        $tmp = [];
        for ($i = 1; $i <= 30; $i++) {
            dump("当前第{$i}次" . PHP_EOL);
            $start_time = date('Y-m-d H:i:s', mktime(0, 0, 0, 4, $i, 2024));
            $end_time = date('Y-m-d H:i:s', mktime(23, 59, 59, 4, $i, 2024));
            // 资金流水
            $uri = '';
            $headers = [
                'cookie' => '',
            ];
            $data = [
                'req' => '',
            ];
            $toolService->getSpecifyKeyDataCopy($uri, $headers, $data, $path1, 'Order|order_num');
            // 交易记录
            $uri = '';
            $headers = [
                'cookie' => '',
            ];
            $data = [
                // [[">=","' . $start_time . '"],["<=","' . $end_time . '"]]
                'req' => '',
            ];
            $toolService->getSpecifyKeyDataCopySpec($uri, $headers, $data, $path2, 'conn_info');
            $order_num = explode(',', file_get_contents($path1));
            $now_order_num = explode(',', file_get_contents($path2));
//            $now_order_num = explode(',', file_get_contents($path1)); // 资金流水重复划拨
//            $order_num = explode(',', file_get_contents($path2));
//            dump(count($now_order_num));
//            dump(count($order_num));
//            dd(array_diff($order_num,$now_order_num));
            if (empty($order_num)) Log::write($i . '$order_num empty');
            foreach ($order_num as $t) {
                foreach ($now_order_num as $k => $v) {
                    if ($t == $v) {
                        unset($now_order_num[$k]);
                        continue 2;
                    }
                }
            }
            Log::write($i . '  ' . json_encode(array_unique(array_values(array_filter($now_order_num))), 256));
            $tmp = array_merge($tmp, array_values($now_order_num));
        }
        Log::write(json_encode(array_values($tmp), 256));
        return json(array_values(array_unique(array_filter($tmp))));
    }

    // 查找缺少凭证的资金流水
    public function compare2(ToolService $toolService)
    {
        ini_set('memory_limit', '4G');
        ini_set("max_execution_time", "30000");
        for ($i = 1; $i <= 30; $i++) {
            dump("当前第{$i}次");
            $start_time = date('Y-m-d H:i:s', mktime(0, 0, 0, 4, $i, 2024));
            $end_time = date('Y-m-d H:i:s', mktime(23, 59, 59, 4, $i, 2024));
            // 资金流水
            $uri = '';
            $headers = [
                'cookie' => '',
            ];
            $data = [
                // [[">=","'.$start_time.'"],["<=","'.$end_time.'"]]
                'req' => '',
            ];
            $toolService->docDateCompare($uri, $headers, $data);
            Log::write($i . "页");
        }
        dd('执行完成');
    }

    public function updateAccrual()
    {
        $this->file_path = runtime_path() . DIRECTORY_SEPARATOR . 'tmp.txt';
        $arr = file_get_contents($this->file_path);
        $arr = array_values(array_filter(explode(',', $arr)));
        $tmp = array_chunk($arr, 10);
        $uri = 'http://yundan.vkj56.cn/api/Accounts/DoData/updateAccrual?doc_ids=';
        $i = 1;
        $count = count($tmp);
        foreach ($tmp as $v) {
            $response = Requests::get($uri . implode(',', $v), [], []);
            Log::write($response->body);
            $process = round($i / $count * 100, 2);
            dump($response->body. '  --- 进度 ' .$process. ' %');
            $i++;
        }
        return 'success';
    }

    public function batchInfo()
    {
        $uri = '';
        $headers = [
            'cookie' => '',
        ];
        $data = [
            'req' => '',
        ];
        $options = [];
        $response = Requests::post($uri, $headers, $data, $options);

        $data = $response->decode_body()['res']['data'];
        $tmp = [];
        foreach ($data as $v) {
            $tmp[] = [
                'b_arr_f' => $v['b_arr_f'],
                'car_batch' => $v['car_batch'],
                'b_basic_id' => $v['b_basic_id'],
                'b_link_id' => $v['b_link_id'],
            ];
        }
        file_put_contents(runtime_path() . DIRECTORY_SEPARATOR . 'tmp.txt', json_encode($tmp, 256));
        return json('success');
    }

    public function fixBatchSettle()
    {
        $this->file_path = runtime_path() . DIRECTORY_SEPARATOR . 'tmp.txt';
        $arr = file_get_contents($this->file_path);
        $data = json_decode($arr, true);
        foreach ($data as $v) {
            $context = [
                "finance_expense" => "b_arr_f",
                "finance_expense_text" => "到付运输费",
                "finance_amount" => $v['b_arr_f'],
                "finance_time" => "2023-11-30 16:16:24",
                "finance_remark" => "",
                "finance_type" => "ft_settle",
                "car_batch" => $v['car_batch'],
                "finance_bill_no" => "JS202312090030",
                "finance_bill_id" => 110945107,
                "login_info" => [
                    "is_switch" => true,
                    "is_jump" => null,
                    "ori_group_id" => "1000",
                    "ori_company_id" => "62046",
                    "ori_user_id" => "129002"
                ]
            ];
            $context = json_encode($context, 256);
            $sql = "INSERT INTO `cmm_pro`.`log` (`parent_log_id`, `type`, `od_basic_id`, `od_id`, `od_link_id`, `b_basic_id`, `b_link_id`, `doc_id`, `op_group_id`, `op_com_id`, `op_com_name`, `op_user_id`, `op_user_name`, `content`, `ext_flags`, `status`, `create_time`, `update_time`, `create_by`, `update_by`, `f_com_id`, `ext`) VALUES (NULL, '600', NULL, NULL, NULL, {$v['b_basic_id']}, {$v['b_link_id']}, NULL, 1000, 1976, '临沂子公司', 129002, '龚芳芳', '{$context}', 1, 1, '2023-12-09 16:30:05', '2023-12-09 16:30:05', 129002, 129002, 26709, '[]');" . PHP_EOL;
            file_put_contents(runtime_path() . DIRECTORY_SEPARATOR . 'tmp.sql', $sql, FILE_APPEND);
        }
    }

    public function testPlainSearch()
    {
        $option = [
            'index' => 'user',
            'page_num' => 0,
            'page_size' => 20,
            'fields' => ['name', 'password'],
//            'sort' => ['id' => 'desc'],
//            'query' => ['name' => 'test'],
//            'aggregate_size' => 1000,
//            'aggregates' => ['id'=>[ELASTIC_SEARCH::AGGREGATE_COUNT,'id']],
//            'distinct' => ['name' => 30],
        ];
        $arr = (new SearchService)->plainSearch($option);
        return json($arr);
    }

    public function testUpload(): View|Json
    {
        try {
            if (!isset($_FILES['file']['error']) || is_array($_FILES['file']['error'])) {
                throw new RuntimeException('Invalid parameters.');
            }
            switch ($_FILES['file']['error']) {
                case UPLOAD_ERR_OK:
                    break;
                case UPLOAD_ERR_NO_FILE:
                    throw new RuntimeException('No file sent.');
                case UPLOAD_ERR_INI_SIZE:
                case UPLOAD_ERR_FORM_SIZE:
                    throw new RuntimeException('Exceeded filesize limit.');
                default:
                    throw new RuntimeException('Unknown errors.');
            }
            if ($_FILES['file']['size'] > 1000000) {
                throw new RuntimeException('Exceeded filesize limit.');
            }
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            if (false === $ext = array_search(
                    $finfo->file($_FILES['file']['tmp_name']),
                    array(
                        'jpg' => 'image/jpeg',
                        'png' => 'image/png',
                        'gif' => 'image/gif',
                    ),
                    true
                )) {
                throw new RuntimeException('Invalid file format.');
            }

            if (!move_uploaded_file(
                $_FILES['file']['tmp_name'],
                sprintf('/mnt/e/test/tmp/%s.%s', sha1_file($_FILES['file']['tmp_name']), $ext)
            )) {
                throw new RuntimeException('Failed to move uploaded file.');
            }

            return doResponse(ERRNO::SUCCESS, ERRNO::e(ERRNO::SUCCESS), []);
        } catch (RuntimeException $e) {
            return doResponse(ERRNO::UPLOAD_FAIL, ERRNO::e($e->getMessage()), []);
        }
    }

    private function getData(): \Generator
    {
        $id = 1;
        do {
            $data = Db::name('user')->where('id', '=', $id)->limit(1)->field('id,name,create_time')->select();
            $id++;
            if (isset($data[0])) {
                yield $data[0];
            }
            if ($id > 10) {
                break;
            }
        } while (!empty($data));
    }

    // 查询财务记录凭证缺少科目的运单
    public function docForOrder()
    {
        $order_data = file_get_contents(runtime_path() . DIRECTORY_SEPARATOR . 'tmp.txt');
        $order_data = json_decode($order_data, true);

        $err = [];
        $succ = [];
        foreach ($order_data as $com_name => $item) {
            foreach ($item as $order_num) {
                $o_d = Db::connect('pro_order')->table('od')
                    ->field('id,od_basic_id')
                    ->whereRaw("group_id = 1000 and order_num = '{$order_num}'")
                    ->fetchSql(false)->select()->toArray();
                $log_d = Db::connect('pro_log')->table('log')
                    ->field('id,doc_id,content')
                    ->whereRaw("od_basic_id = '{$o_d[0]['od_basic_id']}' and type = 614")
                    ->order('id', 'desc')
                    ->fetchSql(false)->select()->toArray();
                $doc_id = 0;
                foreach ($log_d as $log_data) {
                    $content = json_decode($log_data['content'], true);
                    if ($content['finance_expense'] === 'pay_arrival') {
                        $doc_id = $log_data['doc_id'];
                        break;
                    }
                }
                if ($doc_id == 0) {
                    dd('no doc ' . $order_num);
                }
                $log_d = Db::connect('pro_finance')->table('ac_doc_detail')
                    ->field('id')
                    ->whereRaw("record_id = '{$doc_id}' and account_detail_id = 291720")
                    ->fetchSql(false)->select()->toArray();
                if (empty($log_d)) {
                    $err[$com_name][] = $order_num;
                } else {
                    $succ[$com_name][] = $order_num;
                }
            }
        }
        dump($err);
        dump($succ);
        file_put_contents(runtime_path() . DIRECTORY_SEPARATOR . 'tmp1.txt', json_encode($err));
        file_put_contents(runtime_path() . DIRECTORY_SEPARATOR . 'tmp2.txt', json_encode($succ));
    }

    /**
     * @noinspection ForgottenDebugOutputInspection
     * @see https://www.php.net/manual/zh/session.configuration.php#ini.session.gc-maxlifetime
     * session.gc_probability / session.gc_divisor  所得的值为每个请求中有 ？% 的概率启动 gc 进程
     * session.gc_maxlifetime 指定session过期时间，默认为 1440（24分钟）秒
     */
    public function session(): void
    {
        session_cache_limiter('private'); // Cache-Control https://juejin.cn/post/7282692458247962676
        session_cache_expire(); // 并不是session过期时间，给浏览器的缓冲会话页面的存活期
        dump(ini_set('session.gc_probability', 1));
        dump(ini_set('session.gc_divisor', 1));
        dump(ini_set('session.gc_maxlifetime', 10)); // 10秒后的第一次请求接收会运行gc，第二次访问session才为空
        session_start();
        session(); // 这是框架用来设置缓存，缓存使用$_SESSION
        dump(ini_get('session.gc_probability'));
        dump(ini_get('session.gc_divisor'));
        dump(ini_get('session.gc_maxlifetime'));
        if (isset($_SESSION['id'])) {
            dump('当前session未过期');
            dump($_SESSION);
        } else {
            $_SESSION['id'] = 1;
            dump('session已过期，已重新分配session_id:' . session_id());
        }
    }

    // 触发pdd订阅
    public function pdd(): void
    {
        $this->file_path = runtime_path() . DIRECTORY_SEPARATOR . 'tmp.txt';
        $order_nums = explode(',', file_get_contents($this->file_path));
        $order_nums = ['Y24100156132'];
        foreach ($order_nums as $order_num) {
            $data = [
                "trackingOrderNo" => $order_num,
                "trackingNumber" => $order_num
            ];
            $req = [
                "client_id" => "e7c59e4899fa46a18f256027c7e943d1",
                "data" => encode_json($data),
                "from_client_id" => "edb65ef927c14c7bb0d4b86981de48ce",
                "ship_id" => "427",
                "timestamp" => time(),
                "type" => "pdd.logistics.co.track.sub",
            ];
            $str = '';
            foreach($req as $key => $value) {
                $str = $str.$key.$value;
            }
            $secret = 'fc7178f88b85b29b34415d5ce82fe73006e6db88';
            $str = $secret.$str.$secret;
            $req['sign'] = strtoupper(md5($str));

            $uri = env('YUN_DAN_PROD') . "/api/OpenApi/Pinduoduo/trackSub";
            $response = Requests::post($uri, [], encode_json($req), []);
            dump([$order_num, $response->decode_body()]);
        }
    }

    public function pddSearch(): Json
    {
        $order_num = 'HHSJ24110016';
        $data = [
            "trackingOrderNo" => $order_num,
            "trackingNumber" => $order_num
        ];
        $req = [
            "client_id" => "e7c59e4899fa46a18f256027c7e943d1",
            "data" => encode_json($data),
            "from_client_id" => "edb65ef927c14c7bb0d4b86981de48ce",
            "ship_id" => "427",
            "timestamp" => time(),
            "type" => "pdd.logistics.co.track.query",
        ];
        $str = '';
        foreach($req as $key => $value) {
            $str = $str.$key.$value;
        }
        $secret = 'fc7178f88b85b29b34415d5ce82fe73006e6db88';
        $str = $secret.$str.$secret;
        $req['sign'] = strtoupper(md5($str));

        $uri = env('YUN_DAN_ALPHA') . '/api/OpenApi/Pinduoduo/trackQuery';
        $response = Requests::post($uri, [], encode_json($req), []);
        return json($response->decode_body());
    }

    // 请求接口获取数据处理
    public function handleData(ToolService $toolService)
    {
        $res = [];
        $url = 'https://yundan.vkj56.cn/api/Table/Search/orderList?logid=12856401733983461490&gid=2024&btnLoadingTag=off';
        $cookie = 'PHPSESSID=6be15af20409ae893c542b86e83d65f0; Hm_lvt_f59ed1ad07a4a48a248b87fac4f62903=1731889078; 124566%7C62044%7C1000%7ClastHandleTime=1733118528719; 124566%7C62044%7C1000%7CisTimeoutLock=1; HMACCOUNT=0D1168C52A766A04; 128564%7C62619%7C2024%7CisTimeoutLock=; user_id=128564; group_id=2024; company_id=61954; Hm_lpvt_f59ed1ad07a4a48a248b87fac4f62903=1733982578; 128564%7C62619%7C2024%7ClastHandleTime=1733983448023';
        $req = '{"category":"Order","tab":"co","sort":{},"page_num":1,"page_size":1000,"fetch_mode":"body","cid":"","query":{"9999":{"_logic":"or","query_num._exact_":["Y24090053894","Y24090053891","Y24090087609","Y24090087607","Y24090087422","Y24090087418","Y24090087401","Y24090087395","Y24090119542","Y24090119483","Y24090119463","Y24090119460","Y24090088059","Y24090152071","Y24090152056","Y24090152048","Y24090152004","Y24090151982","Y24090151426","Y24090186809","Y24090186820","Y24090186748","Y24090218944","Y24090218892","Y24090190392","Y24090187367","Y24090187365","Y24090275184","Y24090274800","Y24090274189","Y24090307909","Y24090340709","Y24090340519","Y24090308089","Y24090308086","Y24090308085","Y24090308044","Y24090308042","Y24090374779","Y24090374501","Y24090374496","Y24090374467","Y24090374459","Y24090374417","Y24090341171","Y24090341169","Y24090341166","Y24090341057","Y24090341049","Y24090407633","Y24090407626","Y24090407209","Y24090407200","Y24090407040","Y24090407023","Y24090406981","Y24090406979","Y24090375286","Y24090439574","Y24090439568","Y24090439530","Y24090439519","Y24090439014","Y24090438890","Y24090438817","Y24090469554","Y24090487589","Y24090469943","Y24090469930","Y24090469828","Y24090469827","Y24090492002","Y24090526571","Y24090526561","Y24090525383","Y24090525351","Y24090559561","Y24090559495","Y24090559493","Y24090559492","Y24090590367","Y24090590309","Y24090590306","Y24090590304","Y24090590183","Y24090590181","Y24090590121","Y24090590118","Y24090636731","Y24090624288","Y24090624287","Y24090624109","Y24090683407","Y24090683404","Y24090718671","Y24090718633","Y24090718631","Y24090718627","Y24090718625","Y24090718622","Y24090683767","Y24090753568","Y24090753487","Y24090753412","Y24090718989","Y24090718986","Y24090718979","Y24090718974","Y24090718874","Y24090788675","Y24090782861","Y24090782807","Y24090823803","Y24090823782","Y24090822502","Y24090822417","Y24090822131","Y24090789498","Y24090789478","Y24090789370","Y24090789367","Y24090789366","Y24090857736","Y24090857701","Y24090857695","Y24090857693","Y24090857690","Y24090857684","Y24090857679","Y24090857672","Y24090857298","Y24090857018","Y24090856993","Y24090856889","Y24090856886","Y24090891782","Y24090891563","Y24090890954","Y24090920289","Y24090920265","Y24090374226"],"order_num._exact_":["Y24090053894","Y24090053891","Y24090087609","Y24090087607","Y24090087422","Y24090087418","Y24090087401","Y24090087395","Y24090119542","Y24090119483","Y24090119463","Y24090119460","Y24090088059","Y24090152071","Y24090152056","Y24090152048","Y24090152004","Y24090151982","Y24090151426","Y24090186809","Y24090186820","Y24090186748","Y24090218944","Y24090218892","Y24090190392","Y24090187367","Y24090187365","Y24090275184","Y24090274800","Y24090274189","Y24090307909","Y24090340709","Y24090340519","Y24090308089","Y24090308086","Y24090308085","Y24090308044","Y24090308042","Y24090374779","Y24090374501","Y24090374496","Y24090374467","Y24090374459","Y24090374417","Y24090341171","Y24090341169","Y24090341166","Y24090341057","Y24090341049","Y24090407633","Y24090407626","Y24090407209","Y24090407200","Y24090407040","Y24090407023","Y24090406981","Y24090406979","Y24090375286","Y24090439574","Y24090439568","Y24090439530","Y24090439519","Y24090439014","Y24090438890","Y24090438817","Y24090469554","Y24090487589","Y24090469943","Y24090469930","Y24090469828","Y24090469827","Y24090492002","Y24090526571","Y24090526561","Y24090525383","Y24090525351","Y24090559561","Y24090559495","Y24090559493","Y24090559492","Y24090590367","Y24090590309","Y24090590306","Y24090590304","Y24090590183","Y24090590181","Y24090590121","Y24090590118","Y24090636731","Y24090624288","Y24090624287","Y24090624109","Y24090683407","Y24090683404","Y24090718671","Y24090718633","Y24090718631","Y24090718627","Y24090718625","Y24090718622","Y24090683767","Y24090753568","Y24090753487","Y24090753412","Y24090718989","Y24090718986","Y24090718979","Y24090718974","Y24090718874","Y24090788675","Y24090782861","Y24090782807","Y24090823803","Y24090823782","Y24090822502","Y24090822417","Y24090822131","Y24090789498","Y24090789478","Y24090789370","Y24090789367","Y24090789366","Y24090857736","Y24090857701","Y24090857695","Y24090857693","Y24090857690","Y24090857684","Y24090857679","Y24090857672","Y24090857298","Y24090857018","Y24090856993","Y24090856889","Y24090856886","Y24090891782","Y24090891563","Y24090890954","Y24090920289","Y24090920265","Y24090374226"]}},"filter":{},"batch_search_order_by":["query_num","order_num"]}';

        $param = [
            'url' => $url,
            'cookie' => $cookie,
            'data' => [
                'req' => $req,
            ],
        ];
        $data = test_curl('post', $param);
        foreach ($data['res']['data'] as $v) {
            $res[] = [
                'od_link_id' => $v['od_link_id'],
                'od_basic_id' => $v['od_basic_id'],
            ];
        }
        return json($res);
    }

    // 保存系统设置
    public function setting()
    {
        $uri = '';

        $headers = [
            'cookie' => '',
        ];
        $data = [
            'req' => '',
        ];
        $options = [];
        $response = Requests::post($uri, $headers, $data, $options);
        return json($response->decode_body());
    }

    // 触发拼多多推送
    public function pddPush(): void
    {
        $order_nums = ['Y24110583260','Y24100802713','Y24100373436','Y24110899939','24110138318','Y24110607127','Y24120116261','Y24100260525','Y24110850346','Y24110349021','Y24110064293','Y24110746870','Y24110278253','Y24110661716','Y24110604231','Y24110707142','Y24110034658','Y24110163588','Y24110902930','Y24110792106','Y24100506480','Y24100373450','Y24100330662','Y24110127726','Y24100523450','Y24100553941','Y24110670286','Y24100646352','Y24110465715','Y24110557470','Y24110114715','Y24110745445','Y24100469355','Y24110844535','Y24100571119','Y24110031089','Y24110798802','Y24100840654','Y24110733205','Y24100370128','Y24110591936','Y24110498126','142063050','Y24110662607','Y24110564495','Y24110250207','Y24100414071','Y24100812629','Y24110867447','Y24110399246','Y24120024972','Y24100772027','Y24110058911','Y24110090525','Y24110235080','Y24110211375','Y24100611644','Y24110707142','Y24110559920','Y24100500281','24100301265','Y24110787755','Y24100203047','Y24110701477','Y24100836977','Y24110159415','Y24110517972','Y24120023529','Y24110583250','Y24100239696','Y24100582932','Y24100310437','Y24110783387','Y24100736127','Y24110429519','Y24110611682','Y24100469355','Y24110648714','Y24110879258','Y24110250207','Y24100540663','Y24110156944','Y24110836074','Y24110448021','Y24110747081','Y24110163588','Y24100373436','Y24100573186','Y24100122835','Y24110158242','Y24110175660','Y24100869785','Y24110544195','Y24110521167','Y24110430527','Y24100468998','Y24100187489','Y24110774493','Y24100555329','Y24100188157','Y24110737147','Y24110611671','Y24100354898','Y24100792122','Y24110667005','Y24100737995','Y24100480759','Y24100668534','Y24100365950','Y24110052286','Y24100802713','Y24110644055','Y24110122713','4217929','Y24100840650','Y24110029168','Y24110741719','Y24110596224','Y24120147519','4211583','Y24110652880','Y24100510836','Y24100875796','Y24120026782','Y24110116005','Y24110603368','Y24110784085','Y24110436277','Y24110072689','Y24120035209','Y24110662659','Y24110542545','Y24110100894','Y24110695168','4217982','Y24110864837'];
        foreach ($order_nums as $order_num) {
            $uri = env('YUN_DAN_GAMMA8009') . "/api/OpenApi/Pinduoduo/trackRPush?order_num=$order_num";
            $response = Requests::get($uri, [], []);
            dump([$order_num, $response->decode_body()]);
        }
    }

    // 触发京东推送
    public function jdPush(): void
    {
        $data = [
            ["id" => 883850, "group_id" => 8866, "waybill_code" => "Y987898888981", "order_id" => "300683322139", "type" => 1, "create_time" => "2024-12-12 10:16:50", "update_time" => "2024-12-12 10:16:50"],
        ];
        foreach ($data as $val) {
            $order_num = $val['waybill_code'];
            $order_id = $val['order_id'];
            $group_id = 2024;
            $uri = env('YUN_DAN_GAMMA8009') . "/api/OpenApi/JingDong/JDPush?order_num=$order_num&group_id=$group_id&order_id=$order_id";
            $response = Requests::get($uri, [], []);
            dump([$order_num, $response->decode_body()]);
        }
    }

    // 模拟京东订阅
    public function jd(): void
    {
        $data = [
            "list" => [
                [
                    "logisticsProviderCode" => "SZWL",
                    "waybillCode" => "S020AAN124120127",
                    "orderId" => "1111"
                ]
            ]
        ];
        $req = ['logistics_info'=>urlencode(encode_json($data))];
        $uri = env('YUN_DAN_ALPHA') . '/api/OpenApi/JingDong/subscribe';
        $response = Requests::post($uri, ['cookie'=>'XDEBUG_SESSION=PHPSTORM'], $req, ['timeout' => 120]);
        dump($response->decode_body());
    }

    // 模拟京东查询
    public function jdQuery(): void
    {
        $data = [
            "list" => [
                [
                    "logisticsProviderCode" => "SZWL",
                    "waybillCode" => "S020AAN124080040",
                    "orderId" => "1111"
                ]
            ]
        ];
        $req = ['logistics_info'=>urlencode(encode_json($data))];
        $uri = env('YUN_DAN_ALPHA') . '/api/OpenApi/JingDong/queryOrder';
        $response = Requests::post($uri, ['cookie'=>'XDEBUG_SESSION=PHPSTORM'], $req, ['timeout' => 120]);
        dump($response->decode_body());
    }

    public function debug(): void
    {
        $uri = env('YUN_DAN_DEBUG') . '/api/Finance/DoData/test';
        $response = Requests::post($uri, ['cookie'=>'XDEBUG_SESSION=PHPSTORM'], [], ['timeout' => 120]);
        dump($response->decode_body());
    }
}
