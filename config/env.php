<?php

$path = dirname(__DIR__) . '/.env.php';
if (!is_file($path)) {
    throw new RuntimeException('Konfigurasi lokal belum tersedia. Salin .env.example.php ke .env.php dan isi credential Anda.');
}

$config = require $path;
if (!is_array($config) || empty($config['database']['password'])) {
    throw new RuntimeException('Isi password database pada .env.php sebelum menjalankan RadBoX.');
}

return $config;
