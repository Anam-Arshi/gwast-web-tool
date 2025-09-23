<?php
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);
include("../../connect.php");
session_start();

// Session cleanup
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]);
}
session_destroy();

// Process input data
$arrInput = $_POST["snpid"] ?? array();
$aInp = array_unique($arrInput);
$rsids = array();
$positions = array();
$chr_pos_map = array(); // To map chr:pos to SNP ID

foreach($aInp as $snp) {
    $snpEx = explode("#", $snp);
    if (count($snpEx) >= 2) {
        $rsid = trim($snpEx[0]);
        $location = $snpEx[1]; // This is "CHR:POS"
        
        $rsids[] = $rsid;
        $positions[] = $location;
        
        // Parse chromosome and position
        $locParts = explode(":", $location);
        if (count($locParts) >= 2) {
            $chr_pos_map[$locParts[1]] = $rsid; // Map position to SNP ID
        }
    }
}

// Get form parameters
$case_selection_type = $_POST['case_selection_type'] ?? 'all';
$selected_cases = $_POST['selected_cases'] ?? array();
$mapped_gene = isset($_POST['mapped_gene']) ? true : false;
$qtl_analysis = isset($_POST['qtl_analysis']) ? true : false;
$p_valu1 = $_POST["p_value1"] ?? 5;
$p_valu2 = $_POST["p_value2"] ?? 2;
$pvalue = floatval($p_valu1 * pow(10, -$p_valu2));
$allTissue = $_POST['tissue'] ?? array();

if (!is_array($allTissue)) {
    $allTissue = explode(",", $allTissue);
}

// Build filter conditions for case selection
$cas = "";
if ($case_selection_type === 'specific' && !empty($selected_cases)) {
    $case_quoted = array_map(function($value) {
        return "'" . addslashes($value) . "'";
    }, $selected_cases);
    $cas = "AND snp_table.casee IN (" . implode(",", $case_quoted) . ")";
}

// Create quoted arrays for queries
$snp_quoted = array_map(function($value) {
    return "'" . addslashes($value) . "'";
}, $rsids);
$snpq = "(" . implode(",", $snp_quoted) . ")";

// Extract positions for QTL queries
$pos_only = array();
foreach($positions as $pos) {
    $locParts = explode(":", $pos);
    if (count($locParts) >= 2) {
        $pos_only[] = $locParts[1]; // Just the position number
    }
}

$pos_quoted = array_map(function($value) {
    return "'" . addslashes($value) . "'";
}, $pos_only);
$posq = "(" . implode(",", $pos_quoted) . ")";

include('../../header.php');
?>
<!-- Include DataTables CSS and JS -->
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.dataTables.min.css">
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/select/1.7.0/css/select.dataTables.min.css">



<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/select/1.7.0/js/dataTables.select.min.js"></script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>

<style>
/* Main layout styling consistent with previous page */
.results-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 20px;
}

.analysis-section {
    background: #fff;
    border: 2px solid #5D4E37;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 30px;
}

.analysis-section h4 {
    color: #5D4E37;
    margin-bottom: 20px;
    font-weight: 600;
}

/* Table styling */
.results-table {
    width: 100% !important;
    border-collapse: collapse;
}

.results-table thead th {
    background-color: #5D4E37 !important;
    color: white !important;
    text-align: center;
    padding: 12px 8px;
    font-size: 13px;
    border: 1px solid #dee2e6 !important;
}

.results-table tbody td {
    padding: 8px;
    font-size: 13px;
    vertical-align: middle;
    border: 1px solid #dee2e6 !important;
}

.results-table tbody tr:hover {
    background-color: #fff !important;
}

/* DataTables styling */
.dataTables_wrapper .dataTables_length,
.dataTables_wrapper .dataTables_filter,
.dataTables_wrapper .dataTables_info,
.dataTables_wrapper .dataTables_paginate {
    margin: 10px 0;
}

.dataTables_wrapper .dataTables_filter {
    float: right;
    display: flex !important;
    align-items: center;
    gap: 10px;
    justify-content: flex-end;
}

