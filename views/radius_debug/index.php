<div class="mb-5 flex items-center justify-between">
  <div>
    <h1 class="text-2xl font-semibold">Radius Debug</h1>
    <p class="text-sm text-slate-500">Verifikasi RadBoX membaca dan menulis ke database FreeRADIUS yang sama.</p>
  </div>
  <a href="/radius-debug" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold">Refresh</a>
</div>

<?php if (!$debug['connected']): ?>
  <div class="rounded-xl border border-red-200 bg-red-50 p-5 text-sm text-red-800">
    Koneksi database gagal: <?= e($debug['error']) ?>
  </div>
<?php else: ?>
  <div class="mb-4 grid gap-4 md:grid-cols-3">
    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
      <div class="text-sm text-slate-500">Database</div>
      <div class="mt-2 font-semibold"><?= e($debug['database_name']) ?></div>
    </div>
    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
      <div class="text-sm text-slate-500">DB Server</div>
      <div class="mt-2 font-semibold"><?= e($debug['server_version']) ?></div>
    </div>
    <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-5 shadow-sm">
      <div class="text-sm text-emerald-700">Connection</div>
      <div class="mt-2 font-semibold text-emerald-900">OK</div>
    </div>
  </div>

  <div class="mb-4 grid gap-4 lg:grid-cols-2">
    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
      <h2 class="mb-3 font-semibold">Tabel FreeRADIUS</h2>
      <div class="grid gap-2 sm:grid-cols-2">
        <?php foreach ($debug['tables'] as $table => $exists): ?>
          <div class="flex items-center justify-between rounded-lg bg-slate-50 px-3 py-2 text-sm">
            <span class="font-mono"><?= e($table) ?></span>
            <span class="<?= $exists ? 'text-emerald-700' : 'text-red-700' ?>"><?= $exists ? 'tersedia' : 'missing' ?></span>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
      <h2 class="mb-3 font-semibold">Jumlah Data</h2>
      <?php foreach ($debug['counts'] as $table => $count): ?>
        <div class="flex items-center justify-between border-t border-slate-100 py-2 text-sm">
          <span class="font-mono"><?= e($table) ?></span>
          <strong><?= $count === null ? 'table missing' : e((string) $count) ?></strong>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="grid gap-4 xl:grid-cols-2">
    <?php foreach ($debug['latest'] as $label => $rows): ?>
      <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 px-5 py-4 font-semibold"><?= e($label) ?></div>
        <div class="overflow-x-auto">
          <table class="min-w-full text-xs">
            <tbody class="divide-y divide-slate-100">
            <?php foreach ($rows as $row): ?>
              <tr>
                <?php foreach ($row as $value): ?>
                  <td class="whitespace-nowrap px-3 py-2"><?= e((string) $value) ?></td>
                <?php endforeach; ?>
              </tr>
            <?php endforeach; ?>
            <?php if (!$rows): ?>
              <tr><td class="px-3 py-4 text-slate-500">Tidak ada data.</td></tr>
            <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
