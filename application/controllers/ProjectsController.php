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
				 projects.edit_status,
				 DATE_FORMAT(projects.accession, '%Y-%m-%d') as proj_pub
				 FROM projects
				 WHERE projects.project_id != '0'
					ORDER BY projects.accession DESC
				 ";
	
		$result = $db->fetchAll($sql, 2);
		if($result){
	  
			$proj_atom = array();
			$proj_dates = array();
			$proj = new Project ;
			foreach($result as $act_result){
				$projectEditStatus = $act_result["edit_status"];
				if(strlen($act_result["proj_atom"])>0){
						$heroLink = "<link rel=\"enclosure\" href=\"".$act_result['hero_pict']."\" title=\"Illustrative image\" />";
						$atom = $act_result["proj_atom"];
						$atom = str_replace("</id>", "</id>".chr(13).$heroLink.chr(13), $atom);
						$XML = simplexml_load_string($atom);
						$XML = OpenContext_ProjectReviewAnnotate::XMLmodify($projectEditStatus, $XML, $proj->nameSpaces());
						$atom = $XML->asXML();
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
		$all_project_feed_string = false;
	  
		$db_params = OpenContext_OCConfig::get_db_config();
		$db = new Zend_Db_Adapter_Pdo_Mysql($db_params);
									 
		$db->getConnection();
		$this->setUTFconnection($db);
	
		$sql = "SELECT projects.proj_atom,
				 projects.total_views,
				 projects.view_count,
				 projects.hero_pict,
				 projects.edit_status,
				 DATE_FORMAT(projects.accession, '%Y-%m-%d') as proj_pub
				 FROM projects
				 WHERE projects.project_id != '0'
					ORDER BY projects.accession DESC
				 ";
	
		//echo $sql;
	
		$result = $db->fetchAll($sql, 2);
		if($result){
	  
			$proj_atom = array();
			$proj_dates = array();
			$proj = new Project ;
			foreach($result as $act_result){
				$projectEditStatus = $act_result["edit_status"];
				if(strlen($act_result["proj_atom"])>0){
						$heroLink = "<link rel=\"enclosure\" href=\"".$act_result['hero_pict']."\" title=\"Illustrative image\" />";
						$atom = $act_result["proj_atom"];
						$atom = str_replace("</id>", "</id>".chr(13).$heroLink.chr(13), $atom);
						$XML = simplexml_load_string($atom);
						$XML = OpenContext_ProjectReviewAnnotate::XMLmodify($projectEditStatus, $XML, $proj->nameSpaces());
						$atom = $XML->asXML();
						$proj_atom[] = $atom;
				}
				$proj_dates[] = strtotime($act_result["proj_pub"]);
			}//end loop
			$last_date = max($proj_dates);
			$all_project_feed_string = OpenContext_ProjectAtomJson::all_project_atom_feed($proj_atom, $last_date);
			$this->view->xml_string = $all_project_feed_string;
			
	  }//end case with result
	  
	  $db->closeConnection();
	  
   }//end index action
    
    
    
    public function viewAction() {
		
		// get the space uuid from the uri
		$itemUUID = $this->_request->getParam('proj_uuid');
		
		//check for referring links
		OpenContext_SocialTracking::update_referring_link('project', $this->_request->getRequestUri(), @$_SERVER['HTTP_USER_AGENT'], @$_SERVER['HTTP_REFERER']);
		
		$proj = new Project ;
		$itemFound = $proj->getByID($itemUUID);
		 
		if($itemFound){

			$proj_name = $proj->label;
			$proj_atom = $proj->atomFull;
			$projectEditStatus = $proj->editStatus;
			$view_count = $proj->viewCount;
			$sp_view_count = $proj->totalViewCount;
			$view_count = $proj->addViewCount($itemUUID, $view_count);
			
			$xml_string = $proj_atom; 
			$rank = $proj->rankProjectViewcounts();
			$xml_string = OpenContext_ProjectAtomJson::project_atom_feed($proj_atom, $view_count, $sp_view_count, $rank);
			$XML = simplexml_load_string($xml_string);
			$XML = OpenContext_ProjectReviewAnnotate::XMLmodify($projectEditStatus, $XML, $proj->nameSpaces());
			$xml_string = $XML->asXML();
			$this->view->xml_string = $xml_string;
		}
		else{
			$this->view->requestURI = $this->_request->getRequestUri(); 
			return $this->render('404error');
		}
	}
    
    
   public function atomAction() {
                
		// get the space uuid from the uri
		$itemUUID = $this->_request->getParam('proj_uuid');
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
				
				$xml_string = $proj_atom; 
				$rank = $proj->rankProjectViewcounts();
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
        
	
	
	
	//make the ArchaeoML XML representation
	public function xmlAction() {
                
		$this->_helper->viewRenderer->setNoRender();
                // get the project uuid from the uri
		$itemUUID = $this->_request->getParam('proj_uuid');
      
		if($itemUUID == "0"){
			$itemUUID = false;
		}
		
		if(strlen($itemUUID)>0){
			$proj = new Project ;
			$itemFound = $proj->getByID($itemUUID);
			if($itemFound){
				
				//$proj->addDOI("doi:10.6078/M77P8W98"); //add a doi

				$xml_string = $proj->archaeoML;
				header('Content-type: application/xml; charset=UTF-8'); 
				echo $xml_string;
			}
			else{
				$this->view->requestURI = $this->_request->getRequestUri(); 
				return $this->render('404error');
			}
		}//end case with an id requested
		else{
			return $this->render('index');
		}//end case with no id requested
	   
	}//end atom function
	

	//make the JSON representation
   public function jsonAction() {
      $this->_helper->viewRenderer->setNoRender();
      // get the project uuid from the uri
		$itemUUID = $this->_request->getParam('proj_uuid');
      
		if($itemUUID == "0"){
			$itemUUID = false;
		}
		
		if(strlen($itemUUID)<1){
			return $this->render('index');
		}
		else{
			$proj = new Project ;
			$itemFound = $proj->getByID($itemUUID);
			if(!$itemFound){
				$this->view->requestURI = $this->_request->getRequestUri(); 
				return $this->render('404error');
			}
			else{
				$host = OpenContext_OCConfig::get_host_config();
				//data available, time to make some JSON
				$output = array();
				$output["label"] = $proj->label;
				$output["uri"] = $host."/projects/".$proj->projectUUID;
				$output["cacheID"] = "pTM_".md5($proj->projectUUID);
				$output["editStatus"] = $proj->editStatus +0;
				$output["item_view_count"] = $proj->viewCount +0;
				$output["ranking"] = $proj->rankProjectViewcounts();
				
				$metadataObj = new dbXML_dbxmlMetadata;
				$metadataObj->initialize();
				$metadataObj->getMetadata($proj->projectUUID, $proj);
				$output["metadata"] = $metadataObj->publicMetadata();
				$output["descriptions"] = $proj->getXMLProjectDescriptions($proj->projectUUID, $proj->archaeoML);
				
				if(substr($proj->rootPath, 0, 1) == "/"){
					$setPath = "/sets".$proj->rootPath;
				}
				else{
					$setPath = "/sets/".$proj->rootPath;
				}
		
				$jsonQuery = $host.$setPath.".json?proj=".urlencode($proj->label);
				$output["href-proj-sets"] = $host.$setPath."?proj=".urlencode($proj->label);
				$output["href-proj-sets-json"] = $jsonQuery;
				@$jsonString = file_get_contents($jsonQuery);
				if($jsonString != false){
					@$sets = Zend_Json::decode($jsonString);
					if($sets != false){
						if(isset($sets["facets"]["context"])){
							$output["contexts"] = $sets["facets"]["context"];
						}
						else{
							$output["contexts"] = array();
							if($proj->editStatus == 0){
								$output["draftGeoTime"] = $proj->draftGeoTime;
							}
							
						}
						if(isset($sets["facets"]["category"])){
							$output["categories"] = $sets["facets"]["category"];
						}
						else{
							$output["categories"] = array();
						}
						$output["size"] = $sets["numFound"];
						$output["updated"] = $sets["updated"];
					}
				}
				
				
				header('Content-Type: application/json; charset=utf8');
				echo Zend_Json::encode($output);
			}
		}//end case with an id requested
		

	}//end json function

}

