<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

Auth::requireAdmin();

if (is_post()) {
    verify_csrf();
    try {
        save_product($_POST);
        flash('success', 'Product updated.');
    } catch (Throwable $exception) {
        flash('error', $exception->getMessage());
    }
    redirect('admin/product.php');
}

$product = active_product();
$pageTitle = 'Product';
require BASE_PATH . '/includes/admin_header.php';
?>

<section class="admin-section">
    <h1>Product</h1>
    <?php if (!$product): ?>
        <div class="alert alert-error">No active product found. Run installer or import database/schema.sql.</div>
    <?php else: ?>
        <form class="content-panel admin-form" method="post">
            <?= csrf_field() ?>
            <h2>Core Product</h2>
            <label>
                Product Name
                <input type="text" name="name" value="<?= e($product['name']) ?>" required maxlength="190">
            </label>
            <label>
                Tagline
                <input type="text" name="tagline" value="<?= e($product['tagline']) ?>" maxlength="255">
            </label>
            <label>
                Description
                <textarea name="description" rows="5"><?= e($product['description']) ?></textarea>
            </label>
            <label>
                Highlights
                <textarea name="highlights" rows="5"><?= e($product['highlights']) ?></textarea>
            </label>
            <div class="form-grid">
                <label>
                    Price
                    <input type="number" name="price" value="<?= e($product['price']) ?>" min="0" step="1" required>
                </label>
                <label>
                    Compare Price
                    <input type="number" name="compare_price" value="<?= e($product['compare_price']) ?>" min="0" step="1">
                </label>
                <label>
                    Stock
                    <input type="number" name="stock" value="<?= e($product['stock']) ?>" min="0" step="1" required>
                </label>
            </div>
            <label>
                Main Product Image URL
                <input type="url" name="image_url" value="<?= e($product['image_url']) ?>">
            </label>

            <h2>Landing Page Content</h2>
            <label>
                Hero Badge
                <input type="text" name="landing_badge" value="<?= e(landing_value('badge')) ?>">
            </label>
            <label>
                Hero Subtitle
                <textarea name="landing_hero_subtitle" rows="2"><?= e(landing_value('hero_subtitle')) ?></textarea>
            </label>
            <div class="form-grid">
                <label>
                    Discount Label
                    <input type="text" name="landing_discount_label" value="<?= e(landing_value('discount_label')) ?>">
                </label>
                <label>
                    Button Text
                    <input type="text" name="landing_cta_text" value="<?= e(landing_value('cta_text')) ?>">
                </label>
            </div>
            <label>
                Demo / Circle Image URL
                <input type="url" name="landing_demo_image_url" value="<?= e(landing_value('demo_image_url')) ?>">
            </label>
            <div class="form-grid">
                <label>
                    Dhaka Inside Delivery Charge
                    <input type="number" name="landing_delivery_inside_charge" value="<?= e(landing_value('delivery_inside_charge')) ?>" min="0" step="1">
                </label>
                <label>
                    Dhaka Outside Delivery Charge
                    <input type="number" name="landing_delivery_outside_charge" value="<?= e(landing_value('delivery_outside_charge')) ?>" min="0" step="1">
                </label>
            </div>
            <label>
                Feature Cards
                <textarea name="landing_feature_rows" rows="8"><?= e(landing_value('feature_rows')) ?></textarea>
                <span class="field-help">One per line: Title|Small text|Image URL</span>
            </label>
            <label>
                Usage Images
                <textarea name="landing_usage_rows" rows="6"><?= e(landing_value('usage_rows')) ?></textarea>
                <span class="field-help">One per line: Title|Image URL</span>
            </label>
            <label>
                Why People Like It
                <textarea name="landing_reason_rows" rows="5"><?= e(landing_value('reason_rows')) ?></textarea>
                <span class="field-help">One reason per line.</span>
            </label>
            <button class="button button-primary" type="submit">Save Product</button>
        </form>
    <?php endif; ?>
</section>

<?php require BASE_PATH . '/includes/admin_footer.php'; ?>
