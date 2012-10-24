<?php


//this class interacts with the database for accessing and changing project items
class Person {
    
    /*
     General item metadata
    */
    public $noid;
    public $projectUUID;
    public $itemUUID;
    public $sourceID;
    public $label;
    
    /*
    Person specific
    */
    public $firstName;
    public $midInitials;
    public $lastName;
    public $initials;
    
    public $mimeType; //keep it
    public $viewCount;
    public $createdTime;
    public $updatedTime;
    
    public $archaeoML;
    public $atomFull; 
    public $atomEntry;
    
    public $kml_in_Atom; // add KML timespan to atom entry, W3C breaks validation
    public $xhtml_rel; // value for link rel attribute for XHTML version ("self" or "alternate")
    public $atom_rel; //value for link rel attribute for Atom version ("self" or "alternate")
    
    public $geoCurrent; //check if geo-reference is current.
    
    
    public $errors = array();
    
    const default_mimeType = "application/xhtml+xml";
    const OC_namespaceURI = "http://opencontext.org/schema/person_schema_v1.xsd";
    
    //get User data from database
    function getByID($id){
        
        $id = $this->security_check($id);
        $output = false; //no user
        $db_params = OpenContext_OCConfig::get_db_config();
        $db = new Zend_Db_Adapter_Pdo_Mysql($db_params);
		  $db->getConnection();
		  $this->setUTFconnection($db);
        
        $sql = 'SELECT *
                FROM persons
                WHERE person_uuid = "'.$id.'"
                LIMIT 1';
		
        $result = $db->fetchAll($sql, 2);
        if($result){
            
            $this->noid = $result[0]["noid"];
            $this->projectUUID = $result[0]["project_id"];
            $this->sourceID = $result[0]["source_id"];
				$this->itemUUID = $result[0]["person_uuid"];
				$this->label = $result[0]["combined_name"];
				
				$this->mimeType = self::default_mimeType;
				
				$this->viewCount = $result[0]["view_count"];
				$this->spViewCount = $result[0]["sp_view_count"];
					  $this->createdTime = $result[0]["created"];
					  $this->updatedTime = $result[0]["updated"];
				
				$this->atomFull = $result[0]["atom_entry"];
				$this->atomEntry = $result[0]["atom_entry"];
				$this->archaeoML = $result[0]["archaeoML"];
				
				$this->xhtml_rel = "alternate";
				$this->atom_rel = "self";
	    
            $output = true;
        }
        
		  $db->closeConnection();
    
        return $output;
    }
    

