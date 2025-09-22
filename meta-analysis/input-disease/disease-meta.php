<?php
session_start();

// Safely clear previous step data but keep session alive
unset($_SESSION['selected_disease_ids'], $_SESSION['selected_study_ids']);

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf_token'];

require_once 'connect.php';
include('header.php');
?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<style>
/* ===== Theme Variables ===== */
:root {
  --brown: #8B7355;
  --light: #F5F2E8;
  --beige: #E8DCC0;
  --dark: #5D4E37;
  --accent: #A0906B;
  --gray: #6C757D;
  --light-gray: #F8F9FA;
  --dark-gray: #495057;
}

/* ===== Layout & Components ===== */
.disease-analyzer-container { max-width: 1200px; margin: 20px auto; padding: 0 20px; }
.analyzer-header {
  background: linear-gradient(135deg, var(--light) 0%, var(--beige) 100%);
  border: 1px solid var(--brown);
  border-radius: 8px;
  padding: 30px;
  margin-bottom: 30px;
  text-align: center;
  box-shadow: 0 2px 10px rgba(139, 115, 85, 0.1);
}
.analyzer-header h2 { color: var(--dark); font-size: 2.2rem; margin-bottom: 10px; font-weight: 600; }
.analyzer-subtitle { color: var(--brown); font-size: 1.05rem; font-weight: 400; }
.analyzer-form {
  background: white; border: 1px solid var(--brown); border-radius: 8px;
  padding: 40px; box-shadow: 0 2px 10px rgba(139, 115, 85, 0.1);
}
.form-label { display: block; color: var(--dark); font-weight: 600; margin-bottom: 8px; font-size: 1.1rem; }
.form-note { color: var(--brown); font-size: 0.9rem; margin-left: 10px; }

/* ===== Selection Info ===== */
.selection-info {
  background: var(--light-gray);
  border-left: 4px solid var(--brown);
  padding: 10px 15px;
  margin-top: 10px;
  border-radius: 0 4px 4px 0;
  font-size: .9rem; color: var(--dark-gray);
  min-height: 24px;
}

/* ===== Buttons ===== */
.gwast-btn { padding: 12px 25px; border: none; border-radius: 6px; font-size: 1rem; font-weight: 600; cursor: pointer; margin: 0 10px; transition: all 0.3s ease; min-width: 120px; }
.gwast-btn:disabled { opacity: 0.6; cursor: not-allowed; }
.gwast-btn-primary { background: linear-gradient(135deg, var(--brown), var(--dark)); color: white; }
.gwast-btn-primary:hover:not(:disabled) { background: linear-gradient(135deg, var(--dark), var(--brown)); transform: translateY(-1px); }
.gwast-btn-secondary { background: linear-gradient(135deg, var(--gray), var(--dark-gray)); color: white; }
.gwast-btn-secondary:hover { background: linear-gradient(135deg, var(--dark-gray), var(--gray)); transform: translateY(-1px); }

/* ===== Select2 Styling ===== */
.disease-select { width: 100% !important; }
.select2-container--default .select2-selection--multiple {
  border: 2px solid var(--brown); border-radius: 6px; min-height: 120px; padding: 8px; background: white;
}
.select2-container--default .select2-selection--multiple .select2-selection__choice {
  background: var(--brown); color: white; padding: 6px 10px; margin: 3px 3px 0 0; border-radius: 4px;
}

