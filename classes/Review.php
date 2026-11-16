<?php
class Review {
    private $pdo;

    public function __construct($db_connection) {
        $this->pdo = $db_connection;
    }

    public function verifyOrderOwnership(int $userId, int $orderId, int $productId): bool {
        $sql = "SELECT 1
                FROM orders o
                JOIN order_items oi ON o.id = oi.order_id
                JOIN product_variants pv ON oi.variant_id = pv.id
                WHERE o.id = :order_id
                  AND o.user_id = :user_id
                  AND pv.product_id = :product_id
                  AND o.status = 'delivered'
                LIMIT 1";

        try {
            $stmt = $this->pdo->run_query($sql, [
                'order_id' => $orderId,
                'user_id' => $userId,
                'product_id' => $productId
            ]);
            return (bool) $stmt->fetch();
        } catch(PDOException $e) {
            throw $e;
        }
    }

    public function create(array $data): int {
        $sql = "INSERT INTO reviews
                    (user_id, product_id, order_id, rating, comment, is_active, moderation_status)
                VALUES (:user_id, :product_id, :order_id, :rating, :comment, 0, 'pending')";

        try {
            $this->pdo->run_query($sql, [
                'user_id' => $data['user_id'],
                'product_id' => $data['product_id'],
                'order_id' => $data['order_id'],
                'rating' => $data['rating'],
                'comment' => $data['comment'] ?? null
            ]);
            return (int) $this->pdo->connect()->lastInsertId();
        } catch(PDOException $e) {
            throw $e;
        }
    }

    public function getByProductId(int $productId): array {
        $sql = "SELECT r.id, r.rating, r.comment, r.created_at, u.name as user_name
                FROM reviews r
                JOIN users u ON r.user_id = u.id
                WHERE r.product_id = :product_id
                  AND r.is_active = 1
                  AND r.moderation_status = 'approved'
                ORDER BY r.created_at DESC";

        try {
            $stmt = $this->pdo->run_query($sql, ['product_id' => $productId]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            throw $e;
        }
    }

    public function getAll(?int $userId = null, ?int $productId = null): array {
        $sql = "SELECT r.*, u.name as user_name, p.name as product_name
                FROM reviews r
                JOIN users u ON r.user_id = u.id
                JOIN products p ON r.product_id = p.id
                WHERE 1=1";

        $params = [];

        if ($userId !== null) {
            $sql .= " AND r.user_id = :user_id";
            $params['user_id'] = $userId;
        }

        if ($productId !== null) {
            $sql .= " AND r.product_id = :product_id";
            $params['product_id'] = $productId;
        }

        $sql .= " ORDER BY r.created_at DESC";

        try {
            $stmt = $this->pdo->run_query($sql, $params);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            throw $e;
        }
    }

    public function softDelete(int $reviewId): bool {
        $sql = "UPDATE reviews
                SET is_active = 0,
                    moderation_status = 'rejected',
                    moderated_at = NOW()
                WHERE id = :id";
        try {
            $stmt = $this->pdo->run_query($sql, ['id' => $reviewId]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            throw $e;
        }
    }

    public function setModerationResult(
        int $reviewId,
        bool $flagged,
        string $category,
        string $reason
    ): bool {
        $status = $flagged ? 'pending' : 'approved';
        $active = $flagged ? 0 : 1;
        $sql = "UPDATE reviews
                SET moderation_status = :status,
                    moderation_category = :category,
                    moderation_reason = :reason,
                    is_active = :is_active,
                    moderated_at = NOW()
                WHERE id = :id";
        $stmt = $this->pdo->run_query($sql, [
            'status' => $status,
            'category' => $category,
            'reason' => $reason,
            'is_active' => $active,
            'id' => $reviewId
        ]);
        return $stmt->rowCount() > 0;
    }

    public function setAdminDecision(int $reviewId, string $status): bool {
        if (!in_array($status, ['approved', 'rejected'], true)) {
            return false;
        }

        $active = $status === 'approved' ? 1 : 0;
        $stmt = $this->pdo->run_query(
            "UPDATE reviews
             SET moderation_status = :status, is_active = :is_active, moderated_at = NOW()
             WHERE id = :id AND moderation_status = 'pending'",
            ['status' => $status, 'is_active' => $active, 'id' => $reviewId]
        );
        return $stmt->rowCount() > 0;
    }

    public function update(int $reviewId, int $userId, int $rating, ?string $comment): bool {
        $sql = "UPDATE reviews
                SET rating = :rating, comment = :comment
                WHERE id = :id AND user_id = :user_id";
        try {
            $stmt = $this->pdo->run_query($sql, [
                'rating' => $rating,
                'comment' => $comment,
                'id' => $reviewId,
                'user_id' => $userId
            ]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            throw $e;
        }
    }
}
?>
