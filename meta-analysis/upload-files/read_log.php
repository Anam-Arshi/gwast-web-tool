<?php
$logPath = $_GET["log"] ?? "";
if (!file_exists($logPath)) {
    echo "Waiting for log file...";
} else {
    echo file_get_contents($logPath);
}

?>


<?php
$jobId = $_GET['job'] ?? '';
$logPath = "../../user_uploads/$jobId/preprocess.log";
if (file_exists($logPath)) {
    readfile($logPath);
} else {
    echo "Log file not found yet...";
}
?>
