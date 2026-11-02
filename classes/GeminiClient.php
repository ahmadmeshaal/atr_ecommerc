<?php

class GeminiClient implements StructuredAiClient
{
    private string $apiKey;
    private string $model;
    private int $timeout;

    public function __construct(?array $config = null)
    {
        $configPath = __DIR__ . '/../config/ai.php';
        $config = $config ?? (is_file($configPath) ? require $configPath : []);
        $this->apiKey = trim((string)($config['api_key'] ?? getenv('GEMINI_API_KEY') ?: ''));
        $this->model = trim((string)($config['model'] ?? getenv('GEMINI_MODEL') ?: 'gemini-3.5-flash'));
        $this->timeout = max(5, min(90, (int)($config['timeout_seconds'] ?? 30)));
        if ($this->apiKey === '') {
            throw new RuntimeException('Gemini API key is not configured.');
        }
        if (!function_exists('curl_init')) {
            throw new RuntimeException('cURL extension is not available.');
        }
    }

    public function createStructuredResponse(string $instructions, string $input, string $name, array $schema, int $maxOutputTokens = 300): array
    {
        $url = 'https://generativelanguage.googleapis.com/v1beta/models/' . rawurlencode($this->model) . ':generateContent';
        $payload = [
            'systemInstruction' => ['parts' => [['text' => $instructions]]],
            'contents' => [['role' => 'user', 'parts' => [['text' => $input]]]],
            'generationConfig' => [
                'responseMimeType' => 'application/json',
                'responseSchema' => $this->toGeminiSchema($schema),
                'maxOutputTokens' => max(64, min(1024, $maxOutputTokens)),
                'temperature' => 0.3
            ]
        ];
        $handle = curl_init($url);
        curl_setopt_array($handle, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'x-goog-api-key: ' . $this->apiKey],
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)
        ]);
        $raw = curl_exec($handle);
        $status = (int)curl_getinfo($handle, CURLINFO_HTTP_CODE);
        $curlError = curl_error($handle);
        curl_close($handle);
        if ($raw === false) {
            error_log('Gemini request failed: ' . $curlError);
            throw new RuntimeException('Gemini request failed.');
        }
        $decoded = json_decode($raw, true);
        if ($status < 200 || $status >= 300 || !is_array($decoded)) {
            $providerMessage = is_array($decoded) ? ($decoded['error']['message'] ?? 'Unknown provider error') : 'Invalid provider response';
            error_log('Gemini API returned HTTP ' . $status . ': ' . $providerMessage);
            throw new RuntimeException('Gemini API HTTP ' . (int)$status . ' request failed.');
        }
        $candidate = $decoded['candidates'][0] ?? [];
        $parts = $candidate['content']['parts'] ?? [];
        $jsonText = '';
        foreach ($parts as $part) if (isset($part['text']) && is_string($part['text'])) $jsonText .= $part['text'];
        $result = json_decode(trim($jsonText), true);
        if (!is_array($result)) {
            // Be tolerant of harmless Markdown fences or text surrounding a JSON object.
            $clean = preg_replace('/^```(?:json)?\\s*|\\s*```$/i', '', trim($jsonText));
            $result = json_decode($clean, true);
            if (!is_array($result) && preg_match('/\\{[\\s\\S]*\\}/', $clean, $match)) {
                $result = json_decode($match[0], true);
            }
        }
        if (!is_array($result)) {
            $reason = (string)($candidate['finishReason'] ?? 'no_candidate');

            error_log('Gemini structured output parse failed; finish=' . $reason . '; text_length=' . strlen($jsonText));

            throw new RuntimeException('Gemini returned invalid structured output.');
        }
        return $result;
    }

    private function toGeminiSchema(array $schema): array
    {
        unset($schema['additionalProperties']);
        foreach ($schema['properties'] ?? [] as $key => $value) {
            if (is_array($value)) $schema['properties'][$key] = $this->toGeminiSchema($value);
        }
        if (isset($schema['items']) && is_array($schema['items'])) $schema['items'] = $this->toGeminiSchema($schema['items']);
        return $schema;
    }
}