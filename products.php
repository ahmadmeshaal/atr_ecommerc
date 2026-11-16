<?php

require_once __DIR__ . '/config/Database.php';

function catalogEscape($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function catalogLower(string $value): string
{
    return function_exists('mb_strtolower')
        ? mb_strtolower($value, 'UTF-8')
        : strtolower($value);
}

$products = [];
$categoryGroups = [];
$categoriesByGroup = [];
$brands = [];
$pageError = null;

try {
    $database = new Database();
    $pdo = $database->connect();

    $products = $pdo->query(
        "SELECT p.id, p.brand_id, b.name AS brand_name, p.name, p.slug,
                p.description, p.created_at
         FROM products p
         LEFT JOIN brands b ON b.id = p.brand_id
         WHERE p.is_active = 1
         ORDER BY p.created_at DESC, p.name ASC"
    )->fetchAll(PDO::FETCH_ASSOC);

    $categoryGroups = $pdo->query(
        "SELECT cg.id, cg.name, cg.slug
         FROM category_groups cg
         WHERE EXISTS (
             SELECT 1 FROM categories c WHERE c.group_id = cg.id
         )
         ORDER BY cg.id ASC"
    )->fetchAll(PDO::FETCH_ASSOC);

    $allCategories = $pdo->query(
        "SELECT id, group_id, name
         FROM categories
         ORDER BY name ASC"
    )->fetchAll(PDO::FETCH_ASSOC);

    foreach ($allCategories as $category) {
        $categoriesByGroup[(int) $category['group_id']][] = $category;
    }

    $brands = $pdo->query(
        "SELECT id, name FROM brands ORDER BY name ASC"
    )->fetchAll(PDO::FETCH_ASSOC);

    $productIds = array_map(static fn($product) => (int) $product['id'], $products);
    $imagesByProduct = [];
    $variantsByProduct = [];
    $productCategories = [];

    if ($productIds !== []) {
        $placeholders = implode(',', array_fill(0, count($productIds), '?'));

        $imageStmt = $pdo->prepare(
            "SELECT product_id, image_path, is_primary, sort_order
             FROM product_images
             WHERE product_id IN ({$placeholders})
             ORDER BY is_primary DESC, sort_order ASC, id ASC"
        );
        $imageStmt->execute($productIds);
        foreach ($imageStmt->fetchAll(PDO::FETCH_ASSOC) as $image) {
            $imagesByProduct[(int) $image['product_id']][] = $image;
        }

        $variantStmt = $pdo->prepare(
            "SELECT product_id, size_ml, price, discount_price, stock
             FROM product_variants
             WHERE product_id IN ({$placeholders})
             ORDER BY size_ml ASC"
        );
        $variantStmt->execute($productIds);
        foreach ($variantStmt->fetchAll(PDO::FETCH_ASSOC) as $variant) {
            $variantsByProduct[(int) $variant['product_id']][] = $variant;
        }

        $categoryStmt = $pdo->prepare(
            "SELECT pc.product_id, c.id AS category_id, c.name AS category_name,
                    cg.id AS group_id, cg.name AS group_name
             FROM product_categories pc
             JOIN categories c ON c.id = pc.category_id
             JOIN category_groups cg ON cg.id = c.group_id
             WHERE pc.product_id IN ({$placeholders})
             ORDER BY cg.id, c.name"
        );
        $categoryStmt->execute($productIds);
        foreach ($categoryStmt->fetchAll(PDO::FETCH_ASSOC) as $category) {
            $productId = (int) $category['product_id'];
            $groupId = (int) $category['group_id'];
            $productCategories[$productId][$groupId] = $category;
        }
    }
} catch (Throwable $e) {
    error_log('Product catalog page error: ' . $e->getMessage());
    $pageError = 'تعذر تحميل المنتجات حالياً. تأكد من تشغيل MySQL واستيراد البيانات إلى قاعدة perfume_ecommerce.';
}

function catalogShortDescription(?string $description): string
{
    $description = trim((string) $description);
    if ($description === '') {
        return 'اكتشف تفاصيل هذا العطر ومكوناته.';
    }

    $description = preg_split('/\R\s*Source:/i', $description)[0] ?? $description;
    $description = preg_split('/\R\R+/', $description)[0] ?? $description;
    $description = trim(strip_tags($description));

    if (function_exists('mb_strlen') && mb_strlen($description, 'UTF-8') > 145) {
        return mb_substr($description, 0, 142, 'UTF-8') . '…';
    }

    return strlen($description) > 180 ? substr($description, 0, 177) . '…' : $description;
}

?>
<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>مجموعة العطور | Perfume Store</title>
    <style>
        :root {
            color-scheme: light;
            --ink: #29251f;
            --muted: #79736c;
            --line: #e9e2d8;
            --paper: #fbf9f5;
            --white: #fff;
            --gold: #a17d4b;
            --gold-dark: #795c36;
            --serif: Georgia, 'Times New Roman', serif;
            font-family: Tahoma, Arial, sans-serif;
            color: var(--ink);
            background: var(--paper);
        }
        * { box-sizing: border-box; }
        body { margin: 0; min-width: 320px; background: var(--paper); }
        a { color: inherit; }
        .topline { height: 5px; background: var(--gold); }
        .site-header { background: var(--white); border-bottom: 1px solid var(--line); }
        .header-inner { max-width: 1260px; margin: auto; padding: 22px 28px; display: flex; align-items: center; justify-content: space-between; gap: 20px; }
        .brand-mark { text-decoration: none; font: 27px var(--serif); letter-spacing: 2px; white-space: nowrap; }
        .brand-mark span { color: var(--gold); }
        .header-link { color: var(--muted); font-size: 14px; text-decoration: none; }
        .header-link:hover { color: var(--gold-dark); }
        .hero { padding: 50px 24px 42px; text-align: center; background: radial-gradient(ellipse at 50% 0%, #eee4d3 0, #f7f3ec 47%, var(--paper) 100%); }
        .eyebrow { color: var(--gold-dark); font-size: 12px; font-weight: 700; letter-spacing: 2px; }
        h1 { margin: 12px 0 10px; font: 46px var(--serif); font-weight: 400; }
        .hero p { max-width: 650px; margin: auto; color: var(--muted); line-height: 1.9; font-size: 15px; }
        .catalog { max-width: 1260px; margin: auto; padding: 0 28px 64px; }
        .toolbar { margin: 0 0 25px; padding: 17px; display: grid; grid-template-columns: minmax(220px, 1.4fr) repeat(3, minmax(150px, 1fr)) auto; gap: 10px; align-items: center; background: var(--white); border: 1px solid var(--line); border-radius: 12px; box-shadow: 0 8px 26px #4b38200a; }
        .control { width: 100%; min-height: 44px; padding: 9px 12px; border: 1px solid var(--line); border-radius: 7px; background: #fff; color: var(--ink); font: inherit; font-size: 13px; outline: none; }
        .control:focus { border-color: var(--gold); box-shadow: 0 0 0 3px #a17d4b1a; }
        .clear-button { min-height: 42px; padding: 9px 14px; border: 0; border-radius: 7px; background: #f1ece4; color: var(--gold-dark); font: inherit; font-size: 13px; cursor: pointer; }
        .results-row { display: flex; justify-content: space-between; align-items: center; gap: 16px; margin-bottom: 16px; }
        .results-count { color: var(--muted); font-size: 13px; }
        .results-count strong { color: var(--ink); font-size: 15px; }
        .product-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 18px; }
        .product-card { min-width: 0; overflow: hidden; background: var(--white); border: 1px solid var(--line); border-radius: 11px; transition: transform .2s ease, box-shadow .2s ease; }
        .product-card:hover { transform: translateY(-4px); box-shadow: 0 14px 32px #40301c12; }
        .product-card[hidden] { display: none; }
        .image-frame { position: relative; height: 270px; overflow: hidden; display: grid; place-items: center; background: linear-gradient(145deg, #f5f1e9, #ebe4d8); }
        .product-image { width: 100%; height: 100%; object-fit: cover; display: block; }
        .image-fallback { display: none; width: 100%; height: 100%; place-items: center; color: #9b8d78; font: 42px var(--serif); }
        .image-frame.image-missing .image-fallback { display: grid; }
        .image-frame.image-missing .product-image { display: none; }
        .image-badge { position: absolute; top: 12px; right: 12px; padding: 6px 9px; border: 1px solid #fff9; border-radius: 20px; background: #ffffffdd; color: var(--gold-dark); font-size: 10px; }
        .product-body { padding: 16px 16px 17px; }
        .product-brand { margin: 0 0 6px; color: var(--gold-dark); font-size: 11px; font-weight: 700; letter-spacing: .7px; text-transform: uppercase; }
        .product-name { margin: 0; min-height: 48px; font: 22px var(--serif); font-weight: 400; line-height: 1.35; direction: ltr; text-align: right; }
        .product-name a { text-decoration: none; }
        .product-name a:hover { color: var(--gold-dark); }
        .product-description { min-height: 58px; margin: 8px 0 12px; color: var(--muted); font-size: 12px; line-height: 1.75; }
        .tag-list { min-height: 27px; display: flex; flex-wrap: wrap; gap: 5px; margin-bottom: 12px; }
        .tag { padding: 5px 8px; border-radius: 20px; background: #f4f0e9; color: #64543f; font-size: 10px; }
        .variant-heading { margin: 0 0 7px; color: var(--muted); font-size: 10px; }
        .variant-choices { display: flex; gap: 6px; overflow-x: auto; padding: 1px 1px 8px; }
        .variant-choice { flex: 0 0 auto; min-width: 82px; padding: 7px 9px; border: 1px solid var(--line); border-radius: 7px; background: #fff; color: var(--ink); cursor: pointer; text-align: center; }
        .variant-choice[aria-pressed="true"] { border-color: var(--gold); background: #fbf7ef; box-shadow: inset 0 0 0 1px var(--gold); }
        .variant-choice strong, .variant-choice span { display: block; }
        .variant-choice strong { font-size: 11px; }
        .variant-choice span { margin-top: 4px; color: var(--muted); font-size: 9px; }
        .product-meta { display: flex; justify-content: space-between; align-items: end; gap: 10px; padding-top: 12px; border-top: 1px solid #f0ece6; }
        .sizes { color: var(--muted); font-size: 11px; line-height: 1.7; }
        .selected-stock { display: block; }
        .price { color: var(--ink); font: 19px var(--serif); white-space: nowrap; }
        .price small { display: block; margin-top: 2px; color: var(--muted); font: 10px Tahoma, Arial, sans-serif; text-align: left; }
        .empty-state, .error-state { padding: 44px 20px; border: 1px dashed #d8cbb7; border-radius: 12px; background: #fff; color: var(--muted); text-align: center; line-height: 1.9; }
        .empty-state[hidden] { display: none; }
        footer { padding: 26px 20px; border-top: 1px solid var(--line); color: var(--muted); background: #fff; font-size: 12px; text-align: center; }
        dialog { width: min(560px, calc(100% - 28px)); padding: 0; border: 1px solid var(--line); border-radius: 12px; color: var(--ink); box-shadow: 0 20px 80px #0003; }
        dialog::backdrop { background: #211b15a6; }
        .dialog-inner { padding: 22px; }
        .dialog-close { float: left; width: 34px; height: 34px; border: 0; border-radius: 50%; background: #f1ece4; cursor: pointer; font-size: 20px; }
        .dialog-title { margin: 4px 0 8px; font: 28px var(--serif); }
        .dialog-copy { color: var(--muted); font-size: 14px; line-height: 1.9; white-space: pre-line; }
        .dialog-tags { display: flex; flex-wrap: wrap; gap: 7px; margin: 15px 0; }
        @media (max-width: 1050px) { .product-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); } .toolbar { grid-template-columns: repeat(3, minmax(0, 1fr)); } .toolbar .search-control { grid-column: span 2; } }
        @media (max-width: 700px) { .header-inner { padding: 17px 16px; } .catalog { padding: 0 15px 42px; } .hero { padding: 38px 18px 32px; } h1 { font-size: 36px; } .product-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 11px; } .toolbar { grid-template-columns: repeat(2, minmax(0, 1fr)); padding: 12px; gap: 8px; } .toolbar .search-control { grid-column: span 2; } .toolbar .clear-button { grid-column: span 2; } .image-frame { height: 210px; } .product-body { padding: 12px; } .product-name { font-size: 18px; min-height: 45px; } .product-description { min-height: 72px; font-size: 11px; } .price { font-size: 16px; } }
        @media (max-width: 390px) { .product-grid { grid-template-columns: 1fr; } .image-frame { height: 270px; } .product-description { min-height: auto; } }
    </style>
</head>
<body>
<div class="topline"></div>
<header class="site-header">
    <div class="header-inner">
        <a class="brand-mark" href="index.php">PERFUME <span>ÉDIT</span></a>
        <a class="header-link" href="cart.php">السلة&nbsp; ←</a>
    </div>
</header>

<section class="hero">
    <div class="eyebrow">THE FRAGRANCE COLLECTION</div>
    <h1>اكتشف عطرك القادم</h1>
    <p>تصفّح مجموعتنا، واستخدم العائلة العطرية والعائلة الفرعية لتصل إلى الرائحة التي تناسب ذوقك.</p>
</section>

<main class="catalog">
    <?php if ($pageError !== null): ?>
        <div class="error-state"><?= catalogEscape($pageError) ?></div>
    <?php else: ?>
        <section class="toolbar" aria-label="فلاتر المنتجات">
            <input class="control search-control" id="productSearch" type="search" placeholder="ابحث عن عطر أو دار عطور..." aria-label="بحث">
            <?php foreach ($categoryGroups as $group): ?>
                <select class="control category-filter" data-group-id="<?= (int) $group['id'] ?>" aria-label="<?= catalogEscape($group['name']) ?>">
                    <option value=""><?= catalogEscape($group['name']) ?></option>
                    <?php foreach ($categoriesByGroup[(int) $group['id']] ?? [] as $category): ?>
                        <option value="<?= (int) $category['id'] ?>"><?= catalogEscape($category['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            <?php endforeach; ?>
            <select class="control" id="brandFilter" aria-label="البراند">
                <option value="">كل البراندات</option>
                <?php foreach ($brands as $brand): ?>
                    <option value="<?= (int) $brand['id'] ?>"><?= catalogEscape($brand['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <select class="control" id="sortProducts" aria-label="ترتيب المنتجات">
                <option value="newest">الأحدث</option>
                <option value="name">الاسم</option>
                <option value="price-asc">السعر: الأقل أولاً</option>
                <option value="price-desc">السعر: الأعلى أولاً</option>
            </select>
            <button class="clear-button" id="clearFilters" type="button">مسح الفلاتر</button>
        </section>

        <div class="results-row">
            <div class="results-count">النتائج: <strong id="visibleCount"><?= count($products) ?></strong> عطر</div>
            <div class="results-count">تُعرض المنتجات المفعّلة في المتجر</div>
        </div>

        <?php if ($products === []): ?>
            <div class="empty-state">ما في منتجات مفعّلة لعرضها حالياً.<br>تأكد إن الاستيراد أضاف المنتجات إلى قاعدة <b>perfume_ecommerce</b> وإن `is_active = 1`.</div>
        <?php else: ?>
            <section class="product-grid" id="productGrid" aria-label="منتجات العطور">
                <?php foreach ($products as $product): ?>
                    <?php
                    $productId = (int) $product['id'];
                    $images = $imagesByProduct[$productId] ?? [];
                    $imagePath = $images[0]['image_path'] ?? '';
                    $variants = $variantsByProduct[$productId] ?? [];
                    $categories = $productCategories[$productId] ?? [];
                    $effectivePrices = [];
                    $sizes = [];
                    $inStock = false;
                    foreach ($variants as $variant) {
                        $sizes[] = (int) $variant['size_ml'];
                        if ((int) $variant['stock'] > 0) {
                            $inStock = true;
                        }
                        $variantPrice = (float) ($variant['discount_price'] ?? 0);
                        if ($variantPrice <= 0) {
                            $variantPrice = (float) ($variant['price'] ?? 0);
                        }
                        if ($variantPrice > 0) {
                            $effectivePrices[] = $variantPrice;
                        }
                    }
                    $minPrice = $effectivePrices === [] ? null : min($effectivePrices);
                    $defaultVariant = $variants[0] ?? null;
                    $defaultVariantPrice = null;
                    if ($defaultVariant !== null) {
                        $defaultVariantPrice = (float) ($defaultVariant['discount_price'] ?? 0);
                        if ($defaultVariantPrice <= 0) {
                            $defaultVariantPrice = (float) ($defaultVariant['price'] ?? 0);
                        }
                        if ($defaultVariantPrice <= 0) {
                            $defaultVariantPrice = null;
                        }
                    }
                    $description = catalogShortDescription($product['description'] ?? null);
                    $filterAttributes = '';
                    foreach ($categoryGroups as $group) {
                        $groupId = (int) $group['id'];
                        $categoryId = isset($categories[$groupId]) ? (int) $categories[$groupId]['category_id'] : '';
                        $filterAttributes .= ' data-group-' . $groupId . '="' . $categoryId . '"';
                    }
                    $categoryLabels = array_values($categories);
                    $detailCategories = implode('، ', array_map(static fn($category) => $category['group_name'] . ': ' . $category['category_name'], $categoryLabels));
                    ?>
                    <article class="product-card"
                        data-name="<?= catalogEscape(catalogLower((string) $product['name'])) ?>"
                        data-brand-name="<?= catalogEscape(catalogLower((string) ($product['brand_name'] ?? ''))) ?>"
                        data-brand-id="<?= (int) $product['brand_id'] ?>"
                        data-price="<?= $minPrice === null ? '' : catalogEscape($minPrice) ?>"
                        data-created="<?= catalogEscape($product['created_at'] ?? '') ?>"<?= $filterAttributes ?>>
                        <div class="image-frame<?= $imagePath === '' ? ' image-missing' : '' ?>">
                            <?php if ($imagePath !== ''): ?>
                                <img class="product-image" src="<?= catalogEscape($imagePath) ?>" alt="<?= catalogEscape($product['name']) ?>" loading="lazy">
                            <?php endif; ?>
                            <div class="image-fallback" aria-hidden="true">✦</div>
                            <?php if ($inStock): ?><span class="image-badge">متوفر</span><?php endif; ?>
                        </div>
                        <div class="product-body">
                            <p class="product-brand"><?= catalogEscape($product['brand_name'] ?? 'Perfume') ?></p>
                            <h2 class="product-name"><a href="product-details.php?slug=<?= rawurlencode((string) $product['slug']) ?>"><?= catalogEscape($product['name']) ?></a></h2>
                            <p class="product-description"><?= catalogEscape($description) ?></p>
                            <div class="tag-list">
                                <?php foreach (array_slice($categoryLabels, 0, 3) as $category): ?>
                                    <span class="tag"><?= catalogEscape($category['category_name']) ?></span>
                                <?php endforeach; ?>
                            </div>
                            <p class="variant-heading">اختار الحجم</p>
                            <div class="variant-choices" aria-label="أحجام المنتج">
                                <?php if ($variants === []): ?>
                                    <span class="sizes">لا توجد فاريانتات مسجلة</span>
                                <?php else: ?>
                                    <?php foreach ($variants as $variantIndex => $variant): ?>
                                        <?php
                                        $variantPrice = (float) ($variant['discount_price'] ?? 0);
                                        if ($variantPrice <= 0) {
                                            $variantPrice = (float) ($variant['price'] ?? 0);
                                        }
                                        ?>
                                        <button class="variant-choice" type="button"
                                            data-size="<?= (int) $variant['size_ml'] ?>"
                                            data-price="<?= $variantPrice > 0 ? catalogEscape($variantPrice) : '' ?>"
                                            data-stock="<?= (int) $variant['stock'] ?>"
                                            aria-pressed="<?= $variantIndex === 0 ? 'true' : 'false' ?>">
                                            <strong><?= (int) $variant['size_ml'] ?> ml</strong>
                                            <span><?= $variantPrice > 0 ? '$' . number_format($variantPrice, 2) : 'السعر غير محدد' ?></span>
                                        </button>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                            <div class="product-meta">
                                <div class="sizes">
                                    <span class="selected-size"><?= $defaultVariant !== null ? (int) $defaultVariant['size_ml'] . ' ml' : 'الحجم غير متوفر' ?></span>
                                    <span class="selected-stock"><?= $defaultVariant !== null && (int) $defaultVariant['stock'] > 0 ? 'متوفر · ' . (int) $defaultVariant['stock'] . ' قطعة' : 'غير متوفر حالياً' ?></span>
                                </div>
                                <div class="price">
                                    <span class="selected-price"><?= $defaultVariantPrice !== null ? '$' . number_format($defaultVariantPrice, 2) : 'غير محدد' ?></span>
                                    <small>سعر الحجم المختار</small>
                                </div>
                            </div>
                            <a class="clear-button" href="product-details.php?slug=<?= rawurlencode((string) $product['slug']) ?>" style="display:block; width:100%; margin-top:14px; text-align:center; text-decoration:none">تفاصيل العطر</a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </section>
            <div class="empty-state" id="noResults" hidden>ما لقينا عطور مطابقة للفلاتر. جرّب تغيّر البحث أو امسح الفلاتر.</div>
        <?php endif; ?>
    <?php endif; ?>
</main>

<footer>Perfume Édit · مجموعة العطور</footer>
<script>
    const grid = document.getElementById('productGrid');
    const cards = grid ? Array.from(grid.querySelectorAll('.product-card')) : [];
    const search = document.getElementById('productSearch');
    const brandFilter = document.getElementById('brandFilter');
    const sortSelect = document.getElementById('sortProducts');
    const groupFilters = Array.from(document.querySelectorAll('.category-filter'));
    const visibleCount = document.getElementById('visibleCount');
    const noResults = document.getElementById('noResults');

    document.querySelectorAll('.product-image').forEach(image => {
        image.addEventListener('error', () => image.closest('.image-frame')?.classList.add('image-missing'));
    });

    document.querySelectorAll('.variant-choice').forEach(choice => {
        choice.addEventListener('click', () => {
            const card = choice.closest('.product-card');
            if (!card) return;

            card.querySelectorAll('.variant-choice').forEach(option => {
                option.setAttribute('aria-pressed', option === choice ? 'true' : 'false');
            });

            const price = Number(choice.dataset.price || 0);
            card.querySelector('.selected-size').textContent = `${choice.dataset.size} ml`;
            card.querySelector('.selected-price').textContent = price > 0 ? `$${price.toFixed(2)}` : 'غير محدد';
            card.querySelector('.selected-stock').textContent = Number(choice.dataset.stock) > 0
                ? `متوفر · ${choice.dataset.stock} قطعة`
                : 'غير متوفر حالياً';
        });
    });

    function applyFilters() {
        const query = (search?.value || '').trim().toLocaleLowerCase();
        const selectedBrand = brandFilter?.value || '';
        const selectedGroups = groupFilters
            .filter(filter => filter.value !== '')
            .map(filter => ({ groupId: filter.dataset.groupId, categoryId: filter.value }));
        let visible = 0;

        cards.forEach(card => {
            const text = `${card.dataset.name || ''} ${card.dataset.brandName || ''}`.toLocaleLowerCase();
            const matchesText = query === '' || text.includes(query);
            const matchesBrand = selectedBrand === '' || card.dataset.brandId === selectedBrand;
            const matchesGroups = selectedGroups.every(filter => card.dataset[`group${filter.groupId}`] === filter.categoryId);
            const show = matchesText && matchesBrand && matchesGroups;
            card.hidden = !show;
            if (show) visible++;
        });

        if (visibleCount) visibleCount.textContent = String(visible);
        if (noResults) noResults.hidden = visible !== 0;
    }

    function sortCards() {
        if (!grid) return;
        const sort = sortSelect.value;
        const sorted = [...cards].sort((a, b) => {
            if (sort === 'name') return (a.dataset.name || '').localeCompare(b.dataset.name || '');
            if (sort === 'price-asc' || sort === 'price-desc') {
                const aPrice = Number(a.dataset.price || Number.MAX_SAFE_INTEGER);
                const bPrice = Number(b.dataset.price || Number.MAX_SAFE_INTEGER);
                return sort === 'price-asc' ? aPrice - bPrice : bPrice - aPrice;
            }
            return (b.dataset.created || '').localeCompare(a.dataset.created || '');
        });
        sorted.forEach(card => grid.appendChild(card));
    }

    [search, brandFilter, ...groupFilters].filter(Boolean).forEach(control => {
        control.addEventListener(control === search ? 'input' : 'change', applyFilters);
    });
    sortSelect?.addEventListener('change', sortCards);

    document.getElementById('clearFilters')?.addEventListener('click', () => {
        if (search) search.value = '';
        if (brandFilter) brandFilter.value = '';
        groupFilters.forEach(filter => filter.value = '');
        applyFilters();
    });

</script>
</body>
</html>
