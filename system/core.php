<?php
namespace system;


//启动环境配置
use cc\Config;
use cc\Db;
use cc\Lang;
use cc\Oauth;
use cc\View;

require 'base.php';
require APP_PATH . 'config.define' . EXT;//公共常量


//MVC 实例化
class App
{
    public static $power = [];//权限级别

    /**
     * @var array 访问页面的ID
     */
    public static $accountInfo = [];

    public function __construct()
    {

    }

    //封装加载开始
    public static function init()
    {
        //处理URL 参数
        self::loaderUrlData();

        //加载MVC路径参数
        self::loaderMvcFilePath();

        //加载自定义函数
        self::loaderFunction();

        //加载自定义类库
        self::loaderClass();

        //加载图书馆公共类
        self::loaderLibrary();

        //载入一些环境配置
        self::loaderSetting();

        //访问页面验证
        Oauth::OauthPage();

        //登录检测
        Oauth::OauthSign();

        //权限检测
        Oauth::OauthPower();


        //启动项目模块
        startApp::start();
    }

    /**
     * 载入一些环境配置
     * loaderSetting
     */
    static private function loaderSetting()
    {
        $config = Config::getCB();
        //错误屏蔽
        if (true === $config['error_show']) {
            error_reporting(E_ALL);
        } else {
            error_reporting(0);
            ini_set('display_errors',0);
            //ini_set('log_errors',1);
            //ini_set('error_log','E:\\'.date('Y-m-d').'_kjcms.com.txt') ;
        }
        date_default_timezone_set($config['timezone']);//设置时区
        ini_set('memory_limit', $config['memory_limit']);//设置内存大小

        if (true === $config['session']) {
            session_start();
        }
        ini_set('gd.jpeg_ignore_warning', $config['gd_warning']);//图片错误屏蔽

        set_time_limit($config['time_limit']);//超时

        define('URL_TYPE', Config::getCB('url_type'));
        define('DOMAIN_URL', Config::getCB('domain_url'));
    }

    /**
     * loaderLibrary 加载图书馆公共类
     */
    static private function loaderLibrary()
    {
        //优先载入 CONFIG 配置文件类并加载默认配置参数
        cc__requireFile(LIBRARY_PATH . 'Config' . EXT);
        Config::getC();

        $loadLibrary = Config::getCB('library');
        if (false === $loadLibrary) {
            self::systemExit('[System Error]: 基本配置中加载公共图书馆类的配置错误 【config.base】');
        }

        foreach ($loadLibrary as $name => $status) {
            if (true === $status) {
                cc__requireFile(LIBRARY_PATH . ucfirst($name) . EXT);
                $dir = LIBRARY_PATH . $name . DS;
                if (is_dir($dir)) {
                    //加载全部的内部文件
                    $Arr = cc__listDir($dir, 'php');
                    if (is_array($Arr)) {
                        foreach ($Arr as $libFile) {
                            cc__requireFile($libFile['path']);
                        }
                    }
                }
            }
        }
    }


    /**
     * 处理URL 参数
     * loaderUrlData
     * @define URL_MODULES
     * @define URL_MODEL
     * @define URL_ACTION
     * @define URL_PARAMS
     */
    private static function loaderUrlData()
    {
        //start URL 参数设置
        if (!empty($_GET['request_url']) && strpos($_GET['request_url'], '/') !== false) { //静态URL
            $request_url = explode('/', $_GET['request_url']);
            $_GET['modules'] = isset($request_url[0]) ? $request_url[0] : 'Index';
            $_GET['model'] = isset($request_url[1]) ? $request_url[1] : strtolower($_GET['modules']);
            $_GET['action'] = isset($request_url[2]) ? $request_url[2] : 'index';
            $_GET['params'] = isset($request_url[3]) ? $request_url[3] : '';
        } else {
            $_GET['modules'] = empty($_GET['modules']) ? 'Index' : $_GET['modules'];
            $_GET['model'] = empty($_GET['model']) ? strtolower($_GET['modules']) : $_GET['model'];
            $_GET['action'] = empty($_GET['action']) ? 'index' : $_GET['action'];
            $_GET['params'] = empty($_GET['params']) ? '' : $_GET['params'];
        }
        unset($_GET['request_url']);
        //end URL 参数设置

        define('URL_MODULES', $_GET['modules']);   //模块
        define('URL_MODEL', $_GET['model']);       //子模块
        define('URL_ACTION', $_GET['action']);     //功能
        define('URL_PARAMS', $_GET['params']);     //参数
    }

