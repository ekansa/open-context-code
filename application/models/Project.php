<?php


//this class interacts with the database for accessing and changing project items
class Project {
    
    public $noid;
    public $projectUUID;
    
    public $mimeType;
    public $itemUUID;
    
    public $label;
    public $viewCount;
	 public $rank;
	 public $rankPopulation;
    public $createdTime;
    public $updatedTime;
    
	 public $editStatus; //editorial status, an integer
	 
    public $shortDes; //short description of the project
    public $longDes; //long description of the project
    public $spaceCount; //spatial unit count
    public $docCount; //diary / document count
    public $mediaCount; //media count
    public $totalViewCount; //total views
    public $rootUUID; //uuid of the root spatial item
    public $rootPath; //path to the project root (for searches)
    public $noProps; //no property message
    public $licenseURI; //URI to Creative Commons (or other standard) license
    
    public $archaeoML;
    public $atomFull;  //full atom item
    public $atomEntry; //small atom item
    
    public $kml_in_Atom; // add KML timespan to atom entry, W3C breaks validation
    public $xhtml_rel; // value for link rel attribute for XHTML version ("self" or "alternate")
    public $atom_rel; //value for link rel attribute for Atom version ("self" or "alternate")
    
    public $draftGeoTime; //array of draft geo coordinates
    
    public $newArchaeoML;
    
    const default_mimeType = "application/xhtml+xml";
    const OC_namespaceURI = "http://opencontext.org/schema/project_schema_v1.xsd";
    
    //get User data from database
    function getByID($id){
        
        $id = $this->security_check($id);
        $output = false; //no user
        $db_params = OpenContext_OCConfig::get_db_config();
        $db = new Zend_Db_Adapter_Pdo_Mysql($db_params);
		  $db->getConnection();
		  $this->setUTFconnection($db);
	
	
        $sql = 'SELECT *
                FROM projects
                WHERE project_id = "'.$id.'"
                LIMIT 1';
		
        $result = $db->fetchAll($sql, 2);
        if($result){
            
            $this->noid = $result[0]["noid"];
            $this->projectUUID = $result[0]["project_id"];
				$this->itemUUID = $result[0]["project_id"];
				$this->label = $result[0]["proj_name"];
				$this->mimeType = self::default_mimeType;
			 
				$this->shortDes = $result[0]["short_des"];
				$this->viewCount = $result[0]["view_count"];
				$this->totalViewCount = $result[0]["total_views"];
				$this->createdTime = $result[0]["accession"];
				$this->updatedTime = $result[0]["updated"];
				$this->archaeoML = $result[0]["proj_archaeoml"];
				$this->atomFull = $result[0]["proj_atom"];
				
				$this->editStatus = $result[0]["edit_status"];
				$this->rootPath = $result[0]["root_path"];
				
				if($this->editStatus == 0){
					 $this->draftGeoTime = Zend_Json::decode($result[0]["draftGeoTime"]);
				}
				
			/*
			$this->accentFix($this->archaeoML, "archaeoML", $db);	
			$this->accentFix($this->atomFull, "atom", $db);
			$oldAtom = $this->atomFull;
			$this->fullAtomCreate($this->archaeoML);
			if($oldAtom != $this->atomFull){
				$data = array("proj_atom" => $this->atomFull);
				$where = "project_id = '".$this->projectUUID."' ";
				$db->update("projects", $data, $where);
			}
			*/
		
			$this->xhtml_rel = "alternate";
			$this->atom_rel = "self";
	    
            $output = true;
        }
        
		  $db->closeConnection();
    
        return $output;
    }
    
