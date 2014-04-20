<?php
/** Zend_Controller_Action */
require_once 'Zend/Controller/Action.php';


class mediaController extends Zend_Controller_Action
{   
      
    public function indexAction(){
		  OpenContext_SocialTracking::update_referring_link('media_index', $this->_request->getRequestUri(), @$_SERVER['HTTP_USER_AGENT'], @$_SERVER['HTTP_REFERER']);
		  $requestParams =  $this->_request->getParams();
		  if(isset($requestParams['page'])){
			 $page = $requestParams['page'];
		  }
		  else{
			 $page = 1;
		  }
		  
		  $host = OpenContext_OCConfig::get_host_config();
		  
		  $archiveFeed = new ArchiveFeed;
		  $archiveFeed->set_up_feed_page($page, "media");
		  $archiveFeed->getItemList();
		  $this->view->archive =  $archiveFeed;
    }
    
    
    public function viewAction() {
		  OpenContext_SocialTracking::update_referring_link('media', $this->_request->getRequestUri(), @$_SERVER['HTTP_USER_AGENT'], @$_SERVER['HTTP_REFERER']);
		  
		  $itemUUID = $this->_request->getParam('uuid');
		  
		  $mediaItem = New Media;
		  $itemFound = $mediaItem->getByID($itemUUID);
		  if($itemFound){
			  
				$crawler = OpenContext_SocialTracking::crawlerDetect(@$_SERVER['HTTP_USER_AGENT']);
				if(!$crawler){
					 $mediaItem->addViewCount();
				}
				@$XML = simplexml_load_string($mediaItem->archaeoML);
				if(!$XML){
					$mediaItem->archaeoML  = iconv('UTF-8', 'UTF-8//IGNORE', $mediaItem->archaeoML );
					$XML = simplexml_load_string($mediaItem->archaeoML);
				}
				$XML = OpenContext_ProjectReviewAnnotate::addProjectReviewStatus($mediaItem->projectUUID, $XML, $mediaItem->nameSpaces());
				$mediaItem->archaeoML = $XML->asXML();
				
				$media_dom = new DOMDocument("1.0", "utf-8");
				$media_dom->loadXML($mediaItem->archaeoML);
				OpenContext_MediaAtom::update_view_count($media_dom, $mediaItem->viewCount, $mediaItem->nameSpaces());   
				$xml_string = $media_dom->saveXML();
				
				$this->view->xml_string = $xml_string;
				$this->view->label = $mediaItem->label;
				$this->view->itemUUID = $itemUUID;
				
		  }
		  else{
				$this->view->requestURI = $this->_request->getRequestUri(); 
				return $this->render('404error');
		  }
       
    }
    
    
    public function fullviewAction() {
		  OpenContext_SocialTracking::update_referring_link('media', $this->_request->getRequestUri(), @$_SERVER['HTTP_USER_AGENT'], @$_SERVER['HTTP_REFERER']);
		  
		  $itemUUID = $this->_request->getParam('uuid');
		  
		  $mediaItem = New Media;
		  $itemFound = $mediaItem->getByID($itemUUID);
		  if($itemFound){
				
				$crawler = OpenContext_SocialTracking::crawlerDetect(@$_SERVER['HTTP_USER_AGENT']);
				if(!$crawler){
					 $mediaItem->addViewCount();
				}
				
				@$XML = simplexml_load_string($mediaItem->archaeoML);
				if(!$XML){
					$mediaItem->archaeoML  = iconv('UTF-8', 'UTF-8//IGNORE', $mediaItem->archaeoML );
				}
				
				$media_dom = new DOMDocument("1.0", "utf-8");
				$media_dom->loadXML($mediaItem->archaeoML);
				$nameSpaceArray = $mediaItem->nameSpaces();
				OpenContext_MediaAtom::update_view_count($media_dom, $mediaItem->viewCount, $nameSpaceArray);   
				$xml_string = $media_dom->saveXML();
				
				$this->view->xml_string = $xml_string;
				$this->view->dom = $media_dom;
				$this->view->label = $mediaItem->label;
				$this->view->itemUUID = $itemUUID;
				
				if(!stristr($mediaItem->mimeType, 'image')){
					 $isImage = false;
					 //now check if it's in the XML
					 $xpath = new DOMXpath($media_dom);
					 // Register OpenContext's namespace
					 foreach($nameSpaceArray as $prefix => $uri){
						 $xpath->registerNamespace($prefix, $uri);
					 }
					 $query = "//arch:externalFileInfo/arch:fileFormat";
					  
					 $result = $xpath->query($query, $media_dom);
					 if($result != null){
						  $mainMime = $result->item(0)->nodeValue;
						  if(!strstr($mainMime, "/")){
								$mainMime = $mediaItem->mime_type_clean($mainMime);
						  }
						  if(stristr($mainMime, 'image')){
								$isImage = true;
						  }
					 }
			  
					 if(!$isImage){
						  $this->_helper->viewRenderer->setNoRender();
						  echo "not image";
					 }
				}
				
		  }
		  else{
				$this->view->requestURI = $this->_request->getRequestUri(); 
				return $this->render('404error');
		  }
       
    }
    
    
    
    public function atomAction() {
	
		  $itemUUID = $this->_request->getParam('uuid');
		  if($itemUUID && $itemUUID != ".atom"){   
				$mediaItem = New Media;
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
				$archiveFeed->set_up_feed_page($page, "media");
				
				header('Content-type: application/atom+xml', true);
				echo $archiveFeed->generateFeed();
		  }
    }
    
	
   public function xmlAction() {
		  $itemUUID = $this->_request->getParam('uuid');
		  
		  $mediaItem = New Media;
		  $itemFound = $mediaItem->getByID($itemUUID);
		  if($itemFound){
				@$XML = simplexml_load_string($mediaItem->archaeoML);
				if(!$XML){
					$mediaItem->archaeoML  = iconv('UTF-8', 'UTF-8//IGNORE', $mediaItem->archaeoML );
					$XML = simplexml_load_string($mediaItem->archaeoML);
				}
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

