<?php

class dbXML_xmlProject  {
    
    public $itemObj;
    public $doc;
    public $root;
    
    const NSarchaeoML = "http://ochre.lib.uchicago.edu/schema/Project/Project.xsd"; //URI to the ArchaeoML person Schema
    const NSocItem = "http://opencontext.org/schema/project_schema_v1.xsd"; //URI to open context person Schema
    const NSdc = "http://purl.org/dc/elements/1.1/";
    const NSgml = "http://www.opengis.net/gml";
    const NSxmhtml = "";
    
    const classIconRoot = "http://opencontext.org/database/ui_images/med_oc_icons/";
    const spaceRootURI = "http://opencontext.org/subjects/";
    const mediaRootURI = "http://opencontext.org/media/";
    const personRootURI = "http://opencontext.org/persons/"; //URI to open context person Schema
    
    public function initialize(){
	$itemObj = $this->itemObj;
	$doc = new DOMDocument("1.0", "utf-8");
	$doc->formatOutput = true;
	$root = $doc->createElement("arch:project");
	$root->setAttribute("xmlns:arch", self::NSarchaeoML);
	$root->setAttribute("xmlns:oc", self::NSocItem);
	$root->setAttribute("xmlns:dc", self::NSdc);
	$root->setAttribute("xmlns:gml", self::NSgml);
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
	$elementBtext = $doc->createTextNode($itemObj->projectName);
	$elementB->appendChild($elementBtext);
	$element->appendChild($elementB);
	$root->appendChild($element);
		
    }

    public function addProjectInfo(){
	
	$doc = $this->doc;
	$root = $this->root;
	$itemObj = $this->itemObj;
	
	$element = $doc->createElement("oc:manage_info");
	
	$elementB = $doc->createElement("oc:queryVal");
	$elementBtext = $doc->createTextNode(urlencode($itemObj->projectName));
	$elementB->appendChild($elementBtext);
	$element->appendChild($elementB);
	
	$elementB = $doc->createElement("oc:rootPath");
	$elementBtext = $doc->createTextNode($itemObj->rootPath);
	$elementB->appendChild($elementBtext);
	$element->appendChild($elementB);
	
	$elementB = $doc->createElement("oc:spaceCount");
	$elementBtext = $doc->createTextNode($itemObj->spaceCount +0);
	$elementB->appendChild($elementBtext);
	$element->appendChild($elementB);
	
	$elementB = $doc->createElement("oc:mediaCount");
	$elementBtext = $doc->createTextNode($itemObj->mediaCount +0);
	$elementB->appendChild($elementBtext);
	$element->appendChild($elementB);
	
	$elementB = $doc->createElement("oc:diaryCount");
	$elementBtext = $doc->createTextNode($itemObj->diaryCount +0);
	$elementB->appendChild($elementBtext);
	$element->appendChild($elementB);
	
	$elementB = $doc->createElement("oc:projGeoPoint");
	$elementBtext = $doc->createTextNode($itemObj->projectLat." ".$itemObj->projectLon);
	$elementB->appendChild($elementBtext);
	$element->appendChild($elementB);
	
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
	
    
	
	if(is_array($itemObj->propertiesObj->notes)){
	    $allNotes = $itemObj->propertiesObj->notes;
	    $projNotes = $allNotes[1];
	}
	else{
	    $projNotes = array();
	}
	
	$projNotes[] = array("type" => "short_des",
			     "validForXML" => $itemObj->projShortDesXMLok,
			     "noteText" => $itemObj->projShortDes
			     );
	$projNotes[] = array("type" => "long_des",
			     "validForXML" => $itemObj->projAbstractXMLok,
			     "noteText" => $itemObj->projAbstract
			     );
	
	if(count($projNotes)>0){
	    $xmlNotes = new dbXML_xmlNotes;
	    $xmlNotes->doc = $doc;
	    $xmlNotes->rootNode = $root;
	    $xmlNotes->notes = $projNotes;
	    $xmlNotes->addNotes();
	}
	
    }//end function
    
    
      
    
    public function addMetadata(){
        
        $doc = $this->doc;
	$root = $this->root;
	$itemObj = $this->itemObj;
    
	$xmlMetadata = new dbXML_xmlMetadata;
	$xmlMetadata->doSocial = false; //don't add social usage node
	$xmlMetadata->metadata = $itemObj->metadataObj;
	$xmlMetadata->label = $itemObj->projectName;
	$xmlMetadata->className = false;
	$xmlMetadata->contributors = $itemObj->linksObj->contributors;
	$xmlMetadata->itemType = "project";
	$xmlMetadata->itemUUID = $itemObj->itemUUID;
	
	$xmlMetadata->doc = $doc;
	$xmlMetadata->rootNode = $root;
	$xmlMetadata->addMetadata();
	
    
    }//end function
    
    
    
    
}  
