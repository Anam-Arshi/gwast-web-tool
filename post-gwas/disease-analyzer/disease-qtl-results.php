<?php
ini_set('display_errors', '1');
error_reporting(E_ALL);
include("../../connect.php");
include('../../header.php');
session_start();

// --- 1. DATA RECEPTION AND PREPARATION ---

// QTL P-value cutoff from the previous form
$qtl_p_value1 = $_POST["qtl_p_value1"] ?? 5;
$qtl_p_value2 = $_POST["qtl_p_value2"] ?? 8;
$pvalue = $qtl_p_value1 * pow(10, -$qtl_p_value2);
$pvalue_qtl = sprintf('%.20f', $pvalue); 

// Get the array of selected tissues
$allTissue = $_POST['tissue'] ?? [];

// Check which analyses were requested
$show_mapped_genes = isset($_POST['mapped_genes']);
$show_qtl_analysis = isset($_POST['qtl_anal']);

// Process the filtered SNP data passed from the previous page
$arrD = $_POST["stu_acc"] ?? [];
$adata = array_unique($arrD);

$pos_list = [];
$snp_ids = [];
$snp_details_map = []; // To hold details for QTL lookup
$disease_names = [];
$study_accessions = [];

foreach ($adata as $stu_acct) {
    $parts = explode("#", $stu_acct);
    if (count($parts) < 5) continue; // Skip malformed data
    // [0]=>Location, [1]=>StudyAccession, [2]=>Disease, [3]=>SNPType, [4]=>SNPid
    
    list($location, $accession, $disease, $snp_type, $snp_id) = $parts;
    list($chr, $pos) = explode(': ', $location);
    $pos = intval($pos);

    $pos_list[] = $pos;
    $snp_ids[] = $snp_id;
    $disease_names[] = $disease;
    $study_accessions[] = $accession;

    // Store details in a map with position as the key for easy lookup later
    if ($pos > 0) {
        $snp_details_map[$pos] = ['id' => $snp_id, 'type' => $snp_type, 'chr' => $chr];
    }
}

$pos_list = array_unique($pos_list);
$snp_ids = array_unique($snp_ids);
$disease_names = array_unique($disease_names);
$study_accessions = array_unique($study_accessions);
$disease_in_clause = "('" . implode("','", array_map([$conn, 'real_escape_string'], $disease_names)) . "')";

// --- 2. DATABASE QUERIES ---

$mapped_genes_data = [];
// REVISED: Using your simplified query for Mapped Genes with snp_ids
if ($show_mapped_genes && !empty($snp_ids)) {
    $snpid_in_clause = "('" . implode("','", array_map([$conn, 'real_escape_string'], $snp_ids)) . "')";
    $sql_mapped_genes = "
        SELECT snps, chr, pos, gene, MIN(p_value) AS min_pvalue
        FROM snp_table 
        WHERE snps IN $snpid_in_clause AND gene IS NOT NULL
        GROUP BY snps, chr, pos, gene
        ORDER BY min_pvalue ASC";
    
    $result_mapped = mysqli_query($conn, $sql_mapped_genes);
    if ($result_mapped) {
        while ($row = mysqli_fetch_assoc($result_mapped)) {
            $mapped_genes_data[] = [
               'snp_id' => $row['snps'],
                'location' => $row['chr'] . ':' . $row['pos'],
                'gene' => str_replace(";", ", ", $row['gene']),
                'p_value' => sprintf("%.2e", $row['min_pvalue'])
            ];
        }
    }
}

