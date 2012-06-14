<?php
/** Zend_Controller_Action */
//require_once 'Zend/Controller/Action.php';

class queryController extends Zend_Controller_Action {
    
    private function solrEscape($stringToEscape) {
    /**  In addition to the space character, solr requires that we escape the following characters because
    they're part of solr/lucene's query language: + - && || ! ( ) { } [ ] ^ " ~ * ? : \
    */
    
    //characters we need to escape
    $search = array('\\', ' ', ':', '\'', '&&', '||', '(', ')', '+', '-', '!', '{', '}','[', ']', '^', '~', '*', '"', '?');
   
    // escaped version of characters
    $replace = array('\\\\', '\ ', '\:', '\\\'', '\&\&', '\|\|', '\(', '\)', '\+', '\-', '\!', '\{', '\}', '\[', '\]', '\^', '\~', '\*', '\\"', '\?');

    return str_replace($search, $replace, $stringToEscape);
    }          
            
    
    
    public function context_date_range($context_label, $parameters, $default_context){
    
        //this function requests time ranges for a context facet.
        //the time ranges are useful for KML expression
        //make sure that the $context_label is solr escaped
        
        $third_param_array = array();
        $third_param_array = $parameters;
        $third_param_array["facet.field"] = array("time_start", "time_end"); 
        
        $context_label = $this->solrEscape($context_label);
        
        if (!$default_context) {
            $third_query_prefix = "";
                        
        }
        else
        {
            $third_query_prefix = $this->solrEscape($default_context);
            $third_query_prefix = $default_context;
        }
        
        $thirdQuery = "default_context_path:".$third_query_prefix.$context_label."/*";
        //$thirdQuery = "default_context_path:".$thirdqueryitem."&rows=0&facet=true&facet.limit=1&facet.field=time_start&facet.field=time_end";
        
        $solr = new Apache_Solr_Service('localhost', 8983, '/solr');
        $thirdResponse = $solr->search($thirdQuery, 0, 1, $third_param_array);
                        
        //process response to get date range for this item
        $third_rawResponse = Zend_Json::decode($thirdResponse->getRawResponse());
        $third_facet_counts = $third_rawResponse['facet_counts'];
        $third_facet_fields = $third_facet_counts['facet_fields'];
        $date_range = OpenContext_DateRange::early_late_range($third_facet_fields["time_start"], $third_facet_fields["time_end"]);
                        
        $display_range = "For: ".$context_label." ".$date_range["begin"]." to ".$date_range["end"];
        //return   $display_range;  
        return $date_range;
    
        //$kml_string = '<kml:TimeSpan xmlns:kml="http://www.opengis.net/kml/2.2"><kml:begin>'.$date_range["begin"].'</kml:begin>';
        //$kml_string = '<kml:TimeSpan><kml:begin>'.$date_range["begin"].'</kml:begin>';
        //$kml_string .= "<kml:end>".$date_range["end"]."</kml:end></kml:TimeSpan>";
    
        //$KMLXML = simplexml_load_string($kml_string);
        
        //$kml_range = $KMLXML->saveXML();
    
        //return $kml_range;
    
    
    }//end function context_date_range
    
    
    
    
      
