#!/usr/bin/env php
<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

if (in_array('--help', $argv, true)) {
    echo "Usage: php bin/create-admin.php USERNAME [NAME]\nPassword diminta tanpa echo, minimal 12 dan maksimal 72 byte.\n";
    exit(0);
}

$username = $argv[1] ?? '';
$name = $argv[2] ?? 'Super Admin';
if (!preg_match('/\A[A-Za-z0-9_.-]{3,80}\z/', $username) || mb_strlen($name) > 150 || trim($name) === '') {
    fwrite(STDERR, "Gunakan: php bin/create-admin.php USERNAME [NAME] (username 3–80 huruf, angka, _, ., -).\n");
    exit(1);
}
if (!stream_isatty(STDIN)) {
    fwrite(STDERR, "Jalankan melalui terminal interaktif agar password tidak masuk argumen atau history shell.\n");
    exit(1);
}

require dirname(__DIR__) . '/app/Core/Database.php';

function readPassword(string $prompt): string
{
    $state = [];
    exec('stty -g', $state, $code);
    if ($code !== 0 || empty($state[0])) {
        throw new RuntimeException('Tidak dapat mengamankan input terminal.');
    }
    exec('stty -echo', $unused, $code);
    if ($code !== 0) {
        throw new RuntimeException('Tidak dapat menyembunyikan input password.');
    }
    fwrite(STDOUT, $prompt);
    try {
        $line = fgets(STDIN);
        if ($line === false) {
            throw new RuntimeException('Input password dibatalkan.');
        }
        return rtrim($line, "\r\n");
    } finally {
        exec('stty ' . escapeshellarg($state[0]));
        fwrite(STDOUT, PHP_EOL);
    }
}

try {
    $password = readPassword('Password: ');
    $confirmation = readPassword('Ulangi password: ');
    if (strlen($password) < 12 || strlen($password) > 72 || !hash_equals($password, $confirmation)) {
        throw new RuntimeException('Password harus sama dan panjangnya 12–72 byte.');
    }
    $hash = password_hash($password, PASSWORD_DEFAULT);
    unset($password, $confirmation);
    $db = App\Core\Database::pdo();
    $stmt = $db->prepare("INSERT INTO rb_users (tenant_id, name, username, password_hash, role, status, created_at, updated_at) VALUES (NULL, ?, ?, ?, 'super_admin', 'active', NOW(), NOW())");
    $stmt->execute([$name, $username, $hash]);
    echo "Super Admin berhasil dibuat.\n";
} catch (PDOException $e) {
    fwrite(STDERR, "Gagal membuat admin. Periksa konfigurasi/schema database dan pastikan username belum digunakan.\n");
    exit(1);
} catch (Throwable $e) {
    fwrite(STDERR, $e->getMessage() . PHP_EOL);
    exit(1);
}
