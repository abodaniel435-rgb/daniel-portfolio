<?php
// list-images.php
// Scans the /images folder and returns all image filenames as JSON.
// This lets products.js automatically pick up whatever photos are actually
// sitting in the folder, without anyone needing to type out filenames by hand.

header('Content-Type: application/json');

$imagesDir = __DIR__ . '/images';
$allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];

$files = [];

if (is_dir($imagesDir)) {
    $items = scandir($imagesDir);
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;
        $ext = strtolower(pathinfo($item, PATHINFO_EXTENSION));
        if (in_array($ext, $allowedExtensions)) {
            $files[] = $item;
        }
    }
}

sort($files);

echo json_encode($files);