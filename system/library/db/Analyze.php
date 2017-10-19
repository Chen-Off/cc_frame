<?php

namespace cc\db;

use cc\Cache;

/**
 * Db sql 语句分析.
 * User: Administrator
 * Date: 2017/10/7
 * Time: 15:26
 */
class Analyze
{
    /**
     * connection对象实例
     * @var Connection
     */
    protected $connection;

    /**
     * @var Query
     */
    protected $query;

    /**
     * 数据结构缓存名称
     * @var
     */
    protected $tableCacheName = 'db_table_cache';

    /**
     * 数据结构缓存内容
     * @var
     */
    protected $tableConstruct = [];

    /**
     * 数据表及其别名
     * @var array
     */
    protected $tablesNames = [];

    /**
     * 数据库配置配置
     * @var array
     */
    protected $config = [];


    // 数据库表达式

    protected $exp = [
        '> TIME', '< TIME', '>= TIME', '<= TIME',
        '<>', '<=', '>=', '>','<','!=','=',
        'NOT LIKE', 'LIKE',
        'NOT IN', 'IN',
        'EXP',
        'NOT EXISTS', 'EXISTS',
        'IS NOT NULL', 'IS NULL',
        'NOT BETWEEN TIME','BETWEEN TIME','NOT BETWEEN','BETWEEN'
    ];

    // SQL表达式
    protected $selectSql = 'SELECT%DISTINCT% %FIELD% FROM %TABLE%%FORCE%%JOIN%%WHERE%%GROUP%%HAVING%%ORDER%%LIMIT% %UNION%%LOCK%%COMMENT%';
    protected $insertSql = '%INSERT% INTO %TABLE% (%FIELD%) VALUES (%DATA%) %COMMENT%';
    protected $updateSql = 'UPDATE %TABLE% SET %SET% %JOIN% %WHERE% %ORDER% %LIMIT% %COMMENT%';
    protected $deleteSql = 'DELETE FROM %TABLE% %USING% %JOIN% %WHERE% %ORDER% %LIMIT% %COMMENT%';

    /**
     * 架构函数
     * @access public
     * @param Connection $connection 数据库连接对象实例
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
        $this->config = $connection->getConfig();
    }

    /**
     * 设置query驱动实例
     * setQuery
     * @param $query
     */
    public function setQuery($query)
    {
        $this->query = $query;
    }

    /**
     * 释放缓存
     * free
     */
    public function free()
    {
        $this->tableConstruct = [];
        $this->tablesNames = [];
    }

    /**
     * 生成查询SQL
     * @access public
     * @param array $options 表达式
     * @return string
     */
    public function select($options = [])
    {
        $sql = str_replace(
            ['%TABLE%', '%DISTINCT%', '%FIELD%', '%JOIN%', '%WHERE%', '%GROUP%', '%HAVING%', '%ORDER%', '%LIMIT%', '%UNION%', '%LOCK%', '%COMMENT%', '%FORCE%'],
            [
                $this->parseTable($options['table']),
                $this->parseDistinct($options['distinct']),
                $this->parseFields($options['fields']),
                $this->parseJoin($options['join']),
                $this->parseWhere($options['where']),
                $this->parseGroup($options['group']),
                $this->parseHaving($options['having']),
                $this->parseOrder($options['order']),
                $this->parseLimit($options['limit']),
                $this->parseUnion($options['union']),
                $this->parseLock($options['lock']),
                $this->parseComment($options['comment']),
                $this->parseForce($options['force']),
            ], $this->selectSql);
        return $sql;
    }

