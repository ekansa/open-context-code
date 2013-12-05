<?php
/* This class creates files based on data saved for export.
 * It makes csv, zip (with csv), and gzipped csv files.
 * 
 */

class XMLjsonLD_Item  {
    
	 public $db; //database connection object
	 
	 public $uri; //item URI
	 public $uuid; //uuid of the item
	 public $label; //main label of the object
	 public $itemType; //main type of Open Context item or resource (subject, media, document, person, project)
	 public $projectUUID; //uuid of the item's project
	 
	 public $published; //dublin core publication date
	 public $license; //copyright license
	 
	 public $contributors;
	 public $creators;
	 
	 //class, usually used with subject items
	 public $itemClass;  //any object of an RDF type predicate
	 
	 //media specific
	 public $mediaType;
	 
	 //documents specific
	 public $documentContents;
	 
	 //person specific
	 public $surname; //person's last name
	 public $givenName; //persons first name

	 public $contexts; //context array (for subjects)
	 
	 public $observations; //observation array (has observation metadata, properties, notes, links, and linked data)
	 
	 function makeJSON_LD(){
		  
		  $JSON_LD = array();
		  
		  $JSON_LD["@context"] = array(
				"type" => "@type",
				"id" => "@id",
				"dc-elements" => "http://purl.org/dc/elements/1.1/",
				"dcterms" => "http://purl.org/dc/terms/",
				"dcterms:contributor" => array("@type" => "@id"),
				"dcterms:creator" => array("@type" => "@id"),
				"dcterms:license" => array("@type" => "@id"),
				"uuid" => "dcterms:identifier",
				"bibo" => "http://purl.org/ontology/bibo/",
				"label" => "http://www.w3.org/2000/01/rdf-schema#label",
				"xsd" => "http://www.w3.org/2001/XMLSchema#",
				"oc-gen" => "http://opencontext.org/vocabularies/oc-general/",
				);
		  
		  $JSON_LD["id"] = $this->uri;
		  $JSON_LD["label"] = $this->label;
		  $JSON_LD["uuid"] = $this->uuid;
		 
		  if(is_array($this->contexts)){
				
				$contextTree = 1;
				foreach($this->contexts as $treeKey => $contextList){
					 
					 $actTree = array(	"id" => "#context-path-".$contextTree,
												"oc-gen:path-des" => $treeKey,
												"oc-gen:has-path-items" => $contextList
												);
					 
					 $JSON_LD["oc-gen:has-context-path"][] = $actTree;
					 
					 if($treeKey == "default"){
						  foreach($contextList as $actContext){
								
								//$JSON_LD["oc-gen:has-main-context"][] = $actContext;
						  }
					 }
					 else{
						  foreach($contextList as $actContext){
								$JSON_LD["oc-gen:has-alt-context"][] = $actContext;
						  }
						  $contextTree++;
					 }
					 
				}
		  }
		  
		  if(is_array($this->observations)){
				$propNum = 1;
				$vars = array();
				$links = array();
				foreach($this->observations as $obsNumKey => $observation){
					 $obsNode = "#obs-".$obsNumKey;
					 $actObsOutput = array("id" => $obsNode,
												"oc-gen:sourceID" => $observation["sourceID"],
												"oc-gen:obsStatus" => $observation["status"]);
					 
					 if(isset($observation["properties"])){
						  if(is_array($observation["properties"])){
								
								foreach($observation["properties"] as $varURI => $varValues){
									 foreach($varValues as $values){
										  if(isset($values["id"])){
												$actType = "@id";
										  }
										  else{
												$actType = $values["type"];
										  }
										  if(!array_key_exists($varURI, $vars)){
												$actVarNumber = count($vars) + 1;
												$vars[$varURI] = array("type" => $actType, "abrev" => "var-".$actVarNumber);
										  }
									 }
								}
								
								foreach($vars as $varURI => $varArray){
									 $abrev = $varArray["abrev"];
									 $JSON_LD["@context"][$abrev] = array("@id" => $varURI,
																						"@type" => $varArray["type"]);
								}
								
								foreach($observation["properties"] as $varURI => $varValues){
									 $abrev = $vars[$varURI]["abrev"];
									 foreach($varValues as $values){
										  
										  if(isset($values["id"])){
												$outputValue = $values["id"];
										  }
										  else{
												$actType = $values["type"];
												$outputValue = $values[$actType];
										  }
										  //$actObsOutput[$varURI][] =  $outputValue;
										  $actObsOutput[$abrev][] =  $outputValue;
										  $propNum++;
									 }
								}
						  }
					 }
					 
					 if(isset($observation["notes"])){
						  if(is_array($observation["notes"])){
								$actObsOutput["oc-gen:has-note"] = $observation["notes"];
						  }
					 }
					 if(isset($observation["links"])){
						  if(is_array($observation["links"])){
								foreach($observation["links"] as $predicateKey => $objectURIs){
									 if(!array_key_exists($predicateKey, $links)){
										  $actLinkNumber = count($links) + 1;
										  $links[$predicateKey] = array("type" => "@id", "abrev" => "link-".$actLinkNumber);
									 }
								}
								
								foreach($links as $predicateKey => $linkArray){
									 $abrev = $linkArray["abrev"];
									 $JSON_LD["@context"][$abrev] = array("@id" => $predicateKey,
																						"@type" => $linkArray["type"]);
								}
								
								foreach($observation["links"] as $predicateKey => $objectURIs){
									 $abrev = $links[$predicateKey]["abrev"];
									 foreach($objectURIs as $objectURI){
										  $actObsOutput[$abrev][] = $objectURI;
									 }
								}
								
						  }
					 }
					 $JSON_LD["oc-gen:has-obs"][] = $actObsOutput;
				}
		  }
		 
		  //$JSON_LD["rawobs"] = $this->observations;
		  $JSON_LD["dcterms:published"] = $this->published;
		  $JSON_LD["dcterms:license"] = $this->license;
		  if(is_array($this->creators)){
				$JSON_LD["dcterms:creator"] = $this->creators;
		  }
		  if(is_array($this->contributors)){
				$JSON_LD["dcterms:contributor"] = $this->contributors;
		  }
		  
		  return $JSON_LD;
	 }
	 
	 
	 function assignSubjectClass($item_class){
		  $this->itemClass = $item_class;
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
	 

    
}  
