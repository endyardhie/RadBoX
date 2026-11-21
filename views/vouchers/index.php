<div class="mb-5 flex flex-wrap items-center justify-between gap-3">
  <h1 class="text-2xl font-semibold">Vouchers</h1>
  <div class="flex flex-wrap items-center gap-2">
    <?php if (can_write_data()): ?><a href="/vouchers/create" class="inline-flex items-center gap-2 rounded-lg bg-cyan-600 px-4 py-2 text-sm font-semibold text-white"><i data-lucide="plus" class="h-4 w-4"></i> User Manual</a><?php endif; ?>
    <?php if ($selectedBatchId): ?>
      <form method="get" action="/vouchers/print" target="_blank" class="flex items-center gap-2">
        <input type="hidden" name="batch_id" value="<?= e((string) $selectedBatchId) ?>">
        <select name="template" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm">
          <?php foreach (voucher_templates() as $index => $template): ?>
            <option value="<?= e((string) $index) ?>"><?= e($template['name']) ?></option>
          <?php endforeach; ?>
        </select>
        <button class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold"><i data-lucide="printer" class="h-4 w-4"></i> Print Batch</button>
      </form>
    <?php endif; ?>
    <?php if (can_write_data() && $hasFiltered && $items): ?>
      <form method="post" action="/vouchers/delete-filtered" onsubmit="return confirm('Hapus semua voucher pada hasil filter ini?')">
        <?= \App\Core\Csrf::field() ?>
        <?php foreach (['search', 'tenant_id', 'batch_id', 'router_id', 'profile_id', 'status'] as $field): ?>
          <input type="hidden" name="<?= e($field) ?>" value="<?= e((string) ($_GET[$field] ?? '')) ?>">
        <?php endforeach; ?>
        <button class="inline-flex items-center gap-2 rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white"><i data-lucide="trash-2" class="h-4 w-4"></i> Hapus Hasil Filter</button>
      </form>
    <?php endif; ?>
    <?php if (can_write_data()): ?><form method="post" action="/vouchers/sync"><?= \App\Core\Csrf::field() ?><button class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold">Sync Voucher Status</button></form><?php endif; ?>
  </div>
</div>
<form method="get" action="/vouchers" class="mb-4 grid grid-cols-2 gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:grid-cols-3 xl:grid-cols-7">
  <input name="search" value="<?= e($_GET['search'] ?? '') ?>" placeholder="Cari username" class="min-w-0 rounded-lg border border-slate-300 px-3 py-2 text-sm">
  <?php if (is_super_admin()): ?><select name="tenant_id" class="min-w-0 rounded-lg border border-slate-300 px-3 py-2 text-sm"><option value="">Semua tenant</option><?php foreach ($tenants as $tenant): ?><option value="<?= e($tenant['id']) ?>" <?= ($_GET['tenant_id'] ?? '')==$tenant['id']?'selected':'' ?>><?= e($tenant['name']) ?></option><?php endforeach; ?></select><?php endif; ?>
  <select name="batch_id" class="min-w-0 rounded-lg border border-slate-300 px-3 py-2 text-sm"><option value="">Semua batch</option><?php foreach ($batches as $batch): ?><option value="<?= e($batch['id']) ?>" <?= ($_GET['batch_id'] ?? '')==$batch['id']?'selected':'' ?>><?= e($batch['batch_code']) ?><?= is_super_admin() ? ' - ' . e($batch['tenant_name']) : '' ?></option><?php endforeach; ?></select>
  <select name="router_id" class="min-w-0 rounded-lg border border-slate-300 px-3 py-2 text-sm"><option value="">Semua router</option><?php foreach ($routers as $router): ?><option value="<?= e($router['id']) ?>" <?= ($_GET['router_id'] ?? '')==$router['id']?'selected':'' ?>><?= e($router['name']) ?></option><?php endforeach; ?></select>
  <select name="profile_id" class="min-w-0 rounded-lg border border-slate-300 px-3 py-2 text-sm"><option value="">Semua profile</option><?php foreach ($profiles as $profile): ?><option value="<?= e($profile['id']) ?>" <?= ($_GET['profile_id'] ?? '')==$profile['id']?'selected':'' ?>><?= e($profile['name']) ?></option><?php endforeach; ?></select>
  <select name="status" class="min-w-0 rounded-lg border border-slate-300 px-3 py-2 text-sm"><option value="">Semua status</option><?php foreach (['generated','active','expired','deleted'] as $status): ?><option value="<?= $status ?>" <?= ($_GET['status'] ?? '')===$status?'selected':'' ?>><?= $status ?></option><?php endforeach; ?></select>
  <button class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white">Filter</button>
