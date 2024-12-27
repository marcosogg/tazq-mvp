<?php 
$pageTitle = 'Register - TAZQ';
require_once __DIR__ . '/partials/header.php';
?>

<div class="form-container">
    <h1>Create Account</h1>
    
    <div class="message-container"></div>
    
    <form action="/api/auth/register" method="POST" data-ajax="true">
        <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
        
        <div class="form-group">
            <label for="name">Full Name</label>
            <input type="text" id="name" name="name" class="form-control" required>
        </div>
        
        <div class="form-group">
            <label for="email">Email Address</label>
            <input type="email" id="email" name="email" class="form-control" required>
        </div>
        
        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" class="form-control" required 
                   pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}" 
                   title="Must contain at least one number and one uppercase and lowercase letter, and at least 8 or more characters">
        </div>
        
        <div class="form-group">
            <label for="password_confirm">Confirm Password</label>
            <input type="password" id="password_confirm" name="password_confirm" class="form-control" required>
        </div>
        
        <div class="form-group">
            <button type="submit" class="btn btn-primary">Create Account</button>
        </div>
    </form>
    
    <p class="text-center">
        Already have an account? <a href="/login">Log In</a>
    </p>
</div>

<?php require_once __DIR__ . '/partials/footer.php'; ?>