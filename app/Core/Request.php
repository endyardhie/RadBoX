<?php

namespace App\Core;

class Request
{
    public function method(): string
    {
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    }

    public function path(): string
    {
        $route = $_GET['route'] ?? null;
        if ($route) {
            return '/' . trim(parse_url($route, PHP_URL_PATH), '/');
        }
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        $script = dirname($_SERVER['SCRIPT_NAME'] ?? '');
        if ($script !== '/' && str_starts_with($path, $script)) {
            $path = substr($path, strlen($script));
        }
        $path = '/' . trim($path, '/');
        return $path === '//' ? '/' : $path;
    }

    public function input(string $key, mixed $default = null): mixed
    {
        return $_POST[$key] ?? $_GET[$key] ?? $default;
    }

    public function all(): array
    {
        return $this->method() === 'POST' ? $_POST : $_GET;
    }
}
