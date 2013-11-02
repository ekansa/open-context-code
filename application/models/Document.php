<?php


//this class interacts with the database for accessing and changing Document items
class Document {
    
	 public $db;
    public $noid;
    public $projectUUID;
    public $sourceID;
    public $mimeType;
    public $itemUUID;
    public $schemaFix;
    public $label;
    public $viewCount;
    public $createdTime;
    public $updatedTime;
    public $archaeoML;
    public $atomFull; 
    public $atomEntry;
    public $internalDoc;
    
    public $kml_in_Atom; // add KML timespan to atom entry, W3C breaks validation
    public $xhtml_rel = "alternate"; // value for link rel attribute for XHTML version ("self" or "alternate")
    public $atom_rel = "alternate"; //value for link rel attribute for Atom version ("self" or "alternate")
	
	
    public $geoCurrent; //check if geo-reference is current.

    
    const default_mimeType = "application/xhtml+xml";
    const OC_namespaceURI = "http://opencontext.org/schema/resource_schema_v1.xsd";
    
    //get User data from database
    function getByID($id){
        
        $id = $this->security_check($id);
        $output = false; //no user
		  $db = $this->startDB();
        
        $sql = 'SELECT *
                FROM diary
                WHERE uuid = "'.$id.'"
                LIMIT 1';
		
        $result = $db->fetchAll($sql, 2);
        if($result){
            
            $this->noid = $result[0]["noid"];
            $this->projectUUID = $result[0]["project_id"];
            $this->sourceID = $result[0]["source_id"];
				$this->itemUUID = $result[0]["uuid"];
				$this->label = $result[0]["diary_label"];
				$this->internalDoc = $result[0]["internal_doc"];
				$this->mimeType = self::default_mimeType;
				$this->viewCount = $result[0]["view_count"];
            $this->createdTime = $result[0]["created"];
            $this->updatedTime = $result[0]["updated"];
				$this->atomEntry = $result[0]["atom_entry"];
				$this->archaeoML = $result[0]["archaeoML"];
	    
				if(OpenContext_OCConfig::need_bigString($this->archaeoML)){
					 $bigString = new BigString;
					 $xml = $bigString->get_CurrentBigString($this->itemUUID, "archaeoML", $db);
					 if(strlen($xml)>strlen($this->archaeoML)){
						  $this->archaeoML = $xml;
					 }
				}
	    
				$this->accentFix($db, $this->atomEntry , "atom_entry");
				$this->accentFix($db, $this->archaeoML , "archaeoML");
	    
				$this->xhtml_rel = "alternate";
				$this->atom_rel = "self";
	    
            $output = true;
        }
        
        return $output;
    }
    
    
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
    
    
    
