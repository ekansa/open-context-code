<?php


//this class interacts with solr to run searches
class FacetURLs{
    
   
    
    public $requestURI; //request URI
    public $requestParams; // array of the request parameters and values
    
    public $facetFields; //facets from the SolrSearch object
    public $facetQueries; //facet queries from the SolrSearch object
    public $timeSpanFacets; //treated somewhat differently
    public $geoTileFacets; //geo tile facets
    public $geoTileFacetURLs; //array of URLs to geo-tile facets
    
    public $doContextMetadata; //boolean, do context metadata
    public $default_context_path; //default context path
    public $original_default_context_path; //original, unprocessed default context path
    
    public $FacetURLs; //html representations
    
    //these are links to facets only
    public $facetURI_Atom;
    public $facetURI_JSON;
       
       
       
    //add raw solr facet results, set aside time spans for special treatment
    function setSolrFacets($solrFacets){
	
		  if(isset($solrFacets["facet_fields"])){
			  $facetFields = $solrFacets["facet_fields"];
			  if(isset($facetFields["time_span"])){
				  $this->timeSpanFacets = OpenContext_DateRange::timeSpanFacetOutput($facetFields["time_span"]);
				  $facetFields["time_span"] = null;
				  unset($facetFields["time_span"]);
			  }
			  
			  $facetFields = OpenContext_FacetOutput::downTaxaCombine($facetFields, $this->requestParams); //combine taxa fields if parents have || "or"
			  $this->facetFields = $facetFields;
		  }	
		  if(isset($solrFacets["facet_queries"])){
				$this->facetQueries = $solrFacets["facet_queries"];
		  }
	
    }//end function



    //set the requestParams, stripped of page and callback parameters 
    function setRequestParams($requestParams){
	
		  if(isset($requestParams["page"])){
			  unset($requestParams["page"]); //paging information not relevant to facets, since by default they need to return first results
		  }
		  if(isset($requestParams["callback"])){
			  unset($requestParams["callback"]); //remove JSONP parameter
		  }
		  
		  $this->requestParams = $requestParams;
		  $this->doContextMetadata = false; //default to false for this value
    }
    
    
    
    function facetLinking(){
		$host = OpenContext_OCConfig::get_host_config(); 
		$requestParams = $this->requestParams;
		
		$FacetURLs = array();
		$facet_fields = $this->facetFields;
		
		
		//combine facets from different fields if these came from an "OR" search
		
		if(is_array($facet_fields)){		
			foreach ($facet_fields as $facet_cat => $value_array) {
			
				$facet_category_label = "";
				$linkURLprefix = false;
				$linkURLsuffix = null;
						
				if (count($value_array)) { // make sure there are facets before displaying the label; TODO: verify this is the behavior we want
					
					$facetCategory = new FacetCategory;
					$facetCategory->getAltRepresentations = true; //make alternative representations for links
					$facetCategory->facet_cat = $facet_cat;
					$facetCategory->setParameter();
					$facetCategory->prepareFacetURL($requestParams);
					$facet_category_label = $facetCategory->facet_category_label;
					$checkParameter = $facetCategory->checkParameter;
					$skipUTF8 = false; //do some functions for more UTF8 processing
					
					if(stristr($facet_category_label, "description")){
						if(isset($requestParams["taxa"])){
							$lastTaxonNum = count($requestParams["taxa"])-1;
							$lastTaxon = str_replace("::", " :: ", $requestParams["taxa"][$lastTaxonNum]);
							$facet_category_label = "Fliter(s) for ".str_replace("||", " OR ", $lastTaxon);
							$facetCategory->facet_category_label = $facet_category_label;
							$skipUTF8 = true;
						}
					}
					
					if($facetCategory->facet_category_label == "Context"){
						$skipUTF8 = true;
					}
					
					foreach ($value_array as $va_key => $va_value) {
					
						$link = null;
						$Facet = new Facet;
						$Facet->skip_UTF8 = $skipUTF8;
						
						$Facet->checkParmater = $checkParameter;
						 
						$Facet->normalFacet($va_key, $va_value, $host, $facetCategory->linkURLprefix, $facetCategory->linkURLsuffix);
						$link = $Facet->link;
						$linkQuery = $Facet->linkQuery;
						$value_out = $Facet->standard_link_html;
						$value_string = $Facet->value_string;
						
						//now for json facets
						$Facet->normalFacet($va_key, $va_value, $host, $facetCategory->facJSON_linkURLprefix, $facetCategory->facJSON_linkURLsuffix);
						$linkJSON = $Facet->link;
						
						//now for json results
						$Facet->normalFacet($va_key, $va_value, $host, $facetCategory->resJSON_linkURLprefix, $facetCategory->resJSON_linkURLsuffix);
						$linkJSONresults = $Facet->link;
						
						
						$LinkingArray = array("name" => $value_string,
									"href" => $link,
									"facet_href" => $linkJSON,
									"result_href" => $linkJSONresults,
									"linkQuery" =>$linkQuery,
									"param" => $checkParameter,
									"count" => $va_value);
						
						if($this->doContextMetadata && $facetCategory->facet_category_feed == "context"){
							$date_range = $this->contextGeoTime($value_string);
							$LinkingArray["geoTime"] = $date_range;
						}
						
						if($link && strlen($value_string)>0){
							$FacetURLs[$facetCategory->facet_category_feed][] = $LinkingArray;
						}
					 }
				
				}//end case with values in this facet
			
			}//end loop through facets
		
		}//end case with facet URLs
		
		$this->FacetURLs = $FacetURLs;
		$this->timeFacets(); //get time facets
		$this->geoTileFacets(); //get geo tile facets
    }
   
   
   
