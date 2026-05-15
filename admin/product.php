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
                    Delivery Charge
                    <input type="number" name="delivery_charge" value="<?= e($product['delivery_charge']) ?>" min="0" step="1" required>
                </label>
                <label>
                    Stock
                    <input type="number" name="stock" value="<?= e($product['stock']) ?>" min="0" step="1" required>
                </label>
            </div>
            <label>
                Image URL
                <input type="url" name="image_url" value="<?= e($product['image_url']) ?>">
            </label>
            <button class="button button-primary" type="submit">Save Product</button>
        </form>
    <?php endif; ?>
</section>

<?php require BASE_PATH . '/includes/admin_footer.php'; ?>
