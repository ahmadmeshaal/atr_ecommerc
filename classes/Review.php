<?php
class Review {
    private $pdo;

    public function __construct($db_connection) { $this->pdo = $db_connection; }

    public function verifyOrderOwnership(int $userId, int $orderId, int $productId): bool {
        $sql = "SELECT 1 FROM orders o JOIN order_items oi ON o.id = oi.order_id JOIN product_variants pv ON oi.variant_id = pv.id WHERE o.id = :order_id AND o.user_id = :user_id AND pv.product_id = :product_id AND o.status = 'delivered' LIMIT 1";
        $stmt = $this->pdo->run_query($sql, ['order_id'=>$orderId, 'user_id'=>$userId, 'product_id'=>$productId]);
        return (bool)$stmt->fetch();
    }

    public function create(array $data): int {
        $sql = "INSERT INTO reviews (user_id, product_id, order_id, rating, comment, is_active, moderation_status, moderation_reason) VALUES (:user_id, :product_id, :order_id, :rating, :comment, 0, 'pending', 'AI review check in progress')";
        $this->pdo->run_query($sql, ['user_id'=>$data['user_id'], 'product_id'=>$data['product_id'], 'order_id'=>$data['order_id'], 'rating'=>$data['rating'], 'comment'=>$data['comment'] ?? null]);
        return (int)$this->pdo->connect()->lastInsertId();
    }

    public function setModeration(int $reviewId, string $status, ?string $reason = null): void {
        if (!in_array($status, ['approved','pending','flagged','rejected'], true)) throw new InvalidArgumentException('Invalid review moderation status.');
        $active = $status === 'approved' ? 1 : 0;
        $sql = 'UPDATE reviews SET moderation_status = :status, moderation_reason = :reason, moderated_at = CURRENT_TIMESTAMP, is_active = :active WHERE id = :id';
        $this->pdo->run_query($sql, ['status'=>$status, 'reason'=>$reason, 'active'=>$active, 'id'=>$reviewId]);
    }

    public function getByProductId(int $productId): array {
        $sql = "SELECT r.id, r.rating, r.comment, r.created_at, u.name as user_name FROM reviews r JOIN users u ON r.user_id = u.id WHERE r.product_id = :product_id AND r.is_active = 1 AND r.moderation_status = 'approved' ORDER BY r.created_at DESC";
        return $this->pdo->run_query($sql, ['product_id'=>$productId])->fetchAll();
    }

    public function getAll(?int $userId = null, ?int $productId = null): array {
        $sql = 'SELECT r.*, u.name as user_name, p.name as product_name FROM reviews r JOIN users u ON r.user_id = u.id JOIN products p ON r.product_id = p.id WHERE 1=1';
        $params = [];
        if ($userId !== null) { $sql .= ' AND r.user_id = :user_id'; $params['user_id'] = $userId; }
        if ($productId !== null) { $sql .= ' AND r.product_id = :product_id'; $params['product_id'] = $productId; }
        $sql .= ' ORDER BY FIELD(r.moderation_status, \'pending\', \'flagged\', \'approved\', \'rejected\'), r.created_at DESC';
        return $this->pdo->run_query($sql, $params)->fetchAll();
    }

    public function softDelete(int $reviewId): bool {
        $stmt = $this->pdo->run_query("UPDATE reviews SET is_active = 0, moderation_status = 'rejected', moderation_reason = 'Hidden by admin', moderated_at = CURRENT_TIMESTAMP WHERE id = :id", ['id'=>$reviewId]);
        return $stmt->rowCount() > 0;
    }

    public function update(int $reviewId, int $userId, int $rating, ?string $comment): bool {
        $sql = "UPDATE reviews SET rating = :rating, comment = :comment, is_active = 0, moderation_status = 'pending', moderation_reason = 'AI review check in progress', moderated_at = NULL WHERE id = :id AND user_id = :user_id";
        return $this->pdo->run_query($sql, ['rating'=>$rating, 'comment'=>$comment, 'id'=>$reviewId, 'user_id'=>$userId])->rowCount() > 0;
    }
}
?>
