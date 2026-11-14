<?php

declare(strict_types=1);

namespace ChallengeChain\Services;

use ChallengeChain\Core\Database;
use ChallengeChain\Core\HttpException;
use ChallengeChain\Models\Challenge;
use ChallengeChain\Models\Question;

final class ChallengeService
{
    /**
     * Creates a challenge (AI, demo or validated manual payload) inside a transaction.
     *
     * @param array<string, mixed> $payload Normalised payload (title, description, questions…)
     * @return array<string, mixed> The created challenge row
     */
    public function create(?int $userId, array $payload, string $source = 'ai', bool $isDemo = false): array
    {
        $validated = AIService::validatePayload($payload);
        $questions = $validated['questions'];
        if (count($questions) < 1) {
            throw new HttpException(422, 'A challenge needs at least one question.');
        }

        $category = (string) ($payload['category'] ?? 'general');
        if (!array_key_exists($category, DemoQuestionBank::CATEGORIES)) {
            $category = 'general';
        }
        $difficulty = (string) ($payload['difficulty'] ?? 'medium');
        if (!in_array($difficulty, ['easy', 'medium', 'hard'], true)) {
            $difficulty = 'medium';
        }
        $language = (string) ($payload['language'] ?? 'en');
        $topic = trim((string) ($payload['topic'] ?? ''));
        $isPublic = !isset($payload['is_public']) || (bool) $payload['is_public'];

        return Database::transaction(static function () use ($userId, $validated, $questions, $category, $difficulty, $language, $topic, $isPublic, $source, $isDemo): array {
            $slug = Challenge::uniqueSlug($validated['title']);

            $challengeId = Database::insert('challenges', [
                'user_id' => $userId,
                'slug' => $slug,
                'title' => $validated['title'],
                'description' => $validated['description'],
                'category' => $category,
                'topic' => $topic !== '' ? $topic : $validated['title'],
                'difficulty' => $difficulty,
                'language' => $language,
                'question_count' => count($questions),
                'is_public' => $isPublic ? 1 : 0,
                'status' => 'published',
                'is_demo' => $isDemo ? 1 : 0,
                'source' => in_array($source, ['ai', 'demo', 'manual'], true) ? $source : 'ai',
            ]);

            foreach ($questions as $position => $q) {
                $questionId = Database::insert('questions', [
                    'challenge_id' => $challengeId,
                    'position' => $position,
                    'question_text' => $q['question'],
                    'explanation' => $q['explanation'] ?? '',
                ]);
                foreach ($q['options'] as $optIndex => $optionText) {
                    Database::insert('question_options', [
                        'question_id' => $questionId,
                        'option_text' => $optionText,
                        'position' => $optIndex,
                        'is_correct' => $optIndex === (int) $q['correct_index'] ? 1 : 0,
                    ]);
                }
            }

            $challenge = Challenge::find($challengeId);
            if ($challenge === null) {
                throw new HttpException(500, 'Could not save the challenge.');
            }
            return $challenge;
        });
    }

    /** Published challenges are playable by link. Unlisted ones stay out of Explore. */
    public function requirePublic(string $slug): array
    {
        $challenge = Challenge::findBySlug($slug);
        if ($challenge === null || $challenge['status'] !== 'published') {
            throw new HttpException(404, 'This challenge does not exist or is no longer available.');
        }
        return $challenge;
    }

    public function requireOwn(int $userId, string $slug): array
    {
        $challenge = Challenge::findBySlug($slug);
        if ($challenge === null || (int) $challenge['user_id'] !== $userId) {
            throw new HttpException(404, 'Challenge not found.');
        }
        return $challenge;
    }

    /**
     * Full shareable view model for the public challenge page.
     *
     * @return array<string, mixed>
     */
    public function publicView(array $challenge, ?int $viewerUserId): array
    {
        $author = null;
        if ($challenge['user_id'] !== null) {
            $author = Database::fetch(
                'SELECT username, display_name FROM users WHERE id = :id',
                ['id' => $challenge['user_id']]
            );
        }
        return [
            'challenge' => $challenge,
            'author' => $author,
            'participants' => Challenge::participantCount((int) $challenge['id']),
            'questions_preview' => array_slice(Question::playableForChallenge((int) $challenge['id']), 0, 0),
            'leaderboard' => (new AttemptService())->leaderboard((int) $challenge['id'], 5),
            'viewer_id' => $viewerUserId,
        ];
    }
}
