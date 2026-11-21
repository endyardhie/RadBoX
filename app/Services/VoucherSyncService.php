<?php

namespace App\Services;

use App\Core\Database;

class VoucherSyncService
{
    public function __construct(private RadiusService $radius = new RadiusService())
    {
    }

    private int $lastExpiredKickSuccess = 0;
    private int $lastExpiredKickFailed = 0;

    public function syncFirstLoginFromRadacct(): int
    {
        $sql = 'UPDATE rb_vouchers v
                JOIN (
                    SELECT v2.id, MIN(a.acctstarttime) first_login
                    FROM rb_vouchers v2
                    JOIN radacct a ON a.username = v2.username AND a.acctstarttime >= v2.created_at
                    WHERE v2.status IN ("generated","active")
                    GROUP BY v2.id
                ) a ON a.id = v.id
                SET v.first_login_at = CASE
                        WHEN v.first_login_at IS NULL OR v.first_login_at < v.created_at THEN a.first_login
                        ELSE v.first_login_at
                    END,
                    v.expires_at = CASE
                        WHEN v.validity_minutes > 0 AND (v.expires_at IS NULL OR v.first_login_at IS NULL OR v.first_login_at < v.created_at) THEN DATE_ADD(a.first_login, INTERVAL v.validity_minutes MINUTE)
                        WHEN v.validity_minutes > 0 THEN v.expires_at
                        ELSE NULL
                    END,
                    v.status = IF(v.status = "generated", "active", v.status),
                    v.updated_at = NOW()
                WHERE v.status IN ("generated","active")';
        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute();
        Database::pdo()->prepare('UPDATE rb_transactions t JOIN rb_vouchers v ON v.id = t.voucher_id SET t.status = "used", t.updated_at = NOW() WHERE v.first_login_at IS NOT NULL AND v.first_login_at >= v.created_at AND t.status IN ("generated","sold")')->execute();
        return $stmt->rowCount();
    }

    public function syncTotalUptimeFromRadacct(): int
    {
        $sql = 'UPDATE rb_vouchers v
                LEFT JOIN (
                    SELECT
                        v2.id,
                        COALESCE(SUM(CASE
                            WHEN a.acctstoptime IS NULL AND a.acctstarttime IS NOT NULL THEN GREATEST(COALESCE(a.acctsessiontime, 0), COALESCE(TIMESTAMPDIFF(SECOND, a.acctstarttime, NOW()), 0))
                            ELSE COALESCE(a.acctsessiontime, 0)
                        END), 0) used,
                        MAX(COALESCE(a.acctstoptime, a.acctstarttime)) last_seen
                    FROM rb_vouchers v2
                    LEFT JOIN radacct a ON a.username = v2.username AND a.acctstarttime >= v2.created_at
                    WHERE v2.status IN ("generated","active")
                    GROUP BY v2.id
                ) a ON a.id = v.id
                SET v.total_uptime_used_seconds = COALESCE(a.used, 0),
                    v.last_seen_at = a.last_seen,
                    v.updated_at = NOW()
                WHERE v.status IN ("generated","active")';
        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute();
        return $stmt->rowCount();
    }

    public function expireVouchersByTotalUptime(): int
    {
        return $this->expireWhere('status IN ("generated","active") AND total_uptime_limit_seconds > 0 AND total_uptime_used_seconds >= total_uptime_limit_seconds');
    }

    public function expireVouchersByValidity(): int
    {
        return $this->expireWhere('status IN ("generated","active") AND validity_minutes > 0 AND expires_at IS NOT NULL AND NOW() > expires_at');
    }

    public function disableRadiusAccessForExpiredVoucher(string $username): void
    {
        $this->radius->deleteUserAccess($username);
    }

    public function syncAll(): array
    {
        $this->lastExpiredKickSuccess = 0;
        $this->lastExpiredKickFailed = 0;
        $result = [
            'released_deleted_usernames' => $this->radius->releaseAllDeletedUsernames(),
            'repaired_reused_username_usage' => $this->repairInvalidUsageFromReusedUsernames(),
            'first_login' => $this->syncFirstLoginFromRadacct(),
            'uptime' => $this->syncTotalUptimeFromRadacct(),
            'expired_uptime' => $this->expireVouchersByTotalUptime(),
            'expired_validity' => $this->expireVouchersByValidity(),
            'expired_kick_success' => $this->lastExpiredKickSuccess,
            'expired_kick_failed' => $this->lastExpiredKickFailed,
        ];
        return $result;
    }

    private function repairInvalidUsageFromReusedUsernames(): int
    {
        $db = Database::pdo();
        $db->prepare('UPDATE rb_vouchers
            SET first_login_at = NULL,
                expires_at = NULL,
                total_uptime_used_seconds = 0,
                last_seen_at = NULL,
                status = IF(status = "active", "generated", status),
                updated_at = NOW()
            WHERE status IN ("generated","active")
                AND first_login_at IS NOT NULL
                AND first_login_at < created_at')->execute();

        $stmt = $db->prepare('UPDATE rb_transactions t
            JOIN rb_vouchers v ON v.id = t.voucher_id
            SET t.status = "generated", t.updated_at = NOW()
            WHERE t.status IN ("used","expired")
                AND (v.first_login_at IS NULL OR v.first_login_at < v.created_at)
                AND NOT EXISTS (
                    SELECT 1
                    FROM radacct a
                    WHERE a.username = t.username
                        AND a.acctstarttime >= v.created_at
                    LIMIT 1
                )');
        $stmt->execute();
        return $stmt->rowCount();
    }

    private function expireWhere(string $where): int
    {
        $db = Database::pdo();
        $stmt = $db->prepare('SELECT username FROM rb_vouchers WHERE ' . $where);
        $stmt->execute();
        $usernames = $stmt->fetchAll(\PDO::FETCH_COLUMN);
        if (!$usernames) {
            return 0;
        }
        $db->prepare('UPDATE rb_vouchers SET status = "deleted", deleted_at = NOW(), updated_at = NOW() WHERE ' . $where)->execute();
        $coa = new RadiusCoaService();
        foreach ($usernames as $username) {
            $kick = $coa->disconnectUsername((string) $username);
            $this->lastExpiredKickSuccess += (int) ($kick['kicked'] ?? 0);
            $this->lastExpiredKickFailed += (int) ($kick['failed'] ?? 0);
            if (!$kick['ok']) {
                error_log_simple('Gagal kick expired voucher ' . $username . ': ' . $kick['message']);
            }
            $this->disableRadiusAccessForExpiredVoucher($username);
        }
        $in = implode(',', array_fill(0, count($usernames), '?'));
        $db->prepare("UPDATE rb_transactions SET status = 'expired', updated_at = NOW() WHERE username IN ({$in}) AND status <> 'deleted'")->execute($usernames);
        foreach ($usernames as $username) {
            $this->radius->releaseDeletedUsername((string) $username);
        }
        return count($usernames);
    }
}
