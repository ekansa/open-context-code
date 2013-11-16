<?php
/*
This class uses faceted searh results to calculate proportional data (percentages etc.)

*/

class ProportionalData{
    
    public $requestParams;
    public $propOf; //string of what defines the denominator
    
    public $linkPropOf; //string that describes / defines a link for getting a comparative percentage view
    public $linkPropOfURL; //url for getting a comparative percentage view
    public $nominatorCurrentVal; //string of the currently queried term, used to make it easier to understand proportions
    
    const cacheLife = 7200; // cache lifetime, measured in seconds, 7200 = 2 hours
    const cacheDir = "./cache/"; //cache directory for the denominator data
    
    
    //get denominator data (if requested with the "comp" parameter), either with new search or from cache
    function getDenominatorData(){
        
        $requestParams = $this->requestParams;
        $denominatorLink = $this->makeDenominatorDataLink();
        if($denominatorLink != false){
            $frontendOptions = array(
                'lifetime' => self::cacheLife, // cache lifetime, measured in seconds, 7200 = 2 hours
                'automatic_serialization' => true
            );
                
            $backendOptions = array(
                 'cache_dir' => self::cacheDir // Directory where to put the cache files
            );
                
            $cache = Zend_Cache::factory('Core',
                        'File',
                        $frontendOptions,
                        $backendOptions);
             
             
            $cache->clean(Zend_Cache::CLEANING_MODE_OLD); // clean old cache records
            $cacheID = "setJS_".md5($denominatorLink);
            if(!$cache_result = $cache->load($cacheID)) {
					 $ctx = stream_context_create(array( 
						  'http' => array( 
								'timeout' => 60 
								) 
						  ) 
					 ); 
					 
                $JSON_string = file_get_contents($denominatorLink, false, $ctx);
                @$denominatorArray = Zend_Json::decode($JSON_string);
					 if(is_array($denominatorArray)){
						  $cache->save($denominatorArray, $cacheID ); //save result to the cache, only if valid JSON
					 }
                else{
                    $denominatorArray = false;
                }
            }
            else{
                $denominatorArray = $cache_result;
                
            }
            return $denominatorArray;    
        }
        else{
            return false;
        }
        
    }
    
    
    //get the URL for the denominator data
    function makeDenominatorDataLink(){
        $output = false;
        $requestParams = $this->requestParams;
        $newJSONrequestURI = false;
        if(isset($requestParams["comp"])){
            $host = OpenContext_OCConfig::get_host_config();
            $compData = $requestParams["comp"];
            unset($requestParams["comp"]);
            $requestParams["recs"] = 1;
        
            if(stristr($compData, "prop::")){
                $propKey = str_replace("prop::", "", $compData);
                $this->propOf = $propKey;
                $propVal = null;
                if(substr_count($propKey, "::")>0){
                   $propArray = explode("::", $propKey);
                   $propKey = $propArray[0];
                   $propVal = $propArray[1];
                }
                $newJSONrequestURI = $host.(OpenContext_FacetOutput::generateFacetURL($requestParams, "prop", $propVal, $propKey, false, "results_json"));
            }
            elseif(stristr($compData, "taxa::")){
                $lookCompData = str_replace("taxa::", "", $compData);
                $foundNumber = false;
                $foundIndex = 0;
                $i=0;
                foreach($requestParams["taxa"] as $actRequestTaxa){
                    if(substr_count($actRequestTaxa, $lookCompData)>0){
                        $this->propOf = str_replace("||", " OR ", $lookCompData);
                        $nominatorRequestValArray = $this->pathStringToArray($actRequestTaxa);
                        $this->nominatorCurrentVal = str_replace("||", " OR ", $nominatorRequestValArray[count($nominatorRequestValArray) - 1]); //get the value of the last element of the array
                        $foundNumber = true;
                        $foundIndex  = $i;
                    }
                $i++;
                }
               
                if($foundNumber){
                   $requestParams["taxa"][$foundIndex] = $lookCompData;
                   $newJSONrequestURI = $host.(OpenContext_FacetOutput::generateFacetURL($requestParams, null, null, false, false, "results_json"));
                }
            }
            elseif(stristr($compData, "rel::")){
                $lookCompData = str_replace("rel::", "", $compData);
                
                $foundNumber = false;
                $foundIndex = false;
                $i=0;
                foreach($requestParams["rel"] as $actRequestTaxa){
                   
                   //echo "<br/>".$lookCompData. " looking at: ".$actRequestTaxa;
                   
                    if(substr_count($actRequestTaxa, $lookCompData)>0){
                    
                        $nominatorRequestValArray = $this->pathStringToArray($actRequestTaxa);
                        $this->nominatorCurrentVal = $this->relURIsToLabels($nominatorRequestValArray[count($nominatorRequestValArray) - 1]); //get the value of the last element of the array
                    
                        $relArray = $this->pathStringToArray($lookCompData); //make an array of the path string
                        $this->propOf = $this->relURIsToLabels($relArray);
                        unset($linkedData);
                    
                        $foundNumber = true;
                        $foundIndex  = $i;
                        //echo "<br/>FOUND at $i ";
                    }
                $i++;
                }
               
                if($foundNumber  != false){
                   $requestParams["rel"][$foundIndex] = $lookCompData;
                   $newJSONrequestURI = $host.(OpenContext_FacetOutput::generateFacetURL($requestParams, null, null, false, false, "results_json"));
                }
            }
				elseif(stristr($compData, "eol")){
					 unset($requestParams[$compData]);
                $newJSONrequestURI = $host.(OpenContext_FacetOutput::generateFacetURL($requestParams, "eol", "root", false, false, "results_json"));
					 $this->propOf = "Items with biological taxa";
				}
            else{
                unset($requestParams[$compData]);
                $newJSONrequestURI = $host.(OpenContext_FacetOutput::generateFacetURL($requestParams, null, null, false, false, "results_json"));
            }
            
            /*
            if(substr_count($newJSONrequestURI,".json")<1){
               if(substr_count($newJSONrequestURI,"?")<1){
                  $newJSONrequestURI = $newJSONrequestURI.".json";
               }
               else{
                  $newJSONrequestURI = str_replace("?", ".json?", $newJSONrequestURI);
                              
               }
            }
            */
            
            $output = $newJSONrequestURI;
        }
        
        return $output;
	}
    
    
    //convert an array of REL uris into human readable labels
    function relURIsToLabels($relURIarray){
        $output = false;
        
        if(!is_array($relURIarray)){
            $relURIarray = array($relURIarray);
        }
        
        $linkedData = new LinkedDataRef;
        foreach($relURIarray as $rawActRel){
            
            if(stristr($rawActRel, "||")){
                $actRelEx = explode("||", $rawActRel);
            }
            else{
                $actRelEx = array($rawActRel);
            }
            
            $tempOutput = false;
            foreach($actRelEx as $actRel){
                if($linkedData->lookup_refURI($actRel)){
                    if(!$tempOutput){
                        $tempOutput = $linkedData->refLabel;
                    }
                    else{
                        $tempOutput .= " OR ".$linkedData->refLabel; //add ORs
                    }
                }
            }
            
            if(!$output){
                $output = $tempOutput;
            }
            else{
                $output .= "::".$tempOutput; //put into a hierarch path
            }
            
        }
        
        return $output;
    }
    
    
    //turns a path string into an array
    function pathStringToArray($pathString, $pathDelim = "::"){
        if(stristr($pathString, $pathDelim)){
            return explode($pathDelim, $pathString);
        }
        else{
            return array( 0 => $pathString);
        }
    }
    
    
    //calculate the greatest common demoninator
    function gcd($a, $b){
        return ($b) ? $this->gcd($b, $a % $b) : $a;
    }
    
    
    //generate a link for propotional getting propotional data
    function proportionalVisLink($type = "xhtml"){
        
        $host = OpenContext_OCConfig::get_host_config();
        $this->linkPropOf = false;
        $this->linkPropOfURL = false;
        $output = false;
        $requestParams = $this->requestParams;
        $actCompParam = false;
		  $selectedComp = false;
        foreach($requestParams as $paramKey => $vals){
            if($paramKey == "rel" || $paramKey == "taxa" ){
                $actCompParam = $paramKey;   //the last param key that is a rel or taxa is used to make a proportional comparison
            }
        }
        if(isset($requestParams["comp"])){
            $actCompParam = false;
				$selectedComp = $requestParams["comp"];
            $this->makeDenominatorDataLink(); //do this so that 
            unset($requestParams["comp"]);
            $output = $host.(OpenContext_FacetOutput::generateFacetURL($requestParams, null, null, false, false, $type));
        }
		  
        
        if($actCompParam != false){
            $compValArray = $requestParams[$actCompParam];
            $compValIndex = count($compValArray)-1; //the last taxonomic path in the array of paths
            $compVal = $compValArray[$compValIndex];
            
            if(stristr($compVal, "::")){ //case where the comp val is a hierarchy
                $compValEx = $this->pathStringToArray($compVal);
                $rawFinalCompValue = $compValEx[count($compValEx)-1];
                $this->nominatorCurrentVal = str_replace("||", " OR ", $rawFinalCompValue); //the current queried term
                unset($compValEx[count($compValEx)-1]); //remove the last part of the taxonomic or rel path
                $useCompVal =  $actCompParam."::".implode("::", $compValEx);
                if($actCompParam == "rel"){
                    $this->nominatorCurrentVal = $this->relURIsToLabels($rawFinalCompValue);
                    $this->linkPropOf = $this->relURIsToLabels($compValEx);
                }
                else{
                    $this->linkPropOf = implode("::", $compValEx);
                }
                //create a comp link
                $this->linkPropOfURL = $host.(OpenContext_FacetOutput::generateFacetURL($requestParams, "comp", $useCompVal, false, false, "xhtml"));
                $output = $host.(OpenContext_FacetOutput::generateFacetURL($requestParams, "comp", $useCompVal, false, false, $type));
            }
        }
		  
		  if(isset($requestParams["eol"])){
				if($requestParams["eol"] != "root"){
					 if(!$selectedComp){
						  $output = $host.(OpenContext_FacetOutput::generateFacetURL($requestParams, "comp", "eol", false, false, $type));
					 }
					 $this->linkPropOfURL = $host.(OpenContext_FacetOutput::generateFacetURL($requestParams, "comp", "eol", false, false, "xhtml"));
					 $this->nominatorCurrentVal = str_replace("||", " OR ", $requestParams["eol"]); //the current queried term
					 $this->nominatorCurrentVal = $this->relURIsToLabels($this->nominatorCurrentVal);
					 $this->nominatorCurrentVal = preg_replace("/\([^)]+\)/","",$this->nominatorCurrentVal);
					 $this->nominatorCurrentVal = trim($this->nominatorCurrentVal);
					 $this->linkPropOf = "All taxa";
				}
		  }
		  
        return $output;
    }
    
}