    /**
     * 分析表
     * parseTable
     * @param $table [description]   表名
     * @param $main [description]   是否为查询主表
     * @return bool
     */
    public function parseTable($table, $main = false)
    {
        //检测是否已经自定义设置好了表前缀
        $prefix = $this->config['db_prefix'] . '_';
        if (substr($table, 0, strlen($prefix)) != $prefix) {
            $table = $prefix . $table;
        }

        //空格别名截取处理
        $tableArr = explode(' ', $table);
        $tableName = $tableArr[0];
        $tableAs = isset($tableArr[1]) ? $tableArr[1] : '';

        //优先检测缓存
        $cacheName = $this->tableCacheName;
        $cache = self::hasCache($cacheName, $tableName);

        if (false === $cache) {
            //检测是否存在，并获取数据结构
            $rs = self::getCache($cacheName, $tableName);
            if (false === $rs) {
                $msg = '[DB ERROR]:数据表未发现 -【' . $tableName . '】';
                $this->connection->db_error($msg);
            }
        }

        $this->setTables($tableName, $tableAs, $main);

        return $table;
    }

    /**
     * 检测缓存
     * hasCache
     * @param $cache    [description]   缓存名称
     * @param $table    [description]   表名
     * @return bool
     */
    private function hasCache($cache, $table)
    {
        //获取缓存总数据
        $cacheData = Cache::get($cache);
        if (!isset($cacheData[$table])) {
            return false;
        } else {
            //写入缓存
            $this->tableConstruct = $cacheData;
            return true;
        }
    }

    /**
     * 获取缓存
     * getCache
     * @param $cache    [description]   缓存名称
     * @param $table    [description]   表名
     * @return array|bool
     */
    private function getCache($cache, $table)
    {
        //先检测是否存在已经获取
        if (isset($this->tableConstruct[$table])) {
            return true;
        } else {
            //检测是否存在，并获取数据结构
            $sql = 'SHOW TABLES LIKE "' . $table.'"';

            $rs = $this->connection->query($sql);
            if (!empty($rs)) {
                $sql = 'DESC ' . $table;
                $query = $this->connection->query($sql);
                //数据变换 字段做为键值
                //$keyArr = array_column($query, 'Field');
                $newQuery = array_combine(array_column($query, 'Field'), $query);

                //写入缓存
                $this->tableConstruct[$table] = $newQuery;

                $data = [$table => $newQuery];
                self::setCache($cache, $data);
                return true;
            } else {
                return false;
            }
        }
    }

    /**
     * 写入缓存
     * setCache
     * @param $cache    [description]   缓存名称
     * @param $data    [description]   缓存数据
     * @return bool
     */
    private function setCache($cache, $data)
    {
        //获取旧的缓存
        $oldCache = Cache::get($cache);
        if (empty($oldCache)) {
            $newCache = $data;
        } else {
            //合并缓存
            $newCache = array_merge($oldCache, $data);
        }
        //写入缓存  一天有效期限
        return Cache::set($cache, $newCache, 86400);
    }

    /**
     * 设置表数据及其别名
     * setTables
     * @param $table    [description]   表名
     * @param $as   [description]   表别名
     * @param $main
     */
    protected function setTables($table, $as, $main = false)
    {
        if (true === $main || empty($as)) {
            $this->tablesNames['main'] = $table;
        }

        $as = !empty($as) ? $as : $table;
        $this->tablesNames[$as] = $table;
    }

    /**
     * distinct分析
     * @access protected
     * @param mixed $distinct   [description]
     * @return string
     */
    protected function parseDistinct($distinct)
    {
        return !empty($distinct) ? ' DISTINCT ' : '';
    }

