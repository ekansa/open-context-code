<?php

/*
Manages hierarchy data for URIs to enable faceted search
*/

class Facets_Hierarchy {

	 public $db; //database connection object
    public $countReqChildren; //count of the number of requested children
	 
	 
	 public $relTypes = array("eol" => "http://purl.org/NET/biol/ns#term_hasTaxonomy"
									  
									  
									  );
	 
	 
	 
	 
	 
	 
	 //this gets an identifier from a GET request and creates a search string
	 //equivalent to a "rel[]=..." search. Or (||) deliminate between various URI that are children concepts in vocabulary hierarchy
	 function generateRelSearchEquivalent($rawParent, $typeKey){
		  $output = false;
		  $relTypes = $this->relTypes;
		  if(isset($relTypes[$typeKey])){
				$actRel = $relTypes[$typeKey];
				if(strstr($rawParent, "||")){
					 $parentURIs = explode("||", $rawParent);
				}
				else{
					 $parentURIs = $rawParent;
				}
				
				if($typeKey == "eol"){
					 $eolObj = new Facets_EOL;
					 $parentURIs = $eolObj->validateURIs($parentURIs);
				}
				
				$searchURIs = $this->getLabeledListChildURIs($parentURIs);
				if(is_array($searchURIs)){
					 $this->countReqChildren = count($searchURIs);
					 if($this->countReqChildren > 0){
						  $output = $actRel."::";
						  $firstLoop = true;
						  foreach($searchURIs as $searchURIkey => $label){
								if(!$firstLoop){
									 $output .= "||";
								}
								$output .= $searchURIkey;
								$firstLoop = false;
						  }
					 }
				}
		  }
		  
		  return $output;
	 }
	 
	 
	 
	 //gets a list of children where there are labels for the items
	 //this filters down a list to only those ChildURIs that are actually used
	 function getLabeledListChildURIs($parentURIs, $recursive = true, $tree = false){
		  $children = array();
		  $rawChildren = $this->getListChildURIs($parentURIs, $recursive, $tree);
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
	 function getNestedChildURIs($parentURIs, $recursive = true, $tree = false){
		  
		  $db = $this->startDB();
		  
		  if($tree != false){
				$treeCondition = " AND hi.tree = '$tree' ";
		  }
		  else{
				$treeCondition = " ";
		  }
		  
		  if(!is_array($parentURIs)){
				$parentURIs = array(0 => $parentURIs);
		  }
		  $parentCondition = " (";
		  $pFirst = true;
		  foreach($parentURIs as $parentURI){
				if(!$pFirst){
					 $parentCondition .= " OR ";
				}
				$parentCondition .= " hi.parentURI = '$parentURI' ";
				$pFirst = false;
		  }
		  $parentCondition .= ") ";
		  
		  
		  $sql = "SELECT DISTINCT hi.childURI, ld.linkedLabel
		  FROM hierarchies AS hi
		  LEFT JOIN linked_data AS ld ON hi.childURI = ld.linkedURI
		  WHERE $parentCondition
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
	 function getListChildURIs($parentURIs, $recursive = true, $tree = false){
		  
		  $children = array();
		  $db = $this->startDB();
		  
		  if($tree != false){
				$treeCondition = " AND hi.tree = '$tree' ";
		  }
		  else{
				$treeCondition = " ";
		  }
		  
		  if(!is_array($parentURIs)){
				$parentURIs = array(0 => $parentURIs);
		  }
		  $parentCondition = " (";
		  $pFirst = true;
		  foreach($parentURIs as $parentURI){
				if(!$pFirst){
					 $parentCondition .= " OR ";
				}
				$parentCondition .= " hi.parentURI = '$parentURI' ";
				$pFirst = false;
		  }
		  $parentCondition .= ") ";
		  
		  
		  $sql = "SELECT DISTINCT hi.childURI, ld.linkedLabel
		  FROM hierarchies AS hi
		  LEFT JOIN linked_data AS ld ON hi.childURI = ld.linkedURI
		  WHERE $parentCondition 
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