// Query for QTL Analysis (if checkbox was selected)
$qtl_results_data = [];
if ($show_qtl_analysis && !empty($allTissue) && !empty($pos_list)) {
    $pos_in_clause = implode(",", array_map('intval', $pos_list));

    foreach ($allTissue as $tsu_table_name) {
        $tsu_table_name = mysqli_real_escape_string($conn, str_replace("`", "", $tsu_table_name));
        $tissue_display_name = ucfirst(str_replace("_", " ", $tsu_table_name));

        // --- CORRECTED QUERY ---
        // This query now fetches ALL records that meet the p-value cutoff, without finding the minimum.
        $sql_qtl = "
            SELECT distinct 
                t1.SNP_pos_hg38 AS pos,
                t1.Mapped_gene AS gene,
                t1.Pvalue AS pvalue,
                qsi.xQTL AS xQTL
            FROM 
                `$tsu_table_name` t1
            JOIN 
                qtlbase_sourceid qsi ON t1.Sourceid = qsi.Sourceid
            WHERE 
                t1.SNP_pos_hg38 IN ($pos_in_clause)
                AND t1.Pvalue <= '$pvalue_qtl'
                AND t1.Mapped_gene IS NOT NULL
                AND qsi.Tissue = '" . mysqli_real_escape_string($conn, $tissue_display_name) . "'
        ";

        $result_qtl = mysqli_query($conn, $sql_qtl);
        if ($result_qtl) {
            while ($row = mysqli_fetch_assoc($result_qtl)) {
                $pos = $row['pos'];
                // The rest of the logic remains the same, using the map to get SNP details.
                if (isset($snp_details_map[$pos])) {
                    $qtl_results_data[] = [
                        'snp_id' => $snp_details_map[$pos]['id'],
                        'location' => $snp_details_map[$pos]['chr'] . ':' . $pos,
                        'snp_type' => $snp_details_map[$pos]['type'],
                        'gene' => $row['gene'],
                        'p_value' => sprintf("%.2e", $row['pvalue']),
                        'tissue' => $tissue_display_name,
                        'qtl_type' => $row['xQTL']
                    ];
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Analysis Results</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.2.2/css/buttons.dataTables.min.css">
    
    <style>
        /* Using the same theme from the previous page for consistency */
        :root {
            --primary-beige: #F5F0E8; --secondary-beige: #E8DCC0; --accent-beige: #F9F4EE;
            --primary-brown: #8B7355; --dark-brown: #6B5B47; --light-brown: #A68A6F;
            --primary-gray: #6B7280; --accent-gray: #9CA3AF; --text-dark: #4A4A4A;
            --border-color: #B99C6B; --hover-color: #D4C4A8;
        }
        body { background-color: var(--primary-beige); color: var(--text-dark); font-family: Arial, sans-serif; }
        .main-container { max-width: 1400px; margin: 0 auto; padding: 20px; }
        .page-title { text-align: center; color: var(--dark-brown); margin-bottom: 30px; font-size: 28px; font-weight: bold; }
        
        /* Section Styling */
        .result-section, .analysis-section, .summary-section {
            background: white; padding: 25px; border-radius: 12px;
            border: 2px solid var(--border-color); margin-bottom: 30px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .result-section h3, .analysis-section h3, .summary-section h3 {
            margin-top: 0; color: var(--dark-brown); border-bottom: 3px solid var(--primary-brown);
            padding-bottom: 15px; margin-bottom: 25px; font-size: 20px;
        }
        .summary-section p { margin: 5px 0; font-size: 14px; }
        .summary-section p b { color: var(--dark-brown); }

        /* Table Styling */
        table.dataTable { width: 100% !important; border-collapse: collapse !important; font-size: 13px; }
        table.dataTable thead th {
            background: var(--secondary-beige); color: var(--dark-brown); font-weight: bold;
            padding: 12px 18px; border: 1px solid var(--border-color); text-align: left;
            white-space: nowrap;
        }
        table.dataTable tbody td { padding: 10px 18px; border: 1px solid var(--border-color); vertical-align: middle; }
        table.dataTable tbody tr:nth-child(even) { background-color: var(--accent-beige); }
        table.dataTable tbody tr:hover { background-color: var(--hover-color); }
        table.dataTable a { color: var(--primary-brown); text-decoration: none; font-weight: 500; }
        table.dataTable a:hover { color: var(--dark-brown); text-decoration: underline; }
        
        /* DataTables Controls Styling */
        .dataTables_wrapper .dataTables_paginate .paginate_button {
            color: var(--primary-brown) !important; background: white; border: 1px solid var(--border-color) !important;
            border-radius: 5px; margin: 0 3px; padding: 0.4em 0.8em;
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button:hover { background: var(--hover-color) !important; }
        .dataTables_wrapper .dataTables_paginate .paginate_button.current { background: var(--primary-brown) !important; color: white !important; }
        .dt-button {
            background: linear-gradient(135deg, var(--primary-brown) 0%, var(--dark-brown) 100%) !important;
            color: white !important; border: none !important; padding: 8px 16px !important;
            border-radius: 6px !important; font-weight: bold !important;
        }
        
        /* Gene Selection and Analysis Form Styling */
        .gene-selection-display { background: var(--accent-beige); border: 1px solid var(--border-color); border-radius: 8px; padding: 15px; min-height: 50px; line-height: 1.6; font-family: monospace; font-size: 13px; word-wrap: break-word; }
        .analysis-options label { display: flex; align-items: center; margin-bottom: 15px; cursor: pointer; }
        .analysis-options input[type="radio"] { margin-right: 12px; transform: scale(1.2); accent-color: var(--primary-brown); }
        .btn {
            background: linear-gradient(135deg, var(--primary-brown) 0%, var(--dark-brown) 100%); color: white;
            border: none; padding: 12px 25px; border-radius: 6px; cursor: pointer;
            font-size: 16px; font-weight: bold; transition: all 0.3s;
        }
        .btn:hover { transform: translateY(-2px); box-shadow: 0 4px 8px rgba(0,0,0,0.3); }
        .btn-secondary { background: linear-gradient(135deg, var(--accent-gray) 0%, var(--primary-gray) 100%); }
    </style>
</head>

<body>
<div class="main-container">
    <h1 class="page-title">Analysis Results</h1>

    <!-- Analysis Summary Section -->
    <div class="summary-section">
        <h3>Analysis Summary</h3>
        <p><b>Selected Diseases/Traits:</b> <?php echo implode(", ", array_map('htmlspecialchars', $disease_names)); ?></p>
        <p><b>Unique Study Accessions:</b> <?php echo count($study_accessions); ?></p>
        <p><b>SNPs for Analysis:</b> <?php echo count($pos_list); ?></p>
    </div>

    <!-- Mapped Genes Table -->
    <?php if (!empty($mapped_genes_data)): ?>
    <div class="result-section">
        <h3>Mapped Genes</h3>
        <table id="mappedGenesTable">
            <thead>
                <tr>
                    <th><input type="checkbox" id="selectAllMapped"></th>
                    <th>SNP ID</th>
                    <th>Location</th>
                    <th>Mapped Gene</th>
                    <th>P-value</th>
                </tr>
            </thead>
        </table>
    </div>
    <?php endif; ?>

    <!-- QTL Results Table -->
    <?php if (!empty($qtl_results_data)): ?>
    <div class="result-section">
        <h3>QTL Analysis Results</h3>
        <table id="qtlResultsTable">
            <thead>
                <tr>
                    <th><input type="checkbox" id="selectAllQTL"></th>
                    <th>SNP ID</th>
                    <th>Location</th>
                    <th>SNP Type</th>
                    <th>Mapped Gene</th>
                    <th>QTL P-value</th>
                    <th>Tissue</th>
                    <th>QTL Type</th>
                </tr>
            </thead>
        </table>
    </div>
    <?php endif; ?>

    <!-- Perform Further Analysis Section -->
    <form action="gs_en_o.php" method="post" target="_blank" id="analysisForm">
        <div class="analysis-section">
            <h3>Perform Further Analysis on Selected Genes</h3>
            <p>Select genes from the tables above to perform enrichment or prioritization analysis.</p>
            
            <h4>Selected Genes:</h4>
            <div id="geneSelectionDisplay" class="gene-selection-display">No genes selected.</div>
            <br>

            <div class="analysis-options">
                <label><input type="radio" name="p" value="Pathway enrichment" required> Pathway Enrichment</label>
                <label><input type="radio" name="p" value="Disease enrichment (Genes)"> Disease Enrichment (Genes)</label>

                 <!-- Gene Prioritization Section -->
    <?php
    // Check for gene prioritization options
    
    $diseasename = array();
    
    $query = mysqli_query($conn, "SELECT * FROM gene_prioritization WHERE disease_merge_gedipnet IN $disease_in_clause");
	 	while($row = mysqli_fetch_array($query)){
	 		$diseasename[] = $row['disease_merge_gedipnet'];
	 	}
    if (!empty($diseasename)) {
       

        ?>
                <label><input type="radio" name="p" value="Gene prioritization"> Gene Prioritization</label>
                <?php } ?>
            </div>
            
            <div style="text-align: center; margin-top: 20px;">
                <!-- Hidden fields for the form -->
                <input type="hidden" name="dataset" value="disease" />
                <input type="hidden" name="organis" value="human" />
                <input type="hidden" name="diseas" value="<?php echo htmlspecialchars(implode(";", $diseasename)); ?>" />
                
                <input type="submit" value="Submit Analysis" class="btn" />
                <input type="button" onclick="history.back();" value="Back" class="btn btn-secondary" />
            </div>
        </div>
    </form>

</div>

<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.2.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.2.2/js/buttons.html5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>

<script>
$(document).ready(function() {
    // --- 1. GENE SELECTION LOGIC ---
    const selectedGenes = new Set();
    const geneDisplay = $('#geneSelectionDisplay');

    function updateGeneDisplay() {
        if (selectedGenes.size === 0) {
            geneDisplay.text('No genes selected.');
        } else {
            geneDisplay.text(Array.from(selectedGenes).join(', '));
        }
    }

    function handleCheckboxChange(checkbox) {
        const gene = $(checkbox).val();
        const isChecked = $(checkbox).prop('checked');
        
        if (isChecked) {
            selectedGenes.add(gene);
        } else {
            selectedGenes.delete(gene);
        }
        updateGeneDisplay();
    }
    
    // --- 2. DATATABLES INITIALIZATION ---
    function initializeTable(selector, data, columns, selectAllSelector) {
        const table = $(selector).DataTable({
            data: data,
            columns: columns,
            pageLength: 10,
            dom: 'Bfrtip',
            buttons: [{
                extend: 'csvHtml5',
                text: '📋 Download CSV',
                title: $(selector).closest('.result-section').find('h3').text().replace(/ /g, '_'),
            }],
            "columnDefs": [{
                "orderable": false,
                "targets": 0 // First column (checkboxes) is not orderable
            }]
        });

        // Attach event listeners to checkboxes
        $(selector).on('change', 'tbody input[type="checkbox"]', function() {
            handleCheckboxChange(this);
        });

        // "Select All" checkbox logic
        $(selectAllSelector).on('click', function(){
            const isChecked = this.checked;
            $(selector + ' tbody input[type="checkbox"]').each(function() {
                $(this).prop('checked', isChecked);
                handleCheckboxChange(this);
            });
        });
    }

    // Initialize Mapped Genes Table
    const mappedGenesData = <?php echo json_encode($mapped_genes_data); ?>;
    if (mappedGenesData.length > 0) {
        initializeTable('#mappedGenesTable', mappedGenesData, [
            { data: 'gene', render: (d) => `<input type="checkbox" value="${d}">` },
            { data: 'snp_id', render: (d) => `<a href="https://www.ncbi.nlm.nih.gov/snp/${d}" target="_blank">${d}</a>` },
            { data: 'location' },
            { data: 'gene', render: (d) => `<a href="https://www.ncbi.nlm.nih.gov/gene/?term=${d}" target="_blank">${d}</a>` },
            { data: 'p_value' }
        ], '#selectAllMapped');
    }

    // Initialize QTL Results Table
    const qtlResultsData = <?php echo json_encode($qtl_results_data); ?>;
    if (qtlResultsData.length > 0) {
        initializeTable('#qtlResultsTable', qtlResultsData, [
            { data: 'gene', render: (d) => `<input type="checkbox" value="${d}">` },
            { data: 'snp_id', render: (d) => `<a href="https://www.ncbi.nlm.nih.gov/snp/${d}" target="_blank">${d}</a>` },
            { data: 'location' },
            { data: 'snp_type' },
            { data: 'gene', render: (d) => `<a href="https://www.ncbi.nlm.nih.gov/gene/?term=${d}" target="_blank">${d}</a>` },
            { data: 'p_value' },
            { data: 'tissue' },
            { data: 'qtl_type' }
        ], '#selectAllQTL');
    }

    // --- 3. FORM SUBMISSION ---
    $('#analysisForm').on('submit', function(e) {
        if (selectedGenes.size === 0) {
            alert('Please select at least one gene from the tables to perform an analysis.');
            e.preventDefault();
            return;
        }

        // Clear any old hidden inputs and add the currently selected genes
        $(this).find('input[name="gen[]"]').remove();
        Array.from(selectedGenes).forEach(gene => {
            $(this).append(`<input type="hidden" name="gen[]" value="${gene}">`);
        });
    });
});
</script>

</body>
</html>

<?php include('../../footer.php'); ?>
