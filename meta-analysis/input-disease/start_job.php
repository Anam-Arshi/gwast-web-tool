<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method Not Allowed');
}

$selected_json = $_POST['selected_gcst_json'] ?? '[]';
$selected_gcsts = json_decode($selected_json, true);
if (!is_array($selected_gcsts) || empty($selected_gcsts)) {
    die('No studies selected.');
}

require_once 'connect.php';

$placeholders = implode(',', array_fill(0, count($selected_gcsts), '?'));
$sql = "SELECT `study_accession`, `summary_stats_location` FROM `summary_stats_available` WHERE `study_accession` IN ($placeholders)";
$stmt = $conn->prepare($sql);
$stmt->bind_param(str_repeat('s', count($selected_gcsts)), ...$selected_gcsts);
$stmt->execute();
$res = $stmt->get_result();

$files = [];
while ($row = $res->fetch_assoc()) {
    if (!empty($row['summary_stats_location'])) {
        $files[] = ['stats_url' => $row['summary_stats_location']];
    }
}
$stmt->close();

if (empty($files)) {
    die('No valid summary statistics files found.');
}

$job_id = "job_" . bin2hex(random_bytes(6)); // generate job id
$job_folder = __DIR__ . "/user_uploads/$job_id";
if (!is_dir($job_folder)) {
    mkdir($job_folder, 0777, true);
    chmod($job_folder, 0777);
}

// Save files list JSON for Python input
// $tmp_json_path = tempnam(sys_get_temp_dir(), 'job_files_') . '.json';
file_put_contents("$job_folder/studies.json", json_encode($files));

$python_script = __DIR__ . '/download_script.py';

// Build shell command for background execution
$cmd = escapeshellcmd("python3 $python_script $job_folder") . " > $job_folder/download.log 2>&1 &";

// Start process in background and get PID (optional)
$pid = shell_exec($cmd);

// Remove temporary JSON file (optional, or Python can delete it)
// unlink($tmp_json_path);

// Return JSON response immediately with job id for frontend
header('Content-Type: application/json');
echo json_encode(['job_id' => $job_id]);

// Optionally log $pid somewhere if you want to track process
exit;
