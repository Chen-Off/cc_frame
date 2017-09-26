<?php
namespace cc;

use cc\db\Connection;
use cc\db\Query;
use PDO;


/**
 * Class Db
 * @package cc
 * @method Query table(string $table) static 指定数据表（不含前缀）
 *
 * @method Query startTrans() static 启动事务
 * @method Query commit() static 提交预处理语句
 * 
 * @method Query lock(true|false $lock) static 锁表
 *
 * @method Query join(string|array $join, string $type = 'INNER') static JOIN查询
 * @method Query bind(number|array $bind, bool|string $value = null, string $type = PDO::PARAM_STR) static BIND绑定参数
 * @method Query whereOr(string|array $whereOr) static 或者查询条件
 * @method Query where(string|array $where) static 查询条件

 * @method Query order(string|array $order) static 查询ORDER
 * @method Query limit(string $offset = 1, integer $length = null) static 查询LIMIT
 * @method Query group(mixed $group) static 查询GROUP
 *
 * @method Query data(string|array $file, $value = null) static 更新插入的数据
 *
 * @method Query delete() static 删除记录
 * @method Query find(mixed $data = []) static 查询单个记录
 * @method Query select(string|array $select = '*') static 查询多个记录
 * @method Query update(bool|array $update = null) static 更新记录
 * @method Query insert(bool|array $insert = null) static 插入一条记录
 *
 * @method Query query(string $sql,array $bind = [], $fetch = false) static 执行sql语句
 * @method Query execute(string $sql,array $bind = [], boolean $getLastInsID = false) static 执行sql语句
 *
 *
 * @method Query rowCount() static 返回受影响的数量
 * @method Query getLastSql() static 获取最后一条执行的SQL语句
 * @method Query getLastInsId(string $sequence = null) static 获取最后插入成功的ID
 *
 * @method mixed transaction(callable $callback) static 执行数据库事务
 * @method boolean batchQuery(array $sqlArray) static 批处理执行SQL语句
 */


class Db
{

    public static $instance = [];
    private static $msg;

    /**
     * connect 数据库初始化 并取得数据库类实例
     * @param array $options [description] 数据库链接配置
     * @param string|bool $name [description] 连接标识 true 强制重新连接
     * @return \cc\db\Connection
     * @throws \Exception
     */
    public static function connect($options = [], $name = false)
    {
        if (false === $name) {
            $name = md5(serialize($options));
        }

        $class = '\\cc\\db\\Connection';
        if (true === $name || !isset(self::$instance[$name])) {
            $options = self::oauthOptions($options);

            //var_dump(new Connection($options));
            //强制重新链接数据库
            if (true === $name) {
                return new $class($options);
            } else {
                self::$instance[$name] = new $class($options);
            }
        }
        return self::$instance[$name];
    }

    /**
     * oauthOptions 检查数据库链接配置
     * @param array $options
     * @return array|bool|mixed
     */
    public static function oauthOptions($options = [])
    {
        if (empty($options)) {
            $options = Config::DB();
        }
        switch (true) {
            case !isset($options['db_host']) || empty($options['db_host']):
                self::$msg = '服务器地址未配置';
                break;
            case !isset($options['db_user']) || empty($options['db_user']):
                self::$msg = '数据库用户未配置';
                break;
            case !isset($options['db_password']):
                self::$msg = '数据库密码未配置';
                break;
            case !isset($options['db_name']) || empty($options['db_name']):
                self::$msg = '数据库名称未配置';
                break;

            case !isset($options['db_prefix']):
                self::$msg = '数据库表前缀未配置';
                break;
        }
        if(!empty(self::$msg)) {
            DeBug::msgExit(self::$msg);
        }

        if (!isset($options['port'])) {
            $options['port'] = '3306';
        }

        if (!isset($options['charset'])) {
            $options['charset'] = 'utf8';
        }

        if (!isset($options['db_error'])) {
            $options['db_error'] = false;
        }

        return $options;
    }

    public static function getModel($method)
    {

    }

    // 调用驱动类的方法
    public static function __callStatic($method, $params)
    {
        // 自动初始化数据库
        return call_user_func_array([self::connect(), $method], $params);
    }



}