<?php

declare (strict_types=1);

namespace app\middleware;

class Test
{
    /**
     * 处理请求
     *
     * @param \think\Request $request
     * @param \Closure       $next
     * @return Response
     */
    public function handle($request, \Closure $next)
    {
        //        if (preg_match('~micromessenger~i', $request->header('user-agent'))) {
        //            $request->InApp = 'WeChat';
        //        } else if (preg_match('~alipay~i', $request->header('user-agent'))) {
        //            $request->InApp = 'Alipay';
        //        }
        return $next($request);
    }
}
