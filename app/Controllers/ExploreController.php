<?php

declare(strict_types=1);

namespace ChallengeChain\Controllers;

use ChallengeChain\Core\Auth;
use ChallengeChain\Models\Challenge;
use ChallengeChain\Services\DemoQuestionBank;

final class ExploreController extends Controller
{
    public function index(): void
    {
        $search = trim((string) ($_GET['q'] ?? ''));
        $category = trim((string) ($_GET['category'] ?? ''));
        $sort = (string) ($_GET['sort'] ?? 'trending');
        if (!in_array($sort, ['trending', 'recent', 'popular', 'most_viewed'], true)) {
            $sort = 'trending';
        }
        if ($search !== '') {
            $search = mb_substr($search, 0, 80);
        }

        $challenges = Challenge::explore($search !== '' ? $search : null, $category !== '' ? $category : null, $sort, 60);

        $this->view('explore/index', [
            'title' => 'Explore challenges — ChallengeChain',
            'description' => 'Discover trending AI-generated challenges, search by topic, pick a category and jump in.',
            'challenges' => $challenges,
            'search' => $search,
            'category' => $category,
            'sort' => $sort,
            'categories' => DemoQuestionBank::CATEGORIES,
            'user' => Auth::user(),
        ]);
    }
}
