<?php

declare(strict_types=1);

namespace ChallengeChain\Core;

use ChallengeChain\Models\User;

final class Auth
{
    private static ?array $user = null;
    private static bool $loaded = false;

    public static function user(): ?array
    {
        if (self::$loaded) {
            return self::$user;
        }
        self::$loaded = true;
        $id = $_SESSION['user_id'] ?? null;
        if ($id === null) {
            self::$user = null;
            return null;
        }
        $user = User::find((int) $id);
        if ($user === null) {
            unset($_SESSION['user_id']);
        }
        self::$user = $user;
        return self::$user;
    }

    public static function id(): ?int
    {
        $user = self::user();
        return $user === null ? null : (int) $user['id'];
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function login(array $user): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
        $_SESSION['user_id'] = (int) $user['id'];
        unset($_SESSION['attempt_ids']);
        self::$user = $user;
        self::$loaded = true;
    }

    public static function logout(): void
    {
        unset($_SESSION['user_id'], $_SESSION['attempt_ids']);
        self::$user = null;
        self::$loaded = true;
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
    }

    /**
     * Rate-limited login attempt. Returns the user on success, null on bad credentials.
     * Throws HttpException 429 when the limit is exceeded.
     */
    public static function attempt(string $email, string $password): ?array
    {
        $key = 'login:' . ip_hash() . '|' . mb_strtolower(trim($email));
        $max = (int) config('security.login_max_attempts', 5);
        $window = (int) config('security.login_window', 900);

        if (RateLimiter::isBlocked($key, $max, $window)) {
            throw new HttpException(429, 'Too many login attempts. Please wait a few minutes and try again.');
        }

        $user = User::findByEmail($email);
        if ($user === null || !password_verify($password, $user['password_hash'])) {
            RateLimiter::hit($key, $max, $window);
            return null;
        }

        if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
            User::updatePassword((int) $user['id'], $password);
        }

        RateLimiter::reset($key);
        $fresh = User::find((int) $user['id']);
        if ($fresh === null) {
            return null;
        }
        self::login($fresh);
        return $fresh;
    }
}
