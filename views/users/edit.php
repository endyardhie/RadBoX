<h1 class="mb-5 text-2xl font-semibold">Edit User</h1>
<form method="post" action="/users/update" class="max-w-2xl space-y-4 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
  <?= \App\Core\Csrf::field() ?><input type="hidden" name="id" value="<?= e($item['id']) ?>">
  <?php $passwordRequired = false; require app_path('views/users/form.php'); ?>
</form>
