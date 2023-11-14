<?php
declare (strict_types = 1);

namespace app\controller;

use app\BaseController;
use Gaoming13\WechatPhpSdk\Utils\FileCache;
use Gaoming13\WechatPhpSdk\Wechat as WX;
use app\cnsts\WeChat as WX_Cnsts;
use Gaoming13\WechatPhpSdk\Api;
use think\App;
use think\facade\Log;
use think\Response;

class WeChat extends BaseController
{
    private string $message;// 回复的信息
    private string $default_msg = 'Will always become an experienced driver!';
    private array $msg;// 接收的信息
    private object $weChat;
    private object $api;

    public function __construct(App $app,FileCache $cache)
    {
        parent::__construct($app);
        $this->weChat = new WX([
            // 开发者中心-配置项-AppID(应用ID)
            'appId' => env('APP_ID'),
            // 开发者中心-配置项-服务器配置-Token(令牌)
            'token' => env('TOKEN'),
            // 开发者中心-配置项-服务器配置-EncodingAESKey(消息加解密密钥)
            'encodingAESKey' => env('ENCODIND_AES_KEY')
        ]);
        $this->api = new Api([
            'ghId' => env('GH_ID'),
            'appId' => env('APP_ID'),
            'appSecret' => env('APP_SECRET'),
            'get_access_token' => function() use ($cache) {
                return $cache->get('access_token');
            },
            'save_access_token' => function($token) use ($cache) {
                $cache->set('access_token', $token, 7000);
            },
        ]);
        $this->msg = $this->weChat->serve();
        Log::info("来自{$this->msg['FromUserName']}的消息：{$this->msg['Content']}");
    }
    /**
     * 显示资源列表
     *
     * @return Response
     */
    public function index()
    {
        // 用户关注微信号后 - 回复用户普通文本消息
        if ($this->msg['MsgType'] === WX_Cnsts::EVENT && $this->msg['Event'] === WX_Cnsts::SUBSCRIBE) {
            $this->message = $this->weChat->reply($this->default_msg);
            goto ret;
        }

// 回复微信消息
        if ($this->msg['MsgType'] === WX_Cnsts::TEXT && $this->msg['Content'] === '你好') {
            $this->message = $this->weChat->reply("你也好！");
        } else {
            $this->message = $this->weChat->reply("听不懂！");
        }
        ret:
        return xml($this->message);
    }

    /**
     * 显示创建资源表单页.
     *
     * @return Response
     */
    public function create()
    {
        return xml('<xml><Encrypt><![CDATA[vaPahzMkbTgPdJELTVq7eyNhOi8psdILpU08aQQzzFUqaHZPWzTLvLqbi56lbf9aLSxQiDDtiTa42NO8U1Fh6XTH+pKeJNyQDUOrrU4zD7iIPLdFnI5HdHkWCr9NP6LmqCinjPPYCKS1Xve5nRyfgL6Ryzd2DxV17u07ycBHF0hh8sLzIY9TEHhinMiteTybe2Ozf+dqyuxKDcrYLapHUKOUtZ5IbmrpQ0ZTcFo/e7WYT+HP/CZ9VNln1mecbfe8bKk8qEljGkBSN1pT+s0ZlIjJLYjmIR4UQhcnYheMGKOGy/cYx6CnX2a0LrX5/+a0P7oFTqTscWSKo9Q7Ie7uYe3kdIqs7DkVOKG2iiGjgIL/PWzwWKaL7iw9lLU11MOf]]></Encrypt><MsgSignature><![CDATA[9a6c77e768fc391f0afbe0f52b8f47b90af9b7f3]]></MsgSignature><TimeStamp>1699861262</TimeStamp><Nonce><![CDATA[2111573894]]></Nonce></xml>');
    }

    /**
     * 保存新建的资源
     *
     * @param  \think\Request  $request
     * @return Response
     */
    public function save(Request $request)
    {
        //
    }

    /**
     * 显示指定的资源
     *
     * @param  int  $id
     * @return Response
     */
    public function read($id)
    {
        //
    }

    /**
     * 显示编辑资源表单页.
     *
     * @param  int  $id
     * @return Response
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
     * @return Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * 删除指定资源
     *
     * @param  int  $id
     * @return Response
     */
    public function delete($id)
    {
        //
    }
}
