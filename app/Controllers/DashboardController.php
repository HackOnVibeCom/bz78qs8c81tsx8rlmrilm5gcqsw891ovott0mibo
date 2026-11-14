<?php

declare(strict_types=1);

namespace ChallengeChain\Controllers;

use ChallengeChain\Core\Database;
use ChallengeChain\Models\Achievement;
use ChallengeChain\Models\Attempt;
use ChallengeChain\Models\Challenge;
use ChallengeChain\Services\ReferralService;

final class DashboardController extends Controller
{
    public function index(): void
    {
        $user = $this->requireLogin();
        $userId = (int) $user['id'];

        $myChallenges = Challenge::forUser($userId);

        $created = count($myChallenges);
        $participants = array_sum(array_map(static fn (array $c): int => (int) ($c['participants'] ?? 0), $myChallenges));
        $views = array_sum(array_map(static fn (array $c): int => (int) ($c['view_count'] ?? 0), $myChallenges));

        $completedRow = Database::fetch(
            "SELECT COUNT(*) AS c FROM attempts WHERE user_id = :u AND status = 'completed'",
            ['u' => $userId]
        );
        $completed = (int) ($completedRow['c'] ?? 0);

        $referralVisits = (new ReferralService())->referralCount($userId);

        // Recent activity: completions on my challenges + my own completions.
        $onMine = Attempt::recentOnOwnChallenges($userId, 8);
        $mine = Attempt::forUser($userId, 8);
        $seen = [];
        $activity = [];
        foreach (array_merge($onMine, $mine) as $row) {
            $key = (int) $row['id'];
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $row['kind'] = (int) ($row['user_id'] ?? 0) === $userId ? 'yours' : 'on_yours';
            $activity[] = $row;
        }
        usort($activity, static fn (array $a, array $b): int => strcmp(
            (string) ($b['completed_at'] ?? ''),
            (string) ($a['completed_at'] ?? '')
        ));
        $activity = array_slice($activity, 0, 8);

        $badges = Achievement::forUser($userId);
        $allAchievements = Achievement::all();

        $this->view('dashboard/index', [
            'title' => 'Dashboard — ChallengeChain',
            'description' => 'Your challenges, scores, views and referral growth at a glance.',
            'user' => $user,
            'stats' => [
                'created' => $created,
                'completed' => $completed,
                'participants' => $participants,
                'views' => $views,
                'referral_visits' => $referralVisits,
                'xp' => (int) $user['xp'],
            ],
            'challenges' => array_slice($myChallenges, 0, 6),
            'activity' => $activity,
            'badges' => $badges,
            'allAchievements' => $allAchievements,
        ]);
    }
}
