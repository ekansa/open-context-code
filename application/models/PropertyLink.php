<?php

/*
This class creates or updates linked data relationships for properties
it adds or updates new XML to the property description and to spatial items
*/

class PropertyLink {
    
    public $db;
    public $propertyUUID;
    public $variableUUID;
    public $varLabel;
    public $valText;
    
    public $propVarRelations;
    public $propValRelations;
    
    public $numItems;
    public $doneCount;
    public $message;
    public $errors;
    
    public $lastItem;
    public $lastXML;
    
    public $limitedProcess;
    
    public $solrVar;
    public $solrValue;
    
    public $nameArray = array(
			'Aves'=> 'http://www.eol.org/pages/695',
			'bird, indeterminate'=> 'http://www.eol.org/pages/695',
			
			'Bos primigenius'=> 'http://www.eol.org/pages/11021570', 
			'Bos taurus'=> 'http://www.eol.org/pages/328699',
			'cow'=> 'http://www.eol.org/pages/328699',
			'bos'=> 'http://www.eol.org/pages/34548',
			'Bovid'=> 'http://www.eol.org/pages/7687', 
			'Camelus'=> 'http://www.eol.org/pages/38902', 
			'Camelus bactrianus'=> 'http://www.eol.org/pages/344581', 
			'Camelus cf. dromedarius'=> 'http://www.eol.org/pages/309019', 
			'Camelus dromedarius'=> 'http://www.eol.org/pages/309019', 
			'Canis familiaris'=> 'http://www.eol.org/pages/1228387', 
			'Canis sp.'=> 'http://www.eol.org/pages/14460',
			'Medium Canid' => 'http://www.eol.org/pages/7676',
			
			'Capra hircus'=> 'http://www.eol.org/pages/328660', 
			'Capra sp.'=> 'http://www.eol.org/pages/42403',
			'Capra'=> 'http://www.eol.org/pages/42403', 
			'Carnivore'=> 'http://www.eol.org/pages/7662', 
			'Clarias'=> 'http://www.eol.org/pages/23806', 
			'Crab'=> 'http://www.eol.org/pages/10948079', 
			'Dama mesopotamica'=> 'http://www.eol.org/pages/308402',
			'fallow deer'=> 'http://www.eol.org/pages/38816',
			'Equid'=> 'http://www.eol.org/pages/11018612', 
			'Equus caballus'=> 'http://www.eol.org/pages/328648', 
			'Equus hemionus'=> 'http://www.eol.org/pages/311507', 
			'Equus hemionus/asinus'=> 'http://www.eol.org/pages/11018612',
			'Equus asinus / hemionus' => 'http://www.eol.org/pages/11018612',
			'Equus sp.'=> 'http://www.eol.org/pages/11018612', 
			'Equus spp.'=> 'http://www.eol.org/pages/11018612', 
			'Fish'=> 'http://www.eol.org/pages/2775704',
			'unident. fish' => 'http://www.eol.org/pages/2775704',
			'Gallus gallus'=> 'http://www.eol.org/pages/1049263', 
			'Gazella sp.'=> 'http://www.eol.org/pages/15584', 
			'Gerbillus sp.'=> 'http://www.eol.org/pages/111264', 
			'goat'=> 'http://www.eol.org/pages/328660', 
			'Hemiechinus sp.'=> 'http://www.eol.org/pages/34867', 
			'Large Canid'=> 'http://www.eol.org/pages/7676', 
			'Large mammal'=> 'http://www.eol.org/pages/1642', 
			'Large Mammal'=> 'http://www.eol.org/pages/1642',
			'med-lg mammal'=> 'http://www.eol.org/pages/1642',
			'Small Mammal'=> 'http://www.eol.org/pages/1642',
			'Lepus sp.'=> 'http://www.eol.org/pages/10840', 
			'Lepus spp.'=> 'http://www.eol.org/pages/10840', 
			'Medium Artiodactyl'=> 'http://www.eol.org/pages/7678', 
			'Medium Canid'=> 'http://www.eol.org/pages/7676', 
			'Medium Carnivore'=> 'http://www.eol.org/pages/7662', 
			'Medium mammal'=> 'http://www.eol.org/pages/1642', 
			'Medium Mammal'=> 'http://www.eol.org/pages/1642', 
			'Medium-large mammal'=> 'http://www.eol.org/pages/1642', 
			'medium-sized mammal'=> 'http://www.eol.org/pages/1642', 
			'medium-sized ungulate'=> 'http://www.eol.org/pages/7678', 
			'Odocoileus hemionus'=> 'http://www.eol.org/pages/328651', 
			'Ovis aries'=> 'http://www.eol.org/pages/311906', 
			'Ovis aries / Capra hircus'=> 'http://www.eol.org/pages/2851411', 
			'Ovis aries/Capra hircus'=> 'http://www.eol.org/pages/2851411', 
			'Ovis orientalis'=> 'http://www.eol.org/pages/13845095', 
			'Ovis sp.'=> 'http://www.eol.org/pages/39510',
			'Ovis'=> 'http://www.eol.org/pages/39510',
			'Ovis/Capra'=> 'http://www.eol.org/pages/2851411',
			'Ovis / Capra'=> 'http://www.eol.org/pages/2851411',
			'Ovis/Capra/Gazella'=> 'http://www.eol.org/pages/7687', 
			'pig'=> 'http://www.eol.org/pages/4445655', 
			'Rodent'=> 'http://www.eol.org/pages/8677', 
			'Rodentia'=> 'http://www.eol.org/pages/8677', 
			'sapiens'=> 'http://www.eol.org/pages/327955', 
			'scrofa'=> 'http://www.eol.org/pages/4445655', 
			'sheep'=> 'http://www.eol.org/pages/311906', 
			'sheep/goat'=> 'http://www.eol.org/pages/2851411', 
			'Small Artiodactyl'=> 'http://www.eol.org/pages/7678', 
			'Small Canid'=> 'http://www.eol.org/pages/7676', 
			'Small Carnivore'=> 'http://www.eol.org/pages/7662', 
			'Small Felid'=> 'http://www.eol.org/pages/7674', 
			'Small Mammal'=> 'http://www.eol.org/pages/1642', 
			'Small Phasianidae'=> 'http://www.eol.org/pages/7591', 
			'Small to medium-sized mammal (rabbit)'=> 'http://www.eol.org/pages/1642', 
			'Sus scrofa'=> 'http://www.eol.org/pages/4445655', 
			'Sus sp.'=> 'http://www.eol.org/pages/42318', 
			'Tatera indica'=> 'http://www.eol.org/pages/1179780', 
			'Vulpes vulpes'=> 'http://www.eol.org/pages/328609'
			
			
		);
    
    
    public $genus;
    public $species;
    public $linkItems = array();
    
