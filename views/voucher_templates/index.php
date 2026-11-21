<div class="mb-5 flex flex-wrap items-center justify-between gap-3">
  <h1 class="text-2xl font-semibold">Template Voucher</h1>
  <a href="/voucher-templates/create" class="inline-flex items-center gap-2 rounded-lg bg-cyan-600 px-4 py-2 text-sm font-semibold text-white">
    <i data-lucide="plus" class="h-4 w-4"></i> Tambah Template
  </a>
</div>

<div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
  <div class="overflow-x-auto">
    <table class="min-w-full text-sm">
      <thead class="bg-slate-100 text-left text-slate-600">
        <tr>
          <th class="p-3">Nama</th>
          <th class="p-3">Kolom</th>
          <th class="p-3">Ukuran</th>
          <th class="p-3">Font</th>
          <th class="w-40 p-3 text-right">Aksi</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-100">
        <?php foreach ($templates as $index => $template): ?>
          <tr>
            <td class="p-3 font-semibold"><?= e($template['name']) ?></td>
            <td class="p-3"><?= e((string) $template['columns']) ?></td>
            <td class="p-3"><?= e((string) $template['width_mm']) ?> x <?= e((string) $template['height_mm']) ?> mm</td>
            <td class="p-3"><?= e((string) $template['font_size']) ?> px</td>
            <td class="p-3">
              <div class="flex justify-end gap-2">
                <a href="/voucher-templates/edit?id=<?= e((string) $index) ?>" class="grid h-9 w-9 place-items-center rounded-lg border border-slate-300 bg-white text-slate-700 hover:bg-cyan-50 hover:text-cyan-700" title="Edit">
                  <i data-lucide="pencil" class="h-4 w-4"></i>
                </a>
                <form method="post" action="/voucher-templates/delete" onsubmit="return confirm('Hapus template ini?')">
                  <?= \App\Core\Csrf::field() ?>
                  <input type="hidden" name="id" value="<?= e((string) $index) ?>">
                  <button class="grid h-9 w-9 place-items-center rounded-lg border border-slate-300 bg-white text-red-600 hover:bg-red-50" title="Hapus">
                    <i data-lucide="trash-2" class="h-4 w-4"></i>
                  </button>
                </form>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
