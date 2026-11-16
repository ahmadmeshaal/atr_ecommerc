<?php

require_once __DIR__ . '/../../config/Database.php';
require_once __DIR__ . '/../../core/Response.php';
require_once __DIR__ . '/../../core/AdminGuard.php';
require_once __DIR__ . '/../../classes/Review.php';

header("Content-Type: application/json; charset=UTF-8");
requireAdminAccess();

try {
    $database = new Database();
    $pdo = $database->connect();
} catch (Throwable $e) {
    Response::json(500, ["success" => false, "message" => "Database connection error"]);
}

$review = new Review($database);
$method = $_SERVER["REQUEST_METHOD"];
$id = isset($_GET['id']) ? filter_var($_GET['id'], FILTER_VALIDATE_INT) : null;

try {
    switch ($method) {
        case 'GET':
            $userIdFilter = isset($_GET['user_id']) ? filter_var($_GET['user_id'], FILTER_VALIDATE_INT) : null;
            $productIdFilter = isset($_GET['product_id']) ? filter_var($_GET['product_id'], FILTER_VALIDATE_INT) : null;

            $data = $review->getAll($userIdFilter !== false ? $userIdFilter : null, $productIdFilter !== false ? $productIdFilter : null);
            Response::json(200, ["success" => true, "data" => $data]);
            break;

        case 'DELETE':
            if (!$id) {
                Response::json(400, ["success" => false, "message" => "Review ID is required"]);
            }

            if ($review->softDelete($id)) {
                Response::json(200, ["success" => true, "message" => "Review soft-deleted successfully"]);
            } else {
                Response::json(400, ["success" => false, "message" => "Failed to delete review"]);
            }
            break;

        case 'PUT':
            if (!$id) {
                Response::json(400, ["success" => false, "message" => "Review ID is required"]);
            }

            try {
                $body = json_decode(file_get_contents('php://input'), true, 512, JSON_THROW_ON_ERROR);
            } catch (JsonException $e) {
                Response::json(400, ["success" => false, "message" => "Invalid JSON body"]);
            }

            $status = is_array($body) ? ($body['status'] ?? '') : '';
            if (!in_array($status, ['approved', 'rejected'], true)) {
                Response::json(422, ["success" => false, "message" => "Status must be approved or rejected"]);
            }

            if ($review->setAdminDecision($id, $status)) {
                Response::json(200, ["success" => true, "message" => "Review {$status} successfully"]);
            }
            Response::json(409, ["success" => false, "message" => "Review is not pending or was already handled"]);
            break;

        default:
            header("Allow: GET, PUT, DELETE");
            Response::json(405, ["success" => false, "message" => "Method not allowed"]);
            break;
    }
} catch (PDOException $e) {
    error_log($e->getMessage());
    Response::json(500, ["success" => false, "message" => "A database error occurred"]);
} catch (Throwable $e) {
    error_log($e->getMessage());
    Response::json(500, ["success" => false, "message" => "An internal server error occurred"]);
}
?>
