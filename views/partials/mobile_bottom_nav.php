<?php
$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$userName = trim((string) (current_user()['name'] ?? ''));
$mainItems = [
    ['Dashboard', '/dashboard', 'layout-dashboard'],
    ['Vouchers', '/vouchers', 'tickets'],
    ['Active Users', '/active', 'activity'],
    ['Reports', '/reports', 'chart-column'],
];
$menuItems = [
    ['Tenants', '/tenants', 'building-2', is_super_admin()],
    ['Users', '/users', 'users', can_manage_users()],
    ['Routers', '/routers', 'router', can_write_data()],
    ['Profiles', '/profiles', 'badge-dollar-sign', can_write_data()],
    ['Generate Voucher', '/vouchers/generate', 'ticket-plus', can_write_data()],
    ['Template Voucher', '/voucher-templates', 'layout-template', is_super_admin()],
    ['Radius Debug', '/radius-debug', 'bug', is_super_admin() || is_technician()],
    ['Database', '/database', 'database-backup', is_super_admin()],
    ['Settings', '/settings', 'settings', can_write_data()],
];
?>
<nav class="mobile-bottom-nav no-print fixed inset-x-0 bottom-0 z-50 border-t border-slate-200 bg-white/95 px-1 pb-[env(safe-area-inset-bottom)] shadow-[0_-10px_30px_rgba(15,23,42,.08)] backdrop-blur lg:hidden">
  <div class="mx-auto grid max-w-lg grid-cols-5">
    <?php foreach ($mainItems as [$label, $url, $icon]): $active = $currentPath === $url || ($url !== '/dashboard' && str_starts_with($currentPath, $url)); ?>
      <a href="<?= e($url) ?>" class="flex min-h-[64px] flex-col items-center justify-center gap-1 px-1 text-[10px] font-semibold <?= $active ? 'text-cyan-700' : 'text-slate-500' ?>">
        <i data-lucide="<?= e($icon) ?>" class="h-5 w-5"></i>
        <span class="leading-tight"><?= e($label) ?></span>
      </a>
    <?php endforeach; ?>
    <details class="mobile-bottom-menu">
      <summary class="flex min-h-[64px] cursor-pointer flex-col items-center justify-center gap-1 px-1 text-[10px] font-semibold text-slate-500 hover:text-cyan-700">
        <i data-lucide="menu" class="h-5 w-5"></i>
        <span class="leading-tight">Menu</span>
      </summary>
      <div class="fixed inset-x-3 bottom-[78px] overflow-hidden rounded-xl border border-slate-200 bg-white shadow-2xl">
        <div class="border-b border-slate-200 px-4 py-3">
          <div class="text-sm font-semibold text-slate-900">Menu</div>
          <?php if ($userName !== ''): ?><div class="text-xs text-slate-500"><?= e($userName) ?></div><?php endif; ?>
        </div>
        <div class="grid max-h-[60vh] grid-cols-2 gap-1 overflow-y-auto p-2">
          <?php foreach ($menuItems as [$label, $url, $icon, $show]): if (!$show) continue; ?>
            <a href="<?= e($url) ?>" class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium text-slate-700 hover:bg-cyan-50 hover:text-cyan-700">
              <i data-lucide="<?= e($icon) ?>" class="h-4 w-4"></i><span><?= e($label) ?></span>
            </a>
          <?php endforeach; ?>
        </div>
        <form method="post" action="/logout" class="border-t border-slate-200 p-2">
          <?= \App\Core\Csrf::field() ?>
          <button class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium text-red-700 hover:bg-red-50">
            <i data-lucide="log-out" class="h-4 w-4"></i><span>Logout</span>
          </button>
        </form>
      </div>
    </details>
  </div>
</nav>
