<?php

declare(strict_types=1);

namespace ChallengeChain\Services;

use ChallengeChain\Core\Database;
use ChallengeChain\Models\Achievement;

/**
 * XP + achievements (secondary priority — never blocks core flows).
 */
final class GamificationService
{
    public const XP_CHALLENGE_CREATED = 20;

    public static function awardXp(int $userId, int $xp, string $reason): void
    {
        if ($xp <= 0) {
            return;
        }
        Database::query('UPDATE users SET xp = xp + :xp, updated_at = datetime(\'now\') WHERE id = :id', [
            'xp' => $xp,
            'id' => $userId,
        ]);
        log_message('info', "XP +{$xp} for user {$userId} ({$reason})");
    }

    /** @param array<string, mixed> $attempt @param array<string, mixed> $challenge */
    public static function afterAttempt(array $attempt, array $challenge): void
    {
        $userId = (int) ($attempt['user_id'] ?? 0);
        if ($userId <= 0) {
            return;
        }
        try {
            self::maybeGrant($userId, 'rookie_competitor');

            if ((int) $attempt['correct_count'] === (int) $attempt['total_questions'] && (int) $attempt['total_questions'] > 0) {
                self::maybeGrant($userId, 'perfectionist');
                if ((int) $attempt['duration_ms'] > 0 && (int) $attempt['duration_ms'] < 60000) {
                    self::maybeGrant($userId, 'speed_demon');
                }
            }

            $distinct = Database::fetch(
                "SELECT COUNT(DISTINCT challenge_id) AS c FROM attempts WHERE user_id = :u AND status = 'completed'",
                ['u' => $userId]
            );
            if ((int) ($distinct['c'] ?? 0) >= 5) {
                self::maybeGrant($userId, 'explorer');
            }
        } catch (\Throwable $e) {
            log_message('error', 'Gamification (attempt) failed: ' . $e->getMessage());
        }
    }

    public static function afterChallengeCreated(int $userId): void
    {
        try {
            self::maybeGrant($userId, 'first_step');
        } catch (\Throwable $e) {
            log_message('error', 'Gamification (create) failed: ' . $e->getMessage());
        }
    }

    /** @param array<string, mixed> $challenge */
    public static function afterShare(array $challenge, int $userId): void
    {
        try {
            $row = Database::fetch(
                'SELECT share_count FROM challenges WHERE id = :id',
                ['id' => $challenge['id']]
            );
            if ((int) ($row['share_count'] ?? 0) >= 5) {
                self::maybeGrant($userId, 'social_butterfly');
            }
        } catch (\Throwable $e) {
            log_message('error', 'Gamification (share) failed: ' . $e->getMessage());
        }
    }

    public static function afterParticipants(int $userId, int $participants): void
    {
        try {
            if ($participants >= 10) {
                self::maybeGrant($userId, 'crowd_favorite');
            }
        } catch (\Throwable $e) {
            log_message('error', 'Gamification (participants) failed: ' . $e->getMessage());
        }
    }

    private static function maybeGrant(int $userId, string $code): void
    {
        $achievement = Achievement::findByCode($code);
        if ($achievement === null) {
            return;
        }
        if (Achievement::grant($userId, (int) $achievement['id'])) {
            self::awardXp($userId, (int) $achievement['xp_reward'], 'achievement:' . $code);
        }
    }
}
