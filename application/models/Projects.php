<?php


//this class interacts with the database for accessing projects items
class Projects {
    
   
    
    //get User data from database
    function DBgetProjects(){
        
        $output = false; //no user
        $db_params = OpenContext_OCConfig::get_db_config();
        $db = new Zend_Db_Adapter_Pdo_Mysql($db_params);
		  $db->getConnection();
		  $this->setUTFconnection($db);
	
        $sql = "SELECT projects.project_id AS id,
					 projects.root_path AS context_path,
					 projects.edit_status
					 projects.proj_archaeoml
                FROM projects
                WHERE projects.project_id != '0'
                AND projects.project_id != '2'
                ";
		
        $result = $db->fetchAll($sql, 2);
        if($result){
            return $result;
		  }
		  else{
				return $output;
		  }
		  
		  $db->closeConnection();

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
    
    private function setUTFconnection($db){
	    $sql = "SET collation_connection = utf8_general_ci;";
	    $db->query($sql, 2);
	    $sql = "SET NAMES utf8;";
	    $db->query($sql, 2);
    } 
    
    
    
    
}
