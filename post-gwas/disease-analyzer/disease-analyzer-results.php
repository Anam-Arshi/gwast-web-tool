<?php
include("connect.php");
include('header.php');


$dataset = "disease";
$p_value1 = $_POST["p_value1"];
$p_value2 = $_POST["p_value2"];
$p_valuee = $p_value1 * pow(10, -$p_value2);


$disha = array();
if(isset($_POST["dis_grr"])) {
    foreach($_POST["dis_grr"] as $disa) {
        $disha[] = $disa;
    }
}


if(count($disha) > 0) {
?>


<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Disease Analyzer Results</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="css/select2_min.css">
    <script src="js/select2_min.js"></script>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.2.2/css/buttons.dataTables.min.css">
    <style>
:root {
    --primary-beige: #F5F0E8;
    --secondary-beige: #E8DCC0;
    --accent-beige: #F9F4EE;
    --primary-brown: #8B7355;
    --dark-brown: #6B5B47;
    --light-brown: #A68A6F;
    --primary-gray: #6B7280;
    --accent-gray: #9CA3AF;
    --text-dark: #4A4A4A;
    --border-color: #B99C6B;
    --hover-color: #D4C4A8;
}



        body {
            background-color: var(--primary-beige);
            color: var(--text-dark);
            font-family: Arial, sans-serif;
        }


.main-container {
    max-width: 1700px; /* Changed from 1600px */
    margin: 0 auto;
    padding: 20px;
}



        .page-title {
            text-align: center;
            color: var(--dark-brown);
            margin-bottom: 30px;
            font-size: 28px;
            font-weight: bold;
        }


        .content-wrapper {
            display: flex;
            gap: 25px;
            margin-bottom: 30px;
        }


        .filters-panel {
            flex: 0 0 320px;
            background: linear-gradient(135deg, var(--secondary-beige) 0%, var(--accent-beige) 100%);
            padding: 25px;
            border-radius: 12px;
            border: 2px solid var(--border-color);
            height: fit-content;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }


        .filters-panel h3 {
            margin-top: 0;
            color: var(--dark-brown);
            border-bottom: 3px solid var(--primary-brown);
            padding-bottom: 10px;
            font-size: 18px;
        }


        .filter-group {
    margin-bottom: 25px;
    padding: 15px;
    background: rgba(255,255,255,0.6);
    border-radius: 8px;
    border-left: 4px solid var(--primary-gray); /* Changed from primary-blue */
}



        .filter-group label.group-title {
            font-weight: bold;
            color: var(--dark-brown);
            display: block;
            margin-bottom: 12px;
            font-size: 15px;
        }


        .checkbox-group {
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin-left: 10px;
        }


        .checkbox-group label {
            display: flex;
            align-items: center;
            padding: 6px 10px;
            border-radius: 6px;
            transition: background-color 0.2s;
            cursor: pointer;
        }


        .checkbox-group label:hover {
            background-color: var(--hover-color);
        }


        .checkbox-group input[type="checkbox"] {
            margin-right: 10px;
            accent-color: var(--primary-brown);
            transform: scale(1.1);
        }


        .reset-filters {
            width: 100%;
            background: linear-gradient(135deg, var(--accent-gray) 0%, var(--primary-gray) 100%); /* Changed colors */
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: bold;
            margin-top: 20px;
            transition: transform 0.2s;
        }



        .reset-filters:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }


        .table-container {
            flex: 1;
            background: white;
            border-radius: 12px;
            border: 2px solid var(--border-color);
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }


        #qtlTblMap {
            width: 100% !important;
            border-collapse: collapse;
            background: white;
            font-size: 13px;
        }


        #qtlTblMap thead {
    background: var(--secondary-beige); /* Solid color instead of gradient */
}



        /* --- MODIFIED --- */
