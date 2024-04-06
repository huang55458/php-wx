window.sessionStorage.layui_show = '_static_html_es';
layui.use(['element', 'layer', 'util'], function(){
    const element = layui.element
        ,
        layer = layui.layer
        ,
        util = layui.util
        ,
        $ = layui.$

    //头部事件
    util.event('lay-header-event', {
        //左侧菜单事件
        menuLeft: function(othis){
            layer.msg('展开左侧菜单的操作', {icon: 0});
        }
        ,menuRight: function(){
            layer.open({
                type: 1
                ,content: '<div style="padding: 15px;">处理右侧面板的操作</div>'
                ,area: ['260px', '100%']
                ,offset: 'rt' //右上角
                ,anim: 5
                ,shadeClose: true
            });
        }
    });

    $(document).on('click', '#logout', function(){
        $.ajax({
            url: '/Index/logout',
            type: 'GET',
            data: '',
            dataType: 'json',
            success: function(res) {
                if (res.errno === 0) {
                    reload()
                } else {
                    layer.msg(res.errmsg);
                }
            },
        });
    });
    // $('#logout').click( function(){
    //   // 按钮点击后的操作
    //   alert('test')
    //   layer.msg('登出');
    // });

    // 监听菜单点击事件
    element.on('nav(side-nav)', function(elem){
        let url = elem.attr('href_bak') // 获取菜单链接地址
        let layui_body = $('.layui-body')
        let show_id = url.replace('.html','').replace(/\//g,'_')
        // $('.layui-body').load(url); // 直接变更内容

        if (layui_body.find('#'+show_id).length !== 1) {
            $.get(url, function (data) {
                layui_body.append(data);
                $('#'+window.sessionStorage.layui_show).removeClass('layui-show')
                $('#'+show_id).addClass('layui-show')
                window.sessionStorage.layui_show = show_id;
            });
        } else {
            $('#' + window.sessionStorage.layui_show).removeClass('layui-show')
            $('#' + show_id).addClass('layui-show')
            window.sessionStorage.layui_show = show_id;
        }


    });

    $(document).on('click', '#start_test', function(){
        $.ajax({
            url: '/api/Home/Index/socketInit',
            type: 'GET',
            data: '',
            dataType: 'json',
            success: function(res) {
            },
        });




        // 初始化io对象
        //
        //     const socket = io('https://' + document.domain + ':9502')
        //     // uid 可以为网站用户的uid，作为例子这里用session_id代替
        //     const uid = '<?php echo session_id();?>'
        //     // 当socket连接后发送登录请求
        //     socket.on('connect', function () {
        //       layer.msg("Connected")
        //       socket.emit('login', uid)
        //     })
        //     // 当服务端推送来消息时触发，这里简单的aler出来，用户可做成自己的展示效果
        //     socket.on('new_msg', function (msg) {
        //       layer.msg(msg)
        //     })

    });

});

function reload() {
    setTimeout(function () {
        location.reload();
    }, 1000)
}