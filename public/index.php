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
            
        case '/api/groups/create':
            if ($method !== 'POST') break;
            
            // Validate CSRF token
            validateCsrfToken($_POST['csrf_token'] ?? '');
            
            $name = trim($_POST['name'] ?? '');
            if (empty($name)) {
                http_response_code(400);
                echo json_encode(['error' => 'Group name is required']);
                exit;
            }
            
            $group_id = createFamilyGroup($name, $_SESSION['user_id']);
            if ($group_id) {
                echo json_encode([
                    'success' => true,
                    'group' => [
                        'id' => $group_id,
                        'name' => $name,
                        'is_admin' => true,
                        'member_count' => 1
                    ],
                    'invite_link' => "/invite/$invite_code"
                ]);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to create group']);
            }
            exit;
    
        case '/api/groups':
            if ($method !== 'GET') break;
            $groups = getUserGroups($_SESSION['user_id']);
            echo json_encode([
                'success' => true,
                'groups' => $groups
            ]);
            exit;
    
        case (preg_match('/^\/api\/groups\/([^\/]+)\/join$/', $uri, $matches) ? true : false):
            if ($method !== 'POST') break;
            
            validateCsrfToken($_POST['csrf_token'] ?? '');
            
            $invite_code = $matches[1];
            $group = getGroupByInviteCode($invite_code);
            
            if (!$group) {
                http_response_code(404);
                echo json_encode(['error' => 'Invalid invite code']);
                exit;
            }
            
            if (isGroupMember($group['id'], $_SESSION['user_id'])) {
                http_response_code(400);
                echo json_encode(['error' => 'You are already a member of this group']);
                exit;
            }
            
            if (joinGroup($group['id'], $_SESSION['user_id'])) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Successfully joined the group',
                    'redirect' => '/group/' . $group['id']
                ]);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to join group']);
            }
            exit;
            
        case '/api/tasks':
            if ($method !== 'GET') break;
            
            $group_id = $_SESSION['active_group_id'] ?? null;
            if (!$group_id) {
                http_response_code(400);
                echo json_encode(['error' => 'No active group selected']);
                exit;
            }
            
            $assignee = $_GET['assignee'] ?? null;
            $status = $_GET['status'] ?? null;
            
            $tasks = getGroupTasks($group_id, $assignee, $status);
            echo json_encode(['success' => true, 'tasks' => $tasks]);
            exit;
    
        case '/api/tasks/create':
            if ($method !== 'POST') break;
            
            validateCsrfToken($_POST['csrf_token'] ?? '');
            
            $group_id = $_SESSION['active_group_id'] ?? null;
            if (!$group_id) {
                http_response_code(400);
                echo json_encode(['error' => 'No active group selected']);
                exit;
            }
            
            $title = trim($_POST['title'] ?? '');
            if (empty($title)) {
                http_response_code(400);
                echo json_encode(['error' => 'Task title is required']);
                exit;
            }
            
            $task_id = createTask(
                $title,
                $_POST['description'] ?? null,
                $_POST['due_date'] ?? null,
                $group_id,
                $_POST['assigned_to'] ?? null
            );
            
            if ($task_id) {
                echo json_encode(['success' => true, 'task_id' => $task_id]);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to create task']);
            }
            exit;
    
        case (preg_match('/^\/api\/tasks\/(\d+)\/toggle$/', $uri, $matches) ? true : false):
            if ($method !== 'POST') break;
            
            validateCsrfToken($_POST['csrf_token'] ?? '');
            
            $task_id = (int)$matches[1];
            $completed = isset($_POST['completed']) && $_POST['completed'] === 'true';
            
            if (!canAccessTask($task_id, $_SESSION['user_id'])) {
                http_response_code(403);
                echo json_encode(['error' => 'Access denied']);
                exit;
            }
            
            if (toggleTaskStatus($task_id, $completed)) {
                echo json_encode(['success' => true]);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to update task']);
            }
            exit;
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
