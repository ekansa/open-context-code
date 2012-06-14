<?php
/** Zend_Controller_Action */
require_once 'Zend/Controller/Action.php';
ini_set("memory_limit", "2048M");
ini_set("max_execution_time", "0");

class allController extends Zend_Controller_Action
{   
  public function indexAction(){
    //$this->_helper->viewRenderer->setNoRender();
    
    $requestParams =  $this->_request->getParams();
    if(isset($requestParams['page'])){
      $page = $requestParams['page'];
    }
    else{
      $page = 1;
    }
    
    $host = OpenContext_OCConfig::get_host_config();
    
    $archiveFeed = new ArchiveFeed;
    $archiveFeed->set_up_feed_page($page);
    $archiveFeed->getItemList();
    $this->view->archive =  $archiveFeed;
    
  }//end function
    
  
   public function atomAction(){
    $this->_helper->viewRenderer->setNoRender();
    
    //check for referring links
    OpenContext_SocialTracking::update_referring_link('all_feed', $this->_request->getRequestUri(), @$_SERVER['HTTP_USER_AGENT'], @$_SERVER['HTTP_REFERER']);

    $requestParams =  $this->_request->getParams();
    if(isset($requestParams['page'])){
      $page = $requestParams['page'];
    }
    else{
      $page = 1;
    }
    
    $host = OpenContext_OCConfig::get_host_config();
    $archiveFeed = new ArchiveFeed;
    $archiveFeed->set_up_feed_page($page);
    
    header('Content-type: application/atom+xml', true);
    echo $archiveFeed->generateFeed();
    
  }//end function
  
  
  public function personAction(){
    $this->_helper->viewRenderer->setNoRender();
    $person = New Person;
    echo $person->getItemEntry("642_DT_Person");
    
    
    
  }
  
  public function spaceAction(){
    $this->_helper->viewRenderer->setNoRender();
    $requestParams =  $this->_request->getParams();
    if(isset($requestParams['batch'])){
      $batch = $requestParams['batch'];
    }
    else{
      $batch = 0;
    }
    $archiveFeed = new ArchiveFeed;
    $archiveFeed->set_up_feed_page(1);
    echo "Start Batch: ".$batch;
    $batch = $archiveFeed->insertSpatial($batch);
    echo "<br/>Done: ".$batch;
    echo "<br/><a href='http://opencontext.org/all/space?batch=".$batch."'>next...</a>";
  }
  
  public function mediaAction(){
    $this->_helper->viewRenderer->setNoRender();
    $requestParams =  $this->_request->getParams();
    if(isset($requestParams['batch'])){
      $batch = $requestParams['batch'];
    }
    else{
      $batch = 0;
    }
    $archiveFeed = new ArchiveFeed;
    $archiveFeed->set_up_feed_page(1);
    echo "Start Batch: ".$batch;
    $batch = $archiveFeed->insertMedia();
    echo "<br/>Done: ".$batch;
    //echo "<br/><a href='http://opencontext.org/all/space?batch=".$batch."'>next...</a>";
  }
  
   
}//end of class
