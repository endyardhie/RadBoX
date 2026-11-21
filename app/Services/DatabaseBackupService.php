<?php

namespace App\Services;

use App\Core\Database;
use RuntimeException;

class DatabaseBackupService
{
    private array $dbConfig;
    private array $envConfig;
    private string $backupDir;

    public function __construct()
    {
        $config = require dirname(__DIR__, 2) . '/config/env.php';
        $this->dbConfig = $config['database'];
        $this->envConfig = $config['backup'] ?? [];
        $this->backupDir = dirname(__DIR__, 2) . '/storage/backups';
    }

    public function createFullBackup(bool $includeRadacct = true, bool $compress = true): string
    {
        $tables = ['radcheck','radreply','radusergroup','radgroupcheck','radgroupreply','nas','rb_tenants','rb_users','rb_routers','rb_user_routers','rb_profiles','rb_voucher_batches','rb_vouchers','rb_transactions','rb_settings','rb_audit_logs','rb_backup_logs'];
        if ($includeRadacct) {
            $tables[] = 'radacct';
        }
        return $this->dump('full', $tables, $compress);
    }

    public function createLightBackup(bool $compress = true): string
    {
        return $this->dump('light', ['radcheck','radreply','radusergroup','radgroupcheck','radgroupreply','nas','rb_tenants','rb_users','rb_routers','rb_user_routers','rb_profiles','rb_voucher_batches','rb_vouchers','rb_transactions','rb_settings','rb_audit_logs','rb_backup_logs'], $compress);
    }

    public function createAppBackup(bool $compress = true): string
    {
        return $this->dump('app', ['nas','rb_tenants','rb_users','rb_routers','rb_user_routers','rb_profiles','rb_voucher_batches','rb_vouchers','rb_transactions','rb_settings','rb_audit_logs','rb_backup_logs'], $compress);
    }

    public function listBackups(): array
    {
        $files = glob($this->backupDir . '/*.sql*') ?: [];
        usort($files, fn ($a, $b) => filemtime($b) <=> filemtime($a));
        return array_map(fn ($file) => [
            'filename' => basename($file),
            'size' => filesize($file),
            'created_at' => date('Y-m-d H:i:s', filemtime($file)),
        ], $files);
    }

    public function diagnostics(): array
    {
        $mysqldump = $this->envConfig['mysqldump_path'] ?? '/usr/bin/mysqldump';
        $mysql = $this->envConfig['mysql_path'] ?? '/usr/bin/mysql';

        return [
            ['label' => 'Folder backup', 'ok' => is_dir($this->backupDir) && is_writable($this->backupDir), 'detail' => $this->backupDir],
            ['label' => 'PHP exec', 'ok' => function_exists('exec'), 'detail' => function_exists('exec') ? 'Aktif' : 'Nonaktif, fallback PDO akan dipakai'],
            ['label' => 'mysqldump', 'ok' => is_executable($mysqldump), 'detail' => $mysqldump],
            ['label' => 'mysql CLI', 'ok' => is_executable($mysql), 'detail' => $mysql],
            ['label' => 'Gzip PHP', 'ok' => function_exists('gzopen'), 'detail' => function_exists('gzopen') ? 'Aktif' : 'Tidak tersedia'],
        ];
    }

    public function pathFor(string $filename): string
    {
        $safe = basename($filename);
        $path = $this->backupDir . '/' . $safe;
        if (!is_file($path) || !preg_match('/\.sql(\.gz)?$/', $safe)) {
            throw new RuntimeException('File backup tidak valid.');
        }
        return $path;
    }

    public function deleteBackup(string $filename): void
    {
        $path = $this->pathFor($filename);
        unlink($path);
    }

    private function dump(string $type, array $tables, bool $compress): string
    {
        $this->ensureBackupDir();
        $tables = $this->existingTables($tables);
        if (!$tables) {
            throw new RuntimeException('Tidak ada tabel yang bisa dibackup.');
        }

        $filename = sprintf('radbox-%s-%s.sql%s', $type, date('Ymd-His'), $compress ? '.gz' : '');
        $path = $this->backupDir . '/' . $filename;

        try {
            $this->dumpWithMysqlDump($path, $tables, $compress);
        } catch (\Throwable $e) {
            @unlink($path);
            $this->dumpWithPdo($path, $tables, $compress);
        }

        $user = current_user();
        if ($user) {
            try {
                Database::pdo()->prepare('INSERT INTO rb_backup_logs (user_id, filename, backup_type, file_size, status, created_at) VALUES (?,?,?,?, "success", NOW())')
                    ->execute([$user['id'], $filename, $type, filesize($path)]);
            } catch (\Throwable) {
                // Backup must still be downloadable even if the optional log table is unavailable.
            }
        }

        return $filename;
    }

