<?php
/**
 * HYBRID INTENT PARSER v3 (GROQ)
 * 
 * Architecture:
 * 1. Rule-based parser runs first (fast, low cost)
 * 2. If confidence < 0.55 → call Groq LLM (accurate fallback)
 * 3. LLM output strictly validated
 * 4. Backend executes mapped action (NOT LLM)
 * 
 * Key principle: LLM interprets, PHP backend executes
 */

header('Content-Type: application/json');

// ============================================================================
// LOGGING SETUP
// ============================================================================

$logFile = 'C:\xampp\htdocs\rabbit-pfa-platform\backend\api\chat\tmp\chat_hybrid.log';

// Create log file if it doesn't exist
if (!file_exists($logFile)) {
    $fh = fopen($logFile, 'w');
    if ($fh) {
        fwrite($fh, "[HYBRID] Log file created at " . date('Y-m-d H:i:s') . "\n");
        fclose($fh);
    }
}

// Make sure it's writable
if (file_exists($logFile)) {
    chmod($logFile, 0666);
}

ini_set('log_errors', 1);
ini_set('error_log', $logFile);

require_once __DIR__ . '/../../includes/db.php';

// ============================================================================
// CONSTANTS & CONFIG
// ============================================================================

const CONFIDENCE_THRESHOLD = 0.55;  // Below this → use LLM fallback

// ============================================================================
// GROQ API CONFIGURATION
// ============================================================================

const USE_GROQ = true;
const GROQ_API_KEY = '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';  // Get from https://console.groq.com/keys
const GROQ_API_URL = 'https://api.groq.com/openai/v1/chat/completions';
const GROQ_MODEL = 'openai/gpt-oss-120b';  // Fast and accurate, free tier available

const ALLOWED_INTENTS = ['navigation', 'product_search', 'supplier_query', 'low_stock_query', 'unknown'];
const ALLOWED_LANGUAGES = ['en', 'fr'];
const ALLOWED_ROUTES = ['catalogue', 'stock', 'orders', 'rfq', 'suppliers', 'chat', 'none'];
const ALLOWED_CATEGORIES = ['assembly', 'sub_assembly', 'component', 'raw_material', null];

// ============================================================================
// UTILITIES
// ============================================================================

