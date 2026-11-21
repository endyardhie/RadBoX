<?php

return [
    'app' => [
        'name' => 'RadBoX',
        'timezone' => 'Asia/Jayapura',
        'url' => 'http://localhost',
        'debug' => false,
    ],
    'database' => [
        'host' => '127.0.0.1',
        'port' => 3306,
        'name' => 'radius',
        'user' => 'radbox',
        'password' => '',
        'charset' => 'utf8mb4',
    ],
    'backup' => [
        'mysqldump_path' => '/usr/bin/mysqldump',
        'mysql_path' => '/usr/bin/mysql',
        'max_upload_mb' => 128,
    ],
];
