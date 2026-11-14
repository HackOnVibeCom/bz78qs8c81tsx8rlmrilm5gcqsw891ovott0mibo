<?php
/** @var list<array<string, mixed>> $challenges */
?>
<div class="page-head page-head--mist">
    <div class="container">
        <div class="flex items-center justify-between gap-16 flex-wrap">
            <div>
                <h1 class="page-head__title">My challenges</h1>
                <p class="page-head__sub">Everything you've created — views, participants and share counts.</p>
            </div>
            <a class="btn btn--blue" href="<?= e(url('/create')) ?>"><i data-lucide="plus" width="16" height="16"></i> New challenge</a>
        </div>
    </div>
</div>

<section class="section">
    <div class="container">
        <?php if ($challenges === []): ?>
            <div class="empty">
                <div class="empty__title">You haven't created any challenges yet</div>
                <p class="muted mb-24">It takes about 30 seconds with AI generation.</p>
                <a class="btn btn--blue" href="<?= e(url('/create')) ?>">Create your first challenge</a>
            </div>
        <?php else: ?>
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Challenge</th>
                            <th>Status</th>
                            <th>Questions</th>
                            <th>Participants</th>
                            <th>Views</th>
                            <th>Shares</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($challenges as $c): ?>
                        <tr>
                            <td>
                                <strong><?= e($c['title']) ?></strong>
                                <div class="caption muted"><?= e($c['category']) ?> · <?= e($c['difficulty']) ?> · <?= e(time_ago($c['created_at'])) ?></div>
                            </td>
                            <td>
                                <span class="chip <?= (int) $c['is_public'] === 1 ? 'chip--mint' : 'chip--outline' ?>">
                                    <?= (int) $c['is_public'] === 1 ? 'Public' : 'Unlisted' ?>
                                </span>
                                <?php if ((int) $c['is_demo'] === 1): ?><span class="chip chip--sunburst">Demo</span><?php endif; ?>
                            </td>
                            <td><?= (int) $c['real_question_count'] ?></td>
                            <td><?= (int) ($c['participants'] ?? 0) ?></td>
                            <td><?= (int) $c['view_count'] ?></td>
                            <td><?= (int) $c['share_count'] ?></td>
                            <td>
                                <div class="flex gap-8 flex-wrap" style="justify-content:end">
                                    <a class="btn btn--soft btn--sm" href="<?= e(url('/challenge/' . $c['slug'])) ?>">View</a>
                                    <a class="btn btn--primary btn--sm" href="<?= e(url('/challenge/' . $c['slug'] . '/leaderboard')) ?>">Ranks</a>
                                    <button class="btn btn--blue btn--sm" type="button"
                                            data-copy="<?= e(base_url('/challenge/' . $c['slug'] . '?ref=' . urlencode($user['referral_code']))) ?>">
                                        <i data-lucide="link" width="13" height="13"></i> Copy
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</section>
