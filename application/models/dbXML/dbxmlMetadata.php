<?php

class dbXML_dbxmlMetadata {
    
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
	}
    
    
    //do when you're only metadata for a location/object, media, document, person, etc. item
    public function getProjectMeta($projectUUID){
    
        $db = $this->db;
		  
		  $itemObj = New Project;
        $itemXMLstring = $itemObj->getItemXML($projectUUID);
		  if($itemXMLstring != false){
				$nameSpaceArray = $itemObj->nameSpaces();
				$itemXML = simplexml_load_string($itemXMLstring);
				foreach($nameSpaceArray as $prefix => $uri){
					 @$itemXML->registerXPathNamespace($prefix, $uri);
				}
				
				$this->projectName = $itemObj->label;
				$this->noPropMessage = "";
				
				$dcCreators = array();
				$dcCoverages = array();
				$dcSubjects = array();
				
				//get dc creator metadata
				foreach ($itemXML->xpath("//oc:metadata/dc:creator") as $xpathResult){
					 $personID = false;
					 foreach($xpathResult->xpath("@href") as $link){
						  $link  = (string)$link;
						  $personID = $this->URItoUUID($link);
					 }
					 
					 $xpathResult = (string)$xpathResult;
					 if(!$personID){
						  $personID = $this->getPersonID($projectUUID, $xpathResult);
					 }
					 $hash = sha1($projectUUID."creator".$personID);
					 $dcCreators[$hash] = array("value" => $xpathResult, "itemUUID" => $personID);
					 $this->projCreators = $dcCreators;
				}
				
				//get dc subject metadata
				foreach ($itemXML->xpath("//oc:metadata/dc:subject") as $xpathResult){
					 $xpathResult = (string)$xpathResult;
					 $hash = sha1($projectUUID."subject".$xpathResult);
					 $dcSubjects[$hash] = array("value" => $xpathResult);
					 $this->projSubjects = $dcSubjects;
				}
				//get dc coverage metadata
				foreach ($itemXML->xpath("//oc:metadata/dc:coverage") as $xpathResult){
					 $xpathResult = (string)$xpathResult;
					 $hash = sha1($projectUUID."coverage".$xpathResult);
					 $dcCoverages[$hash] = array("value" => $xpathResult);
					 $this->projCoverages = $dcCoverages;
				}
				
				foreach ($itemXML->xpath("//oc:metadata/oc:pub_date") as $xpathResult){
					$xpathResult = (string)$xpathResult;
					$this->projCreatedXML = $xpathResult;
					$this->projCreatedHuman = date("Y/m/d H:i:s", strtotime($this->projCreatedXML));
				}
		  
				foreach ($itemXML->xpath("//oc:metadata/oc:copyright_lic/oc:lic_name") as $xpathResult){
					$xpathResult = (string)$xpathResult;
					$this->licenseName = $xpathResult;
				}
				
				foreach ($itemXML->xpath("//oc:metadata/oc:copyright_lic/oc:lic_vers") as $xpathResult){
					$xpathResult = (string)$xpathResult;
					$this->licenseVersion = $xpathResult;
				}
				
				foreach ($itemXML->xpath("//oc:metadata/oc:copyright_lic/oc:lic_URI") as $xpathResult){
					$xpathResult = (string)$xpathResult;
					$this->licenseURI = $xpathResult;
				}
				
				foreach ($itemXML->xpath("//oc:metadata/oc:copyright_lic/oc:lic_icon_URI") as $xpathResult){
					$xpathResult = (string)$xpathResult;
					$this->licenseIconURI = $xpathResult;
				}
		  }
    } //end function
    
   
	 //extract the item UUID from a URI
	 public function URItoUUID($OCuri){
		  $expodeURI = explode("/", $OCuri);
		  $countParts = count($expodeURI);
		  return $expodeURI[$countParts - 1]; //return the last part
	 }
	
	
	
    //get names for people in a project
    public function getPersonID($projectUUID, $personName){
	
	
		  $db = $this->db;

		  $sql = "SELECT persons.person_uuid as uuid
		  FROM persons
		  WHERE persons.project_id = '".$projectUUID."'
		  AND persons.combined_name LIKE '%".$personName."%'	
		  LIMIT 1;
		  ";

		  $result = $db->fetchAll($sql, 2);
		  $personID = false;
				 if($result){
				$personID = $result[0]["uuid"];
		  }
		  
		  return $personID;
   
    } //end function
    
    
    
    
    
    
}  
