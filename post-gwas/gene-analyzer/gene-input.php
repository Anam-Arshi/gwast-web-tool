<?php
session_start();
$geneid = $_POST['geneid'] ?? '';

// Destroy existing session and cookie for fresh state
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]);
}
session_destroy();

include("connect.php");
include('header.php');
?>

<style>
/* Custom button hover effects */
/* Simplified button styling */
.btn-analyze {
  background-color: #5D4E37;
  color: white;
  border: none;
  transition: all 0.2s ease;
}

.btn-analyze:hover {
  background-color: #4a3d2b;
  color: white;
  transform: translateY(-1px);
  box-shadow: 0 2px 8px rgba(93, 78, 55, 0.2);
}

.btn-analyze:active {
  transform: translateY(0);
  box-shadow: 0 1px 4px rgba(93, 78, 55, 0.1);
}

.btn-analyze:disabled {
  background-color: #8d8d8d;
  color: #ffffff;
  cursor: not-allowed;
  transform: none;
  box-shadow: none;
}

/* Custom border for card */
.border-custom {
  border-color: #5D4E37 !important;
}

/* Textarea focus enhancement */
#txtbox:focus {
  border-color: #5D4E37;
  box-shadow: 0 0 0 0.2rem rgba(93, 78, 55, 0.25);
}

/* Counter animations */
.counter-update {
  animation: counterPop 0.3s ease;
}

@keyframes counterPop {
  0% { transform: scale(1); }
  50% { transform: scale(1.1); }
  100% { transform: scale(1); }
}
</style>

<div class="container py-5">
  <h3 class="text-center mb-4" style="color:#5D4E37;">Gene Analysis Tool</h3>
  <p class="text-center text-muted mb-5">
    Analyze gene functions and pathways with <b>gene databases & annotations</b>. Enter gene symbols below to get started.
  </p>

  <!-- Main Gene Input Card -->
  <div class="card mb-4 border-custom shadow-sm">
    <div class="card-header bg-light">
      <h6 class="mb-0"><i class="fas fa-dna me-2"></i>Gene Input</h6>
    </div>
    <div class="card-body">
      <form action="gene_result.php" method="post" name="myForm" id="myForm" onsubmit="return validateAndSubmit();">
        
        <!-- Example Links -->
        <div class="text-center mb-3">
          <span class="text-muted me-2">Quick Examples:</span>
          <button type="button" class="btn btn-outline-secondary btn-sm me-2" onclick="loadCommaExample();">
            <i class="fas fa-comma"></i> Comma Separated
          </button>
          <button type="button" class="btn btn-outline-secondary btn-sm" onclick="loadLineExample();">
            <i class="fas fa-list"></i> Line Separated
          </button>
        </div>

        <!-- File Upload -->
        <div class="mb-3">
          <input type="file" class="form-control" id="flin" onchange="handleFileUpload()" accept=".txt,.csv">
          <small class="text-muted">Upload a text file containing gene symbols</small>
        </div>

        <!-- Textarea Input -->
        <div class="mb-3">
          <div class="d-flex justify-content-between align-items-center mb-2">
            <label for="txtbox" class="form-label mb-0">Enter Gene Symbols:</label>
            <small class="text-muted" id="gene-counter">0 genes detected</small>
          </div>
          <textarea 
            class="form-control" 
            id="txtbox" 
            name="snpid" 
            rows="8"
            required 
            placeholder="Enter your gene symbols here...

Examples:
TP53, BRCA1, APOE

or

TP53
BRCA1
APOE"
            style="font-family: 'Courier New', monospace;"><?php echo htmlspecialchars($geneid); ?></textarea>
          <div class="form-text">
            <i class="fas fa-info-circle"></i>
            Supported formats: Gene symbols (e.g., TP53, BRCA1), comma or newline separated. Maximum 5,000 genes per analysis.
          </div>
        </div>

        <!-- Action Buttons -->
        <div class="text-center">
          <button class="btn btn-analyze me-2" type="submit" id="analyzeBtn" disabled>
            <i class="fas fa-search me-2"></i>
            <span id="btnText">Analyze Genes</span>
          </button>
          <button class="btn btn-outline-secondary" type="button" onclick="location.href='index.php';">
            <i class="fas fa-arrow-left me-2"></i>Back to Home
          </button>
        </div>

      </form>
    </div>
  </div>

</div>

<script>
let geneCount = 0;

