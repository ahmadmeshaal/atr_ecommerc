<?php
// Copy to config/ai.php. Ollama must be running locally with this model installed.
return [
    'provider' => 'ollama',
    'base_url' => 'http://127.0.0.1:11434',
    'model' => 'qwen3:8b',
    'timeout_seconds' => 120,
];