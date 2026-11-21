<?php

namespace App\Core;

class Session
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            $lifetime = 60 * 60 * 24 * 30;
            ini_set('session.gc_maxlifetime', (string) $lifetime);
            ini_set('session.cookie_lifetime', (string) $lifetime);
            session_start([
                'cookie_lifetime' => $lifetime,
                'cookie_httponly' => true,
                'cookie_samesite' => 'Lax',
                'cookie_secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
            ]);
        }
    }
}
