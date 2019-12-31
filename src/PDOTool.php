<?php


namespace BastMain\utils;

class PDOTool
{
    static private $pdo;

    static public $_config = [
        'host' => '127.0.0.1',
        'username' => 'root',
        'password' => 'root',
        'database' => 'test',
        'port' => '3306',
        'charset' => 'utf8',
    ];

    /**
     * 初始化链接
     *
     * @throws \Exception
     */
    static private function Init()
    {
        if (self::$pdo == NULL) {
            $options = array();
            try {
                self::$pdo = new \PDO('mysql:host=' . self::$_config['host'] . ';dbname=' . self::$_config['database'] . ';port=' . self::$_config['port'] . ';charset=' . self::$_config['charset'], self::$_config['username'], self::$_config['password'], $options);
                self::$pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            } catch (\Exception $ex) {
                throw $ex;
                //return $ex->getMessage();
            }
        }
        return self::$pdo;
    }

    /**
     * 开始事务
     * @return bool
     * @throws \Exception
     */
    static public function beginTransaction()
    {
        return self::Init()->beginTransaction();
    }

    /**
     * 提交事务
     * @return bool
     * @throws \Exception
     */
    static public function commit()
    {
        return self::Init()->commit();
    }

    /**
     * 事务回滚
     * @return bool
     * @throws \Exception
     */
    static public function rollBack()
    {
        return self::Init()->rollBack();
    }

    /**
     * 错误代码
     * @return mixed
     * @throws \Exception
     */
    static public function errorCode()
    {
        return self::Init()->errorCode();
    }

    /**
     * 错误信息
     * @return array
     * @throws \Exception
     */
    static public function errorInfo()
    {
        return self::Init()->errorInfo();
    }

    /**
     * 上次插入的ID
     * @return string
     * @throws \Exception
     */
    static public function lastInsertId()
    {
        return self::Init()->lastInsertId();
    }

    /**
     * 获取一条记录
     * @param $sql
     * @param array $params
     * @return mixed
     * @throws \Exception
     */
    static public function fetch($sql, $params = array(), $fetchMode = \PDO::FETCH_ASSOC)
    {
        $results = self::prepare($sql, $params);
        $results->setFetchMode($fetchMode);
        return $results->fetch();
    }

    /**
     * 获取一个字段值
     * @param $sql
     * @param array $params
     * @return string
     * @throws \Exception
     */
    static public function fetchColumn($sql, $params = array())
    {
        $results = self::prepare($sql, $params);
        return $results->fetchColumn();
    }

    /**
     * 获取一个数据集
     * @param $sql
     * @param array $params
     * @return array
     * @throws \Exception
     */
    static public function fetchAll($sql, $params = array(), $keyfield = '', $fetchMode = \PDO::FETCH_ASSOC)
    {
        $results = self::prepare($sql, $params);
        $results->setFetchMode($fetchMode);
        $res = $results->fetchAll();
        $ret = $res;
        if (!empty($res) && !empty($keyfield)) {
            $ret = array();

            foreach ($res as $val) {
                $ret[$val[$keyfield]] = $val;
            }
        }

        return $ret;
    }

    static public function insert($table, $data = array(), $replace = false)
    {
        $cmd = $replace ? 'REPLACE INTO' : 'INSERT INTO';
        $condition = self::implode($data, ',');
        return self::exec($cmd . ' ' . ($table) . (' SET ' . $condition['fields']), $condition['params']);
    }

    static public function update($table, $data = array(), $params = array(), $glue = 'AND')
    {
        $fields = self::implode($data, ',');
        $condition = self::implode($params, $glue);
        $params = array_merge($fields['params'], $condition['params']);
        $sql = 'UPDATE ' . ($table) . (' SET ' . $fields['fields']);
        $sql .= $condition['fields'] ? ' WHERE ' . $condition['fields'] : '';
        return self::exec($sql, $params);
    }

    static public function delete($table, $params = array(), $glue = 'AND')
    {
        $condition = self::implode($params, $glue);
        $sql = 'DELETE FROM ' . ($table);
        $sql .= $condition['fields'] ? ' WHERE ' . $condition['fields'] : '';
        return self::exec($sql, $condition['params']);
    }

    static private function implode($params, $glue = ',')
    {
        $result = array(
            'fields' => ' 1 ',
            'params' => array()
        );
        $split = '';
        $suffix = '';
        $allow_operator = array('>', '<', '<>', '!=', '>=', '<=', '+=', '-=', 'LIKE', 'like');

        if (in_array(strtolower($glue), array('and', 'or'))) {
            $suffix = '__';
        }

        if (!is_array($params)) {
            $result['fields'] = $params;
            return $result;
        }

        if (is_array($params)) {
            $result['fields'] = '';

            foreach ($params as $fields => $value) {
                $operator = '';

                if (strpos($fields, ' ') !== false) {
                    list($fields, $operator) = explode(' ', $fields, 2);

                    if (!in_array($operator, $allow_operator)) {
                        $operator = '';
                    }
                }

                if (empty($operator)) {
                    $fields = trim($fields);
                    if (is_array($value) && !empty($value)) {
                        $operator = 'IN';
                    } else {
                        $operator = '=';
                    }
                } else if ($operator == '+=') {
                    $operator = ' = `' . $fields . '` + ';
                } else if ($operator == '-=') {
                    $operator = ' = `' . $fields . '` - ';
                } else {
                    if ($operator == '!=' || $operator == '<>') {
                        if (is_array($value) && !empty($value)) {
                            $operator = 'NOT IN';
                        }
                    }
                }

                if (is_array($value) && !empty($value)) {
                    $insql = array();
                    $value = array_values($value);

                    foreach ($value as $k => $v) {
                        $insql[] = ':' . $suffix . $fields . '_' . $k;
                        $result['params'][':' . $suffix . $fields . '_' . $k] = is_null($v) ? '' : $v;
                    }

                    $result['fields'] .= $split . ('`' . $fields . '` ' . $operator . ' (') . implode(',', $insql) . ')';
                    $split = ' ' . $glue . ' ';
                } else {
                    $result['fields'] .= $split . ('`' . $fields . '` ' . $operator . '  :' . $suffix . $fields);
                    $split = ' ' . $glue . ' ';
                    $result['params'][':' . $suffix . $fields] = is_null($value) || is_array($value) ? '' : $value;
                }
            }
        }

        return $result;
    }

    /**
     * 执行SQL, 插入返回 lastInsertID 其他返回影响行数
     * @param $sql
     * @param array $params
     * @return int|string
     * @throws \Exception
     */
    static public function exec($sql, $params = array())
    {
        $results = self::prepare($sql, $params);

        if (preg_match('/^\\s*(INSERT\\s+INTO|REPLACE\\s+INTO)\\s+/i', $sql)) {
            return (int)self::Init()->lastInsertId();
        }

        return $results->rowCount();
    }

    /**
     * @param $sql
     * @param array $params
     * @return bool|\PDOStatement
     * @throws \Exception
     */
    static protected function prepare($sql, $params = array())
    {
        try {
            $stmt = self::Init()->prepare($sql);

            if (!is_array($params)) {
                $params = array();
            }

            $exec = $stmt->execute($params);

            if ($exec) {
                return $stmt;
            }

            return false;
        } catch (\Exception $ex) {
            if ($ex->getCode() == 'HY000') {
                self::$pdo = NULL;
                return self::prepare($sql, $params);
            }

            throw $ex;
        }
    }
}

