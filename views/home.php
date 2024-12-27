<?php include('partials/header.php'); ?>

    <h1>Welcome to Tazq</h1>
    <p>Tazq is a simple task management application designed to help you stay organized and productive.</p>

    <h2>Login</h2>
    <form action="/api/auth/login" method="post" data-ajax="true">
        <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
        <label for="email">Email:</label>
        <input type="email" id="email" name="email" required><br><br>
        <label for="password">Password:</label>
        <input type="password" id="password" name="password" required><br><br>
        <button type="submit">Login</button>
    </form>
    <p>Don't have an account? <a href="/register">Register</a></p>

    <h2>Key Features</h2>
    <ul>
        <li>Create and manage tasks</li>
        <li>Organize tasks into groups</li>
        <li>Invite others to collaborate</li>
    </ul>

<?php include('partials/footer.php'); ?>
