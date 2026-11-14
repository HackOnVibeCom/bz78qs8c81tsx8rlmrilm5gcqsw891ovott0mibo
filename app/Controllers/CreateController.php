<?php

declare(strict_types=1);

namespace ChallengeChain\Controllers;

use ChallengeChain\Core\RateLimiter;
use ChallengeChain\Core\Auth;
use ChallengeChain\Models\Challenge;
use ChallengeChain\Services\AIService;
use ChallengeChain\Services\ChallengeService;
use ChallengeChain\Services\DemoQuestionBank;
use ChallengeChain\Services\GamificationService;

/**
 * Challenge creation flow:
 *  1. GET  /create            → generator form
 *  2. POST /api/generate      → AI/demo JSON preview (no DB write)
 *  3. POST /api/challenges    → validated insert → unique public URL
 */
final class CreateController extends Controller
{
    public function index(): void
    {
        $user = Auth::user();

        $this->view('challenge/create', [
            'title' => 'Create a challenge — ChallengeChain',
            'description' => 'Configure category, topic, difficulty and let AI build your shareable challenge.',
            'user' => $user,
            'categories' => DemoQuestionBank::CATEGORIES,
            'demoMode' => is_demo_mode(),
            'pageScripts' => ['assets/js/generate.js'],
        ]);
    }

    public function generate(): void
    {
        $user = Auth::user();
        $this->verifyCsrf();

        $max = (int) config('security.ai_max_per_hour', 6);
        $key = $user !== null ? 'ai:' . $user['id'] : 'ai:guest:' . ip_hash();
        if (!RateLimiter::hit($key, $max, 3600)) {
            $this->json([
                'error' => 'AI generation limit reached (' . $max . '/hour). Try again a bit later.',
            ], 429);
        }

        $params = [
            'category' => $this->str('category', 'general', 40),
            'topic' => $this->str('topic', '', 120),
            'difficulty' => $this->str('difficulty', 'medium', 10),
            'question_count' => $this->str('question_count', '5', 4),
            'language' => $this->str('language', 'en', 10),
        ];

        try {
            $data = (new AIService())->generate($params);
        } catch (\ChallengeChain\Core\HttpException $e) {
            $this->json(['error' => $e->getMessage()], $e->getStatus() >= 500 && $e->getStatus() < 600 ? 502 : $e->getStatus());
        }

        $this->json([
            'ok' => true,
            'demo' => (bool) ($data['demo'] ?? false),
            'challenge' => $data,
            'remaining' => RateLimiter::remaining($key, $max, 3600),
        ]);
    }

    public function store(): void
    {
        $user = Auth::user();
        $this->verifyCsrf();

        $publishKey = $user !== null ? 'publish:' . $user['id'] : 'publish:guest:' . ip_hash();
        if (!RateLimiter::hit($publishKey, 20, 3600)) {
            $this->json(['error' => 'Publishing limit reached. Please try again in an hour.'], 429);
        }

        $payload = $this->input();
        unset($payload['_csrf']);

        $isDemo = !empty($payload['demo']) || is_demo_mode();
        $source = (string) ($payload['source'] ?? ($isDemo ? 'demo' : 'ai'));
        if (!in_array($source, ['ai', 'demo', 'manual'], true)) {
            $source = $isDemo ? 'demo' : 'ai';
        }

        try {
            $challenge = (new ChallengeService())->create(
                $user !== null ? (int) $user['id'] : null,
                $payload,
                $source,
                $isDemo
            );
        } catch (\ChallengeChain\Core\HttpException $e) {
            $this->json(['error' => $e->getMessage()], $e->getStatus() >= 400 && $e->getStatus() < 500 ? $e->getStatus() : 422);
        }

        if ($user !== null) {
            GamificationService::afterChallengeCreated((int) $user['id']);
            GamificationService::awardXp((int) $user['id'], GamificationService::XP_CHALLENGE_CREATED, 'challenge_created');
        }

        $shareUrl = (new \ChallengeChain\Services\ReferralService())->challengeShareUrl(
            (string) $challenge['slug'],
            $user['referral_code'] ?? null
        );

        $this->json([
            'ok' => true,
            'slug' => $challenge['slug'],
            'url' => base_url('/challenge/' . $challenge['slug']),
            'share_url' => $shareUrl,
            'edit_url' => base_url('/challenge/' . $challenge['slug']),
        ], 201);
    }

    /** My challenges list (used by dashboard partial / dedicated page). */
    public function mine(): void
    {
        $user = $this->requireLogin();
        $challenges = Challenge::forUser((int) $user['id']);

        $this->view('challenge/mine', [
            'title' => 'My challenges — ChallengeChain',
            'challenges' => $challenges,
            'user' => $user,
        ]);
    }
}
