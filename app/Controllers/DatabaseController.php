<?php

namespace App\Controllers;

use App\Core\Csrf;
use App\Core\Request;
use App\Services\DatabaseBackupService;
use App\Services\DatabaseRestoreService;

class DatabaseController extends BaseController
{
    public function index(): void
    {
        require_super_admin();
        $service = new DatabaseBackupService();
        $backups = $service->listBackups();
        $diagnostics = $service->diagnostics();
        $this->view('database/index', compact('backups', 'diagnostics'));
    }

    public function backups(): void
    {
        $this->index();
    }

    public function backup(Request $request): void
    {
        require_super_admin();
        Csrf::verify();
        try {
            $service = new DatabaseBackupService();
            $type = $request->input('type', 'light');
            $compress = (bool) $request->input('compress', true);
            $filename = match ($type) {
                'full' => $service->createFullBackup((bool) $request->input('include_radacct'), $compress),
                'app' => $service->createAppBackup($compress),
                default => $service->createLightBackup($compress),
            };
            audit_log(null, current_user()['id'], 'backup database', $filename);
            flash('success', 'Backup dibuat: ' . $filename);
        } catch (\Throwable $e) {
            flash('error', $e->getMessage());
        }
        redirect('/database');
    }

    public function download(Request $request): void
    {
        require_super_admin();
        $path = (new DatabaseBackupService())->pathFor((string) $request->input('file'));
        audit_log(null, current_user()['id'], 'download backup', basename($path));
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($path) . '"');
        header('Content-Length: ' . filesize($path));
        readfile($path);
        exit;
    }

    public function deleteBackup(Request $request): void
    {
        require_super_admin();
        Csrf::verify();
        (new DatabaseBackupService())->deleteBackup((string) $request->input('file'));
        audit_log(null, current_user()['id'], 'delete backup', (string) $request->input('file'));
        flash('success', 'Backup dihapus.');
        redirect('/database');
    }

    public function restore(): void
    {
        require_super_admin();
        $this->view('database/restore');
    }

    public function restorePost(Request $request): void
    {
        require_super_admin();
        Csrf::verify();
        if ($request->input('confirm') !== 'RESTORE') {
            flash('error', 'Ketik RESTORE untuk konfirmasi.');
            redirect('/database/restore');
        }
        try {
            $service = new DatabaseRestoreService();
            $service->autoBackupBeforeRestore();
            $path = $service->validateUpload($_FILES['sql_file'] ?? []);
            $service->importSql($path);
            audit_log(null, current_user()['id'], 'restore database', basename($path));
            flash('success', 'Restore selesai.');
        } catch (\Throwable $e) {
            flash('error', $e->getMessage());
        }
        redirect('/database');
    }

    public function import(): void
    {
        require_super_admin();
        $this->view('database/import');
    }

    public function importPost(Request $request): void
    {
        require_super_admin();
        Csrf::verify();
        if ($request->input('confirm') !== 'IMPORT') {
            flash('error', 'Ketik IMPORT untuk konfirmasi.');
            redirect('/database/import');
        }
        try {
            $service = new DatabaseRestoreService();
            $service->autoBackupBeforeRestore();
            $path = $service->validateUpload($_FILES['sql_file'] ?? []);
            $service->importSql($path);
            audit_log(null, current_user()['id'], 'import sql', basename($path));
            flash('success', 'Import selesai.');
        } catch (\Throwable $e) {
            flash('error', $e->getMessage());
        }
        redirect('/database');
    }
}
