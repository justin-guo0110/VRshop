<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$currentUser = $_SESSION['user'] ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VR Mall</title>
    <link rel="stylesheet" href="../public/css/style.css">
</head>
<body>
    <header class="site-header">
        <div class="container header-inner">
            <div class="logo">VR Mall</div>
            <nav class="nav">
                <a href="../views/products.php">Products</a>
                <a href="../views/profile.php">Profile</a>
                <?php if ($currentUser && ($currentUser['role'] ?? '') === 'admin'): ?>
                    <a href="../views/admin.php">Admin</a>
                <?php endif; ?>
            </nav>
            <div class="user-info">
                <?php if ($currentUser): ?>
                    <span>Hello, <?php echo htmlspecialchars($currentUser['name'] ?? $currentUser['email']); ?></span>
                    <button class="btn btn-secondary" id="logoutBtn">Logout</button>
                <?php else: ?>
                    <a class="btn" href="../views/login.php">Login</a>
                <?php endif; ?>
            </div>
        </div>
    </header>
    <main class="container">
