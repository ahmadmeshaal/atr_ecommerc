<?php

require_once __DIR__ . '/../../config/Database.php';
require_once __DIR__ . '/../../core/Response.php';
require_once __DIR__ . '/../../core/AdminGuard.php';

header('Content-Type: application/json; charset=UTF-8');
requireAdminAccess();

try {
    $database = new Database();
    $stmt = $database->connect()->query(
        'SELECT id, name, email, phone, role, email_verified_at, created_at
         FROM users
         ORDER BY created_at DESC'
    );
    $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    Response::json(200, [
        'success' => true,
        'count' => count($customers),
        'data' => $customers
    ]);
} catch (Throwable $e) {
    error_log('Admin customer list failed: ' . $e->getMessage());
    Response::json(500, [
        'success' => false,
        'message' => 'Could not load customers.'
    ]);
}
