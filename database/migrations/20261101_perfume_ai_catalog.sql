-- Creates the AI reference catalog and product-link tables. The bundled importer applies this with CREATE TABLE IF NOT EXISTS.
-- Store prices, inventory, and images remain authoritative in the storefront database.

CREATE TABLE IF NOT EXISTS perfume_ai_catalog (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    source_id INT UNSIGNED NOT NULL,
    slug VARCHAR(255) NOT NULL,
    name VARCHAR(255) NOT NULL,
    house VARCHAR(255) DEFAULT NULL,
    release_year SMALLINT UNSIGNED DEFAULT NULL,
    perfumer VARCHAR(255) DEFAULT NULL,
    concentration VARCHAR(100) DEFAULT NULL,
    family VARCHAR(100) DEFAULT NULL,
    subfamily VARCHAR(100) DEFAULT NULL,
    notes JSON DEFAULT NULL,
    note_pyramid JSON DEFAULT NULL,
    signature_materials JSON DEFAULT NULL,
    scent_character TEXT DEFAULT NULL,
    character_words JSON DEFAULT NULL,
    wears JSON DEFAULT NULL,
    reference_url TEXT DEFAULT NULL,
    bottle_image_url TEXT DEFAULT NULL,
    campaign_image_url TEXT DEFAULT NULL,
    original_image_url TEXT DEFAULT NULL,
    reference_price DECIMAL(10,2) DEFAULT NULL COMMENT 'Source price reference only, not used for store checkout.',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_perfume_ai_catalog_source_id (source_id),
    KEY idx_perfume_ai_catalog_name_house (name, house),
    KEY idx_perfume_ai_catalog_family (family),
    KEY idx_perfume_ai_catalog_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS perfume_product_map (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    catalog_id INT UNSIGNED NOT NULL,
    product_id INT UNSIGNED NOT NULL,
    match_method ENUM('name_house', 'image_review', 'manual') NOT NULL,
    is_approved TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_perfume_product_map_catalog (catalog_id),
    UNIQUE KEY uq_perfume_product_map_product (product_id),
    CONSTRAINT fk_perfume_product_map_catalog
        FOREIGN KEY (catalog_id) REFERENCES perfume_ai_catalog (id) ON DELETE CASCADE,
    CONSTRAINT fk_perfume_product_map_product
        FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
