<?php

//header('content-type: application/javascript; charset=utf-8');
//header('Access-Control-Allow-Origin: http://www.example.com/');
//header('Access-Control-Max-Age: 3628800');
//header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');

// declare some variables
$rtnjonobj;
$searchTerm;
$results = array();
$gfResultsArray = array();
$phenoResultsArray = array();
$diseaseResultsArray = array();

// Create a generic JSONP object and assign it the properties of the term variables sent by the iphone app
if (isset($_GET['term'])) {
        
        global $rtnjsonobj, $searchTerm;
        
        $rtnjsonobj->term = $_GET['term'];
        $searchTerm = $_GET['term'];
        $searchTerm = preg_replace('/\\s+/', '+', trim($searchTerm));
} // end of if

// use this for testing
//$searchTerm = "parkinson";

//------------------------------------------

// hit the feature Bucket endpoint of the MGI QuickSearch to get the returned genome features

// store the feature bucket endpoint URL
$fburl = "http://www.informatics.jax.org/quicksearch/featureBucket?queryType=exactPhrase&query=" . $searchTerm. "&submit=Quick%0D%0ASearch&startIndex=0&results=10";

// get the json results using the URL and the searchTerm
$fbjson = file_get_contents($fburl);

// decode the feature bucket json results as an associative array
$decoded_fbjson = json_decode($fbjson, true);

// save the feature bucket results as rows
$fbsummaryRows = $decoded_fbjson['summaryRows'];

// for each row of feature bucket results...
foreach($fbsummaryRows as $fbrow) {

	// save the json data that we need
	$gene_detailUri = $fbrow['detailUri'];
        $gene_href = "http://www.informatics.jax.org".$gene_detailUri;
        $gene_mgiID = substr($gene_detailUri, 8);
	$gene_symbol = $fbrow['symbol'];
	$gene_supTxt = ""; // keep
	$gene_name = $fbrow['name'];
	$gene_key = ""; // I don't think we need this anymore
	$gene_feature_type = $fbrow['featureType'];
	$gene_chr = $fbrow['chromosome'];
	$gene_coords = $fbrow['location'];		

	// save the json data to the gene_results array
	$gene_results = array(
		"mgi" => $gene_mgiID,  # MGI ID
		"url" => $gene_href,  # link to this gene page in MGI
		"type" => "Gene",  # hard-coded
		"symbol" => $gene_symbol,  # gene symbol
		"term" => "",  # must be empty
		"supTxt" => $gene_supTxt,  # must be empty
		"name" => $gene_name,  # gene name
		"key" => $gene_key,  # I don’t think we need this anymore
		"feature_type" => $gene_feature_type,  # feature type
		"chr" => $gene_chr,  # chromosome
		"coords" => $gene_coords  # genome coordinates
	);

	// store the results in an array to be sent to the app as json
	array_push($gfResultsArray, $gene_results);
} // end of feature bucket foreach loop

//------------------------------------------

// hit the vocab Bucket endpoint of the MGI QuickSearch to get the returned vocabulary terms
$vburl = "http://www.informatics.jax.org/quicksearch/vocabBucket?queryType=exactPhrase&query=" . $searchTerm. "&submit=Quick%0D%0ASearch&startIndex=0&results=10";

// get the json results using the URL and the searchTerm
$vbjson = file_get_contents($vburl);

// decode the vocab bucket json results as an associative array
$decoded_vbjson = json_decode($vbjson, true);

// save the vocab bucket results as rows
$vbsummaryRows = $decoded_vbjson['summaryRows'];

// for each row of vocab bucket results...
foreach($vbsummaryRows as $vbrow) {

	// store the vocabName
	$vocabName = $vbrow['vocabName'];

	// if we found a phenotype...
	if ($vocabName == 'Phenotype') { 
	
		// save the json data that we need
        	$pheno_mgiID = $vbrow['primaryID'];
		$pheno_detailUri = $vbrow['detailUri'];
        	$pheno_href = "http://www.informatics.jax.org".$pheno_detailUri;		
        	$pheno_term = $vbrow['term'];
        	$pheno_key = "";
        	$pheno_feature_type = $vocabName;

		// save the json data to an array
		$pheno_results = array(
			"mgi" => $pheno_mgiID,  # MP ID
			"url" => $pheno_href,  # link to this phenotype page in MGI
			"type" => "Phenotype",  # hard-coded
			"symbol" => "",  # must be empty
			"term" => $pheno_term,  # term for this phenotype
			"supTxt" => "",  # must be empty
			"name" => "",  # must be empty
			"key" => $pheno_key,  # I don’t think we need this anymore
			"feature_type" => $pheno_feature_type  
		);
		
		// store the results in an array to be sent to the app as json
		array_push($phenoResultsArray, $pheno_results);
	} // end of phenotype if

	// else if we found a disease...
	else if ($vocabName == 'Disease') {

		$disease_mgiID = $vbrow['primaryID'];
		$disease_detailUri = $vbrow['detailUri'];	
		$disease_href = "http://www.informatics.jax.org".$disease_detailUri;
		$disease_term = $vbrow['term'];
		$disease_key = "";
		$disease_feature_type = $vocabName;

		$disease_results = array(
			"mgi" => $disease_mgiID,  # DO ID
			"url" => $disease_href,  # link to this disease page in MGI
			"type" => "Disease",  # hard-coded
			"symbol" => "",  # must be empty
			"term" => $disease_term,  # term for this disease
			"supTxt" => "",  # must be empty
			"name" => "",  # must be empty
			"key" => $disease_key,  # I don’t think we need this anymore
			"feature_type" => $disease_feature_type  # should always be “Disease”
		);
	
		// store the results in an array to be sent to the app as json
                array_push($diseaseResultsArray, $disease_results);
	} // end of disease else if

} // end of vocab bucket foreach loop

// add the data to the json object to return to the iphone app
$rtnjsonobj->gf_results = $gfResultsArray;
$rtnjsonobj->pheno_results = $phenoResultsArray;
$rtnjsonobj->disease_results = $diseaseResultsArray;
$rtnjsonobj->genome_build = "GRCm39";

// Wrap and write a JSON-formatted object with a function call, using the supplied value of parm 'callback' in the URL:
echo $_GET['callback']. '('. json_encode($rtnjsonobj) . ')';

exit;

?>
