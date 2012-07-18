<?php

require ('Apache/Solr/Service.php');






// Connection to solr server
$solr = new Apache_Solr_Service('localhost', 8180, '/solr');
$solr->setDefaultTimeout(5.0);
// test the connection to the solr server
if ($solr->ping()) {

	try {
		$response = $solr->search($uuid_query, 0, 1, array (/* you can include other parameters here */));

		foreach (($response->response->docs) as $doc) {
			//$atom_entry = simplexml_load_string($doc->atom_entry);
			//header('Content-type: application/xml; charset=UTF-8');
			//header("Content-type: application/atom+xml", true);
			//echo $atom_entry->saveXML();
			echo $doc->atom_entry;
		}

		/*if ( $response->getHttpStatus() == 200 ) { 
				print_r( $response->getRawResponse() );
		}
		*/
		// returned with status = ', $response->responseHeader->status, ' and
		echo '<br/><br/> search took ', microtime(true) - $start, ' seconds ', "\n";
		//here's how you would access results
		//Notice that I've mapped the values by name into a tree of stdClass objects
		//and arrays (actually, most of this is done by json_decode )
		/*if ($response->response->numFound > 0) {
			$doc_number = $response->response->start;
		
			foreach ($response->response->docs as $doc) {
				$doc_number++;
				echo $doc_number, ': ', $doc->text, "\n";
		
			}
		
		}
		
		//for the purposes of seeing the available structure of the response
		//NOTE: Solr_Response::_parsedData is lazy loaded, so a print_r on the response before
		//any values are accessed may result in different behavior (in case
		//anyone has some troubles debugging)
		 * 
		 */
		//print_r($response);

	} catch (Exception $e) {
		echo $e->getMessage(), "\n";

	}

} else {
	die("unable to connect to the solr server. exiting...");
}




?>