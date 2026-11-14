<?php
$errors = $errors ?? [];
$invalid = $invalid ?? false;
?>
<div class="auth-page">
    <div class="auth-card">
        <div class="auth-card__brand">
            <a class="nav__brand" href="<?= e(url('/')) ?>">
                <span class="nav__brand-mark"><i data-lucide="zap" width="18" height="18"></i></span>
                ChallengeChain
            </a>
        </div>
        <h1 class="auth-card__title">Choose a new password</h1>
        <p class="auth-card__sub">Your reset link is valid for one hour.</p>

        <?php if ($errors !== []): ?>
            <ul class="form-errors">
                <?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <?php if (!$invalid): ?>
        <form method="post" action="<?= e(url('/reset-password/' . $token)) ?>" novalidate>
            <?= csrf_field() ?>
            <div class="field">
                <label class="field__label" for="password">New password</label>
                <input class="input" type="password" id="password" name="password" required minlength="8" autocomplete="new-password" placeholder="At least 8 characters">
            </div>
            <div class="field">
                <label class="field__label" for="password_confirm">Confirm password</label>
                <input class="input" type="password" id="password_confirm" name="password_confirm" required autocomplete="new-password" placeholder="Repeat password">
            </div>
            <button class="btn btn--primary btn--block btn--lg" type="submit">Update password</button>
        </form>
        <?php else: ?>
            <a class="btn btn--primary btn--block" href="<?= e(url('/forgot-password')) ?>">Request a new link</a>
        <?php endif; ?>

        <div class="auth-card__footer">
            <a href="<?= e(url('/login')) ?>">Back to sign in</a>
        </div>
    </div>
</div>
