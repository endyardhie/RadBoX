<?php
$user = current_user();
$appName = app_setting('app_name', 'RadBoX') ?: 'RadBoX';
$footerText = scoped_app_setting('footer_text', 'Powered by RadBoX') ?: 'Powered by RadBoX';
$theme = app_setting('app_theme', 'light') === 'dark' ? 'theme-dark' : 'theme-light';
$cssVersion = is_file(app_path('public/assets/css/custom.css')) ? filemtime(app_path('public/assets/css/custom.css')) : time();
$jsVersion = is_file(app_path('public/assets/js/app.js')) ? filemtime(app_path('public/assets/js/app.js')) : time();
$swVersion = is_file(app_path('public/sw.js')) ? filemtime(app_path('public/sw.js')) : time();
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="theme-color" content="#0891b2">
  <meta name="mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-title" content="<?= e($appName) ?>">
  <title><?= e($appName) ?></title>
  <link rel="manifest" href="/manifest.webmanifest">
  <link rel="icon" href="<?= e(pwa_icon_path()) ?>">
  <link rel="apple-touch-icon" href="<?= e(pwa_icon_path()) ?>">
  <script src="https://cdn.tailwindcss.com"></script>
  <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
  <script src="https://unpkg.com/lucide@latest"></script>
  <link rel="stylesheet" href="/assets/css/custom.css?v=<?= e((string) $cssVersion) ?>">
</head>
<body class="<?= e($theme) ?> bg-slate-50 text-slate-900">
<div class="min-h-screen lg:flex">
  <?php require app_path('views/partials/sidebar.php'); ?>
  <main class="flex-1 lg:pl-72">
    <?php require app_path('views/partials/header.php'); ?>
    <div class="mx-auto max-w-7xl px-4 pb-28 pt-6 sm:px-6 lg:px-8 lg:pb-6">
      <?php require app_path('views/partials/flash.php'); ?>
      <?= $content ?>
      <footer class="no-print mt-8 border-t border-slate-200 py-5 text-center text-xs text-slate-500"><?= e($footerText) ?></footer>
    </div>
  </main>
</div>
<?php require app_path('views/partials/mobile_bottom_nav.php'); ?>
<script>lucide.createIcons();</script>
<script src="/assets/js/app.js?v=<?= e((string) $jsVersion) ?>"></script>
<script>
if ('serviceWorker' in navigator) {
  window.addEventListener('load', function () {
    navigator.serviceWorker.register('/sw.js?v=<?= e((string) $swVersion) ?>');
  });
}
</script>
</body>
</html>
