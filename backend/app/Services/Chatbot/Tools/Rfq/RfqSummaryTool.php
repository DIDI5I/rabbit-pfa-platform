<?php

namespace App\Services\Chatbot\Tools\Rfq;

use App\Services\Chatbot\Tools\BaseToolHandler;
use App\Services\RfqService;
use Throwable;

class RfqSummaryTool extends BaseToolHandler
{
    public function supports(string $intent): bool
    {
        return $intent === 'rfq_summary';
    }

    public function execute(string $intent, array $params, array $identity, array $context = []): array
    {
        $role = $identity['role'] ?? 'guest';

        try {
            $response = (new RfqService())->list();
            $data = $response['data'] ?? $response;

            $items = $data['items']
                ?? $data['rfqs']
                ?? (is_array($data) ? $data : []);

            if (!is_array($items)) {
                $items = [];
            }

            $summary = $this->summarizeByStatus($items);

            return $this->result(
                'rfq_summary',
                'success',
                [
                    'rfqs' => $items,
                    'summary' => $summary,
                    'count' => count($items),
                ],
                $this->meta('rfq_summary', $role, 'read_only', false, count($items))
            );
        } catch (Throwable $e) {
            return $this->result(
                'rfq_summary',
                'failed',
                [],
                $this->meta('rfq_summary', $role, 'read_only', false, 0),
                [
                    [
                        'code' => 'rfq_summary_failed',
                        'message' => $e->getMessage(),
                    ],
                ]
            );
        }
    }

    private function summarizeByStatus(array $items): array
    {
        $summary = [
            'total' => count($items),
            'draft' => 0,
            'open' => 0,
            'quoted' => 0,
            'accepted' => 0,
            'rejected' => 0,
            'expired' => 0,
            'cancelled' => 0,
        ];

        foreach ($items as $item) {
            $status = $item['status'] ?? null;

            if ($status && array_key_exists($status, $summary)) {
                $summary[$status]++;
            }
        }

        return $summary;
    }
}