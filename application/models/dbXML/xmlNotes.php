<?php

/*
This is used for constructing notes XML used in spatial units, projects, documents, media items, etc.
*/
class dbXML_xmlNotes  {
    
    public $notes;
    public $doc;
    public $rootNode;
    
    
    public function addNotes(){
	
	$notes = $this->notes;
	$doc = $this->doc;
	$rootNode = $this->rootNode;
	
	if(is_array($notes)){
	    $element = $doc->createElement("arch:notes");    
	    foreach($notes as $note){
		$elementB = $doc->createElement("arch:note");
		if(array_key_exists("type",  $note)){
		    $elementB->setAttribute("type", $note["type"]);
		}
		$elementC = $doc->createElement("arch:string");
		
		$validXHTML = false;
		if(isset($note["validForXML"])){
		  if($note["validForXML"]){
		    $elementC->setAttribute("type", "xhtml");
			 $validXHTML = true;
		  }
		}

		if($validXHTML){  
		  $elementC->setAttribute("type", "xhtml");
		  $elementCC = $doc->createElement("div");
		  $elementCC->setAttribute("xmlns", "http://www.w3.org/1999/xhtml");
		  $contentFragment = $doc->createDocumentFragment();
		  $contentFragment->appendXML("<div>".$note["noteText"]."</div>");  // add the XHTML fragment
		  $elementCC->appendChild($contentFragment);
		  $elementC->appendChild($elementCC);
		}
		else{
		    $elementCtext = $doc->createTextNode($note["noteText"]);
		    $elementC->appendChild($elementCtext);
		}
		
		$elementB->appendChild($elementC);
		$element->appendChild($elementB);
	    }
	    $rootNode->appendChild($element);
	    $this->rootNode = $rootNode;
	}
	
    }
    
    
}  
