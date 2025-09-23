<?php 
// ini_set('display_errors', '1');
// ini_set('display_startup_errors', '1');
// error_reporting(E_ALL);
//session_start();
?>
<style>
    .leftDiv{
        float: left;
        margin: 10px 0;
    }

    .rightDiv{
        float: right;
        margin: 7px 0;
    } 

    .qtlTbl{
        margin-bottom: 1px;
        border: #b99c6b;
        border-collapse: collapse;
        display: block;
        /* min-height: 200px; */
        max-height: 900px;
        overflow: auto;
        width: 1120px;
    }
	
	
table.dataTable thead .sorting:after,
table.dataTable thead .sorting:before,
table.dataTable thead .sorting_asc:after,
table.dataTable thead .sorting_asc:before,
table.dataTable thead .sorting_asc_disabled:after,
table.dataTable thead .sorting_asc_disabled:before,
table.dataTable thead .sorting_desc:after,
table.dataTable thead .sorting_desc:before,
table.dataTable thead .sorting_desc_disabled:after,
table.dataTable thead .sorting_desc_disabled:before {
  bottom: .5em;
}
.dataTables_wrapper {
        width: 60%;
		margin: auto;
    }
</style>

<?php
	include "../../connect.php";
	header("Set-Cookie: cross-site-cookie=whatever; SameSite=None; Secure");
	

	//////////////////////////// Working version Uncomment (It is for the Seeds) ////////////////////////////
	
	 $selDis = explode(";", $_POST['diseas']);
	 
	 
	 if($selDis[0] == 'on'){
	 	array_shift($selDis);
	 }
	 $dis = implode(", ", $selDis);
	 //$dis = $selDis;
	//var_dump($selDis);
		 
	foreach($_POST["gen"] as $gent)
	{
		
		$arr = explode(",", $gent);
		foreach($arr as $val){
		$gwas_genes[]=trim($val);
		}
	}
	 	
	 $gwas_genes = array_values(array_unique($gwas_genes));
	 //var_dump($gwas_genes);
	 
	 
	 
	 //$gwas_trait = explode(',', $_POST['selectedDis']);
	 /* $gwas_trait = $selDis;
	 foreach($gwas_trait as $disVal){
		$Val = trim($disVal);
		 
	 	$query = mysqli_query($conn, "SELECT * FROM gene_prioritization WHERE disease_merge_gedipnet = '$disVal'");
	 	while($row = mysqli_fetch_array($query)){
	 		$diseasename[] = $row['disease_merge_gedipnet'];
	 	}
	 }
	 $diseasename = array_unique($diseasename); */
	 //var_dump($diseasename);
	
	  //Seeds
	 foreach($selDis as $dVal){
		 $Val = trim($dVal);
		 //echo "SELECT * FROM `disease_gdp` WHERE disease_merge = '$Val'";
	 	$query = mysqli_query($conn, "SELECT * FROM `disease_gdp` WHERE disease_merge = '$Val'");
		
	 	while($row = mysqli_fetch_array($query)){
	 		$seed[] = $row['geneSymbol'];
	 	}
	 }
	 $seed = array_values(array_unique($seed));
	 //var_dump($seed);
	 //echo "seed..........<br>";
	 
	////////////////////////////// Till here ///////////////////////////////


	

	// array to string (new line separated) for input as a variable in diamond
	$seed_s = implode("\n", $seed);
	$rand_number = rand(10,10000);
	$seed_file = 'gene_prio/'.$rand_number.'_seed_in.txt'; #seed genes file
	
	$seed_file_open = fopen($seed_file, "w") or die("Unable to open file! ".$seed_file);
	fwrite($seed_file_open, $seed_s);
	fclose($seed_file_open);

	// find intersection of input and seed genes; they are all important.
	$prioritised_genes = array_intersect($gwas_genes, $seed); # print this as one part of result
	sort($prioritised_genes);

	// run gene prioritization for the remaining genes
	$toprioritise = array_diff($gwas_genes, $seed);
	$toprioritise = array_values($toprioritise);
	//var_dump($toprioritise);
	
	//echo "toprioti.....<br>";
	
	//$gen = implode(",", $toprioritise );
	// echo $gen."<br>";

	// count number of genes in the toprioritise; if its greater than 100 then use RWR followed by DIAMOnD otherwise use only DIAMOnD
	$countinp = count($toprioritise);

	// find interaction of the input genes (toprioritise) for network based methods
	
	$geneNett = array_merge($toprioritise, $seed);
	
	$geneNet = array_values(array_unique($geneNett));
	//var_dump($geneNet);
	//echo "toprioti+seed.....<br>";
	
	$nct = 0;
	foreach($geneNet as $entry){
		if($nct == 0){
			$geneSa = "Symbol='".$entry."'";
			$geneSb = "Interactant='".$entry."'";
		}else{
			$geneSa = $geneSa." OR Symbol='".$entry."'";
			$geneSb = $geneSb." OR Interactant='".$entry."'";
		}
		$nct++;
	}

