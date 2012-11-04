<?php

class dbXML_xmlProperty  {
    
    public $itemObj;
    public $doc;
    public $root;
    
    const NSarchaeoML = "http://ochre.lib.uchicago.edu/schema/Project/Variable.xsd"; //URI to the ArchaeoML property Schema
    const NSocSpace = "http://opencontext.org/schema/property_schema_v1.xsd"; //URI to open context property Schema
    const NSdc = "http://purl.org/dc/elements/1.1/";
    const NSgml = "http://www.opengis.net/gml";
    const NSxmhtml = "";
    
    const classIconRoot = "http://opencontext.org/database/ui_images/med_oc_icons/";
    const spaceRootURI = "http://opencontext.org/subjects/";
    const mediaRootURI = "http://opencontext.org/media/";
    const propRootURI = "http://opencontext.org/properties/";
    
    public function initialize(){
		  $itemObj = $this->itemObj;
		  $doc = new DOMDocument("1.0", "utf-8");
		  $doc->formatOutput = true;
		  $root = $doc->createElement("arch:property");
		  $root->setAttribute("xmlns:arch", self::NSarchaeoML);
		  $root->setAttribute("xmlns:oc", self::NSocSpace);
		  $root->setAttribute("xmlns:dc", self::NSdc);
		  //$root->setAttribute("xmlns:gml", self::NSgml);
		  $root->setAttribute("UUID", $itemObj->itemUUID);
		  $root->setAttribute("ownedBy", $itemObj->projectUUID);
		  $doc->appendChild($root);
		  $this->doc = $doc;
		  $this->root = $root;
    }
    
    public function addName(){
        
        $doc = $this->doc;
		  $root = $this->root;
		  $itemObj = $this->itemObj;
		  
		  //name construction
		  $element = $doc->createElement("arch:name");
		  $elementB = $doc->createElement("arch:string");
		  $elementBtext = $doc->createTextNode($itemObj->label);
		  $elementB->appendChild($elementBtext);
		  $element->appendChild($elementB);
		  $root->appendChild($element);
		
    }

