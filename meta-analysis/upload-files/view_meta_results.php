<?php
$jobId = $_GET['job'] ?? die("Job ID missing");
$jobId = preg_replace("/[^a-zA-Z0-9_-]/", "", $jobId);
$folder = "user_uploads/$jobId";
$logFile = "$folder/meta_analysis.log";
$csvFile = "$folder/meta_results.tsv";
$metaInfoFile = "$folder/meta_info.json";
$harmonizeLogFile = "$folder/harmonization.log";
$showHarmonize = file_exists($harmonizeLogFile);

include('header.php');
?>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css">
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.0/jszip.min.js"></script>

<style>
.container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 30px;
}
.card {
    background: #ffffff;
    border-radius: 16px;
    padding: 24px;
    margin-bottom: 30px;
    box-shadow: 0 3px 8px rgba(0, 0, 0, 0.08);
}
.card h3 {
    color: #5d4037;
    margin-bottom: 15px;
}
.log-box {
    background: #fff3e0;
    border-left: 4px solid #a1887f;
    padding: 15px;
    font-family: monospace;
    max-height: 200px;
    overflow-y: auto;
    white-space: pre-wrap;
}
.spinner {
    border: 6px solid #f3f3f3;
    border-top: 6px solid #6a4d35;
    border-radius: 50%;
    width: 40px;
    height: 40px;
    animation: spin 1s linear infinite;
    margin: 20px auto;
}
@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
.btn-meta {
    background: #6a4d35;
    color: white;
    padding: 8px 16px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
}
/* meta details style */
.meta-list {
    list-style: none;
    padding-left: 0;
}
.meta-list li {
    padding: 6px 0;
    border-bottom: 1px solid #e0d4c8;
}
.meta-list li:last-child {
    border-bottom: none;
}
.meta-label {
    font-weight: bold;
    color: #5d4037;
}
.meta-value {
    color: #333;
}
.gcst-tag {
    display: inline-block;
    background: #e3cfc2;
    color: #4e342e;
    padding: 3px 8px;
    border-radius: 12px;
    margin: 2px;
    font-size: 90%;
    cursor: pointer;
}
.gcst-tag:hover {
    background: #d7bba9;
}
.copy-confirm {
    font-size: 85%;
    color: green;
    margin-left: 10px;
}

</style>

<div class="container">
    <h2>Meta-analysis Results</h2>
    <p>Job ID: <code><?= htmlspecialchars($jobId) ?></code></p>

<div class="card">
    <h3>Meta-analysis Details</h3>
    <div id="meta-info-box">
        <p><em>Loading metadata...</em></p>
    </div>
</div>


    <?php if ($showHarmonize): ?>
    <div class="card" id="harmonization-log-card" style="display:none;">
        <h3>Harmonization Log</h3>
        <div id="harmonization-log" class="log-box">Loading harmonization log...</div>
        <div id="harmonization-spinner" class="spinner"></div>
    </div>
    <?php endif; ?>

    <div class="card">
        <h3>Meta-analysis Log</h3>
        <div id="log-box" class="log-box">Loading log...</div>
        <div id="spinner" class="spinner"></div>
    </div>

    <div class="card" id="meta-results-section" style="display:none;">
        <h3>Meta-analysis Table</h3>
        <div id="meta-results-spinner" class="spinner" style="margin: 20px auto;"></div>
        <div id="results-table"></div>
        <p><a href="<?= $csvFile ?>" download class="btn-meta">Download Full Results (TSV)</a></p>
    </div>


<div class="card" id="plots-card">
    <h3>Plots (QQ & Manhattan)</h3>
    <p>
        <button class="btn-meta" id="plot-btn" onclick="runMetaPlots()">Generate Plots</button>
    </p>
    <div id="plot-spinner" class="spinner" style="display:none;"></div>
    <div id="plot-area"></div>
</div>


