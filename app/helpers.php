<?php

declare(strict_types=1);

use ChallengeChain\Core\Csrf;
use ChallengeChain\Core\HttpException;

if (!function_exists('env')) {
    function env(string $key, mixed $default = null): mixed
    {
        static $loaded = false;
        static $vars = [];

        if (!$loaded) {
            $loaded = true;
            $file = dirname(__DIR__) . '/.env';
            if (is_file($file)) {
                foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
                    $line = trim($line);
                    if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
                        continue;
                    }
                    [$name, $value] = explode('=', $line, 2);
                    $name = trim($name);
                    $value = trim($value);
                    if ($value !== '' && ($value[0] === '"' || $value[0] === "'") && str_ends_with($value, $value[0])) {
                        $value = substr($value, 1, -1);
                    }
                    $vars[$name] = $value;
                }
            }
        }

        // An explicitly empty environment variable overrides .env too.
        // Tests can disable real AI calls without reusing production secrets.
        $value = array_key_exists($key, $_ENV) ? $_ENV[$key] : getenv($key);
        if ($value === false) {
            $value = $vars[$key] ?? null;
        }
        if ($value === null || $value === '') {
            return $default;
        }
        return $value;
    }
}

if (!function_exists('config')) {
    function config(string $key, mixed $default = null): mixed
    {
        static $config = null;
        if ($config === null) {
            $config = require dirname(__DIR__) . '/config/config.php';
        }
        $segments = explode('.', $key);
        $value = $config;
        foreach ($segments as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }
        return $value;
    }
}

if (!function_exists('e')) {
    function e(mixed $value): string
    {
        return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('base_url')) {
    function base_url(string $path = ''): string
    {
        if ($path === '') {
            return (string) config('app.url');
        }
        if (preg_match('#^https?://#i', $path)) {
            return $path;
        }
        return rtrim((string) config('app.url'), '/') . '/' . ltrim($path, '/');
    }
}

if (!function_exists('url')) {
    function url(string $path = ''): string
    {
        if (preg_match('#^https?://#i', $path)) {
            return $path;
        }
        // Navigation, forms and assets stay on the origin serving this page.
        // APP_URL remains the canonical address for public sharing links.
        $basePath = rtrim((string) parse_url((string) config('app.url'), PHP_URL_PATH), '/');
        return $basePath . '/' . ltrim($path, '/');
    }
}

if (!function_exists('asset_url')) {
    function asset_url(string $path): string
    {
        $url = url($path);
        $file = dirname(__DIR__) . '/public/' . ltrim($path, '/');
        if (is_file($file)) {
            $url .= '?v=' . filemtime($file);
        }
        return $url;
    }
}

if (!function_exists('current_url')) {
    function current_url(): string
    {
        $path = $_SERVER['REQUEST_URI'] ?? '/';
        return base_url($path);
    }
}

if (!function_exists('redirect')) {
    function redirect(string $path, int $status = 302): never
    {
        $target = url($path);
        if (!headers_sent()) {
            http_response_code($status);
            header('Location: ' . $target);
        }
        exit;
    }
}

if (!function_exists('json_response')) {
    function json_response(mixed $data, int $status = 200): never
    {
        if (!headers_sent()) {
            http_response_code($status);
            header('Content-Type: application/json; charset=utf-8');
            header('X-Content-Type-Options: nosniff');
        }
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
        exit;
    }
}

if (!function_exists('abort')) {
    function abort(int $status, string $message = ''): never
    {
        throw new HttpException($status, $message !== '' ? $message : match ($status) {
            403 => 'Forbidden',
            404 => 'Page not found',
            419 => 'Session expired. Please refresh and try again.',
            429 => 'Too many requests. Please slow down.',
            default => 'Request failed',
        });
    }
}

if (!function_exists('session_boot')) {
    function session_boot(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }
        $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
        session_name((string) config('session.name'));
        session_set_cookie_params([
            'lifetime' => (int) config('session.lifetime'),
            'path' => '/',
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }
}

if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        return Csrf::token();
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field(): string
    {
        return Csrf::field();
    }
}

if (!function_exists('storage_path')) {
    function storage_path(string $path = ''): string
    {
        $base = dirname(__DIR__) . '/storage';
        return $path === '' ? $base : $base . '/' . ltrim($path, '/');
    }
}

if (!function_exists('log_message')) {
    function log_message(string $level, string $message): void
    {
        $dir = storage_path('logs');
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        $line = sprintf("[%s] %s: %s%s", gmdate('Y-m-d H:i:s'), strtoupper($level), $message, PHP_EOL);
        @file_put_contents($dir . '/app.log', $line, FILE_APPEND | LOCK_EX);
    }
}

if (!function_exists('render_error')) {
    function render_error(int $status, string $message = ''): never
    {
        if (PHP_SAPI === 'cli') {
            fwrite(STDERR, 'HTTP ' . $status . ': ' . $message . PHP_EOL);
            exit(1);
        }
        if (!headers_sent()) {
            http_response_code($status);
            header('Content-Type: text/html; charset=utf-8');
        }
        $template = match ($status) {
            403 => 'errors/403',
            404 => 'errors/404',
            419 => 'errors/419',
            429 => 'errors/429',
            default => 'errors/500',
        };
        try {
            ChallengeChain\Core\View::render($template, [
                'title' => $status . ' — ' . config('app.name'),
                'status' => $status,
                'message' => $message,
                'layout' => 'bare',
            ], 'bare');
        } catch (Throwable) {
            echo '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><title>' . e($status) . '</title></head><body style="font-family:system-ui;background:#000;color:#fff;display:grid;place-items:center;min-height:100vh;margin:0"><div style="text-align:center"><h1 style="font-size:64px;margin:0">' . e($status) . '</h1><p>' . e($message) . '</p><a style="color:#4da2ff" href="/">Back to home</a></div></body></html>';
        }
        exit;
    }
}

if (!function_exists('ip_hash')) {
    function ip_hash(): string
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $forwarded = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $ip = trim($forwarded[0]);
        }
        return hash('sha256', $ip . '|' . (string) config('app.name'));
    }
}

