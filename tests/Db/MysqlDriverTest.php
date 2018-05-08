<?php
/**
 * Created by IntelliJ IDEA.
 * Author: flashytime
 * Date: 2015/5/22 21:49
 */

namespace Flashytime\DbCache\Tests\Db;

use Flashytime\DbCache\Db\Drivers\MysqlDriver;

class MysqlDriverTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var MysqlDriver
     */
    private $mysqlDriver;

    public function setUp()
    {
        $config = $this->getConfig();
        $this->mysqlDriver = new MysqlDriver($config['db'], ['name' => 'test']);
    }

    public function testSelect()
    {
        $result = $this->mysqlDriver->select(['where' => 'id = :id'], ['id' => 1]);
        $this->assertArrayHasKey('id', $result);
        $expected = 'SELECT * FROM `test` WHERE id = 1 LIMIT 1';
        $this->assertEquals($expected, $this->mysqlDriver->getSql());
    }

    public function testAll()
    {
        $result = $this->mysqlDriver->all(
            ['where' => 'id > :id', 'order' => 'id DESC', 'limit' => ':limit'],
            ['id' => 1, 'limit' => 10]
        );
        $this->assertNotEmpty($result);
        $expected = 'SELECT * FROM `test` WHERE id > 1 ORDER BY id DESC LIMIT 10';
        $this->assertEquals($expected, $this->mysqlDriver->getSql());
    }

    public function testCount()
    {
        $result = $this->mysqlDriver->count(['where' => 'id < :id'], ['id' => 10]);
        $this->assertInternalType('integer', $result);
        $expected = 'SELECT count(*) FROM `test` WHERE id < 10';
        $this->assertEquals($expected, $this->mysqlDriver->getSql());
    }

    public function testInsert()
    {
        $dateTime = date('Y-m-d H:i:s');
        $result = $this->mysqlDriver->insert(['a' => '1', 'b' => 'test1', 'c' => $dateTime]);
        $this->assertInternalType('numeric', $result);
        $expected = "INSERT INTO `test` (`a`, `b`, `c`) VALUES (1, test1, $dateTime)";
        $this->assertEquals($expected, $this->mysqlDriver->getSql());
    }

    public function testUpdate()
    {
        $result = $this->mysqlDriver->update(['where' => 'id = :id'], ['id' => 1], ['a' => 2]);
        $this->assertInternalType('integer', $result);
        $expected = "UPDATE `test` SET `a` = 2 WHERE id = 1";
        $this->assertEquals($expected, $this->mysqlDriver->getSql());
    }

    public function testDelete()
    {
        $result = $this->mysqlDriver->delete(['where' => 'id = :id'], ['id' => 2]);
        $this->assertInternalType('integer', $result);
        $expected = "DELETE FROM `test` WHERE id = 2";
        $this->assertEquals($expected, $this->mysqlDriver->getSql());
    }

    public function getConfig()
    {
        return require __DIR__ . '/../../config/db-cache.php';
    }


}