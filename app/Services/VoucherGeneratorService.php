<?php

namespace App\Services;

use App\Core\Database;
use RuntimeException;

class VoucherGeneratorService
{
    public function __construct(private RadiusService $radius = new RadiusService())
    {
    }

    public function generateUsername(string $prefix, int $length = 6): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $value = strtoupper(preg_replace('/[^A-Z0-9]/', '', $prefix));
        for ($i = 0; $i < $length; $i++) {
            $value .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }
        return $value;
    }

    public function generateBulk(int $profileId, ?int $routerId, int $quantity, array $options): int
    {
        if ($quantity < 1 || $quantity > 1000) {
            throw new RuntimeException('Jumlah voucher harus 1 sampai 1000.');
        }

        $db = Database::pdo();
        $profile = $this->findProfile($profileId);
        if (!$profile) {
            throw new RuntimeException('Profile tidak ditemukan.');
        }
        $router = $routerId ? $this->findRouter($routerId) : null;
        if ($routerId && !$router) {
            throw new RuntimeException('Router target tidak ditemukan atau tidak aktif.');
        }
        if ($router && (int) $router['tenant_id'] !== (int) $profile['tenant_id']) {
            throw new RuntimeException('Router dan profile harus berada pada tenant yang sama.');
        }

        $tenant = $this->findTenant((int) $profile['tenant_id']);
        $prefix = ($router['voucher_prefix'] ?? '') ?: (($profile['username_prefix'] ?? '') ?: $tenant['code']);
        $batchCode = $this->generateBatchCode((string) $profile['name'], 'V');

        $db->beginTransaction();
        try {
            $this->radius->createOrUpdateGroupProfile($profile);
            $db->prepare('INSERT INTO rb_voucher_batches (tenant_id,router_id,profile_id,batch_code,quantity,total_price,created_by,comment,created_at) VALUES (?,?,?,?,?,?,?,?,NOW())')
                ->execute([$profile['tenant_id'], $routerId, $profileId, $batchCode, $quantity, $quantity * (float) $profile['price'], $options['created_by'], $options['comment'] ?? null]);
            $batchId = (int) $db->lastInsertId();

            for ($i = 0; $i < $quantity; $i++) {
                $username = $this->ensureUniqueUsername($prefix);
                $this->radius->createUser($username, $username, $profile['group_name']);
                $db->prepare('INSERT INTO rb_vouchers (tenant_id,router_id,batch_id,profile_id,username,password,price,status,total_uptime_limit_seconds,total_uptime_used_seconds,validity_minutes,created_at,updated_at) VALUES (?,?,?,?,?,?,?,"generated",?,0,?,NOW(),NOW())')
                    ->execute([$profile['tenant_id'], $routerId, $batchId, $profileId, $username, $username, $profile['price'], (int) $profile['total_uptime_minutes'] * 60, $profile['validity_minutes']]);
                $voucherId = (int) $db->lastInsertId();
                $db->prepare('INSERT INTO rb_transactions (tenant_id,router_id,batch_id,voucher_id,username,profile_id,price,status,created_at,updated_at) VALUES (?,?,?,?,?,?,?,"generated",NOW(),NOW())')
                    ->execute([$profile['tenant_id'], $routerId, $batchId, $voucherId, $username, $profileId, $profile['price']]);
            }

            $db->commit();
            return $batchId;
        } catch (\Throwable $e) {
            $db->rollBack();
            throw $e;
        }
    }

    public function createManual(int $profileId, ?int $routerId, string $username, string $password, array $options): int
    {
        $db = Database::pdo();
        $profile = $this->findProfile($profileId);
        if (!$profile) {
            throw new RuntimeException('Profile tidak ditemukan.');
        }
        $router = $routerId ? $this->findRouter($routerId) : null;
        if ($routerId && !$router) {
            throw new RuntimeException('Router target tidak ditemukan atau tidak aktif.');
        }
        if ($router && (int) $router['tenant_id'] !== (int) $profile['tenant_id']) {
            throw new RuntimeException('Router dan profile harus berada pada tenant yang sama.');
        }

        $username = strtoupper(trim($username));
        $password = trim($password);
        if ($username === '' || $password === '') {
            throw new RuntimeException('Username dan password wajib diisi.');
        }
        if (!preg_match('/^[A-Z0-9._-]{3,80}$/', $username)) {
            throw new RuntimeException('Username hanya boleh huruf, angka, titik, underscore, atau strip.');
        }
        $this->radius->releaseDeletedUsername($username);
        if ($this->radius->usernameExists($username)) {
            throw new RuntimeException('Username sudah digunakan.');
        }

        $batchCode = $this->generateBatchCode((string) $profile['name'], 'M');
        $db->beginTransaction();
        try {
            $this->radius->createOrUpdateGroupProfile($profile);
            $this->radius->createUser($username, $password, $profile['group_name']);
            $db->prepare('INSERT INTO rb_voucher_batches (tenant_id,router_id,profile_id,batch_code,quantity,total_price,created_by,comment,created_at) VALUES (?,?,?,?,1,?,?,?,NOW())')
                ->execute([$profile['tenant_id'], $routerId, $profileId, $batchCode, (float) $profile['price'], $options['created_by'], $options['comment'] ?? 'User manual']);
            $batchId = (int) $db->lastInsertId();
            $db->prepare('INSERT INTO rb_vouchers (tenant_id,router_id,batch_id,profile_id,username,password,price,status,total_uptime_limit_seconds,total_uptime_used_seconds,validity_minutes,created_at,updated_at) VALUES (?,?,?,?,?,?,?,"generated",?,0,?,NOW(),NOW())')
                ->execute([$profile['tenant_id'], $routerId, $batchId, $profileId, $username, $password, $profile['price'], (int) $profile['total_uptime_minutes'] * 60, $profile['validity_minutes']]);
            $voucherId = (int) $db->lastInsertId();
            $db->prepare('INSERT INTO rb_transactions (tenant_id,router_id,batch_id,voucher_id,username,profile_id,price,status,created_at,updated_at) VALUES (?,?,?,?,?,?,?,"generated",NOW(),NOW())')
                ->execute([$profile['tenant_id'], $routerId, $batchId, $voucherId, $username, $profileId, $profile['price']]);
            $db->commit();
            return $voucherId;
        } catch (\Throwable $e) {
            $db->rollBack();
            $this->radius->deleteUserAccess($username);
            throw $e;
        }
    }

    public function ensureUniqueUsername(string $prefix): string
    {
        for ($i = 0; $i < 50; $i++) {
            $username = $this->generateUsername($prefix);
            $this->radius->releaseDeletedUsername($username);
            if (!$this->radius->usernameExists($username)) {
                return $username;
            }
        }
        throw new RuntimeException('Gagal membuat username unik.');
    }

    private function generateBatchCode(string $profileName, string $prefix): string
    {
        $date = date('d.m.Y');
        $slug = preg_replace('/[^A-Za-z0-9]+/', '_', trim($profileName)) ?: 'Profile';
        $slug = trim($slug, '_') ?: 'Profile';
        $slug = substr($slug, 0, 48);
        for ($i = 0; $i < 20; $i++) {
            $code = $prefix . random_int(10000, 99999) . '-' . $date . '-' . $slug;
            if (!$this->batchCodeExists($code)) {
                return $code;
            }
        }
        throw new RuntimeException('Gagal membuat kode batch unik.');
    }

    private function batchCodeExists(string $code): bool
    {
        $stmt = Database::pdo()->prepare('SELECT 1 FROM rb_voucher_batches WHERE batch_code = ? LIMIT 1');
        $stmt->execute([$code]);
        return (bool) $stmt->fetchColumn();
    }

    private function findProfile(int $id): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM rb_profiles WHERE id = ? AND is_active = 1');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    private function findRouter(int $id): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM rb_routers WHERE id = ? AND status = "active"');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    private function findTenant(int $id): array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM rb_tenants WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
}