    /**
     * 分析表字段是否正确
     * parseFields
     * @param string|array $fields [description]   要分析的字段
     * @param string $exp [description]   切割和合并的参数
     * @return string
     */
    public function parseFields($fields, $exp = ',')
    {
        if(empty($fields)) {
            return $fields;
        }
        if (is_string($fields)) {
            $fields = explode($exp, $fields);
        }

        //移除空格
        $fields = array_map('trim', $fields);

        if (true === $this->config['check_desc']) {
            $tableNames = $this->tablesNames;
            $tableFields = $this->tableConstruct;
            //辨识数据表是否存在该字段
            foreach ($fields as $as => $val) {
                //是否属于特殊查询要素 avg, count .跳过不检测
                if (strpos($val, '(') !== false) {
                    continue;
                }

                //是否携带别名
                if (false !== strpos($val, '.')) {
                    $val = explode('.', $val);
                    $field = $val[1];
                    $tabAS = $val[0];
                } else {
                    $field = $val;
                    $tabAS = 'main';
                }

                //表名
                if (!isset($tableNames[$tabAS])) {
                    $msg = '[DB ERROR]:不存在或未设置的数据表别名 -【' . $tabAS . '】';
                    $this->connection->db_error($msg);
                }

                if($field == '*') {
                    continue;
                }

                $table = $tableNames[$tabAS];
                if (!isset($tableFields[$table][$field])) {
                    $msg = '[DB ERROR]:该表不存在这个字段 -【' . $table . ' - ' . $field . '】';
                    $this->connection->db_error($msg);
                }
            }
        }

        foreach ($fields as $as => $val) {
            if (!is_numeric($as)) {
                $fields[$as] = $val . ' AS ' . $as;
            }
        }

        $fieldsStr = implode($exp, $fields);
        return $fieldsStr;
    }

    /**
     * join分析
     * parseJoin
     * @param $join
     * @return string
     */
    protected function parseJoin($join)
    {
        $joinStr = '';
        if (!empty($join)) {
            $joinStr = ' ' . implode(' ', $join) . ' ';
        }
        return $joinStr;
    }

    /**
     * where分析
     * @access protected
     * @param mixed $where [description]   查询条件
     * @return string
     */
    protected function parseWhere($where)
    {
        $whereStr = $this->buildWhere($where);

        return empty($whereStr) ? '' : ' WHERE ' . $whereStr;
    }

    /**
     * 生成查询条件SQL
     * buildWhere
     * @param mixed $where [description]   查询条件
     * @return string
     */
    public function buildWhere($where)
    {
        if (empty($where)) {
            return '';
        }

        $msg = '';
        $expArr = $this->exp;
        $whereArr = [];
        $whereOrArr = [];

        //优先处理 WHERE 条件
        foreach ($where as $valArr) {
            if(!is_array($valArr['data'])) {
                $val = $valArr['data'];
                $valData = [];
                //根据条件表达式要素切割条件
                foreach ($expArr as $exp) {
                    if (false !== stripos($val, $exp)) {
                        $valData = preg_split('/' . $exp . '/i', $val);
                        $valData = array_map('trim', $valData);
                        $valData = [
                            'field' => $valData[0], //查询字段
                            'op' => $exp,   //查询表达式
                            'condition' => $valData[1],   //查询条件
                        ];
                        break;
                    }
                }
            } else {
                $valData = $valArr['data'];
                //检测是否存在表达式
                if(!in_array($valData['op'], $expArr)) {
                    $msg = '[DB ERROR]:错误的WHERE条件 表达式 -【' . $valData['op'] . '】';
                    break;
                }
                $val = implode(' ', $valData);
            }

            //检测是否存在表达式要素
            if (empty($valData)) {
                $msg = '[DB ERROR]:错误的WHERE条件 -【' . $val . '】';
                break;
            }

            //取得查询条件真实数据 根据表达式验证查询条件正确性
            $this->parseFields($valData['field']);
            $val = $this->parseWhereItem($valData['field'], $valData['op'], $valData['condition']);


            //聚合数据
            if ($valArr['type'] == 'where') {
                $whereArr[] = $val;
            } else {
                $whereOrArr[] = $val;
            }
        }

        //如果有报错，则退出
        if (!empty($msg)) {
            $this->connection->db_error($msg);
        }

        //聚合条件
        $str = '';
        if (!empty($whereArr)) {
            $str .= implode(' AND ', $whereArr);
        }
        if (!empty($whereOrArr)) {
            if (!empty($str)) {
                $str .= ' AND ';
            }
            $str .= '(' . implode(' OR ', $whereOrArr) . ')';
        }
        return $str;
    }

