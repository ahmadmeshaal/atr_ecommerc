<?php
/**
 * Import Armaf perfumes from testttt.perfumes into perfume_ecommerce.
 *
 * Run from the project folder:
 *   php import_armaf.php
 *
 * Update the database credentials below if your local MySQL setup is different.
 */

declare(strict_types=1);

$host = '127.0.0.1';
$username = 'root';
$password = ''; // Set your MySQL password here if you have one.

$sourceDb = 'testttt';
$targetDb = 'perfume_ecommerce';
$imagesOnly = in_array('--images-only', $argv ?? [], true);

$sizeOptions = [100, 200, 350];
$initialPrice = '0.00';
$initialStock = 25;

$dsn = "mysql:host={$host};charset=utf8mb4";

try {
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    // Read source records and write to the target database using the same connection.
    $sourceTable = "`{$sourceDb}`.`perfumes`";
    $target = "`{$targetDb}`.";

    $sourceQuery = $pdo->query(
        "SELECT id, slug, name, house, perfumer, concentration, family,
                subfamily, scent_character, perfume_url, image_url,
                campaign_image_url, public_image_url
         FROM {$sourceTable}
         WHERE house = 'Armaf'
         ORDER BY id"
    );
    $perfumes = $sourceQuery->fetchAll();

    if (!$perfumes) {
        exit("No Armaf perfumes found in {$sourceDb}.perfumes.\n");
    }

    $pdo->beginTransaction();

    if (!$imagesOnly) {
        // Ensure the Armaf brand exists.
        $brandName = 'Armaf';
        $stmt = $pdo->prepare("SELECT id FROM {$target} `brands` WHERE name = ?");
        $stmt->execute([$brandName]);
        $brandId = $stmt->fetchColumn();

        if (!$brandId) {
            $stmt = $pdo->prepare("INSERT INTO {$target} `brands` (name) VALUES (?)");
            $stmt->execute([$brandName]);
            $brandId = (int) $pdo->lastInsertId();
        } else {
            $brandId = (int) $brandId;
        }
    }

    // Create/find a category group.
    $getOrCreateGroup = function (string $name) use ($pdo, $target): int {
        $slug = makeSlug($name);

        $stmt = $pdo->prepare("SELECT id FROM {$target} `category_groups` WHERE name = ?");
        $stmt->execute([$name]);
        $id = $stmt->fetchColumn();

        if ($id) {
            return (int) $id;
        }

        $stmt = $pdo->prepare(
            "INSERT INTO {$target} `category_groups` (name, slug) VALUES (?, ?)"
        );
        $stmt->execute([$name, $slug]);
        return (int) $pdo->lastInsertId();
    };

    // Create/find a category inside a group.
    $getOrCreateCategory = function (int $groupId, string $name) use ($pdo, $target): int {
        $slug = makeSlug($name);

        $stmt = $pdo->prepare(
            "SELECT id FROM {$target} `categories` WHERE group_id = ? AND slug = ?"
        );
        $stmt->execute([$groupId, $slug]);
        $id = $stmt->fetchColumn();

        if ($id) {
            return (int) $id;
        }

        $stmt = $pdo->prepare(
            "INSERT INTO {$target} `categories` (group_id, name, slug) VALUES (?, ?, ?)"
        );
        $stmt->execute([$groupId, $name, $slug]);
        return (int) $pdo->lastInsertId();
    };

    if (!$imagesOnly) {
        $familyGroupId = $getOrCreateGroup('Fragrance Families');
        $subfamilyGroupId = $getOrCreateGroup('Scent Subfamilies');
    }

    $findProduct = $pdo->prepare(
        "SELECT id FROM {$target} `products` WHERE slug = ? LIMIT 1"
    );
    $insertProduct = $pdo->prepare(
        "INSERT INTO {$target} `products`
            (brand_id, name, slug, description, is_active)
         VALUES (?, ?, ?, ?, 1)"
    );

    $linkCategory = $pdo->prepare(
        "INSERT IGNORE INTO {$target} `product_categories` (product_id, category_id)
         VALUES (?, ?)"
    );

    $findImage = $pdo->prepare(
        "SELECT id FROM {$target} `product_images`
         WHERE product_id = ? AND image_path = ? LIMIT 1"
    );
    $insertImage = $pdo->prepare(
        "INSERT INTO {$target} `product_images`
            (product_id, image_path, is_primary, sort_order)
         VALUES (?, ?, ?, ?)"
    );
    $updateImage = $pdo->prepare(
        "UPDATE {$target} `product_images`
         SET is_primary = ?, sort_order = ?
         WHERE id = ?"
    );

    $findVariant = $pdo->prepare(
        "SELECT id FROM {$target} `product_variants`
         WHERE product_id = ? AND size_ml = ? LIMIT 1"
    );
    $insertVariant = $pdo->prepare(
        "INSERT INTO {$target} `product_variants`
            (product_id, size_ml, price, stock)
         VALUES (?, ?, ?, ?)"
    );

    $createdProducts = 0;
    $existingProducts = 0;
    $createdVariants = 0;
    $createdImages = 0;

    foreach ($perfumes as $perfume) {
        $name = trim((string) ($perfume['name'] ?? ''));
        if ($name === '') {
            continue;
        }

        $slug = trim((string) ($perfume['slug'] ?? ''));
        if ($slug === '') {
            $slug = makeSlug($name);
        }

        $findProduct->execute([$slug]);
        $productId = $findProduct->fetchColumn();

        // Image-only mode updates only products that have already been imported.
        if (!$productId && $imagesOnly) {
            continue;
        }

        if (!$productId) {
            $descriptionParts = [];

            if (!empty($perfume['scent_character'])) {
                $descriptionParts[] = trim((string) $perfume['scent_character']);
            }
            if (!empty($perfume['concentration'])) {
                $descriptionParts[] = 'Concentration: ' . trim((string) $perfume['concentration']);
            }
            if (!empty($perfume['perfumer'])) {
                $descriptionParts[] = 'Perfumer: ' . trim((string) $perfume['perfumer']);
            }
            if (!empty($perfume['perfume_url'])) {
                $descriptionParts[] = 'Source: ' . trim((string) $perfume['perfume_url']);
            }

            $description = implode("\n\n", $descriptionParts);

            try {
                $insertProduct->execute([$brandId, $name, $slug, $description]);
            } catch (PDOException $e) {
                // If another product already uses this slug, append the source row ID.
                if ((string) $e->getCode() !== '23000') {
                    throw $e;
                }

                $slug = $slug . '-' . (int) $perfume['id'];
                $insertProduct->execute([$brandId, $name, $slug, $description]);
            }

            $productId = (int) $pdo->lastInsertId();
            $createdProducts++;
        } else {
            $productId = (int) $productId;
            $existingProducts++;
        }

        if (!$imagesOnly) {
            // Link the fragrance family category, if available.
            $family = trim((string) ($perfume['family'] ?? ''));
            if ($family !== '') {
                $categoryId = $getOrCreateCategory($familyGroupId, $family);
                $linkCategory->execute([$productId, $categoryId]);
            }

            // Link the scent subfamily category, if available.
            $subfamily = trim((string) ($perfume['subfamily'] ?? ''));
            if ($subfamily !== '') {
                $categoryId = $getOrCreateCategory($subfamilyGroupId, $subfamily);
                $linkCategory->execute([$productId, $categoryId]);
            }
        }

        // Keep the campaign image as the product detail hero and the public
        // image as the additional product/gallery image.
        $campaignImage = trim((string) ($perfume['campaign_image_url'] ?? ''));
        $publicImage = trim((string) ($perfume['public_image_url'] ?? ''));
        $fallbackImage = trim((string) ($perfume['image_url'] ?? ''));
        $imageEntries = [];

        if ($campaignImage !== '') {
            $imageEntries[$campaignImage] = [1, 0];
        }

        if ($publicImage !== '') {
            $imageEntries[$publicImage] = $campaignImage !== '' ? [0, 1] : [1, 0];
        }

        if ($imageEntries === [] && $fallbackImage !== '') {
            $imageEntries[$fallbackImage] = [1, 0];
        }

        foreach ($imageEntries as $imagePath => [$isPrimary, $sortOrder]) {
            $findImage->execute([$productId, $imagePath]);
            $imageId = $findImage->fetchColumn();

            if ($imageId) {
                // Re-running the import updates the order of these source images
                // without creating duplicate rows.
                $updateImage->execute([$isPrimary, $sortOrder, (int) $imageId]);
            } else {
                $insertImage->execute([$productId, $imagePath, $isPrimary, $sortOrder]);
                $createdImages++;
            }
        }

        if (!$imagesOnly) {
            // Add the three requested sizes. Existing variants are left unchanged.
            foreach ($sizeOptions as $size) {
                $findVariant->execute([$productId, $size]);
                if (!$findVariant->fetchColumn()) {
                    $insertVariant->execute([
                        $productId,
                        $size,
                        $initialPrice,
                        $initialStock
                    ]);
                    $createdVariants++;
                }
            }
        }
    }

    $pdo->commit();

    echo "Import completed successfully.\n";
    if ($imagesOnly) {
        echo "Image-only mode: product, category, and variant records were not changed.\n";
    }
    echo "Armaf source rows: " . count($perfumes) . "\n";
    echo "New products: {$createdProducts}\n";
    echo "Products already present: {$existingProducts}\n";
    echo "New variants: {$createdVariants}\n";
    echo "New product images: {$createdImages}\n";
    echo "Sizes: " . implode(', ', $sizeOptions) . " ml\n";
    echo "Price per size: {$initialPrice}\n";
    echo "Initial stock per size: {$initialStock}\n";

} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    fwrite(STDERR, "Import failed: " . $e->getMessage() . "\n");
    exit(1);
}

/**
 * Convert a category/product label into a URL-friendly slug.
 */
function makeSlug(string $value): string
{
    $value = trim($value);

    if (function_exists('iconv')) {
        $converted = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
        if ($converted !== false) {
            $value = $converted;
        }
    }

    $value = strtolower($value);
    $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?? '';
    $value = trim($value, '-');

    return $value !== '' ? $value : 'item';
}
