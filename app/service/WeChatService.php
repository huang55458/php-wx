<?php

declare (strict_types=1);

namespace app\service;

use app\cnsts\WeChat;
use app\cnsts\WeChat as WX_Cnsts;
use Gaoming13\WechatPhpSdk\Api;
use Gaoming13\WechatPhpSdk\Utils\FileCache;

class WeChatService extends \think\Service
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

    public function genMediaList(Api $api, FileCache $cache, string $key)
    {

        $item_count = 20;
        $images = $api->get_materials(WX_Cnsts::IMAGE, 0, $item_count);
        $total = $images[1]['total_count'];

        $list = self::imageFactory($images[1]['item'], $key);
        $num = ceil($total / $item_count);
        if ($num > 1) {
            for ($i = 1; $i < $num; $i++) {
                $images = $api->get_materials(WX_Cnsts::IMAGE, $i, $item_count);
                $list = array_merge($list, self::imageFactory($images[1]['item'], $key));
            }
        }
        $cache->set(WeChat::IMAGE_GENSHIN_LIST, $list, 24 * 60 * 60);

        return $list;
    }

    public function randomGenshinImage(Api $api, FileCache $cache)
    {
        $list = $cache->get(WeChat::IMAGE_GENSHIN_LIST);
        if ($list === false) {
            $list = $this->genMediaList($api, $cache, WX_Cnsts::GENSHIN);
        }

        return $list[array_rand($list)];
    }


    public static function imageFactory(array $images, string $key)
    {
        $list = [];

        foreach ($images as $image) {
            if (strpos($image['name'], $key) !== false) {
                $list[] = $image['media_id'];
            }
        }

        return $list;
    }
}
