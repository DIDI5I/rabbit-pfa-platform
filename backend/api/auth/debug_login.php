<?php
header('Content-Type: application/json');
session_start();

$_SESSION['user_id'] = 1;
$_SESSION['name'] = 'Admin Rabbit';
$_SESSION['email'] = 'owner@rabbit.ma';
$_SESSION['role'] = 'owner';

echo json_encode([
    'message' => 'debug session set',
    'session' => $_SESSION
], JSON_PRETTY_PRINT);