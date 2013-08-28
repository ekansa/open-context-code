<?php

/*
Manages hierarchy data for URIs to enable faceted search
*/

class Facets_Hierarchy {

	 public $db; //database connection object
    
	 
	 //gets a list of children where there are labels for the items
	 //this filters down a list to only those ChildURIs that are actually used
	 function getLabeledListChildURIs($parentURI, $recursive = true, $tree = false){
		  $children = array();
		  $rawChildren = $this->getListChildURIs($parentURI, $recursive, $tree);
		  if(is_array($rawChildren)){
				foreach($rawChildren as $uriKey => $label){
					 $label = trim($label);
					 if(strlen($label)>1){
						  $children[$uriKey] = $label;
					 }
					 
				}
		  }
		  return $children;	 
	 }
	 
	 //gets children of a given URI as a nested / tree array
	 function getNestedChildURIs($parentURI, $recursive = true, $tree = false){
		  
		  $db = $this->startDB();
		  
		  if($tree != false){
				$treeCondition = " AND hi.tree = '$tree' ";
		  }
		  else{
				$treeCondition = " ";
		  }
		  
		  $sql = "SELECT DISTINCT hi.childURI, ld.linkedLabel
		  FROM hierarchies AS hi
		  LEFT JOIN linked_data AS ld ON hi.childURI = ld.linkedURI
		  WHERE hi.parentURI = '$parentURI'
		  $treeCondition
		  ";
		  
		  
		  $result =  $db->fetchAll($sql);
		  if($result){
				$children = array();
				foreach($result as $row){
					 $childURI = $row["childURI"];
					 if($recursive){
						  $row["children"] = $this->getNestedChildURIs($childURI, $recursive, $tree);
					 }
					 $children[] = $row;
				}
		  }
		  else{
				$children = false;
		  }
		  
		  return $children;
	 }
	 
	 
	 
	 //gets children of a given URI as a List
	 function getListChildURIs($parentURI, $recursive = true, $tree = false){
		  
		  $children = array();
		  $db = $this->startDB();
		  
		  if($tree != false){
				$treeCondition = " AND hi.tree = '$tree' ";
		  }
		  else{
				$treeCondition = " ";
		  }
		  
		  $sql = "SELECT DISTINCT hi.childURI, ld.linkedLabel
		  FROM hierarchies AS hi
		  LEFT JOIN linked_data AS ld ON hi.childURI = ld.linkedURI
		  WHERE hi.parentURI = '$parentURI'
		  $treeCondition
		  ";
		  
		  
		  $result =  $db->fetchAll($sql);
		  if($result){
				foreach($result as $row){
					 $childURI = $row["childURI"];
					 if(!array_key_exists($childURI, $children)){
						  $children[$childURI] = $row["linkedLabel"];
					 }
					 if($recursive){
						  $subChildren = $this->getListChildURIs($childURI, $recursive, $tree);
						  foreach($subChildren as $uriKey => $label){
								 if(!array_key_exists($uriKey, $children)){
									 $children[$uriKey] = $label;
								}
						  }
					 }
				}
		  }
		  
		  return $children;
	 }
	 
	 
	 
	 //add a hiearchy record form parameters
	 function addHierarchyFromParams($parentURI, $childURI, $vocabURI, $tree){
		  $output = false;
		  $db = $this->startDB();
		  $hashID = md5($tree."_".$parentURI."_". $childURI);
		  
		  $data = array("hashID" => $hashID,
							 "parentURI" => $parentURI,
							 "childURI" => $childURI,
							 "vocabURI" => $vocabURI,
							 "tree" => $tree
							 );
		  
		  try{
				$db->insert("hierarchies", $data);
				$output = true;
		  }
		  catch (Exception $e) {
				$error = (string)$e;
				$output = false;
		  }
		  
		  return $output;
	 }
	 
	 //add a hierarchy record from an array
	 function  addHierarchyFromArray($data){
		  $output = false;
		  $db = $this->startDB();
		  
		  if(isset($data["tree"]) && isset($data["parentURI"]) && isset($data["childURI"])){
					 
				$hashID = md5($data["tree"]."_".$data["parentURI"]."_". $data["childURI"]);
				$data["hashID"] = $hashID;
				
				try{
					 $db->insert("hierarchies", $data);
					 $output = true;
				}
				catch (Exception $e) {
					 $error = (string)$e;
					 $output = false;
					 
					 //echo $error;
					 //die;
				}
		  }
		  
		  return $output;
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
