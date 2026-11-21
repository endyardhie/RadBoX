<?php
$appName = app_setting('app_name', 'RadBoX') ?: 'RadBoX';
$appLogo = app_logo_path();
?>
<header class="border-b border-slate-200 bg-white">
  <div class="flex h-16 items-center justify-between px-4 sm:px-6 lg:px-8">
    <a href="/dashboard" class="flex min-w-0 items-center gap-2 rounded-lg lg:hidden">
      <img src="<?= e($appLogo) ?>" alt="<?= e($appName) ?>" class="h-10 w-10 shrink-0 rounded-lg object-contain">
      <div class="min-w-0 truncate text-lg font-semibold leading-tight"><?= e($appName) ?></div>
    </a>
    <div class="hidden lg:block"></div>
    <div class="flex items-center gap-2">
      <div class="rounded-full border border-slate-200 px-3 py-1 text-xs text-slate-600 sm:text-sm"><?= date('d M Y H:i') ?></div>
    </div>
  </div>
</header>
