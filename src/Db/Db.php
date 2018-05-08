<?php
/**
 * Created by IntelliJ IDEA.
 * Author: flashytime
 * Date: 2015/5/22 17:26
 */

namespace Flashytime\DbCache\Db;

/**
 * Class Db
 * @package Flashytime\DbCache\Db
 */
class Db
{
    /**
     * @var array
     */
    protected $config = [];

    /**
     * @var array
     */
    protected $table;

    /**
     * @var DbInterface
     */
    protected static $instance = null;

    /**
     * Db constructor.
     * @param array $config
     * @param array $table
     * @throws \Exception
     */
    public function __construct(array $config, array $table)
    {
        $this->table = $table;
        $this->config = $config;
        if (empty($this->config) || !isset($this->config['driver'])) {
            throw new \Exception("Db config error");
        }
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
            $dbDriver = __NAMESPACE__ . '\Drivers\\' . ucfirst($this->config['driver']) . 'Driver';
            if (!class_exists($dbDriver)) {
                throw new \Exception("Db Driver {$dbDriver} does not exist");
            }
            self::$instance = new $dbDriver($this->config, $this->table);
        }

        return call_user_func_array([self::$instance, $method], $arguments);
    }
}
