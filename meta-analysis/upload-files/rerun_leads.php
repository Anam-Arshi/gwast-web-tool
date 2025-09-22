<?php
// rerun_leads.php
header('Content-Type: application/json');
$data = json_decode(file_get_contents("php://input"), true);

$jobId = $data['jobId'] ?? '';
$study = $data['studies'] ?? '';
$windowSize = intval($data['windowSize'] ?? 500);
$pval = $data['pvalThreshold'] ?? '5e-8';

if (!$jobId || empty($study)) {
    echo json_encode(["success" => false, "error" => "Missing job ID or studies"]);
    exit;
}

$basePath = __DIR__ . "/user_uploads/$jobId";

    $python = '/home/biomedinfo/gwaslab_env/bin/python'; 
    $cmd = escapeshellcmd("nohup $python extract_leads.py $jobId $windowSize $pval $study");

    exec($cmd . ">> $basePath/extraction_log.log 2>&1", $out, $status);
    if ($status !== 0) {
        error_log("Lead extraction failed for $study: " . implode("\n", $out));
        echo json_encode(["success" => false, "error" => "Lead extraction failed for $study"]);
        exit;
    }


echo json_encode(["success" => true]);
