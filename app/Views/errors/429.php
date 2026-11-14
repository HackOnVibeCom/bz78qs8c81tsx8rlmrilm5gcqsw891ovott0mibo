<?php
/** @var int $status */
/** @var string $message */
$code = $status ?? 404;
$title = match (true) {
    $code === 403 => 'Forbidden',
    $code === 404 => 'Page not found',
    $code === 419 => 'Session expired',
    $code === 429 => 'Too many requests',
    default => 'Something went wrong',
};
?>
<div class="auth-page">
    <div class="auth-card text-center">
        <div class="display" style="font-size:88px;color:var(--color-electric-blue)"><?= (int) $code ?></div>
        <h1 class="auth-card__title"><?= e($title) ?></h1>
        <p class="auth-card__sub"><?= e($message !== '' ? $message : 'The page you are looking for does not exist.') ?></p>
        <div class="flex gap-12" style="justify-content:center;flex-wrap:wrap">
            <a class="btn btn--primary" href="<?= e(url('/')) ?>">Back home</a>
            <a class="btn btn--ghost" href="<?= e(url('/explore')) ?>">Explore</a>
        </div>
    </div>
</div>
