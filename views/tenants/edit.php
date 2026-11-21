<h1 class="mb-5 text-2xl font-semibold">Edit Tenant</h1>
<form method="post" action="/tenants/update" class="max-w-2xl space-y-4 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
  <?= \App\Core\Csrf::field() ?><input type="hidden" name="id" value="<?= e($item['id']) ?>">
  <?php require app_path('views/tenants/form.php'); ?>
</form>