<div class="card">
    <h3>Lead Variants</h3>

    <div style="margin-bottom: 10px;">
        <label>Window Size (kb):</label>
        <input type="number" id="window-size" value="500" style="width: 80px; margin-right: 15px;" />

        <label>Significance p-value:</label>
        <input type="text" id="pval-threshold" value="5e-8" style="width: 100px;" />
    </div>

    <p>
        <button class="btn-meta" id="lead-btn" onclick="loadLeadVariants('<?= $jobId ?>')">Load Lead Variants</button>
    </p>

    <div id="lead-spinner" class="spinner" style="display: none;"></div>
    <div id="lead-params" style="margin-bottom: 10px; font-style: italic; color: #5d4037;"></div>

    <div id="lead-variants"></div>
</div>


<div class="card">
    <h3>Post-GWAS Analysis with Lead SNPs</h3>

    <div style="margin-bottom: 20px;">
        <button class="btn-meta" onclick="runAnnotation()">Run Annotation</button>
        <p style="margin: 6px 0 0 0; font-size: 90%; color: #5d4037;">
            Annotate lead variants to gain functional insights.
        </p>
    </div>

    <div id="annotation-status" class="log-box" style="margin-top: 20px;"></div>
    <div id="annotation-results"></div>

    <div style="margin: 20px 0;">
        <button class="btn-meta" id="snp-analyzer-btn" onclick="openSNPAnalyzer()">Open SNP Analyzer</button>
        <p style="margin: 6px 0 0 0; font-size: 90%; color: #5d4037;">
            Analyze lead SNPs for novelty and known associations in GWAS Catalog and GRASP. Proceeding with QTL mapping and gene enrichment.
        </p>
    </div>

    <div style="margin-bottom: 0;">
        <button class="btn-meta" id="gedipnet-btn" onclick="startGedipnetEnrichment()">Enrichment Analysis</button>
        <p style="margin: 6px 0 0 0; font-size: 90%; color: #5d4037;">
            Perform gene enrichment analysis using lead genes in Gedipnet database.
        </p>
    </div>
</div>


  
</div>


<!-- 
    <div class="card">
        <h3>Fine-mapping</h3>
        <p><button class="btn-meta" onclick="alert('Fine-mapping not implemented yet.')">Run Fine-mapping</button></p>
        <div id="fine-mapping-results"></div>
    </div>

    <div class="card">
        <h3>QTL Mapping</h3>
        <p><button class="btn-meta" onclick="alert('QTL Mapping not implemented yet.')">Run QTL Lookup</button></p>
        <div id="qtl-results"></div>
    </div> 
-->
</div> 
<script>
let jobId = "<?= $jobId ?>";
let metaPolling = setInterval(fetchMetaLog, 3000);
<?php if ($showHarmonize): ?>
let harmonizePolling = setInterval(fetchHarmonizationLog, 3000);
<?php endif; ?>


// Load metadata
fetch(`user_uploads/${jobId}/meta_info.json`)
    .then(res => res.json())
    .then(data => {
        const infoBox = document.getElementById("meta-info-box");
        const items = [];

        if (data.gcst_ids?.length) {
            const tags = data.gcst_ids.map(id => `<span class="gcst-tag" onclick="copyGCST('${id}')">${id}</span>`).join(" ");
            items.push(`<li><span class="meta-label">GCST IDs:</span> <span class="meta-value">${tags}</span><span id="copy-confirm" class="copy-confirm" style="display:none;">Copied!</span></li>`);
        }
        if (data.method) {
            items.push(`<li><span class="meta-label">Meta-analysis Method:</span> <span class="meta-value">${data.method}</span></li>`);
        }
        if (data.tau2_method) {
            items.push(`<li><span class="meta-label">τ² Estimator:</span> <span class="meta-value">${data.tau2_method}</span></li>`);
        }
        if (data.build) {
            items.push(`<li><span class="meta-label">Input Genome Build:</span> <span class="meta-value">hg${data.build}</span></li>`);
        }
        if (data.target_build && data.build !== data.target_build) {
            items.push(`<li><span class="meta-label">Target Genome Build:</span> <span class="meta-value">hg${data.target_build}</span></li>`);
        }
        if (data.harmonize) {
            items.push(`<li><span class="meta-label">Harmonization:</span> <span class="meta-value">${data.harmonize}</span></li>`);
        }
        if (data.date) {
            items.push(`<li><span class="meta-label">Date:</span> <span class="meta-value">${data.date}</span></li>`);
        }

        infoBox.innerHTML = `<ul class="meta-list">${items.join("")}</ul>`;
    });