    public $standardVarRels = array( "linkedLabel"=> "Has Biological Taxonomy",
				 "linkedURI" => "http://purl.org/NET/biol/ns#term_hasTaxonomy",
				 "vocabulary"=> "Biological Taxonomy Vocabulary",
				 "vocabURI"=> "http://purl.org/NET/biol/ns#");
    
    const propRootURI = "http://opencontext.org/properties/";
    
    //initiallize the database
    public function initialize($db = false){
	
	$this->variableUUID = false;
	$this->propVarRelations = false;
	$this->propValRelations = false;
	$this->doneCount = 0;
	$this->limitedProcess = false;
	
	if(!$db){
            $db_params = OpenContext_OCConfig::get_db_config();
            $db = new Zend_Db_Adapter_Pdo_Mysql($db_params);
            $db->getConnection();
        }
        
        $this->db = $db;
    }
    
    //find the associated variable, then get the variable and the property linked data relations
    function get_property_relations(){
	$db = $this->db;
	$propertyUUID = $this->propertyUUID;
	
	$sql = "SELECT properties.variable_uuid, var_tab.var_label, val_tab.val_text
		FROM properties
		LEFT JOIN var_tab ON properties.variable_uuid = var_tab.variable_uuid
		LEFT JOIN val_tab ON properties.value_uuid = val_tab.val_text
		WHERE properties.property_uuid = '".$propertyUUID."' LIMIT 1";
	
	//echo $sql;
	$result = $db->fetchAll($sql, 2);
        if($result){
	    $variableUUID = $result[0]["variable_uuid"];
	    $this->variableUUID = $variableUUID;
	    $this->varLabel = trim($result[0]["var_label"]);
	    $this->valText = trim($result[0]["val_text"]);
	    $this->propVarRelations = $this->getLinkedData($variableUUID);
	    $this->propValRelations = $this->getLinkedData($propertyUUID);
	}
    }
    
    
    //get linked data relations, keep in an array
    function getLinkedData($itemUUID){
	$db = $this->db;
	
	$output = false;
	$sql = "SELECT linked_data.linkedLabel, linked_data.linkedURI, linked_data.vocabulary, 	linked_data.vocabURI
	FROM linked_data
	WHERE linked_data.itemUUID = '$itemUUID' ";
	
	$result = $db->fetchAll($sql, 2);
        if($result){
	    $raw_out = array();
	    $output = array();
	    $raw_out = $result[0];
	    foreach($raw_out as $key => $value){
		$output[$key] = trim($value);
	    }
	}
	
	
	return $output;
    }
    
    
    function get_spatial_refs(){
	
	$db = $this->db;
	
	$sql = "SELECT subject_uuid as uuid
	FROM observe
	WHERE property_uuid = '".$this->propertyUUID."' ";
	
	$result = $db->fetchAll($sql, 2);
        if($result){
	    foreach($result as $row){
		$spaceUUID = $row["uuid"];
		$this->cache_space_todo($spaceUUID);
	    }
	    $this->numItems = count($result);
	}
    }//end function
    
    /*
    Useful function for fixing bad Atom Dates
    
    
UPDATE space
SET atom_entry = REPLACE(atom_entry, '1969-12-31T16:00:00-07:00', DATE_FORMAT(created, '%Y-%m-%d\T%H:%i:%s\-07:00'))
WHERE atom_entry LIKE '%1969-12-31T16:00:00-07:00%'

    */
    
