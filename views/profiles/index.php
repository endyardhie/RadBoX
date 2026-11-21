<div class="mb-5 flex items-center justify-between">
  <h1 class="text-2xl font-semibold">Profiles Voucher</h1>
  <a href="/profiles/create" class="rounded-lg bg-cyan-600 px-4 py-2 text-sm font-semibold text-white">Tambah Profile</a>
</div>
<div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
  <div class="overflow-x-auto"><table class="min-w-[980px] text-sm">
    <thead class="bg-slate-100 text-left text-slate-600"><tr><th class="w-20 p-2"></th><th class="p-3">Nama</th><th class="p-3">Group</th><th class="p-3">Tenant</th><th class="p-3">Uptime</th><th class="p-3">Validity</th><th class="p-3">Rate</th><th class="p-3">Harga</th><th class="p-3">Status</th></tr></thead>
    <tbody class="divide-y divide-slate-100">
    <?php foreach ($items as $item): ?>
      <tr>
        <td class="p-2">
          <div class="flex items-center gap-1">
            <form method="post" action="/profiles/delete" onsubmit="return confirm('Hapus profile ini?')"><?= \App\Core\Csrf::field() ?><input type="hidden" name="id" value="<?= e($item['id']) ?>"><button class="grid h-8 w-8 place-items-center rounded-lg text-red-600 hover:bg-red-50" title="Hapus"><i data-lucide="minus" class="h-4 w-4"></i></button></form>
            <form method="post" action="/profiles/toggle"><?= \App\Core\Csrf::field() ?><input type="hidden" name="id" value="<?= e($item['id']) ?>"><button class="grid h-8 w-8 place-items-center rounded-lg <?= (int) $item['is_active'] === 1 ? 'text-emerald-700 hover:bg-emerald-50' : 'text-slate-500 hover:bg-slate-100' ?>" title="<?= (int) $item['is_active'] === 1 ? 'Disable profile' : 'Enable profile' ?>"><i data-lucide="<?= (int) $item['is_active'] === 1 ? 'lock-open' : 'lock' ?>" class="h-4 w-4"></i></button></form>
          </div>
        </td>
        <td class="p-3 font-medium"><a class="text-cyan-700 hover:underline" href="/profiles/edit?id=<?= e($item['id']) ?>"><?= e($item['name']) ?></a></td>
        <td class="p-3"><?= e($item['group_name']) ?></td>
        <td class="p-3"><?= e($item['tenant_name']) ?></td>
        <td class="p-3"><?= minutesToHuman($item['total_uptime_minutes']) ?></td>
        <td class="p-3"><?= minutesToHuman($item['validity_minutes']) ?></td>
        <td class="p-3"><?= e($item['rate_limit'] ?: '-') ?></td>
        <td class="p-3"><?= formatRupiah($item['price']) ?></td>
        <td class="p-3"><?= (int) $item['is_active'] === 1 ? 'active' : 'inactive' ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
</div>