    //add to the view count;
	 function addViewCount($id, $viewCount){
		  $id = $this->security_check($id);
		 
		  $db_params = OpenContext_OCConfig::get_db_config();
		  $db = new Zend_Db_Adapter_Pdo_Mysql($db_params);
		  $db->getConnection();
		  $this->setUTFconnection($db);
		  
		  
		  
		  $viewCount = $viewCount + 1;
		  $where_term = 'project_id = "'.$id.'"';
		  $data = array('view_count' => $viewCount);
		  
		  /*
		   //just to add geo data to pending projects without geo referencing
		   
		  if($id == "01D080DF-2F6B-4F59-BCF0-87543AC89574"){
				$draftGeo = array("minLat" => 30.297018,
										"minLon" => 25.48169,
										"maxLat" => 39.368279,
										"maxLon" => 41.258057,
										"timeStart" => -1400,
										"timeEnd" => -600
										);
				
				$draftGeo = array("minLat" => 31.498889,
										"minLon" => 35.785556,
										"maxLat" => 31.498889,
										"maxLon" => 35.785556,
										"timeStart" => -2700,
										"timeEnd" => 1200
										);
				$draftGeoJSON = Zend_Json::encode($draftGeo);
				$data["draftGeoTime"] = $draftGeoJSON;
		  }
		  */
		  
		  $n = $db->update('projects', $data, $where_term);
		  $this->viewCount = $viewCount;
		  $db->closeConnection();
		  return $viewCount;
	 }
	 
	 function rankProjectViewcounts($proj_uuid = false){
		  
		  if(!$proj_uuid){
				$proj_uuid = $this->projectUUID;
		  }

		  //spatial item is a simple xml object for an spatial item's Atom xml
		  $db_params = OpenContext_OCConfig::get_db_config();
		  $db = new Zend_Db_Adapter_Pdo_Mysql($db_params);
		  $db->getConnection();
		  
		  $rank = false;
			  
		  $sql = 'SELECT 1 + COUNT( * ) AS rank
			  FROM projects AS p1
			  JOIN projects AS p2 ON ( p1.total_views > p2.total_views
			  AND p2.project_id =  "'.$proj_uuid.'" ) 
			  LIMIT 1';
		  
		  $result = $db->fetchAll($sql, 2);
		  if($result){
				$rank_val = $result[0]["rank"];
				
				$query = 'SELECT COUNT(*) AS rowcount FROM projects WHERE projects.project_id != "0"
                AND projects.project_id != "2" ';
				$result_b = $db->fetchAll($query, 2);
				$total_pop = $result_b[0]["rowcount"];
				
				$this->rank = $rank_val;
				$this->rankPopulation = $total_pop;
				
				$ranking = array("rank"=>$rank_val, "pop"=>$total_pop);
		  }//end case with a result
		  $db->closeConnection();
		  return $ranking;
	}//end function
	 
	 
	 
