<?php
/** Zend_Controller_Action */
require_once 'Zend/Controller/Action.php';
//require_once '../library/Zend/Json/encoder.php';

ini_set("max_execution_time", "0");

class projectsController extends Zend_Controller_Action
{   
    
	 //make sure character encoding is set, so greek characters work
    private function setUTFconnection($db){
	    $sql = "SET collation_connection = utf8_unicode_ci;";
	    $db->query($sql, 2);
	    $sql = "SET NAMES utf8;";
	    $db->query($sql, 2);
    }  
	 
	 
	  
    //this function retrieves an atom feed for all projects
    //this function is called if a project UUID is absent
    public function all_atom(){
        
			$all_project_feed_string = false;
        
			$db_params = OpenContext_OCConfig::get_db_config();
			$db = new Zend_Db_Adapter_Pdo_Mysql($db_params);
                               
			$db->getConnection();
			$this->setUTFconnection($db);
		
			$sql = "SELECT projects.proj_atom,
                projects.total_views,
                projects.view_count,
					 projects.hero_pict,
                DATE_FORMAT(projects.accession, '%Y-%m-%d') as proj_pub
                FROM projects
                WHERE projects.project_id != '0'
						ORDER BY projects.accession DESC
                ";
		
			$result = $db->fetchAll($sql, 2);
			if($result){
        
            $proj_atom = array();
            $proj_dates = array();
            foreach($result as $act_result){
                if(strlen($act_result["proj_atom"])>0){
							$heroLink = "<link rel=\"enclosure\" href=\"".$act_result['hero_pict']."\" title=\"Illustrative image\" />";
							$atom = $act_result["proj_atom"];
							$atom = str_replace("</id>", "</id>".chr(13).$heroLink.chr(13), $atom); 
							$proj_atom[] = $atom;
                }
                $proj_dates[] = strtotime($act_result["proj_pub"]);
            }//end loop
            $last_date = max($proj_dates);
            $all_project_feed_string = OpenContext_ProjectAtomJson::all_project_atom_feed($proj_atom, $last_date);
    
        }//end case with result
        
        $db->closeConnection();
        
        return $all_project_feed_string;
    
    }//end all atom function
    
    
    
    public function indexAction(){
	//check for referring links
	OpenContext_SocialTracking::update_referring_link('projects', $this->_request->getRequestUri(), @$_SERVER['HTTP_USER_AGENT'], @$_SERVER['HTTP_REFERER']);
        $AllAtomURI = OpenContext_OCConfig::get_host_config();
        $AllAtomURI .= "/projects/.atom";
        $xml_string = file_get_contents($AllAtomURI);
        $this->view->xml_string = $xml_string;
    }//end index action
    
    
    
