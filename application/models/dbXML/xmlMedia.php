<?php

class dbXML_xmlMedia  {
    
    public $itemObj;
    public $doc;
    public $root;
    
    const NSarchaeoML = "http://ochre.lib.uchicago.edu/schema/Resource/Resource.xsd";
    const NSocItem = "http://opencontext.org/schema/resource_schema_v1.xsd";
    const NSdc = "http://purl.org/dc/elements/1.1/";
    const NSgml = "http://www.opengis.net/gml";
    const NSxmhtml = "";
    
    const classIconRoot = "http://opencontext.org/database/ui_images/med_oc_icons/";
    const spaceRootURI = "http://opencontext.org/subjects/";
    const mediaRootURI = "http://opencontext.org/media/";
    
    public function initialize(){
	$itemObj = $this->itemObj;
	$doc = new DOMDocument("1.0", "utf-8");
	$doc->formatOutput = true;
	$root = $doc->createElement("arch:resource");
	$root->setAttribute("xmlns:arch", self::NSarchaeoML);
	$root->setAttribute("xmlns:oc", self::NSocItem);
	$root->setAttribute("xmlns:dc", self::NSdc);
	$root->setAttribute("xmlns:gml", self::NSgml);
	$root->setAttribute("UUID", $itemObj->itemUUID);
	$root->setAttribute("ownedBy", $itemObj->projectUUID);
	$root->setAttribute("type", $itemObj->archaeoMLtype);
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
	    $obsNotes = $allNotes[1];
	    if(count($obsNotes)>0){
		$xmlNotes = new dbXML_xmlNotes;
		$xmlNotes->doc = $doc;
		$xmlNotes->rootNode = $root;
		$xmlNotes->notes = $obsNotes;
		$xmlNotes->addNotes();
	    }
	}
	
    }//end function
    
    
    public function addFileInfo(){
	
	$doc = $this->doc;
	$root = $this->root;
	$itemObj = $this->itemObj;
	
	$element = $doc->createElement("arch:content");
	$elementB = $doc->createElement("arch:externalFileInfo");
	
	$elementC = $doc->createElement("arch:fileFormat");
	$elementCtext = $doc->createTextNode($itemObj->MIMEtype);
	$elementC->appendChild($elementCtext);
	$elementB->appendChild($elementC);
	
	$elementC = $doc->createElement("oc:FileInfo");
	if($itemObj->archaeoMLtype == "image"){
	    $elementD = $doc->createElement("oc:ImageSize");
	    $elementDtext = $doc->createTextNode($itemObj->imageSize);
	    $elementD->appendChild($elementDtext);
	    $elementC->appendChild($elementD);
	}
	
	$elementD = $doc->createElement("oc:FileSize");
	$elementD->setAttribute("ReadSize", $itemObj->fileSizeHuman);
	$elementDtext = $doc->createTextNode($itemObj->fileSize);
	$elementD->appendChild($elementDtext);
	$elementC->appendChild($elementD);
	
	$elementD = $doc->createElement("oc:FileName");
	$elementDtext = $doc->createTextNode($itemObj->fileName);
	$elementD->appendChild($elementDtext);
	$elementC->appendChild($elementD);
	
	$elementB->appendChild($elementC); //add the fileinfo
	
	$elementC = $doc->createElement("arch:resourceURI");
	$elementCtext = $doc->createTextNode($itemObj->fullURI);
	$elementC->appendChild($elementCtext);
	$elementB->appendChild($elementC);
	
	$elementC = $doc->createElement("arch:previewURI");
	$elementCtext = $doc->createTextNode($itemObj->previewURI);
	$elementC->appendChild($elementCtext);
	$elementB->appendChild($elementC);
	
	$elementC = $doc->createElement("arch:thumbnailURI");
	$elementCtext = $doc->createTextNode($itemObj->thumbURI);
	$elementC->appendChild($elementCtext);
	$elementB->appendChild($elementC);
	
	
	$element->appendChild($elementB);
	$root->appendChild($element);
	
    }
    
    
    public function addMetadata(){
        
        $doc = $this->doc;
	$root = $this->root;
	$itemObj = $this->itemObj;
    
	$xmlMetadata = new dbXML_xmlMetadata;
	$xmlMetadata->metadata = $itemObj->metadataObj;
	$xmlMetadata->label = $itemObj->label;
	$xmlMetadata->className = false;
	$xmlMetadata->contributors = $itemObj->linksObj->contributors;
	$xmlMetadata->itemType = "media";
	$xmlMetadata->itemUUID = $itemObj->itemUUID;
	$xmlMetadata->geoData = array("geoLat" => $itemObj->geoLat,
				      "geoLon" => $itemObj->geoLon,
				      "geoGML" => $itemObj->geoGML,
				      "geoKML" => $itemObj->geoKML,
				      "geoSource" => $itemObj->geoSource,
				      "geoSourceName" => $itemObj->geoSourceName
				      );
	$xmlMetadata->chronoData = $itemObj->chronoArray;
	
	$xmlMetadata->doc = $doc;
	$xmlMetadata->rootNode = $root;
	$xmlMetadata->addMetadata();
	
    
    }//end function
    
    
    
    
}  
