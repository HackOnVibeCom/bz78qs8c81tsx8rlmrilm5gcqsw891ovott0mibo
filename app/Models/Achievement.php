<?php

declare(strict_types=1);

namespace ChallengeChain\Models;

use ChallengeChain\Core\Database;

final class Achievement
{
    /** @return list<array<string, mixed>> */
    public static function all(): array
    {
        return Database::fetchAll('SELECT * FROM achievements ORDER BY xp_reward ASC, id ASC');
    }

    public static function findByCode(string $code): ?array
    {
        return Database::fetch('SELECT * FROM achievements WHERE code = :code', ['code' => $code]);
    }

    /** @return list<array<string, mixed>> Achievement definitions earned by a user. */
    public static function forUser(int $userId): array
    {
        return Database::fetchAll(
            <<<'SQL'
            SELECT a.*, ua.awarded_at
            FROM user_achievements ua
            JOIN achievements a ON a.id = ua.achievement_id
            WHERE ua.user_id = :uid
            ORDER BY ua.awarded_at DESC
            SQL,
            ['uid' => $userId]
        );
    }

    public static function has(int $userId, int $achievementId): bool
    {
        $row = Database::fetch(
            'SELECT id FROM user_achievements WHERE user_id = :u AND achievement_id = :a',
            ['u' => $userId, 'a' => $achievementId]
        );
        return $row !== null;
    }

    public static function grant(int $userId, int $achievementId): bool
    {
        if (self::has($userId, $achievementId)) {
            return false;
        }
        Database::query(
            'INSERT OR IGNORE INTO user_achievements (user_id, achievement_id) VALUES (:u, :a)',
            ['u' => $userId, 'a' => $achievementId]
        );
        return true;
    }
}
