<?php
/** Zend_Controller_Action */
require_once 'Zend/Controller/Action.php';
//ini_set("memory_limit", "1024M");

ini_set('memory_limit', '512M');
ini_set("max_execution_time", "0");
ini_set('default_socket_timeout',    240);


//session_start();
class tabllesController extends Zend_Controller_Action {
  
  
  public function viewAction(){
    
    $tableID = $_GET["tableID"];
    if(isset($_GET["partID"])){
      $partID =  $_GET["tableID"];
    }
    
    $partID =  $_GET["tableID"];
    
    $exTableObj = new ExportTable;
    $found = $exTableObj->getByID($tableID);
    if($found){
      if(isset($_GET["page"])){
        $exTableObj->recPage = $_GET["page"];
      }
      $this->view->exTableObj = $exTableObj;
      //return $this->render('tview');
    }
    else{
      $this->view->requestURI = $this->_request->getRequestUri(); 
		return $this->render('404error');
    }
    
    $this->_helper->viewRenderer->setNoRender();
    $outJSON = Zend_Json::encode($exTableObj);
    header('Content-Type: application/json; charset=utf8');
    echo $outJSON;
  }
  

  public function jsonLdAction(){
    
    $tableID = $_GET["tableID"];
    if(isset($_GET["partID"])){
      $partID =  $_GET["tableID"];
    }
    
    $partID =  $_GET["tableID"];
    
    $exTableObj = new ExportTable;
    $found = $exTableObj->getByID($tableID);
    if($found){
      if(isset($_GET["page"])){
        $exTableObj->recPage = $_GET["page"];
      }
      $this->view->exTableObj = $exTableObj;
      //return $this->render('tview');
    }
    else{
      $this->view->requestURI = $this->_request->getRequestUri(); 
		return $this->render('404error');
    }
    
    $this->_helper->viewRenderer->setNoRender();
    $outJSON = Zend_Json::encode($exTableObj->metadata);
    header('Content-Type: application/json; charset=utf8');
    echo $outJSON;
  }
  
  //returns a csv file
  public function csvAction(){
    
    $tableID = $_GET["tableID"];
    if(isset($_GET["partID"])){
      $partID =  $_GET["partID"];
    }
    
    $file = false;
    $exTableObj = new ExportTable;
    $found = $exTableObj->getByID($tableID);
    if($found){
      $file = $exTableObj->retrieveFile("csv");
    }
    else{
      $this->view->requestURI = $this->_request->getRequestUri(); 
		return $this->render('404error');
    }
   
    if(!$file){
      $this->view->requestURI = $this->_request->getRequestUri(); 
		return $this->render('500error');
    }
    else{
      $this->_helper->viewRenderer->setNoRender();
      header('Content-type: text/csv');
      header('Content-disposition: attachment; filename=OpenContext.csv');
      ob_clean();
      flush();
      readfile($file);
      exit;
    }
  }
  
  //returns a zip file
  public function zipAction(){
    
    $tableID = $_GET["tableID"];
    if(isset($_GET["partID"])){
      $partID =  $_GET["partID"];
    }
    
    $file = false;
    $exTableObj = new ExportTable;
    $found = $exTableObj->getByID($tableID);
    if($found){
      $file = $exTableObj->retrieveFile("zip");
    }
    else{
      $this->view->requestURI = $this->_request->getRequestUri(); 
		return $this->render('404error');
    }
   
    if(!$file){
      $this->view->requestURI = $this->_request->getRequestUri(); 
		return $this->render('500error');
    }
    else{
      $this->_helper->viewRenderer->setNoRender();
      header('Content-type: application/zip');
      header('Content-disposition: attachment; filename=OpenContext.zip');
      ob_clean();
      flush();
      readfile($file);
      exit;
    }
  }
  
  //returns a gzip file
  public function gzipAction(){
    
    $tableID = $_GET["tableID"];
    if(isset($_GET["partID"])){
      $partID =  $_GET["partID"];
    }
    
    $file = false;
    $exTableObj = new ExportTable;
    $found = $exTableObj->getByID($tableID);
    if($found){
      $file = $exTableObj->retrieveFile("gzip");
    }
    else{
      $this->view->requestURI = $this->_request->getRequestUri(); 
		return $this->render('404error');
    }
   
    if(!$file){
      $this->view->requestURI = $this->_request->getRequestUri(); 
		return $this->render('500error');
    }
    else{
      $this->_helper->viewRenderer->setNoRender();
      header('Content-type: application/x-gzip');
      header('Content-disposition: attachment; filename=OpenContext.gz');
      ob_clean();
      flush();
      readfile($file);
      exit;
    }
  }
  
  
  
  //returns a json file of the whole dataset
  public function jsonAction(){
    
    $tableID = $_GET["tableID"];
    if(isset($_GET["partID"])){
      $partID =  $_GET["partID"];
    }
    
    $file = false;
    $exTableObj = new ExportTable;
    $found = $exTableObj->getByID($tableID);
    if($found){
      $file = $exTableObj->retrieveFile("json");
    }
    else{
      $this->view->requestURI = $this->_request->getRequestUri(); 
		return $this->render('404error');
    }
   
    if(!$file){
      $this->view->requestURI = $this->_request->getRequestUri(); 
		return $this->render('500error');
    }
    else{
      if(isset($_SERVER['HTTP_ACCEPT_ENCODING'])){
        $encoding = $this->check_compress_ok($_SERVER['HTTP_ACCEPT_ENCODING']);
        $compressOK = true;
      }
      else{
        $encoding = 'gzip';
        $compressOK = false;
      }
      
      $this->_helper->viewRenderer->setNoRender();
      header('Content-Type: application/json; charset=utf8');
      header ("Cache-Control:max-age=290304000, public");
      if($compressOK ){
        header('Content-Encoding: '.$encoding);
        print("\x1f\x8b\x08\x00\x00\x00\x00\x00");
        //$filestring = readfile($file);
        $filestring = 1;
        print gzcompress($filestring, 9);
        unset($filestring);
      }
      else{
        readfile($file);
      }
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
  
  
}//end class