    //used to fix legacy non utf8 problem
    function accentFix($xmlString, $XMLtype, $db){
	
		$stringArray = array(
			0 => array("bad" => "Christian Aug&amp;#233;", "good" => "Christian Augé"),
			1 => array("bad" => "G&#252;rdil", "good" => "Gürdil"),
			2 => array("bad" => "G&#xFC;rdil", "good" => "Gürdil"),
			3 => array("bad" => "G&amp;#252;rdil", "good" => "Gürdil"),
			4 => array("bad" => "Ã‡akÄ±rlar", "good" => "Çakırlar"),
			5 => array("bad" => "&#xC7;ak&#x131;rlar", "good" => "Çakırlar"),
			6 => array("bad" => "Ã‡igdem", "good" => "Çigdem"),
			7 => array("bad" => "GÃ¼rdil", "good" => "Gürdil"),
			8 => array("bad" => "JosÃ©", "good" => "José"),
			9 => array("bad" => "FustÃ©", "good" => "Fusté"),
			10 => array("bad" => "RogÃ©r", "good" => "Rogér"),
			11 => array("bad" => "BadÃ¨", "good" => "Badè"),
			12 => array("bad" => "Ã‡atalhÃ¶yÃ¼k", "good" => "Çatalhöyük"),
			13 => array("bad" => "straÃŸe", "good" => "straße"),
			14 => array("bad" => "straÃƒÅ¸e", "good" => "straße"),
			15 => array("bad" => "GroÃƒÅ¸e", "good" => "Große"),
			16 => array("bad" => "GroÃŸe", "good" => "Große"),
			17 => array("bad" => "PÄ±narbaÅŸÄ±", "good" => "Pınarbaşı"),
			18 => array("bad" => "&#xC7;igdem", "good" => "Çigdem")
			//19 => array("bad" => "Cigdem", "good" => "Çigdem"),
			//20 => array("bad" => "Gurdil", "good" => "Gürdil")
			);
	
		  //echo $xmlString;
		  $change = false;
		  foreach($stringArray as $checks){
			  $badString = $checks["bad"];
			  $goodString = $checks["good"];
			  //echo $badString ." ".$goodString;
			  if(stristr($xmlString, $badString)){
					$xmlString = str_replace($badString, $goodString, $xmlString);
					$change = true;
			  }
		  }
	
		  if($change){
				$newXML = $xmlString;
				@$xml = simplexml_load_string($newXML);
				if($XMLtype == "atom" && $xml){
			  $this->atomFull = $newXML;
			  
			  $data = array("proj_atom" => $newXML);
			  $where = " project_id = '".$this->itemUUID."' ";
			  $db->update("projects", $data, $where);
				}
				elseif($XMLtype == "archaeoML" && $xml){
			  $this->archaeoML = $newXML;
			  $data = array("proj_archaeoml" => $newXML);
			  $where = " project_id = '".$this->itemUUID."' ";
			  $db->update("projects", $data, $where);
				}
		  }
	
    }//end function
    
    
    
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
    
    
    function versionUpdate($id, $db = false){
		
		if(!$db){
			$db_params = OpenContext_OCConfig::get_db_config();
			$db = new Zend_Db_Adapter_Pdo_Mysql($db_params);
			$db->getConnection();
			$this->setUTFconnection($db);
		}
		
		$sql = 'SELECT proj_archaeoml AS archaeoML
					FROM projects
					WHERE project_id = "'.$id.'"
					LIMIT 1';
			
			$result = $db->fetchAll($sql, 2);
			if($result){
			$xmlString = $result[0]["archaeoML"];
			OpenContext_DeleteDocs::saveBeforeUpdate($id, "project", $xmlString);
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
		  if(!$this->archaeoML){
			  $this->archaeoML = $this->newArchaeoML;
		  }
		
		  $data = array("noid" => $this->noid,
		      "project_id" => $this->itemUUID,
		      "proj_name" => $this->label,
		      "def_lic_id" => $this->licenseURI,
		      "root_uuid" => $this->rootUUID,
		      "root_path" => $this->rootPath,
		      "short_des" => $this->shortDes,
		      "long_des" => $this->longDes,
		      "space_cnt" => $this->spaceCount,
		      "diary_cnt" => $this->docCount,
		      "media_cnt" => $this->mediaCount,
		      "noprop_mes" => $this->noProps,
		      "accession" => $this->createdTime
		      );
	
		  if($versionUpdate){
			  $this->versionUpdate($this->itemUUID, $db); //save previous version history
			  unset($data["created"]);
		  }
		
		  //if(OpenContext_OCConfig::need_bigString($this->archaeoML)){
		  if(false){
			  /*
			  This gets around size limits for inserting into MySQL.
			  It breaks up big inserts into smaller ones, especially useful for HUGE strings of XML
			  */
			  $bigString = new BigString;
			  $bigString->saveCurrentBigString($this->itemUUID, "archaeoML", "project", $this->archaeoML, $db);
			  $data["proj_archaeoml"] = OpenContext_OCConfig::get_bigStringValue();
		  }
		  else{
			  $data["proj_archaeoml"] = $this->archaeoML;
		  }
		
		  //if(OpenContext_OCConfig::need_bigString($this->atomFull)){
		  if(false){
			  /*
			  This gets around size limits for inserting into MySQL.
			  It breaks up big inserts into smaller ones, especially useful for HUGE strings of XML
			  */
			  $bigString = new BigString;
			  $bigString->saveCurrentBigString($this->itemUUID, "atomFull", "project", $this->atomFull, $db);
			  $data["proj_atom"] = OpenContext_OCConfig::get_bigStringValue();
		  }
		  else{
			  $data["proj_atom"] = $this->atomFull;
		  }
	
	
	 
		 $success = false;
		 try{
			 $db->insert("projects", $data);
			 $success = true;
		 }catch(Exception $e){
			 $success = false;
			 $where = array();
			 $where[] = 'project_id = "'.$this->itemUUID.'" ';
			 $db->update("projects", $data, $where);
			 $success = true;
			 //echo $e;
		 }
	
		$db->closeConnection();
		return $success;
    }//end function
    
    
    
	 function getXMLProjectDescriptions($projectUUID, $archaeML_string = false){
		  
		  if(!$archaeML_string){
				$archaeML_string = $this->getItemXML($projectUUID);
		  }
		  
		  @$itemXML = simplexml_load_string($archaeML_string);
		  if($itemXML != false){
				$nameSpaceArray = $this->nameSpaces();
				foreach($nameSpaceArray as $prefix => $uri){
					 @$itemXML->registerXPathNamespace($prefix, $uri);
				}
				
				// get project short description
				foreach($itemXML->xpath("//arch:notes/arch:note[@type='short_des']/arch:string") as $xpathResult){
					$this->shortDes = (string)$xpathResult;
				}
				// get project long description
				foreach($itemXML->xpath("//arch:notes/arch:note[@type='long_des']/arch:string") as $xpathResult){
					$this->longDes = (string)$xpathResult;
				}
				return array("short" => strip_tags($this->shortDes), "long" => strip_tags($this->longDes));
		  }
		  else{
				return false;
		  }
	 }
	 
	 
    
    
    
    function nameSpaces(){
		$nameSpaceArray = array("oc" => self::OC_namespaceURI,
			   "dc" => OpenContext_OCConfig::get_namespace("dc"),
			   "arch" => OpenContext_OCConfig::get_namespace("arch", "project"),
			   "gml" => OpenContext_OCConfig::get_namespace("gml"),
			   "kml" => OpenContext_OCConfig::get_namespace("kml"));
	
		return $nameSpaceArray;
    }
    
    
    function DOM_AtomCreate($archaeML_string){
		
		$host = OpenContext_OCConfig::get_host_config();
		$baseURI = $host."/projects/";
		
		$projItem = simplexml_load_string($archaeML_string);
	
		// Register OpenContext's namespace
		//$projItem->registerXPathNamespace("oc", OpenContext_OCConfig::get_namespace("oc", "spatial"));
		
		$projItem->registerXPathNamespace("oc", self::OC_namespaceURI);
		
		// Register OpenContext's namespace
		$projItem->registerXPathNamespace("arch", OpenContext_OCConfig::get_namespace("arch", "project"));
	
		// Register Dublin Core's namespace
		$projItem->registerXPathNamespace("dc", OpenContext_OCConfig::get_namespace("dc"));
	
		// Register the GML namespace
		$projItem->registerXPathNamespace("gml", OpenContext_OCConfig::get_namespace("gml"));
		
		
		// get the item_id
		foreach($projItem->xpath("//arch:project/@UUID") as $item_result) {
			$uuid = $item_result."";
		}
	
	
		// get the item_label
		foreach ($projItem->xpath("//arch:project/arch:name/arch:string") as $item_label) {
			$item_label = $item_label."";
		}
		
		if(!stristr($item_label, "(Overview)")){
		    $item_label .= " (Overview)";
		}
		
		
		//project name
		foreach ($projItem->xpath("//arch:project/oc:metadata/oc:project_name") as $project_name) {
			$project_name = $project_name."";
		}
	
		$creators = $projItem->xpath("//arch:project/oc:metadata/dc:creator");
		$contributors = $projItem->xpath("/arch:project/oc:metadata/dc:contributor");
		
		$pubDate = false;
		foreach($projItem->xpath("//oc:metadata/dc:date") as $pubDate) {
		    $pubDate = $pubDate."";
		}
	
	
	
		$atomEntryDoc = new DOMDocument("1.0", "utf-8");
	
		$rootEntry = $atomEntryDoc->createElementNS("http://www.w3.org/2005/Atom", "entry");
		
		// add newlines and indent the output - this is at least useful for debugging and making the output easier to read
		$atomEntryDoc->formatOutput = true;
		
		$rootEntry->setAttribute("xmlns:georss", OpenContext_OCConfig::get_namespace("georss"));
		$rootEntry->setAttribute("xmlns:gml", OpenContext_OCConfig::get_namespace("gml"));
		$rootEntry->setAttribute("xmlns:kml", OpenContext_OCConfig::get_namespace("kml"));
		//$rootEntry->setAttribute("xmlns:arch", OpenContext_OCConfig::get_namespace("arch", "media"));
		//$rootEntry->setAttribute("xmlns:oc", self::OC_namespaceURI);
		//$rootEntry->setAttribute("xmlns:oc", OpenContext_OCConfig::get_namespace("oc", "spatial"));
		//$rootEntry->setAttribute("xmlns:dc", OpenContext_OCConfig::get_namespace("dc"));
		//$rootEntry->setAttribute("xmlns:xhtml", OpenContext_OCConfig::get_namespace("xhtml"));
	
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
		$entryCat->setAttribute("term", "project");
		$rootEntry->appendChild($entryCat);
	
		// entry atom Summary element
		$entrySummary = $atomEntryDoc->createElement("summary");
		$summaryTextContent =  strip_tags($this->shortDes);
		
		$summaryText =  $atomEntryDoc->CreateTextNode($summaryTextContent);
		$entrySummary->appendChild($summaryText);
		$rootEntry->appendChild($entrySummary);
		
		/*
		We've done the hard part of constructing the Atom Entry. The entry is now ready for integrating into atom feeds.
		*/
		$atomEntryDoc->formatOutput = true;
		$atomEntryXML = $atomEntryDoc->saveXML();
		
		$this->atomEntry = $atomEntryXML;
		
    }//end make projectAtomCreate function
    
    
    
    function fullAtomCreate($projectArchaeoML_string){
		
		$baseURI = OpenContext_OCConfig::get_host_config();
		$baseURI .= "/projects/";
		
		$proj_dom = new DOMDocument("1.0", "utf-8");
		$proj_dom->loadXML($projectArchaeoML_string);
		
		$xpath = new DOMXpath($proj_dom);
			
		// Register OpenContext's namespace
		$xpath->registerNamespace("arch", OpenContext_OCConfig::get_namespace("arch", "project"));
		$xpath->registerNamespace("oc", OpenContext_OCConfig::get_namespace("oc", "project"));
		$xpath->registerNamespace("dc", OpenContext_OCConfig::get_namespace("dc", "project"));
		
		$query = "/arch:project/@UUID";
		$projectUUIDnode = $xpath->query($query, $proj_dom);
		$projectUUID = $projectUUIDnode->item(0)->nodeValue;
		
		$query = "/arch:project/arch:name/arch:string";
		$result_title = $xpath->query($query, $proj_dom);
			
		if($result_title != null){
		    $proj_item_name = $result_title->item(0)->nodeValue;
		}
		
		$query = "//arch:notes/arch:note[@type='long_des']/arch:string";
		$result_des = $xpath->query($query, $proj_dom);
			
		if($result_des != null){
		    $long_des = $result_des->item(0)->nodeValue;
		}
		
		
		$query = "//oc:metadata/oc:project_name";
		$result_proj = $xpath->query($query, $proj_dom);
			
		if($result_proj != null){
		    $project_name = $result_proj->item(0)->nodeValue;
		}
			
		$query = "//oc:metadata/dc:creator";
		$result_create = $xpath->query($query, $proj_dom);
		$author_array = array();	
			
		foreach($result_create AS $res_creators){
		    $author_array[] = $res_creators->nodeValue;
		}
		
		$query = "//oc:metadata/dc:contributor";
		$result_contrib = $xpath->query($query, $proj_dom);	
		$contributor_array = array();
			
		foreach($result_contrib AS $act_contrib){
		    $contributor_array[] = $act_contrib->nodeValue;
		}
	
		$query = "//oc:metadata/dc:title";
		$result_dctitle = $xpath->query($query, $proj_dom);	
		     
		if($result_dctitle!= null){
		    $project_title = $result_dctitle->item(0)->nodeValue;
		}
	
		$query = "//oc:manage_info/oc:mediaCount";
		$result_media = $xpath->query($query, $proj_dom);		
		     
		if($result_media!= null){
		    $mediaCount = $result_media->item(0)->nodeValue;
		}
	
		$query = "//oc:manage_info/oc:diaryCount";
		$result_diary = $xpath->query($query, $proj_dom);		
		     
		if($result_media!= null){
		    $diaryCount = $result_diary->item(0)->nodeValue;
		}
	
	
		$query = "//oc:manage_info/oc:projGeoPoly";
		$result_poly = $xpath->query($query, $proj_dom);		
		
		$proj_poly = null;
		     
		if($result_poly != null){
		    foreach($result_poly as $act_poly){
			$proj_poly = $act_poly->nodeValue;
		    }
		}
		
		$query = "//oc:manage_info/oc:projGeoPoint";
		$result_point = $xpath->query($query, $proj_dom);		
		     
		if($result_poly!= null){
		    $proj_point = $result_point->item(0)->nodeValue;
		}
		
	
	
		//done querying old xml version
		
		$proj_entry_title = $project_title;
		$proj_feed_title = "Open Context Project/Collection Record: ".$project_name;
		
		//echo "<br/>".$proj_feed_title."<br/>".$proj_entry_title."<br/>";
		
		
		$atomFullDoc = new DOMDocument("1.0", "utf-8");
		
		$root = $atomFullDoc->createElementNS(OpenContext_OCConfig::get_namespace("atom"), "feed");
		
		// add newlines and indent the output - this is at least useful for debugging and making the output easier to read
		$atomFullDoc->formatOutput = true;
		
		$root->setAttribute("xmlns:georss", "http://www.georss.org/georss");
		$root->setAttribute("xmlns:gml", "http://www.opengis.net/gml");
	       
		$atomFullDoc->appendChild($root);
	
		// Feed Title 
		$feedTitle = $atomFullDoc->createElement("title");
		$feedTitleText = $atomFullDoc->createTextNode($proj_feed_title);
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
		
		$linkURI = $baseURI . $projectUUID. ".atom";
		// feed (self) link element
		$feedLink = $atomFullDoc->createElement("link");
		$feedLink->setAttribute("rel", "self");
		$feedLink->setAttribute("href", $linkURI);
		$root->appendChild($feedLink);
		
		// feed id
		$feedId = $atomFullDoc->createElement("id");
		$feedIdText = $atomFullDoc->createTextNode($baseURI . $projectUUID);
		$feedId->appendChild($feedIdText);
		$root->appendChild($feedId);
		
		
		$feed_entry = $atomFullDoc->createElement("entry");
		$root->appendChild($feed_entry);
		
		$entryCat = $atomFullDoc->createElement("category");
		$entryCat->setAttribute("term", "project_overview");
		$feed_entry->appendChild($entryCat);
		
		
		$entry_title_el = $atomFullDoc->createElement("title");
		$entry_title_text = $atomFullDoc->createTextNode($proj_entry_title);
		$entry_title_el->appendChild($entry_title_text);
		$feed_entry->appendChild($entry_title_el);
		
		$entry_id_el = $atomFullDoc->createElement("id");
		$entry_id_text = $atomFullDoc->createTextNode($baseURI . $projectUUID);
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
		
		
		//create GeoRSS information for the item
		$entry_geo = $atomFullDoc->createElement("georss:point");
		$entry_geo_val = $atomFullDoc->createTextNode($proj_point);
		$entry_geo->appendChild($entry_geo_val);
		$feed_entry->appendChild($entry_geo);
		
		if($proj_poly != null){
			$entry_geo_all_poly = $atomFullDoc->createElement("georss:where");
			$entry_geo_poly = $atomFullDoc->createElement("gml:Polygon");
			$entry_geo_ext = $atomFullDoc->createElement("gml:exterior");
			$entry_geo_lr = $atomFullDoc->createElement("gml:LinearRing");
			$entry_geo_pos = $atomFullDoc->createElement("gml:posList");
			$entry_geo_poly_val = $atomFullDoc->createTextNode($proj_poly);
			$entry_geo_pos->appendChild($entry_geo_poly_val);
			$entry_geo_lr->appendChild($entry_geo_pos);
			$entry_geo_ext->appendChild($entry_geo_lr);
			$entry_geo_poly->appendChild($entry_geo_ext);
			$entry_geo_all_poly->appendChild($entry_geo_poly);
			$feed_entry->appendChild($entry_geo_all_poly);
		}
		
		
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
		<h2>'.$proj_item_name.'</h2>
		<p>Number of associated media items: ('.$mediaCount.') Number of Associated Narrative Texts: ('.$diaryCount.')</p><br/>
		<p><strong>Description of this Project / Collection:</strong></p>
		'.$long_des.' 
		</div>
		';
		
		// add the XHTML content string
		$contentFragment = $atomFullDoc->createDocumentFragment();
		$contentFragment->appendXML($content_div_text);  // $atom_content from short atom entry
		$content_el->appendChild($contentFragment);
		$feed_entry->appendChild($content_el);
		
		//now add ArchaeoML String
		$proj_archaeoML = str_replace('<?xml version="1.0"?>', "", $projectArchaeoML_string);
		$proj_archaeoML = str_replace('<?xml version="1.0" encoding="utf-8"?>', "", $projectArchaeoML_string);
		$arch_contentFragment = $atomFullDoc->createDocumentFragment();
		$arch_contentFragment->appendXML($proj_archaeoML);
		$feed_entry->appendChild($arch_contentFragment);
		
		$atom_xml_string = $atomFullDoc->saveXML();
		
		$atom_xml_string = str_replace("<default:", "<", $atom_xml_string);
		$atom_xml_string = str_replace("</default:", "</", $atom_xml_string);
		$atom_xml_string = str_replace('<content xmlns:default="http://www.w3.org/1999/xhtml"', "<content ", $atom_xml_string);
		
		$this->atomFull = $atom_xml_string;
		return $atom_xml_string ;
    }//makes a new atom full entry for the project
    
    
    
    function getEditStatusByID($id){
        
        $id = $this->security_check($id);
        $output = false; //no user
        $db_params = OpenContext_OCConfig::get_db_config();
        $db = new Zend_Db_Adapter_Pdo_Mysql($db_params);
		  $db->getConnection();
		  $this->setUTFconnection($db);
	
	
        $sql = 'SELECT *
                FROM projects
                WHERE project_id = "'.$id.'"
                LIMIT 1';
		
        $result = $db->fetchAll($sql, 2);
        if($result){
            
            $this->noid = $result[0]["noid"];
            $this->projectUUID = $result[0]["project_id"];
				$this->itemUUID = $result[0]["project_id"];
				$this->label = $result[0]["proj_name"];
				$this->mimeType = self::default_mimeType;
				$this->shortDes = $result[0]["short_des"];
				$this->viewCount = $result[0]["view_count"];
				$this->totalViewCount = $result[0]["total_views"];
            $this->createdTime = $result[0]["accession"];
            $this->updatedTime = $result[0]["updated"];
				$this->editStatus = $result[0]["edit_status"];
				return $this->editStatus;
		  }
		  else{
				return false;
		  }
	 }
	 
    
    function db_find_personID($personName){
	
		  $personID = false;
		  $db_params = OpenContext_OCConfig::get_db_config();
				 $db = new Zend_Db_Adapter_Pdo_Mysql($db_params);
		  $db->getConnection();
		  $this->setUTFconnection($db);
		  
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
	    $sql = "SET collation_connection = utf8_general_ci;";
	    $db->query($sql, 2);
	    $sql = "SET NAMES utf8;";
	    $db->query($sql, 2);
    } 
    
    
    
    
}
