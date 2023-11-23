<?php

namespace app\controller;

use app\BaseController;
use think\facade\Db;


class Tool extends BaseController
{
    private string $file_path = '';

    public function test() {
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
    public function test1() {
        return json(Db::query("select * from user where id=:id", ['id' => 2]));
    }


    public function test2() {
        $this->file_path = runtime_path().DIRECTORY_SEPARATOR.'tmp.txt';
        $data = file_get_contents('C:\Users\Administrator\Documents\demo.json');
        $data = json_decode($data,true)['RECORDS'];
        $data = array_column($data, null, 'id');
        $err_data = [];
        foreach ($data as $key => $value) {
            $tmp = [];
            $tmp = $this->test3($data,$key, $tmp);
            $tmp = array_reverse($tmp);
            $ids = implode(',',$tmp);
            if ($ids !== $value['parent_ids']) {
                $sql = "UPDATE `cmm_pro`.`company` SET `parent_ids` = '{$ids}' WHERE `id` = {$key};";
//                $err_data[$key] = [
//                    $value['parent_ids'] => $ids
//                ];
                file_put_contents($this->file_path,$sql.PHP_EOL,FILE_APPEND);
                $err_data[] = $sql;
            }

        }
        return json($err_data);
    }
    public function test3($data , $id, $tmp) {
        if (!empty($data[$id]) && !empty($data[$id]['sup_id'])) {
            $tmp[] = $data[$id]['sup_id'];
            $tmp = $this->test3($data,$data[$id]['sup_id'], $tmp);
        }

        return $tmp;
    }
}
