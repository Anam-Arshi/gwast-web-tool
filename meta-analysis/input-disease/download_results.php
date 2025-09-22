<?php
// download_results.php

$job_id = $_GET['job_id'] ?? null;
if (!$job_id) { 
    echo "<p style='color:red;'>Missing job id.</p>"; 
    return; 
}

$job_folder = __DIR__ . "/user_uploads/$job_id";
$config_file = "$job_folder/job_config.json";
$progress_file = "$job_folder/progress.json";
$log_file = "$job_folder/download.log"; // Add log file path

// Include your site's header
include 'header.php';

// Function to parse download errors from log file
function parseDownloadErrors($log_file) {
    $errors = [];
    if (!file_exists($log_file)) {
        return $errors;
    }
    
    $log_content = file_get_contents($log_file);
    $lines = explode("\n", $log_content);
    
    foreach ($lines as $line) {
        // Look for "Failed to download" lines
        if (strpos($line, 'Failed to download') !== false) {
            // Extract URL and error message
            preg_match('/Failed to download ([^:]+): (.+)/', $line, $matches);
            if (count($matches) >= 3) {
                $url = $matches[1];
                $error_msg = $matches[2];
                
                // Extract filename from URL
                $filename = basename(parse_url($url, PHP_URL_PATH));
                // Extract GCST ID from URL
                preg_match('/GCST\d+/', $url, $gcst_matches);
                $gcst_id = $gcst_matches[0] ?? 'Unknown';
                
                $errors[$gcst_id][$filename] = $error_msg;
            }
        }
    }
    return $errors;
}

// Function to check if download is still in progress
function isDownloadInProgress($log_file) {
    if (!file_exists($log_file)) {
        return true;
    }
    
    $log_content = file_get_contents($log_file);
    // Check if log contains completion indicators
    return !preg_match('/Job config file saved to/', $log_content);
}
?>

<div style="min-height:80vh;background:#f9f6ef;padding:32px;">
  <div style="max-width:900px;margin:32px auto;background:#fff3e6;box-shadow:0 2px 10px #e7dac4;border-radius:12px;">
    <h2 style="color:#684d28;padding:28px 28px 0 28px;margin:0;font-weight:800;letter-spacing:.02em;">Download Results</h2>
    <div style="padding:0 28px 32px 28px;">

