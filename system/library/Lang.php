<?php
namespace cc;

/**
 * 语言包基类
 * Class Lang
 * @package cc
 */
class Lang
{
    // 语言数据
    private static $lang = [];
    // 语言作用域
    private static $range = 'zh-cn';

    // 允许语言列表
    protected static $allowLangList = [];

    function __construct()
    {

    }

    /**
     * 获取语言定义(不区分大小写)
     * @param string $name 语言变量
     * @param string $range 语言作用域
     * @return mixed
     */
    public static function has($name, $range = '')
    {
        $range = $range ?: self::$range;
        return isset(self::$lang[$range][$name]);
    }

    /**
     * 载入程序指定模块语言包
     * getM
     * @param $modules
     * @return array
     */
    public static function getM($modules = null)
    {
        if(null === $modules) {
            $modules = URL_MODULES;
        }
        return self::getModulesLang($modules);
    }

    /**
     * 获取语言定义(不区分大小写)
     * @param string|array|null $name 语言变量
     * @param string $range 语言作用域
     * @return string|array|false
     */
    public static function get($name = null, $range = '')
    {
        $range = $range ?: self::$range;
        $lang = self::$lang[$range];

        if (empty($name)) {
            return $lang;
        }

        if (is_array($name)) {
            foreach ($name as $n) {
                if (isset($lang[$n])) {
                    $lang = $lang[$n];
                } else {
                    return false;
                }
            }
        } else {
            if (isset($lang[$name])) {
                $lang = $lang[$name];
            } else {
                return false;
            }
        }
        return $lang;
    }


    /**
     * 设置语言定义(不区分大小写)
     * @param string|array $name 语言变量
     * @param string $value 语言值
     * @param string $range 语言作用域
     * @return mixed
     */
    public static function set($name, $value = null, $range = '')
    {
        $range = $range ?: self::$range;
        if (is_array($name)) {
            $oldLang = self::$lang[$range];
            self::$lang[$range] = array_merge($oldLang, $name);
        } else {
            self::$lang[$range][$name] = $value;
        }
    }

    /**
     * 载入语言包基本要素
     * loader
     */
    public static function loader()
    {
        //载入系统核心语言包
        self::getCoreLang();
        //载入公共语言
        self::getBaseLang();
        //载入程序主模块语言包
        self::getModulesLang(URL_MODULES);
        //完善必备语言参数
        self::completeBaseLang();
    }

    /**
     * 设定当前语言版本
     * range
     * @param string $range
     * @return string
     */
    public static function range($range = '')
    {
        if (!empty($range)) {
            self::$range = $range;
        }
        self::$lang[$range] = [];


        return self::$range;
    }

    /**
     * 完善必备语言参数
     * completeBaseLang
     */
    private static function completeBaseLang()
    {
        $funName = '';
        $lang = self::get(URL_MODULES);
        $funName['modules'] = isset($lang['name']) ? $lang['name'] : '主模块';
        $funName['model'] = cc__isset($lang, [URL_MODEL, 'name'], null, '子模块');
        $funName['action'] = cc__isset($lang, [URL_MODEL, 'action', URL_ACTION], null, '功能模块');

        self::set($funName);
    }

    /**
     * 载入程序主模块语言包
     * getModulesLang
     * @param $modules
     * @return array
     */
    private static function getModulesLang($modules)
    {
        $langPath = MVC_CONTROLLER_PATH . $modules . DS . 'lang' . EXT;
        $ModulesLang = cc__requireFile($langPath, true);
        if (false === $ModulesLang || !is_array($ModulesLang)) {
            $ModulesLang = [];
        } else {
            self::$lang[self::$range][$modules] = $ModulesLang;
        }
        return $ModulesLang;
    }

    /**
     * 载入系统核心语言包
     * getCoreLang
     */
    private static function getCoreLang()
    {
        $range = self::$range;
        $coreLangPath = LIBRARY_PATH . 'lang' . DS . $range . EXT;
        $coreLang = cc__requireFile($coreLangPath, true);
        if (false === $coreLang || !is_array($coreLang)) {
            self::exitDie('系统核心语言包非法，请正确编辑');
        } else {
            $oldLang = self::$lang[$range];
            self::$lang[$range] = array_merge($oldLang, $coreLang);
        }
    }

    /**
     * 载入公共语言
     * getBaseLang
     */
    private static function getBaseLang()
    {
        $range = self::$range;
        $baseLangPath = APP_PATH . 'lang' . EXT;
        $baseLang = cc__requireFile($baseLangPath, true);
        if (false === $baseLang || !is_array($baseLang)) {
            self::exitDie('公共语言包非法，请正确编辑');
        } else {
            $oldLang = self::$lang[$range];
            self::$lang[$range] = array_merge($oldLang, $baseLang);
        }
    }


    /**
     * 错误的配置文件退出
     * exitDie
     * @param $msg [description] 错误的MSG
     */
    private static function exitDie($msg)
    {
        $msg = '[LANG ERROR]: ' . $msg;
        die('<h1>' . $msg . '</h1>');
    }

}