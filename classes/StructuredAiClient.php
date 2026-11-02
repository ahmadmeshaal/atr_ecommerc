<?php

interface StructuredAiClient
{
    public function createStructuredResponse(string $instructions, string $input, string $name, array $schema, int $maxOutputTokens = 300): array;
}