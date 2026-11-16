# Ater Perfume Store

A PHP and MySQL e-commerce website for browsing and purchasing fragrances. The project includes a customer storefront, product catalog, cart and wishlist, checkout, order management, reviews, and an admin area.

## Features

- Browse fragrances, brands, categories, and product variants.
- Guest cart and wishlist, with account sign-in for checkout and saved account features.
- Customer registration, email verification, password reset, and Google sign-in.
- Cash on delivery and Stripe Checkout integration.
- Product reviews with optional AI moderation and admin review controls.
- Admin tools for products, categories, brands, coupons, customers, orders, and reviews.

## Technology

- PHP 8.2 or newer
- MySQL or MariaDB
- HTML, CSS, and JavaScript
- Composer libraries: PHPMailer, Google API Client, Stripe PHP SDK, and Twilio SDK

Required PHP extensions: `pdo_mysql`, `curl`, `mbstring`, `openssl`, and `fileinfo`.

## Project structure

```text
actions/       Authentication and form actions
admin/         Admin pages
api/           JSON endpoints used by the storefront and admin
assets/        CSS, JavaScript, and image placeholders
classes/       Database and business logic classes
config/        Database settings and private integration configuration
core/          Shared response and access-control helpers
database/      SQL schema and migrations
includes/      Shared PHP bootstrap, navigation, and layout files
postman/       API collection
uploads/       Product image upload directory
```

## Local setup with XAMPP

1. Put the project folder under `C:\xampp\htdocs` and start Apache and MySQL from the XAMPP Control Panel.
2. Open a terminal in the project folder and install PHP dependencies:

   ```powershell
   composer install
   ```

3. In phpMyAdmin, create a database named `perfume_ecommerce` using `utf8mb4`, then import `database/schema.sql`.
4. Import your product catalog data separately. The schema creates tables only; it does not include fragrances, images, brands, categories, or variants. Keep database exports containing customer or order information private.
5. The current local database defaults in `config/Database.php` are `localhost`, database `perfume_ecommerce`, username `root`, and an empty password. Change these values if your XAMPP database uses different credentials.
6. Open the site at the URL matching the folder name. For this repository path, that is:

   ```text
   http://localhost/projects/atr_ecommerc/
   ```

## Optional integrations

The site requires private configuration files for integrations. Create these files locally or on the server; do not commit real credentials to GitHub.

### Google sign-in

Create `config/google.php`:

```php
<?php
return [
    'client_id' => 'YOUR_GOOGLE_CLIENT_ID',
    'client_secret' => 'YOUR_GOOGLE_CLIENT_SECRET',
    'redirect_uri' => 'https://YOUR-DOMAIN/actions/google-callback.php',
];
```

The redirect URI must exactly match the authorized URI in Google Cloud Console. For local development, use the localhost URL and folder path where this project is installed.

### Email delivery

Create `config/mail.php`:

```php
<?php
return [
    'host' => 'smtp.example.com',
    'port' => 465,
    'username' => 'YOUR_SMTP_USERNAME',
    'password' => 'YOUR_SMTP_APP_PASSWORD',
];
```

Email verification, password reset, and order notifications require working SMTP credentials. The mailer uses secure SMTP on port 465.

### Stripe payments

Create `config/stripe.php`:

```php
<?php
return [
    'secret_key' => 'YOUR_STRIPE_SECRET_KEY',
];
```

Use Stripe test keys while developing. Configure production keys and verify the hosted checkout flow before accepting live payments.

### AI review moderation

AI moderation is optional. To enable the OpenAI-powered moderation and its test endpoint, create `config/ai.php`:

```php
<?php
return [
    'api_key' => 'YOUR_OPENAI_API_KEY',
    'model' => 'YOUR_SUPPORTED_MODEL',
];
```

Keep the key private. The review moderation test endpoint returns a classification result without saving a review; disable or restrict that endpoint on a public production site.

## Create an admin account

Register an account and verify its email. Then run this query in phpMyAdmin, replacing the email with your account's address:

```sql
UPDATE users
SET role = 'admin'
WHERE email = 'you@example.com';
```

Sign in again and open `/admin/` under the project URL.

## Deployment notes

GitHub stores the source code but does not run PHP or provide the MySQL database. Use a PHP host with MySQL/MariaDB, Composer, HTTPS, and Apache `.htaccess` support.

- Deploy the repository's `main` branch and set the project root containing `index.php` as the web root.
- Create a production database and database user, import `database/schema.sql`, then import catalog data privately.
- Update the database connection in `config/Database.php` for the hosting provider. Do not use the MySQL `root` account on a live site, and do not commit production credentials.
- Create the private Google, mail, Stripe, and AI config files on the server only for integrations you use.
- Configure Google OAuth with the production callback URL, enable HTTPS, and make `uploads/` writable by PHP.
- Update localhost-based callback and password-reset URLs in the application before launch.
- Stripe Checkout requires the server to reach Stripe's API. AI moderation requires outbound HTTPS access to OpenAI.

## Security and repository hygiene

- Never commit API keys, OAuth secrets, SMTP passwords, payment keys, `.env` files, or database exports with private user data.
- `vendor/` is installed with Composer and is not stored in Git.
- Product uploads are local files and are not included in the repository; transfer them to the host separately when needed.