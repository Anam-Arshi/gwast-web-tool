<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

session_start();
require_once 'connect.php'; // must set $conn = new mysqli(...)

// Persist traits in session across pagination GET requests
if (!empty($_POST['dis_grr']) && is_array($_POST['dis_grr'])) {
    $_SESSION['selected_disease_terms'] = array_filter(array_map('trim', $_POST['dis_grr']));
}
$traits = $_SESSION['selected_disease_terms'] ?? [];

// Pagination
$page  = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$size  = isset($_GET['size']) ? min(100, max(10, (int)$_GET['size'])) : 20;
$offset = ($page - 1) * $size;

// WHERE condition
$whereSql = '1=1';
$bind = [];
$types = '';
if (!empty($traits)) {
    $parts = [];
    foreach ($traits as $t) {
        $parts[] = 'LOWER(`disease_trait`) LIKE ?'; // updated column name
        $bind[] = '%' . strtolower($t) . '%';
        $types .= 's';
    }
    $whereSql = '(' . implode(' OR ', $parts) . ')';
}

// Count total rows
$sqlCount = "SELECT COUNT(*) AS cnt FROM `summary_stats_available` WHERE $whereSql";
$stmt = $conn->prepare($sqlCount);
if (!$stmt) { die('Count query error: ' . $conn->error); }
if (!empty($bind)) $stmt->bind_param($types, ...$bind);
$stmt->execute();
$res = $stmt->get_result();
$total = (int)($res->fetch_assoc()['cnt'] ?? 0);
$stmt->close();

// Fetch data with added columns
$sql = "SELECT 
  `pubmedid`, `first_author`, `date`, `journal`, `link`, 
  `study`, `disease_trait`, `mapped_trait`, `mapped_trait_uri`,
  `study_accession`, `summary_stats_location`,
  `initial_sample_size`, `replication_sample_size`, `association_count`
FROM `summary_stats_available`
WHERE $whereSql
ORDER BY `date` DESC, `study_accession` ASC
LIMIT ? OFFSET ?";

$stmt = $conn->prepare($sql);
if (!$stmt) { die('Data query error: ' . $conn->error); }
if (!empty($bind)) {
    $types2 = $types . 'ii';
    $params2 = array_merge($bind, [$size, $offset]);
    $stmt->bind_param($types2, ...$params2);
} else {
    $stmt->bind_param('ii', $size, $offset);
}
$stmt->execute();
$res = $stmt->get_result();
$rows = [];
while ($r = $res->fetch_assoc()) $rows[] = $r;
$stmt->close();

// Include header
include 'header.php';

// Ensure totalPages minimum 1
$totalPages = max(1, ceil($total / $size));
?>

<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>GWAST — Studies with Summary Statistics</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<!-- DataTables CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css"/>
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css"/>

<!-- jQuery and DataTables JS -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>

