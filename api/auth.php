<?php
require_once __DIR__ . '/db.php';

$action = $_GET['action'] ?? '';
switch ($action) {
    case 'register':
        register();
        break;
    case 'login':
        login();
        break;
    case 'logout':
        logout();
        break;
    case 'me':
        me();
        break;
    default:
        respond_json(['error' => 'Unknown action'], 400);
}

function register(): void {
    $db = get_db();
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

    if ($email === '' || $password === '' || $name === '') {
        respond_json(['error' => 'Missing fields'], 422);
    }

    $stmt = $db->prepare('SELECT member_id FROM members WHERE email = ?');
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        respond_json(['error' => 'Email already registered'], 409);
    }
    $stmt->close();

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $db->prepare('INSERT INTO members (email, password_hash, name, phone, role) VALUES (?, ?, ?, ?, "member")');
    $stmt->bind_param('ssss', $email, $hash, $name, $phone);
    if ($stmt->execute()) {
        $_SESSION['user'] = [
            'member_id' => $stmt->insert_id,
            'email' => $email,
            'name' => $name,
            'phone' => $phone,
            'role' => 'member'
        ];
        respond_json(['success' => true, 'user' => $_SESSION['user']]);
    }
    respond_json(['error' => 'Registration failed'], 500);
}

function login(): void {
    $db = get_db();
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    if ($email === '' || $password === '') {
        respond_json(['error' => 'Missing credentials'], 422);
    }
    $stmt = $db->prepare('SELECT member_id, email, password_hash, name, phone, role FROM members WHERE email = ?');
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $res = $stmt->get_result();
    $user = $res->fetch_assoc();
    if ($user && (password_verify($password, $user['password_hash']) || hash_equals(hash('sha256', $password), $user['password_hash']))) {
        unset($user['password_hash']);
        $_SESSION['user'] = $user;
        respond_json(['success' => true, 'user' => $user]);
    }
    respond_json(['error' => 'Invalid credentials'], 401);
}

function logout(): void {
    session_destroy();
    respond_json(['success' => true]);
}

function me(): void {
    $user = current_user();
    if ($user) {
        respond_json(['user' => $user]);
    }
    respond_json(['user' => null]);
}