    public function viewAction() {
		
		// get the space uuid from the uri
		$itemUUID = $this->_request->getParam('proj_uuid');
		
		//check for referring links
		OpenContext_SocialTracking::update_referring_link('project', $this->_request->getRequestUri(), @$_SERVER['HTTP_USER_AGENT'], @$_SERVER['HTTP_REFERER']);
		
                $db_params = OpenContext_OCConfig::get_db_config();
                $db = new Zend_Db_Adapter_Pdo_Mysql($db_params);
				$db->getConnection();
				
				$sql = "SET collation_connection = utf8_general_ci;";
				$db->query($sql, 2);
				$sql = "SET NAMES utf8;";
				$db->query($sql, 2);
				
                $sql = 'SELECT projects.proj_name, projects.proj_atom,
                    projects.total_views,
                    projects.view_count
                    FROM projects
                    WHERE projects.project_id = "'.$itemUUID.'"
                    LIMIT 1';
		
                $result = $db->fetchAll($sql, 2);
                 
                if($result){
		    
					$proj = new Project ;
					$proj->getByID($itemUUID);
		    
		    $proj_name = $result[0]["proj_name"];
                    $proj_atom = $result[0]["proj_atom"];
                    $view_count = $result[0]["view_count"];
                    $sp_view_count = $result[0]["total_views"];
                    
		    
		    $proj_atom = OpenContext_OCConfig::updateNamespace($proj_atom, $itemUUID, "proj_atom", "project");
		    
                    $view_count++; // increment it up one.
                    $where_term = 'project_id = "'.$itemUUID.'"';
		    $data = array('view_count' => $view_count); 
		    $n = $db->update('projects', $data, $where_term);
                    $db->closeConnection();
                    
                    $xml_string = $proj_atom; 
                    $rank = OpenContext_SocialTracking::rank_project_viewcounts($itemUUID);
                    $xml_string = OpenContext_ProjectAtomJson::project_atom_feed($proj_atom, $view_count, $sp_view_count, $rank);
                }
                else{
                    $db->closeConnection();
		    $this->view->requestURI = $this->_request->getRequestUri(); 
		    return $this->render('404error');
                }
                
                
                $this->view->xml_string = $xml_string;
                
                //$this->view->result = $result;
                
	}
    
    
        public function atomAction() {
                
            // get the space uuid from the uri
			$itemUUID = $this->_request->getParam('proj_uuid');
                
			//echo "UUID:".$itemUUID;
			
			if($itemUUID == "0"){
				$itemUUID = false;
			}
			
			if(strlen($itemUUID)>0){
				$proj = new Project ;
				$itemFound = $proj->getByID($itemUUID);
				
				if($itemFound){
					$proj_atom = $proj->atomFull;
					$view_count = $proj->viewCount;
					$sp_view_count = $proj->totalViewCount;
							
					//$proj_atom = OpenContext_OCConfig::updateNamespace($proj_atom, $itemUUID, "proj_atom", "project");
		
					$xml_string = $proj_atom; 
					$rank = OpenContext_SocialTracking::rank_project_viewcounts($itemUUID);
					$xml_string = OpenContext_ProjectAtomJson::project_atom_feed($proj_atom, $view_count, $sp_view_count, $rank);
					
				}
				else{
			
					$this->view->requestURI = $this->_request->getRequestUri(); 
					return $this->render('404error');
                }
            }//end case with an id requested
            else{
                $xml_string = $this->all_atom(); //get string of all project atom feed data
            }//end case with no id requested
                
        $this->view->xml_string = $xml_string;
	}//end atom function
        
	
	public function xmlAction() {
                
		$this->_helper->viewRenderer->setNoRender();
                // get the space uuid from the uri
		$itemUUID = $this->_request->getParam('proj_uuid');
                
                //echo "UUID:".$itemUUID;
                
                if($itemUUID == "0"){
                    $itemUUID = false;
                }
                
                if(strlen($itemUUID)>0){
					$proj = new Project ;
					$itemFound = $proj->getByID($itemUUID);
					
					if($itemFound){
						$xml_string = $proj->archaeoML;
					}
					else{
						$this->view->requestURI = $this->_request->getRequestUri(); 
						return $this->render('404error');
					}
                }//end case with an id requested
                else{
                    $xml_string = " "; //get string of all project atom feed data
                }//end case with no id requested
               
	       
	    header('Content-type: application/xml; charset=UTF-8'); 
        echo $xml_string;
	}//end atom function
	
	
	
	