#qtlTblMap th {
    padding: 12px 35px 12px 15px; /* Top, Right, Bottom, Left - Increased right padding */
    border: 1px solid var(--border-color);
    font-weight: bold;
    color: var(--dark-brown); 
    white-space: nowrap; 
    text-align: left; 
    position: relative; /* Needed for precise icon positioning */
}


        


        #qtlTblMap td {
            padding: 10px 15px; /* MODIFIED: Increased padding */
            border: 1px solid var(--border-color);
            text-align: left; /* MODIFIED: Align text to the left */
            vertical-align: middle; /* MODIFIED: Center content vertically */
        }
        
        /* --- NEW ---: Class for centering specific columns */
        #qtlTblMap .dt-center {
            text-align: center;
        }


        #qtlTblMap tbody tr:nth-child(even) {
            background-color: var(--accent-beige);
        }


        #qtlTblMap tbody tr:hover {
            background-color: var(--hover-color);
            /* transform: scale(1.01); Removed for smoother hover */
            transition: all 0.2s;
        }

        /* --- NEW ---: Style for links within the table */
        #qtlTblMap a {
            color: var(--primary-brown);
            text-decoration: none;
            font-weight: 500;
        }
        #qtlTblMap a:hover {
            color: var(--dark-brown);
            text-decoration: underline;
        }


        .analysis-section {
            background: linear-gradient(135deg, var(--secondary-beige) 0%, var(--accent-beige) 100%);
            padding: 30px;
            border-radius: 12px;
            border: 2px solid var(--border-color);
            margin: 25px 0;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }


        .analysis-section h3 {
            margin-top: 0;
            color: var(--dark-brown);
            text-align: center;
            font-size: 22px;
            border-bottom: 3px solid var(--primary-brown);
            padding-bottom: 15px;
            margin-bottom: 25px;
        }


        .analysis-options {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 25px;
        }


        .analysis-card {
            background: rgba(255,255,255,0.8);
            padding: 20px;
            border-radius: 10px;
            border: 2px solid var(--light-brown);
            text-align: center;
            transition: all 0.3s;
        }


        .analysis-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0,0,0,0.2);
        }


        .analysis-card input[type="checkbox"] {
            transform: scale(1.5);
            margin-bottom: 10px;
            accent-color: var(--primary-brown);
        }


        .analysis-card h4 {
            margin: 15px 0 10px 0;
            color: var(--dark-brown);
            font-size: 16px;
        }


        .analysis-card p {
            color: var(--text-dark);
            font-size: 13px;
            line-height: 1.4;
            margin: 0;
        }


        .qtl-options {
    grid-column: 1 / -1;
    background: rgba(255,255,255,0.9);
    padding: 20px;
    border-radius: 10px;
    border: 2px solid var(--primary-gray); /* Changed from primary-blue */
    margin-top: 20px;
}



        .qtl-options h4 {
            color: var(--dark-brown);
            margin-bottom: 15px;
            text-align: center;
        }


        .p-value-section {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }


        .p-value-section label {
            font-weight: bold;
            color: var(--dark-brown);
        }


        .p-value-section input {
            padding: 6px 10px;
            border: 2px solid var(--border-color);
            border-radius: 5px;
            text-align: center;
            font-weight: bold;
        }


        .tissue-selection label {
            font-weight: bold;
            color: var(--dark-brown);
            margin-bottom: 10px;
            display: block;
            text-align: center;
        }


        .tissue-controls {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-top: 15px;
        }


        .btn {
            background: linear-gradient(135deg, var(--primary-brown) 0%, var(--dark-brown) 100%);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            font-weight: bold;
            transition: all 0.3s;
        }


        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.3);
        }


        .btn-secondary {
            background: linear-gradient(135deg, var(--accent-gray) 0%, var(--primary-gray) 100%); /* Changed colors */
        }



        .form-actions {
            text-align: center;
            margin-top: 30px;
        }


        .form-actions .btn {
            margin: 0 15px;
            padding: 15px 30px;
            font-size: 16px;
        }


        .select2-container .select2-selection--multiple {
            min-height: 45px;
            border: 2px solid var(--border-color);
            border-radius: 6px;
        }

        /* --- MODIFIED ---: Updated DataTables control styles */
        .dataTables_wrapper .dataTables_filter {
            float: right;
            text-align: right;
        }
        
        .dataTables_wrapper .dataTables_filter input,
        .dataTables_wrapper .dataTables_length select {
            border: 1px solid var(--border-color);
            border-radius: 5px;
            padding: 8px 12px;
            margin-left: 10px;
            background-color: var(--accent-beige);
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .dataTables_wrapper .dataTables_filter input:focus,
        .dataTables_wrapper .dataTables_length select:focus {
             outline: none;
            border-color: var(--primary-brown);
            box-shadow: 0 0 0 2px rgba(139, 115, 85, 0.3);
        }

        .dataTables_wrapper .dataTables_info {
            color: var(--text-dark);
            padding-top: 0.85em; /* Align with pagination */
        }

        /* --- NEW ---: DataTables Pagination Styles */
        .dataTables_paginate .paginate_button {
            color: var(--primary-brown) !important;
            background: white;
            border: 1px solid var(--border-color) !important;
            border-radius: 5px;
            margin: 0 3px;
            padding: 0.4em 0.8em;
            transition: all 0.2s ease-in-out;
        }
        .dataTables_paginate .paginate_button:hover {
            background: var(--hover-color) !important;
            color: var(--dark-brown) !important;
            border-color: var(--primary-brown) !important;
        }
        .dataTables_paginate .paginate_button.current, 
        .dataTables_paginate .paginate_button.current:hover {
            background: var(--primary-brown) !important;
            color: white !important;
            border-color: var(--dark-brown) !important;
            font-weight: bold;
        }
        .dataTables_paginate .paginate_button.disabled, 
        .dataTables_paginate .paginate_button.disabled:hover {
            background: var(--accent-beige) !important;
            color: var(--accent-gray) !important;
            border-color: var(--secondary-beige) !important;
            cursor: not-allowed;
        }

        /* --- NEW ---: DataTables Sorting Icon Styles */
        /* --- MODIFIED --- */
#qtlTblMap.dataTable thead .sorting,
#qtlTblMap.dataTable thead .sorting_asc,
#qtlTblMap.dataTable thead .sorting_desc {
    background-repeat: no-repeat;
    background-position: center right 0.8rem; /* Positioned icon */
    background-size: 0.8em; /* Made icon slightly larger */
}