    function process_prop_todo(){
	$db = $this->db;
	$errors = array();
	
	if(!$this->limitedProcess){
	    $sql = "SELECT DISTINCT itemUUID as uuid
	    FROM prop_docs_log
	    WHERE propertyUUID = '".$this->propertyUUID."'
	    AND status = 'to do'
	    
	    ";
	}
	else{
	    $sql = "SELECT DISTINCT itemUUID as uuid
	    FROM prop_docs_log
	    WHERE propertyUUID = '".$this->propertyUUID."'
	    AND status = 'to do'
	    LIMIT 10;
	    ";
	}
	//echo $sql;
	//break;
	$result = $db->fetchAll($sql, 2);
        if(!$result){
	    $errors[] = "No todo records found !";
	    $this->errors = $errors;
	    $this->message = "All items associated with property are ALREADY up to date.";
	}
	else{
	    
	    foreach($result as $row){
		
		$spaceUUID = $row["uuid"];
		$hashID = md5($spaceUUID."_".$this->propertyUUID);
		
		$sql = "SELECT archaeoML
		FROM space
		WHERE uuid = '$spaceUUID'
		LIMIT 1;
		";
		
		$resultB = $db->fetchAll($sql, 2);
		if(!$resultB){
		    $errors[] = "Space record not found: ".$spaceUUID;
		    $this->errors = $errors;
		}
		else{
		    $xmlString = $resultB[0]["archaeoML"];
		    if(strlen($xmlString)< 10){
			$xmlString = file_get_contents("http://opencontext.org/subjects/".$spaceUUID.".xml");
			//break;
		    }
		    
		    $subject = new Subject;
		    $xmlString = $subject->namespace_fix($xmlString);
		    $xmlString = $subject->archaeoml_fix($xmlString);
		    
		    
		    @$xml = simplexml_load_string($xmlString); 
		    if($xml){
			unset($xml);
			
			$xmlString = str_replace('<?xml version="1.0"?>', '<?xml version="1.0" encoding="utf-8"?>', $xmlString);
			$doc = new DOMDocument("1.0", "utf-8");
			$doc->loadXML($xmlString);
			//unset($xmlString);
			$xpath = new DOMXpath($doc);
			$xpath->registerNamespace('arch', OpenContext_OCConfig::get_namespace("arch", "spatial"));
			$xpath->registerNamespace('oc', OpenContext_OCConfig::get_namespace("oc", "spatial"));
			
			
			
			$propVarRelations = $this->propVarRelations;
			$propValRelations = $this->propValRelations;
			    
			$addLinkedData = true;
			$addToOld = false;
			
			$varAlready = false;
			$queryDuplicateA = "//oc:linkedData/oc:relationLink[@href = '".$propVarRelations["linkedURI"]."']";
			$oldLinks = $xpath->query($queryDuplicateA);
			foreach($oldLinks as $old){
			    $varAlready = true;
			}
			
			$valAlready = false;
			$queryDuplicateB = "//oc:linkedData/oc:relationLink/oc:targetLink[@href = '".$propValRelations["linkedURI"]."']";
			$oldLinks = $xpath->query($queryDuplicateB);
			foreach($oldLinks as $old){
			    $valAlready = true;
			}
			
			if($valAlready && $varAlready){
			    $addLinkedData = false;
			}
			
			if($addLinkedData){
			    $queryOld = "//arch:properties/arch:property[oc:propid = '".$this->propertyUUID."']/oc:linkedData";
			    $oldLinks = $xpath->query($queryOld);
			    foreach($oldLinks as $old){
				//$addLinkedData = false;
				$this->remove_children($old);
				$addToOld = true;
			    }
			    
			    $query = "//arch:properties/arch:property[oc:propid = '".$this->propertyUUID."']";
			    $props = $xpath->query($query);
			    
			    foreach($props as $prop){
				if(!$addToOld){
				    $elementC = $doc->createElement("oc:linkedData");
				}
				else{
				    $elementC = $old;
				}
				if(is_array($propVarRelations)){
				    $elementD = $doc->createElement("oc:relationLink");
				    $elementD->setAttribute("localType", "variable");
				    $elementD->setAttribute("localID", $this->variableUUID);
				    $elementD->setAttribute("href", $propVarRelations["linkedURI"]);
				    $elementE = $doc->createElement("oc:vocabulary");
				    $elementE->setAttribute("href", $propVarRelations["vocabURI"]);
				    $elementEtext = $doc->createTextNode($propVarRelations["vocabulary"]);
				    $elementE->appendChild($elementEtext);
				    $elementD->appendChild($elementE);
				    $elementE = $doc->createElement("oc:label");
				    $elementEtext = $doc->createTextNode($propVarRelations["linkedLabel"]);
				    $elementE->appendChild($elementEtext);
				    $elementD->appendChild($elementE);
				    
				    if(is_array($propValRelations)){
					$elementE = $doc->createElement("oc:targetLink");
					$elementE->setAttribute("localType", "property");
					$elementE->setAttribute("localID", $this->propertyUUID);
					$elementE->setAttribute("localURI", self::propRootURI.$this->propertyUUID);
					$elementE->setAttribute("href", $propValRelations["linkedURI"]);
					$elementF = $doc->createElement("oc:vocabulary");
					$elementF->setAttribute("href", $propValRelations["vocabURI"]);
					$elementFtext = $doc->createTextNode($propValRelations["vocabulary"]);
					$elementF->appendChild($elementFtext);
					$elementE->appendChild($elementF);
					$elementF = $doc->createElement("oc:label");
					$elementFtext = $doc->createTextNode($propValRelations["linkedLabel"]);
					$elementF->appendChild($elementFtext);
					$elementE->appendChild($elementF);
					$elementD->appendChild($elementE);
				    }
				    
				    $elementC->appendChild($elementD);
				}
				$prop->appendChild($elementC);
			    }
			
			    $doc->formatOutput = true;
			    $newXMLstring = $doc->saveXML();
			    
			    $response = false;
			    if(true){
				try{
				    @$response = OpenContext_NewDocs::spaceAdd($newXMLstring, true);
				}catch(Exception $e) {
				    $errors[] = "Failure on: ".$spaceUUID." ".$e;
				    $this->errors = $errors;
				    //echo $e;
				    //break;
				}
			    }
			    //$this->lastItem = OpenContext_NewDocs::spaceAdd($newXMLstring, true);
			    //$this->lastXML = $newXMLstring;
			    
			    $data = array("status" => "done");
			    $where = array();
			    $where = "prop_space_id = '$hashID' ";
			    
			    if($response != false){
				$db->update("prop_docs_log", $data, $where);
				$this->doneCount ++;
			    }
			    else{
				$data = array("status" => "ERROR");
				$db->update("prop_docs_log", $data, $where);
				$errors[] = "Note of failure on: ".$spaceUUID;
				$this->errors = $errors;
			    }
			    
			    unset($doc);
			    unset($newXMLstring);
			    unset($subject);
			    unset($xmlString);
			    //break;
			}
			else{
			    $errors[] = "Already has this linked data: ".$spaceUUID;
			    $this->errors = $errors;
			    
			    $data = array("status" => "done");
			    $data["propertyUUID"] = $this->propertyUUID;
			    $where = array();
			    $where[] = "itemUUID = '$spaceUUID' ";
			    $db->update("prop_docs_log", $data, $where);
			    
			    $nData = array();
			    $nData["solr_indexed"] = false;
			    unset($where);
			    $where = array();
			    $where[] = "itemUUID = '$spaceUUID' ";
			    $db->update("noid_bindings", $nData, $where);
			    
			}
		    }
		    else{
			$errors[] = "Invalid XML: ".$spaceUUID;
			$this->errors = $errors;
		    }
		}
    
	    }//end loop through spatial items
	    
	    $this->message = "All items associated with property are NOW up to date.";
	    $SolrDocsIndexer = new SolrDocsIndex;
	    $SolrDocsIndexer->forceIndexing = true;
	    $SolrDocsIndexer->checkRunIndex();
	    $doneMessage = "";
	    if(is_array($SolrDocsIndexer->errors)){
		foreach($SolrDocsIndexer->errors as $key=>$value){
		    $doneMessage .= " ".$key.": ".$value;
		}
	    }
	    
	    $this->message .= " ". $doneMessage;
	}

    }//end function
    
