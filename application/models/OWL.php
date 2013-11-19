<?php

/*
Manages, parses, and uses OWL ontologies
*/

class OWL {

public $vocab; //name of the OWL ontology from the URL (slug)
public $vocabLabel; //label for the OWL ontology
public $vocabURI; //URI for the current vocabulary
public $vocabReposity; //URI for the repository (Github) version control
public $vocabCommits; //URI for commits / updates to the vocabulary
public $vocabStatus; //URI for the vocabularie's peer review status (if given)
public $vocabLicense; //uri for the vocabularies license
public $vocabAttribution; //string for who gets attribution for the vocabulary
public $concept; //name-identifier (slug) for a concept referenced in the request URL
public $conceptFound; //boolean, if the requested concept identifier was found in the ontology
public $conceptLabel; //label for the active concept, if found.
public $conceptType; //false if not found, or class or property

public $OWLfile; //filename for the OWL ontology
public $storedHash; //database stored hash of the vocabulary file, used to check on versioning / updates
public $xml; //simple xml of the ontology
public $owlArray; //array of the full OWL ontology

public $created; //when was the ontology first created
public $updated; //when was the ontology last updated

public $vocabError = false;
public $requestURI; //current request URI
public $db;

const baseLocalRepositoryURI = "/vocabs/";
const BaseRawRepositoryHome = "http://raw.github.com/ekansa/oc-ontologies/master/vocabularies/";
const BaseRepositoryHome = "http://github.com/ekansa/oc-ontologies/blob/master/vocabularies/";
const BaseVocabCommitsFeed = "http://github.com/ekansa/oc-ontologies/commits/master/vocabularies/";
const localOntologyDirectory = "C:\\GitHub\\oc-ontologies\\vocabularies\\";
const labelAbbrevIRI = "rdfs:label";
const commentAbbrevIRI = "rdfs:comment";
const definedByAbbrevIRI = "rdfs:isDefinedBy";
const objectPropRange = "ObjectPropertyRange";
const vocabReviewIRI = "bibo:status";
const attributionIRI = "cc:attributionName";
const licenseIRI = "xhv:license";

