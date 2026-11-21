<?php

use App\Core\Request;
use App\Core\Router;
use App\Core\Session;
use App\Controllers\ActiveController;
use App\Controllers\AuthController;
use App\Controllers\DashboardController;
use App\Controllers\DatabaseController;
use App\Controllers\ProfileController;
use App\Controllers\RadiusDebugController;
use App\Controllers\ReportController;
use App\Controllers\RouterController;
use App\Controllers\SettingController;
use App\Controllers\TenantController;
use App\Controllers\UserController;
use App\Controllers\VoucherController;
use App\Controllers\VoucherTemplateController;

require dirname(__DIR__) . '/app/Helpers/functions.php';
require dirname(__DIR__) . '/app/Helpers/format.php';

spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }
    $path = dirname(__DIR__) . '/app/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
    if (is_file($path)) {
        require $path;
    }
});

Session::start();
apply_app_timezone();

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');
header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
}

$router = new Router();

$router->get('/login', [AuthController::class, 'showLogin']);
$router->post('/login', [AuthController::class, 'login']);
$router->post('/logout', [AuthController::class, 'logout']);

$router->get('/', [DashboardController::class, 'index']);
$router->get('/dashboard', [DashboardController::class, 'index']);
$router->post('/dashboard/sync', [DashboardController::class, 'sync']);

$router->get('/tenants', [TenantController::class, 'index']);
$router->get('/tenants/create', [TenantController::class, 'create']);
$router->post('/tenants', [TenantController::class, 'store']);
$router->get('/tenants/edit', [TenantController::class, 'edit']);
$router->post('/tenants/update', [TenantController::class, 'update']);
$router->post('/tenants/toggle', [TenantController::class, 'toggle']);
$router->post('/tenants/delete', [TenantController::class, 'delete']);

$router->get('/users', [UserController::class, 'index']);
$router->get('/users/create', [UserController::class, 'create']);
$router->post('/users', [UserController::class, 'store']);
$router->get('/users/edit', [UserController::class, 'edit']);
$router->post('/users/update', [UserController::class, 'update']);
$router->post('/users/toggle', [UserController::class, 'toggle']);
$router->post('/users/delete', [UserController::class, 'delete']);

$router->get('/routers', [RouterController::class, 'index']);
$router->get('/routers/create', [RouterController::class, 'create']);
$router->post('/routers', [RouterController::class, 'store']);
$router->get('/routers/edit', [RouterController::class, 'edit']);
$router->post('/routers/update', [RouterController::class, 'update']);
$router->post('/routers/toggle', [RouterController::class, 'toggle']);
$router->post('/routers/delete', [RouterController::class, 'delete']);

$router->get('/profiles', [ProfileController::class, 'index']);
$router->get('/profiles/create', [ProfileController::class, 'create']);
$router->post('/profiles', [ProfileController::class, 'store']);
$router->get('/profiles/edit', [ProfileController::class, 'edit']);
$router->post('/profiles/update', [ProfileController::class, 'update']);
$router->post('/profiles/toggle', [ProfileController::class, 'toggle']);
$router->post('/profiles/delete', [ProfileController::class, 'delete']);

$router->get('/vouchers', [VoucherController::class, 'index']);
$router->get('/vouchers/create', [VoucherController::class, 'create']);
$router->post('/vouchers/manual', [VoucherController::class, 'storeManual']);
$router->get('/vouchers/generate', [VoucherController::class, 'generate']);
$router->post('/vouchers/generate', [VoucherController::class, 'storeGenerate']);
$router->get('/vouchers/batch', [VoucherController::class, 'batch']);
$router->get('/vouchers/print', [VoucherController::class, 'print']);
$router->post('/vouchers/delete', [VoucherController::class, 'delete']);
$router->post('/vouchers/delete-filtered', [VoucherController::class, 'deleteFiltered']);
$router->post('/vouchers/toggle-access', [VoucherController::class, 'toggleAccess']);
$router->post('/vouchers/sync', [VoucherController::class, 'sync']);
$router->get('/voucher-templates', [VoucherTemplateController::class, 'index']);
$router->get('/voucher-templates/create', [VoucherTemplateController::class, 'create']);
$router->post('/voucher-templates', [VoucherTemplateController::class, 'store']);
$router->get('/voucher-templates/edit', [VoucherTemplateController::class, 'edit']);
$router->post('/voucher-templates/update', [VoucherTemplateController::class, 'update']);
$router->post('/voucher-templates/delete', [VoucherTemplateController::class, 'delete']);

$router->get('/active', [ActiveController::class, 'index']);
$router->post('/active/kick', [ActiveController::class, 'kick']);
$router->get('/radius-debug', [RadiusDebugController::class, 'index']);
$router->get('/reports', [ReportController::class, 'index']);
$router->get('/reports/export', [ReportController::class, 'exportPdf']);
$router->post('/reports/delete-month', [ReportController::class, 'deleteMonth']);
$router->get('/settings', [SettingController::class, 'index']);
$router->post('/settings', [SettingController::class, 'update']);

$router->get('/database', [DatabaseController::class, 'index']);
$router->get('/database/backups', [DatabaseController::class, 'backups']);
$router->post('/maintenance/backups/create', [DatabaseController::class, 'backup']);
$router->post('/database/create-backup', [DatabaseController::class, 'backup']);
$router->post('/database/backup', [DatabaseController::class, 'backup']);
$router->get('/database/download', [DatabaseController::class, 'download']);
$router->post('/database/delete-backup', [DatabaseController::class, 'deleteBackup']);
$router->get('/database/restore', [DatabaseController::class, 'restore']);
$router->post('/database/restore', [DatabaseController::class, 'restorePost']);
$router->get('/database/import', [DatabaseController::class, 'import']);
$router->post('/database/import', [DatabaseController::class, 'importPost']);

$router->dispatch(new Request());