function updateGeneCounter() {
  const textarea = document.getElementById('txtbox');
  const text = textarea.value.trim();
  
  if (text === '') {
    geneCount = 0;
  } else {
    const genes = text.split(/[,\n\r\s]+/).filter(gene => {
      const cleaned = gene.trim();
      return cleaned !== '' && cleaned.match(/^[A-Z0-9\-_]+$/i);
    });
    geneCount = genes.length;
  }
  
  const counter = document.getElementById('gene-counter');
  const analyzeBtn = document.getElementById('analyzeBtn');
  const btnText = document.getElementById('btnText');
  
  if (counter) {
    counter.classList.add('counter-update');
    setTimeout(() => counter.classList.remove('counter-update'), 300);
    
    if (geneCount === 0) {
      counter.textContent = '0 genes detected';
      counter.className = 'text-muted';
      analyzeBtn.disabled = true;
      btnText.textContent = 'Analyze Genes';
    } else if (geneCount > 5000) {
      counter.textContent = `${geneCount} genes (Too many! Limit: 5,000)`;
      counter.className = 'text-danger fw-bold';
      analyzeBtn.disabled = true;
      btnText.textContent = 'Too Many Genes';
    } else if (geneCount > 1000) {
      counter.textContent = `${geneCount} genes detected`;
      counter.className = 'text-warning fw-bold';
      analyzeBtn.disabled = false;
      btnText.textContent = `Analyze ${geneCount} Genes`;
    } else {
      counter.textContent = `${geneCount} genes detected`;
      counter.className = 'fw-bold';
      counter.style.color = '#5D4E37';
      analyzeBtn.disabled = false;
      btnText.textContent = `Analyze ${geneCount} Genes`;
    }
  }
}

function loadCommaExample() {
  const genes = "TP53,BRCA1,BRCA2,APOE,TNF,EGFR,MYC,APC,CDKN2A,BCL2,VEGFA,IL6,ESR1";
  const textarea = document.getElementById("txtbox");
  
  textarea.value = genes;
  updateGeneCounter();
  
  textarea.classList.add('border-success');
  setTimeout(() => {
    textarea.classList.remove('border-success');
  }, 2000);
  
  showToast('Example loaded successfully!', 'success');
}

function loadLineExample() {
  const genes = `TP53
BRCA1
BRCA2
APOE
TNF
EGFR
MYC
APC
CDKN2A
BCL2
VEGFA
IL6
ESR1`;
  const textarea = document.getElementById("txtbox");
  
  textarea.value = genes;
  updateGeneCounter();
  
  textarea.classList.add('border-success');
  setTimeout(() => {
    textarea.classList.remove('border-success');
  }, 2000);
  
  showToast('Example loaded successfully!', 'success');
}

function handleFileUpload() {
  const fileInput = document.getElementById('flin');
  const file = fileInput.files[0];
  
  if (file) {
    if (file.size > 5 * 1024 * 1024) {
      showToast('File too large! Please use files smaller than 5MB.', 'error');
      fileInput.value = '';
      return;
    }
    
    const reader = new FileReader();
    reader.onload = function(e) {
      document.getElementById('txtbox').value = e.target.result;
      updateGeneCounter();
      showToast(`File loaded successfully! Found ${geneCount} genes.`, 'success');
    };
    
    reader.onerror = function() {
      showToast('Error reading file. Please try again.', 'error');
    };
    
    reader.readAsText(file);
  }
}

function validateAndSubmit() {
  const textarea = document.getElementById('txtbox');
  const geneVal = textarea.value.trim();
  
  if (geneVal === '') {
    textarea.classList.add('is-invalid');
    textarea.focus();
    showToast('Please enter gene symbols before analyzing.', 'error');
    
    setTimeout(() => {
      textarea.classList.remove('is-invalid');
    }, 3000);
    
    return false;
  }
  
  if (geneCount === 0) {
    showToast('No valid gene symbols found. Please check your input format.', 'error');
    textarea.focus();
    return false;
  }
  
  if (geneCount > 5000) {
    showToast('Too many genes! Please limit to 5,000 genes per analysis.', 'error');
    return false;
  }
  
  // Show loading state
  const analyzeBtn = document.getElementById('analyzeBtn');
  const btnText = document.getElementById('btnText');
  analyzeBtn.disabled = true;
  btnText.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Processing...';
  
  return true;
}

function showToast(message, type = 'info') {
  const toast = document.createElement('div');
  toast.className = `alert alert-${type === 'error' ? 'danger' : type === 'success' ? 'success' : 'info'} alert-dismissible fade show position-fixed`;
  toast.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
  toast.innerHTML = `
    <i class="fas fa-${type === 'error' ? 'exclamation-circle' : type === 'success' ? 'check-circle' : 'info-circle'} me-2"></i>
    ${message}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  `;
  
  document.body.appendChild(toast);
  
  setTimeout(() => {
    if (toast.parentNode) {
      toast.remove();
    }
  }, 4000);
}

// Initialize when page loads
document.addEventListener('DOMContentLoaded', function() {
  const textarea = document.getElementById('txtbox');
  
  let timeout;
  textarea.addEventListener('input', function() {
    clearTimeout(timeout);
    timeout = setTimeout(updateGeneCounter, 300);
  });
  
  textarea.addEventListener('paste', function() {
    setTimeout(updateGeneCounter, 100);
  });
  
  updateGeneCounter();
});

</script>

<?php include('footer.php'); ?>
