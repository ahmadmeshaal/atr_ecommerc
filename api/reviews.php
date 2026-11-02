<?php

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../core/Response.php';
require_once __DIR__ . '/../classes/Review.php';
require_once __DIR__ . '/../classes/AiClientFactory.php';
require_once __DIR__ . '/../classes/ReviewModeration.php';

$method = $_SERVER['REQUEST_METHOD'];

if (!in_array($method, ['GET', 'POST', 'PUT'], true)) {
    header('Allow: GET, POST, PUT');

    Response::json(405, [
        'success' => false,
        'message' => 'Method not allowed'
    ]);
}

$userId = null;

if (in_array($method, ['POST', 'PUT'], true)) {
    if (!isset($_SESSION['user_id'])) {
        Response::json(401, [
            'success' => false,
            'message' => 'Unauthorized. Please login.'
        ]);
    }

    $userId = (int) $_SESSION['user_id'];
}

$readInteger = static function ($value, int $min, int $max): int|false {
    if (!is_int($value) && !is_string($value)) {
        return false;
    }

    return filter_var(
        $value,
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => $min, 'max_range' => $max]]
    );
};

$readJsonBody = static function (): array {
    try {
        $body = json_decode(
            file_get_contents('php://input'),
            false,
            512,
            JSON_THROW_ON_ERROR
        );
    } catch (JsonException $e) {
        Response::json(400, [
            'success' => false,
            'message' => 'Invalid JSON body'
        ]);
    }

    if (!$body instanceof stdClass) {
        Response::json(400, [
            'success' => false,
            'message' => 'JSON body must be an object'
        ]);
    }

    return (array) $body;
};

$readReviewData = static function (array $body) use ($readInteger): array {
    $rating = $readInteger($body['rating'] ?? null, 1, 5);
    $comment = $body['comment'] ?? null;

    if ($rating === false) {
        Response::json(422, [
            'success' => false,
            'message' => 'Rating must be an integer between 1 and 5'
        ]);
    }

    if ($comment !== null && !is_string($comment)) {
        Response::json(422, [
            'success' => false,
            'message' => 'Comment must be a string or null'
        ]);
    }

    if ($comment !== null) {
        $comment = trim($comment);

        if (strlen($comment) > 65535) {
            Response::json(422, [
                'success' => false,
                'message' => 'Comment exceeds the supported length'
            ]);
        }

        if ($comment === '') {
            $comment = null;
        }
    }

    return [
        'rating' => $rating,
        'comment' => $comment
    ];
};

try {
    $database = new Database();
    $pdo = $database->connect();
} catch (Throwable $e) {
    error_log('Reviews connection failed: ' . $e->getMessage());

    Response::json(500, [
        'success' => false,
        'message' => 'Database connection error'
    ]);
}

