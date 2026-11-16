<?php

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../core/Response.php';
require_once __DIR__ . '/../classes/Review.php';
require_once __DIR__ . '/../classes/ReviewModerator.php';

header("Content-Type: application/json; charset=UTF-8");

try {
    $database = new Database();
    $pdo = $database->connect();
} catch (Throwable $e) {
    Response::json(500, ["success" => false, "message" => "Database connection error"]);
}

$review = new Review($database);
$method = $_SERVER["REQUEST_METHOD"];
$id = isset($_GET['id']) ? filter_var($_GET['id'], FILTER_VALIDATE_INT) : null;
$productId = isset($_GET['product_id']) ? filter_var($_GET['product_id'], FILTER_VALIDATE_INT) : null;

$userId = null;
if (in_array($method, ['POST', 'PUT'], true)) {
    require_once __DIR__ . '/../includes/session.php';
    if (!isset($_SESSION['user_id'])) {
        Response::json(401, ["success" => false, "message" => "Please sign in to submit a review."]);
    }
    $userId = (int) $_SESSION['user_id'];
}

function getJsonBody(): array
{
    $rawBody = file_get_contents("php://input");
    try {
        $data = json_decode($rawBody, true, 512, JSON_THROW_ON_ERROR);
    } catch (JsonException $e) {
        Response::json(400, ["success" => false, "message" => "Invalid JSON body"]);
    }
    if (!is_array($data)) {
        Response::json(400, ["success" => false, "message" => "JSON body must be an object"]);
    }
    return $data;
}

try {
    switch ($method) {
        case 'GET':
            if (!$productId) {
                Response::json(400, ["success" => false, "message" => "Valid product_id is required"]);
            }

            // Calls a method to fetch only active reviews
            $data = $review->getByProductId($productId);
            Response::json(200, ["success" => true, "data" => $data]);
            break;

        case 'POST':
            $body = getJsonBody();

            $rating = isset($body['rating']) ? (int) $body['rating'] : 0;
            $comment = $body['comment'] ?? null;
            $orderId = isset($body['order_id']) ? (int) $body['order_id'] : 0;
            $reqProductId = isset($body['product_id']) ? (int) $body['product_id'] : 0;

            if ($rating < 1 || $rating > 5) { // Enforces CHECK constraint[cite: 1]
                Response::json(400, ["success" => false, "message" => "Rating must be between 1 and 5"]);
            }
            if (!$orderId || !$reqProductId) {
                Response::json(400, ["success" => false, "message" => "order_id and product_id are required"]);
            }
            if ($comment !== null && !is_string($comment)) {
                Response::json(422, ["success" => false, "message" => "comment must be a string or null"]);
            }
            $comment = is_string($comment) ? trim($comment) : null;
            if ($comment === '') {
                $comment = null;
            }

            // Validates that the user owns the order, it contains the product, and is delivered
            if (!$review->verifyOrderOwnership($userId, $orderId, $reqProductId)) {
                Response::json(403, ["success" => false, "message" => "You can only review products you have received."]);
            }

            $reviewId = $review->create([
                'user_id' => $userId,
                'product_id' => $reqProductId,
                'order_id' => $orderId,
                'rating' => $rating,
                'comment' => $comment
            ]);

            $moderation = [
                'flagged' => false,
                'category' => 'none',
                'reason' => 'لا يوجد نص مكتوب يحتاج إلى فحص.'
            ];

            if ($comment !== null) {
                try {
                    $configPath = __DIR__ . '/../config/ai.php';
                    if (!is_file($configPath)) {
                        throw new RuntimeException('AI moderation is not configured.');
                    }

                    $moderator = new ReviewModerator(require $configPath);
                    $moderation = $moderator->analyze($comment);
                } catch (Throwable $e) {
                    error_log('Review moderation unavailable: ' . $e->getMessage());
                    $moderation = [
                        'flagged' => true,
                        'category' => 'other',
                        'reason' => 'تعذر فحص المراجعة تلقائيًا؛ أُرسلت للأدمن للمراجعة.'
                    ];
                }
            }

            $review->setModerationResult(
                $reviewId,
                $moderation['flagged'],
                $moderation['category'],
                $moderation['reason']
            );

            $moderationStatus = $moderation['flagged'] ? 'pending' : 'approved';

            Response::json(201, [
                "success" => true,
                "message" => $moderationStatus === 'pending'
                    ? "Review submitted and is awaiting administrator review."
                    : "Review submitted successfully.",
                "review_id" => $reviewId,
                "moderation_status" => $moderationStatus
            ]);
            break;

        case 'PUT':
            if (!$id) {
                Response::json(400, ["success" => false, "message" => "Review ID is required"]);
            }

            $body = getJsonBody();
            $rating = isset($body['rating']) ? (int) $body['rating'] : 0;
            $comment = $body['comment'] ?? null;

            if ($rating < 1 || $rating > 5) { // Enforces CHECK constraint[cite: 1]
                Response::json(400, ["success" => false, "message" => "Rating must be between 1 and 5"]);
            }

            if ($review->update($id, $userId, $rating, $comment)) {
                Response::json(200, ["success" => true, "message" => "Review updated successfully"]);
            } else {
                Response::json(400, ["success" => false, "message" => "Failed to update review or unauthorized"]);
            }
            break;

        default:
            header("Allow: GET, POST, PUT");
            Response::json(405, ["success" => false, "message" => "Method not allowed"]);
            break;
    }
} catch (PDOException $e) {
    error_log($e->getMessage());

    if ($e->getCode() == 23000) {
        Response::json(409, ["success" => false, "message" => "You have already reviewed this product."]);
    }

    // TEMPORARY FOR DEBUGGING: Expose the raw SQL error
    Response::json(500, [
        "success" => false,
        "message" => "Database error: " . $e->getMessage()
    ]);
} catch (Throwable $e) {
    error_log($e->getMessage());
    Response::json(500, ["success" => false, "message" => "An internal server error occurred"]);
}
?>
