<?php
/** @var array<string, mixed> $challenge */
/** @var array<string, mixed>|null $author */
/** @var int $participants */
/** @var list<array<string, mixed>> $leaderboard */
/** @var string $shareUrl */
/** @var string $categoryLabel */
$user = $user ?? null;
$shareText = 'Take on "' . $challenge['title'] . '" on ChallengeChain — can you beat me?';
?>
<div class="page-head page-head--mist">
    <div class="container">
        <div class="flex flex-wrap gap-12 mb-24">
            <span class="chip chip--dark"><?= e($categoryLabel) ?></span>
            <span class="chip <?= $challenge['difficulty'] === 'easy' ? 'chip--mint' : ($challenge['difficulty'] === 'hard' ? 'chip--ember' : 'chip--sky') ?>"><?= e($challenge['difficulty']) ?></span>
            <span class="chip chip--outline"><?= (int) $challenge['question_count'] ?> questions</span>
            <?php if ((int) $challenge['is_demo'] === 1): ?><span class="chip chip--sunburst">Demo challenge</span><?php endif; ?>
            <?php if ((int) $challenge['share_count'] > 0): ?><span class="chip chip--lavender"><?= (int) $challenge['share_count'] ?> shares</span><?php endif; ?>
        </div>
        <h1 class="page-head__title"><?= e($challenge['title']) ?></h1>
        <?php if ($challenge['description'] !== ''): ?>
            <p class="page-head__sub"><?= e($challenge['description']) ?></p>
        <?php endif; ?>
        <p class="caption muted mt-16">
            <?php if ($author !== null): ?>
                By <a href="<?= e(url('/u/' . $author['username'])) ?>" style="font-weight:700"><?= e($author['display_name']) ?></a> ·
            <?php endif; ?>
            Created <?= e(time_ago($challenge['created_at'])) ?> ·
            <?= $participants ?> participant<?= $participants === 1 ? '' : 's' ?> ·
            <?= (int) $challenge['view_count'] ?> views
        </p>

        <div class="mt-32 flex flex-wrap gap-16">
            <a class="btn btn--blue btn--lg" href="<?= e(url('/challenge/' . $challenge['slug'] . '/play')) ?>">
                <i data-lucide="play" width="18" height="18"></i> Start challenge
            </a>
            <a class="btn btn--ghost btn--lg" href="<?= e(url('/challenge/' . $challenge['slug'] . '/leaderboard')) ?>">
                <i data-lucide="trophy" width="18" height="18"></i> Leaderboard
            </a>
        </div>

        <div class="mt-32">
            <?= \ChallengeChain\Core\View::partial('partials/share', [
                'challenge' => $challenge,
                'shareUrl' => $shareUrl,
                'shareText' => $shareText,
                'light' => true,
            ]) ?>
        </div>
    </div>
</div>

<section class="section">
    <div class="container grid grid--2" style="align-items:start">
        <div>
            <h2 class="section__title mb-24">How it works</h2>
            <div class="feed">
                <div class="feed__item"><span class="feed__dot"></span><div class="feed__text"><strong>Answer <?= (int) $challenge['question_count'] ?> questions</strong> — no account needed to play.</div></div>
                <div class="feed__item"><span class="feed__dot feed__dot--mint"></span><div class="feed__text"><strong>Every answer counts</strong> — 100 points for each correct answer, plus 25 for a perfect round.</div></div>
                <div class="feed__item"><span class="feed__dot"></span><div class="feed__text"><strong>Speed breaks ties</strong> — finish fast to climb the ranks.</div></div>
                <div class="feed__item"><span class="feed__dot feed__dot--mint"></span><div class="feed__text"><strong>Share your result</strong> and dare your friends to beat it.</div></div>
            </div>
            <a class="btn btn--primary mt-24" href="<?= e(url('/challenge/' . $challenge['slug'] . '/play')) ?>">Play now — it's free</a>
        </div>

        <div>
            <div class="flex items-center justify-between mb-16">
                <h2 class="section__title">Top players</h2>
                <a class="btn btn--soft btn--sm" href="<?= e(url('/challenge/' . $challenge['slug'] . '/leaderboard')) ?>">Full board</a>
            </div>
            <?php if ($leaderboard === []): ?>
                <div class="empty">
                    <div class="empty__title">No scores yet</div>
                    <p class="muted">Be the first to play and take the #1 spot.</p>
                </div>
            <?php else: ?>
                <div class="table-wrap">
                    <table class="table">
                        <thead><tr><th>#</th><th>Player</th><th>Score</th><th>Time</th></tr></thead>
                        <tbody>
                        <?php foreach ($leaderboard as $row): ?>
                            <tr>
                                <td><span class="rank-pill rank-pill--<?= (int) $row['rank'] ?>"><?= (int) $row['rank'] ?></span></td>
                                <td><?= e($row['participant_name']) ?></td>
                                <td><strong><?= (int) $row['score'] ?></strong>/<?= (int) $row['max_score'] ?></td>
                                <td><?= e(format_duration((int) $row['duration_ms'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>
