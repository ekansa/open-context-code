<?php


//this class interacts with the database for accessing and changing Subjects (location and object items)
class Subject {
    
	 public $db;
	 
    /*
     General item metadata
    */
    public $noid;
    public $projectUUID;
    public $sourceID;
    public $itemUUID;
    public $label;
    
    /*
    Location / object specific
    */
    public $classID; //identifier for a class
    public $className; //name for a class
    public $contain_hash;
    public $contextPath; //path of context
    
	 public $repo; //repository, used for keeping data in Github 
	 
	 
    public $schemaFix;
    public $viewCount;
    public $createdTime;
    public $updatedTime;
    public $atomEntry;
    public $archaeoML;
    
    public $kml_in_Atom; // add KML timespan to atom entry, W3C breaks validation
    public $xhtml_rel = "alternate"; // value for link rel attribute for XHTML version ("self" or "alternate")
    public $atom_rel = "self"; //value for link rel attribute for Atom version ("self" or "alternate")
    
    public $geoCurrent; //check if geo-reference is current.
    
    
    public $itemFound;
    
    public $errors = array(); //array to put in error messages, incase solr messes up for instance
    
    const OC_namespaceURI = "http://opencontext.org/schema/space_schema_v1.xsd";
    const maxRepoSize = 45000; //maximum safe size of a repo, before github gets mad
	 
    //get User data from database
    function getByID($id){
        
        $id = $this->security_check($id);
        $output = false; //no user
        
        $db = $this->startDB();
        
        $sql = 'SELECT *
                FROM space 
                WHERE space.uuid = "'.$id.'"
                LIMIT 1';
		
        $result = $db->fetchAll($sql, 2);
        if($result){
            
            $this->noid = $result[0]["noid"];
            $this->projectUUID = $result[0]["project_id"];
            $this->sourceID = $result[0]["source_id"];
            $this->classID = $result[0]["class_uuid"];
				$this->itemUUID = $result[0]["uuid"];
				$this->label = $result[0]["space_label"];
				$this->viewCount = $result[0]["view_count"];
				$this->repo = $result[0]["repo"];
				
            $this->createdTime = $result[0]["created"];
            $this->updatedTime = $result[0]["updated"];
	    
				$this->atomEntry = $result[0]["atom_entry"];
				$this->archaeoML = $result[0]["archaeoML"];
			 
				@$xml = simplexml_load_string($this->archaeoML);
				if(!$xml  || trim($this->archaeoML) == "big value"){
					 $bigString = new BigString;
					 $this->archaeoML = $bigString->get_CurrentBigString($this->itemUUID, "archaeoML", $db);
				}
	    
				//$this->accentFix($this->archaeoML, "archaeoML");
				//$this->accentFix($this->atomEntry, "atom");
	
				$this->xhtml_rel = "alternate";
				$this->atom_rel = "self";
				
				$output = true;
        }
        
		  $db->closeConnection();
    
		  if(!$output){
				$this->itemUUID = $id;
		  }
			
		  $this->itemFound = $output;
        return $output;
    }
    
    
	 
	 
	 
    //get created time from database
    function getCreatedTime($id){
        
        $id = $this->security_check($id);
        $db = $this->startDB();
        
        $sql = 'SELECT created
                FROM space
                WHERE uuid = "'.$id.'"
                LIMIT 1';
		
        $result = $db->fetchAll($sql, 2);
        if($result){
        
            $this->createdTime = $result[0]["created"];
            $output = true;
        }
        
		  $db->closeConnection();
    }
    
    
    
    
    function versionUpdate($id, $db = false){
	
		  $db = $this->startDB();
		  
		  $sql = 'SELECT *
							FROM space
							WHERE uuid = "'.$id.'"
							LIMIT 1';
			  
		  $result = $db->fetchAll($sql, 2);
		  if($result){
				$xmlString = $result[0]["archaeoML"];
				OpenContext_DeleteDocs::saveBeforeUpdate($id, "spatial", $xmlString);
		  }	
    }//end function
    
    
    
    
	 
	 //git hub has pretty strict size restrictions this puts subjects into different repos, if the project is big
	 function assignRepository($projectUUID, $db = false){
		  $db = $this->startDB();
		  
		  $output = false;
		  $sql = "SELECT COUNT(uuid) AS idCount
					 FROM  space 
					 WHERE project_id = '$projectUUID'
					 GROUP BY  project_id; ";
		  
		  $result = $db->fetchAll($sql, 2);
        if($result){
				$idCount = $result[0]["idCount"];
				$repoID = floor(($idCount / self::maxRepoSize))+1;
				$output = "opencontext-".$projectUUID;
				if($repoID > 1){
					 $output .= "-".$repoID;
				}
		  }
		  return $output;
	 }
	 
	 //create a new spatial item
    function createUpdate($versionUpdate){
        
        $db = $this->startDB();
			
		  if(!$this->noid){
				$this->noid = false;
		  }
			
		  $repoID = $this->assignRepository($this->projectUUID, $db); //make an ide for the repository
		  
		  $data = array("noid" => $this->noid,
					  "project_id" => $this->projectUUID,
					  "source_id" => $this->sourceID,
					  "class_uuid" => $this->classID, 
					  "uuid" => $this->itemUUID,
					  "space_label" => $this->label,
					  "contain_hash" => $this->contain_hash,
					  "repo" => $repoID,
					  "created" => $this->createdTime,
					  "atom_entry" => $this->atomEntry
					  );
		  
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
				$bigString->saveCurrentBigString($this->itemUUID, "archaeoML", "spatial", $this->archaeoML, $db);
				$data["archaeoML"] = OpenContext_OCConfig::get_bigStringValue();
				$updateOK = true;
		  }
		  else{
				
				$data["archaeoML"] = $this->archaeoML;
				$updateOK = true;
		  }
	  
		  $success = false;
		  try{
				$db->insert("space", $data);
				$success = true;
		  }catch(Exception $e){
				$success = false;
				$where = array();
				$where[] = 'uuid = "'.$this->itemUUID.'" ';
				$db->update("space", $data, $where);
				$success = $this->getByID($this->itemUUID);
		  }
	  
