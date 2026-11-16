# Ater Perfume Store

A PHP and MySQL e-commerce project for browsing and purchasing fragrances. It includes a customer storefront, product catalog, cart and wishlist, checkout, orders, reviews, and an admin area.

## Project overview

The application is organized as a traditional PHP project rather than a Laravel application. PHP pages render the storefront and admin screens, `api/` endpoints handle browser requests, classes contain database/business logic, and MySQL stores users, products, variants, orders, reviews, and related data.

Main capabilities:

- Browse fragrances, brands, categories, scent families, and product sizes.
- Guest cart and wishlist, with sign-in required for checkout and account-specific features.
- Registration, email verification, password reset, and Google sign-in.
- Cash on delivery and Stripe Checkout.
- Product reviews with optional AI moderation and admin review controls.
- Admin pages for products, categories, brands, coupons, customers, orders, and reviews.

## How the perfume data was collected and imported

The web scraper used to collect the perfume source data is not included in this repository. The scraper populated a separate MySQL database named `testttt`, in a table named `perfumes`. The importer in this project reads that prepared table; it does not crawl websites itself.

`import_armaf.php` currently imports rows where `house = 'Armaf'`. It reads the perfume name and slug, house, perfumer, concentration, fragrance family and subfamily, scent description, source URL, and image URLs. It then:

1. Creates or reuses the Armaf brand and the fragrance-family category groups.
2. Creates or reuses product and category records in `perfume_ecommerce`.
3. Stores the campaign image as the primary product image and the public image as a gallery image. It falls back to `image_url` when those are unavailable.
4. Creates 100 ml, 200 ml, and 350 ml variants when they do not already exist.
5. Runs the import inside a database transaction and avoids duplicating existing products, images, and variants.

The importer currently assigns a starting price of `0.00` and stock of `25` to newly created variants. Set real prices and verify stock before making imported products available for sale.

To run the importer, both databases must be accessible on the same MySQL server. Update the connection values near the top of `import_armaf.php` if needed, then run this from the project directory:

```powershell
php import_armaf.php
```

To update images only for products that are already in the shop database:

```powershell
php import_armaf.php --images-only
```

`database/schema.sql` creates the shop tables but contains no perfume catalog rows. The scraper output/source database is separate and is not included in this repository.

## AI: current implementation and Ollama

### Review moderation currently uses OpenAI

When a customer submits a written review, `api/reviews.php` saves the review and asks `ReviewModerator` to classify its text through OpenAI's Responses API. The result contains a flag, moderation category, and short reason. Suitable reviews are approved; flagged reviews, or reviews that could not be checked because the AI service is unavailable, are left pending for an administrator. Ratings without written comments do not need text moderation.

To enable this feature, copy `config/ai.example.php` to the ignored local file `config/ai.php` and set its `api_key` and `model` values. Never commit the real API key.

### Ollama is not connected to this repository yet

Ollama is not currently called by this code. `classes/AiPerfumeAdvisor.php` and `api/ai-recommendations.php` are placeholders, so installing Ollama alone will not enable a perfume recommendation chatbot in this version. The existing review moderator is specifically implemented for OpenAI's API.

For a local Ollama installation on Windows, install Ollama, then run:

```powershell
ollama pull qwen3:8b
ollama serve
```

The local Ollama API is normally available at `http://127.0.0.1:11434`. These commands start Ollama and download a model; the PHP project still needs an Ollama client and request/response handling before any page can use it. A hosted website also cannot reach Ollama on your personal computer through `127.0.0.1`; Ollama would need to run on the same server or on a server the website can securely reach.

## Technology and requirements

- PHP 8.2 or newer
- MySQL or MariaDB
- Composer
- PHP extensions: `pdo_mysql`, `curl`, `mbstring`, `openssl`, and `fileinfo`
- Optional integrations: SMTP, Google OAuth, Stripe, OpenAI review moderation, or a separately implemented Ollama client

Composer dependencies include PHPMailer, Google API Client, Stripe PHP SDK, and Twilio SDK.

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

## Run locally with XAMPP

1. Put the project folder under `C:\xampp\htdocs` and start Apache and MySQL in the XAMPP Control Panel.
2. Open a terminal in the project directory and install dependencies:

   ```powershell
   composer install
   ```

3. In phpMyAdmin, create a database named `perfume_ecommerce` using `utf8mb4`, then import `database/schema.sql`.
4. Import the product catalog data separately. To use `import_armaf.php`, first make sure the source database `testttt` and its `perfumes` table are available on the same MySQL server.
5. The current local database defaults in `config/Database.php` are `localhost`, database `perfume_ecommerce`, username `root`, and an empty password. Change them if your local MySQL setup differs.
6. Open the site at the URL matching its folder. For this repository path:

   ```text
   http://localhost/projects/atr_ecommerc/
   ```

## Optional service configuration

Create these configuration files locally or on the server. They are private credentials and must not be committed to GitHub.

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

The redirect URI must exactly match the authorized URI in Google Cloud Console. For local development, include the actual localhost folder path.

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

Email verification, password resets, and order notifications require working SMTP credentials. The mailer uses secure SMTP on port 465.

### Stripe Checkout

Create `config/stripe.php`:

```php
<?php
return [
    'secret_key' => 'YOUR_STRIPE_SECRET_KEY',
];
```

Use Stripe test keys during development. Configure production credentials and verify the hosted checkout flow before accepting live payments.

## Create an admin account

Register an account and verify its email. Then run this query in phpMyAdmin, replacing the email with your own:

```sql
UPDATE users
SET role = 'admin'
WHERE email = 'you@example.com';
```

Sign in again and open `/admin/` under the project URL.

## Deployment notes

GitHub stores source code but does not run PHP or provide a MySQL database. Use a PHP host with MySQL/MariaDB, Composer, HTTPS, and Apache `.htaccess` support.

- Deploy the repository's `main` branch and set the project root containing `index.php` as the web root.
- Create a production database and database user, import `database/schema.sql`, then import catalog data privately.
- Update `config/Database.php` with the host's database connection values. Do not use the MySQL `root` account on a live site or commit production credentials.
- Create the private Google, mail, and Stripe config files on the server for the integrations you use.
- Configure Google OAuth with the production callback URL, enable HTTPS, and make `uploads/` writable by PHP.
- Update localhost-based callback and password-reset URLs in the application before launch.
- Stripe Checkout needs outbound access to Stripe. Review moderation needs outbound HTTPS access to OpenAI. The current Ollama commands do not connect Ollama to this application.

## Security and repository hygiene

- Never commit API keys, OAuth secrets, SMTP passwords, payment keys, `.env` files, or database exports with private customer/order data.
- `vendor/` is installed with Composer and is not stored in Git.
- Uploaded product images are local files and are not included in the repository; transfer them to the host separately when needed.
- `api/test-review-moderation.php` is restricted to localhost in this version; keep it unavailable to public visitors in production.