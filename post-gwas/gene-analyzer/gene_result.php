<?php
ini_set('display_errors', '1');
error_reporting(E_ALL);


session_start();

// Session cleanup code...
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]);
}

session_destroy();
include("connect.php");

$snpid = $_POST["snpid"];
$snpid = preg_replace("/\s\s+/", " ", $snpid);
$snpid = rtrim($snpid);
$lst_snpa = preg_split("/[\s,]+/", $snpid);
$lst_snp = array_unique($lst_snpa);

// Escape and quote the array values
$snp_quoted = array_map(function($value) {
    return "'" . addslashes($value) . "'";
}, $lst_snp);

$snpLst = "(".implode(",", $snp_quoted). ")"; 

include('header.php');
?>

<!-- Include DataTables and Select2 CSS and JS -->
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.dataTables.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">

<style>
/* Main layout styling - identical to SNP page */
.results-container {
    display: flex;
    gap: 20px;
    margin-top: 20px;
}

.filters-sidebar {
    width: 280px;
    background: #f8f9fa;
    border: 2px solid #5D4E37;
    border-radius: 8px;
    padding: 20px;
    height: fit-content;
    position: sticky;
    top: 20px;
}

.results-main {
    display: flex;
    flex-direction: column;
    width: 100%;
}

/* Filter section styling */
.filter-section {
    margin-bottom: 25px;
    padding-bottom: 20px;
    border-bottom: 1px solid #dee2e6;
}

.filter-section:last-child {
    border-bottom: none;
    margin-bottom: 0;
}

.filter-section h6 {
    color: #5D4E37;
    font-weight: 600;
    margin-bottom: 12px;
    font-size: 14px;
}

/* Custom checkbox styling */
.custom-checkbox {
    display: block;
    margin-bottom: 8px;
    font-size: 13px;
}

.custom-checkbox input[type="checkbox"] {
    margin-right: 8px;
    transform: scale(1.1);
}

.custom-checkbox input[type="checkbox"]:checked + label {
    color: #5D4E37;
    font-weight: 500;
}

/* Table styling */
.results-table-container {
    border-radius: 8px;
    overflow: hidden;
    margin-bottom: 20px;
}

.results-table {
    margin: 0 !important;
    border-collapse: separate;
    border-spacing: 0;
    width: 100% !important;
}

.results-table thead th {
    background-color: #5D4E37 !important;
    color: white !important;
    text-align: center;
    padding: 12px 8px;
    font-size: 13px;
}

.results-table tbody td {
    padding: 8px;
    font-size: 13px;
    vertical-align: middle;
    border-bottom: 1px solid #dee2e6;
}

.results-table tbody tr:hover {
    background-color: #f8f9fa !important;
}

/* DataTables styling adjustments */
.dataTables_wrapper {
    width: 100% !important;
    margin: 0 !important;
    padding: 0 !important;
}

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

.dt-button:hover {
    background-color: #4a3d2b !important;
}

.dataTables_filter label {
    display: flex;
    align-items: center;
    gap: 5px;
    order: 1;
    margin: 0;
}

.dataTables_filter input {
    margin: 0;
}

.dt-buttons {
    display: inline-flex !important;
    align-items: center;
    margin-left: 0 !important;
    vertical-align: middle;
}

.dataTables_filter input {
    margin-left: 5px;
}

/* Gene count styling */
.gene-count {
    background: #5D4E37;
    color: white;
    padding: 15px 20px;
    border-radius: 8px;
    margin: 20px 0;
    text-align: center;
    font-weight: 600;
}

.gene-count .count-number {
    font-size: 1.5em;
    font-weight: bold;
}

/* Analysis options styling */
.analysis-options {
    background: #f8f9fa;
    border: 2px solid #5D4E37;
    border-radius: 8px;
    padding: 20px;
    margin-top: 20px;
}

.option-group {
    display: flex;
    gap: 40px;
    align-items: flex-start;
    margin-bottom: 20px;
}

.option-section {
    flex: 1;
}

