<?php include 'header.php'; ?>
<div class="container py-5">

  <h3 class="text-center mb-4" style="color:#5D4E37;">GWASLab Utilities</h3>
  <p class="text-center text-muted mb-5">
    Use <b>GWASLab's</b> additional helper functions for summary statistics. Select an uploaded file and run tasks.
  </p>

  <!-- Universal Upload Section -->
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
      <!-- show upload status -->
      <div id="upload-status" class="mt-3" style="display:none;">
        <h6>Upload Status</h6>
        <div id="file-info" class="row g-2"></div>
      </div>
    </div>
  </div>

 <ul class="nav nav-tabs" id="utilitiesTab" role="tablist">
  <li class="nav-item">
    <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#extract_lead" type="button">Extract Lead Variants</button>
  </li>
  <li class="nav-item">
    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#extract_novel" type="button">Extract Novel Variants</button>
  </li>
  <li class="nav-item">
    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#format_save" type="button">Format and Save</button>
  </li>
  <li class="nav-item">
    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#convert_heritability" type="button">Convert Heritability</button>
  </li>
  <li class="nav-item">
    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#r2_f" type="button">Per-SNP R2 and F</button>
  </li>
  <li class="nav-item">
    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#infer_genome_build" type="button">Infer Genome Build</button>
  </li>

    <li class="nav-item">
    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#abf_finemapping" type="button">Fine-mapping</button>
  </li>
</ul>

  <div class="tab-content border p-4 shadow-sm rounded-bottom bg-white">

    <!-- Extract leads -->
     <?php include "extract_lead_variants.html"; ?>

     <!-- Extract novel -->
      <?php include "extract_novel_variants.html"; ?>

      <!-- Format and save -->
      <?php include "format_save.html"; ?>

      <!-- Convert heritability -->
      <?php include "convert_heritability.html"; ?>

      <!-- Per-SNP R2 and F -->
        <?php include "r2_f.html"; ?>

    <!-- Infer genome build -->
      <?php include "infer_genome_build.html"; ?>  

      <!-- Fine-mapping -->
      <?php include "abf_finemapping.html"; ?>

    

    
    

  </div>
</div>

<script>
let isUploading = false;