function sendJson(int $statusCode, array $payload): void
{
    http_response_code($statusCode);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

function getRequestData(): array
{
    $raw = file_get_contents('php://input');
    $json = json_decode($raw, true);

    if (json_last_error() === JSON_ERROR_NONE && is_array($json)) {
        return $json;
    }

    if (!empty($_POST)) {
        return $_POST;
    }

    return [];
}

function debugLog(string $message): void
{
    error_log("[HYBRID] " . $message);
}

function normalizeText(string $text): string
{
    $text = trim(mb_strtolower($text, 'UTF-8'));
    $text = preg_replace('/\s+/', ' ', $text);
    $text = preg_replace('/([!?.])\1+/', '$1', $text);
    return $text;
}

function detectLanguage(string $message): string
{
    $frenchIndicators = [
        'le', 'la', 'les', 'de', 'des', 'un', 'une', 'du', 'et', 'ou', 'ne',
        'cherche', 'chercher', 'cherchent', 'trouve', 'trouver', 'trouvent',
        'produit', 'produits', 'fournisseur', 'fournisseurs',
        'devis', 'commande', 'commandes', 'rupture', 'stock', 'faible',
        'ouvre', 'ouvrir', 'affiche', 'afficher', 'montre', 'montrer',
        'bonjour', 'salut', 'merci', 'svp', 'aidez', 'page', 'section'
    ];

    $englishIndicators = [
        'the', 'and', 'or', 'a', 'an', 'to', 'for', 'of', 'in', 'is', 'are',
        'find', 'search', 'look', 'show', 'get', 'fetch', 'retrieve',
        'product', 'products', 'supplier', 'suppliers', 'vendor', 'vendors',
        'order', 'orders', 'quote', 'rfq', 'inventory', 'stock',
        'open', 'go', 'navigate', 'page', 'help', 'assist'
    ];

    $frenchScore = 0;
    $englishScore = 0;

    foreach ($frenchIndicators as $indicator) {
        if (mb_strpos($message, $indicator) !== false) {
            $frenchScore += 2;
        }
    }

    foreach ($englishIndicators as $indicator) {
        if (mb_strpos($message, $indicator) !== false) {
            $englishScore += 2;
        }
    }

    if (preg_match('/[éèêëàâäùûüôöœæç]/u', $message)) {
        $frenchScore += 5;
    }

    return $frenchScore > $englishScore ? 'fr' : 'en';
}

function localize(string $language, string $fr, string $en): string
{
    return $language === 'fr' ? $fr : $en;
}

function extractSearchTerm(string $message): string
{
    $englishPatterns = [
        '/(?:find|search|looking for|look for|show me|get)\s+(?:a|an|the)?\s*(.+?)(?:\?|$)/iu',
        '/(?:find|search)\s+(?:for\s+)?(.+?)(?:\?|$)/iu',
    ];

    $frenchPatterns = [
        '/(?:cherche|chercher|trouve|trouver|cherches|je cherche|je voudrais)\s+(?:une?|le|la|les|des)?\s*(.+?)(?:\?|$)/iu',
        '/(?:montre|montre-moi|affiche|afficher)\s+(?:une?|le|la|les|des)?\s*(.+?)(?:\?|$)/iu',
    ];

    foreach ($englishPatterns as $pattern) {
        if (preg_match($pattern, $message, $matches)) {
            $term = trim($matches[1] ?? '');
            if (!empty($term)) {
                return cleanSearchTerm($term);
            }
        }
    }

    foreach ($frenchPatterns as $pattern) {
        if (preg_match($pattern, $message, $matches)) {
            $term = trim($matches[1] ?? '');
            if (!empty($term)) {
                return cleanSearchTerm($term);
            }
        }
    }

    $stopwords = [
        'the', 'a', 'an', 'please', 'thanks', 'pls', 'svp', 'stp',
        'le', 'la', 'les', 'et', 'ou', 'pas', 'aucun', 'aucune',
        'je', 'veux', 'voudrais', 'cherche', 'trouve', 'montre', 'affiche',
        'find', 'search', 'get', 'show', 'want', 'need'
    ];

    $words = preg_split('/\s+/', mb_strtolower($message, 'UTF-8'));
    $filtered = array_filter($words, function($word) use ($stopwords) {
        return !in_array($word, $stopwords, true) && !empty($word);
    });

    $result = implode(' ', array_values($filtered));
    return cleanSearchTerm($result);
}

function cleanSearchTerm(string $term): string
{
    $term = preg_replace('/\s+/', ' ', trim($term));
    
    $stopwords = ['the', 'a', 'an', 'le', 'la', 'les', 'de', 'en', 'à', 'dans', 'pas', 'aucun', 'aucune', 'très'];
    
    do {
        $changed = false;
        $words = preg_split('/\s+/', $term);
        
        if (!empty($words) && in_array(mb_strtolower($words[0]), $stopwords, true)) {
            array_shift($words);
            $changed = true;
        }
        
        if (!empty($words) && in_array(mb_strtolower($words[count($words) - 1]), $stopwords, true)) {
            array_pop($words);
            $changed = true;
        }
        
        $term = implode(' ', $words);
    } while ($changed && !empty($term));

    if (mb_strlen($term) > 100) {
        $term = mb_substr($term, 0, 100);
        $lastSpace = mb_strrpos($term, ' ');
        if ($lastSpace !== false) {
            $term = mb_substr($term, 0, $lastSpace);
        }
    }

    return trim($term);
}

// ============================================================================
// RULE-BASED INTENT CLASSIFICATION
// ============================================================================

function classifyIntentImproved(string $message, string $language): array
{
    $normalized = mb_strtolower($message, 'UTF-8');

    $navigationKeywords = [
        'open', 'go', 'go to', 'show', 'show me', 'navigate', 'navigate to',
        'take me to', 'page', 'section',
        'ouvre', 'ouvrir', 'aller', 'aller à', 'aller vers', 'affiche',
        'afficher', 'montre', 'montrer', 'emmène', 'section', 'page'
    ];

    $productSearchKeywords = [
        'find', 'search', 'looking for', 'look for', 'show me',
        'where', 'get', 'fetch', 'retrieve', 'product', 'component',
        'cherche', 'chercher', 'trouve', 'trouver', 'cherches', 'je cherche',
        'je voudrais', 'montre', 'affiche', 'produit', 'composant', 'pompe',
        'pièce', 'matériel', 'équipement'
    ];

    $supplierKeywords = [
        'supplier', 'vendor', 'vendors', 'suppliers', 'provide',
        'fournisseur', 'fournisseurs', 'vendeur', 'vendeurs', 'prestataire',
        'prestataires'
    ];

    $lowStockKeywords = [
        'low stock', 'stock low', 'out of stock', 'stock alert', 'inventory',
        'low', 'stock', 'alert', 'out', 'rupture',
        'stock faible', 'stock bas', 'alerte stock', 'rupture', 'en rupture',
        'stock', 'faible', 'bas', 'inventaire'
    ];

    $scores = [
        'navigation' => 0,
        'product_search' => 0,
        'supplier_query' => 0,
        'low_stock_query' => 0,
    ];

    foreach ($navigationKeywords as $keyword) {
        if (preg_match('/\b' . preg_quote($keyword) . '\b/u', $normalized)) {
            $scores['navigation'] += 2;
        }
    }

    foreach ($productSearchKeywords as $keyword) {
        if (preg_match('/\b' . preg_quote($keyword) . '\b/u', $normalized)) {
            $scores['product_search'] += 2;
        }
    }

    if (preg_match('/(?:pompe|pièce|composant|équipement|matériel|component|part|piece)/u', $normalized)) {
        $scores['product_search'] += 3;
    }

    foreach ($supplierKeywords as $keyword) {
        if (preg_match('/\b' . preg_quote($keyword) . '\b/u', $normalized)) {
            $scores['supplier_query'] += 3;
        }
    }

    foreach ($lowStockKeywords as $keyword) {
        if (preg_match('/\b' . preg_quote($keyword) . '\b/u', $normalized)) {
            $scores['low_stock_query'] += 2;
        }
    }

    arsort($scores);
    $maxScore = reset($scores);
    $topIntent = key($scores);

    $totalScore = array_sum(array_values($scores));
    $confidence = $totalScore > 0 ? round($maxScore / $totalScore, 2) : 0.0;

    // PENALTY: Reduce confidence for complex/messy queries
    if (mb_strlen($message) > 80) {
        $confidence *= 0.7;
        debugLog("Penalty: long message (" . mb_strlen($message) . " chars) → confidence *= 0.7");
    }

    $constraintKeywords = ['pas', 'moins', 'plus', 'cher', 'expensive', 'budget', 'fiable', 'reliable', 'qualité', 'quality', 'couleur', 'color', 'taille', 'size'];
    $constraintCount = 0;
    foreach ($constraintKeywords as $keyword) {
        if (preg_match('/\b' . preg_quote($keyword) . '\b/u', $normalized)) {
            $constraintCount++;
        }
    }
    if ($constraintCount >= 2) {
        $confidence *= 0.65;
        debugLog("Penalty: $constraintCount constraints detected → confidence *= 0.65");
    }

    $actionVerbs = [
        'find', 'search', 'show', 'get', 'need', 'want', 'look',
        'cherche', 'chercher', 'trouve', 'trouver', 'voudrais', 'besoin',
        'affiche', 'afficher', 'montre', 'montrer', 'open', 'ouvre', 'ouvrir'
    ];
    $hasActionVerb = false;
    foreach ($actionVerbs as $verb) {
        if (preg_match('/\b' . preg_quote($verb) . '\b/u', $normalized)) {
            $hasActionVerb = true;
            break;
        }
    }
    if (!$hasActionVerb && mb_strlen($message) > 40) {
        $confidence *= 0.6;
        debugLog("Penalty: no action verb + long message → confidence *= 0.6");
    }

    $confidence = max(0.0, min(0.95, $confidence));

    if ($maxScore === 0) {
        return ['intent' => 'unknown', 'confidence' => 0.0, 'scores' => $scores];
    }

    return ['intent' => $topIntent, 'confidence' => $confidence, 'scores' => $scores];
}

function detectRoute(string $message): ?string
{
    $normalized = mb_strtolower($message, 'UTF-8');

    $routeMap = [
        'rfq' => 'owner.html#rfq',
        'devis' => 'owner.html#rfq',
        'quote' => 'owner.html#rfq',
        'quotes' => 'owner.html#rfq',
        'stock' => 'owner.html#stock',
        'inventory' => 'owner.html#stock',
        'inventaire' => 'owner.html#stock',
        'order' => 'owner.html#orders',
        'orders' => 'owner.html#orders',
        'commande' => 'owner.html#orders',
        'commandes' => 'owner.html#orders',
        'supplier' => 'supplier.html',
        'suppliers' => 'supplier.html',
        'fournisseur' => 'supplier.html',
        'fournisseurs' => 'supplier.html',
        'vendor' => 'supplier.html',
        'vendors' => 'supplier.html',
        'catalogue' => 'client.html#catalogue',
        'catalog' => 'client.html#catalogue',
        'product' => 'client.html#catalogue',
        'products' => 'client.html#catalogue',
        'produit' => 'client.html#catalogue',
        'produits' => 'client.html#catalogue',
        'component' => 'client.html#catalogue',
        'components' => 'client.html#catalogue',
        'chat' => 'client.html#chat',
        'help' => 'client.html#chat',
        'aide' => 'client.html#chat',
        'support' => 'client.html#chat',
    ];

    foreach ($routeMap as $keyword => $route) {
        if (preg_match('/\b' . preg_quote($keyword) . '\b/u', $normalized)) {
            return $route;
        }
    }

    return null;
}

// ============================================================================
// DATABASE QUERIES
// ============================================================================

function searchProducts(PDO $pdo, string $term, int $limit = 5): array
{
    if (empty(trim($term))) {
        return [];
    }

    $sql = "
        SELECT
            id, name, sku, category, unit_of_measure,
            stock_qty, low_stock_threshold
        FROM components
        WHERE is_active = 1
          AND (name LIKE :term OR sku LIKE :term OR category LIKE :term)
        ORDER BY
            CASE WHEN name LIKE :starts_with THEN 0 ELSE 1 END,
            CASE WHEN stock_qty > low_stock_threshold THEN 0 ELSE 1 END,
            name ASC
        LIMIT :limit
    ";

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':term', '%' . $term . '%');
        $stmt->bindValue(':starts_with', $term . '%');
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as &$row) {
            $row['id'] = (int) $row['id'];
            $row['stock_qty'] = (float) $row['stock_qty'];
            $row['low_stock_threshold'] = (float) $row['low_stock_threshold'];
            $row['stock_status'] = $row['stock_qty'] <= 0
                ? 'out'
                : ($row['stock_qty'] <= $row['low_stock_threshold'] ? 'low' : 'ok');
        }
        unset($row);

        return $rows;
    } catch (PDOException $e) {
        debugLog("searchProducts error: " . $e->getMessage());
        return [];
    }
}

