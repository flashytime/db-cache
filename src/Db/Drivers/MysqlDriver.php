<?php
/**
 * Created by IntelliJ IDEA.
 * Author: flashytime
 * Date: 2015/5/21 18:29
 */

namespace Flashytime\DbCache\Db\Drivers;

use Flashytime\DbCache\Db\DbInterface;
use PDO;

/**
 * Class MysqlDriver
 * @package Flashytime\DbCache\Db\Drivers
 */
class MysqlDriver implements DbInterface
{
    /**
     * @var array
     */
    protected $config = [];

    /**
     * @var string
     */
    protected $table;

    /**
     * 是否启用分表
     * @var bool
     */
    protected $enableSharding = false;

    /**
     * 分表字段
     * @var string
     */
    protected $shardingField = null;

    /**
     * 分表个数
     * @var int
     */
    protected $shardingCount = null;

    /**
     * @var string
     */
    protected $sql;

    /**
     * @var array
     */
    protected $data;

    /**
     * @var string
     */
    protected $method;

    /**
     * @var array
     */
    protected static $connection = [];

    const SCHEMA_MASTER = 'master';
    const SCHEMA_SLAVE = 'slave';

    /**
     * MysqlDriver constructor.
     * @param array $config
     * @param array $table ['name' => '', 'sharding_field' => '', 'sharding_count' => '']
     */
    public function __construct(array $config, array $table)
    {
        $this->config = $config;
        $this->setTable($table);
    }

    /**
     * 查询单条数据
     * @param array $param
     * @param array $data
     * @return array|bool
     */
    public function select(array $param, array $data)
    {
        $param['field'] = '*';
        $param['limit'] = 1;
        $this->method = __FUNCTION__;

        return $this->query($param, $data);
    }

    /**
     * 查询列表
     * @param array $param
     * @param array $data
     * @return array|bool
     */
    public function all(array $param, array $data)
    {
        $this->method = __FUNCTION__;

        return $this->query($param, $data);
    }

    /**
     * 查询数量
     * @param array $param
     * @param array $data
     * @return int
     */
    public function count(array $param, array $data)
    {
        $param ['field'] = 'count(*)';
        $this->method = __FUNCTION__;

        return $this->query($param, $data);
    }

    /**
     * 插入数据
     * @param array $data
     * @return bool|int
     */
    public function insert(array $data)
    {
        $data = array_key_lower($data);
        $table = $this->table($data);
        $fields = $values = [];
        foreach ($data as $key => $value) {
            $fields[] = "`{$key}`";
            $values[] = ":{$key}";
        }
        $this->sql = "INSERT INTO {$table} (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $values) . ")";
        $this->data = $data;
        $this->method = __FUNCTION__;