    public function jsonAction() {
                
                
                $frontendOptions = array(
                'lifetime' => 72000, // cache lifetime, measured in seconds, 7200 = 2 hours
                'automatic_serialization' => true
                );
                
                $backendOptions = array(
                'cache_dir' => './cache/' // Directory where to put the cache files
                );
                
                $cache = Zend_Cache::factory('Core',
                             'File',
                             $frontendOptions,
                             $backendOptions);
                
                
                // get the space uuid from the uri
		$itemUUID = $this->_request->getParam('proj_uuid');
                $requestURI = $this->_request->getRequestUri();
                $cache_id = str_replace("/", "_", $requestURI);
                $cache_id = str_replace(".", "_", $cache_id );
                $cache_id = str_replace("-", "_", $cache_id );
                $cache_id = trim($cache_id);
                
                if(strlen($itemUUID)>0){
               
                    if(!$cache_result = $cache->load($cache_id)) {
                        $db_params = OpenContext_OCConfig::get_db_config();
                        $db = new Zend_Db_Adapter_Pdo_Mysql($db_params);
                                       
                        $db->getConnection();
                        $sql = 'SELECT projects.proj_atom,
                            projects.total_views,
                            projects.view_count
                            FROM projects
                            WHERE projects.project_id = "'.$itemUUID.'"
                            LIMIT 1';
                        
                        $result = $db->fetchAll($sql, 2);
                         
                        if($result){
                            $proj_atom = $result[0]["proj_atom"];
                            $view_count = $result[0]["view_count"];
                            $sp_view_count = $result[0]["total_views"];
                            
			    $proj_atom = OpenContext_OCConfig::updateNamespace($proj_atom, $itemUUID, "proj_atom", "project");
			    
                            $db->closeConnection();
                            
                            $xml_string = $proj_atom; 
                            $rank = OpenContext_SocialTracking::rank_project_viewcounts($itemUUID);
                            $xml_string = OpenContext_ProjectAtomJson::project_atom_feed($proj_atom, $view_count, $sp_view_count, $rank);
                            //$array_for_json = OpenContext_ProjectAtomJson::project_atom_to_json($xml_string);
                            
                            $json_string  = OpenContext_ProjectAtomJson::project_atom_to_json($xml_string);
                            //$json_string = Zend_Json::encode($array_for_json);
                            $cache->save($json_string, $cache_id ); //save result to the cache
                            
                            $this->view->json_string = $json_string;
                        }
                        else{
                            $db->closeConnection();
			    $this->view->requestURI = $this->_request->getRequestUri(); 
			    return $this->render('404error');
                        }
                        
                    }//end case with no cached item
                    else{ // case with an item found in cache
                        
                        $json_string = $cache_result;
                        $this->view->json_string = $json_string;
                        
                    }// end case with item from cache
                    
                    
                }//end case with an id requested
                else{
                    //$xml_string = $this->all_atom(); //get string of all project atom feed data
                }//end case with no id requested
                
                
	}//end json function

    
    //this returns the subprojects for a given project
    //this is needed to help document different aspects of a project
    //a subproject is like a chapter of a book (the whole project)
     public function subprojAction() {
        
        //$this->_helper->viewRenderer->setNoRender();
        $projectUUID = $this->_request->getParam('proj_uuid');           
        $className = $this->_request->getParam('class_name');    

	//check for referring links
	OpenContext_SocialTracking::update_referring_link('sub-project', $this->_request->getRequestUri(), $_SERVER['HTTP_USER_AGENT'], $_SERVER['HTTP_REFERER']);

        $db_params = OpenContext_OCConfig::get_db_config();
        $db = new Zend_Db_Adapter_Pdo_Mysql($db_params);
                       
        $db->getConnection();
        $sql = 'SELECT subprojects.subprojid,
            subprojects.atom,
            subprojects.total_views,
            subprojects.view_count
            FROM subprojects
            WHERE subprojects.project_id = "'.$projectUUID.'"
            AND subprojects.sub_id = "'.$className.'"
            LIMIT 1';
        
        $result = $db->fetchAll($sql, 2);
         
        if($result){
            $sub_atom = $result[0]["atom"];
            $view_count = $result[0]["view_count"];
            $sp_view_count = $result[0]["total_views"];
            
            $view_count++; // increment it up one.
            $where_term = 'subprojid = "'.$result[0]["subprojid"].'"';
	    $data = array('view_count' => $view_count); 
	    $n = $db->update('subprojects', $data, $where_term);
            
            $db->closeConnection();
            //echo $result[0]["archaeoML"];
            $rank = OpenContext_SocialTracking::rank_subproject_viewcounts($result[0]["subprojid"]);
            
            $xml_string = OpenContext_SubProject::project_atom_feed($sub_atom, $view_count, $sp_view_count, $rank, $className);
            $this->view->xml_string = $xml_string;
        }
        else{
            //generate a new sub project
            //(1) check to make sure that the class exists for a project
            $classFacetURI = OpenContext_SubProject::project_class_exists($projectUUID, $className);
            $new_archaeoML = "You're kidding me...";
            
            $new_atom = "";
            
            if(!$classFacetURI == false){
                $new_archaeoML = OpenContext_SubProject::make_new_sub_XML($projectUUID, $className, $classFacetURI);
                //header('Content-type: application/xml', true);
                $new_atom =  OpenContext_SubProject::make_first_atom($new_archaeoML, $projectUUID, $className);
                
                //echo $new_atom;
                $xml_string = OpenContext_SubProject::project_atom_feed($new_atom, 0, 0, 0, $className);
                $this->view->xml_string = $xml_string;
            }
            else{
                $this->view->xml_string = false;
            }
            

        }
        
        
     }//end sub project
     
     
     
