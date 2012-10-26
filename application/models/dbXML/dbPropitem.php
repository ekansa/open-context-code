<?php

class dbXML_dbPropitem  {
    
    public $itemUUID;
    public $label;
    public $projectUUID;
    public $sourceID;
    
    /*
    Property specific
    */
    public $queryVal; //URL encoded text for how to query for this property
    public $queryPrefix; //URL encoded text for query prefix, used to make graphs.
    
    public $propDescription;
    public $propDesXMLok;
    public $propLinkLabel; //label for the related concept linked by URI for the property
    public $propLinkURI; //URI to related concept for the property
    public $propLinkVocab; //name of the vocabulary for the linked concept
    public $propLinkVocabURI; //URI to the vocabulary for the linked concept
    
    public $varUUID;
    public $varLabel;
    public $varSort;
    public $varType;
    public $varDescription;
    public $varDesXMLok;
    public $varLinkLabel; //label for the related concept linked by URI for the variable
    public $varLinkURI; //URI to related concept for the variable
    public $varLinkVocab; //name of the vocabulary for the linked concept
    public $varLinkVocabURI; //URI to the vocabulary for the linked concept
    
	 public $varUnitURI; //uri for the measurement unit
	 public $varUnitName; //name of the measurement unit
	 public $varUnitAbrv; //abreviation for the measurement unit
	
    public $valUUID;
    public $value;
    public $valNumeric;
    public $valCalendric;
    
    public $varSummary; //array usage summary for the variable
    public $frequencyRanks; //array of frequency ranking for this property
    
    
    public $propertiesObj; //object for properties
    public $linksObj; // object for links
    public $metadataObj; //object for metadata
    
    private $pen_obsItemTypes = array("space" => "spatial",
				  "media" => "media",
				  "project" => "project",
				  "person" => "person",
				  "document" => "diary");
    
     private $oc_obsItemTypes = array("space" => "%spatial%",
				  "media" => "%resource%",
				  "project" => "%project%",
				  "person" => "%person%",
				  "document" => "%diary%");
    
    public $dbName;
    public $dbPenelope;
    public $db;
    
    public function initialize($db = false){
        if(!$db){
            $db_params = OpenContext_OCConfig::get_db_config();
            $db = new Zend_Db_Adapter_Pdo_Mysql($db_params);
            $db->getConnection();
				$this->setUTFconnection($db);
        }
        
        $this->db = $db;
	
		  $this->propDescription = false;
		  $this->propDesXMLok = false;
		  $this->propLinkLabel = false;
		  $this->propLinkURI = false;
		  $this->propLinkVocab = false;
		  $this->propLinkVocabURI = false;
			
		  $this->varUUID = false;
		  $this->varLabel = false;
		  $this->varSort = false;
		  $this->varType = false;
		  $this->varDescription = false;
		  $this->varDesXMLok = false;
		  
		  $this->varUnitURI = false;
		  $this->varUnitName = false;
		  $this->varUnitAbrv = false;
		  
		  $this->varLinkLabel = false;
		  $this->varLinkURI = false;
		  $this->varLinkVocab = false;
		  $this->varLinkVocabURI = false;
		  
		  $this->valUUID = false;
		  $this->value = false;
		  $this->valNumeric = false;
		  $this->valCalendric = false;
		  
		  $this->varSummary = false;
		  $this->frequencyRanks = false;
		  
		  $this->propertiesObj = false;
		  $this->linksObj = false;
		  $this->metadataObj = false;
    }
    
    public function getByID($id){
        
        $this->itemUUID = $id;
        $found = false;
        
        if($this->dbPenelope){
            $found = $this->pen_itemGet();
				if($found){
					 $this->pen_getVarDescription();
				}
        }
        else{
            $found = $this->oc_itemGet();
        }
        
        return $found;
    }
    
