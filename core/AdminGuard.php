<?php

require_once __DIR__ . '/Response.php';

function requireAdminAccess(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        require_once __DIR__ . '/../includes/session.php';
    }

    if (!isset($_SESSION['user_id'])) {
        Response::json(401, [
            'success' => false,
            'message' => 'Please sign in as an administrator.'
        ]);
    }

    if (($_SESSION['user_role'] ?? null) !== 'admin') {
        Response::json(403, [
            'success' => false,
            'message' => 'Administrator access required.'
        ]);
    }
}

function requireAdminForMutation(string $method): void
{
    if (in_array(strtoupper($method), ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
        requireAdminAccess();
    }
}
