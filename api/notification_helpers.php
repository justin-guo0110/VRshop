<?php
require_once __DIR__ . '/db.php';

if (!function_exists('table_exists')) {
    function table_exists(mysqli $db, string $table): bool {
        $tableEsc = $db->real_escape_string($table);
        $res = $db->query("SHOW TABLES LIKE '$tableEsc'");
        return $res && $res->num_rows > 0;
    }
}

function ensure_member_notifications_table(mysqli $db): void {
    if (table_exists($db, 'member_notifications')) {
        return;
    }

    $db->query(
        'CREATE TABLE IF NOT EXISTS member_notifications (
            notification_id INT NOT NULL AUTO_INCREMENT,
            member_id INT NOT NULL,
            title VARCHAR(255) NOT NULL,
            message TEXT NOT NULL,
            type ENUM("announcement","order","system") NOT NULL DEFAULT "system",
            is_read TINYINT(1) NOT NULL DEFAULT 0,
            data_url VARCHAR(255) DEFAULT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (notification_id),
            KEY idx_member_notifications (member_id, is_read),
            CONSTRAINT fk_member_notifications_member FOREIGN KEY (member_id) REFERENCES members(member_id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );
}

function create_member_notification(mysqli $db, int $memberId, string $title, string $message, string $type = 'system', ?string $dataUrl = null): bool {
    if ($memberId <= 0) {
        return false;
    }
    ensure_member_notifications_table($db);
    $stmt = $db->prepare('INSERT INTO member_notifications (member_id, title, message, type, is_read, data_url) VALUES (?, ?, ?, ?, 0, ?)');
    if (!$stmt) {
        error_log('create_member_notification prepare failed: ' . $db->error);
        return false;
    }
    $dataUrlValue = $dataUrl ?? '';
    $stmt->bind_param('issss', $memberId, $title, $message, $type, $dataUrlValue);
    $executed = $stmt->execute();
    if (!$executed) {
        error_log('create_member_notification execute failed: ' . $stmt->error);
    }
    return (bool)$executed;
}

function list_member_notifications(mysqli $db, int $memberId): array {
    ensure_member_notifications_table($db);
    $stmt = $db->prepare('SELECT notification_id, title, message, type, is_read, data_url, created_at FROM member_notifications WHERE member_id = ? ORDER BY created_at DESC');
    if (!$stmt) {
        return [];
    }
    $stmt->bind_param('i', $memberId);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function mark_member_notification_read(mysqli $db, int $memberId, int $notificationId): bool {
    if ($memberId <= 0 || $notificationId <= 0) {
        return false;
    }
    ensure_member_notifications_table($db);
    $stmt = $db->prepare('UPDATE member_notifications SET is_read = 1 WHERE notification_id = ? AND member_id = ?');
    if (!$stmt) {
        return false;
    }
    $stmt->bind_param('ii', $notificationId, $memberId);
    if (!$stmt->execute()) {
        return false;
    }
    return $stmt->affected_rows > 0;
}