    public function indexAction() {
    
        $query_id = $this->_request->getParam('qid');
        
        $query = "[* TO *]";
        $param_array = array();
        $param_array = OpenContext_FacetQuery::build_simple_parameters($requestParams);
        //$this->view->solrParams = $param_array;
        
        $complex_output = OpenContext_FacetQuery::build_complex_parameters($requestParams, $param_array, $extendedFacets, $context_depth, true);
        $unfold_vals = $complex_output["unfold_vals"];
        $param_array = $complex_output["param_array"];
    
        $solr = new Apache_Solr_Service('localhost', 8983, '/solr');
		
        // test the connection to the solr server
	if ($solr->ping()) {
	    try {
        
                $response = $solr->search($query, 0, 1, $param_array);
                //$this->view->queryString = $solr->queryString;
                //$this->view->sResponse = $response;
                
                $docs_array = $response->response->docs;
                                
                $rawResponse = Zend_Json::decode($response->getRawResponse());
                // for testing
                $this->view->rawResponse = $rawResponse;
                // get facet counts
                $facet_counts = $rawResponse['facet_counts'];
                
                $facet_fields = $facet_counts['facet_fields'];
                // the array of contexts
                
                $geo_lat = $facet_fields['geo_lat'];
                $labelTimeArray = array(); //this array stores time ranges for context facets
                
                // if there's more than one location in the query results
                if ((count($geo_lat) > 1)) {
                    // query for the various locations' geoorss element (by extracting them from the items' abbreviated atom entries)
                    $secondQuery = '';
                    
                    foreach ($facet_fields[$context_depth] as $key => $value) {
                        if (!$default_context_path) {
                            $contextQuery = 'default_context_path:ROOT';
                        }
                        else {
                            $contextQuery = 'default_context_path:' . $this->solrEscape($default_context_path);    
                        }
                        
                        $secondQuery .= '(' . $contextQuery . ' && item_label:' . $this->solrEscape($key) . ') || ';
                        
                        //this next function gets the date range for a given context facet
                        $labelTimeArray[$key] = $this->context_date_range($key, $param_array, $default_context_path);
                    }
                
                    // remove the trailing ' || ' from the query string
                    $secondQuery = substr($secondQuery, 0, (strlen($secondQuery) -4));
                    $this->view->secondQuery = $secondQuery; // for testing
                    $secondResponse = $solr->search($secondQuery, 0, count($geo_lat)  /* , $param_array*/);
                    //$secondRawResponse = Zend_Json::decode($secondResponse->getRawResponse());
                    $labelPointArray = array();
                    $second_docs_array = array();
                    $item_label_array = array();
                
                    foreach (($secondResponse->response->docs) as $second_doc) {
                        $atomXML = simplexml_load_string($second_doc->atom_abbreviated);
                        $georssChildren = $atomXML->children('http://www.georss.org/georss');
                        $georssPoint = $georssChildren->point;
                        // we'll use this assossiative array to add georss to the atom feed
                        $labelPointArray[$second_doc->item_label] = $georssPoint->saveXML();
                    }
                    
                    $this->view->labelPointArray = $labelPointArray;
                    $this->view->contextCount = 'there are ' . count($geo_lat) . ' locations.';
                }
                else {
                    
                    if (!$default_context_path) {
                        $contextQuery_prefix = "ROOT";
                    }
                    else
                    {
                        $contextQuery_prefix = $this->solrEscape($default_context_path);
                        $contextQuery_prefix = $default_context_path;
                    }
        
                    $contextQuery = "default_context_path:".$contextQuery_prefix."*";
                    $this->view->secondQuery = $contextQuery;
                    
                    $this->view->contextCount = 'there is just ' . count($geo_lat) . ' location.';
                    //$secondResponse = $solr->search('[* TO *]', 0, 1  /*, $param_array*/);
                    $secondResponse = $solr->search($contextQuery, 0, 1  /*, $param_array*/);
                    
                    $secondRawResponse = Zend_Json::decode($secondResponse->getRawResponse());
                    $secondRawResponse = $secondRawResponse['response'];
                    $secondRawResponse = $secondRawResponse['docs'];
                    $secondRawResponse = $secondRawResponse[0];
                    $secondRawResponse = $secondRawResponse['atom_abbreviated'];
                    $SRRXML = simplexml_load_string($secondRawResponse);
                    $children =  $SRRXML->children('http://www.georss.org/georss');
    
                    $this->view->georssPoint = $children->point->saveXML();
                    
                    //these next steps gets the date range for a given context facet
                    foreach ($facet_fields[$context_depth] as $key => $value) {
                        //this next function gets the date range for a given context facet
                        $labelTimeArray[$key] = $this->context_date_range($key, $param_array, $default_context_path);
                    }
                }
                $this->view->geo_lat = $geo_lat;
                
                //this adds time range information to the labelTimeArray
                $this->view->labelTimeArray = $labelTimeArray;
                $facet_queries = $facet_counts['facet_queries'];
                $reponse = $rawResponse['response'];                      
                
                $this->view->facet_counts = $facet_counts; // for testing
                $this->view->facet_fields = $facet_fields;
                $this->view->facet_queries = $facet_queries;

                //get numbers of documents found
                $numFound = $reponse['numFound'];
                
                $this->view->numFound = $numFound;
                
			} catch (Exception $e) {
			echo $e->getMessage(), "\n";
            }

		} else {
			die("unable to connect to the solr server. exiting...");
		}
        
        

    }//end index viewer
   



   
   /*
    
    FACET JSON
    
   */

// facets (but no results) in Atom format
    public function facetsAction() {

        $query = "[* TO *]";
    
        $solr = new Apache_Solr_Service('localhost', 8983, '/solr');
		
        // test the connection to the solr server
	if ($solr->ping()) {
	    try {
        
                $response = $solr->search($query, 0, 1, $param_array);
                //$this->view->queryString = $solr->queryString;
                //$this->view->sResponse = $response;
                
                $docs_array = $response->response->docs;
                                
                $rawResponse = Zend_Json::decode($response->getRawResponse());
                // for testing
                $this->view->rawResponse = $rawResponse;
                // get facet counts
                $facet_counts = $rawResponse['facet_counts'];
                
                $facet_fields = $facet_counts['facet_fields'];
                // the array of contexts
                
                $geo_lat = $facet_fields['geo_lat'];
                $labelTimeArray = array(); //this array stores time ranges for context facets
                
                // if there's more than one location in the query results
                if ((count($geo_lat) > 1)) {
                    // query for the various locations' geoorss element (by extracting them from the items' abbreviated atom entries)
                    $secondQuery = '';
                    
                    foreach ($facet_fields[$context_depth] as $key => $value) {
                        if (!$default_context_path) {
                            $contextQuery = 'default_context_path:ROOT';
                        }
                        else {
                            $contextQuery = 'default_context_path:' . $this->solrEscape($default_context_path);    
                        }
                        
                        $secondQuery .= '(' . $contextQuery . ' && item_label:' . $this->solrEscape($key) . ') || ';
                        
                        //this next function gets the date range for a given context facet
                        $labelTimeArray[$key] = $this->context_date_range($key, $param_array, $default_context_path);
                    }
                
                    // remove the trailing ' || ' from the query string
                    $secondQuery = substr($secondQuery, 0, (strlen($secondQuery) -4));
                    $this->view->secondQuery = $secondQuery; // for testing
                    $secondResponse = $solr->search($secondQuery, 0, count($geo_lat)  /* , $param_array*/);
                    //$secondRawResponse = Zend_Json::decode($secondResponse->getRawResponse());
                    $labelPointArray = array();
                    $second_docs_array = array();
                    $item_label_array = array();
                
                    foreach (($secondResponse->response->docs) as $second_doc) {
                        $atomXML = simplexml_load_string($second_doc->atom_abbreviated);
                        $georssChildren = $atomXML->children('http://www.georss.org/georss');
                        $georssPoint = $georssChildren->point;
                        // we'll use this assossiative array to add georss to the atom feed
                        $labelPointArray[$second_doc->item_label] = $georssPoint->saveXML();
                    }
                    
                    $this->view->labelPointArray = $labelPointArray;
                    $this->view->contextCount = 'there are ' . count($geo_lat) . ' locations.';
                }
                else {
                    
                    if (!$default_context_path) {
                        $contextQuery_prefix = "ROOT";
                    }
                    else
                    {
                        $contextQuery_prefix = $this->solrEscape($default_context_path);
                        $contextQuery_prefix = $default_context_path;
                    }
        
                    $contextQuery = "default_context_path:".$contextQuery_prefix."*";
                    $this->view->secondQuery = $contextQuery;
                    
                    $this->view->contextCount = 'there is just ' . count($geo_lat) . ' location.';
                    //$secondResponse = $solr->search('[* TO *]', 0, 1  /*, $param_array*/);
                    $secondResponse = $solr->search($contextQuery, 0, 1  /*, $param_array*/);
                    
                    $secondRawResponse = Zend_Json::decode($secondResponse->getRawResponse());
                    $secondRawResponse = $secondRawResponse['response'];
                    $secondRawResponse = $secondRawResponse['docs'];
                    $secondRawResponse = $secondRawResponse[0];
                    $secondRawResponse = $secondRawResponse['atom_abbreviated'];
                    $SRRXML = simplexml_load_string($secondRawResponse);
                    $children =  $SRRXML->children('http://www.georss.org/georss');
    
                    $this->view->georssPoint = $children->point->saveXML();
                    
                    //these next steps gets the date range for a given context facet
                    foreach ($facet_fields[$context_depth] as $key => $value) {
                        //this next function gets the date range for a given context facet
                        $labelTimeArray[$key] = $this->context_date_range($key, $param_array, $default_context_path);
                    }
                }
                $this->view->geo_lat = $geo_lat;
                
                //this adds time range information to the labelTimeArray
                $this->view->labelTimeArray = $labelTimeArray;
                $facet_queries = $facet_counts['facet_queries'];
                $reponse = $rawResponse['response'];                      
                
                $this->view->facet_counts = $facet_counts; // for testing
                $this->view->facet_fields = $facet_fields;
                $this->view->facet_queries = $facet_queries;

                //get numbers of documents found
                $numFound = $reponse['numFound'];
                
                $this->view->numFound = $numFound;
                
			} catch (Exception $e) {
			echo $e->getMessage(), "\n";
            }

		} else {
			die("unable to connect to the solr server. exiting...");
		}
        
    }
    
    
    
    
    
