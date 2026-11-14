<?php

declare(strict_types=1);

namespace ChallengeChain\Models;

use ChallengeChain\Core\Database;

final class PasswordReset
{
    public static function create(int $userId, string $token, int $ttlSeconds = 3600): array
    {
        Database::query('DELETE FROM password_reset_tokens WHERE user_id = :u OR expires_at < datetime(\'now\')', ['u' => $userId]);
        $id = Database::insert('password_reset_tokens', [
            'user_id' => $userId,
            'token_hash' => hash('sha256', $token),
            'expires_at' => gmdate('Y-m-d H:i:s', time() + $ttlSeconds),
        ]);
        return ['id' => $id, 'token' => $token];
    }

    public static function validToken(string $token): ?array
    {
        return Database::fetch(
            <<<'SQL'
            SELECT * FROM password_reset_tokens
            WHERE token_hash = :hash AND used_at IS NULL AND expires_at > datetime('now')
            SQL,
            ['hash' => hash('sha256', $token)]
        );
    }

    public static function markUsed(int $id): void
    {
        Database::query(
            "UPDATE password_reset_tokens SET used_at = datetime('now') WHERE id = :id",
            ['id' => $id]
        );
    }
}
