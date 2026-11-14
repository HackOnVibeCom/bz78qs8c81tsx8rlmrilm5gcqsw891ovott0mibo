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
        <h1 class="auth-card__title">Create your account</h1>
        <p class="auth-card__sub">Keep track of your challenges and collect your achievements.</p>

        <?php if ($errors !== []): ?>
            <ul class="form-errors">
                <?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <form method="post" action="<?= e(url('/register')) ?>" novalidate>
            <?= csrf_field() ?>
            <div class="field">
                <label class="field__label" for="username">Username</label>
                <input class="input" type="text" id="username" name="username" value="<?= e(old('username')) ?>" required minlength="3" maxlength="30" pattern="[a-zA-Z0-9_]+" autocomplete="username" placeholder="challenge_master">
                <span class="hint">3–30 characters: letters, numbers, underscore.</span>
            </div>
            <div class="field">
                <label class="field__label" for="display_name">Display name</label>
                <input class="input" type="text" id="display_name" name="display_name" value="<?= e(old('display_name')) ?>" maxlength="60" placeholder="Alex">
            </div>
            <div class="field">
                <label class="field__label" for="email">Email</label>
                <input class="input" type="email" id="email" name="email" value="<?= e(old('email')) ?>" required autocomplete="email" placeholder="you@example.com">
            </div>
            <div class="field">
                <label class="field__label" for="password">Password</label>
                <input class="input" type="password" id="password" name="password" required minlength="8" autocomplete="new-password" placeholder="At least 8 characters">
            </div>
            <div class="field">
                <label class="field__label" for="password_confirm">Confirm password</label>
                <input class="input" type="password" id="password_confirm" name="password_confirm" required autocomplete="new-password" placeholder="Repeat password">
            </div>
            <button class="btn btn--blue btn--block btn--lg" type="submit">Create account</button>
        </form>

        <div class="auth-card__footer">
            Already registered? <a href="<?= e(url('/login')) ?>">Sign in</a>
        </div>
        <div class="auth-card__guest"><a href="<?= e(url('/create')) ?>">Just here to play? Continue as a guest →</a></div>
    </div>
</div>
