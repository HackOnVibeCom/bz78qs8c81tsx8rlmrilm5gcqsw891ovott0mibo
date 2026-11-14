<?php

declare(strict_types=1);

namespace ChallengeChain\Services;

use ChallengeChain\Core\Database;
use ChallengeChain\Models\Attempt;
use ChallengeChain\Models\Challenge;
use ChallengeChain\Models\Question;

final class AttemptService
{
    /**
     * Starts an attempt and returns gameplay data WITHOUT correct answers.
     *
     * @return array<string, mixed>
     */
    public function start(array $challenge, ?array $user, ?string $participantName, ?string $referralCode): array
    {
        $questionCount = (int) Database::fetch(
            'SELECT COUNT(*) AS c FROM questions WHERE challenge_id = :cid',
            ['cid' => $challenge['id']]
        )['c'];

        if ($questionCount === 0) {
            throw new \ChallengeChain\Core\HttpException(422, 'This challenge has no questions yet.');
        }

        $name = trim((string) ($participantName ?? ''));
        if ($name === '') {
            $name = $user !== null && ($user['display_name'] ?? '') !== '' ? (string) $user['display_name'] : 'Anonymous';
        }
        $attempt = Attempt::create([
            'challenge_id' => (int) $challenge['id'],
            'user_id' => $user['id'] ?? null,
            'participant_name' => $name,
            'max_score' => $questionCount * 100 + 25,
            'total_questions' => $questionCount,
            'referral_code' => $referralCode,
            'referrer_user_id' => $referralCode !== null ? (new ReferralService())->userIdFromCode($referralCode) : null,
            'ip_hash' => ip_hash(),
        ]);

        return [
            'attempt_id' => $attempt['public_id'],
            'challenge' => [
                'slug' => $challenge['slug'],
                'title' => $challenge['title'],
                'difficulty' => $challenge['difficulty'],
                'question_count' => $questionCount,
            ],
            'participant' => $attempt['participant_name'],
            'questions' => Question::playableForChallenge((int) $challenge['id']),
            'started_at' => $attempt['started_at'],
        ];
    }

    /**
     * Grades a pending attempt server-side, in one transaction.
     * Duplicate submissions are rejected.
     *
     * @param array<string, int|string> $answers question_id => option_id
     * @return array<string, mixed>
     */
    public function submit(string $publicId, array $answers, ?int $sessionUserId, array $sessionAttemptIds): array
    {
        $attempt = Attempt::findByPublicId($publicId);
        if ($attempt === null) {
            throw new \ChallengeChain\Core\HttpException(404, 'Attempt not found.');
        }

        $owns = ($sessionUserId !== null && (int) $attempt['user_id'] === $sessionUserId)
            || in_array($publicId, $sessionAttemptIds, true);
        if (!$owns) {
            throw new \ChallengeChain\Core\HttpException(403, 'This attempt does not belong to you.');
        }

        if ($attempt['status'] === 'completed') {
            throw new \ChallengeChain\Core\HttpException(409, 'This attempt was already submitted.');
        }

        $started = strtotime($attempt['started_at'] . ' UTC');
        $elapsedMs = $started === false ? 0 : max(0, (time() - $started) * 1000);
        if ($elapsedMs > 6 * 3600 * 1000) {
            Database::query("UPDATE attempts SET status = 'expired' WHERE id = :id AND status = 'pending'", ['id' => $attempt['id']]);
            throw new \ChallengeChain\Core\HttpException(410, 'This attempt expired. Please start a new one.');
        }

        $graded = ScoringService::grade((int) $attempt['id'], (int) $attempt['challenge_id'], $answers, $elapsedMs);

        // Refresh for response.
        $fresh = Attempt::find((int) $attempt['id']);
        $challenge = Challenge::find((int) $attempt['challenge_id']);

        if ($fresh !== null && $challenge !== null && (int) $fresh['user_id'] > 0) {
            GamificationService::afterAttempt($fresh, $challenge);
        }

        return [
            'score' => $graded['score'],
            'max_score' => $graded['max_score'],
            'correct' => $graded['correct'],
            'total' => $graded['total'],
            'duration_ms' => $graded['duration_ms'],
            'result_url' => base_url('/result/' . $publicId),
            'leaderboard_url' => base_url('/challenge/' . ($challenge['slug'] ?? '') . '/leaderboard'),
            'xp_earned' => $graded['xp'] ?? 0,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function leaderboard(int $challengeId, int $limit = 50): array
    {
        $rows = Attempt::leaderboard($challengeId, $limit);
        foreach ($rows as $i => $row) {
            $row['rank'] = $i + 1;
            $rows[$i] = $row;
        }
        return $rows;
    }
}
