<h1 class="mb-5 text-2xl font-semibold">Restore Database</h1>
<form method="post" action="/database/restore" enctype="multipart/form-data" class="max-w-2xl space-y-4 rounded-xl border border-red-200 bg-white p-6 shadow-sm">
  <?= \App\Core\Csrf::field() ?>
  <div class="rounded-lg bg-red-50 p-4 text-sm text-red-800">Restore database akan menimpa data saat ini. Auto backup akan dibuat sebelum restore, tetapi tetap pastikan file backup aman.</div>
  <input type="file" name="sql_file" accept=".sql,.gz" required class="block w-full rounded-lg border border-slate-300 px-3 py-2">
  <label class="block text-sm font-medium">Ketik RESTORE<input name="confirm" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2"></label>
  <button class="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white">Restore</button>
</form>
