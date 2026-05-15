<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

$order = null;
$lookupError = null;

if (is_post()) {
    verify_csrf();
    $order = order_by_number((string)($_POST['order_number'] ?? ''), (string)($_POST['phone'] ?? ''));
    if (!$order) {
        $lookupError = 'Tracking details were not found for that order.';
    }
}

$pageTitle = 'Track Order';
require BASE_PATH . '/includes/header.php';
?>

<section class="container section narrow">
    <form class="content-panel" method="post">
        <h1>Track Order</h1>
        <?php if ($lookupError): ?>
            <div class="alert alert-error"><?= e($lookupError) ?></div>
        <?php endif; ?>
        <?= csrf_field() ?>
        <label>
            Order ID
            <input type="text" name="order_number" value="<?= e($_POST['order_number'] ?? '') ?>" required>
        </label>
        <label>
            Phone
            <span class="phone-field">
                <span class="phone-prefix">+88</span>
                <input type="tel" name="phone" value="<?= e(normalize_phone((string)($_POST['phone'] ?? ''))) ?>" inputmode="numeric" pattern="01[3-9][0-9]{8}" maxlength="11" data-phone-input required placeholder="01XXXXXXXXX">
            </span>
        </label>
        <button class="button button-primary button-full" type="submit">Track</button>
    </form>

    <?php if ($order): ?>
        <div class="tracking-timeline">
            <?php foreach (status_options() as $status): ?>
                <?php
                $isActive = array_search($status, status_options(), true) <= array_search((string)$order['status'], status_options(), true)
                    && $order['status'] !== 'Cancelled';
                ?>
                <div class="timeline-step <?= $isActive ? 'is-active' : '' ?>">
                    <span></span>
                    <strong><?= e($status) ?></strong>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="content-panel">
            <h2>Current Status</h2>
            <p><span class="<?= e(status_class($order['status'])) ?>"><?= e($order['status']) ?></span></p>
            <?php if ($order['shipment']): ?>
                <div class="summary-table">
                    <div><span>Courier</span><strong><?= e($order['shipment']['courier_name']) ?></strong></div>
                    <div><span>Tracking Code</span><strong><?= e($order['shipment']['tracking_code'] ?: 'Pending') ?></strong></div>
                    <div><span>Courier Status</span><strong><?= e($order['shipment']['shipment_status'] ?: 'Created') ?></strong></div>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</section>

<?php require BASE_PATH . '/includes/footer.php'; ?>
