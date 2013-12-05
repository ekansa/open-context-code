<?php


//this class interacts with the database for accessing and changing Predicates
//predicates are defined in different projects
class Items_Predicate {
    
	 public $db;
	 
    /*
     General item metadata
    */
    public $uuid;
    public $projectUUID;
    public $sourceID;
    public $label;
	 public $created;
    public $updated;
    public $data;
	 
	 /*
	 Predicate specific metadata
	 */
	 public $archaeoMLtype;
	 public $dataType;
	 
	 /*
	 Administrative
	 */
	 public $repo; //repository, used for keeping data in Github 
    public $viewCount;
   
    
   
   
    //get data from database
    function getByUUID($uuid){
        
        $uuid = $this->security_check($uuid);
        $output = false; //not found
        
        $db = $this->startDB();
        
        $sql = 'SELECT *
                FROM predicates
                WHERE uuid = "'.$uuid.'"
                LIMIT 1';
		
        $result = $db->fetchAll($sql, 2);
        if($result){
            $output = $result[0];
				$this->uuid = $uuid;
				$this->projectUUID = $result[0]["project_id"];
				$this->sourceID = $result[0]["source_id"];
				$this->archaeoMLtype = $result[0]["archaeoMLtype"];
				$this->dataType = $result[0]["dataType"];
				$this->label = $result[0]["label"];
				$this->created = $result[0]["created"];
				$this->updated = $result[0]["updated"];
				//$this->getItemData($uuid);
		  }
        return $output;
    }
    
	 
	 function getByLabel($label, $project_ids = false, $archaeoMLtypes = false, $dataTypes = false){
		  
		  $db = $this->startDB();
		  
		  $conditions = " label = '$label' ";
		  $projConds = $this->makeORcondition($project_ids, "project_id");
		  if($projConds != false){
				$conditions .= " AND ($projConds) ";
		  }
		  $archConds = $this->makeORcondition($archaeoMLtypes, "archaeoMLtype");
		  if($archConds != false){
				$conditions .= " AND ($archConds) ";
		  }
		  $typeConds = $this->makeORcondition($dataTypes, "dataType");
		  if($typeConds != false){
				$conditions .= " AND ($typeConds) ";
		  }
		  
		  $output = false;
		  $sql = "SELECT * FROM predicates WHERE $conditions LIMIT 1; ";
		  
		  $result = $db->fetchAll($sql, 2);
        if($result){
            $output = $result[0];
				$this->uuid = $result[0]["uuid"];
				$this->projectUUID = $result[0]["project_id"];
				$this->sourceID = $result[0]["source_id"];
				$this->archaeoMLtype = $result[0]["archaeoMLtype"];
				$this->dataType = $result[0]["dataType"];
				$this->label = $result[0]["label"];
				$this->created = $result[0]["created"];
				$this->updated = $result[0]["updated"];
				//$this->getItemData($uuid);
		  }
        return $output;
	 }
	 
	 
	 function makeORcondition($valueArray, $field, $table = false){
		  
		  $allCond = false;
		  
		  if($valueArray != false){
				if(!is_array($valueArray)){
					 $valueArray = array(0 => $valueArray);
				}
				
				if(!$table){
					 $fieldPrefix = $field;
				}
				else{
					 $fieldPrefix = $table.".".$field;
				}
				
				foreach($valueArray as $value){
					 $actCond = "$fieldPrefix = '$value'";
					 if(!$allCond ){
						  $allCond  = $actCond;
					 }
					 else{
						  $allCond  .= " OR ".$actCond;
					 }
				}
		  }
		  return $allCond ;
	 }
	 
	 
	 
	 
	 //adds an item to the database
	 function createItem($data = false){
		 
		  $db = $this->startDB();
		  $success = false;
		  if(!is_array($data)){
				$data = array("uuid" => $this->uuid,
								  "project_id" => $this->projectUUID,
								  "source_id" => $this->sourceID,
								  "archaeoMLtype" => $this->archaeoMLtype,
								  "dataType" => $this->dataType,
								  "label" => $this->label,
								  "created" => date("Y-m-d")
								  );	
		  }
		  
		  try{
				$db->insert("predicates", $data);
		  } catch (Exception $e) {
				$success = false;
		  }
		  return $success;
	 }
	 
	 
	 
	 
	 
	 
	 
	 
	 
    //Gets compressed XML from database, decompresses, adds to object
	 function getItemData($uuid){
		  $db = $this->startDB();
		  $reposObj = new Repository;
		  $reposObj->db = $db;
		  $this->data = $reposObj->getItemData($uuid);
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
