<?php
/** @var list<array<string, mixed>> $challenges */
$filterUrl = static fn (string $key): string => url('/explore?' . http_build_query(['q' => $search, 'category' => $key, 'sort' => $sort]));
?>
<div class="page-head page-head--mist">
    <div class="container">
        <span class="page-head__eyebrow"><i data-lucide="compass" width="16" height="16"></i> Follow your curiosity</span>
        <h1 class="page-head__title">Find your next “I know this.”</h1>
        <p class="page-head__sub">Quick quizzes, friendly competition, and a topic for every kind of curious. Pick one and play.</p>
        <form class="filters" method="get" action="<?= e(url('/explore')) ?>" role="search">
            <div class="search-field"><i data-lucide="search" width="17" height="17"></i><input class="input" type="search" name="q" value="<?= e($search) ?>" placeholder="What are you curious about?" aria-label="Search challenges"></div>
            <select class="select" name="category" aria-label="Category"><option value="">All categories</option><?php foreach ($categories as $key => $label): ?><option value="<?= e($key) ?>" <?= $category === $key ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?></select>
            <select class="select" name="sort" aria-label="Sort challenges"><?php foreach (['trending' => 'Trending now', 'recent' => 'Newest first', 'popular' => 'Most played', 'most_viewed' => 'Most viewed'] as $key => $label): ?><option value="<?= e($key) ?>" <?= $sort === $key ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?></select>
            <button class="btn btn--primary" type="submit">Find a quiz <i data-lucide="arrow-right" width="16" height="16"></i></button>
        </form>
        <nav class="filter-categories" aria-label="Browse categories"><a class="filter-category" href="<?= e($filterUrl('')) ?>" aria-current="<?= $category === '' ? 'true' : 'false' ?>">All topics</a><?php foreach ($categories as $key => $label): ?><a class="filter-category" href="<?= e($filterUrl($key)) ?>" aria-current="<?= $category === $key ? 'true' : 'false' ?>"><i data-lucide="<?= e(category_icon($key)) ?>" width="13" height="13"></i><?= e($label) ?></a><?php endforeach; ?></nav>
    </div>
</div>
<section class="section">
    <div class="container">
        <div class="result-count"><span><strong><?= count($challenges) ?></strong> challenge<?= count($challenges) === 1 ? '' : 's' ?><?= $search !== '' ? ' for “' . e($search) . '”' : ' to get you thinking' ?></span><?php if ($search !== '' || $category !== '' || $sort !== 'trending'): ?><a class="text-link" href="<?= e(url('/explore')) ?>">Reset filters <i data-lucide="x" width="13" height="13"></i></a><?php endif; ?></div>
        <?php if ($challenges === []): ?>
            <div class="empty"><i class="empty__icon" data-lucide="search" width="48" height="48"></i><h2 class="empty__title">No quiz here. Yet.</h2><p class="muted mb-24">Try another topic, reset your filters, or make a challenge of your own.</p><div class="flex flex-wrap gap-16" style="justify-content:center"><a class="btn btn--white" href="<?= e(url('/explore')) ?>">Show all challenges</a><a class="btn btn--primary" href="<?= e(url('/create')) ?>"><i data-lucide="plus" width="16" height="16"></i> Create a challenge</a></div></div>
        <?php else: ?>
            <div class="grid grid--3"><?php foreach ($challenges as $c): ?><?= \ChallengeChain\Core\View::partial('partials/challenge-card', ['c' => $c]) ?><?php endforeach; ?></div>
        <?php endif; ?>
    </div>
</section>
