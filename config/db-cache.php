<?php
/**
 * Created by IntelliJ IDEA.
 * Author: flashytime
 * Date: 2015/5/20 21:17
 */

return [
    'enable' => true, //是否开启缓存
    'expiration' => 600, //缓存过期时间
    'force_flush_count' => 10, //强制刷新缓存的临界值
    'db' => [
        'driver' => 'mysql',
        'mysql' => [
            'database' => 'test',
            'master' => [
                'host' => '127.0.0.1',
                'port' => 3306,
                'username' => 'root',
                'password' => '',
            ],
            'slave' => [
                'host' => '127.0.0.1',
                'port' => 3306,
                'username' => 'root',
                'password' => '',
            ],
            'charset' => 'utf8'
        ]
    ],
    'cache' => [
        'driver' => 'memcached',
        'memcached' => [
            'servers' => [
                [
                    'host' => '127.0.0.1',
                    'port' => 11211,
                ]
            ],
        ],
        'redis' => [
            'host' => '127.0.0.1',
            'port' => 6379,
        ]
    ]
];
