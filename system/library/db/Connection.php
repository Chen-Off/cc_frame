<?php
namespace cc\db;

use PDO;
use PDOStatement;
use PDOException;

use cc\Db;
use cc\db\Query;

/**
 * Class Connection
 * @package cc\db
 *
 * @method Query table(string $table) 指定数据表（含前缀）
 */
class Connection
{
    /** @var PDOStatement PDO操作实例 */
    protected $PDOStatement;

    /**
     * 数据库操作分析实例
     * @var Analyze
     */
    protected $analyze;

    /** @var string 当前SQL指令 */
    // 返回或者影响记录数
    protected $numRows = 0;
    // 事务指令数
    protected $transTimes = 0;

    protected $queryStr = '';

    /** @var PDO[] 数据库连接ID 支持多个连接 */
    protected $links = [];


    // 查询结果类型
    protected $fetchType = PDO::FETCH_ASSOC;

    /** @var PDO 当前连接ID */
    protected $linkID;

    /** @var string 错误信息 */
    protected $message;

    // 查询对象
    protected $query = [];

    // 数据库连接参数配置
    protected $config = [
        'db_host' => '',
        'db_user' => '',
        'db_password' => '',
        'db_name' => '',
        'db_prefix' => '',

        'port' => '3306',
        'charset' => 'utf8',
        'db_error' => false,

        'analyze' => 'mysql',
    ];

    // PDO连接参数
    protected $params = [
        PDO::ATTR_CASE => PDO::CASE_NATURAL,
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_ORACLE_NULLS => PDO::NULL_NATURAL,
        PDO::ATTR_STRINGIFY_FETCHES => false,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];


    /**
     * 架构函数 读取数据库配置信息
     * @access public
     * @param array $config 数据库配置数组
     */
    public function __construct(array $config = [])
    {
        if (!empty($config)) {
            $this->config = array_merge($this->config, $config);
        }
    }


    /**
     * 调用Query类的查询方法
     * @access public
     * @param string $method 方法名称
     * @param array $args 调用参数
     * @return mixed
     */
    public function __call($method, $args)
    {
        if (!isset($this->query['database'])) {
            $this->query['database'] = new Query($this);
        }
        return call_user_func_array([$this->query['database'], $method], $args);
    }

    /**
     * 创建指定模型的查询对象
     * @access public
     * @param string $model 模型类名称
     * @return Query
     */
    public function model($model)
    {
        if (!isset($this->query[$model])) {
            $this->query[$model] = new Query($this, $model);
        }
        return $this->query[$model];
    }

    /**
     * 析构方法
     * @access public
     */
    public function __destruct()
    {
        // 释放查询
        if ($this->PDOStatement) {
            $this->free();
        }
        // 关闭连接
        $this->close();
    }

    /**
     * 关闭数据库
     * @access public
     */
    public function close()
    {
        $this->linkID = null;
    }


    /**
     * 获取最近一次查询的sql语句
     * @access public
     * @return string
     */
    public function getLastSql()
    {
        return $this->queryStr;
    }

    /**
     * 获取最近插入的ID
     * @access public
     * @param string $sequence 自增序列名
     * @return string
     */
    public function getLastInsID($sequence = null)
    {
        return $this->linkID->lastInsertId($sequence);
    }

    /**
     * 获取返回或者影响的记录数
     * @access public
     * @return integer
     */
    public function getNumRows()
    {
        return $this->numRows;
    }

    /**
     * 获取最近的错误信息
     * @access public
     * @return string
     */
    public function getError()
    {
        if ($this->PDOStatement) {
            $error = $this->PDOStatement->errorInfo();
            $error = $error[1] . ':' . $error[2];
        } else {
            $error = '';
        }
        if ('' != $this->queryStr) {
            $error .= "\n [ SQL语句 ] : " . $this->queryStr;
        }
        return $error;
    }

    /**
     * SQL指令安全过滤
     * @access public
     * @param string $str SQL字符串
     * @param bool $master 是否主库查询
     * @return string
     */
    public function quote($str, $master = true)
    {
        $this->initConnect($master);
        return $this->linkID ? $this->linkID->quote($str) : $str;
    }

    /**
     * 初始化数据库连接
     * @access protected
     * @param boolean $master 是否主服务器
     * @return void
     */
    protected function initConnect($master = true)
    {
        // 默认单数据库
        $this->linkID = $this->connect();
    }


