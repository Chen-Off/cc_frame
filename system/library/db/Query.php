<?php

namespace cc\db;

use cc\Db;
use cc\db\Connection;

use PDO;
use PDOStatement;
use PDOException;


/**
 * Class Query
 * @package cc\db
 */
class Query
{

    /** @var string 错误信息 */
    protected $message;


    /**
     * 数据库分析实例
     * @var Analyze
     */
    protected $analyze;

    /**
     * @var \cc\db\Connection
     * 数据库Connection对象实例
     */
    protected $connection;

    /**
     * @var string 实例名称
     */
    protected $connectName;

    // 当前模型类名称
    protected $model;

    // 当前数据表
    protected $table;

    // 当前数据表前缀
    protected $prefix = '';
    // 查询参数
    protected $options = [];
    // 参数绑定
    protected $bind = [];

    // 数据表信息
    protected static $info = [];

    /**
     * 架构函数
     * Query constructor.
     * @access public
     * @param $connection [description] [数据库对象实例]
     */
    public function __construct($connection = null)
    {
        $this->connection = $connection ? : Db::connect([], true);

        //表前缀
        $this->prefix = $this->connection->getConfig('db_prefix');
        //表分析实例
        $this->analyze = $this->connection->getConfig('analyze');
        //设置表分析实例用途的配置名称
        $this->connectName = $this->connection->getConnectName();

        //设置数据库实例
        $this->Analyze()->setConnection($this->connectName, $this->connection);

        //设置数据库配置组
        $this->Analyze()->setConfig($this->connectName, $this->getConfig());

        if (!empty($this->prefix)) $this->prefix .= '_';
        $this->free();
    }


    /**
     * __set 外部赋值的对象方式
     * @param $file     [description]   字段
     * @param $value    [description]   字段内容
     */
    function __set($file, $value)
    {

    }

    /**
     * 启动事务
     * @access public
     * @return void
     */
    public function startTrans()
    {
        $this->connection->startTrans();
    }

    /**
     * 用于非自动提交状态下面的查询提交
     * @access public
     * @return void
     * @throws PDOException
     */
    public function commit()
    {
        $this->connection->commit();
    }

    /**
     * 事务回滚
     * @access public
     * @return void
     * @throws PDOException
     */
    public function rollback()
    {
        $this->connection->rollback();
    }

    /**
     * 批处理执行SQL语句
     * 批处理的指令都认为是execute操作
     * @access public
     * @param array $sql SQL批处理指令
     */
    public function batchQuery($sql = [])
    {
        //return $this->connection->batchQuery($sql);
    }

    /**
     * 获取数据库的配置参数
     * @access public
     * @param string $name 参数名称
     * @return boolean
     */
    public function getConfig($name = '')
    {
        return $this->connection->getConfig($name);
    }


    /**
     * data    赋值的对象方式
     * @param string|array $file [description]  设置字段参数
     * @param $value [description] 设置字段值    'chenchao'
     * @return $this
     */
    function data($file, $value = null)
    {
        if (is_array($file)) {
            $this->options['data'] = array_merge($this->options['data'], $file);
        } else {
            $this->options['data'][$file] = $value;
        }
        return $this;

    }

    /**
     * connect 切换当前的数据库连接
     * @param $option   [description]   数据库连接配置
     * @return $this
     */
    function connect($option)
    {
        $this->connection = Db::connect($option);
        return $this;
    }

    /**
     * 获取当前Analyze实例对象
     * @access protected
     * @return Analyze
     */
    protected function Analyze()
    {
        static $analyze = [];
        $driver = $this->analyze;

        if (!isset($analyze[$driver])) {
            $class = false !== strpos($driver, '\\') ? $driver : '\\cc\\db\\Analyze';
            $analyze[$driver] = new $class($this->connection);
        }

        //设置当前查询对象
        $analyze[$driver]->setQuery($this);
        $analyze[$driver]->getConnection($this->connectName);
        $analyze[$driver]->getConfig($this->connectName);
        return $analyze[$driver];
    }


    /**
     * 获取当前的数据库Connection对象
     * @access public
     * @return Connection
     */
    public function getConnection()
    {
        return $this->connection;
    }


