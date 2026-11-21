<?php

namespace App\Controllers;

use App\Core\Csrf;
use App\Core\Request;

class SettingController extends BaseController
{
    public function index(): void
    {
        require_not_technician();
        $tenantId = is_super_admin() ? null : $this->scope->currentTenantId();
        $stmt = $this->db->prepare('SELECT * FROM rb_settings WHERE tenant_id ' . ($tenantId ? '= ?' : 'IS NULL') . ' ORDER BY setting_key, id');
        $stmt->execute($tenantId ? [$tenantId] : []);
        $settings = $stmt->fetchAll();
        $settingsByKey = [];
        foreach ($settings as $setting) {
            $settingsByKey[$setting['setting_key']] = $setting['setting_value'];
        }
        $this->view('settings/index', compact('settings', 'settingsByKey', 'tenantId'));
    }

    public function update(Request $request): void
    {
        require_not_technician();
        Csrf::verify();
        $tenantId = is_super_admin() ? null : $this->scope->currentTenantId();
        foreach (($request->input('settings', []) ?: []) as $key => $value) {
            if (in_array($key, ['app_logo_path', 'voucher_templates'], true)) {
                continue;
            }
            $this->upsertSetting($tenantId, (string) $key, (string) $value);
        }
        flash('success', 'Settings disimpan.');
        redirect('/settings');
    }

    private function upsertSetting(?int $tenantId, string $key, string $value): void
    {
        if ($tenantId === null) {
            $stmt = $this->db->prepare('SELECT id FROM rb_settings WHERE tenant_id IS NULL AND setting_key = ? ORDER BY id LIMIT 1');
            $stmt->execute([$key]);
            $id = $stmt->fetchColumn();
            if ($id) {
                $this->db->prepare('UPDATE rb_settings SET setting_value=?, updated_at=NOW() WHERE id=?')->execute([$value, $id]);
            } else {
                $this->db->prepare('INSERT INTO rb_settings (tenant_id,setting_key,setting_value,created_at,updated_at) VALUES (NULL,?,?,NOW(),NOW())')->execute([$key, $value]);
            }
            return;
        }

        $stmt = $this->db->prepare('INSERT INTO rb_settings (tenant_id,setting_key,setting_value,created_at,updated_at) VALUES (?,?,?,NOW(),NOW()) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value), updated_at=NOW()');
        $stmt->execute([$tenantId, $key, $value]);
    }
}