    /*
     *
     *
     *
     *
     *
     *
     *
     *
     *
     *
     *
     *
     *
     * 
     *
     *
     * Results
     *
     */  

    public function resultsAction() {
        // the offset number we send to solr when requesting results
        $offset = 0;
        $maxrecs = 100;
        
        $page = $this->_request->getParam('page');
        $number_recs = $this->_request->getParam('recs');
        
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
        
        if($number_recs < 1 ){
            $number_recs = 1;
        }
        if($number_recs > $maxrecs){
            $number_recs = $maxrecs;
        }
        
        
        if (is_numeric($page) && $page > 0 ) {
            $offset = ($page - 1) * $number_recs;
        }
        
        $this->view->page = $page;
        $this->view->offset = $offset;
        
        $requestURI = $this->_request->getRequestUri();
        $this->view->requestURI =  $requestURI;
        
        $this->view->requestParams = $this->_request->getParams();  // for testing
        $requestParams =  $this->_request->getParams();
        
         // get the default_context_path from the request uri
        $default_context_path = $this->_request->getParam("default_context_path");
         
        // for testing uri parameter schemes
        $prop = $this->_request->getParam('prop');
        $this->view->prop = $prop;
         
        $default_context_path = OpenContext_FacetQuery::clean_context_path($default_context_path);
        
        // calculate the context depth
        $slashCount =  substr_count($default_context_path, "/"); // note:  $slashCount is used later to determine whether or not to display properties
        $context_depth = "def_context_" . $slashCount;
        $this->view->context_depth = $context_depth;  // for testing
        
        // for testing purposes, make this available in the view
        $this->view->default_context_path = $default_context_path;

        //determine if "deep" facets should be made available
        $extendedFacets = OpenContext_FacetQuery::unfold_deep_parameters($requestParams, $slashCount);
        $this->view->extendedFacets = $extendedFacets;

        // start building the array of query parameters to send to solr
        $param_array = array();
        $param_array = OpenContext_FacetQuery::build_simple_parameters($requestParams);
        //$this->view->solrParams = $param_array;
        
        $complex_output = OpenContext_FacetQuery::build_complex_parameters($requestParams, $param_array, $extendedFacets, $context_depth, false);
        $unfold_vals = $complex_output["unfold_vals"];
        $param_array = $complex_output["param_array"];
        $param_array['sort'] = "interest_score desc";
             
        $this->view->unfold_vals = $unfold_vals;
        $this->view->solrParams = $param_array;  
                
              
        // if there's no context path in the uri (i.e., http://www.opencontext.org/sets/ ), then search for all items.
        if (!$default_context_path) {
            $query = "[* TO *]";
        // otherwise, query for the default context path.
        } else {
            $query = "default_context_path:" . $default_context_path . "*";
        }    
        
        // Connection to solr server	
        //$solr = new Apache_Solr_Service('localhost', 8180, '/solr');
        $solr = new Apache_Solr_Service('localhost', 8983, '/solr');

		// test the connection to the solr server
		if ($solr->ping()) {
		    try {
                                                                            // Todo: sort by interest score
			    $response = $solr->search($query, $offset, $number_recs, $param_array);
                
                $docs_array = array();
                
                $this->view->param_array = $param_array;                
                //if ($response->response->docs){
                    foreach (($response->response->docs) as $doc) {
                        $doc->atom_abbreviated = substr($doc->atom_abbreviated, 38);
                        $docs_array[] .= $doc->atom_abbreviated;
                        $this->view->docs_array = $docs_array;
                    }
                
                //get numbers of documents found
                $rawResponse = Zend_Json::decode($response->getRawResponse());
                $reponse = $rawResponse['response'];
                $numFound = $reponse['numFound'];
                $this->view->numFound = $numFound;
                // for testing
                //  $this->view->rawResponse = $rawResponse;
	
			} catch (Exception $e) {
			echo $e->getMessage(), "\n";
            }

		} else {
			die("unable to connect to the solr server. exiting...");
		}
        
        
        
    }
    
    
    
    
    
    
    
    
    
    
    
