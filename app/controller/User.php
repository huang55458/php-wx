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
    public function index($page = 1, $limit = 10)
    {
        $data = \app\model\User::limit(($page-1)*$limit,$limit)->select();
        $count = \app\model\User::count();
        $totalRow = [
            'tang' => 1,
            'song' => 1,
            'xian' => 1,
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
