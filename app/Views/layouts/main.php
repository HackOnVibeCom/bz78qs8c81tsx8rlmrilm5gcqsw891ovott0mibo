<?php
/**
 * @var string $content
 * @var string $title
 * @var string $description
 * @var array<string, mixed> $og
 * @var list<string> $pageScripts
 */
$og = $og ?? [];
$pageScripts = $pageScripts ?? [];
$flash = pull_flash();
$user = $user ?? (\ChallengeChain\Core\Auth::user());
$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$isActive = static fn (string $p): bool => $currentPath === $p || ($p !== '/' && str_starts_with($currentPath, $p));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title) ?></title>
    <meta name="description" content="<?= e($description) ?>">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <?php if ($og !== []): ?>
    <meta property="og:site_name" content="<?= e(config('app.name')) ?>">
    <meta property="og:type" content="<?= e($og['type'] ?? 'website') ?>">
    <meta property="og:title" content="<?= e($og['title'] ?? $title) ?>">
    <meta property="og:description" content="<?= e($og['description'] ?? $description) ?>">
    <meta property="og:url" content="<?= e($og['url'] ?? current_url()) ?>">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= e($og['title'] ?? $title) ?>">
    <meta name="twitter:description" content="<?= e($og['description'] ?? $description) ?>">
    <?php endif; ?>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><rect width='100' height='100' rx='22' fill='%23000'/><text y='.9em' font-size='80' x='50' text-anchor='middle' dominant-baseline='central'>⚡</text></svg>">
    <link rel="stylesheet" href="<?= e(asset_url('assets/css/app.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset_url('assets/css/theme.css')) ?>">
    <script src="<?= e(asset_url('assets/js/icons.js')) ?>" defer></script>
</head>
<body data-app-url="<?= e(url('/')) ?>">
<a class="skip-link" href="#main-content">Skip to content</a>
<?php foreach ($flash as $f): ?>
    <div class="hidden" data-flash="<?= e($f['message']) ?>" data-flash-type="<?= e($f['type']) ?>"></div>
<?php endforeach; ?>

<?= \ChallengeChain\Core\View::partial('partials/nav', ['user' => $user, 'currentPath' => $currentPath, 'isActive' => $isActive]) ?>

<main class="page__main" id="main-content" tabindex="-1">
    <?= $content ?>
</main>

<?= \ChallengeChain\Core\View::partial('partials/footer', []) ?>

<script src="<?= e(asset_url('assets/js/app.js')) ?>" defer></script>
<?php foreach ($pageScripts as $script): ?>
<script src="<?= e(asset_url($script)) ?>" defer></script>
<?php endforeach; ?>
</body>
</html>