function getLowStockProducts(PDO $pdo, int $limit = 10): array
{
    $sql = "
        SELECT
            id, name, sku, category, unit_of_measure,
            stock_qty, low_stock_threshold
        FROM components
        WHERE is_active = 1 AND stock_qty <= low_stock_threshold
        ORDER BY stock_qty ASC, name ASC
        LIMIT :limit
    ";

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as &$row) {
            $row['id'] = (int) $row['id'];
            $row['stock_qty'] = (float) $row['stock_qty'];
            $row['low_stock_threshold'] = (float) $row['low_stock_threshold'];
            $row['stock_status'] = $row['stock_qty'] <= 0 ? 'out' : 'low';
        }
        unset($row);

        return $rows;
    } catch (PDOException $e) {
        debugLog("getLowStockProducts error: " . $e->getMessage());
        return [];
    }
}

function searchSuppliers(PDO $pdo, string $term, int $limit = 5): array
{
    if (empty(trim($term))) {
        return [];
    }

    $sql = "
        SELECT id, name, country, email, phone, rating, is_active
        FROM suppliers
        WHERE is_active = 1
          AND (name LIKE :term OR country LIKE :term OR email LIKE :term)
        ORDER BY
            CASE WHEN name LIKE :starts_with THEN 0 ELSE 1 END,
            rating DESC,
            name ASC
        LIMIT :limit
    ";

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':term', '%' . $term . '%');
        $stmt->bindValue(':starts_with', $term . '%');
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as &$row) {
            $row['id'] = (int) $row['id'];
            $row['is_active'] = (bool) $row['is_active'];
            $row['rating'] = $row['rating'] !== null ? (float) $row['rating'] : null;
        }
        unset($row);

        return $rows;
    } catch (PDOException $e) {
        debugLog("searchSuppliers error: " . $e->getMessage());
        return [];
    }
}