    /** 初始化方式 */
    function free()
    {
        $this->bind = [];
        $this->options = [

            'table' => '',
            'fields' => [],
            'join' => [],
            'where' => [],
            'having' => '',
            'order' => [],
            'limit' => '',
            'group' => '',
            'comment' => '',
            'data' => [],
            'union' => [],
            'force' => [],
            'using' => '',
            'fetch_sql' => false,
            'lock' => false,
            'distinct' => false,
        ];

        $this->Analyze()->free();
    }


    /**
     * 设置操作主表
     * @param $table       [description]    表名 表别名
     * @return $this
     */
    function table($table)
    {
        //加入缓存
        $this->Analyze()->parseTable($table, true);
        $this->options['table'] = $table;
        return $this;
    }

    /**
     * 锁表
     * lock
     * @param bool $lock
     * @return $this
     */
    function lock($lock = false)
    {
        $this->options['lock'] = $lock;
        return $this;
    }

    /**
     * USING支持 用于多表删除
     * @access public
     * @param mixed $using  [description]
     * @return $this
     */
    public function using($using)
    {
        $this->options['using'] = $using;
        return $this;
    }

    /**
     * 去重
     * distinct
     * @param bool $distinct    [description]   是否强制去重
     * @return $this
     */
    function distinct($distinct = false)
    {
        $this->options['distinct'] = $distinct;
        return $this;
    }

    /**
     * 指定查询字段 支持字段排除和指定数据表
     * @access public
     * @param mixed $field [description]  要查询的字段
     * @param string $tableName [description]  数据表名
     * @return $this
     */
    function field($field, $tableName = '')
    {
        if (is_string($field)) {
            $field = array_map('trim', explode(',', $field));
        }

        if (!empty($tableName)) {
            // 获取表别名
            $tableAs = $this->Analyze()->parseTableAs($tableName).'.';
        } else {
            $tableAs = '';
        }

        /**
         * eg.
         * [0 => 'name', 1 => 'password as pwd'];
         * [0 => 'name', 'pwd' => 'password'];
         */

        foreach ($field as $key => $val) {
            //如果存在别名授予情况，提取别名
            if (is_numeric($key) && stripos($val, ' as ') !== false) {
                $arr = preg_split('/ as /i', $val);
                unset($field[$key]);
                $arr = array_map('trim', $arr);
                $key = $arr[1];
                $val = $arr[0];
            }
            $field[$key] = $tableAs . $val;
        }

        if (!empty($this->options['fields'])) {
            $field = array_merge($this->options['fields'], $field);
        }

        $this->options['fields'] = array_unique($field);
        return $this;
    }

    /**
     * select
     * @param string $fields    [description]   要查询的字段
     * @return array|string
     */
    function select($fields = '*')
    {
        if (!empty($fields) || $fields != '*') {
            $this->field($fields);
        }

        $options = $this->options;
        $sql = $this->Analyze()->select($options);

        // 获取参数绑定
        $bind = $this->getBind();

        $this->free();
        if (true === $options['fetch_sql']) {
            // 获取实际执行的SQL语句
            return $this->connection->getRealSql($sql, $bind);
        }

        // 执行操作
        $result = $this->query($sql, $bind);
        if (!is_array($result)) {
            $result = [];
        }
        return $result;
    }

    /**
     * find
     * @param string $fields    [description]   要查询的字段
     * @return array|string
     */
    function find($fields = '*')
    {
        if (!empty($fields) || $fields != '*') {
            $this->field($fields);
        }
        $options = $this->options;

        $sql = $this->Analyze()->select($options);

        // 获取参数绑定
        $bind = $this->getBind();


        $this->free();
        if (true === $options['fetch_sql']) {
            // 获取实际执行的SQL语句
            var_dump($bind);
            return $this->connection->getRealSql($sql, $bind);
        }

        // 执行操作
        $result = $this->query($sql, $bind, true);
        if (!is_array($result)) {
            $result = [];
        }
        return $result;
    }

    /**
     * 获取平均值
     * @param string $field
     * @return string
     */
    function avg($field = '*') {
        $as = 'cc_avg';
        $field = 'AVG(' . $field . ') AS '.$as;
        $qs = $this->find($field);
        return $qs[$as];
    }


    /**
     * 查询某个字段总和
     * @param string $field
     * @return string
     */
    function sum($field = '*') {
        $as = 'cc_sum';
        $field = 'SUM(' . $field . ') AS '.$as;
        $qs = $this->find($field);
        return $qs[$as];
    }


