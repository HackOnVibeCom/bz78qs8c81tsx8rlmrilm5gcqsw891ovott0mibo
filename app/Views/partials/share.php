<?php
/**
 * @var array<string, mixed> $challenge
 * @var string $shareUrl
 * @var string $shareText
 * @var bool $light Use light-styled buttons (default dark contexts use .share-btn)
 */
$light = $light ?? false;
$btnClass = $light ? 'share-btn share-btn--light' : 'share-btn';
$shareUrl = $shareUrl ?? base_url('/challenge/' . $challenge['slug']);
$shareText = $shareText ?? ('Take on "' . $challenge['title'] . '" on ChallengeChain!');
$slug = $challenge['slug'];
?>
<div class="share-row <?= $light ? 'share-row--start' : '' ?>">
    <button type="button" class="<?= $btnClass ?>" data-copy="<?= e($shareUrl) ?>" data-slug="<?= e($slug) ?>">
        <i data-lucide="link" width="15" height="15"></i> Copy link
    </button>
    <button type="button" class="<?= $btnClass ?>" data-share-native data-url="<?= e($shareUrl) ?>" data-text="<?= e($shareText) ?>" data-slug="<?= e($slug) ?>">
        <i data-lucide="share-2" width="15" height="15"></i> Share
    </button>
    <a class="<?= $btnClass ?> share-btn--wa" data-share="whatsapp" data-url="<?= e($shareUrl) ?>" data-text="<?= e($shareText) ?>" data-slug="<?= e($slug) ?>" href="https://wa.me/?text=<?= rawurlencode($shareText . ' ' . $shareUrl) ?>" target="_blank" rel="noopener">
        <i data-lucide="message-circle" width="15" height="15"></i> WhatsApp
    </a>
    <a class="<?= $btnClass ?> share-btn--fb" data-share="facebook" data-url="<?= e($shareUrl) ?>" data-text="<?= e($shareText) ?>" data-slug="<?= e($slug) ?>" href="https://www.facebook.com/sharer/sharer.php?u=<?= rawurlencode($shareUrl) ?>" target="_blank" rel="noopener">
        <i data-lucide="facebook" width="15" height="15"></i> Facebook
    </a>
    <a class="<?= $btnClass ?> share-btn--x" data-share="x" data-url="<?= e($shareUrl) ?>" data-text="<?= e($shareText) ?>" data-slug="<?= e($slug) ?>" href="https://twitter.com/intent/tweet?url=<?= rawurlencode($shareUrl) ?>&text=<?= rawurlencode($shareText) ?>" target="_blank" rel="noopener">
        <i data-lucide="twitter" width="15" height="15"></i> X
    </a>
</div>
