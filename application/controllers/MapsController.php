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
        $output = Zend_Json::encode($timeMap);
	
		  if($callback){
				$output = $callback."(".$output.");";
		  }
	
        $offset = 60 * 60 * 60 * 60;
        $expire = "Expires: " . gmdate("D, d M Y H:i:s", time() + $offset). " GMT";
        $encoding = $this->check_compress_ok($_SERVER['HTTP_ACCEPT_ENCODING']);
		  $encoding = false;
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