<?php

ini_set('display_errors', 'On');
error_reporting(E_ALL);

header('content-type: application/javascript; charset=utf-8');
//header('Access-Control-Allow-Origin: http://www.example.com/');
//header('Access-Control-Max-Age: 3628800');
//header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');

// declare some variables
$rtnjsonobj;
$faveData;
$date_last_sync;
$geneResultsArray = array();
$phenoResultsArray = array();
$diseaseResultsArray = array();
$type;
$label;
$update;
$title;
$link;
$pubdate;
//$diffs = array();
//$diffs2 = array();

// Create a generic JSONP object and assign it the properties of the eurl variable sent by the iphone app
//if (isset($_GET['date_last_sync'])) {
if (isset($_GET['faveData'])) {

	global $rtnjsonobj, $faveData, $date_last_sync;

	$data = $_GET['faveData'];
	$faveData_string = stripslashes($data);
	$faveData = json_decode($faveData_string, true);
	$date_last_sync = $_GET['date_last_sync'];
//	$rtnjsonobj->date_last_sync = $date_last_sync;
} // end of if

$geneData = array("MGI:96974","Gene","1","1","1","1","1","1");
$phenoData = array("MP:0012585","Phenotype","1","1","1","1");
$diseaseData = array("116790","Disease","1","1","1","1");
//$faveData = array($geneData, $phenoData, $diseaseData);
$rtnjsonobj->faveData = $faveData;
//echo $_GET['callback']. '('. json_encode($rtnjsonobj) . ')';

// read in the XML file of weekly MGD updates
$url = "http://proto.informatics.jax.org/prototypes/iphone_app/htdocs/mgiRSS.xml";
$xml = file_get_contents($url);

// create a new dom object
$dom = new domDocument;

// load the html into the object
// (the @ symbol represses parse warnings from any badly formatted html that we are reading in)
@$dom->loadHTML($xml);

// discard white space
$dom->preserveWhiteSpace = false;

// store the dom object in a new DOMXPath object to make the parsing easier
$xpath = new DOMXPath($dom);

// for each sub-array of data in the faveData array...
$length = sizeof($faveData);
for ($i=0; $i<$length; $i++) {

        // declare some variables
	global $subarray, $id, $type, $update;
	$subarray = $faveData[$i];
	$id = $subarray[0];
        $type = $subarray[1];
        $update = "";

        // go through the preferences in the sub-array ($value), depending on type...
        if ($type == "Gene") {
                if ($subarray[2] == 1) { $update = "newRef"; checkXml($id, $type, $update); }
                if ($subarray[3] == 1) { $update = "newAllele"; checkXml($id, $type, $update); }
                if ($subarray[4] == 1) { $update = "newMPterm"; checkXml($id, $type, $update); }
                if ($subarray[5] == 1) { $update = "newOMIMtermGeno"; checkXml($id, $type, $update);
                                         $update = "newOMIMtermOrtho"; checkXml($id, $type, $update); }
                if ($subarray[6] == 1) { $update = "newGOtermC"; checkXml($id, $type, $update);
                                         $update = "newGOtermF"; checkXml($id, $type, $update);
                                         $update = "newGOtermP"; checkXml($id, $type, $update); }
                if ($subarray[7] == 1) { $update = "newNomenAssn"; checkXml($id, $type, $update);
                                         $update = "newNomenRename"; checkXml($id, $type, $update);
                                         $update = "newNomenSplit"; checkXml($id, $type, $update);
                                         $update = "newNomenDelete"; checkXml($id, $type, $update); }
        } // end of if

        elseif ($type == "Phenotype") {
                if ($subarray[2] == 1) { $update = "newRef"; checkXml($id, $type, $update); }
                if ($subarray[3] == 1) { $update = "newGene"; checkXml($id, $type, $update); }
                if ($subarray[4] == 1) { $update = "newAllele"; checkXml($id, $type, $update); }
                if ($subarray[5] == 1) { $update = "newGenotype"; checkXml($id, $type, $update); }
        } // end of else if

        elseif ($type == "Disease") {
                if ($subarray[2] == 1) { $update = "newRef"; checkXml($id, $type, $update); }
                if ($subarray[3] == 1) { $update = "newGene"; checkXml($id, $type, $update); }
                if ($subarray[4] == 1) { $update = "newAllele"; checkXml($id, $type, $update); }
                if ($subarray[5] == 1) { $update = "newGenotype"; checkXml($id, $type, $update); }
        } // end of else if

} // end of foreach loop

// we've done all the queries,

// so add the data to the json object to return to the iphone app
$rtnjsonobj->gene_results = $geneResultsArray;
$rtnjsonobj->pheno_results = $phenoResultsArray;
$rtnjsonobj->disease_results = $diseaseResultsArray;

//$rtnjsonobj->diffs = $diffs;
//$rtnjsonobj->diffs2 = $diffs2;

// Wrap and write a JSON-formatted object with a function call, using the supplied value of parm 'callback' in the URL:
echo $_GET['callback']. '('. json_encode($rtnjsonobj) . ')';

// exit the program
exit;




//-----------------------------------
// Functions
//-----------------------------------

function checkXml($id, $type, $update) {

	global $xpath, $rtnjsonobj, $geneResultsArray, $phenoResultsArray, $diseaseResultsArray, $type, $label, $update, $link, $pubdate;// $diffs, $diffs2;

//      $items = $xpath->query("//item[@id=$id]/update[@update=$update]");
	$query = "//item[@id=\"" . $id . "\"]/update[@update=\"" . $update . "\"]";
        $items = $xpath->query($query);

        foreach ($items as $item) {

                // clear the results array
                $results = array();

                // get and store the info associated with this xml item
                $parentNode = $item->parentNode;
                $childNodes = $parentNode->childNodes;
                $count = 0;
                foreach($childNodes as $childNode) {
                        if ($count == 0) { $type = (trim($childNode->nodeValue)); }
                        if ($count == 1) { $label = (trim($childNode->nodeValue)); }
                        if ($count == 3) { $update = (trim($childNode->nodeValue)); }
                        if ($count == 5) { $link = (trim($childNode->nodeValue)); }
                        if ($count == 6) { $pubdate = (trim($childNode->nodeValue)); }
                        $count++;
                } // end foreach loop

                $results = array("id" => $id, "type" => $type, "label" => $label, "update" => $update, "link" => $link, "pubdate" => $pubdate);

                // add the sub-array of results to the big resultsArray if its pubdate is later than the user's last sync date
//		$diff = strtotime($pubdate) - strtotime($date_last_sync);
//		$diff = floor($diff / 86400);
//		$diff2 = strtotime($date_last_sync) - strtotime($pubdate);
//		array_push($diffs, $diff);
//		array_push($diffs2, $diff2);
//		if ($diff >= 0) {
	                if ($type == "Gene") { array_push($geneResultsArray, $results); }
	                if ($type == "Phenotype") { array_push($phenoResultsArray, $results); }
        	        if ($type == "Disease") { array_push($diseaseResultsArray, $results); }
//		} // end of if

        } // end of foreach loop

	return;
} // end of checkXml function


?>