.option-section h6 {
    color: #5D4E37;
    font-weight: 600;
    margin-bottom: 10px;
}

/* QTL Controls styling */
.qtl-controls {
    margin-top: 15px;
    padding: 15px;
    background: white;
    border-radius: 5px;
    border: 1px solid #dee2e6;
}

.pvalue-section {
    margin-bottom: 15px;
}

.pvalue-input {
    display: flex;
    align-items: center;
    gap: 5px;
    margin-top: 8px;
}

.pvalue-input input {
    width: 50px;
    padding: 4px 6px;
    border: 1px solid #ced4da;
    border-radius: 4px;
    text-align: center;
}

.tissue-section {
    margin-top: 15px;
}

/* Select2 and button styling for tissue selection */
.select2-container {
    width: 100% !important;
}

.tissue-buttons {
    margin-top: 10px;
}

.allBtn {
    background-color: #6c757d;
    color: white;
    border: none;
    padding: 6px 12px;
    border-radius: 4px;
    margin-right: 5px;
    cursor: pointer;
    font-size: 12px;
}

.allBtn:hover {
    background-color: #5a6268;
}

/* Button styling */
.btn-analyze {
    background-color: #5D4E37;
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 5px;
    transition: all 0.2s ease;
}

.btn-analyze:hover {
    background-color: #4a3d2b;
    color: white;
    transform: translateY(-1px);
    box-shadow: 0 2px 8px rgba(93, 78, 55, 0.2);
}

/* Summary info styling */
.gene-summary {
    background: #fff;
    border: 2px solid #5D4E37;
    border-radius: 8px;
    padding: 15px;
    margin-bottom: 20px;
}

.gene-summary .found {
    color: #28a745;
    font-weight: 600;
}

.gene-summary .not-found {
    color: #dc3545;
    font-weight: 600;
}

/* Clear filters button */
.clear-filters-btn {
    background-color: #6c757d;
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 4px;
    font-size: 12px;
    cursor: pointer;
    width: 100%;
    margin-top: 10px;
}

.clear-filters-btn:hover {
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

/* Legacy styles for compatibility */
#loadingmsg, #loadingover {
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

#loadingmsg {
    background: white;
    width: 300px;
    height: 100px;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    border-radius: 8px;
    border: 1px solid #5D4E37;
}

/* Add this to your existing CSS in the gene result page */

/* Cases filter container with scroll for 30+ items */
.cases-filter-container {
    max-height: 400px; /* Adjust this value as needed */
    overflow-y: auto;
    padding-right: 5px; /* Space for scrollbar */
}

/* Custom scrollbar styling for cases filter */
.cases-filter-container::-webkit-scrollbar {
    width: 8px;
}

.cases-filter-container::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 4px;
}

.cases-filter-container::-webkit-scrollbar-thumb {
    background: #5D4E37;
    border-radius: 4px;
}

.cases-filter-container::-webkit-scrollbar-thumb:hover {
    background: #4a3d2b;
}

/* For Firefox */
.cases-filter-container {
    scrollbar-width: thin;
    scrollbar-color: #5D4E37 #f1f1f1;
}

</style>

<script>
function validate() {
    var hid = $('input[type="hidden"][name="snpid[]"]').serializeArray();
    var fields = $("input[name='snpid[]']").serializeArray();
    if (fields.length == 0 && hid.length == 0) {
        alert('Please select gene/s');
        return false;
    } else {
        showLoading();
        return true;
    }
}

function showLoading() {
    document.getElementById('loadingOverlay').style.display = 'flex';
}
</script>

