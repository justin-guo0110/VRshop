<?php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$db = new mysqli('localhost', 'root', '', 'vr_mall');

// Update product images with correct path
$updates = [
    1 => 'image/1.jpg',
    2 => 'image/2.jpg',
    3 => 'image/3.jpg',
    4 => 'image/4.jpg',
    5 => 'image/5.jpg',
    6 => 'image/6.jpg',
    7 => 'image/7.jpg',
    8 => 'image/8.jpg',
];

foreach ($updates as $id => $path) {
    $db->query("UPDATE products SET image_url = '$path' WHERE product_id = $id");
    echo "Updated product $id to $path\n";
}

$db->close();
echo "Done!\n";
?>
