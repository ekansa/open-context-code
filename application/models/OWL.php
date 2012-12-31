<?php

/*
Manages, parses, and uses OWL ontologies
*/

class OWL {

public $vocab; //name of the OWL ontology from the URL (slug)
public $vocabLabel; //label for the OWL ontology
public $vocabURI; //URI for the current vocabulary
public $vocabStatus; //URI for the vocabularie's peer review status (if given)
public $concept; //name-identifier (slug) for a concept referenced in the request URL
public $conceptFound; //boolean, if the requested concept identifier was found in the ontology
public $conceptLabel; //label for the active concept, if found.
public $conceptType; //false if not found, or class or property

public $OWLfile; //filename for the OWL ontology
public $xml; //simple xml of the ontology
public $owlArray; //array of the full OWL ontology

public $created; //when was the ontology first created
public $updated; //when was the ontology last updated

public $requestURI; //current request URI
public $db;

const ontologyDirectory = "C:\\GitHub\\oc-ontologies\\vocabularies\\";
const labelAbbrevIRI = "rdfs:label";
const commentAbbrevIRI = "rdfs:comment";
const objectPropRange = "ObjectPropertyRange";
const vocabReviewIRI = "bibo:status";

    function getOntology($vocab, $concept = false){
        
        $db_params = OpenContext_OCConfig::get_db_config();
        $db = new Zend_Db_Adapter_Pdo_Mysql($db_params);
		  $db->getConnection();
		  $this->setUTFconnection($db);    
        
        $this->vocab = false;
		  $this->vocabLabel = false;
		  $this->vocabURI = false;
		  $this->vocabStatus = false;
		  $this->concept = false;
		  $this->conceptLabel = false;
		  $this->conceptType = false;
		  $this->owlArray = false;
		  
		  if($concept != false){
				$this->concept = $concept;
		  }
		  
		  /*
		  if(strstr($vocab, "#")){
				$vocabEx = explode("#", $vocab);
				$vocab = $vocabEx[0];
				$this->hashConcept = $vocabEx[1];
		  }
		  */

        $vocab = $this->security_check($vocab);
        $sql = "SELECT * FROM vocabularies WHERE vocab = '$vocab' LIMIT 1; ";
        $result = $db->fetchAll($sql, 2);
        if($result){
            $this->OWLfile = $result[0]["filename"];
            $this->created = $result[0]["created"];
            $this->updated = $result[0]["updated"];
            $this->vocab = $vocab;
				
            $sFilename = self::ontologyDirectory.$this->OWLfile;
            @$xmlString = $this->loadFile($sFilename);
            if($xmlString != false){
                @$xml = simplexml_load_string($xmlString);
                if($xml != false){
                    $this->xml = $xml;
						  $this->OWLtoArray();
                }
            }
            return true;
        }
        else{
				return false;
		  }
    }
    
	 
	 //construct a PHP array from the OWL ontology, easier to use for displaying
	 function OWLtoArray(){
		  if($this->xml){
				$xml = $this->xml;
				$nameSpaceArray = $this->nameSpaces();
				foreach($nameSpaceArray as $prefix => $uri){
					 @$xml->registerXPathNamespace($prefix, $uri);
				}
				$owlArray = array();
				
				$ontologyAnnotations = array();
				foreach($xml->xpath("/owl:Ontology/@ontologyIRI") as $xpathResult){
					 $this->vocabURI = (string)$xpathResult;
				}
				
				foreach($xml->xpath("/owl:Ontology/owl:Annotation") as $assertion){
					 $nameSpaceArray = $this->nameSpaces();
					 foreach($nameSpaceArray as $prefix => $uri){
						  @$assertion->registerXPathNamespace($prefix, $uri);
					 }
					 $prop = false;
					 $propVal = false;
					 foreach($assertion->xpath("owl:AnnotationProperty/@abbreviatedIRI") as $xpathResult){
						  $prop = (string)$xpathResult;
					 }
					 foreach($assertion->xpath("owl:Literal") as $xpathResult){
						  $propVal = (string)$xpathResult;
					 }
					 
					 if($prop == self::labelAbbrevIRI && $propVal != false){
					   $this->vocabLabel = $propVal;
					 }
					 if($prop == self::vocabReviewIRI && $propVal != false){
					   $this->vocabStatus = $propVal;
					 }
					 
					 if($prop != false && $propVal != false){
						  $ontologyAnnotations[] = array($prop => $propVal);
					 }
				}
				$owlArray["ontology"] = $ontologyAnnotations;
				
				
				$classes = array();
				foreach($xml->xpath("//owl:Declaration/owl:Class/@IRI") as $xpathResult){
					$class = (string)$xpathResult;
               $classes[$class] = array();
				}
				
				           
            $externalParents = array();
            $rootParents = array();
            //search parents not in declared classes
            foreach($xml->xpath("//owl:SubClassOf/owl:Class[2]/@IRI") as $xpathResult){
					$parent = (string)$xpathResult;
               if(!array_key_exists($parent, $classes)){
						  $externalParents[] = $parent;
						  if(!array_key_exists($parent, $classes)){
								$classes[$parent] = array();
						  }
               }
               $parentIsChild = false;
               foreach($xml->xpath("//owl:SubClassOf/owl:Class[1][@IRI = '$parent']") as $xpathResultB){
                    $parentIsChild = true; 
               }
               if(!$parentIsChild){
                    $rootParents[] = $parent;
						  if(!array_key_exists($parent, $classes)){
								$classes[$parent] = array();
						  }
               }
				}
            $owlArray["externalParents"] = $externalParents;
            $owlArray["rootParents"] = $rootParents;
            
            //develop a class hierchy
            $hierarchy = array();
            foreach($rootParents as $parent){
                $hierarchy[$parent] = $this->childClasses($parent, $xml);
            }
            $owlArray["hierachy"] = $hierarchy;
            
				$properties = array();
				foreach($xml->xpath("//owl:Declaration/owl:ObjectProperty/@IRI") as $xpathResult){
					$property = (string)$xpathResult;
               $properties[$property] = array();
				}
				
				$propertyAnnotations = array();
            foreach($properties as $propKey => $propArray){
                foreach($xml->xpath("//owl:AnnotationAssertion[owl:IRI[text() = '$propKey']]") as $assertionIRI){
                    $nameSpaceArray = $this->nameSpaces();
                    foreach($nameSpaceArray as $prefix => $uri){
                        @$assertionIRI->registerXPathNamespace($prefix, $uri);
                    }
                    foreach($assertionIRI->xpath("owl:AnnotationProperty/@abbreviatedIRI") as $xpathResult){
                        $prop = (string)$xpathResult;
                    }
                    foreach($assertionIRI->xpath("owl:Literal") as $xpathResult){
                        $propVal = (string)$xpathResult;
                    }
                    $propertyAnnotations[$propKey][] = array($prop => $propVal);
                }
					 
					 foreach($xml->xpath("//owl:ObjectPropertyRange[owl:ObjectProperty[@IRI = '$propKey']]") as $range){
						  $nameSpaceArray = $this->nameSpaces();
                    foreach($nameSpaceArray as $prefix => $uri){
                        @$range->registerXPathNamespace($prefix, $uri);
                    }
						  foreach($range->xpath("owl:Class/@IRI") as $xpathResult){
                        $rangeIRI = (string)$xpathResult;
								
								if(!array_key_exists($rangeIRI, $classes)){
									 $classes[$rangeIRI] = array();
								}
								
								$propertyAnnotations[$propKey][] = array(self::objectPropRange => $rangeIRI);
                    }
					 }
            }
				
				$classAnnotations = array();
            foreach($classes as $classKey => $classArray){
                foreach($xml->xpath("//owl:AnnotationAssertion[owl:IRI[text() = '$classKey']]") as $assertionIRI){
                    $nameSpaceArray = $this->nameSpaces();
                    foreach($nameSpaceArray as $prefix => $uri){
                        @$assertionIRI->registerXPathNamespace($prefix, $uri);
                    }
                    foreach($assertionIRI->xpath("owl:AnnotationProperty/@abbreviatedIRI") as $xpathResult){
                        $prop = (string)$xpathResult;
                    }
                    foreach($assertionIRI->xpath("owl:Literal") as $xpathResult){
                        $propVal = (string)$xpathResult;
                    }
                    $classAnnotations[$classKey][] = array($prop => $propVal);
                }
            }
				
				$owlArray["classes"] = $classAnnotations;
				$owlArray["properties"] = $propertyAnnotations;
            
				if($this->concept){
					 //checks to see if the requested concept actually exists in the ontology
					 $this->conceptFound = false;
					 $slashConcept = "/".$this->concept;
					 if(array_key_exists($this->concept, $classAnnotations)){
						  $this->conceptFound = true;
						  $this->conceptType = "class";
						  $this->activeConceptLabel($this->concept, $classAnnotations);
					 }
					 elseif(array_key_exists($this->concept, $propertyAnnotations)){
						  $this->conceptFound = true;
						  $this->conceptType = "property";
						  $this->activeConceptLabel($this->concept, $propertyAnnotations);
					 }
					 elseif(array_key_exists($slashConcept, $classAnnotations)){
						  $this->concept = $slashConcept;
						  $this->conceptFound = true;
						  $this->conceptType = "class";
						  $this->activeConceptLabel($slashConcept, $classAnnotations);
					 }
					 elseif(array_key_exists($slashConcept, $propertyAnnotations)){
						  $this->concept = $slashConcept;
						  $this->conceptFound = true;
						  $this->conceptType = "property";
						  $this->activeConceptLabel($slashConcept, $propertyAnnotations);
					 }
					 
					 if(!$this->vocabURI){
						  //fallback way of getting the vocabulary URI
						  $this->getVocabularyURI();
					 }
				}
				
				$this->owlArray = $owlArray;
		  }
	 }
	 
	 
	 function getVocabularyURI(){
		  $conceptLen = strlen($this->concept);
		  $requestLen = strlen($this->requestURI);
		  $vocabularyURI = substr($this->requestURI, 0, ($requestLen - $conceptLen));
		  $this->vocabURI = $vocabularyURI;
	 }
	 
	 
	 //recusive function to traverse a class hierarchy in owl
    function childClasses($parent, $xml){
        $output = false;
        $children = array();
        foreach($xml->xpath("//owl:SubClassOf/owl:Class[2][@IRI = '$parent']") as $pResult){
            
            $nameSpaceArray = $this->nameSpaces();
				foreach($nameSpaceArray as $prefix => $uri){
					 @$pResult->registerXPathNamespace($prefix, $uri);
				}
            
            foreach($pResult->xpath("preceding-sibling::owl:Class/@IRI") as $childResult){
                $child = (string)$childResult;
                $childChildren = $this->childClasses($child, $xml);
                if(is_array($childChildren)){
                    $children[$child] = $childChildren;
                }
                else{
                    $children[$child] = null;
                }
            }
        }
        
        if(count($children)>0){
            $output = $children;
        }
        
        return $output;
    }
    
