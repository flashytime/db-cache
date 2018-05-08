<?php
/**
 * Created by IntelliJ IDEA.
 * Author: flashytime
 * Date: 2015/5/20 20:34
 */

namespace Flashytime\DbCache\Cache;

/**
 * Interface CacheInterface
 * @package Flashytime\DbCache\Cache
 */
interface CacheInterface
{
    /**
     * 设置缓存
     * @param $key
     * @param $value
     * @param null $expiration
     * @return mixed
     */
    public function set($key, $value, $expiration = null);

    /**
     * 获取缓存
     * @param $key
     * @return mixed
     */
    public function get($key);

    /**
     * 删除缓存
     * @param $key
     * @return mixed
     */
    public function delete($key);
}
