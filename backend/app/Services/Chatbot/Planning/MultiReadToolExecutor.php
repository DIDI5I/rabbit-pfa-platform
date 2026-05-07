<?php

namespace App\Services\Chatbot\Planning;

use App\Services\Chatbot\ToolExecutor;
use Throwable;

class MultiReadToolExecutor
{
    public function execute(array $plan, array $identity): array
    {
        $executor = new ToolExecutor();

        $results = [];
        $failures = [];

        foreach (($plan['tools'] ?? []) as $toolPlan) {
            $intent = $toolPlan['intent'] ?? null;
            $params = $toolPlan['params'] ?? [];

            if (!$intent) {
                $failures[] = [
                    'intent' => null,
                    'status' => 'missing_intent',
                ];

                continue;
            }

            try {
                $result = $executor->execute($intent, $params, $identity);

                $results[] = [
                    'intent' => $intent,
                    'params' => $params,
                    'result' => $result,
                    'status' => 'used',
                ];
            } catch (Throwable $e) {
                $failures[] = [
                    'intent' => $intent,
                    'params' => $params,
                    'status' => 'failed',
                    'error' => $e->getMessage(),
                ];
            }
        }

        return [
            'tool' => 'multi_source_read',
            'status' => count($results) > 0 ? 'success' : 'failed',
            'data' => [
                'results' => $results,
                'failures' => $failures,
            ],
            'meta' => [
                'tool_count' => count($plan['tools'] ?? []),
                'used_count' => count($results),
                'failed_count' => count($failures),
                'reason' => $plan['reason'] ?? null,
            ],
        ];
    }
}