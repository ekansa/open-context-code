<?php


//this class interacts with the database for accessing projects items
class Projects {
    
	 public $frontendOptions = array(
					  'lifetime' => 720000, // cache lifetime, measured in seconds, 7200 = 2 hours
					  'automatic_serialization' => true
			);
					  
	  public  $backendOptions = array(
				 'cache_dir' => './time_cache/' // Directory where to put the cache files
			);
    
	 public $projectList;
	 public $projectsJSON;
	 
	 
	 function getMakeTimeMap(){
		  
		  $host = OpenContext_OCConfig::get_host_config();
		  $cache = Zend_Cache::factory('Core',
                             'File',
                             $this->frontendOptions,
                             $this->backendOptions);
		  
		  $cacheID = "timemapObj";
		  if(!$cache_result = $cache->load($cacheID)) {
				$this->DBgetProjects(); //get list of projects from the database
				$projectsJSON = $this->projectsJSON(); //get JSON metadata for all projects
				$timeMapObj = $this->makeTimeMapObject($projectsJSON); //convert project metadata to the TimeMap object
				return $timeMapObj;
		  }
		  else{
				return $cache_result;
		  }
	 }
	 
	 
	 /*
	  convert the project's JSON metadata into the structure needed for TimeMap
	  Because timemap is a visualization tool, we need to preprocess the raw project metadata
	  into a form that is easier to visualize for users.
	 */
	 function makeTimeMapObject($projectsJSON = false){
		  
		  $timeMapObj = false;
		  if(!$projectsJSON){
				$projectsJSON = $this->projectsJSON;
		  }
		  
		  if(is_array($projectsJSON)){
				$timeMapObj = array();
				foreach($projectsJSON as $project){
					 
					 if($this->keyArrayCheck("contexts", $project)){
						  $workingContexts = array(); //limit number of contexts that have the same timespans
						  foreach($project["contexts"] as $actContext){
								
								$timeSpanHash = md5(($actContext["geoTime"]["timeBegin"])."-".($actContext["geoTime"]["timeEnd"]));
								if(!array_key_exists($timeSpanHash, $workingContexts)){
									 $workingContexts[$timeSpanHash] = array("count"=> 1,
                                                      "used"=> false,
                                                      "start"=> $actContext["geoTime"]["timeBegin"],
                                                      "end" => $actContext["geoTime"]["timeEnd"],
                                                      "minLat" => $actContext["geoTime"]["geoLat"],
                                                      "minLon" => $actContext["geoTime"]["geoLong"],
                                                      "maxLat" => $actContext["geoTime"]["geoLat"],
                                                      "maxLon" => $actContext["geoTime"]["geoLong"],
                                                      "itemTotal" => $actContext["count"],
                                                      "contNames" => $actContext["name"],
                                                      "innerHTML" => false,
																		"point" => array("lat" => $actContext["geoTime"]["geoLat"], "lon" => $actContext["geoTime"]["geoLong"])
                                                      );	 
								}
								else{
									 //multiple contexts 
									 $workingContexts[$timeSpanHash]["count"]++;
									 $workingContexts[$timeSpanHash]["contNames"] .= ", ".$actContext["name"];
									 $workingContexts[$timeSpanHash]["itemTotal"] +=  $actContext["count"];
									 
									 if($actContext["geoTime"]["geoLat"] < $workingContexts[$timeSpanHash]["minLat"]){
										  $workingContexts[$timeSpanHash]["minLat"] = $actContext["geoTime"]["geoLat"];
									 }
									 if($actContext["geoTime"]["geoLong"] < $workingContexts[$timeSpanHash]["minLon"]){
										  $workingContexts[$timeSpanHash]["minLon"] = $actContext["geoTime"]["geoLong"];
									 }
									 if($actContext["geoTime"]["geoLat"] > $workingContexts[$timeSpanHash]["maxLat"]){
										 $workingContexts[$timeSpanHash]["maxLat"] = $actContext["geoTime"]["geoLat"];
									 }
									 if($actContext["geoTime"]["geoLong"] > $workingContexts[$timeSpanHash]["maxLon"]){
										 $workingContexts[$timeSpanHash]["maxLon"] = $actContext["geoTime"]["geoLong"];
									 }
									 
									 
									 unset($workingContexts[$timeSpanHash]["point"]);
									 $workingContexts[$timeSpanHash]["point"] = array("lat"=> ($workingContexts[$timeSpanHash]["minLat"] + $workingContexts[$timeSpanHash]["maxLat"])/2,
																							  "lon"=> ($workingContexts[$timeSpanHash]["minLon"] + $workingContexts[$timeSpanHash]["maxLon"])/2);
									 unset($workingContexts[$timeSpanHash]["polygon"]);
									 $workingContexts[$timeSpanHash]["polygon"] = array();
									 $workingContexts[$timeSpanHash]["polygon"][] = array("lat"=> $workingContexts[$timeSpanHash]["minLat"],
																									"lon"=> $workingContexts[$timeSpanHash]["minLon"]);
									 $workingContexts[$timeSpanHash]["polygon"][] = array("lat"=> $workingContexts[$timeSpanHash]["minLat"],
																									"lon"=> $workingContexts[$timeSpanHash]["maxLon"]);
									 $workingContexts[$timeSpanHash]["polygon"][] = array("lat"=> $workingContexts[$timeSpanHash]["maxLat"],
																									"lon"=> $workingContexts[$timeSpanHash]["maxLon"]);
									 $workingContexts[$timeSpanHash]["polygon"][] = array("lat"=> $workingContexts[$timeSpanHash]["maxLat"],
																									"lon"=> $workingContexts[$timeSpanHash]["minLon"]);
									 
									 
									 $innerHTML = "<div style='width:345px; padding:4px;'>";
									 $innerHTML .= "<div style='float:left; width:40px;'><img src='../images/item_view/project_icon.jpg' border='0' ></img>";
									 $innerHTML .= "</div>";
									 $innerHTML .= "<div style='float:right; width:300px; margin-left:4px;'>";
									 $innerHTML .= "<p class='bodyText'>As part of the <a href='".$project["uri"]."'><em>".$project["label"];
									 $innerHTML .= "</em></a> project, there are several contexts including: <strong>".$workingContexts[$timeSpanHash]["contNames"]."</strong>";
									 $innerHTML .= "</div>";
									 $innerHTML .= "<div style='clear:both; width:100%; margin-left:44px;'>";
									 $innerHTML .= "<p class='bodyText'>(<a href='".$project["href-proj-sets"]."'>Click here</a>) to browse contexts in this project.</p>";
									 $innerHTML .= "</div>";
									 $innerHTML .= "</div>";
									 $workingContexts[$timeSpanHash]["innerHTML"] = $innerHTML;
									 
								}//end case where we're adding to an existing context display
								
						  }//end loop through contexts
						  
						  foreach($workingContexts as $key => $actContext){
								$timeMapObj[] = $actContext;
						  }
						  unset($workingContexts);
					 }//end case with contexts
					 
				}//end loop through projects
		  }
		  
		  return $timeMapObj;
	 }
	 
	 
	 
