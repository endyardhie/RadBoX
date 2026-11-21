<div class="mb-5 flex flex-wrap items-center justify-between gap-3">
  <div>
    <h1 class="text-2xl font-semibold">Reports Penjualan Voucher</h1>
    <?php if ($hasFiltered): ?><p class="text-sm text-slate-500">Periode <?= e($dateFrom) ?> sampai <?= e($dateTo) ?></p><?php endif; ?>
  </div>
  <?php if ($hasFiltered): ?>
    <div class="flex flex-wrap gap-2">
      <a href="/reports/export?<?= e(http_build_query(array_merge($_GET, ['filtered' => 1]))) ?>" class="inline-flex items-center gap-2 rounded-lg bg-cyan-600 px-4 py-2 text-sm font-semibold text-white"><i data-lucide="file-down" class="h-4 w-4"></i> Export PDF</a>
      <?php if (can_write_data()): ?>
        <form method="post" action="/reports/delete-month" onsubmit="return confirm('Hapus data laporan <?= e($deleteLabel) ?>?')">
          <?= \App\Core\Csrf::field() ?>
          <input type="hidden" name="delete_month" value="<?= e($filters['year'] . '-' . $filters['month']) ?>">
          <?php if (is_super_admin()): ?><input type="hidden" name="tenant_id" value="<?= e((string) ($filters['tenant_id'] ?? '')) ?>"><?php endif; ?>
          <button class="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white">Hapus Data <?= e($deleteLabel) ?></button>
        </form>
      <?php endif; ?>
    </div>
  <?php endif; ?>
</div>

<form method="get" action="/reports" class="mb-4 grid grid-cols-2 gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:grid-cols-3 xl:grid-cols-6">
  <input type="hidden" name="filtered" value="1">
  <label class="block text-sm font-medium">Tanggal
    <select name="date" class="mt-1 w-full min-w-0 rounded-lg border border-slate-300 px-3 py-2 text-sm">
      <option value="">Semua tanggal</option>
      <?php for ($day = 1; $day <= 31; $day++): ?>
        <option value="<?= e($day) ?>" <?= (string) $filters['date'] === (string) $day ? 'selected' : '' ?>><?= e($day) ?></option>
      <?php endfor; ?>
    </select>
  </label>
  <label class="block text-sm font-medium">Bulan
    <select name="month" class="mt-1 w-full min-w-0 rounded-lg border border-slate-300 px-3 py-2 text-sm">
      <?php foreach ([1=>'Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'] as $number => $name): ?>
        <option value="<?= sprintf('%02d', $number) ?>" <?= (int) $filters['month_number'] === $number ? 'selected' : '' ?>><?= e($name) ?></option>
      <?php endforeach; ?>
    </select>
  </label>
  <label class="block text-sm font-medium">Tahun
    <input type="number" name="year" min="2000" max="2100" value="<?= e((string) $filters['year']) ?>" class="mt-1 w-full min-w-0 rounded-lg border border-slate-300 px-3 py-2 text-sm">
  </label>
  <?php if (is_super_admin()): ?>
    <label class="block text-sm font-medium">Tenant
      <select name="tenant_id" class="mt-1 w-full min-w-0 rounded-lg border border-slate-300 px-3 py-2 text-sm"><option value="">Semua tenant</option><?php foreach ($tenants as $tenant): ?><option value="<?= e($tenant['id']) ?>" <?= (string)($filters['tenant_id'] ?? '') === (string)$tenant['id'] ? 'selected' : '' ?>><?= e($tenant['name']) ?></option><?php endforeach; ?></select>
    </label>
  <?php endif; ?>
  <label class="block text-sm font-medium">Router
    <select name="router_id" class="mt-1 w-full min-w-0 rounded-lg border border-slate-300 px-3 py-2 text-sm"><option value="">Semua router</option><?php foreach ($routers as $router): ?><option value="<?= e($router['id']) ?>" <?= (string)($filters['router_id'] ?? '') === (string)$router['id'] ? 'selected' : '' ?>><?= e($router['name']) ?></option><?php endforeach; ?></select>
  </label>
  <button class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white xl:self-end">Filter</button>
</form>

<?php if ($hasFiltered): ?>
  <div class="mb-4 grid grid-cols-2 gap-3 md:gap-4">
    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm"><div class="text-sm text-slate-500">Voucher Terjual</div><div class="mt-2 text-3xl font-semibold"><?= e((string) $summary['voucher_count']) ?></div></div>
    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm"><div class="text-sm text-slate-500">Total Penjualan</div><div class="mt-2 text-3xl font-semibold"><?= formatRupiah($summary['revenue']) ?></div></div>
  </div>

  <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
    <div class="overflow-x-auto"><table class="min-w-full text-sm">
      <thead class="bg-slate-100 text-left text-slate-600"><tr><th class="p-3">Tanggal</th><?php if (is_super_admin()): ?><th class="p-3">Tenant</th><?php endif; ?><th class="p-3">Batch</th><th class="p-3">Router</th><th class="p-3">Voucher</th><th class="p-3">Profile</th><th class="p-3">Harga</th></tr></thead>
      <tbody class="divide-y divide-slate-100">
        <?php foreach ($transactions as $row): ?>
          <tr><td class="p-3"><?= formatDateTime($row['sold_at'] ?? $row['created_at']) ?></td><?php if (is_super_admin()): ?><td class="p-3"><?= e($row['tenant_name'] ?? '-') ?></td><?php endif; ?><td class="p-3"><?= e($row['batch_code'] ?? '-') ?></td><td class="p-3"><?= e($row['router_name'] ?? 'Semua') ?></td><td class="p-3 font-mono"><?= e($row['username']) ?></td><td class="p-3"><?= e($row['profile_name'] ?? '-') ?></td><td class="p-3"><?= formatRupiah($row['price']) ?></td></tr>
        <?php endforeach; ?>
        <?php if (!$transactions): ?><tr><td colspan="<?= is_super_admin() ? 7 : 6 ?>" class="p-6 text-center text-slate-500">Tidak ada transaksi pada filter ini.</td></tr><?php endif; ?>
      </tbody>
    </table></div>
  </div>
<?php endif; ?>
