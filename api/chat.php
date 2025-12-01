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
    case 'send_message':
        send_message($userId, $sessionId);
        break;
    default:
        echo json_encode(['success' => false, 'error' => 'Invalid action']);
}

function get_messages($userId, $sessionId) {
    $db = get_db();
    
    if ($userId) {
        $stmt = $db->prepare("SELECT * FROM chat_messages WHERE user_id = ? ORDER BY created_at ASC");
        $stmt->bind_param("i", $userId);
    } else {
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
