<?php

declare (strict_types=1);

namespace app\controller;

use think\Request;

class SQL
{
    public function create_sql($table, $arr)
    {
        foreach ($arr as $k => $v) {
            if ($k === 'id') {
                continue;
            }

            $f[] = '`'.$k.'`';
            if (is_int($v)) {
                $val[] =  $v ;
            } elseif(is_null($v)) {
                $val[] = 'null';
            } else {
                $val[] = "'" . $v . "'";
            }

        }
        $f = implode(',', $f);
        $val = implode(',', $val);
        return "insert into ".$table."(".$f.") values (".$val.");";
    }
    /**
     * 显示资源列表
     *
     * @return \think\Response
     */
    public function index()
    {
        $file_path = runtime_path().DIRECTORY_SEPARATOR.'tmp.sql';
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
            file_put_contents($file_path, $sql.PHP_EOL, FILE_APPEND);
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
        //
    }

    /**
     * 保存新建的资源
     *
     * @param  \think\Request  $request
     * @return \think\Response
     */
    public function save(Request $request)
    {
        //
    }

    /**
     * 显示指定的资源
     *
     * @param  int  $id
     * @return \think\Response
     */
    public function read($id)
    {
        //
    }

    /**
     * 显示编辑资源表单页.
     *
     * @param  int  $id
     * @return \think\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * 保存更新的资源
     *
     * @param  \think\Request  $request
     * @param  int  $id
     * @return \think\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * 删除指定资源
     *
     * @param  int  $id
     * @return \think\Response
     */
    public function delete($id)
    {
        //
    }
}