    public function pen_itemGet(){
        $found = false;
        $db = $this->db;
        
        $sql = "SELECT *
        FROM properties
		  Left JOIN val_tab ON properties.value_uuid = val_tab.value_uuid
		  JOIN var_tab ON properties.variable_uuid = var_tab.variable_uuid
        WHERE properties.property_uuid = '".$this->itemUUID."' ";
        
        $result = $db->fetchAll($sql, 2);
        if($result){
				$found = true;
				$this->projectUUID = $result[0]["project_id"];
				$this->sourceID = $result[0]["source_id"];
				$this->value =  html_entity_decode($result[0]["val_text"], ENT_QUOTES, 'UTF-8');
				$this->varLabel =  html_entity_decode($result[0]["var_label"], ENT_QUOTES, 'UTF-8');
	    
				$this->label = ($this->varLabel.": ".$this->value);
				
				$this->varType = strtolower($result[0]["var_type"]);
				$this->varUUID = $result[0]["variable_uuid"];
				$this->valUUID = $result[0]["value_uuid"];
				if($result[0]["val_num"]){
					 $this->valNumeric = $result[0]["val_num"] + 0;
				}
				$this->calendarValueValidate();
				
				if(strlen($result[0]["var_sum"])>10){
					 $this->varSummary = Zend_Json::decode($result[0]["var_sum"]);
				}
	    
				$unitData = $this->pen_getUnitData($this->varUUID);
				if(is_array($unitData)){
					
					$this->varUnitURI = $unitData["linkedURI"];
					$this->varUnitName = $unitData["linkedLabel"];
					$this->varUnitAbrv = $unitData["linkedAbrv"];
				}
		
		
				$linkedData = $this->pen_getLinkedData($this->varUUID);
				if(is_array($linkedData)){
					 $this->varLinkLabel = $linkedData["linkedLabel"];
					 $this->varLinkURI = $linkedData["linkedURI"];
					 $this->varLinkVocab = $linkedData["vocabulary"];
					 $this->varLinkVocabURI = $linkedData["vocabURI"];
				}
	    
				$linkedData = $this->pen_getLinkedData($this->itemUUID);
				if(is_array($linkedData)){
					 $this->propLinkLabel = $linkedData["linkedLabel"];
					 $this->propLinkURI = $linkedData["linkedURI"];
					 $this->propLinkVocab = $linkedData["vocabulary"];
					 $this->propLinkVocabURI = $linkedData["vocabURI"];
				}
				
				$this->propDescription = $result[0]["note"];
				$xmlNote = "<div>".chr(13);
				$xmlNote .= $this->propDescription.chr(13);
				$xmlNote .= "</div>".chr(13);
				
				@$xml = simplexml_load_string($xmlNote);
				if($xml){
					 $this->propDesXMLok = true;
				}
        }
        
        return $found;
    }
    
    public function oc_itemGet(){
        $found = false;
        $db = $this->db;
        return $found;
    }
    
    public function calendarValueValidate(){
		  if(stristr($this->varType, "calend")){
				$this->varType = "calendric";
				$cal_test_string = str_replace("/", "-", $this->value);
				if (($timestamp = strtotime($cal_test_string)) === false) {
					 $this->valCalendric = false;
				}
				else{
					 $this->valCalendric = date("Y-m-d\TH:i:s\-07:00", strtotime($cal_test_string));
				}
		  }
		  else{
				$this->valCalendric = false;
		  }
    }//end function
    
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
	
	
	
    
    public function pen_getVarDescription(){
		  $db = $this->db;
		  
		  $sql = "SELECT * FROM var_notes WHERE variable_uuid = '".$this->varUUID."'";
		  
		  $result = $db->fetchAll($sql, 2);
				 if($result){
				$this->varDescription = $result[0]["note_text"];
				
				$xmlNote = "<div>".chr(13);
				$xmlNote .= $this->varDescription.chr(13);
				$xmlNote .= "</div>".chr(13);
				
				@$xml = simplexml_load_string($xmlNote);
				if($xml){
					 $this->varDesXMLok = true;
				}
		  }
	
    }//end function
    
    
    
