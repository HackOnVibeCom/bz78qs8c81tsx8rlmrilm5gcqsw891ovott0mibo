<?php

declare(strict_types=1);

namespace ChallengeChain\Services;

use ChallengeChain\Core\Database;
use ChallengeChain\Models\Challenge;
use ChallengeChain\Models\User;

final class ReferralService
{
    /**
     * Records a referred visit + a challenge view.
     * Never throws — analytics must not break the page.
     */
    public function trackVisit(?int $challengeId, ?string $refCode, string $path): void
    {
        try {
            $referrerUserId = null;
            if ($refCode !== null && $refCode !== '') {
                $referrer = User::findByReferralCode($refCode);
                if ($referrer !== null) {
                    $referrerUserId = (int) $referrer['id'];
                    Database::insert('referral_events', [
                        'user_id' => $referrerUserId,
                        'referral_code' => $referrer['referral_code'],
                        'challenge_id' => $challengeId,
                        'ip_hash' => ip_hash(),
                        'path' => mb_substr($path, 0, 200),
                    ]);
                }
            }

            if ($challengeId !== null) {
                Database::insert('challenge_views', [
                    'challenge_id' => $challengeId,
                    'viewer_user_id' => auth_id(),
                    'ip_hash' => ip_hash(),
                    'referrer_code' => $refCode,
                ]);
                Challenge::incrementViews($challengeId);
            }
        } catch (\Throwable $e) {
            log_message('error', 'Referral tracking failed: ' . $e->getMessage());
        }
    }

    public function userIdFromCode(string $code): ?int
    {
        $user = User::findByReferralCode($code);
        return $user === null ? null : (int) $user['id'];
    }

    public function referralCount(int $userId): int
    {
        $row = Database::fetch('SELECT COUNT(*) AS c FROM referral_events WHERE user_id = :u', ['u' => $userId]);
        return (int) ($row['c'] ?? 0);
    }

    /** Unique shareable link for a challenge, attributed to the sharer. */
    public function challengeShareUrl(string $slug, ?string $referralCode): string
    {
        $url = base_url('/challenge/' . $slug);
        if ($referralCode !== null && $referralCode !== '') {
            $url .= '?ref=' . rawurlencode($referralCode);
        }
        return $url;
    }
}
