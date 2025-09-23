<?php
$jobId = $_GET['job'] ?? '';
if (empty($jobId)) {
    http_response_code(400);
    exit('Job ID required');
}

$jobDir = "../../user_uploads/" . $jobId;
$files = [];

if (is_dir($jobDir)) {
    $pattern = $jobDir . '/*_processed.ssf.log';
    $logFiles = glob($pattern);
    
    foreach ($logFiles as $logFile) {
        $basename = basename($logFile);
        // Extract the original filename by removing _processed.ssf.log
        $originalName = preg_replace('/_processed\.ssf\.log$/', '', $basename);
        $files[] = $originalName;
    }
}

header('Content-Type: application/json');
echo json_encode($files);
?>
