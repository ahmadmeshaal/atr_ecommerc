<?php
// Usage: php tools/import-perfume-ai-catalog.php [path-to-json] [--apply]
// Without --apply, validates the bundled catalog and makes no database changes.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$args = array_slice($argv, 1);
$apply = in_array('--apply', $args, true);
$inputFile = __DIR__ . '/../data/perfumes_ai_catalog.json';
foreach ($args as $argument) {
    if ($argument === '--apply' || $argument === '--dry-run') continue;
    if (str_starts_with($argument, '--')) {
        fwrite(STDERR, "Unknown option: {$argument}\n");
        exit(1);
    }
    $inputFile = $argument;
}
if (!is_file($inputFile)) {
    fwrite(STDERR, "Catalog file not found: {$inputFile}\n");
    exit(1);
}
try {
    $data = json_decode((string) file_get_contents($inputFile), true, 512, JSON_THROW_ON_ERROR);
} catch (Throwable $error) {
    fwrite(STDERR, "Could not read the AI perfume catalog: {$error->getMessage()}\n");
    exit(1);
}
if (($data['format'] ?? '') !== 'ater-perfume-ai-catalog-v1' || !is_array($data['perfumes'] ?? null)) {
    fwrite(STDERR, "Invalid AI perfume catalog JSON.\n");
    exit(1);
}
$perfumes = array_values(array_filter($data['perfumes'], static function ($perfume): bool {
    if (!is_array($perfume) || empty($perfume['source_id']) || empty($perfume['name'])) return false;
    $images = $perfume['images'] ?? [];
    return is_array($images) && count(array_filter($images, static fn($url) => is_string($url) && trim($url) !== '')) > 0;
}));
if (!$perfumes) {
    fwrite(STDERR, "The catalog contains no valid perfume records with images.\n");
    exit(1);
}
if (!$apply) {
    echo 'Dry run: ' . count($perfumes) . " image-backed perfume records are ready. No database rows were changed.\n";
    echo "Run the same command with --apply to create the catalog tables, import the data, and link matching store products.\n";
    exit(0);
}

require_once __DIR__ . '/../config/Database.php';
$pdo = (new Database())->connect();
$migrationFile = __DIR__ . '/../database/migrations/20261101_perfume_ai_catalog.sql';
$migration = (string) file_get_contents($migrationFile);
$migration = preg_replace('/^\s*--.*$/m', '', $migration) ?? $migration;
foreach (array_filter(array_map('trim', explode(';', $migration))) as $statement) {
    $pdo->exec($statement);
}

$upsert = $pdo->prepare("INSERT INTO perfume_ai_catalog (
    source_id, slug, name, house, release_year, perfumer, concentration,
    family, subfamily, notes, note_pyramid, signature_materials,
    scent_character, character_words, wears, reference_url,
    bottle_image_url, campaign_image_url, original_image_url, reference_price
) VALUES (
    :source_id, :slug, :name, :house, :release_year, :perfumer, :concentration,
    :family, :subfamily, :notes, :note_pyramid, :signature_materials,
    :scent_character, :character_words, :wears, :reference_url,
    :bottle_image_url, :campaign_image_url, :original_image_url, :reference_price
) ON DUPLICATE KEY UPDATE
    slug=VALUES(slug), name=VALUES(name), house=VALUES(house),
    release_year=VALUES(release_year), perfumer=VALUES(perfumer),
    concentration=VALUES(concentration), family=VALUES(family), subfamily=VALUES(subfamily),
    notes=VALUES(notes), note_pyramid=VALUES(note_pyramid),
    signature_materials=VALUES(signature_materials), scent_character=VALUES(scent_character),
    character_words=VALUES(character_words), wears=VALUES(wears), reference_url=VALUES(reference_url),
    bottle_image_url=VALUES(bottle_image_url), campaign_image_url=VALUES(campaign_image_url),
    original_image_url=VALUES(original_image_url), reference_price=VALUES(reference_price)");
