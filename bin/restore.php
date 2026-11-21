#!/usr/bin/env php
<?php

require dirname(__DIR__) . '/app/Helpers/functions.php';
spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) return;
    $path = dirname(__DIR__) . '/app/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
    if (is_file($path)) require $path;
});

$path = $argv[1] ?? null;
if (!$path || !is_file($path) || !preg_match('/\.sql(\.gz)?$/', $path)) {
    fwrite(STDERR, "Usage: php bin/restore.php /path/file.sql[.gz]\n");
    exit(1);
}

$service = new App\Services\DatabaseRestoreService();
$service->autoBackupBeforeRestore();
$service->importSql($path);
echo "Restore complete: {$path}" . PHP_EOL;
