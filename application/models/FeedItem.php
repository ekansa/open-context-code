<?php


//this class interacts with the database for accessing and changing Subjects (location and object items)
class FeedItem {
    
    public $noid;
    public $itemUUID;
    public $itemType;
    public $itemLabel;
    public $projectUUID;
    public $projectName;
    public $className;
    public $viewCount;
    public $createdTime;
    public $updatedTime;
    public $mediaLinks; //array of links to media files, if any
    
    public $db; //database object, used over and over so connection is established only once
    
    
    function startDB(){
	
	$db_params = OpenContext_OCConfig::get_db_config();
        $db = new Zend_Db_Adapter_Pdo_Mysql($db_params);
	$db->getConnection();
	$this->db = $db;
    }
    
    
    function getByIDType($id, $itemType){
	$this->startDB();
	$this->itemType = $itemType;
	if($itemType == "spatial"){
	    $this->get_spatial($id);
	}
	elseif($itemType == "media"){
	    $this->get_media($id);
	}
	elseif($itemType == "project"){
	    $this->get_project($id);
	}
	elseif($itemType == "document"){
	    $this->get_document($id);
	}
	elseif($itemType == "person"){
	    $this->get_person($id);
	}
	elseif($itemType == "variable"){
	    $this->get_variable($id);
	}
	elseif($itemType == "property"){
	    $this->get_variable($id);
	}
	elseif($itemType == "table"){
	    $this->get_variable($id);
	}
	else{
	    //wierd things are going on!
	}
	
    }
    
    function get_spatial($id){
	$db = $this->db;
	$sql = 'SELECT *
                FROM space
                WHERE uuid = "'.$id.'"
                LIMIT 1';
		
        $result = $db->fetchAll($sql, 2);
        if($result){
            
            $this->noid = $result[0]["noid"];
            $this->projectUUID = $result[0]["project_id"];
            $this->sourceID = $result[0]["source_id"];
            $this->classID = $result[0]["class_uuid"];
	    $this->itemUUID = $result[0]["uuid"];
	    $this->label = $result[0]["space_label"];
	    $this->schemaFix = $result[0]["schema_fix"];
	    $this->viewCount = $result[0]["view_count"];
            $this->createdTime = $result[0]["created"];
            $this->updatedTime = $result[0]["updated"];
	    $this->atomEntry = $result[0]["atom_entry"];
	    $this->newArchaeoML = $result[0]["archaeoML"];
	    
	    if($this->newArchaeoML == "big value"){
		$this->newArchaeoML = $this->big_value_get("archaeoML", $db);
	    }
	}
    }
    
    function get_media($id){
	$db = $this->db;
	
    }
    
    function get_project($id){
	$db = $this->db;
	
    }
    
    function get_document($id){
	$db = $this->db;
	
    }
    
    function get_person($id){
	$db = $this->db;
	
    }
    
    function get_variable($id){
	$db = $this->db;
	
    }
    
    function get_property($id){
	$db = $this->db;
	
    }
    
    
    //get User data from database
    function getByID($id){
        
        $id = $this->security_check($id);
        $output = false; //no user
        $db_params = OpenContext_OCConfig::get_db_config();
        $db = new Zend_Db_Adapter_Pdo_Mysql($db_params);
	$db->getConnection();
        
        $sql = 'SELECT *
                FROM space
                WHERE uuid = "'.$id.'"
                LIMIT 1';
		
        $result = $db->fetchAll($sql, 2);
        if($result){
            
            $this->noid = $result[0]["noid"];
            $this->projectUUID = $result[0]["project_id"];
            $this->sourceID = $result[0]["source_id"];
            $this->classID = $result[0]["class_uuid"];
	    $this->itemUUID = $result[0]["uuid"];
	    $this->label = $result[0]["space_label"];
	    $this->schemaFix = $result[0]["schema_fix"];
	    $this->viewCount = $result[0]["view_count"];
            $this->createdTime = $result[0]["created"];
            $this->updatedTime = $result[0]["updated"];
	    $this->archaeoML = $result[0]["space_archaeoml"];
	    $this->atomFull = $result[0]["xml_full"];
	    
	    $this->atomEntry = $result[0]["atom_entry"];
	    $this->newArchaeoML = $result[0]["archaeoML"];
	    
	    if($this->newArchaeoML == "big value"){
		$this->newArchaeoML = $this->big_value_get("archaeoML", $db);
	    }
	    
	    
	    if(strlen($result[0]["space_archaeoml"])<2){
		$this->archaeoML = $this->newArchaeoML;
	    }
	    
	    if(strlen($this->atomEntry)<2 && strlen($this->newArchaeoML)>2){
		$this->DOM_spatialAtomCreate($this->newArchaeoML);
		$this->update_atom_entry();
	    }
	    
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
    
    
    function DOM_spatialAtomCreate($archaeML_string){
		
		$host = OpenContext_OCConfig::get_host_config();
		$baseURI = $host."/subjects/";
		
		$archaeML_string = $this->namespace_fix($archaeML_string);
		
		
		@$spatialItem = simplexml_load_string($archaeML_string);
	
		if(!$spatialItem){
		    $this->atomEntry = false;
		    $this->newArchaeoML = false;
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
		$pubDate = $this->createdTime;
		if(!$pubDate){
		    $pubDate = $this->updatedTime;
		}
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
	$baseURI = $host."/subjects/";
	$this->archaeoML = file_get_contents($baseURI.$this->itemUUID.".xml");
    }
    
    
    
    
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
	    
	    $this->newArchaeoML = $archaeoML;
	    unset($spatialItem);
	    $this->committ_update_archaeoML();
	}
	
	return $archaeoML;
    }
    
    /*
    Big inserts break MySQL without special changes to settings, which could cause other problems
    So, it insert big values, we break them into smaller strings so that they can be recombined later
    */
    function big_value_get($field, $db){
	$string = "";
	
	$sql = "SELECT value_frag
	FROM big_values
	WHERE field = '".$field."'
	AND itemUUID = '".$this->itemUUID."'
	ORDER BY id
	";
	
	$result = $db->fetchAll($sql, 2);
	if($result){
	    foreach($result as $row){
		$string.= $row["value_frag"];
	    }
	}
	return $string;
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
    
     
    
    
}
