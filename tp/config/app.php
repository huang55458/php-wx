<?php
// +----------------------------------------------------------------------
// | 应用设置
// +----------------------------------------------------------------------

/**
 * Config::get('app');  读取一级配置的所有参数（每个配置文件都是独立的一级配置）
 * Config::get('app.app_name');  读取单个配置参数
 * Config::has('route.route_rule_merge'); 判断是否存在某个设置参数
 * Config::set(['name1' => 'value1', 'name2' => 'value2'], 'config'); 批量设置参数
 */
return [
    // 应用地址
    'app_host'         => env('APP_HOST', ''),
    // 应用的命名空间
    'app_namespace'    => '',
    // 是否启用路由
    'with_route'       => true,
    // 默认应用
    'default_app'      => 'index',
    // 默认时区
    'default_timezone' => 'Asia/Shanghai',

    // 应用映射（自动多应用模式有效）
    'app_map'          => [],
    // 域名绑定（自动多应用模式有效）
    'domain_bind'      => [],
    // 禁止URL访问的应用列表（自动多应用模式有效）
    'deny_app_list'    => [],

    // 异常页面的模板文件
    'exception_tmpl'   => app()->getThinkPath() . 'tpl/think_exception.tpl',

    // 错误显示信息,非调试模式有效
    'error_message'    => '页面错误！请稍后再试～',
    // 显示错误信息
    'show_error_msg'   => false,
];