//echo "SELECT distinct Symbol, Interactant FROM interactions where ($geneSa) and ($geneSb)";
	$q3 = "SELECT distinct Symbol, Interactant FROM interactions where ($geneSa) and ($geneSb)";
	$q3rr = mysqli_query($conn, $q3);
	$cnt_q3rr = mysqli_num_rows($q3rr);
	$arr = array();
	if($cnt_q3rr > 0)
	{
	$interactions=0;
	while($row3=mysqli_fetch_array($q3rr)){
		$p1=$row3["Symbol"];
		$p2=$row3["Interactant"];
		$arr[] = $p1;
		$arr[] = $p2;
		
		$ppi_comma[$interactions]=$p1.",".$p2;
		$ppi_tab[$interactions]=$p1."\t".$p2;
		$interactions++;
	}
	
	$uniqArr = array_values(array_unique($arr));
	//var_dump($uniqArr);
	
	//echo "interactant.....<br>";
	$cnt_uniq = count($uniqArr) - 1;

	$ppi_cs = implode("\n", $ppi_comma); # Comma Separated 
	#$rand_number = rand(10,10000);
	$ppi_file_comma = 'gene_prio/'.$rand_number.'_ppi_comma.txt'; #seed genes file
	$ppi_file_open = fopen($ppi_file_comma, "w") or die("Unable to open file!");
	fwrite($ppi_file_open, $ppi_cs);
	fclose($ppi_file_open);

	$ppi_ts = implode("\n", $ppi_tab); 					# Tab Separated
	$ppi_file_tab = 'gene_prio/'.$rand_number.'_ppi_tab.txt'; #seed genes file
	$ppi_file_open = fopen($ppi_file_tab, "w") or die("Unable to open file!");
	fwrite($ppi_file_open, $ppi_ts);
	fclose($ppi_file_open);

	
		$random_out = "gene_prio/".$rand_number."_random_out".".txt";
		$randomWalk = exec("python3 gene_prio/randomwalk_ik.py $ppi_file_tab $seed_file $random_out");
	 
		$diamond_out = "gene_prio/".$rand_number."_diamond_out".".txt";
		//echo "<br>python3 gene_prio/DIAMOnD_edited.py $ppi_file_comma $seed_file $diamond_out<br>";
		$dmndOut = exec("python3 gene_prio/DIAMOnD_edited.py $ppi_file_comma $seed_file $diamond_out");
	
 
    $fh = fopen($diamond_out, 'r');
    while (($line = fgetcsv($fh, 10000, "\t", '"', '')) !== false) {
        $result[] = $line;
    }
	
	
	$fh_r = fopen($random_out, 'r');
    while (($line = fgetcsv($fh_r, 10000, "\t", '"', '')) !== false) {
        if(in_array($line[0], $gwas_genes)){
        $result_r[] = $line;
		}
    }
	
	#$result = array_values(array_diff($seed, $result1));
	
	
    $rsHead = array_shift($result);
    foreach($result as $key){
        $top_prio[] = $key[1];
    }
	
	}
	else{
		
		//echo "<br>No Interactions found for the selected genes!<br>";
		$err = "No Interactions found for the selected genes!";
		}
		
		$counter = 0;
		
