<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function get_db(): mysqli {
    static $conn = null;
    if ($conn === null) {
        // Default to local XAMPP settings; production can override via env vars.
        // Use localhost so PHP connects through the UNIX socket on XAMPP/macOS.
        $dbHost = getenv('DB_HOST') ?: 'localhost';
        $dbUser = getenv('DB_USER') ?: 'root';
        $dbPass = getenv('DB_PASS') ?: '';
        $dbName = getenv('DB_NAME') ?: 'vr_mall';
        $dbPort = intval(getenv('DB_PORT') ?: '3306');

        mysqli_report(MYSQLI_REPORT_OFF);
        $conn = @new mysqli($dbHost, $dbUser, $dbPass, $dbName, $dbPort);
        if ($conn->connect_error) {
            respond_json(['error' => 'DB connection failed'], 500);
            exit;
        }
        $conn->set_charset('utf8mb4');
    }
    return $conn;
}

function respond_json($data, int $status = 200): void {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function current_user() {
    return $_SESSION['user'] ?? null;
}

function require_login(): array {
    $user = current_user();
    if (!$user) {
        respond_json(['error' => 'Auth required'], 401);
    }
    return $user;
}

function require_admin(): array {
    $user = require_login();
    if (($user['role'] ?? '') !== 'admin') {
        respond_json(['error' => 'Admin only'], 403);
    }
    return $user;
}
