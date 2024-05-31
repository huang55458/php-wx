<?php

declare (strict_types=1);

namespace app\controller;

use app\BaseController;
use app\service\ExcelService;
use think\App;
use think\facade\Db;

class Excel extends BaseController
{
    protected ExcelService $excelService;

    public function __construct(App $app, ExcelService $excelService)
    {
        parent::__construct($app);
        $this->excelService = $excelService;
    }

    public function testSqlDataExport()
    {
        $res = Db::name('login_restrictions')->field('id,type,group_id,pc_name,mac_md5,state,status,ext,remark,province,province_name,city,city_name,user_id,company_id,create_time,update_time,create_by_user,update_by_user,create_by_company,update_by_company')->select();
        $this->excelService->exportXlsx(array_keys($res[0]), $res);
    }

    public function testLoad()
    {
        $res = $this->excelService->loadXlsx('/mnt/d/download/01simple (6).xlsx');
        return json($res);
    }

    public function testFileExport()
    {
        $res = [];
        $data = file_get_contents('/mnt/c/Users/Administrator/Documents/demo.json');
        $data = json_decode($data, true);
        $ledger = file_get_contents(runtime_path() . DIRECTORY_SEPARATOR . 'tmp.txt');
        $ledger = json_decode($ledger, true);
        $ledger = array_column($ledger, null, 'ledger_id');
        foreach ($data['RECORDS'] as $val) {
            $res[] = [
                '账套' => $ledger[$val['ledger_id']]['ledger_name'],
                '凭证号' => $val['no'],
            ];
        }
        $this->excelService->exportXlsx(['账套', '凭证号'], $res);
    }

    public function genFixDepartmentSql()
    {
        // 部门查询时加上集团和网点
        $department = [];
        $department = array_column($department, null, 'department_name');
        $data = file_get_contents(runtime_path() . DIRECTORY_SEPARATOR . 'tmp.txt');
        $data = json_decode($data, true);
        //        jdd($data);
        //        $res = $this->excelService->loadXlsx('/mnt/d/download/新武汉项目未生成发货人2.23.xlsx');
        $res = $this->excelService->loadXlsx('/mnt/c/Users/Administrator/Documents/银川项目部空部门.xlsx');
//        $res = array_filter($res, static function ($v) {
//            return !empty($v['序号']);
//        });
        $res = array_filter($res, static function ($v) {
            return in_array($v['序号'], [1, 2, 3, 4, 5, 6, 7, 8, 9, 10]);
        });
        //        jdd($res);
        //        jdd(count($res));
        $ids = [];
        //        array_pop($res); // 多了两条修发货人的数据
        //        array_pop($res);
        foreach ($res as $val) {
            //            $val['三级科目名称'] = ''; // excel 没有这两列
            //            $val['四级科目名称'] = '';
            //            $ac_doc = $data[$val['凭证编码'].'-'.$val['一级科目名称'].'-'.$val['二级科目名称'].'-'.$val['三级科目名称'].'-'.$val['四级科目名称'].'-'.sprintf("%.2f",$val['余额']).'-'.$val['发货人名称']];
            $ac_doc = $data[$val['凭证编码'] . '-' . $val['一级科目名称'] . '-' . $val['二级科目名称'] . '-' . $val['三级科目名称'] . '-' . $val['四级科目名称'] . '-' . sprintf("%.2f", $val['余额'])];
            //            $ac_doc = $data[$val['凭证编码'].'-'.$val['一级科目名称'].'-'.$val['二级科目名称'].'-'.sprintf("%.2f",$val['余额']).'-'.$val['发货人名称']];
            //            $ac_doc = $data[$val['凭证编码'].'-'.$val['一级科目名称'].'-'.$val['二级科目名称'].'-'.sprintf("%.2f",$val['余额'])];
            //            $ac_doc = $data[$val['凭证日期']];
            if ($ac_doc['department_id'] == $department[$val['部门名称']]['id']) {
                continue;
            }
            $ids = array_merge($ids, [$ac_doc['id']]);
            $rollback_sql = "UPDATE `cmm_pro`.`ac_doc_detail` SET `department_id` = {$ac_doc['department_id']} WHERE `id` = {$ac_doc['id']};";
            file_put_contents(runtime_path() . DIRECTORY_SEPARATOR . 'tmp.sql', $rollback_sql . PHP_EOL, FILE_APPEND);
            $update_sql = "UPDATE `cmm_pro`.`ac_doc_detail` SET `department_id` = {$department[$val['部门名称']]['id']} WHERE `id` = {$ac_doc['id']};";
            file_put_contents(runtime_path() . DIRECTORY_SEPARATOR . 'tmp2.sql', $update_sql . PHP_EOL, FILE_APPEND);
            //            jdd('stop');
        }
        file_put_contents(runtime_path() . DIRECTORY_SEPARATOR . 'tmp2.txt', implode(',', $ids));

        return json('success');
    }
}