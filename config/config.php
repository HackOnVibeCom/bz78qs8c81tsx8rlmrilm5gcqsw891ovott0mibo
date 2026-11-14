<?php

declare(strict_types=1);

$databasePath = (string) env('DB_PATH', 'storage/database.sqlite');
if ($databasePath !== ':memory:' && !str_starts_with($databasePath, '/')
    && !preg_match('~^[A-Za-z]:[\\\\/]~', $databasePath)) {
    $databasePath = dirname(__DIR__) . '/' . $databasePath;
}

$aiBaseUrl = rtrim((string) env('AI_BASE_URL', 'https://openrouter.ai/api/v1'), '/');
[$aiProvider, $aiAccountUrl] = match (strtolower((string) parse_url($aiBaseUrl, PHP_URL_HOST))) {
    'openrouter.ai' => ['OpenRouter', 'https://openrouter.ai/settings/keys'],
    'api.mistral.ai' => ['Mistral', 'https://admin.mistral.ai/plateforme/limits'],
    default => ['Le service IA', ''],
};

return [
    'app' => [
        'name' => (string) env('APP_NAME', 'ChallengeChain'),
        'url' => rtrim((string) env('APP_URL', 'http://localhost:8000'), '/'),
        'env' => (string) env('APP_ENV', 'local'),
        'debug' => filter_var(env('APP_DEBUG', 'true'), FILTER_VALIDATE_BOOLEAN),
        'timezone' => (string) env('APP_TIMEZONE', 'UTC'),
    ],
    'database' => [
        'path' => $databasePath,
    ],
    'session' => [
        'name' => (string) env('SESSION_NAME', 'challengechain_session'),
        'lifetime' => (int) env('SESSION_LIFETIME', 604800),
    ],
    'ai' => [
        'api_key' => (string) env('AI_API_KEY', ''),
        'base_url' => $aiBaseUrl,
        'model' => (string) env('AI_MODEL', 'openrouter/free'),
        'provider_name' => $aiProvider,
        'account_url' => $aiAccountUrl,
        'timeout' => (int) env('AI_TIMEOUT', 45),
    ],
    'security' => [
        'login_max_attempts' => (int) env('LOGIN_MAX_ATTEMPTS', 5),
        'login_window' => (int) env('LOGIN_WINDOW_SECONDS', 900),
        'ai_max_per_hour' => (int) env('AI_MAX_PER_HOUR', 6),
    ],
    'mail' => [
        'enabled' => filter_var(env('MAIL_ENABLED', 'false'), FILTER_VALIDATE_BOOLEAN),
        'from' => (string) env('MAIL_FROM', 'no-reply@challengechain.local'),
        'from_name' => (string) env('MAIL_FROM_NAME', 'ChallengeChain'),
    ],
];
