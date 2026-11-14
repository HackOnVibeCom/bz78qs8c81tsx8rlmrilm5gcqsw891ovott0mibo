<?php
/** @var list<array<string, mixed>> $trending */
$demoMode = is_demo_mode();
$featured = $trending[0] ?? $recent[0] ?? null;
$categories = \ChallengeChain\Services\DemoQuestionBank::CATEGORIES;
?>
<section class="hero">
    <div class="container hero__layout">
        <div class="hero__content">
            <span class="hero__eyebrow"><i data-lucide="zap" width="13" height="13"></i> A little knowledge. A lot of fun.</span>
            <h1 class="display">Pick a topic.<br>Start a little<br><em>friendly rivalry.</em></h1>
            <p class="hero__sub">Turn your curiosity into a quick quiz. Pass it to your friends, compare scores, and see who really knows their stuff.</p>
            <div class="hero__cta">
                <a class="btn btn--primary btn--lg" href="<?= e(url('/create')) ?>"><i data-lucide="sparkles" width="18" height="18"></i> Create a challenge</a>
                <a class="btn btn--ghost btn--lg" href="<?= e(url('/explore')) ?>">Find a quiz <i data-lucide="arrow-up-right" width="18" height="18"></i></a>
            </div>
            <div class="hero__note">
                <span><i data-lucide="check-circle" width="13" height="13"></i> No account needed</span>
                <span><i data-lucide="check-circle" width="13" height="13"></i> Free to create &amp; play</span>
            </div>
        </div>
        <div class="hero-art">
            <div class="hero-art__back" aria-hidden="true"></div>
            <div class="hero-pick">
                <div class="hero-pick__top"><span><?= $featured ? 'Your next challenge' : 'Curiosity looks good on you' ?></span><i data-lucide="sparkles" width="18" height="18"></i></div>
                <div class="hero-pick__body">
<img src="/assets/illustrations/friendly-rivalry.png" alt="Illustration de personnages jouant à un quiz" style="display:block;width:100%;max-width:320px;height:auto;margin:0 auto 24px">
                    <div class="hero-pick__icon"><i data-lucide="<?= e(category_icon($featured['category'] ?? 'general')) ?>" width="33" height="33"></i></div>
                    <h2><?= e($featured['title'] ?? 'Your next big idea starts with a little quiz.') ?></h2>
                    <p class="hero-pick__desc"><?= e($featured['description'] ?? 'Pick a category, make a challenge, and send the first link.') ?></p>
                    <div class="hero-pick__meta">
                        <?php if ($featured): ?>
                            <span><i data-lucide="layers" width="14" height="14"></i> <?= (int) $featured['question_count'] ?> questions</span>
                            <span class="chip chip--mint"><?= e($featured['difficulty']) ?></span>
                            <?php if ((int) $featured['is_demo'] === 1): ?><span class="chip">Demo</span><?php endif; ?>
                        <?php else: ?>
                            <span><i data-lucide="link" width="14" height="14"></i> One quiz. One shareable link.</span>
                        <?php endif; ?>
                    </div>
                    <a class="btn btn--primary btn--block" href="<?= e(url($featured ? '/challenge/' . $featured['slug'] . '/play' : '/create')) ?>"><?= $featured ? 'Let’s play' : 'Make the first challenge' ?> <i data-lucide="arrow-right" width="17" height="17"></i></a>
                </div>
            </div>
            <div class="hero-sticker" aria-hidden="true"><i data-lucide="trophy" width="25" height="25"></i>BRAGGING<br>RIGHTS AWAIT</div>
            <div class="hero-art__caption"><i data-lucide="corner-up-right" width="19" height="19"></i> Made to be passed on.</div>
        </div>
    </div>
</section>

