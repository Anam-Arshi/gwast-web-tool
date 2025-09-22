<?php 
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);
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
	include "connect.php";
	header("Set-Cookie: cross-site-cookie=whatever; SameSite=None; Secure");
	

	//////////////////////////// Working version Uncomment (It is for the Seeds) ////////////////////////////
	
	 $selDis = explode(";", $_POST['diseas']);
	 if($selDis[0] == 'on'){
	 	array_shift($selDis);
	 }
	 $dis = implode(", ", $selDis);
	var_dump($selDis);
		 
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
	 $gwas_trait = $selDis;
	 foreach($gwas_trait as $disVal){
		
		 
	 	$query = mysqli_query($conn, "SELECT * FROM gene_prioritization WHERE disease_merge_gedipnet = '$disVal'");
	 	while($row = mysqli_fetch_array($query)){
	 		$diseasename[] = $row['disease_merge_gedipnet'];
	 	}
	 }
	 $diseasename = array_unique($diseasename);
	 //var_dump($diseasename);
	
	  //Seeds
	 foreach($diseasename as $dVal){
	 	$query = mysqli_query($conn, "SELECT * FROM `disease_gdp` WHERE disease_merge = '$dVal'");
		
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
		//echo "<br>python3 gene_prio/DIAMOnD_edited.py $ppi_file_comma $seed_file $diamond_out";
		$dmndOut = exec("python3 gene_prio/DIAMOnD_edited.py $ppi_file_comma $seed_file $diamond_out");
	

    $fh = fopen($diamond_out, 'r');
    while (($line = fgetcsv($fh, 10000, "\t", "")) !== false) {
        $result[] = $line;
    }
	
	
	
	$fh_r = fopen($random_out, 'r');
    while (($line = fgetcsv($fh_r, 10000, "\t", "")) !== false) {
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
            //echo "python3 gene_prio/hypergeometric_python.py $totalgenes $countseed $countseedpath $countintersectionpath 2>&1 <br>";
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
	include_once "header.php";
?>

<style>
	.mHead {
        display: block;
        font-size: 1.5em;
        margin-block-start: 0.83em;
        margin-block-end: 0.83em;
        margin-inline-start: 0px;
        margin-inline-end: 0px;
        text-align: center;
        font-weight: bold;
    }
	
	h4{
		padding-top: 7px;
		
		
	}

    /* .dataTables_length{
        margin-left: 112px;
    }

    button.dt-button:first-child, div.dt-button:first-child, a.dt-button:first-child, input.dt-button:first-child{
        padding: 3px 10px;
        margin-left: 10px;
    }

    .dataTables_filter{
        margin-bottom: 5px;
        margin-right: 112px;
    }

    .dataTables_info{
        margin-top: 10px;
        margin-left: 112px;
    }

    .dataTables_paginate{
        margin-top: 7px;
        margin-right: 102px;
    } */
</style>
<div align="center">
        <div id='loadingmsg' style='display: none;'>
            <div align="center">Processing, please wait......</div>
        </div>
        <div id='loadingover' style='display: none;'></div>
        <p></p>
    </div>
<div class="main">
    <h2 class='mHead' align="center">Disease analyzer: Gene prioritization</h2>
	<p><b>Selected diseases:</b> &nbsp;<?php echo $dis; ?></p>
	
	
	<table class="mb-3">
		<tr>
			<td width="7%"><strong>Known genes:  </strong></td>
			<td width="80%"><?= implode(', ', $prioritised_genes); ?></td>
		</tr>
	</table>
	<?php
	if(isset($result) && count($result) >1 ){
		$cnt = count($result);
		?>
	<h4>Prioritized genes usig Diamond algorithm</h4>
            <div id="dwnld" align="right" style="float: right; margin-right: 0px;"></div>
			<table width="100%" border="1" cellspacing="0" cellpadding="4" bordercolor="#B99C6B" id="diamond" align="center">
		<thead bgcolor="#F1E8D1">
			<tr>
				<td><strong>Genes</strong></td>
				<td><strong>P-value</strong></td>
				<td><strong>Rank</strong></td>
			</tr>
		</thead>
		<tbody>
			<?php
				foreach($result as $rowk){
					?>
						<tr>
							<td><?= $rowk[1]; ?></td>
							<td><?php  //echo sprintf("%.2e", $rowk[2]);
									echo round($rowk[2], 3);
							?></td>
							<td><?= $rowk[0]; ?></td>
						</tr>
					<?php 
				}
				echo "<script> var rowcnt = '$cnt';</script>";
			?>
		</tbody>
	</table>
	<p></p>
	<script>
	$("document").ready(function () {

      var dataTableOptions = {
	
   buttons: [
    {
      extend: 'excelHtml5',
	  className: 'downBtn',
      title: 'prioritized_genes_Results_diamond',
	  text: '<i style="font-size:20px; cursor: pointer; margin-left: 4px; vertical-align: text-top;" class="fa dnbtn">&#xf019;</i>',
      titleAttr: 'Download',
	  
        
    }
  ],
   
      }
	  
	  if (rowcnt < 25) {
    // If less than 25 rows, disable pagination
    dataTableOptions.paging = false;
	//console.log(rowcnt);
        
}
	  
	  //Get a reference to the new datatable
        var table = new DataTable('#diamond', dataTableOptions);
	  
	  
		$("#diamond_filter.dataTables_filter").append($("#dwnld"));
		table.buttons().container().appendTo($('#dwnld'));
	});
</script>
    <?php
	
	}else{
		
		if(isset($err)){
			echo "<h6 style='text-align: center;'>$err</h6><p></p>";
			
		}
		
	}
	?>
	
	<?php
	
	//RandomWalk table
	
	if(isset($result_r)){
		$cnt_r = count($result_r);
		echo "<script> var rowcnt_r = '$cnt_r';</script>";
		$rankk = 1;
		?>
    <p></p>
	<h4>Prioritized genes usig RandomWalk algorithm</h4>
            <div id="dwnldR" align="right" style="float: right; margin-right: 0px;"></div>
			<table width="100%" border="1" cellspacing="0" cellpadding="4" bordercolor="#B99C6B" id="randomWalk" align="center">
		<thead bgcolor="#F1E8D1">
			<tr>
				<td><strong>Genes</strong></td>
				<td><strong>Score</strong></td>
				<td><strong>Rank</strong></td>
			</tr>
		</thead>
		<tbody>
			<?php
				foreach($result_r as $rowk){
					?>
						<tr>
							<td><?= $rowk[0]; ?></td>
							<td><?php  echo sprintf("%.2e", $rowk[1]); ?></td>
							<td><?php echo $rankk; ?></td>
						</tr>
					<?php 
					$rankk++;
				}
			?>
		</tbody>
	</table>
	<p></p>
	<script>
	$("document").ready(function () {

      var dataTableOptions = {
	
   buttons: [
    {
      extend: 'excelHtml5',
	  className: 'downBtn',
      title: 'prioritized_genes_Results_randomWalk',
	  text: '<i style="font-size:20px; cursor: pointer; margin-left: 4px; vertical-align: text-top;" class="fa dnbtn">&#xf019;</i>',
      titleAttr: 'Download',
	  
        
    }
  ],
   
      }
	  
	 if (rowcnt_r < 25) {
    // If less than 25 rows, disable pagination
    dataTableOptions.paging = false;
	//console.log(rowcnt_r);
        
}
	  
	  //Get a reference to the new datatable
        var table = new DataTable('#randomWalk', dataTableOptions);
		
		$("#randomWalk_filter.dataTables_filter").append($("#dwnldR"));
		table.buttons().container().appendTo($('#dwnldR'));
	});
</script>
    <?php
	
	}else{
		
		if(isset($err)){
			echo "<h6 style='text-align: center;'>$err</h6><p></p>";
			
		}
		
	}
	
	
	
	
	//// Hypergeometric table ////
        if(!empty($genP)){
			$cnt_h = count($genP);
			echo "<script> var rowcnt_h = '$cnt_h';</script>";
            ?>
			<p></p>
               <h4>Prioritized genes usig Hypergeometric algorithm</h4>
            <div id="dwnldH" align="right" style="float: right; margin-right: 0px;"></div>
			<table width="100%" border="1" cellspacing="0" cellpadding="4" bordercolor="#B99C6B" id="hyper" align="center">
                    <thead bgcolor="#F1E8D1">
                        <tr>
                            <td><strong>Gene</strong></td>
                            <td><strong>P-value</strong></td>
                            <td><strong>Rank</strong></td>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
						$rank = 1;
                            for($i=0; $i<count($genP); $i++){
								
									?>
										<tr>
											<td>
												<?php
												
													$genSym = $genP[$i]['gene'];
													$qu = mysqli_query($conn, "SELECT GeneID FROM gene_information WHERE Symbol = '$genSym'");
													while($rowG = mysqli_fetch_array($qu)){
														$GenID = $rowG["GeneID"];
														if($GenID != ''){
															echo "<a href=https://www.ncbi.nlm.nih.gov/gene/$GenID target=_blank>$genSym</a>";
														}else{
															echo $genSym;
														}
													}
												?>
											</td>
											<td><?php if($genP[$i]['pval'] == '0.0')
											{
												echo "0.0 (P-value too small to be displayed)";
											}
											else{
												echo sprintf("%.2e", $genP[$i]['pval']);
												
											} ?></td>
											<td><?= $rank; ?></td>
										</tr>
									<?php
									$rank++;
								}
                            
                        ?>
                    </tbody>
                </table>
                <p>&nbsp;</p>
				
				<script>
	$("document").ready(function () {

      var dataTableOptions = {
	

   buttons: [
    {
      extend: 'excelHtml5',
	  className: 'downBtn',
      title: 'prioritized_genes_Results_hypergeometric',
	  text: '<i style="font-size:20px; cursor: pointer; margin-left: 4px; vertical-align: text-top;" class="fa dnbtn">&#xf019;</i>',
      titleAttr: 'Download',
	  
        
    }
  ],
   
      }
	  
	  if (rowcnt_h < 25) {
    // If less than 25 rows, disable pagination
    dataTableOptions.paging = false;
	console.log(rowcnt_h);
        
}
	  
	  //Get a reference to the new datatable
        var table = new DataTable('#hyper', dataTableOptions);
		
		$("#hyper_filter.dataTables_filter").append($("#dwnldH"));
		table.buttons().container().appendTo($('#dwnldH'));
	});
</script>
            <?php
        } else {
            //echo "<h5 style='text-align: center;'>No enriched pathway has been found for the selected disease</h5><p></p>";
        }
    ?>
    <div align="center">
        <input type="submit" id="button2" onclick="location.href='index.php';" value="Back to main page" />
    </div>
    <p>&nbsp;</p>
</div>



<?php
	include_once "footer.php";
?>