    function timeFacets(){
	
		$timeSpanFacets = $this->timeSpanFacets;
		if(is_array($timeSpanFacets)){
			$host = OpenContext_OCConfig::get_host_config();
			$FacetURLs = $this->FacetURLs;
			$requestParams = $this->requestParams;
			foreach($timeSpanFacets as $timeSpan){
				$requestParams["t-start"] =  $timeSpan["t-start"];
				$requestParams["t-end"] =  $timeSpan["t-end"];
				$link = $host.OpenContext_FacetOutput::generateFacetURL($requestParams, false, false, false, false, "xhtml");
				$linkJSON_facets = $host.OpenContext_FacetOutput::generateFacetURL($requestParams, false, false, false, false, "facets_json");
				$linkJSON_results = $host.OpenContext_FacetOutput::generateFacetURL($requestParams, false, false, false, false, "results_json");
				$LinkingArray = array("name" => $timeSpan["display"] ,
								"href" => $link,
								"facet_href" => $linkJSON_facets,
								"result_href" => $linkJSON_results,
								"linkQuery" => $timeSpan["t-start"]." ".$timeSpan["t-end"],
								"param" => $timeSpan["uri_param"],
								"count" => $timeSpan["count"]);
				
				$FacetURLs["date range"][] = $LinkingArray;
			}//end loop
			$this->FacetURLs = $FacetURLs;
		}
	
    }//end function  

