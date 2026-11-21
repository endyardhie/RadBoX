<?php

namespace App\Controllers;

use App\Core\Csrf;
use App\Core\Request;
use App\Services\RadiusNasService;

class RouterController extends BaseController
{
    public function index(): void
    {
        require_not_technician();
        $where = is_super_admin() ? '' : 'WHERE r.tenant_id = ?';
        $stmt = $this->db->prepare("SELECT r.*, t.name tenant_name FROM rb_routers r JOIN rb_tenants t ON t.id = r.tenant_id {$where} ORDER BY r.created_at DESC");
        $stmt->execute(is_super_admin() ? [] : [$this->scope->currentTenantId()]);
        $items = $stmt->fetchAll();
        $this->view('routers/index', compact('items'));
    }

    public function create(): void
    {
        require_super_admin();
        $tenants = $this->tenants();
        $this->view('routers/create', compact('tenants'));
    }

    public function store(Request $request): void
    {
        require_super_admin();
        Csrf::verify();
        try {
            $this->db->beginTransaction();
            $stmt = $this->db->prepare('INSERT INTO rb_routers (tenant_id,name,nas_ip_address,radius_secret,voucher_prefix,description,status,created_at,updated_at) VALUES (?,?,?,?,?,?,?,NOW(),NOW())');
            $stmt->execute([(int) $request->input('tenant_id'), $request->input('name'), $request->input('nas_ip_address'), $request->input('radius_secret'), $this->voucherPrefix($request), $request->input('description'), $request->input('status', 'active')]);
            $id = (int) $this->db->lastInsertId();
            $this->syncNas($id);
            $this->db->commit();
            audit_log((int) $request->input('tenant_id'), current_user()['id'], 'create router', (string) $request->input('name'));
            flash('success', 'Router dibuat dan disinkronkan ke tabel FreeRADIUS nas.');
        } catch (\Throwable $e) {
            $this->db->rollBack();
            flash('error', 'Gagal sync router ke nas: ' . $e->getMessage());
        }
        redirect('/routers');
    }

    public function edit(Request $request): void
    {
        require_super_admin();
        $stmt = $this->db->prepare('SELECT * FROM rb_routers WHERE id = ?');
        $stmt->execute([(int) $request->input('id')]);
        $item = $stmt->fetch();
        $tenants = $this->tenants();
        $this->view('routers/edit', compact('item', 'tenants'));
    }

    public function update(Request $request): void
    {
        require_super_admin();
        Csrf::verify();
        $id = (int) $request->input('id');
        if ($request->input('radius_secret')) {
            $sql = 'UPDATE rb_routers SET tenant_id=?, name=?, nas_ip_address=?, radius_secret=?, voucher_prefix=?, description=?, status=?, updated_at=NOW() WHERE id=?';
            $params = [(int) $request->input('tenant_id'), $request->input('name'), $request->input('nas_ip_address'), $request->input('radius_secret'), $this->voucherPrefix($request), $request->input('description'), $request->input('status'), $id];
        } else {
            $sql = 'UPDATE rb_routers SET tenant_id=?, name=?, nas_ip_address=?, voucher_prefix=?, description=?, status=?, updated_at=NOW() WHERE id=?';
            $params = [(int) $request->input('tenant_id'), $request->input('name'), $request->input('nas_ip_address'), $this->voucherPrefix($request), $request->input('description'), $request->input('status'), $id];
        }
        try {
            $this->db->beginTransaction();
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $this->syncNas($id);
            $this->db->commit();
            flash('success', 'Router diperbarui dan disinkronkan ke tabel FreeRADIUS nas.');
        } catch (\Throwable $e) {
            $this->db->rollBack();
            flash('error', 'Gagal sync router ke nas: ' . $e->getMessage());
        }
        redirect('/routers');
    }

    public function delete(Request $request): void
    {
        require_super_admin();
        Csrf::verify();
        try {
            $this->db->beginTransaction();
            $id = (int) $request->input('id');
            $stmt = $this->db->prepare('UPDATE rb_routers SET status="inactive", updated_at=NOW() WHERE id=?');
            $stmt->execute([$id]);
            $this->syncNas($id);
            $this->db->commit();
            flash('success', 'Router dinonaktifkan dan dihapus dari tabel FreeRADIUS nas.');
        } catch (\Throwable $e) {
            $this->db->rollBack();
            flash('error', 'Gagal menonaktifkan router di nas: ' . $e->getMessage());
        }
        redirect('/routers');
    }

    public function toggle(Request $request): void
    {
        require_super_admin();
        Csrf::verify();
        $id = (int) $request->input('id');
        try {
            $this->db->beginTransaction();
            $stmt = $this->db->prepare('SELECT status FROM rb_routers WHERE id = ?');
            $stmt->execute([$id]);
            $status = $stmt->fetchColumn();
            if (!$status) {
                throw new \RuntimeException('Router tidak ditemukan.');
            }
            $newStatus = $status === 'active' ? 'inactive' : 'active';
            $stmt = $this->db->prepare('UPDATE rb_routers SET status=?, updated_at=NOW() WHERE id=?');
            $stmt->execute([$newStatus, $id]);
            $this->syncNas($id);
            $this->db->commit();
            flash('success', 'Router ' . ($newStatus === 'active' ? 'diaktifkan' : 'dinonaktifkan') . '.');
        } catch (\Throwable $e) {
            $this->db->rollBack();
            flash('error', 'Gagal mengubah status router: ' . $e->getMessage());
        }
        redirect('/routers');
    }

    private function syncNas(int $id): void
    {
        $stmt = $this->db->prepare('SELECT r.*, t.name tenant_name FROM rb_routers r JOIN rb_tenants t ON t.id = r.tenant_id WHERE r.id = ?');
        $stmt->execute([$id]);
        (new RadiusNasService())->syncRouterToNasTable($stmt->fetch());
    }

    private function voucherPrefix(Request $request): string
    {
        return strtoupper(trim((string) $request->input('voucher_prefix', '')));
    }
}
