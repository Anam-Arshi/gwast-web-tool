<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);

ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/upload_errors.log');


session_start();
header('Content-Type: application/json');

if ($_POST['action'] !== 'upload' || !isset($_FILES['sumstats_file'])) {
    echo json_encode(['success' => false, 'error' => 'No file uploaded']);
    exit;
}

$file = $_FILES['sumstats_file'];
$format = $_POST['input_format'] ?? 'auto';

// Validate file
if ($file['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'error' => 'File upload error: ' . $file['error']]);
    exit;
}

// Create main uploads directory if it doesn't exist
$base_upload_dir = __DIR__ . '/uploads/';

if (!is_dir($base_upload_dir)) {
    mkdir($base_upload_dir, 0777, true);
}

// Generate random folder name
$random_folder = uniqid() . '_' . time();
$upload_dir = $base_upload_dir . $random_folder . '/';

// Create the random folder
if (!mkdir($upload_dir, 0777)) {
    echo json_encode([
        'success' => false,
        'error' => 'Failed to create upload directory: ' . $upload_dir
    ]);
    exit;
}
chmod($upload_dir, 0777);

// Keep original filename
$original_filename = $file['name'];
$target_path = $upload_dir . $original_filename;

// Move uploaded file
if (!move_uploaded_file($file['tmp_name'], $target_path)) {
    echo json_encode(['success' => false, 'error' => 'Failed to save uploaded file']);
    exit;
}

// Store file info in session for later use
$_SESSION['uploaded_file'] = [
    'path' => $target_path,
    'folder' => $random_folder,
    'original_name' => $original_filename,
    'format' => $format,
    'upload_time' => time()
];

// Get file info
$file_size = formatBytes(filesize($target_path));

// Try to get basic file info (rows count)
$variants_count = 'Unknown';
try {
    if (pathinfo($original_filename, PATHINFO_EXTENSION) === 'gz') {
        $handle = gzopen($target_path, 'r');
        $line_count = 0;
        while (($line = gzgets($handle)) !== false && $line_count < 1000) {
            $line_count++;
        }
        gzclose($handle);
        $variants_count = $line_count > 1 ? ($line_count - 1) . '+' : 'Header only';
    } else {
        $line_count = 0;
        $handle = fopen($target_path, 'r');
        while (($line = fgets($handle)) !== false && $line_count < 1000) {
            $line_count++;
        }
        fclose($handle);
        $variants_count = $line_count > 1 ? ($line_count - 1) . '+' : 'Header only';
    }
} catch (Exception $e) {
    $variants_count = 'Error reading file';
}

echo json_encode([
    'success' => true,
    'file_path' => $target_path,
    'folder' => $random_folder,
    'file_info' => [
        'filename' => $original_filename,
        'size' => $file_size,
        'format' => $format,
        'variants' => $variants_count
    ]
]);

function formatBytes($bytes, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    
    for ($i = 0; $bytes > 1024; $i++) {
        $bytes /= 1024;
    }
    
    return round($bytes, $precision) . ' ' . $units[$i];
}
?>
