<?php
declare (strict_types = 1);

namespace app\controller;

use app\cnsts\ERRNO;
use think\Request;

class User
{
    public function __construct(private $errno = ERRNO::SUCCESS)
    {
    }

    /**
     * 显示资源列表
     *
     * @return \think\Response
     */
    public function index(int $page = 1, int $limit = 10, $sortField = '',$sortOrder = '')
    {
        $where = [];
        !empty(\request()->param('name')) && $where['name'] = trim(\request()->param('name'));
        !empty(\request()->param('telephone')) && $where['telephone'] = trim(\request()->param('telephone'));
        if (!empty(\request()->param('create_time'))) {
            [$start_time,$end_time] = explode(' - ',\request()->param('create_time'));
            $where[] = ['create_time', 'between', [$start_time,$end_time]];
        }

        $data = \app\model\User::where($where)->order($sortField,$sortOrder)->limit(($page-1)*$limit,$limit)->select();
        $count = \app\model\User::where($where)->count();
        $totalRow = [
            'ext' => [
                'tang' => 1,
                'song' => 1,
                'xian' => 1,
            ],
            'id' => $count,
        ];
        $resp = [
            'code' => $this->errno,
            'msg' => ERRNO::e($this->errno),
            'data' => $data,
            'count' => $count,
            'totalRow' => $totalRow,
        ];
        return json($resp);
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
    public function save(string $data,\app\model\User $user)
    {
        $data = json_decode($data, true);
        foreach ($data as $key => $value) {
            $key === 'password' && $value = password_hash($value,PASSWORD_BCRYPT);
            $user->$key = $value;
        }
        $user->save() || $this->errno = ERRNO::DB_FAIL;
        return doResponse($this->errno,ERRNO::e($this->errno),[]);
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
    public function update(string $data = '', int $id = 0)
    {
        $data = json_decode($data,true);
        \app\model\User::update($data,['id' => $id]);
        return doResponse($this->errno,ERRNO::e($this->errno),[]);
    }

    /**
     * 删除指定资源
     *
     * @param  int  $id
     * @return \think\Response
     */
    public function delete(int $id = 0)
    {
        \app\model\User::update(['status' => 0],['id' => $id]);
        return doResponse($this->errno,ERRNO::e($this->errno),[]);
    }
}
