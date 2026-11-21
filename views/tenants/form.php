<label class="block text-sm font-medium">Nama
  <input name="name" value="<?= e($item['name']) ?>" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
</label>
<label class="block text-sm font-medium">Code
  <input name="code" value="<?= e($item['code']) ?>" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 uppercase">
</label>
<label class="block text-sm font-medium">Status
  <select name="status" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
    <?php foreach (['active','inactive','suspended'] as $status): ?><option value="<?= $status ?>" <?= $item['status']===$status?'selected':'' ?>><?= $status ?></option><?php endforeach; ?>
  </select>
</label>
<label class="block text-sm font-medium">Notes
  <textarea name="notes" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2"><?= e($item['notes'] ?? '') ?></textarea>
</label>
<button class="rounded-lg bg-cyan-600 px-4 py-2 text-sm font-semibold text-white">Simpan</button>
<a href="/tenants" class="ml-2 text-sm text-slate-600">Batal</a>
