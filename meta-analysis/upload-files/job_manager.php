<?php
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/user_uploads/job_manager_errors.log');
error_reporting(E_ALL);

session_start();
include('connect.php');
header("Content-Type: application/json");

$jobId = $_POST['job_id'] ?? $_SESSION['job_id'] ?? null;
if (!$jobId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Job ID missing']);
    exit;
}

$upload_time = date("Y-m-d H:i:s");
$jobDir = "user_uploads/$jobId";

if (!is_dir($jobDir)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Job folder missing']);
    exit;
}

// Get uploaded files
$files = array_values(array_filter(scandir($jobDir), function($f) {
    return preg_match('/\.(tsv|csv|txt|gz|zip)$/i', $f);
}));

if (count($files) === 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'No valid input files uploaded']);
    exit;
}

// Step 1: Decode genome builds and formats info from POST
$fileBuildsFormats = [];
if (!empty($_POST['file_builds_formats'])) {
    $decoded = json_decode($_POST['file_builds_formats'], true);
    if (is_array($decoded)) {
        $fileBuildsFormats = $decoded;
    }
}

// Ensure every uploaded file has genome_build and format (default values if missing)
foreach ($files as $file) {
    if (empty($fileBuildsFormats[$file]['genome_build'])) {
        $fileBuildsFormats[$file]['genome_build'] = "38";
    }
    if (empty($fileBuildsFormats[$file]['file_format'])) {
        // Default format if not provided
        $fileBuildsFormats[$file]['file_format'] = "auto";
    }
}

// Step 2: Build detailed file info array
$fileDetails = [];
foreach ($files as $file) {
    $build = $fileBuildsFormats[$file]['genome_build'] ?? "38";
    $format = $fileBuildsFormats[$file]['file_format'] ?? "auto";
    $fileDetails[] = [
        "filename" => $file,
        "genome_build" => $build,
        "format" => $format
    ];
}

$fileList = implode(",", $files);

// Step 3: Save job data to MySQL (genome_build set as mixed for multiple builds)
$stmt = $conn->prepare("INSERT INTO gwas_jobs (job_id, created_at, genome_build, status, files)
                        VALUES (?, ?, ?, 'uploaded', ?)
                        ON DUPLICATE KEY UPDATE
                        genome_build = VALUES(genome_build),
                        files = VALUES(files),
                        status = 'uploaded'");
$defaultGenomeBuild = "mixed";
$stmt->bind_param("ssss", $jobId, $upload_time, $defaultGenomeBuild, $fileList);
$stmt->execute();
$stmt->close();

// Step 4: Save structured job configuration JSON for downstream scripts
$config = [
    "job_id" => $jobId,
    "files" => $fileDetails
];
file_put_contents("$jobDir/job_config.json", json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

// Step 5: Trigger background Python job processing
$python = '/home/biomedinfo/gwaslab_env/bin/python';
$cmd = "nohup $python run_gwaslab_job.py " . escapeshellarg($jobId) . " > $jobDir/debug.log 2>&1 &";
exec($cmd);

// Send success response with job ID
echo json_encode(['success' => true, 'job_id' => $jobId]);
?>