    //removes all children nodes
    function remove_children(&$node) {
	while ($node->firstChild) {
	  while ($node->firstChild->firstChild) {
	    $this->remove_children($node->firstChild);
	  }
	  $node->removeChild($node->firstChild);
	}
    }
    

    //cache to-do list for a property
    function cache_space_todo($spaceUUID){
	
	$db = $this->db;
	
	$hashID = md5($spaceUUID."_".$this->propertyUUID);
	$data = array('prop_space_id' => $hashID,
		      'itemUUID' => $spaceUUID,
		      'propertyUUID' => $this->propertyUUID,
		      'status' => 'to do'
		      );
	
	try{
	    $db->insert("prop_docs_log", $data);
	}catch(Exception $e) {
	    
	}
	
    }




    function json_results($url){
	@$resultsString = file_get_contents($url);
	if(!$resultsString){
	    sleep(.75);
	    $resultsString = file_get_contents($url);
	}
	
	$resultObj = Zend_Json::decode($resultsString);
	$this->initialize();
	$db = $this->db;
	
	if(!isset($resultObj["results"])){
	    sleep(.75);
	    $resultsString = file_get_contents($url);
	    $resultObj = Zend_Json::decode($resultsString);
	    if(!isset($resultObj["results"])){
		echo "<br/><br/> NO QUERY RESULT ON: ".$url;
		break;
	    }
	}
	
	foreach($resultObj["resultsA"] as $item){
	    $data = array();
	    //$itemUUID = str_replace("http://opencontext.org/subjects/", "", $item["uri"]);
	    $itemUUID = $item["uuid"];
	    $data["prop_space_id"] = md5($this->solrValue."_".$itemUUID);
	    $data["itemUUID"] = $itemUUID;
	    $data["propertyUUID"] = $this->solrVar;
	    $data["status"] = $this->solrValue;
	    
	    $sql = "SELECT itemUUID FROM prop_docs_log WHERE itemUUID = '$itemUUID' LIMIT 1;";
	    $result = $db->fetchAll($sql, 2);
	    if(!$result){
		try{
		    $db->insert("prop_docs_log", $data);
		}catch(Exception $e) {
		    echo "fail! ".$e;
		    break;
		}
	    }
	    else{
		foreach($result as $row){
		    $itemUUID = $row["itemUUID"];
		    $where = "itemUUID = '".$itemUUID."' ";
		    $data = array("status" => "to do",
				  "propertyUUID" => $this->solrVar);
		    $db->update("prop_docs_log", $data, $where);
		}
	    }
	    unset($data);
	}
	
	$nextURL = $resultObj["paging"]["next"];
	if($nextURL != false){
	    $db->closeConnection();
	    $this->db = false;
	    sleep(.25);
	    $this->json_results($nextURL);
	}
    }







