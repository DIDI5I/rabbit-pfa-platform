<?php

namespace App\Services\Chatbot;

class SecurityVerifier
{
    public function verify(array $response, array $identity): array
    {
        $role = $identity['role'] ?? 'guest';
        $data = $response['data'] ?? [];

        if (!$this->hasBackendConvention($response)) {
            return $this->fail('Response does not follow backend convention.');
        }

        if ($role !== 'owner' && $this->containsOwnerSensitiveData($data)) {
            return $this->fail('Response contains owner-sensitive data.');
        }

        return [
            'passed' => true,
            'reason' => null,
        ];
    }

    private function hasBackendConvention(array $response): bool
    {
        return array_key_exists('message', $response)
            && array_key_exists('data', $response)
            && is_array($response['data']);
    }

    private function containsOwnerSensitiveData(array $data): bool
    {
        $forbiddenKeys = [
            'current_stock',
            'low_stock_threshold',
            'preferred_unit_cost_mad',
            'estimated_stock_value',
            'estimated_stock_value_mad',
            'total_material_cost',
            'total_material_cost_mad',
            'reorder_recommendations',
            'purchase_lots',
            'supplier_internal_cost',
        ];

        return $this->arrayContainsForbiddenKey($data, $forbiddenKeys);
    }

    private function arrayContainsForbiddenKey(array $array, array $forbiddenKeys): bool
    {
        foreach ($array as $key => $value) {
            if (in_array($key, $forbiddenKeys, true)) {
                return true;
            }

            if (is_array($value) && $this->arrayContainsForbiddenKey($value, $forbiddenKeys)) {
                return true;
            }
        }

        return false;
    }

    private function fail(string $reason): array
    {
        return [
            'passed' => false,
            'reason' => $reason,
        ];
    }
}