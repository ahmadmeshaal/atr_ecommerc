<?php

class OllamaClient implements StructuredAiClient
{
    private string $baseUrl;
    private string $model;
    private int $timeout;

    public function __construct(?array $config = null)
    {
        $configPath = __DIR__ . '/../config/ai.php';
        $config = $config ?? (is_file($configPath) ? require $configPath : []);
        $this->baseUrl = rtrim(trim((string)($config['base_url'] ?? 'http://127.0.0.1:11434')), '/');
        $this->model = trim((string)($config['model'] ?? 'qwen3:8b'));
        $this->timeout = max(10, min(240, (int)($config['timeout_seconds'] ?? 120)));
        if ($this->model === '') throw new RuntimeException('Ollama model is not configured.');
        if (!function_exists('curl_init')) throw new RuntimeException('cURL extension is not available.');
    }

    public function createStructuredResponse(string $instructions, string $input, string $name, array $schema, int $maxOutputTokens = 300): array
    {
        $payload = [
            'model' => $this->model,
            'messages' => [
                ['role' => 'system', 'content' => $instructions],
                ['role' => 'user', 'content' => $input]
            ],
            'stream' => false,
            'think' => false,
            'format' => $schema,
            'keep_alive' => '10m',
            'options' => ['temperature' => 0.2, 'num_ctx' => 8192, 'num_predict' => max(64, min(2048, $maxOutputTokens))]
        ];
        $handle = curl_init($this->baseUrl . '/api/chat');
        curl_setopt_array($handle, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)
        ]);
        $raw = curl_exec($handle);
        $status = (int)curl_getinfo($handle, CURLINFO_HTTP_CODE);
        $curlError = curl_error($handle);
        curl_close($handle);
        if ($raw === false) {
            error_log('Ollama request failed: ' . $curlError);
            throw new RuntimeException('Ollama is not running or could not be reached. Start Ollama and download the configured model.');
        }
        $decoded = json_decode($raw, true);
        if ($status < 200 || $status >= 300 || !is_array($decoded)) {
            $error = is_array($decoded) ? (string)($decoded['error'] ?? '') : '';
            error_log('Ollama API returned HTTP ' . $status . ($error !== '' ? ': ' . $error : ''));
            throw new RuntimeException($status === 404
                ? 'The configured Ollama model is not installed. Run ollama pull ' . $this->model . '.'
                : 'Ollama request failed with HTTP ' . $status . '.');
        }
        $content = $decoded['message']['content'] ?? '';
        $result = is_string($content) ? json_decode(trim($content), true) : null;
        if (!is_array($result)) {
            error_log('Ollama returned invalid structured output for ' . $name . '.');
            throw new RuntimeException('Ollama returned invalid structured output.');
        }
        return $result;
    }
}