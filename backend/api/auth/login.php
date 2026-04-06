<?php
header('Content-Type: application/json');
session_start();

require_once __DIR__ . '/../../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$email = $_POST['email'] ?? null;
$password = $_POST['password'] ?? null;

if (!$email || !$password) {
    http_response_code(400);
    echo json_encode(['error' => 'Email and password are required']);
    exit;
}

$sql = "
SELECT id, name, email, password_hash, role, company_name, is_active, supplier_company_id
FROM users
WHERE email = :email
LIMIT 1
";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        http_response_code(401);
        echo json_encode(['error' => 'Invalid credentials']);
        exit;
    }

    if (!(bool)$user['is_active']) {
        http_response_code(403);
        echo json_encode(['error' => 'User account is inactive']);
        exit;
    }

    if (!password_verify($password, $user['password_hash'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Invalid credentials']);
        exit;
    }

    $_SESSION['user_id'] = (int)$user['id'];
    $_SESSION['name'] = $user['name'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['supplier_company_id'] = isset($user['supplier_company_id'])
    ? (int)$user['supplier_company_id']
    : null;

    echo json_encode([
        'message' => 'Login successful',
        'user' => [
            'id' => (int)$user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'role' => $user['role'],
            'company_name' => $user['company_name']
        ]
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);



} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Login query failed',
        'details' => $e->getMessage()
    ]);
}