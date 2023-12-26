<?php
declare (strict_types = 1);

namespace app\service;

use think\facade\Log;

class ToolService extends \think\Service
{
    /**
     * 注册服务
     *
     * @return mixed
     */
    public function register()
    {
    	//
    }

    /**
     * 执行服务
     *
     * @return mixed
     */
    public function boot()
    {
        //
    }

    public function getSpecifyKeyData($param, $file_path, $key)
    {
        $req = json_decode($param['data']['req'],true);
        $page_size = $req['page_size'];
        $data = test_curl('post', $param);
        $count = $data['res']['total']['count'];
        if ($count > 50000 ) {
            jdd('超过50000条，添加一些查询参数');
        }
        file_put_contents($file_path, implode(',',array_filter(array_column($data['res']['data'],$key))));
        if ($count > $page_size) {
            $y = floor($count / $page_size);
            for ($i = 0; $i < $y; $i++) {
                $req['page_num']++;
                $param['data']['req'] = json_encode($req,JSON_UNESCAPED_UNICODE);
                $data = test_curl('post', $param);
                Log::write('page_num :'.$req['page_num']);
                file_put_contents($file_path, ','.implode(',', array_filter(array_column($data['res']['data'],$key))), FILE_APPEND);
            }
        }
    }
}