// ============================================================================
// GROQ LLM INTEGRATION
// ============================================================================

function callLlmParser(string $message): ?array
{
    debugLog("LLM FALLBACK TRIGGERED for: $message");

    if (!USE_GROQ) {
        debugLog("Groq is disabled");
        return null;
    }

    return callGroqApi($message);
}

function callGroqApi(string $message): ?array
{
    debugLog("Calling Groq API...");

    $apiKey = GROQ_API_KEY;
    if ($apiKey === 'gsk_YOUR_KEY_HERE' || empty($apiKey)) {
        debugLog("Groq API key not configured");
        return null;
    }

    $systemPrompt = <<<'PROMPT'
You are a B2B ecommerce intent classifier. Your job is to analyze user messages and return ONLY a valid JSON object with NO additional text, NO markdown, NO explanation.

The user message will be in English or French and may be messy or informal.

You MUST respond with ONLY this JSON structure, nothing else:
{
  "intent": "navigation|product_search|supplier_query|low_stock_query|unknown",
  "language": "en|fr",
  "confidence": 0.0-1.0,
  "route_target": "catalogue|stock|orders|rfq|suppliers|chat|none",
  "search_term": "string or empty",
  "reply": "user-facing message",
  "filters": {
    "category": null,
    "min_price": null,
    "max_price": null,
    "min_supplier_rating": null
  }
}

IMPORTANT RULES:
- Return ONLY valid JSON, no other text
- intent must be one of: navigation, product_search, supplier_query, low_stock_query, unknown
- language must be 'en' or 'fr'
- confidence must be a number between 0.0 and 1.0
- route_target must be one of: catalogue, stock, orders, rfq, suppliers, chat, none
_ category must map to one of: assembly, sub_assembly, component, raw_material
- search_term must be a clean, short string (max 50 chars) or empty string
- reply must be a helpful message in the same language as the user
- filters are optional but must be in the JSON structure
PROMPT;

    $userMessage = "Analyze this message and return ONLY JSON: " . $message;

    $payload = json_encode([
        'model' => GROQ_MODEL,
        'messages' => [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $userMessage]
        ],
        'temperature' => 0.1,  // Low temperature for consistent JSON output
        'max_tokens' => 500,
        'response_format' => ['type' => 'json_object']  // Force JSON output
    ], JSON_UNESCAPED_UNICODE);

    debugLog("Groq payload: " . substr($payload, 0, 300));

    $ch = curl_init(GROQ_API_URL);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $apiKey,
            'Content-Type: application/json'
        ],
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_CONNECTTIMEOUT => 10
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    debugLog("Groq HTTP code: " . $httpCode);
    debugLog("Groq raw response: " . substr($response, 0, 500));

    if ($response === false || !empty($curlError)) {
        debugLog("Groq curl error: " . $curlError);
        return null;
    }

    if ($httpCode !== 200) {
        debugLog("Groq API error: HTTP $httpCode");
        return null;
    }

    $decoded = json_decode($response, true);
    if (!is_array($decoded)) {
        debugLog("Groq response not JSON");
        return null;
    }

    // Standard OpenAI format: choices[0].message.content
    if (!isset($decoded['choices'][0]['message']['content'])) {
        debugLog("Groq response missing choices/message/content structure");
        return null;
    }

    $content = $decoded['choices'][0]['message']['content'];
    debugLog("Groq content: " . substr($content, 0, 300));

    // Parse JSON from content
    $parsed = json_decode($content, true);
    if (is_array($parsed)) {
        debugLog("Groq parsed JSON: " . json_encode($parsed));
        return $parsed;
    }

    // Fallback: try regex extraction if response has extra text
    if (preg_match('/\{.*\}/s', $content, $matches)) {
        $parsed = json_decode($matches[0], true);
        if (is_array($parsed)) {
            debugLog("Groq extracted JSON via regex: " . json_encode($parsed));
            return $parsed;
        }
    }

    debugLog("Groq failed to parse JSON from content");
    return null;
}

