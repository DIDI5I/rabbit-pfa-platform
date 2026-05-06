<?php
const BASE_DIR = __DIR__ . '/../';
require BASE_DIR . 'vendor/autoload.php';
use App\Core\Session;
require BASE_DIR . 'Support/helpers.php';
require base_path('bootstrap.php');
Session::start();

use App\Core\Router;
use App\Core\Response;


$allowedOrigins = [
    'http://localhost:5173',
    'http://127.0.0.1:5173',
];

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

if (in_array($origin, $allowedOrigins, true)) {
    header("Access-Control-Allow-Origin: {$origin}");
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Allow-Headers: Content-Type, Accept, Authorization, X-Requested-With');
    header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
}

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$router = new Router();

require base_path('app/Config/routes.php');

try {
    $method = $_SERVER['REQUEST_METHOD'];
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    
 
    $result = $router->route($method, $path);
    
    Response::success($result);

} catch (Exception $e) {
    Response::handleException($e);
}