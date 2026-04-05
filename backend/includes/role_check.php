<?php
require_once __DIR__ . '/auth_check.php';

function requireRole($allowedRoles)
{
    if (!is_array($allowedRoles)) {
        $allowedRoles = [$allowedRoles];
    }

    $userRole = $_SESSION['role'] ?? null;

    if ($userRole === null) {
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Authentication required']);
        exit;
    }

    if (!in_array($userRole, $allowedRoles, true)) {
        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode([
            'error' => 'Access denied',
            'session_role' => $userRole,
            'allowed_roles' => $allowedRoles
        ]);
        exit;
    }
}