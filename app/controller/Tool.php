<?php

namespace app\controller;

use app\BaseController;
use app\cnsts\ELASTIC_SEARCH;
use app\service\SearchService;
use app\service\ToolService;
use ReverseRegex\Generator\Scope;
use ReverseRegex\Lexer;
use ReverseRegex\Parser;
use ReverseRegex\Random\MersenneRandom;
use think\facade\Db;
use think\facade\Log;
use think\helper\Str;
use WpOrg\Requests\Requests;

class Tool extends BaseController
{
    private string $file_path = '';

    public function hello($name = 'ThinkPHP8')
    {
        event('Test');
        return json('hello,' . $name);
    }

    public function testPcntl() {
        if (PHP_SAPI !== 'cli') {
            exit('仅可cli执行');
        }
        $number = 100;  //最大线程数
        $process_count = 0;
        $this->file_path = runtime_path().DIRECTORY_SEPARATOR.'tmp.txt';
        $od_ids = explode(',', file_get_contents($this->file_path));
        $uri = 'http://test.cn:8080/api/DoData/upSing?id=';
        $od_ids = array_chunk($od_ids,1000);
        $child_processes = array();
        $j=1;
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
            pcntl_waitpid($pid, $status);  // 等所有线程执行完
        }
        dd('执行完成');
    }

    public function test()
    {
        $this->file_path = 'C:\Users\Administrator\Documents\a04385382384806312cd6126bab3633d';
        $arr = file_get_contents($this->file_path);
        $arr = json_decode($arr, true);
        $handle = fopen('C:\Users\Administrator\Documents\PDetail_default_2023-11-18_11-27-44_43492_266938.txt', 'r');
        $cnt = 0;
        $step = 0;
        $offset = 0;
        // 跳过前面的行
        while(!feof($handle) && $cnt != $offset) {
            fgets($handle);
            $cnt += 1;
        }
        $data = [];
        $cnt = 0;
        while(!feof($handle)) {
            // 注意这里只去掉换行，因为导入的数据里面最后N列可能是空的
            // 所以不能直接trim所有空字符
            $line = trim(fgets($handle), "\r\n");

            $data[] = explode("\t", $line);
            $cnt += 1;
            if ($cnt >= $step) {
                break;
            }
        }
        fclose($handle);
        return json($data);
        //        return json($arr);
    }

    /*
     *  库是以前的，php8 使用需要在这个文件加上约束（ReverseRegex\Generator\Node）
     */
    public function testData()
    {
        function name($arr) {
            $first_names = ['罗','梁','宋','唐','许','韩','冯','邓','曹','彭','曾','萧','田','董','袁','潘','于','蒋','蔡','余','杜','叶','程','苏','魏','吕','丁','任','沈','姚','卢','姜','崔','钟','谭','陆','汪','范','金','石','廖','贾','夏','韦','付','方','白','邹','孟','熊','秦','邱','江','尹','薛','闫','段','雷','侯','龙','史','陶','黎','贺','顾','毛','郝','龚','邵','万','钱','严','覃','武','戴','莫','孔','向','汤'];
            $second_names = ['睿','浩','博','瑞','昊','悦','妍','涵','玥','蕊','子','梓','浩','宇','俊','轩','宇','泽','杰','豪','雨','梓','欣','子','思','涵','萱','怡','彤','琪','浩','宇','子','轩','浩','然','雨','泽','宇','轩','子','涵','欣','怡','子','涵','梓','涵','雨','涵','可','馨','诗','涵','颖','灵','睿','锐','哲','慧','敦','迪','明','晓','显','悉','晰','维','学','思','悟','析','文','书','勤','俊','威','英','健','壮','焕','挺','秀','伟','武','雄','巍','松','柏','山','石','婵','娟','姣','妯','婷','姿','媚','婉','妩','倩','兰','达','耀','兴','荣','华','旺','盈','丰','余','昌','盛','乎','安','静','顺','通','坦','泰','然','宁','定','和','康'];
            $first_name = $first_names[random_int(0,count($first_names)-1)];
            $second_name = $second_names[$i = random_int(0,count($second_names)-1)];
            $time = random_int(1,2);
            if ($time === 2) {
                $ii = random_int(0, count($second_names) - 1);
                while ($ii === $i) {
                    $ii = random_int(0, count($second_names) - 1);
                }
                $second_name .= $second_names[$ii];
            }
            $arr[] = [
                'key' => '姓名',
                'value' => $first_name.$second_name,
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
            $lexer     = new Lexer($value);
            $parser    = new Parser($lexer,new Scope(),new Scope());
            $random = new MersenneRandom(random_int(PHP_INT_MIN, PHP_INT_MAX));
            $generator = $parser->parse()->getResult();
            $generator->generate($result,$random);
            $resp[] = [
                'key' => $key,
                'value' => $result,
            ];
        }
        $resp[] = [
            'key' => '当前时间',
            'value' => date('Y-m-d H:i:s'),
        ];
        return json(['code' => 0,'data' => $resp]);
    }

    public function test1()
    {
        return json(Db::query("select * from login_restrictions"));
    }
    public function m()
    {
        for ($i = 0; $i < 10; $i++) {
            sleep(5);
            dump($i);
            Log::write($i);
            fastcgi_finish_request();
        }
        return json('success');
    }

    public function test2()
    {
        $this->file_path = runtime_path().DIRECTORY_SEPARATOR.'tmp.txt';
        $data = file_get_contents('C:\Users\Administrator\Documents\demo.json');
        $data = json_decode($data, true)['RECORDS'];
        $data = array_column($data, null, 'id');
        $err_data = [];
        foreach ($data as $key => $value) {
            $tmp = [];
            $tmp = $this->test3($data, $key, $tmp);
            $tmp = array_reverse($tmp);
            $ids = implode(',', $tmp);
            if ($ids !== $value['parent_ids']) {
                $sql = "UPDATE `cmm_pro`.`company` SET `parent_ids` = '{$ids}' WHERE `id` = {$key};";
                //                $err_data[$key] = [
                //                    $value['parent_ids'] => $ids
                //                ];
                file_put_contents($this->file_path, $sql.PHP_EOL, FILE_APPEND);
                $err_data[] = $sql;
            }

        }
        return json($err_data);
    }
    public function test3($data, $id, $tmp)
    {
        if (!empty($data[$id]) && !empty($data[$id]['sup_id'])) {
            $tmp[] = $data[$id]['sup_id'];
            $tmp = $this->test3($data, $data[$id]['sup_id'], $tmp);
        }

        return $tmp;
    }

    public function test4()
    {
        $file_handle = fopen(runtime_path().DIRECTORY_SEPARATOR.'tmp2.txt', 'rb');
        function get_all_lines($file_handle): \Generator
        {
            while (!feof($file_handle)) {
                yield fgets($file_handle);
            }
        }
        $flag = true;
        $data = array();
        foreach (get_all_lines($file_handle) as $line) {
            if ($flag) {
                $flag = false;
                continue;
            }
            $data[] = $line;
        }
        echo array_sum($data);
        fclose($file_handle);
    }

    public function test5(ToolService $toolService)
    {
        $this->file_path = runtime_path().DIRECTORY_SEPARATOR.'tmp.txt';
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


    public function test6()
    {
        $lines = file_get_contents('/mnt/c/Users/Administrator/Documents/1111.txt');
        $lines = explode("\n", $lines);
        $lines2 = file_get_contents(runtime_path().DIRECTORY_SEPARATOR.'tmp.txt');
        $lines2 = explode("\n", $lines2);
        $f = 0;
        $count = count($lines);
        for ($i = 0; $i < $count; $i++) {
            $tmp = (int)$lines2[$i] - (int)$lines[$i];
            echo $tmp;
            $f += $tmp ;
            if ($f > 1000 || $f < -1000) {
                echo $i;
                break;
            }
        }
    }

    public function test7()
    {

        //        $data = $this->data();
        $data = [
            [1,'name','ffffff'],
            [2,'name','ffffff'],
            [3,'name','ffffff'],
        ];
        export_csv('test', ['id', 'name', 'create_time'], $data);
        die();
    }

    public function data(): \Generator
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

    public function test8()
    {
//        sleep(1111);
        //        $data = file_get_contents('/mnt/c/Users/Administrator/Downloads/thinkphp_3.2.4/Application/Runtime/abnormal.txt');
        //        $data = file_get_contents(runtime_path().DIRECTORY_SEPARATOR.'id'.DIRECTORY_SEPARATOR.'ac_apply_total.txt');
        $data = file_get_contents(runtime_path().DIRECTORY_SEPARATOR.'tmp.txt');
        $data = array_filter(explode(',', $data));
                jdd(count($data));
//        return json(array_diff($data, [976135]));
    }

    public function test9()
    {
        $context = [
            "finance_expense" => "b_arr_f",
            "finance_expense_text" => "到付运输费",
            "finance_amount" => -8230,
            "finance_time" => "2023-12-07 17:03:09",
            "finance_remark" => "",
            "finance_type" => "ft_settle",
            "car_batch" => "济南-南昌23年10-1车",
            "finance_bill_no" => "JS202312070241",
            "finance_bill_id" => 110735017,
            "login_info" => [
                "is_switch" => true,
                "is_jump" => null,
                "ori_group_id" => "1000",
                "ori_company_id" => "62046",
                "ori_user_id" => "71697"
            ]
        ];
        $basic_info = json_decode(file_get_contents(runtime_path().DIRECTORY_SEPARATOR.'tmp2.txt'), true);
        $amount_info = json_decode(file_get_contents(runtime_path().DIRECTORY_SEPARATOR.'tmp.txt'), true);
        foreach ($basic_info as $val) {
            $context = is_string($context) ? json_decode($context, true) : $context;
            $context['car_batch'] = $val['car_batch'];
            $context['finance_amount'] = $amount_info[$val['car_batch']];
            $context = json_encode($context, 256);
            $sql = "INSERT INTO `cmm_pro`.`log` (`parent_log_id`, `type`, `od_basic_id`, `od_id`, `od_link_id`, `b_basic_id`, `b_link_id`, `doc_id`, `op_group_id`, `op_com_id`, `op_com_name`, `op_user_id`, `op_user_name`, `content`, `ext_flags`, `status`, `create_time`, `update_time`, `create_by`, `update_by`, `f_com_id`, `ext`) VALUES (NULL, '600', NULL, NULL, NULL, {$val['b_basic_id']}, {$val['b_link_id']}, NULL, 1000, 24, '济南子公司', 71697, '赵黄妍', '$context', 1, 1, '2023-12-07 17:03:09', '2023-12-07 17:03:09', 71697, 71697, 26708, '[]');";
            file_put_contents(runtime_path() . DIRECTORY_SEPARATOR . 'tmp.sql', $sql . PHP_EOL, FILE_APPEND);
        }
        return 'success';
    }

    public function test10()
    {
        $this->file_path = runtime_path().DIRECTORY_SEPARATOR.'tmp.txt';
        //        ini_set('memory_limit','4000M');
        $url = 'http://yundan.vkj56.cn/api/Table/Search/settleList?logid=12456601703303389269&gid=1000&btnLoadingTag=off';
        $cookie = 'PHPSESSID=ec1996745c79aba7bee697ce070ac521; Hm_lvt_f59ed1ad07a4a48a248b87fac4f62903=1703032310,1703118610,1703205075,1703291341; user_id=124566; group_id=1000; company_id=26708; Hm_lpvt_f59ed1ad07a4a48a248b87fac4f62903=1703296512; Order_tr_up_loading_list_124566=true; 124566%7C62044%7C1000%7ClastHandleTime=1703303376041';
        $req = '{"category":"Settle","tab":"detail","sort":{"create_time":"desc","serial_no":"desc","id":"desc"},"page_num":1,"page_size":100,"cid":"73067e693dac1586773c349fa93a5d65","query":{"settle_no":["JS202312070241"],"company_id":[26708]},"filter":{},"ignore_default":true,"fetch_mode":"body"}';

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
        //        jdd(array_column($data,'Batch|car_batch'));
        foreach ($data as $val) {
            $tmp[$val['Batch|car_batch']] = $val['settle_amount'];
        }
        file_put_contents($this->file_path, json_encode($tmp, 256));
        return 'success';
    }

    public function test11()
    {
        $this->file_path = runtime_path().DIRECTORY_SEPARATOR.'tmp2.txt';
        //        ini_set('memory_limit','4000M');
        $url = 'http://yundan.vkj56.cn/api/Table/Search/batchList?logid=12456601703309854981&gid=1000&btnLoadingTag=off';
        $cookie = 'PHPSESSID=ec1996745c79aba7bee697ce070ac521; Hm_lvt_f59ed1ad07a4a48a248b87fac4f62903=1703032310,1703118610,1703205075,1703291341; user_id=124566; group_id=1000; company_id=26708; Hm_lpvt_f59ed1ad07a4a48a248b87fac4f62903=1703296512; Order_tr_up_loading_list_124566=true; 124566%7C62044%7C1000%7ClastHandleTime=1703303376041';
        $req = '{"category":"Batch","tab":"tr_up","sort":{},"page_num":1,"page_size":100,"fetch_mode":"body","cid":"73067e693dac1586773c349fa93a5d65","query":{"car_batch._exact_":["济南--广州23年11月1车","济南--顺德23年11月7车","济南-顺德11月12车","济南-韶关23年11月4车","济南--广州23年11月22车","济南--顺德23年11月23车","济南--广州23年11月35车","济南--顺德23年11月32车","济南--顺德23年11月3车","济南-顺德23年11月8车","济南-广州11月17车","济南--广州23年11月23车","济南--广州23年11月29车","济南--广州23年11月34车","济南-广州11月40车","济南--广州23年11月2车","济南-韶关11月1车","济南--广州23年11月7车","济南-顺德23年11月11车","济南--广州23年11月21车","济南-顺德23年11月25车","济南--广州23年11月39车","济南--顺德23年11月2车","济南-广州23年11月9车","济南--广州23年11月16车","济南到顺德11月18车","济南--顺德23年11月20车","济南--顺德23年11月22车","济南--广州23年11月33车","济南--顺德23年11月30车","济南--顺德23年11月4车","济南-广州11月14车","济南--广州23年11月19车","济南--广州23年11月24车","济南--广州23年11月30车","济南--顺德23年11月28车","济南--顺德23年11月6车","济南-广州11月15车","济南--广州23年11月20车","济南--广州23年11月26车","济南--顺德23年11月24车","济南-广州11月37车","济南--广州23年11月5车","济南-顺德23年11月9车","济南--顺德23年11月14车","济南--顺德23年11月21车","济南-深圳23年11月14车","济南-顺德11月29车","济南-广州11月43车"]},"filter":{},"batch_search_order_by":["car_batch"]}';

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
        //        jdd(implode(',',array_column($data,'b_basic_id')));
        foreach ($data as $val) {
            $tmp[$val['car_batch']] = [
                'car_batch' => $val['car_batch'],
                'b_basic_id' => $val['b_basic_id'],
                'b_link_id' => $val['b_link_id'],
            ];
        }
        file_put_contents($this->file_path, json_encode($tmp, 256));
        return 'success';
    }

    public function test12()
    {
        $this->file_path = runtime_path().DIRECTORY_SEPARATOR.'tmp.txt';
        //        ini_set('memory_limit','4000M');
//        $url = '';
//        $cookie = '';
//        $req = '';
        $url = 'http://yundan.vkj56.com/api/Table/Search/docDetailList?logid=12698801709599704130&gid=2024&btnLoadingTag=off';
        $cookie = 'PHPSESSID=be6c93525f1bff19fc8c6f6e387a43aa; Hm_lvt_f59ed1ad07a4a48a248b87fac4f62903=1709253079,1709339434,1709512376,1709598807; user_id=126988; group_id=2024; company_id=63408; Hm_lpvt_f59ed1ad07a4a48a248b87fac4f62903=1709599620; 126988%7C61983%7C2024%7ClastHandleTime=1709599688938';
        $req = '{"category":"Accounts","tab":"doc_detail_list","sort":{},"page_num":1,"page_size":100,"cid":"73067e693dac1586773c349fa93a5d65","query":{"no._exact_":["YCXMB202401110001","YCXMB202401110001","YCXMB202401030021","YCXMB202401030021","YCXMB202401030020","YCXMB202401030020","YCXMB202401030019","YCXMB202401030019","YCXMB202401030015","YCXMB202401030015","YCXMB202401030015","YCXMB202401030015","YCXMB202401030013","YCXMB202401030013","YCXMB202401030012","YCXMB202401030012","YCXMB202401030010","YCXMB202401030010","YCXMB202401030009","YCXMB202401030009","YCXMB202401030009","YCXMB202401030009","YCXMB202401030005","YCXMB202401030005","YCXMB202401030005","YCXMB202401030005","YCXMB202401030003","YCXMB202401030003","YCXMB202401030002","YCXMB202401030002"]},"filter":{},"batch_search_order_by":["no"],"fetch_mode":"body"}';

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
            if (isset($tmp[$val['no'].'-'.$val['account_name_one'].'-'.$val['account_name_two'].'-'.$val['account_name_three'].'-'.$val['account_name_four'].'-'.$val['accrual']])) {
                //            if (isset($tmp[$val['no'].'-'.$val['account_name_one'].'-'.$val['account_name_two'].'-'.$val['accrual'].'-'.$val['consignor_name']])) {
                //            if (isset($tmp[$val['no'].'-'.$val['account_name_one'].'-'.$val['account_name_two'].'-'.$val['accrual']])) {
                //            if (isset($tmp[$val['doc_date']])) {
                jdd($val);
            }
            //            $tmp[$val['doc_date']] = [
            //            $tmp[$val['no'].'-'.$val['account_name_one'].'-'.$val['account_name_two'].'-'.$val['accrual']] = [
            //            $tmp[$val['no'].'-'.$val['account_name_one'].'-'.$val['account_name_two'].'-'.$val['accrual'].'-'.$val['consignor_name']] = [
            //            $tmp[$val['no'].'-'.$val['account_name_one'].'-'.$val['account_name_two'].'-'.$val['account_name_three'].'-'.$val['account_name_four'].'-'.$val['accrual'].'-'.$val['consignor_name']] = [
            $tmp[$val['no'].'-'.$val['account_name_one'].'-'.$val['account_name_two'].'-'.$val['account_name_three'].'-'.$val['account_name_four'].'-'.$val['accrual']] = [
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

    public function test13()
    {
        $data = file_get_contents('/mnt/c/Users/Administrator/Documents/cmm_pro_ac_apply4.json');
        $data = json_decode($data, true);
        $data = implode(',', array_unique(array_filter(array_column($data, 'id'))));
        file_put_contents('/mnt/c/Users/Administrator/Documents/ac_apply4.txt', $data);
        return 'success';
    }

    public function test14()
    {
        $uri = 'http://yundan.vkj56.cn/api/Table/Search/batchList?logid=12456601710309813620&gid=1000&btnLoadingTag=off';
        $headers = [
            'cookie' => 'Order_tr_down_loading_list_124566=false; Order_tr_up_loading_list_124566=false; PHPSESSID=a6cd253fd5772e1741ed1f815ec90368; Hm_lvt_f59ed1ad07a4a48a248b87fac4f62903=1709944391,1710117167,1710203544,1710289831; 124566%7C62044%7C1000%7ClastHandleTime=1710290816405; 124566%7C62044%7C1000%7CisTimeoutLock=1; 128564%7C62619%7C2024%7ClastHandleTime=1710299954389; 128564%7C62619%7C2024%7CisTimeoutLock=1; user_id=124566; group_id=1000; company_id=2; Hm_lpvt_f59ed1ad07a4a48a248b87fac4f62903=1710299984',
        ];
        $data = [
            'req' => '{"category":"Batch","tab":"tr_up","sort":{},"page_num":1,"page_size":100,"fetch_mode":"body","cid":"73067e693dac1586773c349fa93a5d65","query":{"car_batch._exact_":["临沂-长沙23年11月25车","临沂-西安23年11月24车","临沂-重庆23年11月24车","临沂-长沙23年11月24车","临沂-郑州23年11月36车","临沂-成都23年11月32车","临沂-西安23年11月23车","临沂-长沙23年11月23车","临沂-重庆23年11月22车","临沂-成都23年11月31车","临沂-长沙23年11月22车","临沂-西安23年11月22车","临沂-郑州23年11月34车","临沂-重庆23年11月21车","临沂-长沙23年11月21车","临沂-成都23年11月30车","临沂-长沙23年11月20车","临沂-西安23年11月20车","临沂-重庆23年11月19车","临沂-成都23年11月27车","临沂-郑州23年11月30车","临沂-长沙23年11月19车","临沂-重庆23年11月18车","临沂-西安23年11月19车","临沂-成都23年11月24车","临沂-长沙23年11月18车","临沂-郑州23年11月27车","临沂-重庆23年11月15车","临沂-长沙23年11月17车","临沂-成都23年11月22车","临沂-西安23年11月17车","临沂-郑州23年11月24车","临沂-重庆23年11月14车","临沂-长沙23年11月16车","临沂-成都23年11月20车","临沂-西安23年11月15车","临沂-长沙23年11月15车","临沂-成都23年11月18车","临沂-郑州23年11月19车","临沂-长沙23年11月14车","临沂-西安23年11年13车","临沂-重庆23年11月11车","临沂-长沙23年11月13车","临沂-成都23年11月16车","临沂-郑州23年11月15车","临沂-重庆23年11月10车","临沂-西安23年11月11车","临沂-成都23年11月11车","临沂-长沙23年11月10车","临沂-郑州23年11月13车","临沂-西安23年11月9车","临沂-长沙23年11月9车","临沂-成都23年11月10车","临沂-重庆23年11月6车","临沂-郑州23年11月11车","临沂-长沙23年11月8车","临沂-长沙23年11月6车","临沂-成都23年11月8车","临沂-西安23年11月7车","临沂-重庆23年11月5车","临沂-郑州23年11月9车","临沂-长沙23年11月5车","临沂-重庆23年11月4车","临沂-西安23年11月5车","临沂-成都23年11月5车","临沂-长沙23年11月4车","临沂-郑州23年11月7车","临沂-成都23年11月4","临沂-重庆23年11月2车","临沂-郑州23年11月4车","临沂-成都23年11月2车","临沂-重庆23年11月1车","临沂-成都23年11月1车"]},"filter":{},"batch_search_order_by":["car_batch"]}',
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
        file_put_contents(runtime_path().DIRECTORY_SEPARATOR.'tmp.txt',json_encode($tmp,256));
        return json('success');
    }

    public function test15()
    {
        phpinfo();
    }

    public function test16()
    {
        $data = file_get_contents('/mnt/c/Users/Administrator/Documents/cmm_pro_ac_apply4.json');
        $data = json_decode($data, true);
        $data = implode(',', array_unique(array_filter(array_column($data, 'id'))));
        file_put_contents('/mnt/c/Users/Administrator/Documents/ac_apply4.txt', $data);
    }


    // 合并一定范围的id
    public function test17()
    {

        //        $filename = "/mnt/c/Users/Administrator/Downloads/thinkphp_3.2.4/Application/Runtime/order_mod";
        $perfix = 'settle_record';
        $path = runtime_path().DIRECTORY_SEPARATOR.$perfix;
        $tmp = [];
        for ($i = 1; $i <= 6; $i++) {
            $filename = $path."_{$i}.txt";
            //            var_dump($filename);
            if (is_file($filename)) {
                $data = file_get_contents($filename);
                //                jdd($data);
                $data = array_filter(explode(',', $data));
                if (empty($data)) {
                    continue;
                }
                $tmp = array_merge($tmp, $data);
            }
        }
        //        $data = array_filter(explode(',',$data));
        //        jdd(count($tmp));
        $data = implode(',', array_unique(array_filter($tmp)));
        file_put_contents($path.'_total.txt', $data);


        jdd('success');
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
    public function test18()
    {
        ini_set("max_execution_time", "30000");
        ini_set("memory_limit", "4024M");

        $start = time();
        $i = 1;
        Db::connect('local_monitor')
            ->table('error_trace')->field('id,get,post')->json(['get','post'])
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
                    file_put_contents(runtime_path().DIRECTORY_SEPARATOR.'id'.DIRECTORY_SEPARATOR."{$table}-{$i}.txt", implode(',', array_unique(array_filter($item))));
                }
                $i++;
            }, 'id');
        return json(['耗时：' => (time() - $start).'s','内存使用：' => memory_get_usage() / 1024 / 1024 . 'M']);
    }

    /*
     * 一次查询，对于耗时特长的查询来说，没什么用
     */
    public function test19()
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
            file_put_contents(runtime_path().DIRECTORY_SEPARATOR.'ids'.DIRECTORY_SEPARATOR."{$table}.txt", implode(',', array_unique(array_filter($item))));
        }
        return json(['耗时：' => (time() - $start).'s','内存使用：' => memory_get_usage() / 1024 / 1024 . 'M']);
    }


    /**
     * 合并指定目录中特定前缀文件的id至一个文件
     * @return void
     */
    public function mergeId(): void
    {
        ini_set("max_execution_time", "30000");
        ini_set("memory_limit", "4024M");
        $path = runtime_path().DIRECTORY_SEPARATOR.'id';
        //        $path = 'C:\Users\Administrator\Downloads\thinkphp_3.2.4\Application\Runtime';
        $prefix = 'ac_apply';
        $arr = [];
        if ($handle = opendir($path)) {
            while (false !== ($file = readdir($handle))) {
                if ($file !== "." && $file !== "..") {
                    if ($file === $prefix.'_total.txt') {
                        continue;
                    }
                    if (Str::startsWith($file, $prefix)) {
                        $file = $path.DIRECTORY_SEPARATOR.$file;
                        $arr = array_filter(array_unique(array_merge(explode(",", file_get_contents($file)), $arr)));
                    }
                }
            }
            closedir($handle);
        }
        file_put_contents($path.DIRECTORY_SEPARATOR.$prefix.'_total.txt', implode(',', $arr));
        jdd('success');
    }


    public function test20()
    {
        $start = time();
        $data = Db::connect('monitor')->table('error_trace')->json(['get','post'])
            ->field('id,get,post')
            ->whereRaw("id = 3304549")
            ->select();
        return json(['data' => $data,'耗时：' => (time() - $start).'s','内存使用：' => memory_get_usage() / 1024 / 1024 . 'M']);
    }

    public function es($option)
    {
        $uri = 'http://gamma.vkj56.cn:8009/api/Table/Tools/searchByOption?';
        $option = json_decode($option, true);
        $option = json_decode($option, true, 512, JSON_THROW_ON_ERROR);
        if (empty($option) || !is_array($option)) {
            return json('error');
        }
        $option = json_encode($option, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
        $response = Requests::get($uri.http_build_query(['option' => $option]), [], []);
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
        $response = Requests::get($uri.http_build_query(['option' => $option]), $headers, []);
        return json($response->decode_body());
//        $data = $response->decode_body();
//        return json(array_unique(array_column($data['data_list'], 'order_num')));
    }


    public function getKey(ToolService $toolService) {
        ini_set('memory_limit', '4G');
        ini_set("max_execution_time", "30000");
        $this->file_path = runtime_path().DIRECTORY_SEPARATOR.'tmp.txt';
        $arr = [];
        for ($i = 1; $i <= 31; $i++) {
            dump("当前第{$i}次".PHP_EOL);
            $start_time = date('Y-m-d H:i:s', mktime(0, 0, 0, 3, $i, 2024));
            $end_time = date('Y-m-d H:i:s', mktime(23, 59, 59, 3, $i, 2024));
            $url = 'http://yundan.vkj56.cn/api/Table/Search/TradeRecordList?logid=12856401712046409727&gid=2024&btnLoadingTag=off';
            $cookie = 'PHPSESSID=a0eafd4e43d712516e535eb7f3a2b28e; Hm_lvt_f59ed1ad07a4a48a248b87fac4f62903=1712044660; 124566%7C62044%7C1000%7ClastHandleTime=1712044663094; 124566%7C62044%7C1000%7CisTimeoutLock=1; user_id=128564; group_id=2024; 128564%7C62619%7C2024%7ClastHandleTime=1712045427659; 128564%7C62619%7C2024%7CisTimeoutLock=1; company_id=64255; Hm_lpvt_f59ed1ad07a4a48a248b87fac4f62903=1712045545';
            $req = '{"category":"TradeRecord","tab":"vir_com_tr","sort":{"create_time":"desc","trade_id":"desc"},"page_num":1,"page_size":1000,"cid":"73067e693dac1586773c349fa93a5d65","query":{},"filter":{"create_time":[[">=","'.$start_time.'"],["<=","'.$end_time.'"]]},"fetch_mode":"body"}';
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
        file_put_contents($this->file_path,array_values(array_unique(array_filter($arr))));
        dd('执行完成');
    }

    public function upSingleV3() {
        if (PHP_SAPI !== 'cli') {
            exit('仅可cli执行');
        }
        $number = 100;  //最大线程数
        $process_count = 0;
        $this->file_path = runtime_path().DIRECTORY_SEPARATOR.'tmp.txt';
        $od_ids = explode(',', file_get_contents($this->file_path));
//        $uri = 'http://gamma.vkj56.cn:8008/api/Finance/DoData/upSingleV2?od_id=';
        $uri = 'http://w-sas-1000-web-alpha-00.vkj56.cn:306/api/Finance/DoData/upSingleV2?od_id=';
        $od_ids = array_chunk($od_ids,1000);
        $child_processes = array();
        $j=1;
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

    public function upSingleV2() {
        ini_set("max_execution_time", "30000");
        $this->file_path = runtime_path().DIRECTORY_SEPARATOR.'tmp.txt';
        $od_ids = explode(',', file_get_contents($this->file_path));
//        $od_ids = [166030991];
        $count = count($od_ids);
        $uri = 'http://gamma.vkj56.cn:8008/api/Finance/DoData/upSingleV2?od_id=';
//        $uri = 'http://localhost:9018/api/Finance/DoData/upSingleV2?od_id=';
        $i = 1;
        foreach ($od_ids as $od_id) {
            $response = Requests::get($uri . $od_id, [], []);
            $p = round($i/$count, 3)* 100;
            dump("当前操作od_id:{$od_id}    ".$response->body."        ---------process {$p}%");
            $i++;
        }
        dd('执行完成');
    }

    public function v2Roll() {
        ini_set("max_execution_time", "30000");
        $this->file_path = runtime_path().DIRECTORY_SEPARATOR.'tmp.txt';
//        $od_ids = explode(',', file_get_contents($this->file_path));
//        $tr_ids = explode(',', file_get_contents(runtime_path().DIRECTORY_SEPARATOR.'tmp2.txt'));
        $od_ids = [165126067,164797128,165077027,165009228];
        $tr_ids = [442453988,442453989,442444783,442444784,442431250,442431251,442427512,442427513];
        $count = count($od_ids);
//        $uri = 'http://gamma.vkj56.cn:8008/api/Finance/DoData/upSingleV2?od_id=';
        $uri = 'http://localhost:9018/api/Finance/DoData/v2Roll?od_id=';
        $i = 1;
        foreach ($od_ids as $od_id) {
            dd($uri . $od_id. '&tr_ids='.implode(',',$tr_ids));
            $response = Requests::get($uri . $od_id. '&tr_ids='.implode(',',$tr_ids), [], []);
            $p = round($i/$count, 3)* 100;
            dump("当前操作od_id:{$od_id}    ".$response->body."        ---------process {$p}%");
            $i++;
        }
        dd('执行完成');
    }

    // 比较资金流水和交易记录
    public function compare(ToolService $toolService): \think\response\Json
    {
        ini_set('memory_limit', '4G');
        ini_set("max_execution_time", "30000");
        $path1 = runtime_path() . DIRECTORY_SEPARATOR . 'tmp.txt';
        $path2 = runtime_path() . DIRECTORY_SEPARATOR . 'tmp2.txt';
        $tmp = [];
        for ($i = 5; $i <= 5; $i++) {
            dump("当前第{$i}次".PHP_EOL);
            $start_time = date('Y-m-d H:i:s', mktime(0, 0, 0, 3, $i, 2024));
            $end_time = date('Y-m-d H:i:s', mktime(23, 59, 59, 3, $i, 2024));
            // 资金流水
            $uri = 'http://yundan.vkj56.cn/api/Table/Search/settleList?logid=12856401712374389184&gid=2024&btnLoadingTag=off';
            $headers = [
                'cookie' => 'PHPSESSID=07d91f64f3bb3b6c715cfaba0a0043db; Hm_lvt_f59ed1ad07a4a48a248b87fac4f62903=1712545581; 128564%7C62619%7C2024%7ClastHandleTime=1712545583566; 128564%7C62619%7C2024%7CisTimeoutLock=1; 124566%7C62044%7C1000%7ClastHandleTime=1712545802264; 124566%7C62044%7C1000%7CisTimeoutLock=1; user_id=128564; group_id=2024; company_id=63328; Hm_lpvt_f59ed1ad07a4a48a248b87fac4f62903=1712555498',
            ];
            $data = [
                'req' => '{"category":"Settle","tab":"detail","sort":{"create_time":"desc","serial_no":"desc","id":"desc"},"page_num":1,"page_size":1000,"cid":"73067e693dac1586773c349fa93a5d65","query":{"settle_category":[80],"company_id":[63328]},"filter":{"settle_time":[[">=","'.$start_time.'"],["<=","'.$end_time.'"]]},"fetch_mode":"body"}',
            ];
            $toolService->getSpecifyKeyDataCopy($uri, $headers, $data, $path1, 'Order|order_num');
            // 交易记录
            $uri = 'http://yundan.vkj56.cn/api/Table/Search/TradeRecordList?logid=12856401712374301575&gid=2024&btnLoadingTag=off';
            $headers = [
                'cookie' => 'PHPSESSID=07d91f64f3bb3b6c715cfaba0a0043db; Hm_lvt_f59ed1ad07a4a48a248b87fac4f62903=1712545581; 128564%7C62619%7C2024%7ClastHandleTime=1712545583566; 128564%7C62619%7C2024%7CisTimeoutLock=1; 124566%7C62044%7C1000%7ClastHandleTime=1712545802264; 124566%7C62044%7C1000%7CisTimeoutLock=1; user_id=128564; group_id=2024; company_id=63328; Hm_lpvt_f59ed1ad07a4a48a248b87fac4f62903=1712555498',
            ];
            $data = [
                'req' => '{"category":"TradeRecord","tab":"vir_com_tr","sort":{"create_time":"asc"},"page_num":1,"page_size":1000,"cid":"73067e693dac1586773c349fa93a5d65","query":{},"filter":{"create_time":[[">=","'.$start_time.'"],["<=","'.$end_time.'"]]},"fetch_mode":"body"}',
            ];
            $toolService->getSpecifyKeyDataCopySpec($uri, $headers, $data, $path2, 'conn_info');
//            $order_num = explode(',', file_get_contents($path1));
//            $now_order_num = explode(',', file_get_contents($path2));
            $now_order_num = explode(',', file_get_contents($path1)); // 资金流水重复划拨
            $order_num = explode(',', file_get_contents($path2));
            dump(count($now_order_num));
            dump(count($order_num));
//            dd(array_diff($order_num,$now_order_num));
            if (empty($order_num))         Log::write($i.'$order_num empty');
            foreach ($order_num as $t) {
                foreach ($now_order_num as $k => $v) {
                    if ($t == $v) {
                        unset($now_order_num[$k]);
                        continue 2;
                    }
                }
            }
            Log::write($i.'  '.json_encode(array_unique(array_values(array_filter($now_order_num))), 256));
            $tmp = array_merge($tmp, array_values($now_order_num));
        }
        Log::write(json_encode(array_values($tmp), 256));
        return json(array_values(array_unique(array_filter($tmp))));
    }

    // 查询缺少的凭证
    public function compare2(ToolService $toolService)
    {
        ini_set('memory_limit', '4G');
        ini_set("max_execution_time", "30000");
        for ($i = 1; $i <= 31; $i++) {
            dump("当前第{$i}次");
            $start_time = date('Y-m-d H:i:s', mktime(0, 0, 0, 3, $i, 2024));
            $end_time = date('Y-m-d H:i:s', mktime(23, 59, 59, 3, $i, 2024));
            // 资金流水
            $uri = 'http://yundan.vkj56.cn/api/Table/Search/settleList?logid=12856401712115595552&gid=2024&btnLoadingTag=off';
            $headers = [
                'cookie' => 'PHPSESSID=3e9770487c882fea3e9306870cb55136; Hm_lvt_f59ed1ad07a4a48a248b87fac4f62903=1712044660,1712104014; user_id=128564; group_id=2024; 128564%7C62619%7C2024%7ClastHandleTime=1712105214367; 128564%7C62619%7C2024%7CisTimeoutLock=1; company_id=64368; Hm_lpvt_f59ed1ad07a4a48a248b87fac4f62903=1712115438',
            ];
            $data = [
                'req' => '{"category":"Settle","tab":"detail","sort":{"create_time":"desc","serial_no":"desc","id":"desc"},"page_num":1,"page_size":1000,"cid":"73067e693dac1586773c349fa93a5d65","query":{},"filter":{"settle_time":[[">=","'.$start_time.'"],["<=","'.$end_time.'"]]},"fetch_mode":"body"}',
            ];
            $toolService->test($uri, $headers, $data);
            Log::write($i."页");
        }
        dd('执行完成');
    }

    // 获取交易记录od_id
    public function compare3() {
        $path1 = runtime_path() . DIRECTORY_SEPARATOR . 'tmp.txt';
        $arr = [];
        for ($i = 1; $i <= 31; $i++) {
            dump("当前第{$i}次".PHP_EOL);
            $start_time = date('Y-m-d H:i:s', mktime(0, 0, 0, 3, $i, 2024));
            $end_time = date('Y-m-d H:i:s', mktime(23, 59, 59, 3, $i, 2024));
            $option = [
                "data_name"=> "TradeRecord",
                "filter"=> [
                    [
                        "group_id"=> 2024,
                        "user_id"=> [
                            63328
                        ],
                        "user_type"=> 1
                    ],
                    "tr_category"=> 1,
                    "tr_status"=> 1,
                    "create_time"=> [
                        [
                            ">=",
                            $start_time
                        ],
                        [
                            "<=",
                            $end_time
                        ]
                    ]
                ],
                "fields"=> [
                    "order_id"
                ],
                "page_num"=> 1,
                "page_size"=> 50000
            ];
            $uri = 'http://gamma.vkj56.cn:8009/api/Table/Tools/searchByOption?';
            $option = json_encode($option, JSON_UNESCAPED_UNICODE);
            $response = Requests::get($uri.http_build_query(['option' => $option]), [], []);
            $order_ids = array_column($response->decode_body()['data_list'],'order_id');
            dump($response->decode_body()['total_info']['count']);
            $arr = array_merge($arr,$order_ids);
        }
        file_put_contents($path1,json_encode($arr,256));
        dd('success');
    }

// 获取资金流水od_id
    public function compare4() {
        $path1 = runtime_path() . DIRECTORY_SEPARATOR . 'tmp2.txt';
        $arr = [];
        for ($i = 1; $i <= 31; $i++) {
            dump("当前第{$i}次".PHP_EOL);
            $start_time = date('Y-m-d H:i:s', mktime(0, 0, 0, 3, $i, 2024));
            $end_time = date('Y-m-d H:i:s', mktime(23, 59, 59, 3, $i, 2024));
            $option = [
                "data_name"=> "Settle",
                "filter"=> [
                    "settle_category"=> 80,
//                    "superior.company_id"=> 63328,
                    "company_id"=> 63328,
                    "bill_type"=> "settle",
                    "bill_status"=> 1,
                    "pay_mode_no"=> [0,1],
                    "settle_time"=> [
                        [
                            ">=",
                            $start_time
                        ],
                        [
                            "<=",
                            $end_time
                        ]
                    ]
                ],
                "fields"=> [
                    "od_id"
                ],
                "page_num"=> 1,
                "page_size"=> 50000
            ];
            $uri = 'http://gamma.vkj56.cn:8009/api/Table/Tools/searchByOption?';
            $option = json_encode($option, JSON_UNESCAPED_UNICODE);
            $response = Requests::get($uri.http_build_query(['option' => $option]), [], []);
            $od_ids = array_column($response->decode_body()['data_list'],'od_id');
            dump($response->decode_body()['total_info']['count']);
            $arr = array_merge($arr,$od_ids);
        }
        file_put_contents($path1,json_encode($arr,256));
        dd('success');
    }

    public function compare5() {
//        $bill = json_decode(file_get_contents(runtime_path() . DIRECTORY_SEPARATOR . 'tmp2.txt'),true);
//        $trade = json_decode(file_get_contents(runtime_path() . DIRECTORY_SEPARATOR . 'tmp.txt'),true);

        $bill = explode(',',file_get_contents(runtime_path() . DIRECTORY_SEPARATOR . 'tmp2.txt'));
        $trade = explode(',',file_get_contents(runtime_path() . DIRECTORY_SEPARATOR . 'tmp.txt'));
//        $bill = array_map('intval',$bill);
//        $trade = array_map('intval',$trade);
//        dd(count((array_filter($trade))));
//        sort($bill);
//        sort($trade);
        foreach ($bill as $f => $t) {
            foreach ($trade as $k => $v) {
                if ($t == $v) {
                    unset($bill[$f], $trade[$k]);
                    dump(count($trade));
                    continue 2;
                }
            }
        }
//        dd(json_encode(array_values(array_diff($trade, $bill)),256));
        dd($trade);
    }

    public function test21()
    {
        $option = [
            'data_name' => 'CustomerContract',
            "query" => [
//                'id' => '3277'
            ],
            "filter" => [
                'status' => 1,
                'name' => '到货',
                'group_id' => 1000,
                'type' => 'customer',
            ],
            'page_num' => 1,
            'page_size' => 10,
            'fields' => [
//                'id'
            ],
        ];
        //        $uri = 'http://gamma.vkj56.cn:8009/api/Table/Tools/searchByOption?';
        $uri = 'http://yundan.vkj56.cn/api/Table/Tools/searchByOption?';
        $option = json_encode($option, JSON_ERROR_CTRL_CHAR);
        //        jdd($uri.http_build_query(['option' => $option]));;
        $response = Requests::get($uri.http_build_query(['option' => $option]), [], []);
        return json($response->decode_body());
    }

    public function test22()
    {
        $this->file_path = runtime_path().DIRECTORY_SEPARATOR.'tmp.txt';
        $arr = file_get_contents($this->file_path);
        $arr = array_values(array_filter(explode(',', $arr)));
        $tmp = array_chunk($arr, 10);
        $uri = 'http://yundan.vkj56.cn/api/Accounts/DoData/updateAccrual?doc_ids=';
        foreach ($tmp as $v) {
            $response = Requests::get($uri . implode(',', $v), [], []);
            Log::write($response->body);
        }
        return 'success';
    }

    public function test23()
    {
        $this->file_path = runtime_path().DIRECTORY_SEPARATOR.'tmp.txt';
        $arr = file_get_contents($this->file_path);
        $data = json_decode($arr, true);
        foreach ($data as $v) {
            $context = [
                "finance_expense"=> "b_arr_f",
                "finance_expense_text"=> "到付运输费",
                "finance_amount"=> $v['b_arr_f'],
                "finance_time"=> "2023-11-30 16:16:24",
                "finance_remark"=> "",
                "finance_type"=> "ft_settle",
                "car_batch"=> $v['car_batch'],
                "finance_bill_no"=> "JS202312090030",
                "finance_bill_id"=> 110945107,
                "login_info"=> [
                    "is_switch"=> true,
                    "is_jump"=> null,
                    "ori_group_id"=> "1000",
                    "ori_company_id"=> "62046",
                    "ori_user_id"=> "129002"
                ]
            ];
            $context = json_encode($context,256);
            $sql = "INSERT INTO `cmm_pro`.`log` (`parent_log_id`, `type`, `od_basic_id`, `od_id`, `od_link_id`, `b_basic_id`, `b_link_id`, `doc_id`, `op_group_id`, `op_com_id`, `op_com_name`, `op_user_id`, `op_user_name`, `content`, `ext_flags`, `status`, `create_time`, `update_time`, `create_by`, `update_by`, `f_com_id`, `ext`) VALUES (NULL, '600', NULL, NULL, NULL, {$v['b_basic_id']}, {$v['b_link_id']}, NULL, 1000, 1976, '临沂子公司', 129002, '龚芳芳', '{$context}', 1, 1, '2023-12-09 16:30:05', '2023-12-09 16:30:05', 129002, 129002, 26709, '[]');".PHP_EOL;
            file_put_contents(runtime_path().DIRECTORY_SEPARATOR.'tmp.sql',$sql,FILE_APPEND);
        }
    }

    public function testPlainSearch(){
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
}
