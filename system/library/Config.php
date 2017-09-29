<?php
/**
 * 配置文件
 */
namespace cc;

class Config
{
    /**
     * @var array 配置参数
     */
    static public $MConfig = [];

    /**
     * @var array 公共需要加载的配置文件
     */
    static private $CommonConfigName = [
        'DB' => 'database',     //数据库配置文件
        'CB' => 'config.base',  //基本配置文件
        'CE' => 'config.extend' //扩展配置文件
    ];

    /**
     * @var array 公共配置
     */
    static public $CommonConfig = [];

    function __construct()
    {
    }


    /**
     * 获取模块配置文件
     * getM
     * @param $params [description] 参数名称
     * @param null $modules [description] 模块名称
     * @return bool|mixed
     */
    public static function getM($params, $modules = null)
    {
        if (null === $modules) {
            $modules = URL_MODULES;
        }
        if(!isset(self::$MConfig[$modules])) {
            $config = self::loadModules($modules);
        } else {
            $config = self::$MConfig[$modules];
        }

        if(false === $config) {
            return false;
        }

        if(!is_array($params)) {
            $params = [$params];
        }

        foreach ($params as $p) {
            if(is_numeric($p) || is_string($p)) {
                $p = strtolower($p);
                if(!isset($config[$p])) {
                    return false;
                }
                $config = $config[$p];
            } else {
                return false;
            }

        }
        return $config;
    }


    /**
     * 获取额外配置参数
     * getDB
     * @param null $param
     * @return bool|mixed
     */
    public static function getDB($param = null)
    {
        return self::getC('DB', $param);
    }

    /**
     * 获取额外配置参数
     * getCE
     * @param null $params
     * @param null $param2
     * @return bool|mixed
     */
    public static function getCE($params = null, $param2 = null)
    {
        return self::getC('CE', $params, $param2);
    }

    /**
     * 获取基本配置参数
     * getCB
     * @param null $params
     * @param null $param2
     * @return bool|mixed
     */
    public static function getCB($params = null, $param2 = null)
    {
        return self::getC('CB', $params, $param2);
    }


    /**
     * 获取公共配置指定内容
     * getC
     * @param null $name [description] 配置文件名称
     * @param null $params [description] 配置文件某个参数
     * @param null $param2 [description] 配置文件某个参数
     * @return bool|mixed
     */
    public static function getC($name = null, $params = null, $param2 = null)
    {
        //惰性加载载入配置文件
        if (empty(self::$CommonConfig)) {
            $config = self::loadCommonConfig();
        } else {
            $config = self::$CommonConfig;
        }

        if (null === $name) {
            return $config;
        }
        $configName = self::$CommonConfigName;
        $name = isset($configName[$name]) ? $configName[$name] : $name;

        if (!isset($config[$name])) {
            return false;
        } elseif (null === $params) {
            return $config[$name];
        } else {
            $config = $config[$name];
        }

        $params = !is_array($params) ? [$params] : $params;
        if (null !== $param2 && !is_array($param2)) {
            $params[] = $param2;
        }

        foreach ($params as $p) {
            if(is_numeric($p) || is_string($p)) {
                $p = strtolower($p);
                if(!isset($config[$p])) {
                    return false;
                }
                $config = $config[$p];
            } else {
                return false;
            }
        }
        return $config;
    }

    /**
     * 加载模块
     * loadModules
     * @param $modulesName [description] 模块名称
     * @return bool|array
     */
    private static function loadModules($modulesName)
    {
        $filePath = MVC_CONTROLLER_PATH . $modulesName . DS . 'config' . EXT;
        $config = cc__requireFile($filePath, true);
        if (false !== $config && is_array($config)) {
            self::$MConfig[$modulesName] = $config;
            return $config;
        } else {
            return false;
        }
    }

    /**
     * 载入公共配置
     * loadCommonConfig
     * @return array
     */
    static private function loadCommonConfig()
    {
        $CommonConfig = [];
        //载入公共配置
        $configFile = self::$CommonConfigName;
        foreach ($configFile as $name) {
            $CommonConfig[$name] = self::loadCommon($name);
        };
        self::$CommonConfig = $CommonConfig;
        return $CommonConfig;
    }


    /**
     * 载入公共配置
     * loadCommon
     * @param $name [description] 配置文件名称
     * @return bool|array
     */
    private static function loadCommon($name)
    {
        $filePath = APP_PATH . $name . EXT;
        $config = cc__requireFile($filePath, true);
        if (false === $config || !is_array($config)) {
            self::exitDie($name, $config);
            return false;
        } else {
            return $config;
        }
    }

    /**
     * 错误的配置文件退出
     * exitDie
     * @param $name [description] 配置文件名称
     * @param $value [description] 错误的参数值
     */
    private static function exitDie($name, $value)
    {
        $msg = '';
        switch ($name) {
            case 'database':
                $msg = '数据库配置文件加载错误';
                break;
        }

        $msg = '[CONFIG ERROR]: ' . $msg . '{' . $value . '}';
        die('<h1>' . $msg . '</h1>');
    }

}