function validateLlmOutput(array $data): ?array
{
    debugLog("Validating LLM output...");

    if (!isset($data['intent']) || !in_array($data['intent'], ALLOWED_INTENTS, true)) {
        debugLog("LLM validation failed: invalid intent = " . ($data['intent'] ?? 'missing'));
        return null;
    }

    if (!isset($data['language']) || !in_array($data['language'], ALLOWED_LANGUAGES, true)) {
        debugLog("LLM validation failed: invalid language = " . ($data['language'] ?? 'missing'));
        return null;
    }

    if (!isset($data['route_target']) || !in_array($data['route_target'], ALLOWED_ROUTES, true)) {
        debugLog("LLM validation failed: invalid route_target = " . ($data['route_target'] ?? 'missing'));
        return null;
    }

    if (!array_key_exists('search_term', $data)) {
        debugLog("LLM validation failed: missing search_term");
        return null;
    }

    if (!isset($data['filters']) || !is_array($data['filters'])) {
        debugLog("LLM validation failed: invalid filters");
        return null;
    }

    $filters = $data['filters'];
    if (!array_key_exists('category', $filters) || !in_array($filters['category'], ALLOWED_CATEGORIES, true)) {
        debugLog("LLM validation failed: invalid category = " . ($filters['category'] ?? 'missing'));
        return null;
    }

    foreach (['min_supplier_rating', 'min_price', 'max_price'] as $field) {
        if (isset($filters[$field]) && $filters[$field] !== null && !is_numeric($filters[$field])) {
            debugLog("LLM validation failed: non-numeric $field = " . $filters[$field]);
            return null;
        }
    }

    debugLog("LLM output validated successfully");
    return $data;
}