    //get linked data relations, keep in an array
    function name_getLinkedData(){
	$db = $this->db;
	
	$nameArray = $this->nameArray;
	$name = $this->solrValue;
	
	if(isset($nameArray[$name])){
	    $uri = $nameArray[$name];
	}
	else{
	    echo "the horror";
	    break;
	}
	
	$output = false;
	$sql = "SELECT linked_data.linkedLabel, linked_data.linkedURI, linked_data.vocabulary, 	linked_data.vocabURI
	FROM linked_data
	WHERE linked_data.linkedURI = '$uri'
	LIMIT 1;
	";
	
	$result = $db->fetchAll($sql, 2);
        if($result){
	    $raw_out = array();
	    $output = array();
	    $raw_out = $result[0];
	    foreach($raw_out as $key => $value){
		$output[$key] = trim($value);
	    }
	}
	
	$this->propValRelations = $output;
    }


    function name_de_dupe(){
	
	$db = $this->db;
	$errors = array();
	$name = $this->solrValue;
	
	$sql = "SELECT DISTINCT prop_docs_log.itemUUID as uuid
	    FROM prop_docs_log
	    WHERE prop_docs_log.status = '".$name."'
	    ";
	
	$resultA = $db->fetchAll($sql, 2);
	foreach($resultA as $row){
	    $itemUUID = $row["uuid"];
	    $sql = "SELECT * FROM prop_docs_log WHERE itemUUID = '".$itemUUID."' AND prop_docs_log.status != '".$name."'; ";
	    $resultB = $db->fetchAll($sql, 2);
	    if($resultB){
		$where = array();
		$where[] = "itemUUID = '$itemUUID' ";
		$where[] = "status = '$name' ";
		$db->delete("prop_docs_log", $where);
	    }
	}
	
    }

    function count_names_to_process(){
	$db = $this->db;
	
	$name = $this->solrValue;
	
	$sql = "SELECT itemUUID
	    FROM prop_docs_log
	    WHERE status = '".$name."'
	    GROUP BY itemUUID;
	    ";
	
	//echo $sql ;
	$select = $db->select()
             ->from('prop_docs_log')
             ->where('status  = ?',$name)
	     ->group('itemUUID');
	
	//$result = $db->fetchAll($select);
	$result = $db->fetchAll($sql, 2);
	$count = 0;
	foreach($result as $row){
	   // echo "<br/>".$row["uuid"];
	    $count++;
	}
	if($count<1){
	    $this->numItems = 0 ;
	}
	else{
	    $this->numItems = $count ;
	}
    }



