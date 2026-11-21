<?php

namespace App\Controllers;

use App\Core\Csrf;
use App\Core\Request;
use App\Services\AuthService;

class AuthController extends BaseController
{
    public function showLogin(): void
    {
        if (current_user()) {
            redirect('/dashboard');
        }
        $this->view('auth/login', [], 'auth');
    }

    public function login(Request $request): void
    {
        Csrf::verify();
        $_SESSION['login_attempts'] = $_SESSION['login_attempts'] ?? ['count' => 0, 'time' => time()];
        if ($_SESSION['login_attempts']['count'] >= 5 && time() - $_SESSION['login_attempts']['time'] < 300) {
            flash('error', 'Terlalu banyak percobaan login. Coba lagi beberapa menit.');
            redirect('/login');
        }

        $user = (new AuthService())->attempt(trim((string) $request->input('username')), (string) $request->input('password'));
        if (!$user) {
            $_SESSION['login_attempts']['count']++;
            $_SESSION['login_attempts']['time'] = time();
            flash('error', 'Username atau password salah.');
            redirect('/login');
        }

        session_regenerate_id(true);
        $_SESSION['user'] = $user;
        unset($_SESSION['login_attempts']);
        audit_log($user['tenant_id'] ? (int) $user['tenant_id'] : null, (int) $user['id'], 'login', 'User login');
        redirect('/dashboard');
    }

    public function logout(): void
    {
        Csrf::verify();
        $_SESSION = [];
        $params = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires' => time() - 3600,
            'path' => $params['path'] ?? '/',
            'domain' => $params['domain'] ?? '',
            'secure' => (bool) ($params['secure'] ?? false),
            'httponly' => true,
            'samesite' => $params['samesite'] ?? 'Lax',
        ]);
        session_destroy();
        redirect('/login');
    }
}
