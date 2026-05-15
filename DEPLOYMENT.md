# cPanel Deployment Runbook

## 1. Create Database

In cPanel, create:

- MySQL database
- MySQL user
- Assign the user to the database with all privileges

Keep these values ready for `config.php`.

## 2. Upload Files

Upload all project files to the domain document root, usually `public_html/`.

Do not upload a real `config.php` to a public repo. Create it directly on the server by copying `config.sample.php`.

## 3. Configure App

Create `config.php` from `config.sample.php` and update:

```php
'app' => [
    'base_url' => 'https://yourdomain.com',
],
'database' => [
    'host' => 'localhost',
    'name' => 'cpanel_db_name',
    'user' => 'cpanel_db_user',
    'pass' => 'cpanel_db_password',
],
```

For production, keep:

```php
'debug' => false,
```

## 4. Install

Open:

```text
https://yourdomain.com/install.php
```

Create the first admin user.

After successful install, delete or rename:

```text
install.php
```

## 5. Admin Setup

Open:

```text
https://yourdomain.com/admin/login.php
```

Then configure:

- Product name, price, stock, image, delivery charge
- Site name and support phone
- GTM ID
- GA4 measurement ID
- Facebook Pixel ID
- Google site verification
- Facebook domain verification
- Steadfast base URL, API key, and secret key

## 6. Required Server Extensions

Ask hosting support to enable these if unavailable:

- PDO MySQL
- cURL
- JSON
- mbstring

## 7. Smoke Test

Before running ads:

- Place one COD order from mobile.
- Confirm thank-you page shows the order ID.
- Look up the order from My Account with phone + order ID.
- Open admin order details.
- Print invoice.
- Save manual courier details.
- Test Steadfast shipment creation with a real API key.
- Confirm GA4/Facebook purchase events from thank-you page.

## 8. Performance Checklist

- Use a WebP product image under 250 KB.
- Avoid third-party widgets beyond GTM/GA4/Pixel.
- Keep hosting PHP version at 8.2 or newer.
- Enable LiteSpeed Cache or server-level compression if available.
- Test with PageSpeed Insights after connecting the real domain.

## 9. Updating the Live Website

Recommended repeat workflow:

```text
Local project -> GitHub private repo -> cPanel Git pull/deploy -> live website
```

### First-Time Git Setup

1. Create a private GitHub repository.
2. Push this project to that repository.
3. In cPanel, open **Git Version Control**.
4. Clone the GitHub repository.
5. Set the repository deployment path to the website folder, usually `public_html/`.
6. Keep the live `config.php` only on the server. It should not be committed.

This project includes `.cpanel.yml`, which tells cPanel how to deploy files. It excludes:

- `.git`
- `config.php`
- `install.php`
- README/deployment docs

### Normal Update Flow

After making changes locally:

```bash
git status
git add .
git commit -m "Update ecommerce site"
git push
```

Then in cPanel:

1. Open **Git Version Control**.
2. Open the repository.
3. Click **Pull or Deploy**.
4. Run **Update from Remote**.
5. Run **Deploy HEAD Commit**.

### Quick Live Check

After every deploy:

- Open homepage.
- Place a test order if checkout changed.
- Check thank-you page.
- Check My Account lookup.
- Check admin orders page.
- Check invoice page.
- If courier logic changed, test Steadfast on one order.

### Security Update Notes

After pulling the security hardening update:

- Open admin login once. The app will create security tables automatically if the database user has `CREATE` permission.
- If auto-create is disabled by hosting permissions, import `database/security_migration.sql` from phpMyAdmin.
- Confirm `/install.php` is deleted or renamed on the live server.
- Confirm `config.php` exists only on the server and is not committed to Git.
- In cPanel, keep PHP `display_errors` off for production.

### Product Images

Upload product images to:

```text
public_html/assets/images/
```

Suggested filenames:

```text
kitchen-brush-hero-drain.jpg
kitchen-brush-frypan-foam.jpg
kitchen-brush-pan-cleaning.jpg
kitchen-brush-hanging-storage.jpg
kitchen-brush-plate-demo.jpg
kitchen-brush-pan-close.jpg
```

The cPanel deploy config excludes `assets/images/*`, so future Git deploys will not delete images uploaded on the server.

### Emergency Manual Update

If Git is not available in cPanel, upload changed files with File Manager or FTP/SFTP.

Do not overwrite live `config.php`.

For the latest `SQLSTATE[HY093]` fix, upload:

```text
includes/store.php
```
