<?php

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../classes/AiPerfumeAdvisor.php';
require_once __DIR__ . '/../classes/AiClientFactory.php';
require_once __DIR__ . '/../classes/AiPerfumeChat.php';
require_once __DIR__ . '/../core/Response.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    Response::json(405, ['success' => false, 'message' => 'Use POST to chat or search perfume preferences.']);
}

$rawBody = file_get_contents('php://input');
if ($rawBody === false || strlen($rawBody) > 12000) {
    Response::json(413, ['success' => false, 'message' => 'Request is too large.']);
}

try {
    $body = json_decode($rawBody, true, 32, JSON_THROW_ON_ERROR);
} catch (JsonException $error) {
    Response::json(400, ['success' => false, 'message' => 'Request body must be valid JSON.']);
}
if (!is_array($body)) {
    Response::json(400, ['success' => false, 'message' => 'Request body must be a JSON object.']);
}

if (($body['action'] ?? '') === 'reset') {
    unset($_SESSION['perfume_chat_history']);
    Response::json(200, ['success' => true, 'message' => 'Chat history cleared.']);
}

$message = $body['message'] ?? null;
if ($message !== null) {
    if (!is_string($message) || trim($message) === '' || strlen($message) > 1000) {
        Response::json(422, ['success' => false, 'message' => 'Message must contain 1–1000 characters.']);
    }

    $now = time();
    $recentRequests = array_values(array_filter(
        $_SESSION['perfume_ai_request_times'] ?? [],
        static fn($timestamp) => is_int($timestamp) && $timestamp > $now - 60
    ));
    if (count($recentRequests) >= 12) {
        Response::json(429, ['success' => false, 'message' => 'Please wait a minute before sending more messages.']);
    }
    $recentRequests[] = $now;
    $_SESSION['perfume_ai_request_times'] = $recentRequests;

    try {
        $pdo = (new Database())->connect();
        $advisor = new AiPerfumeAdvisor($pdo);
        $ai = AiClientFactory::create();
        $chat = new AiPerfumeChat($ai, $advisor);
        $history = $_SESSION['perfume_chat_history'] ?? [];
        $result = $chat->reply(trim($message), is_array($history) ? $history : []);

        $history[] = ['role' => 'user', 'content' => trim($message)];
        $history[] = ['role' => 'assistant', 'content' => $result['message']];
        $_SESSION['perfume_chat_history'] = array_slice($history, -8);

        Response::json(200, [
            'success' => true,
            'message' => $result['message'],
            'language' => $result['language'],
            'preferences' => $result['preferences'],
            'count' => count($result['products']),
            'data' => $result['products']
        ]);
    } catch (Throwable $error) {
        error_log('Perfume AI chat error: ' . $error->getMessage());
        $message = $error->getMessage();
        $isOllamaIssue = str_contains($message, 'Ollama');
        $isRateLimited = str_contains($message, 'HTTP 429');
        $status = str_contains($message, 'not configured') || str_contains($message, 'cURL extension') || $isOllamaIssue
            ? 503
            : ($isRateLimited ? 429 : 502);
        if ($isOllamaIssue) {
            $messageOut = $message;
        } elseif ($status === 503) {
            $messageOut = 'AI chat is not configured yet. Add the provider API key on the server.';
        } elseif ($isRateLimited) {
            $messageOut = 'The AI service has reached its current request limit. Please try again later.';
        } else {
            $messageOut = 'The AI advisor is temporarily unavailable. Please try again.';
        }        Response::json($status, ['success' => false, 'message' => $messageOut]);
    }
}

// Structured search remains available for direct API callers and development.
$preferences = $body['preferences'] ?? $body;
if (!is_array($preferences)) {
    Response::json(422, ['success' => false, 'message' => 'Preferences must be an object.']);
}

$season = $preferences['season'] ?? '';
$family = $preferences['family'] ?? '';
$brand = $preferences['brand'] ?? '';
$notes = $preferences['notes'] ?? [];
$query = $preferences['query'] ?? '';
$minPrice = $preferences['min_price'] ?? null;
$maxPrice = $preferences['max_price'] ?? null;
if (!is_string($season) || !is_string($family) || !is_string($brand) || !is_string($query) || (!is_array($notes) && !is_string($notes))) {
    Response::json(422, ['success' => false, 'message' => 'Invalid preference types.']);
}
foreach (['min_price' => $minPrice, 'max_price' => $maxPrice] as $key => $value) {
    if ($value !== null && (!is_numeric($value) || (float)$value < 0 || (float)$value > 100000)) {
        Response::json(422, ['success' => false, 'message' => $key . ' must be a valid JOD amount.']);
    }
}
if ($minPrice !== null && $maxPrice !== null && (float)$minPrice > (float)$maxPrice) {
    Response::json(422, ['success' => false, 'message' => 'Minimum price cannot be greater than maximum price.']);
}$limit = filter_var($body['limit'] ?? 6, FILTER_VALIDATE_INT);
if ($limit === false || $limit < 1 || $limit > 12) {
    Response::json(422, ['success' => false, 'message' => 'Limit must be between 1 and 12.']);
}

try {
    $pdo = (new Database())->connect();
    $advisor = new AiPerfumeAdvisor($pdo);
    $results = $advisor->recommend([
        'season' => strtolower(trim($season)),
        'family' => trim($family),
        'brand' => trim($brand),
        'notes' => $notes,
        'query' => trim($query),
        'min_price' => $minPrice,
        'max_price' => $maxPrice
    ], $limit);
    Response::json(200, [
        'success' => true,
        'count' => count($results),
        'filters' => ['season' => $season ?: null, 'family' => $family ?: null, 'brand' => $brand ?: null, 'notes' => $notes, 'query' => $query ?: null, 'min_price' => $minPrice, 'max_price' => $maxPrice],
        'data' => $results
    ]);
} catch (InvalidArgumentException $error) {
    Response::json(422, ['success' => false, 'message' => $error->getMessage()]);
} catch (Throwable $error) {
    error_log('Perfume recommendation error: ' . $error->getMessage());
    Response::json(500, ['success' => false, 'message' => 'Could not load fragrance recommendations.']);
}
