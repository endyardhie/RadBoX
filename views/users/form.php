<label class="block text-sm font-medium">Nama<input name="name" value="<?= e($item['name']) ?>" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2"></label>
<label class="block text-sm font-medium">Username<input name="username" value="<?= e($item['username']) ?>" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2"></label>
<label class="block text-sm font-medium">Password<input name="password" type="password" <?= $passwordRequired ? 'required' : '' ?> class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2" placeholder="<?= $passwordRequired ? '' : 'Kosongkan jika tidak diubah' ?>"></label>
<?php if (is_super_admin()): ?>
  <label class="block text-sm font-medium">Tenant
    <select name="tenant_id" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2"><option value="">Super Admin tanpa tenant</option><?php foreach ($tenants as $tenant): ?><option value="<?= e($tenant['id']) ?>" <?= (string)$item['tenant_id']===(string)$tenant['id']?'selected':'' ?>><?= e($tenant['name']) ?></option><?php endforeach; ?></select>
  </label>
  <label class="block text-sm font-medium">Role<select name="role" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2"><?php foreach (['super_admin','tenant_owner','technician'] as $role): ?><option value="<?= $role ?>" <?= $item['role']===$role?'selected':'' ?>><?= $role ?></option><?php endforeach; ?></select></label>
<?php else: ?>
  <input type="hidden" name="tenant_id" value="<?= e((string) current_user()['tenant_id']) ?>">
  <input type="hidden" name="role" value="technician">
  <div class="rounded-lg border border-slate-200 bg-slate-50 p-3 text-sm text-slate-600">Tenant owner hanya dapat membuat dan mengedit user technician.</div>
<?php endif; ?>
<label class="block text-sm font-medium">Status<select name="status" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2"><?php foreach (['active','inactive'] as $status): ?><option value="<?= $status ?>" <?= $item['status']===$status?'selected':'' ?>><?= $status ?></option><?php endforeach; ?></select></label>

<div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
  <div class="text-sm font-medium">Akses Router Technician</div>
  <div class="mt-2 max-h-64 space-y-2 overflow-y-auto rounded-lg border border-slate-200 bg-white p-3">
    <?php foreach (($routers ?? []) as $router): ?>
      <label class="flex items-center gap-2 text-sm">
        <input type="checkbox" name="router_ids[]" value="<?= e($router['id']) ?>" <?= in_array((int) $router['id'], $assignedRouterIds ?? [], true) ? 'checked' : '' ?> class="rounded border-slate-300">
        <span><?= e($router['name']) ?><?= is_super_admin() ? ' - ' . e($router['tenant_name'] ?? '') : '' ?></span>
      </label>
    <?php endforeach; ?>
    <?php if (empty($routers)): ?>
      <div class="text-sm text-slate-500">Belum ada router yang bisa dipilih.</div>
    <?php endif; ?>
  </div>
  <p class="mt-2 text-xs text-slate-500">Pilihan ini hanya dipakai saat role user adalah technician.</p>
</div>
<button class="rounded-lg bg-cyan-600 px-4 py-2 text-sm font-semibold text-white">Simpan</button><a href="/users" class="ml-2 text-sm text-slate-600">Batal</a>
