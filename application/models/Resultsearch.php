<?php


//this class interacts with solr to run searches
class Resultsearch{
    
    
    
    
    public $page;        //page of result request
    public $number_recs; //number of results per page
    public $offset; // page offset value
    
    public $requestURI; //request URI
    public $requestParams; // array of the request parameters and values
    
    public $original_default_context_path;  //default context path, never modified
    public $default_context_path; // default context path, gets modified
    public $slashCount; //number of slashes or depth of context
    public $context_depth; //used in query
   
   
    public $param_array; //SOLR query parameter array 
    public $query; //SOLR query
   
   
    public $docs_array; //solr records found in query
    public $numFound; //total number of records found in Solr search
   
    const MaxsRecords = 100;
    

    //sets number of records to a managable level, defaults to 10
    function set_number_records($number_recs){
	
	if(!$number_recs){
            $number_recs = 10;
        }
        else{
            if(is_integer($number_recs+0)){
                $number_recs = abs($number_recs);
            }
            else{
                $number_recs = 10;
            }
        }
	
	if($number_recs > self::MaxsRecords){
	    $number_recs = self::MaxsRecords;
	}
	
	$this->number_recs = $number_recs;
	$this->set_offset();
	return $number_recs;
    }
    
    function set_offset(){
	$page = $this->page;
	$number_recs = $this->number_recs;
	if (is_numeric($page) && $page > 0 ) {
            $this->offset = ($page - 1) * $number_recs;
        }
	else{
	    $this->offset = 0;
	}
    }
    
    
    function set_default_context($default_context_path){
	//removes trailing "/"
	$this->original_default_context_path = $default_context_path;
        if(substr($default_context_path, -1, 1) == "/"){
            $default_context_path = substr($default_context_path, 0, (strlen($default_context_path)-1));
        }
	
	$default_context_path = OpenContext_FacetQuery::clean_context_path($default_context_path);
	
	$this->slashCount =  substr_count($default_context_path, "/"); // note:  $slashCount is used later to determine whether or not to display properties
	$this->context_depth = "def_context_" . $this->slashCount;
	
	$this->default_context_path = $default_context_path;
	return $default_context_path;
    }
    
    
    
    //composes query for SOLR
    function result_search_build_query(){
	$requestParams = $this->requestParams;
	$slashCount = $this->slashCount;
	$context_depth = $this->context_depth;
	
	$extendedFacets = OpenContext_FacetQuery::unfold_deep_parameters($requestParams, $slashCount);
	
	$param_array = array();
        $param_array = OpenContext_FacetQuery::build_simple_parameters($requestParams, "spatialunit");
	$complex_output = OpenContext_FacetQuery::build_complex_parameters($requestParams, $param_array, $extendedFacets, $context_depth, false);
        $unfold_vals = $complex_output["unfold_vals"];
        $param_array = $complex_output["param_array"];
	
	if(isset($requestParams['sort'])){
	    $sort = $requestParams['sort'];
	}
	else{
	    $sort = false;
	}
	
	
	if($sort == "label"){
            $param_array['sort'] = "item_label asc";
        }
        elseif($sort == "cat"){
            $param_array['sort'] = "item_class asc, interest_score desc";
        }
        elseif($sort == "context"){
            $param_array['sort'] = "default_context_path asc, interest_score desc";
        }
        elseif($sort == "proj"){
            $param_array['sort'] = "project_name asc, interest_score desc";
        }
        else{
            $param_array['sort'] = "interest_score desc";
        }
	
	
	$default_context_path = $this->default_context_path;
	if (!$default_context_path) {
            $query = "[* TO *]";
        // otherwise, query for the default context path.
        } else {
            $query = "default_context_path:" . $default_context_path . "*";
        }    
        
	$contextArray = Opencontext_FacetQuery::defaultContextORparser("default_context_path", $this->original_default_context_path);
	$query = $contextArray["query"];
	
	$this->param_array = $param_array;
	$this->query = $query;
	
    }
    
    
    function execute_search(){
	/*
	echo "<br/>Query: ".$this->query;
	echo "<br/>".$this->number_recs;
	echo "<br/>".var_dump($this->param_array);
	*/
	
	$solr = new Apache_Solr_Service('localhost', 8983, '/solr');
	if ($solr->ping()) {
	    try {
	
		$response = $solr->search($this->query,
					$this->offset,
					$this->number_recs,
					$this->param_array);
                
		$docs_array = array();
		
		foreach (($response->response->docs) as $doc) {
		    $time_start = false;
		    $time_end = false;
		    if(!empty($doc->time_start_hr)){
			$time_start = $doc->time_start_hr;
		    }
		    if(!empty($doc->time_end_hr)){
			$time_end = $doc->time_end_hr;
		    }
		    $docs_array[] = array("uuid" => $doc->uuid, "begin"=> $time_start, "end"=>$time_end );
		}
		
		$rawResponse = Zend_Json::decode($response->getRawResponse());
		$reponse = $rawResponse['response'];
		$numFound = $reponse['numFound'];
		$this->numFound = $numFound;
		$this->docs_array =  $docs_array;
		
	    } catch (Exception $e) {
		echo "Problem:". $e->getMessage(), "\n";
	    }

	} else {
	    die("unable to connect to the solr server. exiting...");
	}
	
    }
    
    
    
