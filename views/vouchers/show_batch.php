<div class="mb-5 flex flex-wrap items-center justify-between gap-3">
  <div><h1 class="text-2xl font-semibold">Batch <?= e($batch['batch_code']) ?></h1><p class="text-sm text-slate-500"><?= e($batch['tenant_name']) ?> · <?= e($batch['profile_name']) ?></p></div>
  <form method="get" action="/vouchers/print" target="_blank" class="flex items-center gap-2">
    <input type="hidden" name="batch_id" value="<?= e($batch['id']) ?>">
    <select name="template" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
      <?php foreach (voucher_templates() as $index => $template): ?>
        <option value="<?= e($index) ?>"><?= e($template['name']) ?></option>
      <?php endforeach; ?>
    </select>
    <button class="rounded-lg bg-cyan-600 px-4 py-2 text-sm font-semibold text-white">Print</button>
  </form>
</div>
<div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
  <table class="min-w-full text-sm">
    <thead class="bg-slate-100 text-left text-slate-600"><tr><th class="p-3">Username</th><th class="p-3">Password</th><th class="p-3">Profile</th><th class="p-3">Harga</th><th class="p-3">Status</th></tr></thead>
    <tbody class="divide-y divide-slate-100"><?php foreach ($items as $item): ?><tr><td class="p-3 font-mono font-semibold"><?= e($item['username']) ?></td><td class="p-3 font-mono"><?= e($item['password']) ?></td><td class="p-3"><?= e($item['profile_name']) ?></td><td class="p-3"><?= formatRupiah($item['price']) ?></td><td class="p-3"><?= e($item['status']) ?></td></tr><?php endforeach; ?></tbody>
  </table>
</div>
