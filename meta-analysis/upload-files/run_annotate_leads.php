<?php
// Decode JSON payload
$input = json_decode(file_get_contents('php://input'), true);

$job_id = $input["job"] ?? "";

if (!$job_id) {
    echo json_encode(['success' => false, 'error' => 'Job ID missing']);
    exit;
}

$job_dir = __DIR__ . "../../user_uploads/$job_id";
$input_file = "$job_dir/meta_leads.tsv";

if (!file_exists($input_file)) {
    http_response_code(404);
    echo json_encode(["success" => false, "error" => "meta_leads.tsv not found for job $job_id"]);
    exit;
}

$cmd = escapeshellcmd("Rscript annotate_leads.R") . " " . escapeshellarg($job_id);
exec($cmd . " > $job_dir/meta_annotation.log 2>&1", $output, $return_var);

echo json_encode([
    "success" => $return_var === 0,
    "error" => $return_var !== 0 ? "R script failed. Check log." : null
]);
?>
