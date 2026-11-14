<?php
/** @var array<string, mixed> $profile */
/** @var list<array<string, mixed>> $challenges */
/** @var int $completed */
/** @var list<array<string, mixed>> $bestScores */
/** @var list<array<string, mixed>> $badges */
?>
<div class="page-head page-head--mist">
    <div class="container">
        <div class="flex items-center gap-24 flex-wrap">
            <div class="nav__brand-mark" style="width:72px;height:72px;font-size:28px;border-radius:20px">
                <?= e(strtoupper(mb_substr($profile['display_name'], 0, 1))) ?>
            </div>
            <div>
                <h1 class="page-head__title"><?= e($profile['display_name']) ?></h1>
                <p class="caption muted">@<?= e($profile['username']) ?> · <?= (int) $profile['xp'] ?> XP · <?= $completed ?> challenges completed</p>
                <?php if ($profile['bio'] !== ''): ?>
                    <p class="page-head__sub"><?= e($profile['bio']) ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<section class="section">
    <div class="container grid grid--2" style="align-items:start">
        <div>
            <h2 class="section__title mb-16">Public challenges</h2>
            <?php if ($challenges === []): ?>
                <div class="empty"><div class="empty__title">No public challenges</div></div>
            <?php else: ?>
                <div class="grid" style="gap:16px">
                    <?php foreach ($challenges as $c): ?>
                        <?= \ChallengeChain\Core\View::partial('partials/challenge-card', ['c' => $c]) ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div>
            <h2 class="section__title mb-16">Best scores</h2>
            <?php if ($bestScores === []): ?>
                <div class="empty"><div class="empty__title">No scores yet</div></div>
            <?php else: ?>
                <div class="feed mb-32">
                    <?php foreach ($bestScores as $s): ?>
                        <div class="feed__item">
                            <span class="feed__dot feed__dot--mint"></span>
                            <div class="feed__text">
                                <strong><?= (int) $s['score'] ?>/<?= (int) $s['max_score'] ?></strong> on
                                <a href="<?= e(url('/challenge/' . $s['slug'])) ?>"><?= e($s['title']) ?></a>
                            </div>
                            <span class="feed__time"><?= e(format_duration((int) $s['duration_ms'])) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <h2 class="section__title mb-16">Badges</h2>
            <?php if ($badges === []): ?>
                <p class="muted">No badges unlocked yet.</p>
            <?php else: ?>
                <div class="flex gap-12 flex-wrap">
                    <?php foreach ($badges as $b): ?>
                        <span class="chip chip--lavender"><i data-lucide="<?= e($b['icon']) ?>" width="13" height="13"></i> <?= e($b['name']) ?></span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>
