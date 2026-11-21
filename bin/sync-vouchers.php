#!/usr/bin/env php
<?php

require dirname(__DIR__) . '/app/Helpers/functions.php';
require dirname(__DIR__) . '/app/Helpers/format.php';

spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }
    $path = dirname(__DIR__) . '/app/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
    if (is_file($path)) {
        require $path;
    }
});

$result = (new App\Services\VoucherSyncService())->syncAll();
echo date('Y-m-d H:i:s') . ' sync ' . json_encode($result) . PHP_EOL;
