<?php
/*This class edits XML so we can cleanly remove erroneous media items from subject items, and vice versa
 *
 *
 */
class Debug_SpaceMediaEdit  {
    
	 public $mediaUUID;
    public $db;
   
	
	
	 //this function edits the xml of a subject item to remove the offending media item
	 function removeMediaFromSpace($subjectUUID, $mediaUUID){
		  $subjectObj = new Subject;
		  $xmlString = $subjectObj->getItemXML($subjectUUID);
		  $subjectNameSpaceArray = $subjectObj->nameSpaces();
		  $xml = new DOMDocument();
		  $xml->loadXML($xmlString);
		  $xpath = new DOMXPath($xml);
		  foreach($subjectNameSpaceArray as $prefix => $uri){
				$xpath->registerNamespace($prefix, $uri);
		  }
		  $changed = false;
		  $query = "//arch:docID";
		  $links = $xpath->query($query);
		  foreach($links as $node){
				if($node->textContent == $mediaUUID ){
					 $node->parentNode->removeChild($node); //get rid of the node we don't want
					 $changed = true;
					 break;
				}
		  }
		  $uriMedia = "http://opencontext.org/media/".$mediaUUID;
		  $query = "//oc:link[@href='$uriMedia']";
		  $links = $xpath->query($query);
		  foreach($links as $node){
				$node->parentNode->removeChild($node); //get rid of the node we don't want
				$changed = true;
		  }
		  $xml->formatOutput = true;
		  $newXML = $xml->saveXML();
		  $subjectObj->archaeoML = $newXML;
		  if($changed){
				$subjectObj->createUpdate(true);
		  }
		  return $changed;
	 }
	
	
	
	 //this function gets the Subject items directly referenced by a media item
	 function getSpaceFromMediaXML($mediaUUID){
		  
		  $MediaObj = new Media;
		  $xmlString = $MediaObj->getItemXML($mediaUUID);
		  $mediaNameSpaceArray = $MediaObj->nameSpaces();
		  $xml = simplexml_load_string($xmlString);
		  foreach($mediaNameSpaceArray as $prefix => $uri){
				$xml->registerXPathNamespace($prefix, $uri);
		  }
		  $subjectUUIDs = array();
		  foreach($xml->xpath('//arch:docID[@type="spatialUnit"]') as $xpathRes){
				$subjectUUIDs[] = (string)$xpathRes;
		  }
		  
		  return $subjectUUIDs;
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
	 
	 
	 //preps for utf8
	 function setUTFconnection($db){
		  $sql = "SET collation_connection = utf8_unicode_ci;";
		  $db->query($sql, 2);
		  $sql = "SET NAMES utf8;";
		  $db->query($sql, 2);
	 }

    
}  
