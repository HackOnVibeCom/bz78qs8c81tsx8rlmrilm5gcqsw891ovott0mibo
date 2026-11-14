<?php

declare(strict_types=1);

namespace ChallengeChain\Models;

use ChallengeChain\Core\Database;

final class Attempt
{
    public static function find(int $id): ?array
    {
        return Database::fetch('SELECT * FROM attempts WHERE id = :id', ['id' => $id]);
    }

    public static function findByPublicId(string $publicId): ?array
    {
        return Database::fetch('SELECT * FROM attempts WHERE public_id = :pid', ['pid' => $publicId]);
    }

    public static function create(array $data): array
    {
        $publicId = self::uniquePublicId();
        $id = Database::insert('attempts', [
            'public_id' => $publicId,
            'challenge_id' => $data['challenge_id'],
            'user_id' => $data['user_id'] ?? null,
            'participant_name' => mb_substr(trim((string) ($data['participant_name'] ?? 'Anonymous')), 0, 60) ?: 'Anonymous',
            'status' => 'pending',
            'max_score' => (int) ($data['max_score'] ?? 0),
            'total_questions' => (int) ($data['total_questions'] ?? 0),
            'referral_code' => $data['referral_code'] ?? null,
            'referrer_user_id' => $data['referrer_user_id'] ?? null,
            'ip_hash' => (string) ($data['ip_hash'] ?? ''),
            'started_at' => gmdate('Y-m-d H:i:s'),
        ]);
        $attempt = self::find($id);
        if ($attempt === null) {
            throw new \RuntimeException('Failed to create attempt.');
        }
        return $attempt;
    }

    public static function complete(int $id, int $score, int $maxScore, int $correct, int $total, int $durationMs): void
    {
        Database::query(
            <<<'SQL'
            UPDATE attempts
            SET status = 'completed', score = :score, max_score = :max_score,
                correct_count = :correct, total_questions = :total,
                duration_ms = :duration, completed_at = datetime('now')
            WHERE id = :id AND status = 'pending'
            SQL,
            [
                'score' => $score,
                'max_score' => $maxScore,
                'correct' => $correct,
                'total' => $total,
                'duration' => max(0, $durationMs),
                'id' => $id,
            ]
        );
    }

    /**
     * Leaderboard rows — best attempt per participant, ranked by score then speed.
     *
     * @return list<array<string, mixed>>
     */
    public static function leaderboard(int $challengeId, int $limit = 50): array
    {
        return Database::fetchAll(
            <<<'SQL'
            SELECT * FROM (
                SELECT a.*,
                       u.username AS username,
                       ROW_NUMBER() OVER (
                           PARTITION BY COALESCE('u' || a.user_id, 'g' || a.ip_hash || '|' || a.participant_name)
                           ORDER BY a.score DESC, a.duration_ms ASC, a.completed_at ASC
                       ) AS rn
                FROM attempts a
                LEFT JOIN users u ON u.id = a.user_id
                WHERE a.challenge_id = :cid AND a.status = 'completed'
            ) ranked
            WHERE rn = 1
            ORDER BY score DESC, duration_ms ASC, completed_at ASC
            LIMIT :lim
            SQL,
            ['cid' => $challengeId, 'lim' => $limit]
        );
    }

    public static function rankOf(int $challengeId, int $attemptId): int
    {
        $rows = self::leaderboard($challengeId, 1000);
        foreach ($rows as $i => $row) {
            if ((int) $row['id'] === $attemptId) {
                return $i + 1;
            }
        }
        return 0;
    }

    /** @return list<array<string, mixed>> */
    public static function forUser(int $userId, int $limit = 10): array
    {
        return Database::fetchAll(
            <<<'SQL'
            SELECT a.*, c.title AS challenge_title, c.slug AS challenge_slug
            FROM attempts a
            JOIN challenges c ON c.id = a.challenge_id
            WHERE a.user_id = :uid AND a.status = 'completed'
            ORDER BY a.completed_at DESC
            LIMIT :lim
            SQL,
            ['uid' => $userId, 'lim' => $limit]
        );
    }

    /** @return list<array<string, mixed>> Recent completions on challenges owned by the user. */
    public static function recentOnOwnChallenges(int $userId, int $limit = 10): array
    {
        return Database::fetchAll(
            <<<'SQL'
            SELECT a.*, c.title AS challenge_title, c.slug AS challenge_slug
            FROM attempts a
            JOIN challenges c ON c.id = a.challenge_id
            WHERE c.user_id = :uid AND a.status = 'completed'
            ORDER BY a.completed_at DESC
            LIMIT :lim
            SQL,
            ['uid' => $userId, 'lim' => $limit]
        );
    }

    private static function uniquePublicId(): string
    {
        for ($i = 0; $i < 30; $i++) {
            $id = strtolower(random_code(10));
            if (self::findByPublicId($id) === null) {
                return $id;
            }
        }
        return strtolower(random_code(16));
    }
}
