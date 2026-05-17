<?php

declare(strict_types=1);

Auth::requireAdmin();
$pageTitle = $pageTitle ?? 'Admin';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?> - Admin</title>
    <link rel="stylesheet" href="<?= e(asset_url('assets/css/styles.css')) ?>">
</head>
<body class="admin-body">
<aside class="admin-sidebar">
    <a class="admin-brand" href="<?= e(base_url('admin/index.php')) ?>"><?= e(setting('site_name', 'Store')) ?></a>
    <nav class="admin-nav" aria-label="Admin navigation">
        <a href="<?= e(base_url('admin/index.php')) ?>">Dashboard</a>
        <a href="<?= e(base_url('admin/orders.php')) ?>">Orders</a>
        <a href="<?= e(base_url('admin/product.php')) ?>">Product</a>
        <a href="<?= e(base_url('admin/media.php')) ?>">Media</a>
        <a href="<?= e(base_url('admin/email.php')) ?>">Notifications</a>
        <a href="<?= e(base_url('admin/update.php')) ?>">Updates</a>
        <a href="<?= e(base_url('admin/settings.php')) ?>">Settings</a>
        <a href="<?= e(base_url('admin/security.php')) ?>">Security</a>
        <a href="<?= e(base_url('/')) ?>" target="_blank" rel="noopener">View Store</a>
        <a href="<?= e(base_url('admin/logout.php')) ?>">Logout</a>
    </nav>
</aside>
<main class="admin-main">
    <header class="admin-topbar">
        <div>
            <p class="muted">Logged in as</p>
            <strong><?= e(Auth::userName()) ?></strong>
        </div>
    </header>
    <?php if ($message = flash('success')): ?>
        <div class="alert alert-success"><?= e($message) ?></div>
    <?php endif; ?>
    <?php if ($message = flash('error')): ?>
        <div class="alert alert-error"><?= e($message) ?></div>
    <?php endif; ?>