function copyGCST(text) {
    navigator.clipboard.writeText(text).then(() => {
        const confirm = document.getElementById("copy-confirm");
        confirm.style.display = "inline";
        setTimeout(() => { confirm.style.display = "none"; }, 1000);
    });
}



// Harmonization log (if enabled)
function fetchHarmonizationLog() {
    fetch(`user_uploads/${jobId}/harmonization.log`)
        .then(res => res.ok ? res.text() : "Log not available")
        .then(text => {
            document.getElementById("harmonization-log").textContent = text;
            document.getElementById("harmonization-log-card").style.display = "block";
            if (text.includes("Harmonization completed")) {
                clearInterval(harmonizePolling);
                document.getElementById("harmonization-spinner").style.display = "none";
            }
        });
}

// Meta-analysis log
function fetchMetaLog() {
    fetch(`user_uploads/${jobId}/meta_analysis.log`)
        .then(res => res.ok ? res.text() : "Log not available")
        .then(text => {
            document.getElementById("log-box").textContent = text;
            if (text.includes("Results saved")) {
                clearInterval(metaPolling);
                document.getElementById("spinner").style.display = "none";
                loadResults();
                document.getElementById("meta-results-section").style.display = "block";

                // Optionally hide the entire card wrapping the log box:
                document.getElementById("log-box").closest('.card').style.display = 'none';
            }
        });
}

function loadResults() {
    fetch(`user_uploads/${jobId}/meta_results_top_10k.tsv`)
        .then(res => res.text())
        .then(data => {
            const rows = data.trim().split("\n");
            const headers = rows[0].split("\t");
            const bodyRows = rows.slice(1).map(r => r.split("\t"));

            let html = `<table id='metaTable' class='display nowrap' style='width:100%;font-size:90%;'>`;
            html += "<thead><tr>" + headers.map(h => `<th>${h}</th>`).join('') + "</tr></thead><tbody>";
            bodyRows.forEach(row => {
                html += "<tr>" + row.map(cell => `<td>${cell}</td>`).join('') + "</tr>";
            });
            html += "</tbody></table>";

            document.getElementById("results-table").innerHTML = html;
            document.getElementById("meta-results-spinner").style.display = "none";

            $("#metaTable").DataTable({
                dom: 'Bfrtip',
                buttons: ['excel'],
                scrollX: true,
                paging: true
            });
        });
        loadLeadVariants(jobId);

}

function runMetaPlots() {
    const btn = document.getElementById("plot-btn");
    const spinner = document.getElementById("plot-spinner");
    const container = document.getElementById("plot-area");

    btn.disabled = true;
    btn.textContent = "Generating...";
    spinner.style.display = "block";
    container.innerHTML = "";

    fetch("run_meta_plots.php?job=" + jobId)
        .then(res => res.json())
        .then(data => {
            if (data.success && data.plots.length > 0) {
                container.innerHTML = data.plots.map(p => `<img src='${p}' style='max-width:100%;margin-bottom:15px;'>`).join('');
            } else {
                container.innerHTML = `<p style="color:red;">No plots generated.</p>`;
            }
        })
        .catch(err => {
            container.innerHTML = `<p style="color:red;">Error generating plots: ${err.message}</p>`;
        })
        .finally(() => {
            btn.disabled = false;
            btn.textContent = "Generate Plots";
            spinner.style.display = "none";
        });
}

</script>

