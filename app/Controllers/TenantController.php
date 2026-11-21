<?php

namespace App\Controllers;

use App\Core\Csrf;
use App\Core\Request;

class TenantController extends BaseController
{
    public function index(): void
    {
        require_super_admin();
        $stmt = $this->db->prepare('SELECT * FROM rb_tenants ORDER BY created_at DESC');
        $stmt->execute();
        $items = $stmt->fetchAll();
        $this->view('tenants/index', compact('items'));
    }

    public function create(): void
    {
        require_super_admin();
        $this->view('tenants/create');
    }

    public function store(Request $request): void
    {
        require_super_admin();
        Csrf::verify();
        $this->db->prepare('INSERT INTO rb_tenants (name,code,status,notes,created_at,updated_at) VALUES (?,?,?,?,NOW(),NOW())')
            ->execute([$request->input('name'), strtoupper((string) $request->input('code')), $request->input('status', 'active'), $request->input('notes')]);
        audit_log(null, current_user()['id'], 'create tenant', (string) $request->input('name'));
        flash('success', 'Tenant dibuat.');
        redirect('/tenants');
    }

    public function edit(Request $request): void
    {
        require_super_admin();
        $stmt = $this->db->prepare('SELECT * FROM rb_tenants WHERE id = ?');
        $stmt->execute([(int) $request->input('id')]);
        $item = $stmt->fetch();
        $this->view('tenants/edit', compact('item'));
    }

    public function update(Request $request): void
    {
        require_super_admin();
        Csrf::verify();
        $this->db->prepare('UPDATE rb_tenants SET name=?, code=?, status=?, notes=?, updated_at=NOW() WHERE id=?')
            ->execute([$request->input('name'), strtoupper((string) $request->input('code')), $request->input('status'), $request->input('notes'), (int) $request->input('id')]);
        flash('success', 'Tenant diperbarui.');
        redirect('/tenants');
    }

    public function delete(Request $request): void
    {
        require_super_admin();
        Csrf::verify();
        $this->db->prepare('UPDATE rb_tenants SET status="inactive", updated_at=NOW() WHERE id=?')->execute([(int) $request->input('id')]);
        flash('success', 'Tenant dinonaktifkan.');
        redirect('/tenants');
    }

    public function toggle(Request $request): void
    {
        require_super_admin();
        Csrf::verify();
        $id = (int) $request->input('id');
        $stmt = $this->db->prepare('SELECT status FROM rb_tenants WHERE id = ?');
        $stmt->execute([$id]);
        $status = $stmt->fetchColumn();
        if (!$status) {
            flash('error', 'Tenant tidak ditemukan.');
            redirect('/tenants');
        }
        $newStatus = $status === 'active' ? 'inactive' : 'active';
        $this->db->prepare('UPDATE rb_tenants SET status=?, updated_at=NOW() WHERE id=?')->execute([$newStatus, $id]);
        flash('success', 'Tenant ' . ($newStatus === 'active' ? 'diaktifkan' : 'dinonaktifkan') . '.');
        redirect('/tenants');
    }
}