         //used to fix legacy non utf8 problem
    function accentFix($db, $xmlString, $XMLtype){
		  $db = $this->startDB();
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
				if($xml){
					 $data = array();
					 $where = array();
					 $where[] = "uuid = '".$this->itemUUID."' ";
					 if($XMLtype == "atom_entry"){
						  $this->atomEntry = $newXML;
						  $data["atom_entry"] = $newXML;
					 }
					 elseif($XMLtype == "archaeoML"){
						  $this->archaeoML = $newXML;
						  $data["archaeoML"] = $newXML;
					 }
					 $db->update("diary", $data, $where);
				}
		  }
	
    }//end function
    
    
    
    
    function versionUpdate($id, $db = false){
	
		  $db = $this->startDB();
		  $this->setUTFconnection($db);
		  
		  $sql = 'SELECT archaeoML AS archaeoML
						FROM diary
						WHERE uuid = "'.$id.'"
						LIMIT 1';
		
        $result = $db->fetchAll($sql, 2);
        if($result){
				$xmlString = $result[0]["archaeoML"];
				OpenContext_DeleteDocs::saveBeforeUpdate($id, "document", $xmlString);
		  }
	
    }//end function
    
    
    
    //create a new diary / document item
    function createUpdate($versionUpdate){
        
        $db = $this->startDB();
		  $this->setUTFconnection($db);
		
		  if(!$this->noid){
			  $this->noid = false;
		  }
    
		  $data = array("noid" => $this->noid,
					 "project_id" => $this->projectUUID,
					 "source_id" => $this->sourceID,
					 "uuid" => $this->itemUUID,
					 "diary_label" => $this->label,
					 "internal_doc" => $this->internalDoc,
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
				$bigString->saveCurrentBigString($this->itemUUID, "archaeoML", "document", $this->archaeoML, $db);
				$data["archaeoML"] = OpenContext_OCConfig::get_bigStringValue();
				$updateOK = true;
		  }
		  else{
				
				$data["archaeoML"] = $this->archaeoML;
				$updateOK = true;
		  }

		  $success = false;
		  try{
				$db->insert("diary", $data);
				$success = true;
		  }catch(Exception $e){
				$success = false;
				$where = array();
				$where[] = 'uuid = "'.$this->itemUUID.'" ';
				$db->update("diary", $data, $where);
				$success = true;
		  }
	  
		  $db->closeConnection();
		  return $success;
    }//end function
    
    
    
    
    
    
    
    
    function update_atom_entry(){
	
		  $updateOK = false;
		  $db = $this->startDB();
		  $this->setUTFconnection($db);
		  
		  @$xml = simplexml_load_string($this->atomEntry); 
		  if($xml){
				$data = array("atom_entry" => $this->atomEntry);
				$where = array();
				$where[] = 'uuid = "'.$this->itemUUID.'" ';
				$db->update("diary", $data, $where);
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
					  "kml" => OpenContext_OCConfig::get_namespace("kml"),
					  "xhtml" => OpenContext_OCConfig::get_namespace("xhtml"));
		  
		  return $nameSpaceArray;
    }
    
    
    function DOM_spatialAtomCreate($archaeML_string){
		  
		  $host = OpenContext_OCConfig::get_host_config();
		  $baseURI = $host."/documents/";
		  
		  @$mediaItem = simplexml_load_string($archaeML_string);
		  if(!@$mediaItem){
				return false;
		  }
	  
		  // Register OpenContext's namespace
		  //$mediaItem->registerXPathNamespace("oc", OpenContext_OCConfig::get_namespace("oc", "spatial"));
		  
		  $nameSpaceArray = $this->nameSpaces();
		  foreach($nameSpaceArray as $prefixKey => $uri){
				$mediaItem->registerXPathNamespace($prefixKey, $uri);
		  }
		  
		  // get the item_id
		  foreach($mediaItem->xpath("//arch:resource/@UUID") as $item_result) {
			  $uuid = $item_result."";
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
		  
		  //for documents / diaries
		  if ($mediaItem->xpath("//arch:internalDocument/arch:string[@type='xhtml']")) {
				foreach ($mediaItem->xpath("//arch:internalDocument/arch:string[@type='xhtml']/xhtml:div") as $docContent) {
					 @$dom_sxe = dom_import_simplexml($docContent);
					 if($dom_sxe){
						  $dom = new DOMDocument('1.0', 'UTF-8');
						  $dom_sxe = $dom->importNode($dom_sxe, true);
						  $dom->appendChild($dom_sxe);
						  $dom->appendChild($dom_sxe);
						  $this->internalDoc = $dom->saveXML($dom_sxe);
					 }
				}
		  }
		  else{
				if ($mediaItem->xpath("//arch:internalDocument/arch:string")) {
					 foreach ($mediaItem->xpath("//arch:internalDocument/arch:string") as $docContent) {
						  $this->internalDoc = (string)$docContent;
					 }
				}
		  }
		  
		  
		  
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
		  $feedTitleText = $atomEntryDoc->CreateTextNode( $project_name . ": " . $item_label . " (Narrative Document)" );	
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
		  $entryLink->setAttribute("title", "XHTML representation of ". $project_name . ": " . $item_label . " (Narrative Document)" );
		  $entryLink->setAttribute("href", $baseURI . $uuid);
		  $rootEntry->appendChild($entryLink);
		  
		  // entry archaeoml xml link element
		  $entryLink = $atomEntryDoc->createElement("link");
		  $entryLink->setAttribute("rel", "alternate");
		  $entryLink->setAttribute("type", "application/xml");
		  $entryLink->setAttribute("title", "ArchaeoML (XML) representation of ". $project_name . ": " . $item_label . " (Narrative Document)" );
		  $entryLink->setAttribute("href", $baseURI . $uuid. ".xml");
		  $rootEntry->appendChild($entryLink);
		  
		  // entry atom link element
		  $entryLink = $atomEntryDoc->createElement("link");
		  $entryLink->setAttribute("rel", $this->atom_rel);
		  $entryLink->setAttribute("type", "application/atom+xml");
		  $entryLink->setAttribute("title", "Atom representation of ". $project_name . ": " . $item_label . " (Narrative Document)" );
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
	  
		  
		  // entry atom Summary element
		  $entrySummary = $atomEntryDoc->createElement("summary");
		  $summaryTextContent =  strip_tags($this->internalDoc);
		  $initialLen = strlen($summaryTextContent);
		  $firstSpace = stripos($summaryTextContent, " ", 250);
		  if($initialLen >= $firstSpace + 5){
				$summaryTextContent = substr($summaryTextContent, 0, $firstSpace);
				$summaryTextContent .= "...";
		  }
		  
		  $summaryText =  $atomEntryDoc->CreateTextNode($summaryTextContent);
		  $entrySummary->appendChild($summaryText);
		  $rootEntry->appendChild($entrySummary);
		  
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
		  $baseURI = $host."/documents/";
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
		  $baseURI = $host."/documents/";
		  
		  $bad_array = array("http://www.opencontext.org/database/diary.php?item=",
					  "http://ishmael.ischool.berkeley.edu/documents/",
					  "http://opencontext/documents/",
					  "http://www.opencontext.org/documents/",
					  "http://testing.opencontext.org/documents/");
		  
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
				$data = array(	"schema_fix" => date("Y-m-d H:i:s"),
									 "archaeoML" => $this->archaeoML);
				$where = array();
				$where[] = 'uuid = "'.$this->itemUUID.'" ';
				$db->update("diary", $data, $where);
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
	
		  $db = $this->startDB();
	
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
		  
		  return $mediaItem;
    }
    

    //this updates chronological range to reflect the latest version
    function chronoUpdate($mediaItem){
	
		  $db = $this->startDB();
	
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
