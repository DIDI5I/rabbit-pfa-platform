<?php
/**
 * Projet Rabbit – Module Paiement Stripe
 * Fichier  : backend/api/payment/webhook.php
 * Rôle     : Réceptionner et traiter les événements Stripe (webhook).
 *            Sur "payment_intent.succeeded" : créer la commande, décrémenter
 *            le stock et tracer le mouvement dans une transaction PDO atomique.
 * Auteur   : Responsable Paiement – Projet académique MRO B2B
 * PHP      : 8.0+
 *
 * ⚠️  Ce fichier est appelé DIRECTEMENT par Stripe, pas par un utilisateur connecté.
 *     NE PAS inclure auth_check.php ici – la sécurité est assurée par la signature HMAC.
 */

declare(strict_types=1);

// ─────────────────────────────────────────────────────────────────────────────
// 1. En-têtes HTTP
// ─────────────────────────────────────────────────────────────────────────────
header('Content-Type: application/json; charset=UTF-8');

// ─────────────────────────────────────────────────────────────────────────────
// 2. Méthode autorisée : POST uniquement
// ─────────────────────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Méthode non autorisée.']);
    exit;
}

// ─────────────────────────────────────────────────────────────────────────────
// 3. Chargement du SDK Stripe
// ─────────────────────────────────────────────────────────────────────────────
require_once __DIR__ . '/../../../vendor/autoload.php';

// ─────────────────────────────────────────────────────────────────────────────
// 4. Lecture du corps brut AVANT tout traitement
//    (indispensable : json_decode() au préalable invalide la signature)
// ─────────────────────────────────────────────────────────────────────────────
$payload = file_get_contents('php://input');

if (empty($payload)) {
    http_response_code(400);
    echo json_encode(['error' => 'Corps de la requête vide.']);
    exit;
}

// ─────────────────────────────────────────────────────────────────────────────
// 5. Vérification de la signature Stripe (sécurité essentielle)
// ─────────────────────────────────────────────────────────────────────────────
$webhookSecret = getenv('STRIPE_WEBHOOK_SECRET');  // whsec_xxxxx

if (empty($webhookSecret)) {
    http_response_code(500);
    error_log('[Rabbit-Webhook] STRIPE_WEBHOOK_SECRET manquant.');
    echo json_encode(['error' => 'Configuration serveur incomplète.']);
    exit;
}

$sigHeader = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

try {
    $event = \Stripe\Webhook::constructEvent($payload, $sigHeader, $webhookSecret);
} catch (\UnexpectedValueException $e) {
    // Payload malformé
    http_response_code(400);
    error_log('[Rabbit-Webhook] Payload invalide : ' . $e->getMessage());
    echo json_encode(['error' => 'Payload invalide.']);
    exit;
} catch (\Stripe\Exception\SignatureVerificationException $e) {
    // Signature incorrecte ou rejouée
    http_response_code(403);
    error_log('[Rabbit-Webhook] Signature invalide : ' . $e->getMessage());
    echo json_encode(['error' => 'Signature invalide.']);
    exit;
}

// ─────────────────────────────────────────────────────────────────────────────
// 6. Routage des événements Stripe
//    On répond 200 immédiatement pour les événements non gérés
//    (Stripe re-tentera sinon)
// ─────────────────────────────────────────────────────────────────────────────
switch ($event->type) {

    // ── Paiement confirmé ──────────────────────────────────────────────────
    case 'payment_intent.succeeded':
        handlePaymentSucceeded($event->data->object);
        break;

    // ── Paiement échoué (optionnel – extensible) ───────────────────────────
    case 'payment_intent.payment_failed':
        error_log('[Rabbit-Webhook] Paiement échoué : ' . $event->data->object->id);
        http_response_code(200);
        echo json_encode(['status' => 'payment_failed_logged']);
        exit;

    default:
        // Événement non géré : toujours répondre 200 pour éviter les re-tentatives Stripe
        http_response_code(200);
        echo json_encode(['status' => 'event_ignored', 'type' => $event->type]);
        exit;
}

// ─────────────────────────────────────────────────────────────────────────────
// ─────────────────────────────────────────────────────────────────────────────
// FONCTIONS
// ─────────────────────────────────────────────────────────────────────────────
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Gère l'événement payment_intent.succeeded.
 * Crée la commande, décrémente le stock et trace le mouvement
 * dans une transaction PDO atomique.
 *
 * @param  \Stripe\PaymentIntent $paymentIntent  Objet PaymentIntent Stripe
 * @return void
 */
