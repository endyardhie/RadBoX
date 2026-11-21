<?php

namespace App\Controllers;

use App\Core\Database;
use App\Core\View;
use App\Services\TenantScopeService;
use PDO;

abstract class BaseController
{
    protected PDO $db;
    protected TenantScopeService $scope;

    public function __construct()
    {
        $this->db = Database::pdo();
        $this->scope = new TenantScopeService();
    }

    protected function view(string $view, array $data = [], string $layout = 'app'): void
    {
        View::render($view, $data, $layout);
    }

    protected function tenants(): array
    {
        if (!is_super_admin()) {
            $stmt = $this->db->prepare('SELECT * FROM rb_tenants WHERE id = ?');
            $stmt->execute([$this->scope->currentTenantId()]);
            return $stmt->fetchAll();
        }
        $stmt = $this->db->prepare('SELECT * FROM rb_tenants ORDER BY name');
        $stmt->execute();
        return $stmt->fetchAll();
    }

    protected function scopedOptions(string $table, string $order = 'name'): array
    {
        if (is_super_admin()) {
            $stmt = $this->db->prepare("SELECT * FROM {$table} ORDER BY {$order}");
            $stmt->execute();
            return $stmt->fetchAll();
        }
        if (is_technician() && $table === 'rb_routers') {
            [$routerWhere, $routerParams] = $this->scope->routerAccessWhere('id');
            $tenantWhere = $this->scope->currentTenantId() ? 'tenant_id = ?' : '1=1';
            $tenantParams = $this->scope->currentTenantId() ? [$this->scope->currentTenantId()] : [];
            $stmt = $this->db->prepare("SELECT * FROM {$table} WHERE {$tenantWhere} {$routerWhere} ORDER BY {$order}");
            $stmt->execute(array_merge($tenantParams, $routerParams));
            return $stmt->fetchAll();
        }
        $stmt = $this->db->prepare("SELECT * FROM {$table} WHERE tenant_id = ? ORDER BY {$order}");
        $stmt->execute([$this->scope->currentTenantId()]);
        return $stmt->fetchAll();
    }
}
