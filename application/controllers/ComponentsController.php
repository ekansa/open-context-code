<?php
/** Zend_Controller_Action */
require_once 'Zend/Controller/Action.php';

/*
This is used to consolidate different css and js files and then g-zip them for fast 
page loads
*/
class componentsController extends Zend_Controller_Action
{   
  public function cssAction(){
    
    $this->_helper->viewRenderer->setNoRender();
    $pageID = $this->_request->getParam('pageID');
    $offset = 60 * 60 * 60;
    $expire = "expires: " . gmdate ("D, d M Y H:i:s", time() + $offset) . " GMT";
  

    $encoding = $this->check_compress_ok($_SERVER['HTTP_ACCEPT_ENCODING']);
    if(!$encoding){
      $components = OpenContext_Components::get_components($pageID, "css");
      header ("content-type: text/css; charset: UTF-8");
      header ("Cache-Control:max-age=290304000, public");
      header ($expire);
      echo $components;
    }
    else{
      $GZIP_comp = OpenContext_Components::get_gzip_comp_data($pageID, "css");
      header ("content-type: text/css; charset: UTF-8");
      header ("Cache-Control:max-age=290304000, public");
      header ($expire);
      header('Content-Encoding: '.$encoding);
      print("\x1f\x8b\x08\x00\x00\x00\x00\x00");
      print $GZIP_comp;
    }
  }
  
  public function jsAction(){
    
    $this->_helper->viewRenderer->setNoRender();
    $pageID = $this->_request->getParam('pageID');
    
    $offset = 60 * 60 * 60 * 60;
    $expire = "Expires: " . gmdate("D, d M Y H:i:s", time() + $offset). " GMT";
  

    $encoding = $this->check_compress_ok($_SERVER['HTTP_ACCEPT_ENCODING']);
    //$encoding = false;
    
    if(!$encoding){
      $components = OpenContext_Components::get_components($pageID, "js");
      
      if(substr_count($pageID, ".css")>0){
	header ("content-type: text/css; charset: UTF-8");
      }
      else{
	header ("content-type: text/javascript; charset: UTF-8");
      }
      
      header ("Cache-Control:max-age=290304000, public");
      header ($expire);
      echo $components;
    }
    else{
      $GZIP_comp = OpenContext_Components::get_gzip_comp_data($pageID, "js");
      if(substr_count($pageID, ".css")>0){
	header ("content-type: text/css; charset: UTF-8");
      }
      else{
	header ("content-type: text/javascript; charset: UTF-8");
      }
      header ("Cache-Control:max-age=290304000, public");
      header ($expire);
      header('Content-Encoding: '.$encoding);
      print("\x1f\x8b\x08\x00\x00\x00\x00\x00");
      print $GZIP_comp;
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
}//end of class