/* ===== Overlay ===== */
.loading-overlay {
  position: fixed; top:0; left:0; width:100%; height:100%;
  background: rgba(93,78,55,0.8); display:none; z-index:99999;
}
.loading-content {
  position:absolute; top:50%; left:50%; transform:translate(-50%,-50%);
  background:white; padding:30px; border-radius:8px;
  text-align:center; border:2px solid var(--brown);
}
.spinner { width:40px; height:40px; border:3px solid var(--beige); border-top:3px solid var(--brown); border-radius:50%; animation: spin 1s linear infinite; margin:0 auto 15px; }
@keyframes spin { 0%{transform:rotate(0)} 100%{transform:rotate(360deg)} }
</style>
<div class="disease-analyzer-container">
  <div class="analyzer-header">
    <h2>Select Diseases</h2>
    <p class="analyzer-subtitle">
      Select up to 6 diseases/traits. Next, you’ll pick studies from GWAS Catalog with summary stats.
    </p>
  </div>

  <div class="analyzer-form">
    <form id="diseaseForm" action="disease-meta-results.php" method="post">
      <label for="diseases" class="form-label" id="diseasesLabel">
        Diseases/Traits/Phenotypes:
        <span class="form-note">(Max 6)</span>
      </label>

      <select id="diseases" name="dis_grr[]" multiple class="disease-select"
              aria-labelledby="diseasesLabel" aria-describedby="selectionInfo" aria-live="polite">
        <?php
        try {
          $stmt = $conn->prepare("select disease_merge from distinct_diseases");
          $stmt->execute();
          $result = $stmt->get_result();
          while ($row = $result->fetch_assoc()) {
            $disease = htmlspecialchars($row['disease_merge'], ENT_QUOTES);
            echo "<option value=\"{$disease}\">{$disease}</option>";
          }
        } catch (Exception $e) {
          echo "<option disabled>Error loading diseases</option>";
        }
        ?>
      </select>

      <div id="selectionInfo" class="selection-info">
        <strong>Selected:</strong> <span id="selectedCount">0</span> of 6 — start typing to search
      </div>

    

      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf, ENT_QUOTES); ?>">

      <div class="form-actions" style="margin-top:20px;">
        <button type="submit" class="gwast-btn gwast-btn-primary" id="submitBtn" disabled>
          <span class="btn-text">Continue</span>
          <span class="loading-spinner" style="display:none;">Processing...</span>
        </button>
        <button type="button" id="resetBtn" class="gwast-btn gwast-btn-secondary">Reset Form</button>
      </div>
    </form>
  </div>
</div>

<!-- Loading -->
<div id="loadingOverlay" class="loading-overlay">
  <div class="loading-content">
    <div class="spinner"></div>
    <p class="loading-text">Processing your request...</p>
  </div>
</div>
<script>
$(function(){
  const MAX = 6, LS_KEY = 'gwast_selected_diseases';
  let isInitialized = false;

  function initSelect2(){
    if (isInitialized) return;
    $('#diseases').select2({
      placeholder: 'Search and select...',
      allowClear: true,
      maximumSelectionLength: MAX,
      width: '100%',
      closeOnSelect: false
    });
    $('#diseases').on('select2:open', () => {
      document.querySelector('.select2-search__field')?.focus();
    });
    isInitialized = true;
  }

  function updateInfo(){
    let count = ($('#diseases').val() || []).length;
    $('#selectedCount').text(count);
    $('#submitBtn').prop('disabled', count===0);
  }

  function saveLocal(){
    localStorage.setItem(LS_KEY, JSON.stringify($('#diseases').val() || []));
  }
  function restoreLocal(){
    let v = JSON.parse(localStorage.getItem(LS_KEY) || '[]');
    if (v.length) { $('#diseases').val(v).trigger('change'); }
  }

  function showLoading(){
    $('#loadingOverlay').fadeIn(200);
    $('.btn-text').hide();
    $('.loading-spinner').show();
  }

  initSelect2();
  restoreLocal();
  updateInfo();

  $('#diseases').on('select2:select select2:unselect change', function(){
    updateInfo();
    saveLocal();
  });


  $('#resetBtn').click(function(){
    $('#diseases').val(null).trigger('change');
    updateInfo();
    saveLocal();
  });

  $('#diseaseForm').submit(function(e){
    let count = ($('#diseases').val() || []).length;
    if (count === 0 || count > MAX) {
      alert(`Please select between 1 and ${MAX} items`);
      return false;
    }
    showLoading();
  });
});
</script>
<?php
include('footer.php');
?>