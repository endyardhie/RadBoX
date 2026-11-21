<?php

namespace App\Controllers;

use App\Core\Csrf;
use App\Core\Request;

class UserController extends BaseController
{
    public function index(): void
    {
        require_user_manager();
        if (is_super_admin()) {
            $stmt = $this->db->prepare('SELECT u.*, t.name tenant_name FROM rb_users u LEFT JOIN rb_tenants t ON t.id = u.tenant_id ORDER BY u.created_at DESC');
            $stmt->execute();
        } else {
            $stmt = $this->db->prepare('SELECT u.*, t.name tenant_name FROM rb_users u LEFT JOIN rb_tenants t ON t.id = u.tenant_id WHERE u.tenant_id = ? AND u.role = "technician" ORDER BY u.created_at DESC');
            $stmt->execute([$this->scope->currentTenantId()]);
        }
        $items = $stmt->fetchAll();
        $this->view('users/index', compact('items'));
    }

    public function create(): void
    {
        require_user_manager();
        $tenants = $this->tenants();
        $routers = $this->assignableRouters();
        $assignedRouterIds = [];
        $this->view('users/create', compact('tenants', 'routers', 'assignedRouterIds'));
    }

    public function store(Request $request): void
    {
        require_user_manager();
        Csrf::verify();
        $role = $this->roleFromRequest($request);
        $tenantId = $role === 'super_admin' ? null : (int) $request->input('tenant_id');
        if (!is_super_admin()) {
            $tenantId = $this->scope->currentTenantId();
        }
        $this->ensureCanManageRole($role, $tenantId);

        $this->db->beginTransaction();
        $this->db->prepare('INSERT INTO rb_users (tenant_id,name,username,password_hash,role,status,created_at,updated_at) VALUES (?,?,?,?,?,?,NOW(),NOW())')
            ->execute([$tenantId, $request->input('name'), $request->input('username'), password_hash((string) $request->input('password'), PASSWORD_DEFAULT), $role, $request->input('status', 'active')]);
        $userId = (int) $this->db->lastInsertId();
        $this->syncRouterAssignments($userId, $role, $tenantId, $request->input('router_ids', []));
        $this->db->commit();
        audit_log(null, current_user()['id'], 'create user', (string) $request->input('username'));
        flash('success', 'User dibuat.');
        redirect('/users');
    }

    public function edit(Request $request): void
    {
        require_user_manager();
        $stmt = $this->db->prepare('SELECT * FROM rb_users WHERE id = ?');
        $stmt->execute([(int) $request->input('id')]);
        $item = $stmt->fetch();
        if (!$item || !$this->canManageUser($item)) {
            http_response_code(403);
            exit('Forbidden');
        }
        $tenants = $this->tenants();
        $routers = $this->assignableRouters(is_super_admin() ? null : (int) ($item['tenant_id'] ?? 0));
        $assignedRouterIds = $this->assignedRouterIds((int) $item['id']);
        $this->view('users/edit', compact('item', 'tenants', 'routers', 'assignedRouterIds'));
    }

    public function update(Request $request): void
    {
        require_user_manager();
        Csrf::verify();
        $stmt = $this->db->prepare('SELECT * FROM rb_users WHERE id = ?');
        $stmt->execute([(int) $request->input('id')]);
        $old = $stmt->fetch();
        if (!$old || !$this->canManageUser($old)) {
            http_response_code(403);
            exit('Forbidden');
        }

        $role = $this->roleFromRequest($request);
        $tenantId = $role === 'super_admin' ? null : (int) $request->input('tenant_id');
        if (!is_super_admin()) {
            $role = 'technician';
            $tenantId = $this->scope->currentTenantId();
        }
        $this->ensureCanManageRole($role, $tenantId);

        $params = [$tenantId, $request->input('name'), $request->input('username'), $role, $request->input('status'), (int) $request->input('id')];
        $sql = 'UPDATE rb_users SET tenant_id=?, name=?, username=?, role=?, status=?, updated_at=NOW() WHERE id=?';
        if ($request->input('password')) {
            $sql = 'UPDATE rb_users SET tenant_id=?, name=?, username=?, role=?, status=?, password_hash=?, updated_at=NOW() WHERE id=?';
            $params = [$tenantId, $request->input('name'), $request->input('username'), $role, $request->input('status'), password_hash((string) $request->input('password'), PASSWORD_DEFAULT), (int) $request->input('id')];
        }
        $this->db->beginTransaction();
        $this->db->prepare($sql)->execute($params);
        $this->syncRouterAssignments((int) $request->input('id'), $role, $tenantId, $request->input('router_ids', []));
        $this->db->commit();
        flash('success', 'User diperbarui.');
        redirect('/users');
    }

