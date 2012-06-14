<?php


class OpenContext_ProjectTables {
	
	//this function makes links between tables and projects. it returns an array
	//of project ids
	public static function assign_project_links($tableID, $metadata, $db){
		
		if(!isset($db)){
			$db_params = OpenContext_OCConfig::get_db_config();
			$db = new Zend_Db_Adapter_Pdo_Mysql($db_params);
				       
			$db->getConnection();
		}
		
		$projectIDs = array();
		
		
		foreach($metadata["projects"] as $actProject){
			$projectID = OpenContext_ProjectTables::find_project_id($actProject, $db);
			if($projectID != false){
				$projectIDs[] = $projectID;
				$hashID = md5($tableID."_".$projectID);
				$data = array("rel_id" => $hashID,
					      "table_id" => $tableID,
					      "item_id" => $projectID,
					      "item_type" => 'project',
					      "relation" => 'standard',
					      "rel_strength" => 1
					      );
				try{
					$n = $db->insert('table_links', $data);	
				}
				catch (Exception $e) {
					//do nothing but contemplate the meaning of existence
				}	
			}
			
		}
		
		return $projectIDs;
	}
	
	
	
	
	//this function makes links between tables and people. it returns an array
	//of person ids
	public static function assign_person_links($tableID, $metadata, $projectIDs, $db){
		
		if(!isset($db)){
			$db_params = OpenContext_OCConfig::get_db_config();
			$db = new Zend_Db_Adapter_Pdo_Mysql($db_params);
				       
			$db->getConnection();
		}
		
		$personIDs = array();
		$metadata["numFound"];
		
		
		foreach($metadata["person_links"] as $actPerson => $numLinked){
			$personID = OpenContext_ProjectTables::find_person_id($actPerson,  $projectIDs, $db);
			if($personID != false){
				
				$relStrength = $numLinked/$metadata["numFound"];
				
				$personIDs[] = $personID;
				$hashID = md5($tableID."_".$personID."_linked");
				$data = array("rel_id" => $hashID,
					      "table_id" => $tableID,
					      "item_id" => $personID,
					      "item_type" => 'person',
					      "relation" => 'linked',
					      "rel_strength" => $relStrength
					      );
				try{
					$n = $db->insert('table_links', $data);	
				}
				catch (Exception $e) {
					//do nothing but contemplate the meaning of existence
				}	
			}
			
		}
		foreach($metadata["creators"] as $actPerson => $numLinked){
			$personID = OpenContext_ProjectTables::find_person_id($actPerson,  $projectIDs, $db);
			if($personID != false){
				
				$relStrength = $numLinked/$metadata["numFound"];
				
				$personIDs[] = $personID;
				$hashID = md5($tableID."_".$personID."_creator");
				$data = array("rel_id" => $hashID,
					      "table_id" => $tableID,
					      "item_id" => $personID,
					      "item_type" => 'person',
					      "relation" => 'creator',
					      "rel_strength" => $relStrength
					      );
				try{
					$n = $db->insert('table_links', $data);	
				}
				catch (Exception $e) {
					//do nothing but contemplate the meaning of existence
				}	
			}
			
		}
		
		
		
		return $projectIDs;
	}
	
	
	
	
	
	
	
	
	
	//looks up a project id from a project name
	public static function find_project_id($projectName, $db){
		
		$projectID = false;
		
		$sql = "SELECT *
		FROM projects
		WHERE proj_name LIKE '%".$projectName."%'";
		
		$result = $db->fetchAll($sql, 2);
                if($result){
			$projectID = $result[0]["project_id"];
		}
		
		return $projectID;
	}
	
	
	//looks up a person id from a person's name
	public static function find_person_id($personName, $projectIDs, $db){
		
		//$projectIDs is an array of project ids used in the table
		//it's needed to narrow the search of names
		
		$personID = false;
		
		$whereTerm = "";
		$firstLoop = true;
		foreach($projectIDs as $projectID){
			if($firstLoop){
				$whereTerm = " project_id = '".$projectID."' ";
			}
			else{
				$whereTerm .= " OR project_id = '".$projectID."' ";
			}
		$firstLoop = false;
		}
		$whereTerm = "(".$whereTerm.")";
		
		$sql = "SELECT *
		FROM persons
		WHERE combined_name LIKE '%".$personName."%'
		AND $whereTerm ";
		
		$result = $db->fetchAll($sql, 2);
                if($result){
			$personID = $result[0]["person_uuid"];
		}
		
		return $personID;
	}
	
	
	
	
	//find tables related to a project
	public static function find_related_tables($projectUUID){
		
		$db_params = OpenContext_OCConfig::get_db_config();
                $db = new Zend_Db_Adapter_Pdo_Mysql($db_params);
                               
		$db->getConnection();
		$sql = "SELECT table_links.table_id, dataset.table_name
		FROM table_links
		JOIN dataset ON dataset.cache_id = table_links.table_id
		WHERE table_links.item_id = '".$projectUUID."'
		AND table_links.item_type = 'project'
		";
		
		$relatedTabs = false;
		$result = $db->fetchAll($sql, 2);
                if($result){
			$relatedTabs = array();
			foreach($result as $row){
				$tableID = $row["table_id"];
				$table_name = $row["table_name"];
				$relatedTabs[] = array("tableID" => $tableID, "tabName" => $table_name);
			}
		}
		
		$db->closeConnection();
		return $relatedTabs;
	}
	
	
	
	//short term hack
	public static function temp_find_related_tables($proj_name, $projectUUID = false){
		
		$db_params = OpenContext_OCConfig::get_db_config();
                $db = new Zend_Db_Adapter_Pdo_Mysql($db_params);
                               
		$db->getConnection();
		
		$sql = "SELECT *
		FROM dataset
		WHERE metadata LIKE '%".$proj_name."%'";
		
		$result = $db->fetchAll($sql, 2);
                 
		$relatedTabs = false;
		 
                if($result){
			$relatedTabs = array();
			foreach($result as $row){
				$tableID = $row["cache_id"];
				$table_name = $row["table_name"];
				$metadataJSON = $row["metadata"];
				$metaArray = Zend_Json::decode($metadataJSON);
				$metadata = $metaArray["meta"];
				foreach($metadata["projects"] as $fProject){
					if(stristr($proj_name, $fProject) || stristr($fProject, $proj_name)){
						$relatedTabs[] = array("tableID" => $tableID, "tabName" => $table_name);
						$projectIDs = OpenContext_ProjectTables::assign_project_links($tableID, $metadata, $db);
					}
				}
				$personIDs = OpenContext_ProjectTables::assign_person_links($tableID, $metadata, $projectIDs, $db);
				unset($metaArray);
			}
			unset($result);
			
			if(count($relatedTabs)<1){
				$relatedTabs = false;
			}
		}
		
		$db->closeConnection();
		return $relatedTabs;
	}
	
	
	
	
	
	
	
	
	
	
	
}//end class declaration

?>
