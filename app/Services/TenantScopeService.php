<?php

namespace App\Services;

use App\Core\Database;

class TenantScopeService
{
    public function currentTenantId(): ?int
    {
        $user = current_user();
        return $user && $user['tenant_id'] ? (int) $user['tenant_id'] : null;
    }

    public function tenantWhere(string $alias = ''): array
    {
        if (is_super_admin()) {
            return ['', []];
        }
        $column = ($alias ? $alias . '.' : '') . 'tenant_id';
        return [" AND {$column} = ? ", [$this->currentTenantId()]];
    }

    public function currentRouterIds(): array
    {
        if (!is_technician()) {
            return [];
        }
        $stmt = Database::pdo()->prepare('SELECT router_id FROM rb_user_routers WHERE user_id = ? ORDER BY router_id');
        $stmt->execute([(int) current_user()['id']]);
        return array_map('intval', array_column($stmt->fetchAll(), 'router_id'));
    }

    public function routerAccessWhere(string $routerColumn, bool $includeNullRouter = false): array
    {
        if (!is_technician()) {
            return ['', []];
        }

        $routerIds = $this->currentRouterIds();
        if (!$routerIds) {
            return [' AND 1=0 ', []];
        }

        $placeholders = implode(',', array_fill(0, count($routerIds), '?'));
        $where = $includeNullRouter
            ? " AND ({$routerColumn} IS NULL OR {$routerColumn} IN ({$placeholders})) "
            : " AND {$routerColumn} IN ({$placeholders}) ";

        return [$where, $routerIds];
    }

    public function canAccessTenant(?int $tenantId): bool
    {
        return is_super_admin() || (int) $tenantId === $this->currentTenantId();
    }

    public function canAccessRouter(int $routerId): bool
    {
        if (is_super_admin()) {
            return true;
        }
        if (is_technician()) {
            return in_array($routerId, $this->currentRouterIds(), true);
        }
        $stmt = Database::pdo()->prepare('SELECT tenant_id FROM rb_routers WHERE id = ?');
        $stmt->execute([$routerId]);
        $tenantId = $stmt->fetchColumn();
        return $tenantId && (int) $tenantId === $this->currentTenantId();
    }

    public function canAccessVoucher(int $voucherId): bool
    {
        if (is_super_admin()) {
            return true;
        }
        $stmt = Database::pdo()->prepare('SELECT tenant_id, router_id FROM rb_vouchers WHERE id = ?');
        $stmt->execute([$voucherId]);
        $voucher = $stmt->fetch();
        if (!$voucher) {
            return false;
        }
        if ($this->currentTenantId() && (int) $voucher['tenant_id'] !== $this->currentTenantId()) {
            return false;
        }
        if (is_technician()) {
            $routerIds = $this->currentRouterIds();
            return $voucher['router_id'] !== null && in_array((int) $voucher['router_id'], $routerIds, true);
        }
        return true;
    }

    public function canAccessBatch(int $batchId): bool
    {
        if (is_super_admin()) {
            return true;
        }
        $stmt = Database::pdo()->prepare('SELECT tenant_id, router_id FROM rb_voucher_batches WHERE id = ?');
        $stmt->execute([$batchId]);
        $batch = $stmt->fetch();
        if (!$batch) {
            return false;
        }
        if ($this->currentTenantId() && (int) $batch['tenant_id'] !== $this->currentTenantId()) {
            return false;
        }
        if (is_technician()) {
            $routerIds = $this->currentRouterIds();
            return $batch['router_id'] !== null && in_array((int) $batch['router_id'], $routerIds, true);
        }
        return true;
    }
}