<div class="ticker" aria-label="Create, share, play, repeat">
    <?php foreach (['Stay curious', 'Challenge your friends', 'Share the fun', 'Keep the chain going'] as $label): ?>
        <span><?= e($label) ?> <i data-lucide="asterisk" width="15" height="15" aria-hidden="true"></i></span>
    <?php endforeach; ?>
</div>

<section class="section" id="challenges" aria-labelledby="trending-title">
    <div class="container">
        <div class="section__head">
            <div><span class="eyebrow">Your next rabbit hole</span><h2 class="section__title" id="trending-title">Small quizzes. Big energy.</h2><p class="section__sub">Pick a challenge and jump straight in. Your leaderboard spot is waiting.</p></div>
            <a class="text-link" href="<?= e(url('/explore')) ?>">Explore all challenges <i data-lucide="arrow-up-right" width="16" height="16"></i></a>
        </div>
        <?php if ($trending !== []): ?>
            <div class="grid grid--3">
                <?php foreach (array_slice($trending, 0, 3) as $c): ?>
                    <?= \ChallengeChain\Core\View::partial('partials/challenge-card', ['c' => $c]) ?>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty"><i class="empty__icon" data-lucide="sparkles" width="48" height="48"></i><h3 class="empty__title">Every chain starts somewhere.</h3><p class="muted mb-24">Make the first challenge and invite your friends to play.</p><a class="btn btn--primary" href="<?= e(url('/create')) ?>">Create a challenge</a></div>
        <?php endif; ?>
        <?php if ($demoMode): ?>
            <div class="notice mt-32"><i data-lucide="info" width="18" height="18"></i><p><strong>Demo mode, real fun.</strong> Try quizzes from our built-in question bank. You can play, publish and share without an account.</p></div>
        <?php endif; ?>
    </div>
</section>

<section class="section how-section" id="how-it-works" aria-labelledby="how-title">
    <div class="container">
        <div class="section__head"><div><span class="eyebrow">A good kind of chain reaction</span><h2 class="section__title" id="how-title">From “I know this” to “beat that.”</h2></div><p class="section__sub">Four little steps. Plenty of friendly competition.</p></div>
        <div class="grid grid--4">
            <?php foreach ([['sparkles', 'Make it yours', 'Pick a category and topic. Generate your questions, then give them a quick review.'], ['link', 'Pass it on', 'Publish your challenge and share one simple link with anyone, anywhere.'], ['trophy', 'Take your shot', 'Play without signing up. Get your score and see where you land on the leaderboard.'], ['repeat', 'Keep it going', 'Share your result, invite a rematch, or create the next challenge.']] as $i => [$icon, $heading, $text]): ?>
                <div class="card step"><div class="step__top"><span class="step__num">0<?= $i + 1 ?></span><i data-lucide="<?= e($icon) ?>" width="23" height="23"></i></div><h3 class="step__title"><?= e($heading) ?></h3><p class="step__text"><?= e($text) ?></p></div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="section" aria-labelledby="categories-title">
    <div class="container">
        <div class="section__head"><div><span class="eyebrow">Follow your curiosity</span><h2 class="section__title" id="categories-title">What’s your thing?</h2></div><p class="section__sub">A little bit of everything, for everyone.</p></div>
        <div class="category-list">
            <?php foreach ($categories as $key => $label): ?>
                <a class="category-tile" href="<?= e(url('/explore?category=' . rawurlencode($key))) ?>" style="--category-accent:<?= e(category_color($key)) ?>"><i data-lucide="<?= e(category_icon($key)) ?>" width="22" height="22"></i><?= e($label) ?><i data-lucide="arrow-up-right" width="15" height="15"></i></a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<section class="cta-section">
    <div class="container"><div class="cta-panel"><div><h2>Your group chat needs this.</h2><p>Make something fun. Send one link. Let the friendly rivalry begin.</p></div><a class="btn btn--white btn--lg" href="<?= e(url('/create')) ?>">Start a chain <i data-lucide="arrow-up-right" width="19" height="19"></i></a></div></div>
</section>
