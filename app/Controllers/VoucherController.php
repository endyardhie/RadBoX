<?php

namespace App\Controllers;

use App\Core\Csrf;
use App\Core\Request;
use App\Services\RadiusService;
use App\Services\VoucherGeneratorService;
use App\Services\VoucherSyncService;

class VoucherController extends BaseController
{
    public function index(Request $request): void
    {
        require_auth();
        if (can_write_data()) {
            (new VoucherSyncService())->syncAll();
        }
        [$where, $params] = $this->voucherFilters($request);
        $sql = "SELECT v.*, p.name profile_name, r.name router_name, t.name tenant_name, b.batch_code, rc.username access_username
                FROM rb_vouchers v
                JOIN rb_profiles p ON p.id = v.profile_id
                JOIN rb_tenants t ON t.id = v.tenant_id
                LEFT JOIN rb_routers r ON r.id = v.router_id
                LEFT JOIN rb_voucher_batches b ON b.id = v.batch_id
                LEFT JOIN radcheck rc ON rc.username = v.username AND rc.attribute = 'Cleartext-Password'
                WHERE 1=1 {$where}
                ORDER BY v.created_at DESC LIMIT 200";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $items = $stmt->fetchAll();
        $profiles = $this->scopedOptions('rb_profiles');
        $routers = $this->scopedOptions('rb_routers');
        $tenants = $this->tenants();
        $batches = $this->batches();
        $selectedBatchId = $request->input('batch_id') ? (int) $request->input('batch_id') : null;
        $hasFiltered = $this->hasVoucherFilters($request);
        $this->view('vouchers/index', compact('items', 'profiles', 'routers', 'tenants', 'batches', 'selectedBatchId', 'hasFiltered'));
    }

    public function create(): void
    {
        require_not_technician();
        $profiles = $this->scopedOptions('rb_profiles');
        $routers = $this->scopedOptions('rb_routers');
        $this->view('vouchers/create', compact('profiles', 'routers'));
    }

    public function storeManual(Request $request): void
    {
        require_not_technician();
        Csrf::verify();
        $profileId = (int) $request->input('profile_id');
        $routerId = $request->input('router_id') ? (int) $request->input('router_id') : null;
        if (!$this->canAccessProfile($profileId) || ($routerId && !$this->scope->canAccessRouter($routerId))) {
            http_response_code(403);
            exit('Forbidden');
        }
        try {
            (new VoucherGeneratorService())->createManual($profileId, $routerId, (string) $request->input('username'), (string) $request->input('password'), [
                'created_by' => current_user()['id'],
                'comment' => $request->input('comment'),
            ]);
            flash('success', 'User manual berhasil ditambahkan.');
            redirect('/vouchers');
        } catch (\Throwable $e) {
            flash('error', $e->getMessage());
            redirect('/vouchers/create');
        }
    }

    public function generate(): void
    {
        require_not_technician();
        $profiles = $this->activeProfiles();
        $tenants = is_super_admin() ? $this->tenants() : [];
        $this->view('vouchers/generate', compact('profiles', 'tenants'));
    }

    public function storeGenerate(Request $request): void
    {
        require_not_technician();
        Csrf::verify();
        $profileId = (int) $request->input('profile_id');
        $targetTenantId = is_super_admin() ? (int) $request->input('tenant_id') : $this->scope->currentTenantId();
        $profileTenantId = $this->activeProfileTenantId($profileId);
        if (!$targetTenantId || !$profileTenantId || (int) $profileTenantId !== (int) $targetTenantId || !$this->scope->canAccessTenant($targetTenantId)) {
            flash('error', 'Pilih target tenant dan profile yang valid.');
            redirect('/vouchers/generate');
        }
        try {
            $batchId = (new VoucherGeneratorService())->generateBulk($profileId, null, (int) $request->input('quantity'), [
                'created_by' => current_user()['id'],
                'comment' => $request->input('comment'),
            ]);
            audit_log($targetTenantId, current_user()['id'], 'generate voucher', 'Batch #' . $batchId);
            flash('success', 'Voucher berhasil digenerate.');
            redirect('/vouchers/batch?id=' . $batchId);
        } catch (\Throwable $e) {
            flash('error', $e->getMessage());
            redirect('/vouchers/generate');
        }
    }

