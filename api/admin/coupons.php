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

try {
    $pdo = (new Database())->connect();
    $stmt = $pdo->query(
        "SELECT c.id, c.code, c.discount_type, c.discount_value,
                c.min_order_amount, c.usage_limit, c.starts_at, c.expires_at,
                c.is_active, c.created_at,
                COUNT(CASE WHEN o.status <> 'cancelled' THEN o.id END) AS used_count,
                CASE WHEN c.usage_limit IS NULL THEN NULL
                     ELSE GREATEST(c.usage_limit - COUNT(CASE WHEN o.status <> 'cancelled' THEN o.id END), 0)
                END AS remaining_uses,
                CASE
                    WHEN c.is_active = 0 THEN 'inactive'
                    WHEN c.starts_at IS NOT NULL AND c.starts_at > NOW() THEN 'scheduled'
                    WHEN c.expires_at IS NOT NULL AND c.expires_at < NOW() THEN 'expired'
                    WHEN c.usage_limit IS NOT NULL AND COUNT(CASE WHEN o.status <> 'cancelled' THEN o.id END) >= c.usage_limit THEN 'exhausted'
                    ELSE 'active'
                END AS availability
         FROM coupons c
         LEFT JOIN orders o ON o.coupon_id = c.id
         GROUP BY c.id, c.code, c.discount_type, c.discount_value,
                  c.min_order_amount, c.usage_limit, c.starts_at, c.expires_at,
                  c.is_active, c.created_at
         ORDER BY c.created_at DESC, c.id DESC"
    );
    Response::json(200, ['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
} catch (Throwable $e) {
    error_log('Admin coupons request failed: ' . $e->getMessage());
    Response::json(500, ['success' => false, 'message' => 'Unable to load coupons']);
}