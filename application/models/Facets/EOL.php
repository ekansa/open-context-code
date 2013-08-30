<?php

/*
Manages, parses, and uses the EOL API
*/

class Facets_EOL {

	 public $db; //database connection object
    
	 //this represents the label for the source hierarchies to use to get taxonomy hierachies for Open Context
	 public $preferredHierarchies = array("Species 2000 & ITIS Catalogue of Life: April 2013",
													  "Integrated Taxonomic Information System (ITIS)"
													  );
	 
	 const vocabURI = "http://eol.org/";
	 const defaultTree = "default";
	 
	 const baseEOLpageURI = "http://eol.org/pages/";
	 
	 const baseEOLpageJSON = "http://eol.org/api/pages/1.0/";
	 const EOLpageJSONsuffix = ".json?images=0&videos=0&sounds=0&maps=0&text=2&iucn=false&subjects=overview&licenses=all&details=false&common_names=false&synonyms=false&references=false&vetted=0";
	 
	 const baseEOLhierarchyJSON = "http://eol.org/api/hierarchy_entries/1.0/";
	 const EOLhierarchyJSONsuffix = ".json?common_names=false&synonyms=false";
	 
	 
	 
	 function accessionEOLhierarchy(){
		  $output = array();
		  $db = $this->startDB();
		  
		  $sql = "SELECT DISTINCT ld.linkedURI, CHAR_LENGTH(ld.linkedURI) as uriLen
		  FROM linked_data AS ld
		  LEFT JOIN hierarchies AS hi ON ld.linkedURI = hi.childURI
		  WHERE linkedURI LIKE 'http://eol.org/pages/%'
		  AND hi.childURI IS NULL
		  ORDER BY uriLen, ld.linkedURI
		  ";
		  
		  $result = $db->fetchAll($sql);
		  if($result){
				foreach($result as $row){
					 sleep(1);
					 $eolURI = $row["linkedURI"];
					 $hierarchyArray = $this->getEOLpageHierarchy($eolURI);
					 $saveOK = $this->saveEOLpageHierarchyData($hierarchyArray);
					 if($saveOK){
						  $output["done"][] = $eolURI;
					 }
					 else{
						  $output["noChanges"][] = $eolURI;
					 }
				}
		  }
		  
		  return $output;
	 }
	 
	 
	 //save the hierarchy data obtained from the EOL API
	 function saveEOLpageHierarchyData($hierarchyArray){
		  
		  $output = false;
		  $lastChildURI = false;
		  if(is_array($hierarchyArray)){
				
				if(isset($hierarchyArray["taxonConceptID"])){
					 $lastChildURI = self::baseEOLpageURI.$hierarchyArray["taxonConceptID"];
				}//case with childID
		  
				if(isset($hierarchyArray["ancestors"])){
					 if(is_array($hierarchyArray["ancestors"])){
						  $numberAncestors = count($hierarchyArray["ancestors"]);
						  $i = 0;
						  $db = $this->startDB();
						  $hierarchyObj = new Facets_Hierarchy;
						  $hierarchyObj->db = $db;
						  while($i < $numberAncestors){
								$data = array();
								$data["tree"] = self::defaultTree;
								$data["vocabURI"] = self::vocabURI;
								$actAncestor = $hierarchyArray["ancestors"][$i];
								$data["parentURI"] = self::baseEOLpageURI.$actAncestor["taxonConceptID"];
								if($i + 1 < $numberAncestors){
									 $child = $hierarchyArray["ancestors"][$i + 1];
									 $data["childURI"] = self::baseEOLpageURI.$child["taxonConceptID"];
								}
								else{
									 $data["childURI"] = $lastChildURI;
								}
								$actOut = $hierarchyObj->addHierarchyFromArray($data);
								if($actOut){
									 $output = true;
								}
						  $i++;
						  }//end loop through ancestors
					 }//end case with ancestor array
				}//end case with ancestors
				
				if(isset($hierarchyArray["children"])){
					 if(is_array($hierarchyArray["children"])){
						  
						  $db = $this->startDB();
						  $hierarchyObj = new Facets_Hierarchy;
						  $hierarchyObj->db = $db;
						  foreach($hierarchyArray["children"] as $childTaxa){
								$actChildURI = self::baseEOLpageURI.$childTaxa["taxonConceptID"];
								
								$sql = "SELECT DISTINCT 1 AS yes
								FROM hierarchies
								WHERE hierarchies.parentURI = '$actChildURI'
								OR hierarchies.childURI = '$actChildURI'

								UNION
								
								SELECT DISTINCT 1 AS yes
								FROM linked_data
								WHERE linked_data.linkedURI = '$actChildURI'
								";
								
								$result =  $db->fetchAll($sql);
								if($result){
									 
									 $data = array();
									 $data["tree"] = self::defaultTree;
									 $data["vocabURI"] = self::vocabURI;
									 $data["parentURI"] = $lastChildURI; //we're using the current URI as the parent, because we're looking at it's children now
									 $data["childURI"] = $actChildURI;
									 $actOut = $hierarchyObj->addHierarchyFromArray($data);
									 if($actOut){
										  $output = true;
									 }
								}//end case where there are children item we care about, meaning they are used in the database
						  }//end loop through children
					 }//end case with ancestor array
				}//end case with ancestors
				
		  }//end case with an array of hiearchy data
		  
		  return $output;
	 }
	 
	 
	 //get a taxonomic hierarchy for a given EOL page
	 function getEOLpageHierarchy($eolURI){
		  $output = false;
		  $preferredHierarchies = $this->preferredHierarchies;
		  $eolID = $this->idFromEOLpageURI($eolURI);
		  $pageArray = $this->getPageJSON($eolID);
		  $hierarchyEntryID = false;
		  if(is_array($pageArray)){
				if(isset($pageArray["taxonConcepts"])){
					 if(is_array($pageArray["taxonConcepts"])){
						  foreach($pageArray["taxonConcepts"] as $taxCon){
								if(in_array($taxCon["nameAccordingTo"], $preferredHierarchies)){
									 $hierarchyEntryID = $taxCon["identifier"]; //got the hierarchy entry ID !!
									 break;
								}
						  }
					 }
				}
		  }
		  else{
				//echo "bad page: ".$eolID;
				//die;
		  }
		  
		  if($hierarchyEntryID != false){
				sleep(1); //give the EOL a break
				$output = $this->getHierarchyJSON($hierarchyEntryID);
		  }
		  else{
				//echo "no hierarchy id";
				//die;
		  }
		  
		  return $output;
	 }
	 
	 
	 
	 
	 //get JSON data for an EOL hierarchy entry
	 function getHierarchyJSON($hierarchyEntryID){
		  $output = false;
		  
		  if(is_numeric($hierarchyEntryID)){
				$JSONurl = self::baseEOLhierarchyJSON.$hierarchyEntryID.self::EOLhierarchyJSONsuffix;
				@$jsonString = file_get_contents($JSONurl);
				if($jsonString){
					 $output = Zend_Json::decode($jsonString);
				}
		  }
		  
		  return $output;
	 }

