<?php


//this class interacts with the database for accessing and changing Media (location and object items)
class Media {
    
    /*
     General item metadata
    */
    public $noid;
    public $projectUUID;
    public $sourceID;
    public $itemUUID;
    public $label;
    
    /*
    Media resource specific
    */
    public $archType; //archaeoML resource type
    public $mimeType; //internet MIME type
    public $filename; //filename
    public $thumbnailURI; //URL to thumbnail
    public $previewURI; //URL to preview
    public $fullURI; //URL to fullfile 
    
    public $schemaFix;
    public $viewCount;
    public $createdTime;
    public $updatedTime;
    public $archaeoML;
    public $atomEntry;
    
    public $kml_in_Atom; // add KML timespan to atom entry, W3C breaks validation
    public $xhtml_rel = "alternate"; // value for link rel attribute for XHTML version ("self" or "alternate")
    public $atom_rel = "alternate"; //value for link rel attribute for Atom version ("self" or "alternate")
    
    public $geoCurrent; //check if geo-reference is current.
    
    
    const OC_namespaceURI = "http://opencontext.org/schema/resource_schema_v1.xsd";
    
    //get User data from database
    function getByID($id){
        
        $id = $this->security_check($id);
        $output = false; //no user
        $db_params = OpenContext_OCConfig::get_db_config();
        $db = new Zend_Db_Adapter_Pdo_Mysql($db_params);
		  $db->getConnection();
        
        $sql = 'SELECT *
                FROM resource
                WHERE uuid = "'.$id.'"
                LIMIT 1';
		
        $result = $db->fetchAll($sql, 2);
        if($result){
            
            $this->noid = $result[0]["noid"];
            $this->projectUUID = $result[0]["project_id"];
            $this->sourceID = $result[0]["source_id"];
				$this->itemUUID = $result[0]["uuid"];
				$this->label = $result[0]["res_label"];
				$this->mimeType = $result[0]["mime_type"];
				$this->viewCount = $result[0]["view_count"];
            $this->createdTime = $result[0]["created"];
            $this->updatedTime = $result[0]["updated"];
				$this->atomEntry = $result[0]["atom_entry"];
				$this->archaeoML = $result[0]["archaeoML"];
	    
				/*
				$this->accentFix($this->atomEntry, "atom");
				$this->accentFix($this->archaeoML, "archaeoML");
				*/
				
				if(OpenContext_OCConfig::need_bigString($this->archaeoML)){
					 $bigString = new BigString;
					 $this->archaeoML = $bigString->get_CurrentBigString($this->itemUUID, "archaeoML", $db);
				}
				
				$this->xhtml_rel = "alternate";
				$this->atom_rel = "self";
				
				$this->mimeType = $this->mime_type_clean($this->mimeType);
	    
            $output = true;
        }
        
		  $db->closeConnection();
    
        return $output;
    }
    
    
    function addViewCount(){
		  $db_params = OpenContext_OCConfig::get_db_config();
        $db = new Zend_Db_Adapter_Pdo_Mysql($db_params);
		  $db->getConnection();
	
		  $data = array("view_count" => $this->viewCount + 1);
		  $where = "uuid = '".$this->itemUUID."' ";
	
		  $db->update("resource", $data, $where);
		  
		  $db->closeConnection();
    }
    
    
    
