<meta http-equiv="refresh" content="30">
<div class="mb-5 flex items-center justify-between">
  <h1 class="text-2xl font-semibold">Active Users</h1>
  <a href="/active" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold">Refresh</a>
</div>
<div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
  <div class="overflow-x-auto"><table class="min-w-full text-sm">
    <thead class="bg-slate-100 text-left text-slate-600"><tr><?php if (can_write_data()): ?><th class="w-10 p-2"></th><?php endif; ?><th class="p-3">Username</th><?php if (is_super_admin()): ?><th class="p-3">Tenant</th><?php endif; ?><th class="p-3">Router</th><th class="p-3">IP</th><th class="p-3">Start</th><th class="p-3">Session</th><th class="p-3">Upload</th><th class="p-3">Download</th><th class="p-3">Status</th></tr></thead>
    <tbody class="divide-y divide-slate-100">
    <?php foreach ($items as $item): ?>
      <tr><?php if (can_write_data()): ?><td class="p-2"><form method="post" action="/active/kick" onsubmit="return confirm('Kick user ini?')"><?= \App\Core\Csrf::field() ?><input type="hidden" name="username" value="<?= e($item['username'] ?? '') ?>"><input type="hidden" name="nasipaddress" value="<?= e($item['nasipaddress'] ?? '') ?>"><input type="hidden" name="acctsessionid" value="<?= e($item['acctsessionid'] ?? '') ?>"><button class="grid h-8 w-8 place-items-center rounded-lg text-red-600 hover:bg-red-50" title="Kick"><i data-lucide="minus" class="h-4 w-4"></i></button></form></td><?php endif; ?><td class="p-3 font-mono font-semibold"><?= e($item['username'] ?? '') ?></td><?php if (is_super_admin()): ?><td class="p-3"><?= e($item['tenant_name'] ?? '-') ?></td><?php endif; ?><td class="p-3"><?= e($item['router_name'] ?? $item['nasipaddress'] ?? '-') ?></td><td class="p-3"><?= e($item['framedipaddress'] ?? '-') ?></td><td class="p-3"><?= formatDateTime($item['acctstarttime'] ?? null) ?></td><td class="p-3"><?= secondsToHuman($item['live_session_seconds'] ?? $item['acctsessiontime'] ?? 0) ?></td><td class="p-3"><?= formatBytes($item['live_input_octets'] ?? $item['acctinputoctets'] ?? 0) ?></td><td class="p-3"><?= formatBytes($item['live_output_octets'] ?? $item['acctoutputoctets'] ?? 0) ?></td><td class="p-3"><?= e($item['voucher_status'] ?? '-') ?></td></tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
</div>