        return $this->execute();
    }

    /**
     * 修改数据
     * @param array $param
     * @param array $data
     * @param $setData
     * @return bool|int
     */
    public function update(array $param, array $data, $setData)
    {
        $param = array_key_lower($param);
        $data = array_key_lower($data);
        $table = $this->table($data);
        $where = $this->where($param);
        $order = $this->order($param);
        $limit = $this->limit($param);
        $set = '';
        if (is_array($setData)) {
            $setData = array_key_lower($setData);
            foreach ($setData as $key => $value) {
                $set .= '`' . $key . '` = :' . $key . ',';
            }
            $set = rtrim($set, ',');
            $this->data = $data + $setData;
        } elseif (is_string($setData)) {
            $set = $setData;
            $this->data = $data;
        }
        if (!$set || !$where) {
            return false;
        }
        $this->sql = "UPDATE {$table} SET {$set}{$where}{$order}{$limit}";
        $this->method = __FUNCTION__;

        return $this->execute();
    }

    /**
     * 删除数据
     * @param array $param
     * @param array $data
     * @return bool|int
     */
    public function delete(array $param, array $data)
    {
        $param = array_key_lower($param);
        $data = array_key_lower($data);
        $table = $this->table($data);
        $where = $this->where($param);
        $order = $this->order($param);
        $limit = $this->limit($param);
        if (!$where && !$limit) {
            return false;
        }
        $this->sql = "DELETE FROM {$table}{$where}{$order}{$limit}";
        $this->data = $data;
        $this->method = __FUNCTION__;

        return $this->execute();
    }

    /**
     * 获取执行的sgl语句
     * @return string|null
     */
    public function getSql()
    {
        if (!empty($this->sql) && !empty($this->data)) {
            $sql = $this->sql;
            foreach ($this->data as $key => $value) {
                $sql = preg_replace('/:' . $key . '/', $value, $sql, 1);
            }

            return $sql;
        }

        return null;
    }

    /**
     * 事务开始
     * @return bool
     */
    public function beginTransaction()
    {
        return $this->getConnection(self::SCHEMA_MASTER)->beginTransaction();
    }

    /**
     * 事务提交
     * @return bool
     */
    public function commit()
    {
        return $this->getConnection(self::SCHEMA_MASTER)->commit();
    }

    /**
     * 事务回滚
     * @return bool
     */
    public function rollBack()
    {
        return $this->getConnection(self::SCHEMA_MASTER)->rollBack();
    }

    /**
     * @param array $table
     */
    public function setTable(array $table)
    {
        $table = array_key_lower($table);
        $this->table = $table['name'];
        $this->setShardingTable($table);
    }

    /**
     * @param array $param
     * @param array $data
     * @return array|bool|int
     */
    private function query(array $param, array $data)
    {
        $param = array_key_lower($param);
        $data = array_key_lower($data);
        $table = $this->table($data);
        $field = $this->field($param);
        $where = $this->where($param);
        $group = $this->group($param);
        $having = $this->having($param);
        $order = $this->order($param);
        $limit = $this->limit($param);
        $this->sql = "SELECT {$field} FROM {$table}{$where}{$group}{$having}{$order}{$limit}";
        $this->data = $data;

        return $this->execute(self::SCHEMA_SLAVE);
    }

    /**
     * @param string $schema
     * @return mixed
     */
    private function execute($schema = self::SCHEMA_MASTER)
    {
        $conn = $this->getConnection($schema);
        $stmt = $conn->prepare($this->sql);
        $this->bindValues($stmt, $this->data);
        $result = $stmt->execute();
        switch ($this->method) {
            case 'select':
                return $stmt->fetch(PDO::FETCH_ASSOC);
                break;
            case 'count':
                return intval($stmt->fetchColumn());
                break;
            case 'all':
            case 'query':
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
                break;
            case 'insert':
                return $conn->lastInsertId() ?: $result;
            case 'update':
            case 'delete':
                return $stmt->rowCount();
                break;
        }

        return $result;
    }

    /**
     * @param array $data
     * @return string
     * @throws \Exception
     */
    private function table(array $data)
    {
        if (!$this->enableSharding || $this->shardingCount <= 1) {
            return "`{$this->table}`";
        }
        if (!array_has($data, $this->shardingField)) {
            throw new \Exception("Data does not contain sharding field key: {$this->shardingField}", 500);
        }
        if ($data[$this->shardingField] <= 0) {
            throw new \Exception("Sharding field value error: {$data[$this->shardingField]}", 500);
        }
        $suffix = $data[$this->shardingField] % $this->shardingCount;

        return "`{$this->table}_{$suffix}`";
    }

    /**
     * @param $param
     * @return string
     */
    private function field($param)
    {
        return array_get($param, 'field', '*');
    }

    /**
     * @param $param
     * @return string
     */
    private function where($param)
    {
        return array_has($param, 'where') ? " WHERE {$param['where']}" : '';
    }

    /**
     * @param $param
     * @return string
     */
    private function group($param)
    {
        return array_has($param, 'group') ? " GROUP BY {$param['group']}" : '';
    }

    /**
     * @param $param
     * @return string
     */
    private function having($param)
    {
        return array_has($param, 'having') ? " HAVING {$param['having']}" : '';
    }

    /**
     * @param $param
     * @return string
     */
    private function order($param)
    {
        return array_has($param, 'order') ? " ORDER BY {$param['order']}" : '';
    }

    /**
     * @param $param
     * @return string
     */
    private function limit($param)
    {
        $limit = '';
        if (array_has($param, 'limit')) {
            $limit = $param['limit'];
            if (is_array($limit)) { //limit => [0,10]
                $limit = " LIMIT {$limit[0]}, {$limit[1]}";
            } elseif (is_numeric($limit)) { // limit => 10
                $limit = " LIMIT {$limit}";
            } elseif (is_string($limit)) { // limit => '0,10' 或者 limit => :limit进行绑定
                $limit = " LIMIT {$limit}";
            }
        }

        return $limit;
    }

    /**
     * @param $stmt \PDOStatement
     * @param $array
     */
    private function bindValues($stmt, $array)
    {
        foreach ($array as $key => $value) {
            $type = PDO::PARAM_STR;
            if (is_integer($value)) {
                $type = PDO::PARAM_INT;
            }
            $stmt->bindValue(':' . $key, $value, $type);
        }
    }

    /**
     * 设置分表
     * @param array $table
     */
    private function setShardingTable(array $table)
    {
        if (array_has($table, 'sharding_field') && array_has($table, 'sharding_count')) {
            $this->enableSharding = true;
            $this->shardingField = $table['sharding_field'];
            $this->shardingCount = intval($table['sharding_count']);
        }
    }

    /**
     * @param string $schema write|read
     * @return PDO
     * @throws \Exception
     */
    private function getConnection($schema)
    {
        if (!isset(self::$connection[$schema])) {
            $db = $this->config['mysql'];
            $config = $db[$schema];
            $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$db['database']};charset={$db['charset']}";
            try {
                $pdo = new PDO($dsn, $config['username'], $config['password']);
            } catch (\PDOException $e) {
                throw new \Exception('connect database error: ' . $e->getMessage(), 500);
            }
            $pdo->exec("set names {$db['charset']}");
            self::$connection[$schema] = $pdo;
        }

        return self::$connection[$schema];
    }
}
