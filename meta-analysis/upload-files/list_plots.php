<?php
$jobId = $_GET['job'] ?? '';
$dir = "user_uploads/$jobId/plots";
$images = [];

if (is_dir($dir)) {
    foreach (scandir($dir) as $file) {
        if (preg_match('/\.(png)$/i', $file)) {
            $images[] = $file;
        }
    }
}

header('Content-Type: application/json');
echo json_encode($images);
?>