function mapRouteTarget(string $routeTarget): ?string
{
    return match ($routeTarget) {
        'catalogue' => 'client.html#catalogue',
        'stock' => 'owner.html#stock',
        'orders' => 'owner.html#orders',
        'rfq' => 'owner.html#rfq',
        'suppliers' => 'supplier.html',
        'chat' => 'client.html#chat',
        'none' => null,
        default => null,
    };
}

function executeLlmParsedIntent(array $parsed, PDO $pdo): void
{
    $intent = $parsed['intent'];
    $language = $parsed['language'];
    $confidence = isset($parsed['confidence']) ? (float) $parsed['confidence'] : 0.5;
    $reply = $parsed['reply'] ?? null;
    $filters = $parsed['filters'] ?? [];
    $searchTerm = isset($parsed['search_term']) ? trim((string) $parsed['search_term']) : '';

    debugLog("Executing LLM intent: $intent with search_term: $searchTerm");

    if ($intent === 'navigation') {
        $route = mapRouteTarget($parsed['route_target'] ?? 'none');

        sendJson(200, [
            'intent' => 'navigation',
            'language' => $language,
            'confidence' => $confidence,
            'source' => 'llm',
            'message' => $reply ?? localize($language, 'Ouverture de la section demandée.', 'Opening the requested section.'),
            'route' => $route
        ]);
    }

    if ($intent === 'low_stock_query') {
        $results = getLowStockProducts($pdo);

        sendJson(200, [
            'intent' => 'low_stock_query',
            'language' => $language,
            'confidence' => $confidence,
            'source' => 'llm',
            'message' => $reply ?? localize($language, 'Voici les produits en stock faible.', 'Here are the low-stock products.'),
            'results' => $results,
            'result_count' => count($results)
        ]);
    }

    if ($intent === 'supplier_query') {
        $term = $searchTerm !== '' ? $searchTerm : ($filters['supplier_name'] ?? '');
        $results = $term !== '' ? searchSuppliers($pdo, $term) : [];

        sendJson(200, [
            'intent' => 'supplier_query',
            'language' => $language,
            'confidence' => $confidence,
            'source' => 'llm',
            'search_term' => $term,
            'message' => $reply ?? localize($language, 'Voici les fournisseurs correspondants.', 'Here are the matching suppliers.'),
            'results' => $results,
            'result_count' => count($results)
        ]);
    }

    if ($intent === 'product_search') {
        $term = $searchTerm;
        if ($term === '' && isset($filters['category']) && $filters['category'] !== null) {
            $term = (string) $filters['category'];
        }

        $results = $term !== '' ? searchProducts($pdo, $term) : [];

        sendJson(200, [
            'intent' => 'product_search',
            'language' => $language,
            'confidence' => $confidence,
            'source' => 'llm',
            'search_term' => $term,
            'message' => $reply ?? localize($language, 'Voici les produits correspondants.', 'Here are the matching products.'),
            'results' => $results,
            'result_count' => count($results),
            'filters' => $filters
        ]);
    }

    sendJson(200, [
        'intent' => 'unknown',
        'language' => $language,
        'confidence' => $confidence,
        'source' => 'llm',
        'message' => $reply ?? localize($language, "Je n'ai pas bien compris votre demande.", "I did not fully understand your request.")
    ]);
}

