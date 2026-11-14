<?php

declare(strict_types=1);

namespace ChallengeChain\Core;

/**
 * File-based sliding-window rate limiter (works on shared PHP hosting, no extra tables).
 */
final class RateLimiter
{
    private static function file(string $key): string
    {
        return storage_path('logs/ratelimit_' . hash('sha256', $key) . '.json');
    }

    private static function read(string $file): array
    {
        if (!is_file($file)) {
            return [];
        }
        $raw = @file_get_contents($file);
        if ($raw === false) {
            return [];
        }
        $data = json_decode($raw, true);
        return is_array($data) ? $data : [];
    }

    private static function write(string $file, array $data): void
    {
        $dir = dirname($file);
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        @file_put_contents($file, json_encode($data), LOCK_EX);
    }

    /** Returns true when the key has already exhausted its allowance. */
    public static function isBlocked(string $key, int $max, int $windowSeconds): bool
    {
        $file = self::file($key);
        $data = self::read($file);
        $now = time();

        if ($data === [] || (int) ($data['reset_at'] ?? 0) <= $now) {
            return false;
        }
        return (int) ($data['count'] ?? 0) >= $max;
    }

    /** Registers one attempt. Returns false when the allowance is exhausted. */
    public static function hit(string $key, int $max, int $windowSeconds): bool
    {
        $file = self::file($key);
        $data = self::read($file);
        $now = time();

        if ($data === [] || (int) ($data['reset_at'] ?? 0) <= $now) {
            $data = ['count' => 0, 'reset_at' => $now + $windowSeconds];
        }
        if ((int) $data['count'] >= $max) {
            return false;
        }
        $data['count'] = (int) $data['count'] + 1;
        self::write($file, $data);
        return true;
    }

    public static function remaining(string $key, int $max, int $windowSeconds): int
    {
        $file = self::file($key);
        $data = self::read($file);
        $now = time();
        if ($data === [] || (int) ($data['reset_at'] ?? 0) <= $now) {
            return $max;
        }
        return max(0, $max - (int) ($data['count'] ?? 0));
    }

    public static function reset(string $key): void
    {
        $file = self::file($key);
        if (is_file($file)) {
            @unlink($file);
        }
    }
}
