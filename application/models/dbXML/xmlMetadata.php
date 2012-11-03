<?php

/*
This is used for constructing medatada XML used in spatial units, projects, documents, media items, etc.
*/
class dbXML_xmlMetadata  {
    
    public $metadata;
    public $label;
    public $itemUUID;
    public $className;
    public $contributors; //contributors
    
    public $firstSpaceObj;
    
    public $itemType;
    public $geoData;
    public $chronoData;
    
    public $doc;
    public $rootNode;
    public $doSocial = true; //make a social usage node, set to false if project
    
    
    const spaceRootURI = "http://opencontext.org/subjects/";
    const mediaRootURI = "http://opencontext.org/media/";
    const personRootURI = "http://opencontext.org/persons/";
    const projRootURI = "http://opencontext.org/projects/";
    const docsRootURI = "http://opencontext.org/documents/";
    const propRootURI = "http://opencontext.org/properties/";
    
    
    function getIdentifierLink(){
		  $typeArray = array("spatial" => self::spaceRootURI,
					  "media" => self::mediaRootURI,
					  "person" => self::personRootURI,
					  "project" => self::projRootURI,
					  "document" => self::docsRootURI,
					  "property" => self::propRootURI
					  );
		  
		  if(array_key_exists($this->itemType, $typeArray)){
				return $typeArray[$this->itemType];
		  }
		  else{
				return false;
		  }
	
    }
    
    
    
    
    public function addMetadata(){
		  $metadata = $this->metadata;
		  $doc = $this->doc;
		  $rootNode = $this->rootNode;
		  
		  $coinsArray = array();
		  $coinsArray[] = "ctx_ver=Z39.88-2004";
		  $coinsArray[] = "rft_val_fmt=info%3Aofi%2Ffmt%3Akev%3Amtx%3Adc";
		  $coinsArray[] = "rft_val_fmt=info%3Aofi%2Ffmt%3Akev%3Amtx%3Adc";
		  $coinsArray[] = "rft.type=dataset";
		  
		  if($this->className != false){
				$title = $metadata->projectName.": ".$this->label." (".$this->className.")";
		  }
		  elseif($this->itemType == "project"){
				$title = $metadata->projectName.": (Overview)";
		  }
		  else{
				$title = $metadata->projectName.": ".$this->label;
		  }
		  
		  
		  
		  
		  $element = $doc->createElement("oc:metadata");
		  
		  //dc:title
		  $elementB = $doc->createElement("dc:title");
		  $elementBtext = $doc->createTextNode($title);
		  $elementB->appendChild($elementBtext);
		  $element->appendChild($elementB);
		  $coinsArray[] = "rft.title=".urlencode($title);
		  
		  $elementB = $doc->createElement("dc:publisher");
		  $elementBtext = $doc->createTextNode("Open Context");
		  $elementB->appendChild($elementBtext);
		  $element->appendChild($elementB);
		  $coinsArray[] = "rft.publisher=".urlencode("Open Context");
		  $coinsArray[] = "rft.source=".urlencode("Open Context");
		  
		  $elementB = $doc->createElement("dc:date");
		  $elementBtext = $doc->createTextNode(date("Y-m-d", strtotime($metadata->projCreatedXML)));
		  $elementB->appendChild($elementBtext);
		  $element->appendChild($elementB);
		  
		  if(is_array($metadata->projCoverages)){
				foreach($metadata->projCoverages as $key=>$valArray){
			  $elementB = $doc->createElement("dc:coverage");
			  $elementBtext = $doc->createTextNode($valArray["value"]);
			  $elementB->appendChild($elementBtext);
			  $element->appendChild($elementB);
			  $coinsArray[] = "rft.coverage=".urlencode($valArray["value"]);
				}
		  }
		  if(is_array($metadata->projSubjects)){
				foreach($metadata->projSubjects as $key=>$valArray){
			  $elementB = $doc->createElement("dc:subject");
			  $elementBtext = $doc->createTextNode($valArray["value"]);
			  $elementB->appendChild($elementBtext);
			  $element->appendChild($elementB);
			  $coinsArray[] = "rft.subject=".urlencode($valArray["value"]);
				}
		  }
		  
		  if(is_array($this->contributors)){
				foreach($this->contributors as $key=>$value){
					 $elementB = $doc->createElement("dc:contributor");
					 $elementB->setAttribute("href", self::personRootURI.$key);
					 $elementBtext = $doc->createTextNode($value);
					 $elementB->appendChild($elementBtext);
					 $element->appendChild($elementB);
					 $coinsArray[] = "rft.contributor=".urlencode($value);
				}
		  }
		  
		  if(is_array($metadata->projCreators)){
				foreach($metadata->projCreators as $key=>$valArray){
			  $elementB = $doc->createElement("dc:creator");
			  $elementB->setAttribute("href", self::personRootURI.$valArray["itemUUID"]);
			  $elementBtext = $doc->createTextNode($valArray["value"]);
			  $elementB->appendChild($elementBtext);
			  $element->appendChild($elementB);
			  $coinsArray[] = "rft.creator=".urlencode($valArray["value"]);
				}
		  }
		  
		  if($this->getIdentifierLink() != false){
				$elementB = $doc->createElement("dc:identifier");
				$elementBtext = $doc->createTextNode($this->getIdentifierLink().$this->itemUUID);
				$elementB->appendChild($elementBtext);
				$element->appendChild($elementB);
				$coinsArray[] = "rft.identifier=".urlencode($this->getIdentifierLink().$this->itemUUID);
		  }
		  
		  $elementB = $doc->createElement("oc:project_name");
		  $elementB->setAttribute("href", self::projRootURI.$metadata->projectUUID);
		  $elementBtext = $doc->createTextNode($metadata->projectName);
		  $elementB->appendChild($elementBtext);
		  $element->appendChild($elementB);
	  
		  $elementB = $doc->createElement("oc:pub_date");
		  $elementBtext = $doc->createTextNode(date("Y-m-d\TH:i:s\-07:00", strtotime($metadata->projCreatedXML)));
		  $elementB->appendChild($elementBtext);
		  $element->appendChild($elementB);
		  
		  $elementB = $doc->createElement("oc:coins");
		  $elementBtext = $doc->createTextNode(implode("&", $coinsArray));
		  $elementB->appendChild($elementBtext);
		  $element->appendChild($elementB);
		  
		  
		  if($this->geoData){
				$geoData = $this->geoData;
				
				if($this->firstSpaceObj){
			  $linkedUUID = $this->firstSpaceObj->linkedUUID;
				}
				else{
			  $linkedUUID = false;
				}
				
				$elementB = $doc->createElement("oc:geo_reference");
				$elementC = $doc->createElement("oc:geo_lat");
				$elementCtext = $doc->createTextNode($geoData["geoLat"]);
				$elementC->appendChild($elementCtext);
				$elementB->appendChild($elementC);
				
				$elementC = $doc->createElement("oc:geo_long");
				$elementCtext = $doc->createTextNode($geoData["geoLon"]);
				$elementC->appendChild($elementCtext);
				$elementB->appendChild($elementC);
				
				$elementC = $doc->createElement("oc:metasource");
				if($geoData["geoSource"] == "self" || ($geoData["geoSource"] == $this->itemUUID || $geoData["geoSource"] == $linkedUUID)){
			  $elementC->setAttribute("ref_type", "self");
			  if($linkedUUID != false){
					$geoSource = $linkedUUID;
			  }
			  else{
					$geoSource = $this->itemUUID;
			  }
				}
				else{
			  $elementC->setAttribute("ref_type", "contained");
			  $geoSource = $geoData["geoSource"];
				}
				$elementC->setAttribute("href", self::spaceRootURI.$geoSource);
				
				$elementD = $doc->createElement("oc:source_name");
				$elementDtext = $doc->createTextNode($geoData["geoSourceName"]);
				$elementD->appendChild($elementDtext);
				$elementC->appendChild($elementD);
				
				$elementD = $doc->createElement("oc:source_id");
				$elementDtext = $doc->createTextNode($geoSource);
				$elementD->appendChild($elementDtext);
				$elementC->appendChild($elementD);
				
				$elementB->appendChild($elementC);
	  
				$element->appendChild($elementB);
		  }
		  
		  
		  if($metadata->licenseURI){
				$elementB = $doc->createElement("oc:copyright_lic");
				$elementB->setAttribute("href", $metadata->licenseURI);
				$elementC = $doc->createElement("oc:lic_name");
				$elementCtext = $doc->createTextNode($metadata->licenseName);
				$elementC->appendChild($elementCtext);
				$elementB->appendChild($elementC);
				$elementC = $doc->createElement("oc:lic_vers");
				$elementCtext = $doc->createTextNode($metadata->licenseVersion);
				$elementC->appendChild($elementCtext);
				$elementB->appendChild($elementC);
				$elementC = $doc->createElement("oc:lic_URI");
				$elementCtext = $doc->createTextNode($metadata->licenseURI);
				$elementC->appendChild($elementCtext);
				$elementB->appendChild($elementC);
				$elementC = $doc->createElement("oc:lic_icon_URI");
				$elementCtext = $doc->createTextNode($metadata->licenseIconURI);
				$elementC->appendChild($elementCtext);
				$elementB->appendChild($elementC);
				
				$element->appendChild($elementB);
		  }
	  
		  $rootNode->appendChild($element); 
		  
		  
		  
		  //add social usage, gets added to root, not oc:metadata element
		  if($this->doSocial){
				$elementB = $doc->createElement("oc:social_usage");
				
				//updated time
				$elementC = $doc->createElement("oc:last_updated");
				$elementCtext = $doc->createTextNode(date("Y-m-d\TH:i:s\-07:00"));
				$elementC->appendChild($elementCtext);
				$elementB->appendChild($elementC);
				//itemviews
				$elementC = $doc->createElement("oc:item_views");
				$elementD = $doc->createElement("oc:count");
				$elementDtext = $doc->createTextNode(1);
				$elementD->appendChild($elementDtext);
				$elementC->appendChild($elementD);
				$elementD = $doc->createElement("oc:view_time");
				$elementDtext = $doc->createTextNode(date("Y/m/d H:i:s"));
				$elementD->appendChild($elementDtext);
				$elementC->appendChild($elementD);
				$elementB->appendChild($elementC);
				
				//user tags
				$elementC = $doc->createElement("oc:user_tags");
				if(is_array($this->chronoData)){
			
			  foreach($this->chronoData as $chronoTag){
					$elementD = $doc->createElement("oc:tag");
					$elementD->setAttribute("type", "chronological");
					$elementD->setAttribute("status", "public");
					
					$elementE = $doc->createElement("oc:name");
					$elementEtext = $doc->createTextNode($chronoTag["label"]);
					$elementE->appendChild($elementEtext);
					$elementD->appendChild($elementE);
					
					$elementE = $doc->createElement("oc:chrono");
					
					if($chronoTag["start_time"] == $chronoTag["end_time"]){
				  $elementE->setAttribute("isDuration", "false");
					}
					else{
				  $elementE->setAttribute("isDuration", "true");
					}
					
					$elementF = $doc->createElement("oc:time_start");
					$elementFtext = $doc->createTextNode($chronoTag["start_time"]);
					$elementF->appendChild($elementFtext);
					$elementE->appendChild($elementF);
					
					$elementF = $doc->createElement("oc:time_finish");
					$elementFtext = $doc->createTextNode($chronoTag["end_time"]);
					$elementF->appendChild($elementFtext);
					$elementE->appendChild($elementF);
					
					
					$elementF = $doc->createElement("oc:metasource");
					if($chronoTag["chronoSource"] == "self" || ($chronoTag["chronoSource"] == $this->itemUUID || $chronoTag["chronoSource"] == $linkedUUID)){
				  $elementF->setAttribute("ref_type", "self");
				  if($linkedUUID != false){
						$chronoSource = $linkedUUID;
				  }
				  else{
						$chronoSource = $this->itemUUID;
				  }
					}
					else{
				  $elementF->setAttribute("ref_type", "contained");
				  $chronoSource = $chronoTag["chronoSource"];
					}
					$elementF->setAttribute("href", self::spaceRootURI.$chronoSource);
					
					$elementG = $doc->createElement("oc:source_name");
					$elementGtext = $doc->createTextNode($chronoTag["sourceName"]);
					$elementG->appendChild($elementGtext);
					$elementF->appendChild($elementG);
					
					$elementG = $doc->createElement("oc:source_id");
					$elementGtext = $doc->createTextNode($chronoSource);
					$elementG->appendChild($elementGtext);
					$elementF->appendChild($elementG);
					
			
					$elementE->appendChild($elementF); //add metasource
					$elementD->appendChild($elementE);//add chrono
					
					
					$elementDd = $doc->createElement("oc:tag_creator");
					$elementDd->setAttribute("id", $chronoTag["creator_uuid"]);
					$elementE = $doc->createElement("oc:creator_name");
					$elementEtext = $doc->createTextNode($chronoTag["taggerName"]);
					$elementE->appendChild($elementEtext);
					$elementDd->appendChild($elementE);
					
					$elementE = $doc->createElement("oc:set_label");
					$elementEtext = $doc->createTextNode($chronoTag["note_id"]);
					$elementE->appendChild($elementEtext);
					$elementDd->appendChild($elementE);
					
					$elementE = $doc->createElement("oc:set_id");
					$elementEtext = $doc->createTextNode($chronoTag["note_id"]);
					$elementE->appendChild($elementEtext);
					$elementDd->appendChild($elementE);
					
					$elementE = $doc->createElement("oc:set_date");
					$elementEtext = $doc->createTextNode(date("Y-m-d\TH:i:s\-07:00", strtotime($chronoTag["created"])));
					$elementE->appendChild($elementEtext);
					$elementDd->appendChild($elementE);
					$elementD->appendChild($elementDd);//add chrono tag creator
					
					$elementC->appendChild($elementD);//add user tag
			  }
				}
				$elementB->appendChild($elementC);//add user tags
				
				
				$rootNode->appendChild($elementB); //add social usage to root
		  }
		  
		  $this->rootNode = $rootNode;
    }
    
    
}  
