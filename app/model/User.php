<?php

declare (strict_types=1);

namespace app\model;

use think\Model;

/**
 * @mixin \think\Model
 */
class User extends Model
{
    // 设置json类型字段
    protected $json = ['ext'];

    // 定义全局的查询范围
    protected $globalScope = ['status'];

    public function scopeStatus($query)
    {
        $query->where('status',1);
    }

    public function getIsAdminAttr($value)
    {
        $status = [1=>'是',0=>'否'];
        return $status[$value];
    }
}
