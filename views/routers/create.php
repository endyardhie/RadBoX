<h1 class="mb-5 text-2xl font-semibold">Tambah Router</h1>
<form method="post" action="/routers" class="max-w-2xl space-y-4 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
  <?= \App\Core\Csrf::field() ?>
  <?php $item = ['tenant_id'=>'','name'=>'','nas_ip_address'=>'','voucher_prefix'=>'','description'=>'','status'=>'active']; $secretRequired = true; require app_path('views/routers/form.php'); ?>
</form>
