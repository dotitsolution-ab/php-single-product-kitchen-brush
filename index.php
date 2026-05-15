<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

$setupError = null;
$product = null;

try {
    $product = active_product();
} catch (Throwable $exception) {
    $setupError = $exception->getMessage();
}

$pageTitle = setting('site_name', app_config('app.name', 'Single Product Store'));
require BASE_PATH . '/includes/header.php';
?>

<?php if ($setupError): ?>
    <section class="container section">
        <div class="alert alert-error">
            <?= e($setupError) ?> Copy <strong>config.sample.php</strong> to <strong>config.php</strong>, update database credentials, then run <strong>/install.php</strong>.
        </div>
    </section>
<?php elseif (!$product): ?>
    <section class="container section">
        <div class="alert alert-error">No active product found. Add a product from the admin panel.</div>
    </section>
<?php else: ?>
    <section class="hero">
        <div class="container hero-grid">
            <div class="hero-copy">
                <span class="eyebrow">Cash on delivery</span>
                <h1><?= e($product['name']) ?></h1>
                <p class="lead"><?= e($product['tagline']) ?></p>
                <div class="price-row">
                    <strong><?= e(money($product['price'])) ?></strong>
                    <?php if (!empty($product['compare_price'])): ?>
                        <del><?= e(money($product['compare_price'])) ?></del>
                    <?php endif; ?>
                </div>
                <div class="hero-actions">
                    <a class="button button-primary" href="#checkout">Order Now</a>
                    <a class="button button-secondary" href="<?= e(base_url('track.php')) ?>">Track Order</a>
                </div>
            </div>
            <div class="product-visual">
                <img src="<?= e($product['image_url']) ?>" alt="<?= e($product['name']) ?>" loading="eager" width="900" height="700">
            </div>
        </div>
    </section>

    <section class="container section product-section">
        <div class="content-panel">
            <h2>Product Details</h2>
            <p><?= nl2br(e($product['description'])) ?></p>
            <ul class="check-list">
                <?php foreach (product_highlights($product) as $highlight): ?>
                    <li><?= e($highlight) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>

        <form id="checkout" class="checkout-panel" method="post" action="<?= e(base_url('checkout.php')) ?>">
            <h2>Complete Order</h2>
            <?php if ($message = flash('error')): ?>
                <div class="alert alert-error"><?= e($message) ?></div>
            <?php endif; ?>
            <?= csrf_field() ?>
            <label>
                Name
                <input type="text" name="name" value="<?= e(old('name')) ?>" autocomplete="name" required maxlength="120">
            </label>
            <label>
                Phone
                <input type="tel" name="phone" value="<?= e(old('phone')) ?>" autocomplete="tel" required placeholder="01XXXXXXXXX">
            </label>
            <label>
                Address
                <textarea name="address" rows="3" required maxlength="500"><?= e(old('address')) ?></textarea>
            </label>
            <label>
                District / Area
                <input type="text" name="district_area" value="<?= e(old('district_area')) ?>" required maxlength="120">
            </label>
            <div class="form-grid">
                <label>
                    Quantity
                    <input type="number" name="quantity" value="<?= e(old('quantity', '1')) ?>" min="1" max="<?= e($product['stock']) ?>" required>
                </label>
                <label>
                    Delivery
                    <input type="text" value="<?= e(money($product['delivery_charge'])) ?>" disabled>
                </label>
            </div>
            <label>
                Delivery Note
                <textarea name="delivery_note" rows="2" maxlength="500"><?= e(old('delivery_note')) ?></textarea>
            </label>
            <div class="order-total">
                <span>Total starts from</span>
                <strong><?= e(money((float)$product['price'] + (float)$product['delivery_charge'])) ?></strong>
            </div>
            <button class="button button-primary button-full" type="submit">Place COD Order</button>
        </form>
    </section>
<?php endif; ?>

<?php
clear_old();
require BASE_PATH . '/includes/footer.php';

