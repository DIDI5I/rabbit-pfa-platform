<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Authentication required']);
    exit;
}

function require_role(array $allowed_roles): void {
    if (!in_array($_SESSION['role'], $allowed_roles)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Accès interdit']);
        exit;
    }
}

function get_supplier_company_id(): ?int {
    return $_SESSION['supplier_company_id'] ?? null;
}