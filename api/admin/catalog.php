<?php
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../config/Database.php';
require_once __DIR__ . '/../../classes/Product.php';
require_once __DIR__ . '/../../core/Response.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
    Response::json(403, ['success' => false, 'message' => 'Access denied: admins only']);
}
if (!in_array($_SERVER['REQUEST_METHOD'], ['GET', 'PUT'], true)) {
    header('Allow: GET, PUT');
    Response::json(405, ['success' => false, 'message' => 'Method not allowed']);
}

$search = trim((string) ($_GET['search'] ?? ''));
$brandId = filter_var($_GET['brand_id'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$brandIdsRaw = $_GET['brand_ids'] ?? '';
$brandIds = [];
if (!is_string($brandIdsRaw)) {
    Response::json(422, ['success' => false, 'message' => 'Invalid fragrance house filter']);
}
if (trim($brandIdsRaw) !== '') {
    foreach (explode(',', $brandIdsRaw) as $rawId) {
        $validId = filter_var(trim($rawId), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($validId === false) {
            Response::json(422, ['success' => false, 'message' => 'Invalid fragrance house filter']);
        }
        $brandIds[] = (int) $validId;
    }
    $brandIds = array_values(array_unique($brandIds));
}
$status = trim((string) ($_GET['status'] ?? ''));
$stock = trim((string) ($_GET['stock'] ?? ''));
if ($brandId === false && ($_GET['brand_id'] ?? '') !== '') {
    Response::json(422, ['success' => false, 'message' => 'Invalid brand filter']);
}
if (!in_array($status, ['', 'active', 'inactive'], true)) {
    Response::json(422, ['success' => false, 'message' => 'Invalid product status']);
}
if (!in_array($stock, ['', 'low'], true)) {
    Response::json(422, ['success' => false, 'message' => 'Invalid stock filter']);
}
$page = max(1, (int) ($_GET['page'] ?? 1));
$limit = 16;

try {
    $pdo = (new Database())->connect();
    if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
        $id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($id === false || $id === null) {
            Response::json(400, ['success' => false, 'message' => 'Valid product ID is required']);
        }
        try {
            $body = json_decode(file_get_contents('php://input'), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            Response::json(400, ['success' => false, 'message' => 'Invalid JSON body']);
        }
        if (!is_array($body) || !in_array($body['is_active'] ?? null, [true, false, 0, 1, '0', '1'], true)) {
            Response::json(422, ['success' => false, 'message' => 'is_active must be true or false']);
        }
        $isActive = in_array($body['is_active'], [true, 1, '1'], true);
        $changed = (new Product($pdo))->setActiveStatus((int) $id, $isActive);
        if (!$changed) {
            Response::json(404, ['success' => false, 'message' => 'Product not found']);
        }
        Response::json(200, [
            'success' => true,
            'message' => $isActive ? 'Draft restored to the storefront.' : 'Product archived as a draft.',
            'data' => ['id' => (int) $id, 'is_active' => $isActive ? 1 : 0]
        ]);
    }
    $where = [];
    $params = [];
    if ($search !== '') {
        $where[] = '(p.name LIKE ? OR b.name LIKE ? OR p.slug LIKE ?)';
        array_push($params, '%' . $search . '%', '%' . $search . '%', '%' . $search . '%');
    }
    if ($brandIds) {
        $where[] = 'p.brand_id IN (' . implode(',', array_fill(0, count($brandIds), '?')) . ')';
        array_push($params, ...$brandIds);
    } elseif ($brandId !== false && $brandId !== null && $brandId !== '') {
        $where[] = 'p.brand_id = ?';
        $params[] = (int) $brandId;
    }
    if ($status !== '') {
        $where[] = 'p.is_active = ?';
        $params[] = $status === 'active' ? 1 : 0;
    }
    if ($stock === 'low') {
        $where[] = 'EXISTS (SELECT 1 FROM product_variants low_v WHERE low_v.product_id = p.id AND low_v.stock <= 5)';
    }
    $whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM products p JOIN brands b ON b.id = p.brand_id$whereSql");
    $countStmt->execute($params);
    $total = (int) $countStmt->fetchColumn();
    $pages = max(1, (int) ceil($total / $limit));
    $page = min($page, $pages);
    $offset = ($page - 1) * $limit;
    $sql = "SELECT p.id, p.brand_id, p.name, p.slug, p.description, p.is_active,
                   b.name AS brand_name,
                   (SELECT pi.image_path FROM product_images pi WHERE pi.product_id = p.id ORDER BY pi.is_primary DESC, pi.sort_order, pi.id LIMIT 1) AS image_path,
                   (SELECT COUNT(*) FROM product_variants v WHERE v.product_id = p.id) AS variant_count,
                   (SELECT COALESCE(SUM(v.stock), 0) FROM product_variants v WHERE v.product_id = p.id) AS stock_count,
                   (SELECT MIN(COALESCE(v.discount_price, v.price)) FROM product_variants v WHERE v.product_id = p.id) AS from_price
            FROM products p JOIN brands b ON b.id = p.brand_id$whereSql
            ORDER BY p.updated_at DESC, p.id DESC LIMIT $limit OFFSET $offset";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    Response::json(200, [
        'success' => true,
        'data' => $stmt->fetchAll(PDO::FETCH_ASSOC),
        'pagination' => ['total' => $total, 'pages' => $pages, 'page' => $page, 'per_page' => $limit]
    ]);
} catch (Throwable $e) {
    error_log('Admin catalog failed: ' . $e->getMessage());
    Response::json(500, ['success' => false, 'message' => 'Unable to load products']);
}
