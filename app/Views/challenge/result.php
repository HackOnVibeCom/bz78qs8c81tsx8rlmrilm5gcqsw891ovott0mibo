<?php
/** @var array<string, mixed> $attempt */
/** @var array<string, mixed> $challenge */
/** @var int $rank */
/** @var int $totalParticipants */
/** @var list<array<string, mixed>> $review */
/** @var array<int, array{selected:int, correct:bool}> $answerMap */
/** @var string $shareText */
$score = (int) $attempt['score'];
$max = (int) $attempt['max_score'];
$pct = $max > 0 ? (int) round(($score / $max) * 100) : 0;
$resultUrl = base_url('/result/' . $attempt['public_id']);
$perfect = (int) $attempt['correct_count'] === (int) $attempt['total_questions'];
?>
<div class="result-hero">
    <div class="container">
        <span class="chip chip--sunburst"><?= $perfect ? 'Perfect score!' : 'Challenge complete' ?></span>
        <h1 class="display mt-24" style="font-size:clamp(36px,7vw,56px)"><?= e($attempt['participant_name']) ?></h1>
        <div class="score-ring" style="--score-pct: <?= $pct ?>">
            <div class="text-center">
                <div class="score-ring__value"><?= $score ?></div>
                <div class="score-ring__label">/ <?= $max ?></div>
            </div>
        </div>
        <p class="result-hero__stats">
            <?= (int) $attempt['correct_count'] ?>/<?= (int) $attempt['total_questions'] ?> correct ·
            <?= e(format_duration((int) $attempt['duration_ms'])) ?>
            <?php if ($rank > 0): ?> · Rank <strong>#<?= $rank ?></strong> of <?= $totalParticipants ?><?php endif; ?>
        </p>
        <p class="share-heading">A score worth passing on. Can your friends beat it?</p>
        <div class="share-row">
            <?= \ChallengeChain\Core\View::partial('partials/share', [
                'challenge' => $challenge,
                'shareUrl' => $resultUrl,
                'shareText' => $shareText,
            ]) ?>
        </div>
        <div class="share-row mt-16">
            <a class="btn btn--blue" href="<?= e(url('/challenge/' . $challenge['slug'] . '/play')) ?>">Play again</a>
            <a class="btn btn--white" href="<?= e(url('/challenge/' . $challenge['slug'] . '/leaderboard')) ?>">Leaderboard</a>
            <a class="btn btn--ghost" href="<?= e(url('/challenge/' . $challenge['slug'])) ?>">Challenge info</a>
        </div>
    </div>
</div>

<section class="section">
    <div class="container container--narrow">
        <h2 class="section__title mb-24">Answer review</h2>
        <div class="card">
            <?php foreach ($review as $i => $q): ?>
                <?php
                $ans = $answerMap[(int) $q['id']] ?? null;
                $selected = $ans['selected'] ?? 0;
                ?>
                <div class="review-item">
                    <div class="review-item__q"><?= $i + 1 ?>. <?= e($q['question']) ?></div>
                    <?php foreach ($q['options'] as $opt): ?>
                        <?php
                        $isCorrect = $opt['is_correct'];
                        $isSelected = (int) $opt['id'] === $selected;
                        $class = 'review-opt ';
                        if ($isCorrect) {
                            $class .= 'review-opt--correct';
                        } elseif ($isSelected) {
                            $class .= 'review-opt--wrong';
                        } else {
                            $class .= 'review-opt--muted';
                        }
                        ?>
                        <div class="<?= $class ?>">
                            <span>
                                <?php if ($isCorrect): ?><i data-lucide="check-circle" width="15" height="15"></i>
                                <?php elseif ($isSelected): ?><i data-lucide="x-circle" width="15" height="15"></i>
                                <?php else: ?><i data-lucide="circle" width="15" height="15"></i><?php endif; ?>
                            </span>
                            <span><?= e($opt['text']) ?><?= $isSelected ? ' <strong>(your answer)</strong>' : '' ?></span>
                        </div>
                    <?php endforeach; ?>
                    <?php if ($q['explanation'] !== ''): ?>
                        <div class="review-expl"><strong>Why:</strong> <?= e($q['explanation']) ?></div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="mt-32 flex gap-16 flex-wrap" style="justify-content:center">
            <a class="btn btn--primary" href="<?= e(url('/explore')) ?>">Explore more challenges</a>
            <a class="btn btn--blue" href="<?= e(url('/create')) ?>"><i data-lucide="sparkles" width="16" height="16"></i> Create your own</a>
        </div>
    </div>
</section>
