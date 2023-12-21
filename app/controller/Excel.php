<?php
declare (strict_types = 1);

namespace app\controller;

use app\BaseController;
use app\service\ExcelService;
use think\App;
use think\facade\Db;
use think\Request;

class Excel extends BaseController
{
    protected ExcelService $excelService;

    public function __construct(App $app,ExcelService $excelService)
    {
        parent::__construct($app);
        $this->excelService = $excelService;
    }

    public function test() {
        $res = Db::name('login_restrictions')->field('id,type,group_id,pc_name,mac_md5,state,status,ext,remark,province,province_name,city,city_name,user_id,company_id,create_time,update_time,create_by_user,update_by_user,create_by_company,update_by_company')->select();
        $this->excelService->exportXlsx(array_keys($res[0]),$res);
    }

    public function test1() {
        $res = $this->excelService->loadXlsx('/mnt/d/download/01simple (6).xlsx');
        return json($res);
    }
}