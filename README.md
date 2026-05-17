# Single Product Ecommerce

Core PHP + MySQL ecommerce for cPanel/shared hosting. It includes a fast landing page, COD checkout, thank-you page, customer order lookup, admin order management, printable invoices, settings for tracking pixels, and Steadfast shipment creation.

## Requirements

- PHP 8.2+
- MySQL 5.7+ or MariaDB 10.4+
- PHP extensions: PDO MySQL, cURL, JSON, mbstring
- Apache/cPanel hosting with `.htaccess` support

## Setup

1. Copy `config.sample.php` to `config.php`.
2. Update database credentials in `config.php`.
3. Open `/install.php` in the browser and create the first admin user.
4. Delete or rename `install.php` after setup.
5. Log in at `/admin/login.php`.

For production upload steps, follow `DEPLOYMENT.md`.

## Git ছাড়া easy deploy/sync

এই project direct FTP/SFTP দিয়ে cPanel `public_html`-এ deploy করা যায়। Credentials codebase-এ hardcode করা নেই; local `.env.deploy` file থেকে পড়া হয়।

### 1. Deploy env তৈরি করুন

```powershell
Copy-Item .env.deploy.example .env.deploy
```

তারপর `.env.deploy` edit করুন:

```dotenv
DEPLOY_HOST=example.com
DEPLOY_PORT=22
DEPLOY_USER=cpanel_user
DEPLOY_PASSWORD=your_password
DEPLOY_REMOTE_PATH=/public_html/
DEPLOY_PROTOCOL=sftp
```

`DEPLOY_PROTOCOL` হতে পারে `sftp` অথবা `ftp`। Script `curl.exe` ব্যবহার করে, তাই Windows-এ `curl.exe` available থাকতে হবে এবং SFTP ব্যবহার করলে curl build-এ SFTP support থাকতে হবে।

### 2. One-time deploy

```powershell
powershell -ExecutionPolicy Bypass -File .\deploy.ps1
```

### 3. Watch mode

Local file edit/save করলে auto upload করতে:

```powershell
powershell -ExecutionPolicy Bypass -File .\deploy.ps1 --watch
```

Deploy script এগুলো upload করে না: `.git`, `.env`, `.env.deploy`, `config.php`, `node_modules`, `vendor`, `storage/logs`, `cache`, log/cache files, `install.php`, README/deploy helper files। Live `config.php` cPanel-এ আলাদা রাখুন।

## Plain PHP database migrations

এই project Laravel না, তাই lightweight SQL migration runner আছে।

### 1. DB env তৈরি করুন

```powershell
Copy-Item .env.example .env
```

`.env` file-এ DB info দিন:

```dotenv
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=your_database_name
DB_USERNAME=your_database_user
DB_PASSWORD=your_database_password
DB_CHARSET=utf8mb4
```

### 2. Migration file তৈরি করুন

নতুন SQL file রাখুন:

```text
database/migrations/2026_05_17_000001_add_example_column.sql
```

### 3. Pending migrations run করুন

```powershell
php scripts/migrate.php
```

Dangerous SQL যেমন `DROP TABLE` বা `DROP DATABASE` থাকলে runner `RUN` টাইপ করে confirmation চাইবে। Automation-এ ইচ্ছাকৃতভাবে চালাতে হলে:

```powershell
php scripts/migrate.php --force
```

### Test/run checklist

```powershell
powershell -ExecutionPolicy Bypass -File .\deploy.ps1
powershell -ExecutionPolicy Bypass -File .\deploy.ps1 --watch
php scripts/migrate.php
```

এই local machine-এ PHP CLI না থাকলে migration command local-এ চলবে না; cPanel Terminal/SSH বা যেই machine-এ PHP CLI আছে সেখানে run করুন।

## Admin

- Manage product price, stock, images, and delivery charge.
- View/filter orders and update statuses.
- Print invoices from each order detail page.
- Add GTM, GA4, Facebook Pixel, verification meta tags, and Steadfast credentials in settings.
- Create a Steadfast shipment from an order detail page.

## Steadfast

The default API shape uses `POST /create_order` with `Api-Key` and `Secret-Key` headers. Confirm the base URL and credentials from your Steadfast merchant/API panel before going live.