#qtlTblMap.dataTable thead .sorting { 
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 320 512'%3e%3cpath fill='%23a68a6f' d='M41 288h238c21.4 0 32.1 25.9 17 41L177 448c-9.4 9.4-24.6 9.4-33.9 0L24 329c-15.1-15.1-4.4-41 17-41zm255-105L177 64c-9.4-9.4-24.6-9.4-33.9 0L24 183c-15.1 15.1-4.4 41 17 41h238c21.4 0 32.1-25.9 17-41z'/%3e%3c/svg%3e") !important; 
}
#qtlTblMap.dataTable thead .sorting_asc { 
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 320 512'%3e%3cpath fill='%236B5B47' d='M177 159.7l136 136c9.4 9.4 9.4 24.6 0 33.9l-22.6 22.6c-9.4 9.4-24.6 9.4-33.9 0L160 255.9l-96.4 96.4c-9.4 9.4-24.6 9.4-33.9 0L7 329.7c-9.4-9.4-9.4-24.6 0-33.9l136-136c9.4-9.5 24.6-9.5 34-.1z' transform='rotate(180 160 256)'/%3e%3c/svg%3e") !important;
}
#qtlTblMap.dataTable thead .sorting_desc { 
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 320 512'%3e%3cpath fill='%236B5B47' d='M177 159.7l136 136c9.4 9.4 9.4 24.6 0 33.9l-22.6 22.6c-9.4 9.4-24.6 9.4-33.9 0L160 255.9l-96.4 96.4c-9.4 9.4-24.6 9.4-33.9 0L7 329.7c-9.4-9.4-9.4-24.6 0-33.9l136-136c9.4-9.5 24.6-9.5 34-.1z'/%3e%3c/svg%3e") !important;
}

        .dt-buttons {
            margin-bottom: 10px;
            float: left; /* MODIFIED */
        }


        .dt-button {
            background: linear-gradient(135deg, var(--primary-brown) 0%, var(--dark-brown) 100%) !important;
            color: white !important;
            border: none !important;
            padding: 8px 16px !important;
            border-radius: 6px !important;
            font-weight: bold !important;
            transition: all 0.2s;
        }
        .dt-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }


        #loadingmsg {
            color: var(--text-dark);
            background: white;
            padding: 30px;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 1001;
            border-radius: 12px;
            border: 3px solid var(--primary-brown);
            box-shadow: 0 8px 15px rgba(0,0,0,0.3);
        }


        #loadingover {
            background: rgba(0,0,0,0.6);
            z-index: 1000;
            width: 100%;
            height: 100%;
            position: fixed;
            top: 0;
            left: 0;
        }

        .selection-summary {
            background: #f3efe6; 
            color: #4a3f35; 
            border: 1px solid #d6cfc2; 
            border-radius: 6px;
            padding: 10px 12px;
            margin: 8px 0 14px 0;
            font-size: 14px;
        }
        .selection-summary strong {
            color: #6b4f3b; 
        }
    </style>
