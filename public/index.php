<?php
// Session configuration - MUST be before session_start()
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1);
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_samesite', 'Strict');

// Allow non-HTTPS cookies in development
if (php_sapi_name() === 'cli-server') {
    ini_set('session.cookie_secure', 0);
}

require_once __DIR__ . '/../config.php';
session_start();

// Include helper files
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../db.php';

// Rest of the routing code remains the same...

// Parse the request URI
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

// API routes
if (strpos($uri, '/api/') === 0) {
    header('Content-Type: application/json');
    
    // All API routes except auth require authentication
    if (!strpos($uri, '/api/auth/') === 0 && !isAuthenticated()) {
        http_response_code(401);
        echo json_encode(['error' => 'Authentication required']);
        exit;
    }
    
    // Handle API routes
    switch ($uri) {
        case '/api/auth/login':
            if ($method !== 'POST') break;
            
            // Validate CSRF token
            $token = $_POST['csrf_token'] ?? '';
            validateCsrfToken($token);
            
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';
            
            if (login($email, $password)) {
                echo json_encode(['success' => true, 'redirect' => '/tasks']);
            } else {
                http_response_code(401);
                echo json_encode(['success' => false, 'error' => 'Invalid credentials']);
            }
            exit;
            
        case '/api/auth/register':
            if ($method !== 'POST') break;
            
            // Validate CSRF token
            $token = $_POST['csrf_token'] ?? '';
            validateCsrfToken($token);
            
            $name = $_POST['name'] ?? '';
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';
            $password_confirm = $_POST['password_confirm'] ?? '';
            
            if (createUser($name, $email, $password, $password_confirm)) {
                echo json_encode(['success' => true, 'redirect' => '/login']);
            } else {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Registration failed']);
            }
            exit;
            
        // Add more API routes here
    }
    
    // If no API route matched, return 404
    if (strpos($uri, '/api/') === 0) {
        http_response_code(404);
        echo json_encode(['error' => 'Not found']);
        exit;
    }
}

// Web routes
$routes = [
    '/' => 'views/home.php',
    '/login' => 'views/login.php',
    '/register' => 'views/register.php',
    '/tasks' => 'views/tasks.php',
    '/group' => 'views/group.php',
    '/invite' => 'views/invite.php'
];

// Check if route exists
if (!isset($routes[$uri])) {
    http_response_code(404);
    require __DIR__ . '/../views/404.php';
    exit;
}

// Check authentication for protected routes
$public_routes = ['/', '/login', '/register'];
if (!in_array($uri, $public_routes) && !isAuthenticated()) {
    header('Location: /login');
    exit;
}

// Load the requested view
require __DIR__ . '/../' . $routes[$uri];
