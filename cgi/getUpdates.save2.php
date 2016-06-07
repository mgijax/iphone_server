<?php

//ini_set('display_errors', 'On');
//error_reporting(E_ALL);

ini_set('display_errors',1);
ini_set('display_startup_errors',1);
error_reporting(-1);

header('content-type: application/javascript; charset=utf-8');
//header('Access-Control-Allow-Origin: http://www.example.com/');
//header('Access-Control-Max-Age: 3628800');
//header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');

// declare some variables
$rtnjsonobj;
$faveData;
//$date_last_sync;
$geneResultsArray = array();
$phenoResultsArray = array();
$diseaseResultsArray = array();
$type;
$label;
$update;
$title;
$link;
$pubdate;


if (isset($_GET['faveData'])) {

        // define some variables
        global $rtnjsonobj, $faveData, $index; //, $date_last_sync;

	$favesArray = array();
        $length = 0;
	$fave;
        $id = "";
        $prefArray = array();
        $index = array();

        // read in the faveData
        $data = $_GET['faveData'];
        $faveData_string = stripslashes($data);
        $faveData = json_decode($faveData_string, true); // faveData
	$favesArray = $faveData['faves'];
	$length = sizeof($favesArray);

        // build an index of fave IDs -> prefs
        for ($i=0; $i<$length; $i++) {
	
		// grab the fave
		$fave = $favesArray[$i];
		$faveDataArray = $fave[0];

		// store the id
		$id = $faveDataArray['id'];

		// store the prefArray
		$prefArray = $faveDataArray['prefs'][0];
  
                // add the prefArray to the index keyed by id
                $index[$id] = $prefArray;
        } // end of for loop

} // end of if


//--------------------------

/*
// read in the XML file containing the MGI RSS feed
$file = '/usr/local/mgi/proto/prototypes/iphone_app/htdocs/mgiRSS.xml';

// create a new dom object
$xmlDoc = new DOMDocument();
$xmlDoc->load($file);
*/

//$xmlfile = 'compress.zlib://path/to/large.xml.gz';
$xmlfile = '/usr/local/mgi/proto/prototypes/iphone_app/htdocs/mgiRSS.xml';

$reader = new XMLReader();
$reader->open($xmlfile);

//--------------------------

/*--------------------------*/
// put in for testing
// script times out when this chunk is here
// but works fine when pasted above reading in the file
/*--------------------------*/
/*
$snake = "snake";
$output = array( 'snake' => $snake );

$callback = $_REQUEST['callback'];

// start output
if ($callback) {
    header('Content-Type: text/javascript');
    echo $callback . '(' . json_encode($output) . ');';
} else {
    header('Content-Type: application/x-json');
    echo json_encode($output);
} // end of else
exit;
*/
/*--------------------------*/


// go through the xmlDoc, pulling out the ID and update type
$searchNode = $xmlDoc->getElementsByTagName("item");
foreach( $searchNode as $searchNode ) { 

	// get the ID
	$valueID = $searchNode->getAttribute('id'); 

	// get the update type
	$xmlUpdate = $searchNode->getElementsByTagName("update"); 
	foreach($xmlUpdate as $xmlUpdate1) {

		$updateType = $xmlUpdate1->getAttribute('update');
		$updateType = "pref_" . $updateType;

		// modify it if it's a nomen type
		if ($updateType == 'pref_newNomenRename') {
			$updateType = "pref_nomenChange";
		} // end of if

	} // end of foreach


	// if this ID and update type are in the index,
	// process this update and add it to the jsonp object
	if(isset($index[$valueID])) {
		$returnedPrefArray = $index[$valueID];
	
		// get the value of the update type we're interested in
		$updateValue = $prefArray[$updateType];

		// if the value is 1, process the update
		if ($updateValue == 1) {
	
			// get the xml update data
			$xmlType = $searchNode->getElementsByTagName("type"); 
			$xmlLabel = $searchNode->getElementsByTagName("label"); 
			$xmlLink = $searchNode->getElementsByTagName("link"); 
			$xmlPubdate = $searchNode->getElementsByTagName("pubdate");

			$retType = $xmlType->item(0)->nodeValue;
			$retLabel = $xmlLabel->item(0)->nodeValue;
			$retUpdate = $xmlUpdate->item(0)->nodeValue;
			$retLink = $xmlLink->item(0)->nodeValue;
			$retPubdate = $xmlPubdate->item(0)->nodeValue;	

			// add the info to the results array
			$results = array("id" => $valueID, "type" => $retType, "label" => $retLabel, "update" => $retUpdate, "link" => $retLink, "pubdate" => $retPubdate);

			// add the results array to the appropriate larger array
	                if ($retType == "Gene") { array_push($geneResultsArray, $results); }
        	        if ($retType == "Phenotype") { array_push($phenoResultsArray, $results); }
                	if ($retType == "Disease") { array_push($diseaseResultsArray, $results); }
		} // end of if
		
	} // end of if

} // end of foreach loop


//exit;


//--------------------------


// we've done all the queries, so

$callback = $_REQUEST['callback'];

// create the output object.
$output = array(
		'gene_results' => $geneResultsArray, 
		'pheno_results' => $phenoResultsArray,
		'disease_results' => $diseaseResultsArray
);

// start output
if ($callback) {
    header('Content-Type: text/javascript');
    echo $callback . '(' . json_encode($output) . ');';
} else {
    header('Content-Type: application/x-json');
    echo json_encode($output);
}

// exit the program
exit;



?>
