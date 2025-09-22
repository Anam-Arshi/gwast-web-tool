
<?php
session_start();
header('Content-Type: application/json');

// --- Config / base dirs ---
$uploadsDir = realpath(__DIR__ . '/uploads');
if ($uploadsDir === false) {
    echo json_encode(['success' => false, 'error' => 'Uploads directory not found.']);
    exit;
}

// --- Validate session uploaded file ---
if (!isset($_SESSION['uploaded_file'])) {
    echo json_encode(['success' => false, 'error' => 'No file uploaded. Please upload a file first.']);
    exit;
}

$uploaded_file = $_SESSION['uploaded_file'];
$task = $_POST['task'] ?? '';

if (empty($task)) {
    echo json_encode(['success' => false, 'error' => 'No task specified']);
    exit;
}

// --- Input/output setup ---
$input_file  = $uploaded_file['path'];
$folder      = $uploaded_file['folder'];
$format      = $uploaded_file['format'];
$base_name   = pathinfo($uploaded_file['original_name'], PATHINFO_FILENAME);

// user-visible output (relative to project root)
$output_file = 'uploads/' . $folder . '/processed_' . $task . '_' . $base_name . '.tsv';

// --- Map task -> python script ---
$script_map = [
    // Manipulation tasks
    'standardize'      => 'standardize.py',
    'harmonize'        => 'harmonize.py',
    'assign_rsid'      => 'assign_rsid.py',
    'assign_chrpos'    => 'assign_chrpos.py',
    'liftover'         => 'liftover_utils.py',
    'data_conversion'  => 'data_conversion.py',

    // Utilities tasks
    'extract_lead'         => 'extract_lead_variants.py',
    'extract_novel'        => 'extract_novel_variants.py',
    'format_save'          => 'format_save.py',
    'convert_heritability' => 'convert_heritability.py',
    'r2_f'                 => 'r2_f.py',
    'infer_genome_build'   => 'infer_genome_build.py',
    'abf_finemapping'      => 'abf_finemapping.py'
];

if (!isset($script_map[$task])) {
    echo json_encode(['success' => false, 'error' => 'Unknown task: ' . $task]);
    exit;
}

$python_script = $script_map[$task];

// Collect all POST params (except task)
$params = $_POST;
unset($params['task']);

// Build config array and save JSON (python scripts are expected to accept JSON config path)
$config = [
    'task'        => $task,
    'input_file'  => $input_file,
    'output_file' => $output_file,
    'format'      => $format,
    'params'      => $params
];

$json_file = 'uploads/' . $folder . '/task_' . $task . '.json';
file_put_contents($json_file, json_encode($config, JSON_PRETTY_PRINT));

// --- Build and run Python command ---
$python = '/home/biomedinfo/gwaslab_env/bin/python';
$python_script_path = __DIR__ . '/' . $python_script; // assume script sits next to this php file
if (!file_exists($python_script_path)) {
    // fallback: try script name alone (in PATH)
    $python_script_path = $python_script;
}

$command = $python . ' ' . escapeshellarg($python_script_path) . ' ' . escapeshellarg($json_file) . ' 2>&1';
error_log("Executing command: " . $command);

// execute
$output = [];
$return_code = 0;
exec($command, $output, $return_code);
$logs = implode("\n", $output);

