<?php

namespace App\Services;

use RuntimeException;

class DatabaseRestoreService
{
    private array $dbConfig;
    private array $envConfig;
    private string $tmpDir;

    public function __construct(private DatabaseBackupService $backup = new DatabaseBackupService())
    {
        $config = require dirname(__DIR__, 2) . '/config/env.php';
        $this->dbConfig = $config['database'];
        $this->envConfig = $config['backup'] ?? [];
        $this->tmpDir = dirname(__DIR__, 2) . '/storage/backups/tmp';
    }

    public function validateUpload(array $file): string
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Upload file gagal.');
        }
        $name = basename($file['name']);
        if (!preg_match('/\.sql(\.gz)?$/', $name)) {
            throw new RuntimeException('File harus berekstensi .sql atau .sql.gz.');
        }
        $max = (int) ($this->envConfig['max_upload_mb'] ?? 128) * 1024 * 1024;
        if (($file['size'] ?? 0) > $max) {
            throw new RuntimeException('Ukuran file melebihi batas.');
        }
        if (!is_dir($this->tmpDir) && !mkdir($this->tmpDir, 0750, true)) {
            throw new RuntimeException('Folder upload sementara tidak bisa dibuat.');
        }
        $target = $this->tmpDir . '/' . date('Ymd-His') . '-' . preg_replace('/[^A-Za-z0-9._-]/', '-', $name);
        if (!move_uploaded_file($file['tmp_name'], $target)) {
            throw new RuntimeException('Gagal menyimpan upload sementara.');
        }
        return $target;
    }

    public function autoBackupBeforeRestore(): string
    {
        return $this->backup->createLightBackup(true);
    }

    public function restoreFromSql(string $path): void
    {
        $this->runMysql('< ' . escapeshellarg($path));
    }

    public function restoreFromGzip(string $path): void
    {
        $this->runMysql('gzip -dc ' . escapeshellarg($path) . ' |');
    }

    public function importSql(string $path): void
    {
        try {
            str_ends_with($path, '.gz') ? $this->restoreFromGzip($path) : $this->restoreFromSql($path);
        } finally {
            if (str_starts_with($path, $this->tmpDir . '/')) {
                @unlink($path);
            }
        }
    }

    private function runMysql(string $pipe): void
    {
        $mysql = $this->envConfig['mysql_path'] ?? '/usr/bin/mysql';
        if (!is_executable($mysql)) {
            throw new RuntimeException('mysql CLI tidak ditemukan atau tidak executable.');
        }
        $defaultsFile = $this->createMysqlDefaultsFile();
        $mysqlCommand = implode(' ', [
            escapeshellarg($mysql),
            '--defaults-extra-file=' . escapeshellarg($defaultsFile),
            escapeshellarg($this->dbConfig['name']),
        ]);
        $command = str_ends_with($pipe, '|') ? $pipe . ' ' . $mysqlCommand : $mysqlCommand . ' ' . $pipe;
        try {
            exec($command, $output, $code);
            if ($code !== 0) {
                throw new RuntimeException('Restore/import gagal. Untuk database besar gunakan CLI.');
            }
        } finally {
            @unlink($defaultsFile);
        }
    }

    private function createMysqlDefaultsFile(): string
    {
        if (!is_dir($this->tmpDir) && !mkdir($this->tmpDir, 0750, true)) {
            throw new RuntimeException('Folder upload sementara tidak bisa dibuat.');
        }
        $path = tempnam($this->tmpDir, 'mysql-defaults-');
        if ($path === false) {
            throw new RuntimeException('Gagal membuat file konfigurasi database sementara.');
        }
        $content = "[client]\n"
            . 'host=' . $this->dbConfig['host'] . "\n"
            . 'port=' . (string) ($this->dbConfig['port'] ?? 3306) . "\n"
            . 'user=' . $this->dbConfig['user'] . "\n"
            . 'password=' . $this->dbConfig['password'] . "\n";
        if (file_put_contents($path, $content) === false) {
            @unlink($path);
            throw new RuntimeException('Gagal menulis file konfigurasi database sementara.');
        }
        chmod($path, 0600);
        return $path;
    }
}
