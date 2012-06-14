<?php

/*
This class gets information from the Pelagios API to get data related to place
*/

class Pelagios {
    
    public $db;
    public $requestParams;
    public $makeAPIrequest;
    public $actPlaceURI;
    public $jsonString;
    public $HTMLoutput;
    
    const baseURI = "http://api-pelagios.apigee.com/graph-explorer/"; //base URI for Pelagios API
    const keep_seconds = 604800; // number of seconds to cache a request (604800 seconds is 1 week)
    
    //initiallize the database
    public function initialize($requestParams, $db = false){
	
        $this->actPlaceURI = false;
	$this->makeAPIrequest = false;
	$this->requestParams = $requestParams;
	$this->jsonString = false;
        $this->HTMLoutput = "";
        
	if(array_key_exists("rel", $requestParams)){
            //$this->HTMLoutput = "found the param";
	    foreach($requestParams["rel"] as $relParam){
		
		if(stristr($relParam, "http://pleiades.stoa.org/places/")){
		    $relArray = explode("::", $relParam);
		    if(count($relArray)>=2){
                        $this->actPlaceURI = $relArray[1];
                        $this->makeAPIrequest = true;
                        //$this->HTMLoutput = "found the link: ".$relArray[1];
                    }
		}
		
	    }
	
	}
	
	if(!$db && $this->makeAPIrequest){
            $db_params = OpenContext_OCConfig::get_db_config();
            $db = new Zend_Db_Adapter_Pdo_Mysql($db_params);
            $db->getConnection();
            $this->db = $db;
            $this->deleteOld();
        }
        
    }
    
    public function getRelated(){
       
        $actPlaceURI = $this->actPlaceURI;
        
        if($actPlaceURI != false){
            $db = $this->db;
            $sql  = "SELECT * FROM pelagios WHERE placeURI = '$actPlaceURI' LIMIT 1";       
            $result = $db->fetchAll($sql, 2);
            if($result){
                $this->jsonString = $result[0]["json"];
            }
            else{
                
                $urlAPI = self::baseURI . "places/referencesTo?place=" . urlencode($actPlaceURI);
                @$rawResponse = file_get_contents($urlAPI);
                if($rawResponse != false){
                    
                    $this->jsonString = $rawResponse;
                    $data = array("placeURI" => $actPlaceURI,
                          "phpTime" => microtime(true),
                          "json" => $rawResponse
                          );
                    
                    $outcome = true;
                    try{
                        $db->insert("pelagios", $data); //cache the data in the database
                    }
                    catch(Exception $e){
                        $outcome = false;
                    }
                }//end case where data is retrieved from API  
            }//end case where data is NOT cached
        }
    }
    
    
    public function displayPelagiosData(){
        
        if($this->jsonString != false){
            $collections = array( "Perseus" => array( "baseURI" => "http://www.perseus.tufts.edu/", "query" => false),
                               "SPQR" => array( "baseURI" => "http://spqr.cerch.kcl.ac.uk/", "query" => false),
                               "Arachne" => array( "baseURI" => "http://arachne.uni-koeln.de/", "query" => false),
                               "nomisma" => array( "baseURI" => "http://nomisma.org/", "query" => "http://www.google.com/search?as_sitesearch=nomisma.org%2Fid&q="),
                               "Google Ancient Places" => array( "baseURI" => "http://googleancientplaces.wordpress.com/", "query" => "http://gap.alexandriaarchive.org/places/books?uri=")
                              );
            
            
            $pelagiosRawArray = Zend_Json::decode($this->jsonString);
            $pelagiosArray = array();
            foreach($pelagiosRawArray as $rec){
                if(!array_key_exists($rec["rootDataset"], $pelagiosArray)){
                    $pelagiosArray[$rec["rootDataset"]] = $rec;
                }
                else{
                    $pelagiosArray[$rec["rootDataset"]]["referencesTo"] += $rec["referencesTo"];
                }
            }
            
            $placeRef = New LinkedDataRef;
            $placeRef->lookup_refURI($this->actPlaceURI);
            $placeName = $placeRef->refLabel;
            
            $xmlString = "<div></div>";
            $xml = simplexml_load_string($xmlString);
            $xml->addAttribute('id', 'pelagios_results');
            $xml->addAttribute('style', 'background-color: #F5F5DC; padding:5px;');
            $xmlP = $xml->addChild('p', 'Other References to "'.$placeName.'", outside Open Context:' );
            $xmlP->addAttribute('class', 'bodyText');
            $xmlUL = $xml->addChild('ul');
            $xmlUL->addAttribute('class', 'bodyText');
            
            if(count($pelagiosArray)>=1){
                foreach($pelagiosArray as $rec){
                    $collection = $rec["rootDataset"];
                    $collectionURI = $collections[$collection]["baseURI"]; 
                    $queryURI =  $collections[$collection]["query"]; 
                    
                    $xmlLI = $xmlUL->addChild('li');
                    
                    if($collection == "nomisma"){
                        $collection = "Nomisma";
                    }
                    
                    
                    if(!$queryURI){
                        $xmlA = $xmlLI->addChild('a', $collection);
                        $xmlA->addAttribute('href', $collectionURI);
                        $xmlA->addAttribute('title', "Link to this project / collection outside Open Context");
                    }
                    else{
                        $xmlA = $xmlLI->addChild('a', $collection);
                        $xmlA->addAttribute('href', $queryURI.urlencode($this->actPlaceURI));
                        $xmlA->addAttribute('title', "Link to references to this place in ".$collection);
                    }
                    
                    $xmlSpan = $xmlLI->addChild('span', " [".$rec["referencesTo"]." references]");
                }
            }
            else{
                $xmlLI = $xmlUL->addChild('li', '(No other references identified)');
            }
            
            $xmlP2 = $xml->addChild('p');
            $xmlP2->addAttribute('class', 'tinyText');
            $xmlEM = $xmlP2->addChild('em', 'Linking data provided by ');
            $xmlEMA = $xmlEM->addChild('a', 'Pelagios');
            $xmlEMA->addAttribute('href', 'http://pelagios-project.blogspot.com/');
            
            $dom = dom_import_simplexml($xml)->ownerDocument;
            $dom->formatOutput = true;
            $this->HTMLoutput = str_replace('<?xml version="1.0"?>', '', $dom->saveXML());
            
        }
        
    }
    
    
    
    
    
    //delete old cached data
    public function deleteOld(){
        $db = $this->db;
	$where = array();
	$currentTime = microtime(true);
	$expiredRecord = $currentTime - self::keep_seconds;
	$where[] = "phpTime <= ".$expiredRecord;
	$db->delete("pelagios", $where);
    }
    
    
    
    
}


?>