    //get the label for the active concept
	 function activeConceptLabel($actConcept, $conceptArray){
		  if(!$this->conceptLabel){
				$actConArray = $conceptArray[$actConcept];
				foreach($actConArray as $annoationArray){
					 if(array_key_exists(self::labelAbbrevIRI, $annoationArray)){
						  $this->conceptLabel = $annoationArray[self::labelAbbrevIRI];
					 }
				}
		  }
	 }
	 
	 
	 function IRIgetLabel($actIRI, $actArray){
		  $output = false;
		  if(array_key_exists($actIRI, $actArray)){
				$actConArray =  $actArray[$actIRI];
				foreach($actConArray as $annoationArray){
					 if(array_key_exists(self::labelAbbrevIRI, $annoationArray)){
						  $output = $annoationArray[self::labelAbbrevIRI];
					 }
				}
		  }
		  return $output;
	 }
	 
	 function IRIgetComment($actIRI, $actArray){
		  $output = false;
		  if(array_key_exists($actIRI, $actArray)){
				$actConArray =  $actArray[$actIRI];
				foreach($actConArray as $annoationArray){
					 if(array_key_exists(self::commentAbbrevIRI, $annoationArray)){
						  $output = $annoationArray[self::commentAbbrevIRI];
					 }
				}
		  }
		  return $output;
	 }
	 
