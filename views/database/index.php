<div class="mb-5 flex items-center justify-between">
  <h1 class="text-2xl font-semibold">Database</h1>
  <div class="flex gap-2"><a href="/database/restore" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold">Restore</a><a href="/database/import" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold">Import</a></div>
</div>
<form method="post" action="/maintenance/backups/create" class="mb-4 grid gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-sm md:grid-cols-5">
  <?= \App\Core\Csrf::field() ?>
  <select name="type" class="rounded-lg border border-slate-300 px-3 py-2 text-sm"><option value="light">Lightweight Backup</option><option value="full">Full Backup</option><option value="app">App Backup</option></select>
  <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="include_radacct" value="1"> Include radacct</label>
  <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="compress" value="1" checked> Gzip</label>
  <button class="rounded-lg bg-cyan-600 px-4 py-2 text-sm font-semibold text-white">Create Backup</button>
</form>
<div class="mb-4 grid gap-3 md:grid-cols-5">
  <?php foreach ($diagnostics as $item): ?>
    <div class="rounded-xl border <?= $item['ok'] ? 'border-emerald-200 bg-emerald-50' : 'border-amber-200 bg-amber-50' ?> p-3">
      <div class="text-xs font-semibold uppercase tracking-wide <?= $item['ok'] ? 'text-emerald-700' : 'text-amber-700' ?>"><?= $item['ok'] ? 'OK' : 'Check' ?></div>
      <div class="mt-1 text-sm font-semibold text-slate-900"><?= e($item['label']) ?></div>
      <div class="mt-1 truncate text-xs text-slate-600" title="<?= e($item['detail']) ?>"><?= e($item['detail']) ?></div>
    </div>
  <?php endforeach; ?>
</div>
<?php require app_path('views/database/backups.php'); ?>