    /**
     * 查询某个字段最大值
     * @param string $field
     * @return string
     */
    function max($field = '*') {
        $as = 'cc_max';
        $this->field($field);
        $field = 'MAX(' . $field . ') AS '.$as;
        $qs = $this->find($field);
        return $qs[$as];
    }


    /**
     * 查询某个字段最小值
     * @param string $field
     * @return string
     */
    function min($field = '*') {
        $as = 'cc_min';
        $field = 'MIN(' . $field . ') AS '.$as;
        $qs = $this->find($field);
        return $qs[$as];
    }


    /**
     * 查询某个字段总数
     * @param string $field
     * @return string
     */
    function count($field = '*') {
        $as = 'cc_count';
        $field = 'COUNT(' . $field . ') AS '.$as;
        $qs = $this->find($field);
        return $qs[$as];
    }


    /**
     * insert
     * @param null $newData [description]   参数数据
     * @return array|int|PDOStatement|string
     */
    function insertAll($newData = null) {

        $options = $this->options;

        //检测字段和参数
        $sql = $this->Analyze()->insertAll($newData, $options);

        // 获取参数绑定
        $bind = $this->getBind();
        $this->free();
        if (true === $options['fetch_sql']) {
            // 获取实际执行的SQL语句
            return $this->connection->getRealSql($sql, $bind);
        }

        // 执行操作
        $lastInsertID = $this->execute($sql, $bind, true);
        return $lastInsertID;
    }

    /**
     * insert
     * @param null $newData [description]   参数数据
     * @return array|int|PDOStatement|string
     */
    function insert($newData = null)
    {
        //补充参数
        $this->add_prepare($newData);

        $options = $this->options;

        //检测字段和参数
        $sql = $this->Analyze()->insert($options);

        // 获取参数绑定
        $bind = $this->getBind();
        $this->free();
        if (true === $options['fetch_sql']) {
            // 获取实际执行的SQL语句
            return $this->connection->getRealSql($sql, $bind);
        }

        // 执行操作
        $lastInsertID = $this->execute($sql, $bind, true);
        return $lastInsertID;
    }

    /**
     * update
     * @param null|array $newData [description]   参数数据
     * @param bool $where [description]   是否强行全表更新
     * @return int
     */
    public function update($newData = null, $where = true)
    {
        //补充参数
        if (is_string($newData)) {
            $newData = $this->updateStrToArray($newData);
        }

        $this->add_prepare($newData);

        $options = $this->options;
        //检测是否使用where 语句，避免全表更新。全表更新校验预留
        if (true === $where && empty($options['where'])) {
            $msg = 'UPDATE 缺少 WHERE 条件，全表更新更新要素需为 FALSE';
            $this->connection->db_error($msg);
        }

        //检测字段和参数
        $sql = $this->Analyze()->update($options);

        // 获取参数绑定
        $bind = $this->getBind();
        $this->free();
        if (true === $options['fetch_sql']) {
            // 获取实际执行的SQL语句
            return $this->connection->getRealSql($sql, $bind);
        }

        return $this->execute($sql, $bind);
    }

    /**
     * 更新字符串转数组
     * @param $string   [description]   更新操作，所提交的字符串参数
     * @return array
     */
    private function updateStrToArray($string)
    {
        $data = explode(',', $string);
        $newData = [];
        foreach ($data as $key => $val) {
            $arr = explode('=', $val);
            $arr = array_map('trim', $arr);
            if (count($arr) != 2) {
                $msg = '非法的【update】数据更新语句' . $string;
                $this->connection->db_error($msg);
            }
            $newData[$arr[0]] = $arr[1];
        }
        return $newData;
    }


    /**
     * 删除操作
     * delete
     * @return string
     */
    function delete()
    {
        // 获取参数绑定
        $bind = $this->getBind();
        $options = $this->options;
        $this->free();

        // 生成删除SQL语句
        $sql = $this->Analyze()->delete($options);
        if ($options['fetch_sql']) {
            // 获取实际执行的SQL语句
            return $this->connection->getRealSql($sql, $bind);
        }

        $result = $this->execute($sql, $bind);
        return $result;
    }


