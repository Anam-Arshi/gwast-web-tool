<?php
session_start();

// Check if result file exists in session
if (!isset($_SESSION['result_zip'])) {
    http_response_code(404);
    die('No processed file available for download.');
}

$result_zip = $_SESSION['result_zip'];
$file_path   = $result_zip['path'];

// Verify uploads directory base
$job_folder = dirname($file_path);

// Determine ZIP filename and path from GET param or fallback to session
$zip_filename = isset($_GET['zip']) ? basename($_GET['zip']) : basename($result_zip['path']);
$zip_path = $job_folder . '/' . $zip_filename;

// Check if ZIP file exists, create if missing
if (!file_exists($zip_path)) {
    $zip = new ZipArchive();
    if ($zip->open($zip_path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        http_response_code(500);
        die('Could not create ZIP file.');
    }

    // Add only relevant files (tsv, log, txt) from job folder
    $extensions = ['tsv', 'log', 'txt'];
    foreach (glob($job_folder . "/*") as $file) {
        $ext = pathinfo($file, PATHINFO_EXTENSION);
        if (in_array(strtolower($ext), $extensions, true)) {
            $zip->addFile($file, basename($file));
        }
    }
    $zip->close();
}

// Serve the ZIP file for download
header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="' . $zip_filename . '"');
header('Content-Length: ' . filesize($zip_path));
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');

flush();
readfile($zip_path);

// Optional: clean up ZIP after download to save space
unlink($zip_path);

// Optional: Log the download
$task = $result_zip['task'] ?? 'unknown';
error_log("Downloaded ZIP: " . $zip_filename . " (Task: " . $task . ")");
exit;