</head>
<body>


<div class="main-container">
    <h1 class="page-title">Disease Analyzer Results</h1>
    
    <form action="disease-qtl-results.php" method="post" name="myForm" id="myForm" target="_new">


        <div class="content-wrapper">
            <!-- Filters Panel -->
            <div class="filters-panel">
                <h3>Data Filters</h3>
                
                <div class="filter-group">
                    <label class="group-title">Select Cases:</label>
                    <div class="checkbox-group" id="caseFilters">
                        <!-- Cases will be populated by JavaScript -->
                    </div>
                </div>


                <div class="filter-group" id="reviewFilter" style="display: none;">
                    <label class="group-title">Review Status:</label>
                    <div class="checkbox-group">
                        <label><input type="checkbox" name="revFil[]" value="Reviewed"> Reviewed</label>
                        <label><input type="checkbox" name="revFil[]" value="Non-reviewed">Non-reviewed</label>
                    </div>
                </div>


                <div class="filter-group" id="codingFilter" style="display: none;">
                    <label class="group-title">SNP Type:</label>
                    <div class="checkbox-group">
                        <label><input type="checkbox" name="codFil[]" value="Coding"> Coding</label>
                        <label><input type="checkbox" name="codFil[]" value="Non-coding">Non-coding</label>
                    </div>
                </div>


                <button type="button" class="reset-filters" onclick="resetAllFilters()">
                    🔄 Reset All Filters
                </button>
            </div>


            
            <!-- Table Container -->
            <div class="table-container">
                <table id="qtlTblMap">
                    <!-- MODIFIED: Header order changed to match JS and PHP data structure -->
                    <thead>
                        <tr>
                            <th>Disease/Trait/Phenotype</th>
                            <th>Study Accession</th>
                            <th>PubMed ID</th>
                            <th>Case</th>
                            <th>Control</th>
                            <th>Review</th>
                            <th>SNP ID</th>
                            <th>SNP Type</th>
                            <th>Location</th>
                            <th>Mapped Gene/s</th>
                            <th>P-value</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $data = array();
                        for($i = 0; $i < sizeof($disha); $i++) {
                            $disn = preg_replace("/'/", '`', $disha[$i]);              
                            $res4 = mysqli_query($conn, "SELECT snps, study_accession, pubmedid, context, p_value, snp_type, chr, pos, casee, control, gene, review FROM snp_table WHERE disease_merge = '$disn' AND p_value < '$p_valuee' AND gene IS NOT NULL ORDER BY study_accession");
                            
                            while ($row4 = mysqli_fetch_array($res4)) {
                                $hypStu_Acc = $row4["study_accession"];
                                $qsnps = $row4['snps'];
                                $qcntxt = $row4['snp_type'] != '' ? $row4['snp_type'] : "-";
                                $qchr_id = $row4['chr'];
                                $qchr_pos = $row4['pos'];
                                $qmad_gen = $row4['gene'];
                                $pval = sprintf("%.2e", $row4['p_value']);
                                
                                // This section now only populates the data array for JavaScript.
                                // The initial table rows will be drawn by DataTables.
                                
                                $data[] = array(
                                    htmlspecialchars($disn),
                                    $hypStu_Acc,
                                    $row4['pubmedid'],
                                    htmlspecialchars($row4['casee']),
                                    htmlspecialchars($row4['control']),
                                    htmlspecialchars($row4['review']),
                                    $qsnps,
                                    htmlspecialchars($qcntxt),
                                    "$qchr_id: $qchr_pos",
                                    htmlspecialchars($qmad_gen),
                                    $pval
                                );
                            }
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

        
<div class="selection-summary" id="selectionSummary"> 
                <span id="selectedCount">0</span> SNPs currently selected by filters. These SNPs will be used for downstream analysis. </div>


        <!-- Analysis Options -->
        <div class="analysis-section">
            <h3>Analysis Options</h3>
            
            <div class="analysis-options">
                <div class="analysis-card">
                    <input type="checkbox" name="mapped_genes" value="mappedGenes" id="c_snp_map_cb">
                    <h4>Mapped Genes</h4>
                    <p>Get genes mapped to your filtered SNPs for enrichment analysis. If the same Gene–SNP pair appears in multiple studies, only the record with the lowest P-value is retained.</p>
                </div>
                
                <div class="analysis-card">
                    <input type="checkbox" name="qtl_anal" value="qtlAnal" id="qtl">
                    <h4>QTL Analysis</h4>
                    <p>Perform Quantitative Trait Loci analysis to identify genetic variants that influence quantitative traits in your selected tissues.</p>
                </div>
            </div>


            <div class="qtl-options" id="qtlOptions" style="display: none;">
                <h4>QTL Analysis Configuration</h4>
                
                <div class="p-value-section">
                    <label><strong>P-value cutoff for QTL:</strong></label>
                    <input name="qtl_p_value1" type="number" value="5" size="1" maxlength="1"/> × 10<sup>-<input name="qtl_p_value2" type="number" value="5" size="3" maxlength="2"></sup>
                </div>


                <div class="tissue-selection">
                    <label><strong>Select Tissue(s) for QTL Analysis:</strong></label>
                    <select class="select_TissCls" name="tissue[]" id="select_TissID" multiple="multiple" style="width:100%;" data-placeholder="Select Tissue(s) for QTL Analysis">
                        <option></option>
                        <?php  
                        $tissues = array("Adipose", "Adipose-Subcutaneous", "Adipose-Visceral", "Adrenal_Gland", "Artery", "Artery-Aorta", "Artery-Coronary", "Artery-Tibial", "Bladder", "Blood", "Blood-B_cell", "Blood-B_cell_CD19+", "Blood-Erythroid", "Blood-Macrophage", "Blood-Monocyte", "Blood-Monocytes_CD14+", "Blood-Natural_killer_cell", "Blood-Neutrophils_CD16+", "Blood-T_cell", "Blood-T_cell_CD4+", "Blood-T_cell_CD4+_activated", "Blood-T_cell_CD4+_naive", "Blood-T_cell_CD8+", "Blood-T_cell_CD8+_activated", "Blood-T_cell_CD8+_naive", "Bone", "Brain", "Brain-Amygdala", "Brain-Anterior_Cingulate_Cortex", "Brain-Caudate", "Brain-Cerebellar_Hemisphere", "Brain-Cerebellum", "Brain-Cortex", "Brain-Frontal_Cortex", "Brain-Hippocampus", "Brain-Hypothalamus", "Brain-Nucleus_Accumbens", "Brain-Pons", "Brain-Prefrontal_Cortex", "Brain-Putamen", "Brain-Spinal_Cord", "Brain-Substantia_Nigra", "Brain-Temporal_Cortex", "Breast", "Cartilage", "Central_Nervous_System", "Cervix", "Dendritic_cells", "Epithelium", "Esophagus", "Eye", "Fibroblast", "Gallbladder", "Heart", "Heart-Atrial_Appendage", "Heart-Left_Ventricle", "Kidney", "Large_Intestine", "Large_Intestine-Colon", "Large_Intestine-Rectum", "Liver", "Lung", "Lymphocyte", "Minor_Salivary_Gland", "Mouth-Saliva", "Mouth-Sputum", "Muscle", "Muscle-Skeletal", "Muscle-Smooth", "Ovary", "Pancreas", "Peripheral_Nervous_System", "Placenta", "Prostate", "Skin", "Small_Intestine", "Small_Intestine-Duodenum", "Small_Intestine-Ileum", "Spleen", "Stomach", "Testis", "Thymus", "Thyroid_Gland", "Uterus", "Vagina");
                        foreach($tissues as $tissue) {
                            $tissue_display = ucfirst(str_replace("_", " ", $tissue));
                            echo "<option value='$tissue'>$tissue_display</option>";
                        } 
                        ?>
                    </select>
                    <div class="tissue-controls">
                        <button type="button" id="addAll" class="btn">Add All</button>
                        <button type="button" id="resetAll" class="btn btn-secondary">Clear All</button>
                    </div>
                </div>
            </div>
        </div>


        <div class="form-actions">
            <input type="hidden" name="dataset" value="<?php echo $dataset; ?>" />
            <input type="hidden" name="sel_rs" id="sel_rs">
            <input type="submit" value="Start Analysis" class="btn"/>
            <input type="button" onclick="location.href='disease-analyzer.php';" value="Back" class="btn btn-secondary" />
        </div>
    </form>
</div>


<!-- Loading overlay -->
<div id='loadingmsg' style='display: none;'>
    <div>🔄 Processing your analysis, please wait...</div>
</div>
<div id='loadingover' style='display: none;'></div>


<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="js/select2_min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.2.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.2.2/js/buttons.html5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>


<script>
var originalData = <?php echo json_encode($data); ?>;
var filteredData = [...originalData];
var table;

// Update selection summary
function updateSelectionSummary() {
const count = filteredData.length;
$('#selectedCount').text(count);
}


// Initialize DataTable
function initializeTable() {
    if (table) {
        table.destroy();
    }
    
    // <!-- MODIFIED: Entire DataTable initialization is updated -->
    table = $('#qtlTblMap').DataTable({
        data: filteredData,
        pageLength: 10,
        searching: true,
        autoWidth: false, // Important for custom widths to work
        
        columnDefs: [ 
            // Define column widths for better layout
            { "width": "15%", "targets": 0 }, // Disease
            { "width": "10%", "targets": 1 }, // Study Accession
            { "width": "8%", "targets": 2 },  // PubMed ID
            { "width": "15%", "targets": 3 }, // Case
            { "width": "10%", "targets": 4 }, // Control
            { "width": "8%", "targets": 5 },  // Review
            { "width": "8%", "targets": 6 },  // SNP ID
            { "width": "7%", "targets": 7 },  // SNP Type
            { "width": "9%", "targets": 8 },  // Location
            { "width": "10%", "targets": 9 }, // Mapped Gene/s
            { "width": "5%", "targets": 10 }, // P-value

            // Center-align specific columns for tidiness
            { "className": "dt-center", "targets": [2, 5, 7, 10] } 
        ],
        dom: 'Bfrtip',
        buttons: [
            {
                extend: 'csvHtml5',
                text: '📋 Download CSV',
                title: 'Disease_Analysis_Results_' + new Date().toISOString().split('T')[0],
                className: 'dt-button'
            }
        ],
        columns: [
            { title: "Disease/Trait/Phenotype" },
            { title: "Study Accession", render: function(data, type, row) {
                return '<a href="https://www.ebi.ac.uk/gwas/studies/' + data + '" target="_blank">' + data + '</a>';
            }},
            { title: "PubMed ID", render: function(data, type, row) {
                return '<a href="https://pubmed.ncbi.nlm.nih.gov/' + data + '" target="_blank">' + data + '</a>';
            }},
            { title: "Case" },
            { title: "Control" },
            { title: "Review" },
            { title: "SNP ID", render: function(data, type, row) {
                return '<a href="https://www.ncbi.nlm.nih.gov/snp/' + data + '" target="_blank">' + data + '</a>';
            }},
            { title: "SNP Type" },
            { title: "Location" },
            { title: "Mapped Gene/s" },
            { title: "P-value" }
        ],
        // Remove initial table body content to prevent duplication
        // as DataTables will populate it from the 'data' source.
        initComplete: function(settings, json) {
            $('#qtlTblMap tbody').show();
            updateSelectionSummary();
        }
    });
}


// Add this helper function somewhere in your script
// Function to decode HTML entities
function decodeHtmlEntities(str) {
    if (!str) return "";
    const textarea = document.createElement('textarea');
    textarea.innerHTML = str;
    return textarea.value;
}




// Initialize filters
function initializeFilters() {
    // Generate case checkboxes
    const uniqueCases = [...new Set(originalData.map(item => item[3]))].filter(Boolean);
    const caseFiltersContainer = document.getElementById('caseFilters');
    caseFiltersContainer.innerHTML = ''; // Clear previous filters before adding new ones


    uniqueCases.forEach(caseValue => {
        const label = document.createElement('label');
        const checkbox = document.createElement('input');
        checkbox.type = 'checkbox';
        checkbox.name = 'caseFilter[]';
        
        checkbox.value = caseValue; 
        checkbox.checked = true; // All selected by default
        
        const decodedText = decodeHtmlEntities(caseValue);
        
        label.appendChild(checkbox);
        label.appendChild(document.createTextNode(' ' + decodedText)); // Use the decoded text here
        caseFiltersContainer.appendChild(label);
    });


    // Show/hide review and coding filters based on data
    const reviewedPresent = originalData.some(item => item[5] === "Reviewed");
    const nonReviewedPresent = originalData.some(item => item[5] === "Non-reviewed");
    if (reviewedPresent && nonReviewedPresent) {
        document.getElementById('reviewFilter').style.display = 'block';
    }


    const codingPresent = originalData.some(item => item[7] === "Coding");
    const nonCodingPresent = originalData.some(item => item[7] === "Non-coding");
    if (codingPresent && nonCodingPresent) {
        document.getElementById('codingFilter').style.display = 'block';
    }
}



// Apply filters function
function applyFilters() {
    filteredData = [...originalData];
    
    // Case filters
    const selectedCases = Array.from(document.querySelectorAll('input[name="caseFilter[]"]:checked')).map(cb => cb.value);
    if (selectedCases.length > 0 && selectedCases.length < [...new Set(originalData.map(item => item[3]))].filter(Boolean).length) {
        filteredData = filteredData.filter(item => selectedCases.includes(item[3]));
    }
    
    // Review filters
    const selectedReviews = Array.from(document.querySelectorAll('input[name="revFil[]"]:checked')).map(cb => cb.value);
    if (selectedReviews.length > 0) {
        filteredData = filteredData.filter(item => selectedReviews.includes(item[5]));
    }
    
    // Coding filters
    const selectedCoding = Array.from(document.querySelectorAll('input[name="codFil[]"]:checked')).map(cb => cb.value);
    if (selectedCoding.length > 0) {
        filteredData = filteredData.filter(item => selectedCoding.includes(item[7]));
    }
    
    // Update table with filtered data
    table.clear().rows.add(filteredData).draw();

    // Update selection summary
    updateSelectionSummary();
}


// Reset all filters
function resetAllFilters() {
    // Reset case filters
    document.querySelectorAll('input[name="caseFilter[]"]').forEach(cb => cb.checked = true);
    
    // Reset review filters
    document.querySelectorAll('input[name="revFil[]"]').forEach(cb => cb.checked = false);
    
    // Reset coding filters
    document.querySelectorAll('input[name="codFil[]"]').forEach(cb => cb.checked = false);
    
    // Apply filters to reset table
    applyFilters();
}


// Form validation
function validate() {
    const mappedGenesChecked = $('#c_snp_map_cb').is(':checked');
    const qtlChecked = $('#qtl').is(':checked');
    
    if (!mappedGenesChecked && !qtlChecked) {
        alert('Please select at least one analysis type (Mapped Genes or QTL Analysis).');
        return false;
    }
    
    if (qtlChecked) {
        if ($('#select_TissID').val().length === 0) {
            alert('Please select at least one tissue for QTL Analysis.');
            return false;
        }
    }
    
    return true;
}


// Initialize everything when document is ready
$(document).ready(function() {
    // Hide the initial table body to prevent FOUC (Flash of Unstyled Content)
    $('#qtlTblMap tbody').hide();

    initializeFilters();
    initializeTable();
    updateSelectionSummary();
    
    // Add event listeners for filters
    $(document).on('change', 'input[name="caseFilter[]"], input[name="revFil[]"], input[name="codFil[]"]', applyFilters);
    
    // Show/hide QTL options based on checkbox
    $('#qtl').change(function() {
        if (this.checked) {
            $('#qtlOptions').slideDown();
        } else {
            $('#qtlOptions').slideUp();
        }
    });
    
    // Initialize Select2 for tissue selection
    $('#select_TissID').select2({
        placeholder: "Select Tissue(s) for QTL Analysis",
        allowClear: true
    });
    
    $('#addAll').click(function () {
        $("#select_TissID > option:not(:first-child)").prop("selected", true);
        $("#select_TissID").trigger("change");
    });


    $('#resetAll').click(function () {
        $("#select_TissID").val([]).trigger('change');
    });
    
    // Form submission
    $('#myForm').on('submit', function(e) {
        e.preventDefault();
        
        if (validate()) {
            // Remove any existing hidden inputs
            $('#myForm').find('input[name="stu_acc[]"]:hidden').remove();
            
            // Add filtered data as hidden inputs
            let uniqueValues = [];
            filteredData.forEach(function(rowdata) {
                // MODIFIED: Corrected indexes based on the data array
                let value = rowdata[8] + "#" + rowdata[1] + "#" + rowdata[0] + "#" + rowdata[7] + "#" + rowdata[6];
                
                if (!uniqueValues.includes(value)) {
                    uniqueValues.push(value);
                }
            });
            
            uniqueValues.forEach(function(value) {
                $(this).append(
                    $('<input>')
                        .attr('type', 'hidden')
                        .attr('name', 'stu_acc[]')
                        .val(value)
                );
            }.bind(this));
            
            // Show loading and submit
            // $('#loadingmsg, #loadingover').show();
            this.submit();
        }
    });
});
</script>


</body>
</html>


<?php  
} else {
    header('Location: disease-analyzer.php');
} 
?>

<?php include('footer.php'); ?>
