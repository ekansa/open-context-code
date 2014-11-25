<?php

class AllIdentifiers
{
    public $latestDate;
    public $earliestDate;
    public $Records;
    const default_license = "http://creativecommons.org/licenses/by/4.0/";
	
	public $frontendOptions = array(
					  'lifetime' => 720000, // cache lifetime, measured in seconds, 7200 = 2 hours
					  'automatic_serialization' => true
			);
					  
	public  $backendOptions = array(
				 'cache_dir' => './time_cache/' // Directory where to put the cache files
			);
	
	
    function get_projects_categories($set, $earlyDate, $lateDate, $specific_id = false){
        $jsonMetaMappings = array("subjects" => "subject",
								  "coverages" => "coverage",
								  "creators" => "creator",
								  "created" => "date");
        $Records = array();
        
        if(strlen($set)>0){
            $set = str_replace(" images", "", $set);
            $set = str_replace(" analytic field data", "", $set);
        }
        else{
            $set = false;
        }
        
        $host = OpenContext_OCConfig::get_host_config();
        
        $allDates = array();
        $db_params = OpenContext_OCConfig::get_db_config();
        $db = new Zend_Db_Adapter_Pdo_Mysql($db_params);
                               
		$db->getConnection();
        
        $earlyParam = "";
        $lateParam = "";
        $subearlyParam = "";
        $sublateParam = "";
        
        if(strlen($earlyDate)>0){
            $earlyParam = " AND projects.accession >= '".$earlyDate."' ";
            $subearlyParam = " AND subprojects.published >= '".$earlyDate."' ";
        }
        if(strlen($lateDate)>0){
            $lateParam = " AND projects.accession <= '".$lateDate."' ";
            $sublateParam = " AND subprojects.published <= '".$lateDate."' ";
        }
        
        if(!$specific_id){
            $proj_param = "";
            $subprog_param = "";
            $do_projects = true;
            $do_subprojects = true;
            if($set != false){
                " AND subprojects.sub_id = '".$set."' ";
            }
        }
        
        if (substr_count($specific_id, "/projects/")>0){
            
            //a request for a specific project OR subproject
            $proj_param = "";
            $subprog_param = "";
            $loc_proj = strpos($specific_id, "projects/", 0);
            $id_string = substr($specific_id, ($loc_proj+strlen("projects/")), strlen($specific_id)-0);
            $proj_param = " AND projects.project_id = '".$id_string."' ";
            $do_projects = true;
            
            
            
            if(substr_count($id_string, "/")==1){
                $id_array = explode("/", $id_string);
                $subprog_param = " AND subprojects.project_id = '".$id_array[0]."' AND subprojects.sub_id = '".$id_array[1]."' ";
                $do_subprojects = true;
            }
            else{
                $do_subprojects = false;
            }
        }
       
        if($set == 'projects'){
            $do_projects = true;
            $do_subprojects = false;
        }
       
        if($set != false && $set != 'projects'){
            $do_projects = false;
            $do_subprojects = true;
        }
       
        if($do_projects){
            $sql = "SELECT DISTINCT projects.project_id, projects.proj_name,
                    projects.accession as proj_pub
                    FROM projects
                    WHERE projects.project_id != '0'
                    AND projects.project_id != '2'
                    ".$earlyParam.$lateParam.$proj_param;
                    
            $result = $db->fetchAll($sql, 2);
            if($result){
        
                foreach($result as $act_result){
                    $proj_date = $act_result["proj_pub"];
                    $act_date = strtotime($proj_date);
                    $allDates[] = $act_date;
                    $proj_name = $act_result["proj_name"];
                    $projectID = $act_result["project_id"];
                    $URIprojectJSON = $host."/projects/".$projectID.".json";
                    
                    $projURI = $host."/projects/".$projectID;
                    $projURI = "projects/".$projectID;
                    $identifier = $this->make_proj_metaelements($projectID, $proj_date);
					if($identifier != false){
						$Records[] = $identifier;
					}
                }//end loop
                
                $this->latestDate = max($allDates);
                $this->earliestDate = min($allDates);
                $this->Records = $Records;
        
            }//end case with result
        
        }//end case to do projects
        
        
        
        if($do_subprojects){
            $sql = "SELECT subprojects.subprojid,
                            subprojects.project_id,
                            subprojects.sub_id,
                            subprojects.sub_name,
                            subprojects.published
                            FROM subprojects
                    WHERE subprojects.project_id != '0'
                    AND subprojects.project_id != '2'
                    ".$subearlyParam.$sublateParam.$subprog_param;
                    
                    //echo $sql;
			
            $result = $db->fetchAll($sql, 2);
            if($result){
        
                foreach($result as $act_result){
                    $proj_date = $act_result["published"];
                    $act_date = strtotime($proj_date);
                    $allDates[] = $act_date;
                    $proj_name = $act_result["sub_name"];
                    $sub_id = $act_result["sub_id"];
                    $projectID = $act_result["project_id"];
                    $URIprojectJSON = $host."/projects/".$projectID."/".urlencode($sub_id).".json";
                    unset($projJSON);
                    @$projJSONstring = file_get_contents($URIprojectJSON);
                    if ($projJSONstring){
						$projJSON = Zend_Json::decode($projJSONstring);
						unset($projJSONstring);
						
						unset($cat_metadata_array);
						unset($proj_metadata_array);
						
						$proj_metadata_array = array();
						$projJSON_meta = array();
						$projJSON_meta = $projJSON["dc_metadata"];
						if(count($projJSON_meta)>0){
							foreach($projJSON_meta as $metadata_element => $value_array){
								if(in_array($metadata_element, $jsonMetaMappings)){
									$use_element = $jsonMetaMappings[$metadata_element];
									if(!is_array($value_array)){
										$value_item = array("value" => $value_array);
										$value_array = array();
										$value_array[] = $value_item;
									}
									foreach($value_array as $value_item){
										$DCmetadataElement = new MetaElement();
										$DCmetadataElement->element = $use_element;
										$DCmetadataElement->value = $value_item["value"];
										$proj_metadata_array[] = $DCmetadataElement;
									}
								}
							}
						}
						//add a dc identifier specifically for the subproject
						$proj_dc_identifier = new MetaElement();
						$proj_dc_identifier->element = "identifier";
						$proj_dc_identifier->value = $host."/projects/".$projectID."/".$sub_id;
						$proj_metadata_array[] = $proj_dc_identifier;
						
						if (isset($projJSON_meta["label"])){
							$DCmetadataElement = new MetaElement();
							$DCmetadataElement->element = 'title';
							$DCmetadataElement->value = $projJSON_meta["label"];
							$proj_metadata_array[] = $DCmetadataElement;
						}
						if (isset($projJSON_meta["metadata"]["licensing"]["uri"])){
							$DCmetadataElement = new MetaElement();
							$DCmetadataElement->element = 'rights';
							if($projJSON_meta["metadata"]["licensing"]["uri"] != false){
								$DCmetadataElement->value = $projJSON_meta["metadata"]["licensing"]["uri"] ;
							}
							else{
								$DCmetadataElement->value = self::default_license;
							}
							$proj_metadata_array[] = $DCmetadataElement;
						}
						$description = "DRAFT content. Still in preparation.";
						if (isset($projJSON_meta["descriptions"]["long"])){
							if(strlen($projJSON_meta["descriptions"]["long"]) > 10){
								$description = $projJSON_meta["descriptions"]["long"];
							}
						}
						$DCmetadataElement = new MetaElement();
						$DCmetadataElement->element = 'description';
						$DCmetadataElement->value = $description;	
						$proj_metadata_array[] = $DCmetadataElement;
						
						$DCmetadataElement = new MetaElement();
						$DCmetadataElement->element = 'publisher';
						$DCmetadataElement->value = 'Open Context (http://opencontext.org)';
						$proj_metadata_array[] = $DCmetadataElement;
				
						$DCmetadataElement = new MetaElement();
						$DCmetadataElement->element = 'type';
						$DCmetadataElement->value = 'Dataset';
						$proj_metadata_array[] = $DCmetadataElement;
						
						$DCmetadataElement = new MetaElement();
						$DCmetadataElement->element = 'resourceType';
						$DCmetadataElement->value = 'Dataset';
						$proj_metadata_array[] = $DCmetadataElement;
						
						unset($identifier);
						$identifier = new Identifier();
						$identifier->id = $projectID."/".urlencode($sub_id);
						$identifier->collection_id = urlencode($sub_id);
						$identifier->time = $proj_date;
						$identifier->dc_metadata = $proj_metadata_array;
						$Records[] = $identifier;
						
						unset($projJSON);
					}
                    
                }//end loop
                
                $this->latestDate = max($allDates);
                $this->earliestDate = min($allDates);
                $this->Records = $Records;
        
            }//end case with result
        
        }//end case to do subprojects
        
        
        
        
        
    }//end function
    
	
	function make_proj_metaelements($projectID, $proj_date = false){
		$identifier = false;
		$host = OpenContext_OCConfig::get_host_config();
		$projURI = $host."/projects/".$projectID;
		$projsObj = new Projects;
		$projJSON = $projsObj->get_cache_json($projectID);
		$jsonMetaMappings = array("subjects" => "subject",
								  "coverages" => "coverage",
								  "creators" => "creator",
								  "created" => "date");
		if(is_array($projJSON )){
			$proj_metadata_array = array();
			$projJSON_meta = $projJSON["metadata"];
			if(is_array($projJSON_meta)){
				foreach($projJSON_meta as $metadata_element => $value_array){
					if(array_key_exists($metadata_element, $jsonMetaMappings)){
						$use_element = $jsonMetaMappings[$metadata_element];
						if($use_element == 'date' || $metadata_element == 'created'){
							$proj_date = $value_array;
						}
						if(!is_array($value_array)){
							$value_item = array("value" => $value_array);
							$value_array = array();
							$value_array[] = $value_item;
						}
						foreach($value_array as $value_item){
							if(strlen(trim($value_item["value"]))>1){
								$DCmetadataElement = new MetaElement();
								$DCmetadataElement->element = $use_element;
								$DCmetadataElement->value = $value_item["value"];
								$proj_metadata_array[] = $DCmetadataElement;
							}
						}
					}
				}
			}
			if (isset($projJSON["categories"])){
				foreach($projJSON["categories"] as $cat){
					$DCmetadataElement = new MetaElement();
					$DCmetadataElement->element = 'subject';
					$DCmetadataElement->value = 'Archaeology :: '.$cat["name"];
					$proj_metadata_array[] = $DCmetadataElement;
				}
			}
			if (isset($projJSON["contexts"])){
				foreach($projJSON["contexts"] as $context){
					$DCmetadataElement = new MetaElement();
					$DCmetadataElement->element = 'coverage';
					$DCmetadataElement->value = $context["name"];
					$proj_metadata_array[] = $DCmetadataElement;
				}
			}
			
			//add a dc identifier specifically for the project
			$proj_dc_identifier = new MetaElement();
			$proj_dc_identifier->element = "identifier";
			$proj_dc_identifier->value = $host."/projects/".$projectID;
			$proj_metadata_array[] = $proj_dc_identifier;
			
			if (isset($projJSON["label"])){
				$DCmetadataElement = new MetaElement();
				$DCmetadataElement->element = 'title';
				$DCmetadataElement->value = $projJSON["label"];
				$proj_metadata_array[] = $DCmetadataElement;
			}
			$rights = self::default_license;
			if (isset($projJSON_meta["metadata"]["licensing"]["uri"])){
				if($projJSON_meta["metadata"]["licensing"]["uri"] != false){
					$rights = $projJSON_meta["metadata"]["licensing"]["uri"] ;
				}
				else{
					$DCmetadataElement->value = self::default_license;
				}
			}
			$DCmetadataElement = new MetaElement();
			$DCmetadataElement->element = 'rights';
			$DCmetadataElement->value = $rights;
			$proj_metadata_array[] = $DCmetadataElement;
				
			$description = false;
			if (isset($projJSON["descriptions"]["short"])){
				if(strlen(trim($projJSON["descriptions"]["short"])) > 2){
					$description = strip_tags($projJSON["descriptions"]["short"]);
				}
			}
			if (!$description){
				if (isset($projJSON["descriptions"]["long"])){
					if(strlen(trim($projJSON["descriptions"]["long"])) > 2){
						$description = strip_tags($projJSON["descriptions"]["long"]);
					}
				}
			}
			if (!$description){
				$description = "DRAFT content. Still in preparation.";
			}
			$DCmetadataElement = new MetaElement();
			$DCmetadataElement->element = 'description';
			$DCmetadataElement->value = $description;	
			$proj_metadata_array[] = $DCmetadataElement;
			
			$doi = false;
			if (isset($projJSON["descriptions"]["doi"])){
				if(strlen(trim($projJSON["descriptions"]["doi"])) > 2){
					$doi = $projJSON["descriptions"]["doi"];
				}
			}
			$DCmetadataElement = new MetaElement();
			$DCmetadataElement->element = 'doi';
			$DCmetadataElement->value = $doi;
			$proj_metadata_array[] = $DCmetadataElement;
			
			$DCmetadataElement = new MetaElement();
			$DCmetadataElement->element = 'publisher';
			$DCmetadataElement->value = 'Open Context (http://opencontext.org)';
			$proj_metadata_array[] = $DCmetadataElement;
	
			$DCmetadataElement = new MetaElement();
			$DCmetadataElement->element = 'type';
			$DCmetadataElement->value = 'Dataset';
			$proj_metadata_array[] = $DCmetadataElement;
			
			$DCmetadataElement = new MetaElement();
			$DCmetadataElement->element = 'resourceType';
			$DCmetadataElement->value = 'Dataset';
			$proj_metadata_array[] = $DCmetadataElement;
			
			unset($identifier);
			$identifier = new Identifier();
			$identifier->id = $projURI;
			$identifier->time = $proj_date;
			$identifier->collection_id = "projects";
			$identifier->dc_metadata = $proj_metadata_array;
		}
		return $identifier;
	}
	
	
    function get_cache_project_json($projectID){
		//gets a cached version of the project json
		$cacheID = "pTM_".md5($projectUUID);
		if(!$cache_result = $cache->load($cacheID)) {
			 @$projJSON_string = file_get_contents($host."/projects/".$projectUUID.".json");
			 
			 if($projJSON_string != false){
				   @$projJSON = Zend_Json::decode($projJSON_string);
				   if(is_array($projJSON)){
						$cache->save($projJSON, $cacheID ); //save result to the cache, only if valid JSON
				   }
			 }
		}
		else{
			 $projJSON = $cache_result;
		}
	}
    
}
