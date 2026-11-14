<?php

declare(strict_types=1);

namespace ChallengeChain\Controllers;

use ChallengeChain\Core\Auth;
use ChallengeChain\Models\Challenge;

final class HomeController extends Controller
{
    public function index(): void
    {
        $trending = Challenge::trending(6);
        $recent = Challenge::explore(null, null, 'recent', 6);

        $this->view('home/index', [
            'title' => 'ChallengeChain — Create AI challenges. Share them. Go viral.',
            'description' => 'ChallengeChain turns any topic into a shareable AI-powered challenge. Create, share, compete, climb the leaderboard.',
            'trending' => $trending,
            'recent' => $recent,
            'user' => Auth::user(),
        ]);
    }
}
