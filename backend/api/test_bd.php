<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/db.php';

$stmt = $pdo->query("SELECT 1 AS ok");
$result = $stmt->fetch(PDO::FETCH_ASSOC);

echo json_encode($result);