$mapInsert = $pdo->prepare("INSERT IGNORE INTO perfume_product_map (catalog_id, product_id, match_method, is_approved)
    SELECT unique_matches.catalog_id, unique_matches.product_id, 'name_house', 1
    FROM (
        SELECT c.id AS catalog_id, MIN(p.id) AS product_id
        FROM perfume_ai_catalog c
        JOIN products p ON LOWER(TRIM(REPLACE(c.name, ':', ''))) = LOWER(TRIM(p.name))
        JOIN brands b ON b.id = p.brand_id AND LOWER(TRIM(c.house)) = LOWER(TRIM(b.name))
        WHERE p.is_active = 1
          AND (COALESCE(c.bottle_image_url, '') <> '' OR COALESCE(c.campaign_image_url, '') <> '' OR COALESCE(c.original_image_url, '') <> '')
          AND EXISTS (SELECT 1 FROM product_images pi WHERE pi.product_id = p.id AND pi.image_path <> '')
        GROUP BY c.id
        HAVING COUNT(*) = 1
    ) AS unique_matches");
$encode = static fn($value) => $value === null
    ? null
    : json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

$imported = 0;
$pdo->beginTransaction();
try {
    foreach ($perfumes as $perfume) {
        $images = $perfume['images'] ?? [];
        $upsert->execute([
            ':source_id' => (int) $perfume['source_id'],
            ':slug' => (string) ($perfume['slug'] ?? ''),
            ':name' => (string) $perfume['name'],
            ':house' => $perfume['house'] ?? null,
            ':release_year' => $perfume['year'] ?? null,
            ':perfumer' => $perfume['perfumer'] ?? null,
            ':concentration' => $perfume['concentration'] ?? null,
            ':family' => $perfume['family'] ?? null,
            ':subfamily' => $perfume['subfamily'] ?? null,
            ':notes' => $encode($perfume['notes'] ?? null),
            ':note_pyramid' => $encode($perfume['note_pyramid'] ?? null),
            ':signature_materials' => $encode($perfume['signature_materials'] ?? null),
            ':scent_character' => $perfume['scent_character'] ?? null,
            ':character_words' => $encode($perfume['character_words'] ?? null),
            ':wears' => $encode($perfume['wears'] ?? null),
            ':reference_url' => $perfume['reference_url'] ?? null,
            ':bottle_image_url' => $images['bottle'] ?? null,
            ':campaign_image_url' => $images['campaign'] ?? null,
            ':original_image_url' => $images['original'] ?? null,
            ':reference_price' => $perfume['reference_price'] ?? null,
        ]);
        $imported++;
    }
    $mapInsert->execute();
    $newLinks = $mapInsert->rowCount();
    $matched = (int) $pdo->query("SELECT COUNT(*) FROM perfume_product_map m
        JOIN perfume_ai_catalog c ON c.id = m.catalog_id
        WHERE COALESCE(c.bottle_image_url, '') <> '' OR COALESCE(c.campaign_image_url, '') <> '' OR COALESCE(c.original_image_url, '') <> ''")->fetchColumn();
    $unmatched = (int) $pdo->query("SELECT COUNT(*) FROM perfume_ai_catalog c
        WHERE (COALESCE(c.bottle_image_url, '') <> '' OR COALESCE(c.campaign_image_url, '') <> '' OR COALESCE(c.original_image_url, '') <> '')
          AND NOT EXISTS (SELECT 1 FROM perfume_product_map m WHERE m.catalog_id = c.id)")->fetchColumn();
    $pdo->commit();
    echo "Imported or refreshed {$imported} image-backed perfume records.\n";
    echo "Matched {$matched} catalog records to store products by unique name and house.\n";
    echo "Added {$newLinks} new approved product links.\n";
    echo "Left {$unmatched} catalog records unlinked because a store match was missing or ambiguous.\n";
    echo "Store prices, inventory, and product photos remain live and are read from the shop database.\n";
} catch (Throwable $error) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    fwrite(STDERR, 'Import failed; catalog data changes rolled back: ' . $error->getMessage() . "\n");
    exit(1);
}