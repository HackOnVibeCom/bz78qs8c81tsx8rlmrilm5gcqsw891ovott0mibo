<?php

declare(strict_types=1);

namespace ChallengeChain\Controllers;

use ChallengeChain\Core\Auth;
use ChallengeChain\Core\HttpException;
use ChallengeChain\Models\Attempt;
use ChallengeChain\Models\Challenge;
use ChallengeChain\Models\Question;
use ChallengeChain\Services\AttemptService;
use ChallengeChain\Services\ChallengeService;
use ChallengeChain\Services\GamificationService;
use ChallengeChain\Services\ReferralService;

final class ChallengeController extends Controller
{
    /** Public challenge page + view/referral tracking. */
    public function show(string $slug): void
    {
        $service = new ChallengeService();
        $challenge = $service->requirePublic($slug);

        $ref = trim((string) ($_GET['ref'] ?? ''));
        if ($ref !== '') {
            $_SESSION['referral_code'] = mb_substr($ref, 0, 20);
        }
        $referral = $_SESSION['referral_code'] ?? null;

        (new ReferralService())->trackVisit(
            (int) $challenge['id'],
            is_string($referral) && $referral !== '' ? $referral : null,
            '/challenge/' . $slug
        );

        $user = Auth::user();
        $participants = Challenge::participantCount((int) $challenge['id']);
        $leaderboard = (new AttemptService())->leaderboard((int) $challenge['id'], 5);

        $author = null;
        if ($challenge['user_id'] !== null) {
            $author = \ChallengeChain\Core\Database::fetch(
                'SELECT username, display_name FROM users WHERE id = :id',
                ['id' => $challenge['user_id']]
            );
        }

        $shareUrl = (new ReferralService())->challengeShareUrl($slug, $user['referral_code'] ?? null);

        $this->view('challenge/show', [
            'title' => $challenge['title'] . ' — ChallengeChain',
            'description' => $challenge['description'] !== '' ? $challenge['description'] : 'Take on this challenge on ChallengeChain.',
            'og' => [
                'type' => 'website',
                'url' => base_url('/challenge/' . $slug),
                'title' => $challenge['title'],
                'description' => $challenge['description'],
            ],
            'challenge' => $challenge,
            'author' => $author,
            'participants' => $participants,
            'leaderboard' => $leaderboard,
            'shareUrl' => $shareUrl,
            'user' => $user,
            'categoryLabel' => \ChallengeChain\Services\DemoQuestionBank::CATEGORIES[$challenge['category']] ?? ucfirst($challenge['category']),
        ]);
    }

    /** Play screen (start form → JS loads questions via API). */
    public function play(string $slug): void
    {
        $service = new ChallengeService();
        $challenge = $service->requirePublic($slug);

        $ref = trim((string) ($_GET['ref'] ?? ''));
        if ($ref !== '') {
            $_SESSION['referral_code'] = mb_substr($ref, 0, 20);
        }

        $user = Auth::user();

        $this->view('challenge/play', [
            'title' => 'Play: ' . $challenge['title'] . ' — ChallengeChain',
            'description' => 'Answer ' . $challenge['question_count'] . ' questions and climb the leaderboard.',
            'challenge' => $challenge,
            'user' => $user,
            'pageScripts' => ['assets/js/play.js'],
        ], 'bare');
    }

    /** Leaderboard for a challenge. */
    public function leaderboard(string $slug): void
    {
        $service = new ChallengeService();
        $challenge = $service->requirePublic($slug);

        $rows = (new AttemptService())->leaderboard((int) $challenge['id'], 50);

        $myRank = 0;
        $user = Auth::user();
        if ($user !== null) {
            foreach ($rows as $i => $row) {
                if ((int) ($row['user_id'] ?? 0) === (int) $user['id']) {
                    $myRank = $i + 1;
                    break;
                }
            }
        }

        $this->view('challenge/leaderboard', [
            'title' => 'Leaderboard: ' . $challenge['title'] . ' — ChallengeChain',
            'description' => 'Top scores for ' . $challenge['title'] . '.',
            'challenge' => $challenge,
            'rows' => $rows,
            'myRank' => $myRank,
            'user' => $user,
        ]);
    }

    /** Shareable result page (public — powers the viral loop). */
    public function result(string $publicId): void
    {
        $attempt = Attempt::findByPublicId($publicId);
        if ($attempt === null || $attempt['status'] !== 'completed') {
            throw new HttpException(404, 'Result not found. It may not be finished yet.');
        }

        $challenge = Challenge::find((int) $attempt['challenge_id']);
        if ($challenge === null) {
            throw new HttpException(404, 'Challenge not found.');
        }

        $rank = Attempt::rankOf((int) $challenge['id'], (int) $attempt['id']);
        $totalParticipants = Challenge::participantCount((int) $challenge['id']);

        // Post-completion review (correct answers only after submission).
        $review = Question::gradedForChallenge((int) $challenge['id']);
        $answers = \ChallengeChain\Core\Database::fetchAll(
            'SELECT question_id, selected_option_id, is_correct FROM attempt_answers WHERE attempt_id = :a',
            ['a' => $attempt['id']]
        );
        $answerMap = [];
        foreach ($answers as $row) {
            $answerMap[(int) $row['question_id']] = [
                'selected' => (int) ($row['selected_option_id'] ?? 0),
                'correct' => (int) $row['is_correct'] === 1,
            ];
        }

        $shareText = sprintf(
            'I scored %d/%d on "%s" on ChallengeChain — can you beat me?',
            (int) $attempt['score'],
            (int) $attempt['max_score'],
            $challenge['title']
        );

        $this->view('challenge/result', [
            'title' => $attempt['participant_name'] . ' scored ' . $attempt['score'] . ' — ' . $challenge['title'],
            'description' => $shareText,
            'og' => [
                'type' => 'website',
                'url' => base_url('/result/' . $publicId),
                'title' => $attempt['participant_name'] . ' scored ' . $attempt['score'] . '/' . $attempt['max_score'],
                'description' => $shareText,
            ],
            'attempt' => $attempt,
            'challenge' => $challenge,
            'rank' => $rank,
            'totalParticipants' => $totalParticipants,
            'review' => $review,
            'answerMap' => $answerMap,
            'shareText' => $shareText,
            'user' => Auth::user(),
        ]);
    }

    /** POST — counts a share (viral loop analytics + achievement). */
    public function share(string $slug): void
    {
        $this->verifyCsrf();
        $challenge = Challenge::findBySlug($slug);
        if ($challenge === null || $challenge['status'] !== 'published') {
            $this->json(['error' => 'Challenge not found.'], 404);
        }

        Challenge::incrementShares((int) $challenge['id']);
        $user = Auth::user();
        if ($user !== null) {
            GamificationService::afterShare($challenge, (int) $user['id']);
        }
        $this->json(['ok' => true, 'share_count' => (int) $challenge['share_count'] + 1]);
    }
}
