<?php $notFoundPath = current_path(); ?>
<section class="error-page">
    <div class="container error-page__inner">
        <span class="error-page__code">404</span>
        <p class="eyebrow"><span></span> Page not found</p>
        <h1>This line isn’t in the catalog.</h1>
        <p>The page may have moved, or the product or category no longer exists. Try the catalog, or send the requirement to our sales team.</p>
        <div class="error-page__actions">
            <a class="btn btn--primary btn--lg" href="/catalog">Browse the catalog <?= icon('arrow-right') ?></a>
            <a class="btn btn--outline btn--lg" href="/quote">Request a quote</a>
        </div>
        <div class="error-page__suggestions">
            <span>Popular:</span>
            <a href="/catalog/extinguisher">Fire extinguishers</a>
            <a href="/catalog/hose-reel-drums">Hose reel drums</a>
            <a href="/catalog/hose-boxes-and-fire-shaft-door">Hose boxes</a>
            <a href="/catalog/sprinkler">Sprinklers</a>
        </div>
        <p class="error-page__path">Requested: <code><?= e($notFoundPath) ?></code></p>
    </div>
</section>
