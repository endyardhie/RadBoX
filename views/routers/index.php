<div class="mb-5 flex items-center justify-between">
  <h1 class="text-2xl font-semibold">Routers / NAS</h1>
  <?php if (is_super_admin()): ?><a href="/routers/create" class="rounded-lg bg-cyan-600 px-4 py-2 text-sm font-semibold text-white">Tambah Router</a><?php endif; ?>
</div>
<div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
  <table class="min-w-full text-sm">
    <thead class="bg-slate-100 text-left text-slate-600"><tr><?php if (is_super_admin()): ?><th class="w-20 p-2"></th><?php endif; ?><th class="p-3">Nama</th><th class="p-3">Tenant</th><th class="p-3">NAS IP</th><th class="p-3">Prefix</th><th class="p-3">Secret</th><th class="p-3">Status</th></tr></thead>
    <tbody class="divide-y divide-slate-100">
    <?php foreach ($items as $item): ?>
      <tr>
        <?php if (is_super_admin()): ?>
          <td class="p-2">
            <div class="flex items-center gap-1">
              <form method="post" action="/routers/delete" onsubmit="return confirm('Nonaktifkan dan hapus router ini dari tabel NAS?')"><?= \App\Core\Csrf::field() ?><input type="hidden" name="id" value="<?= e($item['id']) ?>"><button class="grid h-8 w-8 place-items-center rounded-lg text-red-600 hover:bg-red-50" title="Nonaktifkan"><i data-lucide="minus" class="h-4 w-4"></i></button></form>
              <form method="post" action="/routers/toggle"><?= \App\Core\Csrf::field() ?><input type="hidden" name="id" value="<?= e($item['id']) ?>"><button class="grid h-8 w-8 place-items-center rounded-lg <?= $item['status'] === 'active' ? 'text-emerald-700 hover:bg-emerald-50' : 'text-slate-500 hover:bg-slate-100' ?>" title="<?= $item['status'] === 'active' ? 'Disable router' : 'Enable router' ?>"><i data-lucide="<?= $item['status'] === 'active' ? 'lock-open' : 'lock' ?>" class="h-4 w-4"></i></button></form>
            </div>
          </td>
        <?php endif; ?>
        <td class="p-3 font-medium"><?php if (is_super_admin()): ?><a class="text-cyan-700 hover:underline" href="/routers/edit?id=<?= e($item['id']) ?>"><?= e($item['name']) ?></a><?php else: ?><?= e($item['name']) ?><?php endif; ?></td>
        <td class="p-3"><?= e($item['tenant_name']) ?></td>
        <td class="p-3"><?= e($item['nas_ip_address']) ?></td>
        <td class="p-3"><?= e($item['voucher_prefix'] ?: '-') ?></td>
        <td class="p-3">••••••••</td>
        <td class="p-3"><?= e($item['status']) ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
