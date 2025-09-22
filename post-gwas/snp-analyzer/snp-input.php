<?php
session_start();
$snpid = $_POST['snpid'] ?? '';

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

/* Pulse animation for when SNPs are detected */
/* .btn-analyze.ready {
  animation: readyPulse 2s infinite;
}

@keyframes readyPulse {
  0% { box-shadow: 0 0 0 0 rgba(93, 78, 55, 0.4); }
  70% { box-shadow: 0 0 0 10px rgba(93, 78, 55, 0); }
  100% { box-shadow: 0 0 0 0 rgba(93, 78, 55, 0); }
} */

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
  <h3 class="text-center mb-4" style="color:#5D4E37;">SNP Analysis Tool</h3>
  <p class="text-center text-muted mb-5">
    Analyze your genetic variants with <b>GWAS catalog & GRASP</b>. Enter SNP IDs below to get started.
  </p>

  <!-- Main SNP Input Card -->
  <div class="card mb-4 border-custom shadow-sm">
    <div class="card-header bg-light">
      <h6 class="mb-0"><i class="fas fa-dna me-2"></i>SNP Input</h6>
    </div>
    <div class="card-body">
      <form action="snp_result.php" method="post" name="myForm" id="myForm" onsubmit="return validateAndSubmit();">
        
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
          <small class="text-muted">Upload a text file containing SNP IDs</small>
        </div>

        <!-- Textarea Input -->
        <div class="mb-3">
          <div class="d-flex justify-content-between align-items-center mb-2">
            <label for="txtbox" class="form-label mb-0">Enter SNP IDs:</label>
            <small class="text-muted" id="snp-counter">0 SNPs detected</small>
          </div>
          <textarea 
            class="form-control" 
            id="txtbox" 
            name="snpid" 
            rows="8"
            required 
            placeholder="Enter your SNP IDs here...

Examples:
rs1975802, rs7191183, rs1319017

or

rs1975802
rs7191183
rs1319017"
            style="font-family: 'Courier New', monospace;"><?php echo htmlspecialchars($snpid); ?></textarea>
          <div class="form-text">
            <i class="fas fa-info-circle"></i>
            Supported formats: rs numbers, comma or newline separated. Maximum 10,000 SNPs per analysis.
          </div>
        </div>

        <!-- Action Buttons -->
        <div class="text-center">
          <button class="btn btn-analyze me-2" type="submit" id="analyzeBtn" disabled>
            <i class="fas fa-search me-2"></i>
            <span id="btnText">Analyze SNPs</span>
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
let snpCount = 0;

function updateSnpCounter() {
  const textarea = document.getElementById('txtbox');
  const text = textarea.value.trim();
  
  if (text === '') {
    snpCount = 0;
  } else {
    const snps = text.split(/[,\n\r\s]+/).filter(snp => {
      const cleaned = snp.trim();
      return cleaned !== '' && cleaned.match(/^rs\d+$/i);
    });
    snpCount = snps.length;
  }
  
  const counter = document.getElementById('snp-counter');
  const analyzeBtn = document.getElementById('analyzeBtn');
  const btnText = document.getElementById('btnText');
  
  if (counter) {
    counter.classList.add('counter-update');
    setTimeout(() => counter.classList.remove('counter-update'), 300);
    
    if (snpCount === 0) {
      counter.textContent = '0 SNPs detected';
      counter.className = 'text-muted';
      analyzeBtn.disabled = true;
      btnText.textContent = 'Analyze SNPs';
    } else if (snpCount > 10000) {
      counter.textContent = `${snpCount} SNPs (Too many! Limit: 10,000)`;
      counter.className = 'text-danger fw-bold';
      analyzeBtn.disabled = true;
      btnText.textContent = 'Too Many SNPs';
    } else if (snpCount > 1000) {
      counter.textContent = `${snpCount} SNPs detected`;
      counter.className = 'text-warning fw-bold';
      analyzeBtn.disabled = false;
      btnText.textContent = `Analyze ${snpCount} SNPs`;
    } else {
      counter.textContent = `${snpCount} SNPs detected`;
      counter.className = 'fw-bold';
      counter.style.color = '#5D4E37';
      analyzeBtn.disabled = false;
      btnText.textContent = `Analyze ${snpCount} SNPs`;
    }
  }
}

function loadCommaExample() {
  const snps = "rs1975802,rs7191183,rs1319017,rs4925114,rs11409090,rs35604463,rs1899543,rs11783093,rs10083370,rs3617,rs36043959,rs11165867,rs1150754";
  const textarea = document.getElementById("txtbox");
  
  textarea.value = snps;
  updateSnpCounter();
  
  textarea.classList.add('border-success');
  setTimeout(() => {
    textarea.classList.remove('border-success');
  }, 2000);
  
  showToast('Example loaded successfully!', 'success');
}

function loadLineExample() {
  const snps = `rs1975802
rs7191183
rs1319017
rs4925114
rs11409090
rs35604463
rs1899543
rs11783093
rs10083370
rs3617
rs36043959
rs11165867
rs1150754`;
  const textarea = document.getElementById("txtbox");
  
  textarea.value = snps;
  updateSnpCounter();
  
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
      updateSnpCounter();
      showToast(`File loaded successfully! Found ${snpCount} SNPs.`, 'success');
    };
    
    reader.onerror = function() {
      showToast('Error reading file. Please try again.', 'error');
    };
    
    reader.readAsText(file);
  }
}

function validateAndSubmit() {
  const textarea = document.getElementById('txtbox');
  const snpVal = textarea.value.trim();
  
  if (snpVal === '') {
    textarea.classList.add('is-invalid');
    textarea.focus();
    showToast('Please enter SNP IDs before analyzing.', 'error');
    
    setTimeout(() => {
      textarea.classList.remove('is-invalid');
    }, 3000);
    
    return false;
  }
  
  if (snpCount === 0) {
    showToast('No valid SNP IDs found. Please check your input format.', 'error');
    textarea.focus();
    return false;
  }
  
  if (snpCount > 10000) {
    showToast('Too many SNPs! Please limit to 10,000 SNPs per analysis.', 'error');
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
    timeout = setTimeout(updateSnpCounter, 300);
  });
  
  textarea.addEventListener('paste', function() {
    setTimeout(updateSnpCounter, 100);
  });
  
  updateSnpCounter();
});

</script>

<?php include('footer.php'); ?>