    /**
     * 处理where 条件字段内容
     * @param $field    [description]   字段
     * @param $exp  [description]   表达式
     * @param $value    [description]   字段值内容
     * @return string
     */
    private function parseWhereItem($field, $exp, $value)
    {
        //检测是否问号[?]绑定
        if ($value == '?') {
            return $field. ' '.$exp .' '. $value;
        }

        //检测是否使用参数绑定
        if (strpos($value, ':') === 0) {
            $bind = $this->query->getBind(substr($value, 1));
            if (empty($bind)) {
                $msg = '【' . $field . '】参数绑定【' . $value . '】未设置绑定内容';
                $this->connection->db_error($msg);
            }
            return $field. ' '.$exp .' '. $value;
        }

        switch (true) {
            // 比较运算 及 模糊匹配
            case in_array($exp, ['=', '<>', '>', '>=', '<', '<=', 'LIKE', 'NOT LIKE']):
                $value = $this->dealFieldVal($value);
                $whereStr = $field. ' '.$exp .' '.'"'.$value.'"';
                break;

            // NULL 查询
            case in_array($exp, ['IS NOT NULL', 'IS NULL']):
                $whereStr = $field. ' '.$exp .' ';
                break;

            // IN 查询
            case in_array($exp, ['NOT IN', 'IN']):
                $whereStr = $field. ' '.$exp .' '. $value;
                break;

            // BETWEEN 查询
            case in_array($exp, ['NOT BETWEEN', 'BETWEEN']):
                $whereStr = $field. ' '.$exp .' '. $value;
                break;

            // EXISTS 查询
            case in_array($exp, ['NOT EXISTS', 'EXISTS']):
                $whereStr = $field. ' '.$exp .' '. $value;
                break;

            // TIME 查询
            case in_array($exp, ['< TIME', '> TIME', '<= TIME', '>= TIME']):
                $whereStr = $field. ' '.$exp .' '. $value;
                break;

            default:
                $whereStr = $field. ' '.$exp .' '. $value;

        }
        return $whereStr;
    }

    /**
     * group分析
     * @access protected
     * @param mixed $group
     * @return string
     */
    protected function parseGroup($group)
    {
        return !empty($group) ? ' GROUP BY ' . $group : '';
    }

    /**
     * having分析
     * @access protected
     * @param string $having
     * @return string
     */
    protected function parseHaving($having)
    {
        return !empty($having) ? ' HAVING ' . $having : '';
    }

    /**
     * order分析
     * @access protected
     * @param mixed $order
     * @return string
     */
    protected function parseOrder($order)
    {
        if (empty($order)) {
            return '';
        }
        $sortArr = ['DESC', 'ASC', 'RAND()'];
        $array = [];
        foreach ($order as $field => $sort) {
            if (!empty($sort)) {
                $sort = strtoupper($sort);
                if (!in_array($sort, $sortArr)) {
                    $sort = '';
                }
            }
            $array[] = $this->parseFields($field) . ' ' . $sort;
        }

        $order = implode(',', $array);
        return !empty($order) ? ' ORDER BY ' . $order : '';
    }

    /**
     * limit分析
     * parseLimit
     * @param $limit
     * @return string
     */
    protected function parseLimit($limit)
    {
        $limitStr = '';
        if (!empty($limit)) {
            $limitStr = ' LIMIT ' . implode(', ', $limit) . ' ';
        }
        return $limitStr;
    }

    /**
     * union分析
     * @access protected
     * @param mixed $union
     * @return string
     */
    protected function parseUnion($union)
    {
        unset($union);
        //暂时不布置 2017年10月10日 16:03:37
        return '';
    }

    /**
     * 设置锁机制
     * @access protected
     * @param bool $lock
     * @return string
     */
    protected function parseLock($lock = false)
    {
        return $lock ? ' FOR UPDATE ' : '';
    }

    /**
     * comment分析
     * @access protected
     * @param string $comment
     * @return string
     */
    protected function parseComment($comment)
    {
        return !empty($comment) ? ' /* ' . $comment . ' */' : '';
    }