    function getOntology($vocab, $parseOwl = true, $concept = false){
        
        $db_params = OpenContext_OCConfig::get_db_config();
        $db = new Zend_Db_Adapter_Pdo_Mysql($db_params);
		  $db->getConnection();
		  $this->setUTFconnection($db);    
        
        $this->vocab = false;
		  $this->vocabLabel = false;
		  $this->vocabURI = false;
		  $this->vocabStatus = false;
		  $this->vocabAttribution = false;
		  $this->vocabLicense = false;
		  $this->vocabReposity = false;
		  $this->vocabCommits = false;
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
				$this->storedHash = $result[0]["filehash"];
            $this->vocab = $vocab;
				$this->vocabCommits = self::BaseVocabCommitsFeed.$this->OWLfile.".atom";
				$this->vocabReposity = self::BaseRepositoryHome.$this->OWLfile;
				
            $xmlString = $this->getOwlFile(false);
            if($xmlString != false){
					 if($parseOwl){
						  @$xml = simplexml_load_string($xmlString);
						  if($xml != false){
								$this->checkUpdateHash($vocab, $xmlString);
								$this->xml = $xml;
								$this->OWLtoArray();
								return true;
						  }
						  else{
								$this->vocabError = true;
								return false;
						  }
					 }
					 else{
						  return $xmlString;
					 }
            }
            
        }
        else{
				return false;
		  }
    }
    
	 
	 function checkUpdateHash($vocab, $xmlString){
		  if(!$this->db){
				$db_params = OpenContext_OCConfig::get_db_config();
				$db = new Zend_Db_Adapter_Pdo_Mysql($db_params);
				$db->getConnection();
				$this->setUTFconnection($db);    
		  }
		  else{
				$db = $this->db;
		  }
		  $newHash = sha1($xmlString);
		  if($this->storedHash != $newHash){
				$where = "vocab = '$vocab' ";
				$data = array("filehash" => $newHash);
				@$feed = new Zend_Feed_Atom($this->vocabCommits); //get the feed for the latest commits to the vocabulary
				if($feed){
					 foreach ($feed as $entry) {
						  $updated = $entry->updated();
						  $data["updated"] = date('Y-m-d H:i:s', strtotime($updated));
						  $db->update("vocabularies", $data, $where);
						  break;
					 }
				}
				else{
					 $data["updated"] = date('Y-m-d H:i:s');
					 $db->update("vocabularies", $data, $where);
				}
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
					 $owlArray["ontologyIRI"] = $this->vocabURI;
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
					 elseif($prop == self::vocabReviewIRI && $propVal != false){
					   $this->vocabStatus = $propVal;
					 }
					 elseif($prop == self::attributionIRI && $propVal != false){
					   $this->vocabAttribution = $propVal;
					 }
					 elseif($prop == self::licenseIRI && $propVal != false){
					   $this->vocabLicense = $propVal;
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
						  if(!in_array($parent, $rootParents)){
								$rootParents[] = $parent;
						  }
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
            $owlArray["hierarchy"] = $hierarchy;
            
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
	 
	 //get the "definedBy" property (usually a URI) for an item
	 function IRIgetDefinedBy($actIRI, $actArray){
		  $output = false;
		  if(array_key_exists($actIRI, $actArray)){
				$actConArray =  $actArray[$actIRI];
				foreach($actConArray as $annoationArray){
					 if(array_key_exists(self::definedByAbbrevIRI, $annoationArray)){
						  $output = $annoationArray[self::definedByAbbrevIRI];
					 }
				}
		  }
		  
		  if(!$output && $this->vocabURI){
				$output =  $this->vocabURI.$actIRI; 
		  }
		 
		  return $output;
	 }
	 
	 //searches hierarchy tree to get URIs for child nodes of a given parent IRI
	 function getDefinedByViaHierachy($parentIRI, $owlClasses, $owlHierarchyNode){
		  $outputURIs = false;
		  if(is_array($owlHierarchyNode)){
				foreach($owlHierarchyNode as $nodeIRIkey => $childIRIs){
					 if($nodeIRIkey == $parentIRI){
						  if(!$outputURIs){
								$outputURIs = array();
						  }
						  if(is_array($childIRIs)){
								
								foreach( $childIRIs as $childIRIkey => $subChildren){
									 $actChildURI = $this->IRIgetDefinedBy($childIRIkey , $owlClasses);
									 if($actChildURI != false){
										  $outputURIs[] = $actChildURI;
									 }
								}
						  }
					 }
					 else{
						  if(!$outputURIs){
								$outputURIs = $this->getDefinedByViaHierachy($parentIRI, $owlClasses, $owlHierarchyNode[$nodeIRIkey]);
						  }
					 }
				}
		  }
		  return $outputURIs;
	 }
	 
	 
	 
	 
	 //get the OWL concept IRI(s) based on a "definedBy" relationship
	 function getIRIfromDefinedBy($definedBy, $actArray){
		  $output = false;
		  foreach($actArray as $actIRI => $actConArray){
				foreach($actConArray as $annoationArray){
					 if(array_key_exists(self::definedByAbbrevIRI, $annoationArray)){
						  $actDefinedBy = $annoationArray[self::definedByAbbrevIRI];
						  if($actDefinedBy == $definedBy){
								if(is_array($output)){
									 $output[] = $actIRI;
								}
								else{
									 $output = array();
									 $output[] = $actIRI;
								}
						  }
					 }
				}
		  }
		  return $output;
	 }
	 
	 //get comments or notes about the vocabulary itself
	 function VocabGetComment(){
		  $owlArray = $this->owlArray;
		  $comments = array();
		  if(isset($owlArray["ontology"])){
				foreach($owlArray["ontology"] as $annoationArray){
					 if(array_key_exists(self::commentAbbrevIRI, $annoationArray)){
						  $comments[] = $annoationArray[self::commentAbbrevIRI];
					 }
				}
		  }
		  
		  return $comments;
	 }
	 
	 
	 //get an Object Property Range for a given IRI
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
	 
	  //get parents in the hierachy for a given IRI
	 function getClassParents($actIRI){
		  $parents = array();
		  $owlArray = $this->owlArray;
		  $output = false;
		  if(isset($owlArray["hierarchy"])){
				$hierarchy =  $owlArray["hierarchy"];
				$actConceptParents = $this->findParentPaths($actIRI, $hierarchy);
				if(count($actConceptParents)>0){
					 $output = $actConceptParents;
				}
		  }
		  return $output;
	 }
	 
	 function findParentPaths($actIRI, $hierarchy, $actConceptParents = array(), $actPath = false){
		 
		  foreach($hierarchy as $parentKey => $children){
				if(!$actPath){
					 $newActPath = $parentKey;
				}
				else{
					 $newActPath = $actPath.":::".$parentKey;
				}

				if($actIRI == $parentKey){
					 $actConceptParents[] = $actPath;
				}
				else{
					 if(is_array($children)){
						  $actConceptParents = $this->findParentPaths($actIRI, $children, $actConceptParents, $newActPath);
					 }
				}
		  }
		  
		  return $actConceptParents;
	 }
	 
	 
	 function outputClassParentsHTML($actConceptParents){
		  $owlArray = $this->owlArray;
		  if(isset($owlArray["hierarchy"])){
				$hierarchy =  $owlArray["hierarchy"];
				
				$doc = new DOMDocument();
				$doc->formatOutput = true;
				$root = $doc->createElement('ul');
				$doc->appendChild($root);
				$root = $this->makeClassParentList($doc, $root, $actConceptParents, $hierarchy);
				return $doc->saveXML($root);
		  }
	 }
	 
	 function makeClassParentList($doc, $actNode, $actConceptParents, $hierarchy, $actPath = false){
		  $owlArray = $this->owlArray;
		  if(isset($owlArray["classes"])){
				$classes = $owlArray["classes"];
		  }
		  
		  foreach($hierarchy as $parentKey => $children){
				
				if(!$actPath){
					 $newActPath = $parentKey;
				}
				else{
					 $newActPath = $actPath.":::".$parentKey;
				}
	 
				$newPathFound = false;
				foreach($actConceptParents as $conceptPath){
					 if((strstr($conceptPath, $newActPath) && !$newPathFound) || $conceptPath == "do-all"){
						  $newPathFound = true;
						  $item = $doc->createElement('li');
						  $itemA = $doc->createElement('a');
						  $href = $this->IRItoURL($parentKey);
						  $itemA->setAttribute("href", $href);
						  $parentLabel = $this->IRIgetLabel($parentKey, $classes);
						  $parentNote = $this->IRIgetComment($parentKey, $classes);
						  if(!$parentLabel){
								$parentLabel = $parentKey;
						  }
						  if(!$parentNote){
								$parentNote = "No description for this concept.";
						  }
						  $itemA->setAttribute("title", $parentNote);
						  $itemTextA = $doc->createTextNode($parentLabel);
						  $itemA->appendChild($itemTextA);
						  $item->appendChild($itemA);
						  if(is_array($children) && $newActPath != $conceptPath){
								$newRoot = $doc->createElement('ul');
								$newRoot = $this->makeClassParentList($doc, $newRoot, $actConceptParents, $children, $newActPath);
								$item->appendChild($newRoot);
						  }
						  $actNode->appendChild($item);
					 }
				}
		  }
		  
		  return $actNode;
		  
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
				if(strstr($this->vocabStatus, "http://opencontext.org/about/publishing/#edit-level-")){
					 $output = str_replace("http://opencontext.org/about/publishing/#edit-level-", "", $this->vocabStatus);
				}
		  }
		  return $output;
	 }
	 
	 function reviewStarHTML(){
		  $output = "Not reviewed";
		  $level = $this->getStatusLevel();
		  if($level == 1){
				$output = "&#9679;&#9675;&#9675;&#9675;&#9675;";
		  }
		  elseif($level == 2){
				$output = "&#9679;&#9679;&#9675;&#9675;&#9675;";
		  }
		  elseif($level == 3){
				$output = "&#9679;&#9679;&#9679;&#9675;&#9675;";
		  }
		  elseif($level == 4){
				$output = "&#9679;&#9679;&#9679;&#9679;&#9675;";
		  }
		  elseif($level == 5){
				$output = "&#9679;&#9679;&#9679;&#9679;&#9679;";
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
	 
	 //get the owl file, either from a local directory or direct from the repository
	 function getOwlFile($useLocal = false){
		  $xmlString = false;
		  if($useLocal){
				$sFilename = self::localOntologyDirectory.$this->OWLfile;
				@$xmlString = $this->loadFile($sFilename);
				if(!$xmlString){
					 $host = OpenContext_OCConfig::get_host_config();
					 $sFileURL = $host.self::baseLocalRepositoryURI.$this->OWLfile;
					 @$xmlString = file_get_contents($sFileURL);
				}
		  }
		  else{
				$sFileURL = self::BaseRawRepositoryHome.$this->OWLfile;
				@$xmlString = file_get_contents($sFileURL);
				if(!$xmlString){
					 $host = OpenContext_OCConfig::get_host_config();
					 $sFileURL = $host.self::baseLocalRepositoryURI.$this->OWLfile;
					 @$xmlString = file_get_contents($sFileURL);
				}
		  }
		  return $xmlString;
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
    
	 
	 function getLicenseData($licenseURI = false){
        
		  if(!$this->db){
				$db_params = OpenContext_OCConfig::get_db_config();
				$db = new Zend_Db_Adapter_Pdo_Mysql($db_params);
				$db->getConnection();
				$this->setUTFconnection($db);    
		  }
		  else{
				$db = $this->db;
		  }
		  if(!$licenseURI){
				$licenseURI = $this->vocabLicense;
		  }
		  $licenseURI = $this->security_check($licenseURI);
		  $sql = "SELECT * FROM licenses WHERE license_url LIKE '$licenseURI%' LIMIT 1; ";
		  $result = $db->fetchAll($sql, 2);
        if($result){
				return $result[0];
		  }
		  else{
				return $this->getLicenseData("http://creativecommons.org/licenses/by/3.0/"); // default to attribution
		  }
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
