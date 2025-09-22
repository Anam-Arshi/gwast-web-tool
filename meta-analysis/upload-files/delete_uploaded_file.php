<?php
$data = json_decode(file_get_contents("php://input"), true);
$job_id = $data['job_id'] ?? '';
$file_name = $data['file_name'] ?? '';

if ($job_id && $file_name) {
    $path = __DIR__ . "/user_uploads/$job_id/" . basename($file_name);
    if (file_exists($path)) {
        unlink($path);
        echo json_encode(["status" => "deleted", "file" => $file_name]);
        exit;
    }
}

echo json_encode(["status" => "error", "file" => $file_name]);
