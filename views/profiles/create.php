<h1 class="mb-5 text-2xl font-semibold">Tambah Profile</h1>
<form method="post" action="/profiles" class="max-w-2xl space-y-4 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
  <?= \App\Core\Csrf::field() ?>
  <?php $item = ['tenant_id'=>'','name'=>'','group_name'=>'','username_prefix'=>'','total_uptime_minutes'=>120,'validity_minutes'=>1440,'idle_timeout_minutes'=>10,'rate_limit'=>'','shared_users'=>1,'price'=>0,'description'=>'','is_active'=>1]; $groupEditable = true; require app_path('views/profiles/form.php'); ?>
</form>
