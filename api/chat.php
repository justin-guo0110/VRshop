<?php
require_once __DIR__ . '/db.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';
// Get current logged-in user from session (helper in db.php)
$currentUser = current_user();

// Determine identity
$userId = $currentUser['member_id'] ?? null;
$sessionId = session_id();
if (!$sessionId) {
    session_start();
    $sessionId = session_id();
}

switch ($action) {
    case 'get_messages':
        get_messages($userId, $sessionId);
        break;
    case 'get_unread_count':
        get_unread_count($userId, $sessionId);
        break;
    case 'send_message':
        send_message($userId, $sessionId);
        break;
    default:
        echo json_encode(['success' => false, 'error' => 'Invalid action']);
}

function get_messages($userId, $sessionId) {
    $db = get_db();
    $currentUser = current_user();

    // 如果是管理員 → 撈全部訊息
    if ($currentUser['role'] === 'admin') {
        $stmt = $db->prepare("SELECT * FROM chat_messages ORDER BY created_at ASC");
    }
    // 如果是登入會員 → 撈自己的訊息
    else if ($userId) {
        $stmt = $db->prepare("SELECT * FROM chat_messages ORDER BY created_at ASC");
    }
    // 未登入 → 用 session 查詢
    else {
        $stmt = $db->prepare("SELECT * FROM chat_messages WHERE session_id = ? AND user_id IS NULL ORDER BY created_at ASC");
        $stmt->bind_param("s", $sessionId);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $messages = $result->fetch_all(MYSQLI_ASSOC);

    echo json_encode(['success' => true, 'messages' => $messages]);
}

function send_message($userId, $sessionId) {
    $db = get_db();
    $message = trim($_POST['message'] ?? '');
    
    if (empty($message)) {
        echo json_encode(['success' => false, 'error' => 'Empty message']);
        return;
    }
    
    if ($userId) {
        $stmt = $db->prepare("INSERT INTO chat_messages (user_id, sender, message) VALUES (?, 'user', ?)");
        $stmt->bind_param("is", $userId, $message);
    } else {
        $stmt = $db->prepare("INSERT INTO chat_messages (session_id, sender, message) VALUES (?, 'user', ?)");
        $stmt->bind_param("ss", $sessionId, $message);
    }
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Database error']);
    }
}

function get_unread_count($userId, $sessionId) {
    $db = get_db();
    $count = fetch_unread_count($db, $userId, $sessionId);
    echo json_encode(['success' => true, 'unread_count' => $count]);
}

function mark_admin_messages_read(mysqli $db, $userId, $sessionId): void {
    if ($userId) {
        $stmt = $db->prepare("UPDATE chat_messages SET is_read = 1 WHERE user_id = ? AND sender = 'admin' AND is_read = 0");
        $stmt->bind_param("i", $userId);
    } else {
        $stmt = $db->prepare("UPDATE chat_messages SET is_read = 1 WHERE session_id = ? AND user_id IS NULL AND sender = 'admin' AND is_read = 0");
        $stmt->bind_param("s", $sessionId);
    }
    $stmt->execute();
}

function fetch_unread_count(mysqli $db, $userId, $sessionId): int {
    if ($userId) {
        $stmt = $db->prepare("SELECT COUNT(*) as cnt FROM chat_messages WHERE user_id = ? AND sender = 'admin' AND is_read = 0");
        $stmt->bind_param("i", $userId);
    } else {
        $stmt = $db->prepare("SELECT COUNT(*) as cnt FROM chat_messages WHERE session_id = ? AND user_id IS NULL AND sender = 'admin' AND is_read = 0");
        $stmt->bind_param("s", $sessionId);
    }
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    return (int)($result['cnt'] ?? 0);
}