<style>
  :root { --brown:#8B7355; --light:#F5F2E8; --beige:#E8DCC0; --dark:#5D4E37; --gray:#6C757D; --border:#D9D3CC; }
  body { font-family: Inter, system-ui, sans-serif; margin:0; background:var(--light); color:var(--dark);}
  .container{max-width:1200px;margin:auto;padding:16px;}
  .top-bar{display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;}
  .btn{padding:8px 12px;border:none;border-radius:6px;cursor:pointer;font-weight:600;}
  .btn.primary{background:linear-gradient(135deg,var(--brown),var(--dark));color:#fff;}
  .btn.primary:disabled{opacity:0.5;cursor:not-allowed;}
  .search input{padding:8px 12px;border:1px solid var(--border);border-radius:6px;}
  .badges{margin-bottom:8px; display: inline-block; max-width:100%; overflow-x:auto;}
  .badge{display:inline-block;background:#EDE6DB;color:#5D4E37;border:1px solid var(--border);
         border-radius:999px;padding:3px 8px;font-size:.8rem;margin-right:5px;}
  .table-wrap{overflow:auto;
    /* background:#fff;
    border:1px solid var(--border);
    border-radius:8px; */
  }
  table.dataTable{ background:#fff;}
  table{width:100%;border-collapse:collapse;font-size:14px;}
  th,td{padding:10px;border-bottom:1px solid var(--border);text-align:left;}
  thead th{background:#EDE6DB;position:sticky;top:0;z-index:1;}
  tr:hover{background:#faf6ef;}
  .bottom-bar{display:flex;justify-content:space-between;align-items:center;margin-top:10px;}
  .pagination{display:flex;gap:4px;}
  .page-btn{padding:6px 10px;border:1px solid var(--border);border-radius:6px;background:#fff;text-decoration:none;color:inherit;}
  .page-btn.active{background:var(--brown);color:#fff;}
  .small{color:var(--gray);font-size:.9rem;}
  .link{color:#2f4f4f;text-decoration:underline;}
  
  /* Selection area styling */
  .selection-area{
    background:#fff;
    border:1px solid var(--border);
    border-radius:8px;
    padding:15px;
    margin-top:15px;
    display:flex;
    justify-content:space-between;
    align-items:center;
  }
  .selection-info{
    flex:1;
  }
  .selection-count{
    font-weight:bold;
    color:var(--brown);
    font-size:1.1rem;
  }
  .selection-desc{
    color:var(--gray);
    font-size:0.9rem;
    margin-top:4px;
  }
  .disease-header{
    font-size:1.2rem;
    font-weight:600;
    color:var(--dark);
    margin-bottom:8px;
  }
  input[type="checkbox"]{
    width:16px;
    height:16px;
    accent-color:var(--brown);
  }
</style>
</head>
<body>
<div class="container">

  <!-- Selected diseases with proper header -->
  <?php if ($traits): ?>
    <div class="disease-header">Selected diseases:</div>
    <div class="badges">
      <?php foreach ($traits as $t): ?>
        <span class="badge"><?php echo htmlspecialchars($t, ENT_QUOTES); ?></span>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

 

  <!-- Table -->
<div class="table-wrap">
  <table id="studiesTable" class="display" style="width:100%">
    <thead>
      <tr>
        <th style="width:40px;">Select</th>
        <th>GCST</th>
        <th>Study Title</th>
        <th>Year</th>
        <th>Trait</th>
        <th>Mapped Trait</th>
        <th>Journal</th>
        <th>Initial Sample Size</th>
        <th>Replication Sample Size</th>
        <th>Association Count</th>
        <th>PubMed</th>
      </tr>
    </thead>
    <tbody>
      <?php if (!$rows): ?>
        <tr><td colspan="11" class="small">No studies found.</td></tr>
      <?php else: ?>
        <?php foreach ($rows as $it):
          $gcst = $it['study_accession'];
          $year = substr($it['date'], 0, 4);
        ?>
          <tr data-gcst="<?php echo htmlspecialchars($gcst, ENT_QUOTES); ?>">
            <td><input type="checkbox" class="row-select"></td>
            <td><?php echo htmlspecialchars($gcst, ENT_QUOTES); ?></td>
            <td>
              <?php echo htmlspecialchars($it['study'], ENT_QUOTES); ?>
              <?php if (!empty($it['link'])): ?>
                <div><a class="link" href="<?php echo htmlspecialchars($it['link'], ENT_QUOTES); ?>" target="_blank" rel="noopener">Link</a></div>
              <?php endif; ?>
            </td>
            <td><?php echo htmlspecialchars($year, ENT_QUOTES); ?></td>
            <td><?php echo htmlspecialchars($it['disease_trait'], ENT_QUOTES); ?></td>
            <td>
              <?php echo htmlspecialchars($it['mapped_trait'], ENT_QUOTES); ?>
              <?php if (!empty($it['mapped_trait_uri'])): ?>
                <div class="small"><a class="link" href="<?php echo htmlspecialchars($it['mapped_trait_uri'], ENT_QUOTES); ?>" target="_blank" rel="noopener">EFO</a></div>
              <?php endif; ?>
            </td>
            <td><?php echo htmlspecialchars($it['journal'], ENT_QUOTES); ?></td>
            <td><?php echo htmlspecialchars($it['initial_sample_size'], ENT_QUOTES); ?></td>
            <td><?php echo htmlspecialchars($it['replication_sample_size'], ENT_QUOTES); ?></td>
            <td><?php echo htmlspecialchars($it['association_count'], ENT_QUOTES); ?></td>
            <td>
              <?php if ($it['pubmedid']): ?>
                <a class="link" href="https://www.ncbi.nlm.nih.gov/pubmed/<?php echo htmlspecialchars($it['pubmedid']); ?>" target="_blank" rel="noopener"><?php echo htmlspecialchars($it['pubmedid']); ?></a>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>


<!-- Bottom controls -->



  <!-- Selection area with proper descriptions -->
  <div class="selection-area">
    <div class="selection-info">
      <div class="selection-count" id="selInfo">0 studies selected (max 5)</div>
      <div class="selection-desc" id="selDesc">Select studies to proceed with download and analysis</div>
    </div>
 <form id="continueForm" action="start_job.php" method="post" style="margin:0">
  <input type="hidden" name="selected_gcst_json" id="selectedGcstField">
  <button type="submit" class="btn primary" id="proceedBtn" disabled>Proceed with Download and Analysis</button>
</form>
  </div>

</div>

<script>

$(document).ready(function() {
  const MAX_SELECTION = 5;
  const selected = new Map();

  // Initialize DataTables with buttons and options
  const table = $('#studiesTable').DataTable({
    pageLength: 10,
    lengthChange: false,
    dom: 'Bfrtip',
    buttons: ['csvHtml5'],
    columnDefs: [
      { orderable: false, targets: 0 } // Disable sorting on select-checkbox column
    ],
    order: [[3, 'desc'], [1, 'asc']] // Default sort: Year desc, GCST asc
  });

  // Event handler for checkboxes in table body
  $('#studiesTable tbody').on('change', '.row-select', function() {
    const tr = $(this).closest('tr');
    const gcst = tr.data('gcst');
    if (!gcst) return;

    if (this.checked) {
      if (selected.size >= MAX_SELECTION) {
        this.checked = false;
        alert(`You can select up to ${MAX_SELECTION} studies only.`);
        return;
      }
      selected.set(gcst, true);
    } else {
      selected.delete(gcst);
    }
    updateSelectionInfo();
  });

function updateSelectionInfo() {
  const count = selected.size;
  $('#selInfo').text(`${count} studies selected (max 5)`);

  if (count < 2) {
    // Disable for 0 or 1 selections
    $('#selDesc').text('Select at least 2 studies to proceed with  meta-analysis');
    $('#proceedBtn').prop('disabled', true);
  } else {
    // Enabled at 2 or more selections
    $('#selDesc').text(`${count} studies selected - ready to proceed with  meta-analysis`);
    $('#proceedBtn').prop('disabled', false);
  }

  // Update hidden input with JSON string of selected GCSTs
  $('#selectedGcstField').val(JSON.stringify(Array.from(selected.keys())));
}


  updateSelectionInfo();
});


// Handle form submission via AJAX to avoid popup blocking
$('#continueForm').on('submit', function(e) {
  e.preventDefault();

  const selectedGcstJson = $('#selectedGcstField').val();
  if (!selectedGcstJson || JSON.parse(selectedGcstJson).length < 2) {
    alert('Select at least 2 studies to proceed.');
    return;
  }

  // Open new tab immediately to avoid popup blocking
  const jobTab = window.open('about:blank', '_blank');

  $.ajax({
    url: 'start_job.php',
    type: 'POST',
    data: { selected_gcst_json: selectedGcstJson },
    dataType: 'json',
    success: function(response) {
      if (response.job_id) {
        // Redirect new tab to status page for this job
        jobTab.location = 'download_results.php?job_id=' + encodeURIComponent(response.job_id);
        jobTab.focus();
      } else {
        alert('Unexpected response from server.');
        jobTab.close();
      }
    },
    error: function() {
      alert('Failed to start job. Please try again.');
      jobTab.close();
    }
  });
});


</script>
</body>
</html>
<?php include 'footer.php'; ?>