    //this function adds geographic and time metadata to context facets.
    //uses an additional solr query, but it is useful for summarizing search results
    //in ways that are good for atom+georss feeds, kml, and JSON
    function contextGeoTime($context_label){
	
		  $default_context_path = $this->default_context_path;
		  $requestParams = $this->requestParams;
		  
		  $param_array = array();
		  $param_array = OpenContext_FacetQuery::build_simple_parameters($requestParams, array("spatial"));
		  $param_array["facet"] = "true";
				 $param_array["facet.mincount"] = "1";
		  $param_array["facet.field"] = array("time_span", "geo_point");
		  
		  //echo $default_context;
		  if($this->original_default_context_path){
				$contextArray = Opencontext_FacetQuery::defaultContextORparser("default_context_path", $this->original_default_context_path."/".$context_label);
		  }
		  else{
				$contextArray = Opencontext_FacetQuery::defaultContextORparser("default_context_path", $context_label);
		  }
		  $query = $contextArray["query"];
		  //echo $thirdQuery;
		  
		  $solr = new Apache_Solr_Service('localhost', 8983, '/solr');
		  $response = $solr->search($query, 0, 1, $param_array);
				  
		  //process response to get date range for this item
		  $response = Zend_Json::decode($response->getRawResponse());
		  
		  //return $response;
		  $facet_counts = $response['facet_counts'];
		  $facet_fields = $facet_counts['facet_fields'];
		  $minDate = null;
		  $maxDate = null;
		  $firstLoop = true;
		  foreach($facet_fields['time_span'] as $timeKey => $count){
				$dateArray = explode(" ", $timeKey);
				if($firstLoop){
					 $minDate = $dateArray[0];
					 $maxDate = $dateArray[1];
				}
				else{
			  
					 if($dateArray[0] < $minDate){
						  $minDate = $dateArray[0];
					 }
					 if($dateArray[1] > $maxDate ){
						  $maxDate = $dateArray[1];
					 }
			  
				}
		  
		  $firstLoop = false;    
		  }
		  
		  $output = array("timeBegin" => $minDate + 0 , "timeEnd" => $maxDate + 0 );
		  
		  
		  $firstLoop = true;
		  $i = 0;
		  $sumLat = 0;
		  $sumLon = 0;
		  $maxFCount = 0;
		  foreach($facet_fields['geo_point'] as $pointKey => $count){
				
				if(stristr($pointKey, ",")){
					 $pointArray = explode(",", $pointKey);
				}
				else{
					 $pointArray = explode(" ", $pointKey);
				}
				
				if($pointArray[0] != 0 && $pointArray[1] !=0){
			  
					 if($firstLoop){
						  $sumLat += $pointArray[0];
						  $sumLon += $pointArray[1];
						  $minLat = $pointArray[0];
						  $maxLat = $pointArray[0];
						  $minLon = $pointArray[1];
						  $maxLon = $pointArray[1];
						  $maxFCount = $count;
						  $i++;
					 }
					 else{
						  if($count > ($maxFCount * 0) ){
						 
								$sumLat += $pointArray[0];
								$sumLon += $pointArray[1];
								
								if( $pointArray[0] < $minLat){
									 $minLat = $pointArray[0];
								}
								if($pointArray[0] > $maxLat ){
									 $maxLat = $pointArray[0];
								}
								if( $pointArray[1] < $minLon){
									 $minLon = $pointArray[1];
								}
								if($pointArray[1] > $maxLon ){
									 $maxLon = $pointArray[1];
								}
						 
						  $i++;
						  }
					 }
					 $firstLoop = false;
				}
				
		  }
		  
		  if($i>0){
				$output["geoLat"] =  $sumLat / $i;
				$output["geoLong"] =  $sumLon / $i;
			  
				if($i>1){
					 $output["geoPoly"] = $minLat.",".$maxLon." ".$maxLat.",".$maxLon." ".$minLat.",".$minLon." ".$maxLat.",".$minLon;
				}
		  }
		  else{
				$output["geoLat"] =  $sumLat;
				$output["geoLong"] =  $sumLon;
		  }
		  
		  //$output["sdata"] = $facet_fields['geo_point'];
		  //$output["dpath"] = $this->default_context_path;
		  //$output["json"] = $contextArray;
	
        return $output;
    
    }//end function



    function geoTileFacets(){
	
		$geoTileFacets = $this->geoTileFacets;
		if(is_array($geoTileFacets)){
			$host = OpenContext_OCConfig::get_host_config();
			$requestParams = $this->requestParams;
			$geoTileFacetURLs = array();
			
			$geoObj = new GlobalMapTiles;
			
			foreach($geoTileFacets as $tileKey => $tileCount){
				$tileKey = (string)$tileKey;
				$requestParams["geotile"] =  (string)$tileKey;
				$requestParams["geotile"] = substr($requestParams["geotile"],0,20); //don't go too deep
				$link = $host.OpenContext_FacetOutput::generateFacetURL($requestParams, false, false, false, false, "xhtml");
				$linkJSON_facets = $host.OpenContext_FacetOutput::generateFacetURL($requestParams, false, false, false, false, "facets_json");
				$linkJSON_results = $host.OpenContext_FacetOutput::generateFacetURL($requestParams, false, false, false, false, "results_json");
				
				$geoArray = $geoObj->QuadTreeToLatLon($tileKey);
				
				$LinkingArray = array("name" => "Geographic region ($tileKey)" ,
								"href" => $link,
								"facet_href" => $linkJSON_facets,
								"result_href" => $linkJSON_results,
								"linkQuery" => $tileKey,
								"param" => "geotile",
								"count" => $tileCount,
								"geoBounding" => $geoArray
								);
				
				unset($geoArray);
				$geoTileFacetURLs[] = $LinkingArray;
			}//end loop
			
			$this->geoTileFacetURLs = $geoTileFacetURLs;
		}
		else{
			$this->geoTileFacetURLs = false;
		}
	
    }//end function





}