    /**
     * join
     * @param string|array $join [description]   连接条件
     * @param string $type [description]   连接方式类型
     * @return $this
     */
    function join($join, $type = 'INNER')
    {
        if (empty($join)) {
            return $this;
        }

        //如果是字符串形式的 JOIN 内容
        if (is_string($join)) {
            $join = [$join];
        }

        //如果是数组无标记形式的 JOIN 内容
        foreach ($join as $k => $arr) {
            if (is_string($arr)) {
                $arr = $this->parseJoinStr($arr);
            } else {
                if (!isset($arr['table']) || !isset($arr['on'])) {
                    if (count($arr) != 2) {
                        $msg = '[DB ERROR]:JOIN 语句语法错误 -【' . implode(' ON ', $arr) . '】';
                        $this->connection->db_error($msg);
                    }
                    $arr = [
                        'table' => $join[0],
                        'on' => $join[1],
                    ];
                }
            }


            //分析连接表
            $joinTable = $this->Analyze()->parseTable($arr['table']);

            //分析 ON 参数
            $joinOn = $arr['on'];
            $this->Analyze()->parseFields($joinOn, '=');

            $this->options['join'][] = strtoupper($type) . ' JOIN ' . $joinTable . ' ON ' . $joinOn;
        }


        return $this;
    }

    /**
     * 分析处理JOIN操作中使用字符串模式
     * @param $joinStr  [description]   连接表字符串
     * @return array
     */
    private function parseJoinStr($joinStr)
    {
        $joinStr = str_ireplace(' on ', ' ON ', $joinStr); //小写 on 转大写 ON
        $joinArr = explode('ON', $joinStr);
        $joinArr = array_map('trim', $joinArr);

        if (count($joinArr) != 2) {
            $msg = '[DB ERROR]:JOIN 语句语法错误 -【' . $joinStr . '】';
            $this->connection->db_error($msg);
        }
        $join = [
            'table' => $joinArr[0],
            'on' => $joinArr[1],
        ];
        return $join;
    }

    /**
     * 参数绑定
     * @access public
     * @param number|array $key [description] 参数名
     * @param string|bool $value [description] 绑定变量值
     * @param integer $type [description] 绑定类型
     * @return $this
     */
    public function bind($key, $value = null, $type = PDO::PARAM_STR)
    {
        if (is_array($key)) {
            $this->bind = array_merge($this->bind, $key);
        } else {
            if (null === $value) {
                $this->bind[0] = [$key, $type];
            } elseif (is_int($key) && null !== $value) {

                $this->bind[$key] = [$value, $type];
            }
        }

        return $this;
    }

    /**
     * where
     * @param string|array $where   [description]   搜索条件
     * @return $this
     */
    function where($where)
    {
        if (empty($where)) {
            return $this;
        }

        if (is_string($where)) {
            $this->options['where'][] = [
                'type' => 'where',
                'data' => trim($where),
            ];
        } else {
            //检测是否是一维数组
            foreach ($where as $val) {
                if(empty($val)) {
                    continue;
                }

                if(is_array($val)) {
                    if(count($val) != 3) {
                        $msg = '[DB ERROR]: 错误的WHERE 连贯语法【'.implode(' ',$val).'】';
                        $this->connection->db_error($msg);
                    }

                    $val = array_map('trim', $val);
                    $data = [
                        'field' => $val[0], //查询字段
                        'op' => strtoupper($val[1]),   //查询表达式
                        'condition' => $val[2],   //查询条件
                    ];
                } else {
                    $data = trim($val);
                }

                $this->options['where'][] = [
                    'type' => 'where',
                    'data' => $data
                ];
            }
        }

        return $this;
    }

    /**
     * whereOr
     * @param $whereOr  [description]   搜索条件
     * @return $this
     */
    function whereOr($whereOr)
    {
        if (empty($whereOr)) {
            return $this;
        }

        if (is_string($whereOr)) {
            $this->options['where'][] = [
                'type' => 'or',
                'data' => trim($whereOr),
            ];
        } else {

            foreach ($whereOr as $val) {
                if(empty($val)) {
                    continue;
                }

                if(is_array($val)) {
                    if(count($val) != 3) {
                        $msg = '[DB ERROR]: 错误的WHERE OR 连贯语法【'.implode(' ',$val).'】';
                        $this->connection->db_error($msg);
                    }

                    $val = array_map('trim', $val);
                    $data = [
                        'field' => $val[0], //查询字段
                        'op' => strtoupper($val[1]),   //查询表达式
                        'condition' => $val[2],   //查询条件
                    ];
                } else {
                    $data = trim($val);
                }

                $this->options['where'][] = [
                    'type' => 'or',
                    'data' => $data
                ];
            }
        }

        return $this;
    }