if (!function_exists('slugify')) {
    function slugify(string $text): string
    {
        $text = mb_strtolower(trim($text), 'UTF-8');
        $text = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text) ?: $text;
        $text = preg_replace('/[^a-z0-9]+/', '-', $text) ?? '';
        return trim($text, '-');
    }
}

if (!function_exists('random_code')) {
    function random_code(int $length = 8): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $out = '';
        for ($i = 0; $i < $length; $i++) {
            $out .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }
        return $out;
    }
}

if (!function_exists('format_duration')) {
    function format_duration(int $milliseconds): string
    {
        if ($milliseconds <= 0) {
            return '0s';
        }
        $seconds = $milliseconds / 1000;
        if ($seconds < 60) {
            return rtrim(rtrim(number_format($seconds, 1, '.', ''), '0'), '.') . 's';
        }
        $minutes = intdiv($milliseconds, 60000);
        $secs = intdiv($milliseconds % 60000, 1000);
        return $minutes . 'm ' . $secs . 's';
    }
}

if (!function_exists('time_ago')) {
    function time_ago(string $datetime): string
    {
        $ts = strtotime($datetime . ' UTC');
        if ($ts === false) {
            return '';
        }
        $diff = time() - $ts;
        if ($diff < 60) {
            return 'just now';
        }
        if ($diff < 3600) {
            return intdiv($diff, 60) . 'm ago';
        }
        if ($diff < 86400) {
            return intdiv($diff, 3600) . 'h ago';
        }
        if ($diff < 604800) {
            return intdiv($diff, 86400) . 'd ago';
        }
        return date('M j, Y', $ts);
    }
}

if (!function_exists('is_demo_mode')) {
    function is_demo_mode(): bool
    {
        return (string) config('ai.api_key', '') === '';
    }
}

/** Consistent category artwork across discovery, cards and the featured challenge. */
function category_icon(string $category): string
{
    return match ($category) {
        'technology' => 'code', 'science' => 'flask-conical', 'history' => 'landmark',
        'geography' => 'globe', 'sports' => 'trophy', 'movies' => 'clapperboard',
        'music' => 'music', default => 'brain',
    };
}

function category_color(string $category): string
{
    return match ($category) {
        'technology', 'movies' => 'var(--color-lavender)',
        'science', 'music' => 'var(--color-mint-pop)',
        'history', 'general' => 'var(--color-sunburst)',
        'geography' => 'var(--color-sky-wash)',
        default => 'var(--color-ember)',
    };
}

if (!function_exists('set_flash')) {
    function set_flash(string $type, string $message): void
    {
        $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
    }
}

if (!function_exists('pull_flash')) {
    /** @return array<int, array{type:string,message:string}> */
    function pull_flash(): array
    {
        $flash = $_SESSION['flash'] ?? [];
        unset($_SESSION['flash']);
        return is_array($flash) ? $flash : [];
    }
}

if (!function_exists('old')) {
    function old(string $key, string $default = ''): string
    {
        $value = $_SESSION['old'][$key] ?? $default;
        return is_string($value) ? $value : $default;
    }
}

if (!function_exists('keep_old')) {
    function keep_old(array $values): void
    {
        $_SESSION['old'] = array_map(static fn ($v) => is_string($v) ? mb_substr($v, 0, 500) : '', $values);
    }
}

if (!function_exists('clear_old')) {
    function clear_old(): void
    {
        unset($_SESSION['old']);
    }
}