    public function addPropDetails(){
	
		  $doc = $this->doc;
		  $root = $this->root;
		  $itemObj = $this->itemObj;
		  
		  //name construction
		  $element = $doc->createElement("oc:manage_info");
		  $element->setAttribute("variableID", $itemObj->varUUID);
		  $element->setAttribute("valueID", $itemObj->valUUID);
		  
		  $elementB = $doc->createElement("oc:queryVal");
		  $elementBtext = $doc->createTextNode($itemObj->queryVal);
		  $elementB->appendChild($elementBtext);
		  $element->appendChild($elementB);
		  
		  $elementB = $doc->createElement("oc:varType");
		  $elementBtext = $doc->createTextNode($itemObj->varType);
		  $elementB->appendChild($elementBtext);
		  $element->appendChild($elementB);
		  
		  $elementB = $doc->createElement("oc:propVariable");
		  $elementBtext = $doc->createTextNode($itemObj->varLabel);
		  $elementB->appendChild($elementBtext);
		  $element->appendChild($elementB);
		  
		  if($itemObj->varUnitURI != false){
			  $elementB->setAttribute("href", $itemObj->varUnitURI);
			  $elementB->setAttribute("name", $itemObj->varUnitName);
			  $elementB->setAttribute("abrv", $itemObj->varUnitAbrv);
		  }
	
	
		  $elementB = $doc->createElement("oc:propValue");
		  $elementBtext = $doc->createTextNode($itemObj->value);
		  $elementB->appendChild($elementBtext);
		  $element->appendChild($elementB);
	
		  if(is_array($itemObj->varSummary)){
				
				if(is_array($itemObj->frequencyRanks)){
					 $frequencyRanks = $itemObj->frequencyRanks;
				}
				else{
					 $frequencyRanks = array();
				}
				
				if(is_array($itemObj->varSummary)){
					 $elementBouter = $doc->createElement("oc:AllPropStats");
					 $element->appendChild($elementBouter);
				}
				
				foreach($itemObj->varSummary as $sumKey => $varSum){
			  
					 if(array_key_exists($sumKey, $frequencyRanks)){
						  $rank = $frequencyRanks[$sumKey]["rank"];
						  $propCount = $frequencyRanks[$sumKey]["count"];
					 }
					 else{
						  $rank = false;
						  $propCount = false;
					 }
					
					
					 if($sumKey == "space"){
						  $sumKey = "spatialUnit";
					 }
					 elseif($sumKey == "media"){
						  $sumKey = "resource";
					 }
				
					 $elementB = $doc->createElement("oc:propStats");
					 $elementB->setAttribute("observeType", $sumKey);
			  
					 if(array_key_exists("uniqueCount", $varSum)){
						  $elementC = $doc->createElement("oc:numUniqueVals");
						  $elementCtext = $doc->createTextNode($varSum["uniqueCount"]);
						  $elementC->appendChild($elementCtext);
						  $elementB->appendChild($elementC);
					 }
					 
					 if(array_key_exists("varTotalObs", $varSum)){
						  $elementC = $doc->createElement("oc:varTotalObs");
						  $elementCtext = $doc->createTextNode($varSum["varTotalObs"]);
						  $elementC->appendChild($elementCtext);
						  $elementB->appendChild($elementC);
					 }
			  
					 if(array_key_exists("min", $varSum)){
						  $elementC = $doc->createElement("oc:propMin");
						  $elementCtext = $doc->createTextNode($varSum["min"]);
						  $elementC->appendChild($elementCtext);
						  $elementB->appendChild($elementC);
					 }
					 
					 if(array_key_exists("max", $varSum)){
						  $elementC = $doc->createElement("oc:propMax");
						  $elementCtext = $doc->createTextNode($varSum["max"]);
						  $elementC->appendChild($elementCtext);
						  $elementB->appendChild($elementC);
					 }
					 
					 if(array_key_exists("maxCount", $varSum)){
						  $elementC = $doc->createElement("oc:propMaxCount");
						  $elementCtext = $doc->createTextNode($varSum["maxCount"]);
						  $elementC->appendChild($elementCtext);
						  $elementB->appendChild($elementC);
					 }
					 
			  
					 if(array_key_exists("nominalGraph", $varSum)){
						  
						  if($rank != false){
								$elementC = $doc->createElement("oc:freqRank");
								$elementCtext = $doc->createTextNode($rank);
								$elementC->appendChild($elementCtext);
								$elementB->appendChild($elementC);
						  }
						  
						  $elementC = $doc->createElement("oc:graphData");
						  
						  foreach($varSum["nominalGraph"] as $propIDkey => $barData){
						 
								$elementD = $doc->createElement("oc:bar");
								$elementD->setAttribute("count", $barData["count"]);
								$elementD->setAttribute("setURL", $barData["setURL"] );
								$elementDtext = $doc->createTextNode($barData["text"]);
								$elementD->appendChild($elementDtext);
								$elementC->appendChild($elementD);
						  }
						  
						  $elementB->appendChild($elementC);
					 }
					 
					 if(array_key_exists("histogram", $varSum)){
						  
						  if($rank != false){
								$elementC = $doc->createElement("oc:valueRank");
								$elementCtext = $doc->createTextNode($rank);
								$elementC->appendChild($elementCtext);
								$elementB->appendChild($elementC);
						  }
						  
						  $elementC = $doc->createElement("oc:graphData");
						  
						  foreach($varSum["histogram"] as $barData){
						 
								$elementD = $doc->createElement("oc:bar");
								$elementD->setAttribute("low", $barData["lowVal"]);
								$elementD->setAttribute("high", $barData["highVal"]);
								$elementD->setAttribute("count", $barData["count"]);
								$elementD->setAttribute("setURL", $barData["setURL"] );
								$elementDtext = $doc->createTextNode($barData["lowVal"]." to ".$barData["highVal"]);
								$elementD->appendChild($elementDtext);
								$elementC->appendChild($elementD);
						  }
						  
						  $elementB->appendChild($elementC);
					 }
			  
					 $elementBouter->appendChild($elementB);
				}//end loop through different types of props
		  }
	
	
		  if($itemObj->varLinkURI || $itemObj->propLinkURI){
				$elementB = $doc->createElement("oc:linkedData");
				if($itemObj->varLinkURI){
					 $elementC = $doc->createElement("oc:relationLink");
					 $elementC->setAttribute("localType", "variable");
					 $elementC->setAttribute("id", $itemObj->varUUID);
					 $elementC->setAttribute("href", $itemObj->varLinkURI);
					 $elementD = $doc->createElement("oc:vocabulary");
					 $elementD->setAttribute("href", $itemObj->varLinkVocabURI);
					 $elementDtext = $doc->createTextNode($itemObj->varLinkVocab);
					 $elementD->appendChild($elementDtext);
					 $elementC->appendChild($elementD);
					 $elementD = $doc->createElement("oc:label");
					 $elementDtext = $doc->createTextNode($itemObj->varLinkLabel);
					 $elementD->appendChild($elementDtext);
					 $elementC->appendChild($elementD);
			  
					 if($itemObj->propLinkURI){
						  $elementD = $doc->createElement("oc:targetLink");
						  $elementD->setAttribute("localType", "property");
						  $elementD->setAttribute("id", $itemObj->itemUUID);
						  $elementD->setAttribute("href", $itemObj->propLinkURI);
						  $elementE = $doc->createElement("oc:vocabulary");
						  $elementE->setAttribute("href", $itemObj->propLinkVocabURI);
						  $elementEtext = $doc->createTextNode($itemObj->propLinkVocab);
						  $elementE->appendChild($elementEtext);
						  $elementD->appendChild($elementE);
						  $elementE = $doc->createElement("oc:label");
						  $elementEtext = $doc->createTextNode($itemObj->propLinkLabel);
						  $elementE->appendChild($elementEtext);
						  $elementD->appendChild($elementE);
						  $elementC->appendChild($elementD);
					 }
					 $elementB->appendChild($elementC);
				}
				$element->appendChild($elementB);
		  }
		  $root->appendChild($element);
    }
    
    
    
    
    
