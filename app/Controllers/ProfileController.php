<?php

declare(strict_types=1);

namespace ChallengeChain\Controllers;

use ChallengeChain\Core\Database;
use ChallengeChain\Models\Achievement;
use ChallengeChain\Models\Challenge;
use ChallengeChain\Models\User;

final class ProfileController extends Controller
{
    public function edit(): void
    {
        $user = $this->requireLogin();
        $this->view('profile/edit', [
            'title' => 'Profile — ChallengeChain',
            'user' => $user,
            'badges' => Achievement::forUser((int) $user['id']),
        ]);
    }

    public function update(): void
    {
        $user = $this->requireLogin();
        $this->verifyCsrf();

        $displayName = $this->str('display_name', '', 60);
        $bio = $this->str('bio', '', 300);
        $errors = [];

        if (mb_strlen($displayName) < 2) {
            $errors[] = 'Display name must be at least 2 characters.';
        }

        if ($errors !== []) {
            $this->view('profile/edit', [
                'title' => 'Profile — ChallengeChain',
                'user' => $user,
                'errors' => $errors,
                'badges' => Achievement::forUser((int) $user['id']),
            ]);
            return;
        }

        User::updateProfile((int) $user['id'], $displayName, $bio);
        set_flash('success', 'Profile updated.');
        redirect('/profile');
    }

    /** Public profile — /u/{username} */
    public function show(string $username): void
    {
        $profile = User::findByUsername($username);
        if ($profile === null) {
            $this->notFound('User not found.');
        }

        $challenges = Challenge::forUser((int) $profile['id']);
        $publicChallenges = array_values(array_filter(
            $challenges,
            static fn (array $c): bool => (int) $c['is_public'] === 1 && $c['status'] === 'published'
        ));

        $completed = Database::fetch(
            "SELECT COUNT(*) AS c FROM attempts WHERE user_id = :u AND status = 'completed'",
            ['u' => $profile['id']]
        );

        $bestScores = Database::fetchAll(
            <<<'SQL'
            SELECT a.score, a.max_score, a.duration_ms, c.title, c.slug, a.completed_at
            FROM attempts a
            JOIN challenges c ON c.id = a.challenge_id
            WHERE a.user_id = :u AND a.status = 'completed'
            ORDER BY a.score DESC, a.duration_ms ASC
            LIMIT 5
            SQL,
            ['u' => $profile['id']]
        );

        $this->view('profile/show', [
            'title' => $profile['display_name'] . ' — ChallengeChain',
            'description' => $profile['bio'] !== '' ? $profile['bio'] : $profile['display_name'] . ' on ChallengeChain.',
            'profile' => $profile,
            'challenges' => $publicChallenges,
            'completed' => (int) ($completed['c'] ?? 0),
            'bestScores' => $bestScores,
            'badges' => Achievement::forUser((int) $profile['id']),
            'user' => \ChallengeChain\Core\Auth::user(),
        ]);
    }
}
