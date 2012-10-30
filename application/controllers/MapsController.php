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
	
		  $projectsObj = new Projects;
		  $timeMap = $projectsObj->getMakeTimeMap();
		  
		  header('Content-Type: application/json; charset=utf8');
		  echo Zend_Json::encode($timeMap);
	
	 /*
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