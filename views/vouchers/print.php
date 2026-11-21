<style>
  body,
  body.theme-dark {
    background: #ffffff !important;
    color: #0f172a !important;
  }
  body.theme-dark .no-print,
  body.theme-dark .sheet {
    background: #ffffff !important;
    color: #0f172a !important;
  }
  body.theme-dark select {
    background-color: #ffffff !important;
    border-color: #cbd5e1 !important;
    color: #0f172a !important;
  }
  @media print {
    body,
    body.theme-dark {
      background: #ffffff !important;
      color: #0f172a !important;
    }
  }
</style>
<div class="no-print flex items-center justify-between bg-white p-4 text-slate-900">
  <div><strong>Print Batch <?= e($batch['batch_code']) ?></strong></div>
  <div class="flex items-center gap-2">
    <form method="get" action="/vouchers/print" class="flex items-center gap-2">
      <input type="hidden" name="batch_id" value="<?= e($batch['id']) ?>">
      <select name="template" class="rounded-lg border border-slate-300 px-3 py-2 text-sm" onchange="this.form.submit()">
        <?php foreach ($templates as $index => $option): ?>
          <option value="<?= e($index) ?>" <?= $index === $templateIndex ? 'selected' : '' ?>><?= e($option['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </form>
    <button onclick="window.print()" class="rounded-lg bg-cyan-600 px-4 py-2 text-sm font-semibold text-white">Print</button>
  </div>
</div>
<style>
  @media print { @page { size: A4; margin: 10mm; } }
  .sheet { display:grid; grid-template-columns:repeat(<?= (int) $template['columns'] ?>, <?= (int) $template['width_mm'] ?>mm); gap:2mm; padding:8mm; }
  .voucher-shell { width:<?= (int) $template['width_mm'] ?>mm; height:<?= (int) $template['height_mm'] ?>mm; box-sizing:border-box; font-size:<?= (int) $template['font_size'] ?>px; overflow:hidden; }
</style>
<div class="sheet">
  <?php foreach ($items as $item): ?>
  <div class="voucher-shell">
    <?= render_voucher_template((string) $template['content'], $item, $batch) ?>
  </div>
  <?php endforeach; ?>
</div>