    //used to fix legacy non utf8 problem
    function accentFix($xmlString, $XMLtype){
	
		  $badString = "Christian Aug&amp;#233;";
		  $goodString = "Christian Augé";
	
		  if(stristr($xmlString, $badString)){
				$newXML = str_replace($badString, $goodString, $xmlString);
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
    
    
    
    
    function versionUpdate($id, $db = false){
	
		  if(!$db){
				$db_params = OpenContext_OCConfig::get_db_config();
				$db = new Zend_Db_Adapter_Pdo_Mysql($db_params);
				$db->getConnection();
		  }
		  
		  $sql = 'SELECT archaeoML AS archaeoML
							FROM resource
							WHERE uuid = "'.$id.'"
							LIMIT 1';
			  
				 $result = $db->fetchAll($sql, 2);
				 if($result){
				$xmlString = $result[0]["archaeoML"];
				OpenContext_DeleteDocs::saveBeforeUpdate($id, "media", $xmlString);
		  }
	
    }//end function
    
    
    
    //create a new media item
    function createUpdate($versionUpdate){
        
        $db_params = OpenContext_OCConfig::get_db_config();
        $db = new Zend_Db_Adapter_Pdo_Mysql($db_params);
		  $db->getConnection();
			
		  if(!$this->noid){
				$this->noid = false;
		  }
    
		  $data = array("noid" => $this->noid,
		      "project_id" => $this->projectUUID,
		      "source_id" => $this->sourceID,
		      "uuid" => $this->itemUUID,
		      "res_label" => $this->label,
		      "res_filename" => $this->filename,
		    
		      "res_archml_type" => $this->archType,
		      "mime_type" => $this->mimeType,
		      "ia_thumb" => $this->thumbnailURI,
		      "ia_preview" => $this->previewURI,
		      "ia_fullfile" => $this->fullURI,
		      
		      "created" => $this->createdTime,
		      "atom_entry" => $this->atomEntry,
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
				$bigString->saveCurrentBigString($this->itemUUID, "archaeoML", "media", $this->archaeoML, $db);
				$data["archaeoML"] = OpenContext_OCConfig::get_bigStringValue();
				$updateOK = true;
		  }
		  else{
				
				$data["archaeoML"] = $this->archaeoML;
				$updateOK = true;
		  }

		  $success = false;
		  try{
				$db->insert("resource", $data);
				$success = true;
		  }catch(Exception $e){
				//echo $e;
				//echo print_r($data);
				$success = false;
				$where = array();
				$where[] = 'uuid = "'.$this->itemUUID.'" ';
				$db->update("resource", $data, $where);
				$success = true;
		  }

		  $db->closeConnection();
		  return $success;
    }//end function
    
    
    
    
    
    
    
    
    
    
    //this function gets an item's Atom entry. It's used for making the general
    //feed read by the CDL's archival services.
    function getItemEntry($id){
	
		  $this->getByID($id);
		  if(strlen($this->archaeoML)<10){
				$this->archaeoML_update($this->archaeoML);
				$fullAtom = $this->DOM_spatialAtomCreate($this->archaeoML);
				$this->update_atom_entry();
		  }
		  
		  return $this->atomEntry;
    }
    
    
    //this function gets an item's ArchaeoML. It's used for indexing in Solr
    function getItemXML($id){
	
		  $this->getByID($id);
		  if(strlen($this->archaeoML)<10){
				$this->archaeoML_update($this->archaeoML);
				$fullAtom = $this->DOM_spatialAtomCreate($this->archaeoML);
				$this->update_atom_entry();
		  }
		  
		  return $this->archaeoML;
    }
    
    
    
    function mime_type_clean($testString){
	
		  $type = false;
		  
		  $mimeTypes = array("jpg" => "image/jpeg",
					  "jpeg" => "image/jpeg",
					  "tiff" => "image/tiff",
					  "tif" => "image/tiff",
					  "gif" => "image/gif",
					  "png" => "image/png",
					  "pdf" => "application/pdf",
					  "doc" => "application/msword",
					  "xls" => "application/vnd.ms-excel");
		  
		  if(strlen($testString)>4){
				$addDot = ".";
		  }
		  else{
				$addDot = "";
		  }
		  
		  foreach($mimeTypes as $key => $actType){
				
				if(stristr($testString, ($addDot.$key))){   
					 $type = $actType;
					 break;
				}
				
		  }
		  
		  return $type;
    }
    
    
    
    function update_atom_entry(){
	
		  $updateOK = false;
		  $db_params = OpenContext_OCConfig::get_db_config();
        $db = new Zend_Db_Adapter_Pdo_Mysql($db_params);
		  $db->getConnection();
		  
		  @$xml = simplexml_load_string($this->atomEntry); 
		  if($xml){
				$data = array("atom_entry" => $this->atomEntry);
				$where = array();
				$where[] = 'uuid = "'.$this->itemUUID.'" ';
				$db->update("resource", $data, $where);
				$updateOK = true;
		  }
		  
		  unset($mediaItem);
		  $db->closeConnection();
		  return $updateOK;
    }
    
    
    function nameSpaces(){
		  $nameSpaceArray = array("oc" => self::OC_namespaceURI,
					  "dc" => OpenContext_OCConfig::get_namespace("dc"),
					  "arch" => OpenContext_OCConfig::get_namespace("arch", "media"),
					  "gml" => OpenContext_OCConfig::get_namespace("gml"),
					  "kml" => OpenContext_OCConfig::get_namespace("kml"));
		  
		  return $nameSpaceArray;
    }
    
    
    function XML_fileURIs(){
	
		  @$mediaItem = simplexml_load_string($this->archaeoML);
		  if($mediaItem){
				$mediaItem->registerXPathNamespace("oc", self::OC_namespaceURI); // Register OpenContext's namespace
				$mediaItem->registerXPathNamespace("arch", OpenContext_OCConfig::get_namespace("arch", "media"));
				foreach($mediaItem->xpath("//arch:externalFileInfo/arch:resourceURI") as $file) {
			  $this->fullURI = (string)$file;
				}
				foreach($mediaItem->xpath("//arch:externalFileInfo/arch:previewURI") as $file) {
			  $this->previewURI = (string)$file;
				}
				foreach($mediaItem->xpath("//arch:externalFileInfo/arch:thumbnailURI") as $file) {
			  $this->thumbnailURI = (string)$file;
				}
		  }
	
    }//end function
    
    
    
    function DOM_spatialAtomCreate($archaeML_string){
		
		$host = OpenContext_OCConfig::get_host_config();
		$baseURI = $host."/media/";
		
		$mediaItem = simplexml_load_string($archaeML_string);
		
		// Register OpenContext's namespace
		$mediaItem->registerXPathNamespace("oc", self::OC_namespaceURI);
		
		// Register OpenContext's namespace
		$mediaItem->registerXPathNamespace("arch", OpenContext_OCConfig::get_namespace("arch", "media"));
	
		// Register Dublin Core's namespace
		$mediaItem->registerXPathNamespace("dc", OpenContext_OCConfig::get_namespace("dc"));
	
		// Register the GML namespace
		$mediaItem->registerXPathNamespace("gml", OpenContext_OCConfig::get_namespace("gml"));
		
		
		// get the item_id
		foreach($mediaItem->xpath("//arch:resource/@UUID") as $item_result) {
			$uuid = $item_result."";
			if(!$this->itemUUID){
				$this->itemUUID = $uuid;
			}
		}
	
	
		// get the item_label
		foreach ($mediaItem->xpath("//arch:resource/arch:name/arch:string") as $item_label) {
			$item_label = $item_label."";
		}
		
		//project name
		foreach ($mediaItem->xpath("//arch:resource/oc:metadata/oc:project_name") as $project_name) {
			$project_name = $project_name."";
		}
	
		// get the item class
		$item_class = "";
		foreach ($mediaItem->xpath("//oc:space_links/oc:link/oc:item_class/oc:name") as $item_class) {
			$item_class = $item_class."";
		}
	
		$titleUse = $project_name." media resource: ".$item_label;
		
		$user_tags = array();
		$count_public_tags = 0; // value used to help calculate interest_score	
		if ($mediaItem->xpath("//arch:resource/oc:social_usage/oc:user_tags/oc:tag")) {
			foreach ($mediaItem->xpath("//arch:resource/oc:social_usage/oc:user_tags/oc:tag[@type='text' and @status='public']") as $tag) { // loop through and get the tags. 
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
	
	
		$creators = $mediaItem->xpath("//arch:resource/oc:metadata/dc:creator");
		$contributors = $mediaItem->xpath("/arch:resource/oc:metadata/dc:contributor");
		
		
		
		
		
		foreach ($mediaItem->xpath("//arch:resource/oc:metadata/oc:geo_reference") as $geo_reference) {
			foreach ($geo_reference->xpath("oc:geo_lat") as $geo_lat) {
				$geo_lat = (string)$geo_lat;
			}
			foreach ($geo_reference->xpath("oc:geo_long") as $geo_long) {
				$geo_long = (string)$geo_long;
			}
		}//end loop through geo
	
		// polygon
		$geo_polygon = false;
		if ($mediaItem->xpath("//arch:resource/oc:metadata/oc:geo_reference/oc:metasource[@ref_type='self']")) {
			$self_geo_reference = true; // this value is used to calculate interesting_score.
			
			if ($mediaItem->xpath("//arch:resource/oc:metadata/oc:geo_reference/gml:Polygon/gml:exterior/gml:LinearRing/gml:posList")) {
				$geo_polygon = true; // this value is used to calculate interesting_score. and also in the Atom generation code
				foreach ($mediaItem->xpath("//arch:resource/oc:metadata/oc:geo_reference/gml:Polygon/gml:exterior/gml:LinearRing/gml:posList") as $polygon_pos_list ) {
					//echo "polygon_pos_list: " . $polygon_pos_list;
					//echo "<br/>";
				}
			}
		}
	
	
		$pubDate = false;
		foreach($mediaItem->xpath("//oc:metadata/dc:date") as $pubDate) {
		    $pubDate = $pubDate."";
		}
	
	
		$fileArray = array();
		foreach($mediaItem->xpath("//arch:externalFileInfo/arch:fileFormat") as $mainMime) {
		    $mainMime = $mainMime."";
		    if(!strstr($mainMime, "/")){
			$mainMime = $this->mime_type_clean($mainMime);
		    }
		    
		}
		foreach($mediaItem->xpath("//arch:externalFileInfo/arch:resourceURI") as $file) {
		    $file = $file."";
		}
		$fileArray[] = array("rel" => "enclosure",
				     "title" => $titleUse." (main file)",
				     "type" => $mainMime,
				     "href" => $file);
		
		foreach($mediaItem->xpath("//arch:externalFileInfo/arch:previewURI") as $file) {
		    $file = $file."";
		    $previewURI = $file;
		    $mime = $this->mime_type_clean($file);
		}
		$fileArray[] = array("rel" => "http://purl.org/dc/terms/hasPart",
				     "title" => $titleUse." (preview file)",
				     "type" => $mime,
				     "href" => $file);
		
		foreach($mediaItem->xpath("//arch:externalFileInfo/arch:thumbnailURI") as $file) {
		    $file = $file."";
		    $mime = $this->mime_type_clean($file);
		}
		$fileArray[] = array("rel" => "http://purl.org/dc/terms/hasPart",
				     "title" => $titleUse." (thumbnail file)",
				     "type" => $mime,
				     "href" => $file);
	
	
	
	
	
	
	
	
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
		
		// entry primary file element
		foreach($fileArray as $fileItem){
		    $entryLink = $atomEntryDoc->createElement("link");
		    $entryLink->setAttribute("rel", $fileItem["rel"]);
		    $entryLink->setAttribute("type", trim($fileItem["type"]));
		    $entryLink->setAttribute("title", $fileItem["title"] );
		    $entryLink->setAttribute("href", $fileItem["href"]);
		    $rootEntry->appendChild($entryLink);
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

		    $creatorID = $this->find_personID($mediaItem, $creator);
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

		    $contribID = $this->find_personID($mediaItem, $contributor);
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
		    if ($mediaItem->xpath("//arch:resource/oc:social_usage/oc:user_tags/oc:tag")) {
			foreach ($mediaItem->xpath("//arch:resource/oc:social_usage/oc:user_tags/oc:tag[@type='chronological' and @status='public']") as $chrono_tag) {
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
		//foreach ($mediaItem->xpath("//arch:resource/oc:metatdata/oc:geo_reference/gml:Polygon/gml:exterior/gml:LinearRing/gml:posList") as $posList) {
		foreach ($mediaItem->xpath("//arch:resource/oc:metatdata/oc:geo_reference") as $geoRef) {
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
		
		$contHead = $atomEntryDoc->createElement($XHTML."h1");
		$contHead_text = $atomEntryDoc->CreateTextNode("Open Context Media Resource");
		$contHead->appendChild($contHead_text);
		$contDivA->appendChild($contHead);
	
		$contHead2 = $atomEntryDoc->createElement($XHTML."h2");
		$contHead2_text = $atomEntryDoc->CreateTextNode($titleUse);
		$contHead2->appendChild($contHead2_text);
		$contDivA->appendChild($contHead2);
	
		$contPreview = $atomEntryDoc->createElement($XHTML."img");
		$contPreview->setAttribute("src", $previewURI);
		$contPreview->setAttribute("alt", "Preview Image for ".$titleUse);
		$contPreview->setAttribute("title", "Media Preview of ".$titleUse);
		$contDivA->appendChild($contPreview);
	
		// user generated tags
		if ($user_tags) {
		    
		    // append the project name to the atom content element
		    $contDivA_A_D = $atomEntryDoc->createElement($XHTML."div");
		    $contDivA_A_D->setAttribute("class", "all_user_tags");
		    $contDivA->appendChild($contDivA_A_D);
		    
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
		$root->setAttribute("xmlns:arch", OpenContext_OCConfig::get_namespace("arch", "media"));
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
		$spatialUnitXML = str_replace('<?xml version="1.0"?>', "", $mediaItem->asXML());
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
    
    
    
    
    function solr_getArchaeoML(){
	$host = OpenContext_OCConfig::get_host_config();
	$baseURI = $host."/media/";
	$this->archaeoML = file_get_contents($baseURI.$this->itemUUID.".xml");
    }
    
    
    
    
    //this function fixes XML for the latest schema
    function namespace_fix($xmlString){
	
	//$goodNamespaceURI = "http://opencontext.org/schema/space_schema_v1.xsd";
	$goodNamespaceURI = self::OC_namespaceURI;
	
	$old_namespaceURIs = array("http://about.opencontext.org/schema/resource_schema_v1.xsd",
				      "http://www.opencontext.org/database/schema/resource_schema_v1.xsd");
	
	foreach($old_namespaceURIs as $oldNamespace){
	    $xmlString = str_replace($oldNamespace, $goodNamespaceURI, $xmlString);
	}
	
	return $xmlString;
    }
    
    //this function cleans ArchaeoML to fix old versions of the data
    function archaeoml_fix($xmlString){
	$host = OpenContext_OCConfig::get_host_config();
	$baseURI = $host."/media/";
	
	$bad_array = array("http://www.opencontext.org/database/media.php?item=",
			   "http://ishmael.ischool.berkeley.edu/media/",
			   "http://opencontext/media/",
			   "http://www.opencontext.org/media/",
			   "http://testing.opencontext.org/media/");
	
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
	@$mediaItem = simplexml_load_string($xmlString);
	if($mediaItem){
	    //valid XML, OK to add to database
	    $mediaItem->registerXPathNamespace("oc", self::OC_namespaceURI);
	    $mediaItem->registerXPathNamespace("arch", OpenContext_OCConfig::get_namespace("arch", "media"));
	    $mediaItem->registerXPathNamespace("dc", OpenContext_OCConfig::get_namespace("dc"));
	    
	    $mediaItem = $this->propLinks_Add($mediaItem);
	    $mediaItem = $this->spaceLinks_Add($mediaItem);
	    $mediaItem = $this->mediaLinks_Add($mediaItem);
	    $mediaItem = $this->documentLinks_Add($mediaItem);
	    $mediaItem = $this->personLinks_Add($mediaItem);
	    $mediaItem = $this->parentLinks_Add($mediaItem);
	    $mediaItem = $this->childLinks_Add($mediaItem);
	    $mediaItem = $this->metaLinks_Add($mediaItem);
	    $mediaItem = $this->geoUpdate($mediaItem);
	    $mediaItem = $this->chronoUpdate($mediaItem);
	    $archaeoML = $mediaItem->asXML();
	    
	    $archaeoML = str_replace('<?xml version="1.0"?>', '', $archaeoML);
	    $doc = new DOMDocument('1.0', 'UTF-8');
	    $doc->loadXML($archaeoML);
	    $doc->formatOutput = true;
	    $archaeoML = $doc->saveXML();
	    
	    $this->archaeoML = $archaeoML;
	    unset($mediaItem);
	    $this->committ_update_archaeoML();
	}
	
	return $archaeoML;
    }
    
    function committ_update_archaeoML(){
	
	$updateOK = false;
	$db_params = OpenContext_OCConfig::get_db_config();
        $db = new Zend_Db_Adapter_Pdo_Mysql($db_params);
	$db->getConnection();
	
	@$mediaItem = simplexml_load_string($this->archaeoML); 
	if($mediaItem){
	    $data = array("schema_fix" => date("Y-m-d H:i:s"),
			  "archaeoML" => $this->archaeoML);
	    $where = array();
	    $where[] = 'uuid = "'.$this->itemUUID.'" ';
	    $db->update("resource", $data, $where);
	    $updateOK = true;
	}
	
	unset($mediaItem);
	$db->closeConnection();
	return $updateOK;
    }
    
    
    
    
    //add observation numbers if not present
    function obsNumber_Add($mediaItem){
	$obsNum = 1;
	foreach($mediaItem->xpath("//arch:observation") as $obs){
	    $obs->registerXPathNamespace("oc", self::OC_namespaceURI);
	    $obs->registerXPathNamespace("arch", OpenContext_OCConfig::get_namespace("arch", "media"));
	    $obsNumFound = false;
	    foreach($obs->xpath("@obsNumber") as $obsAt){
		$obsNumFound = true;
	    }
	    if(!$obsNumFound){
		$obs->addAttribute("obsNumber", $obsNum);
	    }
	$obsNum++;
	}
	
	return $mediaItem;
    }
    
    
    //add property links if not present
    function propLinks_Add($mediaItem){
	$host = OpenContext_OCConfig::get_host_config();
	$baseURI = $host."/properties/";
	
	foreach($mediaItem->xpath("//arch:property/oc:propid") as $prop){
	    $prop->registerXPathNamespace("oc", self::OC_namespaceURI);
	    $prop->registerXPathNamespace("arch", OpenContext_OCConfig::get_namespace("arch", "media"));
	    $linkFound = false;
	    foreach($prop->xpath("@href") as $propAt){
		$linkFound = true;
	    }
	    if(!$linkFound){
		$propID = $prop."";
		$prop->addAttribute("href", $baseURI.$propID);
	    }
	}
	
	return $mediaItem;
    }
    
    
    //add space links, if not present
    function spaceLinks_Add($mediaItem){
	$host = OpenContext_OCConfig::get_host_config();
	$baseURI = $host."/subjects/";
	
	foreach($mediaItem->xpath("//oc:space_links/oc:link") as $link){
	    $link->registerXPathNamespace("oc", self::OC_namespaceURI);
	    $link->registerXPathNamespace("arch", OpenContext_OCConfig::get_namespace("arch", "media"));
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
	
	return $mediaItem;
    }
    
    
    
    //add media links if not present
    function mediaLinks_Add($mediaItem){
	$host = OpenContext_OCConfig::get_host_config();
	$baseURI = $host."/media/";
	
	foreach($mediaItem->xpath("//oc:media_links/oc:link") as $link){
	    $link->registerXPathNamespace("oc", self::OC_namespaceURI);
	    $link->registerXPathNamespace("arch", OpenContext_OCConfig::get_namespace("arch", "media"));
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
	
	return $mediaItem;
    }
    
    //add documents links if not present
    function documentLinks_Add($mediaItem){
	$host = OpenContext_OCConfig::get_host_config();
	$baseURI = $host."/documents/";
	
	foreach($mediaItem->xpath("//oc:diary_links/oc:link") as $link){
	    $link->registerXPathNamespace("oc", self::OC_namespaceURI);
	    $link->registerXPathNamespace("arch", OpenContext_OCConfig::get_namespace("arch", "media"));
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
	
	return $mediaItem;
    }
    
    //add person links if not present
    function personLinks_Add($mediaItem){
	$host = OpenContext_OCConfig::get_host_config();
	$baseURI = $host."/persons/";
	
	$contributors = array();
	$creators = array();
	foreach($mediaItem->xpath("//oc:metadata/dc:contributor") as $contrib){
	    $actContrib = $contrib."";
	    if(!in_array($actContrib, $contributors)){
		$contributors[] = $actContrib;
	    }
	}
	foreach($mediaItem->xpath("//oc:metadata/dc:creator") as $create){
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
	
	foreach($mediaItem->xpath("//oc:person_links/oc:link") as $link){
	    $link->registerXPathNamespace("oc", self::OC_namespaceURI);
	    $link->registerXPathNamespace("arch", OpenContext_OCConfig::get_namespace("arch", "media"));
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
	
	return $mediaItem;
    }
    
    //add parent links if not present
    function parentLinks_Add($mediaItem){
	$host = OpenContext_OCConfig::get_host_config();
	$baseURI = $host."/subjects/";
	
	foreach($mediaItem->xpath("//oc:parent") as $link){
	    $link->registerXPathNamespace("oc", self::OC_namespaceURI);
	    $link->registerXPathNamespace("arch", OpenContext_OCConfig::get_namespace("arch", "media"));
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
	
	return $mediaItem;
    }
    
    //add children links if not present
    function childLinks_Add($mediaItem){
	$host = OpenContext_OCConfig::get_host_config();
	$baseURI = $host."/subjects/";
	
	foreach($mediaItem->xpath("//oc:child") as $link){
	    $link->registerXPathNamespace("oc", self::OC_namespaceURI);
	    $link->registerXPathNamespace("arch", OpenContext_OCConfig::get_namespace("arch", "media"));
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
	
	return $mediaItem;
    }
    
    
    
    //add links if not present to geo and chrono metadata sources
    //add children links if not present
    function metaLinks_Add($mediaItem){
	$host = OpenContext_OCConfig::get_host_config();
	$baseURI = $host."/subjects/";
	
	foreach($mediaItem->xpath("//oc:metasource") as $link){
	    $link->registerXPathNamespace("oc", self::OC_namespaceURI);
	    $link->registerXPathNamespace("arch", OpenContext_OCConfig::get_namespace("arch", "media"));
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
	
	return $mediaItem;
    }
    
    
    
    
    //this updates geo spatial coordinates to reflect the latest version
    function geoUpdate($mediaItem){
	
	$db_params = OpenContext_OCConfig::get_db_config();
        $db = new Zend_Db_Adapter_Pdo_Mysql($db_params);
	$db->getConnection();
	
	$mediaItem->registerXPathNamespace("oc", self::OC_namespaceURI);
	foreach($mediaItem->xpath("//oc:geo_reference") as $geo){
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
	return $mediaItem;
    }
    

    //this updates chronological range to reflect the latest version
    function chronoUpdate($mediaItem){
	
	$db_params = OpenContext_OCConfig::get_db_config();
        $db = new Zend_Db_Adapter_Pdo_Mysql($db_params);
	$db->getConnection();
	
	$mediaItem->registerXPathNamespace("oc", self::OC_namespaceURI);
	foreach($mediaItem->xpath("//oc:tag[@type='chronological']") as $chrono){
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
	return $mediaItem;
    }
    

    
    
    
    
    
    function find_personID($mediaItem, $personName){
	
	$personID = false;
	foreach ($mediaItem->xpath("//arch:observation/arch:links/oc:person_links/oc:link") as $personLink) {
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
			
			function atom_pubDate(){
			  
		  $db_params = OpenContext_OCConfig::get_db_config();
		  $db = new Zend_Db_Adapter_Pdo_Mysql($db_params);
						
		  $db->getConnection();
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
    
    
    
    
    
    
}
