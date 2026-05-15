<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

Auth::requireAdmin();
ensure_email_schema();

$plainSettings = [
    'mail_from_email' => 'From Email',
    'mail_from_name' => 'From Name',
    'admin_notification_email' => 'Admin Notification Email',
    'mailjet_api_key' => 'Mailjet API Key',
];

$templateSettings = [
    'admin_order_email_subject' => 'Admin Email Subject',
    'admin_order_email_html' => 'Admin HTML Template',
    'admin_order_email_text' => 'Admin Text Template',
    'customer_order_email_subject' => 'Customer Email Subject',
    'customer_order_email_html' => 'Customer HTML Template',
    'customer_order_email_text' => 'Customer Text Template',
];
$emailLogs = list_email_logs(20);

if (is_post()) {
    verify_csrf();
    try {
        save_setting('email_enabled', isset($_POST['email_enabled']) ? '1' : '0');
        save_setting('admin_order_email_enabled', isset($_POST['admin_order_email_enabled']) ? '1' : '0');
        save_setting('customer_order_email_enabled', isset($_POST['customer_order_email_enabled']) ? '1' : '0');

        foreach ($plainSettings as $key => $label) {
            save_setting($key, trim((string)($_POST[$key] ?? '')));
        }
        foreach ($templateSettings as $key => $label) {
            save_setting($key, trim((string)($_POST[$key] ?? '')));
        }

        $secret = trim((string)($_POST['mailjet_secret_key'] ?? ''));
        if ($secret !== '') {
            save_setting('mailjet_secret_key', $secret);
        }

        flash('success', 'Email settings updated.');
    } catch (Throwable $exception) {
        flash('error', $exception->getMessage());
    }

    redirect('admin/email.php');
}

$pageTitle = 'Email';
require BASE_PATH . '/includes/admin_header.php';
?>

<section class="admin-section">
    <div class="panel-head">
        <div>
            <h1>Email</h1>
            <p class="muted">Mailjet API settings and editable order email templates.</p>
        </div>
    </div>

    <form class="content-panel admin-form email-settings-form" method="post">
        <?= csrf_field() ?>

        <div class="settings-card">
            <h2>Mailjet API</h2>
            <label class="toggle-line">
                <input type="checkbox" name="email_enabled" value="1" <?= setting('email_enabled', '0') === '1' ? 'checked' : '' ?>>
                Enable email sending
            </label>
            <div class="form-grid">
                <?php foreach ($plainSettings as $key => $label): ?>
                    <?php
                    $fallback = match ($key) {
                        'mail_from_email', 'admin_notification_email' => setting('support_email'),
                        'mail_from_name' => setting('site_name'),
                        default => '',
                    };
                    ?>
                    <label>
                        <?= e($label) ?>
                        <input type="<?= str_contains($key, 'email') ? 'email' : 'text' ?>" name="<?= e($key) ?>" value="<?= e(setting($key, $fallback)) ?>">
                    </label>
                <?php endforeach; ?>
                <label>
                    Mailjet Secret Key
                    <input type="password" name="mailjet_secret_key" placeholder="Leave blank to keep current key">
                </label>
            </div>
        </div>

        <div class="settings-card">
            <h2>Email Rules</h2>
            <label class="toggle-line">
                <input type="checkbox" name="admin_order_email_enabled" value="1" <?= setting('admin_order_email_enabled', '1') === '1' ? 'checked' : '' ?>>
                Send admin email when a new order arrives
            </label>
            <label class="toggle-line">
                <input type="checkbox" name="customer_order_email_enabled" value="1" <?= setting('customer_order_email_enabled', '1') === '1' ? 'checked' : '' ?>>
                Send customer order email once after order placement
            </label>
        </div>

        <div class="settings-card">
            <h2>Template Variables</h2>
            <p class="template-vars">
                {{order_number}} {{customer_name}} {{customer_phone}} {{customer_email}} {{customer_address}}
                {{district_area}} {{payment_method}} {{status}} {{subtotal}} {{delivery_charge}} {{total}}
                {{items_table}} {{track_url}} {{admin_order_url}} {{site_name}} {{support_email}} {{contact_phone}}
            </p>
        </div>

        <div class="email-template-grid">
            <div class="settings-card">
                <h2>Admin Template</h2>
                <label>
                    Subject
                    <input type="text" name="admin_order_email_subject" value="<?= e(email_template_value('admin_order_email_subject')) ?>">
                </label>
                <label>
                    HTML Template
                    <textarea name="admin_order_email_html" rows="10"><?= e(email_template_value('admin_order_email_html')) ?></textarea>
                </label>
                <label>
                    Text Template
                    <textarea name="admin_order_email_text" rows="7"><?= e(email_template_value('admin_order_email_text')) ?></textarea>
                </label>
            </div>

            <div class="settings-card">
                <h2>Customer Template</h2>
                <label>
                    Subject
                    <input type="text" name="customer_order_email_subject" value="<?= e(email_template_value('customer_order_email_subject')) ?>">
                </label>
                <label>
                    HTML Template
                    <textarea name="customer_order_email_html" rows="10"><?= e(email_template_value('customer_order_email_html')) ?></textarea>
                </label>
                <label>
                    Text Template
                    <textarea name="customer_order_email_text" rows="7"><?= e(email_template_value('customer_order_email_text')) ?></textarea>
                </label>
            </div>
        </div>

        <button class="button button-primary" type="submit">Save Email Settings</button>
    </form>

    <div class="content-panel">
        <div class="panel-head">
            <h2>Recent Email Logs</h2>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                <tr><th>Time</th><th>Order</th><th>Type</th><th>Recipient</th><th>Status</th><th>Message</th></tr>
                </thead>
                <tbody>
                <?php foreach ($emailLogs as $log): ?>
                    <tr>
                        <td><?= e(date('d M Y H:i', strtotime((string)$log['created_at']))) ?></td>
                        <td><?= e((string)($log['order_number'] ?? '-')) ?></td>
                        <td><?= e($log['email_type']) ?></td>
                        <td><?= e($log['recipient_email']) ?></td>
                        <td><span class="<?= e($log['status'] === 'sent' ? 'badge badge-green' : 'badge badge-red') ?>"><?= e($log['status']) ?></span></td>
                        <td><?= e((string)($log['provider_message_id'] ?: $log['error_message'] ?: '-')) ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$emailLogs): ?>
                    <tr><td colspan="6">No email logs yet.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>

<?php require BASE_PATH . '/includes/admin_footer.php'; ?>
