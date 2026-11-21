<?php

namespace App\Controllers;

use App\Core\Csrf;
use App\Core\Request;

class VoucherTemplateController extends BaseController
{
    public function index(): void
    {
        require_super_admin();
        $templates = voucher_templates();
        $this->view('voucher_templates/index', compact('templates'));
    }

    public function create(): void
    {
        require_super_admin();
        $template = [
            'name' => 'Voucher-Pink',
            'columns' => 4,
            'width_mm' => 45,
            'height_mm' => 28,
            'font_size' => 10,
            'content' => default_voucher_template_content(),
        ];
        $action = '/voucher-templates';
        $title = 'Tambah Template Voucher';
        $this->view('voucher_templates/form', compact('template', 'action', 'title'));
    }

    public function store(Request $request): void
    {
        require_super_admin();
        Csrf::verify();
        $templates = voucher_templates();
        $templates[] = $this->templateFromRequest($request);
        $this->saveTemplates($templates);
        flash('success', 'Template voucher ditambahkan.');
        redirect('/voucher-templates');
    }

    public function edit(Request $request): void
    {
        require_super_admin();
        $templates = voucher_templates();
        $index = (int) $request->input('id', -1);
        if (!isset($templates[$index])) {
            http_response_code(404);
            exit('Template tidak ditemukan.');
        }

        $template = $templates[$index];
        $action = '/voucher-templates/update';
        $title = 'Edit Template Voucher';
        $this->view('voucher_templates/form', compact('template', 'action', 'title', 'index'));
    }

    public function update(Request $request): void
    {
        require_super_admin();
        Csrf::verify();
        $templates = voucher_templates();
        $index = (int) $request->input('id', -1);
        if (!isset($templates[$index])) {
            http_response_code(404);
            exit('Template tidak ditemukan.');
        }

        $templates[$index] = $this->templateFromRequest($request);
        $this->saveTemplates($templates);
        flash('success', 'Template voucher diperbarui.');
        redirect('/voucher-templates');
    }

    public function delete(Request $request): void
    {
        require_super_admin();
        Csrf::verify();
        $templates = voucher_templates();
        $index = (int) $request->input('id', -1);
        if (!isset($templates[$index])) {
            http_response_code(404);
            exit('Template tidak ditemukan.');
        }
        if (count($templates) <= 1) {
            flash('error', 'Minimal harus ada satu template voucher.');
            redirect('/voucher-templates');
        }

        array_splice($templates, $index, 1);
        $this->saveTemplates($templates);
        flash('success', 'Template voucher dihapus.');
        redirect('/voucher-templates');
    }

    private function templateFromRequest(Request $request): array
    {
        $name = trim((string) $request->input('name', ''));
        if ($name === '') {
            $name = 'Template Voucher';
        }

        $content = (string) $request->input('content', '');
        if (trim($content) === '') {
            $content = default_voucher_template_content();
        }

        return [
            'name' => substr($name, 0, 80),
            'columns' => max(1, min(8, (int) $request->input('columns', 4))),
            'width_mm' => max(25, min(90, (int) $request->input('width_mm', 45))),
            'height_mm' => max(18, min(70, (int) $request->input('height_mm', 28))),
            'font_size' => max(8, min(16, (int) $request->input('font_size', 10))),
            'content' => $content,
        ];
    }

    private function saveTemplates(array $templates): void
    {
        $json = json_encode(array_values($templates), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $stmt = $this->db->prepare('SELECT id FROM rb_settings WHERE tenant_id IS NULL AND setting_key="voucher_templates" ORDER BY id LIMIT 1');
        $stmt->execute();
        $id = $stmt->fetchColumn();
        if ($id) {
            $this->db->prepare('UPDATE rb_settings SET setting_value=?, updated_at=NOW() WHERE id=?')->execute([$json, $id]);
        } else {
            $this->db->prepare('INSERT INTO rb_settings (tenant_id,setting_key,setting_value,created_at,updated_at) VALUES (NULL,"voucher_templates",?,NOW(),NOW())')->execute([$json]);
        }
    }
}