.dt-buttons {
    display: inline-flex !important;
    align-items: center;
    order: 2;
}

.dataTables_filter label {
    display: flex;
    align-items: center;
    gap: 5px;
    order: 1;
    margin: 0;
}

.dt-button {
    background-color: #5D4E37 !important;
    color: white !important;
    border: none !important;
    padding: 8px 16px !important;
    border-radius: 4px !important;
    margin-left: 10px !important;
}

.dt-button:hover {
    background-color: #4a3d2b !important;
}

/* Selected genes display */
.selected-genes {
    background: #fff;
    border: 2px solid #5D4E37;
    border-radius: 8px;
    padding: 15px;
    margin: 20px 0;
}

.selected-genes h5 {
    color: #5D4E37;
    margin-bottom: 10px;
}

.gene-display {
    background-color: #fff;
    border: 1px solid #dee2e6;
    border-radius: 4px;
    padding: 10px;
    font-family: monospace;
    min-height: 60px;
    max-height: 200px;
    overflow-y: auto;
}

/* Analysis options */
.analysis-options {
    background: #fff;
    border: 2px solid #5D4E37;
    border-radius: 8px;
    padding: 20px;
    margin-top: 20px;
}

.analysis-options h5 {
    color: #5D4E37;
    margin-bottom: 20px;
}

.option-group {
    margin-bottom: 15px;
}

.custom-radio {
    display: flex;
    align-items: center;
    margin-bottom: 10px;
    font-size: 14px;
}

.custom-radio input[type="radio"] {
    margin-right: 8px;
    transform: scale(1.1);
}

.disease-select {
    margin-left: 20px;
    margin-top: 10px;
}

/* Buttons */
.btn-analyze {
    background-color: #5D4E37;
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 5px;
    transition: all 0.2s ease;
    margin-right: 10px;
}

.btn-analyze:hover {
    background-color: #4a3d2b;
    transform: translateY(-1px);
    box-shadow: 0 2px 8px rgba(93, 78, 55, 0.2);
}

.btn-secondary {
    background-color: #6c757d;
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 5px;
}

.btn-secondary:hover {
    background-color: #5a6268;
}

/* Loading overlay */
#loadingOverlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    display: none;
    justify-content: center;
    align-items: center;
    z-index: 9999;
}

.loading-content {
    background: white;
    padding: 30px;
    border-radius: 8px;
    text-align: center;
}

