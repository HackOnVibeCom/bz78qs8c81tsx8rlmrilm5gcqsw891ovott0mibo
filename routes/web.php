<?php

declare(strict_types=1);

use ChallengeChain\Controllers\ApiController;
use ChallengeChain\Controllers\AuthController;
use ChallengeChain\Controllers\ChallengeController;
use ChallengeChain\Controllers\CreateController;
use ChallengeChain\Controllers\DashboardController;
use ChallengeChain\Controllers\ExploreController;
use ChallengeChain\Controllers\HomeController;
use ChallengeChain\Controllers\ProfileController;
use ChallengeChain\Core\Router;

/** @var Router $router */

// --- Public marketing / discovery ----------------------------------------
$router->get('/', HomeController::class, 'index');
$router->get('/explore', ExploreController::class, 'index');

// --- Authentication --------------------------------------------------------
$router->get('/login', AuthController::class, 'showLogin');
$router->post('/login', AuthController::class, 'login');
$router->get('/register', AuthController::class, 'showRegister');
$router->post('/register', AuthController::class, 'register');
$router->post('/logout', AuthController::class, 'logout');
$router->get('/forgot-password', AuthController::class, 'showForgot');
$router->post('/forgot-password', AuthController::class, 'forgot');
$router->get('/reset-password/{token}', AuthController::class, 'showReset');
$router->post('/reset-password/{token}', AuthController::class, 'reset');

// --- Challenge lifecycle ---------------------------------------------------
$router->get('/challenge/{slug}', ChallengeController::class, 'show');
$router->get('/challenge/{slug}/play', ChallengeController::class, 'play');
$router->get('/challenge/{slug}/leaderboard', ChallengeController::class, 'leaderboard');
$router->post('/challenge/{slug}/share', ChallengeController::class, 'share');
$router->get('/result/{publicId}', ChallengeController::class, 'result');

// --- Authenticated app pages ----------------------------------------------
$router->get('/create', CreateController::class, 'index');
$router->get('/my-challenges', CreateController::class, 'mine');
$router->get('/dashboard', DashboardController::class, 'index');
$router->get('/profile', ProfileController::class, 'edit');
$router->post('/profile', ProfileController::class, 'update');
$router->get('/u/{username}', ProfileController::class, 'show');

// --- JSON API --------------------------------------------------------------
$router->post('/api/generate', CreateController::class, 'generate');
$router->post('/api/challenges', CreateController::class, 'store');
$router->post('/api/challenges/{slug}/start', ApiController::class, 'startAttempt');
$router->post('/api/attempts/{publicId}/submit', ApiController::class, 'submitAttempt');
$router->get('/api/challenges/{slug}/leaderboard', ApiController::class, 'leaderboard');
$router->get('/api/health', ApiController::class, 'health');
$router->get('/api/me', ApiController::class, 'me');
$router->post('/api/referral-link', ApiController::class, 'referralLink');
