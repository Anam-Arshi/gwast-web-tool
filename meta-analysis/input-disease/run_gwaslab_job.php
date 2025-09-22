<?php
header('Content-Type: application/json');

$jobId = $_POST['job_id'] ?? null;

if (!$jobId) {
    echo json_encode(['success' => false, 'error' => 'Missing job ID']);
    exit;
}
$jobDir = "user_uploads/$jobId";

// Step 4: Start background processing
$python = '/home/biomedinfo/gwaslab_env/bin/python';
$cmd = "nohup $python run_gwaslab_job.py " . escapeshellarg($jobId) . " > $jobDir/debug.log 2>&1 &";
exec($cmd);

// Return success and maybe job or status info
echo json_encode(['success' => true, 'job_id' => $jobId]);
?>