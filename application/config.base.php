<?php
/**
 * 基本配置文件参数
 */
return [
    //BOF 基本配置
    'website_name' => '客户管理中心',  //软件名称
    'domain_url' => HTTP . 'localhost/YL/userMangeSystem2' . '/', //前台url !请以 / 结尾
    'url_type' => 'static', //dynamic    动态  / static    静态   (URL类型)

    'timezone' => 'PRC',        //设置时区
    'memory_limit' => '512M',   //设置内存大小
    'session' => true,          //session 是否启用
    'gd_warning' => true,       //图片错误屏蔽
    'time_limit' => '0',        //超时限制
    'error_show' => true,       //系统错误显示
    //EOF 基本配置

    //BOF 模版相关
    'tpl' => [
        'smarty_loader' => true, //使用母板页
        'compress_html' => true, //页面压缩输出
    ],
    //EOF 模版相关

    //BOF 页面鉴权
    'oauth' => [
        'view_page' => true,     //访问页面鉴权
        'account' => true,      //访问帐号鉴权
        'power' => true,        //访问帐号权限鉴权
    ],
    //EOF 页面鉴权

    //BOF 分页相关
    'paginator' => [
        'list_limit' => 30,     //默认每页数量
        'paging_items' => 5,    //默认显示多少页数
    ],
    //EOF 分页相关

    //BOF 系统相关
    'system' => [
        'debug_show' => true,       //系统错误日志显示
        'gd_warning_show' => true,  //页面压缩输出
    ],
    //EOF 系统相关

    //BOF 静默加载的公共图书馆 [Config 公共类默认优先加载]
    'library' => [
        'cache' => true,        //缓存公共类
        'db' => true,           //数据库公共类
        'view' => true,         //视图公共类
        'msg' => true,          //消息堆栈公共类
        'oauth' => true,        //基本鉴权公共类
        'debug' => true,        //DEBUG 公共类
        'paginator' => false,   //分页公共类
    ],
    //EOF 系统相关

    //BOF 缓存相关
    'cache' => [
        'type' => 'File',           // 驱动方式  【File|Redis】
        'path' => CACHE_PATH,       // 缓存保存目录
        'prefix' => 'cc_',          // 缓存前缀
        'expire' => 0,              // 缓存有效期 0表示永久缓存
        'cache_subdir'  => true,    // 使用二级目录
        'data_compress' => false,   // 数据压缩
    ],
    //EOF 缓存相关
];
