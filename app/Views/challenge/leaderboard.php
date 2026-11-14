<?php
/** @var array<string, mixed> $challenge */
/** @var list<array<string, mixed>> $rows */
/** @var int $myRank */
?>
<div class="page-head page-head--mist">
    <div class="container">
        <a class="btn btn--soft btn--sm mb-24" href="<?= e(url('/challenge/' . $challenge['slug'])) ?>">← Back to challenge</a>
        <h1 class="page-head__title">Leaderboard</h1>
        <p class="page-head__sub"><?= e($challenge['title']) ?> — best score per player, ties broken by completion time.</p>
        <?php if ($myRank > 0): ?>
            <span class="chip chip--mint mt-16">Your rank: #<?= $myRank ?></span>
        <?php endif; ?>
        <div class="mt-24 flex gap-12 flex-wrap">
            <a class="btn btn--blue" href="<?= e(url('/challenge/' . $challenge['slug'] . '/play')) ?>"><i data-lucide="play" width="16" height="16"></i> Play now</a>
            <?= \ChallengeChain\Core\View::partial('partials/share', [
                'challenge' => $challenge,
                'shareUrl' => base_url('/challenge/' . $challenge['slug'] . '/leaderboard'),
                'shareText' => 'Top scores for "' . $challenge['title'] . '" on ChallengeChain!',
                'light' => true,
            ]) ?>
        </div>
    </div>
</div>

<section class="section">
    <div class="container">
        <?php if ($rows === []): ?>
            <div class="empty">
                <div class="empty__title">The board is empty</div>
                <p class="muted mb-24">Play now and claim the #1 spot.</p>
                <a class="btn btn--blue" href="<?= e(url('/challenge/' . $challenge['slug'] . '/play')) ?>">Start challenge</a>
            </div>
        <?php else: ?>
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Rank</th>
                            <th>Player</th>
                            <th>Score</th>
                            <th>Correct</th>
                            <th>Time</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($rows as $row): ?>
                        <?php $isMe = (int) ($row['user_id'] ?? 0) > 0 && $user !== null && (int) $row['user_id'] === (int) $user['id']; ?>
                        <tr class="<?= $isMe ? 'is-me' : '' ?>">
                            <td><span class="rank-pill rank-pill--<?= (int) $row['rank'] ?>"><?= (int) $row['rank'] ?></span></td>
                            <td>
                                <strong><?= e($row['participant_name']) ?></strong>
                                <?php if (!empty($row['username'])): ?>
                                    <div class="caption muted">@<?= e($row['username']) ?></div>
                                <?php endif; ?>
                                <?php if ($isMe): ?><span class="chip chip--sky">You</span><?php endif; ?>
                            </td>
                            <td><strong><?= (int) $row['score'] ?></strong> <span class="muted">/ <?= (int) $row['max_score'] ?></span></td>
                            <td><?= (int) $row['correct_count'] ?>/<?= (int) $row['total_questions'] ?></td>
                            <td><?= e(format_duration((int) $row['duration_ms'])) ?></td>
                            <td class="caption muted"><?= e(time_ago($row['completed_at'] ?? '')) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</section>
