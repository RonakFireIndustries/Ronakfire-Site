<?php $flashItems = flashes(); ?>
<?php if ($flashItems): ?>
    <div class="container flash-stack" role="status" aria-live="polite">
        <?php foreach ($flashItems as $flashItem): ?>
            <div class="flash flash--<?= e($flashItem['type'] ?? 'info') ?>">
                <?= icon(($flashItem['type'] ?? '') === 'error' ? 'alert' : (($flashItem['type'] ?? '') === 'success' ? 'check' : 'spark')) ?>
                <p><?= e($flashItem['message'] ?? '') ?></p>
                <button class="flash__close" type="button" data-flash-close aria-label="Dismiss"><?= icon('close') ?></button>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
