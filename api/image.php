<?php
// Image proxy to serve product images from the local filesystem.
// Handles Chinese filenames and spaces that Apache on Windows cannot serve directly via URL.

$rel = $_GET['path'] ?? '';

// Security: prevent directory traversal
$rel = str_replace(['../', '..\\'], '', $rel);
$rel = ltrim($rel, '/\\');

if ($rel === '') {
    http_response_code(404);
    exit;
}

$base = realpath(__DIR__ . '/../image');
if ($base === false) {
    http_response_code(404);
    exit;
}

// Build filesystem path using OS directory separator
$filepath = $base . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $rel);
$filepath = realpath($filepath);

// Ensure the resolved path is within the image directory
if ($filepath === false || strpos($filepath, $base) !== 0 || !is_file($filepath)) {
    http_response_code(404);
    exit;
}

$ext = strtolower(pathinfo($filepath, PATHINFO_EXTENSION));
$mimeTypes = [
    'jpg'  => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png'  => 'image/png',
    'gif'  => 'image/gif',
    'webp' => 'image/webp',
];

$mime = $mimeTypes[$ext] ?? 'application/octet-stream';
header('Content-Type: ' . $mime);
header('Cache-Control: public, max-age=86400');
readfile($filepath);
