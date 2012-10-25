<?php

/*
This is used for constructing spatial context / containment XML
*/
class dbXML_xmlContext  {
    
    public $containment;
    public $doc;
    public $rootNode;
    
    const spaceRootURI = "http://opencontext.org/subjects/";
    const classIconRoot = "http://opencontext.org/database/ui_images/med_oc_icons/";
    
    public function addContainment(){
	
	$doc = $this->doc;
	$rootNode = $this->rootNode;
	
	if(is_array($this->containment)){
	    $element = $doc->createElement("oc:context");
	    foreach($this->containment as $treeKey => $containArray){
		$elementB = $doc->createElement("oc:tree");
		$elementB->setAttribute("id", $treeKey);
		$level = count($containArray);
		foreach($containArray as $containItem){
		    $elementC = $doc->createElement("oc:parent");
		    $elementC->setAttribute("href", self::spaceRootURI.$containItem["itemUUID"]);
		    
		    $elementD = $doc->createElement("oc:name");
		    $elementDtext = $doc->createTextNode($containItem["label"]);
		    $elementD->appendChild($elementDtext); 
		    $elementC->appendChild($elementD); //add name
		    
		    $elementD = $doc->createElement("oc:id");
		    $elementDtext = $doc->createTextNode($containItem["itemUUID"]);
		    $elementD->appendChild($elementDtext); 
		    $elementC->appendChild($elementD); //add ID
		    
		    $elementD = $doc->createElement("oc:level");
		    $elementDtext = $doc->createTextNode($level);
		    $elementD->appendChild($elementDtext); 
		    $elementC->appendChild($elementD); //add level
		    
		    $elementD = $doc->createElement("oc:item_class");
		    $elementE = $doc->createElement("oc:name");
		    $elementEtext = $doc->createTextNode($containItem["className"]);
		    $elementE->appendChild($elementEtext);
		    $elementD->appendChild($elementE); //add a className
		    
		    $elementE = $doc->createElement("oc:iconURI");
		    $elementEtext = $doc->createTextNode(self::classIconRoot.$containItem["smallClassIcon"]);
		    $elementE->appendChild($elementEtext);
		    $elementD->appendChild($elementE); //add a className
		    
		    $elementC->appendChild($elementD); //add class
		    
		    $elementB->appendChild($elementC); //add parent item
		    $level = $level -1;
		}//end loop through containment items
		$element->appendChild($elementB);
	    }//end loop
	    $rootNode->appendChild($element); 
	}//end case with context items
	
	$this->rootNode = $rootNode;
    }//end function
    
    
}  