     public function subatomAction() {
        
        //$this->_helper->viewRenderer->setNoRender();
        $projectUUID = $this->_request->getParam('proj_uuid');           
        $className = $this->_request->getParam('class_name');    

        //echo $projectUUID." ".$className;

        $db_params = OpenContext_OCConfig::get_db_config();
        $db = new Zend_Db_Adapter_Pdo_Mysql($db_params);
                       
        $db->getConnection();
        $sql = 'SELECT subprojects.subprojid,
            subprojects.atom,
            subprojects.total_views,
            subprojects.view_count
            FROM subprojects
            WHERE subprojects.project_id = "'.$projectUUID.'"
            AND subprojects.sub_id = "'.$className.'"
            LIMIT 1';
        
        $result = $db->fetchAll($sql, 2);
         
        if($result){
            $sub_atom = $result[0]["atom"];
            $view_count = $result[0]["view_count"];
            $sp_view_count = $result[0]["total_views"];
            
            $view_count++; // increment it up one.
            $where_term = 'subprojid = "'.$result[0]["subprojid"].'"';
	    $data = array('view_count' => $view_count); 
	    //$n = $db->update('subprojects', $data, $where_term);
            
            $db->closeConnection();
            //echo $result[0]["archaeoML"];
            $rank = OpenContext_SocialTracking::rank_subproject_viewcounts($result[0]["subprojid"]);
            
            $xml_string = OpenContext_SubProject::project_atom_feed($sub_atom, $view_count, $sp_view_count, $rank, $className);
            $this->view->xml_string = $xml_string;
        }
    }
    
    
    
    
    
    public function subjsonAction() {
                
                
                $frontendOptions = array(
                'lifetime' => 72000, // cache lifetime, measured in seconds, 7200 = 2 hours
                'automatic_serialization' => true
                );
                
                $backendOptions = array(
                'cache_dir' => './cache/' // Directory where to put the cache files
                );
                
                $cache = Zend_Cache::factory('Core',
                             'File',
                             $frontendOptions,
                             $backendOptions);
                
                
                // get the space uuid from the uri
		$projectUUID = $this->_request->getParam('proj_uuid');           
                $className = $this->_request->getParam('class_name');
                
                $requestURI = $this->_request->getRequestUri();
                $cache_id = str_replace("/", "_", $requestURI);
                $cache_id = str_replace(".", "_", $cache_id );
                $cache_id = str_replace(" ", "_", $cache_id );
                $cache_id = str_replace("%20", "_", $cache_id );
                $cache_id = str_replace("+", "_", $cache_id );
                $cache_id = str_replace("-", "_", $cache_id );
                $cache_id = trim($cache_id);
                
                if(strlen($projectUUID)>0){
               
                    if(!$cache_result = $cache->load($cache_id)) {
                        $db_params = OpenContext_OCConfig::get_db_config();
                        $db = new Zend_Db_Adapter_Pdo_Mysql($db_params);
                                       
                        $db->getConnection();
                        $sql = 'SELECT subprojects.subprojid,
                            subprojects.atom,
                            subprojects.total_views,
                            subprojects.view_count
                            FROM subprojects
                            WHERE subprojects.project_id = "'.$projectUUID.'"
                            AND subprojects.sub_id = "'.$className.'"
                            LIMIT 1';
                        
                        $result = $db->fetchAll($sql, 2);
                         
                        if($result){
                            $sub_atom = $result[0]["atom"];
                            $view_count = $result[0]["view_count"];
                            $sp_view_count = $result[0]["total_views"];
                            
                            $view_count++; // increment it up one.
                            $where_term = 'subprojid = "'.$result[0]["subprojid"].'"';
                            $data = array('view_count' => $view_count); 
                            //$n = $db->update('subprojects', $data, $where_term);
                            
                            $db->closeConnection();
                            //echo $result[0]["archaeoML"];
                            $rank = OpenContext_SocialTracking::rank_subproject_viewcounts($result[0]["subprojid"]);
                            
                            $xml_string = OpenContext_SubProject::project_atom_feed($sub_atom, $view_count, $sp_view_count, $rank, $className);

                            $json_string  = OpenContext_SubProject::subproject_atom_to_json($xml_string);
                            //$json_string = Zend_Json::encode($array_for_json);
                            $cache->save($json_string, $cache_id ); //save result to the cache
                            
                            $this->view->json_string = $json_string;
                        }
                        else{
                            $xml_string = "no luck...";
                            $db->closeConnection();
                        }
                        
                    }//end case with no cached item
                    else{ // case with an item found in cache
                        
                        $json_string = $cache_result;
                        $this->view->json_string = $json_string;
                        
                    }// end case with item from cache
                    
                    
                }//end case with an id requested
                else{
                    //$xml_string = $this->all_atom(); //get string of all project atom feed data
                }//end case with no id requested
                
                
	}//end atom function
    
    
    
    
    
    
    
    
    
    
    
    
    
}

