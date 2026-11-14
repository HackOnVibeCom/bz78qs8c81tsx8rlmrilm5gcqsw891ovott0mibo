<?php
/**
 * @var string $content
 * @var string $title
 * @var string $description
 */
$flash = pull_flash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title) ?></title>
    <meta name="description" content="<?= e($description ?? 'Sign in to ChallengeChain.') ?>">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><rect width='100' height='100' rx='22' fill='%23000'/><text y='.9em' font-size='80' x='50' text-anchor='middle' dominant-baseline='central'>⚡</text></svg>">
    <link rel="stylesheet" href="<?= e(asset_url('assets/css/app.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset_url('assets/css/theme.css')) ?>">
    <script src="<?= e(asset_url('assets/js/icons.js')) ?>" defer></script>
</head>
<body data-app-url="<?= e(url('/')) ?>">
<?php foreach ($flash as $f): ?>
    <div class="hidden" data-flash="<?= e($f['message']) ?>" data-flash-type="<?= e($f['type']) ?>"></div>
<?php endforeach; ?>
<?= $content ?>
<script src="<?= e(asset_url('assets/js/app.js')) ?>" defer></script>
</body>
</html>
