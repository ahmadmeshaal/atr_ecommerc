# Ater Perfume Store — Local Setup

This guide prepares the project for local development on Windows/XAMPP. It covers the storefront, database, email verification, and the local Ollama AI advisor. Stripe checkout and Google sign-in setup are intentionally outside this guide.

## Requirements

- Windows with XAMPP (Apache, PHP 8.2+, and MariaDB 10.4+).
- PHP extensions enabled in XAMPP: pdo_mysql, curl, mbstring, fileinfo, and openssl.
- Composer 2.
- Ollama for the local AI chat (optional).
- A database data dump containing the store's products, brands, categories, product images, and variants. database/schema.sql creates the tables but contains no catalog data.

## 1. Put the project under XAMPP

Extract the project to:

    C:/xampp/htdocs/perfume_ecommerce

Start Apache and MySQL from the XAMPP Control Panel. The local site URL is:

    http://localhost/perfume_ecommerce/

If you use a different folder name, update project URLs/configuration that assume perfume_ecommerce.

## 2. Install PHP dependencies

Open PowerShell or Command Prompt in the project folder and run:

    composer install

This installs the libraries listed in composer.lock. It does not configure third-party integrations.

## 3. Create and populate the database

1. Open phpMyAdmin at http://localhost/phpmyadmin.
2. Create a database named perfume_ecommerce using utf8mb4.
3. Select that database and import database/schema.sql.
4. Import the separate store data dump to populate products, houses, categories, images, variants, and other records. The schema file is structure only.
5. If using the AI advisor, also import the perfume enrichment and product mapping SQL described in the AI section below.

The project currently uses these default XAMPP credentials:

    Host: localhost
    Database: perfume_ecommerce
    Username: root
    Password: (empty)

If your local MySQL credentials differ, update config/Database.php on your machine. Do not put personal database credentials in a shared archive.

For a fresh database, do not run database/migration_review_ai_moderation.sql after importing the full database/schema.sql; the full schema already defines review moderation columns. That migration is only for an older database that lacks those columns.

## 4. Configure outgoing email (needed for verification and reset emails)

Email verification codes, password reset links, and order notifications use SMTP. Create a local, unshared file at config/mail.php:

    <?php
    return [
        'host' => 'smtp.example.com',
        'port' => 465,
        'username' => 'your-smtp-account@example.com',
        'password' => 'your-smtp-app-password',
    ];

Use SMTP credentials for your own test mailbox. The code expects implicit TLS on port 465. Without valid SMTP settings, account verification and password reset emails will not be delivered.

## 5. Configure the local AI advisor (optional)

The project uses Ollama locally; no paid AI API key is needed.

1. Install Ollama from https://ollama.com/download.
2. Open a terminal and download the configured model:

       ollama pull qwen3:8b

3. The project uses config/ai.example.php automatically when config/ai.php is absent, so the bundled default already points to http://127.0.0.1:11434 and qwen3:8b. Copy it to config/ai.php only if you want a local override.
4. Keep Ollama running while using the AI chat. On Windows, the Ollama app normally runs the local service; if needed, start it with:

       ollama serve

5. Confirm the model is installed:

       ollama list

### AI catalog data required

Ollama handles the conversation, while product matching and price/stock filtering use the database. The AI advisor queries two additional tables:

- perfume_ai_catalog — enriched perfume notes, families, seasons, and other scent information.
- perfume_product_map — links those perfume records to products in this store.

The bundled file `data/perfumes_ai_catalog.json` contains 661 perfume references with at least one image. The importer creates the two required tables, loads those records, and links safe unique name-and-house matches to active store products that have images. Unmatched or ambiguous records stay unlinked. At recommendation time, product images, active status, prices, and stock are read live from this store database.

From the project folder, validate the data first (this does not change the database):

       php tools/import-perfume-ai-catalog.php

Then import it once for each store database:

       php tools/import-perfume-ai-catalog.php --apply

The importer is safe to re-run: it refreshes catalog records and skips existing product links. It does not modify store products, prices, stock, orders, or customers. The receiving computer needs PHP `pdo_mysql` enabled and its store products imported before running the command.

The PHP cURL extension must be enabled for the app to call Ollama.

## 6. Open the website and test

Visit:

    http://localhost/perfume_ecommerce/

The storefront, product browsing, cart, and wishlist use the local database. Email verification requires the SMTP setup above. AI chat requires Ollama plus the perfume catalog/mapping data.

To grant admin access to an account you created, first register and verify it, then run this in phpMyAdmin using that account's email:

    UPDATE users
    SET role = 'admin'
    WHERE email = 'your-account@example.com';

Sign in again and open:

    http://localhost/perfume_ecommerce/admin/

## Before sharing the ZIP

Do not include local credential/config files such as:

- config/mail.php
- config/ai.php
- config/google.php
- config/stripe.php or config/strip.php

The repository ignores some of these files, but a ZIP utility may still include them. Share only example configuration and database schema files. Create separate local credentials on each machine.