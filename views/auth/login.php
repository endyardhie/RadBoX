<?php
$appName = app_setting('app_name', 'RadBoX') ?: 'RadBoX';
$appLogo = app_logo_path();
?>
<div class="grid min-h-screen place-items-center px-4 pb-16">
  <div class="w-full max-w-md rounded-xl border border-slate-200 bg-white p-8 shadow-sm">
    <div class="mb-8 text-center">
      <img src="<?= e($appLogo) ?>" alt="<?= e($appName) ?>" class="mx-auto mb-4 h-24 w-24 rounded-xl object-contain">
      <h1 class="text-3xl font-semibold"><?= e($appName) ?></h1>
      <p class="mt-1 text-base text-slate-500">Hotspot Radius</p>
    </div>
    <?php require app_path('views/partials/flash.php'); ?>
    <form method="post" action="/login" class="space-y-4">
      <?= \App\Core\Csrf::field() ?>
      <label class="block text-sm font-medium">Username
        <input name="username" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-cyan-600 focus:outline-none">
      </label>
      <label class="block text-sm font-medium">Password
        <input name="password" type="password" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-cyan-600 focus:outline-none">
      </label>
      <button class="w-full rounded-lg bg-cyan-600 px-4 py-2 font-semibold text-white hover:bg-cyan-700">Login</button>
    </form>
    <button type="button" data-install-app hidden class="mt-3 inline-flex w-full items-center justify-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-cyan-50 hover:text-cyan-700">
      <span>Install App</span>
    </button>
  </div>
</div>
