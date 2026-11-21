<h1 class="mb-5 text-2xl font-semibold">Tambah User Manual</h1>
<form method="post" action="/vouchers/manual" class="max-w-2xl space-y-4 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
  <?= \App\Core\Csrf::field() ?>
  <div class="grid gap-4 sm:grid-cols-2">
    <label class="block text-sm font-medium">Username
      <input name="username" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 font-mono uppercase">
    </label>
    <label class="block text-sm font-medium">Password
      <input name="password" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 font-mono">
    </label>
  </div>
  <label class="block text-sm font-medium">Profile
    <select name="profile_id" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2"><?php foreach ($profiles as $profile): ?><option value="<?= e($profile['id']) ?>"><?= e($profile['name']) ?> - <?= formatRupiah($profile['price']) ?></option><?php endforeach; ?></select>
  </label>
  <label class="block text-sm font-medium">Router Target
    <select name="router_id" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2"><option value="">Semua router tenant</option><?php foreach ($routers as $router): ?><option value="<?= e($router['id']) ?>"><?= e($router['name']) ?> (<?= e($router['nas_ip_address']) ?>)</option><?php endforeach; ?></select>
  </label>
  <label class="block text-sm font-medium">Catatan
    <textarea name="comment" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2"></textarea>
  </label>
  <button class="inline-flex items-center gap-2 rounded-lg bg-cyan-600 px-4 py-2 text-sm font-semibold text-white"><i data-lucide="plus" class="h-4 w-4"></i> Simpan User</button><a href="/vouchers" class="ml-2 text-sm text-slate-600">Batal</a>
</form>
