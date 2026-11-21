<?php

namespace App\Controllers;

use App\Core\Csrf;
use App\Core\Request;
use App\Services\RadiusService;
use App\Services\VoucherSyncService;

class DashboardController extends BaseController
{
    public function index(Request $request): void
    {
        require_auth();
        if (can_write_data()) {
            (new VoucherSyncService())->syncAll();
        }
        $tenantId = is_super_admin() ? null : $this->scope->currentTenantId();
        $routerIds = is_technician() ? $this->scope->currentRouterIds() : [];
        $count = fn (string $table) => $this->countScoped($table, $tenantId, $routerIds);
        $revenueToday = $this->sumRevenue('DATE(v.first_login_at) = ?', $tenantId, $routerIds, [date('Y-m-d')]);
        $revenueMonth = $this->sumRevenue('YEAR(v.first_login_at) = ? AND MONTH(v.first_login_at) = ?', $tenantId, $routerIds, [(int) date('Y'), (int) date('m')]);
        $radius = new RadiusService();
        $radiusFilters = $tenantId ? ['tenant_id' => $tenantId] : [];
        if (is_technician()) {
            $radiusFilters['router_ids'] = $routerIds ?: [-1];
        }
        $activeUsersCount = $radius->countActiveUsers($radiusFilters);
        $hotspotLogs = $radius->getHotspotLogs($radiusFilters, 20);
        $this->view('dashboard/index', compact('count', 'revenueToday', 'revenueMonth', 'activeUsersCount', 'hotspotLogs'));
    }

    public function sync(): void
    {
        require_not_technician();
        Csrf::verify();
        $result = (new VoucherSyncService())->syncAll();
        audit_log(current_user()['tenant_id'] ? (int) current_user()['tenant_id'] : null, current_user()['id'], 'sync voucher dashboard', json_encode($result));
        flash('success', 'Sync voucher selesai.');
        redirect('/dashboard');
    }

    private function countScoped(string $table, ?int $tenantId, array $routerIds): int
    {
        $where = [];
        $params = [];
        if ($tenantId && $table !== 'rb_tenants') {
            $where[] = 'tenant_id = ?';
            $params[] = $tenantId;
        }
        if (is_technician()) {
            if (!$routerIds) {
                return 0;
            }
            $placeholders = implode(',', array_fill(0, count($routerIds), '?'));
            if ($table === 'rb_routers') {
                $where[] = "id IN ({$placeholders})";
                $params = array_merge($params, $routerIds);
            } elseif (in_array($table, ['rb_vouchers', 'rb_voucher_batches', 'rb_transactions'], true)) {
                $where[] = "router_id IN ({$placeholders})";
                $params = array_merge($params, $routerIds);
            }
        }
        $suffix = $where ? ' WHERE ' . implode(' AND ', $where) : '';
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM {$table}{$suffix}");
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    private function sumRevenue(string $dateWhere, ?int $tenantId, array $routerIds = [], array $dateParams = []): float
    {
        $where = "t.status IN ('used','expired') AND v.first_login_at IS NOT NULL AND v.first_login_at >= v.created_at AND {$dateWhere}";
        $params = $dateParams;
        if ($tenantId) {
            $where .= ' AND t.tenant_id = ?';
            $params[] = $tenantId;
        }
        if (is_technician()) {
            if (!$routerIds) {
                return 0;
            }
            $where .= ' AND t.router_id IN (' . implode(',', array_fill(0, count($routerIds), '?')) . ')';
            $params = array_merge($params, $routerIds);
        }
        $stmt = $this->db->prepare("SELECT COALESCE(SUM(t.price),0) FROM rb_transactions t JOIN rb_vouchers v ON v.id = t.voucher_id WHERE {$where}");
        $stmt->execute($params);
        return (float) $stmt->fetchColumn();
    }
}
