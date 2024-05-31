<?php

declare (strict_types=1);

namespace app\controller;

use Gaoming13\WechatPhpSdk\Utils\FileCache;
use GatewayWorker\Lib\Gateway;
use think\Request;
use think\Response;

/**
 * 1、网站页面建立与GatewayWorker的websocket连接
 *
 * 2、GatewayWorker发现有页面发起连接时，将对应连接的client_id发给网站页面
 *
 * 3、网站页面收到client_id后触发一个ajax请求(假设是bind.php)将client_id发到mvc后端
 *
 * 4、mvc后端bind.php收到client_id后利用GatewayClient调用Gateway::bindUid($client_id, $uid)将client_id与当前uid(用户id或者客户端唯一标识)绑定。如果有群组、群发功能，也可以利用Gateway::joinGroup($client_id, $group_id)将client_id加入到对应分组
 *
 * 5、页面发起的所有请求都直接post/get到mvc框架统一处理，包括发送消息
 *
 * 6、mvc框架处理业务过程中需要向某个uid或者某个群组发送数据时，直接调用GatewayClient的接口Gateway::sendToUid Gateway::sendToGroup 等发送即可
 */
class GatewayWorker
{
    // user_id => client_id
    private array $map = [1 => '7f00000107d000000001'];
    private object $cache;

    public function __construct(FileCache $cache)
    {
        $this->cache = $cache;
        $worker_id_map = json_decode($this->cache->get('worker_id_map'), true);
        if (empty($worker_id_map)) {
            $cache->set('worker_id_map', json_encode($this->map, 256));
        }
    }

    /**
     * 显示资源列表
     *
     * @return string|\think\Response
     */
    public function send($uid, $message = '')
    {
        if (empty($uid)) {
            return 'empty';
        }
        $worker_id_map = json_decode($this->cache->get('worker_id_map'), true);
        Gateway::sendToClient($worker_id_map[$uid], $message);
    }


    /**
     *
     */
    public function save($uid = null, $client_id = null)
    {
        if ($uid === null) {
            return 'empty';
        }
        $worker_id_map = json_decode($this->cache->get('worker_id_map'), true);
        $worker_id_map[$uid] = $client_id;
        $this->cache->set('worker_id_map', json_encode($worker_id_map, 256));
    }

    /**
     * 清空缓存
     *
     * @return Response
     */
    public function clear()
    {
        $this->cache->del('worker_id_map');
    }

    /**
     * 显示编辑资源表单页.
     *
     * @param int $id
     * @return \think\Response
     */
    public function edit($id)
    {

    }

    /**
     * 保存更新的资源
     *
     * @param \think\Request $request
     * @param int $id
     * @return \think\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * 删除指定资源
     *
     * @param int $id
     * @return \think\Response
     */
    public function delete($id)
    {
        //
    }
}
