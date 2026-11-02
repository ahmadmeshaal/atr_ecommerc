<?php

require_once __DIR__ . '/StructuredAiClient.php';
require_once __DIR__ . '/GeminiClient.php';
require_once __DIR__ . '/OllamaClient.php';

class AiClientFactory
{
    public static function create(?array $config = null): StructuredAiClient
    {
        $configPath = __DIR__ . '/../config/ai.php';
        $config = $config ?? (is_file($configPath) ? require $configPath : require __DIR__ . '/../config/ai.example.php');
        $provider = strtolower(trim((string)($config['provider'] ?? 'gemini')));
        if ($provider === 'ollama') return new OllamaClient($config);
        if ($provider === 'gemini') return new GeminiClient($config);
        throw new RuntimeException('Unsupported AI provider in config/ai.php.');
    }
}