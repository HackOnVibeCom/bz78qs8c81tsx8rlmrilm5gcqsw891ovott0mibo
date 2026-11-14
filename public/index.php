<?php

declare(strict_types=1);

/**
 * ChallengeChain front controller — all requests enter here.
 */

// Built-in PHP server: serve real files directly.
if (PHP_SAPI === 'cli-server') {
    $path = parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?: '/';
    $file = __DIR__ . $path;
    if ($path !== '/' && is_file($file)) {
        return false;
    }
}

require dirname(__DIR__) . '/bootstrap.php';

use ChallengeChain\Core\Csrf;
use ChallengeChain\Core\HttpException;
use ChallengeChain\Core\Router;

session_boot();

try {
    Csrf::verify();
} catch (Throwable $e) {
    // Csrf::verify already renders; this is a safety net.
    render_error(419, 'Invalid CSRF token. Please refresh and try again.');
}

$router = new Router();
require dirname(__DIR__) . '/routes/web.php';

try {
    $router->dispatch((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'), (string) ($_SERVER['REQUEST_URI'] ?? '/'));
} catch (HttpException $e) {
    render_error($e->getStatus(), $e->getMessage());
}
// Uncaught exceptions are handled by the global exception handler in bootstrap.php.