.loading-spinner {
    border: 3px solid #f3f3f3;
    border-top: 3px solid #5D4E37;
    border-radius: 50%;
    width: 40px;
    height: 40px;
    animation: spin 1s linear infinite;
    margin: 0 auto 15px;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
</style>

<div class="results-container">
    <h2 class="text-center mb-4" style="color:#5D4E37;">Analysis Results</h2>
    
    <!-- Analysis Summary -->
    <div class="analysis-section">
   <h4><i class="fas fa-info-circle me-2"></i>Analysis Summary</h4>
   <div><strong>Total SNPs:</strong> <?php echo count($rsids); ?></div>
   <div><strong>Case Selection:</strong> <?php echo ucfirst($case_selection_type); ?><?php if ($case_selection_type === 'specific' && !empty($selected_cases)) { echo " (" . implode(", ", $selected_cases) . ")"; } ?></div>
   
            <div>
                <strong>Analysis Types:</strong> 
                <?php 
                $analysisTypes = array();
                if($mapped_gene) $analysisTypes[] = "Mapped Genes";
                if($qtl_analysis) $analysisTypes[] = "QTL Analysis";
                echo implode(", ", $analysisTypes);
                ?>
            </div>
        <?php if($qtl_analysis && !empty($allTissue)): ?>
   <div><strong>P-value cutoff:</strong> <?php echo sprintf("%.2e", $pvalue); ?></div>
   <div><strong>Selected Tissues:</strong> <?php echo count($allTissue); ?> tissues</div>
   <?php endif; ?>
</div>

    <?php if ($mapped_gene): ?>
    <!-- Mapped Gene Analysis Section -->
    <div class="analysis-section">
        <h4><i class="fas fa-dna me-2"></i>Mapped Genes from GWAS Catalog and GRASP</h4>
        
        <?php
       $qry = "SELECT snps, snp_type, chr, pos, gene, MIN(p_value) AS min_pvalue
        FROM snp_table
        WHERE snps IN $snpq
        $cas
        GROUP BY snps, chr, pos, gene, snp_type
        ORDER BY min_pvalue ASC";

        // echo $qry;
    
        $qrym = mysqli_query($conn, $qry);
        
        $cntm = mysqli_num_rows($qrym);
        
        if ($cntm > 0) {
        ?>
        <table class="results-table" id="mappedTable">
            <thead>
                <tr>
                     <th><input type="checkbox" id="selectAllMapped"></th>
                    <th>SNP ID</th>
                    <th>Location</th>
                   
                    <th>Mapped Gene(s)</th>
                    <th>P-value</th>
                   
                </tr>
            </thead>
            <tbody>
                <?php
                while ($row = mysqli_fetch_array($qrym)) {
                    $mappedGene = str_replace(";", ", ", $row['gene']);
                ?>
                <tr>
                    <td><input type="checkbox" value="<?php echo htmlspecialchars($mappedGene); ?>"></td>

                    <td><a href="https://www.ncbi.nlm.nih.gov/snp/<?php echo $row["snps"]; ?>" target="_blank"><?php echo $row["snps"]; ?></a></td>
                    <td><?php echo $row['chr'] . ": " . $row['pos']; ?></td>
                    
                    <td><?php echo htmlspecialchars($mappedGene); ?></td>
                    <td><?php echo sprintf("%.2e", $row['min_pvalue']); ?></td>
                    
                </tr>
                <?php } ?>
            </tbody>
        </table>
        <?php } else { ?>
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle me-2"></i>No mapped genes found for the selected SNPs with the current filters.
        </div>
        <?php } ?>
    </div>
    <?php endif; ?>

    <?php if ($qtl_analysis && !empty($allTissue)): ?>
    <!-- QTL Analysis Section -->
    <div class="analysis-section">
        <h4><i class="fas fa-chart-line me-2"></i>QTL Analysis Results</h4>
        
        <?php
        $snpdat = array();
        $xa = 0;
        
        foreach ($allTissue as $tsu) {
            $tKey = ucfirst(str_replace("_", " ", $tsu));
            
            // Updated query to properly join tables and filter by positions
            $qqry = "SELECT
                `$tsu`.SNP_chr AS chr,
                `$tsu`.SNP_pos_hg38 AS pos,
                `$tsu`.Pvalue AS pvalue,
                `$tsu`.Mapped_gene AS gene,
                qtlbase_sourceid.xQTL AS xQTL
            FROM
                `$tsu`
                JOIN qtlbase_sourceid ON `$tsu`.Sourceid = qtlbase_sourceid.Sourceid
            WHERE
                `$tsu`.SNP_pos_hg38 IN $posq
                AND qtlbase_sourceid.Tissue = '$tKey'
                AND `$tsu`.Pvalue < '$pvalue'
                AND `$tsu`.Mapped_gene IS NOT NULL
                AND `$tsu`.Mapped_gene != ''";
            // echo $qqry . "<br>";
            $res = mysqli_query($conn, $qqry);
            
            $countt = mysqli_num_rows($res);
            if ($countt > 0) {
                while ($row = mysqli_fetch_array($res)) {
                    // Map position back to SNP ID
                    $snpid = isset($chr_pos_map[$row['pos']]) ? $chr_pos_map[$row['pos']] : 'Unknown';
                    
                    $rowArray = array(
                        $snpid, 
                        $row['chr'], 
                        $row['pos'], 
                        'QTL', // SNP type for QTL
                        $row['gene'], 
                        $tKey, 
                        $row['xQTL'], 
                        $row['pvalue']
                    );
                    $snpdat[] = $rowArray;
                    $xa++;
                }
            }
        }
        
        if ($xa > 0) {
            // Remove duplicates and keep lowest p-value
            $filteredData = array();
            foreach ($snpdat as $entry) {
                $key = implode(',', array_slice($entry, 0, 7));
                $pValue = floatval($entry[7]);
                if (!isset($filteredData[$key]) || $pValue < floatval($filteredData[$key][7])) {
                    $filteredData[$key] = $entry;
                }
            }
        ?>
        <table class="results-table" id="qtlTable">
            <thead>
                <tr>
                    <th><input type="checkbox" id="selectAllQTL"></th>

                    <th>SNP ID</th>
                    <th>Location</th>
                    <th>Mapped Gene(s)</th>
                    <th>P-value</th>
                    <th>Tissue</th>
                    <th>QTL Type</th>
                   
                </tr>
            </thead>
            <tbody>
                <?php foreach ($filteredData as $tabd) { 
                    $genes = str_replace(";", ", ", $tabd[4]);
                ?>
                <tr>
                    <td><input type="checkbox" value="<?php echo htmlspecialchars($genes); ?>"></td>

                    <td><a href="https://www.ncbi.nlm.nih.gov/snp/<?php echo $tabd[0]; ?>" target="_blank"><?php echo $tabd[0]; ?></a></td>
                    <td><?php echo $tabd[1] . ": " . $tabd[2]; ?></td>
                    <td><?php echo htmlspecialchars($genes); ?></td>
                    <td><?php echo sprintf("%.2e", $tabd[7]); ?></td>
                    <td><?php echo htmlspecialchars($tabd[5]); ?></td>
                    <td><?php echo htmlspecialchars($tabd[6]); ?></td>
                    
                </tr>
                <?php } ?>
            </tbody>
        </table>
        <?php } else { ?>
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle me-2"></i>No QTL data found for the selected criteria (P-value < <?php echo sprintf("%.2e", $pvalue); ?>).
        </div>
        <?php } ?>
    </div>
    <?php endif; ?>

    <!-- Selected Genes Display -->
    <div class="selected-genes">
        <h5><i class="fas fa-check-circle me-2"></i>Selected Genes</h5>
        <div class="gene-display" id="selectedGenes">
            Click checkboxes above to select genes for downstream analysis...
        </div>
    </div>

<!-- Analysis Options -->
<div class="analysis-options">
    <h5><i class="fas fa-cogs me-2"></i>Downstream Analysis Options</h5>
    
    <!-- Gene Prioritization Section -->
    <?php
    // Check for gene prioritization options
    $snpListU = array_unique($rsids);
    $DisRes = array();
    
    foreach ($snpListU as $uSNP) {
        $qry = mysqli_query($conn, "SELECT DISTINCT snp_table.disease_merge 
        FROM snp_table 
        JOIN gene_prioritization ON snp_table.disease_merge = gene_prioritization.disease_merge_gedipnet 
        WHERE snp_table.snps = '$uSNP'
        $cas");
        
        if (mysqli_num_rows($qry) > 0) {
            while ($qryRes = mysqli_fetch_array($qry)) {
                $DisRes[] = $qryRes['disease_merge'];
            }
        }
    }
    
    if (!empty($DisRes)) {
        $uDisRes = array_unique($DisRes);
    ?>
    <div class="option-group">
        <h6 style="color: #5D4E37; margin-bottom: 15px;"><i class="fas fa-sort-amount-up me-2"></i>Gene Prioritization</h6>
        <div class="disease-select">
            <label for="diseaseSelect"><strong>Select disease for prioritization:</strong></label>
            <select id="diseaseSelect" class="form-control" style="margin-top: 5px;">
                <option value="">Choose a disease...</option>
                <?php foreach ($uDisRes as $dis) { ?>
                <option value="<?php echo htmlspecialchars($dis); ?>"><?php echo htmlspecialchars($dis); ?></option>
                <?php } ?>
            </select>
        </div>
        <div style="margin-top: 15px;">
            <button type="button" class="btn-analyze" id="genePrioritizationBtn">
                <i class="fas fa-sort-amount-up me-2"></i>Run Gene Prioritization
            </button>
        </div>
    </div>
    <?php } ?>
    
    <!-- Enrichment Analysis Section -->
    <div class="option-group" style="margin-top: 30px;">
        <h6 style="color: #5D4E37; margin-bottom: 15px;"><i class="fas fa-chart-bar me-2"></i>Enrichment Analysis</h6>
        <p style="color: #666; font-size: 14px; margin-bottom: 15px;">
            Perform pathway and disease enrichment analysis using external databases
        </p>
        <div>
            <button type="button" class="btn-analyze" id="enrichmentBtn">
                <i class="fas fa-chart-bar me-2"></i>Run Enrichment Analysis
            </button>
        </div>
    </div>
</div>

<!-- Submit buttons -->
<div class="text-center" style="margin-top: 20px;">
    <button type="button" class="btn-secondary" onclick="history.back();">
        <i class="fas fa-arrow-left me-2"></i>Back
    </button>
</div>
                </div>
<!-- Loading Overlay -->
<div id="loadingOverlay">
    <div class="loading-content">
        <div class="loading-spinner"></div>
        <p>Processing analysis...</p>
    </div>
</div>

<script>
$(document).ready(function() {
    // Store selected rows across pages
    var selectedRows = {
        mapped: [],
        qtl: []
    };
    
    var mappedTable = null;
    var qtlTable = null;
    
    // Initialize DataTables for Mapped Genes table
    <?php if ($mapped_gene && isset($cntm) && $cntm > 0): ?>
    mappedTable = $('#mappedTable').DataTable({
        pageLength: 25,
        dom: 'lfBrtip',
        buttons: [{
            extend: 'csvHtml5',
            text: '<i class="fas fa-download"></i> Download CSV',
            title: 'Mapped_Genes_Results',
            className: 'dt-button',
            fieldSeparator: '\t',
            extension: '.tsv'
        }],
        columnDefs: [{
            targets: 0,
            orderable: false
        }],
        rowCallback: function(row, data, index) {
            // Restore checkbox state when row is drawn
            var checkbox = $(row).find('input[type="checkbox"]');
            var geneValue = checkbox.val();
            
            if (selectedRows.mapped.indexOf(geneValue) !== -1) {
                checkbox.prop('checked', true);
            }
        },
        initComplete: function() {
            if ($('#mappedTable_wrapper .dataTables_filter .dt-buttons').length === 0) {
                var buttonsContainer = $('#mappedTable_wrapper .dt-buttons').detach();
                $('#mappedTable_wrapper .dataTables_filter').append(buttonsContainer);
            }
        }
    });
    
    // Update select all state for mapped table
    function updateMappedSelectAll() {
        var totalRows = mappedTable.rows().count();
        var selectedCount = selectedRows.mapped.length;
        $('#selectAllMapped').prop('checked', totalRows > 0 && selectedCount === totalRows);
    }
    
    // Handle mapped table draw events (pagination, search, etc.)
    mappedTable.on('draw', function() {
        updateMappedSelectAll();
        updateSelectedGenes();
    });
    <?php endif; ?>
    
    // Initialize DataTables for QTL table
    <?php if ($qtl_analysis && isset($xa) && $xa > 0): ?>
    qtlTable = $('#qtlTable').DataTable({
        pageLength: 25,
        dom: 'lfBrtip',
        buttons: [{
            extend: 'csvHtml5',
            text: '<i class="fas fa-download"></i> Download CSV',
            title: 'QTL_Analysis_Results',
            className: 'dt-button',
            fieldSeparator: '\t',
            extension: '.tsv'
        }],
        columnDefs: [{
            targets: 0,
            orderable: false
        }],
        rowCallback: function(row, data, index) {
            // Restore checkbox state when row is drawn
            var checkbox = $(row).find('input[type="checkbox"]');
            var geneValue = checkbox.val();
            
            if (selectedRows.qtl.indexOf(geneValue) !== -1) {
                checkbox.prop('checked', true);
            }
        },
        initComplete: function() {
            if ($('#qtlTable_wrapper .dataTables_filter .dt-buttons').length === 0) {
                var buttonsContainer = $('#qtlTable_wrapper .dt-buttons').detach();
                $('#qtlTable_wrapper .dataTables_filter').append(buttonsContainer);
            }
        }
    });
    
    // Update select all state for QTL table
    function updateQTLSelectAll() {
        var totalRows = qtlTable.rows().count();
        var selectedCount = selectedRows.qtl.length;
        $('#selectAllQTL').prop('checked', totalRows > 0 && selectedCount === totalRows);
    }
    
    // Handle QTL table draw events (pagination, search, etc.)
    qtlTable.on('draw', function() {
        updateQTLSelectAll();
        updateSelectedGenes();
    });
    <?php endif; ?>
    
    // Select all for mapped table - works across ALL pages
    $('#selectAllMapped').on('change', function() {
        var checked = this.checked;
        selectedRows.mapped = [];
        
        if (checked && mappedTable) {
            // Get all row data from ALL pages and add to selection
            mappedTable.rows().every(function() {
                var checkbox = $(this.node()).find('input[type="checkbox"]');
                var geneValue = checkbox.val();
                if (geneValue && selectedRows.mapped.indexOf(geneValue) === -1) {
                    selectedRows.mapped.push(geneValue);
                }
            });
        }
        
        // Update currently visible checkboxes
        $('#mappedTable tbody input[type="checkbox"]').prop('checked', checked);
        updateSelectedGenes();
    });
    
    // Select all for QTL table - works across ALL pages
    $('#selectAllQTL').on('change', function() {
        var checked = this.checked;
        selectedRows.qtl = [];
        
        if (checked && qtlTable) {
            // Get all row data from ALL pages and add to selection
            qtlTable.rows().every(function() {
                var checkbox = $(this.node()).find('input[type="checkbox"]');
                var geneValue = checkbox.val();
                if (geneValue && selectedRows.qtl.indexOf(geneValue) === -1) {
                    selectedRows.qtl.push(geneValue);
                }
            });
        }
        
        // Update currently visible checkboxes
        $('#qtlTable tbody input[type="checkbox"]').prop('checked', checked);
        updateSelectedGenes();
    });
    
    // Handle individual checkbox changes
    $(document).on('change', 'input[type="checkbox"]:not(#selectAllMapped):not(#selectAllQTL)', function() {
        var geneValue = $(this).val();
        var isChecked = $(this).prop('checked');
        var tableId = $(this).closest('table').attr('id');
        
        if (tableId === 'mappedTable') {
            if (isChecked) {
                // Add to selection if not already present
                if (selectedRows.mapped.indexOf(geneValue) === -1) {
                    selectedRows.mapped.push(geneValue);
                }
            } else {
                // Remove from selection
                var index = selectedRows.mapped.indexOf(geneValue);
                if (index > -1) {
                    selectedRows.mapped.splice(index, 1);
                }
            }
            updateMappedSelectAll();
            
        } else if (tableId === 'qtlTable') {
            if (isChecked) {
                // Add to selection if not already present
                if (selectedRows.qtl.indexOf(geneValue) === -1) {
                    selectedRows.qtl.push(geneValue);
                }
            } else {
                // Remove from selection
                var index = selectedRows.qtl.indexOf(geneValue);
                if (index > -1) {
                    selectedRows.qtl.splice(index, 1);
                }
            }
            updateQTLSelectAll();
        }
        
        updateSelectedGenes();
    });
    
    // Update selected genes display
    function updateSelectedGenes() {
        var allSelectedGenes = [];
        
        // Process mapped table selections
        selectedRows.mapped.forEach(function(geneValue) {
            if (geneValue) {
                var genes = geneValue.split(', ');
                genes.forEach(function(g) {
                    g = g.trim();
                    if (g && allSelectedGenes.indexOf(g) === -1) {
                        allSelectedGenes.push(g);
                    }
                });
            }
        });
        
        // Process QTL table selections
        selectedRows.qtl.forEach(function(geneValue) {
            if (geneValue) {
                var genes = geneValue.split(', ');
                genes.forEach(function(g) {
                    g = g.trim();
                    if (g && allSelectedGenes.indexOf(g) === -1) {
                        allSelectedGenes.push(g);
                    }
                });
            }
        });
        
        if (allSelectedGenes.length > 0) {
            $('#selectedGenes').text(allSelectedGenes.join(", "));
        } else {
            $('#selectedGenes').text("Click checkboxes above to select genes for downstream analysis...");
        }
    }
    
    // Update the downstream analysis buttons to use the selectedRows data
    $('#genePrioritizationBtn').on('click', function() {
        var allSelectedGenes = [];
        
        // Process mapped table selections
        selectedRows.mapped.forEach(function(geneValue) {
            if (geneValue) {
                var genes = geneValue.split(', ');
                genes.forEach(function(g) {
                    g = g.trim();
                    if (g && allSelectedGenes.indexOf(g) === -1) {
                        allSelectedGenes.push(g);
                    }
                });
            }
        });
        
        // Process QTL table selections
        selectedRows.qtl.forEach(function(geneValue) {
            if (geneValue) {
                var genes = geneValue.split(', ');
                genes.forEach(function(g) {
                    g = g.trim();
                    if (g && allSelectedGenes.indexOf(g) === -1) {
                        allSelectedGenes.push(g);
                    }
                });
            }
        });
        
        if (allSelectedGenes.length === 0) {
            alert('Please select at least one gene for analysis.');
            return false;
        }
        
        var selectedDisease = $('#diseaseSelect').val();
        if (!selectedDisease) {
            alert('Please select a disease for prioritization.');
            return false;
        }
        
        var form = $('<form>', {
            'action': 'gs_en_o.php',
            'method': 'post',
            'target': '_blank'
        });
        
        form.append($('<input>', {'type': 'hidden', 'name': 'organis', 'value': 'human'}));
        form.append($('<input>', {'type': 'hidden', 'name': 'anp', 'value': 'gwasminer'}));
        form.append($('<input>', {'type': 'hidden', 'name': 'type_Select', 'value': 'gene_prio'}));
        form.append($('<input>', {'type': 'hidden', 'name': 'p', 'value': 'Gene prioritization SNP'}));
        form.append($('<input>', {'type': 'hidden', 'name': 'diseas', 'value': selectedDisease}));
        
        allSelectedGenes.forEach(function(gene) {
            form.append($('<input>', {'type': 'hidden', 'name': 'gen[]', 'value': gene}));
        });
        
        $('#loadingOverlay').show();
        form.appendTo('body').submit().remove();
        setTimeout(function() {
            $('#loadingOverlay').hide();
        }, 2000);
    });
    
    $('#enrichmentBtn').on('click', function() {
        var allSelectedGenes = [];
        
        // Process mapped table selections
        selectedRows.mapped.forEach(function(geneValue) {
            if (geneValue) {
                var genes = geneValue.split(', ');
                genes.forEach(function(g) {
                    g = g.trim();
                    if (g && allSelectedGenes.indexOf(g) === -1) {
                        allSelectedGenes.push(g);
                    }
                });
            }
        });
        
        // Process QTL table selections
        selectedRows.qtl.forEach(function(geneValue) {
            if (geneValue) {
                var genes = geneValue.split(', ');
                genes.forEach(function(g) {
                    g = g.trim();
                    if (g && allSelectedGenes.indexOf(g) === -1) {
                        allSelectedGenes.push(g);
                    }
                });
            }
        });
        
        if (allSelectedGenes.length === 0) {
            alert('Please select at least one gene for analysis.');
            return false;
        }
        
        var form = $('<form>', {
            'action': 'https://gedipnet.bicnirrh.res.in/new_enrichment_analysis_input.php',
            'method': 'POST',
            'target': '_blank'
        });
        
        form.append($('<textarea>', {
            'name': 'genes',
            'style': 'display:none',
            'text': Array.from(allSelectedGenes).join(',')
        }));
        
        $('#loadingOverlay').show();
        form.appendTo('body').submit().remove();
        setTimeout(function() {
            $('#loadingOverlay').hide();
        }, 2000);
    });
});
</script>







<?php include('../../footer.php'); ?>