try {
    $review = new Review($database);
    $moderateReview = static function (int $reviewId, int $rating, ?string $comment) use ($review): array {
        $moderator = new ReviewModeration();
        try {
            $moderator = new ReviewModeration(AiClientFactory::create());
        } catch (Throwable $configurationError) {
            error_log('AI review moderation provider unavailable; local safety screening remains active: ' . $configurationError->getMessage());
        }
        try {
            $decision = $moderator->inspect($rating, $comment);
            $status = $decision['status'];
            $reason = $decision['reason'];
            if ($status === 'flagged' && $reason !== null) {
                $reason = ($decision['source'] ?? '') === 'ai' ? 'AI moderation: ' . $reason : 'Automatic safety screening: ' . $reason;
            } elseif ($status === 'approved') {
                $reason = ($decision['source'] ?? '') === 'ai'
                    ? 'AI moderation: approved'
                    : 'Automatic approval: no written comment to check.';
            }
            $review->setModeration($reviewId, $status, $reason);
            return ['status' => $status, 'source' => $decision['source'] ?? 'ai'];
        } catch (Throwable $moderationError) {
            error_log('AI review moderation failed; review held for admin review: ' . $moderationError->getMessage());
            $review->setModeration($reviewId, 'pending', 'AI moderation could not complete. This review is hidden until an admin checks it.');
            return ['status' => 'pending', 'source' => 'unavailable'];
        }
    };

    switch ($method) {
        case 'GET':
            $productId = $readInteger(
                $_GET['product_id'] ?? null,
                1,
                4294967295
            );

            if ($productId === false) {
                Response::json(400, [
                    'success' => false,
                    'message' => 'Valid product_id is required'
                ]);
            }

            $data = $review->getByProductId($productId);

            Response::json(200, [
                'success' => true,
                'data' => $data
            ]);

        case 'POST':
            $body = $readJsonBody();
            $data = $readReviewData($body);

            $orderId = $readInteger(
                $body['order_id'] ?? null,
                1,
                4294967295
            );

            $productId = $readInteger(
                $body['product_id'] ?? null,
                1,
                4294967295
            );

            if ($orderId === false || $productId === false) {
                Response::json(422, [
                    'success' => false,
                    'message' => 'Valid order_id and product_id are required'
                ]);
            }

            if (!$review->verifyOrderOwnership($userId, $orderId, $productId)) {
                Response::json(403, [
                    'success' => false,
                    'message' => 'You can only review products you have received.'
                ]);
            }

            $reviewId = $review->create([
                'user_id' => $userId,
                'product_id' => $productId,
                'order_id' => $orderId,
                'rating' => $data['rating'],
                'comment' => $data['comment']
            ]);
            $moderation = $moderateReview($reviewId, $data['rating'], $data['comment']);
            Response::json(201, [
                'success' => true,
                'message' => $moderation['status'] === 'flagged'
                    ? 'Our automatic review check flagged this review. It is hidden until the store team checks it.'
                    : ($moderation['status'] === 'pending'
                        ? 'Your review is saved and hidden while the automatic check is unavailable. The store team will review it.'
                        : 'Review submitted successfully'),
                'moderation_status' => $moderation['status'],
                'moderation_source' => $moderation['source']
            ]);
        case 'PUT':
            $id = $readInteger(
                $_GET['id'] ?? null,
                1,
                4294967295
            );

            if ($id === false) {
                Response::json(400, [
                    'success' => false,
                    'message' => 'Valid review ID is required'
                ]);
            }

            $body = $readJsonBody();
            $data = $readReviewData($body);

            // Lock the owned review while checking and updating it.
            $pdo->beginTransaction();

            $stmt = $pdo->prepare(
                'SELECT id, is_active
                 FROM reviews
                 WHERE id = ? AND user_id = ?
                 FOR UPDATE'
            );
            $stmt->execute([$id, $userId]);
            $existingReview = $stmt->fetch();

            if (!$existingReview) {
                $pdo->rollBack();

                Response::json(404, [
                    'success' => false,
                    'message' => 'Review not found'
                ]);
            }

            if ((int) $existingReview['is_active'] !== 1) {
                $pdo->rollBack();

                Response::json(403, [
                    'success' => false,
                    'message' => 'This review is inactive and cannot be edited'
                ]);
            }

            $review->update($id, $userId, $data['rating'], $data['comment']);
            $pdo->commit();

            $moderation = $moderateReview($id, $data['rating'], $data['comment']);
            Response::json(200, [
                'success' => true,
                'message' => $moderation['status'] === 'flagged'
                    ? 'Our automatic review check flagged this review. It is hidden until the store team checks it.'
                    : ($moderation['status'] === 'pending'
                        ? 'Your review is saved and hidden while the automatic check is unavailable. The store team will review it.'
                        : 'Review updated successfully'),
                'moderation_status' => $moderation['status'],
                'moderation_source' => $moderation['source']
            ]);    }
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log('Reviews database error: ' . $e->getMessage());

    $driverCode = (int) ($e->errorInfo[1] ?? 0);

    if ($driverCode === 1062) {
        Response::json(409, [
            'success' => false,
            'message' => 'You have already reviewed this product.'
        ]);
    }

    if ($driverCode === 1452) {
        Response::json(409, [
            'success' => false,
            'message' => 'The referenced user, product or order no longer exists'
        ]);
    }

    Response::json(500, [
        'success' => false,
        'message' => 'A database error occurred'
    ]);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log('Reviews API error: ' . $e->getMessage());

    Response::json(500, [
        'success' => false,
        'message' => 'An internal server error occurred'
    ]);
}
