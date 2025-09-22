<?php
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/user_uploads/job_manager_errors.log');
error_reporting(E_ALL);
session_start();

// Debug logging
file_put_contents(__DIR__ . "/user_uploads/debug_upload.log", json_encode([
    '_FILES' => $_FILES,
    '_POST'  => $_POST,
    'error_code' => $_FILES['file']['error'] ?? 'none',
    'size' => $_FILES['file']['size'] ?? 'none'
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL, FILE_APPEND);

// Step 1: Get job ID
$jobId = $_POST['job_id'] ?? $_SESSION['job_id'] ?? null;
if (!$jobId) {
    http_response_code(400);
    echo json_encode(["success" => false, "error" => "Missing job_id"]);
    exit;
}

$_SESSION['job_id'] = $jobId;
$targetDir = "user_uploads/$jobId/";
if (!is_dir($targetDir)) {
    mkdir($targetDir, 0777, true);
    chmod($targetDir, 0777);
}

// Step 2: Handle uploaded file
if (empty($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(["success" => false, "error" => "No valid file uploaded"]);
    exit;
}

$uploadedFile = $_FILES['file'];
$fileName = basename($uploadedFile['name']);
$extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
$allowedExtensions = ['tsv', 'csv', 'txt', 'zip', 'gz'];

if (!in_array($extension, $allowedExtensions)) {
    http_response_code(415);
    echo json_encode(["success" => false, "error" => "Unsupported file type"]);
    exit;
}

$safeName = preg_replace("/[^A-Za-z0-9_\-\.]/", "_", $fileName);
$targetPath = $targetDir . $safeName;

if (!move_uploaded_file($uploadedFile['tmp_name'], $targetPath)) {
    http_response_code(500);
    echo json_encode(["success" => false, "error" => "Failed to save uploaded file"]);
    exit;
}

http_response_code(200);
echo json_encode([
    "success" => true,
    "file" => $safeName
]);
exit;
