<?php
header('Content-Type: application/json');

// Read and decode POST body
$data = json_decode(file_get_contents("php://input"), true);

// Validate jobId
if (!isset($data['jobId']) || empty($data['jobId'])) {
    echo json_encode(["success" => false, "error" => "Missing jobId"]);
    exit;
}

$jobId  = basename($data['jobId']); // sanitize
$folder = "user_uploads/$jobId";

// Ensure folder exists
if (!is_dir($folder)) {
    echo json_encode(["success" => false, "error" => "Job folder not found"]);
    exit;
}

// Save full input to JSON (this will be read later by Python/R)
$inputFile = "$folder/meta_input.json";
file_put_contents($inputFile, json_encode($data, JSON_PRETTY_PRINT));

// Create meta_info.json only for basic metadata (optional)
$metaInfo = [
    "method"       => ($data['model']['fixed'] ? 'Fixed-effects' : '') . 
                      ($data['model']['random'] ? ' + Random-effects' : ''),
    "tau2_method"  => $data['model']['random'] ? $data['tau2'] : null,
    "date"         => date("Y-m-d H:i:s"),
    "target_build" => $data['target_build'] ?? '38'
];
file_put_contents("$folder/meta_info.json", json_encode($metaInfo, JSON_PRETTY_PRINT));

// Paths
$python = '/home/biomedinfo/gwaslab_env/bin/python';

// Step 1: Run harmonization (if needed) – your Python script will check meta_input.json
$harmCmd = escapeshellcmd("nohup $python harmonize_for_meta.py $jobId");
exec($harmCmd . " >> $folder/harmonization.log 2>&1", $harmOutput, $harmStatus);

if ($harmStatus !== 0) {
    echo json_encode(["success" => false, "error" => "Harmonization step failed. Check logs."]);
    exit;
}

// Step 2: Run meta-analysis (R script will also read meta_input.json)
$cmd = escapeshellcmd("Rscript meta-analysis.R $jobId");
exec($cmd . " > $folder/meta_analysis.log 2>&1", $output, $return_var);

// Return status
if ($return_var === 0) {
    echo json_encode(["success" => true]);
} else {
    echo json_encode(["success" => false, "error" => "Meta-analysis R script failed. Check logs."]);
}
?>
