<?php


//this class interacts with the database for accessing and changing Subjects (location and object items)
class Items_Subject {
    
	 public $db;
	 
    /*
     General item metadata
    */
    public $doi;
	 public $ark;
    public $projectUUID;
    public $sourceID;
    public $itemUUID;
    public $label;
    
    /*
    Subject specific
    */
    public $classID; //identifier for a class
    public $className; //name for a class
    public $contain_hash;
    public $contextPath; //path of context
    
	 /*
	 Administrative
	 */
	 public $repo; //repository, used for keeping data in Github 
    public $viewCount;
    public $createdTime;
    public $updatedTime;
    public $atomEntry;
    public $archaeoML;
    
   
   
    //get User data from database
    function getByUUID($uuid){
        
        $uuid = $this->security_check($uuid);
        $output = false; //no user
        
        $db = $this->startDB();
        
        $sql = 'SELECT *
                FROM subjects
                WHERE uuid = "'.$uuid.'"
                LIMIT 1';
		
        $result = $db->fetchAll($sql, 2);
        if($result){
            $output = true;
				
				
				$this->getItemXML($uuid);
		  }
        return $output;
    }
    
    //Gets compressed XML from database, decompresses, adds to object
	 function getItemXML($uuid){
		  $db = $this->startDB();
		  $reposObj = new Repository;
		  $reposObj->db = $db;
		  $this->archaeoML = $reposObj->getItemData($uuid);
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
