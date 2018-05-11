<?php
/**
 * Created by IntelliJ IDEA.
 * Author: flashytime
 * Date: 2015/5/23 18:29
 */

namespace Flashytime\DbCache;

use Flashytime\DbCache\Cache\Cache;
use Flashytime\DbCache\Cache\CacheInterface;
use Flashytime\DbCache\Db\Db;
use Flashytime\DbCache\Db\DbInterface;

/**
 * Class DbCache
 * @package Flashytime\DbCache
 */
class DbCache
{
    /**
     * @var CacheInterface
     */
    protected $cache;

    /**
     * @var DbInterface
     */
    protected $db;

    /**
     * @var CacheVersion
     */
    protected $cacheVersion;

    /**
     * @var array
     */
    public $config;

    /**
     * 模块
     * @var string
     */
    public $module;

    /**
     * 表名
     * @var string
     */
    public $table;

    /**
     * 主键
     * @var array
     */
    public $primary = [];

    /**
     * DbCache constructor.
     * @param array $config
     * @param $module
     * @param array $table
     */
    public function __construct(array $config, $module, array $table)
    {
        $this->config = $config;
        $this->module = $module;
        $this->setTable($table);
        $this->db = new Db($config['db'], $table);
        $this->cache = new Cache($config['cache']);
        $this->cacheVersion = new CacheVersion($this->cache);
    }

    /**
     * 查询单条记录
     * 关键点：判断查询条件中是否只包含主键，如果是则基本会命中缓存，否则筛选出只包含主键的数据存入缓存，以待下一次查询进行映射
     * @param array $param
     * @param array $data
     * @return array|bool|mixed
     */
    public function select(array $param, array $data)
    {
        if (!$this->enableCache()) {
            return $this->getDb()->select($param, $data);
        }

        $args = func_get_args();
        if ($isPrimaryQuery = $this->getIsPrimaryQuery($param)) {
            $args = $this->getPrimaryKeyStr($data);
        }

        $singleKey = $this->getCacheVersion()->getSingleKey($this->getModule(), $args);
        $result = $this->getCache()->get($singleKey);
        if ($result === false) {
            $result = $this->getDb()->select($param, $data);
            if ($result) {
                if ($isPrimaryQuery) {
                    $this->getCache()->set($singleKey, json_encode($result), $this->getCacheExpiration());
                } else {
                    //如果不是主键查询，则不直接保存从DB中查出的结果，而是保存主键value，以便下次进行映射
                    $primaryKeyData = $this->getPrimaryKeyData($result);
                    $this->getCache()->set($singleKey, json_encode($primaryKeyData), $this->getCacheExpiration());
                    ////同时存入主键形式的数据，以便下次递归时进行查询
                    $primarySingleKey = $this->getCacheVersion()->getSingleKey($this->getModule(), $this->toString($primaryKeyData));
                    $this->getCache()->set($primarySingleKey, json_encode($result), $this->getCacheExpiration());
                }
            }
        } else {
            $result = json_decode($result, true);
            //如果查询条件不是主键查询，则需要转换成主键递归一次，返回上一次同样条件查询时存入缓存的数据
            if (!$isPrimaryQuery) {
                $where = [];
                foreach ($this->primary as $key) {
                    $where[] = "{$key} = :{$key}";
                }
                $param['where'] = implode(' AND ', $where);
                $this->select($param, $result);
            }
        }

        return $result;
    }

    /**
     * 查询列表数据
     * @param array $param
     * @param array $data
     * @return array|bool
     */
    public function all(array $param, array $data)
    {
        if (!$this->enableCache()) {
            return $this->getDb()->all($param, $data);
        }
        $listKey = $this->getCacheVersion()->getListKey($this->getModule(), func_get_args());
        $result = $this->getCache()->get($listKey);
        if ($result === false) {
            $result = $this->getDb()->all($param, $data);
            $this->getCache()->set($listKey, json_encode($result), $this->getCacheExpiration());
        } else {
            $result = json_decode($result, true);
        }

        return $result;
    }

    /**
     * 查询数量
     * @param array $param
     * @param array $data
     * @return int
     */
    public function count(array $param, array $data)
    {
        if (!$this->enableCache()) {
            return $this->getDb()->count($param, $data);
        }
        $countKey = $this->getCacheVersion()->getCountKey($this->getModule(), func_get_args());
        $count = $this->getCache()->get($countKey);
        if ($count === false) {
            $count = $this->getDb()->count($param, $data);
            $this->getCache()->set($countKey, $count, $this->getCacheExpiration());
        }

        return $count;
    }

