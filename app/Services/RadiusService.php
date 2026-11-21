<?php

namespace App\Services;

use App\Core\Database;

class RadiusService
{
    public function createOrUpdateGroupProfile(array $profile): void
    {
        $db = Database::pdo();
        $groupName = $profile['group_name'];

        if ((int) ($profile['is_active'] ?? 1) !== 1) {
            $this->deleteGroupProfile($groupName);
            return;
        }

        $stmt = $db->prepare('DELETE FROM radgroupreply WHERE groupname = ? AND attribute IN ("Mikrotik-Rate-Limit","Idle-Timeout","Session-Timeout")');
        $stmt->execute([$groupName]);
        $stmt = $db->prepare('DELETE FROM radgroupcheck WHERE groupname = ? AND attribute = "Simultaneous-Use"');
        $stmt->execute([$groupName]);

        if (!empty($profile['rate_limit'])) {
            $stmt = $db->prepare('INSERT INTO radgroupreply (groupname, attribute, op, value) VALUES (?, "Mikrotik-Rate-Limit", ":=", ?)');
            $stmt->execute([$groupName, $profile['rate_limit']]);
        }
        if (!empty($profile['idle_timeout_minutes'])) {
            $stmt = $db->prepare('INSERT INTO radgroupreply (groupname, attribute, op, value) VALUES (?, "Idle-Timeout", ":=", ?)');
            $stmt->execute([$groupName, (int) $profile['idle_timeout_minutes'] * 60]);
        }
        if (!empty($profile['total_uptime_minutes'])) {
            $stmt = $db->prepare('INSERT INTO radgroupreply (groupname, attribute, op, value) VALUES (?, "Session-Timeout", ":=", ?)');
            $stmt->execute([$groupName, (int) $profile['total_uptime_minutes'] * 60]);
        }
        if (!empty($profile['shared_users'])) {
            $stmt = $db->prepare('INSERT INTO radgroupcheck (groupname, attribute, op, value) VALUES (?, "Simultaneous-Use", ":=", ?)');
            $stmt->execute([$groupName, (int) $profile['shared_users']]);
        }
    }

    public function deleteGroupProfile(string $groupName): void
    {
        $db = Database::pdo();
        $db->prepare('DELETE FROM radgroupreply WHERE groupname = ?')->execute([$groupName]);
        $db->prepare('DELETE FROM radgroupcheck WHERE groupname = ?')->execute([$groupName]);
    }

    public function usernameExists(string $username): bool
    {
        $stmt = Database::pdo()->prepare('SELECT 1 FROM rb_vouchers WHERE username = ? AND status <> "deleted" UNION SELECT 1 FROM radcheck WHERE username = ? LIMIT 1');
        $stmt->execute([$username, $username]);
        return (bool) $stmt->fetchColumn();
    }

    public function releaseDeletedUsername(string $username): void
    {
        $db = Database::pdo();
        $stmt = $db->prepare('SELECT id, username FROM rb_vouchers WHERE username = ? AND status = "deleted"');
        $stmt->execute([$username]);
        foreach ($stmt->fetchAll() as $voucher) {
            $this->releaseDeletedVoucherRow($voucher);
        }
    }

    public function releaseAllDeletedUsernames(): int
    {
        $db = Database::pdo();
        $stmt = $db->prepare('SELECT id, username FROM rb_vouchers WHERE status = "deleted"');
        $stmt->execute();
        $count = 0;
        foreach ($stmt->fetchAll() as $voucher) {
            $suffix = '__DEL' . $voucher['id'];
            if (str_ends_with((string) $voucher['username'], $suffix)) {
                continue;
            }
            $this->releaseDeletedVoucherRow($voucher);
            $count++;
        }
        return $count;
    }

    private function releaseDeletedVoucherRow(array $voucher): void
    {
        $suffix = '__DEL' . $voucher['id'];
        $released = substr((string) $voucher['username'], 0, 80 - strlen($suffix)) . $suffix;
        Database::pdo()->prepare('UPDATE rb_vouchers SET username = ?, updated_at = NOW() WHERE id = ?')->execute([$released, $voucher['id']]);
    }

    public function createUser(string $username, string $password, string $groupName): void
    {
        $db = Database::pdo();
        $stmt = $db->prepare('INSERT INTO radcheck (username, attribute, op, value) VALUES (?, "Cleartext-Password", ":=", ?)');
        $stmt->execute([$username, $password]);
        $stmt = $db->prepare('INSERT INTO radusergroup (username, groupname, priority) VALUES (?, ?, 1)');
        $stmt->execute([$username, $groupName]);
    }

