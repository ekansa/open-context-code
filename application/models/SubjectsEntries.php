<?php


//this class interacts with the database for accessing and changing Subjects (location and object items)
class SubjectsEntries {
    
    public $idEntryArray;
    public $idArray;
    public $sql;    
	
    //get User data from database
    function getByIDArray($idArray){
        
	$cleanIDarray = array();
	
	$query = "";
	$firstLoop = true;
	foreach($idArray as $id){
	    $cleanIDarray[] = $this->security_check($id);
	    
	    if($firstLoop){
		$query = ' uuid = "'.$id.'" ';
	    }
	    else{
		$query .= ' OR uuid = "'.$id.'" ';
	    }
	    
	$firstLoop = false;    
	}
	
	$idCount = count($cleanIDarray);
	$this->idArray = $cleanIDarray;
	
        $output = false; //no user
        $db_params = OpenContext_OCConfig::get_db_config();
        $db = new Zend_Db_Adapter_Pdo_Mysql($db_params);
		$db->getConnection();
		$this->setUTFconnection($db);
        
        $sql = 'SELECT uuid, atom_entry
                FROM space
                WHERE '.$query.'
                LIMIT '.$idCount ;
	
		$this->sql = $sql;
        $result = $db->fetchAll($sql, 2);
	
		$idEntryArray = false;
	
        if($result){
	    
			$idEntryArray = array();
			foreach($cleanIDarray as $cleanID){
				foreach($result as $row){
					$itemUUID = $row["uuid"]; 
					$entry = $row["atom_entry"];
					if($cleanID == $itemUUID){
						$idEntryArray[$itemUUID]= $entry;
					}
				}
			}
        }
        
		$db->closeConnection();
		$this->idEntryArray = $idEntryArray;
	
        return $idEntryArray;
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
    
	 //make sure character encoding is set, so greek characters work
    function setUTFconnection($db){
	    $sql = "SET collation_connection = utf8_unicode_ci;";
	    $db->query($sql, 2);
	    $sql = "SET NAMES utf8;";
	    $db->query($sql, 2);
    }
    
    
    
}
