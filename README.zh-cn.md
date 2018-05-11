# db-cache
一个用于缓存数据库查询的PHP库，支持MySQL/Mongo等数据库，同时支持Memcached/Redis等缓存

### 特点
- 支持常用数据库，如MySQL、Mongo
- 支持常用缓存，如Memcached、Redis
- 支持数据库主从和读写分离
- 支持MySQL数据库分表
- 通过version策略来管理缓存，控制缓存的建立和失效

### 安装
- 在项目根目录运行composer require命令

```bash
composer require flashytime/db-cache
```
- 把`config/db-cache.php`拷贝到项目的配置目录

### 使用

```php
class TestModel
{
    public $dbCache;

    public function __construct()
    {
        // you can get the config in your own way
        $config = $this->getConfig();
        $this->dbCache = new \Flashytime\DbCache\DbCache($config, 'Test', ['name' => 'test', 'primary' => 'id']);
    }

    public function create($data)
    {
        return $this->dbCache->insert($data);
    }

    public function getById($id)
    {
        return $this->dbCache->select(['where' => 'id = :id'], ['id' => $id]);
    }

    public function findAll($offset, $limit)
    {
        return $this->dbCache->all(['limit' => ':offset, :limit', 'order' => 'id DESC'], ['offset' => $offset, 'limit' => $limit]);
    }

    public function updateById($id, $data)
    {
        return $this->dbCache->update(['where' => 'id = :id'], ['id' => $id], $data);
    }

    public function remove($id)
    {
        return $this->dbCache->delete(['where' => 'id = :id'], ['id' => $id]);
    }

    public function getConfig()
    {
        //path to your config directory
        return require __DIR__ . '/../config/db-cache.php';
    }
}
```

### 证书
MIT