// File upload handling
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
        <div class="col-md-3">
            <strong>Filename:</strong><br>
            <span class="text-muted">${fileInfo.filename}</span>
        </div>
        <div class="col-md-3">
            <strong>Size:</strong><br>
            <span class="text-muted">${fileInfo.size}</span>
        </div>
        <div class="col-md-3">
            <strong>Format:</strong><br>
            <span class="text-muted">${fileInfo.format}</span>
        </div>
        <div class="col-md-3">
            <strong>Variants:</strong><br>
            <span class="text-muted">${fileInfo.variants || 'Detecting...'}</span>
        </div>
    `;
}

// Handle ALL form submissions
document.querySelectorAll('form[action="run_gwaslab.php"]').forEach(form => {
    form.addEventListener('submit', function(e) {
        e.preventDefault();

        if (!window.uploadedFilePath) {
            alert('Please upload a summary statistics file first!');
            return;
        }

        const task = this.dataset.task || this.querySelector('input[name="task"]').value;

        // Add uploaded file path to form
        let hiddenInput = this.querySelector('input[name="uploaded_file_path"]');
        if (!hiddenInput) {
            hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = 'uploaded_file_path';
            this.appendChild(hiddenInput);
        }
        hiddenInput.value = window.uploadedFilePath;

        // Show processing status for this specific tab
        showProcessingStatus(task, 'Processing ' + task + '...', 'info');

        // Get form data
        const formData = new FormData(this);

        fetch('run_gwaslab.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showProcessingStatus(task, 'Task completed successfully!', 'success');

                if (task === 'convert_heritability') {
                    const resultDiv = document.getElementById(`result-info-${task}`);
                    resultDiv.style.display = 'block';
                    resultDiv.innerHTML = `
                      <h6>Liability-scale Heritability Conversion Result</h6>
                      <p><strong>h² (liability scale):</strong> ${data.result.h2_liab.toFixed(5)}</p>
                      ${data.result.se_liab !== null && data.result.se_liab !== undefined ? 
                        `<p><strong>Standard Error:</strong> ${data.result.se_liab.toFixed(5)}</p>` : ''
                      }
                    `;
                }
                else if (task === 'infer_genome_build') {
                    const resultDiv = document.getElementById(`result-info-${task}`);
                    resultDiv.style.display = 'block';
                    // Clear previous content
                    resultDiv.innerHTML = `<h6>Inferred Genome Build Result</h6>`;

                    // Display formatted inference results
                    const res = data.result || {};
                    const build = res.build || 'Unknown';
                    const statusCode = res.status_code || res.statusCode || 'N/A';
                    const totalCount = res.total_count || res.totalCount || 'N/A';
                    const matchCount = res.match_count || res.matchCount || 'N/A';

                    resultDiv.innerHTML += `
                      <p><strong>Inferred Build:</strong> GRCh${build}</p>
                      <p><strong>Status Code:</strong> ${statusCode}</p>
                      <p><strong>Total Variants Analyzed:</strong> ${totalCount}</p>
                      <p><strong>Matching HapMap3 Variants:</strong> ${matchCount}</p>
                    `;
                }
                else {
                    showResultInfo(task, data.result_info);

                    if ((task === 'extract_lead' || task === 'extract_novel') 
                        && data.result_info && data.result_info.filename) {

                        // Robustly get job folder from uploadedFilePath regardless of absolute/relative path
                        let parts = window.uploadedFilePath.replace(/^\/+/, '').split('/');
                        let uploadsIndex = parts.indexOf('uploads');
                        let jobFolder = (uploadsIndex !== -1 && parts.length > uploadsIndex + 1) ? parts[uploadsIndex + 1] : parts[1];

                        const resultFileUrl = 'uploads/' + jobFolder + '/' + data.result_info.filename;

                        fetch(resultFileUrl)
                            .then(res => res.text())
                            .then(text => {
                                const tableHtml = tsvToHtmlTable(text);
                                const resultDiv = document.getElementById(`result-info-${task}`);
                                resultDiv.innerHTML = ''; // Clear previous content
                                if (tableHtml) {
                                    resultDiv.innerHTML += `<h6>${task === 'extract_lead' ? 'Lead' : 'Novel'} Variants Table</h6>` + tableHtml;
                                } else {
                                    resultDiv.innerHTML += `<div class="alert alert-warning">No ${task === 'extract_lead' ? 'lead' : 'novel'} variants found for the given inputs.</div>`;
                                }
                            })
                            .catch(() => {
                                // fail silently, user can still download results
                            });
                    }
                    enableZipDownload(task, data.zip);
                }
            } else {
                showProcessingStatus(task, 'Processing failed: ' + data.error, 'error');
            }
        })
        .catch(error => {
            showProcessingStatus(task, 'Error: ' + error.message, 'error');
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

function showResultInfo(task, resultInfo) {
    const resultDiv = document.getElementById(`result-info-${task}`);
    if (!resultDiv) return;
    
    resultDiv.style.display = 'block';
    resultDiv.className = 'card mt-3 border-success';
    resultDiv.innerHTML = `
        <div class="card-header bg-light">
            <h6 class="mb-0"><i class="fas fa-check-circle me-2"></i>Processing Results</h6>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <strong>Result File:</strong><br>
                    <span class="text-muted">${resultInfo.filename}</span>
                </div>
                <div class="col-md-3">
                    <strong>File Size:</strong><br>
                    <span class="text-muted">${resultInfo.size}</span>
                </div>
                <div class="col-md-3">
                    <strong>Variants:</strong><br>
                    <span class="text-muted">${resultInfo.variants.toLocaleString()}</span>
                </div>
                <div class="col-md-3">
                    <strong>Task:</strong><br>
                    <span class="badge bg-success">${resultInfo.task}</span>
                </div>
            </div>
        </div>
    `;
}

function showLogs(task, logs) {
    const logSection = document.getElementById(`log-section-${task}`);
    const logContent = document.getElementById(`log-content-${task}`);
    if (!logSection || !logContent) return;

    logSection.style.display = 'block';
    logContent.textContent = logs;
    logContent.scrollTop = logContent.scrollHeight;
    // keep hidden until user clicks "View Logs"
}


function toggleLogs(task) {
    const logContent = document.getElementById(`log-content-${task}`);
    const btn = document.querySelector(`#log-section-${task} button`);

    if (logContent.style.display === 'none') {
        logContent.style.display = 'block';
        btn.innerHTML = '<i class="fas fa-terminal me-2"></i>Hide Logs';
    } else {
        logContent.style.display = 'none';
        btn.innerHTML = '<i class="fas fa-terminal me-2"></i>View Logs';
    }
}



