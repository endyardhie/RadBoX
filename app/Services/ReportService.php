<?php

namespace App\Services;

use App\Core\Database;

class ReportService
{
    public function getSummary(array $filters): array
    {
        [$where, $params] = $this->filters($filters, 't', 'v');
        $stmt = Database::pdo()->prepare("SELECT COUNT(*) voucher_count, COALESCE(SUM(t.price),0) revenue FROM rb_transactions t JOIN rb_vouchers v ON v.id = t.voucher_id WHERE {$where}");
        $stmt->execute($params);
        return $stmt->fetch() ?: ['voucher_count' => 0, 'revenue' => 0];
    }

    public function getTransactions(array $filters, int $limit = 1000): array
    {
        [$where, $params] = $this->filters($filters, 't', 'v');
        $limit = max(1, min(5000, $limit));
        $sql = "SELECT t.*, v.first_login_at sold_at, tn.name tenant_name, r.name router_name, p.name profile_name, b.batch_code
                FROM rb_transactions t
                JOIN rb_vouchers v ON v.id = t.voucher_id
                LEFT JOIN rb_tenants tn ON tn.id = t.tenant_id
                LEFT JOIN rb_routers r ON r.id = t.router_id
                LEFT JOIN rb_profiles p ON p.id = t.profile_id
                LEFT JOIN rb_voucher_batches b ON b.id = t.batch_id
                WHERE {$where}
                ORDER BY v.first_login_at DESC LIMIT {$limit}";
        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function deleteMonth(int $year, int $month, ?int $tenantId): int
    {
        $from = sprintf('%04d-%02d-01 00:00:00', $year, $month);
        $to = date('Y-m-t 23:59:59', strtotime($from));
        $where = 'v.first_login_at >= ? AND v.first_login_at <= ? AND t.status IN ("used","expired")';
        $params = [$from, $to];
        if ($tenantId) {
            $where .= ' AND t.tenant_id = ?';
            $params[] = $tenantId;
        }
        $stmt = Database::pdo()->prepare("UPDATE rb_transactions t JOIN rb_vouchers v ON v.id = t.voucher_id SET t.status = 'deleted', t.updated_at = NOW() WHERE {$where}");
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    private function filters(array $filters, string $alias = '', string $voucherAlias = ''): array
    {
        $p = $alias ? $alias . '.' : '';
        $v = $voucherAlias ? $voucherAlias . '.' : '';
        $dateColumn = $v ? "{$v}first_login_at" : "{$p}updated_at";
        $where = ["{$p}status IN ('used','expired')"];
        $params = [];

        if ($v) {
            $where[] = "{$v}first_login_at IS NOT NULL";
            $where[] = "{$v}first_login_at >= {$v}created_at";
        }

        if (!empty($filters['date_from'])) {
            $where[] = "{$dateColumn} >= ?";
            $params[] = $filters['date_from'] . ' 00:00:00';
        }
        if (!empty($filters['date_to'])) {
            $where[] = "{$dateColumn} <= ?";
            $params[] = $filters['date_to'] . ' 23:59:59';
        }
        foreach (['tenant_id', 'router_id'] as $field) {
            if (!empty($filters[$field])) {
                $where[] = "{$p}{$field} = ?";
                $params[] = (int) $filters[$field];
            }
        }
        if (empty($filters['router_id']) && !empty($filters['router_ids'])) {
            $routerIds = array_values(array_unique(array_map('intval', (array) $filters['router_ids'])));
            $placeholders = implode(',', array_fill(0, count($routerIds), '?'));
            $condition = "{$p}router_id IN ({$placeholders})";
            if (!empty($filters['include_null_router'])) {
                $condition = "({$p}router_id IS NULL OR {$condition})";
            }
            $where[] = $condition;
            $params = array_merge($params, $routerIds);
        }
        if (!empty($filters['search'])) {
            $where[] = "{$p}username LIKE ?";
            $params[] = '%' . $filters['search'] . '%';
        }

        return [implode(' AND ', $where), $params];
    }
}
