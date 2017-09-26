<?php
// +----------------------------------------------------------------------
// | 抄 ThinkPHP
// +----------------------------------------------------------------------
// | Copyright (c) 2017 All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: chen_off
// +----------------------------------------------------------------------

namespace cc;


class Cache
{
    private static $options = [
        // 驱动方式
        'type' => 'File',
        // 缓存保存目录
        'path' => CACHE_PATH,
        // 缓存前缀
        'prefix' => '',
        // 缓存有效期 0表示永久缓存
        'expire' => 0,
    ];
    protected static $instance = [];
    public static $readTimes = 0;
    public static $writeTimes = 0;

    /**
     * 操作句柄
     * @var object
     * @access protected
     */
    protected static $handler;

    /**
     * 连接缓存
     * @access public
     * @param array $options 配置数组
     * @param bool|string $name 缓存连接标识 true 强制重新连接
     * @return \cc\Cache\Driver
     */
    public static function connect(array $options = [], $name = false)
    {
        $options = array_merge(self::$options, $options);
        $type = !empty($options['type']) ? $options['type'] : 'File';
        if (false === $name) {
            $name = md5(serialize($options));
        }

        if (true === $name || !isset(self::$instance[$name])) {
            $class = false !== strpos($type, '\\') ? $type : '\\cc\\Cache\\Driver\\' . ucwords($type);

            $filePath = LIBRARY_PATH . 'cache' . DS . 'driver' . DS . $options['type'] . EXT;
            $rs = cc__requireFile($filePath);
            if (false === $rs) {
                die('缓存类Cache:【' . $options['type'] . '】功能未发现');
            }
            // 记录初始化信息
            //App::$debug && Log::record('[ CACHE ] INIT ' . $type, 'info');
            if (true === $name) {
                return new $class($options);
            } else {
                self::$instance[$name] = new $class($options);
            }
        }
        self::$handler = self::$instance[$name];
        return self::$handler;
    }

    /**
     * 自动初始化缓存
     * @access public
     * @param array $options 配置数组
     * @return void
     */
    public static function init(array $options = [])
    {
        if (is_null(self::$handler)) {
            // 自动初始化缓存
            if (empty($options)) {
                $options = Config::CB('Cache');
                if (false === $options) {
                    $options = [];
                }
            }
            self::connect($options);
        }
    }

    /**
     * 切换缓存类型 需要配置 cache.type 为 complex
     * @access public
     * @param string $name 缓存标识
     * @return \cc\Cache\Driver
     */
    public static function store($name)
    {
        die('不支持切换缓存');
        /*
        if ('complex' == Config::get('cache.type')) {
            self::connect(Config::get('cache.' . $name), strtolower($name));
        }
        return self::$handler;
        */
    }

    /**
     * 判断缓存是否存在
     * @access public
     * @param string $name 缓存变量名
     * @return bool
     */
    public static function has($name)
    {
        self::init();
        self::$readTimes++;
        return self::$handler->has($name);
    }

    /**
     * 读取缓存
     * @access public
     * @param string $name 缓存标识
     * @param mixed $default 默认值
     * @return mixed
     */
    public static function get($name, $default = false)
    {
        self::init();
        self::$readTimes++;
        return self::$handler->get($name, $default);
    }

    /**
     * 写入缓存
     * @access public
     * @param string $name 缓存标识
     * @param mixed $value 存储数据
     * @param int|null $expire 有效时间 0为永久
     * @return boolean
     */
    public static function set($name, $value, $expire = null)
    {
        self::init();
        self::$writeTimes++;
        return self::$handler->set($name, $value, $expire);
    }

    /**
     * 追加缓存
     * push
     * @access public
     * @param string $name 缓存标识
     * @param mixed $valueN 存储数据
     * @param string $seat 追加的位置
     * @return boolean
     */
    public static function push($name, $valueN, $seat = 'r')
    {
        self::init();
        self::$writeTimes++;
        return self::$handler->push($name, $valueN, $seat);
    }


    /**
     * 自增缓存（针对数值缓存）
     * @access public
     * @param string $name 缓存变量名
     * @param int $step 步长
     * @return false|int
     */
    public static function inc($name, $step = 1)
    {
        self::init();
        self::$writeTimes++;
        return self::$handler->inc($name, $step);
    }

    /**
     * 自减缓存（针对数值缓存）
     * @access public
     * @param string $name 缓存变量名
     * @param int $step 步长
     * @return false|int
     */
    public static function dec($name, $step = 1)
    {
        self::init();
        self::$writeTimes++;
        return self::$handler->dec($name, $step);
    }

    /**
     * 删除缓存
     * @access public
     * @param string $name 缓存标识
     * @return boolean
     */
    public static function rm($name)
    {
        self::init();
        self::$writeTimes++;
        return self::$handler->rm($name);
    }

    /**
     * 删除缓存中的某个值
     * @access public
     * @param string $name 缓存标识
     * @param string $value 删除值
     * @return boolean
     */
    public static function rmV($name, $value)
    {
        self::init();
        self::$writeTimes++;
        return self::$handler->rmV($name, $value);
    }

    /**
     * 删除缓存中左右第一个值
     * @access public
     * @param string $name 缓存标识
     * @param string $seat 位置
     * @return boolean
     */
    public static function pop($name, $seat = 'r')
    {
        self::init();
        self::$writeTimes++;
        return self::$handler->pop($name, $seat);
    }

    /**
     * 清除缓存
     * @access public
     * @param string $tag 标签名
     * @return boolean
     */
    public static function clear($tag = null)
    {
        self::init();
        self::$writeTimes++;
        return self::$handler->clear($tag);
    }

    /**
     * 缓存标签
     * @access public
     * @param string $name 标签名
     * @param string|array $keys 缓存标识
     * @param bool $overlay 是否覆盖
     * @return \cc\Cache\Driver
     */
    public static function tag($name, $keys = null, $overlay = false)
    {
        self::init();
        return self::$handler->tag($name, $keys, $overlay);
    }

}
