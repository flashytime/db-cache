<?php
/**
 * Created by IntelliJ IDEA.
 * Author: flashytime
 * Date: 2015/5/20 21:00
 */

namespace Flashytime\DbCache\Cache\Drivers;

use Flashytime\DbCache\Cache\CacheInterface;
use Redis;

/**
 * Redis缓存驱动
 * Class RedisDriver
 * @package Flashytime\DbCache\Cache\Drivers
 */
class RedisDriver implements CacheInterface
{
    /**
     * @var Redis
     */
    protected $redis;

    /**
     * RedisDriver constructor.
     * @param $config
     */
    public function __construct($config)
    {
        $this->redis = new Redis();
        $this->redis->connect($config['redis']['host'], $config['redis']['port']);
    }

    public function set($key, $value, $expiration = null)
    {
        return $this->redis->set($key, $value, $expiration);
    }

    public function get($key)
    {
        return $this->redis->get($key);
    }

    public function delete($key)
    {
        return $this->redis->del($key);
    }
}