<?php

declare (strict_types=1);

namespace app\listener;

class Test
{
    /**
     * 事件监听处理
     *
     * @return mixed
     */
    public function handle($event)
    {
        echo 'handle';
    }
}
