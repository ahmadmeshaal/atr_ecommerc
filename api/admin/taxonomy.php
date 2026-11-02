<?php

require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../config/Database.php';
require_once __DIR__ . '/../../core/Response.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
    Response::json(403, ['success' => false, 'message' => 'Access denied: admins only']);
}
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    header('Allow: GET');
    Response::json(405, ['success' => false, 'message' => 'Method not allowed']);
}

$type = $_GET['type'] ?? '';
if (!is_string($type) || !in_array($type, ['brands', 'categories', 'products'], true)) {
    Response::json(422, ['success' => false, 'message' => 'Invalid catalog summary type']);
}

try {
    $pdo = (new Database())->connect();

    if ($type === 'brands') {
        $stmt = $pdo->query(
            "SELECT b.id, b.name, b.description,
                    COUNT(p.id) AS product_count,
                    COALESCE(SUM(CASE WHEN p.is_active = 1 THEN 1 ELSE 0 END), 0) AS active_count
             FROM brands b
             LEFT JOIN products p ON p.brand_id = b.id
             GROUP BY b.id, b.name, b.description
             ORDER BY b.name"
        );
        Response::json(200, ['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    }

    if ($type === 'categories') {
        $stmt = $pdo->query(
            "SELECT c.id, c.name, c.slug, c.group_id, cg.name AS group_name,
                    COUNT(DISTINCT p.id) AS product_count,
                    COUNT(DISTINCT CASE WHEN p.is_active = 1 THEN p.id END) AS active_count
             FROM categories c
             LEFT JOIN category_groups cg ON cg.id = c.group_id
             LEFT JOIN product_categories pc ON pc.category_id = c.id
             LEFT JOIN products p ON p.id = pc.product_id
             GROUP BY c.id, c.name, c.slug, c.group_id, cg.name
             ORDER BY cg.name, c.name"
        );
        Response::json(200, ['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    }

    $kind = $_GET['kind'] ?? '';
    $entityId = filter_var($_GET['entity_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if (!is_string($kind) || !in_array($kind, ['brand', 'category'], true) || $entityId === false || $entityId === null) {
        Response::json(422, ['success' => false, 'message' => 'A valid product group is required']);
    }
    if ($kind === 'brand') {
        $stmt = $pdo->prepare(
            "SELECT p.id, p.name, p.is_active, b.name AS brand_name,
                    (SELECT pi.image_path FROM product_images pi WHERE pi.product_id = p.id ORDER BY pi.is_primary DESC, pi.sort_order, pi.id LIMIT 1) AS image_path,
                    (SELECT COUNT(*) FROM product_variants v WHERE v.product_id = p.id) AS variant_count,
                    (SELECT COALESCE(SUM(v.stock), 0) FROM product_variants v WHERE v.product_id = p.id) AS stock_count,
                    (SELECT MIN(COALESCE(v.discount_price, v.price)) FROM product_variants v WHERE v.product_id = p.id) AS from_price
             FROM products p JOIN brands b ON b.id = p.brand_id
             WHERE p.brand_id = ? ORDER BY p.name"
        );
        $stmt->execute([(int) $entityId]);
    } else {
        $stmt = $pdo->prepare(
            "SELECT DISTINCT p.id, p.name, p.is_active, b.name AS brand_name,
                    (SELECT pi.image_path FROM product_images pi WHERE pi.product_id = p.id ORDER BY pi.is_primary DESC, pi.sort_order, pi.id LIMIT 1) AS image_path,
                    (SELECT COUNT(*) FROM product_variants v WHERE v.product_id = p.id) AS variant_count,
                    (SELECT COALESCE(SUM(v.stock), 0) FROM product_variants v WHERE v.product_id = p.id) AS stock_count,
                    (SELECT MIN(COALESCE(v.discount_price, v.price)) FROM product_variants v WHERE v.product_id = p.id) AS from_price
             FROM products p JOIN brands b ON b.id = p.brand_id
             JOIN product_categories pc ON pc.product_id = p.id
             WHERE pc.category_id = ? ORDER BY p.name"
        );
        $stmt->execute([(int) $entityId]);
    }
    Response::json(200, ['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
} catch (Throwable $e) {
    error_log('Admin taxonomy request failed: ' . $e->getMessage());
    Response::json(500, ['success' => false, 'message' => 'Unable to load catalog details']);
}
