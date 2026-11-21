<?php $isSuperAdmin = is_super_admin(); ?>

<h1 class="mb-5 text-2xl font-semibold">Generate Voucher</h1>
<form method="post" action="/vouchers/generate" class="max-w-2xl space-y-4 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
  <?= \App\Core\Csrf::field() ?>

  <?php if ($isSuperAdmin): ?>
    <label class="block text-sm font-medium">Target Tenant
      <select id="targetTenantSelect" name="tenant_id" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
        <option value="">Pilih target tenant</option>
        <?php foreach ($tenants as $tenant): ?>
          <option value="<?= e($tenant['id']) ?>"><?= e($tenant['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
  <?php endif; ?>

  <label class="block text-sm font-medium">Profile
    <select id="profileSelect" name="profile_id" required <?= $isSuperAdmin ? 'disabled' : '' ?> class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 disabled:bg-slate-100 disabled:text-slate-500">
      <?php if ($isSuperAdmin): ?>
        <option value="">Pilih target tenant dulu</option>
      <?php elseif (!$profiles): ?>
        <option value="">Tidak ada profile aktif</option>
      <?php endif; ?>
      <?php foreach ($profiles as $profile): ?>
        <option value="<?= e($profile['id']) ?>" data-tenant-id="<?= e($profile['tenant_id']) ?>" <?= $isSuperAdmin ? 'hidden disabled' : '' ?>>
          <?= e($profile['name']) ?> - <?= formatRupiah($profile['price']) ?>
        </option>
      <?php endforeach; ?>
    </select>
    <?php if ($isSuperAdmin): ?>
      <span id="profileEmptyText" class="mt-1 hidden text-xs text-slate-500">Tenant ini belum memiliki profile aktif.</span>
    <?php endif; ?>
  </label>

  <label class="block text-sm font-medium">Jumlah Voucher
    <input type="number" name="quantity" min="1" max="1000" value="10" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
  </label>

  <label class="block text-sm font-medium">Comment
    <textarea name="comment" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2"></textarea>
  </label>

  <button class="inline-flex items-center gap-2 rounded-lg bg-cyan-600 px-4 py-2 text-sm font-semibold text-white">
    <i data-lucide="ticket-plus" class="h-4 w-4"></i> Generate Voucher
  </button>
</form>

<?php if ($isSuperAdmin): ?>
  <script>
    (() => {
      const tenantSelect = document.getElementById('targetTenantSelect');
      const profileSelect = document.getElementById('profileSelect');
      const profileEmptyText = document.getElementById('profileEmptyText');
      const placeholder = profileSelect.querySelector('option[value=""]');
      const profileOptions = Array.from(profileSelect.querySelectorAll('option[data-tenant-id]'));

      const syncProfiles = () => {
        const tenantId = tenantSelect.value;
        let firstProfileValue = '';

        profileOptions.forEach((option) => {
          const matchesTenant = tenantId && option.dataset.tenantId === tenantId;
          option.hidden = !matchesTenant;
          option.disabled = !matchesTenant;
          if (matchesTenant && !firstProfileValue) {
            firstProfileValue = option.value;
          }
        });

        if (placeholder) {
          placeholder.hidden = Boolean(firstProfileValue);
          placeholder.disabled = Boolean(firstProfileValue);
        }

        profileSelect.disabled = !firstProfileValue;
        profileSelect.value = firstProfileValue || '';
        profileEmptyText.classList.toggle('hidden', !tenantId || Boolean(firstProfileValue));
      };

      tenantSelect.addEventListener('change', syncProfiles);
      syncProfiles();
    })();
  </script>
<?php endif; ?>