    /**
     * 插入数据
     * @param array $data
     * @return bool|int
     */
    public function insert(array $data)
    {
        $result = $this->getDb()->insert($data);
        if ($this->enableCache()) {
            $this->getCacheVersion()->updateListVersion($this->getModule());
        }

        return $result;
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
        if (!$this->enableCache()) {
            return $this->getDb()->update($param, $data, $setData);
        }
        $rowCount = $this->getDb()->update($param, $data, $setData);
        if ($rowCount > 0) {
            if ($rowCount > $this->getForceFlushCount()) {
                $this->getCacheVersion()->updateSingleVersion($this->getModule());
            } else {
                $result = $this->getDb()->all($param, $data);
                foreach ($result as $row) {
                    $singleKey = $this->getCacheVersion()->getSingleKey($this->getModule(), $this->getPrimaryKeyStr($row));
                    $this->getCache()->delete($singleKey);
                }
            }
            $this->getCacheVersion()->updateListVersion($this->getModule());
        }

        return $rowCount;
    }

    /**
     * 删除数据
     * @param array $param
     * @param array $data
     * @return bool|int
     */
    public function delete(array $param, array $data)
    {
        if (!$this->enableCache()) {
            return $this->getDb()->delete($param, $data);
        }
        $rowCount = $this->getDb()->delete($param, $data);
        if ($rowCount > 0) {
            if ($rowCount > $this->getForceFlushCount()) {
                $this->getCacheVersion()->updateSingleVersion($this->getModule());
            } else {
                $result = $this->getDb()->all($param, $data);
                foreach ($result as $row) {
                    $singleKey = $this->getCacheVersion()->getSingleKey($this->getModule(),
                        $this->getPrimaryKeyStr($row));
                    $this->getCache()->delete($singleKey);
                }
            }
            $this->getCacheVersion()->updateListVersion($this->getModule());
        }

        return $rowCount;
    }

    /**
     * 设置表和主键
     * @param array $table
     * @throws \Exception
     */
    public function setTable(array $table)
    {
        $table = array_key_lower($table);
        if (!array_has($table, 'name') || !array_has($table, 'primary')) {
            throw new \Exception('Table config error', 500);
        }
        $this->table = $table['name'];
        $this->setPrimary($table['primary']);
    }

    /**
     * 设置主键
     * @param $primary
     */
    public function setPrimary($primary)
    {
        $primaryArray = array_map('trim', explode(',', strtolower($primary)));
        if (count($primaryArray) > 1) {
            usort($primaryArray, function ($a, $b) {
                return strlen($a) > strlen($b) ? -1 : 1;
            });
        }
        $this->primary = $primaryArray;
    }

    /**
     * 判断查询条件里是否只包含主键
     * @param $param
     * @return bool
     */
    private function getIsPrimaryQuery($param)
    {
        if (array_has($param, 'where')) {
            $where = str_replace(array_merge($this->primary, [' ', '=:']), '', strtolower($param['where']));

            return $where === '' || $where === 'and';
        }

        return false;
    }

    /**
     * 获取主键数据数组
     * @param $data
     * @return array
     * @throws \Exception
     */
    private function getPrimaryKeyData($data)
    {
        $primaryKeyData = [];
        foreach ($this->primary as $key) {
            if (isset($data[$key])) {
                $primaryKeyData[$key] = $data[$key];
            }
        }
        if (empty($primaryKeyData)) {
            throw new \Exception ("Primary key array can't be empty", 500);
        }

        return $primaryKeyData;
    }

    /**
     * 获取主键数据的string形式
     * @param $data
     * @return string
     */
    private function getPrimaryKeyStr($data)
    {
        return $this->toString($this->getPrimaryKeyData($data));
    }

    /**
     * 转换成string
     * 要先遍历转成string，因为会存在1和'1'被json后的string不相同导致md5值不同
     * @param $data
     * @return string
     */
    private function toString($data)
    {
        $str = '';
        foreach ($data as $key => $value) {
            $str .= $key . ':' . $value . '_';
        }

        return $str;
    }

    /**
     * 获取模块
     * @return string
     */
    protected function getModule()
    {
        return $this->module;
    }

    /**
     * 是否开启缓存
     * @return boolean
     */
    protected function enableCache()
    {
        return array_get($this->config, 'enable', true);
    }

    /**
     * 获取缓存过期时间
     * @return integer
     */
    protected function getCacheExpiration()
    {
        return array_get($this->config, 'expiration', 600);
    }

    /**
     * 获取强制刷新缓存的临界值
     * @return integer
     */
    protected function getForceFlushCount()
    {
        return array_get($this->config, 'force_flush_count', 10);
    }

    /**
     * @return CacheInterface
     */
    protected function getCache()
    {
        return $this->cache;
    }

    /**
     * @return DbInterface
     */
    protected function getDb()
    {
        return $this->db;
    }

    /**
     * @return CacheVersion
     */
    protected function getCacheVersion()
    {
        return $this->cacheVersion;
    }
}