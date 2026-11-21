<?php

namespace App\Services;

use App\Core\Database;

class AuthService
{
    public function attempt(string $username, string $password): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM rb_users WHERE username = ? AND status = "active" LIMIT 1');
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            return null;
        }

        Database::pdo()->prepare('UPDATE rb_users SET last_login_at = NOW() WHERE id = ?')->execute([$user['id']]);
        unset($user['password_hash']);
        return $user;
    }
}