    /**
     * 连接数据库方法
     * @access public
     * @param array $config 连接参数
     * @param integer $linkNum 连接序号
     * @return PDO
     */
    function connect(array $config = [], $linkNum = 0)
    {
        if (!isset($this->links[$linkNum])) {
            if (!$config) {
                $config = $this->config;
            } else {
                $config = array_merge($this->config, $config);
            }

            try {
                $this->links[$linkNum] = new PDO('mysql:host=' . $config['db_host'] . ';dbname=' . $config['db_name'], $config['db_user'], $config['db_password'], $this->params);
            } catch (PDOException $e) {
                $this->message = '[DB ERROR]：数据库链接失败 - ' . $e->getMessage();
                $this->db_error();
            }
            $this->links[$linkNum]->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->links[$linkNum]->exec('set names '.$config['charset']);
            //$this->links[$linkNum]->beginTransaction();
        }

        return $this->links[$linkNum];
    }


    /**
     * 释放查询结果
     * @access public
     */
    public function free()
    {
        $this->PDOStatement = null;
    }

    /**
     * 获取PDO对象
     * @access public
     * @return \PDO|false
     */
    public function getPdo()
    {
        if (!$this->linkID) {
            return false;
        } else {
            return $this->linkID;
        }
    }


    /**
     * query
     * @param $sql
     * @param null $bind
     * @param bool $fetch
     * @return array|bool|mixed
     */
    function query($sql, $bind = null, $fetch = false)
    {
        $this->initConnect(true);
        if (!$this->linkID) {
            return false;
        }

        // 根据参数绑定组装最终的SQL语句
        $this->queryStr = $this->getRealSql($sql, $bind);

        try {
            // 预处理
            $this->PDOStatement = $this->linkID->prepare($sql);
            // 参数绑定操作
            $this->bindValue($bind);
            // 执行语句
            $result = $this->PDOStatement->execute();

            return $this->getResult($fetch);
        } catch (PDOException $e) {
            $this->message = '[DB ERROR]：'.$e->getMessage() . '{' . $this->queryStr . '}';
            $this->db_error();
            return false;

            //throw new PDOException($e, $this->config, $this->queryStr);
        }
    }

    /**
     * execute
     * @param $sql
     * @param null $bind
     * @param bool $getLastInsID
     * @return bool|int|string
     */
    function execute($sql, $bind = null, $getLastInsID = false)
    {
        $this->initConnect(true);
        if (!$this->linkID) {
            return false;
        }

        // 根据参数绑定组装最终的SQL语句
        $this->queryStr = $this->getRealSql($sql, $bind);
        try {
            // 预处理
            $this->PDOStatement = $this->linkID->prepare($sql);
            // 参数绑定操作
            $this->bindValue($bind);
            // 执行语句
            $this->PDOStatement->execute();
            //$this->linkID->commit();

            if (true === $getLastInsID) {
                return $this->linkID->lastInsertId();
            } else {
                $this->numRows = $this->PDOStatement->rowCount();
                return $this->numRows;
            }
        } catch (PDOException $e) {
            $this->message = '[DB ERROR]：'.$e->getMessage() . '{' . $this->queryStr . '}';
            $this->db_error();

            //throw new PDOException($e, $this->config, $this->queryStr);
        }
    }


    /**
     * 执行数据库事务
     * @access public
     * @param callable $callback 数据操作方法回调
     * @return mixed
     * @throws PDOException
     * @throws \Exception
     * @throws \Throwable
     */
    public function transaction($callback)
    {
        $this->startTrans();
        try {
            $result = null;
            if (is_callable($callback)) {
                $result = call_user_func_array($callback, [$this]);
            }
            $this->commit();
            return $result;
        } catch (\Exception $e) {
            $this->rollback();
            throw $e;
        } catch (\Throwable $e) {
            $this->rollback();
            throw $e;
        }
    }

    /**
     * 启动事务
     * startTrans
     * @return bool
     */
    public function startTrans()
    {
        $this->initConnect(true);
        if (!$this->linkID) {
            return false;
        }

        ++$this->transTimes;

        if (1 == $this->transTimes) {
            $this->linkID->beginTransaction();
        } elseif ($this->transTimes > 1 && $this->supportSavepoint()) {
            $this->linkID->exec(
                $this->parseSavepoint('trans' . $this->transTimes)
            );
        }
    }

    /**
     * 用于非自动提交状态下面的查询提交
     * @access public
     * @return void
     * @throws PDOException
     */
    public function commit()
    {
        $this->initConnect(true);

        if (1 == $this->transTimes) {
            $this->linkID->commit();
        }

        --$this->transTimes;
    }

    /**
     * 事务回滚
     * @access public
     * @return void
     * @throws PDOException
     */
    public function rollback()
    {
        $this->initConnect(true);

        if (1 == $this->transTimes) {
            $this->linkID->rollBack();
        } elseif ($this->transTimes > 1 && $this->supportSavepoint()) {
            $this->linkID->exec(
                $this->parseSavepointRollBack('trans' . $this->transTimes)
            );
        }

        $this->transTimes = max(0, $this->transTimes - 1);
    }

    /**
     * 是否支持事务嵌套
     * @return bool
     */
    protected function supportSavepoint()
    {
        return false;
    }