</form>
<div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
  <div class="overflow-x-auto"><table class="min-w-full text-sm">
    <thead class="bg-slate-100 text-left text-slate-600"><tr><?php if (can_write_data()): ?><th class="w-20 p-2"></th><?php endif; ?><th class="p-3">Username</th><th class="p-3">Batch</th><th class="p-3">Profile</th><th class="p-3">Harga</th><th class="p-3">Router</th><?php if (is_super_admin()): ?><th class="p-3">Tenant</th><?php endif; ?><th class="p-3">Used</th><th class="p-3">Remaining</th><th class="p-3">Expires</th><th class="p-3">Status</th></tr></thead>
    <tbody class="divide-y divide-slate-100">
    <?php foreach ($items as $item): $remaining = (int)$item['total_uptime_limit_seconds'] > 0 ? max(0, (int)$item['total_uptime_limit_seconds'] - (int)$item['total_uptime_used_seconds']) : null; $accessEnabled = !empty($item['access_username']); ?>
      <tr>
        <?php if (can_write_data()): ?>
          <td class="p-2">
            <div class="flex items-center gap-1">
              <form method="post" action="/vouchers/delete" onsubmit="return confirm('Hapus voucher ini?')"><?= \App\Core\Csrf::field() ?><input type="hidden" name="id" value="<?= e($item['id']) ?>"><button class="grid h-8 w-8 place-items-center rounded-lg text-red-600 hover:bg-red-50" title="Hapus"><i data-lucide="minus" class="h-4 w-4"></i></button></form>
              <form method="post" action="/vouchers/toggle-access"><?= \App\Core\Csrf::field() ?><input type="hidden" name="id" value="<?= e($item['id']) ?>"><button class="grid h-8 w-8 place-items-center rounded-lg <?= $accessEnabled ? 'text-emerald-700 hover:bg-emerald-50' : 'text-slate-500 hover:bg-slate-100' ?>" title="<?= $accessEnabled ? 'Disable voucher' : 'Enable voucher' ?>"><i data-lucide="<?= $accessEnabled ? 'lock-open' : 'lock' ?>" class="h-4 w-4"></i></button></form>
            </div>
          </td>
        <?php endif; ?>
        <td class="p-3 font-mono font-semibold"><?= e($item['username']) ?></td>
        <td class="p-3"><a class="text-cyan-700" href="/vouchers?batch_id=<?= e($item['batch_id']) ?>"><?= e($item['batch_code'] ?? '-') ?></a></td>
        <td class="p-3"><?= e($item['profile_name']) ?></td>
        <td class="p-3"><?= formatRupiah($item['price']) ?></td>
        <td class="p-3"><?= e($item['router_name'] ?? 'Semua') ?></td>
        <?php if (is_super_admin()): ?><td class="p-3"><?= e($item['tenant_name']) ?></td><?php endif; ?>
        <td class="p-3"><?= secondsToHuman($item['total_uptime_used_seconds']) ?></td>
        <td class="p-3"><?= $remaining === null ? 'Selamanya' : secondsToHuman($remaining) ?></td>
        <td class="p-3"><?= $item['expires_at'] ? formatDateTime($item['expires_at']) : 'Selamanya' ?></td>
        <td class="p-3"><?= e($item['status']) ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
</div>
<?php require app_path('views/partials/pagination.php'); ?>
