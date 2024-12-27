<?php 
$pageTitle = 'Login - TAZQ';
require_once __DIR__ . '/partials/header.php';
?>

<div class="form-container">
    <h1>Welcome Back</h1>
    
    <div class="message-container"></div>
    
    <form action="/api/auth/login" method="POST" data-ajax="true">
        <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
        
        <div class="form-group">
            <label for="email">Email Address</label>
            <input type="email" id="email" name="email" class="form-control" required>
        </div>
        
        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" class="form-control" required>
        </div>
        
        <div class="form-group">
            <button type="submit" class="btn btn-primary">Log In</button>
        </div>
    </form>
    
    <p class="text-center">
        Don't have an account? <a href="/register">Register</a>
    </p>
</div>

<?php require_once __DIR__ . '/partials/footer.php'; ?>