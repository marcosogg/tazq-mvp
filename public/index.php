<?php
session_start();

// Include configuration and helper files
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../db.php';

// Basic routing
$route = $_GET['route'] ?? 'home';

// Define allowed routes
$routes = [
    'home' => 'views/home.php',
    'login' => 'views/login.php',
    'register' => 'views/register.php',
    'tasks' => 'views/tasks.php',
    'group' => 'views/group.php',
    'invite' => 'views/invite.php'
];

// Check if route exists
if (!isset($routes[$route])) {
    http_response_code(404);
    require __DIR__ . '/../views/404.php';
    exit;
}

// Check authentication for protected routes
$public_routes = ['home', 'login', 'register'];
if (!in_array($route, $public_routes) && !isAuthenticated()) {
    header('Location: /login');
    exit;
}

// Load the requested view
require __DIR__ . '/../' . $routes[$route];