	 //checks if an array key exists, and if it returns an array
	 function keyArrayCheck($key, $arrayItem){
		  $output = false;
		  if(is_array($arrayItem)){
				if(array_key_exists($key, $arrayItem)){
					 if(is_array($arrayItem[$key])){
						  $output = true;
					 }
				}
		  }
		  return $output;
	 }
	 
	 
	 
	 
    //get list of projects from database
    function DBgetProjects(){
        $this->projectList = false;
        $db_params = OpenContext_OCConfig::get_db_config();
        $db = new Zend_Db_Adapter_Pdo_Mysql($db_params);
		  $db->getConnection();
		  $this->setUTFconnection($db);
	
        $sql = "SELECT projects.project_id 
                FROM projects
                WHERE projects.project_id != '0'
                AND projects.project_id != '2'
                ";
		
        $result = $db->fetchAll($sql, 2);
        if($result){
				$this->projectList = $result;
            return $result;
		  }
		  else{
				return false;
		  }
	 }
	 
	 
	 //get project JSON, either from the project conroller or from the cache
	 function projectsJSON($clearCache = false){
		  
		  $this->projectsJSON = false;
		  if(is_array($this->projectList)){
				$projectsJSON = array();
				$host = OpenContext_OCConfig::get_host_config();
				$cache = Zend_Cache::factory('Core',
                             'File',
                             $this->frontendOptions,
                             $this->backendOptions);
				
				foreach($this->projectList as $record){
					 $projJSON = false;
					 $projectUUID = $record["project_id"];
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
					 
					 if($projJSON != false){
						  $projectsJSON[] = $projJSON;
					 }
				}
		  }
	 
		  $this->projectsJSON = $projectsJSON;
		  return $projectsJSON;
	 }
	 
	 
   
    
    function security_check($input){
        $badArray = array("DROP", "SELECT", "#", "--", "DELETE", "INSERT", "UPDATE", "ALTER", "=");
        foreach($badArray as $bad_word){
            if(stristr($input, $bad_word) != false){
                $input = str_ireplace($bad_word, "XXXXXX", $input);
            }
        }
        return $input;
    }
    
    private function setUTFconnection($db){
	    $sql = "SET collation_connection = utf8_general_ci;";
	    $db->query($sql, 2);
	    $sql = "SET NAMES utf8;";
	    $db->query($sql, 2);
    } 
    
    
    
    
}