    /**
     * order
     * @param string|array $field [description]  排序依据
     * @param string $sort [description]   排序类型
     * @return $this
     */
    function order($field, $sort = null)
    {
        if (empty($field)) {
            return $this;
        }

        if(is_string($field)) {
            $field = [$field];
        }

        foreach ($field as $f_val) {
            if (false !== strpos($f_val, ' ')) {
                $arr = explode(' ', $f_val);
                $sort = empty(end($arr)) ? $sort : end($arr);
                $f_val = $arr[0];
            }

            $this->options['order'][$f_val] = $sort;
        }
        return $this;
    }

    /**
     * 查询注释
     * @access public
     * @param string $comment 注释
     * @return $this
     */
    public function comment($comment)
    {
        $this->options['comment'] = $comment;
        return $this;
    }

    /**
     * 指定查询数量
     * @access public
     * @param int $offset [description]  起始位置
     * @param int $length [description] 查询数量
     * @return $this
     */
    function limit($offset = 1, $length = null)
    {
        $this->options['limit'] = [$offset];
        if (null !== $length && is_numeric($length)) {
            $this->options['limit'][] = $length;
        }
        return $this;
    }

    /**
     * group
     * @param string $group [description]   排序
     * @return $this
     */
    function group($group = null)
    {
        $this->options['group'] = $group;
        return $this;
    }

    /**
     * 指定having查询
     * @access public
     * @param string $having [description]   过滤条件
     * @return $this
     */
    public function having($having)
    {
        $this->options['having'] = $having;
        return $this;
    }

    /**
     * 获取执行的SQL语句
     * @access public
     * @param boolean $fetch [description] 是否返回sql
     * @return $this
     */
    public function fetchSql($fetch = true)
    {
        $this->options['fetch_sql'] = $fetch;
        return $this;
    }

    /**
     * 指定强制索引
     * @access public
     * @param string $force [description] 索引名称
     * @return $this
     */
    public function force($force)
    {
        $this->options['force'] = $force;
        return $this;
    }

    /**
     * query 执行查询 返回数据集
     * @param $sql  [description]sql 语句
     * @param array $bind   [description]   绑定参数
     * @param bool $fetch   [description]   是否返回多维数组
     * @return PDOStatement
     */
    public function query($sql, $bind = [], $fetch = false)
    {
        return $this->connection->query($sql, $bind, $fetch);
    }

    /**
     * 执行语句
     * @access public
     * @param string $sql [description] sql指令
     * @param array $bind [description] 参数绑定
     * @param bool [description]    $getLastInsID
     * @return int
     * @throws PDOException
     */
    public function execute($sql, $bind, $getLastInsID = false)
    {
        return $this->connection->execute($sql, $bind, $getLastInsID);
    }

    /**
     * rowCount 返回受影响的数量
     * @return int
     */
    public function rowCount()
    {
        return $this->connection->rowCount();
    }


    /**
     * getLastSql 获取最后一条执行的SQL语句
     * @return string
     */
    public function getLastSql()
    {
        return $this->connection->getLastSql();
    }

    /**
     * 获取绑定的参数
     * @access public
     * @param $key  [description]   绑定的参数名称
     * @return array|string
     */
    public function getBind($key = null)
    {
        if (null !== $key && is_string($key)) {
            if (isset($this->bind[$key])) {
                return $this->bind[$key];
            } else {
                return '';
            }
        } else {
            return $this->bind;
        }
    }

    /**
     * getLastInsId 获取最后插入成功的ID
     * @param null $sequence
     * @return string
     */
    public function getLastInsId($sequence = null)
    {
        return $this->connection->getLastInsID($sequence);
    }

    /**
     * add_prepare 补充参数
     * @param array $data   [description]   参数
     */
    private function add_prepare($data)
    {
        if (!empty($data) && is_array($data)) {
            $this->options['data'] = array_merge($this->options['data'], $data);
        }
    }
}
