<?php

class AllCollections
{
    public $collections;
    public $earliestDate;
    public $latestDate;
    
    function getCollections(){
        
        $collections = array();
        $allDates = array();
        
        $db_params = OpenContext_OCConfig::get_db_config();
        $db = new Zend_Db_Adapter_Pdo_Mysql($db_params);
                               
	$db->getConnection();
        
        $sql = "SELECT projects.project_id, projects.proj_name,
                projects.accession as proj_pub
                FROM projects
                WHERE projects.project_id != '0'
                AND projects.project_id != '2'
                ";
		
        $result = $db->fetchAll($sql, 2);
        if($result){
    
            foreach($result as $act_result){

                $proj_id = $act_result["project_id"];
                $proj_name = $act_result["proj_name"];
                $proj_date = $act_result["proj_pub"];
                $allDates[] = strtotime($proj_date);
                
                $collection = new Collection();
                $collection->id = $proj_id;
                $collection->name = utf8_encode($proj_name);
                
                $collections[] = $collection;
            }//end loop
            
            $this->latestDate = max($allDates);
            $this->earliestDate = min($allDates);
            $this->collections = $collections;
    
        }//end case with result
        
        $db->closeConnection();

    }
    
    
    function getSets(){
	$db_params = OpenContext_OCConfig::get_db_config();
        $db = new Zend_Db_Adapter_Pdo_Mysql($db_params);
                               
	$db->getConnection();
	$sql = "SELECT DISTINCT 
                subprojects.sub_id
                FROM subprojects
                    WHERE subprojects.project_id != '0'
                    AND subprojects.project_id != '2'
                    ";
		
        $result = $db->fetchAll($sql, 2);
        if($result){
    
            foreach($result as $act_result){
                $collection = new Collection();
                $collection->id = urlencode($act_result["sub_id"]);
                $collection->name = $act_result["sub_id"]." sub-project analytic data";
                
                $collections[] = $collection;
            }//end loop
    
        }//end case with result
	
        $collection = new Collection();
        $collection->id = "projects";
        $collection->name = "Complete data and media for a field project or collection";
	$collections[] = $collection;
	
        $this->collections = $collections;
        
        $db->closeConnection();
	
    }
    
    
    
    function getClassCollections(){
        
        $collections = array();
        $allDates = array();
        
        $db_params = OpenContext_OCConfig::get_db_config();
        $db = new Zend_Db_Adapter_Pdo_Mysql($db_params);
                               
	$db->getConnection();
        
        $sql = "SELECT projects.project_id, projects.proj_name,
                projects.accession as proj_pub
                FROM projects
                WHERE projects.project_id != '0'
                AND projects.project_id != '2'
                ";
		
        $result = $db->fetchAll($sql, 2);
        if($result){
    
            foreach($result as $act_result){
                $proj_date = $act_result["proj_pub"];
                $allDates[] = strtotime($proj_date);
                $proj_id = $act_result["project_id"];
                $proj_name = $act_result["proj_name"];
                $collection = new Collection();
                $collection->id = $proj_id;
                $collection->name = utf8_encode($proj_name);
                
                $collections[] = $collection;
            }//end loop
            
            $this->latestDate = max($allDates);
            $this->earliestDate = min($allDates);
    
        }//end case with result
        
        
         $sql = "SELECT subprojects.subprojid,
                            subprojects.project_id,
                            subprojects.sub_id,
                            subprojects.sub_name,
                            subprojects.published
                            FROM subprojects
                    WHERE subprojects.project_id != '0'
                    AND subprojects.project_id != '2'
                    ";
		
        $result = $db->fetchAll($sql, 2);
        if($result){
    
            foreach($result as $act_result){
                $proj_date = $act_result["published"];
                $allDates[] = strtotime($proj_date);
                $projectID = $act_result["project_id"];
                $proj_name = $act_result["sub_name"];
                $collection = new Collection();
                $collection->id = $projectID."/".urlencode($act_result["sub_id"]);
                $collection->name = utf8_encode($proj_name);
                
                $collections[] = $collection;
            }//end loop
            
            $this->latestDate = max($allDates);
            $this->earliestDate = min($allDates);
    
        }//end case with result
        
        
        $this->collections = $collections;
        
        $db->closeConnection();

    }
    
    
    
    
}
