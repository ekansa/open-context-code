<?php

class AllIdentifiers
{
    public $latestDate;
    public $earliestDate;
    public $Records;
    
    function get_projects_categories($set, $earlyDate, $lateDate, $specific_id = false){
        
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
                     
                    unset($projJSON);
                    $projJSONstring = file_get_contents($URIprojectJSON);
                    $projJSON = Zend_Json::decode($projJSONstring);
                    unset($projJSONstring);
                    
                    unset($cat_metadata_array);
                    unset($proj_metadata_array);
                    
                    $proj_metadata_array = array();
                    
                    $projJSON_meta = array();
                    $projJSON_meta = $projJSON["dc_metadata"];
                    if(count($projJSON_meta)>0){
                        foreach($projJSON_meta as $Act_metaElement){
                            if($Act_metaElement["element"] != "identifier"){
                                $DCmetadataElement = new MetaElement();
                                $DCmetadataElement->element = $Act_metaElement["element"];
                                $DCmetadataElement->value = $Act_metaElement["value"];
                                
                                $proj_metadata_array[] = $DCmetadataElement;
                                   $cat_metadata_array[] = $DCmetadataElement;
                                
                             }
                        }
                    }
                    //add a dc identifier specifically for the project
                    $proj_dc_identifier = new MetaElement();
                    $proj_dc_identifier->element = "identifier";
                    $proj_dc_identifier->value = $host."/projects/".$projectID;
                    $proj_metadata_array[] = $proj_dc_identifier;
                    
                    unset($identifier);
                    $identifier = new Identifier();
                    $identifier->id = $projURI;
                    $identifier->time = $proj_date;
                    $identifier->collection_id = "projects";
                    $identifier->dc_metadata = $proj_metadata_array;
                    $Records[] = $identifier;
                    
                    unset($projJSON);
                    
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
                    $projJSONstring = file_get_contents($URIprojectJSON);
                    $projJSON = Zend_Json::decode($projJSONstring);
                    unset($projJSONstring);
                    
                    unset($cat_metadata_array);
                    unset($proj_metadata_array);
                    
                    $proj_metadata_array = array();
                    $projJSON_meta = array();
                    $projJSON_meta = $projJSON["dc_metadata"];
                    if(count($projJSON_meta)>0){
                        foreach($projJSON_meta as $Act_metaElement){
                            if($Act_metaElement["element"] != "identifier"){
                                $DCmetadataElement = new MetaElement();
                                $DCmetadataElement->element = $Act_metaElement["element"];
                                $DCmetadataElement->value = $Act_metaElement["value"];
                                $proj_metadata_array[] = $DCmetadataElement;
                             }
                        }
                    }
                    //add a dc identifier specifically for the subproject
                    $proj_dc_identifier = new MetaElement();
                    $proj_dc_identifier->element = "identifier";
                    $proj_dc_identifier->value = $host."/projects/".$projectID."/".$sub_id;
                    $proj_metadata_array[] = $proj_dc_identifier;
                    
                    unset($identifier);
                    $identifier = new Identifier();
                    $identifier->id = $projectID."/".urlencode($sub_id);
                    $identifier->collection_id = urlencode($sub_id);
                    $identifier->time = $proj_date;
                    $identifier->dc_metadata = $proj_metadata_array;
                    $Records[] = $identifier;
                    
                    unset($projJSON);
                    
                }//end loop
                
                $this->latestDate = max($allDates);
                $this->earliestDate = min($allDates);
                $this->Records = $Records;
        
            }//end case with result
        
        }//end case to do subprojects
        
        
        
        
        
    }//end function
    
    
    
}
