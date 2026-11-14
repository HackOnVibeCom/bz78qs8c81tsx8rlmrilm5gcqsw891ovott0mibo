<?php
$isActive = $isActive ?? static fn (string $p): bool => $currentPath === $p;
?>
<header class="nav">
    <div class="container nav__inner">
        <a class="nav__brand" href="<?= e(url('/')) ?>"><span class="nav__brand-mark"><i data-lucide="zap" width="20" height="20"></i></span>ChallengeChain</a>
        <button class="nav__toggle" type="button" aria-label="Open navigation" aria-expanded="false" aria-controls="main-navigation"><i data-lucide="menu" width="22" height="22"></i></button>
        <div class="nav__menu" id="main-navigation">
            <nav class="nav__links" aria-label="Main navigation">
                <a class="nav__link <?= $isActive('/') ? 'nav__link--active' : '' ?>" <?= $isActive('/') ? 'aria-current="page"' : '' ?> href="<?= e(url('/')) ?>">Home</a>
                <a class="nav__link <?= $isActive('/explore') ? 'nav__link--active' : '' ?>" <?= $isActive('/explore') ? 'aria-current="page"' : '' ?> href="<?= e(url('/explore')) ?>">Explore</a>
                <?php if ($user !== null): ?>
                    <a class="nav__link <?= $isActive('/dashboard') ? 'nav__link--active' : '' ?>" href="<?= e(url('/dashboard')) ?>">Dashboard</a>
                    <a class="nav__link <?= $isActive('/my-challenges') ? 'nav__link--active' : '' ?>" href="<?= e(url('/my-challenges')) ?>">My challenges</a>
                <?php else: ?>
                    <a class="nav__link" href="<?= e(url('/#how-it-works')) ?>">How it works</a>
                <?php endif; ?>
            </nav>
            <div class="nav__actions">
                <?php if ($user === null): ?>
                    <a class="nav__signin" href="<?= e(url('/login')) ?>">Sign in</a>
                <?php endif; ?>
                <a class="btn btn--primary btn--sm" href="<?= e(url('/create')) ?>" <?= $isActive('/create') ? 'aria-current="page"' : '' ?>><i data-lucide="plus" width="16" height="16"></i> Create a challenge</a>
                <?php if ($user !== null): ?>
                    <div class="account" data-disclosure>
                        <button class="btn btn--soft btn--sm account__trigger" type="button" aria-expanded="false" aria-controls="account-menu"><?= e($user['display_name']) ?><i data-lucide="chevron-down" width="14" height="14"></i></button>
                        <div class="account__menu" id="account-menu" hidden>
                            <a class="nav__link" href="<?= e(url('/profile')) ?>"><i data-lucide="user" width="15" height="15"></i> Profile settings</a>
                            <a class="nav__link" href="<?= e(url('/u/' . $user['username'])) ?>"><i data-lucide="external-link" width="15" height="15"></i> Public profile</a>
                            <form method="post" action="<?= e(url('/logout')) ?>"><?= csrf_field() ?><button class="nav__link" type="submit"><i data-lucide="log-out" width="15" height="15"></i> Sign out</button></form>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</header>
