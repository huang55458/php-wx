<?php

declare (strict_types=1);

namespace app\controller;

use think\facade\Db;
use think\Request;

class SQL
{
    public function create_sql($table, $arr)
    {
        foreach ($arr as $k => $v) {
            if ($k === 'id') {
                continue;
            }

            $f[] = '`' . $k . '`';
            if (is_int($v)) {
                $val[] = $v;
            } elseif (is_null($v)) {
                $val[] = 'null';
            } else {
                $v = str_replace("'", "\'", $v);
                $val[] = "'" . $v . "'";
            }

        }
        $f = implode(',', $f);
        $val = implode(',', $val);
        return "insert into " . $table . "(" . $f . ") values (" . $val . ");";
    }

    /**
     * 显示资源列表
     *
     * @return \think\Response
     */
    public function index()
    {
        $file_path = runtime_path() . DIRECTORY_SEPARATOR . 'tmp.sql';
        $data = file_get_contents('C:\Users\Administrator\Documents\demo.json');
        $data = json_decode($data, true)['RECORDS'];
        foreach ($data as $v) {
            $v['group_id'] = 2024;
            $v['company_id'] = 61954;
            $v['pz_id'] = 3621;
            //            $v['group_id'] = 1000;
            //            $v['company_id'] = 2;
            //            $v['pz_id'] = 2621;
            $v['create_by'] = 61954;
            $v['update_by'] = 61954;
            $v['create_by_uid'] = 128863;
            $v['update_by_uid'] = 128863;
            $v['create_time'] = date('Y-m-d H:i:s');
            $v['update_time'] = date('Y-m-d H:i:s');
            $sql = $this->create_sql(' `cmm_pro`.`p_zone_detail` ', $v);
            file_put_contents($file_path, $sql . PHP_EOL, FILE_APPEND);
        }
        return json('success');
    }

    /**
     * 显示创建资源表单页.
     *
     * @return \think\Response
     */
    public function create()
    {
        $data = [
            'id' => 1,
            'ext' => "'ffffff"
        ];
        return $this->create_sql('od', $data);
    }

    /**
     * 运单更换开票主体
     */
    public function save()
    {
        $ids = [179875620,179875600,179875583];
        foreach ($ids as $od_id) {
            $o_d = Db::connect('pro_order')->table('od')
                ->field('id,od_ext_info')
                ->whereRaw("id = $od_id")
                ->fetchSql(false)->select()->toArray();
            $od_ext_info = $o_d[0]['od_ext_info'];
            $roll_sql = "UPDATE `cmm_pro`.`od` SET `od_ext_info` = '$od_ext_info' WHERE `id` = $od_id;";
            file_put_contents(runtime_path() . DIRECTORY_SEPARATOR . 'tmp.sql', $roll_sql . PHP_EOL, FILE_APPEND);
            $od_ext_info_decode = decode_json($o_d[0]['od_ext_info']);
            $od_ext_info_decode['invoicing_ids'] = array_diff($od_ext_info_decode['invoicing_ids'], [28]);
            $od_ext_info_decode['invoicing_ids'][] = 58;
            $od_ext_info_encode = encode_json($od_ext_info_decode);
            $update_sql = "UPDATE `cmm_pro`.`od` SET `od_ext_info` = '$od_ext_info_encode' WHERE `id` = $od_id;";
            file_put_contents(runtime_path() . DIRECTORY_SEPARATOR . 'tmp2.sql', $update_sql . PHP_EOL, FILE_APPEND);
        }
        dump('success');
    }

    /**
     * 运单关联开票
     *
     */
    public function read()
    {
        $data = [
            [
                "od_link_id" => "442037090",
                "od_basic_id" => "284527201"
            ]
        ];
        //        dd(implode(',', array_map('intval', array_column($data,'od_basic_id'))));
        foreach ($data as $v) {
            $id = $v['od_basic_id'];
            $o_d = Db::connect('pro_order')->table('od_basic')
                ->field('ob_ext')
                ->whereRaw("id = $id")
                ->fetchSql(false)->select()->toArray();
            $ob_ext = $o_d[0]['ob_ext'];
            $roll_sql = "UPDATE `cmm_pro`.`od_basic` SET `ob_ext` = '$ob_ext' WHERE `id` = $id;";
            file_put_contents(runtime_path() . DIRECTORY_SEPARATOR . 'tmp.sql', $roll_sql . PHP_EOL, FILE_APPEND);
            $ob_ext_decode = decode_json($o_d[0]['ob_ext']);
            $ob_ext_decode['invoicing_info'][$v['od_link_id']]['apply_invoicing_id'] = 25001;
            $ob_ext_encode = encode_json($ob_ext_decode);
            $update_sql = "UPDATE `cmm_pro`.`od_basic` SET `ob_ext` = '$ob_ext_encode' WHERE `id` = $id;";
            file_put_contents(runtime_path() . DIRECTORY_SEPARATOR . 'tmp2.sql', $update_sql . PHP_EOL, FILE_APPEND);
        }
        dump('success');
    }

