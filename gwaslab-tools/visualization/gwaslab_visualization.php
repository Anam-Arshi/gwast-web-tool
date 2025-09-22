<?php include 'header.php'; ?>
<div class="container py-5">

  <h3 class="text-center mb-4" style="color:#5D4E37;">GWASLab Visualizations</h3>
  <p class="text-center text-muted mb-5">
    Generate interactive and publication-ready plots from summary statistics.
  </p>

  <!-- Universal Upload Section (one for all plots) -->
  <div class="card mb-4 border-primary">
    <div class="card-header bg-light">
      <h6 class="mb-0"><i class="fas fa-upload me-2"></i>Upload Summary Statistics</h6>
    </div>
    <div class="card-body">
      <div class="row">
        <div class="col-md-8">
          <input type="file" class="form-control" id="sumstats_file" name="sumstats_file" accept=".txt,.tsv,.csv,.gz,.pkl">
        </div>
        <div class="col-md-4">
          <select class="form-control" id="input_format" name="input_format">
            <option value="auto">Auto-detect</option>
            <option value="ssf">GWAS-SSF</option>
            <option value="gwascatalog">GWAS Catalog</option>
            <option value="plink">PLINK</option>
            <option value="plink2">PLINK2</option>
            <option value="saige">SAIGE</option>
            <option value="regenie">REGENIE</option>
            <option value="fastgwa">FastGWA</option>
            <option value="metal">METAL</option>
            <option value="bolt_lmm">BOLT-LMM</option>
            <option value="vcf">GWAS-VCF</option>
            <option value="pickle">GWASLab Pickle (.pkl)</option>
          </select>
        </div>
      </div>
      <div id="upload-status" class="mt-3" style="display:none;">
        <h6>Upload Status</h6>
        <div id="file-info" class="row g-2"></div>
      </div>
    </div>
  </div>

  <ul class="nav nav-tabs" id="vizTab" role="tablist">
    <li class="nav-item">
      <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#mqqplot" type="button">Manhattan & QQ Plot</button>
    </li>
    <li class="nav-item">
      <button class="nav-link" data-bs-toggle="tab" data-bs-target="#region" type="button">Regional Plot</button>
    </li>
    <li class="nav-item">
      <button class="nav-link" data-bs-toggle="tab" data-bs-target="#brisbane" type="button">Brisbane Plot</button>
    </li>
      <li class="nav-item">
      <button class="nav-link" data-bs-toggle="tab" data-bs-target="#trumpet" type="button">Trumpet Plot</button>
    </li>
    <li class="nav-item">
      <button class="nav-link" data-bs-toggle="tab" data-bs-target="#allelefreq" type="button">Allele Frequency Comparison</button>
    </li>

        <li class="nav-item">
      <button class="nav-link" data-bs-toggle="tab" data-bs-target="#correlation" type="button">Correlation Heatmap</button>
    </li>
  </ul>

  <div class="tab-content border p-4 shadow-sm rounded-bottom bg-white">

    <!-- Manhattan & QQ -->
     <?php include 'plot_mqq.html'; ?>


    <!-- Regional -->
    <?php include 'plot_region.html'; ?>
   

    <!-- Brisbane -->
    <?php include 'plot_brisbane.html'; ?>

    <!-- Trumpet -->
    <?php include 'plot_trumpet.html'; ?>

    <!-- Allele Frequency Comparison -->
    <?php include 'plot_allelefreq.html'; ?>

    <!-- Correlation Heatmap -->
    <?php include 'plot_correlation.html'; ?>
 

  
  </div>
</div>

<script>
let isUploading = false;

// Universal file upload logic
document.getElementById('sumstats_file').addEventListener('change', function(e) {
  const file = e.target.files[0];
  if (file && !isUploading) {
    uploadFile(file);
  }
});

function uploadFile(file) {
  if (isUploading) return;
  isUploading = true;

  const formData = new FormData();
  const format = document.getElementById('input_format').value;

  formData.append('sumstats_file', file);
  formData.append('input_format', format);
  formData.append('action', 'upload');

  showUploadStatus('Uploading...', 'info');

  fetch('upload_handler.php', {
    method: 'POST',
    body: formData
  })
    .then(response => response.json())
    .then(data => {
      isUploading = false;
      if (data.success) {
        showUploadStatus('Upload successful! File ready for processing.', 'success');
        showFileInfo(data.file_info);
        window.uploadedFilePath = data.file_path;
      } else {
        showUploadStatus('Upload failed: ' + data.error, 'error');
      }
    })
    .catch(error => {
      isUploading = false;
      showUploadStatus('Upload error: ' + error.message, 'error');
    });
}

function showUploadStatus(message, type) {
  const statusDiv = document.getElementById('upload-status');
  const statusHeader = statusDiv.querySelector('h6');

  statusDiv.style.display = 'block';
  statusHeader.textContent = message;

  statusDiv.className = 'mt-3 alert ';
  if (type === 'success') {
    statusDiv.className += 'alert-success';
  } else if (type === 'error') {
    statusDiv.className += 'alert-danger';
  } else {
    statusDiv.className += 'alert-info';
  }
}

