<?php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$db = new mysqli('localhost', 'root', '', 'vr_mall');
$result = $db->query('SELECT product_id, name, image_url FROM products ORDER BY product_id DESC LIMIT 10');
echo "Product Images Debug:\n";
while ($row = $result->fetch_assoc()) {
    echo $row['product_id'] . ' | ' . $row['name'] . ' | ' . ($row['image_url'] ?? 'NULL') . "\n";
}
$db->close();
?>
