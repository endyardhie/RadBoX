<?php $visibleSettings = array_values(array_filter($settings, fn ($setting) => !in_array($setting['setting_key'], ['app_logo_path', 'voucher_templates', 'app_theme'], true))); ?>
<h1 class="mb-5 text-2xl font-semibold">Settings</h1>
<form method="post" action="/settings" class="max-w-3xl space-y-4 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
  <?= \App\Core\Csrf::field() ?>
  <?php if ($tenantId === null): ?>
    <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
      <label class="block text-sm font-medium">Tampilan Aplikasi
        <select name="settings[app_theme]" class="mt-2 w-full rounded-lg border border-slate-300 px-3 py-2">
          <option value="light" <?= ($settingsByKey['app_theme'] ?? 'light') === 'light' ? 'selected' : '' ?>>Light Mode</option>
          <option value="dark" <?= ($settingsByKey['app_theme'] ?? 'light') === 'dark' ? 'selected' : '' ?>>Dark Mode</option>
        </select>
      </label>
    </div>
  <?php endif; ?>
  <?php foreach ($visibleSettings as $setting): ?>
    <label class="block text-sm font-medium"><?= e($setting['setting_key']) ?>
      <input name="settings[<?= e($setting['setting_key']) ?>]" value="<?= e($setting['setting_value']) ?>" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
    </label>
  <?php endforeach; ?>
  <?php if (!$visibleSettings): ?>
    <?php foreach (($tenantId === null ? ['app_name','currency','default_timezone','footer_text'] : ['business_name','hotspot_name','footer_text','default_voucher_prefix','timezone']) as $key): ?>
      <label class="block text-sm font-medium"><?= e($key) ?><input name="settings[<?= e($key) ?>]" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2"></label>
    <?php endforeach; ?>
  <?php endif; ?>
  <button class="rounded-lg bg-cyan-600 px-4 py-2 text-sm font-semibold text-white">Simpan Settings</button>
</form>
