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

    protected $preKeys = array(), $preValues = array();

    /** @var string 错误信息 */
    protected $message;

    /** @var Connection　数据库Connection对象实例 */
    protected $connection;

    // 当前模型类名称
    protected $model;

    // 当前数据表
    protected $table;

    // 当前数据表前缀
    protected $prefix = '';
    // 查询参数
    protected $options = [
        'table' => '',
        'join' => '',
        'where' => '',
        'order' => '',
        'limit' => '',
        'group' => '',
        'lock' => false
    ];
    // 参数绑定
    protected $bind = [];
    protected $where, $whereOr;
    // 数据表信息
    protected static $info = [];

    /**
     * 架构函数
     * Query constructor.
     * @access public
     * @param $connection [数据库对象实例]
     */
    public function __construct($connection = null)
    {
        $this->connection = $connection ?: Db::connect([], true);

        //表前缀
        $this->prefix = $this->connection->getConfig('db_prefix');
        if (!empty($this->prefix)) $this->prefix .= '_';
    }


    /**
     * __set 外部赋值的对象方式
     * @param $file
     * @param $value
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
     * @return boolean
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
     * @param string|array $file [description]
     * @param $value [description] 'chenchao'
     * @return $this
     */
    function data($file, $value = null)
    {
        if (is_array($file)) {
            $this->preKeys = array_merge($this->preKeys, array_keys($file));
            $this->preValues = array_merge($this->preValues, array_values($file));
        } else {
            $this->preKeys[] = $file;
            $this->preValues[] = $value;
        }
        return $this;

    }

    /**
     * connect 切换当前的数据库连接
     * @param $option
     * @return $this
     */
    function connect($option)
    {
        $this->connection = Db::connect($option);
        return $this;
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
        $this->preKeys = array();
        $this->preValues = array();
        $this->bind = [];
        $this->options = [
            'table' => '',
            'join' => '',
            'where' => '',
            'order' => '',
            'limit' => '',
            'group' => '',
        ];
        $this->where = '';
        $this->whereOr = '';
    }


    function table($table)
    {
        $this->options['table'] = $this->prefix . $table;
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
        //$this->options['lock']   = $lock;
        $this->options['lock'] = $lock ? ' FOR UPDATE ' : '';
        return $this;
    }

    /**
     * select
     * @param string $data
     * @return array
     */
    function select($data = '*')
    {
        self::whereSqlRepair();

        // 生成SQL语句
        $sql = "SELECT " . $data . "  FROM " .
            $this->options['table'] .
            $this->options['join'] .
            $this->options['where'] .
            $this->options['group'] .
            $this->options['order'] .
            $this->options['limit'];

        // 获取参数绑定
        $bind = $this->getBind();

        $this->free();

        // 执行操作
        $result = $this->query($sql, $bind);
        if (!is_array($result)) $result = [];
        return $result;
    }

    /**
     * find
     * @param string $data
     * @return array
     */
    function find($data = '*')
    {
        self::whereSqlRepair();

        // 生成SQL语句
        $sql = "SELECT " . $data . "  FROM " .
            $this->options['table'] .
            $this->options['join'] .
            $this->options['where'] .
            $this->options['group'] .
            $this->options['order'];

        // 获取参数绑定
        $bind = $this->getBind();

        $this->free();

        // 执行操作
        $result = $this->query($sql, $bind, true);
        if (!is_array($result)) $result = [];
        return $result;
    }


    /**
     * insert
     * @param null|array $newData
     * @return int
     */
    function insert($newData = null)
    {
        if (empty($newData)) {
            if (empty($this->preKeys) || empty($this->preValues)) {
                $this->message = '需要新增的参数为空, 请检查连贯操作语句';
                $this->db_error();
            }
        } else {
            if (!is_array($newData)) {
                $this->message = 'insert 参数必须为数组类型 {eg.[\'name\' => \'名字\', \'sex\' => \'性别\', ]}';
                $this->db_error();
            }
            //补充参数
            $this->add_prepare($newData);
        }


        $insertData = ' (`' . implode('`, `', $this->preKeys) . '`)';
        $values = '';
        //判断提交的值是否已经手动设置好了包含，没有的设置包含 绑定的除外 （其实应该根据字段类型进行设置）
        foreach ($this->preValues as $v) {
            $values .= $this->value_real($v) . ',';
        }

        // 生成SQL语句

        $insertData .= ' VALUES (' . substr($values, 0, -1) . ')';
        $sql = 'INSERT INTO ' . $this->options['table'] . $insertData;
        // 获取参数绑定
        $bind = $this->getBind();

        $this->free();

        return $this->execute($sql, $bind, true);
    }

    /**
     * update
     * @param null|array $value
     * @return int
     */
    function update($value = null)
    {
        $updateData = '';
        if (empty($value)) {
            if (empty($this->preKeys) || empty($this->preValues)) {
                $this->message = '需要更新的参数为空, 请检查连贯操作语句';
                $this->db_error();
            }
        } else {
            if (is_array($value)) {
                $this->add_prepare($value);
            } else {
                $updateData = $value;
            }
        }

        if (empty($updateData)) {
            //判断提交的值是否已经手动设置好了包含，没有的设置包含 绑定的除外 （其实应该根据字段类型进行设置）
            foreach ($this->preValues as $k => $v) {
                $updateData .= $this->preKeys[$k] . ' = ' . $this->value_real_to_update($v, $this->preKeys[$k]) . ',';
            }
            $updateData = substr($updateData, 0, -1);
        }

        self::whereSqlRepair();

        // 生成SQL语句
        $sql = "UPDATE " . $this->options['table'] . " SET " . $updateData . ' ' .
            $this->options['where'] . ' ' . $this->options['order'] . ' ' . $this->options['limit'];
        // 获取参数绑定
        $bind = $this->getBind();
        $this->free();
        return '' == $sql ? 0 : $this->execute($sql, $bind);
    }


    /**
     * delete
     * @return PDOStatement
     */
    function delete()
    {
        self::whereSqlRepair();

        // 获取参数绑定
        $bind = $this->getBind();

        // 生成删除SQL语句
        $sql = 'DELETE FROM ' . $this->options['table'] . $this->options['where'];

        $result = $this->execute($sql, $bind);
        $this->free();
        return $result;
    }


    /**
     * join
     * @param string|array $data
     * @param string $type
     * @return $this
     */
    function join($data, $type = 'INNER')
    {
        //if(empty($data)) $this->db_error('{join} 内容请不要为空');

        if (!empty($data)) {
            if (!is_array($data)) {
                $data = array($data);
            }

            foreach ($data as $v) {
                $this->options['join'] .= ' ' . strtoupper($type) . ' JOIN ' . $this->prefix . $v;
            }
        }
        return $this;
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
            if (null === $value && !is_int($key)) {
                $this->bind[0] = [$key, $type];
            } elseif (is_int($key) && null !== $value) {
                $this->bind[$key] = [$value, $type];
            }
        }

        return $this;
    }

    /**
     * where
     * @param string|array $where
     * @return $this
     */
    function where($where)
    {
        if (empty($where))
            return $this;

        if (is_string($where)) {
            $where = [$where];
        }

        $and = '';
        foreach ($where as $k => $value) {
            if ($k != 0) {
                $and = ' AND ';
            }


            //对where 语句参数进行拆解处理值
            if (false !== strpos($value, '=') &&
                false === stripos($value, 'not exists') &&
                false === stripos($value, 'not exists')
            ) {
                $arr = explode('=', $value);
                if (count($arr) == 2) {
                    $this->where .= $and . $arr[0] . '=' . $this->value_real(trim($arr[1]));
                } else {
                    $this->where .= $value;
                }

            } else {
                $this->where .= $and . $value;
            }
        }
        return $this;
    }

    /**
     * whereOr
     * @param $whereOr
     * @return $this
     */
    function whereOr($whereOr)
    {
        if (empty($whereOr))
            return $this;

        if (is_string($whereOr)) {
            $whereOr = [$whereOr];
        }

        $i = 0;
        $and = '';
        foreach ($whereOr as $value) {
            if ($i != 0) {
                $and = ' OR ';
            }
            //对where 语句参数进行拆解处理值
            $arr = explode('=', $value);
            $this->whereOr .= $and . $arr[0] . '=' . $this->value_real(trim($arr[1]));
            $i++;
        }
        return $this;
    }

    /**
     * whereSqlRepair
     * 条件语句 修补
     */
    private function whereSqlRepair()
    {
        $where = $this->where;
        if (!empty($where)) {
            $this->options['where'] = ' WHERE ' . $where;
        }

        $whereOr = $this->whereOr;
        if (!empty($whereOr)) {
            if (empty($this->options['where'])) {
                $this->options['where'] = ' WHERE ' . $whereOr;
            } else {
                //$this->options['where'] .= ' OR ' . $whereOr;
                $this->options['where'] .= ' AND ( ' . $whereOr . ' )';
            }
        }
        $this->where = '';
        $this->whereOr = '';
    }

    /**
     * order
     * @param string|array $order
     * @return $this
     */
    function order($order = null)
    {
        if (null !== $order && !empty($order)) {
            if (is_array($order)) {
                $order = implode(', ', $order);
            }

            $this->options['order'] = ' ORDER BY ' . $order;
        }


        return $this;
    }

    /**
     * 指定查询数量
     * @access public
     * @param mixed $offset 起始位置
     * @param mixed $length 查询数量
     * @return $this
     */
    function limit($offset = '1', $length = null)
    {
        $this->options['limit'] = ' LIMIT ' . $offset;
        if (null !== $length && is_numeric($length)) {
            $this->options['limit'] .= ', ' . $length;
        }
        return $this;
    }

    /**
     * group
     * @param string $group
     * @return $this
     */
    function group($group = null)
    {
        if (null !== $group && !empty($group)) {
            $this->options['group'] = ' GROUP BY ' . $group;
        }
        return $this;
    }


    /**
     * query 执行查询 返回数据集
     * @param $sql
     * @param array $bind
     * @param bool $fetch
     * @return PDOStatement
     */
    function query($sql, $bind = [], $fetch = false)
    {
        return $this->connection->query($sql, $bind, $fetch);
    }

    /**
     * 执行语句
     * @access public
     * @param string $sql sql指令
     * @param array $bind 参数绑定
     * @param bool $getLastInsID
     * @return int
     * @throws PDOException
     */
    private function execute($sql, $bind, $getLastInsID = false)
    {
        return $this->connection->execute($sql, $bind, $getLastInsID);
    }

    /**
     * rowCount 返回受影响的数量
     * @return int
     */
    function rowCount()
    {
        return $this->connection->rowCount();
    }


    /**
     * getLastSql 获取最后一条执行的SQL语句
     * @return string
     */
    function getLastSql()
    {
        return $this->connection->getLastSql();
    }

    /**
     * 获取绑定的参数 并清空
     * @access public
     * @return array
     */
    private function getBind()
    {
        $bind = $this->bind;
        $this->bind = [];
        return $bind;
    }

    /**
     * getLastInsId 获取最后插入成功的ID
     * @param null $sequence
     * @return string
     */
    function getLastInsId($sequence = null)
    {
        return $this->connection->getLastInsID($sequence);
    }

    /**
     * add_prepare 补充参数
     * @param array $data
     */
    private function add_prepare($data)
    {
        $this->preKeys = array_merge($this->preKeys, array_keys($data));
        $this->preValues = array_merge($this->preValues, array_values($data));
    }


    /**
     * value_real_to_update 参数值可用化
     * @param $value
     * @param $key
     * @return string
     */
    function value_real_to_update($value, $key = null)
    {
        $realStr = '';

        //检测是否是直接指定模式
        if (is_array($value)) {
            $valueCount = count($value);
            if ($valueCount == 1) {
                die('db update value special is error');
            }

            //str sum avg
            switch (strtolower($value[0])) {
                case 'str':
                    $realStr = '"' . $value[1] . '"';
                    break;

                //计算差
                case 'minus':
                    $minusV = $value;
                    unset($minusV[0]);
                    if ($valueCount == 2) {
                        if (null === $key) {
                            die('db minus value is error');
                        }
                        $minusV[2] = $minusV[1];
                        $minusV[1] = $key;
                    }

                    foreach ($minusV as $k => $v) {
                        if ($k > 1) {
                            $realStr .= ' - ';
                        }
                        $realStr .= is_numeric($v) || is_int($v) ? '"' . $v . '"' : '`' . $v . '`';
                    }
                    unset($minusV);
                    break;

                //计算和
                case 'sum':
                    $sumV = $value;
                    unset($sumV[0]);
                    if ($valueCount == 2) {
                        if (null === $key) {
                            die('db sum is error');
                        }
                        $sumV[2] = $key;
                    }

                    foreach ($sumV as $k => $v) {
                        if ($k > 1) {
                            $realStr .= ' + ';
                        }
                        $realStr .= is_numeric($v) || is_int($v) ? '"' . $v . '"' : '`' . $v . '`';
                    }
                    unset($sumV);
                    break;

                default:
                    $realStr = $value[0];
            }
        } else {
            $realStr = $this->value_real($value);
        }

        return $realStr;
    }

    /**
     * value_real 参数值可用化
     * @param $value
     * @return string
     */
    function value_real($value)
    {
        //检查是否自己已经包好了
        $strArr = str_split(trim($value));
        $startStr = $strArr[0];
        $endStr = end($strArr);

        //检测是否是直接指定模式
        switch (true) {
            case empty($value):
                return '"' . $value . '"';
                break;

            case $startStr == ':':
                return $value;
                break;

            case $value == '?':
                return $value;
                break;

            case is_numeric($value):
                return '"' . $value . '"';
                break;

            case is_int($value):
                return $value;
                break;

            case $startStr == '"' && $endStr == '"' :
                return $value;
                break;

            case $startStr == '\'' && $endStr == '\'' :
                return $value;
                break;

            default:
                return '"' . str_replace('"', '\"', $value) . '"';
                break;
        }
    }

    function db_error($message = null)
    {
        if ($this->getConfig('db_error') === true) {
            if ($message !== null) $this->message = $message;
            echo "\n" . '<br/>' . "\n" . $this->message;
        }
        die;
    }

}