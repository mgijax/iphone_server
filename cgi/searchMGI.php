<?php

//header('content-type: application/javascript; charset=utf-8');
//header('Access-Control-Allow-Origin: http://www.example.com/');
//header('Access-Control-Max-Age: 3628800');
//header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');

// declare some variables
$rtnjsonobj;
$searchTerm;
$mgiID;
$results = array();
$gfResultsArray = array();
$phenoResultsArray = array();
$diseaseResultsArray = array();
$test = "[type=";

// Create a generic JSONP object and assign it the properties of the term variables sent by the iphone app
if (isset($_GET['term'])) {

	global $rtnjsonobj, $searchTerm;

	$rtnjsonobj->term = $_GET['term'];
	$searchTerm = $_GET['term'];
	$searchTerm = preg_replace('/\\s+/', '+', trim($searchTerm));
} // end of if

//$searchTerm = "cav1";

// pull the html of the Quick Search results in from the URL
$url = "http://www.informatics.jax.org/searchtool/Search.do?query=" . $searchTerm . "&submit=Quick+Search";
$html = file_get_contents($url);

// create a new dom object
$dom = new domDocument;

// load the html into the object
// (the @ symbol represses parse warnings from any badly formatted html that we are reading in)
@$dom->loadHTML($html);

// discard white space
$dom->preserveWhiteSpace = false;

// store the dom object in a new DOMXPath object to make the parsing easier
// (ex: we only want some of the <table> elements, not all of them, etc.)
$xpath = new DOMXPath($dom);

