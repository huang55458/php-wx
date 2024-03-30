<?php

namespace app\controller;

use app\BaseController;
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
        return json(Db::query("select * from user where id=:id", ['id' => 2]));
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

    public function test8(){
        $data = file_get_contents(runtime_path().DIRECTORY_SEPARATOR.'tmp.txt');
        $data = array_filter(explode(',',$data));
        jdd(count($data));
    }
}
