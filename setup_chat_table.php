<?php
require_once __DIR__ . '/api/db.php';

$db = get_db();

$sql = "CREATE TABLE IF NOT EXISTS chat_messages (
  message_id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT DEFAULT NULL,
  session_id VARCHAR(255) DEFAULT NULL,
  sender ENUM('user','admin') NOT NULL,
  message TEXT NOT NULL,
  is_read TINYINT(1) DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($db->query($sql) === TRUE) {
    echo "Table chat_messages created successfully";
} else {
    echo "Error creating table: " . $db->error;
}