// find and store the urls to the result detail pages,
// as well as their associated element symbols
$aDOMTags = $xpath->query("//table[@class='qsBucket']/tr[@class='qsBucketRow1']/td/a | //table[@class='qsBucket']/tr[@class='qsBucketRow2']/td/a");
foreach ($aDOMTags as $aDOM) {
	global $mgiID, $results, $gfResultsArray, $phenoResultsArray, $diseaseResultsArray;
	$results = array(); // use the global array variable, but reset it

	// only proceed if the <td> tag we're inside has an empty class value
	$parent = $aDOM->parentNode;
	$tdClass = "";
	$tdClass = $parent->getAttribute('class');
	if (empty($tdClass)) {
		$href = $aDOM->getAttribute('href');
		$class = ""; // reset the class variable to be an empty string so we can see if it's empty in the if statement
		$class = $aDOM->getAttribute('class');
		if (empty($class)) {
			// make sure the URL is only what we want (no GO links, etc)
			$substr = "?page=alleleDetail&key=";
			$pos = strpos($href,$substr);
			if ($pos === false) {
				// we did not find a genome feature URL that belongs to an allele
				// let's check to see if it's a genome feature that belongs to a marker
				// $substr = "?page=markerDetail&key=";
				$substr = "http://www.informatics.jax.org/marker/MGI:";
	                        $pos = strpos($href,$substr);
        	                if ($pos === false) {
					// we did not find a genome feature URL
					// let's check to see if it's a phenotype URL
				//	$substr = ".cgi?id=MP:";
					$substr = "/vocab/mp_ontology/MP:";
					$pos = strpos($href,$substr);
					if ($pos === false) {
						// we did not find a phenotype URL
						// let's check to see if it's a disease URL
						// $substr = "?page=humanDisease&key=";
						// $substr = "/disease/key/";
						$substr = "/disease/DOID:";
						$pos = strpos($href,$substr);
						if ($pos === false) {
							// we did not find a disease URL
							// we do not need this URL
						} // end of if
						else {
							// we found a disease URL, so save it
							// also save the element symbol inside the <a> tag
							// and the element name inside the next <td> tag
							// and pull the element key out of the URL
							// and pull the "feature type" out of the preceding <span> element (should always be "Disease")
							// preg_match('/\/key\/(\d+)/', $href, $matches);
							$disease_href = $href;
							preg_match("/\/DOID:(\d+)/", $disease_href, $disease_matches);
		                                        $disease_key = $disease_matches[1];
							$disease_term = (trim($aDOM->nodeValue));
                		                        $nextSib = $parent->nextSibling;
                                		        $nextSib = $nextSib->nextSibling;
                		                        $prevSib = $aDOM->previousSibling;
                                		        $prevSib = $prevSib->previousSibling;
		                                        $disease_feature_type = (trim($prevSib->nodeValue));
							$disease_mgiID = "DOID:" . $disease_key;
							
							// get the MGI ID for this disease
						//	$pageHtml = file_get_contents($href);
			
							// create a new dom object
						//	$pageDom = new domDocument;

							// load the html into the object
							// (the @ symbol represses parse warnings from any badly formatted html that we are reading in)
						//	@$pageDom->loadHTML($pageHtml);

							// discard white space
						//	$pageDom->preserveWhiteSpace = false;

							// store the dom object in a new DOMXPath object to make the parsing easier
							// (ex: we only want some of the <table> elements, not all of them, etc.)
						//	$pageXpath = new DOMXPath($pageDom);

						//	$mgiDOMTags = $pageXpath->query("//table[@class='detailStructureTable']/tr/td[@class='detailData1']/a");
						//        foreach ($mgiDOMTags as $mgiDOM) {
						//               global $mgiID;
						//               $mgi_href = $mgiDOM->getAttribute('href');
						//               if (preg_match('/www.omim.org\/entry\/(\d+)/', $mgi_href, $matches)) { $mgiID = $matches[1]; }
						//        } // end of foreach loop

							// add the sub-array of results to the big resultsArray
                		                        $disease_results = array("mgi" => $disease_mgiID, "url" => $disease_href, "type" => "Disease", "symbol" => "", "term" => $disease_term, "supTxt" => "", "name" => "", "key" => $disease_key, "feature_type" => $disease_feature_type);
							array_push($diseaseResultsArray, $disease_results);
						} // end of else
					} // end of if
					else {
						// we found a phenotype URL, so save it
						// also save the element symbol inside the <a> tag
						// save the element name inside the next <td> tag
						// and pull the element key out of the URL
						$pheno_href = $href;
                                        //      preg_match('/\/vocab/mp_ontology/MP:(\d+)/', $pheno_href, $pheno_matches);
					//	preg_match('/vocab/mp_ontology/(.*)/', $pheno_href, $pheno_matches);
					//	preg_match('/MP:(/d+)/', $pheno_href, $pheno_matches);
						preg_match("/vocab\/mp_ontology\/MP:(\d+)/", $pheno_href, $pheno_matches);
					//	$substr = "/disease/key/";
                                                $pheno_key = $pheno_matches[1];
					//	$pheno_key = "ahoy";
					//	$pheno_substr = "/disease/key/";
						$pheno_term = (trim($aDOM->nodeValue));
        	                                $nextSib = $parent->nextSibling;
                	                        $nextSib = $nextSib->nextSibling;
              		                        $prevSib = $aDOM->previousSibling;
						$prevSib = $prevSib->previousSibling;
	                                        $pheno_feature_type = (trim($prevSib->nodeValue));
						$pheno_mgiID = "MP:" . $pheno_key;
					//	$mgiID = "ahoy";
						// add the sub-array of results to the big resultsArray
                                	        $pheno_results = array("mgi" => $pheno_mgiID, "url" => $pheno_href, "type" => "Phenotype", "symbol" => "", "term" => $pheno_term, "supTxt" => "", "name" => "", "key" => $pheno_key, "feature_type" => $pheno_feature_type);
						array_push($phenoResultsArray, $pheno_results);
					} // end of else
				} // end of if
				else {
					// we found a genome feature URL that belongs to a marker
					// make sure it is one of the following (child term of parent term 'gene'):
					// 1. protein coding gene
					// 2. non-coding RNA gene
					// 3. heritable phenotypic marker
					// 4. gene segment
					// 5. unclassified gene

					// get the parent's preceeding sibling
					$prevSib = $parent->previousSibling;
					$prevSib = $prevSib->previousSibling;
					$gene_feature_type = (trim($prevSib->nodeValue));
				
					if (($gene_feature_type == "protein coding gene")
					 || ($gene_feature_type == "non-coding RNA gene")
					 || ($gene_feature_type == "heritable phenotypic marker")
					 || ($gene_feature_type == "gene segment")
					 || ($gene_feature_type == "unclassified gene")) {

						// save the element symbol inside the <a> tag
						// save the element name inside the next <td> tag
						// and pull the element key out of the URL
						// and pull the MGI ID from the detail page
						$gene_href = $href;
                                	        preg_match("/http:\/\/www.informatics.jax.org\/marker\/MGI:(\d+)/", $gene_href, $gene_matches);
                                        	$gene_key = $gene_matches[1];
						$gene_symbol = (trim($aDOM->nodeValue));
        	                                $gene_supTxt = "";
	        	                        $nextSib = $parent->nextSibling;
        	        	                $nextSib = $nextSib->nextSibling;
                	        	        $gene_name = (trim($nextSib->nodeValue));
						$gene_mgiID = "MGI:" . $gene_key;

						// save the genomic location
                                                $nextSib = $nextSib->nextSibling;
                                                $nextSib = $nextSib->nextSibling;
						$gene_chr = (trim($nextSib->nodeValue));

                                                $nextSib = $nextSib->nextSibling;
                                                $nextSib = $nextSib->nextSibling;
						$gene_coords = (trim($nextSib->nodeValue));
						$gene_coords = preg_replace('/\s+/', '', $gene_coords);
		
		                                // add the sub-array of results to the big resultsArray
	                        	        $gene_results = array("mgi" => $gene_mgiID, "url" => $gene_href, "type" => "Gene", "symbol" => $gene_symbol, "term" => "", "supTxt" => $gene_supTxt, "name" => $gene_name, "key" => $gene_key, "feature_type" => $gene_feature_type, "chr" =>
$gene_chr, "coords" => $gene_coords);
        		                        array_push($gfResultsArray, $gene_results);
					} // end of if
				} // end of else
			} // end of if
/*			else {
				// we found a genome feature URL that belongs to an allele, so save it
				// save the element symbol inside the <a> tag
				// save the element name inside the next <td> tag
				// and pull the element key out of the URL
				// see if the symbol contains superscript
				// and pull the MGI ID from the detail page
	                        $symbolNodes = $aDOM->childNodes;
				$count = 0;
				foreach($symbolNodes as $node) {
					$count++;
					$symbol = "";
	                                $supTxt = (trim($node->nodeValue));
                                        $symbol = (trim($aDOM->nodeValue));
					$symbol = str_replace($supTxt, "", $symbol);
				} // end foreach loop
                                preg_match('/\&key=(\d+)/', $href, $matches);
                                $key = $matches[1];
				$nextSib = $parent->nextSibling;
				$nextSib = $nextSib->nextSibling;
				$name = (trim($nextSib->nodeValue));

				// add the sub-array of results to the big resultsArray
				$results = array("url" => $href, "type" => "allele", "symbol" => $symbol, "term" => "", "supTxt" => $supTxt, "name" => $name, "key" => $key, "feature_type" => $feature_type);
				array_push($gfResultsArray, $results);
			} // end of else
*/
		} // end of if
	} // end of if
} // end of foreach loop

// add the data to the json object to return to the iphone app
$rtnjsonobj->gf_results = $gfResultsArray;
$rtnjsonobj->pheno_results = $phenoResultsArray;
$rtnjsonobj->disease_results = $diseaseResultsArray;
$rtnjsonobj->genome_build = "GRCm39";
 
// Wrap and write a JSON-formatted object with a function call, using the supplied value of parm 'callback' in the URL:
echo $_GET['callback']. '('. json_encode($rtnjsonobj) . ')';   

exit;

?>