<script>
// loadLead helper function
function displayLeadVariants(jobId, windowSize = null, pvalThreshold = null) {
    const resultBox = document.getElementById('lead-variants');
    const paramsBox = document.getElementById('lead-params');

    fetch(`user_uploads/${jobId}/meta_leads.tsv`)
        .then(res => {
            if (!res.ok) throw new Error("Lead file missing");
            return res.text();
        })
        .then(tsv => {
            const lines = tsv.trim().split('\n');
            if (lines.length < 2) throw new Error("File is empty or invalid");

            const headers = lines[0].split('\t');
            const colCount = headers.length;

            const rows = lines.slice(1).map(line => {
                const cells = line.split('\t').map(c => c.trim());
                while (cells.length < colCount) cells.push(''); // pad missing cols
                return cells;
            });

            let html = `<table id="lead-table" class="display compact" style="width:100%;font-size:90%;">`;
            html += "<thead><tr>" + headers.map(h => `<th>${h}</th>`).join('') + "</tr></thead><tbody>";
            for (const row of rows) {
                html += "<tr>" + row.map(cell => `<td>${cell}</td>`).join('') + "</tr>";
            }
            html += "</tbody></table>";

            resultBox.innerHTML = html;

            $('#lead-table').DataTable({
                scrollX: true,
                pageLength: 10,
                dom: 'Bfrtip',
                buttons: ['excel']
            });

            if (windowSize && pvalThreshold) {
                paramsBox.innerHTML = `Showing results with <strong>window size = ${windowSize} kb</strong>, <strong>p-value threshold = ${pvalThreshold}</strong>`;
            } else {
                paramsBox.innerHTML = "";
            }
        })
        .catch(err => {
            resultBox.innerHTML = `<p style="color:red;">Failed to load lead variants: ${err.message}</p>`;
        });
}


function loadLeadVariants(jobId) {
    const btn = document.getElementById('lead-btn');
    const spinner = document.getElementById('lead-spinner');
    const resultBox = document.getElementById('lead-variants');

    const windowSize = document.getElementById('window-size').value;
    const pvalThreshold = document.getElementById('pval-threshold').value;

    // UI changes
    btn.disabled = true;
    btn.textContent = "Loading...";
    spinner.style.display = "block";
    resultBox.innerHTML = "";

    fetch('run_extract_leads.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            job_id: jobId,
            window_kb: windowSize,
            pval_threshold: pvalThreshold
        })
    })
    .then(res => res.json())
    .then(data => {
    if (data.success) {
        displayLeadVariants(jobId, windowSize, pvalThreshold);
        btn.textContent = "Reload Lead Variants";
    } else {
        document.getElementById('lead-variants').innerHTML = `<p style="color:red;">Error: ${data.error}</p>`;
        btn.textContent = "Load Lead Variants";
    }
})

    .catch(err => {
        console.error(err);
        resultBox.innerHTML = `<p style="color:red;">Failed to load lead variants.</p>`;
        btn.textContent = "Load Lead Variants";
    })
    .finally(() => {
        spinner.style.display = "none";
        btn.disabled = false;
    });
}

</script>


<script>
function runAnnotation() {
    const jobId = "<?= $jobId ?>"; // PHP-injected job ID
    const statusBox = document.getElementById("annotation-status");
    const resultsBox = document.getElementById("annotation-results");

    statusBox.innerHTML = `<span class="loading">Running annotation...</span>`;
    resultsBox.innerHTML = "";

    fetch("run_annotate_leads.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ job: jobId })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            statusBox.innerHTML = `<span class="success">Annotation completed.</span>`;
            loadAnnotationResults(jobId);  // Will load .tsv now
        } else {
            statusBox.innerHTML = `<span class="error">Error: ${data.error}</span>`;
        }
    })
    .catch(err => {
        statusBox.innerHTML = `<span class="error">Request failed: ${err}</span>`;
    });
}

