<?php


//this class interacts with solr to run searches
class AtomToPelagios{
    
    public $data; //records of Pelagios annotations
    public $clearCache;
    public $lastUpdated;
    
    const queryURI = "http://opencontext.org/sets/.atom?rel%5B%5D=http%3A%2F%2Fgawd.atlantides.org%2Fterms%2Forigin||http%3A%2F%2Fpurl.org%2Fdc%2Fterms%2Freferences";
    const cacheAge = 72000;

    public function getData(){
	
	$frontendOptions = array('lifetime' => self::cacheAge,
				 'automatic_serialization' => true );
	$backendOptions = array('cache_dir' => './cache/' );
		
	$cache = Zend_Cache::factory('Core','File',$frontendOptions,$backendOptions);
	$cache_id = "pelagios";
	
	if((!$cache_result = $cache->load($cache_id)) || $this->clearCache) {
	    $this->data = array();
	    $this->parseAtom(self::queryURI);
	    
	    if(count($this->data)>0){
		//only save to the cache is result was OK
		$JSONstring = Zend_Json::encode($this->data);
		$cache->save($JSONstring, $cache_id); //save results to the cache
	    }
	}
	else{
	    $this->data = Zend_Json::decode($cache_result);
	}
	
    }

    public function parseAtom($atomURI){
	
	@$atomString = file_get_contents($atomURI);
	if($atomString){
	    @$xml = simplexml_load_string($atomString);
	    if($xml){
		
		$data = $this->data;
		$xml->registerXPathNamespace("atom", OpenContext_OCConfig::get_namespace("atom"));
		
		$nextLink = false;
		foreach($xml->xpath("/atom:feed/atom:link[@rel = 'next']/@href") as $xpathRes) {
		    $nextLink = $xpathRes."";
		}
		
		if(!isset($data["lastUpdated"])){
		    foreach($xml->xpath("/atom:feed/atom:updated") as $xpathRes) {
			$this->lastUpdated = $xpathRes."";
			$data["lastUpdated"] = $this->lastUpdated;
		    }
		}
		
		foreach($xml->xpath("//atom:entry") as $entry) {
		    
		    $actRecord = array();
		    $entry->registerXPathNamespace("atom", OpenContext_OCConfig::get_namespace("atom"));
		    
		    foreach($entry->xpath("atom:id") as $xpathRes){
				$actRecord["ocURI"] = $xpathRes."";
		    }
		    foreach($entry->xpath("atom:title") as $xpathRes){
				$actRecord["ocLabel"] = $xpathRes."";
		    }
			
			if($entry->xpath("atom:link[@rel = 'http://gawd.atlantides.org/terms/origin']/@href")){
				$actRecord["ConcordiaType"] = "origin";
				foreach($entry->xpath("atom:link[@rel = 'http://gawd.atlantides.org/terms/origin']/@href") as $xpathRes){
					$actRecord["PleiadesOriginURI"] = $xpathRes."";
				}
			}
			elseif($entry->xpath("atom:link[@rel = 'http://purl.org/dc/terms/references']/@href")){
				$actRecord["ConcordiaType"] = "related";
				foreach($entry->xpath("atom:link[@rel = 'http://purl.org/dc/terms/references']/@href") as $xpathRes){
					$actRecord["PleiadesOriginURI"] = $xpathRes."";
				}
			}
			else{
				$actRecord["ConcordiaType"] = false;
				$actRecord["PleiadesOriginURI"] = false;
			}
		    
		    $data["records"][] = $actRecord;
		    
		}//end loop through entries
		
		$this->data = $data;
		
		if($nextLink != false){
		    $this->parseAtom($nextLink);
		}
	    }//end case when XML is ok
	    else{
		echo "<br/> Bad XML.";
	    }
	}//end case when atom string OK
	else{
	    echo "<br/> Atom not found.";
	}
    }//end Atom parsing function



}