	 function IRIgetObjectPropRange($actIRI){
		  $output = false;
		  $owlArray = $this->owlArray;
		  if(array_key_exists($actIRI, $owlArray["properties"])){
				$actConArray =  $owlArray["properties"][$actIRI];
				foreach($actConArray as $annoationArray){
					 if(array_key_exists(self::objectPropRange, $annoationArray)){
						  $output = $annoationArray[self::objectPropRange];
					 }
				}
		  }
		  return $output;
	 }
	 
	 //convert an IRI to a link
	 function IRItoURL($actIRI){
		  if(stristr($actIRI, "http://")){
				return $actIRI;
		  }
		  else{
				return $this->vocabURI.$actIRI;
		  }
	 }
    
	 
	 function getStatusLevel(){
		  $output = 0;
		  if($this->vocabStatus != false){
				$output = 2;
				if(strstr($this->vocabStatus, "http://opencontext.org/about/publishing/#edit-stars-")){
					 $output = str_replace("http://opencontext.org/about/publishing/#edit-stars-", "", $this->vocabStatus);
				}
		  }
		  return $output;
	 }
	 
	 function reviewStarHTML(){
		  $output = "Not reviewed";
		  $level = $this->getStatusLevel();
		  if($level == 1){
				$output = "&#9733;&#9734;&#9734;&#9734;&#9734;";
		  }
		  elseif($level == 2){
				$output = "&#9733;&#9733;&#9734;&#9734;&#9734;";
		  }
		  elseif($level == 3){
				$output = "&#9733;&#9733;&#9733;&#9734;&#9734;";
		  }
		  elseif($level == 4){
				$output = "&#9733;&#9733;&#9733;&#9733;&#9734;";
		  }
		  elseif($level == 5){
				$output = "&#9733;&#9733;&#9733;&#9733;&#9733;";
		  }
		  return $output;
	 }
	 
