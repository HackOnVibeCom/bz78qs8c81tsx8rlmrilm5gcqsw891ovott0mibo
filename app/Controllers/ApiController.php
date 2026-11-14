<?php

declare(strict_types=1);

namespace ChallengeChain\Controllers;

use ChallengeChain\Core\Auth;
use ChallengeChain\Core\Database;
use ChallengeChain\Core\HttpException;
use ChallengeChain\Models\Challenge;
use ChallengeChain\Services\AttemptService;
use ChallengeChain\Services\ChallengeService;
use ChallengeChain\Services\ReferralService;

/**
 * JSON API used by the frontend (Fetch API).
 */
final class ApiController extends Controller
{
    /** POST /api/challenges/{slug}/start */
    public function startAttempt(string $slug): void
    {
        $this->verifyCsrf();
        $challenge = (new ChallengeService())->requirePublic($slug);

        $user = Auth::user();
        $name = $this->str('participant_name', '', 60);
        $referral = $_SESSION['referral_code'] ?? null;

        $payload = (new AttemptService())->start(
            $challenge,
            $user,
            $name !== '' ? $name : null,
            is_string($referral) && $referral !== '' ? $referral : null
        );

        // Remember guest attempts in session so only their owner can submit.
        if (!isset($_SESSION['attempt_ids']) || !is_array($_SESSION['attempt_ids'])) {
            $_SESSION['attempt_ids'] = [];
        }
        $_SESSION['attempt_ids'][] = $payload['attempt_id'];

        $this->json(['ok' => true] + $payload);
    }

    /** POST /api/attempts/{publicId}/submit — server-side scoring. */
    public function submitAttempt(string $publicId): void
    {
        $this->verifyCsrf();

        $rawAnswers = $this->input()['answers'] ?? [];
        if (!is_array($rawAnswers) || $rawAnswers === []) {
            $this->json(['error' => 'No answers submitted.'], 422);
        }

        $answers = [];
        foreach ($rawAnswers as $qid => $oid) {
            if (is_numeric($qid) && is_numeric($oid)) {
                $answers[(int) $qid] = (int) $oid;
            }
        }
        if ($answers === []) {
            $this->json(['error' => 'No valid answers submitted.'], 422);
        }

        $sessionAttempts = $_SESSION['attempt_ids'] ?? [];
        if (!is_array($sessionAttempts)) {
            $sessionAttempts = [];
        }

        try {
            $result = (new AttemptService())->submit(
                $publicId,
                $answers,
                Auth::id(),
                $sessionAttempts
            );
        } catch (HttpException $e) {
            $this->json(['error' => $e->getMessage()], $e->getStatus());
        }

        // Keep the attempt id in session: a retry then gets a clear 409
        // "already submitted" instead of a confusing 403.
        $this->json(['ok' => true] + $result);
    }

    /** GET /api/challenges/{slug}/leaderboard */
    public function leaderboard(string $slug): void
    {
        $challenge = (new ChallengeService())->requirePublic($slug);
        $rows = (new AttemptService())->leaderboard((int) $challenge['id'], 50);
        $this->json(['ok' => true, 'leaderboard' => $rows]);
    }

    /** GET /api/health — deployment sanity check (no internals exposed). */
    public function health(): void
    {
        try {
            // Opening SQLite alone can silently create an empty database.
            // Check the application tables before reporting readiness.
            Database::query('SELECT id FROM users LIMIT 0');
            Database::query('SELECT id FROM challenges LIMIT 0');
            Database::query('SELECT id FROM attempts LIMIT 0');
        } catch (\Throwable $e) {
            log_message('error', 'Health check failed: ' . $e->getMessage());
            $this->json(['ok' => false, 'app' => config('app.name')], 503);
        }

        $this->json([
            'ok' => true,
            'app' => config('app.name'),
            'demo_mode' => is_demo_mode(),
            'time' => gmdate('c'),
        ]);
    }

    /** GET /api/me — lightweight session probe for the frontend. */
    public function me(): void
    {
        $user = Auth::user();
        if ($user === null) {
            $this->json(['ok' => false, 'user' => null]);
        }
        $this->json([
            'ok' => true,
            'user' => [
                'username' => $user['username'],
                'display_name' => $user['display_name'],
                'xp' => (int) $user['xp'],
                'referral_code' => $user['referral_code'],
            ],
        ]);
    }

    /** POST /api/profile/share-link helper — returns the user's referral link. */
    public function referralLink(): void
    {
        $user = $this->requireLogin();
        $this->verifyCsrf();
        $this->json([
            'ok' => true,
            'referral_code' => $user['referral_code'],
            'url' => base_url('/?ref=' . rawurlencode((string) $user['referral_code'])),
        ]);
    }
}
