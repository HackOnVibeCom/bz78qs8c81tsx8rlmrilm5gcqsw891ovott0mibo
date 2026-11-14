<?php

declare(strict_types=1);

namespace ChallengeChain\Controllers;

use ChallengeChain\Core\Auth;
use ChallengeChain\Core\Csrf;
use ChallengeChain\Core\HttpException;

abstract class Controller
{
    protected function view(string $template, array $data = [], string $layout = 'main'): void
    {
        \ChallengeChain\Core\View::render($template, $data, $layout);
    }

    protected function json(mixed $data, int $status = 200): never
    {
        json_response($data, $status);
    }

    protected function requireLogin(): array
    {
        $user = Auth::user();
        if ($user === null) {
            if ($this->wantsJson()) {
                json_response(['error' => 'Authentication required.'], 401);
            }
            $_SESSION['after_login'] = $_SERVER['REQUEST_URI'] ?? '/dashboard';
            set_flash('error', 'Please sign in to continue.');
            redirect('/login');
        }
        return $user;
    }

    protected function requireGuest(): void
    {
        if (Auth::check()) {
            redirect('/dashboard');
        }
    }

    protected function wantsJson(): bool
    {
        $path = parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?: '';
        if (str_starts_with($path, '/api/')) {
            return true;
        }
        $accept = (string) ($_SERVER['HTTP_ACCEPT'] ?? '');
        return str_contains($accept, 'application/json');
    }

    /** Merged input: JSON body + form POST. */
    protected function input(): array
    {
        static $input = null;
        if ($input !== null) {
            return $input;
        }
        $input = [];
        $contentType = (string) ($_SERVER['CONTENT_TYPE'] ?? '');
        if (str_contains($contentType, 'application/json')) {
            $raw = file_get_contents('php://input') ?: '';
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                $input = $decoded;
            }
        }
        foreach ($_POST as $key => $value) {
            if (!array_key_exists($key, $input) && is_string($value)) {
                $input[$key] = $value;
            }
        }
        return $input;
    }

    protected function str(string $key, string $default = '', int $max = 500): string
    {
        $value = $this->input()[$key] ?? $default;
        if (!is_string($value) && !is_numeric($value)) {
            return $default;
        }
        return mb_substr(trim((string) $value), 0, $max);
    }

    protected function bool(string $key, bool $default = false): bool
    {
        $value = $this->input()[$key] ?? null;
        if ($value === null) {
            return $default;
        }
        if (is_bool($value)) {
            return $value;
        }
        return in_array(strtolower((string) $value), ['1', 'true', 'yes', 'on'], true);
    }

    protected function verifyCsrf(): void
    {
        Csrf::verify();
    }

    protected function notFound(string $message = ''): never
    {
        throw new HttpException(404, $message !== '' ? $message : 'This page does not exist.');
    }
}