    private function dumpWithMysqlDump(string $path, array $tables, bool $compress): void
    {
        if (!function_exists('exec')) {
            throw new RuntimeException('exec tidak tersedia.');
        }
        $mysqldump = $this->envConfig['mysqldump_path'] ?? '/usr/bin/mysqldump';
        if (!is_executable($mysqldump)) {
            throw new RuntimeException('mysqldump tidak ditemukan atau tidak executable.');
        }

        $defaultsFile = $this->createMysqlDefaultsFile();
        $args = [
            escapeshellarg($mysqldump),
            '--defaults-extra-file=' . escapeshellarg($defaultsFile),
            '--single-transaction',
            '--quick',
            escapeshellarg($this->dbConfig['name']),
        ];
        foreach ($tables as $table) {
            $args[] = escapeshellarg($table);
        }

        $command = implode(' ', $args);
        $command .= $compress ? ' | gzip > ' . escapeshellarg($path) : ' > ' . escapeshellarg($path);

        try {
            exec($command, $output, $code);
            if ($code !== 0 || !is_file($path) || filesize($path) === 0) {
                throw new RuntimeException('mysqldump gagal.');
            }
        } finally {
            @unlink($defaultsFile);
        }
    }

    private function dumpWithPdo(string $path, array $tables, bool $compress): void
    {
        $writer = $this->openDumpWriter($path, $compress);
        try {
            $this->writeDump($writer, "-- RadBoX database backup\n", $compress);
            $this->writeDump($writer, '-- Created at ' . date('Y-m-d H:i:s') . "\n\n", $compress);
            $this->writeDump($writer, "SET FOREIGN_KEY_CHECKS=0;\n\n", $compress);

            $db = Database::pdo();
            foreach ($tables as $table) {
                $quotedTable = $this->quoteIdentifier($table);
                $stmt = $db->query('SHOW CREATE TABLE ' . $quotedTable);
                $create = $stmt ? $stmt->fetch() : null;
                $createSql = $create['Create Table'] ?? $create[1] ?? null;
                if (!$createSql) {
                    continue;
                }

                $this->writeDump($writer, "DROP TABLE IF EXISTS {$quotedTable};\n", $compress);
                $this->writeDump($writer, $createSql . ";\n\n", $compress);

                $rows = $db->query('SELECT * FROM ' . $quotedTable);
                if (!$rows) {
                    continue;
                }
                $columns = null;
                while ($row = $rows->fetch(\PDO::FETCH_ASSOC)) {
                    if ($columns === null) {
                        $columns = array_keys($row);
                    }
                    $columnSql = implode(', ', array_map(fn ($column) => $this->quoteIdentifier($column), $columns));
                    $valueSql = implode(', ', array_map(fn ($value) => $this->quoteValue($value), array_values($row)));
                    $this->writeDump($writer, "INSERT INTO {$quotedTable} ({$columnSql}) VALUES ({$valueSql});\n", $compress);
                }
                $this->writeDump($writer, "\n", $compress);
            }

            $this->writeDump($writer, "SET FOREIGN_KEY_CHECKS=1;\n", $compress);
        } finally {
            $this->closeDumpWriter($writer, $compress);
        }

        if (!is_file($path) || filesize($path) === 0) {
            throw new RuntimeException('Backup gagal dibuat.');
        }
    }

    private function existingTables(array $tables): array
    {
        $stmt = Database::pdo()->query('SHOW TABLES');
        $existing = $stmt ? array_map('strval', $stmt->fetchAll(\PDO::FETCH_COLUMN)) : [];
        $existing = array_flip($existing);
        return array_values(array_filter($tables, fn ($table) => isset($existing[$table])));
    }

    private function openDumpWriter(string $path, bool $compress)
    {
        if ($compress) {
            if (!function_exists('gzopen')) {
                throw new RuntimeException('Ekstensi gzip PHP tidak tersedia.');
            }
            $handle = gzopen($path, 'wb9');
        } else {
            $handle = fopen($path, 'wb');
        }
        if (!$handle) {
            throw new RuntimeException('File backup tidak bisa ditulis.');
        }
        return $handle;
    }

    private function writeDump($writer, string $content, bool $compress): void
    {
        $compress ? gzwrite($writer, $content) : fwrite($writer, $content);
    }

    private function closeDumpWriter($writer, bool $compress): void
    {
        $compress ? gzclose($writer) : fclose($writer);
    }

    private function quoteIdentifier(string $identifier): string
    {
        if (!preg_match('/^[A-Za-z0-9_]+$/', $identifier)) {
            throw new RuntimeException('Identifier database tidak valid.');
        }
        return '`' . $identifier . '`';
    }

    private function quoteValue(mixed $value): string
    {
        if ($value === null) {
            return 'NULL';
        }
        return Database::pdo()->quote((string) $value);
    }

    private function ensureBackupDir(): void
    {
        if (!is_dir($this->backupDir) && !mkdir($this->backupDir, 0750, true)) {
            throw new RuntimeException('Folder backup tidak bisa dibuat.');
        }
    }

    private function createMysqlDefaultsFile(): string
    {
        $this->ensureBackupDir();
        $path = tempnam($this->backupDir, 'mysql-defaults-');
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
