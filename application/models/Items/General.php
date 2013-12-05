<?php


//this class interacts with the database for accessing and changing Predicates
//predicates are defined in different projects
class Items_General {
    
	 public $db;
	 
    public static function generateUUID()    {
        $rawid = strtoupper(md5(uniqid(rand(), true)));
		  $workid = $rawid;
		  $byte = hexdec( substr($workid,12,2) );
		  $byte = $byte & hexdec("0f");
		  $byte = $byte | hexdec("40");
		  $workid = substr_replace($workid, strtoupper(dechex($byte)), 12, 2);
			
		  // build a human readable version
		  $rid = substr($rawid, 0, 8).'-'
				 .substr($rawid, 8, 4).'-'
				 .substr($rawid,12, 4).'-'
				 .substr($rawid,16, 4).'-'
				 .substr($rawid,20,12);
					  
					  
					  // build a human readable version
					  $wid = substr($workid, 0, 8).'-'
				 .substr($workid, 8, 4).'-'
				 .substr($workid,12, 4).'-'
				 .substr($workid,16, 4).'-'
				 .substr($workid,20,12);
         
        return $wid;   
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