foreach($seed as $ss){
		if($counter == 0){
			$term = "geneSymbol = '$ss'";
			
		}else{
			$term = $term." OR geneSymbol = '$ss'";
			
		}
		$counter++;
	}
	//echo $term;
	
	$q4="SELECT distinct pathwayname FROM kegg_path WHERE $term  UNION SELECT DISTINCT pathwayname FROM reactome WHERE $term UNION SELECT DISTINCT GO_term FROM gene2go WHERE $term";
	//echo $q4."<br>";
	
	
		$q4rr=mysqli_query($conn, $q4);
		$path_s=0;
		while($row4=mysqli_fetch_array($q4rr)) {
			$path1=$row4["pathwayname"];
			$pathway_seed[$path_s]=$path1;
			$path_s++;
		}
	
	 //var_dump($pathway_seed);
	 
	// find pathways of toprioritise genes one by one; find pvalue of each gene and rank based on their pvalues
	$genP_data = array();
	$pathway_gene = array();
	foreach ($toprioritise as $pp) {
		//echo $pp."<br>";

		$q5 = "SELECT distinct pathwayname FROM kegg_path where geneSymbol = '$pp' UNION SELECT DISTINCT pathwayname from reactome where geneSymbol='$pp'union select DISTINCT GO_term from gene2go where GeneSymbol='$pp'";	
	    //echo $q5."<br>";
		$q5rr=mysqli_query($conn, $q5);
		

		if(mysqli_num_rows($q5rr) > 0) {
			while($row5=mysqli_fetch_array($q5rr)) {
				$path2 = $row5["pathwayname"];
				//echo "$path2<br>";
				array_push($pathway_gene, $path2);
			}
			
		}
	
	
	
	
		if(!empty($pathway_gene)){
			
			// find intesection of pathways 
			$pathway_intersect=array_intersect($pathway_seed, $pathway_gene);
			$countseed = count($seed);
			$totalgenes = $countinp+$countseed;
			$countseedpath = count($pathway_gene);
			$countintersectionpath = count($pathway_intersect);
			if($countintersectionpath != 0){
          // echo "python3 gene_prio/hypergeometric_python.py $totalgenes $countseed $countseedpath $countintersectionpath 2>&1 <br>";
			$pvall = shell_exec("python3 gene_prio/hypergeometric_python.py $totalgenes $countseed $countseedpath $countintersectionpath 2>&1");
			$pval =  preg_split('/\s+/', $pvall);
			//echo "$pval[1]<br>";
			
			$genP_data[] = array("gene"=>$pp, "pval"=>$pval[0], "fold"=>(float)trim($pval[1]));
			
			}
			$pathway_gene=array();
		}
	}
	
	// echo "<pre>";
	// print_r($rsHead);
	// echo "</pre>";

// var_dump($genP_data);
// echo "<br>";
// echo "<br>";
function cmp($a, $b)
{
    return $b['fold'] - $a['fold'];
}

 

uasort($genP_data, "cmp");
$genP = array_values($genP_data);


//var_dump($genP);


	// Removing all files which is been created by RandomWalk and Diamond
	unlink($seed_file);
	unlink($ppi_file_comma);
	unlink($ppi_file_tab);
	unlink($diamond_out);
	unlink($random_out);
	include_once "../../header.php";
?>

<!-- Include DataTables CSS and JS -->
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.dataTables.min.css">
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>

<style>
/* Main layout styling consistent with previous pages */
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
    display: flex;
    align-items: center;
    gap: 10px;
}

/* Summary info styling */
.summary-info {
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 6px;
    padding: 15px;
    margin-bottom: 20px;
}

.summary-info p {
    margin: 8px 0;
    font-size: 14px;
}

.known-genes {
    background: #e8f5e8;
    border: 1px solid #c3e6cb;
    border-radius: 6px;
    padding: 15px;
    margin-bottom: 20px;
    font-family: monospace;
    font-size: 14px;
    line-height: 1.6;
}

/* Table styling */
.results-table {
    width: 100% !important;
    border-collapse: collapse;
    margin-top: 15px;
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
    text-align: center;
}

.results-table tbody tr:hover {
    background-color: #f8f9fa !important;
}

/* Gene links styling */
.results-table a {
    color: #5D4E37;
    text-decoration: none;
    font-weight: 500;
}

.results-table a:hover {
    color: #4a3d2b;
    text-decoration: underline;
}

/* DataTables styling */
.dataTables_wrapper {
    margin: 20px 0;
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
    font-size: 12px !important;
}

.dt-button:hover {
    background-color: #4a3d2b !important;
}

/* Alert styling */
.alert {
    padding: 15px;
    border-radius: 6px;
    margin: 20px 0;
    text-align: center;
}

.alert-warning {
    background-color: #fff3cd;
    border: 1px solid #ffeaa7;
    color: #856404;
}

