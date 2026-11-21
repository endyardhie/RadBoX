<?php

namespace App\Controllers;

use App\Core\Csrf;
use App\Core\Request;
use App\Services\RadiusCoaService;
use App\Services\RadiusService;
use App\Services\VoucherSyncService;

class ActiveController extends BaseController
{
    public function index(): void
    {
        require_auth();
        if (can_write_data()) {
            (new VoucherSyncService())->syncAll();
        }
        $tenantId = is_super_admin() ? null : $this->scope->currentTenantId();
        $filters = $tenantId ? ['tenant_id' => $tenantId] : [];
        if (is_technician()) {
            $filters['router_ids'] = $this->scope->currentRouterIds() ?: [-1];
        }
        $items = (new RadiusService())->getActiveUsers($filters);
        $this->view('active/index', compact('items'));
    }

    public function kick(Request $request): void
    {
        require_not_technician();
        Csrf::verify();
        $username = (string) $request->input('username');
        $nasIp = (string) $request->input('nasipaddress');
        $acctSessionId = (string) $request->input('acctsessionid');
        $where = 'a.username = ? AND a.nasipaddress = ? AND a.acctstoptime IS NULL';
        $params = [$username, $nasIp];
        if ($acctSessionId !== '') {
            $where .= ' AND a.acctsessionid = ?';
            $params[] = $acctSessionId;
        }
        if (!is_super_admin()) {
            $where .= ' AND r.tenant_id = ?';
            $params[] = $this->scope->currentTenantId();
        }
        $stmt = $this->db->prepare("SELECT a.*, r.tenant_id, r.name router_name, r.radius_secret router_radius_secret FROM radacct a LEFT JOIN rb_routers r ON r.nas_ip_address = a.nasipaddress WHERE {$where} ORDER BY a.acctstarttime DESC LIMIT 1");
        $stmt->execute($params);
        $session = $stmt->fetch();
        if (!$session) {
            flash('error', 'Session aktif tidak ditemukan.');
            redirect('/active');
        }
        $result = (new RadiusCoaService())->disconnect($session);
        flash($result['ok'] ? 'success' : 'error', $result['message']);
        redirect('/active');
    }
}
