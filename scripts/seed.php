<?php

declare(strict_types=1);

/**
 * Seed data — php scripts/seed.php
 * Demo user + demo challenges (clearly marked) + achievements.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('CLI only');
}

require dirname(__DIR__) . '/bootstrap.php';

use ChallengeChain\Core\Database;
use ChallengeChain\Models\Achievement;
use ChallengeChain\Models\User;
use ChallengeChain\Services\AIService;
use ChallengeChain\Services\ChallengeService;
use ChallengeChain\Services\DemoQuestionBank;

// --- Achievements ---------------------------------------------------------
$achievements = [
    ['first_step', 'First Challenge', 'Create your first challenge', 'zap', 20],
    ['rookie_competitor', 'Rookie Competitor', 'Complete your first challenge attempt', 'trophy', 15],
    ['perfectionist', 'Perfectionist', 'Get a perfect score', 'target', 50],
    ['speed_demon', 'Speed Demon', 'Perfect score in under 60 seconds', 'timer', 40],
    ['explorer', 'Explorer', 'Complete 5 different challenges', 'compass', 30],
    ['crowd_favorite', 'Crowd Favorite', 'Get 10 participants on one of your challenges', 'users', 60],
    ['social_butterfly', 'Social Butterfly', 'Share one of your challenges 5 times', 'share-2', 40],
];

foreach ($achievements as [$code, $name, $description, $icon, $xp]) {
    $existing = Achievement::findByCode($code);
    if ($existing === null) {
        Database::insert('achievements', [
            'code' => $code,
            'name' => $name,
            'description' => $description,
            'icon' => $icon,
            'xp_reward' => $xp,
        ]);
        echo "Achievement: {$name}\n";
    }
}

// --- Demo user ------------------------------------------------------------
$user = User::findByEmail('demo@challengechain.dev');
if ($user === null) {
    $user = User::create('demo', 'demo@challengechain.dev', 'demo1234', 'Demo Player');
    echo "Demo user created: demo@challengechain.dev / demo1234\n";
} else {
    echo "Demo user already exists.\n";
}

// --- Demo challenges ------------------------------------------------------
$demoChallenges = [
    ['technology', 'Web Development Fundamentals', 'technology', 'medium', 10],
    ['science', 'Everyday Physics', 'science', 'easy', 10],
    ['history', 'Moments That Changed the World', 'history', 'medium', 10],
    ['sports', 'Sports Trivia Showdown', 'sports', 'hard', 10],
];

$service = new ChallengeService();

foreach ($demoChallenges as [$category, $title, $topicKey, $difficulty, $count]) {
    $exists = Database::fetch(
        'SELECT id FROM challenges WHERE title = :t',
        ['t' => $title]
    );
    if ($exists !== null) {
        echo "Skip (exists): {$title}\n";
        continue;
    }

    $bank = DemoQuestionBank::forCategory($category);
    shuffle($bank);
    $bank = array_slice($bank, 0, min($count, count($bank)));
    $questions = [];
    foreach ($bank as [$q, $opts, $a, $e]) {
        $indexed = array_values($opts);
        shuffle($indexed);
        $correctText = $opts[$a];
        $idx = array_search($correctText, $indexed, true);
        $questions[] = [
            'question' => $q,
            'options' => $indexed,
            'correct_index' => $idx === false ? 0 : (int) $idx,
            'explanation' => $e,
        ];
    }

    $payload = [
        'title' => $title,
        'description' => 'A ' . $difficulty . ' ' . DemoQuestionBank::CATEGORIES[$category] . ' challenge with ' . count($questions) . ' questions. Jump in and beat the leaderboard!',
        'category' => $category,
        'topic' => DemoQuestionBank::CATEGORIES[$category],
        'difficulty' => $difficulty,
        'language' => 'en',
        'is_public' => true,
        'questions' => $questions,
    ];

    AIService::validatePayload($payload, count($questions));

    $challenge = $service->create((int) $user['id'], $payload, 'demo', true);
    echo "Demo challenge: {$challenge['title']} → /challenge/{$challenge['slug']}\n";
}

echo "Seed complete.\n";
