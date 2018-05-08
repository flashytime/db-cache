<?php
/**
 * Created by IntelliJ IDEA.
 * Author: flashytime
 * Date: 2015/5/25 16:22
 */

namespace Flashytime\DbCache\Tests;

use Flashytime\DbCache\DbCache;

class DbCacheTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var DbCache
     */
    public $dbCache;

    public function setUp()
    {
        $config = $this->getConfig();
        $this->dbCache = new DbCache($config, 'test', ['name' => 'test', 'primary' => 'id']);
    }

    public function testSelect()
    {
        $result = $this->dbCache->select(['where' => 'id = :id'], ['id' => 20000015]);
        $this->assertArrayHasKey('id', $result);
        $result = $this->dbCache->select(['where' => 'id = :id AND a = :a'], ['id' => 5, 'a' => 11]);
        $this->assertArrayHasKey('id', $result);
    }

    public function testAll()
    {
        $result = $this->dbCache->all(
            ['where' => 'id > :id', 'order' => 'id DESC', 'limit' => ':limit'],
            ['id' => 1, 'limit' => 10]
        );
        $this->assertNotEmpty($result);
    }

    public function testCount()
    {
        $result = $this->dbCache->count(['where' => 'id > :id'], ['id' => 20000001]);
        $this->assertInternalType('integer', $result);
    }

    public function testInsert()
    {
        $dateTime = date('Y-m-d H:i:s');
        $result = $this->dbCache->insert(['a' => '1', 'b' => 'test2', 'c' => $dateTime]);
        $this->assertInternalType('numeric', $result);
    }

    public function testUpdate()
    {
        $result = $this->dbCache->update(['where' => 'id < :id', 'limit' => 20, 'order' => 'id DESC'], ['id' => 20000015], ['a' => 11]);
        $this->assertInternalType('integer', $result);
    }

    public function testDelete()
    {
        $result = $this->dbCache->delete(['where' => 'id = :id'], ['id' => 20000017]);
        $this->assertInternalType('integer', $result);
    }

    public function getConfig()
    {
        return require __DIR__ . '/../config/db-cache.php';
    }
}