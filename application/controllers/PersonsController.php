<?php
/** Zend_Controller_Action */
require_once 'Zend/Controller/Action.php';


class personsController extends Zend_Controller_Action
{   
      
    public function indexAction(){
		  OpenContext_SocialTracking::update_referring_link('person_index', $this->_request->getRequestUri(), @$_SERVER['HTTP_USER_AGENT'], @$_SERVER['HTTP_REFERER']);
		  $requestParams =  $this->_request->getParams();
		  if(isset($requestParams['page'])){
			 $page = $requestParams['page'];
		  }
		  else{
			 $page = 1;
		  }
		  
		  $host = OpenContext_OCConfig::get_host_config();
		  
		  $archiveFeed = new ArchiveFeed;
		  $archiveFeed->set_up_feed_page($page, "person");
		  $archiveFeed->getItemList();
		  $this->view->archive =  $archiveFeed;
    }
    
    
    public function viewAction() {
		
		  // get the space uuid from the uri
		  $itemUUID = $this->_request->getParam('person_uuid');
		  
		  //check for referring links
		  OpenContext_SocialTracking::update_referring_link('persons', $this->_request->getRequestUri(), @$_SERVER['HTTP_USER_AGENT'], @$_SERVER['HTTP_REFERER']);
		  $person = New Person;
		  $itemFound =  $person->getByID($itemUUID);
		  if($itemFound){
				$crawler = OpenContext_SocialTracking::crawlerDetect(@$_SERVER['HTTP_USER_AGENT']);
				if(!$crawler){
					 $view_count = $person->addViewCount();
				}
				else{
					 $view_count = $person->viewCount;
				}
				$pers_atom = OpenContext_OCConfig::updateNamespace($person->atomEntry, $itemUUID , "atom_entry", "person");
				$rank = OpenContext_SocialTracking::rank_person_viewcounts($itemUUID);
				$xml_string = OpenContext_PersonAtom::atom_entry_feed($pers_atom, $view_count, $person->spViewCount, $rank);
				$XML = simplexml_load_string($xml_string);
				$XML = OpenContext_ProjectReviewAnnotate::addProjectReviewStatus($person->projectUUID, $XML, $person->nameSpaces());
				$xml_string = $XML->asXML();
		  }
		  else{
				$this->view->requestURI = $this->_request->getRequestUri(); 
				return $this->render('404error');
        }    
        $this->view->xml_string = $xml_string;   
	}
    
    
    public function atomAction() {
		// get the person uuid from the uri
		  $itemUUID = $this->_request->getParam('person_uuid');
						
		  if($itemUUID && $itemUUID != ".atom"){
				
				$person = New Person;
				$itemFound =  $person->getByID($itemUUID);
				if($itemFound){
			  
					 $view_count = $person->viewCount;
					 
					 $pers_atom = OpenContext_OCConfig::updateNamespace($person->atomEntry, $itemUUID , "atom_entry", "person");
					 $rank = OpenContext_SocialTracking::rank_person_viewcounts($itemUUID);
					 $xml_string = $person->rank_count_atom_entry_feed($pers_atom, $view_count, $person->spViewCount, $rank);
					 
					 $XML = simplexml_load_string($xml_string);
					 $XML = OpenContext_ProjectReviewAnnotate::addProjectReviewStatus($person->projectUUID, $XML, $person->nameSpaces());
					 $xml_string = $XML->asXML();
					 
					 $this->view->xml_string = $xml_string;
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
				$archiveFeed->set_up_feed_page($page, "person");
				
				header('Content-type: application/atom+xml', true);
				echo $archiveFeed->generateFeed();
		  }
		
	}//end atom function
    
    public function jsonAction() {
		  // get the person uuid from the uri
		  $itemUUID = $this->_request->getParam('person_uuid');
		  
		  $person = New Person;
		  $itemFound =  $person->getByID($itemUUID);
		  if($itemFound){
				
				$view_count = $person->viewCount;
	  
				$pers_atom = OpenContext_OCConfig::updateNamespace($person->atomEntry, $itemUUID , "atom_entry", "person");
				$rank = OpenContext_SocialTracking::rank_person_viewcounts($itemUUID);
				$xml_string = $person->rank_count_atom_entry_feed($pers_atom, $view_count, $person->spViewCount, $rank);
				$json_string = OpenContext_PersonAtom::atom_entry_to_json($xml_string);
				
				$this->view->json_string = $json_string;
		  }
		  else{
				$this->view->requestURI = $this->_request->getRequestUri(); 
				return $this->render('404error');
		  }
                
    }//end atom function
    
    
    
    public function xmlAction() {
		  // get the person uuid from the uri
		  $uuid_query = $this->_request->getParam('person_uuid');
		  
		  $person = New Person;
		  $itemFound = $person->getByID($uuid_query);
		  
		  if($itemFound){
				$this->_helper->viewRenderer->setNoRender();
				header('Content-type: application/xml', true);
				
				//$person->archaeoML = $person->addLinkedData($person->archaeoML, "http://xmlns.com/foaf/0.1/isPrimaryTopicOf", "http://orcid.org/0000-0001-7920-5321");
				//OpenContext_NewDocs::personAdd($person->archaeoML);
				
				$XML = simplexml_load_string($person->archaeoML);
				$XML = OpenContext_ProjectReviewAnnotate::addProjectReviewStatus($person->projectUUID, $XML, $person->nameSpaces());
				$person->archaeoML = $XML->asXML();
				
				echo $person->archaeoML;
		  }
		  else{
				$this->view->requestURI = $this->_request->getRequestUri(); 
				return $this->render('404error');
		  }
    }
    
}

