<?php
/** @var array<string, mixed> $c */
$difficulty = (string) $c['difficulty'];
$diffClass = match ($difficulty) { 'easy' => 'chip--mint', 'hard' => 'chip--ember', default => 'chip--lavender' };
$catLabel = \ChallengeChain\Services\DemoQuestionBank::CATEGORIES[$c['category']] ?? ucfirst((string) $c['category']);
?>
<a class="card card--hover challenge-card" href="<?= e(url('/challenge/' . $c['slug'])) ?>" style="--card-accent:<?= e(category_color((string) $c['category'])) ?>">
    <div class="challenge-card__visual"><span class="challenge-card__icon"><i data-lucide="<?= e(category_icon((string) $c['category'])) ?>" width="29" height="29"></i></span><span class="challenge-card__visual-label"><?= e($catLabel) ?></span></div>
    <div class="challenge-card__body">
        <div class="challenge-card__top"><span class="chip <?= $diffClass ?>"><?= e($difficulty) ?></span><?php if ((int) ($c['is_demo'] ?? 0) === 1): ?><span class="chip chip--outline">Demo</span><?php endif; ?></div>
        <h3 class="challenge-card__title"><?= e($c['title']) ?></h3>
        <p class="challenge-card__desc"><?= e($c['description'] !== '' ? $c['description'] : 'Jump in and test your knowledge.') ?></p>
        <div class="challenge-card__meta"><span><i data-lucide="layers" width="13" height="13"></i> <?= (int) $c['question_count'] ?> questions</span><span><i data-lucide="users" width="13" height="13"></i> <?= (int) ($c['participants'] ?? 0) ?> played</span></div>
        <div class="challenge-card__foot"><span>Take the challenge</span><i data-lucide="arrow-right" width="18" height="18"></i></div>
    </div>
</a>
