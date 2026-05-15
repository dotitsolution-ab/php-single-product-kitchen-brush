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

## Admin

- Manage product price, stock, images, and delivery charge.
- View/filter orders and update statuses.
- Print invoices from each order detail page.
- Add GTM, GA4, Facebook Pixel, verification meta tags, and Steadfast credentials in settings.
- Create a Steadfast shipment from an order detail page.

## Steadfast

The default API shape uses `POST /create_order` with `Api-Key` and `Secret-Key` headers. Confirm the base URL and credentials from your Steadfast merchant/API panel before going live.
