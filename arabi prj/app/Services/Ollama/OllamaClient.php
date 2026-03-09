<?php

namespace App\Services\Ollama;

/**
 * Low-level HTTP client for Ollama local API (chat/completion).
 * Uses OLLAMA_BASE_URL (default http://127.0.0.1:11434), optional timeout.
 */
class OllamaClient
{
    private string $baseUrl;
    private int $timeoutSeconds;

    public function __construct(?string $baseUrl = null, int $timeoutSeconds = 90)
    {
        $this->baseUrl = rtrim($baseUrl ?? $_ENV['OLLAMA_BASE_URL'] ?? 'http://127.0.0.1:11434', '/');
        $this->timeoutSeconds = max(15, $timeoutSeconds);
    }

    /**
     * POST to /api/chat, get assistant message content.
     * Request: { "model": "...", "messages": [ {"role": "user", "content": "..."} ], "stream": false }
     */
    public function chat(string $model, string $userMessage): string
    {
        $payload = [
            'model' => $model,
            'messages' => [
                ['role' => 'user', 'content' => $userMessage],
            ],
            'stream' => false,
        ];
        $response = $this->post('/api/chat', $payload);
        $content = $response['message']['content'] ?? '';
        return is_string($content) ? $content : '';
    }

    /**
     * POST to /api/generate (alternative endpoint), get "response" field.
     */
    public function generate(string $model, string $prompt): string
    {
        $payload = [
            'model' => $model,
            'prompt' => $prompt,
            'stream' => false,
        ];
        $response = $this->post('/api/generate', $payload);
        return $response['response'] ?? '';
    }

    private function post(string $path, array $body): array
    {
        $url = $this->baseUrl . $path;
        $json = json_encode($body, JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            throw new \RuntimeException('Failed to encode Ollama request JSON');
        }
        $ctx = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/json\r\n",
                'content' => $json,
                'timeout' => (float) $this->timeoutSeconds,
            ],
        ]);
        $raw = @file_get_contents($url, false, $ctx);
        if ($raw === false) {
            $err = error_get_last();
            throw new \RuntimeException('Ollama request failed: ' . ($err['message'] ?? 'unknown'));
        }
        $decoded = json_decode($raw, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException('Ollama returned invalid JSON');
        }
        return $decoded;
    }
}