    /**
     * 生成定义保存点的SQL
     * @param $name
     * @return string
     */
    protected function parseSavepoint($name)
    {
        return 'SAVEPOINT ' . $name;
    }

    /**
     * 生成回滚到保存点的SQL
     * @param $name
     * @return string
     */
    protected function parseSavepointRollBack($name)
    {
        return 'ROLLBACK TO SAVEPOINT ' . $name;
    }

    /**
     * batchQuery
     * 批处理执行SQL语句
     * 批处理的指令都认为是execute操作
     * @access public
     * @param array $sqlArray SQL批处理指令
     *
     * @return bool
     * @throws \Exception
     */
    public function batchQuery($sqlArray = [])
    {
        if (!is_array($sqlArray)) {
            return false;
        }
        // 自动启动事务支持
        $this->startTrans();
        try {
            foreach ($sqlArray as $sql) {
                $this->execute($sql);
            }
            // 提交事务
            $this->commit();
        } catch (\Exception $e) {
            $this->rollback();
            throw $e;
        }
        return true;
    }

    /**
     * getResult 获得数据集
     * @param $fetch    [description]   是否返回多维数组
     * @return array
     */
    function getResult($fetch = false)
    {
        if (true === $fetch) {
            $result = $this->PDOStatement->fetch($this->fetchType);
        } else {
            $result = $this->PDOStatement->fetchAll($this->fetchType);
        }

        $this->numRows = count($result);
        return $result;
    }


    /**
     * rowCount 返回受影响的数量
     * @return int
     */
    function rowCount()
    {
        return $this->numRows;
    }

    /**
     * 获取数据库的配置参数
     * @access public
     * @param string $config 配置名称
     * @return mixed
     */
    public function getConfig($config = '')
    {
        return $config ? $this->config[$config] : $this->config;
    }

    /**
     * 设置数据库的配置参数
     * @access public
     * @param string|array $config 配置名称
     * @param mixed $value 配置值
     * @return void
     */
    public function setConfig($config, $value = '')
    {
        if (is_array($config)) {
            $this->config = array_merge($this->config, $config);
        } else {
            $this->config[$config] = $value;
        }
    }

    /**
     * 根据参数绑定组装最终的SQL语句 便于调试
     * @access public
     * @param string $sql [description]    带参数绑定的sql语句
     * @param array $bind [description]    参数绑定列表
     * @return string
     */
    public function getRealSql($sql, array $bind = null)
    {
        if (null !== $bind) {
            foreach ($bind as $key => $val) {
                $value = is_array($val) ? $val[0] : $val;
                $type = is_array($val) ? $val[1] : PDO::PARAM_STR;
                if (PDO::PARAM_STR == $type) {
                    $value = $this->quote($value);
                }

                // 判断占位符
                $sql = is_numeric($key) ?
                    substr_replace($sql, $value, strpos($sql, '?'), 1) :
                    str_replace(
                        [':' . $key . ')', ':' . $key . ',', ':' . $key . ' '],
                        [$value . ')', $value . ',', $value . ' '],
                        $sql . ' ');
            }
        }
        return $sql;
    }

    /**
     * 参数绑定
     * 支持 ['name'=>'value','id'=>123] 对应命名占位符
     * 或者 ['value',123] 对应问号占位符
     * @access public
     * @param array $bind 要绑定的参数列表
     */
    protected function bindValue($bind = null)
    {
        if (null !== $bind && !empty($bind)) {
            foreach ($bind as $key => $val) {
                // 占位符
                $param = is_numeric($key) ? $key + 1 : ':' . $key; //$arr['value'] = ':value';
                if (is_array($val)) {
                    $result = $this->PDOStatement->bindValue($param, $val[0], $val[1]);
                } else {
                    $result = $this->PDOStatement->bindValue($param, $val);
                }

                if (!$result) {
                    $this->message = '[DB ERROR]：所绑定的参数是未成功绑定{' . PHP_EOL . $this->queryStr . PHP_EOL . '}';
                    $this->db_error();
                }
            }
        }
    }

    /**
     * 错误退出
     * @param null $message
     */
    function db_error($message = null)
    {
        if ($this->config['db_error'] === true) {
            if (null === $message) $message = $this->message;

            if (!empty($message)) {
                echo $message;
            } else {
                echo "\n" . '<br/>' . "\n" . $this->getError();
            }

            $array =debug_backtrace();
            unset($array[0]);
            $html = '';
            foreach($array as $row)
            {
                $line = isset($row['line']) ? $row['line'] : '';
                $file = isset($row['file']) ? $row['file'] : '';
                $html .= "\n" . '<br/>' . "\n".$file.':'.$line.'行,调用方法:'.$row['function'];
            }
            echo $html;
        }
        die;
    }

}