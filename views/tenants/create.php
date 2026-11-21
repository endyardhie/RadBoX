<h1 class="mb-5 text-2xl font-semibold">Tambah Tenant</h1>
<form method="post" action="/tenants" class="max-w-2xl space-y-4 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
  <?= \App\Core\Csrf::field() ?>
  <?php $item = ['name'=>'','code'=>'','status'=>'active','notes'=>'']; require app_path('views/tenants/form.php'); ?>
</form>