<div class="container-fluid py-4">
    <h3 class="text-center mb-4" style="color:#5D4E37;">Gene Analysis Results</h3>

    <?php
    $qrym = mysqli_query($conn, "SELECT disease_merge, snps, study_accession, pubmedid, context, snp_type, chr, pos, casee, control, gene, review, MIN(p_value) AS min_pvalue
    FROM snp_table
    WHERE gene IN $snpLst
    GROUP BY disease_merge, snps, study_accession, pubmedid, context, snp_type, chr, pos, casee, control, gene, review");

    $cntm = mysqli_num_rows($qrym);
    echo "<script> var rowCount = '$cntm';</script>";
    
    if($cntm > 0){
        $all_rows = mysqli_fetch_all($qrym, MYSQLI_ASSOC);
        $msnp = array_unique(array_column($all_rows, 'gene'));
        $nmsnp = array_diff($lst_snp, $msnp);
        
        // Get unique values for filters (same as SNP page)
        $unique_cases = array_unique(array_filter(array_column($all_rows, 'casee')));
        $unique_reviews = array_unique(array_column($all_rows, 'review'));
        $unique_snp_types = array_unique(array_column($all_rows, 'snp_type'));
        
        // Prepare data for JavaScript
        $data = array();
        foreach($all_rows as $row){
            $mappedGene = "";
            $gArr = array();
            $hypStu_Acc = $row["study_accession"];
            $qchr_id = $row['chr'];
            $qchr_pos = $row['pos'];
            $pval = sprintf("%.2e", $row['min_pvalue']);
            
            $expGene = explode(';', $row['gene']); 
            if(count($expGene) > 1){
                foreach($expGene as $gen) {
                    if(in_array($gen, $lst_snp)){
                        $gArr[] = $gen;
                    }
                }
                $mappedGene = implode(", ", $gArr);
            } else {
                $mappedGene = $row['gene'];
            }
            
            $data[] = array("'$mappedGene'", "'$row[snps]'", "'$hypStu_Acc'", "'$row[pubmedid]'", "'$row[casee]'", "'$row[control]'" , "'$row[review]'",  "'$row[snp_type]'", "'$qchr_id:$qchr_pos'", "'$pval'");
        }
        $jdata = json_encode($data);
    ?>

    <!-- Gene Summary -->
    <div class="gene-summary">
        <div class="row">
            <?php if(count($msnp) > 0) { ?>
            <div class="col-md-6">
                <span class="found"><?php echo count($msnp); ?> genes found:</span>
                <?php echo implode(", ", $msnp); ?>
            </div>
            <?php } ?>
            <?php if(count($nmsnp) > 0) { ?>
            <div class="col-md-6">
                <span class="not-found"><?php echo count($nmsnp); ?> genes not available:</span>
                <?php echo implode(", ", $nmsnp); ?>
            </div>
            <?php } ?>
        </div>
    </div>

    <!-- Main Results Layout -->
    <div class="results-container">
        <!-- Left Sidebar Filters -->
        <div class="filters-sidebar">
            <h5 style="color: #5D4E37; margin-bottom: 20px;">
                <i class="fas fa-filter me-2"></i>Filters
            </h5>

  <!-- Cases Filter -->
<?php if(count($unique_cases) > 1) { ?>
<div class="filter-section">
    <h6><i class="fas fa-users me-2"></i>Cases</h6>
    <div class="custom-checkbox">
        <input type="checkbox" id="case_all" checked>
        <label for="case_all">All Cases</label>
    </div>
    
    <!-- Wrap cases in scrollable container -->
    <div class="cases-filter-container">
        <?php foreach($unique_cases as $case) { 
            if($case && $case !== '') {
                $case_id = 'case_' . md5($case);
        ?>
        <div class="custom-checkbox">
            <input type="checkbox" class="case-filter" id="<?php echo $case_id; ?>" value="<?php echo htmlspecialchars($case); ?>">
            <label for="<?php echo $case_id; ?>"><?php echo htmlspecialchars($case); ?></label>
        </div>
        <?php }} ?>
    </div>
</div>
<?php } ?>


            <!-- Review Status Filter (same as SNP page) -->
            <?php if(in_array('Reviewed', $unique_reviews) && in_array('Non-reviewed', $unique_reviews)) { ?>
            <div class="filter-section">
                <h6><i class="fas fa-check-circle me-2"></i>Review Status</h6>
                <div class="custom-checkbox">
                    <input type="checkbox" id="reviewed" class="review-filter" value="Reviewed">
                    <label for="reviewed">Reviewed</label>
                </div>
                <div class="custom-checkbox">
                    <input type="checkbox" id="non_reviewed" class="review-filter" value="Non-reviewed">
                    <label for="non_reviewed">Non-reviewed</label>
                </div>
            </div>
            <?php } ?>

            <!-- SNP Type Filter (same as SNP page) -->
            <?php if(in_array('Coding', $unique_snp_types) && in_array('Non-coding', $unique_snp_types)) { ?>
            <div class="filter-section">
                <h6><i class="fas fa-dna me-2"></i>SNP Type</h6>
                <div class="custom-checkbox">
                    <input type="checkbox" id="coding" class="type-filter" value="Coding">
                    <label for="coding">Coding</label>
                </div>
                <div class="custom-checkbox">
                    <input type="checkbox" id="non_coding" class="type-filter" value="Non-coding">
                    <label for="non_coding">Non-coding</label>
                </div>
            </div>
            <?php } ?>

            <!-- Clear Filters -->
            <button type="button" class="clear-filters-btn" onclick="clearAllFilters()">
                <i class="fas fa-times me-1"></i>Clear All Filters
            </button>
        </div>

        <!-- Main Results Area -->
        <div class="results-main">
            <div class="results-table-container">
                <table class="results-table cell-border" id="resultsTable">
                    <thead>
                        <tr>
                            <th>Mapped Gene/s</th>
                            <th>SNP ID</th>
                            <th>Disease/Trait</th>
                            <th>Study</th>
                            <th>PubMed ID</th>
                            <th>Case</th>
                            <th>Control</th>
                            <th>Review</th>
                            <th>Location</th>
                            <th>SNP Type</th>
                            <th>P-value</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        foreach($all_rows as $row){
                            $mappedGene = "";
                            $gArr = array();
                            $hypStu_Acc = $row["study_accession"];
                            $qchr_id = $row['chr'];
                            $qchr_pos = $row['pos'];
                            $pval = sprintf("%.2e", $row['min_pvalue']);
                            
                            $expGene = explode(';', $row['gene']); 
                            if(count($expGene) > 1){
                                foreach($expGene as $gen) {
                                    if(in_array($gen, $lst_snp)){
                                        $gArr[] = $gen;
                                    }
                                }
                                $mappedGene = implode(", ", $gArr);
                            } else {
                                $mappedGene = $row['gene'];
                            }
                        ?>
                        <tr>
                            <td><?php echo $mappedGene; ?></td>
                            <td><a href="https://www.ncbi.nlm.nih.gov/snp/<?php echo $row["snps"]; ?>" target="_blank"><?php echo $row["snps"];?></a></td>
                            <td><?php echo htmlspecialchars($row['disease_merge']); ?></td>
                            <td><a href="https://www.ebi.ac.uk/gwas/studies/<?php echo $hypStu_Acc; ?>" target="_blank"><?php echo $hypStu_Acc; ?></a></td>
                            <td><a href="https://pubmed.ncbi.nlm.nih.gov/<?php echo $row['pubmedid']; ?>" target="_blank"><?php echo $row["pubmedid"];?></a></td>
                            <td><?php echo htmlspecialchars($row['casee']); ?></td>
                            <td><?php echo htmlspecialchars($row['control']); ?></td>
                            <td><?php echo htmlspecialchars($row['review']); ?></td>
                            <td><?php echo $row['chr'].": ".$row['pos']; ?></td>
                            <td><?php echo htmlspecialchars($row['snp_type']); ?></td>
                            <td><?php echo $pval; ?></td>
                        </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>

            <!-- Gene Count for Analysis -->
            <div class="gene-count" id="geneCount">
                <span class="count-number" id="countNumber"><?php echo count($msnp); ?></span> SNPs selected for downstream analysis
            </div>

            <!-- Analysis Options (same structure as SNP page) -->
            <form action="qtAnal_gene.php" method="post" id="analysisForm">
                <div class="analysis-options">
                    <h5 style="color: #5D4E37; margin-bottom: 20px;">
                        <i class="fas fa-chart-line me-2"></i>Analysis Options
                    </h5>

                    <div class="option-group">
                        <!-- Mapped Gene Analysis -->
                        <div class="option-section">
                            <h6>Mapped Gene Analysis</h6>
                            <div class="custom-checkbox">
                                <input type="checkbox" id="mapped_gene" name="mapped_gene" value="1">
                                <label for="mapped_gene">Include mapped gene analysis</label>
                            </div>
                            <small class="text-muted">Analyze genes mapped to selected SNPs</small>
                        </div>

                        <!-- QTL Analysis -->
                        <div class="option-section">
                            <h6>QTL Analysis</h6>
                            <div class="custom-checkbox">
                                <input type="checkbox" id="qtl_analysis" name="qtl_analysis" value="1">
                                <label for="qtl_analysis">Include QTL analysis</label>
                            </div>
                            
                            <div class="qtl-controls" id="qtl_controls" style="display: none;">
                                <div class="pvalue-section">
                                    <label><strong>P-value cutoff:</strong></label>
                                    <div class="pvalue-input">
                                        <input type="text" name="p_value1" value="5" size="1" maxlength="1"> × 10<sup>-<input type="text" name="p_value2" value="2" size="2" maxlength="2"></sup>
                                    </div>
                                </div>
                                
                                <div class="tissue-section">
                                    <label><strong>Select tissues:</strong></label>
                                    <select name="tissue[]" id="tissueSelect" multiple="multiple" data-placeholder="Select Tissue">
                                        <option></option>
                                        <?php  
                                        $tab = array("Adipose", "Adipose-Subcutaneous", "Adipose-Visceral", "Adrenal_Gland", "Artery", "Artery-Aorta", "Artery-Coronary", "Artery-Tibial", "Bladder", "Blood", "Blood-B_cell", "Blood-B_cell_CD19+", "Blood-Erythroid", "Blood-Macrophage", "Blood-Monocyte", "Blood-Monocytes_CD14+", "Blood-Natural_killer_cell", "Blood-Neutrophils_CD16+", "Blood-T_cell", "Blood-T_cell_CD4+", "Blood-T_cell_CD4+_activated", "Blood-T_cell_CD4+_naive", "Blood-T_cell_CD8+", "Blood-T_cell_CD8+_activated", "Blood-T_cell_CD8+_naive", "Bone", "Brain", "Brain-Amygdala", "Brain-Anterior_Cingulate_Cortex", "Brain-Caudate", "Brain-Cerebellar_Hemisphere", "Brain-Cerebellum", "Brain-Cortex", "Brain-Frontal_Cortex", "Brain-Hippocampus", "Brain-Hypothalamus", "Brain-Nucleus_Accumbens", "Brain-Pons", "Brain-Prefrontal_Cortex", "Brain-Putamen", "Brain-Spinal_Cord", "Brain-Substantia_Nigra", "Brain-Temporal_Cortex", "Breast", "Cartilage", "Central_Nervous_System", "Cervix", "Dendritic_cells", "Epithelium", "Esophagus", "Eye", "Fibroblast", "Gallbladder", "Heart", "Heart-Atrial_Appendage", "Heart-Left_Ventricle", "Kidney", "Large_Intestine", "Large_Intestine-Colon", "Large_Intestine-Rectum", "Liver", "Lung", "Lymphocyte", "Minor_Salivary_Gland", "Mouth-Saliva", "Mouth-Sputum", "Muscle", "Muscle-Skeletal", "Muscle-Smooth", "Ovary", "Pancreas", "Peripheral_Nervous_System", "Placenta", "Prostate", "Skin", "Small_Intestine", "Small_Intestine-Duodenum", "Small_Intestine-Ileum", "Spleen", "Stomach", "Testis", "Thymus", "Thyroid_Gland", "Uterus", "Vagina");
                                        foreach($tab as $tabn) {
                                            $tabn1 = ucfirst(str_replace("_", " ", $tabn));
                                            echo "<option value='$tabn'>$tabn1</option>";
                                        } 
                                        ?>
                                    </select>
                                    <div class="tissue-buttons">
                                        <button type="button" id="addAll" class="allBtn">Add all</button>
                                        <button type="button" id="resetAll" class="allBtn">Clear all</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Submit Buttons -->
                    <div class="text-center">
                        <button type="submit" class="btn-analyze me-3">
                            <i class="fas fa-play me-2"></i>Start Analysis
                        </button>
                        <button type="button" class="btn btn-outline-secondary" onclick="location.href='gene-input.php';">
                            <i class="fas fa-arrow-left me-2"></i>Back to Input
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <?php } else { ?>
        <div class="text-center">
            <div class="alert alert-warning">
                <h5><i class="fas fa-exclamation-triangle me-2"></i>No Results Found</h5>
                <p>No genes from your input were found in our database.</p>
                <button type="button" class="btn btn-outline-secondary" onclick="location.href='gene-input.php';">
                    <i class="fas fa-arrow-left me-2"></i>Back to Input
                </button>
            </div>
        </div>
    <?php } ?>
</div>

<!-- Loading Overlay -->
<div id="loadingOverlay">
    <div class="loading-content">
        <div class="loading-spinner"></div>
        <p>Processing gene analysis...</p>
    </div>
</div>

<script>
// Initialize DataTable and variables (identical to SNP page)
let dataTable;
let allData = <?php echo isset($jdata) ? $jdata : '[]'; ?>;
let filteredData = [...allData];

$(document).ready(function() {
    // Initialize DataTable with custom download button placement
    dataTable = $('#resultsTable').DataTable({
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
        responsive: true,
        dom: 'lfBrtip',
        buttons: [
            {
                extend: 'csvHtml5',
                text: '<i class="fas fa-download"></i> Download TSV',
                title: 'Gene_Analysis_Results',
                filename: 'Gene_Analysis_Results',
                fieldSeparator: '\t',
                extension: '.tsv',
                exportOptions: {
                    modifier: {
                        search: 'applied',
                        order: 'applied'
                    }
                }
            }
        ],
        columnDefs: [
            { targets: [0, 1, 3, 4], className: 'text-center' },
            { targets: [9], className: 'text-end' }
        ],
        language: {
            search: "Search results:",
            lengthMenu: "Show _MENU_ entries",
            info: "Showing _START_ to _END_ of _TOTAL_ entries"
        },
        drawCallback: function(settings) {
            updateGeneCount();
        },
        initComplete: function() {
            // Move buttons to be INSIDE the filter container, after the label
            var buttonsContainer = $('.dt-buttons').detach();
            $('.dataTables_filter').append(buttonsContainer);
            
            // Ensure proper flex ordering
            $('.dataTables_filter').css({
                'display': 'flex',
                'align-items': 'center',
                'gap': '10px',
                'justify-content': 'flex-end'
            });
            
            $('.dataTables_filter label').css('order', '1');
            $('.dt-buttons').css('order', '2');
        }
    });

    // Initialize filter event listeners
    initializeFilters();
    
    // Initialize QTL controls
    initializeQTLControls();
    
    // Initialize Select2 for tissue selection
    initializeSelect2();
    
    // Initial gene count
    updateGeneCount();
});

function initializeFilters() {
    // Case filter logic
    $('#case_all').on('change', function() {
        if (this.checked) {
            $('.case-filter').prop('checked', false);
        }
        applyFilters();
    });

    $('.case-filter').on('change', function() {
        if (this.checked) {
            $('#case_all').prop('checked', false);
        }
        applyFilters();
    });

    // Review and type filters - apply immediately on change
    $('.review-filter, .type-filter').on('change', function() {
        applyFilters();
    });
}

function initializeQTLControls() {
    $('#qtl_analysis').on('change', function() {
        if (this.checked) {
            $('#qtl_controls').slideDown(300);
        } else {
            $('#qtl_controls').slideUp(300);
        }
    });
}

function initializeSelect2() {
    // Initialize Select2 for tissue selection
    $('#tissueSelect').select2({
        placeholder: "Select Tissue",
        allowClear: true,
        width: '100%'
    });

    // Add all tissues functionality
    $('#addAll').on('click', function() {
        $('#tissueSelect option:not(:first-child)').prop('selected', true);
        $('#tissueSelect').trigger('change');
    });

    // Clear all tissues functionality
    $('#resetAll').on('click', function() {
        $('#tissueSelect').val(null).trigger('change');
    });
}

function applyFilters() {
    filteredData = [...allData];

    // Apply case filters
    const caseAllChecked = $('#case_all').is(':checked');
    if (!caseAllChecked) {
        const selectedCases = $('.case-filter:checked').map(function() {
            return this.value;
        }).get();
        
        if (selectedCases.length > 0) {
            filteredData = filteredData.filter(row => {
                // Fix: Check if row[4] exists and convert to string properly
                const caseValue = row[4] ? String(row[4]).replace(/'/g, '') : '';
                return selectedCases.includes(caseValue);
            });
        }
    }

    // Apply review filters
    const selectedReviews = $('.review-filter:checked').map(function() {
        return this.value;
    }).get();
    
    if (selectedReviews.length > 0) {
        filteredData = filteredData.filter(row => {
            // Fix: Check if row[6] exists and convert to string properly
            const reviewValue = row[6] ? String(row[6]).replace(/'/g, '') : '';
            return selectedReviews.includes(reviewValue);
        });
    }

    // Apply type filters
    const selectedTypes = $('.type-filter:checked').map(function() {
        return this.value;
    }).get();
    
    if (selectedTypes.length > 0) {
        filteredData = filteredData.filter(row => {
            // Fix: Check if row[7] exists and convert to string properly
            const typeValue = row[7] ? String(row[7]).replace(/'/g, '') : '';
            return selectedTypes.includes(typeValue);
        });
    }

    // Update table display
    updateTableDisplay();
    
    // Update analysis options visibility
    updateAnalysisOptions();
}

function updateTableDisplay() {
    // Clear current table
    dataTable.clear();
    
    // Add filtered data
    filteredData.forEach(row => {
        // Fix: Safely convert values to strings and handle null/undefined values
        const safeStringConvert = (value) => {
            return value ? String(value).replace(/'/g, '') : '';
        };
        
        const formattedRow = [
            safeStringConvert(row[0]), // gene
            `<a href="https://www.ncbi.nlm.nih.gov/snp/${safeStringConvert(row[1])}" target="_blank">${safeStringConvert(row[1])}</a>`,
            safeStringConvert(row[2]), // disease - this should not have hyperlink here
            `<a href="https://www.ebi.ac.uk/gwas/studies/${safeStringConvert(row[2])}" target="_blank">${safeStringConvert(row[2])}</a>`, // study
            `<a href="https://pubmed.ncbi.nlm.nih.gov/${safeStringConvert(row[3])}" target="_blank">${safeStringConvert(row[3])}</a>`, // pubmed
            safeStringConvert(row[4]), // cases
            safeStringConvert(row[6]), // review
            safeStringConvert(row[8]), // location
            safeStringConvert(row[7]), // type
            safeStringConvert(row[9])  // pvalue
        ];
        dataTable.row.add(formattedRow);
    });
    
    dataTable.draw();
}

function updateGeneCount() {
    // Get unique genes from current filtered data
    const uniqueGenes = [...new Set(filteredData.map(row => {
        return row[1] ? String(row[1]).replace(/'/g, '') : '';
    }))];
    const count = uniqueGenes.length;
    
    const countElement = document.getElementById('countNumber');
    if (countElement) {
        countElement.textContent = count;
    }
    
    // Update the text based on count
    const countText = count === 1 ? 'SNP selected' : 'SNPs selected';
    const geneCountElement = document.getElementById('geneCount');
    if (geneCountElement) {
        geneCountElement.innerHTML = `<span class="count-number">${count}</span> ${countText} for downstream analysis`;
    }
}

function updateAnalysisOptions() {
    // Show/hide analysis options based on filtered data
    const hasCoding = filteredData.some(row => {
        const typeValue = row[7] ? String(row[7]) : '';
        return typeValue.includes('Coding');
    });
    const hasNonCoding = filteredData.some(row => {
        const typeValue = row[7] ? String(row[7]) : '';
        return typeValue.includes('Non-coding');
    });
    
    // Update mapped gene option visibility based on available data
    const mappedGeneSection = $('#mapped_gene').closest('.option-section');
    if (!hasCoding && !hasNonCoding) {
        mappedGeneSection.hide();
    } else {
        mappedGeneSection.show();
    }
}

function clearAllFilters() {
    // Clear all checkboxes
    $('.case-filter, .review-filter, .type-filter').prop('checked', false);
    
    // Check "All Cases"
    $('#case_all').prop('checked', true);
    
    // Apply filters to reset display
    applyFilters();
}

// Form submission - Fixed loading overlay issue
$('#analysisForm').on('submit', function(e) {
    e.preventDefault();

    // Check if neither analysis checkbox is checked
    let geneChecked = $('#mapped_gene').is(':checked');
    let qtlChecked = $('#qtl_analysis').is(':checked');
    if (!geneChecked && !qtlChecked) {
        alert("Please select at least one analysis option (Mapped Gene Analysis or QTL Analysis) before proceeding.");
        return false;
    }
    
    // Remove any existing hidden inputs
    $(this).find('input[name="snpid[]"]').remove();
    
    // Add filtered genes to form (using same structure as SNP page)
    const uniqueGenes = [...new Set(filteredData.map(row => {
        return row[1] ? String(row[1]).replace(/'/g, '') : '';
    }))];
    
    const geneData = uniqueGenes.map(gene => {
        const row = filteredData.find(r => {
            const geneValue = r[1] ? String(r[1]).replace(/'/g, '') : '';
            return geneValue === gene;
        });
        if (row) {
            const locationValue = row[8] ? String(row[8]).replace(/'/g, '') : '';
            return `${gene}#${locationValue}`;
        }
        return gene;
    });
    
    const form = this;
    geneData.forEach(gene => {
        $('<input>').attr({
            type: 'hidden',
            name: 'snpid[]',
            value: gene
        }).appendTo(form);
    });

    // Add selected cases information
    const caseAllChecked = $('#case_all').is(':checked');
    
    if (caseAllChecked) {
        // User selected "All Cases"
        $('<input>').attr({
            type: 'hidden',
            name: 'case_selection_type',
            value: 'all'
        }).appendTo(form);
    } else {
        // User selected specific cases
        const selectedCases = $('.case-filter:checked').map(function() {
            return this.value;
        }).get();
        
        if (selectedCases.length > 0) {
            $('<input>').attr({
                type: 'hidden',
                name: 'case_selection_type',
                value: 'specific'
            }).appendTo(form);
            
            // Add each selected case as separate hidden input
            selectedCases.forEach(caseValue => {
                $('<input>').attr({
                    type: 'hidden',
                    name: 'selected_cases[]',
                    value: caseValue
                }).appendTo(form);
            });
        } else {
            // No cases selected (shouldn't happen, but handle it)
            $('<input>').attr({
                type: 'hidden',
                name: 'case_selection_type',
                value: 'none'
            }).appendTo(form);
        }
    }
    
    // Show loading - Fixed the null element issue
    const loadingOverlay = document.getElementById('loadingOverlay');
    if (loadingOverlay) {
        loadingOverlay.style.display = 'flex';
    }
    
    // Submit form
    this.submit();
});

// Initialize filters when page loads
document.addEventListener('DOMContentLoaded', function() {
    // Apply initial filters
    applyFilters();
});
</script>

<?php include('footer.php'); ?>
