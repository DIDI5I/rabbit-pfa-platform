<?php

namespace App\Services\Chatbot\Ai;

use Throwable;

class AiIntentRouter
{
    private AiConfig $config;
    private OpenAiCompatibleClient $client;

    public function __construct()
    {
        $this->config = new AiConfig();
        $this->client = new OpenAiCompatibleClient($this->config);
    }

    public function route(string $message, array $identity): array
    {
        if (!$this->config->enabled()) {
            return $this->reject('ai_disabled');
        }

        try {
           $clientResponse = $this->client->refineAnswer([
                'system_prompt' => 'You are Rabbit AI Intent Router. Return only strict JSON. Do not use markdown.',
                'user_prompt' => $this->buildPrompt($message, $identity),
            ]);

            if (!$clientResponse->success) {
                return $this->reject($clientResponse->error ?? 'ai_router_client_failed', [
                    'client_meta' => $clientResponse->meta,
                ]);
            }

            $raw = (string) $clientResponse->text;
            $proposal = $this->parseJson($raw);

            if (!$proposal) {
                return $this->reject('invalid_ai_json', [
                    'raw_output' => mb_substr((string) $raw, 0, 500),
                ]);
            }

            return $this->validateProposal($proposal);
        } catch (Throwable $e) {
            return $this->reject('ai_router_failed', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function buildPrompt(string $message, array $identity): string
    {
        $role = $identity['role'] ?? 'guest';

        return <<<PROMPT
            You are Rabbit's AI Intent Router and Tool Planner.
            You are not a general chatbot.
            You are not allowed to answer the user directly.
            You are not allowed to execute actions.
            You are not allowed to invent business data.

            Your only job is to convert the user's natural-language request into a strict JSON routing proposal for Rabbit's backend.

            Rabbit is an industrial e-commerce, procurement, inventory, stock intelligence, and AI-assisted operations platform.

            Rabbit manages:
            - product catalogue
            - product dependencies and alternatives
            - cost rollups
            - suppliers
            - RFQs
            - purchase lots
            - orders
            - stock movements
            - inventory alerts
            - stock intelligence and reorder recommendations
            - notifications
            - dashboard summaries
            - safe write actions

            Current user role:
            {{ROLE}}

            ========================
            CORE ROUTING PRINCIPLE
            ========================

            Rabbit backend is the source of truth.

            You only propose a route.

            The backend will:
            - validate the intent
            - validate required parameters
            - check user permissions
            - check whether tools exist
            - execute read tools
            - create preview-only write actions
            - require confirmation for sensitive writes

            Never assume that your route will be executed.

            ========================
            OUTPUT FORMAT
            ========================

            Return JSON only.

            Do not return markdown.
            Do not include explanations outside JSON.
            Do not include code fences.

            Allowed output forms:

            1. Single read tool:

            {
            "intent": "tool_name",
            "operation_type": "read_only",
            "confidence": "low|medium|high",
            "params": {},
            "reason": "short explanation"
            }

            2. Single write tool:

            {
            "intent": "tool_name",
            "operation_type": "write_action",
            "confidence": "low|medium|high",
            "params": {},
            "reason": "short explanation"
            }

            3. Multi-source read plan:

            {
            "intent": "multi_source_read",
            "operation_type": "read_only",
            "confidence": "low|medium|high",
            "tools": [
                {
                "intent": "tool_name",
                "params": {}
                }
            ],
            "reason": "short explanation"
            }

            4. Unsupported / ambiguous / unsafe:

            {
            "intent": "unsupported",
            "operation_type": "unsupported",
            "confidence": "low",
            "params": {},
            "reason": "short explanation"
            }

            or

            {
            "intent": "ambiguous",
            "operation_type": "unsupported",
            "confidence": "low",
            "params": {},
            "reason": "short explanation"
            }

            or

            {
            "intent": "multiple_write_actions_detected",
            "operation_type": "write_action",
            "confidence": "high",
            "params": {},
            "reason": "short explanation"
            }

            ========================
            GENERAL RULES
            ========================

            1. Do not answer the user's business question.
            2. Do not invent IDs, stock values, prices, suppliers, RFQs, purchase lots, forecasts, orders, or recommendations.
            3. Extract IDs only if they appear in the user message.
            4. If a required ID is missing, return ambiguous or unsupported.
            5. For read-only requests needing several tools, use multi_source_read.
            6. For write requests, never use multi_source_read.
            7. For write requests, propose only one write action.
            8. If the user asks for multiple write actions in one message, return multiple_write_actions_detected.
            9. Write actions only create backend previews. They do not execute without confirmation.
            10. If the user asks for a recommendation that Rabbit does not have a grounded tool for, return unsupported or a safe read tool with limitations.
            11. If no available tool can safely answer, return unsupported.
            12. Prefer precise backend tools over generic tools.
            13. For broad analytical requests, prefer intelligence/summary tools over catalogue/search tools.
            14. For exact simple requests, choose the exact single tool.
            15. For multi-domain analytical read requests, choose multi_source_read.

            ========================
            READ TOOL CATALOGUE
            ========================

            PRODUCT / CATALOGUE TOOLS

            1. catalog_search
            Use when the user wants to browse/search/list public catalogue products.
            Examples:
            - show products
            - list catalogue
            - search products
            Params:
            - optional search
            - optional category
            - optional availability

            Do not use catalog_search for analytical stock risk, procurement, reorder, or attention questions.

            2. catalog_product_details
            Use when the user asks for public details of one catalogue product.
            Required params:
            - product_id OR component_id

            3. catalog_product_relations
            Use when the user asks for public product relations, related products, alternatives, or compatible products.
            Required params:
            - product_id OR component_id

            4. catalog_product_promotions
            Use when the user asks about promotions for a product.
            Required params:
            - product_id OR component_id

            5. catalog_product_reviews
            Use when the user asks for reviews of a product.
            Required params:
            - product_id OR component_id

            6. catalog_product_rating_summary
            Use when the user asks for a rating summary of a product.
            Required params:
            - product_id OR component_id

            OWNER PRODUCT / PROCUREMENT TOOLS

            7. owner_product_details
            Use when an owner asks for internal product details.
            Required params:
            - product_id OR component_id

            8. owner_product_dependencies
            Use when the user asks about:
            - dependencies
            - replacement parts
            - technical structure
            - related parts
            - alternatives
            - compatible products
            - product composition
            - product relationships
            Required params:
            - product_id

            9. cost_rollup
            Use when the user asks about:
            - cost rollup
            - cost structure
            - cost breakdown
            - material cost
            - cost composition
            Required params:
            - product_id

            10. purchase_lots_by_product
            Use when the user asks about:
            - procurement history for a product
            - purchase lots for a product
            - supplier purchase history
            - received lots by product
            Required params:
            - product_id

            11. purchase_lot_summary
            Use when the user asks for a purchase lot overview.
            Params:
            - none

            12. purchase_lot_details
            Use when the user asks about a specific purchase lot:
            - explain purchase lot 45
            - what happened to stock and costs for purchase lot 45
            - show finalized cost of purchase lot 45
            Required params:
            - purchase_lot_id

            If this tool is not available in backend validation, the backend will safely fallback. Still choose it when the request clearly asks for a specific purchase lot.

            STOCK / INVENTORY TOOLS

            13. inventory_summary
            Use when the user asks for a general inventory overview.
            Params:
            - none

            14. inventory_alerts
            Use when the user asks about:
            - low stock
            - out of stock
            - inventory alerts
            - products currently in alert state
            Params:
            - none

            15. component_stock_analysis
            Use when the user asks about a specific product/component stock situation:
            - analyze component 11
            - is component 11 stock healthy?
            - stock risk for product 1
            - explain stock situation for component/product X
            Required params:
            - component_id

            If the user says "product 1" and asks about stock/risk, use component_id = 1.

            16. dashboard_stock_summary
            Use when the user asks for dashboard-style stock summary.
            Params:
            - none

            STOCK INTELLIGENCE / FORECASTING TOOLS

            17. stock_intelligence_summary
            Use when the user asks broad questions about:
            - stock risk
            - products needing attention
            - inventory health
            - urgent stock issues
            - risk analysis
            - stock intelligence
            - what needs attention from stock perspective
            Params:
            - none

            18. reorder_recommendations
            Use when the user asks:
            - what should I reorder?
            - what should I buy soon?
            - urgent replenishment
            - reorder priorities
            - products to restock
            Params:
            - optional filters

            19. stock_intelligence_explanation
            Use when the user asks how stock intelligence or forecasting works.
            Params:
            - none

            20. stock_intelligence_dashboard_explanation
            Use when the user asks to explain the stock intelligence dashboard.
            Params:
            - none

            ORDER TOOLS

            21. order_summary
            Use when the user asks about:
            - order overview
            - order health
            - order statuses
            - pending/processing/shipped/delivered/cancelled order counts
            - orders needing attention
            Params:
            - none

            22. order_details
            Use when the user asks about one specific order.
            Required params:
            - order_id

            23. orders_by_status
            Use when the user asks for orders with a specific status.
            Required params:
            - status

            Allowed statuses:
            - pending
            - paid
            - processing
            - shipped
            - delivered
            - cancelled

            24. recent_orders
            Use when the user asks for recent/latest orders.
            Params:
            - none

            RFQ TOOLS

            25. rfq_summary
            Use when the user asks about:
            - RFQ overview
            - RFQs needing owner attention
            - open RFQs
            - quoted RFQs
            - accepted/rejected/expired RFQ counts
            Params:
            - none

            26. rfq_details
            Use when the user asks about one specific RFQ.
            Required params:
            - rfq_id

            27. rfqs_by_status
            Use when the user asks for RFQs with a specific status.
            Required params:
            - status

            Allowed statuses:
            - draft
            - open
            - quoted
            - accepted
            - rejected
            - expired
            - cancelled

            28. rfq_allowed_actions
            Use when the user asks what actions can be done on a specific RFQ.
            Required params:
            - rfq_id

            NOTIFICATION TOOLS

            29. notification_summary
            Use when the user asks for notification overview.
            Params:
            - none

            30. unread_notifications
            Use when the user asks for unread notifications.
            Params:
            - none

            31. notifications_by_type
            Use when the user asks for notifications of a specific type.
            Required params:
            - type

            Examples:
            - stock alerts
            - RFQ notifications
            - order notifications

            Be careful: do not choose notifications_by_type just because the user says "stock" unless they specifically ask about notifications or alerts.

            ========================
            WRITE TOOL CATALOGUE
            ========================

            IMPORTANT:
            All write tools are preview-only.
            The backend will require explicit user confirmation before execution.
            You must never chain write actions.
            You must never return multi_source_read for write actions.

            1. set_stock_level
            Use when user asks to set/fix/adjust a component stock to a target level.
            Examples:
            - adjust component 11 stock to 140
            - fix component 11 stock so it becomes 140
            - set stock of component 11 to 140
            Required params:
            - component_id
            - target_stock
            Safe default:
            - reason = MANUAL_ADJUSTMENT if not specified

            2. record_stock_movement
            Use when user asks to record stock in/out.
            Examples:
            - record stock out for component 11 quantity 2
            - add stock in for component 11 quantity 5
            Required params:
            - component_id
            - type
            - quantity
            Safe default:
            - reason = MANUAL_ADJUSTMENT if not specified

            type must be:
            - in
            - out

            3. finalize_purchase_lot
            Use when user asks to finalize/receive a purchase lot.
            Required params:
            - purchase_lot_id
            Safe default costs:
            - transport_cost = 0
            - customs_cost = 0
            - handling_cost = 0
            - packaging_cost = 0
            - order_preparation_cost = 0
            - other_cost = 0

            4. update_pending_purchase_lot_costs
            Use only when there is already a pending finalize_purchase_lot action and user provides extra costs.
            Params may include:
            - transport_cost
            - customs_cost
            - handling_cost
            - packaging_cost
            - order_preparation_cost
            - other_cost

            If user says:
            "add transport cost 100 and customs cost 50"
            this is update_pending_purchase_lot_costs only if there is a pending purchase lot finalization.

            5. accept_rfq
            Use when user asks to accept one RFQ.
            Required params:
            - rfq_id

            6. reject_rfq
            Use when user asks to reject one RFQ.
            Required params:
            - rfq_id

            7. open_rfq
            Use when user asks to open one RFQ.
            Required params:
            - rfq_id

            8. expire_rfq
            Use when user asks to expire one RFQ.
            Required params:
            - rfq_id

            9. update_order_status
            Use when user asks to change one order status.
            Required params:
            - order_id
            - status

            Allowed statuses:
            - processing
            - shipped
            - delivered
            - cancelled

            10. mark_notification_read
            Use when user asks to mark one notification as read.
            Required params:
            - notification_id

            11. mark_all_notifications_read
            Use when user asks to mark all notifications as read.
            Params:
            - none

            ========================
            MULTI-SOURCE READ PLANNING
            ========================

            Use multi_source_read when the user asks one read-only question that clearly spans multiple backend areas.

            Never use multi_source_read for write actions.

            Allowed multi-source read examples:

            A. Product procurement analysis

            User:
            "For product 1, explain dependencies, cost rollup, stock risk, and procurement implications."

            Return:

            {
            "intent": "multi_source_read",
            "operation_type": "read_only",
            "confidence": "high",
            "tools": [
                {
                "intent": "owner_product_dependencies",
                "params": { "product_id": 1 }
                },
                {
                "intent": "cost_rollup",
                "params": { "product_id": 1 }
                },
                {
                "intent": "component_stock_analysis",
                "params": { "component_id": 1 }
                },
                {
                "intent": "purchase_lots_by_product",
                "params": { "product_id": 1 }
                }
            ],
            "reason": "The request asks for dependencies, cost structure, stock risk, and procurement implications."
            }

            B. Owner attention summary

            User:
            "What needs my attention today across stock, RFQs, and orders?"

            Return:

            {
            "intent": "multi_source_read",
            "operation_type": "read_only",
            "confidence": "high",
            "tools": [
                {
                "intent": "stock_intelligence_summary",
                "params": {}
                },
                {
                "intent": "rfq_summary",
                "params": {}
                },
                {
                "intent": "order_summary",
                "params": {}
                }
            ],
            "reason": "The request asks for a cross-module owner attention summary."
            }

            C. Product reorder and RFQ situation

            User:
            "For product 1, do we need to reorder it, are there RFQs related to it, and what is the stock risk?"

            Return:

            {
            "intent": "multi_source_read",
            "operation_type": "read_only",
            "confidence": "high",
            "tools": [
                {
                "intent": "reorder_recommendations",
                "params": {}
                },
                {
                "intent": "rfq_summary",
                "params": {}
                },
                {
                "intent": "component_stock_analysis",
                "params": { "component_id": 1 }
                }
            ],
            "reason": "The request asks for reorder, RFQ, and stock risk information."
            }

            D. Executive summary

            User:
            "Give me an executive summary of Rabbit: stock risks, reorder needs, RFQs, orders, and urgent actions."

            Return:

            {
            "intent": "multi_source_read",
            "operation_type": "read_only",
            "confidence": "high",
            "tools": [
                {
                "intent": "stock_intelligence_summary",
                "params": {}
                },
                {
                "intent": "reorder_recommendations",
                "params": {}
                },
                {
                "intent": "rfq_summary",
                "params": {}
                },
                {
                "intent": "order_summary",
                "params": {}
                }
            ],
            "reason": "The request asks for an executive summary across stock, reorder, RFQ, and order modules."
            }

            ========================
            SINGLE-TOOL EXAMPLES
            ========================

            User:
            "Show cost rollup for product 1"

            Return:

            {
            "intent": "cost_rollup",
            "operation_type": "read_only",
            "confidence": "high",
            "params": { "product_id": 1 },
            "reason": "User asks for one specific cost rollup."
            }

            User:
            "Show dependencies for product 1"

            Return:

            {
            "intent": "owner_product_dependencies",
            "operation_type": "read_only",
            "confidence": "high",
            "params": { "product_id": 1 },
            "reason": "User asks for product dependencies."
            }

            User:
            "What should I reorder soon and why?"

            Return:

            {
            "intent": "reorder_recommendations",
            "operation_type": "read_only",
            "confidence": "high",
            "params": {},
            "reason": "User asks for reorder recommendations."
            }

            User:
            "Which products need attention because of stock risk?"

            Return:

            {
            "intent": "stock_intelligence_summary",
            "operation_type": "read_only",
            "confidence": "high",
            "params": {},
            "reason": "User asks for stock risk and attention summary."
            }

            User:
            "Analyze component 11 and explain if its stock situation is healthy or risky."

            Return:

            {
            "intent": "component_stock_analysis",
            "operation_type": "read_only",
            "confidence": "high",
            "params": { "component_id": 11 },
            "reason": "User asks for stock analysis of a specific component."
            }

            User:
            "Give me an overview of RFQs and tell me which ones need owner attention."

            Return:

            {
            "intent": "rfq_summary",
            "operation_type": "read_only",
            "confidence": "high",
            "params": {},
            "reason": "User asks for RFQ overview and attention."
            }

            User:
            "Summarize order health and tell me which order statuses need attention."

            Return:

            {
            "intent": "order_summary",
            "operation_type": "read_only",
            "confidence": "high",
            "params": {},
            "reason": "User asks for order health overview."
            }

            ========================
            WRITE EXAMPLES
            ========================

            User:
            "Adjust component 11 stock to 140."

            Return:

            {
            "intent": "set_stock_level",
            "operation_type": "write_action",
            "confidence": "high",
            "params": {
                "component_id": 11,
                "target_stock": 140,
                "reason": "MANUAL_ADJUSTMENT"
            },
            "reason": "User asks to set a component stock level."
            }

            User:
            "Record stock out for component 11 quantity 2."

            Return:

            {
            "intent": "record_stock_movement",
            "operation_type": "write_action",
            "confidence": "high",
            "params": {
                "component_id": 11,
                "type": "out",
                "quantity": 2,
                "reason": "MANUAL_ADJUSTMENT"
            },
            "reason": "User asks to record a stock out movement."
            }

            User:
            "Finalize purchase lot 45."

            Return:

            {
            "intent": "finalize_purchase_lot",
            "operation_type": "write_action",
            "confidence": "high",
            "params": {
                "purchase_lot_id": 45,
                "transport_cost": 0,
                "customs_cost": 0,
                "handling_cost": 0,
                "packaging_cost": 0,
                "order_preparation_cost": 0,
                "other_cost": 0
            },
            "reason": "User asks to finalize a purchase lot."
            }

            User:
            "Accept RFQ 13 and then finalize its purchase lot with transport cost 100."

            Return:

            {
            "intent": "multiple_write_actions_detected",
            "operation_type": "write_action",
            "confidence": "high",
            "params": {},
            "reason": "The user requested more than one write action in a single message."
            }

            ========================
            UNSUPPORTED / SAFE FALLBACK EXAMPLES
            ========================

            User:
            "Which supplier should I trust most for hydraulic components and why?"

            If there is no supplier performance/reliability tool available, return:

            {
            "intent": "unsupported",
            "operation_type": "unsupported",
            "confidence": "low",
            "params": {},
            "reason": "Supplier trust ranking requires supplier performance data that is not exposed by available tools."
            }

            User:
            "Predict supplier corruption risk and next year revenue."

            Return:

            {
            "intent": "unsupported",
            "operation_type": "unsupported",
            "confidence": "low",
            "params": {},
            "reason": "The request requires unsupported prediction data and must not be invented."
            }

            ========================
            FINAL VALIDATION BEFORE OUTPUT
            ========================

            Before returning JSON, check:

            1. Is the request read-only or write?
            2. Does the selected intent exist in the available tool list?
            3. Are required IDs present?
            4. Did the user ask for multiple read areas? If yes, use multi_source_read.
            5. Did the user ask for multiple write actions? If yes, return multiple_write_actions_detected.
            6. Are safe defaults needed for write params?
            7. Are you accidentally answering the user? If yes, stop and return only routing JSON.
            8. Are you inventing missing data? If yes, stop and return unsupported.
            9. Is the JSON valid? Return only valid JSON.

            User message:
            {$message}
                        
            PROMPT;
    }

    private function parseJson(string $raw): ?array
    {
        $raw = trim($raw);

        $decoded = json_decode($raw, true);

        if (is_array($decoded)) {
            return $decoded;
        }

        if (preg_match('/\{.*\}/s', $raw, $matches) === 1) {
            $decoded = json_decode($matches[0], true);

            return is_array($decoded) ? $decoded : null;
        }

        return null;
    }

    private function validateProposal(array $proposal): array
    {
        $intent = trim((string) ($proposal['intent'] ?? ''));
        $operationType = trim((string) ($proposal['operation_type'] ?? 'unsupported'));
        $confidence = trim((string) ($proposal['confidence'] ?? 'low'));
        $params = $proposal['params'] ?? [];

        if (!is_array($params)) {
            return $this->reject('params_not_object');
        }

        if ($intent === '' || in_array($intent, ['unsupported', 'ambiguous', 'multiple_write_actions_detected'], true)) {
            return $this->reject($intent !== '' ? $intent : 'empty_intent', [
                'reason' => $proposal['reason'] ?? null,
            ]);
        }

        if ($intent === 'multi_source_read') {
            return $this->validateMultiSourceReadProposal($proposal);
        }

        if (!in_array($operationType, ['read_only', 'write_action'], true)) {
            return $this->reject('invalid_operation_type');
        }

        if (!in_array($confidence, ['low', 'medium', 'high'], true)) {
            $confidence = 'low';
        }

        if ($operationType === 'read_only' && !in_array($intent, $this->allowedReadIntents(), true)) {
            return $this->reject('read_intent_not_allowed', [
                'intent' => $intent,
            ]);
        }

        if ($operationType === 'write_action' && !in_array($intent, $this->allowedWriteIntents(), true)) {
            return $this->reject('write_intent_not_allowed', [
                'intent' => $intent,
            ]);
        }
        $params = $this->applySafeDefaults($intent, $params);
        $requiredError = $this->validateRequiredParams($intent, $params);

        if ($requiredError !== null) {
            return $this->reject($requiredError, [
                'intent' => $intent,
                'params' => $params,
            ]);
        }

        return [
            'matched' => true,
            'intent' => $intent,
            'operation_type' => $operationType,
            'confidence' => $confidence,
            'params' => $this->sanitizeParams($params),
            'reason' => $proposal['reason'] ?? null,
            'source' => 'ai_intent_router',
        ];
    }

    private function validateRequiredParams(string $intent, array $params): ?string
    {
        $required = [
            'component_stock_analysis' => ['component_id'],
            'product_details' => [],
            'order_details' => ['order_id'],
            'rfq_details' => ['rfq_id'],
            'purchase_lot_details' => ['purchase_lot_id'],

            'mark_notification_read' => ['notification_id'],
            'mark_all_notifications_read' => [],
            'open_rfq' => ['rfq_id'],
            'expire_rfq' => ['rfq_id'],
            'reject_rfq' => ['rfq_id'],
            'accept_rfq' => ['rfq_id'],
            'update_order_status' => ['order_id', 'status'],
            'set_stock_level' => ['component_id', 'target_stock'],
            'record_stock_movement' => ['component_id', 'type', 'quantity'],
            'finalize_purchase_lot' => ['purchase_lot_id'],
            'update_pending_purchase_lot_costs' => [],
        ];

        foreach ($required[$intent] ?? [] as $field) {
            if (!array_key_exists($field, $params) || $params[$field] === null || $params[$field] === '') {
                return 'missing_required_param_' . $field;
            }
        }

        if ($intent === 'product_details') {
            if (
                empty($params['product_id'])
                && empty($params['component_id'])
            ) {
                return 'missing_required_param_product_id_or_component_id';
            }
        }

        return null;
    }

    private function sanitizeParams(array $params): array
    {
        $clean = [];

        foreach ($params as $key => $value) {
            $key = trim((string) $key);

            if ($key === '') {
                continue;
            }

            if (is_string($value)) {
                $value = trim($value);
            }

            $clean[$key] = $value;
        }

        return $clean;
    }

    private function reject(string $reason, array $extra = []): array
    {
        return array_merge([
            'matched' => false,
            'intent' => 'unsupported',
            'operation_type' => 'unsupported',
            'confidence' => 'none',
            'params' => [],
            'reason' => $reason,
            'source' => 'ai_intent_router',
        ], $extra);
    }

    private function allowedReadIntents(): array
    {
        return [
            // Multi-source eligible product/procurement tools
            'owner_product_details',
            'owner_product_dependencies',
            'cost_rollup',
            'component_stock_analysis',
            'purchase_lots_by_product',

            // Stock / inventory intelligence
            'inventory_alerts',
            'inventory_summary',
            'stock_intelligence_summary',
            'stock_intelligence_explanation',
            'stock_intelligence_dashboard_explanation',
            'reorder_recommendations',
            'dashboard_stock_summary',

            // Orders
            'order_summary',
            'order_details',
            'orders_by_status',
            'recent_orders',

            // RFQs
            'rfq_summary',
            'rfq_details',
            'rfqs_by_status',
            'rfq_allowed_actions',

            // Notifications
            'notification_summary',
            'unread_notifications',
            'notifications_by_type',

            // Public/catalogue read tools
            'catalog_search',
            'catalog_product_details',
            'catalog_product_relations',
            'catalog_product_promotions',
            'catalog_product_reviews',
            'catalog_product_rating_summary',
            'active_promotions',
        ];
    }

    private function allowedWriteIntents(): array
    {
        return [
            'mark_notification_read',
            'mark_all_notifications_read',
            'open_rfq',
            'expire_rfq',
            'reject_rfq',
            'accept_rfq',
            'update_order_status',
            'record_stock_movement',
            'set_stock_level',
            'finalize_purchase_lot',
            'update_pending_purchase_lot_costs',
        ];
    }

    private function applySafeDefaults(string $intent, array $params): array
    {
        if ($intent === 'set_stock_level') {
            $params['reason'] = $params['reason'] ?? 'MANUAL_ADJUSTMENT';
        }

        if ($intent === 'record_stock_movement') {
            $params['reason'] = $params['reason'] ?? 'MANUAL_ADJUSTMENT';
        }

        if ($intent === 'finalize_purchase_lot') {
            $params['transport_cost'] = $params['transport_cost'] ?? 0;
            $params['customs_cost'] = $params['customs_cost'] ?? 0;
            $params['handling_cost'] = $params['handling_cost'] ?? 0;
            $params['packaging_cost'] = $params['packaging_cost'] ?? 0;
            $params['order_preparation_cost'] = $params['order_preparation_cost'] ?? 0;
            $params['other_cost'] = $params['other_cost'] ?? 0;
        }

        return $params;
    }

    private function validateMultiSourceReadProposal(array $proposal): array
    {
        $operationType = trim((string) ($proposal['operation_type'] ?? 'unsupported'));
        $confidence = trim((string) ($proposal['confidence'] ?? 'medium'));
        $tools = $proposal['tools'] ?? [];

        if ($operationType !== 'read_only') {
            return $this->reject('multi_source_must_be_read_only');
        }

        if (!is_array($tools) || count($tools) === 0) {
            return $this->reject('multi_source_tools_empty');
        }

        if (count($tools) > 5) {
            return $this->reject('multi_source_too_many_tools', [
                'tool_count' => count($tools),
            ]);
        }

        $cleanTools = [];

        foreach ($tools as $index => $tool) {
            if (!is_array($tool)) {
                return $this->reject('multi_source_invalid_tool_item', [
                    'index' => $index,
                ]);
            }

            $toolIntent = trim((string) ($tool['intent'] ?? ''));
            $params = $tool['params'] ?? [];

            if ($toolIntent === '') {
                return $this->reject('multi_source_missing_tool_intent', [
                    'index' => $index,
                ]);
            }

            if (!is_array($params)) {
                return $this->reject('multi_source_params_not_object', [
                    'tool_intent' => $toolIntent,
                ]);
            }

            if (!in_array($toolIntent, $this->allowedReadIntents(), true)) {
                return $this->reject('multi_source_read_intent_not_allowed', [
                    'tool_intent' => $toolIntent,
                ]);
            }

            $requiredError = $this->validateRequiredParams($toolIntent, $params);

            if ($requiredError !== null) {
                return $this->reject($requiredError, [
                    'tool_intent' => $toolIntent,
                    'params' => $params,
                ]);
            }

            $cleanTools[] = [
                'intent' => $toolIntent,
                'params' => $this->sanitizeParams($params),
            ];
        }

        return [
            'matched' => true,
            'intent' => 'multi_source_read',
            'operation_type' => 'read_only',
            'confidence' => in_array($confidence, ['low', 'medium', 'high'], true)
                ? $confidence
                : 'medium',
            'params' => [],
            'tools' => $cleanTools,
            'reason' => $proposal['reason'] ?? null,
            'source' => 'ai_intent_router',
        ];
    }
}