    public function addPropsLinks(){
        
        $doc = $this->doc;
		  $root = $this->root;
		  $itemObj = $this->itemObj;
	
		  if(is_array($itemObj->propertiesObj->properties)){
	  
				$allProperties = $itemObj->propertiesObj->properties;
				$obsProps = $allProperties[1];
				if(count($obsProps)>0){
					 $xmlProperties = new dbXML_xmlProperties;
					 $xmlProperties->doc = $doc;
					 $xmlProperties->rootNode = $root;
					 $xmlProperties->properties = $obsProps;
					 $xmlProperties->addProperties();
				}
		  }//end case with properties
	
		  if(is_array($itemObj->linksObj->links)){
				$allLinks = $itemObj->linksObj->links;
				$spaceLinks = $itemObj->linksObj->spaceLinks;
				$mediaLinks = $itemObj->linksObj->mediaLinks;
				$personLinks = $itemObj->linksObj->personLinks;
				$documentLinks = $itemObj->linksObj->documentLinks;
				
				$obsLinks = 0;
				$obsSpace = false;
				$obsMedia = false;
				$obsPersons = false;
				$obsDocs = false;
				
				if(is_array($allLinks)){
					 if(array_key_exists(1, $allLinks)){
						  $obsLinks = $allLinks[1];
					 }
				}
				if(is_array($spaceLinks)){
					 if(array_key_exists(1, $spaceLinks)){
						  $obsSpace = $spaceLinks[1];
					 }
				}
				if(is_array($mediaLinks)){
					 if(array_key_exists(1, $mediaLinks)){
						  $obsMedia = $mediaLinks[1];
					 }
				}
				if(is_array($personLinks)){
					 if(array_key_exists(1, $personLinks)){
						  $obsPersons = $personLinks[1];
					 }
				}
				if(is_array($documentLinks)){
					 if(array_key_exists(1, $documentLinks)){
						  $obsDocs  = $documentLinks[1];
					 }
				}
				
				if(count($obsLinks)>0){
					 $xmlLinks = new dbXML_xmlLinks;
					 $xmlLinks->doc = $doc;
					 $xmlLinks->rootNode = $root;
					 $xmlLinks->links = $obsLinks;
					 $xmlLinks->spaceLinks = $obsSpace;
					 $xmlLinks->mediaLinks = $obsMedia;
					 $xmlLinks->personLinks = $obsPersons;
					 $xmlLinks->documentLinks = $obsDocs;
					 $xmlLinks->addLinks();
				}    
		  }//end case with links   
		  
		  //add standard notes
		  if(is_array($itemObj->propertiesObj->notes)){
				$allNotes = $itemObj->propertiesObj->notes;
				$obsNotes = $allNotes[1];
				if(count($obsNotes)>0){
					 $xmlNotes = new dbXML_xmlNotes;
					 $xmlNotes->doc = $doc;
					 $xmlNotes->rootNode = $root;
					 $xmlNotes->notes = $obsNotes;
					 $xmlNotes->addNotes();
				}
		  }
		  elseif($itemObj->varDescription != false || $itemObj->propDescription){
				$generalNotes = array();
				if($itemObj->varDescription != false){
					 $generalNotes[] = array("noteText" => $itemObj->varDescription,
								 "validForXML" => $itemObj->varDesXMLok,
								 "type" => "var_des");
				}
				if($itemObj->propDescription != false){
					 $generalNotes[] = array("noteText" => $itemObj->propDescription,
								 "validForXML" => $itemObj->propDesXMLok,
								 "type" => "prop_des");
				}
				
				$xmlNotes = new dbXML_xmlNotes;
				$xmlNotes->doc = $doc;
				$xmlNotes->rootNode = $root;
				$xmlNotes->notes = $generalNotes;
				$xmlNotes->addNotes();    
		  }
    }//end function
    
    
    
    public function addMetadata(){
        
        $doc = $this->doc;
		  $root = $this->root;
		  $itemObj = $this->itemObj;
			
		  $xmlMetadata = new dbXML_xmlMetadata;
		  $xmlMetadata->metadata = $itemObj->metadataObj;
		  $xmlMetadata->label = $itemObj->label;
		  $xmlMetadata->className = false;
		  $xmlMetadata->contributors = $itemObj->linksObj->contributors;
		  $xmlMetadata->itemType = "property";
		  $xmlMetadata->itemUUID = $itemObj->itemUUID;
		  
		  $xmlMetadata->doc = $doc;
		  $xmlMetadata->rootNode = $root;
		  $xmlMetadata->addMetadata();
    }//end function
    
    
    function nameSpaces(){
		  $nameSpaceArray = array("oc" => self::NSocSpace,
					  "dc" => OpenContext_OCConfig::get_namespace("dc"),
					  "arch" => self::NSarchaeoML,
					  "gml" => OpenContext_OCConfig::get_namespace("gml"),
					  "kml" => OpenContext_OCConfig::get_namespace("kml"));
		  
		  return $nameSpaceArray;
    }
    
}  
