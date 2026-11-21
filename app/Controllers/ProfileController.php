<?php

namespace App\Controllers;

use App\Core\Csrf;
use App\Core\Request;
use App\Services\RadiusService;

class ProfileController extends BaseController
{
    public function index(): void
    {
        require_not_technician();
        $where = is_super_admin() ? '' : 'WHERE p.tenant_id = ?';
        $stmt = $this->db->prepare("SELECT p.*, t.name tenant_name FROM rb_profiles p JOIN rb_tenants t ON t.id = p.tenant_id {$where} ORDER BY p.created_at DESC");
        $stmt->execute(is_super_admin() ? [] : [$this->scope->currentTenantId()]);
        $items = $stmt->fetchAll();
        $this->view('profiles/index', compact('items'));
    }

    public function create(): void
    {
        require_not_technician();
        $tenants = $this->tenants();
        $this->view('profiles/create', compact('tenants'));
    }

    public function store(Request $request): void
    {
        require_not_technician();
        Csrf::verify();
        $tenantId = is_super_admin() ? (int) $request->input('tenant_id') : $this->scope->currentTenantId();
        $groupName = $this->tenantCode($tenantId) . '_' . strtoupper(preg_replace('/[^A-Z0-9]+/', '_', (string) $request->input('group_name')));
        try {
            $totalUptime = parse_duration_minutes($request->input('total_uptime'));
            $validity = parse_duration_minutes($request->input('validity'));
            $this->db->beginTransaction();
            $stmt = $this->db->prepare('INSERT INTO rb_profiles (tenant_id,name,group_name,username_prefix,total_uptime_minutes,validity_minutes,idle_timeout_minutes,rate_limit,shared_users,price,description,is_active,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,NOW(),NOW())');
            $stmt->execute([$tenantId, $request->input('name'), $groupName, strtoupper((string) $request->input('username_prefix')), $totalUptime, $validity, $request->input('idle_timeout_minutes') ?: null, $request->input('rate_limit') ?: null, (int) $request->input('shared_users'), (float) $request->input('price'), $request->input('description'), (int) (bool) $request->input('is_active', 1)]);
            $this->syncProfile((int) $this->db->lastInsertId());
            $this->db->commit();
            audit_log($tenantId, current_user()['id'], 'create profile', (string) $request->input('name'));
            flash('success', 'Profile dibuat dan disinkronkan ke FreeRADIUS.');
        } catch (\InvalidArgumentException $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            flash('error', $e->getMessage());
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            flash('error', 'Gagal sync profile ke FreeRADIUS: ' . $e->getMessage());
        }
        redirect('/profiles');
    }

    public function edit(Request $request): void
    {
        require_not_technician();
        $stmt = $this->db->prepare('SELECT * FROM rb_profiles WHERE id = ?');
        $stmt->execute([(int) $request->input('id')]);
        $item = $stmt->fetch();
        if (!$item || !$this->scope->canAccessTenant((int) $item['tenant_id'])) {
            http_response_code(403);
            exit('Forbidden');
        }
        $tenants = $this->tenants();
        $this->view('profiles/edit', compact('item', 'tenants'));
    }

    public function update(Request $request): void
    {
        require_not_technician();
        Csrf::verify();
        $id = (int) $request->input('id');
        $stmt = $this->db->prepare('SELECT * FROM rb_profiles WHERE id = ?');
        $stmt->execute([$id]);
        $old = $stmt->fetch();
        if (!$old || !$this->scope->canAccessTenant((int) $old['tenant_id'])) {
            http_response_code(403);
            exit('Forbidden');
        }
        $tenantId = is_super_admin() ? (int) $request->input('tenant_id') : (int) $old['tenant_id'];
        try {
            $totalUptime = parse_duration_minutes($request->input('total_uptime'));
            $validity = parse_duration_minutes($request->input('validity'));
            $this->db->beginTransaction();
            $stmt = $this->db->prepare('UPDATE rb_profiles SET tenant_id=?, name=?, username_prefix=?, total_uptime_minutes=?, validity_minutes=?, idle_timeout_minutes=?, rate_limit=?, shared_users=?, price=?, description=?, is_active=?, updated_at=NOW() WHERE id=?');
            $stmt->execute([$tenantId, $request->input('name'), strtoupper((string) $request->input('username_prefix')), $totalUptime, $validity, $request->input('idle_timeout_minutes') ?: null, $request->input('rate_limit') ?: null, (int) $request->input('shared_users'), (float) $request->input('price'), $request->input('description'), (int) (bool) $request->input('is_active'), $id]);
            $this->syncProfile($id);
            $this->db->commit();
            flash('success', 'Profile diperbarui dan disinkronkan ke FreeRADIUS.');
        } catch (\InvalidArgumentException $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            flash('error', $e->getMessage());
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            flash('error', 'Gagal sync profile ke FreeRADIUS: ' . $e->getMessage());
        }
        redirect('/profiles');
    }

    public function delete(Request $request): void
    {
        require_not_technician();
        Csrf::verify();
        $id = (int) $request->input('id');
        $used = $this->db->prepare('SELECT COUNT(*) FROM rb_vouchers WHERE profile_id = ?');
        $used->execute([$id]);
        if ((int) $used->fetchColumn() > 0) {
            flash('error', 'Profile sudah digunakan. Nonaktifkan saja.');
            redirect('/profiles');
        }
        $stmt = $this->db->prepare('SELECT group_name FROM rb_profiles WHERE id = ?');
        $stmt->execute([$id]);
        $group = $stmt->fetchColumn();
        if ($group) {
            (new RadiusService())->deleteGroupProfile($group);
        }
        $this->db->prepare('DELETE FROM rb_profiles WHERE id = ?')->execute([$id]);
        flash('success', 'Profile dihapus.');
        redirect('/profiles');
    }

    public function toggle(Request $request): void
    {
        require_not_technician();
        Csrf::verify();
        $id = (int) $request->input('id');
        $stmt = $this->db->prepare('SELECT * FROM rb_profiles WHERE id = ?');
        $stmt->execute([$id]);
        $profile = $stmt->fetch();
        if (!$profile || !$this->scope->canAccessTenant((int) $profile['tenant_id'])) {
            http_response_code(403);
            exit('Forbidden');
        }

        $newStatus = (int) $profile['is_active'] === 1 ? 0 : 1;
        $this->db->prepare('UPDATE rb_profiles SET is_active=?, updated_at=NOW() WHERE id=?')->execute([$newStatus, $id]);
        $this->syncProfile($id);
        flash('success', 'Profile ' . ($newStatus === 1 ? 'diaktifkan' : 'dinonaktifkan') . '.');
        redirect('/profiles');
    }

    private function tenantCode(int $tenantId): string
    {
        $stmt = $this->db->prepare('SELECT code FROM rb_tenants WHERE id = ?');
        $stmt->execute([$tenantId]);
        return strtoupper((string) $stmt->fetchColumn());
    }

    private function syncProfile(int $id): void
    {
        $stmt = $this->db->prepare('SELECT * FROM rb_profiles WHERE id = ?');
        $stmt->execute([$id]);
        (new RadiusService())->createOrUpdateGroupProfile($stmt->fetch());
    }
}
