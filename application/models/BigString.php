<?php


/*
 this class handles big string manipulations.
 MYSQL sometimes won't store string values that are too large in a single cell, so sometimes one needs to store them 
 in seperate fields.
 
 This class lets you store, version, and retrieve large string values.
*/

class BigString {
    
    const splitSize = 50000; 
    
    //save current big string data. this replaces old big strings for new big strings
    function saveCurrentBigString($id, $field, $itemType, $bigString, $db = false){
	if(!$db){
	    $db_params = OpenContext_OCConfig::get_db_config();
	    $db = new Zend_Db_Adapter_Pdo_Mysql($db_params);
	    $db->getConnection();
	    $this->setUTFconnection($db);
	}
	
	$where = array();
	$where[] = "itemUUID = '".$id."' ";
	$where[] = "field = '".$field."' ";
	$db->delete("big_values", $where);
	
	$stringArray = str_split($bigString, self::splitSize);  
	foreach($stringArray as $stringFrag){
	    $data = array("itemUUID" => $id,
			  "itemType" => $itemType,
			  "field" => $field,
			  "value_frag" => $stringFrag);
	    $db->insert("big_values", $data);
	}
    
    }//end function
    
    
    //save current big string data. this replaces old big strings for new big strings
    function saveVersionBigString($id, $versionID, $currentVersion, $itemType, $field, $bigString, $db = false){
	if(!$db){
	    $db_params = OpenContext_OCConfig::get_db_config();
	    $db = new Zend_Db_Adapter_Pdo_Mysql($db_params);
	    $db->getConnection();
	    $this->setUTFconnection($db);
	}
	
	$stringArray = str_split($bigString, self::splitSize);
	$output = true;
	foreach($stringArray as $stringFrag){
	    $data = array("itemUUID" => $id,
			  "versionID" => $versionID,
			  "versionNum" => $currentVersion,
			  "itemType" => $itemType,
			  "field" => $field,
			  "value_frag" => $stringFrag);
	    
		try{
			$db->insert("version_big_values", $data);
		}catch(Exception $e) {
			$output = false;
		}
	}
    
	return $output;
    }//end function
    
    
    //query database to get a big string
    function get_CurrentBigString($id, $field, $db = false){
	if(!$db){
	    $db_params = OpenContext_OCConfig::get_db_config();
	    $db = new Zend_Db_Adapter_Pdo_Mysql($db_params);
	    $db->getConnection();
	    $this->setUTFconnection($db);
	}
	
	$string = "";
	
	$sql = "SELECT value_frag
	FROM big_values
	WHERE field = '".$field."'
	AND itemUUID = '".$id."'
	ORDER BY id
	";
	
	$result = $db->fetchAll($sql, 2);
	if($result){
	    foreach($result as $row){
		$string.= $row["value_frag"];
	    }
	}
	
	return $string;	
    }//end function
    
    //query database to get big string of archived data
    function get_VersionBigString($id, $versionID, $field, $db = false){
	if(!$db){
	    $db_params = OpenContext_OCConfig::get_db_config();
	    $db = new Zend_Db_Adapter_Pdo_Mysql($db_params);
	    $db->getConnection();
	    $this->setUTFconnection($db);
	}
	
	$string = "";
	
	$sql = "SELECT value_frag
	FROM version_big_values
	WHERE itemUUID = '".$id."'
	AND versionID = '".$versionID."'
	AND field = '".$field."'
	ORDER BY id
	";
	
	$result = $db->fetchAll($sql, 2);
	if($result){
	    foreach($result as $row){
		$string.= $row["value_frag"];
	    }
	}
	
	return $string;	
    }//end function
    
     //make sure character encoding is set, so greek characters work
    function setUTFconnection($db){
	    $sql = "SET collation_connection = utf8_unicode_ci;";
	    $db->query($sql, 2);
	    $sql = "SET NAMES utf8;";
	    $db->query($sql, 2);
    } 
    
}
