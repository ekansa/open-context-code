<?php
/* This class creates a compact XML representation of
 * the JSON-LD item to store in the database without annoying escape characters
 * 
 */

class XMLjsonLD_CompactXML  {
    
	 public $db; //database connection object
	 public $compactXML;
	 public $JSONld;
	 
	 function makeJSONld($compactXMLstring){
		  
		  
		  
	 }
	 
	 
	 
	 function makeCompactXML($JSONld) {
		  
		  $dom = $doc = new DOMDocument("1.0", "utf-8");
		  $doc->formatOutput = true;
		  $root = $doc->createElement("root");
		  $doc->appendChild($root);
		  $this->compactXML = $doc;
		  $this->recursiveXML($JSONld, $root);
		  return $doc;
	 }
	 
	 
	 function recursiveXML($arrayNode, $actDomNode){
		  $doc = $this->compactXML;
		  if(is_array($arrayNode)){
				foreach($arrayNode as $key => $actVals){
					 $newActNode = $doc->createElement("n");
					 if(strlen($key)> 0 && !is_numeric($key)){
						  $XMLkey = str_replace("@", "", $key);
						  if($XMLkey != $key){
								$newActNode->setAttribute("at", 1);
						  }
						  $newActNode->setAttribute("key", $XMLkey);
					 }
					 $actDomNode->appendChild($newActNode);
					 if(is_array($actVals)){
						  $this->recursiveXML($actVals, $newActNode);
					 }
					 else{
						  $newActNodeText = $doc->createTextNode($actVals);
						  $newActNode->appendChild($newActNodeText);
					 }
				}
		  }
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
