<?php
/** @var array<string, int> $stats */
/** @var list<array<string, mixed>> $challenges */
/** @var list<array<string, mixed>> $activity */
/** @var list<array<string, mixed>> $badges */
/** @var list<array<string, mixed>> $allAchievements */
$badgeIds = array_map(static fn (array $b): int => (int) $b['id'], $badges);
?>
<div class="page-head page-head--mist">
    <div class="container">
        <div class="flex items-center justify-between gap-16 flex-wrap">
            <div>
                <h1 class="page-head__title">Hey, <?= e($user['display_name']) ?> 👋</h1>
                <p class="page-head__sub">Your challenge activity, reach and growth — all in one place.</p>
            </div>
            <div class="flex gap-12 flex-wrap">
                <a class="btn btn--blue" href="<?= e(url('/create')) ?>"><i data-lucide="sparkles" width="16" height="16"></i> New challenge</a>
                <button class="btn btn--ghost" type="button" id="btn-referral" data-code="<?= e($user['referral_code']) ?>">
                    <i data-lucide="share-2" width="16" height="16"></i> Copy referral link
                </button>
            </div>
        </div>
    </div>
</div>

<section class="section">
    <div class="container">
        <div class="grid grid--4 mb-32">
            <div class="card stat">
                <div class="stat__value"><?= (int) $stats['created'] ?></div>
                <div class="stat__label">Challenges created</div>
            </div>
            <div class="card stat">
                <div class="stat__value"><?= (int) $stats['completed'] ?></div>
                <div class="stat__label">Challenges completed</div>
            </div>
            <div class="card stat stat--accent">
                <div class="stat__value"><?= (int) $stats['participants'] ?></div>
                <div class="stat__label">Total participants</div>
            </div>
            <div class="card stat">
                <div class="stat__value"><?= (int) $stats['views'] ?></div>
                <div class="stat__label">Views</div>
            </div>
        </div>

        <div class="grid grid--4 mb-32">
            <div class="card stat">
                <div class="stat__value"><?= (int) $stats['referral_visits'] ?></div>
                <div class="stat__label">Referral visits</div>
            </div>
            <div class="card stat">
                <div class="stat__value"><?= (int) $stats['xp'] ?></div>
                <div class="stat__label">XP earned</div>
            </div>
            <div class="card stat">
                <div class="stat__value"><?= count($badges) ?>/<?= count($allAchievements) ?></div>
                <div class="stat__label">Badges unlocked</div>
            </div>
            <div class="card card--sky stat">
                <div class="stat__value" style="font-size:22px;word-break:break-all"><?= e($user['referral_code']) ?></div>
                <div class="stat__label">Your referral code</div>
            </div>
        </div>

        <div class="grid grid--2" style="align-items:start">
            <div>
                <div class="flex items-center justify-between mb-16">
                    <h2 class="section__title">Recent activity</h2>
                </div>
                <?php if ($activity === []): ?>
                    <div class="empty">
                        <div class="empty__title">No activity yet</div>
                        <p class="muted">Play a challenge or share yours to get started.</p>
                    </div>
                <?php else: ?>
                    <div class="feed">
                        <?php foreach ($activity as $a): ?>
                            <div class="feed__item">
                                <span class="feed__dot <?= $a['kind'] === 'yours' ? 'feed__dot--mint' : '' ?>"></span>
                                <div class="feed__text">
                                    <?php if ($a['kind'] === 'yours'): ?>
                                        You scored <strong><?= (int) $a['score'] ?></strong> on
                                        <a href="<?= e(url('/challenge/' . $a['challenge_slug'])) ?>"><strong><?= e($a['challenge_title']) ?></strong></a>
                                    <?php else: ?>
                                        <strong><?= e($a['participant_name']) ?></strong> scored <strong><?= (int) $a['score'] ?></strong> on your challenge
                                        <a href="<?= e(url('/challenge/' . $a['challenge_slug'])) ?>"><strong><?= e($a['challenge_title']) ?></strong></a>
                                    <?php endif; ?>
                                </div>
                                <span class="feed__time"><?= e(time_ago($a['completed_at'] ?? '')) ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div>
                <div class="flex items-center justify-between mb-16">
                    <h2 class="section__title">Your challenges</h2>
                    <a class="btn btn--soft btn--sm" href="<?= e(url('/my-challenges')) ?>">See all</a>
                </div>
                <?php if ($challenges === []): ?>
                    <div class="empty">
                        <div class="empty__title">Nothing here yet</div>
                        <p class="muted mb-24">Create your first AI challenge.</p>
                        <a class="btn btn--blue" href="<?= e(url('/create')) ?>">Create challenge</a>
                    </div>
                <?php else: ?>
                    <div class="grid" style="gap:16px">
                        <?php foreach ($challenges as $c): ?>
                            <a class="card card--hover" href="<?= e(url('/challenge/' . $c['slug'])) ?>" style="text-decoration:none">
                                <div class="flex items-center justify-between gap-12">
                                    <strong><?= e($c['title']) ?></strong>
                                    <span class="chip chip--sky"><?= (int) ($c['participants'] ?? 0) ?> players</span>
                                </div>
                                <div class="caption muted mt-8">
                                    <?= (int) $c['view_count'] ?> views · <?= (int) $c['share_count'] ?> shares · <?= e(time_ago($c['created_at'])) ?>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="mt-48">
            <h2 class="section__title mb-24">Achievements</h2>
            <div class="badge-grid">
                <?php foreach ($allAchievements as $a): ?>
                    <?php $unlocked = in_array((int) $a['id'], $badgeIds, true); ?>
                    <div class="badge <?= $unlocked ? '' : 'is-locked' ?>" title="<?= e($a['description']) ?>">
                        <div class="badge__icon"><i data-lucide="<?= e($a['icon']) ?>" width="36" height="36"></i></div>
                        <div class="badge__name"><?= e($a['name']) ?></div>
                        <div class="badge__xp">+<?= (int) $a['xp_reward'] ?> XP</div>
                        <?php if (!$unlocked): ?><div class="caption muted mt-8">Locked</div><?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>

<script>
document.getElementById('btn-referral')?.addEventListener('click', async () => {
    const code = document.getElementById('btn-referral').dataset.code;
    const link = <?= json_encode(config('app.url')) ?> + '/?ref=' + encodeURIComponent(code);
    try { await navigator.clipboard.writeText(link); CC.showToast('Referral link copied!'); }
    catch { CC.showToast('Copy failed — ' + link, 'error'); }
});
</script>
