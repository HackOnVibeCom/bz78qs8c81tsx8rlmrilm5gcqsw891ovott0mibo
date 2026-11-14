<?php
$errors = $errors ?? [];
/** @var list<array<string, mixed>> $badges */
?>
<div class="page-head page-head--mist">
    <div class="container">
        <h1 class="page-head__title">Profile settings</h1>
        <p class="page-head__sub">Manage how you appear on leaderboards and your public page.</p>
    </div>
</div>

<section class="section">
    <div class="container container--narrow">
        <?php if ($errors !== []): ?>
            <ul class="form-errors">
                <?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <div class="card mb-24">
            <div class="flex items-center gap-16 mb-24">
                <div class="nav__brand-mark" style="width:56px;height:56px;font-size:22px;border-radius:16px">
                    <?= e(strtoupper(mb_substr($user['display_name'], 0, 1))) ?>
                </div>
                <div>
                    <div class="subheading"><?= e($user['display_name']) ?></div>
                    <div class="caption muted">@<?= e($user['username']) ?> · <?= (int) $user['xp'] ?> XP · member since <?= e(date('M Y', strtotime($user['created_at'] . ' UTC'))) ?></div>
                    <a class="caption" style="color:var(--color-voltage-violet);font-weight:700" href="<?= e(url('/u/' . $user['username'])) ?>">View public page →</a>
                </div>
            </div>

            <form method="post" action="<?= e(url('/profile')) ?>">
                <?= csrf_field() ?>
                <div class="field">
                    <label class="field__label" for="display_name">Display name</label>
                    <input class="input" type="text" id="display_name" name="display_name" value="<?= e($user['display_name']) ?>" required minlength="2" maxlength="60">
                </div>
                <div class="field">
                    <label class="field__label" for="bio">Bio</label>
                    <textarea class="textarea" id="bio" name="bio" maxlength="300" placeholder="Tell other players about yourself…"><?= e($user['bio']) ?></textarea>
                </div>
                <button class="btn btn--primary" type="submit">Save changes</button>
            </form>
        </div>

        <div class="card mb-24">
            <h2 class="subheading mb-16">Account</h2>
            <p class="muted mb-16">Email: <strong><?= e($user['email']) ?></strong></p>
            <a class="btn btn--soft btn--sm" href="<?= e(url('/forgot-password')) ?>">Change password</a>
        </div>

        <div class="card">
            <h2 class="subheading mb-16">Badges</h2>
            <?php if ($badges === []): ?>
                <p class="muted">No badges yet — play challenges to unlock them.</p>
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
