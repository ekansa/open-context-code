<?php

class dbXML_dbMetadata {
    
    public $projectUUID;
    
    public $projectName;
    public $subProjectID;
    public $subProjectName;
    public $noPropMessage;
    
    
    public $projCreatedXML; //created date for the project, XML
    public $projCreatedHuman; //human readable date format
    public $projCreators; //array of project-level creators, added to each item
    public $projSubjects; //array of dublin core subjects
    public $projCoverages; //array of dublin core coverage
    public $projFormats; //array of dublin core formats
    
    public $licenseURI; //URI to Creative Commons or similar license
    public $licenseName; //human readable name of license
    public $licenseVersion; //version number for license
    public $licenseIconURI; //uri to license icon
    
    
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
	$this->projectName = false;
	$this->subProjectID = false;
	$this->subProjectName = false;
	$this->noPropMessage = "This item has no additional descriptive properties.";
	
	
	$this->projCreatedXML = false;
	$this->projCreatedHuman = false;
	
        $this->projCreators = false;
	$this->projSubjects = false;
	$this->projCoverages = false;
	$this->projFormats = false;
	
	$this->licenseURI = false;
	$this->licenseName = false;
	$this->licenseVersion = false;
	$this->licenseIconURI = false;
	
    }
    
    public function getMetadata($projectUUID, $sourceID = false){
	
		$this->projectUUID = $projectUUID;
		$this->getProjectMeta($projectUUID);
		$this->getDCMetadata($projectUUID);
		
		/*
		if($this->projectUUID == "3"){
			$sourceID = "z_38_34c012771";
		}
		*/
		
		if($sourceID != false){
			$this->pen_getLicense($sourceID);
		}
	}
    
    
    //do when you're only metadata for a location/object, media, document, person, etc. item
    public function getProjectMeta($projectUUID){
    
        $db = $this->db;
	
	if($this->dbPenelope){
	    
	    $sql = "SELECT project_name,
		DATE_FORMAT(created, '%M %e, %Y') as HumanPubDate, 
		DATE_FORMAT(created, '%Y-%m-%d') as XMLPubDate,
		noprop_mes,
		'' AS license_id
	    FROM project_list
	    WHERE project_id = '$projectUUID'
	    LIMIT 1
		";
	}
	else{
	    
	    $sql = "SELECT proj_name AS project_name,
		DATE_FORMAT(accession, '%M %e, %Y') as HumanPubDate, 
		DATE_FORMAT(accession, '%Y-%m-%d') as XMLPubDate,
		noprop_mes,
		license_id
	    FROM projects
	    WHERE project_id = '$projectUUID'
	    LIMIT 1
		";
	    
	}
	
	//echo $sql;
	
        $result = $db->fetchAll($sql, 2);
        if($result){
	    $this->projectName = $result[0]["project_name"];
	    $this->projCreatedXML = $result[0]["XMLPubDate"];
	    $this->projCreatedHuman = $result[0]["HumanPubDate"];    
	    if(strlen($result[0]["noprop_mes"])>0){
		$this->noPropMessage = $result[0]["noprop_mes"];
	    }
	    
	    //get license information for open context 
	    if(!$this->dbPenelope){
			$this->oc_getLicense($result[0]["license_id"]);
	    }
		else{
			$this->pen_getLicense();
		}
	    
        }
        
        
    } //end function
    
    //get license information from penelope
    public function pen_getLicense($sourceID = false){
	
	$db = $this->db;
	if($this->dbPenelope){
		
		//first try, based on linking old licence lookup to the tableID
	    $sql = "SELECT licenses.license_name, licenses.license_url, licenses.lic_pict_url, licenses.license_vers
	    FROM file_summary
	    JOIN licenses ON licenses.license_id = file_summary.fk_license
	    WHERE file_summary.source_id = '$sourceID' ";
	
	    $result = $db->fetchAll($sql, 2);
		
	    if(!$result){
			
			//second try, based on linking new lincense lookup to the tableID
			
			$sql = "SELECT w_lu_creative_commons.NAME AS license_name,
			w_lu_creative_commons.LINK_DEED AS license_url,
			w_lu_creative_commons.IMAGE_LINK AS lic_pict_url,
			w_lu_creative_commons.VERSION AS license_vers
			FROM file_summary
			JOIN w_lu_creative_commons  ON w_lu_creative_commons.PK_LICENSE = file_summary.fk_license
			WHERE file_summary.source_id = '$sourceID' ";
			
			$result = $db->fetchAll($sql, 2);
			
			if(!$result){
				//third try, getting the most frequently used license from the project
				
				$sql = "SELECT w_lu_creative_commons.NAME AS license_name,
				w_lu_creative_commons.LINK_DEED AS license_url,
				w_lu_creative_commons.IMAGE_LINK AS lic_pict_url,
				w_lu_creative_commons.VERSION AS license_vers,
				COUNT( file_summary.source_id ) AS LicCount
				FROM file_summary
				JOIN w_lu_creative_commons  ON w_lu_creative_commons.PK_LICENSE = file_summary.fk_license
				WHERE file_summary.project_id = '".$this->projectUUID."'
				AND file_summary.fk_license > 0
				GROUP BY file_summary.fk_license
				ORDER BY COUNT(file_summary.source_id)
				";
				
			}
			
	    }
	    //echo $sql;
	    if($result){
			$this->licenseName = $result[0]["license_name"];
			$this->licenseURI = $result[0]["license_url"];
			$this->licenseIconURI = $result[0]["lic_pict_url"];
			$this->licenseVersion = $result[0]["license_vers"];
	    }
	}
	
    }
    
    //get license information from Open Context
    public function oc_getLicense($licenseID){
	
	$db = $this->db;
	if(!$this->dbPenelope){
	    $sql = "SELECT *
	    FROM licenses
	    WHERE license_id = $licenseID ";
	
	    $result = $db->fetchAll($sql, 2);
	    if($result){
		$this->licenseName = $result[0]["license_name"];
		$this->licenseURI = $result[0]["license_url"];
		$this->licenseIconURI = $result[0]["lic_pict_url"];
		$this->licenseVersion = $result[0]["license_vers"];
	    }
	}
    }
  
  
  
    //get dublin core metadata from the project
    public function getDCMetadata($projectUUID){
	$db = $this->db;
	if($this->dbPenelope){
	   
	   $sql = "SELECT dc_field, dc_value
	   FROM dcmeta_proj
	   WHERE project_id = '$projectUUID'";
	    
	}
	else{
	   $sql = "SELECT dc_field, dc_value
	   FROM dcmeta_proj
	   WHERE project_id = '$projectUUID'";
	}
	
	$result = $db->fetchAll($sql, 2);
        if($result){
	    
	    $creators = $this->projCreators;
	    if(!is_array($creators)){
		$creators = array();
	    }
	    $coverages = $this->projCoverages;
	    if(!is_array($coverages)){
		$coverages = array();
	    }
	    $subjects = $this->projSubjects;
	    if(!is_array($subjects)){
		$subjects = array();
	    }
	    
	    foreach($result as $row){
		
		$rawField = trim($row["dc_field"]);
		$DCvalue = trim($row["dc_value"]);
		
		if(stristr($rawField, "creator")){
		    $personUUID = $this->getPersonID($projectUUID, $DCvalue);
		    $hashName = sha1($DCvalue);
		    if(!array_key_exists($hashName, $creators)){
			$creators[$hashName] = array("value" => $DCvalue, "itemUUID" => $personUUID);
		    }
		    $this->projCreators = $creators;
		}
		
		if(stristr($rawField, "subject")){
		    $hashName = sha1($DCvalue);
		    if(!array_key_exists($hashName, $subjects)){
			$subjects[$hashName] = array("value" => $DCvalue);
		    }
		    $this->projSubjects = $subjects;
		}
		
		if(stristr($rawField, "coverage")){
		    $hashName = sha1($DCvalue);
		    if(!array_key_exists($hashName, $coverages)){
			$coverages[$hashName] = array("value" => $DCvalue);
		    }
		    $this->projCoverages = $coverages;
		}
		
	    }//end loop
        }
    
    }
  
  
  
    //get names for people in a project
    public function getPersonID($projectUUID, $personName){
	
	$db = $this->db;
	
	if($this->dbPenelope){
	    
	    $sql = "SELECT persons.uuid
		    FROM persons
		    WHERE persons.project_id = '".$projectUUID."'
		    AND persons.combined_name LIKE '%".$personName."%'	
		    LIMIT 1
		    
		    UNION
		    
		    SELECT users.uuid AS uuid
		    FROM users 
		    WHERE users.combined_name LIKE '%".$personName."%'	
		    LIMIT 1
		    
		    ";
	}
	else{
	    
	    $sql = "SELECT persons.uuid
	    FROM persons
	    WHERE persons.project_id = '".$projectUUID."'
	    AND persons.combined_name LIKE '%".$personName."%'	
	    LIMIT 1;
	    ";
	    
	}	
	
	$result = $db->fetchAll($sql, 2);
	$personID = false;
        if($result){
	    $personID = $result[0]["uuid"];
	}
	
	return $personID;
        
    } //end function
    
    
    
    
    
    
}  
