<?php

declare(strict_types=1);

namespace ChallengeChain\Models;

use ChallengeChain\Core\Database;

final class Question
{
    /** @return list<array<string, mixed>> */
    public static function forChallenge(int $challengeId): array
    {
        return Database::fetchAll(
            'SELECT * FROM questions WHERE challenge_id = :cid ORDER BY position ASC, id ASC',
            ['cid' => $challengeId]
        );
    }

    /** @return list<array<string, mixed>> Options for a question. */
    public static function options(int $questionId): array
    {
        return Database::fetchAll(
            'SELECT * FROM question_options WHERE question_id = :qid ORDER BY position ASC, id ASC',
            ['qid' => $questionId]
        );
    }

    /**
     * Public payload for gameplay — NEVER includes correct answers.
     *
     * @return list<array{id:int, position:int, question:string, options:list<array{id:int, text:string}>}>
     */
    public static function playableForChallenge(int $challengeId): array
    {
        $questions = self::forChallenge($challengeId);
        $out = [];
        foreach ($questions as $q) {
            $options = self::options((int) $q['id']);
            $out[] = [
                'id' => (int) $q['id'],
                'position' => (int) $q['position'],
                'question' => (string) $q['question_text'],
                'options' => array_map(
                    static fn (array $o): array => ['id' => (int) $o['id'], 'text' => (string) $o['option_text']],
                    $options
                ),
            ];
        }
        return $out;
    }

    /**
     * Full payload including correctness — only for grading / post-completion review.
     *
     * @return list<array{id:int, question:string, explanation:string, options:list<array{id:int, text:string, is_correct:bool}>}>
     */
    public static function gradedForChallenge(int $challengeId): array
    {
        $questions = self::forChallenge($challengeId);
        $out = [];
        foreach ($questions as $q) {
            $options = self::options((int) $q['id']);
            $out[] = [
                'id' => (int) $q['id'],
                'question' => (string) $q['question_text'],
                'explanation' => (string) $q['explanation'],
                'options' => array_map(
                    static fn (array $o): array => [
                        'id' => (int) $o['id'],
                        'text' => (string) $o['option_text'],
                        'is_correct' => (int) $o['is_correct'] === 1,
                    ],
                    $options
                ),
            ];
        }
        return $out;
    }
}
