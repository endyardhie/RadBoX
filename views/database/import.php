<h1 class="mb-5 text-2xl font-semibold">Import SQL</h1>
<form method="post" action="/database/import" enctype="multipart/form-data" class="max-w-2xl space-y-4 rounded-xl border border-amber-200 bg-white p-6 shadow-sm">
  <?= \App\Core\Csrf::field() ?>
  <div class="rounded-lg bg-amber-50 p-4 text-sm text-amber-800">Import SQL akan menjalankan file tambahan ke database saat ini. Auto backup dibuat terlebih dahulu.</div>
  <input type="file" name="sql_file" accept=".sql,.gz" required class="block w-full rounded-lg border border-slate-300 px-3 py-2">
  <label class="block text-sm font-medium">Ketik IMPORT<input name="confirm" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2"></label>
  <button class="rounded-lg bg-amber-600 px-4 py-2 text-sm font-semibold text-white">Import</button>
</form>
