<?php
function isAuthenticated() {
    return isset($_SESSION['user_id']);
}

function getCurrentUser() {
    if (!isAuthenticated()) {
        return null;
    }
    
    $db = Database::getInstance();
    return $db->querySingle(
        'SELECT * FROM users WHERE id = :id',
        [':id' => $_SESSION['user_id']]
    );
}

function login($email, $password) {
    $user = getUserByEmail($email);
    
    if (!$user) {
        return false;
    }
    
    if (!password_verify($password, $user['password_hash'])) {
        return false;
    }
    
    // Set session
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['last_activity'] = time();
    
    // Regenerate session ID for security
    session_regenerate_id(true);
    
    return true;
}

function logout() {
    // Clear session data
    $_SESSION = array();
    
    // Delete session cookie
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
    }
    
    // Destroy session
    session_destroy();
}

function requireAuth() {
    if (!isAuthenticated()) {
        header('Location: /login');
        exit;
    }
}

// CSRF protection
function generateCsrfToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCsrfToken($token = null) {
    // Check token from POST data
    $post_token = $_POST['csrf_token'] ?? '';
    // Check token from header
    $header_token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    // Use provided token or check both POST and header tokens
    $token = $token ?? $post_token ?? $header_token;

    if (!isset($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
        http_response_code(403);
        if ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '' === 'XMLHttpRequest') {
            die(json_encode(['error' => 'Invalid CSRF token']));
        }
        die('Invalid CSRF token');
    }
}

function validateName($name) {
    return !empty($name) && preg_match('/^[a-zA-Z\s]+$/', $name);
}

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function validatePassword($password) {
    return preg_match('/^(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}$/', $password);
}

function getUserByEmail($email) {
    $db = Database::getInstance();
    return $db->querySingle(
        'SELECT * FROM users WHERE email = :email',
        [':email' => $email]
    );
}

function createUser($name, $email, $password, $password_confirm) {
    if (!validateName($name)) {
        return false;
    }
    
    if (!validateEmail($email)) {
        return false;
    }
    
    if (!validatePassword($password)) {
        return false;
    }
    
    if ($password !== $password_confirm) {
        return false;
    }
    
    if (getUserByEmail($email)) {
        return false; // Email already exists
    }
    
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    
    $db = Database::getInstance();
    $db->query(
        'INSERT INTO users (name, email, password_hash) VALUES (:name, :email, :password_hash)',
        [':name' => $name, ':email' => $email, ':password_hash' => $password_hash]
    );
    
    return true;
}