    public function googearthAction(){
        
        $requestURI = $this->_request->getRequestUri();
        $this->view->requestURI = $requestURI;
        
        // get the default_context_path from the request uri
        $default_context_path = $this->_request->getParam("default_context_path");
        
        if ($default_context_path) {
            // escape problematic characters
            $default_context_path = $this->solrEscape($default_context_path);
            // solr expects default_context_path to end with a slash, so add it.
            $default_context_path = $default_context_path . "/";
        }
        
        
        
        $host = OpenContext_OCConfig::get_host_config();
        //$host = "http://opencontext";
        $atom_facet_uri = $host.(str_replace(".kml", ".atom", $requestURI)); // uri for the atom version of the facet feed
        $atom_string = file_get_contents($atom_facet_uri);
        
        //this is needed because the default namespace seems to screw up xpath queries if it is declared
        //by the feed root element. It's a hack, but it seems needed.
        $atom_fix = str_replace('<feed xmlns="http://www.w3.org/2005/Atom" xmlns:georss="http://www.georss.org/georss" xmlns:kml="http://www.opengis.net/kml/2.2">
','<feed xmlns:georss="http://www.georss.org/georss" xmlns:kml="http://www.opengis.net/kml/2.2">
',$atom_string);
        
        $atomXML = simplexml_load_string($atom_string);
        
        $atomXML->registerXPathNamespace("default", "http://www.w3.org/2005/Atom");
        $atomXML->registerXPathNamespace("georss", "http://www.georss.org/georss");
        $atomXML->registerXPathNamespace("kml", "http://www.opengis.net/kml/2.2");
        
        $this->view->atomXML = $atomXML;
        //$title = $atomXML->xpath("/feed/title");
        $title = $atomXML->xpath("/default:feed/default:title");
        
        $this->view->title = $title;
        
        $contextURIs = array();
        $contextNames = array();
        $contextLats = array();
        $contextLons = array();
        $contextBegin = array();
        $contextEnd = array();
        $contextFacCount = array();
        $context_sortFacCount_a = array();
        $context_sortFacCount_b = array();
        
        $contextCount = 0;
        
        //loop through feed entries
        foreach ($atomXML->xpath("/default:feed/default:entry") as $entry) {
	    
            
            $entry->registerXPathNamespace("default", "http://www.w3.org/2005/Atom");
            $entry->registerXPathNamespace("georss", "http://www.georss.org/georss");
            $entry->registerXPathNamespace("kml", "http://www.opengis.net/kml/2.2");
            $entry->registerXPathNamespace("xhtml", "http://www.w3.org/1999/xhtml");
            
            If($entry->xpath("./default:category[@term='context']")){
                
                foreach ($entry->xpath("./default:link[@rel='self']") AS $act_contextURI){
                    $act_contextURI .= "";
                    $contextURIs[$contextCount] = $act_contextURI;
                }
                
                foreach ($entry->xpath("./default:title") AS $act_title){
                    $act_title .= "";
                    $contextNames[$contextCount] = $act_title;
                }
                
                foreach ($entry->xpath("./georss:point") AS $act_point){
                    $act_point .= "";
                    $point_array = explode(" ",$act_point);
                    $contextLats[$contextCount] = $point_array[0]; //latidude
                    $contextLons[$contextCount] = $point_array[1]; //longitude
                }
                
                foreach ($entry->xpath("./kml:TimeSpan/kml:begin") AS $act_begin){
                    $act_begin .= "";
                    $contextBegin[$contextCount] = $act_begin;
                }
                
                foreach ($entry->xpath("./kml:TimeSpan/kml:end") AS $act_end){
                    $act_end .= "";
                    $contextEnd[$contextCount] = $act_end;
                }
                
                foreach ($entry->xpath(".//xhtml:span[@class='facetcount']") AS $act_facet_count){
                    $act_facet_count .= "";
                    $contextFacCount[$contextCount] = $act_facet_count;
                    $context_sortFacCount_a[$contextCount] = $act_facet_count + ($contextCount / 10000); //keeps facet counts sortable, even if same values are present
                    $context_sortFacCount_b[$contextCount] = $act_facet_count + ($contextCount / 10000);
                }
            
                $contextCount++;
            }
            
	}//end loop through feed entries
        
        
        $maxDistance = OpenContext_GoogleEarth::geo_max_distance($contextLats, $contextLons);
        $maxCount = max($contextFacCount);
        
        
        $square_size_factor = .05; //this number represents the size of square polygons as a proportion of the maximum distance between points 

        if($maxDistance == 0){
		
            //determine where to put auto-generated polygons
            $square_size = (.0001);
            $doOffsets = true;
        }
        else{
	    $square_size = ($square_size_factor*$maxDistance)+($square_size_factor*$maxDistance*.5*($contextCount/$maxCount));
            $doOffsets = false;
        }
        
        $contextColor = array(); //KML color code for a context
        $contextHeight = array(); //KML height or for a context
        $contextPolygon = array(); //KML points for a polygon shape
        $contextPoint = array(); //KML point coordinates centered on generated polygons
        $contextAtom = array(); //Atom object for a given context, used to get additional information about it
        
        //this next part requires that the contexts get sorted by their facet count
        //this is needed because position of contexts (if they happen to have the same geo coding) will be deteremined in ranked order of facet counts
        sort($context_sortFacCount_a);
        
        $i=0;
        while($i<$contextCount){
            
            $rank_fac_count = $context_sortFacCount_b[$i]; //the facet count, with decimals added to disambiguate in case some contexts have the same facet counts
            $act_rank = array_search($rank_fac_count, $context_sortFacCount_a); //the rank from the key for the sorted array of context facet counts 
             
            $act_fac_count = $contextFacCount[$i]; //round the facet count, to get rid of decimal disambiguation
            
            //sets the KML color for a context
            $contextColor[$i] = OpenContext_GoogleEarth::kml_set_color($act_fac_count, $maxCount);
            
            //sets the KML height for a given context
            $act_height = OpenContext_GoogleEarth::kml_set_height($act_fac_count, $maxCount, $maxDistance);
            $contextHeight[$i] = $act_height;
            
            if($doOffsets){
                $act_lat = $contextLats[0]; //all are the same, no need to look up
                $act_lon = $contextLons[0]; //all are the same, no need to look up
                $act_genout = OpenContext_GoogleEarth::kml_gen_polygon_points($square_size, $act_rank, $contextCount, $act_lat, $act_lon, $act_height);
                $contextPolygon[$i] = $act_genout["poly"];
                $contextPoint[$i] = $act_genout["point"]; 
            }//end case of making a longitude offset for autogenerating polygons
            else{
                $act_lon_offset = 0;
                $act_lat = $contextLats[$i]; //contexts at different locations, need to look up
                $act_lon = $contextLons[$i]; //contexts at different locations, need to look up
                $act_genout = OpenContext_GoogleEarth::kml_gen_polypoints($square_size, $act_lon_offset, $act_lat, $act_lon, $act_height);
                $contextPolygon[$i] = $act_genout["poly"];
                $contextPoint[$i] = $act_genout["point"];
            }

            //$contextAtom[$i] = OpenContext_GoogleEarth::get_context_description($default_context_path, $contextNames[$i]);
           $i++; 
        }//end loop through contexts
        
        
        
        $this->view->maxDistance = $maxDistance;
        $this->view->maxCount = $maxCount;
        
        $this->view->contextURIs = $contextURIs;    
	$this->view->contextNames = $contextNames;
        $this->view->contextLats = $contextLats;
        $this->view->contextLons = $contextLons;
        $this->view->contextBegin = $contextBegin;
        $this->view->contextEnd = $contextEnd;
        $this->view->contextFacCount = $contextFacCount;
        $this->view->contextCount = $contextCount;

        $this->view->contextColor = $contextColor;
        $this->view->contextHeight = $contextHeight;
        $this->view->contextPolygon = $contextPolygon;
        $this->view->contextPoint = $contextPoint;        
        $this->view->contextAtom = $contextAtom;
        
        $this->view->requestURI = $this->_request->getRequestUri(); // for testing
        $this->view->requestParams = $this->_request->getParams();  // for testing
        $this->view->DefaultContextPath = $default_context_path;
                
    }//end Googleearth Action function
    
    
    
    
    
     /*
     *
     *
     *
     * JSON Table Results
     *
     */  

    public function jsonAction() {
        // the offset number we send to solr when requesting results
        $offset = 0;
        $maxrecs = 100;
        
        $page = $this->_request->getParam('page');
        $number_recs = $this->_request->getParam('recs');
        
        
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
        
        if($number_recs < 1 ){
            $number_recs = 1;
        }
        if($number_recs > $maxrecs){
            $number_recs = $maxrecs;
        }
        
        if (is_numeric($page) && $page > 0 ) {
            $offset = ($page - 1) * $number_recs;
        }
        
        $this->view->page = $page;
        $this->view->offset = $offset;
        
        $requestURI = $this->_request->getRequestUri();
        $this->view->requestURI =  $requestURI;
        
        $this->view->requestParams = $this->_request->getParams();  // for testing
        $requestParams =  $this->_request->getParams();
        
         // get the default_context_path from the request uri
        $default_context_path = $this->_request->getParam("default_context_path");
         
        // for testing uri parameter schemes
        $prop = $this->_request->getParam('prop');
        $this->view->prop = $prop;
         
        $default_context_path = OpenContext_FacetQuery::clean_context_path($default_context_path);
        
        // calculate the context depth
        $slashCount =  substr_count($default_context_path, "/"); // note:  $slashCount is used later to determine whether or not to display properties
        $context_depth = "def_context_" . $slashCount;
        $this->view->context_depth = $context_depth;  // for testing
        
        // for testing purposes, make this available in the view
        $this->view->default_context_path = $default_context_path;

        //determine if "deep" facets should be made available
        $extendedFacets = OpenContext_FacetQuery::unfold_deep_parameters($requestParams, $slashCount);
        $this->view->extendedFacets = $extendedFacets;

        // start building the array of query parameters to send to solr
        $param_array = array();
        $param_array = OpenContext_FacetQuery::build_simple_parameters($requestParams);
        //$this->view->solrParams = $param_array;
        
        $complex_output = OpenContext_FacetQuery::build_complex_parameters($requestParams, $param_array, $extendedFacets, $context_depth, false);
        $unfold_vals = $complex_output["unfold_vals"];
        $param_array = $complex_output["param_array"];
        $param_array['sort'] = "interest_score desc";
             
        $this->view->unfold_vals = $unfold_vals;
        $this->view->solrParams = $param_array;  
                
              
        // if there's no context path in the uri (i.e., http://www.opencontext.org/sets/ ), then search for all items.
        if (!$default_context_path) {
            $query = "[* TO *]";
        // otherwise, query for the default context path.
        } else {
            $query = "default_context_path:" . $default_context_path . "*";
        }    
        
        // Connection to solr server	
        //$solr = new Apache_Solr_Service('localhost', 8180, '/solr');
        $solr = new Apache_Solr_Service('localhost', 8983, '/solr');
        $var_array = array(); //array of variables used in this list of results
        $pers_array = array(); //array of people used in this list of results
        $record_array = array(); //array of all records
        $recCount = 0;
        
	// test the connection to the solr server
	if ($solr->ping()) {
	    try {
                // Todo: sort by interest score
		$response = $solr->search($query, $offset, $number_recs, $param_array);        
                                
                //if ($response->response->docs){
                    foreach (($response->response->docs) as $doc) {
                        $atom_string =  $doc->atom_full; // get the full atom representation
                        unset($work_ob);
                        $work_ob = array();
                        $work_ob = OpenContext_TableOutput::atom_to_array($var_array, $pers_array, $atom_string);
                        $var_array = $work_ob["var_array"];
                        $pers_array = $work_ob["pers_array"];
                        $record_array[] = $work_ob["item_array"];
                        $recCount++;
                    }//end loop
                
                //get numbers of documents found
                $rawResponse = Zend_Json::decode($response->getRawResponse());
                $reponse = $rawResponse['response'];
                $numFound = $reponse['numFound'];
                
                
                /*
                if($numFound > $recCount){
                    $page = 2; //start at second page
                    while($numFound>$recCount){
                        unset($solr);
                        unset($reponse);
                        $solr = new Apache_Solr_Service('localhost', 8983, '/solr');
                        $offset = ($page - 1) * $num_records;
                        $response = $solr->search($query, $offset, $num_records, $param_array);
                        foreach (($response->response->docs) as $doc) {
                            $atom_string =  $doc->atom_full; // get the full atom representation
                            unset($work_ob);
                            $work_ob = array();
                            $work_ob = OpenContext_TableOutput::atom_to_array($var_array, $atom_string);
                            $var_array = $work_ob["var_array"];
                            $record_array[] = $work_ob["item_array"];
                            $recCount++;
                        }//end loop
                        
                        if($recCount>=500){
                            $recCount = $numFound +1;
                        }
                        $page++;
                    }//end loop
                }//do loop through all records
                
                */
                $this->view->numFound = $numFound;
                $this->view->recArray = $record_array;
                $this->view->varArray = $var_array;
                //$this->view->persArray = $pers_array;
                
            }//end case with working solr
            catch (Exception $e) {
                echo $e->getMessage(), "\n";
            }//end case with solr error
	} else {
	    die("unable to connect to the solr server. exiting...");
	}//end case where solr doesn't work at all
        
        
        
    }//end action for tables
    
    
    
    
    
}