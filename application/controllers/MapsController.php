<?php
/** Zend_Controller_Action */
//require_once 'Zend/Controller/Action.php';
//ini_set("memory_limit", "1024M");
// set maximum execution time to no limit
ini_set("max_execution_time", "0");


class mapsController extends Zend_Controller_Action {
    
   
            
       
       
    //main view for the map  
    public function indexAction() {
    
        $this->view->requestURI = $this->_request->getRequestUri(); // for testing
        
        OpenContext_SocialTracking::update_referring_link('mapd', $this->_request->getRequestUri(), @$_SERVER['HTTP_USER_AGENT'], @$_SERVER['HTTP_REFERER']);
        
        $this->_helper->viewRenderer->setNoRender();
       
        
    }//end index viewer
   

    public function mapjsonAction(){
        
        $this->_helper->viewRenderer->setNoRender();
        
        $host = OpenContext_OCConfig::get_host_config();
        $requestURI = $this->_request->getRequestUri();
        
    }



    public function timemapjsonAction(){
        $this->_helper->viewRenderer->setNoRender();
        $host = OpenContext_OCConfig::get_host_config();
        $callback = $this->_request->getParam('callback');
	
		  echo "here";
	
		  /*
	
        $frontendOptions = array(
                'lifetime' => 720000, // cache lifetime, measured in seconds, 7200 = 2 hours
                'automatic_serialization' => true
        );
                
        $backendOptions = array(
            'cache_dir' => './time_cache/' // Directory where to put the cache files
        );
                
        $cache = Zend_Cache::factory('Core',
                             'File',
                             $frontendOptions,
                             $backendOptions);
        
        
        $db_params = OpenContext_OCConfig::get_db_config();
        $db = new Zend_Db_Adapter_Pdo_Mysql($db_params);
                               
		  $db->getConnection();
        $sql = "SELECT projects.project_id AS id, projects.root_path AS context_path
                FROM projects
                WHERE projects.project_id != '0'
                AND projects.project_id != '2'
                ";
		
        $result = $db->fetchAll($sql, 2);
        $TMitems = array();
        foreach($result as $row){
            
            $actProjID = $row["id"];
            $projContextPath = $row["context_path"];
            
            $cache_id = "pTM_".md5($actProjID);
            if(!$cache_result = $cache->load($cache_id)) {
                $projJSON_string = file_get_contents($host."/projects/".$actProjID.".json");
                $projJSON = Zend_Json::decode($projJSON_string);
                $cache->save($projJSON, $cache_id ); //save result to the cache
            }
            else{
                $projJSON = $cache_result;
            }
            
            
            //echo "<br/><br/>".$projJSON_string;
            
            //$projJSON = null;
            
            $itemCnt = 0;
            $latSum = 0;
            $lonSum = 0;
            $minDate = 0;
            $maxDate = 0;
            $remainderCnt = 0;
            $contextMaxCount = 3;
            
            
            //check for many places with the same time
            $timeCheck = array();
            foreach($projJSON["contexts"] as $actContext){
                
                $timeCheckMD5 = md5(($actContext["timespan"]["begin"]).($actContext["timespan"]["end"]));
            
                if(!array_key_exists($timeCheckMD5, $timeCheck)){
                    $timeCheck[$timeCheckMD5] = array("count"=> 1,
                                                      "used"=> false,
                                                      "start"=> $actContext["timespan"]["begin"],
                                                      "end" => $actContext["timespan"]["end"],
                                                      "minLat" => $actContext["geopoint"]["lat"],
                                                      "minLon" => $actContext["geopoint"]["long"],
                                                      "maxLat" => $actContext["geopoint"]["lat"],
                                                      "maxLon" => $actContext["geopoint"]["long"],
                                                      "itemTotal" => $actContext["item_count"],
                                                      "contNames" => $actContext["name"],
                                                      "innerHTML" => false
                                                      );
                }
                else{
                    $timeCheck[$timeCheckMD5]["count"] = $timeCheck[$timeCheckMD5]["count"] + 1;
                    $timeCheck[$timeCheckMD5]["contNames"] .= ", ".$actContext["name"];
                    $timeCheck[$timeCheckMD5]["itemTotal"] +=  $actContext["item_count"];
                    
                    
                    if($actContext["geopoint"]["lat"] < $timeCheck[$timeCheckMD5]["minLat"]){
                        $timeCheck[$timeCheckMD5]["minLat"] = $actContext["geopoint"]["lat"];
                    }
                    if($actContext["geopoint"]["long"] < $timeCheck[$timeCheckMD5]["minLon"]){
                        $timeCheck[$timeCheckMD5]["minLon"] = $actContext["geopoint"]["long"];
                    }
                    if($actContext["geopoint"]["lat"] > $timeCheck[$timeCheckMD5]["maxLat"]){
                        $timeCheck[$timeCheckMD5]["maxLat"] = $actContext["geopoint"]["lat"];
                    }
                    if($actContext["geopoint"]["long"] > $timeCheck[$timeCheckMD5]["maxLon"]){
                        $timeCheck[$timeCheckMD5]["maxLon"] = $actContext["geopoint"]["long"];
                    }
                    
                    unset($timeCheck[$timeCheckMD5]["point"]);
                    $timeCheck[$timeCheckMD5]["point"] = array("lat"=> ($timeCheck[$timeCheckMD5]["minLat"] + $timeCheck[$timeCheckMD5]["maxLat"])/2,
                                                               "lon"=> ($timeCheck[$timeCheckMD5]["minLon"] + $timeCheck[$timeCheckMD5]["maxLon"])/2);
                    unset($timeCheck[$timeCheckMD5]["polygon"]);
                    $timeCheck[$timeCheckMD5]["polygon"] = array();
                    $timeCheck[$timeCheckMD5]["polygon"][] = array("lat"=> $timeCheck[$timeCheckMD5]["minLat"],
                                                                   "lon"=> $timeCheck[$timeCheckMD5]["minLon"]);
                    $timeCheck[$timeCheckMD5]["polygon"][] = array("lat"=> $timeCheck[$timeCheckMD5]["minLat"],
                                                                   "lon"=> $timeCheck[$timeCheckMD5]["maxLon"]);
                    $timeCheck[$timeCheckMD5]["polygon"][] = array("lat"=> $timeCheck[$timeCheckMD5]["maxLat"],
                                                                   "lon"=> $timeCheck[$timeCheckMD5]["maxLon"]);
                    $timeCheck[$timeCheckMD5]["polygon"][] = array("lat"=> $timeCheck[$timeCheckMD5]["maxLat"],
                                                                   "lon"=> $timeCheck[$timeCheckMD5]["minLon"]);
                    
                    $contextURL = str_replace("/".urlencode($actContext["name"]), "", $actContext["href"]);
                    
                    $innerHTML = "<br/>";
                    $innerHTML .= "<div style='width:345px;'>";
                    $innerHTML .= "<div style='float:left; width:40px;'><img src='/images/item_view/project_icon.jpg' border='0' ></img>";
                    $innerHTML .= "</div>";
                    $innerHTML .= "<div style='float:right; width:300px; margin-left:4px;'>";
                    $innerHTML .= "<p class='bodyText'>As part of the <a href='".$projJSON["href"]."'><em>".$projJSON["name"]."</em></a> project, there are several contexts including: <strong>".$timeCheck[$timeCheckMD5]["contNames"]."</strong>";
                    //$innerHTML .= " contain ".$timeCheck[$timeCheckMD5]["itemTotal"]." items.</p>";
                    $innerHTML .= "</div>";
                    $innerHTML .= "<div style='clear:both; width:100%; margin-left:44px;'>";
                    $innerHTML .= "<p class='bodyText'>(<a href='".$contextURL."'>Click here</a>) to browse contexts in this project.</p>";
                    $innerHTML .= "</div>";
                    $innerHTML .= "</div>";
                    $timeCheck[$timeCheckMD5]["innerHTML"] = $innerHTML;
                    
                }
                
            }//
            
	    
            //needed for chrome bug fix
            $useragent = @$_SERVER['HTTP_USER_AGENT'];
	    //chrome bug fix
	    
            foreach($projJSON["contexts"] as $actContext){
                
                if($itemCnt < $contextMaxCount){
                    $actItem = array();
                    $actItem["start"] = $actContext["timespan"]["begin"]."";
                    $actItem["end"] = $actContext["timespan"]["end"]."";
                    
                    if($actItem["start"]<$minDate){
                        $minDate = $actItem["start"];
                    }
                    if($actItem["end"]>$maxDate){
                        $maxDate = $actItem["end"];
                    }
                    
                    if(strstr($useragent,"Chrome")){
			if($actItem["start"]+0 < 0){
			    $actItem["start"] = abs($actItem["start"])." BC";
			}
			if($actItem["end"]+0 < 0){
			    $actItem["end"] = abs($actItem["end"])." BC";
			}
		    }
		    
                    if(array_key_exists("lat", $actContext["geopoint"])){
                        $actItem["point"]["lat"] = $actContext["geopoint"]["lat"];
                        $actItem["point"]["lon"] = $actContext["geopoint"]["long"];
                        $latSum = $latSum + $actItem["point"]["lat"];
                        $lonSum = $lonSum + $actItem["point"]["lon"];
                        $actItem["title"] = $actContext["name"];
                        $innerHTML = "<br/>";
                        $innerHTML .= "<div style='width:345px;'>";
                        $innerHTML .= "<div style='float:left; width:40px;'><img src='/images/item_view/project_icon.jpg' border='0' ></img>";
                        $innerHTML .= "</div>";
                        $innerHTML .= "<div style='float:right; width:300px; margin-left:4px;'>";
                        $innerHTML .= "<p class='bodyText'>As part of the <a href='".$projJSON["href"]."'><em>".$projJSON["name"]."</em></a> project, <strong>".$actContext["name"]."</strong> contains ".$actContext["item_count"]." items.</p>";
                        $innerHTML .= "</div>";
                        $innerHTML .= "<div style='clear:both; width:100%; margin-left:44px;'>";
                        $innerHTML .= "<p class='bodyText'>(<a href='".$actContext["href"]."'>Click here</a>) to browse within this context.</p>";
                        $innerHTML .= "</div>";
                        $innerHTML .= "</div>";
                        //$innerHTML = htmlentities($innerHTML);
                        $actItem["options"]["infoHtml"] = $innerHTML;
                        
                        $timeCheckMD5 = md5(($actContext["timespan"]["begin"]).($actContext["timespan"]["end"]));
                        if(($timeCheck[$timeCheckMD5]["count"]>1)&&(count($projJSON["contexts"])> $contextMaxCount)){
                            if(!$timeCheck[$timeCheckMD5]["used"]){
                                unset($actItem["point"]);
                                $actItem["point"] = $timeCheck[$timeCheckMD5]["point"];
                                $actItem["title"] = "Several ".$projJSON["name"]." contexts";
                                $actItem["polygon"] = $timeCheck[$timeCheckMD5]["polygon"];
                                $actItem["options"]["infoHtml"] = $timeCheck[$timeCheckMD5]["innerHTML"];
                                $timeCheck[$timeCheckMD5]["used"] = true;
                                $TMitems[] = $actItem;    
                            }
                            else{
                            //do nothing    
                            }
                        }
                        else{
                            $TMitems[] = $actItem;
                        }
                        
                    }
                }
                else{
                    $remainderCnt = $remainderCnt + $actContext["item_count"]; // counts up items not in other contexts
                }
                
                $itemCnt++;
            }//end loop through contexts 
            
            if($itemCnt >= $contextMaxCount){
                $contextURL = str_replace("/".urlencode($actContext["name"]), "", $actContext["href"]);
                
                $actItem = array();
		
		if(strstr($useragent,"Chrome")){
		    if($minDate+0 < 0){
			$minDate = abs($minDate)." BC";
		    }
		    if($maxDate+0 < 0){
			$maxDate = abs($maxDate)." BC";
		    }
		}
		
                 $actItem["start"] = $minDate."";
                 $actItem["end"] = $maxDate."";
                 $actItem["point"]["lat"] = ($latSum)/$contextMaxCount;
                 $actItem["point"]["lon"] = ($lonSum)/$contextMaxCount;
                 $actItem["title"] = "Other ".($itemCnt-$contextMaxCount)." contexts in ".$projJSON["name"];
                 $innerHTML = "<br/>";
                 $innerHTML .= "<div style='width:345px;'>";
                 $innerHTML .= "<div style='float:left; width:40px;'><img src='/images/item_view/project_icon.jpg' border='0' ></img>";
                 $innerHTML .= "</div>";
                 $innerHTML .= "<div style='float:right; width:300px; margin-left:4px;'>";
                 $innerHTML .= "<p class='bodyText'>As part of the <a href='".$projJSON["href"]."'><em>".$projJSON["name"]."</em></a> project, the remaining ".($itemCnt-$contextMaxCount)." contain ".$remainderCnt." items.</p>";
                 $innerHTML .= "</div>";
                 $innerHTML .= "<div style='clear:both; width:100%; margin-left:44px;'>";
                 $innerHTML .= "<p class='bodyText'>(<a href='".$contextURL."'>Click here</a>) to browse these.</p>";
                 $innerHTML .= "</div>";
                 $innerHTML .= "</div>";
                 //$innerHTML = "";
                // $innerHTML = htmlentities($innerHTML);
                 $actItem["options"]["infoHtml"] = $innerHTML;
                 //$TMitems[] = $actItem;
            }
            
            
            
            
            
        }//end loop through projects
        $useragent = @$_SERVER['HTTP_USER_AGENT'];
        $output = Zend_Json::encode($TMitems);
	
	if($callback){
	    $output = $callback."(".$output.");";
	}
	
	
        $offset = 60 * 60 * 60 * 60;
        $expire = "Expires: " . gmdate("D, d M Y H:i:s", time() + $offset). " GMT";
        $encoding = $this->check_compress_ok($_SERVER['HTTP_ACCEPT_ENCODING']);
        if(!$encoding){
            header('Content-Type: application/json; charset=utf8');
            header ("Cache-Control:max-age=290304000, public");
            header ($expire);
            print $output;
        }
        elseif(strstr($useragent,"MSIE 6")||strstr($useragent,"MSIE")){
            //header('Content-Type: application/json; charset=utf8');
            print $output;
        }
	elseif(strstr($useragent,"Android")){
	    header('Content-Type: application/json; charset=utf8');
	    header ("Cache-Control:max-age=290304000, public");
            header ($expire);
            print $output;
	}
        else{
            header('Content-Type: application/json; charset=utf8');
            header ("Cache-Control:max-age=290304000, public");
            header ($expire);
            header('Content-Encoding: '.$encoding);
            print("\x1f\x8b\x08\x00\x00\x00\x00\x00");
            print gzcompress($output, 9);
        }
        
        
        
        
    */
    }
    
    
    
    
    
    private function check_compress_ok($HTTP_ACCEPT_ENCODING){
    
        if( headers_sent() ){
            $encoding = false;
        }elseif( strpos($HTTP_ACCEPT_ENCODING, 'x-gzip') !== false ){
            $encoding = 'x-gzip';
        }elseif( strpos($HTTP_ACCEPT_ENCODING,'gzip') !== false ){
            $encoding = 'gzip';
        }else{
            $encoding = false;
        }
        return $encoding; 
    }
    
    
    
}