<?php

declare(strict_types=1);

namespace ChallengeChain\Core;

final class Csrf
{
    public static function token(): string
    {
        if (empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public static function field(): string
    {
        return '<input type="hidden" name="_csrf" value="' . e(self::token()) . '">';
    }

    public static function verify(): void
    {
        $method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
        if (in_array($method, ['GET', 'HEAD', 'OPTIONS'], true)) {
            return;
        }

        $path = parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?: '/';
        $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($_POST['_csrf'] ?? '');
        if (!is_string($token) || $token === '' || !hash_equals(self::token(), $token)) {
            if (str_starts_with($path, '/api/')) {
                json_response(['error' => 'Invalid or missing CSRF token. Refresh the page and try again.'], 419);
            }
            render_error(419, 'Invalid or missing CSRF token. Please refresh the page and try again.');
        }
    }
}
