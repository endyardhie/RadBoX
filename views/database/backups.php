<div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
  <table class="min-w-full text-sm">
    <thead class="bg-slate-100 text-left text-slate-600"><tr><th class="p-3">Filename</th><th class="p-3">Size</th><th class="p-3">Created</th><th class="p-3"></th></tr></thead>
    <tbody class="divide-y divide-slate-100">
    <?php foreach ($backups as $file): ?>
      <tr><td class="p-3 font-mono"><?= e($file['filename']) ?></td><td class="p-3"><?= formatBytes($file['size']) ?></td><td class="p-3"><?= e($file['created_at']) ?></td><td class="p-3 text-right"><a class="mr-3 text-cyan-700" href="/database/download?file=<?= urlencode($file['filename']) ?>">Download</a><form class="inline" method="post" action="/database/delete-backup" onsubmit="return confirm('Hapus backup?')"><?= \App\Core\Csrf::field() ?><input type="hidden" name="file" value="<?= e($file['filename']) ?>"><button class="text-red-600">Delete</button></form></td></tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
