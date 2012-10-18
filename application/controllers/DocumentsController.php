<?php
/** Zend_Controller_Action */
require_once 'Zend/Controller/Action.php';


class documentsController extends Zend_Controller_Action
{   
      
    public function indexAction(){
		  OpenContext_SocialTracking::update_referring_link('document_index', $this->_request->getRequestUri(), @$_SERVER['HTTP_USER_AGENT'], @$_SERVER['HTTP_REFERER']);
		  $requestParams =  $this->_request->getParams();
		  if(isset($requestParams['page'])){
			 $page = $requestParams['page'];
		  }
		  else{
			 $page = 1;
		  }
		  
		  $host = OpenContext_OCConfig::get_host_config();
		  
		  $archiveFeed = new ArchiveFeed;
		  $archiveFeed->set_up_feed_page($page, "document");
		  $archiveFeed->getItemList();
		  $this->view->archive =  $archiveFeed;
    }
    
    
    public function viewAction() {
		
		
		  // get the space uuid from the uri
		  $itemUUID = $this->_request->getParam('uuid');
		  
		  //check for referring links
		  OpenContext_SocialTracking::update_referring_link('documents', $this->_request->getRequestUri(), @$_SERVER['HTTP_USER_AGENT'], @$_SERVER['HTTP_REFERER']);
		  
		  $mediaItem = New Document;
		  $itemFound = $mediaItem->getByID($itemUUID);
		  if($itemFound){
		  		$XML = simplexml_load_string($mediaItem->archaeoML);
				$XML = OpenContext_ProjectReviewAnnotate::addProjectReviewStatus($mediaItem->projectUUID, $XML, $mediaItem->nameSpaces());
				$mediaItem->archaeoML = $XML->asXML();
				$media_dom = new DOMDocument("1.0", "utf-8");
				$media_dom->loadXML($mediaItem->archaeoML);
				OpenContext_MediaAtom::update_view_count($media_dom, $mediaItem->viewCount, $mediaItem->nameSpaces() );   
				$xml_string = $media_dom->saveXML();
				$this->view->xml_string = $xml_string;
		  }
		  else{
				$this->view->requestURI = $this->_request->getRequestUri(); 
				return $this->render('404error');
		  }
	
	}
    
    
    public function atomAction() {
	
		  $itemUUID = $this->_request->getParam('uuid');
		  
		  if($itemUUID && $itemUUID != ".atom"){
				$mediaItem = New Document;
				$itemFound = $mediaItem->getByID($itemUUID);
				if($itemFound){
					 $this->view->xml_string = $mediaItem->atomEntry;
				}
				else{
					 $this->view->requestURI = $this->_request->getRequestUri(); 
					 return $this->render('404error');
				}
		  }
		  else{
				$this->_helper->viewRenderer->setNoRender();
				$requestParams =  $this->_request->getParams();
				if(isset($requestParams['page'])){
					 $page = $requestParams['page'];
				}
				else{
					 $page = 1;
				}
				  
				$host = OpenContext_OCConfig::get_host_config();
				$archiveFeed = new ArchiveFeed;
				$archiveFeed->set_up_feed_page($page, "document");
				header('Content-type: application/atom+xml', true);
				echo $archiveFeed->generateFeed();
		  }
	 }
    
	
    public function xmlAction() {
		  $itemUUID = $this->_request->getParam('uuid');
		  
		  $mediaItem = New Document;
		  $itemFound = $mediaItem->getByID($itemUUID);
		  if($itemFound){
				$XML = simplexml_load_string($mediaItem->archaeoML);
				$XML = OpenContext_ProjectReviewAnnotate::addProjectReviewStatus($mediaItem->projectUUID, $XML, $mediaItem->nameSpaces());
				$mediaItem->archaeoML = $XML->asXML();
				$this->view->xml_string = $mediaItem->archaeoML;
		  }
		  else{
				$this->view->requestURI = $this->_request->getRequestUri(); 
				return $this->render('404error');
		  }
    }
    
    
    
}

