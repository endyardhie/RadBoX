<div class="mb-5 flex items-center justify-between">
  <h1 class="text-2xl font-semibold">Tenants</h1>
  <a href="/tenants/create" class="rounded-lg bg-cyan-600 px-4 py-2 text-sm font-semibold text-white">Tambah Tenant</a>
</div>
<div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
  <table class="min-w-full text-sm">
    <thead class="bg-slate-100 text-left text-slate-600"><tr><th class="w-20 p-2"></th><th class="p-3">Nama</th><th class="p-3">Code</th><th class="p-3">Status</th></tr></thead>
    <tbody class="divide-y divide-slate-100">
    <?php foreach ($items as $item): ?>
      <tr>
        <td class="p-2">
          <div class="flex items-center gap-1">
            <form method="post" action="/tenants/delete" onsubmit="return confirm('Nonaktifkan tenant ini?')"><?= \App\Core\Csrf::field() ?><input type="hidden" name="id" value="<?= e($item['id']) ?>"><button class="grid h-8 w-8 place-items-center rounded-lg text-red-600 hover:bg-red-50" title="Nonaktifkan"><i data-lucide="minus" class="h-4 w-4"></i></button></form>
            <form method="post" action="/tenants/toggle"><?= \App\Core\Csrf::field() ?><input type="hidden" name="id" value="<?= e($item['id']) ?>"><button class="grid h-8 w-8 place-items-center rounded-lg <?= $item['status'] === 'active' ? 'text-emerald-700 hover:bg-emerald-50' : 'text-slate-500 hover:bg-slate-100' ?>" title="<?= $item['status'] === 'active' ? 'Disable tenant' : 'Enable tenant' ?>"><i data-lucide="<?= $item['status'] === 'active' ? 'lock-open' : 'lock' ?>" class="h-4 w-4"></i></button></form>
          </div>
        </td>
        <td class="p-3 font-medium"><a class="text-cyan-700 hover:underline" href="/tenants/edit?id=<?= e($item['id']) ?>"><?= e($item['name']) ?></a></td>
        <td class="p-3"><?= e($item['code']) ?></td>
        <td class="p-3"><?= e($item['status']) ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