    /**
     * index分析，可在操作链中指定需要强制使用的索引
     * @access protected
     * @param mixed $index
     * @return string
     */
    protected function parseForce($index)
    {
        if (empty($index)) {
            return '';
        }

        if (is_array($index)) {
            $index = join(",", $index);
        }

        return sprintf(" FORCE INDEX ( %s ) ", $index);
    }

    /**
     * 插入数据
     * @param array $options [description]   参数数据
     * @return int|string
     */
    public function insert($options = [])
    {
        // 分析并处理数据
        $data = $this->parseData($options['data'], $options);
        $fields = array_keys($data);
        $values = array_values($data);

        $sql = str_replace(
            ['%INSERT%', '%TABLE%', '%FIELD%', '%DATA%', '%COMMENT%'],
            [
                'INSERT',
                $this->parseTable($options['table']),
                implode(' , ', $fields),
                implode(' , ', $values),
                $this->parseComment($options['comment']),
            ], $this->insertSql);

        return $sql;
    }

    /**
     * 生成update SQL
     * @access public
     * @param array $options 表达式 [description]   参数数据
     * @return string
     */
    public function update($options)
    {
        // 分析并处理数据
        $data = $this->parseData($options['data'], $options, 'update');
        $set = [];
        foreach ($data as $key => $val) {
            $set[] = $key . '=' . $val;
        }

        $sql = str_replace(
            ['%TABLE%', '%SET%', '%JOIN%', '%WHERE%', '%ORDER%', '%LIMIT%', '%COMMENT%'],
            [
                $this->parseTable($options['table']),
                implode(',', $set),
                $this->parseJoin($options['join']),
                $this->parseWhere($options['where']),
                $this->parseOrder($options['order']),
                $this->parseLimit($options['limit']),
                $this->parseComment($options['comment']),
            ], $this->updateSql);

        return $sql;
    }

    /**
     * 字段值校验
     * @param $data
     * @param $options
     * @param string $action
     * @return array
     */
    public function parseData($data, $options, $action = 'insert')
    {
        //获取该表的字段数据结构
        $tableName = $this->parseTable($options['table']);
        $fieldsData = $this->tableConstruct[$tableName];
        $bind = $this->query->getBind();

        //检测所提交的字段是否符合该表新增记录的基本要素
        foreach ($data as $field => $val) {
            if (!isset($fieldsData[$field])) {
                $msg = '【' . $field . '】不存在';
                break;
            }

            $fieldConst = $fieldsData[$field];
            unset($fieldsData[$field]);

            //检测是否使用旧版的的数据模式
            if (is_array($val)) {
                $data[$field] = $this->dealFieldValArr($val, $field);
                continue;
            }

            //检测是否问号[?]绑定
            if ($val == '?') {
                continue;
            }
            //检测是否使用参数绑定
            if (strpos($val, ':') === 0) {
                $bindKey = substr($val, 1);
                if (!isset($bind[$bindKey])) {
                    $msg = '【' . $field . '】参数绑定【' . $val . '】未设置绑定内容';
                    break;
                }
                $bindStatus = true;
                $val = $bind[$bindKey];
            } else {
                $bindStatus = false;
            }

            //值内容处理
            $val = $this->dealFieldVal($val);
            //检测字段值类型是否绑定和正确
            $msg = $this->checkFieldVal(strtolower($fieldConst['Type']), $val);
            //有错误的讯息，退出
            if (!empty($msg)) {
                $msg = '【' . $field . '】' . $msg;
                break;
            }

            //没有错误和非绑定类型，手动包裹值
            if (false === $bindStatus) {
                $data[$field] = '"' . $val . '"';
            }
        }

        if (!empty($msg)) {
            $this->connection->db_error('[DB ERROR]：数据表【' . $tableName . '】字段' . $msg);
        }

        //如果功能为新增则检测是有所有的字段参数都已经设置或者可以为为空和默认
        if ($action == 'insert') {
            $msg = '';
            foreach ($fieldsData as $field => $fieldData) {
                //自增
                if ($fieldData['Extra'] == 'auto_increment') {
                    continue;
                }

                //有默认值
                if ($fieldData['Default'] !== null) {
                    continue;
                }

                //可为空
                if ($fieldData['Null'] == 'YES') {
                    continue;
                }

                $msg = '【' . $field . '】值内容未设置';
                break;
            }

            if (!empty($msg)) {
                $this->connection->db_error('[DB ERROR]：数据表【' . $tableName . '】字段' . $msg);
            }
        }

        return $data;
    }