<?php
if (!file_exists($config_file)) {
    // Parse errors from log file
    $download_errors = parseDownloadErrors($log_file);
    $is_downloading = isDownloadInProgress($log_file);
    
    // Show progress for each file if available
    $progress = file_exists($progress_file) 
        ? json_decode(file_get_contents($progress_file), true) 
        : null;

    if ($progress) {
        foreach ($progress as $gcst_id => $files) {
            echo "<h3 style='color:#9e7a34;margin-top:24px;font-size:1.12em;'>" . htmlspecialchars($gcst_id) . "</h3>";
            
            // Check if there are errors for this GCST ID
            $has_errors = isset($download_errors[$gcst_id]) && !empty($download_errors[$gcst_id]);
            
            echo "<table style='width:100%;background:#fff7ec;border-radius:7px;margin-bottom:20px;'>";
            echo "<thead><tr>
                    <th style='background:#f2debe;'>File</th>
                    <th style='background:#f2debe;'>Progress</th>
                    <th style='background:#f2debe;'>Status</th>
                  </tr></thead><tbody>";
                  
            foreach ($files as $fname => $finfo) {
                $percent = isset($finfo['percent']) ? intval($finfo['percent']) : 0;
                $status = htmlspecialchars($finfo['status'] ?? 'pending');
                
                // Check if this file has an error
                $file_error = '';
                if ($has_errors && isset($download_errors[$gcst_id][$fname])) {
                    $file_error = $download_errors[$gcst_id][$fname];
                    $status = 'Failed';
                    $percent = 0;
                }
                
                // Determine row styling based on status
                $row_style = '';
                $status_style = '';
                if ($status === 'Failed') {
                    $row_style = 'background:#ffeaea;';
                    $status_style = 'color:#d32f2f;font-weight:bold;';
                }
                
                echo "<tr style='$row_style'>
                        <td>" . htmlspecialchars($fname) . "</td>
                        <td>";
                
                if ($status !== 'Failed') {
                    echo "<div style='width:100px;background:#f2e7c2;border-radius:4px;overflow:hidden;display:inline-block;margin-right:7px;'>
                            <div style='background:#e7b96c;height:13px;width:" . $percent . "%;transition:.3s;'></div>
                          </div>
                          $percent%";
                } else {
                    echo "<span style='color:#d32f2f;'>Download Failed</span>";
                }
                
                echo "</td>
                        <td style='$status_style'>$status</td>
                      </tr>";
                
                // Show error details if available
                if ($file_error) {
                    echo "<tr style='$row_style'>
                            <td colspan='3'>
                              <div style='background:#ffebee;border-left:4px solid #d32f2f;padding:8px 12px;margin:4px 0;border-radius:0 4px 4px 0;'>
                                <strong style='color:#d32f2f;'>Error:</strong> 
                                <span style='color:#666;font-size:0.9em;'>" . htmlspecialchars($file_error) . "</span>
                              </div>
                            </td>
                          </tr>";
                }
            }
            echo "</tbody></table>";
        }
        
        if ($is_downloading) {
            echo "<div style='color:#8a6b32;font-size:1.01em;margin-top:16px;'>Files are downloading. This page will refresh automatically when ready.</div>";
        } else {
            // Check if there are any errors
            $total_errors = 0;
            foreach ($download_errors as $gcst_errors) {
                $total_errors += count($gcst_errors);
            }
            
            if ($total_errors > 0) {
                echo "<div style='background:#ffebee;border:1px solid #ffcdd2;border-radius:6px;padding:12px;margin-top:16px;'>
                        <div style='color:#d32f2f;font-weight:bold;margin-bottom:8px;'>⚠️ Download Issues Detected</div>
                        <div style='color:#666;'>Some files failed to download. You can try re-downloading or proceed with available files.</div>
                      </div>";
            }
        }
    } else {
        echo "<div style='color:#8a6b32;font-size:1.01em;'>Files are still downloading. Please wait...</div>";
    }
    
    // Only auto-refresh if still downloading
    if ($is_downloading) {
        ?>
        <script>
          setTimeout(() => location.reload(), 5000);
        </script>
        <?php
    }
} else {
    // Show download links when job is complete - UPDATED FOR NEW STRUCTURE
    $config = json_decode(file_get_contents($config_file), true);
    if (!$config) {
        echo "<div style='color:red;'>Job config file is corrupted or unreadable.</div>";
    } else {
        $files = $config['files'] ?? []; // This is now an array of objects
        
        // Check for any missing files due to download errors
        $download_errors = parseDownloadErrors($log_file);
        $all_error_files = [];
        foreach ($download_errors as $gcst_errors) {
            $all_error_files = array_merge($all_error_files, array_keys($gcst_errors));
        }
        
        if (!empty($all_error_files)) {
            echo "<div style='background:#fff3e0;border:1px solid #ffcc02;border-radius:6px;padding:12px;margin-bottom:16px;'>
                    <div style='color:#e65100;font-weight:bold;margin-bottom:8px;'>⚠️ Some Files Missing</div>
                    <div style='color:#666;'>The following files failed to download: " . implode(', ', $all_error_files) . "</div>
                  </div>";
        }
        
        echo "<table style='width:100%;background:#fff7ec;border-radius:7px;'>";
        echo "<thead>
                <tr>
                  <th style='background:#f2debe;'>File Name</th>
                  <th style='background:#f2debe;'>Genome Build</th>
                  <th style='background:#f2debe;'>Format</th>
                  <th style='background:#f2debe;'>Download Link</th>
                </tr>
              </thead><tbody>";
              
        // Updated to handle new file structure
        foreach ($files as $file_info) {
            $filename = htmlspecialchars($file_info['filename'] ?? 'Unknown');
            $build = htmlspecialchars($file_info['genome_build'] ?? 'Unknown');
            $format = htmlspecialchars($file_info['format'] ?? 'Unknown');
            $dl = htmlspecialchars("user_uploads/$job_id/" . $file_info['filename']);
            
            echo "<tr>
                    <td>$filename</td>
                    <td>$build</td>
                    <td>$format</td>
                    <td><a style='color:#78592e;font-weight:600;text-decoration:underline;' href='$dl' download>Download</a></td>
                  </tr>";
        }
        echo "</tbody></table>";
    }
}
?>

<?php
// Updated completion check for new structure
$is_complete = false;

if (file_exists($config_file)) {
    $config = json_decode(file_get_contents($config_file), true);
    if ($config && !empty($config['files']) && is_array($config['files'])) {
        $is_complete = true;
    }
}
?>

<button id="proceedBtn"
    style="margin-top:20px; padding:10px 20px; background:#78592e; color:white; border:none; border-radius:6px; cursor:pointer; font-weight:700;"
    <?= $is_complete ? "" : "disabled" ?>>
    <?= $is_complete ? "Proceed" : "Download in Progress..." ?>
</button>

    </div>
  </div>
</div>

<?php if ($is_complete) : ?>
    <script>
  const proceedBtn = document.getElementById('proceedBtn');

  proceedBtn.addEventListener('click', function () {
    proceedBtn.disabled = true;
    proceedBtn.innerText = "Redirecting...";

    fetch("run_gwaslab_job.php", {
      method: "POST",
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded'
      },
      body: new URLSearchParams({
        job_id: "<?= htmlspecialchars($job_id) ?>"
      })
    })
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        window.location.href = "gwaslab_status.php?job=" + encodeURIComponent(data.job_id);
      } else {
        alert("Error: " + data.error);
        proceedBtn.disabled = false;
        proceedBtn.innerText = "Proceed";
      }
    })
    .catch(err => {
      alert("Unexpected error occurred.");
      proceedBtn.disabled = false;
      proceedBtn.innerText = "Proceed";
    });
  });
</script>
<?php endif; ?>

<?php
// Include your site's footer
include 'footer.php';
?>
