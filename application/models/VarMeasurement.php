<?php

/*
This class creates or updates linked data relationships for properties
it adds or updates new XML to the property description and to spatial items
*/

class VarMeasurement {
    
    public $db;
    public $variableUUID;
    
    public $numItems;
    public $doneCount;
    public $message;
    public $errors;
    
    public $lastItem;
    public $lastXML;
    
    
       //initiallize the database
    public function initialize($db = false){
	
	$this->variableUUID = false;
	$this->doneCount = 0;
	
	if(!$db){
            $db_params = OpenContext_OCConfig::get_db_config();
            $db = new Zend_Db_Adapter_Pdo_Mysql($db_params);
            $db->getConnection();
	    $this->setUTFconnection($db);
        }
        
        $this->db = $db;
    }
    
    
    function process_spaceItems(){
	$db = $this->db;
	$errors = array();
	
	$mesObj = new MeasurementUnits;
	
	$sql = "SELECT uuid
	FROM mspace
	WHERE done = 0
	;";
	
	
	$result = $db->fetchAll($sql, 2);
        foreach($result as $row){
		
	    $spaceUUID = $row["uuid"];
	    //echo "<h2>Space: $spaceUUID </h2>";
	    $sql = "SELECT archaeoML
		FROM space
		WHERE uuid = '$spaceUUID'
		Limit 1;
		";
		
	    $resultB = $db->fetchAll($sql, 2);
	    if(!$resultB){
		$errors[] = "Space record not found: ".$spaceUUID;
		$this->errors = $errors;
	    }
	    else{
		$xmlString = $resultB[0]["archaeoML"];
		
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
		    
		    $change = false;
		    $queryA = "//arch:property";
		    $props = $xpath->query($queryA);
		    foreach($props as $prop){
			
			
			$variableUUID = false;
			$queryB = "arch:variableID";
			$varIDs = $xpath->query($queryB, $prop);
			foreach($varIDs as $varID){
			    $variableUUID = $varID->nodeValue;
			}
			
			$sql = "SELECT unitURI
			FROM var_tab
			WHERE variable_uuid = '$variableUUID'
			AND unitURI != ''
			LIMIT 1
			";
			
			$resultC = $db->fetchAll($sql, 2);
			if($resultC){
			    
			    $unitURI = $resultC[0]["unitURI"];
			    $mArray = $mesObj->URI_toUnit($unitURI); 
			    
			    //echo "<br/><br/>".$variableUUID. " Unit: ".$unitURI." (Name: ".$mArray["name"]." Abrv: ".$mArray["abrv"].")";
			    $queryC = "arch:decimal | arch:integer";
			    $vals = $xpath->query($queryC, $prop);
			    foreach($vals as $val){
				
				$val->setAttribute("href", $unitURI);
				$val->setAttribute("name", $mArray["name"]);
				$val->setAttribute("abrv", $mArray["abrv"]);
				$change = true;
				//echo "<br/> Value: ".$val->nodeValue;
			    }   
			    
			}//end case with a measurement unit
			
		    }//end loop through props
		    
		    if($change){
			$doc->formatOutput = true;
			$newXMLstring = $doc->saveXML();
			$data = array("archaeoML" => $newXMLstring);
			$where = array();
			$where[] = "uuid = '".$spaceUUID ."' ";
			$db->update("space", $data, $where);
			
			unset($data);
			$data = array("done" => 1);
			$db->update("mspace", $data, $where);
			//header("Content-type: application/xml");
			//echo $newXMLstring;
		    }//end case with changing the xml
		    
		}//end case with valid XML
	    }//end case with space id found
	}//end loop through all space ids to be updated
    }//end function
			
/*
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





*/




    function setUTFconnection($db){
	    $sql = "SET collation_connection = utf8_unicode_ci;";
	    $db->query($sql, 2);
	    $sql = "SET NAMES utf8;";
	    $db->query($sql, 2);
    } 


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