    public function deleteUserAccess(string $username): void
    {
        $db = Database::pdo();
        $db->prepare('DELETE FROM radcheck WHERE username = ?')->execute([$username]);
        $db->prepare('DELETE FROM radreply WHERE username = ?')->execute([$username]);
        $db->prepare('DELETE FROM radusergroup WHERE username = ?')->execute([$username]);
    }

    public function getActiveUsers(array $filters = []): array
    {
        $where = ['a.acctstoptime IS NULL'];
        $params = [];
        if (!empty($filters['tenant_id'])) {
            $where[] = 'r.tenant_id = ?';
            $params[] = $filters['tenant_id'];
        }
        if (!empty($filters['router_ids'])) {
            $routerIds = array_values(array_unique(array_map('intval', (array) $filters['router_ids'])));
            $where[] = 'r.id IN (' . implode(',', array_fill(0, count($routerIds), '?')) . ')';
            $params = array_merge($params, $routerIds);
        }
        $sql = 'SELECT a.*,
                    GREATEST(COALESCE(a.acctsessiontime, 0), COALESCE(TIMESTAMPDIFF(SECOND, a.acctstarttime, NOW()), 0)) live_session_seconds,
                    COALESCE(a.acctinputoctets, 0) live_input_octets,
                    COALESCE(a.acctoutputoctets, 0) live_output_octets,
                    r.name router_name, r.tenant_id, t.name tenant_name, v.status voucher_status, v.expires_at, v.total_uptime_used_seconds, v.total_uptime_limit_seconds
                FROM radacct a
                LEFT JOIN rb_routers r ON r.nas_ip_address = a.nasipaddress
                LEFT JOIN rb_tenants t ON t.id = r.tenant_id
                LEFT JOIN rb_vouchers v ON v.username = a.username
                WHERE ' . implode(' AND ', $where) . '
                ORDER BY a.acctstarttime DESC LIMIT 200';
        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function countActiveUsers(array $filters = []): int
    {
        $where = ['a.acctstoptime IS NULL'];
        $params = [];
        if (!empty($filters['tenant_id'])) {
            $where[] = 'r.tenant_id = ?';
            $params[] = $filters['tenant_id'];
        }
        if (!empty($filters['router_ids'])) {
            $routerIds = array_values(array_unique(array_map('intval', (array) $filters['router_ids'])));
            $where[] = 'r.id IN (' . implode(',', array_fill(0, count($routerIds), '?')) . ')';
            $params = array_merge($params, $routerIds);
        }
        $sql = 'SELECT COUNT(*)
                FROM radacct a
                LEFT JOIN rb_routers r ON r.nas_ip_address = a.nasipaddress
                WHERE ' . implode(' AND ', $where);
        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    public function getHotspotLogs(array $filters = [], int $limit = 20): array
    {
        $where = ['1=1'];
        $params = [];
        if (!empty($filters['tenant_id'])) {
            $where[] = 'r.tenant_id = ?';
            $params[] = $filters['tenant_id'];
        }
        if (!empty($filters['router_ids'])) {
            $routerIds = array_values(array_unique(array_map('intval', (array) $filters['router_ids'])));
            $where[] = 'r.id IN (' . implode(',', array_fill(0, count($routerIds), '?')) . ')';
            $params = array_merge($params, $routerIds);
        }
        $limit = max(1, min(100, $limit));
        $sql = "SELECT
                    COALESCE(a.acctstoptime, a.acctstarttime) occurred_at,
                    a.username,
                    a.framedipaddress ip_address,
                    CASE
                        WHEN a.acctstoptime IS NULL THEN 'Login aktif'
                        WHEN a.acctterminatecause IS NOT NULL AND a.acctterminatecause <> '' THEN CONCAT('Logout: ', a.acctterminatecause)
                        ELSE 'Logout'
                    END message,
                    r.name router_name,
                    t.name tenant_name
                FROM radacct a
                LEFT JOIN rb_routers r ON r.nas_ip_address = a.nasipaddress
                LEFT JOIN rb_tenants t ON t.id = r.tenant_id
                WHERE " . implode(' AND ', $where) . "
                ORDER BY COALESCE(a.acctstoptime, a.acctstarttime) DESC
                LIMIT {$limit}";
        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getLastSession(string $username): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM radacct WHERE username = ? ORDER BY acctstarttime DESC LIMIT 1');
        $stmt->execute([$username]);
        return $stmt->fetch() ?: null;
    }

    public function getTotalUptime(string $username): int
    {
        $stmt = Database::pdo()->prepare('SELECT COALESCE(SUM(acctsessiontime), 0) FROM radacct WHERE username = ?');
        $stmt->execute([$username]);
        return (int) $stmt->fetchColumn();
    }
}
