<?php
header('Content-Type: application/json');

$jobId = $_GET['job'] ?? '';
$jobId = preg_replace("/[^a-zA-Z0-9_-]/", "", $jobId);

$folder = "user_uploads/$jobId";
$csvFile = "$folder/meta_results.tsv";
$plotsDir = "$folder/meta_plots";

if (!file_exists($csvFile)) {
    echo json_encode(["success" => false, "error" => "Meta results CSV not found."]);
    exit;
}

if (!is_dir($plotsDir)) {
    mkdir($plotsDir, 0775, true);
}

$cmd = escapeshellcmd("nohup '/home/biomedinfo/gwaslab_env/bin/python' meta_generate_plots.py $jobId");
exec($cmd . " 2>&1", $output, $ret);

if ($ret !== 0) {
    echo json_encode(["success" => false, "error" => "Plot generation failed", "output" => $output]);
    exit;
}

$plots = glob("$plotsDir/*.png");
$relativePlots = array_map(function($p) { return $p; }, $plots);

echo json_encode(["success" => true, "plots" => $relativePlots]);
exit;
