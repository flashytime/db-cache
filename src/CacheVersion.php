<?php
/**
 * Created by IntelliJ IDEA.
 * Author: flashytime
 * Date: 2015/5/23 18:29
 */

namespace Flashytime\DbCache;

use Flashytime\DbCache\Cache\Cache;
use Flashytime\DbCache\Cache\CacheInterface;

/**
 * Class CacheVersion
 * @package Flashytime\DbCache
 */
class CacheVersion
{
    /**
     * 全局缓存版本
     */
    const CACHE_GLOBAL_VERSION = '1.0.0';

    /**
     * version初始值
     */
    const CACHE_VERSION_INITIAL = 1;

    const CACHE_KEY_TYPE_SINGLE = 'single';
    const CACHE_KEY_TYPE_LIST = 'list';
    const CACHE_KEY_TYPE_LIST_COUNT = 'count';

    /**
     * @var CacheInterface
     */
    private $cache;

    /**
     * CacheVersion constructor.
     * @param Cache $cache
     */
    public function __construct(Cache $cache)
    {
        $this->cache = $cache;
    }

    /**
     * @param $versionKey
     * @return int|mixed
     */
    public function getVersion($versionKey)
    {
        $value = $this->getCache()->get($versionKey);
        if (!$value) {
            $value = self::CACHE_VERSION_INITIAL;
            $this->getCache()->set($versionKey, $value);
        }

        return $value;
    }

    /**
     * @param $versionKey
     * @return mixed
     */
    public function setVersion($versionKey)
    {
        return $this->getCache()->set($versionKey, $this->getMicroTime());
    }

    /**
     * 更新单条缓存version
     * @param $module
     * @return mixed
     */
    public function updateSingleVersion($module)
    {
        $singleVersionKey = $this->getSingleVersionKey($module);
        return $this->setVersion($singleVersionKey);
    }

    /**
     * 更新列表页缓存version
     * @param $module
     * @return mixed
     */
    public function updateListVersion($module)
    {
        $listVersionKey = $this->getListVersionKey($module);
        return $this->setVersion($listVersionKey);
    }

    /**
     * @param $module
     * @param $args
     * @return string
     */
    public function getSingleKey($module, $args)
    {
        return $this->getKey($module, $args, self::CACHE_KEY_TYPE_SINGLE);
    }

    /**
     * @param $module
     * @param $args
     * @return string
     */
    public function getListKey($module, $args)
    {
        return $this->getKey($module, $args, self::CACHE_KEY_TYPE_LIST);
    }

    /**
     * @param $module
     * @param $args
     * @return string
     */
    public function getCountKey($module, $args)
    {
        return $this->getListKey($module, $args) . '_' . self::CACHE_KEY_TYPE_LIST_COUNT;
    }

    /**
     * @param $module
     * @return string
     */
    private function getListVersionKey($module)
    {
        return $this->getVersionKey($module, self::CACHE_KEY_TYPE_LIST);
    }

    /**
     * @param $module
     * @return string
     */
    private function getSingleVersionKey($module)
    {
        return $this->getVersionKey($module, self::CACHE_KEY_TYPE_SINGLE);
    }

    /**
     * @param $module
     * @param $args
     * @param $type
     * @return string
     */
    private function getKey($module, $args, $type)
    {
        $versionKey = $this->getVersionKey($module, $type);
        $version = $this->getVersion($versionKey);

        return $versionKey . '_' . $version . '_' . md5(json_encode($args));
    }

    /**
     * @param $module
     * @param $type
     * @return string
     */
    private function getVersionKey($module, $type)
    {
        return self::CACHE_GLOBAL_VERSION . '_' . $module . '_' . $type . '_version';
    }

    /**
     * 返回微秒
     * @return float
     */
    private function getMicroTime()
    {
        list($usec, $sec) = explode(" ", microtime());

        return ((float)$usec + (float)$sec);
    }

    /**
     * @return CacheInterface
     */
    private function getCache()
    {
        return $this->cache;
    }
}
