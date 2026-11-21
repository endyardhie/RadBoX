<?php
$stats = [
    ['Tenants', is_super_admin() ? $count('rb_tenants') : 1, 'building-2', 'bg-cyan-50 text-cyan-700'],
    ['Routers', $count('rb_routers'), 'router', 'bg-emerald-50 text-emerald-700'],
    ['Vouchers', $count('rb_vouchers'), 'tickets', 'bg-amber-50 text-amber-700'],
    ['Active Users', $activeUsersCount, 'activity', 'bg-violet-50 text-violet-700'],
];
$todayRevenueLabel = is_super_admin() ? 'Pendapatan Semua Tenant Hari Ini' : 'Pendapatan Hari Ini';
$monthRevenueLabel = is_super_admin() ? 'Pendapatan Semua Tenant Bulan Ini' : 'Pendapatan Bulan Ini';
?>
<section class="space-y-6">
  <div class="flex flex-wrap items-center justify-between gap-3">
    <div>
      <h1 class="text-2xl font-semibold">Dashboard</h1>
    </div>
    <?php if (can_write_data()): ?>
      <form method="post" action="/dashboard/sync">
        <?= \App\Core\Csrf::field() ?>
        <button class="inline-flex items-center gap-2 rounded-lg bg-cyan-600 px-4 py-2 text-sm font-semibold text-white hover:bg-cyan-700"><i data-lucide="refresh-cw" class="h-4 w-4"></i> Sync Voucher Status</button>
      </form>
    <?php endif; ?>
  </div>
  <div class="grid grid-cols-2 gap-3 xl:grid-cols-4">
    <?php foreach ($stats as [$label, $value, $icon, $tone]): ?>
      <div class="rounded-xl border border-slate-200 bg-white p-3 shadow-sm sm:p-4">
        <div class="flex items-center gap-2">
          <span class="grid h-8 w-8 place-items-center rounded-lg <?= e($tone) ?>"><i data-lucide="<?= e($icon) ?>" class="h-4 w-4"></i></span>
          <div class="min-w-0">
            <div class="truncate text-xs font-medium text-slate-500 sm:text-sm"><?= e($label) ?></div>
            <div class="text-xl font-semibold leading-tight sm:text-2xl"><?= e((string) $value) ?></div>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
  <div class="grid grid-cols-2 gap-3">
    <div class="rounded-xl border border-slate-200 bg-white p-3 shadow-sm sm:p-4">
      <div class="text-xs font-medium text-slate-500 sm:text-sm"><?= e($todayRevenueLabel) ?></div>
      <div class="mt-1 text-lg font-semibold leading-tight sm:text-2xl"><?= formatRupiah($revenueToday) ?></div>
    </div>
    <div class="rounded-xl border border-slate-200 bg-white p-3 shadow-sm sm:p-4">
      <div class="text-xs font-medium text-slate-500 sm:text-sm"><?= e($monthRevenueLabel) ?></div>
      <div class="mt-1 text-lg font-semibold leading-tight sm:text-2xl"><?= formatRupiah($revenueMonth) ?></div>
    </div>
  </div>
  <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
    <div class="border-b border-slate-200 px-5 py-4 font-semibold">Log Hotspot</div>
    <div class="overflow-x-auto">
      <table class="w-full table-fixed text-sm">
        <colgroup>
          <col class="w-1/3">
          <col class="w-1/3">
          <col class="w-1/3">
        </colgroup>
        <thead class="bg-slate-100 text-left text-slate-600">
          <tr>
            <th class="px-2 py-2 text-[10px] sm:px-5 sm:py-3 sm:text-sm">Time</th>
            <th class="px-2 py-2 text-[10px] sm:px-5 sm:py-3 sm:text-sm">User (IP)</th>
            <th class="px-2 py-2 text-[10px] sm:px-5 sm:py-3 sm:text-sm">Messages</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
        <?php foreach ($hotspotLogs as $row): ?>
          <?php
            $occurredAt = $row['occurred_at'] ?? null;
            $message = (string) ($row['message'] ?? '-');
            $messageParts = preg_match('/^([^:]+:)\s*(.+)$/', $message, $matches) ? [$matches[1], $matches[2]] : [$message, ''];
          ?>
          <tr>
            <td class="px-2 py-2 text-[11px] leading-tight sm:px-5 sm:py-3 sm:text-sm">
              <span class="block whitespace-nowrap"><?= $occurredAt ? e(date('j M Y', strtotime($occurredAt))) : '-' ?></span>
              <span class="block whitespace-nowrap text-slate-500"><?= $occurredAt ? e(date('h:i A', strtotime($occurredAt))) : '' ?></span>
            </td>
            <td class="px-2 py-2 text-[11px] font-medium leading-tight sm:px-5 sm:py-3 sm:text-sm">
              <span class="block truncate"><?= e($row['username'] ?? '-') ?></span>
              <span class="block truncate font-normal text-slate-500">(<?= e($row['ip_address'] ?: '-') ?>)</span>
            </td>
            <td class="px-2 py-2 text-[11px] leading-tight sm:px-5 sm:py-3 sm:text-sm">
              <span class="block truncate"><?= e($messageParts[0]) ?></span>
              <?php if ($messageParts[1] !== ''): ?><span class="block truncate text-slate-500"><?= e($messageParts[1]) ?></span><?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$hotspotLogs): ?>
          <tr><td colspan="3" class="px-5 py-6 text-center text-slate-500">Belum ada log hotspot.</td></tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</section>
