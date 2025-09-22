<?php
session_start();
error_reporting(1);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);
if(isset($_POST['p'])) {
	//echo $_POST['p'];
	if($_POST['p']==="Pathway enrichment")
	{
    include("gs_p_en_o2_test3.php");
	
} elseif($_POST['p']=="Disease enrichment (Genes)") {
    include("gs_d_en_o2.php");
} elseif($_POST['p']=="Gene prioritization") {
	 include("gene_prioritization.php");
}
elseif($_POST['p']=="Gene prioritization SNP") {
	 include("gene_prioritization_snp.php");
	 
}
}
else{
   include("gs_en.php");
}
?>