    function addViewCount(){
	
		  $db_params = OpenContext_OCConfig::get_db_config();
        $db = new Zend_Db_Adapter_Pdo_Mysql($db_params);
		  $db->getConnection();
		  $this->setUTFconnection($db);
	
		  $view_count = $this->viewCount;
		  $view_count++; // increment it up one.
        $where_term = 'person_uuid = "'.$this->itemUUID.'"';
		  $data = array('view_count' => $view_count); 
		  $n = $db->update('persons', $data, $where_term);
        $db->closeConnection();
	
		  $this->viewCount = $view_count;
		  return $view_count;
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
    function namespace_fix($xmlString){
	
		  //$goodNamespaceURI = "http://opencontext.org/schema/space_schema_v1.xsd";
		  $goodNamespaceURI = self::OC_namespaceURI;
		  
		  $old_namespaceURIs = array("http://about.opencontext.org/schema/person_schema_v1.xsd",
							  "http://www.opencontext.org/database/schema/person_schema_v1.xsd");
		  
		  foreach($old_namespaceURIs as $oldNamespace){
				$xmlString = str_replace($oldNamespace, $goodNamespaceURI, $xmlString);
		  }
		  
		  return $xmlString;
    }
    
    
    
    
    function versionUpdate($id, $db = false){
	
		  if(!$db){
				$db_params = OpenContext_OCConfig::get_db_config();
				$db = new Zend_Db_Adapter_Pdo_Mysql($db_params);
				$db->getConnection();
				$this->setUTFconnection($db);
		  }
		  
		  $sql = 'SELECT archaeoML AS archaeoML
							FROM persons
							WHERE person_uuid = "'.$id.'"
							LIMIT 1';
			  
		  $result = $db->fetchAll($sql, 2);
		  if($result){
			  $xmlString = $result[0]["archaeoML"];
			  OpenContext_DeleteDocs::saveBeforeUpdate($id, "person", $xmlString);
		 }
	
    }//end function
    
    
    //create a new diary / document item
    function createUpdate($versionUpdate){
        
        $db_params = OpenContext_OCConfig::get_db_config();
        $db = new Zend_Db_Adapter_Pdo_Mysql($db_params);
		  $db->getConnection();
		  $this->setUTFconnection($db);
		  
		  if(!$this->noid){
				$this->noid = false;
		  }
    
		  $data = array("noid" => $this->noid,
		      "project_id" => $this->projectUUID,
		      "source_id" => $this->sourceID,
		      "person_uuid" => $this->itemUUID,
		      "first_name" =>$this->firstName,
		      "mid_init" =>$this->midInitials,
		      "last_name" =>$this->lastName,
		      "initials" => $this->initials,
		      "combined_name" => $this->label,
		      "created" => $this->createdTime,
		      );
	
		  if($versionUpdate){
				$this->versionUpdate($this->itemUUID, $db); //save previous version history
				unset($data["created"]);
		  }
		  
		  if(!$this->archaeoML){
				$this->archaeoML = $this->archaeoML;
		  }
	
		  if(OpenContext_OCConfig::need_bigString($this->archaeoML)){
				/*
				This gets around size limits for inserting into MySQL.
				It breaks up big inserts into smaller ones, especially useful for HUGE strings of XML
				*/
				$bigString = new BigString;
				$bigString->saveCurrentBigString($this->itemUUID, "archaeoML", "person", $this->archaeoML, $db);
				$data["archaeoML"] = OpenContext_OCConfig::get_bigStringValue();
		  }
		  else{
				$data["archaeoML"] = $this->archaeoML;
		  }
		  
		  if(OpenContext_OCConfig::need_bigString($this->atomFull)){
				/*
				This gets around size limits for inserting into MySQL.
				It breaks up big inserts into smaller ones, especially useful for HUGE strings of XML
				*/
				$bigString = new BigString;
				$bigString->saveCurrentBigString($this->itemUUID, "atomFull", "person", $this->atomFull, $db);
				$data["atom_entry"] = OpenContext_OCConfig::get_bigStringValue();
		  }
		  else{
				$data["atom_entry"] = $this->atomFull;
		  }
	  
	  
	  
		  $success = false;
		  try{
				$db->insert("persons", $data);
				$success = true;
		  }catch(Exception $e){
				$success = false;
				$where = array();
				$where[] = 'person_uuid = "'.$this->itemUUID.'" ';
				$db->update("persons", $data, $where);
				$success = $this->getByID($this->itemUUID);
		  }
	  
		  $db->closeConnection();
		  return $success;
    }//end function
    
    
 
    
    function nameSpaces(){
		  $nameSpaceArray = array("oc" => self::OC_namespaceURI,
			   "dc" => OpenContext_OCConfig::get_namespace("dc"),
			   "arch" => OpenContext_OCConfig::get_namespace("arch", "person"),
			   "gml" => OpenContext_OCConfig::get_namespace("gml"),
			   "kml" => OpenContext_OCConfig::get_namespace("kml"));
	
		  return $nameSpaceArray;
    }
    
    
    function DOM_AtomCreate($archaeML_string){
		
		$host = OpenContext_OCConfig::get_host_config();
		$baseURI = $host."/persons/";
		
		$projItem = simplexml_load_string($archaeML_string);
	
		// Register OpenContext's namespace
		//$projItem->registerXPathNamespace("oc", OpenContext_OCConfig::get_namespace("oc", "spatial"));
		
		$projItem->registerXPathNamespace("oc", self::OC_namespaceURI);
		
		// Register OpenContext's namespace
		$projItem->registerXPathNamespace("arch", OpenContext_OCConfig::get_namespace("arch", "person"));
	
		// Register Dublin Core's namespace
		$projItem->registerXPathNamespace("dc", OpenContext_OCConfig::get_namespace("dc"));
	
		// Register the GML namespace
		$projItem->registerXPathNamespace("gml", OpenContext_OCConfig::get_namespace("gml"));
		
		
		// get the item_id
		foreach($projItem->xpath("//arch:person/@UUID") as $item_result) {
			$uuid = $item_result."";
		}
	
	
		// get the item_label
		foreach ($projItem->xpath("//arch:person/arch:name/arch:string") as $item_label) {
			$item_label = $item_label."";
		}
		
		//project name
		foreach ($projItem->xpath("//arch:person/oc:metadata/oc:project_name") as $project_name) {
			$project_name = $project_name."";
		}
	
		$creators = $projItem->xpath("//arch:person/oc:metadata/dc:creator");
		$contributors = $projItem->xpath("/arch:person/oc:metadata/dc:contributor");
		
		$pubDate = false;
		foreach($projItem->xpath("//oc:metadata/dc:date") as $pubDate) {
		    $pubDate = $pubDate."";
		}
	
	
	
		$atomEntryDoc = new DOMDocument("1.0", "utf-8");
	
		$rootEntry = $atomEntryDoc->createElementNS("http://www.w3.org/2005/Atom", "entry");
		
		// add newlines and indent the output - this is at least useful for debugging and making the output easier to read
		$atomEntryDoc->formatOutput = true;
		
		//$rootEntry->setAttribute("xmlns:georss", OpenContext_OCConfig::get_namespace("georss"));
		//$rootEntry->setAttribute("xmlns:gml", OpenContext_OCConfig::get_namespace("gml"));
		//$rootEntry->setAttribute("xmlns:kml", OpenContext_OCConfig::get_namespace("kml"));
		
		$atomEntryDoc->appendChild($rootEntry);
	
		// Create feed title (as opposed to an entry title)
		$feedTitle = $atomEntryDoc->createElement("title");
		$feedTitleText = $atomEntryDoc->CreateTextNode( $item_label );	
		$feedTitle->appendChild($feedTitleText);
		$rootEntry->appendChild($feedTitle);
	
		// feed id
		$feedId = $atomEntryDoc->createElement("id");
		$feedIdText = $atomEntryDoc->createTextNode($baseURI . $uuid);
		$feedId->appendChild($feedIdText);
		$rootEntry->appendChild($feedId);
	
		// entry(self) link element
		$entryLink = $atomEntryDoc->createElement("link");
		$entryLink->setAttribute("rel", $this->xhtml_rel);
		$entryLink->setAttribute("type", "application/xhtml+xml");
		$entryLink->setAttribute("title", "XHTML representation of ". $item_label );
		$entryLink->setAttribute("href", $baseURI . $uuid);
		$rootEntry->appendChild($entryLink);
		
		// entry archaeoml xml link element
		$entryLink = $atomEntryDoc->createElement("link");
		$entryLink->setAttribute("rel", "alternate");
		$entryLink->setAttribute("type", "application/xml");
		$entryLink->setAttribute("title", "ArchaeoML (XML) representation of ". $item_label );
		$entryLink->setAttribute("href", $baseURI . $uuid. ".xml");
		$rootEntry->appendChild($entryLink);
		
		// entry atom link element
		$entryLink = $atomEntryDoc->createElement("link");
		$entryLink->setAttribute("rel", $this->atom_rel);
		$entryLink->setAttribute("type", "application/atom+xml");
		$entryLink->setAttribute("title", "Atom representation of ".$item_label);
		$entryLink->setAttribute("href", $baseURI . $uuid. ".atom");
		$rootEntry->appendChild($entryLink);
		
		//entry for JSON link element
		$entryLink = $atomEntryDoc->createElement("link");
		$entryLink->setAttribute("rel", "alternate");
		$entryLink->setAttribute("type", "application/json");
		$entryLink->setAttribute("title", "JSON representation of ".$item_label);
		$entryLink->setAttribute("href", $baseURI . $uuid.".json");
		$rootEntry->appendChild($entryLink);
		
		
		// Create feed updated element (as opposed to the entry updated element)
		$entryUpdated = $atomEntryDoc->createElement("updated");
		$updateDateTM = strtotime($this->updatedTime);
		$pubDateTM = strtotime($pubDate);
		if($updateDateTM<$pubDateTM){
		    $updateDateTM = $pubDateTM;
		}
	
	
		// Retrieve the current date and time. Format it in RFC 3339 format. Store it in a text node 
		$entryUpdatedText = $atomEntryDoc->CreateTextNode(date("Y-m-d\TH:i:s\-07:00", $updateDateTM));
		// Append the text node the updated element
		$entryUpdated->appendChild($entryUpdatedText);
	
		// Append the updated node to the root element
		$rootEntry->appendChild($entryUpdated);
		
		
		/*
		PUBLICATION TIME - Important metadate used by the CDL archiving service
		*/
		$entryPublished = $atomEntryDoc->createElement("published");
		// Retrieve the current date and time. Format it in RFC 3339 format. Store it in a text node 
		$entryPublishedText = $atomEntryDoc->CreateTextNode(date("Y-m-d\TH:i:s\-07:00", strtotime($pubDate)));
		$entryPublished->appendChild($entryPublishedText);
		$rootEntry->appendChild($entryPublished);
	
	
	
		// append one or more author elements to the entry
		foreach ($creators as $creator) {
		    $entryPerson = $atomEntryDoc->createElement("author");
		    $entryPersonName = $atomEntryDoc->createElement("name");
		    $entryPersonNameText = $atomEntryDoc->CreateTextNode($creator);
		    $entryPersonName->appendChild($entryPersonNameText);
		    $entryPerson->appendChild($entryPersonName);

		    $creatorID = $this->db_find_personID($creator);
		    if($creatorID != false){
			$entryPersonURI = $atomEntryDoc->createElement("uri");
			$entryPersonURIText = $atomEntryDoc->CreateTextNode($host."/persons/".$creatorID);
			$entryPersonURI->appendChild($entryPersonURIText);
			$entryPerson->appendChild($entryPersonURI);
		    }
		    $rootEntry->appendChild($entryPerson);
		}
	
		// append one or more contributor elements to the entry.
		foreach ($contributors as $contributor) {
		    $entryPerson = $atomEntryDoc->createElement("contributor");
		    $entryPersonName = $atomEntryDoc->createElement("name");
		    $entryPersonNameText = $atomEntryDoc->CreateTextNode($contributor);
		    $entryPersonName->appendChild($entryPersonNameText);
		    $entryPerson->appendChild($entryPersonName);

		    $contribID = $this->db_find_personID($contributor);
		    if($contribID != false){
			$entryPersonURI = $atomEntryDoc->createElement("uri");
			$entryPersonURIText = $atomEntryDoc->CreateTextNode($host."/persons/".$contribID);
			$entryPersonURI->appendChild($entryPersonURIText);
			$entryPerson->appendChild($entryPersonURI);
		    }
		    $rootEntry->appendChild($entryPerson);
		}
	
		// entry atom category element
		$entryCat= $atomEntryDoc->createElement("category");
		$entryCat->setAttribute("term", "person");
		$rootEntry->appendChild($entryCat);
	
		// entry atom Summary element
		$entrySummary = $atomEntryDoc->createElement("summary");
		$summaryTextContent =  "Record for $item_label in the project: $project_name";
		
		$summaryText =  $atomEntryDoc->CreateTextNode($summaryTextContent);
		$entrySummary->appendChild($summaryText);
		$rootEntry->appendChild($entrySummary);
		
		/*
		We've done the hard part of constructing the Atom Entry. The entry is now ready for integrating into atom feeds.
		*/
		$atomEntryDoc->formatOutput = true;
		$atomEntryXML = $atomEntryDoc->saveXML();
		
		$this->atomEntry = $atomEntryXML;
		
	}//end make spatialAtomCreate function
    
    
	 
	 public function addLinkedData($personXML_string, $predicateURI, $objectURI){
		  $person_dom = new DOMDocument("1.0", "utf-8");
		  $person_dom->loadXML($personXML_string);
		  $person_dom->formatOutput = true;
		  $xpath = new DOMXpath($person_dom);
			  
		  // Register OpenContext's namespace
		  $xpath->registerNamespace("arch", OpenContext_OCConfig::get_namespace("arch", "person"));
		  $xpath->registerNamespace("oc", OpenContext_OCConfig::get_namespace("oc", "person"));
		  $xpath->registerNamespace("dc", OpenContext_OCConfig::get_namespace("dc"));
		  
		  $query = "//oc:metadata/oc:links";
		  $linksNodeList = $xpath->query($query, $person_dom);
		  
		  if($linksNodeList->item(0) == null){
				$query = "//oc:metadata";
				$metadataNodeList = $xpath->query($query, $person_dom);
				if($metadataNodeList->item(0) == null){
					 $query = "//arch:person";
					 $personNodeList = $xpath->query($query, $person_dom);
					 $personNode = $personNodeList->item(0);
					 $metadataNode = $person_dom->createElement("oc:metadata");
					 $personNode->appendChild($metadataNode);
				}
				else{
					 $metadataNode = $metadataNodeList->item(0);
				}
				$linksNode = $person_dom->createElement("oc:links");
				$metadataNode->appendChild($linksNode);
		  }
		  else{
				$linksNode = $linksNodeList->item(0);
		  }
		  
		  $query = "//oc:metadata/oc:links/oc:link[@rel='$predicateURI' and text()= '$objectURI']";
		  $sameNodeList = $xpath->query($query, $person_dom);
		  if($sameNodeList->item(0)  == null){
				$linkNode = $person_dom->createElement("oc:link");
				$linkNode->setAttribute("rel", $predicateURI);
				$linkText = $person_dom->createTextNode($objectURI);
				$linkNode->appendChild($linkText);
				$linksNode->appendChild($linkNode);
		  }
		  
		  $personXML_string = $person_dom->saveXML();
		  return $personXML_string;
	 }
    
    
    
    public function fullAtomCreate($personXML_string){
		
		$baseURI = OpenContext_OCConfig::get_host_config();
		$baseURI .= "/persons/";
		
		$person_dom = new DOMDocument("1.0", "utf-8");
		$person_dom->loadXML($personXML_string);
		
		$xpath = new DOMXpath($person_dom);
			
		// Register OpenContext's namespace
		$xpath->registerNamespace("arch", OpenContext_OCConfig::get_namespace("arch", "person"));
		$xpath->registerNamespace("oc", OpenContext_OCConfig::get_namespace("oc", "person"));
		$xpath->registerNamespace("dc", OpenContext_OCConfig::get_namespace("dc"));
		
		
		
		$query = "/arch:person/@UUID";
		$personUUIDnode = $xpath->query($query, $person_dom);
		$personUUID = $personUUIDnode->item(0)->nodeValue;
		
		$query = "/arch:person/arch:name/arch:string";
		$result_title = $xpath->query($query, $person_dom);
			
		if($result_title != null){
		    $pers_item_name = $result_title->item(0)->nodeValue;
		}
		
		$query = "//oc:metadata/oc:project_name";
		$result_proj = $xpath->query($query, $person_dom);
			
		if($result_proj != null){
		    $project_name = $result_proj->item(0)->nodeValue;
		}
			
		$query = "//oc:metadata/dc:creator";
		$result_create = $xpath->query($query, $person_dom);
		$author_array = array();	
			
		foreach($result_create AS $res_creators){
		    $author_array[] = $res_creators->nodeValue;
		}
		
		$query = "//oc:metadata/dc:contributor";
		$result_contrib = $xpath->query($query, $person_dom);	
		$contributor_array = array();
			
		foreach($result_contrib AS $act_contrib){
		    $contributor_array[] = $act_contrib->nodeValue;
		}
	
		$query = "//oc:metadata/dc:title";
		$result_dctitle = $xpath->query($query, $person_dom);	
		     
		if($result_dctitle!= null){
		    $person_title = $result_dctitle->item(0)->nodeValue;
		}
	
		$query = "//arch:links/arch:docID[@type='resource']";
		$result_media = $xpath->query($query, $person_dom);	
		$mediaCount = 0;        
		foreach($result_media AS $act_media){
		    $mediaCount++;
		}
	
		$spaceLinks = "(not counted)";
		$query = "//arch:personInfo/@spaceCount";
		$result_cnt = $xpath->query($query, $person_dom);	
		     
		if($result_cnt != null){
		    @$spaceLinks = $result_cnt->item(0)->nodeValue;
		}

	
		//done querying old xml version
		
		$pers_entry_title = $person_title;
		$pers_feed_title = "Open Context Person Record: ".$pers_item_name." (".$project_name.")";
		
		//echo "<br/>".$pers_feed_title."<br/>".$pers_entry_title."<br/>";
		
		$atomFullDoc = new DOMDocument("1.0", "utf-8");
		
		$root = $atomFullDoc->createElementNS(OpenContext_OCConfig::get_namespace("atom"), "feed");
		
		// add newlines and indent the output - this is at least useful for debugging and making the output easier to read
		$atomFullDoc->formatOutput = true;
		
		$root->setAttribute("xmlns:georss", OpenContext_OCConfig::get_namespace("georss"));
		$root->setAttribute("xmlns:gml", OpenContext_OCConfig::get_namespace("gml"));
	       
		$atomFullDoc->appendChild($root);
	
		// Feed Title 
		$feedTitle = $atomFullDoc->createElement("title");
		$feedTitleText = $atomFullDoc->createTextNode($pers_feed_title);
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
		
		$linkURI = $baseURI . $personUUID. ".atom";
		// feed (self) link element
		$feedLink = $atomFullDoc->createElement("link");
		$feedLink->setAttribute("rel", "self");
		$feedLink->setAttribute("href", $linkURI);
		$root->appendChild($feedLink);
		
		// feed id
		$feedId = $atomFullDoc->createElement("id");
		$feedIdText = $atomFullDoc->createTextNode($baseURI . $personUUID);
		$feedId->appendChild($feedIdText);
		$root->appendChild($feedId);
		
		
		$feed_entry = $atomFullDoc->createElement("entry");
		$root->appendChild($feed_entry);
		
		$entryCat = $atomFullDoc->createElement("category");
		$entryCat->setAttribute("term", "person_summary");
		$feed_entry->appendChild($entryCat);
		
		
		$entry_title_el = $atomFullDoc->createElement("title");
		$entry_title_text = $atomFullDoc->createTextNode($pers_entry_title);
		$entry_title_el->appendChild($entry_title_text);
		$feed_entry->appendChild($entry_title_el);
		
		
		// entry(self) link element
		$entryLink = $atomFullDoc->createElement("link");
		$entryLink->setAttribute("rel", $this->xhtml_rel);
		$entryLink->setAttribute("type", "application/xhtml+xml");
		$entryLink->setAttribute("title", "XHTML representation of ". $pers_item_name );
		$entryLink->setAttribute("href", $baseURI . $personUUID);
		$feed_entry->appendChild($entryLink);
		
		// entry archaeoml xml link element
		$entryLink = $atomFullDoc->createElement("link");
		$entryLink->setAttribute("rel", "alternate");
		$entryLink->setAttribute("type", "application/xml");
		$entryLink->setAttribute("title", "ArchaeoML (XML) representation of ". $pers_item_name );
		$entryLink->setAttribute("href", $baseURI . $personUUID. ".xml");
		$feed_entry->appendChild($entryLink);
		
		// entry atom link element
		$entryLink = $atomFullDoc->createElement("link");
		$entryLink->setAttribute("rel", $this->atom_rel);
		$entryLink->setAttribute("type", "application/atom+xml");
		$entryLink->setAttribute("title", "Atom representation of ".$pers_item_name);
		$entryLink->setAttribute("href", $baseURI . $personUUID. ".atom");
		$feed_entry->appendChild($entryLink);
		
		//entry for JSON link element
		$entryLink = $atomFullDoc->createElement("link");
		$entryLink->setAttribute("rel", "alternate");
		$entryLink->setAttribute("type", "application/json");
		$entryLink->setAttribute("title", "JSON representation of ".$pers_item_name);
		$entryLink->setAttribute("href", $baseURI . $personUUID.".json");
		$feed_entry->appendChild($entryLink);
		
		
		$entry_id_el = $atomFullDoc->createElement("id");
		$entry_id_text = $atomFullDoc->createTextNode($baseURI . $personUUID);
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
		<h2>'.$pers_item_name.'</h2>
		<p>Affiliated with the <em>'.$project_name.'</em> project / collection.</p>
		<p>Related observational records: <strong>'.$spaceLinks.'</strong>, and Related media records: <strong>'.$mediaCount.'</strong> </p> 
		</div>
		';
		
		// add the XHTML content string
		$contentFragment = $atomFullDoc->createDocumentFragment();
		$contentFragment->appendXML($content_div_text);  // $atom_content from short atom entry
		$content_el->appendChild($contentFragment);
		$feed_entry->appendChild($content_el);
		
		//now add ArchaeoML String
		$pers_archaeoML = str_replace('<?xml version="1.0"?>', "", $personXML_string);
		$arch_contentFragment = $atomFullDoc->createDocumentFragment();
		$arch_contentFragment->appendXML($pers_archaeoML);
		$feed_entry->appendChild($arch_contentFragment);
		
		$atom_xml_string = $atomFullDoc->saveXML();
		
		$atom_xml_string = str_replace("<default:", "<", $atom_xml_string);
		$atom_xml_string = str_replace("</default:", "</", $atom_xml_string);
		$atom_xml_string = str_replace('<content xmlns:default="http://www.w3.org/1999/xhtml"', "<content ", $atom_xml_string);
	
		$this->atomFull = $atom_xml_string;
		return $atom_xml_string;
	}//end propety atom create function
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    function db_find_personID($personName){
	
		  $personID = false;
		  $db_params = OpenContext_OCConfig::get_db_config();
		  $db = new Zend_Db_Adapter_Pdo_Mysql($db_params);
		  $db->getConnection();
		  
		  $sql = "SELECT persons.person_uuid
		  FROM persons
		  WHERE persons.project_id = '".$this->projectUUID."'
		  AND persons.combined_name LIKE '%".$personName."%'	
		  LIMIT 1;
		  ";
		  
		  $result = $db->fetchAll($sql, 2);
				 if($result){
				$personID = $result[0]["person_uuid"];
		  }
		  
		  $db->closeConnection();
		  return $personID;
    }
    
    
    
    
    
    function rank_count_atom_entry_feed($atom_entry_string, $view_count, $sp_view_count, $rank){
		//$atom_entry_string is a string object of Atom XML data stored in the MySQL database
		
		//echo $atom_entry_string;
		
		//$atom_entry_string = str_replace("http://about.opencontext.org/schema/person_schema_v1.xsd", self::oc_ns_uri, $atom_entry_string);
		
		
	
		$host = OpenContext_OCConfig::get_host_config();
		
		$person_dom = new DOMDocument("1.0", "utf-8");
                $person_dom->loadXML($atom_entry_string);
                    
                    
                $xpath = new DOMXpath($person_dom);
		
                    // Register OpenContext's namespace
                $xpath->registerNamespace("arch", OpenContext_OCConfig::get_namespace("arch", "person"));
                $xpath->registerNamespace("oc", OpenContext_OCConfig::get_namespace("oc", "person"));
                $xpath->registerNamespace("dc", OpenContext_OCConfig::get_namespace("dc"));
                $xpath->registerNamespace("atom", OpenContext_OCConfig::get_namespace("atom"));
                          
                
		if(!stristr($atom_entry_string, "<arch:person")){
			$idURI = false;
			$query = "/atom:feed/atom:id";
			$result_id = $xpath->query($query, $person_dom);
			if($result_id != null){
				$idURI = $result_id->item(0)->nodeValue;
			}
			if($idURI != false){
				$idarray = explode("/", $idURI);
				$id = $idarray[count($idarray)-1];
				$itemObj = new Person; //start person class
				$itemXML_string = $itemObj->getItemXML($id);
				$itemXML_string = str_replace('<?xml version="1.0" encoding="utf-8"?>', '', $itemXML_string);
				$itemXML_string = str_replace('<?xml version="1.0"?>', '', $itemXML_string);
				
				unset($itemObj);
				$contentFragment = $person_dom->createDocumentFragment();
				$contentFragment->appendXML($itemXML_string);  // add note xml string
				$query = "/atom:feed/atom:entry";
				$result_entry = $xpath->query($query, $person_dom);
				if($result_id != null){
					$result_entry_node = $result_entry->item(0);
				}
				$result_entry_node->appendChild($contentFragment);
			}
			
		}
		
		
		
		
		$query = "//arch:person";
		$result_arch = $xpath->query($query, $person_dom);
                if($result_arch != null){
			$arch_node = $result_arch->item(0);
                }
		
		$query = "//oc:social_usage";
		$result_soc = $xpath->query($query, $person_dom);
		$resultCount = 0;
		foreach($result_soc as $node){
			$resultCount++;
			$this->remove_children($node);
			$social_node = $node;
		}
                if($resultCount>0){
		
                }
		else{
			$social_node = $person_dom->createElement("oc:social_usage");
			$arch_node->appendChild($social_node);
		}
		
			$spview_node = $person_dom->createElement("oc:item_views");
			$spview_node->setAttribute("type", "spatialCount");
			$spcount_node = $person_dom->createElement("oc:count");
			$spcount_node ->setAttribute("rank", $rank["rank"]);
			$spcount_node ->setAttribute("pop", $rank["pop"]);
			$spcount_node_val  = $person_dom->createTextNode($sp_view_count);
			$spcount_node->appendChild($spcount_node_val);
			$spview_node->appendChild($spcount_node);
			$social_node->appendChild($spview_node);
			
			$view_node = $person_dom->createElement("oc:item_views");
			$view_node->setAttribute("type", "self");
			$count_node = $person_dom->createElement("oc:count");
			$count_node_val  = $person_dom->createTextNode($view_count);
			$count_node->appendChild($count_node_val);
			$view_node->appendChild($count_node);
			$social_node->appendChild($view_node);
		
		$query = "/atom:feed";      
                $person_dom_root = $xpath->query($query, $person_dom);      
                            
                $query = "//arch:person/arch:name/arch:string";
                $result_title = $xpath->query($query, $person_dom);
                if($result_title != null){
			$pers_item_name = $result_title->item(0)->nodeValue;
                }
                    
                $person_query_name = urlencode(OpenContext_UTF8::charset_decode_utf_8($pers_item_name));
                
		
		    
                $uri_to_query = $host."/sets/facets/.atom?person=".$person_query_name;

                    
                $pers_feed_xml = file_get_contents($uri_to_query);
                                    
		@$xml = simplexml_load_string($pers_feed_xml);

		if($xml){
			$person_feed_dom = new DOMDocument("1.0", "utf-8");
			$person_feed_dom->loadXML($pers_feed_xml);
                
			$xpath_feed = new DOMXpath($person_feed_dom);
			
			// Register OpenContext's namespace
			$xpath_feed->registerNamespace("atom", OpenContext_OCConfig::get_namespace("atom"));
			$xpath_feed->registerNamespace("arch", OpenContext_OCConfig::get_namespace("arch", "person"));
			$xpath_feed->registerNamespace("oc", OpenContext_OCConfig::get_namespace("oc", "person"));
			$xpath_feed->registerNamespace("dc", OpenContext_OCConfig::get_namespace("dc"));
	
			$query = "/atom:feed/atom:entry";
			$result_entries = $xpath_feed->query($query, $person_feed_dom);
			$nodecount = 0;
			    
			foreach($result_entries AS $sum_entry){
				
				$entry_cat = $sum_entry->getElementsByTagNameNS(OpenContext_OCConfig::get_namespace("atom") , "category");
				//$entry_category = $entry_cat->item(0)->getAttributeNS("http://www.w3.org/2005/Atom" , "term");
				$entry_category = $entry_cat->item(0)->getAttribute("term");
				//$entry_category = $sum_entry->getAttribute("term");
				if($entry_category != "related person"){
					$new_node = $person_dom->importNode($sum_entry, true);
					
					if($entry_category == "category"){
						
						$class_label_nl = $sum_entry->getElementsByTagNameNS(OpenContext_OCConfig::get_namespace("atom"), "title");
						$class_name = $class_label_nl->item(0)->nodeValue;
						$class_icon = OpenContext_PersonAtom::class_icon_lookup($class_name);
						
						$class_node = $person_dom->createElementNS(OpenContext_OCConfig::get_namespace("oc", "person"), "oc:item_class");
						$class_label_node = $person_dom->createElementNS(OpenContext_OCConfig::get_namespace("oc", "person"), "oc:name");
						$class_label_node_val  = $person_dom->createTextNode($class_name);
						$class_label_node->appendChild($class_label_node_val);
						$class_node->appendChild($class_label_node);
						$class_icon_node = $person_dom->createElementNS(OpenContext_OCConfig::get_namespace("oc", "person"), "oc:iconURI");
						$class_icon_node_val = $person_dom->createTextNode($class_icon);
						$class_icon_node->appendChild($class_icon_node_val);
						$class_node->appendChild($class_icon_node);
						$new_node->appendChild($class_node);
					}
					
					$person_dom_root->item(0)->appendChild($new_node);
				}
				//$nodecount ++;
			}
		} 
		    
                $xml_string = $person_dom->saveXML();
                    
                $xml_string = str_replace("<default:", "<", $xml_string);
                $xml_string = str_replace("</default:", "</", $xml_string);
                $xml_string = str_replace('<entry xmlns:default="http://www.w3.org/1999/xhtml" xmlns:oc="http://about.opencontext.org/schema/person_schema_v1.xsd">', chr(13)."<entry>".chr(13), $xml_string);
		$xml_string = str_replace('<entry xmlns:default="http://www.w3.org/1999/xhtml">', chr(13)."<entry>".chr(13), $xml_string);

		    
		return $xml_string;
		
	}//end function
	
	//remove all child nodes
	function remove_children(&$node) {
		while ($node->firstChild) {
		  while ($node->firstChild->firstChild) {
		    $this->remove_children($node->firstChild);
		  }
		  $node->removeChild($node->firstChild);
		}
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
    
    
    private function setUTFconnection($db){
	    $sql = "SET collation_connection = utf8_unicode_ci;";
	    $db->query($sql, 2);
	    $sql = "SET NAMES utf8;";
	    $db->query($sql, 2);
    } 
    
    
    
    
}