function showFileInfo(fileInfo) {
  const infoDiv = document.getElementById('file-info');
  infoDiv.innerHTML = `
    <div class="col-md-3"><strong>Filename:</strong><br><span class="text-muted">${fileInfo.filename}</span></div>
    <div class="col-md-3"><strong>Size:</strong><br><span class="text-muted">${fileInfo.size}</span></div>
    <div class="col-md-3"><strong>Format:</strong><br><span class="text-muted">${fileInfo.format}</span></div>
    <div class="col-md-3"><strong>Variants:</strong><br><span class="text-muted">${fileInfo.variants || 'Detecting...'}</span></div>
  `;
}

// All viz form submissions (no file selectors inside tab forms!)
document.querySelectorAll('.viz-form').forEach(form => {
  form.addEventListener('submit', function(e) {
    e.preventDefault();

    if (!window.uploadedFilePath) {
      alert('Please upload a summary statistics file first!');
      return;
    }

    const task = this.dataset.task || this.querySelector('input[name="task"]').value;

    // Use a hidden input (insert if not present) for file reference
    let hiddenInput = this.querySelector('input[name="uploaded_file_path"]');
    if (!hiddenInput) {
      hiddenInput = document.createElement('input');
      hiddenInput.type = 'hidden';
      hiddenInput.name = 'uploaded_file_path';
      this.appendChild(hiddenInput);
    }
    hiddenInput.value = window.uploadedFilePath;

    showProcessingStatus(task, 'Processing ' + task + '...', 'info');

    const formData = new FormData(this);

    fetch('run_gwaslab_plots.php', {
      method: 'POST',
      body: formData
    })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          showProcessingStatus(task, 'Task completed successfully!', 'success');
          showResultPlot(task, data.plot_url, data.plot_filename, data.is_pdf);
        } else {
          showProcessingStatus(task, 'Processing failed: ' + data.error, 'error');
          clearPlotDisplay(task);
        }
      })
      .catch(error => {
        showProcessingStatus(task, 'Error: ' + error.message, 'error');
        clearPlotDisplay(task);
      });
  });
});

function showProcessingStatus(task, message, type) {
  const statusDiv = document.getElementById(`processing-status-${task}`);
  if (!statusDiv) return;

  statusDiv.style.display = 'block';
  statusDiv.className = 'mt-3 alert ';

  if (type === 'success') {
    statusDiv.className += 'alert-success';
  } else if (type === 'error') {
    statusDiv.className += 'alert-danger';
  } else {
    statusDiv.className += 'alert-info';
  }

  let content = message;
  if (type === 'info') {
    content = `
      <div class="d-flex align-items-center">
        <div class="spinner-border spinner-border-sm me-2" role="status"></div>
        <strong>${message}</strong>
      </div>
    `;
  } else {
    content = `<strong>${message}</strong>`;
  }

  statusDiv.innerHTML = content;
}

function showResultPlot(task, plotUrl, plotFilename, isPdfRequested = false) {
  const plotDiv = document.getElementById(`plot-display-${task}`);
  const downloadSection = document.getElementById(`download-section-${task}`);
  const downloadBtnPng = document.getElementById(`download-plot-${task}-png`);
  const downloadBtnPdf = document.getElementById(`download-plot-${task}-pdf`);

  if (!plotDiv || !downloadSection || !downloadBtnPng || !downloadBtnPdf) return;

  plotDiv.innerHTML = `<img src="${plotUrl}" alt="Plot for ${task}" class="img-fluid rounded shadow-sm" />`;
  downloadSection.style.display = 'block';

  // Enable and set download link for PNG
  downloadBtnPng.disabled = false;
  downloadBtnPng.onclick = () => {
    window.open(plotUrl, '_blank');
  };

  if (isPdfRequested) {
    // Show PDF download button
    const pdfUrl = plotUrl.replace(/\.png$/i, '.pdf');
    downloadBtnPdf.style.display = 'inline-block';
    downloadBtnPdf.onclick = () => {
      window.open(pdfUrl, '_blank');
    };
  } else {
    downloadBtnPdf.style.display = 'none';
    downloadBtnPdf.onclick = null;
  }
}


function clearPlotDisplay(task) {
  const plotDiv = document.getElementById(`plot-display-${task}`);
  const downloadSection = document.getElementById(`download-section-${task}`);
  const downloadBtn = document.getElementById(`download-plot-${task}`);

  if (!plotDiv || !downloadSection || !downloadBtn) return;

  plotDiv.innerHTML = '';
  downloadSection.style.display = 'none';
  downloadBtn.disabled = true;
  downloadBtn.onclick = null;
}
</script>

<?php include 'footer.php'; ?>
