<?php
/*
This class uses faceted searh results to calculate proportional data (percentages etc.)

*/

class ProportionalData{
    
    public $requestParams;
    public $propOf; //string of what defines the denominator
    
    public $linkPropOf; //string that describes / defines a link for getting a comparative percentage view
    public $linkPropOfURL; //url for getting a comparative percentage view
    
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
                $JSON_string = file_get_contents($denominatorLink);
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
                        $this->propOf = $lookCompData;
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
                    
                        $linkedData = new LinkedDataRef;
                        if(stristr($lookCompData,"::")){
                            $relArray = explode("::",$lookCompData);
                        }
                        else{
                            $relArray = array($lookCompData);
                        }
                        
                        $firstLoop = true;
                        foreach($relArray as $actRel){
                            if($linkedData->lookup_refURI($actRel)){
                                if($firstLoop){
                                    $this->propOf = $linkedData->refLabel;
                                }
                                else{
                                    $this->propOf .= "::".$linkedData->refLabel;
                                }
                            }
                            $firstLoop = false;
                        }
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
        foreach($requestParams as $paramKey => $vals){
            if($paramKey == "rel" || $paramKey == "taxa"){
                $actCompParam = $paramKey;   
            }
        }
        if(isset($requestParams["comp"])){
            $actCompParam = false;
            unset($requestParams["comp"]);
            $output = $host.(OpenContext_FacetOutput::generateFacetURL($requestParams, null, null, false, false, $type));
        }
        
        if($actCompParam != false){
            $compValArray = $requestParams[$actCompParam];
            $compValIndex = count($compValArray)-1; //the last taxonomic path in the array of paths
            $compVal = $compValArray[$compValIndex];
            
            if(stristr($compVal, "::")){ //case where the comp val is a hierachy
                $compValEx = explode("::", $compVal);
                unset($compValEx[count($compValEx)-1]); //remove the last part of the taxonomic or rel path
                $useCompVal =  $actCompParam."::".implode("::", $compValEx);
                if($actCompParam == "rel"){
                    
                    $linkedData = new LinkedDataRef;
                    foreach($compValEx as $actRel){
                        if($linkedData->lookup_refURI($actRel)){
                            if(!$this->linkPropOf){
                                $this->linkPropOf = $linkedData->refLabel;
                            }
                            else{
                                $this->linkPropOf .= "::".$linkedData->refLabel;
                            }
                        }
                    }
                }
                else{
                    $this->linkPropOf = implode("::", $compValEx);
                }
                //create a comp link
                $this->linkPropOfURL = $host.(OpenContext_FacetOutput::generateFacetURL($requestParams, "comp", $useCompVal, false, false, "xhtml"));
                $output = $host.(OpenContext_FacetOutput::generateFacetURL($requestParams, "comp", $useCompVal, false, false, $type));
            }
        }
        
        return $output;
    }
    
}
