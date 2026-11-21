<?php

$config = require __DIR__ . '/env.php';

date_default_timezone_set($config['app']['timezone'] ?? 'Asia/Jakarta');

return $config['app'] + [
    'root_path' => dirname(__DIR__),
    'storage_path' => dirname(__DIR__) . '/storage',
];
