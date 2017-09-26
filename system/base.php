<?php
/**
 * 
 */
define('EXT', '.php');//文件后缀
define('LOG_EXT', '.txt'); //日志文件类型
define('DS', DIRECTORY_SEPARATOR); //目录分割符号

define('EOL_N', "\n"); //LINUX 换行
define('EOL_RN', "\r\n"); //LINUX 换行

define('HTTP', 'http://');
define('HTTPS', 'https://');

//网站文件根目录
$root_dir = str_replace('system', '', __DIR__);
$root_dir = rtrim($root_dir, DS);
define('ROOT_DIR', $root_dir . DS);


define('APP_PATH', ROOT_DIR . 'application' . DS);//网站配置文件路径
require APP_PATH . 'language' . EXT; //加载常规语言包数据
require APP_PATH . 'class.common' . EXT; //加载常规通用类


//BOF 加载公共路径参数
define('SYSTEM_PATH', ROOT_DIR . 'system' . DS);//框架系统文件库
define('THIRD_PARTY_PATH', SYSTEM_PATH . 'third-party' . DS);//第三方插件

define('TEMPLATES_PATH', ROOT_DIR . 'tpl' . DS);//模版文件路径
define('PUBLIC_DATA_PATH', ROOT_DIR . 'public_data' . DS);//公共数据存储文件夹
define('CACHE_PATH', ROOT_DIR . 'cc_cache' . DS);// 缓存文件路径

define('LIBRARY_PATH', SYSTEM_PATH . 'library' . DS);//公共数据存储文件夹

define('CC_FUNCTION_PATH', SYSTEM_PATH . 'function' . DS);//自定义函数库
define('CC_CLASS_PATH', SYSTEM_PATH . 'class' . DS);//公共方法库路径
define('CC_FONTS_PATH', SYSTEM_PATH . 'fonts' . DS);//公共字体库路径


//EOF 加载公共路径参数
define('TEMP_EP_HTTP', 'tempEp/');//公共临时文件夹
define('TEMPLATES_HTTP', 'tpl/');//模版文件访问路径URL
define('PUBLIC_DATA_HTTP', 'public_data/');//公共数据访问路径URL


//加载MVC路径参数 MVC
define('MVC_ROOT', ROOT_DIR . 'modules' . DS);//模块根目录
define('MVC_CONTROLLER_PATH', MVC_ROOT . 'controllers' . DS);//模块控制端路径
define('MVC_VIEWS_PATH', MVC_ROOT . 'views' . DS);//模块视图端路径
define('MVC_STYLES_PATH', MVC_ROOT . 'styles' . DS);//模块独立样式路径
define('MVC_STYLES_HTTP', 'modules'.DS.'styles'.DS);//模块独立样式URL
//载入 library 新型类库

