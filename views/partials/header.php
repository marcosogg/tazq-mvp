<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= generateCsrfToken() ?>">
    <title><?= htmlspecialchars($pageTitle ?? 'TAZQ - Family Task Management') ?></title>
    <link rel="stylesheet" href="/css/style.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="/js/app.js"></script>
</head>
<body>
    <?php if (isAuthenticated()): ?>
    <nav class="top-nav">
        <div class="nav-content">
            <a href="/" class="logo">TAZQ</a>
            <div class="nav-links">
                <a href="/tasks">Tasks</a>
                <a href="/group">Group</a>
                <form action="/logout" method="post" class="logout-form">
                    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                    <button type="submit" class="logout-btn">Logout</button>
                </form>
            </div>
        </div>
    </nav>
    <?php endif; ?>
    <main class="container">
