<?php
/* This class creates files based on data saved for export.
 * It makes csv, zip (with csv), and gzipped csv files.
 * 
 */

class Repository  {
    
	 public $db; //database connection object
	 public $uuid;
	 public $projectUUID;
	 public $itemType;
	 public $updated;
	 public $compressed;
	 public $unCompressed;
	 
	 public $error;
	 public $types = array("subjects",
								  "media",
								  "documents",
								  "persons"
								  );
	 
	 
	 const compressionLevel = 9 ; //9 is maxiumum compression, 6 is the PHP default
	 
	 //gets an item's data and uncompresses it
	 function getItemData($uuid){
		  $output = false;
		  $db = $this->startDB();
		  
		  $uuid = $this->security_check($uuid);
		  
		  $sql = "SELECT *
		  FROM repository
		  WHERE uuid = '$uuid'
		  LIMIT 1;
		  ";
		  
		  $result = $db->fetchAll($sql);
		  if($result){
				$this->uuid = $uuid;
				$this->projectUUID = $result[0]["project_id"];
				$this->itemType = $result[0]["type"];
				$this->compressed = $result[0]["compressed"];
				$this->unCompressed = gzuncompress($this->compressed);
				$output = $this->unCompressed;
		  }
		  return $output;
	 }
	 
	 
	 //gets an item's data and uncompresses it
	 function getItemExists($uuid){
		  $data = false;
		  $db = $this->startDB();
		  
		  $uuid = $this->security_check($uuid);
		  
		  $sql = "SELECT uuid
		  FROM repository
		  WHERE uuid = '$uuid'
		  LIMIT 1;
		  ";
		  
		  $result = $db->fetchAll($sql);
		  if($result){
				return true;
		  }
		  else{
				return false;
		  }
	 }
	 
	 
	 
	 //gets an item's data and uncompresses it
	 function addUpdateItemData($unCompressed, $uuid, $projectUUID = false, $itemType = false){
		  
		  $output = false;
		  $db = $this->startDB();
		  
		  $uuid = $this->security_check($uuid);
		  $compressed = gzcompress($unCompressed, self::compressionLevel);
		  unset($unCompressed); //no need to waste memory!
		  $data = array("compressed" => $compressed);
		  
		  if($this->getItemExists($uuid)){
				$where = "uuid = '$uuid' ";
				try{
					 $db->update("repository", $data, $where);
					 $output = true;
				}
				catch (Exception $e) {
					 $error = (string)$e;
					 $output = false;
				}
		  }
		  else{
				
				//do an insert
				if($uuid != false && $projectUUID != false && $itemType != false){
					 
					 $data["uuid"] = $uuid;
					 $data["project_id"] = $this->security_check($projectUUID);
					 $data["type"] = $this->security_check($itemType);

					 try{
						  $db->insert("repository", $data);
						  $output = true;
					 }
					 catch (Exception $e) {
						  $error = (string)$e;
						  $output = false;
					 }
				}
				else{
					 $error = "Missing data";
				}
		  }
		  
		  return $output;
	 }
	 
	 
	 
	 
	 
	 function security_check($input){
        $badArray = array("DROP", "SELECT", "#", "--", "DELETE", "INSERT", "UPDATE", "ALTER", "=");
        foreach($badArray as $bad_word){
            if(stristr($input, $bad_word)){
                $input = str_ireplace($bad_word, "XXXXXX", $input);
            }
        }
        return $input;
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
