<?php

declare (strict_types=1);

namespace app\listener;

use app\Request;
use think\facade\Cache;
use think\helper\Str;

class Refresh
{
    /**
     * 事件监听处理
     *
     * @param $event
     * @return mixed
     */
    public function handle($event, Request $request): mixed
    {
        if ((string)$request->env()['START_LOGIN'] === 'true' && !Str::startsWith($_SERVER['PATH_INFO'],'/static')) {
            $ip = get_ip(0, true);
            if (Cache::get($ip) === null) {
                Cache::set($ip, 'true', 1);
            } else {
                header('Content-type: application/json');
                exit(json_encode(['errno' => 0, 'errmsg' => '过于频繁'], JSON_THROW_ON_ERROR | 256));
            }
        }
        return true;
    }
}
