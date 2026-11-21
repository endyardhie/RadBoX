<?php

namespace App\Services;

use App\Core\Database;

class RadiusCoaService
{
    public function disconnectUsername(string $username): array
    {
        $stmt = Database::pdo()->prepare('SELECT a.*
            FROM radacct a
            JOIN rb_routers r ON r.nas_ip_address = a.nasipaddress AND r.status = "active"
            WHERE a.username = ? AND a.acctstoptime IS NULL
            ORDER BY a.acctstarttime DESC');
        $stmt->execute([$username]);
        $sessions = $stmt->fetchAll();
        if (!$sessions) {
            return ['ok' => true, 'message' => 'Tidak ada session aktif.', 'username' => $username, 'kicked' => 0, 'failed' => 0];
        }

        $kicked = 0;
        $failed = 0;
        $messages = [];
        foreach ($sessions as $session) {
            $result = $this->disconnect($session);
            if ($result['ok']) {
                $kicked++;
            } else {
                $failed++;
                $messages[] = $result['message'];
            }
        }

        return [
            'ok' => $failed === 0,
            'message' => $failed === 0 ? "{$kicked} session berhasil dikick." : implode(' | ', array_unique($messages)),
            'username' => $username,
            'kicked' => $kicked,
            'failed' => $failed,
        ];
    }

    public function disconnect(array $session): array
    {
        $username = (string) ($session['username'] ?? '');
        $nasIp = (string) ($session['nasipaddress'] ?? '');
        $framedIp = (string) ($session['framedipaddress'] ?? '');
        $secret = (string) ($session['router_radius_secret'] ?? '');
        if ($secret === '') {
            $secret = $this->routerSecret($nasIp) ?? '';
        }
        $radclient = $this->radclientPath();

        if ($username === '' || $nasIp === '' || $secret === '') {
            return ['ok' => false, 'message' => 'Data router atau user tidak lengkap untuk Kick.', 'username' => $username];
        }
        if ($radclient === null) {
            return ['ok' => false, 'message' => 'Command radclient belum tersedia di server. Install FreeRADIUS client utilities dan aktifkan CoA/Disconnect MikroTik port UDP 3799.', 'username' => $username];
        }

        $messages = [];
        foreach ($this->disconnectPackets($session) as $packet) {
            $result = $this->runRadclient($radclient, $nasIp, $secret, $packet);
            if ($result['ok']) {
                $this->closeLocalSession($session);
                return ['ok' => true, 'message' => $result['message'], 'username' => $username];
            }
            $messages[] = $result['message'];
        }
        error_log_simple('Kick gagal untuk ' . $username . ' via ' . $nasIp . ': ' . implode(' | ', array_unique($messages)));
        return ['ok' => false, 'message' => end($messages) ?: 'Kick gagal: tidak ada respons dari router.', 'username' => $username];
    }

    private function routerSecret(string $nasIp): ?string
    {
        $stmt = Database::pdo()->prepare('SELECT radius_secret FROM rb_routers WHERE nas_ip_address = ? AND status = "active" LIMIT 1');
        $stmt->execute([$nasIp]);
        $secret = $stmt->fetchColumn();
        if ($secret) {
            return (string) $secret;
        }
        $stmt = Database::pdo()->prepare('SELECT secret FROM nas WHERE nasname = ? LIMIT 1');
        $stmt->execute([$nasIp]);
        $secret = $stmt->fetchColumn();
        return $secret ? (string) $secret : null;
    }

    private function radclientPath(): ?string
    {
        foreach (['/usr/bin/radclient', '/usr/sbin/radclient', '/usr/local/bin/radclient', '/usr/local/sbin/radclient', '/bin/radclient', '/sbin/radclient'] as $path) {
            if (is_executable($path)) {
                return $path;
            }
        }
        if (!function_exists('proc_open')) {
            return $this->radclientPathFromExec();
        }
        $process = proc_open(['which', 'radclient'], [['pipe', 'r'], ['pipe', 'w'], ['pipe', 'w']], $pipes);
        if (!is_resource($process)) {
            return $this->radclientPathFromExec();
        }
        fclose($pipes[0]);
        $output = trim(stream_get_contents($pipes[1]));
        fclose($pipes[1]);
        fclose($pipes[2]);
        return proc_close($process) === 0 && $output !== '' ? $output : $this->radclientPathFromExec();
    }

    private function runRadclient(string $radclient, string $nasIp, string $secret, string $packet): array
    {
        if (!function_exists('proc_open')) {
            return $this->runRadclientWithExec($radclient, $nasIp, $secret, $packet);
        }
        $process = proc_open([$radclient, '-x', '-r', '1', '-t', '3', $nasIp . ':3799', 'disconnect', $secret], [['pipe', 'r'], ['pipe', 'w'], ['pipe', 'w']], $pipes);
        if (!is_resource($process)) {
            return ['ok' => false, 'message' => 'Gagal menjalankan radclient.'];
        }
        fwrite($pipes[0], $packet);
        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $code = proc_close($process);
        $output = trim($stdout . PHP_EOL . $stderr);
        $outputLower = strtolower($output);
        $ok = $code === 0
            && !str_contains($outputLower, 'disconnect-nak')
            && (str_contains($outputLower, 'disconnect-ack') || str_contains($outputLower, 'received response') || str_contains($outputLower, 'code 41'));
        return [
            'ok' => $ok,
            'message' => $ok ? 'User berhasil dikick.' : ('Kick gagal: ' . ($output ?: 'tidak ada respons dari router.')),
        ];
    }

    private function radclientPathFromExec(): ?string
    {
        if (!function_exists('exec')) {
            return null;
        }
        $output = [];
        $code = 1;
        exec('command -v radclient 2>/dev/null', $output, $code);
        $path = trim($output[0] ?? '');
        return $code === 0 && $path !== '' && is_executable($path) ? $path : null;
    }

    private function runRadclientWithExec(string $radclient, string $nasIp, string $secret, string $packet): array
    {
        if (!function_exists('exec')) {
            return ['ok' => false, 'message' => 'proc_open/exec tidak aktif di PHP server.'];
        }
        $tmp = tempnam(sys_get_temp_dir(), 'radbox-coa-');
        if ($tmp === false || file_put_contents($tmp, $packet) === false) {
            return ['ok' => false, 'message' => 'Gagal membuat file sementara untuk Kick.'];
        }
        $command = escapeshellarg($radclient)
            . ' -x -r 1 -t 3 '
            . escapeshellarg($nasIp . ':3799')
            . ' disconnect '
            . escapeshellarg($secret)
            . ' < '
            . escapeshellarg($tmp)
            . ' 2>&1';
        $lines = [];
        $code = 1;
        exec($command, $lines, $code);
        @unlink($tmp);

        $output = trim(implode(PHP_EOL, $lines));
        $outputLower = strtolower($output);
        $ok = $code === 0
            && !str_contains($outputLower, 'disconnect-nak')
            && (str_contains($outputLower, 'disconnect-ack') || str_contains($outputLower, 'received response') || str_contains($outputLower, 'code 41'));
        return [
            'ok' => $ok,
            'message' => $ok ? 'User berhasil dikick.' : ('Kick gagal: ' . ($output ?: 'tidak ada respons dari router.')),
        ];
    }

    private function disconnectPackets(array $session): array
    {
        $username = (string) ($session['username'] ?? '');
        $nasIp = (string) ($session['nasipaddress'] ?? '');
        $framedIp = (string) ($session['framedipaddress'] ?? '');
        $base = ['User-Name = "' . $this->escapeRadiusValue($username) . '"'];
        $full = $base;
        if ($nasIp !== '') {
            $full[] = 'NAS-IP-Address = ' . $nasIp;
        }
        if ($framedIp !== '') {
            $full[] = 'Framed-IP-Address = ' . $framedIp;
        }
        foreach (['acctsessionid' => 'Acct-Session-Id', 'nasportid' => 'NAS-Port-Id', 'callingstationid' => 'Calling-Station-Id'] as $column => $attribute) {
            if (!empty($session[$column])) {
                $full[] = $attribute . ' = "' . $this->escapeRadiusValue((string) $session[$column]) . '"';
            }
        }
        if (isset($session['nasport']) && $session['nasport'] !== '') {
            $full[] = 'NAS-Port = ' . (int) $session['nasport'];
        }

        $variants = [$full];
        if (!empty($session['acctsessionid'])) {
            $variants[] = array_merge($base, ['Acct-Session-Id = "' . $this->escapeRadiusValue((string) $session['acctsessionid']) . '"']);
        }
        if ($framedIp !== '') {
            $variants[] = array_merge($base, ['Framed-IP-Address = ' . $framedIp]);
        }
        $variants[] = $base;

        $packets = [];
        foreach ($variants as $lines) {
            $packet = implode(PHP_EOL, $lines) . PHP_EOL;
            $packets[$packet] = $packet;
        }
        return array_values($packets);
    }

    private function closeLocalSession(array $session): void
    {
        $username = (string) ($session['username'] ?? '');
        $nasIp = (string) ($session['nasipaddress'] ?? '');
        $params = [$username, $nasIp];
        $where = 'username = ? AND nasipaddress = ? AND acctstoptime IS NULL';
        if (!empty($session['acctsessionid'])) {
            $where .= ' AND acctsessionid = ?';
            $params[] = $session['acctsessionid'];
        }
        Database::pdo()->prepare('UPDATE radacct SET acctstoptime = COALESCE(acctstoptime, NOW()), acctterminatecause = COALESCE(NULLIF(acctterminatecause, ""), "Admin-Reset") WHERE ' . $where)->execute($params);
    }

    private function escapeRadiusValue(string $value): string
    {
        return str_replace(['\\', '"'], ['\\\\', '\\"'], $value);
    }
}
