<?php

declare(strict_types=1);

namespace ChallengeChain\Services;

use ChallengeChain\Core\Database;
use ChallengeChain\Core\HttpException;

final class ScoringService
{
    public const POINTS_PER_CORRECT = 100;
    public const PERFECT_BONUS = 25;

    /**
     * Server-side scoring. The browser never sees correct answers before this runs.
     *
     * @param array<string, int|string> $answers question_id => option_id
     * @return array{score:int, max_score:int, correct:int, total:int, duration_ms:int, xp:int}
     */
    public static function grade(int $attemptId, int $challengeId, array $answers, int $durationMs): array
    {
        return Database::transaction(static function () use ($attemptId, $challengeId, $answers, $durationMs): array {
            // Re-check status inside the transaction (duplicate submission protection).
            $attempt = Database::fetch('SELECT * FROM attempts WHERE id = :id', ['id' => $attemptId]);
            if ($attempt === null || $attempt['status'] !== 'pending') {
                throw new HttpException(409, 'This attempt was already submitted.');
            }

            $questions = Database::fetchAll(
                'SELECT id FROM questions WHERE challenge_id = :cid ORDER BY position ASC, id ASC',
                ['cid' => $challengeId]
            );
            $total = count($questions);
            if ($total === 0) {
                throw new HttpException(422, 'This challenge has no questions.');
            }

            $correct = 0;
            $rows = [];

            foreach ($questions as $q) {
                $qid = (int) $q['id'];
                $selectedRaw = $answers[$qid] ?? $answers[(string) $qid] ?? null;
                $selectedId = is_numeric($selectedRaw) ? (int) $selectedRaw : null;

                $isCorrect = false;
                if ($selectedId !== null) {
                    $option = Database::fetch(
                        'SELECT id, is_correct FROM question_options WHERE id = :oid AND question_id = :qid',
                        ['oid' => $selectedId, 'qid' => $qid]
                    );
                    if ($option !== null) {
                        $isCorrect = (int) $option['is_correct'] === 1;
                    }
                }
                if ($isCorrect) {
                    $correct++;
                }
                $rows[] = [
                    'question_id' => $qid,
                    'selected_option_id' => $selectedId,
                    'is_correct' => $isCorrect,
                ];
            }

            $score = $correct * self::POINTS_PER_CORRECT;
            $maxScore = $total * self::POINTS_PER_CORRECT + self::PERFECT_BONUS;
            if ($correct === $total) {
                $score += self::PERFECT_BONUS;
            }

            $updated = Database::query(
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
                    'id' => $attemptId,
                ]
            );
            if ($updated->rowCount() === 0) {
                throw new HttpException(409, 'This attempt was already submitted.');
            }

            foreach ($rows as $row) {
                Database::query(
                    <<<'SQL'
                    INSERT INTO attempt_answers (attempt_id, question_id, selected_option_id, is_correct)
                    VALUES (:a, :q, :o, :c)
                    ON CONFLICT(attempt_id, question_id) DO UPDATE SET
                        selected_option_id = excluded.selected_option_id,
                        is_correct = excluded.is_correct
                    SQL,
                    [
                        'a' => $attemptId,
                        'q' => $row['question_id'],
                        'o' => $row['selected_option_id'],
                        'c' => $row['is_correct'] ? 1 : 0,
                    ]
                );
            }

            $xp = (int) floor($score / 10);
            if ((int) $attempt['user_id'] > 0) {
                Database::query('UPDATE users SET xp = xp + :xp WHERE id = :id', ['xp' => $xp, 'id' => $attempt['user_id']]);
            }

            return [
                'score' => $score,
                'max_score' => $maxScore,
                'correct' => $correct,
                'total' => $total,
                'duration_ms' => max(0, $durationMs),
                'xp' => $xp,
            ];
        });
    }
}
