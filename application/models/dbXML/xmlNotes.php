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
		
		$note["validForXML"] = false;
		if($note["validForXML"]){
		    $elementC->setAttribute("type", "xhtml");
		}
		
		//$note["validForXML"] = false;
		if($note["validForXML"]){
		    
		    //$noteXML = '<div>'.chr(13);
		    
		    $noteXML = str_replace("<", "<xhtml:", $note["noteText"]);
		    $noteXML = str_replace("</", "</xhtml:", $noteXML);
		    //$noteXML .= '</div>'.chr(13);
		    
		    $elementD = $doc->createElement("xhtml:div");
		    $elementD->setAttribute("xmlns:xhtml", "http://www.w3.org/1999/xhtml");
		    $contentFragment = $doc->createDocumentFragment();
		    $contentFragment->appendXML($noteXML);  // add note xml string
		    $elementD->appendChild($contentFragment);
		    $elementC->appendChild($elementD);
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
