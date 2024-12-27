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

function validateCsrfToken($token) {
    if (!isset($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
        http_response_code(403);
        die('Invalid CSRF token');
    }
}