    public function delete(Request $request): void
    {
        require_user_manager();
        Csrf::verify();
        $stmt = $this->db->prepare('SELECT * FROM rb_users WHERE id = ?');
        $stmt->execute([(int) $request->input('id')]);
        $item = $stmt->fetch();
        if (!$item || !$this->canManageUser($item)) {
            http_response_code(403);
            exit('Forbidden');
        }
        $this->db->prepare('UPDATE rb_users SET status="inactive", updated_at=NOW() WHERE id=? AND id <> ?')->execute([(int) $request->input('id'), current_user()['id']]);
        flash('success', 'User dinonaktifkan.');
        redirect('/users');
    }

    public function toggle(Request $request): void
    {
        require_user_manager();
        Csrf::verify();
        $stmt = $this->db->prepare('SELECT * FROM rb_users WHERE id = ?');
        $stmt->execute([(int) $request->input('id')]);
        $item = $stmt->fetch();
        if (!$item || !$this->canManageUser($item) || (int) $item['id'] === (int) current_user()['id']) {
            http_response_code(403);
            exit('Forbidden');
        }

        $newStatus = $item['status'] === 'active' ? 'inactive' : 'active';
        $this->db->prepare('UPDATE rb_users SET status=?, updated_at=NOW() WHERE id=?')->execute([$newStatus, (int) $item['id']]);
        flash('success', 'User ' . ($newStatus === 'active' ? 'diaktifkan' : 'dinonaktifkan') . '.');
        redirect('/users');
    }

    private function roleFromRequest(Request $request): string
    {
        $role = (string) $request->input('role', 'technician');
        if (!in_array($role, ['super_admin', 'tenant_owner', 'technician'], true)) {
            $role = 'technician';
        }
        return is_super_admin() ? $role : 'technician';
    }

    private function ensureCanManageRole(string $role, ?int $tenantId): void
    {
        if (is_super_admin()) {
            return;
        }
        if ($role !== 'technician' || (int) $tenantId !== $this->scope->currentTenantId()) {
            http_response_code(403);
            exit('Forbidden');
        }
    }

    private function canManageUser(array $user): bool
    {
        if (is_super_admin()) {
            return true;
        }
        return $user['role'] === 'technician' && (int) $user['tenant_id'] === $this->scope->currentTenantId();
    }

    private function assignableRouters(?int $tenantId = null): array
    {
        if (is_super_admin()) {
            $sql = 'SELECT r.*, t.name tenant_name FROM rb_routers r JOIN rb_tenants t ON t.id = r.tenant_id';
            $params = [];
            if ($tenantId) {
                $sql .= ' WHERE r.tenant_id = ?';
                $params[] = $tenantId;
            }
            $sql .= ' ORDER BY t.name, r.name';
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        }

        $stmt = $this->db->prepare('SELECT r.*, t.name tenant_name FROM rb_routers r JOIN rb_tenants t ON t.id = r.tenant_id WHERE r.tenant_id = ? ORDER BY r.name');
        $stmt->execute([$this->scope->currentTenantId()]);
        return $stmt->fetchAll();
    }

    private function assignedRouterIds(int $userId): array
    {
        $stmt = $this->db->prepare('SELECT router_id FROM rb_user_routers WHERE user_id = ?');
        $stmt->execute([$userId]);
        return array_map('intval', array_column($stmt->fetchAll(), 'router_id'));
    }

    private function syncRouterAssignments(int $userId, string $role, ?int $tenantId, mixed $routerIds): void
    {
        $this->db->prepare('DELETE FROM rb_user_routers WHERE user_id = ?')->execute([$userId]);
        if ($role !== 'technician') {
            return;
        }

        $ids = array_values(array_unique(array_map('intval', (array) $routerIds)));
        if (!$ids) {
            return;
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $params = $ids;
        $where = "id IN ({$placeholders})";
        if ($tenantId) {
            $where .= ' AND tenant_id = ?';
            $params[] = $tenantId;
        }
        if (!is_super_admin()) {
            $where .= ' AND tenant_id = ?';
            $params[] = $this->scope->currentTenantId();
        }

        $stmt = $this->db->prepare("SELECT id FROM rb_routers WHERE {$where}");
        $stmt->execute($params);
        $allowedIds = array_map('intval', array_column($stmt->fetchAll(), 'id'));
        $insert = $this->db->prepare('INSERT INTO rb_user_routers (user_id, router_id, created_at) VALUES (?, ?, NOW())');
        foreach ($allowedIds as $routerId) {
            $insert->execute([$userId, $routerId]);
        }
    }
}
