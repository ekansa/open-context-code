<?php

class dbXML_dbProperties  {
     
    public $properties; //array of properties
    public $notes; //array of item notes
    
    public $dbName;
    public $dbPenelope;
    public $db;
    
    
    
    
    public function initialize($db = false){
        if(!$db){
            $db_params = OpenContext_OCConfig::get_db_config();
            $db = new Zend_Db_Adapter_Pdo_Mysql($db_params);
            $db->getConnection();
        }
        
        $this->db = $db;
        $this->properties = false;
		  $this->notes = false;
    }
    
    public function getProperties($id, $obsNumbers = false){
        
		  if(!is_array($obsNumbers)){
				$obsNumbers = array(0 => false);
		  }
		  
		  foreach($obsNumbers as $obs){
				
				if(strlen($obs)<1){
					 $obs = false;
				} 
				$this->getPropsByObs($id, $obs);
				$this->getNotesByObs($id, $obs);
		  }
    }
    
    
    
     public function getPropsByObs($id, $obs = false){
    
        $db = $this->db;
		  
		  if(!$obs){
				$obsTerm = "";
		  }
		  else{
				$obsTerm = "AND observe.obs_num = $obs ";
		  }
		  
		  $sql = "SELECT properties.property_uuid, 
			 properties.val_num, 
			 '' as xml_date, 
			 var_tab.var_label, 
			 val_tab.val_text, 
			 IF (
			 val_tab.val_text IS NULL , (
				 IF (
				 properties.val_num =0, properties.val_num, properties.val_num)
				 ), 
				 val_tab.val_text
				 ) AS allprop, 
			 var_tab.var_type, 
			 var_tab.variable_uuid, 
			 val_tab.value_uuid,
			 var_tab.sort_order,
			 var_tab.hideLink
		 
		 FROM observe
		 LEFT JOIN properties ON observe.property_uuid = properties.property_uuid
		 LEFT JOIN var_tab ON properties.variable_uuid = var_tab.variable_uuid
		 LEFT JOIN val_tab ON properties.value_uuid = val_tab.value_uuid
		 LEFT JOIN var_notes ON var_tab.variable_uuid = var_notes.variable_uuid
		 WHERE observe.subject_uuid = '$id' AND properties.variable_uuid <> 'NOTES'
		 $obsTerm
		 ORDER BY var_tab.sort_order, var_notes.field_num
		 
		 ";
	 
	
		  //echo $sql;
	
        $result = $db->fetchAll($sql, 2);
		  $result = false;
        if($result){
	    
				if(!$obs){
					 $obsNum = 1;
				}
				else{
					 $obsNum = $obs;
				}
	    
            $properties = $this->properties;
				if(!is_array($properties)){
					 $properties = array();
					 $properties[$obsNum] = array();
				}
				else{
					 if(!array_key_exists($obsNum, $properties)){
						  $properties[$obsNum] = array();
					 }
				}
	    
				foreach($result as $row){
			  
					 $propertyUUID = $row["property_uuid"];
					 $varUUID = $row["variable_uuid"];
					 $varLabel = $row["var_label"];
					 $varType = strtolower($row["var_type"]);
					 $varSort = $row["sort_order"] + 0;
					 $hideLink = $row["hideLink"];
					 
					 if($hideLink == 1){
						  $hideLink = true;
					 }
					 else{
						  $hideLink = false;
					 }
					 
					 $valUUID = $row["value_uuid"];
					 if(strlen($row["val_num"])>0){
						  $valueNum = $row["val_num"] + 0;
					 }
					 else{
						  $valueNum = false;
					 }
					 
					 $valueDate = $row["xml_date"];
					 $showVal = $row["allprop"];
					 
					 $showVal = html_entity_decode($showVal, ENT_QUOTES, 'UTF-8');
					 //$showVal = str_replace("\r\n", "<br/>", $showVal);
					 if(strlen($showVal)>140){
						  $varType = "alphanumeric";
					 }
					 
					 if(stristr($varType, "calend")){
						  $cal_test_string = str_replace("/", "-", $showVal);
						  if (($timestamp = strtotime($cal_test_string)) === false) {
								$calendardTest = false;
						  }
						  else{
								$calendardTest = true;
						  }
						 
						  if($calendardTest && strlen($valueDate)<1){
								$valueDate = date("Y-m-d", strtotime($cal_test_string));
						  }
						  else{
								$valueDate = false;
						  }
					 }
					 else{
						  $valueDate = false;
					 }
					 
					 $xmlNote = "<div>".chr(13);
					 $xmlNote .= $showVal.chr(13);
					 $xmlNote .= "</div>".chr(13);
					 @$xml = simplexml_load_string($xmlNote);
					 if($xml){
						  $validForXML = true;
					 }
					 else{
						  $validForXML = false;
					 }
					 unset($xml);
					 
					 //get linked data relations for the variable and the property
					 $varUnitsData = $this->pen_getUnitData($varUUID);
					 $linkedDataVar = $this->pen_getLinkedData($varUUID);
					 $linkedDataProp = $this->pen_getLinkedData($propertyUUID);
					 
					 $actPropArray = array("propertyUUID" => $propertyUUID,
									"varUUID" => $varUUID,
									"varLabel" => $varLabel,
									"varType" => $varType,
									"varSort" => $varSort,
									"valUUID" => $valUUID,
									"valueNum" => $valueNum,
									"valueDate" => $valueDate,
									"showVal" => $showVal,
									"validForXML" => $validForXML,
									"varLinkedData" => $linkedDataVar,
									"varUnitsData" => $varUnitsData,
									"propLinkedData" => $linkedDataProp,
									"hideLink" => $hideLink
									);
					 
					 if(!array_key_exists($propertyUUID, $properties[$obsNum]) && $varUUID != false){
						  $properties[$obsNum][$propertyUUID] = $actPropArray;
					 }
			  
				}//end loop
				
				$this->properties = $properties;
		  }
        
        
    } //end function
    
    
    
    
    public function pen_getLinkedData($itemUUID){
		$db = $this->db;
		
		$output = false;
		$sql = "SELECT linked_data.linkedLabel, linked_data.linkedURI, linked_data.vocabulary, 	linked_data.vocabURI
		FROM linked_data
		WHERE linked_data.itemUUID = '$itemUUID' AND linked_data.linkedType = 'type' ";
		
		$result = $db->fetchAll($sql, 2);
			if($result){
			$output = array();
			$output = $result[0];
		}
		
		return $output;
    }
    
	
	public function pen_getUnitData($itemUUID){
		$db = $this->db;
		
		$output = false;
		$sql = "SELECT linked_data.linkedLabel, linked_data.linkedURI, linked_data.linkedAbrv
		FROM linked_data
		WHERE linked_data.itemUUID = '$itemUUID' AND linked_data.linkedType = 'unit' ";
		
		$result = $db->fetchAll($sql, 2);
		if($result){
			$output = array();
			$output = $result[0];
		}
		
		return $output;
    }
	
	
	
    
    
    
    public function getNotesByObs($id, $obs = false){
    
        $db = $this->db;
	
	
		  if($this->dbPenelope){
				
				if(!$obs){
					 $obsTerm = "";
				}
				else{
					 $obsTerm = "AND observe.obs_num = $obs ";
				}
				
				$sql = "SELECT DISTINCT val_tab.val_text, properties.property_uuid, properties.value_uuid
			  FROM observe
			  LEFT JOIN properties ON observe.property_uuid = properties.property_uuid
			  LEFT JOIN val_tab ON properties.value_uuid = val_tab.value_uuid
			  WHERE observe.subject_uuid = '$id' AND properties.variable_uuid = 'NOTES'
			  $obsTerm
			  ";
		  }
		  else{
				
				if(!$obs){
					 $obsTerm = "";
				}
				else{
					 $obsTerm = "AND observe.obs_num = $obs ";
				}
				
				$sql = "SELECT DISTINCT val_tab.val_text, properties.property_uuid, properties.value_uuid
			  FROM observe
			  LEFT JOIN properties ON observe.property_uuid = properties.property_uuid
			  LEFT JOIN val_tab ON properties.value_uuid = val_tab.value_uuid
			  WHERE observe.subject_uuid = '$id' AND properties.variable_uuid = 'NOTES'
			  $obsTerm
			  ";
				
		  }
		  
		  //echo $sql;
		  
		  $result = $db->fetchAll($sql, 2);
		  if($result){
				
				if(!$obs){
					 $obsNum = 1;
				}
				else{
					 $obsNum = $obs;
				}
				
				$notes = $this->notes;
				if(!is_array($notes)){
					 $notes= array();
					 $notes[$obsNum] = array();
				}
				else{
					 if(!array_key_exists($obsNum, $notes)){
						  $notes[$obsNum] = array();
					 }
				}
				
				foreach($result as $row){
			  
					 $propertyUUID = $row["property_uuid"];
					 $valUUID = $row["value_uuid"];
					 $noteText = $row["val_text"];
					 $xmlNote = "<div>".chr(13);
					 $xmlNote .= $noteText.chr(13);
					 $xmlNote .= "</div>".chr(13);
					 @$xml = simplexml_load_string($xmlNote);
					 if($xml){
						  $validForXML = true;
					 }
					 else{
						  $validForXML = false;
					 }
					 unset($xml);
					 
					 $actNoteArray = array("propertyUUID" => $propertyUUID,
									 "valueUUID"=> $valUUID,
									 "noteText" => $noteText,
									 "validForXML" => $validForXML);
					 
					 if(!array_key_exists($propertyUUID, $notes[$obsNum])){
						  $notes[$obsNum][$propertyUUID] = $actNoteArray;
					 }
			  
				}//end loop
				
				$this->notes = $notes;
		  }
        
        
    } //end function
    
    
    
}  
