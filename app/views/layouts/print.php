<?php /** @var string $content */ ?><!doctype html>
<html lang="en-IN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle ?? 'Proforma invoice') ?></title>
    <meta name="robots" content="noindex, nofollow">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(asset('assets/css/print.css')) ?>">
</head>
<body>
<div class="print-toolbar no-print">
    <a href="/account/orders">← Back to orders</a>
    <button type="button" data-print>Print / save as PDF</button>
</div>
<?= $content ?>
<script src="<?= e(asset('assets/js/print.js')) ?>" defer></script>
</body>
</html>