    public function propertySummary(){
	
		  $subjectTypeArray = $this->pen_obsItemTypes;  
		  $varType = $this->varType;
		  
		  if($varType == "integer" || $varType == "decimal" || stristr($varType, "calend")){
				foreach($subjectTypeArray as $subjectType => $value){
					 $this->getNumericSummary($subjectType);
				}
		  }
		  elseif($varType == "boolean" || $varType == "ordinal" || stristr($varType, "nominal")){
				
				if(!is_array($this->varSummary)){
					 //no var summary data, create it.
					 
					 //do this for each type of item
					 foreach($subjectTypeArray as $subjectType => $value){
						  $this->getNomimalSummary($subjectType);
					 }
					
					 //once var summary data is created, save it for use next time
					 $db = $this->db;
					 $where = array();
					 $where[] = "variable_uuid = '".$this->varUUID."' ";
					 $varSummaryJSON = Zend_Json::encode($this->varSummary);
					 $data = array("var_sum" => $varSummaryJSON);
					 if($this->dbPenelope){
						  $db->update("var_tab", $data, $where);
					 }
					 else{
						  $db->update("var_tab", $data, $where);
					 }
				}//end case without var_summary data created earlier
				else{
			  
					 $varSummary = $this->varSummary;
					 $frequencyRanks = array();
					 foreach($subjectTypeArray as $subjectType => $value){
						  if(array_key_exists($subjectType, $varSummary)){
								if(array_key_exists("props", $varSummary[$subjectType])){
									 $propArray = $varSummary[$subjectType]["props"];
									 if(array_key_exists($this->itemUUID, $propArray)){
										  $frequencyRanks[$subjectType] = $propArray[$this->itemUUID];
									 }
								}
						  }
					 }//end loop through subject types
					 $this->frequencyRanks = $frequencyRanks;
			  
				}//end case with var_summary data created earlier
				
		  }//end case for nomimal, boolean, ordinal
	
    }//end function
    
    
    //gets the appropriate subject type for queries of observations
    public function getQuerySubjectType($subjectType, $forceOC = false){
		  if($this->dbPenelope && !$forceOC){
				$subjectTypeArray = $this->pen_obsItemTypes;  
		  }
		  else{
				$subjectTypeArray = $this->oc_obsItemTypes;
		  }
		  
		  if(array_key_exists($subjectType, $subjectTypeArray)){
				return $subjectTypeArray[$subjectType];
		  }
		  else{
				return $subjectTypeArray["space"]; //default to space
		  }
    }//end funciton
    
    
    //get summary of 
    public function getNumericSummary($subjectType){
	
		  $db = $this->db;
		  if(!is_array($this->frequencyRanks)){
				$frequencyRanks = array();
		  }
		  else{
				$frequencyRanks = $this->frequencyRanks;
		  }
		  
		  if(!is_array($this->varSummary)){
				$varSummary = array();
		  }
		  else{
				$varSummary = $this->varSummary;
		  }
		  
		  $querySubjectType = $this->getQuerySubjectType($subjectType);
		  $altQuerySubjectType = $this->getQuerySubjectType($subjectType, true);
		  
		  $varType = $this->varType;
	 
				/*
					  $sql = "SELECT count(observe.property_uuid) AS prop_count,
							properties.property_uuid,
							properties.val_num, val_tab.val_text AS val_date
							FROM properties
							JOIN observe ON (observe.property_uuid = properties.property_uuid
							AND (observe.subject_type LIKE '$querySubjectType' OR observe.subject_type LIKE '$altQuerySubjectType' ))
			  LEFT JOIN val_tab ON  val_tab.value_uuid = properties.value_uuid
							WHERE properties.variable_uuid = '".$this->varUUID."'
							GROUP BY properties.property_uuid
							ORDER BY prop_count DESC;
							";
				*/			
				$sql = "SELECT count(observe.property_uuid) AS prop_count,
			  properties.property_uuid,
			  properties.val_num, val_tab.val_text AS val_date
			  FROM properties
			  JOIN observe ON (observe.property_uuid = properties.property_uuid
			  AND (observe.subject_type = '$querySubjectType'))
			  LEFT JOIN val_tab ON  val_tab.value_uuid = properties.value_uuid
				WHERE properties.variable_uuid = '".$this->varUUID."'
				GROUP BY observe.property_uuid
				ORDER BY prop_count DESC;
				";
	
		  $result = $db->fetchAll($sql, 2);
		  //$result = false;
		  //$varSummary[$subjectType] = $sql;
		  //$this->varSummary = $varSummary;
        if($result){
				$freqRank = 1;
				$varTotalObs = 0; //total number of observations associated with this variable
				$varUniquePropCount = count($result); //count of the number of unque values associated with this variable
				$totalCombinedValues = 0; //all values added together
			
			
				if(stristr($varType, "calend")){
					 $actPropVal = $this->dateStringToTime($this->valCalendric);
					 $minValue = $this->dateStringToTime($result[0]["val_date"]);
					 $maxValue = $minValue;
					 //$minValue = $this->dateStringToTime("3000-01-01");
					 //$maxValue = $this->dateStringToTime("1800-01-01");
				}
				else{
					 $actPropVal = $this->valNumeric;
					 $minValue = $result[0]["val_num"];
					 $maxValue = $result[0]["val_num"];
					 //$minValue = 10000000*1000000;
					 //$maxValue = -(10000000*1000000);
				}
			
			
				$valueArray = array();
				foreach($result as $row){
				
					 $propCount = $row["prop_count"];
					 $varTotalObs = $varTotalObs + $propCount;
					 
					 if(stristr($varType, "calend")){
						 //convert date strings to numeric values for numeric operations
						  $actValue = $this->dateStringToTime($row["val_date"]);
						 
						  if(!$actValue){
								$excelDate = $this->excel_date($row["val_date"]);
								$actValue = $excelDate;
						  }
						 
					 }
					 else{
						  $actValue = $row["val_num"];
					 }
					 
					 if($actValue < $minValue){
						  $minValue = $actValue +0;
					 }
					 if($actValue > $maxValue){
						  $maxValue = $actValue +0;
					 }
					 
					 $totalCombinedValues = $totalCombinedValues + ($propCount * $actValue);
					 
					 if($this->itemUUID == $row["property_uuid"]){
						  $frequencyRanks[$subjectType] = $freqRank;
						  $this->frequencyRanks = $frequencyRanks;
					 }
					 
					 if(!array_key_exists($actValue, $valueArray)){
						  $valueArray[$actValue] = $propCount; 
					 }
					 else{
						  $valueArray[$actValue] = $valueArray[$actValue] + $propCount; 
					 }
					 
					 if($this->itemUUID == $row["property_uuid"] || $actPropVal == $actValue){
						  $frequencyRanks[$subjectType] = array("rank" => $freqRank, "count" => $valueArray[$actValue]);
						  $this->frequencyRanks = $frequencyRanks;
					 }
		
				$freqRank++; //increment up the frequency rank
				}//end loop though all values
			
			
				//number of histogram intervals to generate
				$numIntervals = round(($varTotalObs / ($varUniquePropCount)),0);
				if($numIntervals > $varUniquePropCount){
					 $numIntervals = $varUniquePropCount;
				}
							
				if($numIntervals <= 3){
					 $numIntervals = round($varUniquePropCount/10,0); 
					 if($numIntervals < 3){
						  $numIntervals = 3;
					 }
				}
						
				if($numIntervals > 15){
					 $numIntervals = 15;
				}
				$valueRange = $maxValue - $minValue;
				$intervalRange = $valueRange / $numIntervals;
				$lowVal = $minValue;
				$highVal = $lowVal + $intervalRange;
				
				$histogram = array();
				$actInterval = array("lowVal" => $lowVal,
						 "highVal"=> $highVal,
						 "count" => 0);
			
				ksort($valueArray); //sort smallest to largest values
				$intCount = 1;
				$intCountTotal = count($valueArray);
				foreach($valueArray as $valKey => $valCount){
					 if($valKey >= $lowVal && $valKey < $highVal && $valKey < $maxValue){
						  $actInterval["count"] = $actInterval["count"] + $valCount;
					 }
					 
					 
					 if($valKey == $maxValue){
						  $actInterval["count"] = $actInterval["count"] + $valCount;
						  $actInterval["highVal"] = $maxValue;
						  $histogram[] = $actInterval;
						  unset($actInterval);
						  break;
					 }
					 
					 if($valKey >= $highVal){
						  $histogram[] = $actInterval;
						  unset($actInterval);
						  $lowVal = $highVal;
						  $highVal = $lowVal + $intervalRange;
						  if($highVal>$maxValue){
							  $highVal = $maxValue;
						  }
						  if($intCount == $intCountTotal){
							  //last interval ends in highest value
							  $highVal = $maxValue;
						  }
						 
						  $actInterval = array("lowVal" => $lowVal,
							  "highVal"=> $highVal,
							  "count" => $valCount+0);
					 }
					 
				}//end loop
				
				if($varTotalObs != 0){
					 $average = $totalCombinedValues  / $varTotalObs;
				}
				else{
					 $average = false;
				}
			
				if(stristr($varType, "calend")){
				
					 $dateFormat = "Y-m-d";
					 
					 $minValue = date($dateFormat, $minValue);
					 $maxValue = date($dateFormat, $maxValue);
					 $average = date($dateFormat, $average);
					 
					 $newHisto = array();
					 foreach($histogram as $interval){
						  $newInterval = array("lowVal" => date($dateFormat, $interval["lowVal"]),
									"highVal" => date($dateFormat, $interval["highVal"]),
									"count" => $interval["count"]);
						  $newHisto[] = $newInterval;
					 }
					 unset($histogram);
					 $histogram = $newHisto;
					 unset($newHisto);
				}
				else{
				
					 $newHisto = array();
					 foreach($histogram as $interval){
						 
						 $sql = "SELECT count(observe.subject_uuid) AS sub_count
								 FROM properties
								 JOIN observe ON (observe.property_uuid = properties.property_uuid
								 AND (observe.subject_type = '$querySubjectType'))
								 WHERE properties.variable_uuid = '".$this->varUUID."'
								 AND properties.val_num >= ".$interval["lowVal"]." AND properties.val_num <= ".$interval["highVal"]."
								 GROUP BY properties.variable_uuid
								 ";
						 
						  //echo "<br/>".$sql;
						  $res = $db->fetchAll($sql, 2);
						  if($res){
								 $interval["count"] = $res[0]["sub_count"];
								 $newHisto[] = $interval;
						  }
					 }
					 unset($histogram);
					 $histogram = $newHisto;
					 unset($newHisto);
	 
				}
			
				$varSummary[$subjectType] = array("varTotalObs" => $varTotalObs,
								  "uniqueCount" => $varUniquePropCount,
								  "min" => $minValue,
								  "max" => $maxValue,
								  "average" => $average,
								  "histogram" => $histogram);
				
				$this->varSummary = $varSummary;
		  }//end case with results
	
    }//end function
    
    
    public function dateStringToTime($dateString){
	
		  $cal_test_string = str_replace("/", "-", $dateString);
		  if (($timestamp = strtotime($cal_test_string)) === false) {
				return false;
		  }
		  else{
				return strtotime($cal_test_string);
		  }
    }
    
    
    public function getNomimalSummary($subjectType){
	
		  $db = $this->db;
		  if(!is_array($this->frequencyRanks)){
				$frequencyRanks = array();
		  }
		  else{
				$frequencyRanks = $this->frequencyRanks;
		  }
		  
		  if(!is_array($this->varSummary)){
				$varSummary = array();
		  }
		  else{
				$varSummary = $this->varSummary;
		  }
		  
		  $querySubjectType = $this->getQuerySubjectType($subjectType);
		  $altQuerySubjectType = $this->getQuerySubjectType($subjectType, true);
		  
		  $sql = "SELECT count(observe.property_uuid) AS prop_count,
								 properties.property_uuid,
								 properties.val_num, val_tab.val_text, val_tab.value_uuid
								 FROM properties
								 JOIN observe ON (observe.property_uuid = properties.property_uuid
								 AND (observe.subject_type = '$querySubjectType' ))
								 JOIN val_tab ON  val_tab.value_uuid = properties.value_uuid
								 WHERE properties.variable_uuid = '".$this->varUUID."'
								 GROUP BY val_tab.val_text
								 ORDER BY prop_count DESC;
								 ";  
	
		  $result = $db->fetchAll($sql, 2);
		  //$result = false;
		  //$varSummary[$subjectType] = $sql;
		  //$this->varSummary = $varSummary;
        if($result){
				$freqRank = 1;
				$varTotalObs = 0; //total number of observations associated with this variable
				$varUniquePropCount = count($result); //count of the number of unque values associated with this variable
				$valueArray = array();
				$propRanks = array();

				foreach($result as $row){
					 $propCount = $row["prop_count"] +0 ;
					 $varTotalObs = $varTotalObs + $propCount;
					 $propertyUUID =  $row["property_uuid"];
					 if($propertyUUID == $this->itemUUID){
						  $frequencyRanks[$subjectType] = array("rank" => $freqRank, "count" =>$propCount);
						  $this->frequencyRanks = $frequencyRanks;
					 }
					 
					 $valueText = html_entity_decode($row["val_text"], ENT_QUOTES, 'UTF-8');
					 $valueUUID= $row["value_uuid"];
					 if($freqRank <= 15){
						  $valueArray[$propertyUUID] = array("valueUUID" => $valueUUID,
									"text" => $valueText,
									"rank" => $freqRank,
									"count" => $propCount);
					 }
					 
					 $propRanks[$propertyUUID] = array("rank" => $freqRank, "count" =>$propCount);
			  
				$freqRank++;
				}//end loop through results
	
	
				$varSummary[$subjectType] = array("varTotalObs" => $varTotalObs,
								  "uniqueCount" => $varUniquePropCount,
								  "nominalGraph" => $valueArray,
								  "props" => $propRanks);
	    
				$this->varSummary = $varSummary;
	
		  }//end case with a result
    }//end function
    
    
    
