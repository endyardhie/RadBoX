<div class="mb-5 flex items-center justify-between">
  <h1 class="text-2xl font-semibold">Users</h1>
  <a href="/users/create" class="rounded-lg bg-cyan-600 px-4 py-2 text-sm font-semibold text-white">Tambah User</a>
</div>
<div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
  <div class="overflow-x-auto"><table class="min-w-[760px] text-sm">
    <thead class="bg-slate-100 text-left text-slate-600"><tr><th class="w-20 p-2"></th><th class="p-3">Nama</th><th class="p-3">Username</th><th class="p-3">Tenant</th><th class="p-3">Role</th><th class="p-3">Status</th></tr></thead>
    <tbody class="divide-y divide-slate-100">
    <?php foreach ($items as $item): $isCurrentUser = (int) $item['id'] === (int) current_user()['id']; ?>
      <tr>
        <td class="p-2">
          <div class="flex items-center gap-1">
            <form method="post" action="/users/delete" onsubmit="return confirm('Nonaktifkan user ini?')"><?= \App\Core\Csrf::field() ?><input type="hidden" name="id" value="<?= e($item['id']) ?>"><button <?= $isCurrentUser ? 'disabled' : '' ?> class="grid h-8 w-8 place-items-center rounded-lg text-red-600 hover:bg-red-50 disabled:cursor-not-allowed disabled:opacity-30" title="Nonaktifkan"><i data-lucide="minus" class="h-4 w-4"></i></button></form>
            <form method="post" action="/users/toggle"><?= \App\Core\Csrf::field() ?><input type="hidden" name="id" value="<?= e($item['id']) ?>"><button <?= $isCurrentUser ? 'disabled' : '' ?> class="grid h-8 w-8 place-items-center rounded-lg <?= $item['status'] === 'active' ? 'text-emerald-700 hover:bg-emerald-50' : 'text-slate-500 hover:bg-slate-100' ?> disabled:cursor-not-allowed disabled:opacity-30" title="<?= $item['status'] === 'active' ? 'Disable user' : 'Enable user' ?>"><i data-lucide="<?= $item['status'] === 'active' ? 'lock-open' : 'lock' ?>" class="h-4 w-4"></i></button></form>
          </div>
        </td>
        <td class="p-3 font-medium"><a class="text-cyan-700 hover:underline" href="/users/edit?id=<?= e($item['id']) ?>"><?= e($item['name']) ?></a></td>
        <td class="p-3"><?= e($item['username']) ?></td>
        <td class="p-3"><?= e($item['tenant_name'] ?? '-') ?></td>
        <td class="p-3"><?= e($item['role']) ?></td>
        <td class="p-3"><?= e($item['status']) ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
</div>
