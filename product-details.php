<?php

require_once __DIR__ . '/config/Database.php';

function perfumeEscape($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

$product = null;
$images = [];
$variants = [];
$categories = [];
$pageError = null;

$slug = trim((string) ($_GET['slug'] ?? ''));
$id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

try {
    $database = new Database();
    $pdo = $database->connect();

    if ($slug !== '') {
        $productStmt = $pdo->prepare(
            "SELECT p.id, p.brand_id, b.name AS brand_name, p.name, p.slug,
                    p.description, p.created_at
             FROM products p
             LEFT JOIN brands b ON b.id = p.brand_id
             WHERE p.slug = :slug AND p.is_active = 1
             LIMIT 1"
        );
        $productStmt->execute([':slug' => $slug]);
    } elseif ($id !== false && $id !== null) {
        $productStmt = $pdo->prepare(
            "SELECT p.id, p.brand_id, b.name AS brand_name, p.name, p.slug,
                    p.description, p.created_at
             FROM products p
             LEFT JOIN brands b ON b.id = p.brand_id
             WHERE p.id = :id AND p.is_active = 1
             LIMIT 1"
        );
        $productStmt->execute([':id' => $id]);
    } else {
        http_response_code(400);
        $pageError = 'رابط العطر غير صالح.';
    }

    if ($pageError === null) {
        $product = $productStmt->fetch(PDO::FETCH_ASSOC) ?: null;
        if ($product === null) {
            http_response_code(404);
            $pageError = 'العطر غير موجود أو غير متاح حالياً.';
        } else {
            $imageStmt = $pdo->prepare(
                "SELECT image_path, is_primary, sort_order
                 FROM product_images
                 WHERE product_id = :product_id
                 ORDER BY is_primary DESC, sort_order ASC, id ASC"
            );
            $imageStmt->execute([':product_id' => (int) $product['id']]);
            $images = $imageStmt->fetchAll(PDO::FETCH_ASSOC);

            $variantStmt = $pdo->prepare(
                "SELECT id, size_ml, price, discount_price, stock
                 FROM product_variants
                 WHERE product_id = :product_id
                 ORDER BY size_ml ASC"
            );
            $variantStmt->execute([':product_id' => (int) $product['id']]);
            $variants = $variantStmt->fetchAll(PDO::FETCH_ASSOC);

            $categoryStmt = $pdo->prepare(
                "SELECT cg.name AS group_name, c.name AS category_name
                 FROM product_categories pc
                 JOIN categories c ON c.id = pc.category_id
                 JOIN category_groups cg ON cg.id = c.group_id
                 WHERE pc.product_id = :product_id
                 ORDER BY cg.id, c.name"
            );
            $categoryStmt->execute([':product_id' => (int) $product['id']]);
            $categories = $categoryStmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }
} catch (Throwable $e) {
    error_log('Product details page error: ' . $e->getMessage());
    http_response_code(500);
    $pageError = 'تعذر تحميل تفاصيل العطر. تأكد من تشغيل قاعدة البيانات.';
}

$description = trim((string) ($product['description'] ?? ''));
$description = preg_split('/\R\s*Source:/i', $description)[0] ?? $description;
$description = trim($description);
$heroImage = $images[0]['image_path'] ?? '';
$heroPath = (string) (parse_url($heroImage, PHP_URL_PATH) ?? $heroImage);
$heroIsCampaign = preg_match('~/campaign/?$~i', $heroPath) === 1;

function perfumeVariantPrice(array $variant): ?float
{
    $discount = (float) ($variant['discount_price'] ?? 0);
    if ($discount > 0) {
        return $discount;
    }

    $price = (float) ($variant['price'] ?? 0);
    return $price > 0 ? $price : null;
}

?>
<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $product ? perfumeEscape($product['name']) . ' | Perfume Édit' : 'تفاصيل العطر | Perfume Édit' ?></title>
    <style>
        :root { font-family: Tahoma, Arial, sans-serif; color: #29251f; background: #fbf9f5; --gold: #a17d4b; --muted: #79736c; --line: #e9e2d8; }
        * { box-sizing: border-box; }
        body { margin: 0; }
        a { color: inherit; }
        .topline { height: 5px; background: var(--gold); }
        header { background: #fff; border-bottom: 1px solid var(--line); }
        .header-inner { max-width: 1160px; margin: auto; padding: 19px 24px; display: flex; align-items: center; justify-content: space-between; gap: 14px; }
        .logo { font: 26px Georgia, serif; letter-spacing: 1.5px; text-decoration: none; direction: ltr; }
        .logo span { color: var(--gold); }
        .back-link { color: var(--muted); text-decoration: none; font-size: 13px; }
        main { max-width: 1160px; margin: auto; padding: 34px 24px 62px; }
        .breadcrumbs { margin-bottom: 22px; color: var(--muted); font-size: 12px; }
        .breadcrumbs a { text-decoration: none; }
        .detail-layout { display: grid; grid-template-columns: minmax(0, 1.05fr) minmax(320px, .95fr); gap: 46px; align-items: start; }
        .gallery { min-width: 0; }
        .hero-frame { position: relative; height: min(66vw, 590px); min-height: 380px; overflow: hidden; display: grid; place-items: center; border: 1px solid var(--line); border-radius: 12px; background: linear-gradient(145deg, #f5f1e9, #ebe4d8); }
        .hero-frame img { width: 100%; height: 100%; object-fit: cover; }
        .hero-frame.image-missing img { display: none; }
        .fallback { display: none; color: #9b8d78; font: 64px Georgia, serif; }
        .hero-frame.image-missing .fallback { display: block; }
        .campaign-label { position: absolute; top: 14px; right: 14px; padding: 7px 10px; border-radius: 20px; background: #ffffffdf; color: #795c36; font-size: 10px; letter-spacing: .6px; }
        .thumbnails { display: flex; gap: 9px; overflow-x: auto; margin-top: 11px; }
        .thumbnail { width: 74px; height: 74px; flex: 0 0 auto; padding: 0; overflow: hidden; border: 1px solid var(--line); border-radius: 7px; background: #f1ece4; cursor: pointer; }
        .thumbnail[aria-current="true"] { border: 2px solid var(--gold); }
        .thumbnail img { width: 100%; height: 100%; object-fit: cover; }
        .product-info { padding-top: 14px; }
        .brand { margin: 0 0 8px; color: #795c36; font-size: 12px; font-weight: 700; letter-spacing: 1px; text-transform: uppercase; }
        h1 { margin: 0; font: 42px Georgia, 'Times New Roman', serif; font-weight: 400; direction: ltr; text-align: right; }
        .description { margin: 18px 0 22px; color: #68625a; font-size: 14px; line-height: 2; white-space: pre-line; }
        .category-list { display: flex; flex-wrap: wrap; gap: 7px; margin: 18px 0 25px; }
        .category-chip { padding: 7px 10px; border: 1px solid #e8dfd2; border-radius: 20px; background: #f6f1e8; color: #64543f; font-size: 11px; }
        .section-title { margin: 0 0 10px; font-size: 14px; }
        .variant-list { display: grid; gap: 9px; }
        .variant-option { display: flex; justify-content: space-between; align-items: center; gap: 14px; padding: 13px 14px; border: 1px solid var(--line); border-radius: 8px; background: #fff; cursor: pointer; }
        .variant-option[aria-pressed="true"] { border-color: var(--gold); box-shadow: 0 0 0 2px #a17d4b15; background: #fffdf9; }
        .variant-name { font-weight: 700; font-size: 13px; }
        .variant-stock { display: block; margin-top: 5px; color: var(--muted); font-size: 11px; font-weight: 400; }
        .variant-price { color: #795c36; font: 20px Georgia, serif; white-space: nowrap; }
        .unpriced { color: var(--muted); font: 12px Tahoma, Arial, sans-serif; }
        .stock-note { min-height: 22px; margin-top: 11px; color: var(--muted); font-size: 12px; }
        .empty, .error { padding: 40px 20px; border: 1px dashed #d7cbb8; border-radius: 10px; background: #fff; color: var(--muted); text-align: center; line-height: 1.9; }
        @media (max-width: 780px) { main { padding: 22px 15px 44px; } .header-inner { padding: 16px; } .detail-layout { grid-template-columns: 1fr; gap: 23px; } .hero-frame { height: 100vw; max-height: 520px; min-height: 300px; } .product-info { padding-top: 0; } h1 { font-size: 34px; } }
    </style>
</head>
<body>
<div class="topline"></div>
<header>
    <div class="header-inner">
        <a class="logo" href="index.php">PERFUME <span>ÉDIT</span></a>
        <a class="back-link" href="products.php">← العودة لمجموعة العطور</a>
    </div>
</header>
<main>
    <div class="breadcrumbs"><a href="index.php">الرئيسية</a>　/　<a href="products.php">العطور</a>　/　<?= $product ? perfumeEscape($product['name']) : 'تفاصيل العطر' ?></div>

    <?php if ($pageError !== null): ?>
        <div class="error"><?= perfumeEscape($pageError) ?></div>
    <?php elseif ($product !== null): ?>
        <div class="detail-layout">
            <section class="gallery" aria-label="صور العطر">
                <div class="hero-frame<?= $heroImage === '' ? ' image-missing' : '' ?>" id="heroFrame">
                    <?php if ($heroImage !== ''): ?>
                        <img id="heroImage" src="<?= perfumeEscape($heroImage) ?>" alt="<?= perfumeEscape($product['name']) ?>">
                    <?php else: ?>
                        <img id="heroImage" src="" alt="">
                    <?php endif; ?>
                    <span class="fallback" aria-hidden="true">✦</span>
                    <?php if ($heroImage !== ''): ?><span class="campaign-label"><?= $heroIsCampaign ? 'CAMPAIGN PHOTO' : 'PRODUCT PHOTO' ?></span><?php endif; ?>
                </div>
                <?php if (count($images) > 1): ?>
                    <div class="thumbnails" aria-label="صور إضافية">
                        <?php foreach ($images as $imageIndex => $image): ?>
                            <button class="thumbnail" type="button" data-image="<?= perfumeEscape($image['image_path']) ?>" aria-current="<?= $imageIndex === 0 ? 'true' : 'false' ?>" aria-label="عرض الصورة <?= $imageIndex + 1 ?>">
                                <img src="<?= perfumeEscape($image['image_path']) ?>" alt="" loading="lazy">
                            </button>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>

            <section class="product-info">
                <p class="brand"><?= perfumeEscape($product['brand_name'] ?? 'Perfume') ?></p>
                <h1><?= perfumeEscape($product['name']) ?></h1>
                <p class="description"><?= $description !== '' ? perfumeEscape($description) : 'لا يوجد وصف إضافي لهذا العطر حالياً.' ?></p>

                <?php if ($categories !== []): ?>
                    <div class="category-list" aria-label="تصنيفات العطر">
                        <?php foreach ($categories as $category): ?>
                            <span class="category-chip"><?= perfumeEscape($category['group_name']) ?>: <?= perfumeEscape($category['category_name']) ?></span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <h2 class="section-title">اختار الحجم</h2>
                <?php if ($variants !== []): ?>
                    <div class="variant-list" id="variantList">
                        <?php foreach ($variants as $variantIndex => $variant): ?>
                            <?php $variantPrice = perfumeVariantPrice($variant); ?>
                            <button class="variant-option" type="button" data-stock="<?= (int) $variant['stock'] ?>" aria-pressed="<?= $variantIndex === 0 ? 'true' : 'false' ?>">
                                <span>
                                    <span class="variant-name"><?= (int) $variant['size_ml'] ?> ml</span>
                                    <span class="variant-stock"><?= (int) $variant['stock'] > 0 ? 'متوفر · ' . (int) $variant['stock'] . ' قطعة' : 'غير متوفر حالياً' ?></span>
                                </span>
                                <span class="variant-price<?= $variantPrice === null ? ' unpriced' : '' ?>"><?= $variantPrice !== null ? '$' . number_format($variantPrice, 2) : 'السعر غير محدد' ?></span>
                            </button>
                        <?php endforeach; ?>
                    </div>
                    <p class="stock-note" id="stockNote"></p>
                <?php else: ?>
                    <div class="empty">ما في أحجام أو أسعار مسجلة لهذا العطر حالياً.</div>
                <?php endif; ?>
            </section>
        </div>
    <?php endif; ?>
</main>
<script>
    const heroImage = document.getElementById('heroImage');
    const heroFrame = document.getElementById('heroFrame');

    heroImage?.addEventListener('error', () => heroFrame.classList.add('image-missing'));
    document.querySelectorAll('.thumbnail').forEach(button => {
        button.addEventListener('click', () => {
            heroFrame.classList.remove('image-missing');
            heroImage.src = button.dataset.image;
            document.querySelectorAll('.thumbnail').forEach(item => item.setAttribute('aria-current', item === button ? 'true' : 'false'));
        });
    });

    const variantButtons = Array.from(document.querySelectorAll('.variant-option'));
    const stockNote = document.getElementById('stockNote');
    variantButtons.forEach(button => {
        button.addEventListener('click', () => {
            variantButtons.forEach(item => item.setAttribute('aria-pressed', item === button ? 'true' : 'false'));
            stockNote.textContent = Number(button.dataset.stock) > 0
                ? `الحجم المحدد متوفر، الكمية الحالية: ${button.dataset.stock}.`
                : 'الحجم المحدد غير متوفر حالياً.';
        });
    });
</script>
</body>
</html>
