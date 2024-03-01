<?php

declare (strict_types=1);

namespace app\controller;

use app\BaseController;
use app\service\WeChatService;
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
    private object $cache;

    public function __construct(App $app, FileCache $cache)
    {
        parent::__construct($app);
        $this->cache = $cache;
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
            'get_access_token' => function () use ($cache) {
                return $cache->get('access_token');
            },
            'save_access_token' => function ($token) use ($cache) {
                $cache->set('access_token', $token, 7000);
            },
        ]);
    }
    /**
     * 显示资源列表
     *
     * @return Response
     */
    public function index(WeChatService $weChatService)
    {
        $this->msg = $this->weChat->serve();
        Log::info("来自{$this->msg['FromUserName']}的消息：{$this->msg['Content']}");
        // 用户关注微信号后 - 回复用户普通文本消息
        if ($this->msg['MsgType'] === WX_Cnsts::EVENT && $this->msg['Event'] === WX_Cnsts::SUBSCRIBE) {
            $this->message = $this->weChat->reply($this->default_msg);
            goto ret;
        }

        // 回复微信消息
        if ($this->msg['MsgType'] === WX_Cnsts::TEXT) {
            switch ($this->msg['Content']) {
                case '你好':
                    $this->message = $this->weChat->reply("你也好！");
                    break;
                case '授权测试'://个人账户无法认证
                    $authorize_url = $this->api->get_authorize_url('snsapi_base', 'http://64.176.54.10/demo/WeChat/create');
                    $this->api->send($this->msg['FromUserName'], $authorize_url);
                    return json();
                case '图片测试':
                    $this->message = $this->weChat->reply([
                        'type' => 'image',
                        'media_id' => 'POt8OJd3wh-Y7zlHLAtrNmCL9zbFmkOCLyJIP4RCf361nMqlS18DKYUvf2Y5ZCQi'
                    ]);
                    break;
                case '图片':
                    $media_id = $weChatService->randomGenshinImage($this->api, $this->cache);
                    $this->message = $this->weChat->reply([
                        'type' => 'image',
                        'media_id' => $media_id,
                    ]);
                    break;
                default:
                    $this->message = $this->weChat->reply("听不懂！");
                    break;
            }
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
        list($err, $user_info) = $this->api->get_userinfo_by_authorize('snsapi_base');
        if ($user_info !== null) {
            dump($user_info);
        } else {
            echo '授权失败！';
        }
    }

    /**
     * 上传图片
     *
     * @return Response
     */
    public function save()
    {
        $path = 'C:\Users\Administrator\Pictures\1693032653507.png';
        $arr = $this->api->add_material(WX_Cnsts::IMAGE, $path);
        return json($arr);
    }

    /**
     * 随机显示资源
     *
     * @return Response
     */
    public function read()
    {
        $arr = $this->api->get_materials(WX_Cnsts::IMAGE, 0, 20);
        return json($arr);
    }

    /**
     * 显示编辑资源表单页.
     *
     * @param  string  $media_id
     * @return Response
     */
    public function edit(string $media_id)
    {
        $arr = $this->api->get_material($media_id);
        Log::info(json_encode($arr, 256));
    }

    /**
     * 保存更新的资源
     *
     * @param string $key
     * @return Response
     */
    public function genMediaList(WeChatService $weChatService, string $key = WX_Cnsts::GENSHIN)
    {
        return json($weChatService->genMediaList($this->api, $this->cache, $key));
    }

    /**
     * 删除指定资源
     *
     * @param string $media_id
     * @return Response
     */
    public function delete(string $media_id)
    {
        $arr = $this->api->del_material($media_id);
        return json($arr);
    }
}