function handlePaymentSucceeded(\Stripe\PaymentIntent $paymentIntent): void
{
    // ── 6a. Connexion PDO ──────────────────────────────────────────────────
    $pdo = getPDOConnection();

    // ── 6b. Extraction des données du PaymentIntent ────────────────────────
    $paymentIntentId = $paymentIntent->id;
    $amountReceived  = $paymentIntent->amount_received;   // En centimes
    $currency        = $paymentIntent->currency;
    $metadata        = $paymentIntent->metadata->toArray();

    // Ces valeurs doivent être transmises via les metadata lors de la création
    // Ex : metadata['component_id'] et metadata['quantity']
    $componentId = isset($metadata['component_id']) ? (int) $metadata['component_id'] : null;
    $quantity    = isset($metadata['quantity'])    ? (int) $metadata['quantity']    : null;
    $userId      = isset($metadata['user_id'])     ? (int) $metadata['user_id']     : null;

    if ($componentId === null || $quantity === null || $quantity <= 0) {
        error_log("[Rabbit-Webhook] Métadonnées manquantes pour PI {$paymentIntentId}.");
        http_response_code(422);
        echo json_encode(['error' => 'Métadonnées du paiement insuffisantes.']);
        exit;
    }

    // ── 6c. Idempotence : vérifier si cet événement a déjà été traité ──────
    //    Empêche les doublons en cas de re-tentative Stripe
    $stmtCheck = $pdo->prepare(
        'SELECT id FROM orders WHERE stripe_payment_intent_id = :pi_id LIMIT 1'
    );
    $stmtCheck->execute([':pi_id' => $paymentIntentId]);

    if ($stmtCheck->fetchColumn() !== false) {
        // Déjà traité – répondre 200 pour que Stripe arrête de réessayer
        http_response_code(200);
        echo json_encode(['status' => 'already_processed']);
        exit;
    }

    // ── 6d. Début de la transaction PDO ────────────────────────────────────
    try {
        $pdo->beginTransaction();

        // ── SQL 1 : INSERT INTO orders ─────────────────────────────────────
        // Crée la commande liée au paiement Stripe
        $stmtOrder = $pdo->prepare('
            INSERT INTO orders (
                user_id,
                stripe_payment_intent_id,
                amount,
                currency,
                status,
                created_at
            ) VALUES (
                :user_id,
                :pi_id,
                :amount,
                :currency,
                :status,
                NOW()
            )
        ');

        $stmtOrder->execute([
            ':user_id'  => $userId,
            ':pi_id'    => $paymentIntentId,
            ':amount'   => $amountReceived,
            ':currency' => strtoupper($currency),
            ':status'   => 'paid',
        ]);

        $orderId = (int) $pdo->lastInsertId();

        if ($orderId === 0) {
            throw new \RuntimeException('Échec de la création de la commande (lastInsertId = 0).');
        }

        // ── SQL 2 : UPDATE components – décrémentation du stock ────────────
        // La contrainte CHECK(stock_qty >= 0) en BDD constitue un second filet de sécurité
        $stmtStock = $pdo->prepare('
            UPDATE components
            SET    stock_qty = stock_qty - :qty
            WHERE  id        = :component_id
              AND  stock_qty >= :qty          -- Vérifie la disponibilité atomiquement
        ');

        $stmtStock->execute([
            ':qty'          => $quantity,
            ':component_id' => $componentId,
        ]);

        if ($stmtStock->rowCount() === 0) {
            // Aucune ligne modifiée → stock insuffisant ou composant inexistant
            throw new \RuntimeException(
                "Stock insuffisant ou composant introuvable pour component_id={$componentId}, qty={$quantity}."
            );
        }

        // ── SQL 3 : INSERT INTO stock_movements – traçabilité ──────────────
        $stmtMovement = $pdo->prepare('
            INSERT INTO stock_movements (
                component_id,
                order_id,
                movement_type,
                quantity,
                reference,
                moved_at
            ) VALUES (
                :component_id,
                :order_id,
                :movement_type,
                :quantity,
                :reference,
                NOW()
            )
        ');

        $stmtMovement->execute([
            ':component_id'  => $componentId,
            ':order_id'      => $orderId,
            ':movement_type' => 'outbound',                      // Sortie de stock
            ':quantity'      => -$quantity,                      // Valeur négative = sortie
            ':reference'     => "STRIPE-{$paymentIntentId}",     // Traçabilité Stripe
        ]);

        // ── Validation atomique ────────────────────────────────────────────
        $pdo->commit();

        http_response_code(200);
        echo json_encode([
            'status'   => 'success',
            'order_id' => $orderId,
        ]);

    } catch (\Throwable $e) {
        // ── Annulation totale en cas d'erreur ──────────────────────────────
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        error_log("[Rabbit-Webhook] ROLLBACK – PI {$paymentIntentId} : " . $e->getMessage());

        http_response_code(500);
        echo json_encode([
            'error'   => 'Erreur lors du traitement de la commande.',
            'details' => $e->getMessage(),   // À masquer en production
        ]);
    }
}

/**
 * Retourne une instance PDO configurée avec gestion d'erreurs par exceptions.
 * Les paramètres de connexion sont lus depuis les variables d'environnement.
 *
 * @return PDO
 * @throws \RuntimeException si la connexion échoue
 */
function getPDOConnection(): PDO
{
    $dsn      = sprintf(
        'mysql:host=%s;dbname=%s;charset=utf8mb4',
        getenv('DB_HOST') ?: 'localhost',
        getenv('DB_NAME') ?: 'rabbit_mro'
    );
    $user     = getenv('DB_USER') ?: 'root';
    $password = getenv('DB_PASS') ?: '';

    try {
        $pdo = new PDO($dsn, $user, $password, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,   // Exceptions sur erreur SQL
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,                    // Requêtes préparées natives
        ]);
    } catch (\PDOException $e) {
        error_log('[Rabbit-Webhook] Connexion BDD impossible : ' . $e->getMessage());
        throw new \RuntimeException('Connexion à la base de données impossible.');
    }

    return $pdo;
}