		  $db->closeConnection();
		  return $success;
    }//end function
    
    
     //used to fix legacy non utf8 problem
    function accentFix($xmlString, $XMLtype){
	
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
			17 => array("bad" => "PÄ±narbaÅŸÄ±", "good" => "Pınarbaşı")
			);
	
		  //echo $xmlString;
		  $change = false;
		  foreach($stringArray as $checks){
			  $badString = $checks["bad"];
			  $goodString = $checks["good"];
			  //echo $badString ." ".$goodString;
			  if(mb_stristr($xmlString, $badString)){
					//echo "here!!";
					//$xmlString = str_replace($badString, $goodString, $xmlString);
					$xmlString = mb_eregi_replace($badString, $goodString, $xmlString);
					$change = true;
			  }
		  }
		  
		  if($change){
				$newXML = $xmlString;
				@$xml = simplexml_load_string($newXML);
				if($XMLtype == "atom" && $xml){
					 $this->atomEntry = $newXML;
					 $this->update_atom_entry();
				}
				elseif($XMLtype == "archaeoML" && $xml){
					 $this->archaeoML = $newXML;
					 $this->committ_update_archaeoML();
				}
		  }
	
    }//end function
    
    
    
    //useful function for updating archaeoML, especially if new children items added
    function updateArchaeoML($id, $archaeoML, $db = false){
		  $db = $this->startDB();
		  
		  @$xmlOK = simplexml_load_string($archaeoML);
		  $status = false; //not a success
		  
		  if($xmlOK){ //only do this if XML is valid
				$this->versionUpdate($id, $db); //save the existing version before messing with it
				$where = array();
				$data = array();
				if(OpenContext_OCConfig::need_bigString($archaeoML)){
					 /*
					 This gets around size limits for inserting into MySQL.
					 It breaks up big inserts into smaller ones, especially useful for HUGE strings of XML
					 */
					 $bigString = new BigString;
					 $bigString->saveCurrentBigString($id, "archaeoML", "spatial", $archaeoML, $db);
					 $data["archaeoML"] = OpenContext_OCConfig::get_bigStringValue();
				}
				else{
					 $data["archaeoML"] = $archaeoML;
				}
				
				$where[] = 'uuid = "'.$id.'" ';
				$db->update("space", $data, $where);
				$status = true;
		  }
		  unset($xmlOK);
		  return $status;
    }//end function
    
    
    
    
    //this function gets an item's Atom entry. It's used for making the general
    //feed read by the CDL's archival services.
    function getItemEntry($id){
		  $this->getByID($id);
		  if(strlen($this->archaeoML)<10 || strlen($this->atomEntry)<10){
				$this->archaeoML_update($this->archaeoML);
				$fullAtom = $this->DOM_spatialAtomCreate($this->archaeoML);
				$this->update_atom_entry();
		  }
		  
		  return $this->atomEntry;
    }
    
    
    //this function gets an item's ArchaeoML. It's used for indexing in Solr
    function getItemXML($id){
		  $found = $this->getByID($id);
		  if($found){
				if(strlen($this->archaeoML)<10){
					 $this->archaeoML_update($this->archaeoML);
					 $fullAtom = $this->DOM_spatialAtomCreate($this->archaeoML);
					 $this->update_atom_entry();
				}
				
				$this->booleanFix();
				return $this->archaeoML;
		  }
		  else{
				return false;
		  }
    }
    
    
    
    
    function update_atom_entry(){
	
		  $updateOK = false;
		  $db = $this->startDB();
	
		  @$xml = simplexml_load_string($this->atomEntry); 
		  if($xml){
				$data = array("atom_entry" => $this->atomEntry);
				$where = array();
				$where[] = 'uuid = "'.$this->itemUUID.'" ';
				$db->update("space", $data, $where);
				$updateOK = true;
		  }
		  
		  unset($spatialItem);
		  $db->closeConnection();
		  return $updateOK;
    }
    
    
    function nameSpaces(){
		  $nameSpaceArray = array("oc" => self::OC_namespaceURI,
					  "dc" => OpenContext_OCConfig::get_namespace("dc"),
					  "arch" => OpenContext_OCConfig::get_namespace("arch", "spatial"),
					  "gml" => OpenContext_OCConfig::get_namespace("gml"),
					  "kml" => OpenContext_OCConfig::get_namespace("kml"));
		  
		  return $nameSpaceArray;
    }
    
    
    
	function getClassName($archaeML_string){
		$archaeML_string = $this->namespace_fix($archaeML_string);
		@$spatialItem = simplexml_load_string($archaeML_string);
		$spatialItem->registerXPathNamespace("oc", self::OC_namespaceURI);
		
		// Register OpenContext's namespace
		$spatialItem->registerXPathNamespace("arch", OpenContext_OCConfig::get_namespace("arch", "spatial"));
		
		$item_class = false;
		foreach ($spatialItem->xpath("//arch:spatialUnit/oc:item_class/oc:name") as $item_class) {
			$item_class = $item_class."";
		}
		return $item_class;
	}
	
	function DOM_spatialAtomCreate($archaeML_string){
		
		$host = OpenContext_OCConfig::get_host_config();
		$baseURI = $host."/subjects/";
		
		$archaeML_string = $this->namespace_fix($archaeML_string);
		
		
		@$spatialItem = simplexml_load_string($archaeML_string);
	
		if(!$spatialItem){
		    $this->atomEntry = false;
		    $this->archaeoML = false;
		    return false;
		}
	
		// Register OpenContext's namespace
		//$spatialItem->registerXPathNamespace("oc", OpenContext_OCConfig::get_namespace("oc", "spatial"));
		
		$spatialItem->registerXPathNamespace("oc", self::OC_namespaceURI);
		
		// Register OpenContext's namespace
		$spatialItem->registerXPathNamespace("arch", OpenContext_OCConfig::get_namespace("arch", "spatial"));
	
		// Register Dublin Core's namespace
		$spatialItem->registerXPathNamespace("dc", OpenContext_OCConfig::get_namespace("dc"));
	
		// Register the GML namespace
		$spatialItem->registerXPathNamespace("gml", OpenContext_OCConfig::get_namespace("gml"));
		
		
		// get the item_id
		foreach($spatialItem->xpath("//arch:spatialUnit/@UUID") as $item_result) {
			$uuid = $item_result."";
		}
	
		// get the item_id
		foreach($spatialItem->xpath("//arch:spatialUnit/@ownedBy") as $item_result) {
			$project_id = $item_result."";
		}
	
	
		// get the item_label
		foreach ($spatialItem->xpath("//arch:spatialUnit/arch:name/arch:string") as $item_label) {
			$item_label = $item_label."";
		}
		
		//project name
		foreach ($spatialItem->xpath("//arch:spatialUnit/oc:metadata/oc:project_name") as $project_name) {
			$project_name = $project_name."";
		}
	
		// get the item class
		foreach ($spatialItem->xpath("//arch:spatialUnit/oc:item_class/oc:name") as $item_class) {
			$item_class = $item_class."";
		}
	
		if (!$spatialItem->xpath("//arch:spatialUnit/oc:context/oc:tree")) {
			$default_context_path = "ROOT";  // note: variable $default_context_path used later in abreviated Atom feed
		}	
	
		// For non-root-level items:
		// Get the default context path (there should only be one.)
		// Also index the hierarchy levels - def_context_*
		$j = 0; //used to generate 'def_context_*' fields in solr
		$default_context_path = "";
		if ($spatialItem->xpath("//arch:spatialUnit/oc:context/oc:tree[@id='default']")) {
			foreach ($spatialItem->xpath("//arch:spatialUnit/oc:context/oc:tree[@id='default']/oc:parent/oc:name") as $path) {
				$default_context_path .= $path . "/";
		
			}
		}
	
		
		
		$user_tags = array();
		$count_public_tags = 0; // value used to help calculate interest_score	
		if ($spatialItem->xpath("//arch:spatialUnit/oc:social_usage/oc:user_tags/oc:tag")) {
			foreach ($spatialItem->xpath("//arch:spatialUnit/oc:social_usage/oc:user_tags/oc:tag[@type='text' and @status='public']") as $tag) { // loop through and get the tags. 
				foreach ($tag->xpath("oc:name") as $user_tag) {
					$user_tags[] .= $user_tag; // array of tags to be used later for Atom feed entry
				}
				foreach ($tag->xpath("oc:tag_creator/oc:creator_name") as $tag_creator_name) {
					$tag_creator_name = $tag_creator_name."";
				}
				foreach ($tag->xpath("oc:tag_creator/oc:set_label") as $tag_set_label) {
					$tag_set_label = $tag_set_label."";
				}
			$count_public_tags++; // used to help calculate interest_score	
			}
		}
	
	
		$creators = $spatialItem->xpath("//arch:spatialUnit/oc:metadata/dc:creator");
		$contributors = $spatialItem->xpath("/arch:spatialUnit/oc:metadata/dc:contributor");
		
		
		
		
		
		foreach ($spatialItem->xpath("//arch:spatialUnit/oc:metadata/oc:geo_reference") as $geo_reference) {
			foreach ($geo_reference->xpath("oc:geo_lat") as $geo_lat) {
				$geo_lat = (string)$geo_lat;
			}
			foreach ($geo_reference->xpath("oc:geo_long") as $geo_long) {
				$geo_long = (string)$geo_long;
			}
		}//end loop through geo
	
		// polygon
		$geo_polygon = false;
		if ($spatialItem->xpath("//arch:spatialUnit/oc:metadata/oc:geo_reference/oc:metasource[@ref_type='self']")) {
			$self_geo_reference = true; // this value is used to calculate interesting_score.
			
			if ($spatialItem->xpath("//arch:spatialUnit/oc:metadata/oc:geo_reference/gml:Polygon/gml:exterior/gml:LinearRing/gml:posList")) {
				$geo_polygon = true; // this value is used to calculate interesting_score. and also in the Atom generation code
				foreach ($spatialItem->xpath("//arch:spatialUnit/oc:metadata/oc:geo_reference/gml:Polygon/gml:exterior/gml:LinearRing/gml:posList") as $polygon_pos_list ) {
					//echo "polygon_pos_list: " . $polygon_pos_list;
					//echo "<br/>";
				}
			}
		}
	
	
		//get publication date, in XML
		$pubDateXML = false;
		foreach($spatialItem->xpath("//oc:pub_date") as $pdate) {
		    $pubDateXML = $pdate."";
		    //echo "here! $pubDateXML ".chr(13).chr(13);
		}
	
	
	 //get linked data 
	 if ($spatialItem->xpath("//oc:linkedData/oc:relationLink")){
		  $linkedData = array();
		  foreach($spatialItem->xpath("//oc:linkedData/oc:relationLink") as $links){
				$actLink = array();
				foreach($links->xpath("@href") as $relURI){
					 $actLink["relURI"] = (string)$relURI;
				}
				foreach($links->xpath("oc:vocabulary") as $relVoc){
					 $actLink["relVocab"] = (string)$relVoc;
				}
				foreach($links->xpath("oc:label") as $relLab){
					 $actLink["relLabel"] = (string)$relLab;
				}
				
				//check for a target link
				if($links->xpath("oc:targetLink")){
					 foreach($links->xpath("oc:targetLink") as $target){
						  foreach($target->xpath("@href") as $targURI){
								$actLink["targURI"] = (string)$targURI;
						  }
						  foreach($target->xpath("oc:vocabulary") as $targVoc){
								$actLink["targVocab"] = (string)$targVoc;
						  }
						  foreach($target->xpath("oc:label") as $targLab){
								$actLink["targLabel"] = (string)$targLab;
						  }
					 }//end loop through targets
				}
				else{
					 $actLink = false; //if no target, then not a full linked data ref
				}
				
				if(is_array($actLink)){
					 $linkedData[] = $actLink;
					 unset($actLink);
				}
		}//end loops through relationlinks
		    
	 }
	 else{
		  $linkedData = false; //no links
	 }
	
	
	
	
		$atomEntryDoc = new DOMDocument("1.0", "utf-8");
	
		$rootEntry = $atomEntryDoc->createElementNS("http://www.w3.org/2005/Atom", "entry");
		
		// add newlines and indent the output - this is at least useful for debugging and making the output easier to read
		$atomEntryDoc->formatOutput = true;
		
		$rootEntry->setAttribute("xmlns:georss", OpenContext_OCConfig::get_namespace("georss"));
		$rootEntry->setAttribute("xmlns:gml", OpenContext_OCConfig::get_namespace("gml"));
		$rootEntry->setAttribute("xmlns:kml", OpenContext_OCConfig::get_namespace("kml"));
		//$rootEntry->setAttribute("xmlns:arch", OpenContext_OCConfig::get_namespace("arch", "spatial"));
		//$rootEntry->setAttribute("xmlns:oc", self::OC_namespaceURI);
		//$rootEntry->setAttribute("xmlns:oc", OpenContext_OCConfig::get_namespace("oc", "spatial"));
		//$rootEntry->setAttribute("xmlns:dc", OpenContext_OCConfig::get_namespace("dc"));
		//$rootEntry->setAttribute("xmlns:xhtml", OpenContext_OCConfig::get_namespace("xhtml"));
	
		$atomEntryDoc->appendChild($rootEntry);
	
		// Create feed title (as opposed to an entry title)
		$feedTitle = $atomEntryDoc->createElement("title");
		$feedTitleText = $atomEntryDoc->CreateTextNode( $project_name . ": " . $item_label . " (" . $item_class . ")" );	
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
		$entryLink->setAttribute("title", "XHTML representation of ". $project_name . ": " . $item_label . " (" . $item_class . ")" );
		$entryLink->setAttribute("href", $baseURI . $uuid);
		$rootEntry->appendChild($entryLink);
		
		// entry archaeoml xml link element
		$entryLink = $atomEntryDoc->createElement("link");
		$entryLink->setAttribute("rel", "alternate");
		$entryLink->setAttribute("type", "application/xml");
		$entryLink->setAttribute("title", "ArchaeoML (XML) representation of ". $project_name . ": " . $item_label . " (" . $item_class . ")" );
		$entryLink->setAttribute("href", $baseURI . $uuid. ".xml");
		$rootEntry->appendChild($entryLink);
		
		// entry atom link element
		$entryLink = $atomEntryDoc->createElement("link");
		$entryLink->setAttribute("rel", $this->atom_rel);
		$entryLink->setAttribute("type", "application/atom+xml");
		$entryLink->setAttribute("title", "Atom representation of ". $project_name . ": " . $item_label . " (" . $item_class . ")" );
		$entryLink->setAttribute("href", $baseURI . $uuid. ".atom");
		$rootEntry->appendChild($entryLink);
		
		//add linked data reference
		if(is_array($linkedData)){
		  foreach($linkedData as $link){
				if(strlen($link["relURI"]) > 1 && strlen($link["targURI"]) > 1){
					 //only add if we have URIs for the predicate / property and the object / target
					 $entryLink = $atomEntryDoc->createElement("link");
					 $entryLink->setAttribute("rel", $link["relURI"]);
					 $entryLink->setAttribute("href", $link["targURI"]);
					 $entryLink->setAttribute("title", "Linked Data Relation: ".$link["relVocab"]."-".$link["relLabel"]." :: ".$link["targVocab"]."-".$link["targLabel"] );
					 $rootEntry->appendChild($entryLink);
				}
		  }
		}
		
		
		
		
		  // Create feed updated element (as opposed to the entry updated element)
		  $entryUpdated = $atomEntryDoc->createElement("updated");
	  
		  // Retrieve the current date and time. Format it in RFC 3339 format. Store it in a text node 
		  $entryUpdatedText = $atomEntryDoc->CreateTextNode(date("Y-m-d\TH:i:s\-07:00"));
		  // Append the text node the updated element
		  $entryUpdated->appendChild($entryUpdatedText);
	  
		  // Append the updated node to the root element
		  $rootEntry->appendChild($entryUpdated);
		
		
		  /*
		  PUBLICATION TIME - Important metadata used by the CDL archiving service
		  */
		  if($pubDateXML != false){
				$pubDate = $pubDateXML ;
		  }
		  else{
				$pubDate = $this->createdTime;
				if(strtotime($this->createdTime) == 0){
						$this->getCreatedTime($uuid);
						$pubDate = $this->createdTime;
				}
		  }
		
		  if(!$pubDate){
				$pubDate = $this->updatedTime;
		  }
		  $entryPublished = $atomEntryDoc->createElement("published");
		  // Retrieve the current date and time. Format it in RFC 3339 format. Store it in a text node 
		  $entryPublishedText = $atomEntryDoc->CreateTextNode(date("Y-m-d\TH:i:s\-07:00", strtotime($pubDate)));
		  $entryPublished->appendChild($entryPublishedText);
		  $rootEntry->appendChild($entryPublished);
	
	
		  if($this->projectUUID == 0){
				$creators[] = "Open Context Editors"; //default name for creators of project 0 data
		  }
	
	
		  // append one or more author elements to the entry
		  foreach ($creators as $creator) {
				$entryPerson = $atomEntryDoc->createElement("author");
				$entryPersonName = $atomEntryDoc->createElement("name");
				$entryPersonNameText = $atomEntryDoc->CreateTextNode($creator);
				$entryPersonName->appendChild($entryPersonNameText);
				$entryPerson->appendChild($entryPersonName);
  
				$creatorID = $this->find_personID($spatialItem, $creator);
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

		    $contribID = $this->find_personID($spatialItem, $contributor);
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
		$entryCat->setAttribute("term", $item_class);
		$rootEntry->appendChild($entryCat);
	
	
		// entry georss point element
		$entryGeoPoint = $atomEntryDoc->createElement("georss:point");
		$entryGeoPointText = $atomEntryDoc->CreateTextNode(($geo_lat+0) . " " . ($geo_long+0));
		$entryGeoPoint->appendChild($entryGeoPointText);
		$rootEntry->appendChild($entryGeoPoint);
	
	
		//Apend KML time span
		if($this->kml_in_Atom){
		    if ($spatialItem->xpath("//arch:spatialUnit/oc:social_usage/oc:user_tags/oc:tag")) {
			foreach ($spatialItem->xpath("//arch:spatialUnit/oc:social_usage/oc:user_tags/oc:tag[@type='chronological' and @status='public']") as $chrono_tag) {
			    foreach ($chrono_tag->xpath("oc:chrono/oc:time_start") as $time_start) {
				$time_start = $time_start + 0;
			    }
			    foreach ($chrono_tag->xpath("oc:chrono/oc:time_finish") as $time_end) {
				$time_end = $time_end + 0;
			    }
			}
			
			
			$entryKML = $atomEntryDoc->createElement("kml:TimeSpan");
			//$entryKML->setAttribute("xmlns:kml", OpenContext_OCConfig::get_namespace("kml"));
			//$entryKML = $atomEntryDoc->createElementNS(OpenContext_OCConfig::get_namespace("kml"), "kml:TimeSpan");
			$entryBegin = $atomEntryDoc->createElement("kml:begin");
			$entryBeginText = $atomEntryDoc->CreateTextNode($time_start);
			$entryBegin->appendChild($entryBeginText);
			$entryKML->appendChild($entryBegin);
			$entryEnd = $atomEntryDoc->createElement("kml:end");
			$entryEndText = $atomEntryDoc->CreateTextNode($time_end);
			$entryEnd->appendChild($entryEndText);
			$entryKML->appendChild($entryEnd);
			$rootEntry->appendChild($entryKML);
			
			$entryWhen = $atomEntryDoc->createElement("georss:when");
			$entryWhenPeriod = $atomEntryDoc->createElement("gml:TimePeriod");
			$entryWhenPeriod->setAttribute("gml:id", "1");
			$entryBegin = $atomEntryDoc->createElement("gml:begin");
			$entryBeginText = $atomEntryDoc->CreateTextNode($time_start);
			$entryBegin->appendChild($entryBeginText);
			$entryWhenPeriod->appendChild($entryBegin);
			$entryEnd = $atomEntryDoc->createElement("gml:end");
			$entryEndText = $atomEntryDoc->CreateTextNode($time_end);
			$entryEnd->appendChild($entryEndText);
			$entryWhenPeriod->appendChild($entryEnd);
			$entryWhen->appendChild($entryWhenPeriod);
			//$rootEntry->appendChild($entryWhen);
			
			
		    }//end case with time span data
		}
		
		// if there's a GML polygon, add it to the entry
		//only grab the polygon if the metasource ref_type=self
		//foreach ($spatialItem->xpath("//arch:spatialUnit/oc:metatdata/oc:geo_reference/gml:Polygon/gml:exterior/gml:LinearRing/gml:posList") as $posList) {
		foreach ($spatialItem->xpath("//arch:spatialUnit/oc:metatdata/oc:geo_reference") as $geoRef) {
			$geoRef->registerXPathNamespace("oc", self::OC_namespaceURI);
			$sefRef = false;
			foreach ($geoRef->xpath("oc:metasource/@ref_type") as $refType) {
			    if($refType."" == "self"){
				$sefRef = true;
			    }
			}
			if($sefRef){
			    $geoRef->registerXPathNamespace("gml", OpenContext_OCConfig::get_namespace("gml"));
			    foreach ($geoRef->xpath("gml:Polygon/gml:exterior/gml:LinearRing/gml:posList") as $posList) {
				$posList = $posList."";
				
				$entryWhere = $atomEntryDoc->createElement("georss:where");
				$entryPoly = $atomEntryDoc->createElement("gml:Polygon");
				$entryExt = $atomEntryDoc->createElement("gml:exterior");
				$entryLR = $atomEntryDoc->createElement("gml:LinearRing");
				$entryPos = $atomEntryDoc->createElement("gml:posList");
				$entryPosText = $atomEntryDoc->CreateTextNode($posList);
				$entryPos->appendChild($entryPosText);
				$entryLR->appendChild($entryPos);
				$entryExt->appendChild($entryLR);
				$entryPoly->appendChild($entryExt);
				$entryWhere->appendChild($entryPoly);
				$rootEntry->appendChild($entryWhere);
			    }//end polygon adding
			}
		}//end looking for polygons on self geo referencing.
	
		
		// entry atom content element
		$entryContent = $atomEntryDoc->createElement("content");
		$entryContent->setAttribute("type", "xhtml");
		
		$XHTML = "xhtml:";
		$XHTML = "";
		
		$contDivA = $atomEntryDoc->createElement($XHTML."div");
		$contDivA->setAttribute("xmlns", OpenContext_OCConfig::get_namespace("xhtml"));
		$entryContent->appendChild($contDivA);
		
		$contDivA_A = $atomEntryDoc->createElement($XHTML."div");
		$contDivA_A->setAttribute("class", "item_lft_cont");
		$contDivA->appendChild($contDivA_A);
	
	
		// append the class icon (uri)
		foreach ($spatialItem->xpath("//arch:spatialUnit/oc:item_class/oc:iconURI") as $iconURI) {
		    $iconURI = $iconURI."";
		    $contDivA_A_A = $atomEntryDoc->createElement($XHTML."div");
		    $contDivA_A_A->setAttribute("class", "class_icon");
		    $contClassIcon = $atomEntryDoc->createElement($XHTML."img");
		    $contClassIcon->setAttribute("src", $iconURI);
		    $contClassIcon->setAttribute("alt", $item_class);
		    $contDivA_A_A->appendChild($contClassIcon);
		    $contDivA_A->appendChild($contDivA_A_A);
		}
	
		// append the project name to the atom content element
		$contDivA_A_B = $atomEntryDoc->createElement($XHTML."div");
		$contDivA_A_B->setAttribute("class", "project_name");
		$contDivA_A_B_text = $atomEntryDoc->CreateTextNode($project_name);
		$contDivA_A_B->appendChild($contDivA_A_B_text);
		$contDivA_A->appendChild($contDivA_A_B);
		
		// open the item_mid_cont div
		$contDivA_A_C = $atomEntryDoc->createElement($XHTML."div");
		$contDivA_A_C->setAttribute("class", "item_mid_cont");
		$contDivA_A->appendChild($contDivA_A_C);
		
		// open the item_mid_up_cont div
		$contDivA_A_C_A = $atomEntryDoc->createElement($XHTML."div");
		$contDivA_A_C_A->setAttribute("class", "item_mid_up_cont");
		$contDivA_A_C->appendChild($contDivA_A_C_A);
	
		// class name
		$contDivA_A_C_A_A = $atomEntryDoc->createElement($XHTML."div");
		$contDivA_A_C_A_A->setAttribute("class", "class_name");
		$contDivA_A_C_A_A_text = $atomEntryDoc->CreateTextNode($item_class);
		$contDivA_A_C_A_A->appendChild($contDivA_A_C_A_A_text);
		$contDivA_A_C_A->appendChild($contDivA_A_C_A_A);
		
		// item label; note: removed htmlentities because it causes problems with numeric entities such as &#xC7;atalh&#xF6;y&#xFC; from '2_Global_Space'
		//$item_label_div = "<xhtml:div class='item_label'>" . $item_label . "</xhtml:div>";
		$item_label_use = str_replace("& ", "&amp; ", $item_label); 
		$contDivA_A_C_A_B = $atomEntryDoc->createElement($XHTML."div");
		$contDivA_A_C_A_B->setAttribute("class", "item_label");
		$contDivA_A_C_A_B_text = $atomEntryDoc->CreateTextNode($item_label_use);
		$contDivA_A_C_A_B->appendChild($contDivA_A_C_A_B_text);
		$contDivA_A_C_A->appendChild($contDivA_A_C_A_B);
		
		// open the context div
		$contDivA_A_C_B = $atomEntryDoc->createElement($XHTML."div");
		$contDivA_A_C_B->setAttribute("class", "context");
		$contDivA_A_C->appendChild($contDivA_A_C_B);
		
		$contextString = "<".$XHTML."div>".chr(13);
		//$contextString .= "<".$XHTML."span class='context_label'>Context: </".$XHTML."span>";
		$contextString .= "Context: ";
		if(strlen($default_context_path."")>0){
		    if(strstr($default_context_path,"/")){
			$contextArray = explode("/", $default_context_path);
		    }
		    else{
			$contextArray = array($default_context_path);
		    }
		
		    $firstLoop = true;
		    foreach($contextArray as $actContext){
			$actContextXML = htmlspecialchars(trim($actContext));
			$actContext = $actContextXML;
			
			/*
			if(stristr($actContextXML, "&Atilde;")){
			    $actContext = str_replace("&Atilde;", "&#xDF;", $actContextXML);
			}
			else{
			    $actContext = $actContextXML;
			}
			*/
			
			if($firstLoop){
			    $contextString .= "<".$XHTML."span class='item_root_parent'>" . $actContext . "</".$XHTML."span>";
			}
			else{
			    if(strlen($actContext)>0){
				$contextString .= " / <".$XHTML."span class='item_parent'>" . $actContext . "</".$XHTML."span>";
			    }
			}
		    $firstLoop = false;	
		    }
		}
		$contextString .= "</".$XHTML."div>".chr(13);
		$contextFragment = $atomEntryDoc->createDocumentFragment();
		$contextFragment->appendXML($contextString);  // $atom_content fragment loaded with xhtml data
		$contDivA_A_C_B->appendChild($contextFragment);
		
	
		// user generated tags
		if ($user_tags) {
		    
		    // append the project name to the atom content element
		    $contDivA_A_D = $atomEntryDoc->createElement($XHTML."div");
		    $contDivA_A_D->setAttribute("class", "all_user_tags");
		    $contDivA_A->appendChild($contDivA_A_D);
		    
		    $tagString = "<".$XHTML."div>".chr(13);
		    $tagString .= "User Created Tags: ";
		    
		    $firstLoop = true;
		    foreach ($user_tags as $user_tag) {
			$user_tag_span = "<".$XHTML."span class='user_tag'>" . htmlentities($user_tag) . "</".$XHTML."span>";
			if($firstLoop){
			    $tagString .= $user_tag_span;
			}
			else{
			    $tagString .= ", ".$user_tag_span;
			}
		    $firstLoop = false;
		    }
		    $tagString .= "</".$XHTML."div>".chr(13);
		    unset($user_tags); // re-initalize the $user_tags array in preparation for the next spatial item
		    $contextFragment = $atomEntryDoc->createDocumentFragment();
		    $contextFragment->appendXML($tagString);  // $atom_content fragment loaded with xhtml data
		    $contDivA_A_D->appendChild($contextFragment);
		}
	
		
		$contDivA_B = $atomEntryDoc->createElement($XHTML."div");
		$contDivA_B->setAttribute("class", "item_thumb");
		$contDivA->appendChild($contDivA_B);
		
	
		// thumbnail (uri) (note: since there may be more than one thumbnail image, we use the first one in the array)
		// if the item has one or more thumbnail images
		if ($spatialItem->xpath("//arch:spatialUnit/arch:observations/arch:observation/arch:links/oc:media_links/oc:link/oc:thumbnailURI")) {
			// store the image URIs in an array
			$thumbnailURIs = $spatialItem->xpath("//arch:spatialUnit/arch:observations/arch:observation/arch:links/oc:media_links/oc:link/oc:thumbnailURI");
			// display just the first image in the array.
			
			$thumbLink = $atomEntryDoc->createElement($XHTML."a");
			$thumbLink->setAttribute("href", $baseURI. $uuid);
			$contDivA_B->appendChild($thumbLink);
			$thumbImg = $atomEntryDoc->createElement($XHTML."img");
			$thumbImg->setAttribute("src", $thumbnailURIs[0]."");
			$thumbImg->setAttribute("class", "thumbimage");
			$thumbImg->setAttribute("alt", "Thumbmail image");
			$thumbLink->appendChild($thumbImg);
		} 
	
		$rootEntry->appendChild($entryContent);
		
		/*
		We've done the hard part of constructing the Atom Entry. The entry is now ready for integrating into atom feeds.
		*/
		$atomEntryDoc->formatOutput = true;
		$atomEntryXML = $atomEntryDoc->saveXML();
		
		$this->atomEntry = $atomEntryXML;
		
		
		
		$atomFullDoc = new DOMDocument("1.0", "utf-8");
	
		$root = $atomFullDoc->createElementNS("http://www.w3.org/2005/Atom", "feed");
		
		// add newlines and indent the output - this is at least useful for debugging and making the output easier to read
		$atomFullDoc->formatOutput = true;
		
		$root->setAttribute("xmlns:georss", OpenContext_OCConfig::get_namespace("georss"));
		$root->setAttribute("xmlns:gml", OpenContext_OCConfig::get_namespace("gml"));
		$root->setAttribute("xmlns:arch", OpenContext_OCConfig::get_namespace("arch", "spatial"));
		$root->setAttribute("xmlns:oc", OpenContext_OCConfig::get_namespace("oc", "spatial"));
		$root->setAttribute("xmlns:dc", OpenContext_OCConfig::get_namespace("dc"));
		$root->setAttribute("xmlns:xhtml", OpenContext_OCConfig::get_namespace("xhtml"));
		
		$atomFullDoc->appendChild($root);
		
		// Create feed title (as opposed to an entry title)
		$feedTitle = $atomFullDoc->createElement("title");
		$feedTitleText = $atomFullDoc->CreateTextNode( $project_name . ": " . $item_label . " (" . $item_class . ")" );	
		$feedTitle->appendChild($feedTitleText);
		$root->appendChild($feedTitle);
	
		//echo $atomFullDoc->saveXML();
		
		// Create feed updated element (as opposed to the entry updated element)
		$feedUpdated = $atomFullDoc->createElement("updated");
	
		// Retrieve the current date and time. Format it in RFC 3339 format. Store it in a text node 
		$feedUpdatedText = $atomFullDoc->CreateTextNode(date("Y-m-d\TH:i:s\-07:00"));
	
		// Append the text node the updated element
		$feedUpdated->appendChild($feedUpdatedText);
	
		// Append the updated node to the root element
		$root->appendChild($feedUpdated);
	
		// feed (self) link element
		$entryLink = $atomFullDoc->createElement("link");
		$entryLink->setAttribute("rel", "self");
		$entryLink->setAttribute("href", $baseURI . $uuid . ".atom");
		$root->appendChild($entryLink);
		
		// feed link element
		$entryLink = $atomFullDoc->createElement("link");
		$entryLink->setAttribute("rel", "alternate");
		$entryLink->setAttribute("href", $baseURI . $uuid);
		$root->appendChild($entryLink);
		
		// feed id
		$feedId = $atomFullDoc->createElement("id");
		$feedIdText = $atomFullDoc->createTextNode($baseURI . $uuid);
		$feedId->appendChild($feedIdText);
		$root->appendChild($feedId);
		
		$atomEntryXML = str_replace('<?xml version="1.0" encoding="utf-8"?>', "", $atomEntryXML);
		$entry = $atomFullDoc->createDocumentFragment();
		$entry->appendXML($atomEntryXML);
		$root->appendChild($entry);
	
		// Create a document fragment to hold the full spatial XML
		$spatialUnitFragment = $atomFullDoc->createDocumentFragment();
		
		// remove the spatial item's prologue and store the spatial item as a string
		$spatialUnitXML = str_replace('<?xml version="1.0"?>', "", $spatialItem->asXML());
		$spatialUnitXML = str_replace('<?xml version="1.0" encoding="utf-8"?>', "", $spatialUnitXML);
		
		
		// add the spatial unit XML to the document fragment
		$spatialUnitFragment->appendXML($spatialUnitXML);
		
		// append the document fragment to the entry
		$entry->appendChild($spatialUnitFragment);
		
		$root->appendChild($entry);
		
		
		$atomFullDocString = $atomFullDoc->saveXML();
		
		
		$doc = new DOMDocument();
		$doc->loadXML($atomFullDocString);
		$doc->formatOutput = true;
		$atomFullDocString = $doc->saveXML();
		
		
		return $atomFullDocString;
		
	}//end make spatialAtomCreate function
    
    

    
    //this function fixes XML for the latest schema
    function namespace_fix($xmlString){
		  
		  //$goodNamespaceURI = "http://opencontext.org/schema/space_schema_v1.xsd";
		  $goodNamespaceURI = self::OC_namespaceURI;
		  
		  $old_namespaceURIs = array("http://about.opencontext.org/schema/space_schema_v1.xsd",
							  "http://www.opencontext.org/database/schema/space_schema_v1.xsd");
		  
		  foreach($old_namespaceURIs as $oldNamespace){
				$xmlString = str_replace($oldNamespace, $goodNamespaceURI, $xmlString);
		  }
		  
		  return $xmlString;
    }
    
    //this function cleans ArchaeoML to fix old versions of the data
    function archaeoml_fix($xmlString){
		  $host = OpenContext_OCConfig::get_host_config();
		  $baseURI = $host."/subjects/";
		  
		  $bad_array = array("http://www.opencontext.org/database/space.php?item=",
					  "http://ishmael.ischool.berkeley.edu/subjects/",
					  "http://opencontext/subjects/",
					  "http://www.opencontext.org/subjects/",
					  "http://testing.opencontext.org/subjects/");
		  
		  foreach($bad_array as $badLink){
				$xmlString = str_replace($badLink, $baseURI, $xmlString);
		  }
		  
		  return $xmlString;
    }
    
    
    //updates archaeoML so that it is current
    //and uses the correct namespace
    function archaeoML_update($xmlString){
	
		  $archaeoML = false;
		  $xmlString = $this->archaeoml_fix($xmlString);
		  $xmlString = $this->namespace_fix($xmlString);
		  @$spatialItem = simplexml_load_string($xmlString);
		  if($spatialItem){
				//valid XML, OK to add to database
				$spatialItem->registerXPathNamespace("oc", self::OC_namespaceURI);
				$spatialItem->registerXPathNamespace("arch", OpenContext_OCConfig::get_namespace("arch", "spatial"));
				$spatialItem->registerXPathNamespace("dc", OpenContext_OCConfig::get_namespace("dc"));
				$spatialItem = $this->obsNumber_Add($spatialItem);
				$spatialItem = $this->propLinks_Add($spatialItem);
				$spatialItem = $this->spaceLinks_Add($spatialItem);
				$spatialItem = $this->mediaLinks_Add($spatialItem);
				$spatialItem = $this->documentLinks_Add($spatialItem);
				$spatialItem = $this->personLinks_Add($spatialItem);
				$spatialItem = $this->parentLinks_Add($spatialItem);
				$spatialItem = $this->childLinks_Add($spatialItem);
				$spatialItem = $this->metaLinks_Add($spatialItem);
				$spatialItem = $this->geoUpdate($spatialItem);
				$spatialItem = $this->chronoUpdate($spatialItem);
				
				//if no database item, add it
				if(!$this->itemFound){
					 $this->database_add($spatialItem);
				}
				
				$archaeoML = $spatialItem->asXML();
				
				$archaeoML = str_replace('<?xml version="1.0"?>', '', $archaeoML);
				$doc = new DOMDocument('1.0', 'UTF-8');
				$doc->loadXML($archaeoML);
				$doc->formatOutput = true;
				$archaeoML = $doc->saveXML();
				
				$this->archaeoML = $archaeoML;
				unset($spatialItem);
				$this->committ_update_archaeoML();
		  }
		  
		  return $archaeoML;
    }
    
    
    
    function committ_update_archaeoML(){
	
		  $updateOK = false;
		  $db = $this->startDB();
		  
		  @$spatialItem = simplexml_load_string($this->archaeoML); 
		  if($spatialItem){
				
				if(!OpenContext_OCConfig::need_bigString($this->archaeoML)){
					 //new archaeoML does NOT need to be treated as a big string
					 $data = array("archaeoML" => $this->archaeoML);
					 $where = array();
					 $where[] = 'uuid = "'.$this->itemUUID.'" ';
					 $db->update("space", $data, $where);
					 $updateOK = true;
					  }
					  else{
					 /*
					 This gets around size limits for inserting into MySQL.
					 It breaks up big inserts into smaller ones, especially useful for HUGE strings of XML
					 */
					 //$this->big_value_insert("archaeoML", $this->archaeoML, $db);
					 $bigStringObj = new BigString;
					 $bigStringObj->saveCurrentBigString($this->itemUUID, "archaeoML", "spatial", $this->archaeoML, $db);
					 
					 $data = array("archaeoML" => OpenContext_OCConfig::get_bigStringValue());
					 $where = array();
					 $where[] = 'uuid = "'.$this->itemUUID.'" ';
					 $db->update("space", $data, $where);
					 $updateOK = true;
				}
		  }
		  
		  unset($spatialItem);
		  $db->closeConnection();
		  return $updateOK;
    }
    
    
    
    
    //add observation numbers if not present
    function obsNumber_Add($spatialItem){
		  $obsNum = 1;
		  foreach($spatialItem->xpath("//arch:observation") as $obs){
				$obs->registerXPathNamespace("oc", self::OC_namespaceURI);
				$obs->registerXPathNamespace("arch", OpenContext_OCConfig::get_namespace("arch", "spatial"));
				$obsNumFound = false;
				foreach($obs->xpath("@obsNumber") as $obsAt){
					 $obsNumFound = true;
				}
				if(!$obsNumFound){
					 $obs->addAttribute("obsNumber", $obsNum);
				}
		  $obsNum++;
		  }
		  
		  return $spatialItem;
    }
    
    
    //for those flaky cases with nothing in the MySQL database
    function database_add($spatialItem){
	
		  // get the item_label
		  foreach ($spatialItem->xpath("//arch:spatialUnit/arch:name/arch:string") as $item_label) {
				$item_label = $item_label."";
		  }
		  
		  // get the item_id
		  foreach($spatialItem->xpath("//arch:spatialUnit/@UUID") as $item_result) {
				$uuid = $item_result."";
		  }
		  
		  // get the item_id
		  foreach($spatialItem->xpath("//arch:spatialUnit/@ownedBy") as $item_result) {
				$project_id = $item_result."";
		  }
		  
		  // get the item_id
		  foreach($spatialItem->xpath("//arch:spatialUnit/oc:item_class/oc:name") as $item_result) {
				$item_class = $item_result."";
				$classID = OpenContext_XMLtoItems::class_id_lookup($item_class);
		  }
		  
		  $this->itemUUID = $uuid;
		  
		  $data = array("uuid" => $uuid,
					  "project_id" => $project_id,
					  "space_label" => $item_label, 
					  "class_uuid" => $classID 
						);
		  
		  $db = $this->startDB();
		  try{
				$db->insert("space", $data);
		  }catch(Exception $e){
				
		  }
    }
    
    
    
    //add property links if not present
    function propLinks_Add($spatialItem){
		  $host = OpenContext_OCConfig::get_host_config();
		  $baseURI = $host."/properties/";
		  
		  foreach($spatialItem->xpath("//arch:property/oc:propid") as $prop){
				$prop->registerXPathNamespace("oc", self::OC_namespaceURI);
				$prop->registerXPathNamespace("arch", OpenContext_OCConfig::get_namespace("arch", "spatial"));
				$linkFound = false;
				foreach($prop->xpath("@href") as $propAt){
					  $linkFound = true;
				}
				if(!$linkFound){
					  $propID = $prop."";
					  $prop->addAttribute("href", $baseURI.$propID);
				}
		  }
		  
		  return $spatialItem;
    }
    
    
    //add space links, if not present
    function spaceLinks_Add($spatialItem){
		  $host = OpenContext_OCConfig::get_host_config();
		  $baseURI = $host."/subjects/";
		  
		  foreach($spatialItem->xpath("//oc:space_links/oc:link") as $link){
				$link->registerXPathNamespace("oc", self::OC_namespaceURI);
				$link->registerXPathNamespace("arch", OpenContext_OCConfig::get_namespace("arch", "spatial"));
				$linkFound = false;
				foreach($link->xpath("@href") as $linkAt){
					 $linkFound = true;
				}
				if(!$linkFound){
					 foreach($link->xpath("oc:id") as $itemID){
						  $itemID = $itemID."";
					 }
					 $link->addAttribute("href", $baseURI.$itemID);
				}
		  }
		  
		  return $spatialItem;
    }
    
    
    
    //add media links if not present
    function mediaLinks_Add($spatialItem){
		  $host = OpenContext_OCConfig::get_host_config();
		  $baseURI = $host."/media/";
		  
		  foreach($spatialItem->xpath("//oc:media_links/oc:link") as $link){
				$link->registerXPathNamespace("oc", self::OC_namespaceURI);
				$link->registerXPathNamespace("arch", OpenContext_OCConfig::get_namespace("arch", "spatial"));
				$linkFound = false;
				foreach($link->xpath("@href") as $linkAt){
					 $linkFound = true;
				}
				if(!$linkFound){
					 foreach($link->xpath("oc:id") as $itemID){
						  $itemID = $itemID."";
					 }
					 $link->addAttribute("href", $baseURI.$itemID);
				}
		  }
		  
		  return $spatialItem;
    }
    
    //add documents links if not present
    function documentLinks_Add($spatialItem){
		  $host = OpenContext_OCConfig::get_host_config();
		  $baseURI = $host."/documents/";
		  
		  foreach($spatialItem->xpath("//oc:diary_links/oc:link") as $link){
				$link->registerXPathNamespace("oc", self::OC_namespaceURI);
				$link->registerXPathNamespace("arch", OpenContext_OCConfig::get_namespace("arch", "spatial"));
				$linkFound = false;
				foreach($link->xpath("@href") as $linkAt){
					 $linkFound = true;
				}
				if(!$linkFound){
					 foreach($link->xpath("oc:id") as $itemID){
						  $itemID = $itemID."";
					 }
					 $link->addAttribute("href", $baseURI.$itemID);
				}
		  }
		  
		  return $spatialItem;
    }
    
    //add person links if not present
    function personLinks_Add($spatialItem){
		  $host = OpenContext_OCConfig::get_host_config();
		  $baseURI = $host."/persons/";
		  
		  $contributors = array();
		  $creators = array();
		  foreach($spatialItem->xpath("//oc:metadata/dc:contributor") as $contrib){
				$actContrib = $contrib."";
				if(!in_array($actContrib, $contributors)){
					 $contributors[] = $actContrib;
				}
		  }
		  foreach($spatialItem->xpath("//oc:metadata/dc:creator") as $create){
				$actCreate = $create."";
				if(!in_array($actCreate, $creators)){
					 if(in_array($actCreate, $contributors)){
						  //only add to creator list if not already in the contributor list
						  $key = array_search($actCreate, $contributors);
						  unset($contributors[$key]);
					 }
					 $creators[] = $actCreate;
				}
		  }
	
		  foreach($spatialItem->xpath("//oc:person_links/oc:link") as $link){
				$link->registerXPathNamespace("oc", self::OC_namespaceURI);
				$link->registerXPathNamespace("arch", OpenContext_OCConfig::get_namespace("arch", "spatial"));
				$linkFound = false;
				$citeFound = false;
				foreach($link->xpath("@href") as $linkAt){
					 $linkFound = true;
				}
				foreach($link->xpath("@href") as $linkAt){
					 $citeFound = true;
				}
				if(!$linkFound){
					 foreach($link->xpath("oc:id") as $itemID){
						  $itemID = $itemID."";
					 }
					 $link->addAttribute("href", $baseURI.$itemID);
				}
				if(!$citeFound){
					 foreach($link->xpath("oc:name") as $itemName){
						  $itemName = $itemName."";
					 }
					 if(in_array($itemName, $contributors)){
						  $link->addAttribute("cite", "contributor");
					 }
					 if(in_array($itemName, $creators)){
						  $link->addAttribute("cite", "creator");
					 }
				}
				
		  }
		  
		  return $spatialItem;
    }
    
    //add parent links if not present
    function parentLinks_Add($spatialItem){
		  $host = OpenContext_OCConfig::get_host_config();
		  $baseURI = $host."/subjects/";
		  
		  foreach($spatialItem->xpath("//oc:parent") as $link){
				$link->registerXPathNamespace("oc", self::OC_namespaceURI);
				$link->registerXPathNamespace("arch", OpenContext_OCConfig::get_namespace("arch", "spatial"));
				$linkFound = false;
				foreach($link->xpath("@href") as $linkAt){
					 $linkFound = true;
				}
				if(!$linkFound){
					 foreach($link->xpath("oc:id") as $itemID){
						  $itemID = $itemID."";
					 }
					 $link->addAttribute("href", $baseURI.$itemID);
				}
		  }
		  
		  return $spatialItem;
    }
    
    //add children links if not present
    function childLinks_Add($spatialItem){
		  $host = OpenContext_OCConfig::get_host_config();
		  $baseURI = $host."/subjects/";
		  
		  foreach($spatialItem->xpath("//oc:child") as $link){
				$link->registerXPathNamespace("oc", self::OC_namespaceURI);
				$link->registerXPathNamespace("arch", OpenContext_OCConfig::get_namespace("arch", "spatial"));
				$linkFound = false;
				foreach($link->xpath("@href") as $linkAt){
					 $linkFound = true;
				}
				if(!$linkFound){
					 foreach($link->xpath("oc:id") as $itemID){
						  $itemID = $itemID."";
					 }
					 $link->addAttribute("href", $baseURI.$itemID);
				}
		  }
		  
		  return $spatialItem;
    }
    
    
    
    //add links if not present to geo and chrono metadata sources
    //add children links if not present
    function metaLinks_Add($spatialItem){
		  $host = OpenContext_OCConfig::get_host_config();
		  $baseURI = $host."/subjects/";
		  
		  foreach($spatialItem->xpath("//oc:metasource") as $link){
				$link->registerXPathNamespace("oc", self::OC_namespaceURI);
				$link->registerXPathNamespace("arch", OpenContext_OCConfig::get_namespace("arch", "spatial"));
				$linkFound = false;
				foreach($link->xpath("@href") as $linkAt){
					 $linkFound = true;
				}
				if(!$linkFound){
					 foreach($link->xpath("oc:source_id") as $itemID){
						  $itemID = $itemID."";
					 }
					 $link->addAttribute("href", $baseURI.$itemID);
				}
		  }
		  
		  return $spatialItem;
    }
    
    
	 function booleanFix($spatialItem = false){
		  
		  $change = false;
		  $trueVals = array("1", "yes", "true");
		  $falseVals = array("0", "no", "false");
		  
		  if(!$spatialItem){
				$spatialItem = simplexml_load_string($this->archaeoML);
				$nameSpaceArray = $this->nameSpaces();
				foreach($nameSpaceArray as $prefix => $uri){
					 @$spatialItem->registerXPathNamespace($prefix, $uri);
				}
		  }
		  
		  if($spatialItem->xpath("//arch:properties/arch:property[oc:var_label/@type = 'boolean']")){
				foreach($spatialItem->xpath("//arch:properties/arch:property[oc:var_label/@type = 'boolean']/oc:show_val") as $boolVal){
					 $textVal = (string)$boolVal;
					 $textVal = strtolower($textVal);
					 if(in_array($textVal, $trueVals) && $textVal != "true"){
						  $boolVal[0] = "true";
						  $change = true;
					 }
					 elseif(in_array($textVal, $falseVals) && $textVal != "false"){
						  $boolVal[0] = "false";
						  $change = true;
					 }
				}
		  }
		  
		  if($change && $this->itemUUID){
				$this->archaeoML = $spatialItem->asXML();
				$this->createUpdate(false); //save the edits
		  }
		  
		  return $spatialItem;
    }
	 
	 
	 
    
    
    //this updates geo spatial coordinates to reflect the latest version
    function geoUpdate($spatialItem){
	
		  $db = $this->startDB();
	
		  $spatialItem->registerXPathNamespace("oc", self::OC_namespaceURI);
		  foreach($spatialItem->xpath("//oc:geo_reference") as $geo){
				$geo->registerXPathNamespace("oc", self::OC_namespaceURI);
				$real_Lat = false;
				foreach($geo->xpath("oc:metasource/oc:source_id") as $id){
					 $id = $id."";
					 
					 $sql = "SELECT *
					 FROM geo_space
					 WHERE geo_space.uuid = '".$id."'
					 LIMIT 1";
					 
					 $result = $db->fetchAll($sql, 2);
					 if($result){
						  $real_Lat = $result[0]["latitude"] + 0;
						  $real_Lon = $result[0]["longitude"] + 0;
					 }
				}
		  
				foreach($geo->xpath("oc:geo_lat") as $geoLat){
					 $geoLatNum = $geoLat;
					 $geoLatNum = $geoLatNum + 0;
				}
				foreach($geo->xpath("oc:geo_long") as $geoLong){
					 $geoLongNum = $geoLong;
					 $geoLongNum = $geoLongNum + 0;
				}
		  
				$this->geoCurrent = true;
				if($real_Lat != false){
					 if($real_Lat != $geoLatNum || $real_Lon != $geoLongNum){
						  $geoLat[0] = $real_Lat;
						  $geoLong[0] = $real_Lon;
						  $this->geoCurrent = false;
						  //echo var_dump($geo);
					 }
				}
		  }
	
		  $db->closeConnection();
		  return $spatialItem;
    }
    

    //this updates chronological range to reflect the latest version
    function chronoUpdate($spatialItem){
	
		  $db = $this->startDB();
		  
		  $spatialItem->registerXPathNamespace("oc", self::OC_namespaceURI);
		  foreach($spatialItem->xpath("//oc:tag[@type='chronological']") as $chrono){
				$chrono->registerXPathNamespace("oc", self::OC_namespaceURI);
				
				foreach($chrono->xpath("oc:name") as $tagName){
				}
				
				$real_Start = false;
				foreach($chrono->xpath("oc:chrono/oc:metasource/oc:source_id") as $id){
					 $id = $id."";
					 
					 $sql = "SELECT *
					 FROM initial_chrono_tag
					 WHERE initial_chrono_tag.uuid = '".$id."'
					 LIMIT 1";
					 
					 $result = $db->fetchAll($sql, 2);
					 if($result){
						  $real_Start = $result[0]["start_time"] + 0;
						  $real_End = $result[0]["end_time"] + 0;
					 }
				}
		  
				foreach($chrono->xpath("oc:chrono/oc:time_start") as $timeStart){
					  $timeStartNum = $timeStart;
					  $timeStartNum = $timeStartNum + 0;
				}
				foreach($chrono->xpath("oc:chrono/oc:time_finish") as $timeEnd){
					  $timeEndNum = $timeEnd;
					  $timeEndNum = $timeEndNum + 0;
				}
		  
				$this->chronoUpdate = true; //default to up-to-date chronology
				if($real_Start != false){
					 if($real_Start != $timeStartNum || $real_End != $timeEndNum){
						  $timeStart[0] = $real_Start;
						  $timeEnd[0] = $real_End;
						  $this->chronoUpdate = false; //XML in datastore is NOT uptodate on chronology
						  $dateRange = OpenContext_DateRange::bce_ce_note($real_Start)." - ".OpenContext_DateRange::bce_ce_note($real_End);
						  $dateRange = "(".$dateRange.")";
						  $tagName[0] = $dateRange;
					 }
				}
		  }
		  
		  $db->closeConnection();
		  return $spatialItem;
    }
    

	 //get table associations
    function getTableAssociations(){
		  $host = OpenContext_OCConfig::get_host_config();  
		  $db = $this->startDB();
		  
		  $sql = "SELECT DISTINCT tableID, page
		  FROM export_tabs_records
		  WHERE uuid = '".$this->itemUUID."'
		  ORDER BY updated, tableID, page
		  ";
		  
		  $result = $db->fetchAll($sql, 2);
		  if($result){
				
				$dom = new DOMDocument("1.0", "utf-8");
				$dom->loadXML($this->archaeoML);
				$dom->formatOutput = true;
				$xpath = new DOMXpath($dom);
				
				$NSarray = $this->nameSpaces();
				foreach($NSarray as $prefix => $uri){
					 $xpath->registerNamespace($prefix, $uri);
				}
				$query = "//oc:metadata/oc:tableRefs";
				$tableRefsList = $xpath->query($query, $dom);
		  
				if($tableRefsList->item(0) == null){
					 $query = "//oc:metadata";
					 $metadataNodeList = $xpath->query($query, $dom);
					 $metadataNode = $metadataNodeList->item(0);
					 $tableRefsNode = $dom->createElement("oc:tableRefs");
					 $metadataNode->appendChild($tableRefsNode);
				}
				else{
					 $tableRefsNode = $tableRefsList->item(0);
				}
				
				foreach($result as $row){
					 $tableID = $row["tableID"];
					 $page = $row["page"];
					 $useID = $tableID;
					 if($page > 1){
						  $useID .= "/".$page;
					 }
					  $tableURI = $host."/tables/".$useID;
					 
					 $tabObj = new Table;
					 $tabObj->getByID($useID);
					 $tableName = $tabObj->label;
					 
					 $query = "//oc:metadata/oc:tableRefs/oc:link[@href='$tableURI']";
					 $sameNodeList = $xpath->query($query, $dom);
					 if($sameNodeList->item(0)  == null){
						  $linkNode = $dom->createElement("oc:link");
						  $linkNode->setAttribute("href", $tableURI);
						  $linkText = $dom->createTextNode($tableName);
						  $linkNode->appendChild($linkText);
						  $tableRefsNode->appendChild($linkNode);
					 }//end case where we need to add a table link
				}//end loop through linked tables
				$this->archaeoML = $dom->saveXML();
		  }
		  
	 }
    
    
	 
	 
    
    
    function find_personID($spatialItem, $personName){
	
		  $personID = false;
		  foreach ($spatialItem->xpath("//arch:observation/arch:links/oc:person_links/oc:link") as $personLink) {
				$personLink->registerXPathNamespace("oc", self::OC_namespaceURI);
				foreach($personLink->xpath("oc:name") as $link_personName){
					 if($link_personName."" == $personName){
						  foreach($personLink->xpath("oc:id") as $link_personID){
								$personID = $link_personID."";
						  }
					 }
				}
				
		  }
		  
		  if(!$personID){
				//try via database lookup
				$personID = $this->db_find_personID($personName);
		  }
		  
		  return $personID;
    }
    
    
    function db_find_personID($personName){
	
		  $personID = false;
		  $db = $this->startDB();
		  
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
    
    function atom_pubDate(){
		
		  $db = $this->startDB();
			
		  $sql = "SELECT space.created, space.updated
		  FROM space
		  WHERE space.uuid = '".$this->itemUUID."'
		  LIMIT 1; ";
		  
		  //echo $sql;
		  
		  $result = $db->fetchAll($sql, 2);
		  $pubDate = false;
				 
		  if($result){
			  $createdDate = $result[0]["created"];
			  $updatedDate = $result[0]["updated"];
			  $pubDate = $createdDate;
			  if(!$createdDate){
				  $pubDate = $updatedDate;
			  }
		  }
		  
		  
		  $db->closeConnection();
		  return $pubDate;
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
    

    function startDB(){
		  if(!$this->db){
				$db_params = OpenContext_OCConfig::get_db_config();
				$db = new Zend_Db_Adapter_Pdo_Mysql($db_params);
				$db->getConnection();
				$this->setUTFconnection($db);
				$this->db = $db;
		  }
		  else{
				$db = $this->db;
		  }
		  
		  return $db;
	 }
	 
	 function setUTFconnection($db){
		  $sql = "SET collation_connection = utf8_unicode_ci;";
		  $db->query($sql, 2);
		  $sql = "SET NAMES utf8;";
		  $db->query($sql, 2);
    }
   
    
}