	 function reviewLabelHTML(){
		  $output = "";
		  $level = $this->getStatusLevel();
		  if($level == 1){
				$output = "Demonstration";
		  }
		  elseif($level == 2){
				$output = "Minimal editorial acceptance";
		  }
		  elseif($level == 3){
				$output = "Managing editor reviewed";
		  }
		  elseif($level == 4){
				$output = "Editorial board reviewed";
		  }
		  elseif($level == 5){
				$output = "Peer-reviewed";
		  }
		  return $output;
	 }
	 
	 
	 function nameSpaces(){
		  $nameSpaceArray = array(
		  "owl"=> "http://www.w3.org/2002/07/owl#",
		  "base"=> ("http://opencontext.org/vocabularies/".$this->vocab),
		  "rdfs"=> "http://www.w3.org/2000/01/rdf-schema#",
		  "xsd"=> "http://www.w3.org/2001/XMLSchema#",
		  "rdf"=> "http://www.w3.org/1999/02/22-rdf-syntax-ns#",
		  "xml"=> "http://www.w3.org/XML/1998/namespace");
	
		  return $nameSpaceArray;
    }
	 
	 
	 
	 
    function loadFile($sFilename, $sCharset = 'UTF-8'){
        
        if (!file_exists($sFilename)){
            return false;
        }
        $rHandle = fopen($sFilename, 'r');
        if (!$rHandle){
            return false;
        }
        $sData = '';
        while(!feof($rHandle)){
            $sData .= fread($rHandle, filesize($sFilename));
        }
        fclose($rHandle);
        
        if ($sEncoding = mb_detect_encoding($sData, 'auto', true) != $sCharset){
            $sData = mb_convert_encoding($sData, $sCharset, $sEncoding);
        }
        return $sData;
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
    
    
    private function setUTFconnection($db){
	    $sql = "SET collation_connection = utf8_unicode_ci;";
	    $db->query($sql, 2);
	    $sql = "SET NAMES utf8;";
	    $db->query($sql, 2);
    } 
    

	 
	 
	 
	 
}//end class

?>
