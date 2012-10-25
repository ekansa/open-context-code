<?php

class dbXML_dbProject {
    
    public $itemUUID;
    public $projectUUID;
    
    public $projectName;
    public $subProjectID;
    public $subProjectName;
    public $noPropMessage;
    
    public $projectQueryVal;
    public $rootPath;
    public $spaceCount;
    public $diaryCount;
    public $mediaCount;
    
    public $projRootItems; // array of project root items
    public $projectLat;
    public $projectLon;
    public $projectGML;
    public $projectKML;
    
    public $projShortDes; //short project description
    public $projShortDesXMLok; //XMLOk
    public $projAbstract; //long project description
    public $projAbstractXMLok; //XMLOk
    
    public $projCreatedXML; //created date for the project, XML
    public $projCreatedHuman; //human readable date format
    
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
	$this->projectName = false;
	$this->subProjectID = false;
	$this->subProjectName = false;
	$this->noPropMessage = "This item has no additional descriptive properties.";
	
	$this->projectQueryVal = false;
	$this->rootPath = false;
	$this->spaceCount = false;
	$this->diaryCount = false;
	$this->mediaCount = false;
	
	$this->projRootItems = false;
	$this->projectLat = false;
	$this->projectLon = false;
	$this->projectGML = false;
	$this->projectKML = false;
    
	$this->projShortDes = false; //short project description
	$this->projAbstract = false; //long project description
	
	$this->projecRootItems = false;
	
	$this->projCreatedXML = false;
	$this->projCreatedHuman = false;
	
    }
    
    public function getByID($projectUUID){
	$this->itemUUID = $projectUUID;
	$this->projectUUID = $projectUUID;
	$this->getProjectData();
	$this->getProjectGeo();
	
    }//end function
    
    
    //do when you're only metadata for a location/object, media, document, person, etc. item
    public function getProjectData(){
    
        $db = $this->db;
	$projectUUID = $this->projectUUID;
	
	if($this->dbPenelope){
	    
	    $sql = "SELECT project_name,
		DATE_FORMAT(created, '%M %e, %Y') as HumanPubDate, 
		DATE_FORMAT(created, '%Y-%m-%d') as XMLPubDate,
		abstract,
		short_des,
		parcontext_name
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
		long_des AS abstract
		short_des,
		'' AS parcontext_name
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
	    
	    $shortDes = $result[0]["short_des"];
	    $xmlTest = "<div>".$shortDes."</div>";
	    @$xml = simplexml_load_string($xmlTest);
	    if($xml){
		$this->projShortDesXMLok = true;
	    }
	    else{
		$this->projShortDesXMLok = false;
	    }
	    
	    $abstract = $result[0]["abstract"];
	    $xmlTest = "<div>".$abstract."</div>";
	    @$xml = simplexml_load_string($xmlTest);
	    if($xml){
		$this->projAbstractXMLok = true;
	    }
	    else{
		$this->projAbstractXMLok = false;
	    }
	    
	    $parentPath = $result[0]["parcontext_name"];	
	    $parentPath = urlencode($parentPath);
	    $parentPath = "/".$parentPath;
	    
	    $this->rootPath = $parentPath;
	    $this->projShortDes = $shortDes;
	    $this->projAbstract = $abstract;
        }
        
        
    } //end function
    
    
    
    public function getProjectGeo(){
	$db = $this->db;
	
	if($this->dbPenelope){
	    $sql =
	    
	    "SELECT DISTINCT geo_space.uuid,
	    geo_space.latitude,
	    geo_space.longitude,
	    geo_space.gml_data
	    FROM geo_space
	    WHERE geo_space.project_id = '".$this->projectUUID."'; ";
	    
	    $result = $db->fetchAll($sql, 2);
	    if($result){
		$root_uuids = array();
		$geo_lat = array();
		$geo_lon = array();
		$num_geo = count($result);
		foreach($result as $row){
	    
		    if(!in_array($row["uuid"], $root_uuids)){
			$root_uuids[] = $row["uuid"];
			$this->projRootItems = $root_uuids;
		    }
		    
		    $geo_lat[] = $row["latitude"];
		    $geo_lon[] = $row["longitude"];
		    $this->projectLat = $row["latitude"]+0;
		    $this->projectLon = $row["longitude"]+0;
		}//end loop
	    
		$mean_lat = array_sum($geo_lat)/$num_geo;
		$mean_lon = array_sum($geo_lon)/$num_geo;
	    
		if($num_geo>1){
		    $lat_offset = (max($geo_lat) - min($geo_lat))*.1;
		    $lon_offset = (max($geo_lon) - min($geo_lon))*.1;
			    
		    $proj_polygon = (min($geo_lat)-$lat_offset)." ".(min($geo_lon)-$lon_offset)." ";
		    $proj_polygon .= (min($geo_lat)-$lat_offset)." ".(max($geo_lon)+$lon_offset)." ";
		    $proj_polygon .= (max($geo_lat)+$lat_offset)." ".(max($geo_lon)+$lon_offset)." ";
		    $proj_polygon .= (max($geo_lat)+$lat_offset)." ".(min($geo_lon)-$lon_offset);
		    $this->projectGML = $proj_polygon;
		    $this->projectLat = $mean_lat;
		    $this->projectLon = $mean_lon;
		}
	    }//end case with results
		
	}//end penelope
	
    }//end funciton
    
    
    
    
    
    
}  
