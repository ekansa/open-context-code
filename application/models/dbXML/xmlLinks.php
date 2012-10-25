<?php

/*
This is used for constructing property XML used in spatial units, projects, documents, media items, etc.
*/
class dbXML_xmlLinks  {
    
    public $links;
    public $spaceLinks;
    public $mediaLinks;
    public $personLinks;
    public $documentLinks;
    
    public $doc;
    public $rootNode;
  
    const spaceRootURI = "http://opencontext.org/subjects/";
    const mediaRootURI = "http://opencontext.org/media/";
    const personRootURI = "http://opencontext.org/persons/";
    const projRootURI = "http://opencontext.org/projects/";
    const docsRootURI = "http://opencontext.org/documents/";
    
    public function addLinks(){
	$links = $this->links;
	$doc = $this->doc;
	$rootNode = $this->rootNode;
	
	if(is_array($this->links)){
	    $element = $doc->createElement("arch:links");
	    foreach($this->links as $keyUUID => $link){
		$elementB = $doc->createElement("arch:docID");
		$elementB->setAttribute("type", $link["type"]);
		$elementB->setAttribute("info", $link["linkType"]);
		$elementBtext = $doc->createTextNode($link["linkedUUID"]);
		$elementB->appendChild($elementBtext); //add the link
		$element->appendChild($elementB); //add the link
	    }
	    
	    
	    //space links, the most elaborate
	    if(is_array($this->spaceLinks)){
		$elementB = $doc->createElement("oc:space_links");
		foreach($this->spaceLinks as $keyUUID => $space){
		    $elementC = $doc->createElement("oc:link");
		    $elementC->setAttribute("href", self::spaceRootURI.$space["linkedUUID"]);
		    
		    $elementD = $doc->createElement("oc:name");
		    $elementDtext = $doc->createTextNode($space["linkedName"]);
		    $elementD->appendChild($elementDtext);
		    $elementC->appendChild($elementD); //add a name
		    
		    $elementD = $doc->createElement("oc:id");
		    $elementDtext = $doc->createTextNode($space["linkedUUID"]);
		    $elementD->appendChild($elementDtext);
		    $elementC->appendChild($elementD); //add a id
		    
		    $elementD = $doc->createElement("oc:relation");
		    $elementDtext = $doc->createTextNode($space["linkType"]);
		    $elementD->appendChild($elementDtext);
		    $elementC->appendChild($elementD); //add a relation
		    
		    $elementD = $doc->createElement("oc:item_class");
		    $elementE = $doc->createElement("oc:name");
		    $elementEtext = $doc->createTextNode($space["className"]);
		    $elementE->appendChild($elementEtext);
		    $elementD->appendChild($elementE); //add a className
		    
		    $elementE = $doc->createElement("oc:iconURI");
		    $elementEtext = $doc->createTextNode($space["smallClassIcon"]);
		    $elementE->appendChild($elementEtext);
		    $elementD->appendChild($elementE); //add a className
	
		    $elementC->appendChild($elementD); //add class elements
		    
		    if(isset($space["containment"])){
			$xmlContain = new dbXML_xmlContext;
			$xmlContain->containment = $space["containment"];
			$xmlContain->doc = $doc;
			$xmlContain->rootNode = $elementC;
			$xmlContain->addContainment();
		    }//case with containment items
		    
		    $elementB->appendChild($elementC); //add a link
		}
		$element->appendChild($elementB); //add space links
	    }//end media links
	    
	    
	    //media links
	    if(is_array($this->mediaLinks)){
		$elementB = $doc->createElement("oc:media_links");
		foreach($this->mediaLinks as $keyUUID => $media){
		    $elementC = $doc->createElement("oc:link");
		    $elementC->setAttribute("href", self::mediaRootURI.$media["linkedUUID"]);
		    
		    $elementD = $doc->createElement("oc:name");
		    $elementDtext = $doc->createTextNode($media["linkedName"]);
		    $elementD->appendChild($elementDtext);
		    $elementC->appendChild($elementD); //add a name
		    
		    $elementD = $doc->createElement("oc:id");
		    $elementDtext = $doc->createTextNode($media["linkedUUID"]);
		    $elementD->appendChild($elementDtext);
		    $elementC->appendChild($elementD); //add a id
		    
		    $elementD = $doc->createElement("oc:relation");
		    $elementDtext = $doc->createTextNode($media["linkType"]);
		    $elementD->appendChild($elementDtext);
		    $elementC->appendChild($elementD); //add a relation
		    
		    $elementD = $doc->createElement("oc:type");
		    $elementDtext = $doc->createTextNode($media["archaeoMLtype"]);
		    $elementD->appendChild($elementDtext);
		    $elementC->appendChild($elementD); //add a type
		    
		    $elementD = $doc->createElement("oc:thumbnailURI");
		    $elementDtext = $doc->createTextNode($media["thumbURI"]);
		    $elementD->appendChild($elementDtext);
		    $elementC->appendChild($elementD); //add a type
		    
		    if(isset($media["descriptor"])){
			$elementD = $doc->createElement("oc:descriptor");
			$elementDtext = $doc->createTextNode($media["descriptor"]);
			$elementD->appendChild($elementDtext);
			$elementC->appendChild($elementD); //add a type
		    }
		    
		    
		    $elementB->appendChild($elementC); //add a link
		}
		$element->appendChild($elementB); //add person links
	    }//end media links
	    
	    if(is_array($this->documentLinks)){
		$elementB = $doc->createElement("oc:diary_links");
		foreach($this->documentLinks as $keyUUID => $document){
		    $elementC = $doc->createElement("oc:link");
		    $elementC->setAttribute("href", self::docsRootURI.$document["linkedUUID"]);
		    
		    $elementD = $doc->createElement("oc:name");
		    $elementDtext = $doc->createTextNode($document["linkedName"]);
		    $elementD->appendChild($elementDtext);
		    $elementC->appendChild($elementD); //add a name
		    
		    $elementD = $doc->createElement("oc:id");
		    $elementDtext = $doc->createTextNode($document["linkedUUID"]);
		    $elementD->appendChild($elementDtext);
		    $elementC->appendChild($elementD); //add a id
		    
		    $elementD = $doc->createElement("oc:relation");
		    $elementDtext = $doc->createTextNode($document["linkType"]);
		    $elementD->appendChild($elementDtext);
		    $elementC->appendChild($elementD); //add a relation
		    
		    $elementB->appendChild($elementC); //add a link
		}
		$element->appendChild($elementB); //add document links
	    }//end document/diary links
	    
	    if(is_array($this->personLinks)){
		$elementB = $doc->createElement("oc:person_links");
		foreach($this->personLinks as $keyUUID => $person){
		    $elementC = $doc->createElement("oc:link");
		    $elementC->setAttribute("href", self::personRootURI.$person["linkedUUID"]);
		    if($person["cite"] != false){
			$elementC->setAttribute("cite", $person["cite"] );
		    }
		    
		    $elementD = $doc->createElement("oc:name");
		    $elementDtext = $doc->createTextNode($person["linkedName"]);
		    $elementD->appendChild($elementDtext);
		    $elementC->appendChild($elementD); //add a name
		    
		    $elementD = $doc->createElement("oc:id");
		    $elementDtext = $doc->createTextNode($person["linkedUUID"]);
		    $elementD->appendChild($elementDtext);
		    $elementC->appendChild($elementD); //add a id
		    
		    $elementD = $doc->createElement("oc:relation");
		    $elementDtext = $doc->createTextNode($person["linkType"]);
		    $elementD->appendChild($elementDtext);
		    $elementC->appendChild($elementD); //add a name
		    
		    $elementB->appendChild($elementC); //add a link
		}
		$element->appendChild($elementB); //add person links
	    }//end person links
	    
	    $rootNode->appendChild($element);
	}//end case with links
    
	$this->rootNode = $rootNode;
    }//end add links function
    
    
    
    
}  