    /**
     * 旧版的的数组模式类型
     * @param $array
     * @param $field
     * @return string
     */
    private function dealFieldValArr($array, $field)
    {
        $realStr = '';
        $count = count($array);
        if ($count == 1) {
            die('db update value special is error');
        }
        $type = strtolower($array[0]);

        //str sum avg
        switch ($type) {
            case 'str':
                $realStr = $array[1];
                break;

            //计算差
            case 'minus':
                $minusV = $array;
                unset($minusV[0]);
                if ($count == 2) {
                    $minusV[2] = $minusV[1];
                    $minusV[1] = $field;
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
                $sumV = $array;
                unset($sumV[0]);
                if ($count == 2) {
                    if (null === $field) {
                        die('db sum is error');
                    }
                    $sumV[2] = $field;
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
                $realStr = $array[0];
        }
        return $realStr;
    }

    /**
     * 移除值的手动包裹
     * @param $val
     * @return string
     */
    private function dealFieldVal($val)
    {
        //检查是否自己已经包好了
        $startStr = substr($val, 0, 1);
        $endStr = substr($val, -1);

        //检测是否是直接指定模式
        switch (true) {
            case $startStr == '"' && $endStr == '"' :
            case $startStr == '\'' && $endStr == '\'' :
                $val = substr($val, 1, -1);
                break;

            default:
        }
        return $val;
    }

    /**
     * 2017年10月16日 17:33:29
     * 检测字段值是否正确（未完善）
     * @param $type
     * @param $val
     * @return string
     */
    private function checkFieldVal($type, $val)
    {
        $msg = '';
        switch ($type) {
            //时间格式
            case 'time':
            case 'year':
            case 'date':
            case 'datetime':
                if (false === strtotime($val)) {
                    $msg = '值内容不是一个正确的时间格式';
                }

                break;

            //无需计算
            case 'text':
            case 'longtext':
            case 'tinytext':
            case 'blob':
            case 'longblob':
            case 'tinyblob':
                break;

            //字符数限制设定
            default:
                preg_match('/(\w+)\((.*)\)/i', $type, $match);
                if (!empty($match)) {
                    if (is_int($match[2]) && strlen($val) > $match[2]) {
                        $msg = '值内容超过【' . $match[2] . '】';
                    }
                }

        }
        return $msg;
    }

    /**
     * 生成delete SQL
     * @access public
     * @param array $options 表达式
     * @return string
     */
    public function delete($options)
    {
        $sql = str_replace(
            ['%TABLE%', '%USING%', '%JOIN%', '%WHERE%', '%ORDER%', '%LIMIT%',  '%COMMENT%'],
            [
                $this->parseTable($options['table']),
                !empty($options['using']) ? ' USING ' . $this->parseTable($options['using']) . ' ' : '',
                $this->parseJoin($options['join']),
                $this->parseWhere($options['where']),
                $this->parseOrder($options['order']),
                $this->parseLimit($options['limit']),
                $this->parseComment($options['comment']),
            ], $this->deleteSql);

        return $sql;
    }

    /**
     * 获取表别名
     * parseTableAs
     * @param $table
     */
    public function parseTableAs($table)
    {
        //检测是否已经自定义设置好了表前缀
        $prefix = $this->config['db_prefix'] . '_';
        if (substr($table, 0, strlen($prefix)) != $prefix) {
            $table = $prefix . $table;
        }

        $tableAsArr = array_flip($this->tablesNames);
        if (!isset($tableAsArr[$table])) {
            $msg = '[DB ERROR]:该表不存在或者未载入 -【' . $table . '】';
            $this->connection->db_error($msg);
        }

        return $tableAsArr[$table];
    }

}