function loadAnnotationResults(jobId) {
    const resultsBox = document.getElementById("annotation-results");
    const fileUrl = `user_uploads/${jobId}/meta_results_annotated.tsv`; // adjust if path differs

    fetch(fileUrl)
        .then(res => {
            if (!res.ok) throw new Error("Annotated file not found");
            return res.text();
        })
        .then(tsvText => {
            const rows = tsvText.trim().split("\n").map(line => line.split("\t"));
            if (rows.length < 2) {
                resultsBox.innerHTML = `<div class="error">Annotation file is empty or invalid.</div>`;
                return;
            }

            const headers = rows[0];
            const body = rows.slice(1);

            let table = `<div class="table-container"><table id="leadAnn-table" class="display compact" style="width:100%;font-size:90%;"><thead><tr>`;
            headers.forEach(h => table += `<th>${h}</th>`);
            table += `</tr></thead><tbody>`;
            body.forEach(row => {
                table += `<tr>`;
                row.forEach(cell => table += `<td>${cell}</td>`);
                table += `</tr>`;
            });
            table += `</tbody></table></div>`;

            resultsBox.innerHTML = table;

               $('#leadAnn-table').DataTable({
                        scrollX: true,
                        pageLength: 10,
                        dom: 'Bfrtip',
                        buttons: ['excel']
                    });
        })
        .catch(err => {
            resultsBox.innerHTML = `<div class="error">Could not load annotation results: ${err.message}</div>`;
            console.error("Annotation load error:", err); // debug line
        });
}

function openSNPAnalyzer() {
    // Find the lead variants table
    const table = document.getElementById('lead-table');
    if (!table) {
        alert("Lead variants table not loaded.");
        return;
    }

    // Find the SNP column index by header (assumed to be "SNP" or "rsID")
    const headers = table.querySelectorAll('thead th');
    let snpColIndex = -1;
    headers.forEach((th, i) => {
        const text = th.textContent.trim().toLowerCase();
        if (text === 'snp' || text === 'rsid') {
            snpColIndex = i;
        }
    });

    if (snpColIndex === -1) {
        alert("SNP column not found in lead variants table.");
        return;
    }

    // Get all SNP values in that column
    const rows = table.querySelectorAll('tbody tr');
    let snps = [];
    rows.forEach(row => {
        const cells = row.querySelectorAll('td');
        if (cells.length > snpColIndex) {
            const snpValue = cells[snpColIndex].textContent.trim();
            if (snpValue) snps.push(snpValue);
        }
    });

    if (snps.length === 0) {
        alert("No lead SNPs found to send.");
        return;
    }

    // Prepare the SNP list as newline separated
    const snpList = snps.join('\n');

    // Create a form to POST SNPs to snp-analyzer.php
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = 'snpid.php';
    form.target = '_blank';  // open in new tab

    const input = document.createElement('textarea');
    input.name = 'snpid';
    input.style.display = 'none';
    input.value = snpList;

    form.appendChild(input);
    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
}

</script>
<script>

// Automatically load plots if they exist
function checkAndLoadPlots() {
    fetch(`user_uploads/${jobId}/meta_plots/meta_qq.png`, { method: "HEAD" })
        .then(res => {
            if (res.ok) {
                // Automatically load both plots (we assume 2 for now)
                const plotArea = document.getElementById("plot-area");
                plotArea.innerHTML = `
                    <img src="user_uploads/${jobId}/meta_plots/meta_qq.png" style="max-width:100%;margin-bottom:15px;">
                    <img src="user_uploads/${jobId}/meta_plots/meta_manhattan.png" style="max-width:100%;margin-bottom:15px;">
                `;
            }
        });
}

// Run this on page load
checkAndLoadPlots();

// automatically check for lead variants file
function checkLeadVariantsFile() {
    fetch(`user_uploads/${jobId}/meta_leads.tsv`, { method: "HEAD" })
        .then(res => {
            if (res.ok) {
                displayLeadVariants(jobId); // will load file but not show params
            }
        });
}

checkLeadVariantsFile();


</script>

<?php include('footer.php'); ?>
