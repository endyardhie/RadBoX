<?php

namespace App\Services;

use App\Core\Database;
use PDO;
use Throwable;

class RadiusDebugService
{
    private const FREERADIUS_TABLES = [
        'radcheck',
        'radreply',
        'radusergroup',
        'radgroupcheck',
        'radgroupreply',
        'radacct',
        'nas',
    ];

    public function inspect(array $filters = []): array
    {
        try {
            $db = Database::pdo();
            $databaseName = $this->fetchValue($db, 'SELECT DATABASE()');
            $serverVersion = $this->fetchValue($db, 'SELECT VERSION()');

            return [
                'connected' => true,
                'database_name' => $databaseName,
                'server_version' => $serverVersion,
                'tables' => $tables = $this->tableStatus($db),
                'counts' => $this->tableCounts($db, $tables, $filters),
                'latest' => $this->latestRows($db, $tables, $filters),
                'error' => null,
            ];
        } catch (Throwable $e) {
            return [
                'connected' => false,
                'database_name' => null,
                'server_version' => null,
                'tables' => [],
                'counts' => [],
                'latest' => [],
                'error' => $e->getMessage(),
            ];
        }
    }

    private function tableStatus(PDO $db): array
    {
        $stmt = $db->prepare('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?');
        $status = [];
        foreach (self::FREERADIUS_TABLES as $table) {
            $stmt->execute([$table]);
            $status[$table] = (int) $stmt->fetchColumn() > 0;
        }
        return $status;
    }

    private function tableCounts(PDO $db, array $tables, array $filters = []): array
    {
        [$routerWhere, $routerParams] = $this->routerFilter($filters, 'r.id');
        $counts = [];
        $counts['radcheck'] = empty($tables['radcheck']) ? null : (int) $this->fetchValue($db, 'SELECT COUNT(*) FROM radcheck rc LEFT JOIN rb_vouchers v ON v.username = rc.username LEFT JOIN rb_routers r ON r.id = v.router_id WHERE 1=1' . $routerWhere, $routerParams);
        $counts['radusergroup'] = empty($tables['radusergroup']) ? null : (int) $this->fetchValue($db, 'SELECT COUNT(*) FROM radusergroup rug LEFT JOIN rb_vouchers v ON v.username = rug.username LEFT JOIN rb_routers r ON r.id = v.router_id WHERE 1=1' . $routerWhere, $routerParams);
        $counts['radacct'] = empty($tables['radacct']) ? null : (int) $this->fetchValue($db, 'SELECT COUNT(*) FROM radacct a LEFT JOIN rb_routers r ON r.nas_ip_address = a.nasipaddress WHERE 1=1' . $routerWhere, $routerParams);
        $counts['nas'] = empty($tables['nas']) ? null : (int) $this->fetchValue($db, 'SELECT COUNT(*) FROM nas n LEFT JOIN rb_routers r ON r.nas_ip_address = n.nasname WHERE 1=1' . $routerWhere, $routerParams);
        return $counts;
    }

    private function latestRows(PDO $db, array $tables, array $filters = []): array
    {
        [$routerWhere, $routerParams] = $this->routerFilter($filters, 'r.id');
        return [
            'radcheck' => empty($tables['radcheck']) ? [] : $this->fetchAll($db, 'SELECT rc.username, rc.attribute, rc.op, rc.value FROM radcheck rc LEFT JOIN rb_vouchers v ON v.username = rc.username LEFT JOIN rb_routers r ON r.id = v.router_id WHERE 1=1' . $routerWhere . ' ORDER BY rc.id DESC LIMIT 10', $routerParams),
            'radusergroup' => empty($tables['radusergroup']) ? [] : $this->fetchAll($db, 'SELECT rug.username, rug.groupname, rug.priority FROM radusergroup rug LEFT JOIN rb_vouchers v ON v.username = rug.username LEFT JOIN rb_routers r ON r.id = v.router_id WHERE 1=1' . $routerWhere . ' ORDER BY rug.id DESC LIMIT 10', $routerParams),
            'radacct_active' => empty($tables['radacct']) ? [] : $this->fetchAll($db, 'SELECT a.username, a.nasipaddress, a.framedipaddress, a.acctstarttime, a.acctsessiontime FROM radacct a LEFT JOIN rb_routers r ON r.nas_ip_address = a.nasipaddress WHERE a.acctstoptime IS NULL' . $routerWhere . ' ORDER BY a.acctstarttime DESC LIMIT 10', $routerParams),
            'nas' => empty($tables['nas']) ? [] : $this->fetchAll($db, 'SELECT n.nasname, n.shortname, n.type, n.description FROM nas n LEFT JOIN rb_routers r ON r.nas_ip_address = n.nasname WHERE 1=1' . $routerWhere . ' ORDER BY n.id DESC LIMIT 10', $routerParams),
        ];
    }

    private function fetchValue(PDO $db, string $sql, array $params = []): string
    {
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return (string) $stmt->fetchColumn();
    }

    private function fetchAll(PDO $db, string $sql, array $params = []): array
    {
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    private function routerFilter(array $filters, string $column): array
    {
        if (empty($filters['router_ids'])) {
            return ['', []];
        }

        $routerIds = array_values(array_unique(array_map('intval', (array) $filters['router_ids'])));
        if (!$routerIds) {
            return [' AND 1=0', []];
        }

        return [' AND ' . $column . ' IN (' . implode(',', array_fill(0, count($routerIds), '?')) . ')', $routerIds];
    }
}