    function name_process_prop_todo(){
	
	//$this->name_de_dupe();
	
	$db = $this->db;
	$errors = array();
	$name = $this->solrValue;
	
	$sql = "SELECT DISTINCT prop_docs_log.itemUUID as uuid
	    FROM prop_docs_log
	    WHERE prop_docs_log.status = '".$name."'
	    ";
	
	$result = $db->fetchAll($sql, 2);
	
        if($result){
	    
	    foreach($result as $row){
		
		$spaceUUID = $row["uuid"];
		
		$sql = "SELECT project_id, archaeoML
		FROM space
		WHERE uuid = '$spaceUUID'
		LIMIT 1;
		";
		
		$resultB = $db->fetchAll($sql, 2);
		if($resultB){
		    
		    $xmlString = $resultB[0]["archaeoML"];
		    $projectID = $resultB[0]["project_id"];
		    
		    $subject = new Subject;
		    $xmlString = $subject->namespace_fix($xmlString);
		    $xmlString = $subject->archaeoml_fix($xmlString);
		    
		    
		    @$xml = simplexml_load_string($xmlString); 
		    if($xml){
			unset($xml);
			
			
			$xmlString = str_replace('<?xml version="1.0"?>', '<?xml version="1.0" encoding="utf-8"?>', $xmlString);
			//$this->lastXML = "[old]".$xmlString;
			
			$doc = new DOMDocument("1.0", "utf-8");
			$doc->loadXML($xmlString);
			//unset($xmlString);
			$xpath = new DOMXpath($doc);
			$xpath->registerNamespace('arch', OpenContext_OCConfig::get_namespace("arch", "spatial"));
			$xpath->registerNamespace('oc', OpenContext_OCConfig::get_namespace("oc", "spatial"));
			
			$propVarRelations = $this->standardVarRels;
			$this->propVarRelations = $propVarRelations;
			$propValRelations = $this->propValRelations;
			    
			$addLinkedData = true;
			$addToOld = false;
			
			$varAlready = false;
			$queryDuplicateA = "//oc:linkedData/oc:relationLink[@href = '".$propVarRelations["linkedURI"]."']";
			$oldLinks = $xpath->query($queryDuplicateA);
			foreach($oldLinks as $old){
			    $varAlready = true;
			    //echo "bang var";
			}
			
			$valAlready = false;
			$queryDuplicateB = "//oc:linkedData/oc:relationLink/oc:targetLink[@href = '".$propValRelations["linkedURI"]."']";
			$oldLinks = $xpath->query($queryDuplicateB);
			foreach($oldLinks as $old){
			    $valAlready = true;
			    //echo "bang val";
			    //break;
			}
			
			if($valAlready && $varAlready){
			    $addLinkedData = false;
			}
			
			if($addLinkedData){
			    
			    $queryOld = "//arch:properties/arch:property[oc:show_val = '".$this->solrValue."']/oc:linkedData";
			    $oldLinks = $xpath->query($queryOld);
			    foreach($oldLinks as $old){
				//$addLinkedData = false;
				$this->remove_children($old);
				$addToOld = true;
			    }
			    
			    $query = "//arch:properties/arch:property[oc:show_val = '".$this->solrValue."']";
			    $props = $xpath->query($query);
			    
			    foreach($props as $prop){
				
				$query = "//arch:properties/arch:property[oc:show_val = '".$this->solrValue."']/arch:variableID";
				$varResult = $xpath->query($query);
				foreach($varResult as $vNode){
				    $this->variableUUID = $vNode->nodeValue;
				}
				$query = "//arch:properties/arch:property[oc:show_val = '".$this->solrValue."']/oc:propid";
				$propResult = $xpath->query($query);
				foreach($propResult as $pNode){
				    $this->propertyUUID = $pNode->nodeValue;
				}
				
				
				if(!$addToOld){
				    $elementC = $doc->createElement("oc:linkedData");
				}
				else{
				    $elementC = $old;
				}
				if(is_array($propVarRelations)){
				    $elementD = $doc->createElement("oc:relationLink");
				    $elementD->setAttribute("localType", "variable");
				    $elementD->setAttribute("localID", $this->variableUUID);
				    $elementD->setAttribute("href", $propVarRelations["linkedURI"]);
				    $elementE = $doc->createElement("oc:vocabulary");
				    $elementE->setAttribute("href", $propVarRelations["vocabURI"]);
				    $elementEtext = $doc->createTextNode($propVarRelations["vocabulary"]);
				    $elementE->appendChild($elementEtext);
				    $elementD->appendChild($elementE);
				    $elementE = $doc->createElement("oc:label");
				    $elementEtext = $doc->createTextNode($propVarRelations["linkedLabel"]);
				    $elementE->appendChild($elementEtext);
				    $elementD->appendChild($elementE);
				    
				    if(is_array($propValRelations)){
					$elementE = $doc->createElement("oc:targetLink");
					$elementE->setAttribute("localType", "property");
					$elementE->setAttribute("localID", $this->propertyUUID);
					$elementE->setAttribute("localURI", self::propRootURI.$this->propertyUUID);
					$elementE->setAttribute("href", $propValRelations["linkedURI"]);
					$elementF = $doc->createElement("oc:vocabulary");
					$elementF->setAttribute("href", $propValRelations["vocabURI"]);
					$elementFtext = $doc->createTextNode($propValRelations["vocabulary"]);
					$elementF->appendChild($elementFtext);
					$elementE->appendChild($elementF);
					$elementF = $doc->createElement("oc:label");
					$elementFtext = $doc->createTextNode($propValRelations["linkedLabel"]);
					$elementF->appendChild($elementFtext);
					$elementE->appendChild($elementF);
					$elementD->appendChild($elementE);
				    }
				    
				    $elementC->appendChild($elementD);
				}
				$prop->appendChild($elementC);
			    }
			
			    $doc->formatOutput = true;
			    $newXMLstring = $doc->saveXML();
			    
			    $response = false;
			    if(strlen($newXMLstring)> 1){
				try{
				    @$response = OpenContext_NewDocs::spaceAdd($newXMLstring, true);
				}catch(Exception $e) {
				    $errors[] = "Doc add Failure on: ".$spaceUUID;
				    $this->errors = $errors;
				    //echo $e;
				    //break;
				}
			    }
			    //$this->lastItem = OpenContext_NewDocs::spaceAdd($newXMLstring, true);
			    //$this->lastXML = $newXMLstring;
			    
			    $data = array("status" => "done::".$name);
			    $data["propertyUUID"] = $this->propertyUUID;
			    $where = array();
			    $where[] = "itemUUID = '$spaceUUID' ";
			    $where[] = "status = '$name' ";
			    
			    if($response != false){
				$db->update("prop_docs_log", $data, $where);
				$this->doneCount ++;
			    }
			    else{
				$data["status"] = ("ERROR::".$name);
				$db->update("prop_docs_log", $data, $where);
				$errors[] = "Update add failure on: ".$spaceUUID;
				$this->errors = $errors;
			    }
			    
			    $sql = "SELECT * FROM linked_data WHERE itemUUID = '".$this->variableUUID."' LIMIT 1;";
			    $resultVar = $db->fetchAll($sql, 2);
			    if(!$resultVar){
				$dataVar = array("hashID" => md5($this->variableUUID."_".$propVarRelations["linkedURI"]),
						 "fk_project_uuid" => $projectID,
						 "source_id" => "solr",
						 "itemUUID" => $this->variableUUID,
						 "itemType" => "variable",
						 "linkedLabel" => $propVarRelations["linkedLabel"],
						 "linkedURI" => $propVarRelations["linkedURI"],
						 "vocabulary" => $propVarRelations["vocabulary"],
						 "vocabURI" => $propVarRelations["vocabURI"]
						 );
				$db->insert("linked_data", $dataVar);
			    }
			    
			    $sql = "SELECT * FROM linked_data WHERE itemUUID = '".$this->propertyUUID."' LIMIT 1;";
			    $resultVal = $db->fetchAll($sql, 2);
			    if(!$resultVal){
				$dataVal = array("hashID" => md5($this->propertyUUID."_".$propValRelations["linkedURI"]),
						 "fk_project_uuid" => $projectID,
						 "source_id" => "solr",
						 "itemUUID" => $this->propertyUUID,
						 "itemType" => "property",
						 "linkedLabel" => $propValRelations["linkedLabel"],
						 "linkedURI" => $propValRelations["linkedURI"],
						 "vocabulary" => $propValRelations["vocabulary"],
						 "vocabURI" => $propValRelations["vocabURI"]
						 );
				$db->insert("linked_data", $dataVal);
			    }
			    
			    unset($doc);
			    unset($newXMLstring);
			    unset($subject);
			    unset($xmlString);
			    //break;
			}
			else{
			    $errors[] = "Already has this linked data: ".$spaceUUID;
			    $this->errors = $errors;
			    
			    $data = array("status" => "already-done::".$name);
			    $data["propertyUUID"] = $this->propertyUUID;
			    $where = array();
			    $where[] = "itemUUID = '$spaceUUID' ";
			    $where[] = "status = '$name' ";
			    $db->update("prop_docs_log", $data, $where);
			    
			    $nData = array();
			    $nData["solr_indexed"] = false;
			    unset($where);
			    $where = array();
			    $where[] = "itemUUID = '$spaceUUID' ";
			    $db->update("noid_bindings", $nData, $where);
			    
			}
		    }
		}
    
	    }//end loop through spatial items
	    
	    $this->message = "All items associated with property are NOW up to date.";
	    $SolrDocsIndexer = new SolrDocsIndex;
	    $SolrDocsIndexer->forceIndexing = true;
	    $SolrDocsIndexer->checkRunIndex();
	    $doneMessage = "";
	    if(is_array($SolrDocsIndexer->errors)){
		foreach($SolrDocsIndexer->errors as $key=>$value){
		    $doneMessage .= " ".$key.": ".$value;
		}
	    }
	    
	    $this->message .= " ". $doneMessage;
	}
	else{
	    $this->message = "All items associated with property are ALREADY up to date.";
	}
    }//end function

    





