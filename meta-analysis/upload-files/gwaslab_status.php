<?php
include('header.php');
include('connect.php');

$jobId = $_GET['job'] ?? die("Job ID missing");
$stmt = $conn->prepare("SELECT * FROM gwas_jobs WHERE job_id = ?");
$stmt->bind_param("s", $jobId);
$stmt->execute();
$result = $stmt->get_result();
$job = $result->fetch_assoc();

$jobDir = "user_uploads/$jobId";
$plotsDir = "$jobDir/plots";
$logFile = "$jobDir/preprocess.log";
$zipFile = "$jobDir/{$jobId}_results.zip";
$configFile = "$jobDir/job_config.json";
?>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css">
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.0/jszip.min.js"></script>

<style>
 :root{
  --bg:#f9f5f0; --card:#ffffff; --ink:#5d4037; --accent:#6a4d35; --soft:#fff3e0; --line:#e6dacd;
}
.status-container{max-width:1200px;margin:0 auto;padding:24px;}
h2,h3,h4{color:var(--ink);}
.log-box{
  background:var(--soft);border:1px solid var(--line);border-left:4px solid #a1887f;
  padding:14px;font-family:monospace;max-height:240px;overflow-y:auto;white-space:pre-wrap;border-radius:10px;
}
.global-loader,.tab-loader,.card-loader{
  border:6px solid #f3f3f3;border-top:6px solid var(--accent);border-radius:50%;width:40px;height:40px;animation:spin 1s linear infinite;margin:16px auto;
}
@keyframes spin{0%{transform:rotate(0)}100%{transform:rotate(360deg)}}
.card{
  background:var(--card);border:1px solid var(--line);border-radius:16px;box-shadow:0 3px 10px rgba(0,0,0,.06);padding:16px;margin-bottom:22px;
}
.card-title{display:flex;justify-content:space-between;align-items:center;margin-bottom:8px}
.file-name{font-weight:600;color:var(--ink)}
.tabs{display:flex;gap:8px;border-bottom:1px solid var(--line);margin-top:10px}
.tab-btn{
  background:transparent;border:none;padding:10px 12px;border-radius:10px 10px 0 0;cursor:pointer;color:var(--ink);opacity:.8
}
.tab-btn.active{background:var(--bg);opacity:1;border:1px solid var(--line);border-bottom-color:transparent}
.tab-panel{display:none;padding:14px;background:var(--bg);border:1px solid var(--line);border-top:none;border-radius:0 12px 12px 12px}
.tab-panel.active{display:block}
.plot-img{display:block;width:100%;max-width:100%;height:auto;border:1px solid #ccc;border-radius:10px;margin:10px 0;transition:transform .2s}
.plot-img:hover{transform:scale(1.02)}
.btn{
  background:var(--accent);color:#fff;border:none;border-radius:8px;padding:10px 16px;cursor:pointer
}
.btn:disabled{opacity:.6;cursor:not-allowed}
small.hint{color:var(--accent)}
.meta-box{background:var(--card);border:1px solid var(--line);border-radius:16px;padding:18px;margin-top:28px}
tfoot td{font-size:.9em;color:#7a6a60}
.download-wrap{margin-top:26px;text-align:right}
thead{color:var(--accent)}
.leads-table-wrap {
  overflow-x: auto;
}
.leads-table-wrap table.dataTable {
  width: 100% !important;
}
.leads-table-wrap th, .leads-table-wrap td {
    white-space: nowrap;
    max-width: 250px;
    overflow: auto;
    text-overflow: ellipsis;
}
.leads-table-wrap table.dataTable { width:100% !important; }


</style>

<div class="status-container">
  <h2>Initial QC Results</h2>
  <p>Job ID: <code><?= htmlspecialchars($jobId) ?></code></p>

  <h4>Processing Log</h4>
  <div id="log-box" class="log-box">Loading log...</div>

  <div id="global-loader" class="global-loader" aria-label="Loading plots"></div>
  <div id="cards-container"></div>

  <div class="download-wrap">
    <?php if (file_exists($zipFile)): ?>
      <a href="<?= htmlspecialchars($zipFile) ?>" class="btn" download>⬇ Download Results ZIP</a>
    <?php else: ?>
      <button class="btn" disabled title="ZIP will appear after processing">⬇ Download Results ZIP</button>
    <?php endif; ?>
  </div>

  <div id="meta-analysis-container" class="meta-box" style="display:none;">
    <?php
      $jobId_safe = $jobId;
      include('render_meta_table.php');
    ?>
  </div>
</div>

<script>
const jobId = <?= json_encode($jobId) ?>;
const cardsContainer = document.getElementById('cards-container');
const globalLoader = document.getElementById('global-loader');
const logBox = document.getElementById('log-box');

let displayed = new Set();
let lastLog = '';
let processingComplete = false;

// Smooth log polling with pinned scroll bottom and cards loading
function fetchLog() {
  if (processingComplete) return; // Stop polling if complete

  fetch("read_log.php?job=" + encodeURIComponent(jobId))
    .then(res => res.text())
    .then(t => {
      if (t !== lastLog) {
        const atBottom = logBox.scrollTop + logBox.clientHeight >= logBox.scrollHeight - 4;
        logBox.textContent = t;
        if (atBottom) logBox.scrollTop = logBox.scrollHeight;
        lastLog = t;
      }
      loadCards();
    })
    .catch(err => {
      console.error("Failed to fetch log:", err);
    });
}

// Enhanced loadCards function - creates cards for ALL processed files, not just those with plots
async function loadCards() {
  try {
    // First, get list of all processed log files to create cards for ALL files
    const logRes = await fetch(`list_processed_files.php?job=${encodeURIComponent(jobId)}`);
    const processedFiles = await logRes.json();
    
    // Also get plot files for those that have them
    const plotRes = await fetch("list_plots.php?job=" + encodeURIComponent(jobId));
    const plotFiles = await plotRes.json();
    
    const plotGroups = {};
    plotFiles.forEach(f => {
      const base = f.replace(/_(qq|manhattan)\.png$/, '');
      if (!plotGroups[base]) plotGroups[base] = {};
      if (/_qq\.png$/.test(f)) plotGroups[base].qq = f;
      if (/_manhattan\.png$/.test(f)) plotGroups[base].manhattan = f;
    });

    let anyCard = false;
    let allProcessed = true;

    // Create cards for ALL processed files (even those with no plots)
    for (const base of processedFiles) {
      anyCard = true;
      if (displayed.has(base)) continue;
      displayed.add(base);

      const safeBase = base.replace(/[^a-zA-Z0-9_.-]/g,'_');

      // Fetch the per-file processed log
      const logUrl = `user_uploads/${jobId}/${encodeURIComponent(base)}_processed.ssf.log?t=${Date.now()}`;
      let logText = '';
      let logSummary = '';
      
      try {
        const logFileRes = await fetch(logUrl);
        if (logFileRes.ok) {
          logText = await logFileRes.text();
          
          // Parse log for removed variants - this is the key information
          const removedMatches = logText.match(/Removed\s+(\d+)\s+variants\s+with\s+([^\.]+)\./g);
          const totalRemovedMatch = logText.match(/Removed\s+(\d+)\s+variants\s+with\s+bad statistics\s+in\s+total\./);
          const initialShapeMatch = logText.match(/Current Dataframe shape\s*:\s*(\d+)\s*x/);
          const finalShapeMatch = logText.match(/Current Dataframe shape\s*:\s*(\d+)\s*x[\s\S]*Memory usage:[\s\S]*Start to extract lead variants/);
          
          const initialVariants = initialShapeMatch ? parseInt(initialShapeMatch[1]) : 0;
          const totalRemoved = totalRemovedMatch ? parseInt(totalRemovedMatch[1]) : 0;
          
          if (totalRemoved > 0 && totalRemoved === initialVariants) {
            // All variants removed
            logSummary = `<div class="log-error" style="color:#721c24; font-weight:bold; margin-bottom:12px; padding:12px; background:#f8d7da; border:1px solid #f5c6cb; border-radius:6px;">
              ❌ <strong>Critical Issue:</strong> All ${initialVariants.toLocaleString()} variants were removed during quality control checks.
            </div>`;
            
            // Show specific reasons for removal
            if (removedMatches) {
              logSummary += '<div style="margin-bottom:8px;"><strong>Removal reasons:</strong><ul style="margin:4px 0 0 20px;">';
              removedMatches.forEach(match => {
                const reasonMatch = match.match(/Removed\s+(\d+)\s+variants\s+with\s+(.+)\./);
                if (reasonMatch) {
                  logSummary += `<li>${parseInt(reasonMatch[1]).toLocaleString()} variants: ${reasonMatch[2]}</li>`;
                }
              });
              logSummary += '</ul></div>';
            }
          } else if (totalRemoved > 0) {
            // Some variants removed
            const remaining = initialVariants - totalRemoved;
            logSummary = `<div class="log-warning" style="color:#856404; font-weight:bold; margin-bottom:8px; padding:8px; background:#fff3cd; border:1px solid #ffeaa7; border-radius:4px;">
              ⚠️ <strong>QC Applied:</strong> ${totalRemoved.toLocaleString()} of ${initialVariants.toLocaleString()} variants removed. ${remaining.toLocaleString()} variants retained.
            </div>`;
          } else if (initialVariants > 0) {
            // No variants removed
            logSummary = `<div class="log-success" style="color:#155724; font-weight:bold; margin-bottom:8px; padding:8px; background:#d4edda; border:1px solid #c3e6cb; border-radius:4px;">
              ✅ <strong>QC Passed:</strong> All ${initialVariants.toLocaleString()} variants passed quality control checks.
            </div>`;
          }
          
          // Check if processing is complete for this file
          if (!logText.includes("Finished outputting successfully!")) {
            allProcessed = false;
          }
        }
      } catch(err) {
        logText = 'Log file not available or still being generated...';
        logSummary = '<div style="color:#6c757d; font-style:italic;">Processing log will appear here once analysis is complete.</div>';
        allProcessed = false;
      }

      // Create card with Log tab as first tab (ALWAYS create card)
      const card = document.createElement('div');
      card.className = 'card';
      
      // Determine if plots are available
      const hasPlots = plotGroups[base];
      
      card.innerHTML = `
        <div class="card-title">
          <div class="file-name">${base}</div>
        </div>

        <div class="tabs" role="tablist" aria-label="${base} tabs">
          <button class="tab-btn active" data-target="log-${safeBase}" role="tab" aria-selected="true">📋 Processing Log</button>
          ${hasPlots ? `<button class="tab-btn" data-target="qq-${safeBase}" role="tab">QQ Plot</button>` : ''}
          ${hasPlots ? `<button class="tab-btn" data-target="man-${safeBase}" role="tab">Manhattan Plot</button>` : ''}
          ${hasPlots ? `<button class="tab-btn" data-target="lead-${safeBase}" role="tab">Lead Variants</button>` : ''}
          ${!hasPlots ? `<small style="color:#6c757d; margin-left:10px; font-style:italic;">(No plots available - see log for details)</small>` : ''}
        </div>

        <div id="log-${safeBase}" class="tab-panel active">
          ${logSummary}
          <button class="btn toggle-log" style="margin-bottom:8px; font-size:12px; padding:6px 12px;">Show Full Log ▼</button>
          <pre class="full-log" style="display:none; white-space: pre-wrap; max-height: 300px; overflow-y: auto; border: 1px solid var(--line); padding: 12px; border-radius: 8px; background: var(--soft); font-size: 11px; line-height: 1.3;">${logText}</pre>
        </div>
        ${hasPlots ? `<div id="qq-${safeBase}" class="tab-panel"><div class="tab-loader"></div></div>` : ''}
        ${hasPlots ? `<div id="man-${safeBase}" class="tab-panel"><div class="tab-loader"></div></div>` : ''}
        ${hasPlots ? `<div id="lead-${safeBase}" class="tab-panel"><div class="tab-loader"></div></div>` : ''}
      `;
      cardsContainer.appendChild(card);

      // Add toggle functionality for full log
      const toggleBtn = card.querySelector('.toggle-log');
      const fullLogPre = card.querySelector('.full-log');
      
      toggleBtn.addEventListener('click', () => {
        if (fullLogPre.style.display === 'none') {
          fullLogPre.style.display = 'block';
          toggleBtn.textContent = 'Hide Full Log ▲';
        } else {
          fullLogPre.style.display = 'none';
          toggleBtn.textContent = 'Show Full Log ▼';
        }
      });

      // Only add plot/lead functionality if plots exist
      if (hasPlots) {
        // Escape dots in selectors
        function escapeSelector(id) {
          return id.replace(/\./g, '\\.');
        }

        // Tabs behavior
        card.querySelectorAll('.tab-btn').forEach(btn => {
          btn.addEventListener('click', () => {
            card.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            card.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
            btn.classList.add('active');

            const escapedTarget = escapeSelector(btn.dataset.target);
            const target = card.querySelector('#' + escapedTarget);
            if (target) target.classList.add('active');

            // Initialize DataTable on lead variants tab if necessary
            if(btn.dataset.target === 'lead-' + safeBase) {
              const panel = document.getElementById('lead-' + safeBase);
              if(panel && panel.dataset.initialized !== "true") {
                setTimeout(() => {
                  const tableIdLead = 'lead-' + safeBase + '-table';
                  const tableId = tableIdLead.replace(/\./g, '\\.');
                  
                  if ($.fn.DataTable.isDataTable('#' + tableId)) {
                    $('#' + tableId).DataTable().destroy();
                  }
                  
                  $('#' + tableId).DataTable({
                    dom: 'Bfrtip',
                    buttons: ['excel'],
                    scrollX: true,
                    paging: $('#' + tableId + ' tbody tr').length > 10
                  });
                  panel.dataset.initialized = "true";
                }, 50);
              }
            }
          });
        });

        // Load plots and leads (only if hasPlots)
        if (plotGroups[base]?.qq) {
          const img = new Image();
          img.src = `user_uploads/${jobId}/plots/${plotGroups[base].qq}`;
          img.alt = `QQ plot for ${base}`;
          img.className = 'plot-img';
          img.onload = () => {
            const p = card.querySelector('#qq-' + escapeSelector(safeBase));
            if (p) {
              p.innerHTML = '';
              p.appendChild(img);
            }
          };
        }

        if (plotGroups[base]?.manhattan) {
          const img2 = new Image();
          img2.src = `user_uploads/${jobId}/plots/${plotGroups[base].manhattan}`;
          img2.alt = `Manhattan plot for ${base}`;
          img2.className = 'plot-img';
          img2.onload = () => {
            const p = card.querySelector('#man-' + escapeSelector(safeBase));
            if (p) {
              p.innerHTML = '';
              p.appendChild(img2);
            }
          };
        }

        // Load Leads panel
        loadLeads(base, 'lead-' + safeBase);
      }
    }

    if (anyCard) globalLoader.style.display = 'none';

    // Stop polling and hide main log if all processing is complete
    if (allProcessed && processedFiles.length > 0) {
      processingComplete = true;
      logBox.style.display = 'none';
      document.querySelector('h4').style.display = 'none';

      // Show meta-analysis container
      const metaAnalysisContainer = document.getElementById('meta-analysis-container');
      if (metaAnalysisContainer) {
        metaAnalysisContainer.style.display = '';
      }
      
      console.log('Processing complete - stopped polling');
    }

  } catch (error) {
    console.error('Error loading cards:', error);
  }
}

// Keep your existing loadLeads function and event delegation unchanged
function loadLeads(base, panelId, win = "500", pval = "5e-8") {
  const safeBase = base.replace(/[^a-zA-Z0-9_.-]/g, '_');
  const panel = document.getElementById(panelId);

  panel.innerHTML = `
    <div style="margin-bottom:10px; display:flex; align-items:center; gap:15px; flex-wrap:wrap;">
      <label style="display:flex; align-items:center; gap:5px;">
        Window size (kb): 
        <input type="number" id="win-${safeBase}" value="${win}" style="width:80px;" min="1">
      </label>
      <label style="display:flex; align-items:center; gap:5px;">
        P-value threshold: 
        <input type="text" id="pval-${safeBase}" value="${pval}" style="width:100px;">
      </label>
      <button class="btn btn-run-leads" data-base="${base}">Run Lead Extraction</button>
    </div>
    <div class="leads-table-wrap"><div class="tab-loader"></div></div>
  `;

  const tableWrap = panel.querySelector('.leads-table-wrap');
  const leadsUrl = `user_uploads/${jobId}/${encodeURIComponent(base)}_leads.tsv?t=${Date.now()}`;

  fetch(leadsUrl)
    .then(res => {
      if (!res.ok) throw new Error('No lead variants found');
      return res.text();
    })
    .then(text => {
      const lines = text.trim().split(/\r?\n/).filter(l => l.trim() !== '');
      if (lines.length === 0) throw new Error('Empty lead file');
      const headers = lines[0].split('\t');
      const tbody = lines.slice(1)
        .filter(line => line.trim() !== '')
        .map(line => line.split('\t'));

      if (tbody.length === 0) {
        tableWrap.innerHTML = `<small style="color: #6a4d35; display:block; margin-top:6px;">No lead variants found for the inputs.</small>`;
        panel.dataset.initialized = "false";
        return;
      }

      const tableId = 'lead-' + safeBase + '-table';
      tableWrap.innerHTML = `
        <table id="${tableId}" class="display nowrap compact" style="width:100%;font-size:90%;">
          <thead><tr>${headers.map(h => `<th>${h}</th>`).join('')}</tr></thead>
          <tbody>${tbody.map(row => `<tr>${row.map(cell => `<td>${cell}</td>`).join('')}</tr>`).join('')}</tbody>
        </table>
        <small style="color: #6a4d35; display:block; margin-top:6px;">
          Lead variants derived using <b>window size = ${win}kb</b>, <b>p-value threshold = ${pval}</b>
        </small>
      `;
      
      const tableIdEscaped = tableId.replace(/\./g, '\\.');
      if ($.fn.DataTable.isDataTable('#' + tableIdEscaped)) {
        $('#' + tableIdEscaped).DataTable().destroy();
      }

      $(() => {
        $('#' + tableIdEscaped).DataTable({
          dom: 'Bfrtip',
          buttons: ['excel'],
          scrollX: true,
          paging: $('#' + tableIdEscaped + ' tbody tr').length > 10
        });
      });

      panel.dataset.initialized = "false";
    })
    .catch(() => {
      tableWrap.innerHTML = '<small class="hint">No lead variants found yet.</small>';
    });
}

// Keep your existing event delegation
cardsContainer.addEventListener('click', function(e) {
  if (!e.target.classList.contains('btn-run-leads')) return;

  const base = e.target.dataset.base;
  const safeBase = base.replace(/[^a-zA-Z0-9_.-]/g, '_');
  const winInput = document.getElementById(`win-${safeBase}`);
  const pvalInput = document.getElementById(`pval-${safeBase}`);
  const panelId = 'lead-' + safeBase;
  const tableWrap = document.getElementById(panelId).querySelector('.leads-table-wrap');

  if (!winInput || !pvalInput) {
    alert("Input fields missing.");
    return;
  }

  const win = winInput.value.trim();
  const pval = pvalInput.value.trim();

  if (isNaN(win) || !pval) {
    alert("Please enter valid window size and p-value.");
    return;
  }

  tableWrap.innerHTML = '<div class="tab-loader"></div>';

  fetch('rerun_leads.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ jobId: jobId, studies: base, windowSize: win, pvalThreshold: pval })
  })
  .then(r => r.ok ? r.json() : Promise.reject())
  .then(response => {
    if (response.success) {
      loadLeads(base, panelId, win, pval);
    } else {
      tableWrap.innerHTML = `<small class="hint">Error: ${response.error}</small>`;
    }
  })
  .catch(() => {
    tableWrap.innerHTML = '<small class="hint">Error while running lead extraction.</small>';
  });
});

// Initial load and periodic polling (will stop automatically when complete)
fetchLog();
const pollInterval = setInterval(() => {
  if (processingComplete) {
    clearInterval(pollInterval);
  } else {
    fetchLog();
  }
}, 3000);
</script>


<?php include('footer.php'); ?>
