<?php

/**
 * MySQL PDO 操作类
 */

namespace nice;

use nice\traits\InstanceTrait;

class PDO
{
    use InstanceTrait;

    /**
     * PDO 连接实例
     * @var \PDO
     */
    private $PDO;

    /**
     * PDOStatement 预处理实例
     * @var \PDOStatement
     */
    private $PDOStatement;

    /**
     * 最近一次错误对象
     * @var \PDOException
     */
    private $PDOException;

    /**
     * 获取类型别名
     */
    const FETCH_STYLE = [
        'ARRAY' =>  \PDO::FETCH_ASSOC,
        'NUM'   =>  \PDO::FETCH_NUM,
        'OBJ'   =>  \PDO::FETCH_OBJ,
        'BOTH'  =>  \PDO::FETCH_BOTH,
    ];

    /**
     * 连接数据库
     * @return self
     */
    public function connect($host, $database, $username = null, $password = null, $port = 3306, $options = null)
    {
        if (!$host || !$database) {
            throw new \ErrorException('PDO dsn parameter is illegal.');
        }
        $port = $port ?: 3306;
        $dsn = "mysql:dbname={$database};host={$host};port={$port}";
        try {
            $this->PDO = new \PDO($dsn, $username, $password, $options);
            return $this;
        } catch (\PDOException $e) {
            throw new \ErrorException('PDO connection failed.');
        }
    }

    /**
     * 执行 SQL 语句
     * UPDATE DELETE INSERT
     * @return int 影响行数
     */
    public function execute($sql, $bindValue = [], $dataType = [])
    {
        $this->PDOPrepare($sql)->PDOStatementExecute($bindValue, $dataType);
        return $this->PDOStatement->rowCount();
    }

    /**
     * 获取插入ID
     * INSERT
     * @return int
     */
    public function lastInsertId()
    {
        if (!$this->PDO || !($this->PDO instanceof \PDO)) {
            throw new \ErrorException('PDO not an PDO objcect.');
        }
        return $this->PDO->lastInsertId();
    }

    /**
     * 查询 SQL 语句
     * SELECT
     */
    public function query($sql, $bindValue = [], $dataType = [], $fetchStyle = 'array')
    {
        $this->PDOPrepare($sql)->PDOStatementExecute($bindValue, $dataType);
        return $this->PDOStatement->fetchAll(self::FETCH_STYLE[strtoupper($fetchStyle)] ?? NULL);
    }

    /**
     * 获取一条结果
     */
    public function one($sql, $bindValue = [], $dataType = [], $fetchStyle = 'array')
    {
        $this->PDOPrepare($sql)->PDOStatementExecute($bindValue, $dataType);
        return $this->PDOStatement->fetch(self::FETCH_STYLE[strtoupper($fetchStyle)] ?? NULL);
    }

    /**
     * 开启事务
     * @return bool
     */
    public function beginTransaction()
    {
        if (!$this->PDO || !($this->PDO instanceof \PDO)) {
            throw new \ErrorException('PDO not an PDO objcect.');
        }
        if (!$this->PDO->inTransaction()) {
            return $this->PDO->beginTransaction();
        }
        return true;
    }

    /**
     * 提交事务
     * @return bool
     */
    public function commit()
    {
        if ($this->PDO->inTransaction()) {
            return $this->PDO->commit();
        }
        return false;
    }

    /**
     * 回滚事务
     * @return bool
     */
    public function rollback()
    {
        if ($this->PDO->inTransaction()) {
            return $this->PDO->rollBack();
        }
        return false;
    }

    /**
     * 获取debug信息 - 直接输出
     */
    public function debugDumpParams()
    {
        if ($this->PDOStatement) {
            $this->PDOStatement->debugDumpParams();
        } else {
            echo '';
        }
    }

    /**
     * PDO 预处理
     * 获得 PDOStatement 语句实例
     * @return self
     */
    private function PDOPrepare($sql)
    {
        try {
            if (!$this->PDO || !($this->PDO instanceof \PDO)) {
                throw new \ErrorException('PDO not an PDO objcect.');
            }
            $PDOStatement = $this->PDO->prepare($sql);
            if ($PDOStatement === FALSE) {
                throw new \ErrorException('PDO prepare failed.');
            }
            $this->PDOStatement = $PDOStatement;
            return $this;
        } catch (\PDOException $e) {
            throw new \ErrorException($e->getMessage());
        }
    }

    /**
     * PDOStatement 语句对象执行
     * int => PDO::PARAM_INT, str => PDO::PARAM_STR
     * $valueType: ['key'=>int|str]
     * @return self
     */
    private function PDOStatementExecute($bindValue = [], $dataType = [])
    {
        if ($bindValue) {
            $dataTypeList = ['int' => \PDO::PARAM_INT, 'str' => \PDO::PARAM_STR];
            foreach ($bindValue as $k => $v) {
                if (array_key_exists($k, $dataType)) {
                    $kType = $dataTypeList[$dataType[$k]] ?? null;
                }
                if (is_int($k)) {
                    $bindRes = $this->PDOStatement->bindValue($k + 1, $v, $kType ?? \PDO::PARAM_STR);
                }
                if (is_string($k)) {
                    $bindRes = $this->PDOStatement->bindValue($k, $v, $kType ?? \PDO::PARAM_STR);
                }
                if ($bindRes === FALSE) {
                    $errorInfo = $this->PDOStatement->errorInfo();
                    throw new \ErrorException("PDOStatement bindValue failed. [SQLSTATE] {$errorInfo[0]};[errCode] {$errorInfo[1]};[errInfo] {$errorInfo[2]}");
                }
            }
        }
        if ($this->PDOStatement->execute() === FALSE) {
            $errorInfo = $this->PDOStatement->errorInfo();
            throw new \ErrorException("PDOStatement execute failed. [SQLSTATE] {$errorInfo[0]};[errCode] {$errorInfo[1]};[errInfo] {$errorInfo[2]}");
        }
        return $this;
    }
}
