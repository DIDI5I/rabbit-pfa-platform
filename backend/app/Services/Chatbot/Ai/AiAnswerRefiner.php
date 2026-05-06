<?php

namespace App\Services\Chatbot\Ai;

use Throwable;

class AiAnswerRefiner
{
    private AiConfig $config;
    private AiSafetyPolicy $policy;
    private AiPromptBuilder $promptBuilder;
    private AiClientInterface $client;
    private AiRefinementLogger $logger;

    public function __construct(?AiClientInterface $client = null)
    {
        $this->config = new AiConfig();
    $this->policy = new AiSafetyPolicy();
    $this->promptBuilder = new AiPromptBuilder();
    $this->client = $client ?? (new AiClientFactory())->make();
    $this->logger = new AiRefinementLogger();
    }

    public function refine(array $response): array
    {
        $response = $this->ensureAiFields($response);

        if (!$this->config->enabled()) {
            return $this->withFallback($response, 'ai_disabled');
        }

        $intent = $response['data']['intent'] ?? null;

        if (!$this->config->isIntentAllowed($intent)) {
            return $this->withFallback($response, 'intent_not_allowed');
        }

        if (!$this->policy->shouldRefine($response)) {
            return $this->withFallback($response, 'policy_skip');
        }

        try {
            $safeData = $this->safeData($response);
            $safeDataJson = json_encode($safeData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $inputChars = strlen((string) $safeDataJson);

            if ($inputChars > $this->config->maxInputChars()) {
                return $this->withFallback($response, 'ai_input_too_large', [
                    'input_char_count' => $inputChars,
                ]);
            }

            $payload = $this->promptBuilder->build($safeData);

            $clientResponse = $this->client->refineAnswer($payload);

            if (!$clientResponse->success || !$clientResponse->text) {
                return $this->withFallback($response, $clientResponse->error ?? 'ai_client_failed', array_merge([
                    'input_char_count' => $inputChars,
                    'provider' => $this->config->provider(),
                    'model' => $this->config->model(),
                ], $clientResponse->meta));
            }

            $aiAnswer = trim($clientResponse->text);
            $aiAnswer = $this->normalizeAiText($aiAnswer);
            $outputChars = strlen($aiAnswer);

            if ($aiAnswer === '') {
                return $this->withFallback($response, 'ai_empty_output', [
                    'input_char_count' => $inputChars,
                    'output_char_count' => 0,
                ]);
            }

            if ($outputChars > $this->config->maxOutputChars()) {
                return $this->withFallback($response, 'ai_output_too_long', [
                    'input_char_count' => $inputChars,
                    'output_char_count' => $outputChars,
                ]);
            }

            $validation = $this->policy->validateOutput($aiAnswer, $safeData);

            if (!$validation['passed'] && $this->config->fallbackOnValidationFailure()) {
                return $this->withFallback($response, 'ai_output_validation_failed', [
                    'input_char_count' => $inputChars,
                    'output_char_count' => $outputChars,
                    'validation_passed' => false,
                    'validation_violations' => $validation['violations'] ?? [],
                ]);
            }

            $response['data']['answer'] = $aiAnswer;
            $response['data']['ai_refined'] = true;

            $this->logAiAttempt($response, [
                'ai_refined' => true,
                'fallback_reason' => null,
                'input_char_count' => $inputChars,
                'output_char_count' => $outputChars,
                'validation_passed' => $validation['passed'],
                'validation_violations' => $validation['violations'] ?? [],
            ]);

            if ($this->isDebugMode($response)) {
                $response['data']['result_meta'] = $this->ensureResultMeta($response['data']['result_meta'] ?? []);

                $response['data']['result_meta']['ai'] = [
                    'provider' => $this->config->provider(),
                    'model' => $this->config->model(),
                    'prompt_version' => $this->config->promptVersion(),
                    'fallback_reason' => null,
                    'input_char_count' => $inputChars,
                    'output_char_count' => $outputChars,
                    'validation_passed' => $validation['passed'],
                    'validation_violations' => $validation['violations'] ?? [],
                ];
            }

            return $response;
        } catch (Throwable $e) {
            return $this->withFallback($response, 'ai_refiner_exception');
        }
    }

    private function ensureAiFields(array $response): array
    {
        if (!isset($response['data']) || !is_array($response['data'])) {
            $response['data'] = [];
        }

        if (!array_key_exists('ai_refined', $response['data'])) {
            $response['data']['ai_refined'] = false;
        }

        return $response;
    }

    private function withFallback(array $response, string $reason, array $extraMeta = []): array
    {
        $response['data']['ai_refined'] = false;

        if ($this->isDebugMode($response)) {
            $response['data']['result_meta'] = $this->ensureResultMeta($response['data']['result_meta'] ?? []);

            $response['data']['result_meta']['ai'] = array_merge([
                'provider' => $this->config->provider(),
                'model' => $this->config->model(),
                'prompt_version' => $this->config->promptVersion(),
                'fallback_reason' => $reason,
                'validation_passed' => null,
            ], $extraMeta);
        }

        $this->logAiAttempt($response, array_merge([
            'ai_refined' => false,
            'fallback_reason' => $reason,
            'validation_passed' => $extraMeta['validation_passed'] ?? null,
        ], $extraMeta));

        return $response;
    }

    private function safeData(array $response): array
    {
        $data = $response['data'] ?? [];

        return [
            'answer' => $data['answer'] ?? null,
            'intent' => $data['intent'] ?? null,
            'confidence' => $data['confidence'] ?? null,
            'role' => $data['role'] ?? null,
            'operation_type' => $data['operation_type'] ?? null,
            'summary' => $data['summary'] ?? [],
            'items_preview' => $data['items_preview'] ?? [],
            'result_meta' => $this->resultMetaWithoutAi($data['result_meta'] ?? []),
            'sources' => $data['sources'] ?? [],
            'limitations' => $data['limitations'] ?? [],
            'suggested_actions' => $data['suggested_actions'] ?? [],
        ];
    }

    private function resultMetaWithoutAi(mixed $resultMeta): array
    {
        if (!is_array($resultMeta)) {
            return [];
        }

        unset($resultMeta['ai']);

        return $resultMeta;
    }

    private function ensureResultMeta(mixed $resultMeta): array
    {
        return is_array($resultMeta) ? $resultMeta : [];
    }

    private function isDebugMode(array $response): bool
    {
        /*
         * Temporary V1 behavior:
         * If you later pass an explicit debug flag from ChatbotService,
         * replace this with that flag.
         */
        return isset($_GET['debug']);
    }

    private function normalizeAiText(string $text): string
    {
        $replacements = [
            "\u{00A0}" => ' ',  // non-breaking space
            "\u{202F}" => ' ',  // narrow non-breaking space
            "\u{2011}" => '-',  // non-breaking hyphen inside words
            "\u{2010}" => '-',  // hyphen
            "\u{2212}" => '-',  // minus sign

            "\u{2012}" => ' - ', // figure dash
            "\u{2013}" => ' - ', // en dash
            "\u{2014}" => ' - ', // em dash
            '–' => ' - ',
            '—' => ' - ',
        ];

        $text = strtr($text, $replacements);

        // Clean spacing around hyphens used as sentence separators.
        $text = preg_replace('/\s+-\s+/', ' - ', $text);

        // Keep normal compound words clean: high - priority -> high-priority.
        $text = preg_replace('/\b(high|low|medium|critical|data|stock|reorder|business|role)\s+-\s+(priority|quality|level|facing|based|only)\b/i', '$1-$2', $text);

        $text = preg_replace('/\s+/', ' ', $text);

        return trim($text);
    }

    private function logAiAttempt(array $response, array $meta): void
    {
        if (!$this->config->logEnabled()) {
            return;
        }

        $data = $response['data'] ?? [];

        $source = null;
        if (isset($data['sources'][0]) && is_array($data['sources'][0])) {
            $source = $data['sources'][0];
        }

        $this->logger->log([
            'user_id' => null,
            'role' => $data['role'] ?? 'guest',

            'intent' => $data['intent'] ?? null,
            'tool' => $source['tool'] ?? null,

            'ai_refined' => $meta['ai_refined'] ?? false,
            'provider' => $meta['provider'] ?? $this->config->provider(),
            'model' => $meta['model'] ?? $this->config->model(),
            'prompt_version' => $meta['prompt_version'] ?? $this->config->promptVersion(),

            'fallback_reason' => $meta['fallback_reason'] ?? null,

            'input_char_count' => $meta['input_char_count'] ?? null,
            'output_char_count' => $meta['output_char_count'] ?? null,

            'validation_passed' => $meta['validation_passed'] ?? null,
            'validation_violations' => $meta['validation_violations'] ?? [],

            'error' => $meta['error'] ?? null,
        ]);
    }
}