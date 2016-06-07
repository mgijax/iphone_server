<?php

// error reporting and header information
ini_set('display_errors',1);
ini_set('display_startup_errors',1);
error_reporting(-1);

header('content-type: application/javascript; charset=utf-8');


//--------------------------------

// extension of the XMLReader class to make
// use of the readString() function to get node data
// (readInnerXML is not defined)
// taken from http://php.net/manual/en/xmlreader.read.php
class XMLReader2 extends XMLReader
{
  function readString()
  {
        $depth = 1;
        $text = "";

        while ($this->read() && $depth != 0)
        {
            if (in_array($this->nodeType, array(XMLReader::TEXT,
XMLReader::CDATA, XMLReader::WHITESPACE,
XMLReader::SIGNIFICANT_WHITESPACE)))
                $text .= $this->value;
            if ($this->nodeType == XMLReader::ELEMENT) $depth++;
            if ($this->nodeType == XMLReader::END_ELEMENT) $depth--;
        }
        return $text;
    }
}

//--------------------------------


// declare some variables
$rtnjsonobj;
$faveData;
//$date_last_sync;
$geneResultsArray = array();
$phenoResultsArray = array();
$diseaseResultsArray = array();
$retId;
$retType;
$retLabel;
$retUpdate;
$updateType;
$retLink;
$retPubdate;


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

//$xmlfile = 'compress.zlib://path/to/large.xml.gz';
$xmlfile = '/usr/local/mgi/proto/prototypes/iphone_app/htdocs/mgiRSS.xml';

$xmlreader = new XMLReader2();
$xmlreader->open($xmlfile);

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


// use the reader to go through the xmlfile, pulling out the ID and update type
while($xmlreader->read()) { // start reading

	// read the node
	if($xmlreader->nodeType == XMLReader::ELEMENT) { // only open tags

		// get the name of the tag we're reading
		$tag = $xmlreader->name; //make $tag contain the name of the tag
	echo "tag = $tag\n";
	
		// if this is an <item> element, get its ID attribute
		if ($tag == 'item') {
			if($xmlreader->hasAttributes) {
				$id = $xmlreader->value;
				echo "wow - id=$id\n";
exit;
			} // end of if
		} // end of if
		
		
		// get and store all of this item's data
		while($xmlreader->read()) { // tell the reader to keep reading
                        if ($xmlreader->nodeType == XMLReader::ELEMENT && $xmlreader->name === 'item') {
//				echo "<item>\n";
                        	$retId = $xmlreader->readString();
                        } else if ($xmlreader->nodeType == XMLReader::ELEMENT && $xmlreader->name === 'type') {
				echo "<type>\n";
                        	$retType = $xmlreader->readString();
                        } else if ($xmlreader->nodeType == XMLReader::ELEMENT && $xmlreader->name === 'label') {
				echo "<label>\n";
                            	$retLabel = $xmlreader->readString();
                        } else if ($xmlreader->nodeType == XMLReader::ELEMENT && $xmlreader->name === 'update') {
				echo "<update>\n";
                        	$retUpdate = $xmlreader->readString();

				// get the update's type (<update> attribute 'update')	
//				if($xmlreader->hasAttributes) {

				
	                                $updateType = $xmlreader->getAttribute('update');
					echo "has attributes -> updateType: $updateType\n"; 
//       	                } // end of if
                        } else if ($xmlreader->nodeType == XMLReader::ELEMENT && $xmlreader->name === 'link') {
				echo "<link>\n";
                        	$retLink = $xmlreader->readString();
                        } else if ($xmlreader->nodeType == XMLReader::ELEMENT && $xmlreader->name === 'pubdate') {
				echo "<pubdate>\n";
                        	$retPubdate = $xmlreader->readString();

				// we've read the last bit of info from this
				// xml element, so see if we need to process the update


				if(isset($index[$retId])) {
			                $returnedPrefArray = $index[$retId];

			                echo "returnedPrefArray:\n";
			                var_dump($returnedPrefArray);

			                // get the value of the update type we're interested in
			                $updateValue = $returnedPrefArray[$updateType];

			                // if the value is 1, process the update
			                if ($updateValue == 1) {

                        			// add the info to the results array
						$results = array("id" => $retId, "type" => $retType, "label" => $retLabel, "update" => $retUpdate, "link" => $retLink, "pubdate" => $retPubdate);

			                        echo "updateValue = 1\n";
                        			echo "results = $results\n";

			                        // add the results array to the appropriate larger array
			                        if ($retType == "Gene") {
							array_push($geneResultsArray, $results); }
			                        else if ($retType == "Phenotype") {
							array_push($phenoResultsArray, $results); }
			                        else if ($retType == "Disease") {
							array_push($diseaseResultsArray, $results); }
			                } // end of else if
                 
			        } // end of if       

                        } // end of else if

                } // end of while loop
		
	} // end of if

} // end of while loop



//-----------------------------------
// Functions
//-----------------------------------

function checkIndex($id) {
	
	// if this ID and update type are in the index,
	// process this update and add it to the jsonp object
	echo "index:\n";
	var_dump($index);

	if(isset($index[$id])) {
		$returnedPrefArray = $index[$id];

		echo "returnedPrefArray:\n";
		var_dump($returnedPrefArray);

		// get the value of the update type we're interested in
		$updateValue = $prefArray[$updateType];

		// if the value is 1, process the update
		if ($updateValue == 1) {
	
			// add the info to the results array
			$results = array("id" => $id, "type" => $retType, "label" => $retLabel, "update" => $retUpdate, "link" => $retLink, "pubdate" => $retPubdate);

			echo "updateValue = 1\n";
			echo "results = $results\n";

			// add the results array to the appropriate larger array
                	if ($retType == "Gene") { array_push($geneResultsArray, $results); }
      		        else if ($retType == "Phenotype") { array_push($phenoResultsArray, $results); }
              		else if ($retType == "Disease") { array_push($diseaseResultsArray, $results); }
		} // end of if
		
	} // end of if		

} // end of checkIndex function


//--------------------------


// we've done all the queries, so

// close the xml doc
$xmlreader->close();

// create the callback
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