    public function batch(Request $request): void
    {
        require_auth();
        $batchId = (int) $request->input('id');
        $batch = $this->findBatch($batchId);
        $items = $this->batchVouchers($batchId);
        $this->view('vouchers/show_batch', compact('batch', 'items'));
    }

    public function print(Request $request): void
    {
        require_auth();
        $batchId = (int) $request->input('batch_id');
        $templates = voucher_templates();
        $templateIndex = max(0, min(count($templates) - 1, (int) $request->input('template', 0)));
        $template = $templates[$templateIndex];
        $batch = $this->findBatch($batchId);
        $items = $this->batchVouchers($batchId);
        $this->view('vouchers/print', compact('batch', 'items', 'templates', 'template', 'templateIndex'), 'auth');
    }

    public function sync(): void
    {
        require_not_technician();
        Csrf::verify();
        $result = (new VoucherSyncService())->syncAll();
        audit_log(current_user()['tenant_id'] ? (int) current_user()['tenant_id'] : null, current_user()['id'], 'sync voucher', json_encode($result));
        flash('success', 'Sync voucher selesai.');
        redirect('/vouchers');
    }

    public function delete(Request $request): void
    {
        require_not_technician();
        Csrf::verify();
        $id = (int) $request->input('id');
        if (!$this->scope->canAccessVoucher($id)) {
            http_response_code(403);
            exit('Forbidden');
        }
        $stmt = $this->db->prepare('SELECT username FROM rb_vouchers WHERE id = ?');
        $stmt->execute([$id]);
        $username = $stmt->fetchColumn();
        if ($username) {
            $this->db->prepare('UPDATE rb_vouchers SET status="deleted", deleted_at=NOW(), updated_at=NOW() WHERE id=?')->execute([$id]);
            $this->db->prepare('UPDATE rb_transactions SET status="deleted", updated_at=NOW() WHERE voucher_id=?')->execute([$id]);
            $radius = new RadiusService();
            $radius->deleteUserAccess($username);
            $radius->releaseDeletedUsername((string) $username);
        }
        audit_log(current_user()['tenant_id'] ? (int) current_user()['tenant_id'] : null, current_user()['id'], 'delete voucher', (string) $username);
        flash('success', 'Voucher dinonaktifkan.');
        redirect('/vouchers');
    }

    public function deleteFiltered(Request $request): void
    {
        require_not_technician();
        Csrf::verify();
        if (!$this->hasVoucherFilters($request)) {
            flash('error', 'Pilih filter terlebih dahulu sebelum menghapus banyak voucher.');
            redirect('/vouchers');
        }
        if ($request->input('status') === 'deleted') {
            flash('error', 'Filter status deleted tidak perlu dihapus ulang.');
            redirect('/vouchers?' . http_build_query($this->voucherQuery($request)));
        }

        [$where, $params] = $this->voucherFilters($request);
        $stmt = $this->db->prepare("SELECT v.id, v.username FROM rb_vouchers v WHERE 1=1 {$where}");
        $stmt->execute($params);
        $vouchers = $stmt->fetchAll();
        if (!$vouchers) {
            flash('error', 'Tidak ada voucher pada hasil filter ini.');
            redirect('/vouchers?' . http_build_query($this->voucherQuery($request)));
        }

        $ids = array_map(fn ($voucher) => (int) $voucher['id'], $vouchers);
        $usernames = array_map(fn ($voucher) => (string) $voucher['username'], $vouchers);
        $in = implode(',', array_fill(0, count($ids), '?'));
        $this->db->prepare("UPDATE rb_vouchers SET status = 'deleted', deleted_at = NOW(), updated_at = NOW() WHERE id IN ({$in})")->execute($ids);
        $this->db->prepare("UPDATE rb_transactions SET status = 'deleted', updated_at = NOW() WHERE voucher_id IN ({$in})")->execute($ids);

        $radius = new RadiusService();
        foreach ($usernames as $username) {
            $radius->deleteUserAccess($username);
            $radius->releaseDeletedUsername($username);
        }
        audit_log(current_user()['tenant_id'] ? (int) current_user()['tenant_id'] : null, current_user()['id'], 'delete filtered vouchers', count($ids) . ' voucher');
        flash('success', count($ids) . ' voucher hasil filter berhasil dihapus.');
        redirect('/vouchers?' . http_build_query($this->voucherQuery($request)));
    }