	 //get JSON data for an EOL page URI (or ID)
	 function getPageJSON($eolID){
		  $output = false;
		  $eolID = $this->idFromEOLpageURI($eolID);
		  
		  if($eolID != false){
				$JSONurl = self::baseEOLpageJSON.$eolID.self::EOLpageJSONsuffix;
				@$jsonString = file_get_contents($JSONurl);
				if($jsonString){
					 $output = Zend_Json::decode($jsonString);
				}
		  }
		  
		  return $output;
	 }
	 
	 
	 
	 //get the numeric ID from the EOLuri
	 function idFromEOLpageURI($eolURI){
		  
		  if(!is_numeric($eolURI)){
				$eolID = false;
				if(substr($eolURI, -1) == "/"){
					 $eolURI = substr($eolURI, 0, (strlen($eolURI) - 1)); //strip the trailing "/"
				}
				$eolEx = explode("/", $eolURI);
				$eolID = $eolEx[(count($eolEx) - 1)]; //last part of the URI is the ID
				if(!is_numeric($eolID)){
					 $eolID = false;
				}
		  }
		  else{
				$eolID = $eolURI;
		  }
		  return $eolID;
	 }
	 
	 
	 
	 //get the numeric ID from the EOLuri
	 function validateURIs($eolURIs){
		  
		  if(is_array($eolURIs)){
				$newURIs = array();
				foreach($eolURIs as $actURI){
					 $newURIs[] = $this->URIfromEOLtaxonID($actURI);
				}
				unset($eolURIs);
				$eolURIs = $newURIs;
				unset($newURIs);
		  }
		  else{
				$eolURIs = $this->URIfromEOLtaxonID($eolURIs);
		  }
		  
		  return $eolURIs;
	 }
	 
	 //get the numeric ID from the EOLuri
	 function URIfromEOLtaxonID($eolID){
		  
		  if(is_numeric($eolID)){
				$eolURI = self::baseEOLpageURI.$eolID;
		  }
		  else{
				if(strstr($eolID,self::baseEOLpageURI)){
					 $eolURI = $eolID;
				}
				else{
					 $eolURI = false;
				}
		  }
		  return $eolURI;
	 }
	 
	 
	 function startDB(){
		  if(!$this->db){
				$db_params = OpenContext_OCConfig::get_db_config();
				$db = new Zend_Db_Adapter_Pdo_Mysql($db_params);
				$db->getConnection();
				$this->setUTFconnection($db);
				$this->db = $db;
		  }
		  else{
				$db = $this->db;
		  }
		  
		  return $db;
	 }
	 
	 function setUTFconnection($db){
		  $sql = "SET collation_connection = utf8_unicode_ci;";
		  $db->query($sql, 2);
		  $sql = "SET NAMES utf8;";
		  $db->query($sql, 2);
    }
	 
}//end class

?>
