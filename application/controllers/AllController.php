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
    mb_internal_encoding( 'UTF-8' );
    $host = OpenContext_OCConfig::get_host_config();
    $archiveFeed = new ArchiveFeed;
    $archiveFeed->set_up_feed_page($page);
    if(!$archiveFeed->feedItems){
      $this->view->requestURI = $host.$this->_request->getRequestUri();
		return $this->render('404error'); // page not found
    }
    
    if($page > 1 && count($archiveFeed->feedItems) < 1){
      $this->view->requestURI = $host.$this->_request->getRequestUri();
		return $this->render('404error'); // page not found
    }
    else{
      header('Content-type: application/atom+xml; charset=utf-8', true);
      echo $archiveFeed->generateFeed();
    }
    
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
    mb_internal_encoding( 'UTF-8' );
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
  
  
  public function siteMapAction(){
    $this->_helper->viewRenderer->setNoRender();
    
    $siteMapObj = new SiteMap;
    
    /*
    $siteMapObj->startDB();
    $siteMapObj->getMaxRankings();
    $siteMapObj->get_items();
    $siteMapObj->adjust_rankings();
    */
    
    
    //header('Content-Type: application/javascript; charset=utf8');
    //echo Zend_Json::encode($siteMapObj->itemList);
    
    header('Content-Type: application/xml; charset=utf-8');
    echo $siteMapObj->get_make_sitemap();
  }
  
  public function solrAction(){
		  $this->_helper->viewRenderer->setNoRender();
		  $solrQuery = "http://localhost:8983/solr/select?facet=true&facet.mincount=1&fq=%7B%21cache%3Dfalse%7Ditem_class%3A*++%26%26+NOT+project_id%3A0+%26%26+%28+%28item_type%3Aspatial%29+%29&facet.field=def_context_0&facet.field=project_name&facet.field=item_class&facet.field=time_path&facet.field=geo_point&facet.field=top_taxon&facet.field=geo_path&facet.query=image_media_count%3A%5B1+TO+%2A%5D&facet.query=other_binary_media_count%3A%5B1+TO+%2A%5D&facet.query=diary_count%3A%5B1+TO+%2A%5D&sort=interest_score+desc&wt=json&json.nl=map&q=%28%2A%3A%2A%29+%26%26+%28geo_path%3A0%2A%29-def_context_0%3A%5B%22%22+TO+*%5D&start=0&rows=1500";
		  
		  header('Content-Type: application/json; charset=utf8');
		  echo file_get_contents($solrQuery);
	 }
  
  
}//end of class
