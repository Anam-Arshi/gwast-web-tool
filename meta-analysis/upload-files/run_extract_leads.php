<?php
header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"), true);
$jobId = $data['job_id'] ?? null;

if (!$jobId || !preg_match('/^[a-zA-Z0-9_\-]+$/', $jobId)) {
    echo json_encode(['success' => false, 'error' => 'Invalid or missing job_id']);
    exit;
}
$windowKb = $data['window_kb'] ?? 500;
$pvalThreshold = $data['pval_threshold'] ?? '5e-8';

$escapedJobId = escapeshellarg($jobId);
$cmd = "nohup '/home/biomedinfo/gwaslab_env/bin/python' meta_extract_leads.py $escapedJobId $windowKb $pvalThreshold";

exec($cmd . " 2>&1", $output, $status);

if ($status === 0) {
    echo json_encode(['success' => true, 'message' => implode("\n", $output)]);
} else {
    echo json_encode(['success' => false, 'error' => implode("\n", $output)]);
}
