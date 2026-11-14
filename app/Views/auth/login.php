<?php
$errors = $errors ?? [];
?>
<div class="auth-page">
    <div class="auth-card">
        <div class="auth-card__brand">
            <a class="nav__brand" href="<?= e(url('/')) ?>">
                <span class="nav__brand-mark"><i data-lucide="zap" width="18" height="18"></i></span>
                ChallengeChain
            </a>
        </div>
        <h1 class="auth-card__title">Welcome back</h1>
        <p class="auth-card__sub">Your challenges, scores and achievements, all in one place.</p>

        <?php if ($errors !== []): ?>
            <ul class="form-errors">
                <?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <form method="post" action="<?= e(url('/login')) ?>" novalidate>
            <?= csrf_field() ?>
            <div class="field">
                <label class="field__label" for="email">Email</label>
                <input class="input" type="email" id="email" name="email" value="<?= e(old('email')) ?>" required autocomplete="email" placeholder="you@example.com">
            </div>
            <div class="field">
                <label class="field__label" for="password">Password</label>
                <input class="input" type="password" id="password" name="password" required autocomplete="current-password" placeholder="••••••••">
            </div>
            <button class="btn btn--primary btn--block btn--lg" type="submit">Sign in</button>
        </form>

        <div class="auth-card__footer">
            <a href="<?= e(url('/forgot-password')) ?>">Forgot password?</a>
            · <a href="<?= e(url('/register')) ?>">Create account</a>
        </div>
        <div class="auth-card__guest"><a href="<?= e(url('/create')) ?>">Continue without an account →</a></div>
        <div class="auth-card__footer caption muted">
            Demo access: <strong>demo@challengechain.dev</strong> / <strong>demo1234</strong>
        </div>
    </div>
</div>
