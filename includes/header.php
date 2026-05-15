<?php

declare(strict_types=1);

$pageTitle = $pageTitle ?? setting('site_name', app_config('app.name', 'Single Product Store'));
$bodyClass = $bodyClass ?? '';
$hideHeader = $hideHeader ?? false;
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0d9488">
    <title><?= e($pageTitle) ?></title>
    <?php render_tracking_head(); ?>
    <link rel="preload" href="<?= e(asset_url('assets/css/styles.css')) ?>" as="style">
    <link rel="stylesheet" href="<?= e(asset_url('assets/css/styles.css')) ?>">
</head>
<body class="<?= e($bodyClass) ?>">
<?php render_gtm_noscript(); ?>
<?php if (!$hideHeader): ?>
<header class="site-header">
    <div class="container header-grid">
        <a class="brand" href="<?= e(base_url('/')) ?>"><?= e(setting('site_name', app_config('app.name', 'Store'))) ?></a>
        <nav class="site-nav" aria-label="Primary navigation">
            <a href="<?= e(base_url('/')) ?>">Shop</a>
            <a href="<?= e(base_url('account.php')) ?>">My Account</a>
            <a href="<?= e(base_url('track.php')) ?>">Track Order</a>
        </nav>
    </div>
</header>
<?php endif; ?>
<main>
