<?php


//this class interacts with the database for accessing and changing Omeka items (from Omeka sites)
class Omeka {
    
    public $baseURL;
    public $itemURI;
    public $label;
    public $indexedTime;
    public $createdTime;
    public $updatedTime;
    public $json;
 
    
    //get User data from database
    function getByID($uri){
        
        $uri = $this->security_check($uri);
        $output = false; //no user
        $db_params = OpenContext_OCConfig::get_db_config();
        $db = new Zend_Db_Adapter_Pdo_Mysql($db_params);
	$db->getConnection();
        
        $sql = 'SELECT *
                FROM omeka
                WHERE itemURI = "'.$uri.'"
                LIMIT 1';
		
        $result = $db->fetchAll($sql, 2);
        if($result){
            
            $this->itemURI = $result[0]["itemURI"];
	    $this->label = $result[0]["label"];
            $this->baseURL = $result[0]["base_URL"];
            $this->indexedTime = $result[0]["indexed"];
            $this->createdTime = $result[0]["created"];
            $this->updatedTime = $result[0]["updated"];
	    $this->json = $result[0]["json"];
        }
        
	$db->closeConnection();
    
        return $output;
    }
    
   
   
    //check to see if a doc exists
    function check_doc_exists($uri){
	
	$uri = $this->security_check($uri);
	
        $db_params = OpenContext_OCConfig::get_db_config();
        $db = new Zend_Db_Adapter_Pdo_Mysql($db_params);
	$db->getConnection();
        
        $sql = 'SELECT itemURI
                FROM omeka
                WHERE itemURI = "'.$uri.'"
                LIMIT 1';
		
        $result = $db->fetchAll($sql, 2);
        $db->closeConnection();
        if($result){
            return true;
        }
        else{
            return false;
        }
    }
    
    //add a new doc it it doesn't exist, or update it if it does
    function add_update_doc($uri){
        if($this->check_doc_exists($uri)){
            $this->update_doc($uri);
        }
        else{
            $this->insert_doc($uri);
        }
    }
    
    function update_doc($uri){
        $db_params = OpenContext_OCConfig::get_db_config();
        $db = new Zend_Db_Adapter_Pdo_Mysql($db_params);
	$db->getConnection();
        
        $where = array();
        $where[] = "itemURI = '".$uri."'";
        
        $data = array("label" => $this->label,
                      "base_URL" => $this->baseURL,
                      "json" => $this->json
		      );
        
        $db->update("omeka", $data, $where);
        $db->closeConnection();
    }
    
    function insert_doc($uri){
        $db_params = OpenContext_OCConfig::get_db_config();
        $db = new Zend_Db_Adapter_Pdo_Mysql($db_params);
	$db->getConnection();

        $data = array("itemURI" => $uri,
		      "label" => $this->label,
                      "base_URL" => $this->baseURL,
		      "created" => date("Y-m-d H:i:s"),
                      "json" => $this->json
		      );
        
        $db->insert("omeka", $data);
        $db->closeConnection();
    }
   
   
    //this checks to make sure the item comes from a white listed site.
    function white_list_check($input){
	
	$goodSite = false;
	$whiteList = array("http://alexandriaarchive.org/bonecommons/",
			   "http://microcommons.org/",
			   "http://omeka.ischool.berkeley.edu/"
			   );
	
	foreach($whiteList as $okSite){
	    if(stristr($input, $okSite) != false){
		$goodSite = true;
	    }
	}
	
	return $goodSite;
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
   
   
}
