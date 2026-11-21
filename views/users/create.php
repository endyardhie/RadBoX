<h1 class="mb-5 text-2xl font-semibold">Tambah User</h1>
<form method="post" action="/users" class="max-w-2xl space-y-4 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
  <?= \App\Core\Csrf::field() ?>
  <?php $item = ['name'=>'','username'=>'','tenant_id'=>is_super_admin() ? '' : current_user()['tenant_id'],'role'=>is_super_admin() ? 'tenant_owner' : 'technician','status'=>'active']; $passwordRequired = true; require app_path('views/users/form.php'); ?>
</form>
