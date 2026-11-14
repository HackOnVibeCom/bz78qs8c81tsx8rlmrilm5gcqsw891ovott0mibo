<footer class="footer">
    <div class="container">
        <div class="footer__grid">
            <div><a class="nav__brand" href="<?= e(url('/')) ?>"><span class="nav__brand-mark"><i data-lucide="zap" width="19" height="19"></i></span>ChallengeChain</a><p class="footer__description">A little curiosity goes a long way.<br>Create. Share. Challenge. Repeat.</p></div>
            <nav class="footer__links" aria-label="Footer"><a href="<?= e(url('/explore')) ?>">Explore challenges</a><a href="<?= e(url('/create')) ?>">Create a challenge</a><a href="<?= e(url('/#how-it-works')) ?>">How it works</a><a href="<?= e(url('/login')) ?>">Sign in</a></nav>
        </div>
        <div class="footer__bottom"><span>© <?= date('Y') ?> ChallengeChain · Made for HackOnVibe</span><span class="footer__tagline"><i data-lucide="globe" width="13" height="13"></i> For curious people, everywhere.</span></div>
    </div>
</footer>
