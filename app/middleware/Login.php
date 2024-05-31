<?php

declare (strict_types=1);

namespace app\middleware;

use Closure;
use think\Request;
use think\Response;

class Login
{
    public function __construct()
    {
    }

    /**
     * 处理请求
     *
     * @param Request $request
     * @param Closure $next
     * @return Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ((string)$request->env()['START_LOGIN'] === 'true' && session('USER_ID') === null && !in_array(strtolower($request->server()['PATH_INFO']), ['', '/index/login'])) {
            return redirect('/');
        }
        return $next($request);
    }
}