// --- After run: check result TSV exists ---
$output_abs = __DIR__ . '/' . $output_file;
if ($return_code === 0 && file_exists($output_abs)) {
    // Ensure output is inside uploads dir (avoid path traversal)
    $real_output = realpath($output_abs);
    if ($real_output === false || strpos($real_output, $uploadsDir) !== 0) {
        echo json_encode(['success' => false, 'error' => 'Output file is outside uploads directory (security).', 'logs' => $logs]);
        exit;
    }

    // Gather related files to include in ZIP:
    // Use glob to fetch output_file and any sibling files (e.g., .log, _summary.txt, plots...)
    $glob_pattern = dirname($real_output) . '/' . basename($real_output) . '*';
    $matched = glob($glob_pattern);
    $files_to_zip = [];
    foreach ($matched as $f) {
        if (!is_file($f)) continue;
        $real_f = realpath($f);
        if ($real_f === false) continue;
        // security: only include files under uploads dir
        if (strpos($real_f, $uploadsDir) !== 0) continue;
        $files_to_zip[] = $real_f;
    }

    // Always ensure the main TSV is included
    if (!in_array($real_output, $files_to_zip, true)) {
        array_unshift($files_to_zip, $real_output);
    }

    
    // Create ZIP in the same job folder
    $zip_basename = 'job_' . $task . '_' . time() . '.zip';
    $zip_path = __DIR__ . '/uploads/' . $folder . '/' . $zip_basename;

    $zip = new ZipArchive();
    if ($zip->open($zip_path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        // fall back: return success but mention unable to create zip
        $result_size  = formatBytes(filesize($real_output));
        $result_lines = countFileLines($real_output);

        echo json_encode([
            'success' => true,
            'message' => 'Task completed but failed to create ZIP archive.',
            'result_info' => [
                'filename' => basename($real_output),
                'size'     => $result_size,
                'variants' => ($result_lines > 1) ? ($result_lines - 1) : 0,
                'task'     => $task
            ],
            'files' => array_map('basename', $files_to_zip),
            'zip' => null,
            'logs' => $logs
        ]);
        exit;
    }

    // Add files to zip (use basename inside archive)
    foreach ($files_to_zip as $file_path) {
        $base = basename($file_path);
        error_log("Adding file to ZIP: $base");
        $zip->addFile($file_path, basename($file_path));
    }
    $zip->close();

    // Final checks and response
    if (!file_exists($zip_path)) {
        $result_size  = formatBytes(filesize($real_output));
        $result_lines = countFileLines($real_output);

        echo json_encode([
            'success' => true,
            'message' => 'Task completed but ZIP missing after creation attempt.',
            'result_info' => [
                'filename' => basename($real_output),
                'size'     => $result_size,
                'variants' => ($result_lines > 1) ? ($result_lines - 1) : 0,
                'task'     => $task
            ],
            'files' => array_map('basename', $files_to_zip),
            'zip' => null,
            'logs' => $logs
        ]);
        exit;
    }

    // Store full session result pointer AFTER ZIP creation with all needed info
    $_SESSION['result_zip'] = [
        'path' => 'uploads/' . $folder . '/' . $zip_basename,
        'created' => time(),
        'original_file' => $uploaded_file['original_name'],
        'task' => $task
    ];

    // send success response with zip info (zip size is numeric bytes)
    $result_size  = formatBytes(filesize($real_output));
    $result_lines = countFileLines($real_output);
    $zip_size_bytes = filesize($zip_path);

    echo json_encode([
        'success' => true,
        'message' => 'Task "' . ucfirst($task) . '" completed successfully!',
        'result_info' => [
            'filename' => basename($real_output),
            'size'     => $result_size,
            'variants' => ($result_lines > 1) ? ($result_lines - 1) : 0,
            'task'     => $task
        ],
        'files' => array_map('basename', $files_to_zip),
        'zip' => [
            'filename' => $zip_basename,
            'size'     => $zip_size_bytes
        ],
        'logs' => $logs
    ]);
    exit;

} else {
    // Task failed
    $error_message = "Task failed. ";
    if (!empty($output)) {
        $error_message .= "Error: " . implode("\n", $output);
    }

    echo json_encode([
        'success' => false,
        'error'   => $error_message,
        'command' => $command,
        'logs' => $logs
    ]);
    exit;
}

// ---- Helpers ----
function formatBytes($bytes, $precision = 2) {
    if ($bytes <= 0) return '0 B';
    $units = array('B','KB','MB','GB','TB');
    $i = floor(log($bytes, 1024));
    $i = max(0, min($i, count($units) - 1)); // clamp
    return round($bytes / pow(1024, $i), $precision) . ' ' . $units[$i];
}

function countFileLines($filename) {
    $lines = 0;
    $handle = @fopen($filename, 'r');
    if (!$handle) return 0;
    while (!feof($handle)) {
        $line = fgets($handle);
        if ($line !== false) $lines++;
    }
    fclose($handle);
    return $lines;
}
