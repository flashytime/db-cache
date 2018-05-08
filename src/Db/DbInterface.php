<?php
/**
 * Created by IntelliJ IDEA.
 * Author: flashytime
 * Date: 2015/5/21 14:51
 */

namespace Flashytime\DbCache\Db;

/**
 * Interface DbInterface
 * @package Flashytime\DbCache\Db
 */
interface DbInterface
{
    /**
     * 查询一条记录
     * @param array $param
     * @param array $data
     * @return array|bool
     */
    public function select(array $param, array $data);

    /**
     * 查询列表
     * @param array $param
     * @param array $data
     * @return array|bool
     */
    public function all(array $param, array $data);

    /**
     * 查询数量
     * @param array $param
     * @param array $data
     * @return int
     */
    public function count(array $param, array $data);

    /**
     * 插入数据
     * @param array $data
     * @return bool|int
     */
    public function insert(array $data);

    /**
     * 修改数据
     * @param array $param
     * @param array $data
     * @param $setData
     * @return bool|int
     */
    public function update(array $param, array $data, $setData);

    /**
     * 删除数据
     * @param array $param
     * @param array $data
     * @return bool|int
     */
    public function delete(array $param, array $data);

    /**
     * 获取执行的sgl语句
     * @return string|null
     */
    public function getSql();

    /**
     * 事务开始
     * @return bool
     */
    public function beginTransaction();

    /**
     * 事务提交
     * @return bool
     */
    public function commit();

    /**
     * 事务回滚
     * @return bool
     */
    public function rollBack();
}
