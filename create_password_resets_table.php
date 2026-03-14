<?php
$conn = new mysqli('localhost', 'root', '', 'vr_mall');
if ($conn->connect_error) {
    echo "連接失敗: " . $conn->connect_error;
    exit;
}

$sql = "CREATE TABLE IF NOT EXISTS `password_resets` (
  `reset_id` int(11) NOT NULL AUTO_INCREMENT,
  `member_id` int(11) NOT NULL,
  `token` varchar(255) NOT NULL UNIQUE,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`reset_id`),
  FOREIGN KEY (`member_id`) REFERENCES `members`(`member_id`) ON DELETE CASCADE
) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;";

if ($conn->query($sql) === TRUE) {
    echo "password_resets 表創建成功！";
} else {
    echo "錯誤: " . $conn->error;
}

$conn->close();
?>
