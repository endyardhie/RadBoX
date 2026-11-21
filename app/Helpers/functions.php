<?php

function app_path(string $path = ''): string
{
    return dirname(__DIR__, 2) . ($path ? '/' . ltrim($path, '/') : '');
}

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function route_url(string $path, array $query = []): string
{
    $url = $path === '/' ? '/' : '/' . ltrim($path, '/');
    return $query ? $url . '?' . http_build_query($query) : $url;
}

function redirect(string $path): void
{
    header('Location: ' . route_url($path));
    exit;
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function flashes(): array
{
    $items = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $items;
}

function app_setting(string $key, ?string $default = null): ?string
{
    static $settings = null;

    if ($settings === null) {
        $settings = [];
        try {
            $stmt = \App\Core\Database::pdo()->prepare('SELECT setting_key, setting_value FROM rb_settings WHERE tenant_id IS NULL ORDER BY id');
            $stmt->execute();
            foreach ($stmt->fetchAll() as $row) {
                $settings[$row['setting_key']] = $row['setting_value'];
            }
        } catch (Throwable $e) {
            error_log_simple($e->getMessage());
        }
    }

    return array_key_exists($key, $settings) ? $settings[$key] : $default;
}

function scoped_app_setting(string $key, ?string $default = null): ?string
{
    $tenantId = current_user()['tenant_id'] ?? null;
    if (!$tenantId) {
        return app_setting($key, $default);
    }

    static $tenantSettings = [];
    $tenantId = (int) $tenantId;
    if (!array_key_exists($tenantId, $tenantSettings)) {
        $tenantSettings[$tenantId] = [];
        try {
            $stmt = \App\Core\Database::pdo()->prepare('SELECT setting_key, setting_value FROM rb_settings WHERE tenant_id = ? ORDER BY id');
            $stmt->execute([$tenantId]);
            foreach ($stmt->fetchAll() as $row) {
                $tenantSettings[$tenantId][$row['setting_key']] = $row['setting_value'];
            }
        } catch (Throwable $e) {
            error_log_simple($e->getMessage());
        }
    }

    $value = $tenantSettings[$tenantId][$key] ?? null;
    if ($value !== null && $value !== '') {
        return $value;
    }
    return app_setting($key, $default);
}

function app_logo_path(): string
{
    return '/assets/img/logo.png';
}

function pwa_icon_path(): string
{
    return '/assets/img/app-logo-192.png';
}

function apply_app_timezone(): void
{
    $timezone = app_setting('default_timezone', 'Asia/Jayapura') ?: 'Asia/Jayapura';
    if (in_array($timezone, timezone_identifiers_list(), true)) {
        date_default_timezone_set($timezone);
    }
}

function parse_duration_minutes(?string $value): int
{
    $value = strtolower(trim((string) $value));
    if ($value === '') {
        return 0;
    }
    if (!preg_match('/^(\d+)\s*([mjh]?)$/', $value, $matches)) {
        throw new InvalidArgumentException('Format waktu harus seperti 120m, 6j, atau 30h.');
    }

    $amount = (int) $matches[1];
    $unit = $matches[2] ?: 'm';
    return match ($unit) {
        'm' => $amount,
        'j' => $amount * 60,
        'h' => $amount * 1440,
    };
}

function default_voucher_templates(): array
{
    return [
        ['name' => 'Minimalist', 'columns' => 4, 'width_mm' => 48, 'height_mm' => 30, 'font_size' => 10, 'content' => voucher_example_template_content('Minimalist')],
        ['name' => 'Simple', 'columns' => 4, 'width_mm' => 48, 'height_mm' => 30, 'font_size' => 10, 'content' => voucher_example_template_content('Simple')],
        ['name' => 'Modern', 'columns' => 3, 'width_mm' => 60, 'height_mm' => 38, 'font_size' => 10, 'content' => voucher_example_template_content('Modern')],
        ['name' => 'Colorful', 'columns' => 3, 'width_mm' => 60, 'height_mm' => 38, 'font_size' => 10, 'content' => voucher_example_template_content('Colorful')],
        ['name' => 'Pinky', 'columns' => 4, 'width_mm' => 48, 'height_mm' => 30, 'font_size' => 10, 'content' => voucher_example_template_content('Pinky')],
        ['name' => 'Merauke', 'columns' => 3, 'width_mm' => 62, 'height_mm' => 40, 'font_size' => 10, 'content' => voucher_example_template_content('Merauke')],
        ['name' => 'Black-Pink', 'columns' => 3, 'width_mm' => 60, 'height_mm' => 38, 'font_size' => 10, 'content' => voucher_example_template_content('Black-Pink')],
        ['name' => 'RadBoX', 'columns' => 3, 'width_mm' => 60, 'height_mm' => 38, 'font_size' => 10, 'content' => voucher_example_template_content('RadBoX')],
        ['name' => 'Blue Sky', 'columns' => 4, 'width_mm' => 50, 'height_mm' => 32, 'font_size' => 10, 'content' => voucher_example_template_content('Blue Sky')],
        ['name' => 'Green Forest', 'columns' => 4, 'width_mm' => 50, 'height_mm' => 32, 'font_size' => 10, 'content' => voucher_example_template_content('Green Forest')],
    ];
}

function default_voucher_template_content(): string
{
    return voucher_example_template_content('Pinky');
}

function voucher_example_template_content(string $name): string
{
    $styles = [
        'Minimalist' => ['minimalist', '#111827', '#f8fafc', '#e2e8f0', '#334155', '#ffffff', 'VOUCHER'],
        'Simple' => ['simple', '#0f766e', '#ecfeff', '#99f6e4', '#115e59', '#ffffff', 'HOTSPOT'],
        'Modern' => ['modern', '#2563eb', '#eff6ff', '#bfdbfe', '#1e3a8a', '#ffffff', 'ACCESS'],
        'Colorful' => ['colorful', '#f97316', '#fff7ed', '#fed7aa', '#7c2d12', '#ffffff', 'WIFI'],
        'Pinky' => ['pinky', '#db2777', '#fff1f2', '#f9a8d4', '#831843', '#ffffff', 'PINKY'],
        'Merauke' => ['merauke', '#b45309', '#fffbeb', '#fcd34d', '#713f12', '#ffffff', 'MERAUKE'],
        'Black-Pink' => ['black-pink', '#ec4899', '#111827', '#be185d', '#fdf2f8', '#1f2937', 'BLACK PINK'],
        'RadBoX' => ['radbox', '#0891b2', '#ecfeff', '#67e8f9', '#164e63', '#ffffff', 'RadBoX'],
        'Blue Sky' => ['blue-sky', '#0284c7', '#f0f9ff', '#7dd3fc', '#0c4a6e', '#ffffff', 'BLUE SKY'],
        'Green Forest' => ['green-forest', '#16a34a', '#f0fdf4', '#86efac', '#14532d', '#ffffff', 'FOREST'],
    ];

    [$slug, $accent, $bg, $line, $text, $panel, $badge] = $styles[$name] ?? $styles['Minimalist'];

    return <<<HTML
<style>
.rb-voucher-{$slug} {
  height: 100%;
  box-sizing: border-box;
  border: 1px solid {$line};
  border-radius: 5px;
  background: {$bg};
  color: {$text};
  font-family: Arial, sans-serif;
  padding: 2mm;
}
.rb-voucher-{$slug} .rb-head {
  display: flex;
  justify-content: space-between;
  gap: 2mm;
  font-weight: 700;
  line-height: 1.1;
}
.rb-voucher-{$slug} .rb-badge {
  border-radius: 999px;
  background: {$accent};
  color: #fff;
  padding: .6mm 1.5mm;
  font-size: .8em;
  font-weight: 800;
  white-space: nowrap;
}
.rb-voucher-{$slug} .rb-code {
  margin: 2mm 0;
  border: 1px dashed {$accent};
  background: {$panel};
  border-radius: 4px;
  padding: 1.2mm;
  text-align: center;
  font-size: 1.25em;
  font-weight: 800;
  color: {$text};
}
.rb-voucher-{$slug} .rb-line {
  display: flex;
  justify-content: space-between;
  gap: 2mm;
  line-height: 1.25;
}
.rb-voucher-{$slug} .rb-label {
  color: {$accent};
  font-weight: 600;
}
.rb-voucher-{$slug} .rb-value {
  font-weight: 700;
  text-align: right;
}
</style>
<div class="rb-voucher-{$slug}">
  <div class="rb-head"><span>{{tenant_name}}</span><span class="rb-badge">{$badge}</span></div>
  <div class="rb-code">{{username}}</div>
  <div class="rb-line"><span class="rb-label">Paket</span><span class="rb-value">{{profile_name}}</span></div>
  <div class="rb-line"><span class="rb-label">Password</span><span class="rb-value">{{password}}</span></div>
  <div class="rb-line"><span class="rb-label">Waktu</span><span class="rb-value">{{time_limit}}</span></div>
  <div class="rb-line"><span class="rb-label">Harga</span><span class="rb-value">{{price}}</span></div>
</div>
HTML;
}

function voucher_templates(): array
{
    $json = app_setting('voucher_templates');
    $templates = $json ? json_decode($json, true) : null;
    if (!is_array($templates)) {
        return default_voucher_templates();
    }

    $clean = [];
    foreach ($templates as $template) {
        if (!is_array($template) || empty($template['name'])) {
            continue;
        }
        $clean[] = [
            'name' => trim((string) $template['name']),
            'columns' => max(1, min(8, (int) ($template['columns'] ?? 4))),
            'width_mm' => max(25, min(90, (int) ($template['width_mm'] ?? 45))),
            'height_mm' => max(18, min(70, (int) ($template['height_mm'] ?? 28))),
            'font_size' => max(8, min(16, (int) ($template['font_size'] ?? 10))),
            'content' => trim((string) ($template['content'] ?? '')) !== '' ? (string) $template['content'] : default_voucher_template_content(),
        ];
    }

    if (!$clean) {
        return default_voucher_templates();
    }

    $existingNames = array_map(fn (array $template): string => strtolower($template['name']), $clean);
    foreach (default_voucher_templates() as $defaultTemplate) {
        if (!in_array(strtolower($defaultTemplate['name']), $existingNames, true)) {
            $clean[] = $defaultTemplate;
        }
    }

    return $clean;
}

function render_voucher_template(string $content, array $item, array $batch): string
{
    $createdAt = $item['created_at'] ?? $batch['created_at'] ?? null;
    $values = [
        'username' => $item['username'] ?? '',
        'voucher' => $item['username'] ?? '',
        'password' => $item['password'] ?? '',
        'profile' => $item['profile_name'] ?? $batch['profile_name'] ?? '',
        'profile_name' => $item['profile_name'] ?? $batch['profile_name'] ?? '',
        'tenant' => $batch['tenant_name'] ?? '',
        'tenant_name' => $batch['tenant_name'] ?? '',
        'hotspot_name' => $batch['tenant_name'] ?? '',
        'batch' => $batch['batch_code'] ?? '',
        'batch_code' => $batch['batch_code'] ?? '',
        'price' => formatRupiah($item['price'] ?? $batch['total_price'] ?? 0),
        'raw_price' => (string) ($item['price'] ?? ''),
        'time_limit' => minutesToHuman((int) ($item['total_uptime_minutes'] ?? $batch['total_uptime_minutes'] ?? 0)),
        'timelimit' => minutesToHuman((int) ($item['total_uptime_minutes'] ?? $batch['total_uptime_minutes'] ?? 0)),
        'validity' => minutesToHuman((int) ($item['validity_minutes'] ?? $batch['validity_minutes'] ?? 0)),
        'rate_limit' => $item['rate_limit'] ?? $batch['rate_limit'] ?? '',
        'comment' => $batch['comment'] ?? '',
        'status' => $item['status'] ?? '',
        'created_at' => $createdAt ? formatDateTime((string) $createdAt) : '',
        'dns_name' => '',
        'dns-name' => '',
    ];

    foreach ($values as $key => $value) {
        $escaped = e((string) $value);
        $content = str_replace('{{' . $key . '}}', $escaped, $content);
        $content = str_replace('$(' . $key . ')', $escaped, $content);
    }

    return $content;
}

function audit_log(?int $tenantId, ?int $userId, string $action, string $description = ''): void
{
    try {
        $db = \App\Core\Database::pdo();
        $stmt = $db->prepare('INSERT INTO rb_audit_logs (tenant_id,user_id,action,description,ip_address,user_agent,created_at) VALUES (?,?,?,?,?,?,NOW())');
        $stmt->execute([
            $tenantId,
            $userId,
            $action,
            $description,
            $_SERVER['REMOTE_ADDR'] ?? null,
            substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 250),
        ]);
    } catch (Throwable $e) {
        error_log_simple($e->getMessage());
    }
}

function error_log_simple(string $message): void
{
    $file = app_path('storage/logs/app.log');
    @file_put_contents($file, '[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL, FILE_APPEND);
}

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function is_super_admin(): bool
{
    return (current_user()['role'] ?? null) === 'super_admin';
}

function is_tenant_owner(): bool
{
    return (current_user()['role'] ?? null) === 'tenant_owner';
}

function is_technician(): bool
{
    return (current_user()['role'] ?? null) === 'technician';
}

function can_manage_users(): bool
{
    return is_super_admin() || is_tenant_owner();
}

function can_write_data(): bool
{
    return !is_technician();
}

function require_auth(): void
{
    if (!current_user()) {
        redirect('/login');
    }
}

function require_not_technician(): void
{
    require_auth();
    if (is_technician()) {
        http_response_code(403);
        echo 'Forbidden';
        exit;
    }
}

function require_super_admin(): void
{
    require_auth();
    if (!is_super_admin()) {
        http_response_code(403);
        echo 'Forbidden';
        exit;
    }
}

function require_user_manager(): void
{
    require_auth();
    if (!can_manage_users()) {
        http_response_code(403);
        echo 'Forbidden';
        exit;
    }
}