    /**
     * 加载模块路径参数
     */
    private static function loaderMvcFilePath()
    {
        //BOF 加载MVC路径参数 MVC
        define('MODULES_PATH', MVC_CONTROLLER_PATH . URL_MODULES . DS);//模块路径
        define('MODULES_CONTROLLER_FILE', MODULES_PATH . URL_MODEL . DS . 'controller' . EXT);//子模块控制器文件路径
        define('MODULES_MODEL_FILE', MODULES_PATH . URL_MODEL . DS . 'model' . EXT);//数据层文件路径
        //EOF 加载MVC路径参数 MVC

        //视图文件路径
        $action = empty(URL_ACTION) ? "index" : URL_ACTION;
        define('MODULES_VIEW_FILE', MVC_VIEWS_PATH . DS . URL_MODULES . DS . URL_MODEL . DS . $action . '.html');
        //EOF mvc
    }

    /**
     * 加载自定义函数
     */
    private static function loaderFunction()
    {
        //先加载通用的自定义函数
        $ccFunction = CC_FUNCTION_PATH . DS . 'function.cc' . EXT;
        if (!is_file($ccFunction)) {
            $msg = '[System Error]: {' . $ccFunction . '}自定义函数文件不存在';
            self::systemExit($msg);
        }

        require $ccFunction;

        if (!function_exists('cc__listDir')) {
            $msg = '[System Error]: {cc__listDir()}自定义函数不存在';
            self::systemExit($msg);
        }

        //再加载框架相关的自定义函数
        $funArr = cc__listDir(CC_FUNCTION_PATH, 'php');
        foreach ($funArr as $fun) {
            if ($fun['file'] != 'function.cc' . EXT) {
                cc__requireFile($fun['path']);
            }
        }
    }

    /**
     * 加载类库
     */
    private static function loaderClass()
    {
        $classArr = cc__listDir(CC_CLASS_PATH, 'php');
        foreach ($classArr as $class) {
            cc__requireFile($class['path']);
        }
    }

    /**
     * 系统错误
     * systemExit
     * @param $msg
     */
    public static function systemExit($msg)
    {
        exit($msg);
    }


    /**
     * 禁止克隆
     */
    private function __clone()
    {
    }
}

/**
 * 启动项目模块
 * Class startApp
 * @package system\mvc
 */
class startApp
{
    public static $controller, $model, $viewContent, $viewData, $pageContent;
    //public static $lang;
    //访问页html文件
    public static $tplPageFile = URL_MODULES == 'Access' ? URL_MODEL . '.' . URL_ACTION . '.html' : 'app.html';
    public static $smartyArr = ['header', 'index', 'aside', 'page_footer', 'settings', 'app_footer'];

    public static $power;

    public static function start()
    {
        //验证是否错误存在
        self::checkError();

        //加载语言包模块
        self::callLang();

        //加载META
        self::callMeta();


        //调用控制器操作
        self::callController();


        //加载视图文件内容 + 模版文件内容
        View::lang_data(self::$tplPageFile);
        View::display();
    }

    /**
     * 加载语言包模块
     * callLang
     */
    public static function callLang()
    {
        //设定语言
        Lang::range(Config::getC('config.base', 'lang'));

        //载入语言包
        Lang::loader();
    }