.alert-info {
    background-color: #d1ecf1;
    border: 1px solid #bee5eb;
    color: #0c5460;
}

/* Buttons */
.btn-main {
    background-color: #5D4E37;
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 5px;
    transition: all 0.2s ease;
    margin: 10px 5px;
    text-decoration: none;
    display: inline-block;
}

.btn-main:hover {
    background-color: #4a3d2b;
    transform: translateY(-1px);
    box-shadow: 0 2px 8px rgba(93, 78, 55, 0.2);
    color: white;
    text-decoration: none;
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

/* Responsive improvements */
@media (max-width: 768px) {
    .results-container {
        padding: 10px;
    }
    
    .analysis-section {
        padding: 15px;
    }
    
    .dataTables_wrapper .dataTables_filter {
        flex-direction: column;
        align-items: stretch;
    }
    
    .dt-buttons {
        order: 1;
        margin-bottom: 10px;
    }
}
</style>

<div class="results-container">
    <h2 class="text-center mb-4" style="color:#5D4E37;">
        <i class="fas fa-sort-amount-up me-2"></i>Gene Prioritization Results
    </h2>
    
    <!-- Analysis Summary -->
    <div class="analysis-section">
        <h4><i class="fas fa-info-circle"></i>Analysis Summary</h4>
        <div class="summary-info">
            <p><strong>Disease/Trait:</strong> <?php echo htmlspecialchars($dis); ?></p>
            <p><strong>Total Input Genes:</strong> <?php echo count($gwas_genes); ?></p>
            <p><strong>Known Disease Genes:</strong> <?php echo count($prioritised_genes); ?></p>
            <p><strong>Genes to Prioritize:</strong> <?php echo count($toprioritise); ?></p>
        </div>
        
        <?php if (!empty($prioritised_genes)): ?>
        <div class="known-genes">
            <strong>Known Disease Genes:</strong><br>
            <?php echo implode(', ', $prioritised_genes); ?>
        </div>
        <?php endif; ?>
    </div>

    <?php if (isset($result) && !empty($result)): ?>
    <!-- Diamond Algorithm Results -->
    <div class="analysis-section">
        <h4><i class="fas fa-gem"></i>Diamond Algorithm Results</h4>
        <table class="results-table" id="diamondTable">
            <thead>
                <tr>
                    <th>Gene</th>
                    <th>P-value</th>
                    <th>Rank</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($result as $rowk): ?>
                <tr>
                    <td>
                        <a href="https://www.ncbi.nlm.nih.gov/gene/?term=<?php echo urlencode($rowk[1]); ?>" target="_blank">
                            <?php echo htmlspecialchars($rowk[1]); ?>
                        </a>
                    </td>
                    <td><?php echo round($rowk[2], 3); ?></td>
                    <td><?php echo $rowk[0]; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <?php if (isset($result_r) && !empty($result_r)): ?>
    <!-- Random Walk Algorithm Results -->
    <div class="analysis-section">
        <h4><i class="fas fa-random"></i>Random Walk Algorithm Results</h4>
        <table class="results-table" id="randomWalkTable">
            <thead>
                <tr>
                    <th>Gene</th>
                    <th>Score</th>
                    <th>Rank</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $rankk = 1;
                foreach($result_r as $rowk): ?>
                <tr>
                    <td>
                        <a href="https://www.ncbi.nlm.nih.gov/gene/?term=<?php echo urlencode($rowk[0]); ?>" target="_blank">
                            <?php echo htmlspecialchars($rowk[0]); ?>
                        </a>
                    </td>
                    <td><?php echo sprintf("%.2e", $rowk[1]); ?></td>
                    <td><?php echo $rankk++; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <?php if (!empty($genP)): ?>
    <!-- Hypergeometric Algorithm Results -->
    <div class="analysis-section">
        <h4><i class="fas fa-chart-bar"></i>Hypergeometric Algorithm Results</h4>
        <table class="results-table" id="hyperTable">
            <thead>
                <tr>
                    <th>Gene</th>
                    <th>P-value</th>
                    <th>Rank</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $rank = 1;
                for($i = 0; $i < count($genP); $i++):
                    $genSym = $genP[$i]['gene'];
                    $qu = mysqli_query($conn, "SELECT GeneID FROM gene_information WHERE Symbol = '$genSym'");
                    $GenID = '';
                    if ($row = mysqli_fetch_array($qu)) {
                        $GenID = $row["GeneID"];
                    }
                ?>
                <tr>
                    <td>
                        <?php if ($GenID): ?>
                            <a href="https://www.ncbi.nlm.nih.gov/gene/<?php echo $GenID; ?>" target="_blank">
                                <?php echo htmlspecialchars($genSym); ?>
                            </a>
                        <?php else: ?>
                            <?php echo htmlspecialchars($genSym); ?>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($genP[$i]['pval'] == '0.0'): ?>
                            0.0 (P-value too small to be displayed)
                        <?php else: ?>
                            <?php echo sprintf("%.2e", $genP[$i]['pval']); ?>
                        <?php endif; ?>
                    </td>
                    <td><?php echo $rank++; ?></td>
                </tr>
                <?php endfor; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <?php if (isset($err)): ?>
    <div class="alert alert-warning">
        <i class="fas fa-exclamation-triangle me-2"></i><?php echo htmlspecialchars($err); ?>
    </div>
    <?php endif; ?>

    <?php if (empty($result) && empty($result_r) && empty($genP) && !isset($err)): ?>
    <div class="alert alert-info">
        <i class="fas fa-info-circle me-2"></i>No prioritization results found for the selected genes and disease.
    </div>
    <?php endif; ?>

    <!-- Navigation -->
    <div class="text-center" style="margin-top: 30px;">
        <a href="index.php" class="btn-main">
            <i class="fas fa-home me-2"></i>Back to Main Page
        </a>
        <button type="button" class="btn-secondary" onclick="history.back();">
            <i class="fas fa-arrow-left me-2"></i>Back to Previous Page
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
    // Common DataTables configuration
    const tableConfig = {
        pageLength: 25,
        dom: 'lfBrtip',
        columnDefs: [{
            targets: '_all',
            className: 'text-center'
        }],
        language: {
            search: "Search genes:",
            lengthMenu: "Show _MENU_ entries",
            info: "Showing _START_ to _END_ of _TOTAL_ genes",
            paginate: {
                first: "First",
                last: "Last",
                next: "Next",
                previous: "Previous"
            }
        },
        initComplete: function(settings, json) {
            const tableId = settings.sTableId;
            if ($(`#${tableId}_wrapper .dataTables_filter .dt-buttons`).length === 0) {
                const buttonsContainer = $(`#${tableId}_wrapper .dt-buttons`).detach();
                $(`#${tableId}_wrapper .dataTables_filter`).append(buttonsContainer);
            }
        }
    };

    // Initialize Diamond table
    <?php if (isset($result) && !empty($result)): ?>
    $('#diamondTable').DataTable({
        ...tableConfig,
        buttons: [{
            extend: 'excelHtml5',
            text: '<i class="fas fa-download"></i> Download CSV',
            title: 'Diamond_Prioritization_Results',
            className: 'dt-button',
            fieldSeparator: '\t',
            extension: '.tsv'
        }],
        order: [[2, 'asc']] // Sort by rank
    });
    <?php endif; ?>

    // Initialize Random Walk table
    <?php if (isset($result_r) && !empty($result_r)): ?>
    $('#randomWalkTable').DataTable({
        ...tableConfig,
        buttons: [{
            extend: 'excelHtml5',
            text: '<i class="fas fa-download"></i> Download CSV',
            title: 'RandomWalk_Prioritization_Results',
            className: 'dt-button',
            fieldSeparator: '\t',
            extension: '.tsv'
        }],
        order: [[2, 'asc']] // Sort by rank
    });
    <?php endif; ?>

    // Initialize Hypergeometric table
    <?php if (!empty($genP)): ?>
    $('#hyperTable').DataTable({
        ...tableConfig,
        buttons: [{
            extend: 'excelHtml5',
            text: '<i class="fas fa-download"></i> Download CSV',
            title: 'Hypergeometric_Prioritization_Results',
            className: 'dt-button',
            fieldSeparator: '\t',
            extension: '.tsv'
        }],
        order: [[1, 'asc']] // Sort by p-value
    });
    <?php endif; ?>

    // Add loading overlay for external links
    $('a[target="_blank"]').on('click', function() {
        $('#loadingOverlay').show();
        setTimeout(function() {
            $('#loadingOverlay').hide();
        }, 2000);
    });
});
</script>

<?php include_once "../../footer.php"; ?>