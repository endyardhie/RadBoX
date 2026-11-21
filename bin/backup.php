#!/usr/bin/env php
<?php

require dirname(__DIR__) . '/app/Helpers/functions.php';
spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) return;
    $path = dirname(__DIR__) . '/app/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
    if (is_file($path)) require $path;
});

$type = $argv[1] ?? 'light';
$service = new App\Services\DatabaseBackupService();
$file = match ($type) {
    'full' => $service->createFullBackup(true, true),
    'app' => $service->createAppBackup(true),
    default => $service->createLightBackup(true),
};
echo "Backup created: {$file}" . PHP_EOL;
