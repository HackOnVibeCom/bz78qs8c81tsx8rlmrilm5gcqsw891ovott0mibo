<?php

declare(strict_types=1);

namespace ChallengeChain\Models;

use ChallengeChain\Core\Database;

final class Challenge
{
    public static function find(int $id): ?array
    {
        return Database::fetch('SELECT * FROM challenges WHERE id = :id', ['id' => $id]);
    }

    public static function findBySlug(string $slug): ?array
    {
        return Database::fetch('SELECT * FROM challenges WHERE slug = :slug', ['slug' => $slug]);
    }

    public static function uniqueSlug(string $title): string
    {
        $base = slugify($title);
        if ($base === '') {
            $base = 'challenge';
        }
        $base = substr($base, 0, 50);
        $slug = $base;
        $n = 1;
        while (self::findBySlug($slug) !== null) {
            $n++;
            $slug = $base . '-' . $n;
        }
        if (self::findBySlug($slug) !== null) {
            $slug = $base . '-' . strtolower(random_code(6));
        }
        return $slug;
    }

    public static function incrementViews(int $id): void
    {
        Database::query('UPDATE challenges SET view_count = view_count + 1 WHERE id = :id', ['id' => $id]);
    }

    public static function incrementShares(int $id): void
    {
        Database::query('UPDATE challenges SET share_count = share_count + 1 WHERE id = :id', ['id' => $id]);
    }

    /** @return list<array<string, mixed>> */
    public static function forUser(int $userId): array
    {
        return Database::fetchAll(
            <<<'SQL'
            SELECT c.*,
                   (SELECT COUNT(*) FROM attempts a WHERE a.challenge_id = c.id AND a.status = 'completed') AS participants,
                   (SELECT COUNT(*) FROM questions q WHERE q.challenge_id = c.id) AS real_question_count
            FROM challenges c
            WHERE c.user_id = :uid
            ORDER BY c.created_at DESC
            SQL,
            ['uid' => $userId]
        );
    }

    /**
     * Public explore query with optional search/category filters.
     *
     * @return list<array<string, mixed>>
     */
    public static function explore(?string $search = null, ?string $category = null, string $sort = 'trending', int $limit = 60): array
    {
        $sql = <<<'SQL'
            SELECT c.*,
                   u.display_name AS author_name,
                   u.username AS author_username,
                   (SELECT COUNT(*) FROM attempts a WHERE a.challenge_id = c.id AND a.status = 'completed') AS participants,
                   (SELECT COUNT(*) FROM challenge_views v WHERE v.challenge_id = c.id) AS view_rows
            FROM challenges c
            LEFT JOIN users u ON u.id = c.user_id
            WHERE c.is_public = 1 AND c.status = 'published'
            SQL;
        $params = [];

        if ($search !== null && $search !== '') {
            $sql .= ' AND (c.title LIKE :q OR c.topic LIKE :q OR c.description LIKE :q OR c.category LIKE :q)';
            $params['q'] = '%' . $search . '%';
        }
        if ($category !== null && $category !== '' && $category !== 'all') {
            $sql .= ' AND c.category = :cat';
            $params['cat'] = $category;
        }

        $sql .= ' ORDER BY ';
        $sql .= match ($sort) {
            'recent' => 'c.created_at DESC',
            'popular' => 'participants DESC, c.view_count DESC, c.created_at DESC',
            'most_viewed' => 'c.view_count DESC, c.created_at DESC',
            default => '(participants * 3 + c.view_count * 2) DESC, c.created_at DESC',
        };
        $sql .= ' LIMIT :limit';
        $params['limit'] = $limit;

        $stmt = Database::query($sql, $params);
        return $stmt->fetchAll();
    }

    /** @return list<array<string, mixed>> */
    public static function trending(int $limit = 6): array
    {
        return self::explore(null, null, 'trending', $limit);
    }

    public static function participantCount(int $challengeId): int
    {
        $row = Database::fetch(
            "SELECT COUNT(*) AS c FROM attempts WHERE challenge_id = :id AND status = 'completed'",
            ['id' => $challengeId]
        );
        return (int) ($row['c'] ?? 0);
    }
}
