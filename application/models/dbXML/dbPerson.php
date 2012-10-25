<?php

class dbXML_dbPerson {
    
    public $itemUUID;
    public $label;
    public $projectUUID;
    public $sourceID;
    
    /*
    Person Item Specific Data
    */ 
    public $firstName; //first name
    public $lastName; //last name
    public $midInitial; //middle initial
    public $initials;
    public $affiliation; //institutional affiliation
    public $email; //email address
    
    public $personQueryVal;
    public $spaceCount;
    public $diaryCount;
    public $mediaCount;
    
    public $propertiesObj; //object for properties
    public $linksObj; // object for links
    public $metadataObj; //object for metadata
    
    public $dbName;
    public $dbPenelope;
    public $db;

    
    
    
    public function initialize($db = false){
        if(!$db){
            $db_params = OpenContext_OCConfig::get_db_config();
            $db = new Zend_Db_Adapter_Pdo_Mysql($db_params);
            $db->getConnection();
        }
        
        $this->db = $db;
	$this->firstName = false;
	$this->lastName = false;
	$this->midInitial = false;
	$this->initials = false;
	$this->affiliation = false;
	$this->email = false;
	
	$this->personQueryVal = false;
	$this->spaceCount = false;
	$this->diaryCount = false;
	$this->mediaCount = false;
	
    }
    
    public function getByID($id){
        
        $this->itemUUID = $id;
        $found = false;
        
        if($this->dbPenelope){
            $found = $this->pen_itemGet();
        }
        else{
            $found = $this->oc_itemGet();
        }
        
        return $found;
    }
    
    public function pen_itemGet(){
        $found = false;
        $db = $this->db;
        
        $sql = "SELECT *
        FROM persons
        WHERE uuid = '".$this->itemUUID."'
	";
        
        $result = $db->fetchAll($sql, 2);
        if($result){
            $this->projectUUID = $result[0]["project_id"];
            $this->sourceID = $result[0]["source_id"];
	    $this->label = trim($result[0]["combined_name"]);
	    $this->firstName = $result[0]["first_name"];
	    $this->lastName = $result[0]["last_name"];
	    $this->midInitial = $result[0]["mid_init"];
	    $this->initials = $result[0]["initials"];
	    $this->affiliation = $result[0]["org_name"];
	    $this->personQueryVal = urlencode($this->label);
        }
	else{
	    
	    $sql = "SELECT *
	    FROM users
	    WHERE uuid = '".$this->itemUUID."'
	    ";
	    
	    $result = $db->fetchAll($sql, 2);
	    if($result){
		
		$this->sourceID = $result[0]["source_id"];
		$this->label = trim($result[0]["combined_name"]);
		$this->firstName = $result[0]["first_name"];
		$this->lastName = $result[0]["last_name"];
		$this->midInitial = $result[0]["mid_init"];
		$this->initials = $result[0]["initials"];
		$this->affiliation = $result[0]["affiliation"];
		$this->email = $result[0]["email"];
		$this->personQueryVal = urlencode($this->label);
		
		$sql = "SELECT links.project_id
			FROM links
			WHERE (links.origin_uuid = '".$this->itemUUID."'
			OR
			links.targ_uuid = '".$this->itemUUID."' )
			
			UNION
			
			SELECT observe.project_id
			FROM observe
			WHERE observe.subject_uuid = '".$this->itemUUID."'
			
			";
		
		$resultB = $db->fetchAll($sql, 2);
		if($resultB){
		    $this->projectUUID = $resultB[0]["project_id"]; 
		}
	    }
	    
	}
        
        return $found;
    }
    
    public function oc_itemGet(){
        $found = false;
        $db = $this->db;
        
        $sql = "SELECT *
        FROM space
        WHERE uuid = '".$this->itemUUID."' ";
        
        $result = $db->fetchAll($sql, 2);
        if($result){
            $this->projectUUID = $result[0]["project_id"];
            $this->sourceID = $result[0]["source_id"];
            $this->contain_hash = $result[0]["contain_hash"];
            $this->classID = $result[0]["class_uuid"];
	    $this->label = $result[0]["space_label"];
            $this->classLabelGet($this->classID);
        }
        
        return $found;
    }
    
    
    
    public function getLinkCounts(){
	
	$db = $this->db;
	
	$sql = "SELECT links.origin_type, count(links.origin_uuid) as typeCount
	FROM links
	WHERE links.targ_uuid = '".$this->itemUUID."'
	GROUP BY links.origin_type, links.origin_uuid
	";
	
	$result = $db->fetchAll($sql, 2);
        if($result){
	    $this->spaceCount = 0;
	    $this->mediaCount = 0;
	    $this->diaryCount = 0;
	    
	    foreach($result as $row){
		
		$type = $row["origin_type"];
		$typeCount = $row["typeCount"];
		
		if(stristr($type, "location") || stristr($type, "space") || stristr($type, "spatial")){
		    $this->spaceCount  += $typeCount;
		}
		elseif(stristr($type, "media") || stristr($type, "resource") ){
		     $this->mediaCount += $typeCount;
		}
		elseif(stristr($type, "diary") || stristr($type, "document") ){
		     $this->diaryCount += $typeCount;
		}
	    }
	}
    }

    
    
    
}  
