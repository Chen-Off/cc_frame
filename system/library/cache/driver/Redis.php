<?php
namespace cc\Cache\Driver;
use cc\Cache\Driver;

/**
 * Redis缓存驱动，适合单机部署、有前端代理实现高可用的场景，性能最好
 * 有需要在业务层实现读写分离、或者使用RedisCluster的需求，请使用Redisd驱动
 *
 * 要求安装phpredis扩展：https://github.com/nicolasff/phpredis
 * @author    尘缘 <130775@qq.com>
 */
class Redis extends Driver
{
    /**
     * @var \Redis
     */
    protected $handler = null;
    protected $options = [
        'host'       => '127.0.0.1',
        'port'       => 6379,
        'password'   => '',
        'select'     => 0,
        'timeout'    => 0,
        'expire'     => 0,
        'persistent' => false,
        'prefix'     => '',
    ];

    /**
     * 架构函数
     * @param array $options 缓存参数
     * @access public
     */
    public function __construct($options = [])
    {
        if (!extension_loaded('redis')) {
            throw new \BadFunctionCallException('not support: redis');
        }
        if (!empty($options)) {
            $this->options = array_merge($this->options, $options);
        }

        $func          = $this->options['persistent'] ? 'pconnect' : 'connect';
        $this->handler = new \Redis();
        $rs = $this->handler->$func($this->options['host'], $this->options['port'], $this->options['timeout']);
        if(false === $rs) {
            die('请先启动 Redis 功能');
        }

        if ('' != $this->options['password']) {
            $this->handler->auth($this->options['password']);
        }

        if (0 != $this->options['select']) {
            $this->handler->select($this->options['select']);
        }
    }

    /**
     * 判断缓存
     * @access public
     * @param string $name 缓存变量名
     * @return bool
     */
    public function has($name)
    {
        return $this->handler->get($this->getCacheKey($name)) ? true : false;
    }

    /**
     * 读取缓存
     * @access public
     * @param string $name 缓存变量名
     * @param mixed  $default 默认值
     * @return mixed
     */
    public function get($name, $default = false)
    {
        $value = $this->handler->get($this->getCacheKey($name));
        if (is_null($value)) {
            return $default;
        }
        $jsonData = json_decode($value, true);
        // 检测是否为JSON数据 true 返回JSON解析数组, false返回源数据 byron sampson<xiaobo.sun@qq.com>
        return (null === $jsonData) ? $value : $jsonData;
    }

    /**
     * 写入缓存
     * @access public
     * @param string    $name 缓存变量名
     * @param mixed     $value  存储数据
     * @param integer   $expire  有效时间（秒）
     * @return boolean
     */
    public function set($name, $value, $expire = null)
    {
        if (is_null($expire)) {
            $expire = $this->options['expire'];
        }
        if ($this->tag && !$this->has($name)) {
            $first = true;
        }
        $key = $this->getCacheKey($name);
        //对数组/对象数据进行缓存处理，保证数据完整性  byron sampson<xiaobo.sun@qq.com>
        $value = (is_object($value) || is_array($value)) ? json_encode($value) : $value;
        if (is_int($expire) && $expire) {
            $result = $this->handler->setex($key, $expire, $value);
        } else {
            $result = $this->handler->set($key, $value);
        }
        isset($first) && $this->setTagItem($key);
        return $result;
    }


    /**
     * 增加缓存
     * push
     * @param string $name
     * @param mixed $value
     * @param string $seat
     * @return bool
     */
    public function push($name, $value, $seat = 'r')
    {
        if(false !== $this->has($name)) {
            if ($this->tag && !$this->has($name)) {
                $first = true;
            }
            $key = $this->getCacheKey($name);
            //对数组/对象数据进行缓存处理，保证数据完整性  byron sampson<xiaobo.sun@qq.com>
            $value = (is_object($value) || is_array($value)) ? json_encode($value) : $value;

            if($seat == 'r') {
                $result = $this->handler->rPushx($key, $value);
            } else {
                $result = $this->handler->lPushx($key,$value);
            }
            isset($first) && $this->setTagItem($key);
        } else {
            $result = $this->set($name, $value);
        }

        return $result;
    }


    /**
     * 自增缓存（针对数值缓存）
     * @access public
     * @param string    $name 缓存变量名
     * @param int       $step 步长
     * @return false|int
     */
    public function inc($name, $step = 1)
    {
        $key = $this->getCacheKey($name);
        return $this->handler->incrBy($key, $step);
    }

    /**
     * 自减缓存（针对数值缓存）
     * @access public
     * @param string    $name 缓存变量名
     * @param int       $step 步长
     * @return false|int
     */
    public function dec($name, $step = 1)
    {
        $key = $this->getCacheKey($name);
        return $this->handler->decrBy($key, $step);
    }

    /**
     * 删除缓存
     * @access public
     * @param string $name 缓存变量名
     * @return boolean
     */
    public function rm($name)
    {
        return $this->handler->del($this->getCacheKey($name));
    }

    /**
     * 删除缓存中的某个值
     * rmV
     * @param string $name  缓存变量名
     * @param string $value 缓存值
     * @return int
     */
    public function rmV($name, $value)
    {
        return $this->handler->sRem($this->getCacheKey($name), $value);
    }

    /**
     * 删除缓存中左右第一个值
     * pop
     * @param string $name  缓存变量名
     * @param string $seat 位置
     * @return string
     */
    public function pop($name, $seat = 'r')
    {
        $name = $this->getCacheKey($name);
        if($seat == 'r') {
            return $this->handler->rPop($name);
        } else {
            return $this->handler->lPop($name);
        }
    }

    /**
     * 清除缓存
     * @access public
     * @param string $tag 标签名
     * @return boolean
     */
    public function clear($tag = null)
    {
        if ($tag) {
            // 指定标签清除
            $keys = $this->getTagItem($tag);
            foreach ($keys as $key) {
                $this->handler->delete($key);
            }
            $this->rm('tag_' . md5($tag));
            return true;
        }
        return $this->handler->flushDB();
    }

    /**
     * 返回句柄对象，可执行其它高级方法
     *
     * @access public
     * @return object
     */
    public function handler()
    {
        return $this->handler;
    }
}