    function makeAtomFeed(){
	
	// the number of results per page. Note: the actual number of results per page is set in the controller as an argument to the solr query.
	// the resulstPerPage variable helps us calculate the link and opensearch elements
	$resultsPerPage = $this->number_recs;
	$requestParams =$this->requestParams;
	$host = OpenContext_OCConfig::get_host_config();
	$base_hostname = OpenContext_OCConfig::get_host_config(false);
	$baseURI = OpenContext_OCConfig::get_host_config();
	$requestURI = $this->requestURI;
	$request_array = explode('?', $requestURI, 2);
	
	$atomFullDoc = new DOMDocument("1.0", "utf-8");
		
	$root = $atomFullDoc->createElementNS("http://www.w3.org/2005/Atom", "feed");
		
	// add newlines and indent the output - this is at least useful for debugging and making the output easier to read
	$atomFullDoc->formatOutput = true;
		
	$root->setAttribute("xmlns:georss", "http://www.georss.org/georss");
	$root->setAttribute("xmlns:gml", "http://www.opengis.net/gml");
	$root->setAttribute("xmlns:dc", "http://purl.org/dc/elements/1.1/");
	$root->setAttribute("xmlns:opensearch", "http://a9.com/-/spec/opensearch/1.1/");
	//xmlns:opensearch="http://a9.com/-/spec/opensearch/1.1/"
	//$root->setAttribute("xmlns:xhtml", "http://www.w3.org/1999/xhtml");
		
	$atomFullDoc->appendChild($root);
	
	
	// Feed Title 
	$feedTitle = $atomFullDoc->createElement("title");
	$feedTitleText = $atomFullDoc->createTextNode("Open Context Query Results");
	$feedTitle->appendChild($feedTitleText);
	$root->appendChild($feedTitle);
	
	
	// Prepare the feed's subtitle
	$offset = $this->offset;
	$numFound = $this->numFound;
	
	/* for testing
	if (isset($offset)) {
	    echo 'offset: ';
	    echo $offset;
	    echo '<br/>';  
	}
	*/
	
	
	// Display the number of items found and handle paging. 
	$first = $offset + 1;
	$last = $offset + $resultsPerPage;
	
	// make sure the last page, which will usually contain fewer than 10 items, displays the correct number of items.
	if ($numFound < $last) {
	   $subTitleText = 'items ' . $first . ' to ' . $numFound . ' out of ' . $numFound . ' items'; 
	} else {
	    $subTitleText = 'items ' . $first . ' to ' . $last . ' out of ' . $numFound . ' items';
	}
	//echo $subTitleText;
	/*
	$filterArray = OpenContext_FacetOutput::active_filter_object($requestParams, $request_array, $host);
	if(count($filterArray)>0){
	    $subTitleText .= " FILTERED BY- ";
	    $ff=0;
	    foreach($filterArray as $filterItem){
		if($ff>0){
		    $subTitleText .= ", ";    
		}
		$subTitleText .= "'".$filterItem["filter"]."': '".$filterItem["value"]."'";
		$ff++;
	    }
	}
	*/
	
	$feedSubtitle = $atomFullDoc->createElement("subtitle");
	$feedSubtitleText = $atomFullDoc->createTextNode($subTitleText);
	$feedSubtitle->appendChild($feedSubtitleText);
	$root->appendChild($feedSubtitle);
	
	
	// Feed updated element (as opposed to the entry updated element)
	$feedUpdated = $atomFullDoc->createElement("updated");
	$updatedTime = OpenContext_OCConfig::last_update();
	// Retrieve the current date and time. Format it in RFC 3339 format. Store it in a text node 
	$feedUpdatedText = $atomFullDoc->createTextNode(date("Y-m-d\TH:i:s\-07:00", strtotime($updatedTime)));
	// Append the text node the updated element
	$feedUpdated->appendChild($feedUpdatedText);
	// Append the updated node to the root element
	$root->appendChild($feedUpdated);
	
	$totalResults = $atomFullDoc->createElement('opensearch:totalResults');
	$totalResultsText = $atomFullDoc->createTextNode($numFound);
	$totalResults->appendChild($totalResultsText);
	$root->appendChild($totalResults);
	
	$startIndex = $atomFullDoc->createElement('opensearch:startIndex');
	$startIndexText = $atomFullDoc->createTextNode($first);
	$startIndex->appendChild($startIndexText);
	$root->appendChild($startIndex);
	
	$itemsPerPage = $atomFullDoc->createElement('opensearch:itemsPerPage');
	$itemsPerPageText = $atomFullDoc->createTextNode($resultsPerPage);
	$itemsPerPage->appendChild($itemsPerPageText);
	$root->appendChild($itemsPerPage);
	
	// prepare link element
	$requestURI = $this->requestURI;
	
	$linkURI = $baseURI . $requestURI;
	$linkURI = str_replace("[", "%5B", $linkURI);
	$linkURI = str_replace("]", "%5D", $linkURI);
	
	// feed (self) link element
	$feedLink = $atomFullDoc->createElement("link");
	$feedLink->setAttribute("rel", "self");
	$feedLink->setAttribute("href", $linkURI);
	$root->appendChild($feedLink);
	
	// feed license link element
	$feedLink = $atomFullDoc->createElement("link");
	$feedLink->setAttribute("rel", "license");
	$feedLink->setAttribute("type", "text/html");
	$feedLink->setAttribute("href", "http://creativecommons.org/licenses/by/3.0/");
	$root->appendChild($feedLink);
	
	// feed (facets) link element
	$facetURI = $linkURI;
	$facetURI = str_replace("/sets","/sets/facets", $linkURI);
	//$facetURI = str_replace("facets/.atom", "facets.atom", $facetURI);
	$feedLink = $atomFullDoc->createElement("link");
	$feedLink->setAttribute("rel", "http://opencontext.org/about/services#atom-facets");
	$feedLink->setAttribute("type", "application/atom+xml");
	$feedLink->setAttribute("href", $facetURI);
	$root->appendChild($feedLink);
	
	
	// feed (HTML representation) link element
	$feedHTML_URI = str_replace(".atom","", $linkURI);
	//$facetURI = str_replace("facets/.atom", "facets.atom", $facetURI);
	$feedLink = $atomFullDoc->createElement("link");
	$feedLink->setAttribute("rel", "alternate");
	$feedLink->setAttribute("type", "application/xhtml+xml");
	$feedLink->setAttribute("href", $feedHTML_URI);
	$root->appendChild($feedLink);
	
	
	
	//prepare the first link
	$feedFirstLink = $atomFullDoc->createElement("link");
	$feedFirstLink->setAttribute("rel", "first");
	// remove any existing page number
	$firstURI = preg_replace('/(\?|&)page=\d*/', '', $linkURI);
	// append a '(?|&)page=1'
	if (strpos($firstURI,'?')) {
	    $firstURI = $firstURI . '&page=1';
	} else {
	    $firstURI = $firstURI . '?page=1';
	}
	
	$feedFirstLink->setAttribute("href", $firstURI);
	$root->appendChild($feedFirstLink);
	//echo $firstURI;
	//exit;
	
	
	
	// create last link
	$feedLastLink = $atomFullDoc->createElement('link');
	$feedLastLink->setAttribute('rel', 'last');
	// remove any page number from the URI so we can replace it with the last page.
	$lastURI = preg_replace('/(\?|&)page=\d*/', '', $linkURI);
	
	// calculate the number of pages
	$lastPage = intval($numFound/$resultsPerPage);
	// if there's a remainder, add a page. For example, 13 items should result in two pages.
	if ($numFound % $resultsPerPage) {
	    $lastPage = $lastPage + 1;
	}
	
	if (strpos($lastURI,'?')) {
	    if($lastPage>0){
	      $lastURI = $lastURI . '&page=' . $lastPage;
	    }
	} else {
	   if($lastPage>0){
	      $lastURI = $lastURI . '?page=' . $lastPage;
	   }
	}
	
	$feedLastLink->setAttribute('href', $lastURI);
	
	$root->appendChild($feedLastLink);
	
	//echo $lastURI;
	
	//exit;
	
	// get the page number from the controller; we'll use this to create the 'previous' and 'next' links
	$page = $this->page;
	
	// if there is no page number in the URI, we're on page 1, so set page number accrordingly
	if (!$page) {
	    $page = 1;
	    // update the linkURI so the previous and next links will be correct
	    if (strpos($linkURI,'?')) {
		$linkURI = $linkURI . '&page=1';
	    } else {
		$linkURI = $linkURI . '?page=1';
	    }
	}
	
	// create previous link
	
	if ($page > 1) {
	    $previous = $page - 1;
	    $previousURI = preg_replace('/page=\d*/', 'page=' . $previous , $linkURI);
	    //echo $previousURI;
	    $previousLink = $atomFullDoc->createElement('link');
	    $previousLink->setAttribute('rel', 'previous');
	    $previousLink->setAttribute('href', $previousURI);
	    $root->appendChild($previousLink);    
	}
	
	// create next link
	//get page number and add 1; check to see that page + 1 is not greater than $lastPage
	if ($page < $lastPage) {
	    $next = $page + 1;
	    $nextURI = preg_replace('/page=\d*/', 'page=' . $next , $linkURI);
	    $nextLink = $atomFullDoc->createElement('link');
	    $nextLink->setAttribute('rel', 'next');
	    $nextLink->setAttribute('href', $nextURI);
	    $root->appendChild($nextLink);
	}
	
	$feedId = $atomFullDoc->createElement("id");
	$feedIdText = $atomFullDoc->createTextNode($linkURI);
	$feedId->appendChild($feedIdText);
	$root->appendChild($feedId);
	
	
	if($numFound>0){
	   $docs_array = $this->docs_array;
	   
	   $contentFragment = $atomFullDoc->createDocumentFragment();
	   
	   if ($docs_array) {
	      $idArray = array();
	      foreach ($docs_array as $docArray) {
		 $idArray[] = $docArray["uuid"];
	      }
	      
	      $itemEntries = new SubjectsEntries;
	      $idEntryArray = $itemEntries->getByIDArray($idArray);
	      
	      //echo $itemEntries->sql;
	      
	      foreach($idEntryArray as $itemUUID => $atomEntry){
		 
		 if(strlen($atomEntry)<10){
		    $spaceItem = New Subject;
		    $spaceItem->getByID($itemUUID);
		    if(strlen($spaceItem->atomEntry)<10){
		       $spaceItem->solr_getArchaeoML();
		       $fixed_ArchaeoML = $spaceItem->archaeoML_update($spaceItem->archaeoML);
		       $spaceItem->archaeoML_update($fixed_ArchaeoML);
		       $spaceItem->kml_in_Atom = true; // it doesn't validate, but it is really useful
		       $fullAtom = $spaceItem->DOM_spatialAtomCreate($spaceItem->newArchaeoML);
		       $spaceItem->update_atom_entry();
		       
		       //echo var_dump($spaceItem);
		    }
		    //echo var_dump($spaceItem);
		    $doc = str_replace('<?xml version="1.0" encoding="utf-8"?>', "", $spaceItem->atomEntry);
		    $contentFragment->appendXML($doc);  // $atom_content from short atom entry
		    unset($spaceItem);
		 }
		 else{
		    $doc = str_replace('<?xml version="1.0" encoding="utf-8"?>', "", $atomEntry);
		    $contentFragment->appendXML($doc);  // $atom_content from short atom entry
		 }
		 
		 unset($itemEntries);
		 
	       }
		   
	     
	       $root->appendChild($contentFragment);
	       
	   }
	}
	
	$resultString = $atomFullDoc->saveXML();
	
	// Note: simpleXML will add a 'default:' prefix to the XHTML content.  We don't want this, so remove it.
	$resultString = str_replace('default:', '' , $resultString);
	
	return $resultString;
    }//end function
    

