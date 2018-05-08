<?php
/**
 * 缓存类
 * Created by IntelliJ IDEA.
 * Author: flashytime
 * Date: 2015/5/20 21:55
 */

namespace Flashytime\DbCache\Cache;

/**
 * Class Cache
 * @package Flashytime\DbCache\Cache
 */
class Cache
{
    /**
     * @var array
     */
    protected $config = [];

    /**
     * @var string
     */
    protected $driver;

    /**
     * @var CacheInterface
     */
    protected static $instance = null;

    /**
     * Cache constructor.
     * @param $config
     * @throws \Exception
     */
    public function __construct($config)
    {
        $this->config = $config;
        if (empty($this->config) || !isset($this->config['driver'])) {
            throw new \Exception("Cache config error");
        }
        $this->driver = $this->config['driver'];
    }

    /**
     * @param $method
     * @param $arguments
     * @return mixed
     * @throws \Exception
     */
    public function __call($method, $arguments)
    {
        if (!self::$instance) {
            $cacheDriver = __NAMESPACE__ . '\Drivers\\' . ucfirst($this->driver) . 'Driver';
            if (!class_exists($cacheDriver)) {
                throw new \Exception("Cache Driver {$cacheDriver} does not exist");
            }
            self::$instance = new $cacheDriver($this->config[$this->driver]);
        }

        return call_user_func_array([self::$instance, $method], $arguments);
    }
}