    public function toggleAccess(Request $request): void
    {
        require_not_technician();
        Csrf::verify();
        $id = (int) $request->input('id');
        if (!$this->scope->canAccessVoucher($id)) {
            http_response_code(403);
            exit('Forbidden');
        }
        $stmt = $this->db->prepare('SELECT v.*, p.group_name FROM rb_vouchers v JOIN rb_profiles p ON p.id = v.profile_id WHERE v.id = ?');
        $stmt->execute([$id]);
        $voucher = $stmt->fetch();
        if (!$voucher || $voucher['status'] === 'deleted') {
            flash('error', 'Voucher tidak ditemukan atau sudah dihapus.');
            redirect('/vouchers');
        }

        $radius = new RadiusService();
        if ($this->accessEnabled((string) $voucher['username'])) {
            $radius->deleteUserAccess((string) $voucher['username']);
            flash('success', 'Akses voucher dinonaktifkan.');
        } else {
            $radius->createUser((string) $voucher['username'], (string) $voucher['password'], (string) $voucher['group_name']);
            flash('success', 'Akses voucher diaktifkan.');
        }
        redirect('/vouchers');
    }

    private function voucherFilters(Request $request): array
    {
        $where = '';
        $params = [];
        if (!is_super_admin() && $this->scope->currentTenantId()) {
            $where .= ' AND v.tenant_id = ?';
            $params[] = $this->scope->currentTenantId();
        } elseif ($request->input('tenant_id')) {
            $where .= ' AND v.tenant_id = ?';
            $params[] = (int) $request->input('tenant_id');
        }
        foreach (['router_id', 'profile_id'] as $field) {
            if ($request->input($field)) {
                if ($field === 'router_id' && !$this->scope->canAccessRouter((int) $request->input($field))) {
                    $where .= ' AND 1=0';
                    continue;
                }
                $where .= " AND v.{$field} = ?";
                $params[] = (int) $request->input($field);
            }
        }
        if (is_technician() && !$request->input('router_id')) {
            [$routerWhere, $routerParams] = $this->scope->routerAccessWhere('v.router_id');
            $where .= $routerWhere;
            $params = array_merge($params, $routerParams);
        }
        if ($request->input('batch_id')) {
            $where .= ' AND v.batch_id = ?';
            $params[] = (int) $request->input('batch_id');
        }
        if ($request->input('status')) {
            $where .= ' AND v.status = ?';
            $params[] = $request->input('status');
        } else {
            $where .= ' AND v.status <> "deleted"';
        }
        if ($request->input('search')) {
            $where .= ' AND v.username LIKE ?';
            $params[] = '%' . $request->input('search') . '%';
        }
        return [$where, $params];
    }

