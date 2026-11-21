<?php foreach (flashes() as $flash): ?>
  <?php $color = ['success' => 'emerald', 'error' => 'red', 'warning' => 'amber'][$flash['type']] ?? 'cyan'; ?>
  <div class="mb-4 rounded-lg border border-<?= $color ?>-200 bg-<?= $color ?>-50 px-4 py-3 text-sm text-<?= $color ?>-800">
    <?= e($flash['message']) ?>
  </div>
<?php endforeach; ?>
