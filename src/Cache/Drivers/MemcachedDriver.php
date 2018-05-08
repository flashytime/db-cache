<?php
/**
 * Created by IntelliJ IDEA.
 * Author: flashytime
 * Date: 2015/5/20 20:59
 */

namespace Flashytime\DbCache\Cache\Drivers;

use Flashytime\DbCache\Cache\CacheInterface;
use Memcached;

/**
 * Memcached缓存驱动
 * Class MemcachedDriver
 * @package Flashytime\DbCache\Cache\Drivers
 */
class MemcachedDriver implements CacheInterface
{
    /**
     * @var Memcached
     */
    protected $memcached;

    /**
     * MemcachedDriver constructor.
     * @param $config
     */
    public function __construct($config)
    {
        $this->memcached = new Memcached();
        if (!$this->memcached->getServerList()) {
            foreach ($config['servers'] as $server) {
                call_user_func_array([$this->memcached, 'addServer'], $server);
            }
        }
    }

    public function set($key, $value, $expiration = null)
    {
        return $this->memcached->set($key, $value, $expiration);
    }

    public function get($key)
    {
        return $this->memcached->get($key);
    }

    public function delete($key)
    {
        return $this->memcached->delete($key);
    }
}
