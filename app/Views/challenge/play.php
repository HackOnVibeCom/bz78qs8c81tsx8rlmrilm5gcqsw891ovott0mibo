<?php
/** @var array<string, mixed> $challenge */
$initialName = $user['display_name'] ?? '';
?>
<div class="play-shell" id="play-root"
     data-slug="<?= e($challenge['slug']) ?>"
     data-title="<?= e($challenge['title']) ?>"
     data-name="<?= e($initialName) ?>">
    <div class="play-top">
        <div class="container">
            <div class="play-top__inner">
                <a class="nav__brand" href="<?= e(url('/')) ?>">
                    <span class="nav__brand-mark"><i data-lucide="zap" width="18" height="18"></i></span>
                    ChallengeChain
                </a>
                <div class="play-top__title" id="play-title"><?= e($challenge['title']) ?></div>
                <div class="flex items-center gap-12">
                    <div class="play-timer hidden" id="play-timer">0:00</div>
                    <a class="btn btn--white btn--sm" href="<?= e(url('/challenge/' . $challenge['slug'])) ?>">Exit</a>
                </div>
            </div>
            <div class="play-progress" role="progressbar" aria-label="Questions answered" aria-valuemin="0" aria-valuemax="<?= (int) $challenge['question_count'] ?>" aria-valuenow="0"><div class="play-progress__bar" id="play-progress"></div></div>
        </div>
    </div>

    <div class="play-body">
        <div class="container">
            <!-- Step 1: start form -->
            <div class="play-card" id="step-start">
<img src="/assets/illustrations/quiz-time.png" alt="Illustration de personnages jouant à un quiz" style="display:block;width:100%;max-width:320px;height:auto;margin:0 auto 24px">
                <span class="chip chip--sunburst"><i data-lucide="zap" width="13" height="13"></i> Your moment to shine</span>
                <h1 class="heading-sm mt-16"><?= e($challenge['title']) ?></h1>
                <p class="muted mt-16">
                    <?= (int) $challenge['question_count'] ?> questions ·
                    <?= e($challenge['difficulty']) ?> ·
                    scored instantly, ranked by score then speed.
                </p>
                <?php if ($user === null): ?>
                    <div class="field mt-32">
                        <label class="field__label" for="participant-name">Your name (shown on the leaderboard)</label>
                        <input class="input" type="text" id="participant-name" maxlength="60" placeholder="Anonymous" value="">
                        <span class="hint">No account needed — play as a guest.</span>
                    </div>
                <?php else: ?>
                    <input type="hidden" id="participant-name" value="<?= e($user['display_name']) ?>">
                    <p class="mt-24 caption muted">Playing as <strong><?= e($user['display_name']) ?></strong></p>
                <?php endif; ?>
                <button class="btn btn--blue btn--lg btn--block mt-24" type="button" id="btn-start">
                    <i data-lucide="play" width="18" height="18"></i> Start challenge
                </button>
                <p class="play-help">100 points per correct answer. A perfect round? That’s 25 bonus points.</p>
            </div>

            <!-- Step 2: questions -->
            <div class="play-card hidden" id="step-quiz">
                <div class="q-dots" id="q-dots"></div>
                <p class="caption muted mb-16" id="quiz-progress-label" role="status"></p>
                <div id="quiz-content"></div>
                <div class="play-nav">
                    <button class="btn btn--soft" type="button" id="btn-prev">← Previous</button>
                    <button class="btn btn--primary" type="button" id="btn-next">Next →</button>
                </div>
                <button class="btn btn--mint btn--lg btn--block mt-24 hidden" type="button" id="btn-submit">
                    <i data-lucide="check-circle" width="18" height="18"></i> Submit answers
                </button>
            </div>

            <!-- Step 3: submitting -->
            <div class="play-card hidden text-center" id="step-loading">
                <div class="spinner spinner--dark" style="margin:0 auto 16px"></div>
                <h2 class="subheading">Calculating your score…</h2>
                <p class="muted mt-8">Finding your place on the leaderboard. Fingers crossed!</p>
            </div>
        </div>
    </div>
</div>