function enableZipDownload(task, zipInfo) {
    const section = document.getElementById(`download-section-${task}`);
    if (!section) return;

    section.style.display = 'block';

    const btn = document.getElementById(`download-zip-${task}`);
    const sizeEl = document.getElementById(`zip-size-${task}`);
    const ready = section.querySelector('.download-ready');

    if (zipInfo && zipInfo.filename) {
        // set button
        btn.setAttribute("data-zip", zipInfo.filename);
        btn.disabled = false;
        btn.classList.remove('btn-outline-primary');
        btn.classList.add('btn-primary');

        // show size if provided
        if (sizeEl && typeof zipInfo.size === 'number') {
            sizeEl.textContent = `~ ${humanFileSize(zipInfo.size)}`;
        }

        // ready banner
        if (ready) {
            ready.style.display = 'block';
            ready.innerHTML = `
              <i class="fas fa-download me-2"></i>
              <strong>Download Ready!</strong> All result files packaged into one ZIP.
            `;
        }
    }
}

function humanFileSize(bytes) {
    if (!Number.isFinite(bytes)) return '';
    const thresh = 1024;
    if (Math.abs(bytes) < thresh) return bytes + ' B';
    const units = ['KB','MB','GB','TB','PB','EB','ZB','YB'];
    let u = -1;
    do {
        bytes /= thresh;
        ++u;
    } while (Math.abs(bytes) >= thresh && u < units.length - 1);
    return bytes.toFixed(1) + ' ' + units[u];
}

function downloadZip(btnEl) {
    const zipName = btnEl.dataset.zip;
    if (!zipName) {
        alert('ZIP not ready yet.');
        return;
    }

    btnEl.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Preparing...';
    btnEl.disabled = true;

    setTimeout(() => {
        btnEl.innerHTML = '<i class="fas fa-file-archive me-2"></i>Download All (ZIP)';
        btnEl.disabled = false;
    }, 800);

    // Use the selected zip filename in the query
    window.location.href = "download_files.php?zip=" + encodeURIComponent(zipName);
}

function tsvToHtmlTable(tsv) {
    const lines = tsv.trim().split('\n');
    if (lines.length < 2) return null; // no data or only header
    
    const headers = lines[0].split('\t');
    const rows = lines.slice(1).map(line => line.split('\t'));
    
    let html = '<div class="table-responsive"><table class="table table-striped table-bordered">';
    // header row
    html += '<thead><tr>';
    headers.forEach(h => { html += `<th>${h}</th>`; });
    html += '</tr></thead><tbody>';
    // data rows
    rows.forEach(cols => {
        html += '<tr>';
        cols.forEach(c => { html += `<td>${c}</td>`; });
        html += '</tr>';
    });
    html += '</tbody></table></div>';
    return html;
}

</script>

<?php include 'footer.php'; ?>
