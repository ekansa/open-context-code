<?php


//this class interacts with the database for accessing and changing property items
class Property {
    
    /*
     General item metadata
    */
    public $noid;
    public $projectUUID;
    public $itemUUID;
    public $sourceID;
    public $label;
    
    /*
    Property specific
    */
    public $propDescription;
    
    public $varUUID;
    public $varLabel;
    public $varSort;
    public $varType;
    public $varDescription;
    
    public $valUUID;
    public $value;
    public $valNumeric;
    public $valCalendric;
    
    public $createdTime;
    public $updatedTime;
    
    public $archaeoML;
    public $atomFull; 
    public $atomEntry;
    public $newArchaeoML;
    
    const default_mimeType = "application/xhtml+xml";
    const OC_namespaceURI = "http://opencontext.org/schema/property_schema_v1.xsd";
    const gen_new_propertyURI = "";
    
    //get User data from database
    function getByID($id){
        
        $id = $this->security_check($id);
		
        $output = false; //no user
        $db_params = OpenContext_OCConfig::get_db_config();
        $db = new Zend_Db_Adapter_Pdo_Mysql($db_params);
		  $db->getConnection();
        
        $sql = 'SELECT *
                FROM properties 
                WHERE properties.property_uuid = "'.$id.'"
                LIMIT 1';
	
        $result = $db->fetchAll($sql, 2);
		
        if($result){
				
			if(array_key_exists("noid", $result[0])){
				$this->noid = $result[0]["noid"];
			}
			else{
				$this->noid = false;
			}
			
			$this->projectUUID = $result[0]["project_id"];
			$this->sourceID = $result[0]["source_id"];
			$this->itemUUID = $result[0]["property_uuid"];
			$this->label = "Property: ".$this->itemUUID;
			
			$this->varUUID = $result[0]["variable_uuid"];
			$this->valUUID = $result[0]["value_uuid"];
			$this->valNumeric = $result[0]["val_num"];
			$this->valCalendric = $result[0]["val_date"];
			
			$this->createdTime = $result[0]["created"];
			$this->updatedTime = $result[0]["updated"];
			$this->archaeoML = $result[0]["prop_archaeoml"];
			$this->atomFull = $result[0]["prop_atom"];
			
			if(strlen($this->archaeoML)<10){
				$this->atomFull = OpenContext_PropertyAtom::make_archaeoml_atom($this->itemUUID);
			}
			
			//update old namespaces, if needed
			if(strtotime($this->createdTime) < strtotime("2010-11-1 23:00:00")){
				$this->archaeoML = $this->namespace_fix($this->archaeoML, "prop_archaeoml", $db, true);
				$this->atomFull = $this->namespace_fix($this->atomFull, "prop_atom", $db, true);
			}
			
			if(strlen($this->atomFull)<20){
				$this->atomFull = $this->fullAtomCreate($this->archaeoML);
				$where = array();
				$where[] = "property_uuid = '".$this->itemUUID."' ";
				$data = array($field => $this->atomFull);
				$db->update("prop_atom", $data, $where);
			}
			
			$output = true;
        }
        
	$db->closeConnection();
    
        return $output;
    }
    
    
    //this function gets an item's Atom entry. It's used for making the general
    //feed read by the CDL's archival services.
    function getItemEntry($id){
		  $this->getByID($id);
		  $this->DOM_AtomCreate($this->archaeoML );
		  return $this->atomEntry;
    }
    
