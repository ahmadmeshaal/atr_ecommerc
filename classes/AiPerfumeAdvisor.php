<?php

/**
 * Retrieves purchasable store products using the richer perfume AI catalog.
 * This class does not call an external AI service; it searches using parsed
 * preferences supplied by the caller.
 */
class AiPerfumeAdvisor
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function getBrandVocabulary(): array
    {
        $stmt = $this->pdo->query("SELECT name FROM brands WHERE name IS NOT NULL AND name <> '' ORDER BY name");
        return array_map('strval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    public function getFamilyVocabulary(): array
    {
        $stmt = $this->pdo->query(
            "SELECT DISTINCT value FROM (
                SELECT family AS value FROM perfume_ai_catalog WHERE family IS NOT NULL AND family <> ''
                UNION
                SELECT subfamily AS value FROM perfume_ai_catalog WHERE subfamily IS NOT NULL AND subfamily <> ''
            ) AS fragrance_families ORDER BY value"
        );
        return array_map('strval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    /** @return array<int, array<string, mixed>> */
    public function recommend(array $preferences, int $limit = 6): array
    {
        $limit = max(1, min(12, $limit));
        $conditions = [
            'm.is_approved = 1',
            'p.is_active = 1',
            '(c.bottle_image_url IS NOT NULL OR c.campaign_image_url IS NOT NULL OR c.original_image_url IS NOT NULL)',
            "EXISTS (SELECT 1 FROM product_images pi WHERE pi.product_id = p.id AND pi.image_path <> '')",
            'EXISTS (SELECT 1 FROM product_variants available WHERE available.product_id = p.id AND available.stock > 0)'
        ];
        $params = [];
        $brand = $this->cleanText($preferences['brand'] ?? '', 100);
        if ($brand !== '') {
            $conditions[] = 'LOWER(b.name) = LOWER(:brand_filter)';
            $params[':brand_filter'] = $brand;
        }
        $minPrice = $this->readPrice($preferences['min_price'] ?? null);
        $maxPrice = $this->readPrice($preferences['max_price'] ?? null);
        if ($minPrice !== null && $maxPrice !== null && $minPrice > $maxPrice) {
            throw new InvalidArgumentException('Minimum price cannot be greater than maximum price.');
        }
        if ($minPrice !== null || $maxPrice !== null) {
            $priceChecks = [];
            if ($minPrice !== null) { $priceChecks[] = 'COALESCE(budget_variant.discount_price, budget_variant.price) >= :budget_min'; $params[':budget_min'] = $minPrice; }
            if ($maxPrice !== null) { $priceChecks[] = 'COALESCE(budget_variant.discount_price, budget_variant.price) <= :budget_max'; $params[':budget_max'] = $maxPrice; }
            $conditions[] = 'EXISTS (SELECT 1 FROM product_variants budget_variant WHERE budget_variant.product_id = p.id AND budget_variant.stock > 0 AND ' . implode(' AND ', $priceChecks) . ')';
        }
        $scoreParts = [];

        $season = $this->cleanText($preferences['season'] ?? '', 24);
        if ($season !== '') {
            $conditions[] = "JSON_CONTAINS(c.wears, JSON_QUOTE(:season_filter), '$.seasons')";
            $params[':season_filter'] = $season;
            $scoreParts[] = "CASE WHEN JSON_CONTAINS(c.wears, JSON_QUOTE(:season_score), '$.seasons') THEN 5 ELSE 0 END";
            $params[':season_score'] = $season;
        }

        $family = $this->cleanText($preferences['family'] ?? '', 100);
        if ($family !== '') {
            $conditions[] = '(LOWER(c.family) LIKE :family_filter OR LOWER(c.subfamily) LIKE :family_sub_filter)';
            $like = '%' . strtolower($family) . '%';
            $params[':family_filter'] = $like;
            $params[':family_sub_filter'] = $like;
            $scoreParts[] = 'CASE WHEN LOWER(c.family) = :family_exact THEN 4 WHEN LOWER(c.subfamily) LIKE :family_score THEN 2 ELSE 0 END';
            $params[':family_exact'] = strtolower($family);
            $params[':family_score'] = $like;
        }

        $notes = $preferences['notes'] ?? [];
        if (!is_array($notes)) $notes = [$notes];
        $notes = array_slice(array_values(array_unique(array_filter(array_map(
            fn($note) => $this->cleanText($note, 60),
            $notes
        )))), 0, 5);

        foreach ($notes as $index => $note) {
            $filterKey = ':note_filter_' . $index;
            $scoreKey = ':note_score_' . $index;
            $like = '%' . strtolower($note) . '%';
            $searchColumns = [
                'c.notes', 'c.note_pyramid', 'c.signature_materials',
                'c.character_words', 'c.scent_character'
            ];
            $filterChecks = [];
            $scoreChecks = [];
            foreach ($searchColumns as $columnIndex => $column) {
                $filterColumnKey = $filterKey . '_' . $columnIndex;
                $scoreColumnKey = $scoreKey . '_' . $columnIndex;
                $filterChecks[] = "LOWER(CAST($column AS CHAR)) LIKE $filterColumnKey";
                $scoreChecks[] = "LOWER(CAST($column AS CHAR)) LIKE $scoreColumnKey";
                $params[$filterColumnKey] = $like;
                $params[$scoreColumnKey] = $like;
            }
            $conditions[] = '(' . implode(' OR ', $filterChecks) . ')';
            $scoreParts[] = 'CASE WHEN ' . implode(' OR ', $scoreChecks) . ' THEN 3 ELSE 0 END';
        }

        $query = $this->cleanText($preferences['query'] ?? '', 200);
        $queryTerms = $query === '' ? [] : $this->queryTerms($query);
        foreach (array_slice($queryTerms, 0, 6) as $index => $term) {
            $filterKey = ':query_filter_' . $index;
            $scoreKey = ':query_score_' . $index;
            $like = '%' . strtolower($term) . '%';
            $searchColumns = [
                'c.name', 'c.house', 'c.family', 'c.subfamily',
                'c.notes', 'c.note_pyramid', 'c.signature_materials',
                'c.character_words', 'c.scent_character'
            ];
            $filterChecks = [];
            $scoreChecks = [];
            foreach ($searchColumns as $columnIndex => $column) {
                $filterColumnKey = $filterKey . '_' . $columnIndex;
                $scoreColumnKey = $scoreKey . '_' . $columnIndex;
                $filterChecks[] = "LOWER(CAST($column AS CHAR)) LIKE $filterColumnKey";
                $scoreChecks[] = "LOWER(CAST($column AS CHAR)) LIKE $scoreColumnKey";
                $params[$filterColumnKey] = $like;
                $params[$scoreColumnKey] = $like;
            }
            $conditions[] = '(' . implode(' OR ', $filterChecks) . ')';
            $scoreParts[] = 'CASE WHEN ' . implode(' OR ', $scoreChecks) . ' THEN 1 ELSE 0 END';
        }

        if (!$brand && !$season && !$family && !$notes && !$queryTerms && $minPrice === null && $maxPrice === null) {
            throw new InvalidArgumentException('Provide a season, family, note, or search query.');
        }

        $scoreSql = $scoreParts ? '(' . implode(' + ', $scoreParts) . ')' : '0';
        $sql = "SELECT
                    c.id AS catalog_id,
                    c.source_id,
                    c.name AS perfume_name,
                    c.house,
                    c.family,
                    c.subfamily,
                    c.notes,
                    c.note_pyramid,
                    c.signature_materials,
                    c.scent_character,
                    c.character_words,
                    c.wears,
                    p.id AS product_id,
                    p.name AS product_name,
                    p.slug AS product_slug,
                    b.name AS brand_name,
                    (SELECT pi.image_path FROM product_images pi
                     WHERE pi.product_id = p.id
                     ORDER BY pi.is_primary DESC, pi.sort_order ASC, pi.id ASC LIMIT 1) AS image_path,
                    (SELECT MIN(COALESCE(v.discount_price, v.price)) FROM product_variants v
                     WHERE v.product_id = p.id AND v.stock > 0) AS starting_price,
                    $scoreSql AS relevance_score
                FROM perfume_ai_catalog c
                JOIN perfume_product_map m ON m.catalog_id = c.id
                JOIN products p ON p.id = m.product_id
                JOIN brands b ON b.id = p.brand_id
                WHERE " . implode(' AND ', $conditions) . "
                ORDER BY relevance_score DESC, p.name ASC
                LIMIT " . $limit;

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (!$products) return [];

        $productIds = array_map(static fn($product) => (int) $product['product_id'], $products);
        $placeholders = implode(',', array_fill(0, count($productIds), '?'));
        $variantStmt = $this->pdo->prepare(
            "SELECT id, product_id, size_ml, price, discount_price, stock
             FROM product_variants
             WHERE product_id IN ($placeholders) AND stock > 0
             ORDER BY product_id, size_ml"
        );
        $variantStmt->execute($productIds);
        $variantsByProduct = [];
        foreach ($variantStmt->fetchAll(PDO::FETCH_ASSOC) as $variant) {
            $currentPrice = $variant['discount_price'] === null ? (float)$variant['price'] : (float)$variant['discount_price'];
            if (($minPrice !== null && $currentPrice < $minPrice) || ($maxPrice !== null && $currentPrice > $maxPrice)) continue;
            $variantsByProduct[(int) $variant['product_id']][] = [
                'id' => (int) $variant['id'],
                'size_ml' => (int) $variant['size_ml'],
                'price' => (float) $variant['price'],
                'discount_price' => $variant['discount_price'] === null ? null : (float) $variant['discount_price'],
                'stock' => (int) $variant['stock']
            ];
        }

        $filteredProducts = [];
        foreach ($products as $product) {
            if (!empty($variantsByProduct[(int)$product['product_id']])) $filteredProducts[] = $product;
        }
        return array_map(function (array $product) use ($variantsByProduct): array {
            $productId = (int) $product['product_id'];
            $matchingVariants = $variantsByProduct[$productId] ?? [];
            $prices = array_map(static fn($variant) => $variant['discount_price'] ?? $variant['price'], $matchingVariants);
            return [
                'product_id' => $productId,
                'name' => $product['product_name'],
                'brand' => $product['brand_name'],
                'slug' => $product['product_slug'],
                'product_url' => 'product.php?id=' . $productId,
                'image' => $product['image_path'],
                'starting_price' => $prices ? (float)min($prices) : (float)$product['starting_price'],
                'currency' => 'JOD',
                'variants' => $variantsByProduct[$productId] ?? [],
                'match' => [
                    'family' => $product['family'],
                    'subfamily' => $product['subfamily'],
                    'scent_character' => $product['scent_character'],
                    'notes' => $this->decodeJson($product['notes']),
                    'note_pyramid' => $this->decodeJson($product['note_pyramid']),
                    'signature_materials' => $this->decodeJson($product['signature_materials']),
                    'character_words' => $this->decodeJson($product['character_words']),
                    'wears' => $this->decodeJson($product['wears'])
                ],
                'relevance_score' => (int) $product['relevance_score']
            ];
        }, $filteredProducts);
    }

    private function readPrice($value): ?float
    {
        if ($value === null || $value === '') return null;
        if (!is_numeric($value)) throw new InvalidArgumentException('Prices must be numeric amounts in JOD.');
        $price = (float)$value;
        if ($price < 0 || $price > 100000) throw new InvalidArgumentException('Price must be between 0 and 100000 JOD.');
        return $price;
    }
    private function cleanText($value, int $maxLength): string
    {
        if (!is_string($value)) return '';
        $limited = function_exists('mb_substr')
            ? mb_substr($value, 0, $maxLength, 'UTF-8')
            : substr($value, 0, $maxLength);
        return trim($limited);
    }

    private function queryTerms(string $query): array
    {
        $query = strtolower($query);
        $query = preg_replace('/[^\p{L}\p{N}\s]+/u', ' ', $query) ?? '';
        $stopWords = ['the', 'and', 'for', 'with', 'perfume', 'perfumes', 'fragrance', 'scent', 'please', 'عطر', 'عطور', 'بدي', 'اريد', 'أريد', 'لل', 'بال', 'في', 'من', 'مع', 'عن', 'مناسب', 'مناسبة'];
        $terms = preg_split('/\s+/u', $query, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        return array_values(array_unique(array_filter($terms, static fn($term) => strlen($term) > 2 && !in_array($term, $stopWords, true))));
    }

    private function decodeJson($value)
    {
        if ($value === null || $value === '') return null;
        $decoded = json_decode((string) $value, true);
        return json_last_error() === JSON_ERROR_NONE ? $decoded : $value;
    }
}

