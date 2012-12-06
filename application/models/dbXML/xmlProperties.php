<?php

/*
This is used for constructing property XML used in spatial units, projects, documents, media items, etc.
*/
class dbXML_xmlProperties  {
    
    public $properties;
    public $doc;
    public $rootNode;
    
    const propRootURI = "http://opencontext.org/properties/";
    
    public function addProperties(){
	$properties = $this->properties;
	$doc = $this->doc;
	$rootNode = $this->rootNode;
	
	$element = $doc->createElement("arch:properties");
	foreach($properties as $keyUUID => $property){
	    if(isset($property["varUUID"])){
		$elementB = $doc->createElement("arch:property");
		
		if(isset($property["hideLink"])){
		    if($property["hideLink"]){
			$elementB->setAttribute("hideLink", "true");
		    }
		}
		
		//variable ID
		$elementC = $doc->createElement("arch:variableID");
		$elementCtext = $doc->createTextNode($property["varUUID"]);
		$elementC->appendChild($elementCtext); 
		$elementB->appendChild($elementC);
		
		//value ID
		if(!$property["valueNum"] && $property["varType"] != "integer" && $property["varType"] != "decimal"){
		    $elementC = $doc->createElement("arch:valueID");
		    $elementCtext = $doc->createTextNode($property["valUUID"]);
		    $elementC->appendChild($elementCtext); 
		    $elementB->appendChild($elementC);
		}
		elseif($property["varType"] == "integer"){
		    $elementC = $doc->createElement("arch:integer");
		    $elementCtext = $doc->createTextNode($property["valueNum"]);
		    $elementC->appendChild($elementCtext); 
		    $elementB->appendChild($elementC);
		}
		elseif($property["varType"] == "decimal"){
		    $elementC = $doc->createElement("arch:decimal");
		    $elementCtext = $doc->createTextNode($property["valueNum"]);
		    $elementC->appendChild($elementCtext); 
		    $elementB->appendChild($elementC);
		}
		elseif($property["valueDate"] != false){
		    $elementC = $doc->createElement("arch:date");
		    $elementCtext = $doc->createTextNode($property["valueDate"]);
		    $elementC->appendChild($elementCtext); 
		    $elementB->appendChild($elementC);
		}
		
		if(is_array($property["varUnitsData"])){
			//add standard units of measurement linking data
			$elementC->setAttribute("href", $property["varUnitsData"]["linkedURI"]);
			$elementC->setAttribute("name", $property["varUnitsData"]["linkedLabel"]);
			$elementC->setAttribute("abrv", $property["varUnitsData"]["linkedAbrv"]);
		}
		
		
		//propID
		$elementC = $doc->createElement("oc:propid");
		$elementC->setAttribute("href", self::propRootURI.$property["propertyUUID"]);
		$elementCtext = $doc->createTextNode($property["propertyUUID"]);
		$elementC->appendChild($elementCtext); 
		$elementB->appendChild($elementC);
		
		//variable name / label
		$elementC = $doc->createElement("oc:var_label");
		$elementC->setAttribute("type", $property["varType"]);
		$elementCtext = $doc->createTextNode($property["varLabel"]);
		$elementC->appendChild($elementCtext); 
		$elementB->appendChild($elementC);
		
		//display value
		$elementC = $doc->createElement("oc:show_val");
		$elementCtext = $doc->createTextNode($property["showVal"]);
		$elementC->appendChild($elementCtext); 
		$elementB->appendChild($elementC);
		
		
		if(is_array($property["varLinkedData"]) || is_array($property["propLinkedData"]) ){
		    $elementC = $doc->createElement("oc:linkedData");
		    if(is_array($property["varLinkedData"])){
			$elementD = $doc->createElement("oc:relationLink");
			$elementD->setAttribute("localType", "variable");
			$elementD->setAttribute("localID", $property["varUUID"]);
			$elementD->setAttribute("href", $property["varLinkedData"]["linkedURI"]);
			$elementE = $doc->createElement("oc:vocabulary");
			$elementE->setAttribute("href", $property["varLinkedData"]["vocabURI"]);
			$elementEtext = $doc->createTextNode($property["varLinkedData"]["vocabulary"]);
			$elementE->appendChild($elementEtext);
			$elementD->appendChild($elementE);
			$elementE = $doc->createElement("oc:label");
			$elementEtext = $doc->createTextNode($property["varLinkedData"]["linkedLabel"]);
			$elementE->appendChild($elementEtext);
			$elementD->appendChild($elementE);
			
			if(is_array($property["propLinkedData"])){
				foreach($property["propLinkedData"] as $propLinkedData){
					 $elementE = $doc->createElement("oc:targetLink");
					 $elementE->setAttribute("localType", "property");
					 $elementE->setAttribute("localID", $property["propertyUUID"]);
					 $elementE->setAttribute("localURI", self::propRootURI.$property["propertyUUID"]);
					 $elementE->setAttribute("href", $propLinkedData["linkedURI"]);
					 $elementF = $doc->createElement("oc:vocabulary");
					 $elementF->setAttribute("href", $propLinkedData["vocabURI"]);
					 $elementFtext = $doc->createTextNode($propLinkedData["vocabulary"]);
					 $elementF->appendChild($elementFtext);
					 $elementE->appendChild($elementF);
					 $elementF = $doc->createElement("oc:label");
					 $elementFtext = $doc->createTextNode($propLinkedData["linkedLabel"]);
					 $elementF->appendChild($elementFtext);
					 $elementE->appendChild($elementF);
					 $elementD->appendChild($elementE);
				}
			}
			
			$elementC->appendChild($elementD);
		    }
		    $elementB->appendChild($elementC);
		}
		
		
		
		$element->appendChild($elementB); //add the property
	    }
	}
	$rootNode->appendChild($element); 
	$this->rootNode = $rootNode;
    }
    
    
}  