    //needs metadata object created!
    public function makeQueryVal(){
		  $metadataObj = $this->metadataObj;
		  $projectName = $metadataObj->projectName;
		  //$projectName = "Pınarbaşı 1994: Animal Bones"; //check UTF-8 
		  if($this->varType != "alphanumeric"){
				$this->queryPrefix = "proj=".urlencode($projectName)."&taxa%5B%5D=".urlencode($this->varLabel);
				$this->queryVal = ($this->queryPrefix).urlencode("::".$this->value);
				
		  }
		  else{
				$this->queryPrefix = "proj=".urlencode($projectName)."&taxa%5B%5D=".urlencode($this->varLabel);
				$this->queryVal = ($this->queryPrefix)."&q=".urlencode(substr($this->value, 0, 30));
		  }
    }
    
    
    
    function excel_date($serial){
        //from http://richardlynch.blogspot.com/2007/07/php-microsoft-excel-reader-and-serial.html
        // Excel/Lotus 123 have a bug with 29-02-1900. 1900 is not a
        // leap year, but Excel/Lotus 123 think it is...
        if ($serial == 60) {
            $day = 29;
            $month = 2;
            $year = 1900;
            
            return sprintf('%02d/%02d/%04d', $month, $day, $year);
        }
        else if ($serial < 60) {
            // Because of the 29-02-1900 bug, any serial date 
            // under 60 is one off... Compensate.
            $serial++;
        }
        
        // Modified Julian to DMY calculation with an addition of 2415019
        $l = $serial + 68569 + 2415019;
        $n = floor(( 4 * $l ) / 146097);
        $l = $l - floor(( 146097 * $n + 3 ) / 4);
        $i = floor(( 4000 * ( $l + 1 ) ) / 1461001);
        $l = $l - floor(( 1461 * $i ) / 4) + 31;
        $j = floor(( 80 * $l ) / 2447);
        $day = $l - floor(( 2447 * $j ) / 80);
        $l = floor($j / 11);
        $month = $j + 2 - ( 12 * $l );
        $year = 100 * ( $n - 49 ) + $i + $l;
        return sprintf('%04d-%02d-%02d', $year, $month, $day);
    }
    
    
	 //make sure character encoding is set, so greek characters work
    function setUTFconnection($db){
		  $sql = "SET collation_connection = utf8_unicode_ci;";
		  $db->query($sql, 2);
		  $sql = "SET NAMES utf8;";
		  $db->query($sql, 2);
    } 
    
    
}  //end class
