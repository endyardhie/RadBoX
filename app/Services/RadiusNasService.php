<?php

namespace App\Services;

use App\Core\Database;

class RadiusNasService
{
    public function syncRouterToNasTable(array $router): void
    {
        $db = Database::pdo();
        if (($router['status'] ?? 'active') !== 'active') {
            $this->deleteRouterFromNasTable($router);
            return;
        }

        $tenantName = $router['tenant_name'] ?? 'Tenant';
        $description = trim($tenantName . ' / ' . $router['name']);
        if ($this->routerExistsInNas($router['nas_ip_address'])) {
            $sql = 'UPDATE nas SET shortname = ?, type = "mikrotik", secret = ?, description = ? WHERE nasname = ?';
            $db->prepare($sql)->execute([$router['name'], $router['radius_secret'], $description, $router['nas_ip_address']]);
            return;
        }
        $sql = 'INSERT INTO nas (nasname, shortname, type, ports, secret, server, community, description) VALUES (?, ?, "mikrotik", NULL, ?, NULL, NULL, ?)';
        $db->prepare($sql)->execute([$router['nas_ip_address'], $router['name'], $router['radius_secret'], $description]);
    }

    public function deleteRouterFromNasTable(array $router): void
    {
        Database::pdo()->prepare('DELETE FROM nas WHERE nasname = ?')->execute([$router['nas_ip_address']]);
    }

    public function routerExistsInNas(string $nasIpAddress): bool
    {
        $stmt = Database::pdo()->prepare('SELECT 1 FROM nas WHERE nasname = ? LIMIT 1');
        $stmt->execute([$nasIpAddress]);
        return (bool) $stmt->fetchColumn();
    }
}
