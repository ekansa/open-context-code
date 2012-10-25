<?php

class dbXML_dbLinks  {
    
   
    public $doRecip; //do reciprical links
   
    public $links;
    public $spaceLinks; //array of spatial items
    public $mediaLinks; //array of media items
    public $personLinks; //array of linked persons
    public $documentLinks; //array of linked diary / narrative / document items
    
    public $contributors; //array of Dublin Core contributor people
    public $creators; //array of Dublin Core creator people
    
    //useful data for first linked spatial item
    /*
    public $firstSpaceUUID; //uuid of the first space UUID
    public $f_geoLat;
    public $f_geoLon;
    public $f_geoGML;
    public $f_geoKML;
    public $f_geoSource;
    public $f_geoSourceName;
    public $f_chronoArray;
    */
    public $firstSpaceObj; //object for the first space item
    public $projRootSpaceObj; //object for the first project root item
    
    //possible roles for people linked to an observation
    //that get counted as a Dublin Core "creator"
    public $relToCreator = array("Principle Investigator",
				 "Directed by",
				 "Director",
				 "Editor",
				 "Co-Editor");
    
    //possible roles for people linked to an observation
    //that get counted as a Dublin Core "contributor"
    public $relToContributor = array("Observer",
				     "Creator",
				     "Principle Author / Analyst",
				     "Editor",
				     "Curator",
				     "o_Creator",
				     "Illustrator",
				     "Recorded by",
				     "Analyst",
				     "Photographed by",
				     "Catalogued by",
				     "Excavated by",
					 "Area supervisor"
					 );
    
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
        $this->doRecip = false;
		  $this->links = false;
		  $this->spaceLinks = false;
		  $this->mediaLinks = false;
		  $this->personLinks = false;
		  $this->firstSpaceObj = false;
		  $this->projRootSpaceObj = false;
		  $this->contributors = false;
		  $this->creators = false;
		  $this->documentLinks = false;
    }
    
    public function getLinks($id, $obsNumbers = false){
        
		  if(!is_array($obsNumbers)){
				$obsNumbers = array(0 => false);
		  }
	
		  foreach($obsNumbers as $obs){
				
				if(strlen($obs)<1){
					 $obs = false;
				} 
				$this->getSpaceFromOrigin($id, $obs);
				$this->getMediaFromOrigin($id, $obs);
				$this->getPersonsFromOrigin($id, $obs);
				$this->getDocumentsFromOrigin($id, $obs);
		  }
		  
		  $this->getMediaFromOrigin($id); //now with no observation limits
		  //$this->getDocumentsFromOrigin($id);
    }
    
    
    
    public function getPersonsFromOrigin($id, $obs = false){
        $found = false;
        $db = $this->db;

		  if(!$obs){
				$obsTerm = "";
		  }
		  else{
				$obsTerm = " AND links.origin_obs = $obs ";
		  }
		  
		  $sql = "SELECT links.targ_uuid, links.link_type, links.targ_obs,
		  persons.combined_name, persons.last_name, persons.first_name, persons.mid_init
		  FROM links 
		  JOIN persons ON persons.person_uuid = links.targ_uuid
		  WHERE links.origin_uuid = '".$id."'
		  AND (links.targ_type LIKE '%person%'  )
		  $obsTerm

		  ";

		  //echo $sql;
        $result = $db->fetchAll($sql, 2);
		  
        if($result){
	    
				if(!$obs){
					 $obsNum = 1;
				}
				else{
					 $obsNum = $obs;
				}
	    
            $oldLinks = $this->links;
				if(!is_array($oldLinks)){
					 $oldLinks = array();
					 $oldLinks[$obsNum] = array();
				}
				else{
					 if(!array_key_exists($obsNum, $oldLinks)){
						  $oldLinks[$obsNum] = array();
					 }
				}
				$personLinks = $this->personLinks;
				if(!is_array($personLinks)){
					 $personLinks = array();
					 $personLinks[$obsNum] = array();
				}
				else{
					 if(!array_key_exists($obsNum, $personLinks)){
						  $personLinks[$obsNum] = array();
					 }
				}
				$contributors = $this->contributors;
				if(!is_array($contributors)){
					 $contributors = array();
				}
				$creators = $this->creators;
				if(!is_array($personLinks)){
					 $creators = array();
				}
				
				foreach($result as $row){
		
					 $linkedUUID = $row["targ_uuid"];
					 $linkType = $row["link_type"];
					 $linkedName = $row["combined_name"];
					 
					 $citationType = false;
					 foreach($this->relToContributor as $contribType){
						  if(stristr($linkType, $contribType)){
								$citationType = "contributor";
								if(!array_key_exists($linkedUUID, $contributors)){
									 $contributors[$linkedUUID] = $linkedName;
									 $this->contributors = $contributors;
								}
						  }
					 }
					 foreach($this->relToCreator as $createType){
						  if(stristr($linkType, $createType)){
								$citationType = "creator";
								if(is_array($creators)){
									 if(!array_key_exists($linkedUUID, $creators)){
										  $creators[$linkedUUID] = $linkedName;
										  $this->creators = $creators;
									 }
								}
						  }
					 }
		
					 $actPersonLinkArray = array("linkedName" => $linkedName,
									 "linkType" => $linkType,
									 "linkedUUID" => $linkedUUID,
									 "cite" => $citationType
									 );
		
					 $obsHash = sha1($obs.$linkedUUID.$linkType);
					 if(!array_key_exists($obsHash, $oldLinks[$obsNum])){
						  $oldLinks[$obsNum][$obsHash] = array("type" => "person",
									 "linkType" => $linkType,
									 "linkedUUID" => $linkedUUID);
					 }
					 if(!array_key_exists($obsHash, $personLinks[$obsNum])){
						  $personLinks[$obsNum][$obsHash] = $actPersonLinkArray;
					 }
				
				}//end loop
	    
				$this->links = $oldLinks;
				$this->personLinks = $personLinks;
        }
        
        return $found;
    } //end function
    
    
    
    public function getDocumentsFromOrigin($id, $obs = false){
        $found = false;
        $db = $this->db;
        
	
		 
		  if(!$obs){
				$obsTerm = "";
		  }
		  else{
				$obsTerm = " AND links.origin_obs = $obs ";
		  }
		  
		  $sql = "SELECT links.targ_uuid, links.link_type, links.targ_obs,
		  diary.diary_label
		  FROM links 
		  JOIN diary ON diary.uuid = links.targ_uuid
		  WHERE links.origin_uuid = '".$id."'
		  AND (links.targ_type LIKE '%diary%' OR links.targ_type LIKE '%narrative%' OR links.targ_type LIKE '%document%' )
		  $obsTerm
		  ORDER BY diary.diary_label	
		  ";
		 
		  
		  //echo $sql;
	
        $result = $db->fetchAll($sql, 2);
        if($result){
	    
				if(!$obs){
					 $obsNum = 1;
				}
				else{
					 $obsNum = $obs;
				}
	    
            $oldLinks = $this->links;
				if(!is_array($oldLinks)){
					 $oldLinks = array();
					 $oldLinks[$obsNum] = array();
				}
				else{
					 if(!array_key_exists($obsNum, $oldLinks)){
						  $oldLinks[$obsNum] = array();
					 }
				}
				$documentLinks = $this->documentLinks;
				if(!is_array($documentLinks)){
					 $documentLinks = array();
					 $documentLinks[$obsNum] = array();
				}
				else{
					 if(!array_key_exists($obsNum, $documentLinks)){
						  $documentLinks[$obsNum] = array();
					 }
				}
	    
				foreach($result as $row){
			 
					 $linkedUUID = $row["targ_uuid"];
					 $linkType = $row["link_type"];
					 $linkedName = $row["diary_label"];
					 
					 $actDocLinkArray = array("linkedName" => $linkedName,
									 "linkType" => $linkType,
									 "linkedUUID" => $linkedUUID);
					 
					 $obsHash = sha1($obs.$linkedUUID.$linkType);
					 if(!array_key_exists($obsHash, $oldLinks[$obsNum])){
						  $oldLinks[$obsNum][$obsHash] = array("type" => "resource",
									 "linkType" => $linkType,
									 "linkedUUID" => $linkedUUID);
					 }
					 if(!array_key_exists($obsHash, $documentLinks[$obsNum])){
						  $documentLinks[$obsNum][$obsHash] = $actDocLinkArray;
					 }
			 
				}//end loop
	    
				$this->links = $oldLinks;
				$this->documentLinks = $documentLinks;
        }
        
        return $found;
    } //end function
    
    
    
    
    public function getMediaFromOrigin($id, $obs = false){
        $found = false;
        $db = $this->db;
        
		  if(!$obs){
				$obsTerm = "";
		  }
		  else{
				$obsTerm = " AND links.origin_obs = $obs ";
		  }
		  
		  $sql = "SELECT links.targ_uuid, links.link_type, links.targ_obs,
		  resource.res_label, resource.res_archml_type, resource.mime_type,
		  resource.ia_thumb, resource.ia_preview, resource.ia_fullfile, labeling_options.labelVarUUID
		  FROM links 
		  JOIN resource ON resource.uuid = links.targ_uuid
		  LEFT JOIN labeling_options ON (resource.source_id = labeling_options.source_id
					 AND resource.project_id = labeling_options.project_id
					 AND labeling_options.doc_type LIKE '%media%'
					 AND labeling_options.relType = 'link')
		  
		  WHERE links.origin_uuid = '".$id."'
		  AND (links.targ_type LIKE '%media%' OR links.targ_type LIKE '%resource%' )
		  $obsTerm
		  ORDER BY resource.res_number, resource.res_label	
		  ";
		  
		  $result = $db->fetchAll($sql, 2);
		 
        if($result){
	    
				if(!$obs){
					 $obsNum = 1;
				}
				else{
					 $obsNum = $obs;
				}
	    
				$oldLinks = $this->links;
				if(!is_array($oldLinks)){
					 $oldLinks = array();
					 $oldLinks[$obsNum] = array();
				}
				else{
					 if(!array_key_exists($obsNum, $oldLinks)){
						  $oldLinks[$obsNum] = array();
					 }
				}
	    
				$mediaLinks = $this->mediaLinks;
				if(!is_array($mediaLinks)){
					 $mediaLinks = array();
					 $mediaLinks[$obsNum] = array();
				}
				else{
					 if(!array_key_exists($obsNum, $mediaLinks)){
						  $mediaLinks[$obsNum] = array();
					 }
				}
	    
				foreach($result as $row){
		
					 $linkedUUID = $row["targ_uuid"];
					 $linkType = $row["link_type"];
					 $linkedName = $row["res_label"];
					 $archaeoMLtype = $row["res_archml_type"];
					 $thumbURI = $row["ia_thumb"];
					 $labelVarUUID = $row["labelVarUUID"];
					 
					 $actMediaLinkArray = array("linkedName" => $linkedName,
									 "linkType" => $linkType,
									 "linkedUUID" => $linkedUUID,
									 "archaeoMLtype" => $archaeoMLtype,
									 "thumbURI" => $thumbURI);
					 
					 if(strlen($labelVarUUID)>1){
						  $sql = "SELECT val_tab.val_text
						  FROM observe
						  JOIN properties ON observe.property_uuid = properties.property_uuid
						  JOIN val_tab ON properties.value_uuid = val_tab.value_uuid
						  WHERE observe.subject_uuid = '$linkedUUID'
						  AND properties.variable_uuid = '$labelVarUUID'
						  LIMIT 1;
						  ";
						  
						  $resultB = $db->fetchAll($sql, 2);
						  if($resultB){
								$actMediaLinkArray["descriptor"] = $resultB[0]["val_text"];
						  }
					 }
		
					 $obsHash = sha1($obsNum.$linkedUUID.$linkType);
					 if(!array_key_exists($obsHash, $oldLinks[$obsNum])){
						  $oldLinks[$obsNum][$obsHash] = array("type" => "resource",
									 "linkType" => $linkType,
									 "linkedUUID" => $linkedUUID);
					 }
					 if(!array_key_exists($obsHash, $mediaLinks[$obsNum])){
						  $mediaLinks[$obsNum][$obsHash] = $actMediaLinkArray;
					 }
					 
				}//end loop
					  
				$this->links = $oldLinks;
				$this->mediaLinks = $mediaLinks;
		  }
			
		  return $found;
    } //end function
    
    
    
    
    
    public function getSpaceFromOrigin($id, $obs = false){
        $found = false;
        $db = $this->db;
        
		  if(!$obs){
				$obsTerm = "";
		  }
		  else{
				$obsTerm = " AND links.origin_obs = $obs ";
		  }
		  
		  $sql = "SELECT links.targ_uuid, links.link_type, links.targ_obs, space.class_uuid, space.space_label
		  FROM links 
		  JOIN space ON space.uuid = links.targ_uuid
		  WHERE links.origin_uuid = '".$id."'
		  AND (links.targ_type LIKE '%locations%' OR links.targ_type LIKE '%spatial%' OR links.targ_type LIKE '%space%')
		  $obsTerm
		  ORDER BY space.class_uuid, space.label_sort, space.space_label
		  ";
		  
		  $result = $db->fetchAll($sql, 2);
		  
		  if($result){
		  
				if(!$obs){
					 $obsNum = 1;
				}
				else{
					 $obsNum = $obs;
				}
				
				$oldLinks = $this->links;
				if(!is_array($oldLinks)){
					 $oldLinks = array();
					 $oldLinks[$obsNum] = array();
				}
				else{
					 if(!array_key_exists($obsNum, $oldLinks)){
						  $oldLinks[$obsNum] = array();
					 }
				}
				$spaceLinks = $this->spaceLinks;
				if(!is_array($spaceLinks)){
					 $spaceLinks = array();
					 $spaceLinks[$obsNum] = array();
				}
				else{
					 if(!array_key_exists($obsNum, $spaceLinks)){
						  $spaceLinks[$obsNum] = array();
					 }
				}
		  
				foreach($result as $row){
		 
					 $linkedUUID = $row["targ_uuid"];
					 $linkType = $row["link_type"];
					 $linkedName = $row["space_label"];
					 $classUUID = $row["class_uuid"];
					 
					 $spaceObj = $this->getSpaceData($linkedUUID);
					 
					 $fullContain = array();
					 $containArray = $spaceObj->containment;
					 foreach($containArray as $treeKey => $containArray){
						  foreach($containArray as $containItem){
								$containObj = new dbXML_dbSpace;
								$containObj->initialize($db);
								$containObj->dbPenelope = true;
								$containObj->getByID($containItem);
								$fullContain[$treeKey][] = array("itemUUID" => $containItem,
												 "label" => $containObj->label,
												 "className" => $containObj->className,
												 "smallClassIcon" => $containObj->smallClassIcon
												 );
						  }
					 }
		 
					 $actSpLinkArray = array("linkedName" => $linkedName,
									 "linkType" => $linkType,
									 "linkedUUID" => $linkedUUID,
									 "classID" => $classUUID,
									 "className" => $spaceObj->className,
									 "largeClassIcon" => $spaceObj->largeClassIcon,
									 "smallClassIcon" => $spaceObj->smallClassIcon,
									 "containment" => $fullContain
									 );
		 
					 if(!$this->firstSpaceObj){
						  $this->firstSpaceObj = $actSpLinkArray;
					 }
		 
					 unset($spaceObj);
					 //$actSpLinkArray = $this->classLinkLabelGet($classUUID, $actSpLinkArray);
		 
					 $obsHash = sha1($obs.$linkedUUID.$linkType);
					 if(!array_key_exists($obsHash, $oldLinks[$obsNum])){
						  $oldLinks[$obsNum][$obsHash] = array("type" => "spatialUnit",
									 "linkType" => $linkType,
									 "linkedUUID" => $linkedUUID);
					 }
					 if(!array_key_exists($obsHash, $spaceLinks[$obsNum])){
						  $spaceLinks[$obsNum][$obsHash] = $actSpLinkArray;
					 }
		 
				}//end loop
		  
				$this->links = $oldLinks;
				$this->spaceLinks = $spaceLinks;
		  }
        return $found;
    } //end function
    
    
    public function getSpaceFromTarg($id, $obs = false){
        $found = false;
        $db = $this->db;

		  if(!$obs){
				$obsTerm = "";
		  }
		  else{
				$obsTerm = " AND links.targ_obs = $obs ";
		  }
		  
		  $sql = "SELECT links.origin_uuid, links.link_type, links.origin_obs, space.class_uuid, space.space_label
		  FROM links 
		  JOIN space ON space.uuid = links.origin_uuid
		  WHERE links.targ_uuid = '".$id."'
		  AND (links.origin_type LIKE '%locations%' OR links.origin_type LIKE '%spatial%' OR links.origin_type LIKE '%space%')
		  $obsTerm
		  ORDER BY space.class_uuid, space.label_sort,  space.space_label
		  ";
	
	
        $result = $db->fetchAll($sql, 2);
        if($result){
	    
				if(!$obs){
					 $obsNum = 1;
				}
				else{
					 $obsNum = $obs;
				}
	    
            $oldLinks = $this->links;
				if(!is_array($oldLinks)){
					 $oldLinks = array();
					 $oldLinks[$obsNum] = array();
				}
				else{
					 if(!array_key_exists($obsNum, $oldLinks)){
						  $oldLinks[$obsNum] = array();
					 }
				}
				$spaceLinks = $this->spaceLinks;
				if(!is_array($spaceLinks)){
					 $spaceLinks = array();
					 $spaceLinks[$obsNum] = array();
				}
				else{
					 if(!array_key_exists($obsNum, $spaceLinks)){
						  $spaceLinks[$obsNum] = array();
					 }
				}
	    
				foreach($result as $row){
			  
					 $linkedUUID = $row["origin_uuid"];
					 $linkType = $row["link_type"];
					 $linkedName = $row["space_label"];
					 $classUUID = $row["class_uuid"];
					 $spaceObj = $this->getSpaceData($linkedUUID);
					 
					 $fullContain = array();
					 $containArray = $spaceObj->containment;
					 foreach($containArray as $treeKey => $containArray){
						  foreach($containArray as $containItem){
								$containObj = new dbXML_dbSpace;
								$containObj->initialize($db);
								$containObj->dbPenelope = true;
								$containObj->getByID($containItem);
								$fullContain[$treeKey][] = array("itemUUID" => $containItem,
												 "label" => $containObj->label,
												 "className" => $containObj->className,
												 "smallClassIcon" => $containObj->smallClassIcon
												 );
								 }
						  }
		
						  $actSpLinkArray = array("linkedName" => $linkedName,
										  "linkType" => $linkType,
										  "linkedUUID" => $linkedUUID,
										  "classID" => $classUUID,
										  "className" => $spaceObj->className,
										  "largeClassIcon" => $spaceObj->largeClassIcon,
										  "smallClassIcon" => $spaceObj->smallClassIcon,
										  "containment" => $fullContain
										  );
		
						  if(!$this->firstSpaceObj){
								$this->firstSpaceObj = $actSpLinkArray;
						  }
		
		
						  unset($spaceObj);
						  //$actSpLinkArray = $this->classLinkLabelGet($classUUID, $actSpLinkArray);
				  
						  $obsHash = sha1($obs.$linkedUUID.$linkType);
						  if(!array_key_exists($obsHash, $oldLinks[$obsNum])){
								$oldLinks[$obsNum][$obsHash] = array("type" => "spatialUnit",
										  "linkType" => $linkType,
										  "linkedUUID" => $linkedUUID);
						  }
						  if(!array_key_exists($obsHash, $spaceLinks[$obsNum])){
								$spaceLinks[$obsNum][$obsHash] = $actSpLinkArray;
						  }
		
					 }//end loop
	    
				$this->links = $oldLinks;
				$this->spaceLinks = $spaceLinks;
        }
        
        return $found;
    }//end function
    
    
    public function makeProjRootLinks($rootIDs){
		  $db = $this->db;
		  
		  if($this->dbPenelope && is_array($rootIDs)){
	    
				$obsNum = 1; //main obs
				$oldLinks = $this->links;
				if(!is_array($oldLinks)){
					  $oldLinks = array();
					  $oldLinks[$obsNum] = array();
				}
				else{
					  if(!array_key_exists($obsNum, $oldLinks)){
							$oldLinks[$obsNum] = array();
					  }
				}
				$spaceLinks = $this->spaceLinks;
				if(!is_array($spaceLinks)){
					 $spaceLinks = array();
					 $spaceLinks[$obsNum] = array();
				}
				else{
					 if(!array_key_exists($obsNum, $spaceLinks)){
						  $spaceLinks[$obsNum] = array();
					 }
				}
	    
				foreach($rootIDs as $rootID){
			  
					 $sql = "SELECT space.class_uuid, space.space_label
					 FROM space
					 WHERE space.uuid = '".$rootID."'
					 ORDER BY space.label_sort
					 ";
					 
					 $result = $db->fetchAll($sql, 2);
					 if($result){
						  $obs = 1; //root main obs
						  $row = $result[0];
						  $linkedUUID = $rootID;
						  $linkType = "project root";
						  $linkedName = $row["space_label"];
						  $classUUID = $row["class_uuid"];
						  $spaceObj = $this->getSpaceData($linkedUUID);
						  
						  $fullContain = array();
						  $containArray = $spaceObj->containment;
						  foreach($containArray as $treeKey => $containArray){
								foreach($containArray as $containItem){
									 $containObj = new dbXML_dbSpace;
									 $containObj->initialize($db);
									 $containObj->dbPenelope = true;
									 $containObj->getByID($containItem);
									 $fullContain[$treeKey][] = array("itemUUID" => $containItem,
												"label" => $containObj->label,
												"className" => $containObj->className,
												"smallClassIcon" => $containObj->smallClassIcon
												);
								}
						  }
					
						  $actSpLinkArray = array("linkedName" => $linkedName,
									 "linkType" => $linkType,
									 "linkedUUID" => $linkedUUID,
									 "classID" => $classUUID,
									 "className" => $spaceObj->className,
									 "largeClassIcon" => $spaceObj->largeClassIcon,
									 "smallClassIcon" => $spaceObj->smallClassIcon,
									 "containment" => $fullContain
									 );
					
						  //$actSpLinkArray = $this->classLinkLabelGet($classUUID, $actSpLinkArray);
						  if(!$this->projRootSpaceObj){
								$this->projRootSpaceObj = $actSpLinkArray;
						  }
					
						  $obsHash = sha1($obs.$linkedUUID.$linkType);
						  if(!array_key_exists($obsHash, $oldLinks[$obsNum])){
								$oldLinks[$obsNum][$obsHash] = array("type" => "spatialUnit",
										  "linkType" => $linkType,
										  "linkedUUID" => $linkedUUID);
						  }
						  if(!array_key_exists($obsHash, $spaceLinks[$obsNum])){
								$spaceLinks[$obsNum][$obsHash] = $actSpLinkArray;
						  }
					 }
				}//end loop
				
				$this->links = $oldLinks;
				$this->spaceLinks = $spaceLinks;
		  }
	
    }//end funciton
    
    
    public function makeProjPersonLinks($projectUUID){
		  $db = $this->db;
		  
		  if($this->dbPenelope){
				
				$sql = "SELECT DISTINCT links.targ_uuid, 
				persons.combined_name, persons.last_name, persons.first_name, persons.mid_init
				FROM links 
				JOIN persons ON persons.uuid = links.targ_uuid
				WHERE links.project_id = '".$projectUUID."'
				AND links.targ_type LIKE 'person'
				
				UNION
				
				SELECT DISTINCT links.targ_uuid, 
				users.combined_name, users.last_name, users.first_name, users.mid_init
				FROM links 
				JOIN users ON users.uuid = links.targ_uuid
				WHERE links.project_id = '".$projectUUID."'
				AND links.targ_type LIKE 'person'
				
				";
				
				$result = $db->fetchAll($sql, 2);
				if($result){
					 $obsNum = 1; //main obs
					 $oldLinks = $this->links;
					 if(!is_array($oldLinks)){
						  $oldLinks = array();
						  $oldLinks[$obsNum] = array();
					 }
					 else{
						  if(!array_key_exists($obsNum, $oldLinks)){
								$oldLinks[$obsNum] = array();
						  }
					 }
			  
					 $personLinks = $this->personLinks;
					 if(!is_array($personLinks)){
						  $personLinks = array();
						  $personLinks[$obsNum] = array();
					 }
					 else{
						  if(!array_key_exists($obsNum, $personLinks)){
								$personLinks[$obsNum] = array();
						  }
					 }
			  
					 foreach($result as $row){
						  
						  $linkedUUID = $row["targ_uuid"];
						  $linkType = "Project Participant";
						  $citationType = false;
						  $linkedName = $row["combined_name"];
						  
						  $actPersonLinkArray = array("linkedName" => $linkedName,
									"linkType" => $linkType,
									"linkedUUID" => $linkedUUID,
									"cite" => $citationType
									);
						  
						  $obsHash = sha1($obsNum.$linkedUUID.$linkType);
						  if(!array_key_exists($obsHash, $oldLinks[$obsNum])){
								$oldLinks[$obsNum][$obsHash] = array("type" => "person",
										  "linkType" => $linkType,
										  "linkedUUID" => $linkedUUID);
						  }
						  if(!array_key_exists($obsHash, $personLinks[$obsNum])){
								$personLinks[$obsNum][$obsHash] = $actPersonLinkArray;
						  }
						  
					 }//end loop
			  
					 $this->links = $oldLinks;
					 $this->personLinks = $personLinks;
				}
		  }
	
    }//end funciton
    
    
    
    
    //same for both penelope and open context
    public function classLinkLabelGet($classID, $linkArray = false){
        $db = $this->db;
        
        $sql = "SELECT *
        FROM sp_classes
        WHERE class_uuid = '".$classID."'
        OR class_label = '".$classID."'
        ";
        
        $result = $db->fetchAll($sql, 2);
        if($result){
				if(is_array($linkArray)){
					 $linkArray["className"] = $result[0]["class_label"];
					 $linkArray["largeClassIcon"] = $result[0]["class_icon"];
					 $linkArray["smallClassIcon"] = $result[0]["sm_class_icon"];
					 return $linkArray;
				}
				else{
					 return $result[0]["class_label"];
				}
		  }
		  else{
				return false;
		  }
    }//end function
    
    
    public function getSpaceData($spaceUUID){
		  $spaceObj = new dbXML_dbSpace;
		  $spaceObj->initialize($this->db);
		  $spaceObj->dbPenelope = $this->dbPenelope;
		  $spaceObj->getByID($spaceUUID);
		  $spaceObj->getParents();
		  $spaceObj->getGeo();
		  $spaceObj->getChrono();
		  
		  return $spaceObj;
    }
    
}  