    //this function gets an item's ArchaeoML. It's used for indexing in Solr
    function getItemXML($id){
		  $this->getByID($id);
		  return $this->archaeoML;
    }
    
    
    //this function fixes XML for the latest schema
    function namespace_fix($xmlString, $field, $db, $doUpdate = true){
	
		  //$goodNamespaceURI = "http://opencontext.org/schema/space_schema_v1.xsd";
		  $goodNamespaceURI = self::OC_namespaceURI;
		  
		  $old_namespaceURIs = array("http://about.opencontext.org/schema/property_schema_v1.xsd",
							  "http://www.opencontext.org/database/schema/property_schema_v1.xsd");
		  
		  foreach($old_namespaceURIs as $oldNamespace){
				if(stristr($xmlString, $oldNamespace)){
			  $xmlString = str_replace($oldNamespace, $goodNamespaceURI, $xmlString);
			  if($doUpdate){
					$where = array();
					$where[] = "property_uuid = '".$this->itemUUID."' ";
					$data = array($field => $xmlString);
					$db->update("properties", $data, $where);
			  }
				}
		  }
		  
		  return $xmlString;
    }
    
    
    
    
    
    
    function versionUpdate($id, $db = false){
	
		  if(!$db){
				$db_params = OpenContext_OCConfig::get_db_config();
				$db = new Zend_Db_Adapter_Pdo_Mysql($db_params);
				$db->getConnection();
		  }
		  
		  $sql = 'SELECT prop_archaeoml AS archaeoML
							FROM properties
							WHERE property_uuid = "'.$id.'"
							LIMIT 1';
			  
		  $result = $db->fetchAll($sql, 2);
		  if($result){
				$xmlString = $result[0]["archaeoML"];
				OpenContext_DeleteDocs::saveBeforeUpdate($id, "property", $xmlString);
		  }
	
    }//end function
    
    
    //create a new diary / document item
    function createUpdate($versionUpdate){
        
        $db_params = OpenContext_OCConfig::get_db_config();
        $db = new Zend_Db_Adapter_Pdo_Mysql($db_params);
		  $db->getConnection();
    
	$data = array(//"noid" => $this->noid,
		      "project_id" => $this->projectUUID,
		      "source_id" => $this->sourceID,
		      "property_uuid" => $this->itemUUID,
		      "variable_uuid" => $this->varUUID,
		      "value_uuid" =>$this->valUUID,
		      "val_num" =>$this->valNumeric,
		      "val_date" => $this->valCalendric,
		      "prop_des" => $this->propDescription,
		      "created" => $this->createdTime
		      );
	
	if(strlen($this->varUUID)<1){
	    unset($data["variable_uuid"]);
	}
	if(strlen($this->valUUID)<1){
	    unset($data["value_uuid"]);
	}
	if(strlen($this->valNumeric)<1){
	    unset($data["val_num"]);
	}
	if(strlen($this->valCalendric)<1){
	    unset($data["val_date"]);
	}
	
	if($versionUpdate){
	    $this->versionUpdate($this->itemUUID, $db); //save previous version history
	    unset($data["created"]);
	}
	
	if(OpenContext_OCConfig::need_bigString($this->archaeoML)){
	    /*
	    This gets around size limits for inserting into MySQL.
	    It breaks up big inserts into smaller ones, especially useful for HUGE strings of XML
	    */
	    $bigString = new BigString;
	    $bigString->saveCurrentBigString($this->itemUUID, "archaeoML", "property", $this->archaeoML, $db);
	    $data["prop_archaeoml"] = OpenContext_OCConfig::get_bigStringValue();
	}
	else{
	    $data["prop_archaeoml"] = $this->archaeoML;
	}
	
	if(OpenContext_OCConfig::need_bigString($this->atomFull)){
	    /*
	    This gets around size limits for inserting into MySQL.
	    It breaks up big inserts into smaller ones, especially useful for HUGE strings of XML
	    */
	    $bigString = new BigString;
	    $bigString->saveCurrentBigString($this->itemUUID, "atomFull", "property", $this->atomFull, $db);
	    $data["prop_atom"] = OpenContext_OCConfig::get_bigStringValue();
	}
	else{
	    $data["prop_atom"] = $this->atomFull;
	}



	$success = false;
	try{
	    $db->insert("properties", $data);
	    $success = true;
	}catch(Exception $e){
	    //echo $e;
	    $success = false;
	    $where = array();
	    $where[] = 'property_uuid = "'.$this->itemUUID.'" ';
	    $db->update("properties", $data, $where);
	    $success = true;
	}

	if($success){
	    $varData = array("project_id" => $this->projectUUID,
			     "source_id" => $this->sourceID,
			     "variable_uuid" => $this->varUUID,
			     "var_type" => $this->varType,
			     "var_label" => $this->varLabel,
			     "var_des" => $this->varDescription,
			     "var_sort" => $this->varSort,
			     "created" => $this->createdTime
			     );
	    try{
	    	$db->insert("var_tab", $varData);
	    }catch(Exception $e){
		//do nothing, it's probably in
	    }

	    if($this->valUUID != "number"){ //don't do inserts on numeric data
		$valData = array("project_id" => $this->projectUUID,
				 "source_id" => $this->sourceID,
				 "value_uuid" => $this->valUUID,
				 "val_text" => $this->value,
				 "created" => $this->createdTime
				 );
		try{
		    $db->insert("val_tab", $valData);
		}catch(Exception $e){
		    //do nothing, it's probably in
		}
	    }
	    
	}

	$db->closeConnection();
	return $success;
    }//end function
    
    
    
    
    function nameSpaces(){
		  $nameSpaceArray = array("oc" => self::OC_namespaceURI,
					  "dc" => OpenContext_OCConfig::get_namespace("dc"),
					  "arch" => OpenContext_OCConfig::get_namespace("arch", "property"),
					  "gml" => OpenContext_OCConfig::get_namespace("gml"),
					  "kml" => OpenContext_OCConfig::get_namespace("kml"));
		  
		  return $nameSpaceArray;
    }
    
    
    /*
    Make a new Atom version of this property
    */
    public function fullAtomCreate($propertyXML_string){
		
		$baseURI = OpenContext_OCConfig::get_host_config();
		$baseURI .= "/properties/";
		
		$prop_dom = new DOMDocument("1.0", "utf-8");
		$prop_dom->loadXML($propertyXML_string);
		
		$xpath = new DOMXpath($prop_dom);
			
		// Register OpenContext's namespace
		$xpath->registerNamespace("arch", OpenContext_OCConfig::get_namespace("arch", "property"));
		$xpath->registerNamespace("oc", OpenContext_OCConfig::get_namespace("oc", "property"));
		$xpath->registerNamespace("dc", OpenContext_OCConfig::get_namespace("dc"));
		
		$query = "/arch:property/@UUID";
		$propUUIDnode = $xpath->query($query, $prop_dom);
		$propUUID = $propUUIDnode->item(0)->nodeValue;
			
		$query = "/arch:property/arch:name/arch:string";
		$result_title = $xpath->query($query, $prop_dom);
			
		if($result_title != null){
		    $prop_item_name = utf8_decode($result_title->item(0)->nodeValue);
		    //$prop_item_name = $result_title->item(0)->nodeValue;
		    //$prop_item_name = light_parseXMLcoding($prop_item_name);
		    $prop_item_name = utf8_encode($prop_item_name);
		}
		
		$query = "//oc:propVariable";
		$result_title2 = $xpath->query($query, $prop_dom);
			
		if($result_title2 != null){
		    @$namePart = $result_title2->item(0)->nodeValue;
		    $prop_item_name = $namePart.": ".$prop_item_name ;
		}
		
		
		$query = "//arch:notes/arch:note[@type='var_des']/arch:string";
		$result_des = $xpath->query($query, $prop_dom);
			
		if($result_des != null){
		    if(@$result_des->item(0)->nodeValue){
			$long_des = $result_des->item(0)->nodeValue;
		    }
		}
		
		$query = "//arch:notes/arch:note[@type='prop_des']/arch:string";
		$result_des_p = $xpath->query($query, $prop_dom);
			
		if($result_des_p != null){
		    if(@$result_des_p->item(0)->nodeValue){
			$long_des .= "<br/><br/>".$result_des_p->item(0)->nodeValue;
		    }
		}
		
		
		$query = "//oc:metadata/oc:project_name";
		$result_proj = $xpath->query($query, $prop_dom);
			
		if($result_proj != null){
		    @$project_name = $result_proj->item(0)->nodeValue;
		}
			
		$query = "//oc:metadata/dc:creator";
		$result_create = $xpath->query($query, $prop_dom);
		$author_array = array();	
			
		foreach($result_create AS $res_creators){
		    $author_array[] = $res_creators->nodeValue;
		}
		
		$query = "//oc:metadata/dc:contributor";
		$result_contrib = $xpath->query($query, $prop_dom);	
		$contributor_array = array();
			
		foreach($result_contrib AS $act_contrib){
		    $contributor_array[] = $act_contrib->nodeValue;
		}
	
		$query = "//oc:metadata/dc:title";
		$result_dctitle = $xpath->query($query, $prop_dom);	
		     
		if($result_dctitle!= null){
		    $propery_title = utf8_decode(@$result_dctitle->item(0)->nodeValue);
		    //$propery_title = $result_dctitle->item(0)->nodeValue;
		    //$propery_title = light_parseXMLcoding($propery_title);
			$propery_title = utf8_encode($propery_title);
		    //$propery_title = $propery_title;
		}
	
		
		$query = "//oc:manage_info/oc:propVariable";
		$result_var = $xpath->query($query, $prop_dom);		
		$prop_var = false;     
		if($result_var!= null){
		    @$prop_var  = $result_var->item(0)->nodeValue;
		}
		
		$query = "//oc:manage_info/oc:propStats[@observeType='Spatial']/oc:numUniqueVals";
		$result_obs = $xpath->query($query, $prop_dom);		
		$uniqueVal_sp = false;     
		if($result_obs != null){
			foreach($result_obs as $actNode){
				$uniqueVal_sp = $actNode->nodeValue;
			}
		}
		
		$result_obs = null;
		$query = "//oc:manage_info/oc:propStats[@observeType='Spatial']/oc:varTotalObs";
		$result_obs  = $xpath->query($query, $prop_dom);		
		$uniqueVarTotalObs_sp = false;     
		if($result_obs != null){
			foreach($result_obs as $actNode){
				$uniqueVarTotalObs_sp = $actNode->nodeValue;
			}
		}
		
		$result_obs = null;
		$query = "//oc:manage_info/oc:propStats[@observeType='Spatial']/oc:freqRank";
		$result_obs  = $xpath->query($query, $prop_dom);		
		$uniqueFreqRank_sp = false;     
		if($result_obs != null){
			
			foreach($result_obs as $actNode){
				$uniqueFreqRank_sp = $actNode->nodeValue;
				if($uniqueFreqRank_sp == 1){
					$uniqueFreqRank_sp = "1st";
				}
				if($uniqueFreqRank_sp == 2){
					$uniqueFreqRank_sp = "2nd";
				}
				if($uniqueFreqRank_sp == 3){
					$uniqueFreqRank_sp = "3rd";
				}
				if($uniqueFreqRank_sp >= 4){
					$uniqueFreqRank_sp .= "th";
				}
			}
		}
		
		$rank_message = "";
		if(!$uniqueVarTotalObs_sp && !$uniqueFreqRank_sp){
			$rank_message = "'$prop_item_name' is the $uniqueFreqRank_sp most used value for the variable '$prop_var' ";
			$rank_message .= " in the project / collection: $project_name";
			$rank_message = OpenContext_XMLtoItems::light_parseXMLcoding($rank_message);
		}
		
	
		//done querying old xml version
		
		$prop_entry_title = $prop_item_name;
		$prop_feed_title = "Open Context Property Record for '".$prop_item_name."'";
		
		//echo "<br/>".$prop_feed_title."<br/>".$prop_entry_title."<br/>";
		
		
		$atomFullDoc = new DOMDocument("1.0", "utf-8");
		
		$root = $atomFullDoc->createElementNS(OpenContext_OCConfig::get_namespace("atom"), "feed");
		
		// add newlines and indent the output - this is at least useful for debugging and making the output easier to read
		$atomFullDoc->formatOutput = true;
		
		$root->setAttribute("xmlns:georss", "http://www.georss.org/georss");
		$root->setAttribute("xmlns:gml", "http://www.opengis.net/gml");
	       
		$atomFullDoc->appendChild($root);
	
		// Feed Title 
		$feedTitle = $atomFullDoc->createElement("title");
		$feedTitleText = $atomFullDoc->createTextNode($prop_feed_title);
		$feedTitle->appendChild($feedTitleText);
		$root->appendChild($feedTitle);
		
		// Feed updated element (as opposed to the entry updated element)
		$feedUpdated = $atomFullDoc->createElement("updated");
		// Retrieve the current date and time. Format it in RFC 3339 format. Store it in a text node 
		$feedUpdatedText = $atomFullDoc->createTextNode(date("Y-m-d\TH:i:s\-07:00"));   
		// Append the text node the updated element
		$feedUpdated->appendChild($feedUpdatedText);
		// Append the updated node to the root element
		$root->appendChild($feedUpdated);
		
		$linkURI = $baseURI . $propUUID. ".atom";
		// feed (self) link element
		$feedLink = $atomFullDoc->createElement("link");
		$feedLink->setAttribute("rel", "self");
		$feedLink->setAttribute("href", $linkURI);
		$root->appendChild($feedLink);
		
		// feed id
		$feedId = $atomFullDoc->createElement("id");
		$feedIdText = $atomFullDoc->createTextNode($baseURI . $propUUID);
		$feedId->appendChild($feedIdText);
		$root->appendChild($feedId);
		
		
		$feed_entry = $atomFullDoc->createElement("entry");
		$root->appendChild($feed_entry);
		
		$entryCat = $atomFullDoc->createElement("category");
		$entryCat->setAttribute("term", "property_overview");
		$feed_entry->appendChild($entryCat);
		
		
		$entry_title_el = $atomFullDoc->createElement("title");
		$entry_title_text = $atomFullDoc->createTextNode($prop_entry_title);
		$entry_title_el->appendChild($entry_title_text);
		$feed_entry->appendChild($entry_title_el);
		
		$entry_id_el = $atomFullDoc->createElement("id");
		$entry_id_text = $atomFullDoc->createTextNode($baseURI . $propUUID);
		$entry_id_el->appendChild($entry_id_text);
		$feed_entry->appendChild($entry_id_el);
		
		// Feed updated element (as opposed to the entry updated element)
		$entryUpdated = $atomFullDoc->createElement("updated");
		// Retrieve the current date and time. Format it in RFC 3339 format. Store it in a text node 
		$entryUpdatedText = $atomFullDoc->createTextNode(date("Y-m-d\TH:i:s\-07:00"));   
		// Append the text node the updated element
		$entryUpdated->appendChild($entryUpdatedText);
		// Append the updated node to the root element
		$feed_entry->appendChild($entryUpdated);
		
		
		foreach($author_array AS $act_creator){
		    $author_el = $atomFullDoc->createElement("author");
		    $name_el = $atomFullDoc->createElement("name");
		    $name_text = $atomFullDoc->createTextNode($act_creator);
		    $name_el->appendChild($name_text);
		    $author_el->appendChild($name_el);
		    $feed_entry->appendChild($author_el);
		}
		
		foreach($contributor_array AS $act_contrib){
		    $author_el = $atomFullDoc->createElement("contributor");
		    $name_el = $atomFullDoc->createElement("name");
		    $name_text = $atomFullDoc->createTextNode($act_creator);
		    $name_el->appendChild($name_text);
		    $author_el->appendChild($name_el);
		    $feed_entry->appendChild($author_el);
		}
		    
		$content_el = $atomFullDoc->createElement("content");
		$content_el->setAttribute("type", "xhtml");
		
		$content_div_text =
		'
		<div xmlns="http://www.w3.org/1999/xhtml">
		<h2>'.$project_name.": Property ".OpenContext_XMLtoItems::light_parseXMLcoding($prop_item_name).'</h2>
		<p>'.$rank_message.'</p><br/>
		<p><strong>Description of this property:</strong></p>
		'.$long_des.' 
		</div>
		';
		
	       // echo $content_div_text;
		
		// add the XHTML content string
		$contentFragment = $atomFullDoc->createDocumentFragment();
		$contentFragment->appendXML($content_div_text);  // $atom_content from short atom entry
		$content_el->appendChild($contentFragment);
		$feed_entry->appendChild($content_el);
		
		//now add ArchaeoML String
		$prop_archaeoML = str_replace('<?xml version="1.0"?>', "", $propertyXML_string);
		$prop_archaeoML = str_replace('<?xml version="1.0" encoding="utf-8"?>', "", $prop_archaeoML);
		$arch_contentFragment = $atomFullDoc->createDocumentFragment();
		$arch_contentFragment->appendXML($prop_archaeoML);
		$feed_entry->appendChild($arch_contentFragment);
		
		$atom_xml_string = $atomFullDoc->saveXML();
		
		$atom_xml_string = str_replace("<default:", "<", $atom_xml_string);
		$atom_xml_string = str_replace("</default:", "</", $atom_xml_string);
		$atom_xml_string = str_replace('<content xmlns:default="http://www.w3.org/1999/xhtml"', "<content ", $atom_xml_string);
		
		//fix screwy encoding issues.
		$atom_xml_string = str_replace("&amp;#199;", "&#xC7;", $atom_xml_string);
		$atom_xml_string = str_replace("Ç", "&#xC7;", $atom_xml_string);
		$atom_xml_string = str_replace("&amp;#252;", "&#xFC;", $atom_xml_string);
		$atom_xml_string = str_replace("ü", "&#xFC;", $atom_xml_string);
		
		$this->atomFull = $atom_xml_string;
		return $atom_xml_string;

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
    
    
    
	 function documentProperties($projectUUID, $sourceID, $itemUUID, $itemType, $itemXMLstring, $nameSpaceArray, $db = false){
	
		  if(!$db){
				$db_params = OpenContext_OCConfig::get_db_config();
				$db = new Zend_Db_Adapter_Pdo_Mysql($db_params);
				$db->getConnection();
		  }
		  
		  
		  $itemXML = simplexml_load_string($itemXMLstring);
		  foreach($nameSpaceArray as $prefix => $uri){
				@$itemXML->registerXPathNamespace($prefix, $uri);
		  }
		  
		  //deal with observations first
		  OpenContext_XMLtoItems::obs_props_Retrieve($projectUUID, $sourceID, $itemUUID, $itemType, $itemXML, $db);
		  
		  //now add properties, variables, and values.
		  $dataToAdd = OpenContext_XMLtoItems::itemPropsRetrieve($projectUUID, $sourceID, $itemXML);
		  
		  $keyArray = array("props" => "properties", "vars" => "var_tab", "vals" => "val_tab");
		  
		  foreach($dataToAdd as $key => $dataRecs){
				$table = $keyArray[$key];
				foreach($dataRecs as $data){
					 try {
						  $db->insert($table, $data);
					 } catch (Exception $e) {
    
					 }
				}
		  }
	 }
	 
	 
	 
    
    
    
}
