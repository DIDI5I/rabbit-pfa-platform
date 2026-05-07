<?php

namespace App\Services\Chatbot\Ai;

use Throwable;

class OpenAiCompatibleClient implements AiClientInterface
{
    private AiConfig $config;

    public function __construct()
    {
        $this->config = new AiConfig();
    }

    public function refineAnswer(array $payload): AiClientResponse
    {
        $apiKey = $apiKey = $this->config->apiKey();
        $apiUrl = $this->config->apiUrl();
        $model = $this->config->model();
        $provider = $this->config->provider() ?? 'openai_compatible';

        if (!$apiKey) {
            return AiClientResponse::failure('missing_ai_api_key', [
                'provider' => $provider,
                'model' => $model,
            ]);
        }

        if (!$apiUrl) {
            return AiClientResponse::failure('missing_ai_api_url', [
                'provider' => $provider,
                'model' => $model,
            ]);
        }

        if (!$model) {
            return AiClientResponse::failure('missing_ai_model', [
                'provider' => $provider,
                'model' => null,
            ]);
        }

        $body = [
            'model' => $model,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => (string) ($payload['system_prompt'] ?? ''),
                ],
                [
                    'role' => 'user',
                    'content' => (string) ($payload['user_prompt'] ?? ''),
                ],
            ],
            'temperature' => 0.2,

            // Groq/OpenAI-compatible newer parameter.
            'max_completion_tokens' => 280,

            // For GPT-OSS on Groq: keep reasoning short and hidden.
            'reasoning_effort' => 'low',
            'reasoning_format' => 'hidden',
        ];

        try {
            $response = $this->postJson($apiUrl, $body, $apiKey);

            if (!$response['ok']) {
                return AiClientResponse::failure($response['error'] ?? 'ai_request_failed', [
                    'provider' => $provider,
                    'model' => $model,
                    'http_status' => $response['status'] ?? null,
                ]);
            }

            $decoded = $response['json'] ?? [];
            $text = $this->extractText($decoded);

            if ($text === null || trim($text) === '') {
                return AiClientResponse::failure('ai_empty_output', [
                    'provider' => $provider,
                    'model' => $model,
                    'response_id' => $decoded['id'] ?? null,
                ]);
            }

            return AiClientResponse::success(trim($text), [
                'provider' => $provider,
                'model' => $model,
                'response_id' => $decoded['id'] ?? null,
                'usage' => $decoded['usage'] ?? null,
            ]);
        } catch (Throwable $e) {
            return AiClientResponse::failure('ai_client_exception', [
                'provider' => $provider,
                'model' => $model,
                'exception' => $e->getMessage(),
            ]);
        }
    }

    private function postJson(string $url, array $body, string $apiKey): array
    {
        $ch = curl_init($url);

        if ($ch === false) {
            return [
                'ok' => false,
                'status' => null,
                'error' => 'curl_init_failed',
            ];
        }

        $payload = json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->config->timeoutSeconds(),
            CURLOPT_CONNECTTIMEOUT => min(5, $this->config->timeoutSeconds()),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $apiKey,
            ],
            CURLOPT_POSTFIELDS => $payload,
        ]);

        $raw = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $curlError = curl_error($ch);

        curl_close($ch);

        if ($raw === false) {
            return [
                'ok' => false,
                'status' => $status ?: null,
                'error' => $curlError ?: 'curl_exec_failed',
            ];
        }

        $decoded = json_decode($raw, true);

        if (!is_array($decoded)) {
            return [
                'ok' => false,
                'status' => $status,
                'error' => 'invalid_json_response',
                'raw' => $raw,
            ];
        }

        if ($status < 200 || $status >= 300) {
            return [
                'ok' => false,
                'status' => $status,
                'error' => $decoded['error']['message']
                    ?? $decoded['error']
                    ?? 'ai_http_error',
                'json' => $decoded,
            ];
        }

        return [
            'ok' => true,
            'status' => $status,
            'json' => $decoded,
        ];
    }

    private function extractText(array $response): ?string
    {
        $content = $response['choices'][0]['message']['content'] ?? null;

        if (is_string($content)) {
            return $content;
        }

        return null;
    }

    public function complete(string $prompt): string
    {
        return $this->refineAnswer($prompt);
    }

}