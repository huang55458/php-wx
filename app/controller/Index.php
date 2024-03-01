<?php

namespace app\controller;

use app\BaseController;
use think\facade\Db;

class Index extends BaseController
{
    public function index()
    {
        return '<style>*{ padding: 0; margin: 0; }</style><iframe src="https://www.thinkphp.cn/welcome?version=' . \think\facade\App::version() . '" width="100%" height="100%" frameborder="0" scrolling="auto"></iframe>';
    }

    public function hello($name = 'ThinkPHP8')
    {
        event('Test');
        return 'hello,' . $name;
    }

    public function select()
    {
        $data = [];
        Db::table('login_restrictions')->selectOrFail()->toArray();
        Db::table('customer')->where('id', '<', 100000000)->column('*', 'id');
        Db::table('login_restrictions')->column(['type','id']);
        Db::table('customer')
            ->where('id', '<', 100000000)
            ->chunk(10000, function ($users) use (&$data) { // 分批查询
                foreach ($users as $user) {
                    $user['group_id'] = 'aaaaaaaaaaaaaaaaa';
                    $data[] = $user;
                }
            }, 'id');
        $cursor = Db::table('customer')->where('id', '<', 100000000)->cursor();//游标查询 速度很快,但似乎是一次查询出所有的数据
        foreach($cursor as $user) {
            $user['group_id'] = 'aaaaaaaaaaaaaaaaa';
            $data[] = $user;
        }

        file_put_contents(runtime_path().DIRECTORY_SEPARATOR.'tmp.txt', json_encode($data, 256));
        //        return json($data);
        return memory_get_usage() / 1024 / 1024 . 'M';
    }
}