    private function batches(): array
    {
        if (is_super_admin()) {
            $stmt = $this->db->prepare('SELECT b.*, t.name tenant_name, p.name profile_name
                FROM rb_voucher_batches b
                JOIN rb_tenants t ON t.id = b.tenant_id
                JOIN rb_profiles p ON p.id = b.profile_id
                WHERE EXISTS (SELECT 1 FROM rb_vouchers v WHERE v.batch_id = b.id AND v.status <> "deleted")
                ORDER BY b.created_at DESC LIMIT 200');
            $stmt->execute();
            return $stmt->fetchAll();
        }
        if (is_technician()) {
            [$routerWhere, $routerParams] = $this->scope->routerAccessWhere('b.router_id');
            $tenantWhere = $this->scope->currentTenantId() ? 'b.tenant_id = ?' : '1=1';
            $tenantParams = $this->scope->currentTenantId() ? [$this->scope->currentTenantId()] : [];
            $stmt = $this->db->prepare('SELECT b.*, t.name tenant_name, p.name profile_name
                FROM rb_voucher_batches b
                JOIN rb_tenants t ON t.id = b.tenant_id
                JOIN rb_profiles p ON p.id = b.profile_id
                WHERE ' . $tenantWhere . ' ' . $routerWhere . '
                    AND EXISTS (SELECT 1 FROM rb_vouchers v WHERE v.batch_id = b.id AND v.status <> "deleted")
                ORDER BY b.created_at DESC LIMIT 200');
            $stmt->execute(array_merge($tenantParams, $routerParams));
            return $stmt->fetchAll();
        }
        $stmt = $this->db->prepare('SELECT b.*, t.name tenant_name, p.name profile_name
            FROM rb_voucher_batches b
            JOIN rb_tenants t ON t.id = b.tenant_id
            JOIN rb_profiles p ON p.id = b.profile_id
            WHERE b.tenant_id = ?
                AND EXISTS (SELECT 1 FROM rb_vouchers v WHERE v.batch_id = b.id AND v.status <> "deleted")
            ORDER BY b.created_at DESC LIMIT 200');
        $stmt->execute([$this->scope->currentTenantId()]);
        return $stmt->fetchAll();
    }

    private function hasVoucherFilters(Request $request): bool
    {
        foreach (['search', 'tenant_id', 'batch_id', 'router_id', 'profile_id', 'status'] as $field) {
            if ((string) $request->input($field, '') !== '') {
                return true;
            }
        }
        return false;
    }

    private function voucherQuery(Request $request): array
    {
        $query = [];
        foreach (['search', 'tenant_id', 'batch_id', 'router_id', 'profile_id', 'status'] as $field) {
            $value = (string) $request->input($field, '');
            if ($value !== '') {
                $query[$field] = $value;
            }
        }
        return $query;
    }

    private function accessEnabled(string $username): bool
    {
        $stmt = $this->db->prepare('SELECT 1 FROM radcheck WHERE username = ? AND attribute = "Cleartext-Password" LIMIT 1');
        $stmt->execute([$username]);
        return (bool) $stmt->fetchColumn();
    }

    private function findBatch(int $batchId): array
    {
        $stmt = $this->db->prepare('SELECT b.*, p.name profile_name, p.rate_limit, p.total_uptime_minutes, p.validity_minutes, t.name tenant_name FROM rb_voucher_batches b JOIN rb_profiles p ON p.id = b.profile_id JOIN rb_tenants t ON t.id = b.tenant_id WHERE b.id = ?');
        $stmt->execute([$batchId]);
        $batch = $stmt->fetch();
        if (!$batch || !$this->scope->canAccessBatch($batchId)) {
            http_response_code(403);
            exit('Forbidden');
        }
        return $batch;
    }

    private function batchVouchers(int $batchId): array
    {
        $stmt = $this->db->prepare('SELECT v.*, p.name profile_name, p.rate_limit, p.total_uptime_minutes FROM rb_vouchers v JOIN rb_profiles p ON p.id = v.profile_id WHERE v.batch_id = ? ORDER BY v.id');
        $stmt->execute([$batchId]);
        return $stmt->fetchAll();
    }

    private function canAccessProfile(int $profileId): bool
    {
        if (is_super_admin()) {
            return true;
        }
        $stmt = $this->db->prepare('SELECT tenant_id FROM rb_profiles WHERE id = ?');
        $stmt->execute([$profileId]);
        return (int) $stmt->fetchColumn() === $this->scope->currentTenantId();
    }

    private function activeProfiles(): array
    {
        if (is_super_admin()) {
            $stmt = $this->db->prepare('SELECT p.*, t.name tenant_name FROM rb_profiles p JOIN rb_tenants t ON t.id = p.tenant_id WHERE p.is_active = 1 ORDER BY t.name, p.name');
            $stmt->execute();
            return $stmt->fetchAll();
        }

        $stmt = $this->db->prepare('SELECT p.* FROM rb_profiles p WHERE p.tenant_id = ? AND p.is_active = 1 ORDER BY p.name');
        $stmt->execute([$this->scope->currentTenantId()]);
        return $stmt->fetchAll();
    }

    private function activeProfileTenantId(int $profileId): ?int
    {
        $stmt = $this->db->prepare('SELECT tenant_id FROM rb_profiles WHERE id = ? AND is_active = 1');
        $stmt->execute([$profileId]);
        $tenantId = $stmt->fetchColumn();
        return $tenantId ? (int) $tenantId : null;
    }
}