    /**
     * callMeta
     * 设置三大标签
     */
    public static function callMeta()
    {
        $lang = Lang::get();
        if(false !== $lang) {
            $title = $lang['modules'] . ' | ' . $lang['model'] . ' - ' . $lang['action'];
            $keywords = $lang['action'] . ',' . $lang['model'] . ',' . $lang['modules'];
            $description = $lang['action'];

            //设定三大标签
            Lang::set('meta_title', $title);
            Lang::set('meta_keywords', $keywords);
            Lang::set('meta_description', $description);
        }
    }

    /**
     * 加载项目通用类
     * callClass
     */
    public static function callClass()
    {
        $appClass = MODULES_PATH . DS . 'class' . EXT;
        cc__requireFile($appClass);
    }

    /**
     * 加载数据处理模块
     * callModel
     */
    public static function callModel()
    {
        cc__requireFile(MODULES_MODEL_FILE);
    }

    /**
     * 调用控制器操作
     * callController
     */
    public static function callController()
    {
        cc__requireFile(MODULES_CONTROLLER_FILE);
        $controllerClass = URL_MODULES . '\\Controller\\' . URL_MODEL;

        if (class_exists($controllerClass, false)) {
            $runObj = new $controllerClass(); //实例化项目控制器
            $action = empty(URL_ACTION) ? "index" : URL_ACTION; //设置方法名称
            if (method_exists($runObj, $action)) {

                //加载项目通用类
                self::callClass();

                //加载数据处理模块
                self::callModel();

                $runObj->$action(); //运行方法
                unset($runObj);
            } else {
                $msg = '[App Error]:  项目控制器:{' . $controllerClass . '} 下 {' . $action . '}方法未发现!';
                self::appExit($msg);
            }
        } else {
            $msg = '[App Error]:  {' . $controllerClass . '}项目控制器名称未发现!';
            self::appExit($msg);
        }
    }


    /**
     * checkError
     * 验证错误内容
     */
    public static function checkError()
    {
        //模块文件根目录是否存在
        if (!is_dir(MVC_ROOT)) {
            $msg = '[App Error]: {' . MVC_ROOT . '}项目模块根目录不存在!!!';
            self::appExit($msg);
        }

        //模块控制器文件夹是否存在
        if (!is_dir(MVC_CONTROLLER_PATH)) {
            $msg = '[App Error]: {' . MVC_CONTROLLER_PATH . '}项目模块控制器目录不存在!!!';
            self::appExit($msg);
        }

        //模块控制器文件是否存在
        if (!is_file(MODULES_CONTROLLER_FILE)) {
            $msg = '[App Error]: {' . MODULES_CONTROLLER_FILE . '}项目模块控制器文件不存在!!!';
            self::appExit($msg);
        }

        //模版文件夹是否存在
        if (!is_dir(TEMPLATES_PATH)) {
            $msg = '[Template Error]: {' . TEMPLATES_PATH . '}模版文件夹不存在!!!';
            self::appExit($msg);
        }

        //检测TPL文件是否存在
        if (self::$tplPageFile == 'app.html' && !is_file(TEMPLATES_PATH . DS . 'page' . DS . self::$tplPageFile)) {
            $msg = '[Template Error]: {' . self::$tplPageFile . '}所访问的页面HTML文件不存在!!!';
            self::appExit($msg);
        }

        //smarty 主模版HTML文件是否存在
        foreach (self::$smartyArr as $tmpFile) {
            $path = TEMPLATES_PATH . DS . 'smarty' . DS . $tmpFile . '.html';
            if (!is_file($path)) {
                $msg = '[Template Error]: {smarty' . DS . $tmpFile . '}页面文件不存在!';
                self::appExit($msg);
            }
        }
    }

    public static function appExit($msg)
    {
        if(true === Config::getCB('system','debug_show')) {
            exit($msg);
        } else {
            die;
        }
    }
}
