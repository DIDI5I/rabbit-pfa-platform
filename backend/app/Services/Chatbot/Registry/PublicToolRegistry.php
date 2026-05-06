<?php

namespace App\Services\Chatbot\Registry;

class PublicToolRegistry implements ToolGroupInterface
{
    public function tools(): array
    {
        return [
            'role_help' => [
                'roles' => ['guest', 'client', 'supplier', 'owner'],
                'tool' => 'role_help',
                'operation_type' => 'read_only',
                'sensitive' => false,
                'required_params' => [],
            ],

            'navigate' => [
                'roles' => ['guest', 'client', 'supplier', 'owner'],
                'tool' => 'navigate',
                'operation_type' => 'navigation',
                'sensitive' => false,
                'required_params' => ['target'],
            ],

            'show_more' => [
                'roles' => ['guest', 'client', 'supplier', 'owner'],
                'tool' => 'show_more',
                'operation_type' => 'read_only',
                'sensitive' => false,
                'required_params' => [],
            ],

            'catalog_search' => [
                'roles' => ['guest', 'client', 'supplier', 'owner'],
                'tool' => 'catalog_search',
                'operation_type' => 'read_only',
                'sensitive' => false,
                'required_params' => [],
            ],

            'catalog_product_details' => [
                'roles' => ['guest', 'client', 'supplier', 'owner'],
                'tool' => 'catalog_product_details',
                'operation_type' => 'read_only',
                'sensitive' => false,
                'required_params' => ['product_id'],
            ],

            'active_promotions' => [
                'roles' => ['guest', 'client', 'supplier', 'owner'],
                'tool' => 'active_promotions',
                'operation_type' => 'read_only',
                'sensitive' => false,
                'required_params' => [],
            ],

            'catalog_product_relations' => [
                'roles' => ['guest', 'client', 'supplier', 'owner'],
                'tool' => 'catalog_product_relations',
                'operation_type' => 'read_only',
                'sensitive' => false,
                'required_params' => ['product_id'],
            ],

            'catalog_product_promotions' => [
                'roles' => ['guest', 'client', 'supplier', 'owner'],
                'tool' => 'catalog_product_promotions',
                'operation_type' => 'read_only',
                'sensitive' => false,
                'required_params' => ['product_id'],
            ],

            'catalog_product_reviews' => [
                'roles' => ['guest', 'client', 'supplier', 'owner'],
                'tool' => 'catalog_product_reviews',
                'operation_type' => 'read_only',
                'sensitive' => false,
                'required_params' => ['product_id'],
            ],

            'catalog_product_rating_summary' => [
                'roles' => ['guest', 'client', 'supplier', 'owner'],
                'tool' => 'catalog_product_rating_summary',
                'operation_type' => 'read_only',
                'sensitive' => false,
                'required_params' => ['product_id'],
            ],

            'clarification_response' => [
                'roles' => ['guest', 'client', 'supplier', 'owner'],
                'tool' => 'clarification_response',
                'operation_type' => 'read_only',
                'sensitive' => false,
                'required_params' => ['message'],
            ],
        ];
    }
}