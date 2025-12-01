<?php
require_once __DIR__ . '/api/db.php';

$db = get_db();

// Check if table exists
$result = $db->query("SHOW TABLES LIKE 'chat_messages'");
if ($result->num_rows === 0) {
    echo "Table 'chat_messages' does NOT exist.\n";
} else {
    echo "Table 'chat_messages' exists.\n";
    // Show columns
    $result = $db->query("SHOW COLUMNS FROM chat_messages");
    while ($row = $result->fetch_assoc()) {
        echo $row['Field'] . " - " . $row['Type'] . " - " . ($row['Null'] === 'YES' ? 'NULL' : 'NOT NULL') . "\n";
    }
}

// Check if we can connect
echo "DB Connection successful.\n";