    function json_results_to_array($url){
	
	$linkItems = $this->linkItems;
	
	@$resultsString = file_get_contents($url);
	if(!$resultsString){
	    sleep(.75);
	    $resultsString = file_get_contents($url);
	}
	
	$resultObj = Zend_Json::decode($resultsString);
	
	if(!isset($resultObj["results"])){
	    sleep(.75);
	    $resultsString = file_get_contents($url);
	    $resultObj = Zend_Json::decode($resultsString);
	    if(!isset($resultObj["results"])){
		echo "<br/><br/> NO QUERY RESULT ON: ".$url;
		break;
	    }
	}
	
	foreach($resultObj["results"] as $item){
	    
	    $itemUUID = str_replace("http://opencontext.org/subjects/", "", $item["uri"]);
	    $linkItems[] = $itemUUID;
	}
	
	$this->linkItems = $linkItems;
	
	$nextURL = $resultObj["paging"]["next"];
	if($nextURL != false){
	    $db->closeConnection();
	    $this->db = false;
	    sleep(.25);
	    $this->json_results_to_array($nextURL);
	}
    }




function genus_species_process_prop_todo(){
    $db = $this->db;
    
    $errors = array();
    $name = $this->solrValue;
    
    $sql = "SELECT *
	FROM harvard_links
	WHERE harvard_links.status = 'to do'
	LIMIT 1
	";
    
    $result = $db->fetchAll($sql, 2);
    foreach($result as $row){
	
	$spaceUUID = $row["itemUUID"];
	$genus =  $row["genus"];
	$species = $row["species"];
	$linkLabel = $row["linkedLabel"];
	$linkedURI = $row["linkedURI"];
	
	$propertyQuery = "http://opencontext.org/sets/?proj=Harvard+Peabody+Mus.+Zooarchaeology&cat=Animal+Bone&taxa%5B%5D=Genus";
	$propertyQuery .= "::".urlencode($genus);
	$propertyQuery .= "&taxa%5B%5D=Species::".urlencode($species);
	$queryID = md5($propertyQuery);
	
	
	$sql = "SELECT project_id, archaeoML
	FROM space
	WHERE uuid = '$spaceUUID'
	LIMIT 1;
	";
	
	$resultB = $db->fetchAll($sql, 2);
	if($resultB){
	    
	    $xmlString = $resultB[0]["archaeoML"];
	    $projectID = $resultB[0]["project_id"];
	    
	    $subject = new Subject;
	    $xmlString = $subject->namespace_fix($xmlString);
	    $xmlString = $subject->archaeoml_fix($xmlString);
	    
	    @$xml = simplexml_load_string($xmlString);
	    if($xml){
		unset($xml);
		
		$xmlString = str_replace('<?xml version="1.0"?>', '<?xml version="1.0" encoding="utf-8"?>', $xmlString);
		$doc = new DOMDocument("1.0", "utf-8");
		$doc->loadXML($xmlString);
		
		$xpath = new DOMXpath($doc);
		$xpath->registerNamespace('arch', OpenContext_OCConfig::get_namespace("arch", "spatial"));
		$xpath->registerNamespace('oc', OpenContext_OCConfig::get_namespace("oc", "spatial"));
		
		$propVarRelations = $this->standardVarRels;
		$addLinkedData = true;
			
		$queryDuplicateA = "//oc:linkedData";
		$oldLinks = $xpath->query($queryDuplicateA);
		foreach($oldLinks as $old){
		    $addLinkedData = false;
		}
		
		if($addLinkedData){
		    
		    $query = "//oc:metadata";
		    $metaNodes = $xpath->query($query);
		    foreach($metaNodes as $meta){
			$elementMetadata = $meta; // the new linked data relation will be added to the item's metadata
		    }
		    
		    $elementC = $doc->createElement("oc:linkedData");
		    $elementMetadata->appendChild($elementC);
		    $elementD = $doc->createElement("oc:relationLink");
		    $elementD->setAttribute("localType", "query");
		    $elementC->appendChild($elementD);
		    
		    $elementD->setAttribute("href", $propVarRelations["linkedURI"]);
		    $elementE = $doc->createElement("oc:note");
		    $elementEtext = $doc->createTextNode("Biological taxonomy link to http://www.eol.org assigned on the basis of the values of the 'genus' and the 'species' fields.");
		    $elementE->appendChild($elementEtext);
		    $elementD->appendChild($elementE);
		    
		    $elementE = $doc->createElement("oc:vocabulary");
		    $elementE->setAttribute("href", $propVarRelations["vocabURI"]);
		    $elementEtext = $doc->createTextNode($propVarRelations["vocabulary"]);
		    $elementE->appendChild($elementEtext);
		    $elementD->appendChild($elementE);
		    $elementE = $doc->createElement("oc:label");
		    $elementEtext = $doc->createTextNode($propVarRelations["linkedLabel"]);
		    $elementE->appendChild($elementEtext);
		    $elementD->appendChild($elementE);
		    
		    $elementE = $doc->createElement("oc:targetLink");
		    $elementE->setAttribute("localType", "query");
		    $elementE->setAttribute("localID", $queryID);
		    $elementE->setAttribute("localURI", $propertyQuery);
		    $elementE->setAttribute("href", $linkedURI);
		    
		    $elementF = $doc->createElement("oc:vocabulary");
		    $elementF->setAttribute("href", "http://www.eol.org/");
		    $elementFtext = $doc->createTextNode("Encyclopedia of Life");
		    $elementF->appendChild($elementFtext);
		    $elementE->appendChild($elementF);
		    $elementF = $doc->createElement("oc:label");
		    $elementFtext = $doc->createTextNode($linkLabel);
		    $elementF->appendChild($elementFtext);
		    $elementE->appendChild($elementF);
		    $elementD->appendChild($elementE);
		    $doc->formatOutput = true;
		    $newXMLstring = $doc->saveXML();
		
		    /*
		    header("Content-type: application/xml");
		    echo $newXMLstring;
		    */
		    
		    
		    $response = false;
		    if(strlen($newXMLstring)> strlen($xmlString)){
			try{
			    @$response = OpenContext_NewDocs::spaceAdd($newXMLstring, true);
			}catch(Exception $e) {
			    $errors[] = "Failure on: ".$spaceUUID;
			    $this->errors = $errors;
			    //echo $e;
			    //break;
			}
		    }
		    //$this->lastItem = OpenContext_NewDocs::spaceAdd($newXMLstring, true);
		    $this->lastXML = $newXMLstring;
		    
		    $data = array("status" => "done");
		    $where = array();
		    $where = "itemUUID = '$spaceUUID' ";
		    
		    if($response != false){
			$db->update("harvard_links", $data, $where);
			$this->doneCount ++;
		    }
		    else{
			$data = array("status" => "ERROR");
			$db->update("harvard_links", $data, $where);
			$errors[] = "Note of failure on: ".$spaceUUID;
			$this->errors = $errors;
		    }
		    
		    unset($doc);
		    unset($newXMLstring);
		    unset($subject);
		    unset($xmlString);
		    //break;
		    
		    //save the query with an identifier to link to the linked data table
		   
		    $qlinkData = array("hashID" => $queryID,
				       "query" => $propertyQuery
				       );
		    
		    try{
			$db->insert("query_links" , $qlinkData);
		    }catch(Exception $e) {
			// don't worry 
		    }
		    
		    
		    $sql = "SELECT * FROM linked_data WHERE itemUUID = '".$queryID."' LIMIT 1;";
			    $resultVal = $db->fetchAll($sql, 2);
			    if(!$resultVal){
				$dataVal = array("hashID" => md5($queryID."_".$linkedURI),
						 "fk_project_uuid" => $projectID,
						 "source_id" => "solr",
						 "itemUUID" => $queryID,
						 "itemType" => "query",
						 "linkedLabel" => $linkLabel,
						 "linkedURI" => $linkedURI,
						 "vocabulary" => "Encyclopedia of Life",
						 "vocabURI" => "http://www.eol.org/"
						 );
				$db->insert("linked_data", $dataVal);
			    }
		    
		    
	
		}
		else{
		    //item already has linked data
		    
		    
		}
		
	    }
	    else{
		// XML error!
		
	    }
	}
	else{
	    // spatial item not found!
	    
	}
	
    }

}//end function










/*

SELECT COUNT(  `itemUUID` ) ,  `status` 
FROM  `prop_docs_log` 
GROUP BY  `status` 
ORDER BY COUNT(  `itemUUID` ) DESC 
LIMIT 0 , 150

*/



    function security_check($input){
        $badArray = array("DROP", "SELECT", "#", "--", "DELETE", "INSERT", "UPDATE", "ALTER", "=");
        foreach($badArray as $bad_word){
            if(stristr($input, $bad_word) != false){
                $input = str_ireplace($bad_word, "XXXXXX", $input);
            }
        }
        return $input;
    }
    
}
