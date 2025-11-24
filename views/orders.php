<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user'])) {
    header('Location: /views/login.php');
    exit;
}
?>
<?php require_once __DIR__ . '/layout_header.php'; ?>
<section class="card">
    <h2>訂單查詢</h2>
    <div id="ordersList"></div>
</section>
<?php require_once __DIR__ . '/layout_footer.php'; ?>
