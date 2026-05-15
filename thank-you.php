<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

$orderNumber = (string)($_GET['order'] ?? '');
$order = $orderNumber !== '' ? order_by_number($orderNumber) : null;
$pageTitle = 'Thank You';
require BASE_PATH . '/includes/header.php';
?>

<section class="container section narrow">
    <?php if (!$order): ?>
        <div class="alert alert-error">Order not found.</div>
    <?php else: ?>
        <div class="thank-you">
            <span class="<?= e(status_class($order['status'])) ?>"><?= e($order['status']) ?></span>
            <h1>Thank you for your order.</h1>
            <p>Your order ID is <strong><?= e($order['order_number']) ?></strong>. Please keep it for order lookup and tracking.</p>
            <div class="summary-table">
                <div><span>Name</span><strong><?= e($order['customer_name']) ?></strong></div>
                <div><span>Phone</span><strong><?= e($order['customer_phone']) ?></strong></div>
                <div><span>Total</span><strong><?= e(money($order['total'])) ?></strong></div>
                <div><span>Payment</span><strong><?= e($order['payment_method']) ?></strong></div>
            </div>
            <div class="hero-actions">
                <a class="button button-primary" href="<?= e(base_url('account.php')) ?>">My Account</a>
                <a class="button button-secondary" href="<?= e(base_url('track.php')) ?>">Track Order</a>
            </div>
            <?php render_order_success_tracking($order); ?>
        </div>
    <?php endif; ?>
</section>

<?php require BASE_PATH . '/includes/footer.php'; ?>
