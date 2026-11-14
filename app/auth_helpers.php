<?php

declare(strict_types=1);

/**
 * Global helper used by ReferralService — keeps auth usable from services
 * without a circular dependency on the Core\Auth class import.
 */

use ChallengeChain\Core\Auth;

if (!function_exists('auth_id')) {
    function auth_id(): ?int
    {
        return Auth::id();
    }
}

if (!function_exists('auth_user')) {
    function auth_user(): ?array
    {
        return Auth::user();
    }
}
