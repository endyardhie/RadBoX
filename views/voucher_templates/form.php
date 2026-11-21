<div class="mb-5 flex flex-wrap items-center justify-between gap-3">
  <h1 class="text-2xl font-semibold"><?= e($title) ?></h1>
  <a href="/voucher-templates" class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700">
    <i data-lucide="arrow-left" class="h-4 w-4"></i> Kembali
  </a>
</div>

<form method="post" action="<?= e($action) ?>" class="grid gap-5 lg:grid-cols-[320px_1fr]">
  <?= \App\Core\Csrf::field() ?>
  <?php if (isset($index)): ?>
    <input type="hidden" name="id" value="<?= e((string) $index) ?>">
  <?php endif; ?>

  <div class="space-y-4 rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
    <label class="block text-sm font-medium">Nama Template
      <input name="name" value="<?= e($template['name']) ?>" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
    </label>
    <div class="grid grid-cols-2 gap-3">
      <label class="block text-sm font-medium">Kolom
        <input type="number" name="columns" min="1" max="8" value="<?= e((string) $template['columns']) ?>" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
      </label>
      <label class="block text-sm font-medium">Font
        <input type="number" name="font_size" min="8" max="16" value="<?= e((string) $template['font_size']) ?>" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
      </label>
    </div>
    <div class="grid grid-cols-2 gap-3">
      <label class="block text-sm font-medium">Lebar mm
        <input type="number" name="width_mm" min="25" max="90" value="<?= e((string) $template['width_mm']) ?>" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
      </label>
      <label class="block text-sm font-medium">Tinggi mm
        <input type="number" name="height_mm" min="18" max="70" value="<?= e((string) $template['height_mm']) ?>" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
      </label>
    </div>

    <div class="rounded-lg border border-slate-200 bg-slate-50 p-3 text-xs text-slate-600">
      <div class="mb-2 font-semibold text-slate-700">Placeholder</div>
      <div class="flex flex-wrap gap-2">
        <?php foreach (['{{username}}','{{password}}','{{profile_name}}','{{tenant_name}}','{{price}}','{{time_limit}}','{{validity}}','{{batch_code}}','{{comment}}'] as $placeholder): ?>
          <code class="rounded border border-slate-200 bg-white px-2 py-1"><?= e($placeholder) ?></code>
        <?php endforeach; ?>
      </div>
      <div class="mt-2">Format Mikhmon seperti <code>$(username)</code> juga bisa dipakai.</div>
    </div>

    <button class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-cyan-600 px-4 py-2 text-sm font-semibold text-white">
      <i data-lucide="save" class="h-4 w-4"></i> Simpan Template
    </button>
    <button type="button" data-voucher-template-preview class="inline-flex w-full items-center justify-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700">
      <i data-lucide="eye" class="h-4 w-4"></i> Preview 1 Halaman
    </button>
  </div>

  <label class="block rounded-xl border border-slate-200 bg-white p-5 text-sm font-medium shadow-sm">Isi Template
    <textarea name="content" rows="28" spellcheck="false" class="mt-2 min-h-[560px] w-full rounded-lg border border-slate-300 px-3 py-2 font-mono text-sm leading-relaxed"><?= e($template['content']) ?></textarea>
  </label>
</form>

<script>
document.querySelector('[data-voucher-template-preview]')?.addEventListener('click', function () {
  const form = this.closest('form');
  const content = form.querySelector('[name="content"]').value;
  const columns = Math.max(1, Math.min(8, parseInt(form.querySelector('[name="columns"]').value || '4', 10)));
  const widthMm = Math.max(25, Math.min(90, parseInt(form.querySelector('[name="width_mm"]').value || '45', 10)));
  const heightMm = Math.max(18, Math.min(70, parseInt(form.querySelector('[name="height_mm"]').value || '28', 10)));
  const fontSize = Math.max(8, Math.min(16, parseInt(form.querySelector('[name="font_size"]').value || '10', 10)));
  const sample = {
    username: 'RBX001',
    voucher: 'RBX001',
    password: '123456',
    profile: 'Paket 2 Jam',
    profile_name: 'Paket 2 Jam',
    tenant: 'RadBoX Hotspot',
    tenant_name: 'RadBoX Hotspot',
    hotspot_name: 'RadBoX Hotspot',
    batch: 'BATCH-DEMO',
    batch_code: 'BATCH-DEMO',
    price: 'Rp 5.000',
    raw_price: '5000',
    time_limit: '2 jam',
    timelimit: '2 jam',
    validity: '1 hari',
    rate_limit: '5M/5M',
    comment: 'Preview template',
    status: 'generated',
    created_at: '2 Jun 2026 09:36',
    dns_name: 'radbox.local',
    'dns-name': 'radbox.local'
  };
  const escapeHtml = (value) => String(value).replace(/[&<>"']/g, (char) => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[char]));
  const render = (index) => {
    let html = content;
    const localSample = {...sample, username: 'RBX' + String(index).padStart(3, '0'), voucher: 'RBX' + String(index).padStart(3, '0')};
    Object.entries(localSample).forEach(([key, value]) => {
      const escaped = escapeHtml(value);
      html = html.split('{{' + key + '}}').join(escaped);
      html = html.split('$(' + key + ')').join(escaped);
    });
    return '<div class="voucher-shell">' + html + '</div>';
  };
  const total = Math.max(1, Math.ceil((277 / (heightMm + 2)) * columns));
  const vouchers = Array.from({length: total}, (_, index) => render(index + 1)).join('');
  const preview = window.open('', '_blank');
  if (!preview) {
    return;
  }
  preview.document.write(`<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <title>Preview Template Voucher</title>
  <style>
    * { box-sizing: border-box; }
    body { margin: 0; background: #e2e8f0; color: #0f172a; font-family: Arial, sans-serif; }
    .toolbar { position: sticky; top: 0; z-index: 10; display: flex; justify-content: space-between; align-items: center; padding: 10px 14px; background: #fff; border-bottom: 1px solid #cbd5e1; font-size: 13px; }
    .toolbar button { border: 0; border-radius: 8px; background: #0891b2; color: #fff; padding: 8px 12px; font-weight: 700; cursor: pointer; }
    .page { width: 210mm; min-height: 297mm; margin: 12px auto; background: #fff; padding: 8mm; box-shadow: 0 8px 28px rgba(15,23,42,.18); }
    .sheet { display: grid; grid-template-columns: repeat(${columns}, ${widthMm}mm); gap: 2mm; align-content: start; }
    .voucher-shell { width: ${widthMm}mm; height: ${heightMm}mm; overflow: hidden; font-size: ${fontSize}px; }
    @media print {
      @page { size: A4; margin: 10mm; }
      body { background: #fff; }
      .toolbar { display: none; }
      .page { width: auto; min-height: auto; margin: 0; padding: 0; box-shadow: none; }
    }
  </style>
</head>
<body>
  <div class="toolbar"><strong>Preview 1 Halaman Template Voucher</strong><button onclick="window.print()">Print Preview</button></div>
  <div class="page"><div class="sheet">${vouchers}</div></div>
</body>
</html>`);
  preview.document.close();
});
</script>
