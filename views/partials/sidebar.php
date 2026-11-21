<?php
$appName = app_setting('app_name', 'RadBoX') ?: 'RadBoX';
$appLogo = app_logo_path();
$items = [
    ['Dashboard', '/dashboard', 'layout-dashboard', true],
    ['Tenants', '/tenants', 'building-2', is_super_admin()],
    ['Users', '/users', 'users', can_manage_users()],
    ['Routers', '/routers', 'router', can_write_data()],
    ['Profiles', '/profiles', 'badge-dollar-sign', can_write_data()],
    ['Generate Voucher', '/vouchers/generate', 'ticket-plus', can_write_data()],
    ['Vouchers', '/vouchers', 'tickets', true],
    ['Template Voucher', '/voucher-templates', 'layout-template', is_super_admin()],
    ['Active Users', '/active', 'activity', true],
    ['Radius Debug', '/radius-debug', 'bug', is_super_admin() || is_technician()],
    ['Reports', '/reports', 'chart-column', true],
    ['Database', '/database', 'database-backup', is_super_admin()],
    ['Settings', '/settings', 'settings', can_write_data()],
];
?>
<aside class="fixed inset-y-0 left-0 z-30 hidden w-72 border-r border-slate-200 bg-white lg:block">
  <div class="flex h-16 items-center gap-3 border-b border-slate-200 px-6">
    <img src="<?= e($appLogo) ?>" alt="<?= e($appName) ?>" class="h-9 w-9 rounded-lg object-contain">
    <div class="min-w-0 text-lg font-semibold leading-tight"><?= e($appName) ?></div>
  </div>
  <nav class="space-y-1 px-3 py-4">
    <?php foreach ($items as [$label, $url, $icon, $show]): if (!$show) continue; ?>
      <a href="<?= e($url) ?>" class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium text-slate-700 hover:bg-cyan-50 hover:text-cyan-700">
        <i data-lucide="<?= e($icon) ?>" class="h-4 w-4"></i><span><?= e($label) ?></span>
      </a>
    <?php endforeach; ?>
    <form method="post" action="/logout" class="pt-4">
      <?= \App\Core\Csrf::field() ?>
      <button class="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium text-slate-700 hover:bg-red-50 hover:text-red-700">
        <i data-lucide="log-out" class="h-4 w-4"></i><span>Logout</span>
      </button>
    </form>
  </nav>
</aside>
