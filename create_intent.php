<?php
/**
 * Projet Rabbit – Module Paiement Stripe
 * Fichier  : backend/api/payment/create_intent.php
 * Rôle     : Créer un PaymentIntent Stripe et retourner le client_secret au front-end.
 * Auteur   : Responsable Paiement – Projet académique MRO B2B
 * PHP      : 8.0+
 */

declare(strict_types=1);

// ─────────────────────────────────────────────────────────────────────────────
// 1. Sécurité : vérification de l'authentification
// ─────────────────────────────────────────────────────────────────────────────
require_once __DIR__ . '/../../auth/auth_check.php';   // Lève une exception ou redirige si non authentifié

// ─────────────────────────────────────────────────────────────────────────────
// 2. En-têtes HTTP – JSON uniquement, protection CORS stricte
// ─────────────────────────────────────────────────────────────────────────────
header('Content-Type: application/json; charset=UTF-8');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');

// ─────────────────────────────────────────────────────────────────────────────
// 3. Méthode autorisée : POST uniquement
// ─────────────────────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Méthode non autorisée. Utilisez POST.']);
    exit;
}

// ─────────────────────────────────────────────────────────────────────────────
// 4. Chargement du SDK Stripe (via Composer)
// ─────────────────────────────────────────────────────────────────────────────
require_once __DIR__ . '/../../../vendor/autoload.php';

// ─────────────────────────────────────────────────────────────────────────────
// 5. Configuration Stripe (clé secrète depuis variable d'environnement)
//    Ne jamais coder la clé en dur dans le source !
// ─────────────────────────────────────────────────────────────────────────────
$stripeSecretKey = getenv('STRIPE_SECRET_KEY');

if (empty($stripeSecretKey)) {
    http_response_code(500);
    echo json_encode(['error' => 'Configuration serveur manquante : clé Stripe introuvable.']);
    exit;
}

\Stripe\Stripe::setApiKey($stripeSecretKey);

// ─────────────────────────────────────────────────────────────────────────────
// 6. Lecture et validation du corps de la requête (JSON ou form-data)
// ─────────────────────────────────────────────────────────────────────────────
$rawBody  = file_get_contents('php://input');
$jsonData = json_decode($rawBody, true);

// Support JSON body ET form-data classique
$amount = $jsonData['amount'] ?? ($_POST['amount'] ?? null);

// Validation : le montant doit être un entier positif (centimes MAD)
// Ex. : 15000 = 150,00 MAD
if ($amount === null || !is_numeric($amount) || (int) $amount <= 0) {
    http_response_code(400);
    echo json_encode([
        'error'   => 'Paramètre "amount" invalide ou manquant.',
        'details' => 'Le montant doit être un entier positif exprimé en centimes (ex : 15000 pour 150,00 MAD).',
    ]);
    exit;
}

$amount = (int) $amount;

// Plafond de sécurité : 999 999,99 MAD (Stripe limite à ~999 999 unités)
if ($amount > 99999999) {
    http_response_code(400);
    echo json_encode(['error' => 'Montant supérieur au plafond autorisé.']);
    exit;
}

// ─────────────────────────────────────────────────────────────────────────────
// 7. Création du PaymentIntent Stripe
// ─────────────────────────────────────────────────────────────────────────────
try {
    $paymentIntent = \Stripe\PaymentIntent::create([
        'amount'                    => $amount,
        'currency'                  => 'mad',          // Dirham marocain
        'automatic_payment_methods' => ['enabled' => true],
        'metadata'                  => [
            // Données utiles pour le rapprochement côté webhook
            'project'    => 'rabbit_mro',
            'created_by' => $_SESSION['user_id'] ?? 'anonymous',
        ],
    ]);

    // Succès : on renvoie uniquement le client_secret (jamais la clé secrète !)
    http_response_code(201);
    echo json_encode([
        'client_secret'     => $paymentIntent->client_secret,
        'payment_intent_id' => $paymentIntent->id,   // Utile pour le suivi front-end
    ]);

} catch (\Stripe\Exception\CardException $e) {
    // Carte refusée, fonds insuffisants, etc.
    http_response_code(402);
    echo json_encode([
        'error'   => 'Paiement refusé.',
        'details' => $e->getError()->message,
        'code'    => $e->getError()->code,
    ]);

} catch (\Stripe\Exception\RateLimitException $e) {
    http_response_code(429);
    echo json_encode(['error' => 'Trop de requêtes envoyées à Stripe. Réessayez dans un instant.']);

} catch (\Stripe\Exception\InvalidRequestException $e) {
    http_response_code(400);
    echo json_encode([
        'error'   => 'Requête Stripe invalide.',
        'details' => $e->getError()->message,
    ]);

} catch (\Stripe\Exception\AuthenticationException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Authentification Stripe échouée. Vérifiez la clé API.']);

} catch (\Stripe\Exception\ApiConnectionException $e) {
    http_response_code(503);
    echo json_encode(['error' => "Impossible de contacter l'API Stripe. Vérifiez la connexion réseau."]);

} catch (\Stripe\Exception\ApiErrorException $e) {
    // Catch-all pour toutes les autres erreurs Stripe
    http_response_code(500);
    echo json_encode([
        'error'   => 'Erreur Stripe inattendue.',
        'details' => $e->getError()->message,
    ]);

} catch (\Throwable $e) {
    // Erreur PHP non anticipée
    http_response_code(500);
    error_log('[Rabbit-Payment] create_intent fatal: ' . $e->getMessage());
    echo json_encode(['error' => 'Erreur interne du serveur.']);
}