    /**
     * 异常付款凭证id
     *
     */
    public function edit()
    {
        $doc_id = [];
        $acc_doc_id = [];
        $d = Db::connect('pro_main')->table('abnormal_rbm')
            ->field('id,bill_info')
            ->whereRaw("group_id = 2024 and status = 1")
            ->fetchSql(false)->select()->toArray();
        foreach ($d as $val) {
            if (empty($val['bill_info'])) {
                continue;
            }
            $bill_infos = decode_json($val['bill_info']);
            foreach ($bill_infos as $bill_info) {
                $bill_id = $bill_info['bill_id'];
                $doc_info = Db::connect('pro_main')->table('bill')
                    ->field('doc_info')
                    ->whereRaw("id = $bill_id and status = 1")
                    ->fetchSql(false)->select()->toArray();
                $doc_info = decode_json($doc_info[0]['doc_info']);
                if (empty($doc_info['doc_id'])) {
                    continue;
                }
                if (isset($doc_info['doc_origin']) && $doc_info['doc_origin'] === 'finance_sys') {
                    $acc_doc_id[] = $doc_info['doc_id'];
                } else {
                    $doc_id[] = $doc_info['doc_id'];
                }
            }
        }
        dump(['doc_id' => implode(',', $doc_id), 'acc_doc_id' => implode(',', $acc_doc_id)]);
        dump('success');
    }

    /**
     * 查找影响运单其他科目余额的的ac_apply中的doc_detail_id
     *
     */
    public function update()
    {
        $res = [];
        $apply_ids = file_get_contents(runtime_path() . DIRECTORY_SEPARATOR . 'tmp.txt');
        $data = Db::connect('pro_acc_finance')->table('ac_apply')
            ->field('ledger_id,apply_id,account_detail_id,money_type,count(*) as count,GROUP_CONCAT(id) as ids')
            ->group('ledger_id,apply_id,account_detail_id,money_type')
            ->whereRaw("id in ($apply_ids)")
            ->fetchSql(false)->select()->toArray();
        foreach ($data as $row) {
            $ledger_id = $row['ledger_id'];
            $apply_id = $row['apply_id'];
            $account_detail_id = $row['account_detail_id'];
            //            $money_type = $row['money_type'];
            $tmp = Db::connect('pro_acc_finance')->table('ac_apply')
                ->field('count(*) as count,GROUP_CONCAT(id) as ids')
                ->whereRaw("ledger_id = $ledger_id and apply_id = $apply_id and account_detail_id = $account_detail_id 
                and money_type = '' and apply_type in (1,2,3,7) ")
                ->fetchSql(false)->select()->toArray();
            if (!isset($row['count']) || !isset($tmp[0]['count'])) {
                dd([$row, $tmp]);
            }
            if (!isset($row['ids']) || !isset($tmp[0]['ids'])) {
                dd([$row, $tmp]);
            }
            if ($row['count'] !== $tmp[0]['count']) {
                $no_exist_apply_ids = array_diff(explode(',', $tmp[0]['ids']), explode(',', $row['ids'])) ?? [];
                dump($no_exist_apply_ids);
                $res = array_merge($res, $no_exist_apply_ids);
            }
        }
        dump(['apply_id' => implode(',', $res)]);
        dump('success');
    }

    /**
     * 获取最近pdd推送的运单
     *
     */
    public function pdd()
    {
        $order_nums = [];
        Db::connect('pro_order')->table('od_link')->alias('ol')
            ->leftJoin(['od'=>'od'], 'od.id = ol.od_id')
            ->leftJoin(['od_basic'=>'ob'], 'ol.od_basic_id = ob.id')
            ->field('/*+ MAX_EXECUTION_TIME(200) */ ob.ob_ext, ol.id, od.order_num, od.group_id')
            ->whereRaw("ol.id > 448984711")
            ->chunk(50, function ($od_infos) use (&$order_nums) {
                //                dump(Db::connect('alpha')->getLastSql());
                foreach ($od_infos as $od_info) {
                    if (empty($od_info['ob_ext'])) {
                        continue;
                    }
                    if ($od_info['group_id'] == 1000) {
                        continue;
                    }
                    $ob_ext = decode_json($od_info['ob_ext']);
                    if (isset($ob_ext['show_pdd_sub']) && $ob_ext['show_pdd_sub'] === true && !in_array($od_info['order_num'], $order_nums)) {
                        $order_nums[] = $od_info['order_num'];
                        file_put_contents(runtime_path() . DIRECTORY_SEPARATOR . 'tmp.txt', $od_info['order_num'].PHP_EOL, FILE_APPEND);
                    }
                }
            }, 'ol.id');

        dump(implode(',', $order_nums));
    }
}
