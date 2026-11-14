<?php
/** @var array<string, string> $categories */
/** @var bool $demoMode */
$generateLabel = $demoMode ? 'Generate demo quiz' : 'Generate with AI';
?>
<div class="page-head page-head--mist">
    <div class="container">
        <span class="page-head__eyebrow"><i data-lucide="sparkles" width="16" height="16"></i> Let’s make something worth sharing</span>
        <h1 class="page-head__title">Your topic. Their next challenge.</h1>
        <p class="page-head__sub">A few settings, a quick review, and one link to get everyone playing. No account needed.</p>
        <div class="workflow" aria-label="Creation steps"><span class="is-active" id="workflow-config"><b>1</b> Make it yours</span><i data-lucide="arrow-right" width="13" height="13"></i><span id="workflow-preview"><b>2</b> Review questions</span><i data-lucide="arrow-right" width="13" height="13"></i><span id="workflow-share"><b>3</b> Publish &amp; share</span></div>
    </div>
</div>
<section class="section">
    <div class="container gen-layout">
        <form class="card gen-config" id="gen-form" data-demo="<?= $demoMode ? 'true' : 'false' ?>" data-generate-label="<?= e($generateLabel) ?>">
            <h2 class="subheading mb-24">Make it yours</h2>
            <?php if ($demoMode): ?>
                <div class="notice mb-24"><i data-lucide="info" width="17" height="17"></i><p><strong>You’re in demo mode.</strong> Questions come from your selected category, in English. Your title and difficulty label make the quiz your own.</p></div>
            <?php endif; ?>
            <div class="field"><label class="field__label" for="category">Choose a category</label><select class="select" id="category" name="category"><?php foreach ($categories as $key => $label): ?><option value="<?= e($key) ?>" <?= $key === 'general' ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?></select></div>
            <div class="field">
                <label class="field__label" for="topic"><?= $demoMode ? 'Name your challenge' : 'What’s your topic?' ?> <span aria-hidden="true">*</span></label>
                <input class="input" type="text" id="topic" name="topic" required maxlength="120" placeholder="<?= $demoMode ? 'e.g. The group chat showdown' : 'e.g. Space exploration' ?>" aria-describedby="topic-hint">
                <span class="hint" id="topic-hint"><?= $demoMode ? 'This name appears in your quiz title.' : 'Be specific for questions that hit the mark.' ?></span>
                <div class="topic-suggestions" aria-label="Try a challenge idea"><button type="button" data-topic="The curiosity club" data-category="general">Curiosity club</button><button type="button" data-topic="Tech brain battle" data-category="technology">Tech battle</button><button type="button" data-topic="Movie night showdown" data-category="movies">Movie night</button></div>
            </div>
            <div class="field-row">
                <div class="field"><label class="field__label" for="difficulty"><?= $demoMode ? 'Difficulty label' : 'Difficulty' ?></label><select class="select" id="difficulty" name="difficulty"><option value="easy">Easy</option><option value="medium" selected>Medium</option><option value="hard">Hard</option></select></div>
                <div class="field"><label class="field__label" for="question_count">Questions</label><select class="select" id="question_count" name="question_count"><?php foreach (($demoMode ? [3, 5, 8, 10] : [3, 5, 8, 10, 15]) as $n): ?><option value="<?= $n ?>" <?= $n === 5 ? 'selected' : '' ?>><?= $n ?> questions</option><?php endforeach; ?></select></div>
            </div>
            <div class="field"><label class="field__label" for="language">Quiz language</label><select class="select" id="language" name="language"><option value="en">English</option><?php if (!$demoMode): ?><option value="fr">Français</option><option value="es">Español</option><option value="de">Deutsch</option><option value="ar">العربية</option><?php endif; ?></select></div>
            <div class="field"><label class="field__label" for="is_public">Who can discover it?</label><select class="select" id="is_public" name="is_public" aria-describedby="visibility-hint"><option value="1">Everyone — show in Explore</option><option value="0">Anyone with the link — unlisted</option></select><span class="hint" id="visibility-hint">Both options let friends play without an account.</span></div>
            <button class="btn btn--primary btn--block btn--lg" type="submit" id="btn-generate"><i data-lucide="sparkles" width="18" height="18"></i> <?= e($generateLabel) ?></button>
            <p class="caption muted mt-16 generation-status" id="gen-remaining" role="status"></p>
        </form>
        <div class="gen-preview" id="gen-preview" aria-busy="false">
<img src="/assets/illustrations/quiz-time.png" alt="Illustration de personnages jouant à un quiz" style="display:block;width:100%;max-width:320px;height:auto;margin:0 auto 24px">
            <div class="gen-preview__head"><h2 class="subheading">Your challenge preview</h2><div class="preview-actions" id="preview-actions" hidden><button class="btn btn--soft btn--sm" type="button" id="btn-regen"><i data-lucide="refresh-cw" width="14" height="14"></i> Try again</button><button class="btn btn--mint btn--sm" type="button" id="btn-publish"><i data-lucide="rocket" width="15" height="15"></i> Publish &amp; get link</button></div></div>
            <div class="card gen-empty" id="preview-empty"><div class="gen-empty__icon"><i data-lucide="layers" width="32" height="32"></i></div><h3 class="empty__title">A little blank canvas.<br>A lot of possibilities.</h3><p class="muted">Choose your settings and generate a quiz. Review every question before you share it with the world.</p><div class="gen-empty__steps"><span>Generate</span><i data-lucide="arrow-right" width="14" height="14"></i><span>Review</span><i data-lucide="arrow-right" width="14" height="14"></i><span>Share</span></div></div>
            <div id="preview-error" class="gen-error" tabindex="-1" hidden><div class="alert alert--error" role="alert"></div><button class="btn btn--soft" id="btn-back-preview" type="button" hidden>Back to previous preview</button></div>
            <div id="preview-loading" hidden class="card gen-empty" role="status"><div class="spinner spinner--dark mb-24"></div><h3 class="empty__title">A little quiz magic…</h3><p class="muted" id="generation-detail">Putting your questions together.</p></div>
            <div id="preview-panel" hidden><div class="card"><div class="flex items-center gap-12 flex-wrap mb-16" id="preview-chips"></div><h3 class="heading-sm" id="preview-title" tabindex="-1"></h3><p class="muted mt-8" id="preview-desc"></p><p class="caption muted mt-16 mb-24">Correct answers are highlighted for your review. Players see them only after finishing.</p><div id="preview-questions"></div><div class="notice mt-16"><i data-lucide="link" width="17" height="17"></i><p>Happy with your quiz? <strong>Publish &amp; get link</strong> saves it and opens your challenge, ready to share.</p></div></div></div>
        </div>
    </div>
</section>