    function atom_to_object($atom_string){
		
	$host = OpenContext_OCConfig::get_host_config();	

	@$atomXML = simplexml_load_string($atom_string);

	if($atomXML){

		$atomXML->registerXPathNamespace("default", "http://www.w3.org/2005/Atom");
		$atomXML->registerXPathNamespace("opensearch", "http://a9.com/-/spec/opensearch/1.1/");
		
		$resultCount = $atomXML->xpath("/default:feed/opensearch:totalResults");
		$resultCount = $resultCount[0]+0;
		
		$resultSubTitle = OpenContext_ResultAtom::get_xml_string($atomXML, "/default:feed/default:subtitle");
		$first_PageURI = OpenContext_ResultAtom::get_xml_string($atomXML, "/default:feed/default:link[@rel='first']/@href");
		$last_PageURI = OpenContext_ResultAtom::get_xml_string($atomXML, "/default:feed/default:link[@rel='last']/@href");
		$next_PageURI = OpenContext_ResultAtom::get_xml_string($atomXML, "/default:feed/default:link[@rel='next']/@href");
		$prev_PageURI = OpenContext_ResultAtom::get_xml_string($atomXML, "/default:feed/default:link[@rel='previous']/@href");
		
		/*
		$last_PageURI = htmlentities($last_PageURI);
		$next_PageURI = htmlentities($next_PageURI);
		$prev_PageURI = htmlentities($prev_PageURI);
		*/
		
		if((!$first_PageURI)||($first_PageURI == $atomURI)||((substr_count($atomURI,"page=")<1))){
			
		}//case with deactivated first link
		else{
			$first_PageURI = str_replace(".atom", "", $first_PageURI);
			$first_PageURI = str_replace("sets/?page=1&", "sets/?", $first_PageURI);
			$first_PageURI = str_replace("?page=1", "", $first_PageURI);
			//$first_PageURI = htmlentities($first_PageURI);
		}//case with active first link
		
		if((!$prev_PageURI)||($prev_PageURI == $atomURI)||((substr_count($atomURI,"page=")<1))){
			
		}//case with deactivated previous link
		else{
			$prev_PageURI = str_replace(".atom", "", $prev_PageURI);
		}//case with active previous link
		
		if((!$next_PageURI)||($next_PageURI == $atomURI)){
			
		}//case with deactivated previous link
		else{
			$next_PageURI = str_replace(".atom", "", $next_PageURI);
		}//case with active previous link

		if((!$last_PageURI)||($last_PageURI == $atomURI)){

		}//case with deactivated previous link
		else{
			$last_PageURI = str_replace(".atom", "", $last_PageURI);
		}//case with active previous link		
		
		
		$eee = 0;
		foreach ($atomXML->xpath("/default:feed/default:entry") as $all_entry) {
			
			$all_entry->registerXPathNamespace("default", "http://www.w3.org/2005/Atom");
			$entryID = $all_entry->id;
			$entryURI_all[$eee] = $entryID[0]."";
			$eee++;
		}//end loop through all entries
		
		$iii = 0;
		if($eee>0){
			$allResults = array();
			foreach ($atomXML->xpath("/default:feed/default:entry") as $AtomEntry) {
				$AtomEntry->registerXPathNamespace("default", "http://www.w3.org/2005/Atom");
				$AtomEntry->registerXPathNamespace("xhtml", "http://www.w3.org/1999/xhtml");
				$AtomEntry->registerXPathNamespace("georss", OpenContext_OCConfig::get_namespace("georss"));
				$AtomEntry->registerXPathNamespace("kml", OpenContext_OCConfig::get_namespace("kml"));
				
				$geoLat = false;
				$geoLon = false;
				foreach($AtomEntry->xpath("./georss:point") as $geoNode){
					$geoString = $geoNode."";
					
					if($geoString == "30 35"){
						$geoString = "30.3287 35.4421"; //petra rounding fix	
					}
					
					$geoArray = explode(" ", $geoString);
					$geoLat = $geoArray[0]+0;
					$geoLon = $geoArray[1]+0;
				}
				
				
				
				
				$kmlBegin = false;
				$kmlEnd = false;
				foreach($AtomEntry->xpath("./kml:TimeSpan/kml:begin") as $beginNode){
					$kmlBegin = ($beginNode."") + 0;
				}
				foreach($AtomEntry->xpath("./kml:TimeSpan/kml:end") as $endNode){
					$kmlEnd = ($endNode."") + 0;
				}
				
				$entryURI = $entryURI_all[$iii];
				if(!$entryURI){
					foreach($AtomEntry->xpath("./default:id") as $idNode){
						$entryURI = $idNode."";
					}
				}
				
				
				foreach ($AtomEntry->xpath("./default:content") as $entry) {
				
					
					$entry->registerXPathNamespace("default", "http://www.w3.org/2005/Atom");
					$entry->registerXPathNamespace("xhtml", "http://www.w3.org/1999/xhtml");
					
					foreach ($entry->xpath("./xhtml:div") AS $act_content){
						$act_content->registerXPathNamespace("xhtml", "http://www.w3.org/1999/xhtml");
						foreach($act_content->xpath(".//xhtml:div[@class='class_name']") as $act_class){
							$itemCat = $act_class."";
						}
						foreach($act_content->xpath(".//xhtml:div[@class='project_name']") as $act_proj){
							$itemProject = $act_proj."";
						}
						foreach($act_content->xpath(".//xhtml:div[@class='class_icon']/xhtml:img/@src") as $act_icon){
							$itemIcon = $act_icon."";
						}
						foreach($act_content->xpath(".//xhtml:div[@class='item_label']") as $act_label){
							$itemLabel = $act_label."";
						}
						foreach($act_content->xpath(".//xhtml:div[@class='item_label']") as $act_label){
							$itemLabel = $act_label."";
						}
						$itemThumb = false;
						if($act_content->xpath(".//xhtml:div[@class='item_thumb']//xhtml:img/@src")){
							foreach($act_content->xpath(".//xhtml:div[@class='item_thumb']//xhtml:img/@src") as $act_thumb){
								$itemThumb = $act_thumb."";
							}
						}
						if(!$itemThumb){
							$itemThumb = $itemIcon;
						}
						
					}
						
					
					
					
					$resultItem = array("uri"=>$entryURI,
							    "category"=>$itemCat,
							    "catIcon"=>$itemIcon,
							    "project"=>$itemProject,
							    "label"=>$itemLabel,
							    "thumbIcon"=>$itemThumb,
							    "geoTime" => array("geoLat" => $geoLat,
									       "geoLong" => $geoLon,
									       "timeBegin" => $kmlBegin,
									       "timeEnd" => $kmlEnd
									       ));
					
					
				
				}//end loop through entries
		
			$allResults[] = $resultItem;
			$iii++;
			}//Atom Entries
		}//end case with entries
		
	}//end case with atom content
	
	
	$resultObject = array("resultCount"=>$resultCount,
			      "firstURI"=>$first_PageURI,
			      "lastURI"=>$last_PageURI,
			      "prevURI"=>$prev_PageURI,
			      "nextURI"=>$next_PageURI,
			      "items"=>$allResults
			      );
	
	return $resultObject;
    }//end function atom_to_object



}
