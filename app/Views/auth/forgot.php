<?php
$errors = $errors ?? [];
$message = $message ?? null;
$debugLink = $debugLink ?? null;
?>
<div class="auth-page">
    <div class="auth-card">
        <div class="auth-card__brand">
            <a class="nav__brand" href="<?= e(url('/')) ?>">
                <span class="nav__brand-mark"><i data-lucide="zap" width="18" height="18"></i></span>
                ChallengeChain
            </a>
        </div>
        <h1 class="auth-card__title">Reset password</h1>
        <p class="auth-card__sub">We'll email you a secure one-hour reset link.</p>

        <?php if ($message !== null): ?>
            <div class="alert alert--success"><?= e($message) ?></div>
        <?php endif; ?>
        <?php if ($debugLink !== null): ?>
            <div class="alert alert--info">
                <div class="mb-16"><?= e($debugLink !== null ? 'Mail delivery is not configured — use this link (debug mode):' : '') ?></div>
                <a href="<?= e($debugLink) ?>" style="word-break:break-all;font-weight:700"><?= e($debugLink) ?></a>
            </div>
        <?php endif; ?>
        <?php if ($errors !== []): ?>
            <ul class="form-errors">
                <?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <form method="post" action="<?= e(url('/forgot-password')) ?>" novalidate>
            <?= csrf_field() ?>
            <div class="field">
                <label class="field__label" for="email">Email</label>
                <input class="input" type="email" id="email" name="email" value="<?= e(old('email')) ?>" required autocomplete="email" placeholder="you@example.com">
            </div>
            <button class="btn btn--primary btn--block btn--lg" type="submit">Send reset link</button>
        </form>

        <div class="auth-card__footer">
            <a href="<?= e(url('/login')) ?>">Back to sign in</a>
        </div>
    </div>
</div>
