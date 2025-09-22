<?php
ini_set('display_errors',1); error_reporting(E_ALL);
session_start();
header('Content-Type: application/json');

// --- Config / base dirs ---
$uploadsDir = realpath(__DIR__ . '/uploads');
if ($uploadsDir === false) {
    echo json_encode(['success' => false, 'error' => 'Uploads directory not found.']);
    exit;
}

// --- Validate uploaded file reference ---
if (!isset($_SESSION['uploaded_file'])) {
    echo json_encode(['success' => false, 'error' => 'No file uploaded. Please upload a file first.']);
    exit;
}

$uploaded_file = $_SESSION['uploaded_file'];
$task = $_POST['task'] ?? '';

if (empty($task)) {
    echo json_encode(['success' => false, 'error' => 'No plot task specified']);
    exit;
}

// --- Input/output setup ---
$input_file  = $uploaded_file['path'];
$folder      = $uploaded_file['folder'];
$format      = $uploaded_file['format'];
$base_name   = pathinfo($uploaded_file['original_name'], PATHINFO_FILENAME);

// --- Map plot type ("task") -> python script ---
$python_script_map = [
    'mqqplot' => 'plot_mqq.py',
    'region'    => 'plot_region.py',
    'brisbane' => 'plot_brisbane.py',
    'trumpet'  => 'plot_trumpet.py',
    'allelefreq' => 'plot_allelefreq.py',
    'correlation' => 'plot_correlation.py'  
];

// Validate task type
if (!isset($python_script_map[$task])) {
    echo json_encode(['success' => false, 'error' => 'Unknown plot task: ' . $task]);
    exit;
}

$python_script = $python_script_map[$task];

// Output image (e.g. PNG), e.g. uploads/abc123/plot_manhattan_basefilename.png
$plot_filename = 'plot_' . $task . '_' . $base_name . '.png';
$output_plot   = 'uploads/' . $folder . '/' . $plot_filename;
$output_plot_abs = __DIR__ . '/' . $output_plot;

// Collect all POST params (except task and uploaded_file_path)
$params = $_POST;
unset($params['task']);
unset($params['uploaded_file_path']);

// Create config array expected by your plotting script
$config = [
    'task'        => $task,
    'input_file'  => $input_file,
    'output_file' => $output_plot_abs,
    'format'      => $format,
    'params'      => $params
];

$json_file = 'uploads/' . $folder . '/task_' . $task . '_config.json';
file_put_contents($json_file, json_encode($config, JSON_PRETTY_PRINT));

// --- Build and run Python command ---
$python = '/home/biomedinfo/gwaslab_env/bin/python'; // Set to your Python path
$python_script_path = __DIR__ . '/' . $python_script;

if (!file_exists($python_script_path)) {
    echo json_encode(['success' => false, 'error' => 'Python script missing: ' . $python_script]);
    exit;
}

$command = $python . ' ' . escapeshellarg($python_script_path) . ' ' . escapeshellarg($json_file) . ' 2>&1';
$output = [];
$return_code = 0;
exec($command, $output, $return_code);
$logs = implode("\n", $output);

// --- After run, check that output plot exists ---
if ($return_code === 0 && file_exists($output_plot_abs)) {
    // Security: Ensure output is inside uploads dir
    $real_output = realpath($output_plot_abs);
    if ($real_output === false || strpos($real_output, $uploadsDir) !== 0) {
        echo json_encode(['success' => false, 'error' => 'Output plot outside uploads directory (security).', 'logs' => $logs]);
        exit;
    }

    // For browser display: use relative URL (adjust if needed for your server config)
    $plot_url = $output_plot;

    echo json_encode([
        'success' => true,
        'plot_url' => $plot_url,
        'plot_filename' => $plot_filename,
        'logs' => $logs,
        'is_pdf'  => $params['save_pdf'] ?? false
    ]);
    exit;

} else {
    // Task failed or plot not created
    $error_message = "Plot task failed. ";
    if (!empty($output)) {
        $error_message .= "Error: " . implode("\n", $output);
    }

    echo json_encode([
        'success' => false,
        'error'   => $error_message,
        'command' => $command,
        'logs'    => $logs,
        'is_pdf'  => false
    ]);
    exit;
}
