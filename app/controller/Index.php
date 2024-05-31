<?php

namespace app\controller;

use app\BaseController;
use app\cnsts\ERRNO;
use think\App;
use think\facade\Db;
use think\facade\View;

class Index extends BaseController
{
    public function __construct(App $app)
    {
        parent::__construct($app);
    }

    public function index()
    {
        if ((string)env()['START_LOGIN'] === 'true' && session('USER_ID') === null) {
            View::engine()->layout(false);
            return view('index/login');
        }
        return view('index/index');
    }

    public function login()
    {
        $user = Db::table('user')->where('name', '=', $_REQUEST['name'])->column('id,password');
        if (isset($user[0]['password']) && password_verify($_REQUEST['password'], $user[0]['password'])) {
            session('USER_ID', $user[0]['id']);
            return redirect('/');
        }
        return doResponse(ERRNO::USER_PWD_ERROR, ERRNO::e(ERRNO::USER_PWD_ERROR), []);
    }

    public function logout()
    {
        session(null);
        return doResponse(ERRNO::SUCCESS, ERRNO::e(ERRNO::SUCCESS), []);
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
        Db::table('login_restrictions')->column(['type', 'id']);
        Db::table('customer')
            ->where('id', '<', 100000000)
            ->chunk(10000, function ($users) use (&$data) { // 分批查询
                foreach ($users as $user) {
                    $user['group_id'] = 'aaaaaaaaaaaaaaaaa';
                    $data[] = $user;
                }
            }, 'id');
        $cursor = Db::table('customer')->where('id', '<', 100000000)->cursor();//游标查询 速度很快,但似乎是一次查询出所有的数据
        foreach ($cursor as $user) {
            $user['group_id'] = 'aaaaaaaaaaaaaaaaa';
            $data[] = $user;
        }

        file_put_contents(runtime_path() . DIRECTORY_SEPARATOR . 'tmp.txt', json_encode($data, 256));
        //        return json($data);
        return memory_get_usage() / 1024 / 1024 . 'M';
    }
}