// ============================================================================
// MAIN HANDLER
// ============================================================================

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendJson(405, ['error' => 'Method not allowed']);
    }

    $data = getRequestData();
    $message = isset($data['message']) ? trim((string) $data['message']) : '';

    if ($message === '') {
        sendJson(400, ['error' => 'Message is required']);
    }

    $language = detectLanguage($message);
    $normalizedMessage = normalizeText($message);

    debugLog("User message: $message");
    debugLog("Language: $language");

    // Run rule-based classifier first
    $classificationResult = classifyIntentImproved($normalizedMessage, $language);
    $intent = $classificationResult['intent'];
    $confidence = $classificationResult['confidence'];

    debugLog("Rules: intent=$intent confidence=$confidence");

    // CRITICAL: Check fallback threshold BEFORE executing rule handlers
    if ($confidence < CONFIDENCE_THRESHOLD) {
        debugLog("Confidence $confidence < threshold " . CONFIDENCE_THRESHOLD . " → using LLM fallback");

        $llmParsed = callLlmParser($message);
        if ($llmParsed !== null) {
            $llmValidated = validateLlmOutput($llmParsed);
            if ($llmValidated !== null) {
                debugLog("LLM validated, executing...");
                executeLlmParsedIntent($llmValidated, $pdo);
                // executeLlmParsedIntent calls sendJson and exits
            }
        }

        debugLog("LLM fallback failed, continuing with rule-based unknown handler");
        // If LLM fails, fall through to rule-based unknown handler below
    }

    // ========================================================================
    // RULE-BASED HANDLERS (only if confidence >= threshold)
    // ========================================================================

    if ($intent === 'navigation') {
        $route = detectRoute($normalizedMessage);

        if ($route === null) {
            sendJson(200, [
                'intent' => 'navigation',
                'language' => $language,
                'confidence' => $confidence,
                'source' => 'rules',
                'message' => localize(
                    $language,
                    "Je n'ai pas compris vers quelle page vous voulez aller.",
                    "I could not understand which page you want to open."
                ),
                'route' => null
            ]);
        }

        sendJson(200, [
            'intent' => 'navigation',
            'language' => $language,
            'confidence' => $confidence,
            'source' => 'rules',
            'message' => localize($language, 'Ouverture de la section demandée.', 'Opening the requested section.'),
            'route' => $route
        ]);
    }

    if ($intent === 'low_stock_query') {
        $results = getLowStockProducts($pdo);

        sendJson(200, [
            'intent' => 'low_stock_query',
            'language' => $language,
            'confidence' => $confidence,
            'source' => 'rules',
            'message' => localize(
                $language,
                count($results) > 0 ? 'Voici les produits en stock faible.' : "Aucun produit en stock faible n'a été trouvé.",
                count($results) > 0 ? 'Here are the low-stock products.' : 'No low-stock products were found.'
            ),
            'results' => $results,
            'result_count' => count($results)
        ]);
    }

    if ($intent === 'supplier_query') {
        $term = extractSearchTerm($normalizedMessage);
        $results = searchSuppliers($pdo, $term);

        sendJson(200, [
            'intent' => 'supplier_query',
            'language' => $language,
            'confidence' => $confidence,
            'source' => 'rules',
            'search_term' => $term,
            'message' => localize(
                $language,
                count($results) > 0 ? 'Voici les fournisseurs correspondants.' : 'Aucun fournisseur correspondant n\'a été trouvé.',
                count($results) > 0 ? 'Here are the matching suppliers.' : 'No matching suppliers were found.'
            ),
            'results' => $results,
            'result_count' => count($results)
        ]);
    }

    if ($intent === 'product_search') {
        $term = extractSearchTerm($normalizedMessage);
        $results = searchProducts($pdo, $term);

        sendJson(200, [
            'intent' => 'product_search',
            'language' => $language,
            'confidence' => $confidence,
            'source' => 'rules',
            'search_term' => $term,
            'message' => localize(
                $language,
                count($results) > 0 ? 'Voici les produits correspondants.' : 'Aucun produit correspondant n\'a été trouvé.',
                count($results) > 0 ? 'Here are the matching products.' : 'No matching products were found.'
            ),
            'results' => $results,
            'result_count' => count($results)
        ]);
    }

    // ========================================================================
    // UNKNOWN INTENT FALLBACK
    // ========================================================================

    sendJson(200, [
        'intent' => 'unknown',
        'language' => $language,
        'confidence' => $confidence,
        'source' => 'rules',
        'message' => localize(
            $language,
            "Je n'ai pas bien compris votre demande. Essayez : 'cherche pompe' ou 'affiche stock'.",
            "I didn't understand your request. Try: 'find pump' or 'show stock'."
        )
    ]);

} catch (PDOException $e) {
    debugLog("Database error: " . $e->getMessage());
    sendJson(500, ['error' => 'Database error', 'details' => $e->getMessage()]);
} catch (Exception $e) {
    debugLog("Unexpected error: " . $e->getMessage());
    sendJson(500, ['error' => 'Unexpected error', 'details' => $e->getMessage()]);
}
?>