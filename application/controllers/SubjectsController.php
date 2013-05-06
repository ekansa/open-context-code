<?php

/** Zend_Controller_Action */
//require_once 'Zend/Controller/Action.php';
//require_once 'OpenContext/Controller/Action/Helper/SolrAccess.php';

//error_reporting(E_ALL ^ E_NOTICE);
class subjectsController extends Zend_Controller_Action {


	//index of all items
	public function indexAction() {

		OpenContext_SocialTracking::update_referring_link('space_index', $this->_request->getRequestUri(), @$_SERVER['HTTP_USER_AGENT'], @$_SERVER['HTTP_REFERER']);
		$requestParams =  $this->_request->getParams();
		if(isset($requestParams['page'])){
		  $page = $requestParams['page'];
		}
		else{
		  $page = 1;
		}
		
		$host = OpenContext_OCConfig::get_host_config();
		
		$archiveFeed = new ArchiveFeed;
		$archiveFeed->set_up_feed_page($page, "spatial");
		$archiveFeed->getItemList();
		$this->view->archive =  $archiveFeed;
	}//end function


	public function xmlAction() {
		// get the space uuid from the uri
		$this->_helper->viewRenderer->setNoRender();
		$uuid_query = "uuid:" . $this->_request->getParam('uuid');
		$output = "Snap! Something, may be, sorta didn't quite work 100% right..";	
		$host = OpenContext_OCConfig::get_host_config();
		$XMLgood = false;
		// Connection to solr server
		
		
		$itemUUID = $this->_request->getParam('uuid');
		$spaceItem = New Subject;
		$itemFound = $spaceItem->getByID($itemUUID);
		
		if($itemFound){
			$spaceItem->getTableAssociations(); //find tables for download associated with the item
			$XML = simplexml_load_string($spaceItem->archaeoML);
			$XML = OpenContext_ProjectReviewAnnotate::addProjectReviewStatus($spaceItem->projectUUID, $XML, $spaceItem->nameSpaces());
			$spaceItem->archaeoML = $XML->asXML();
			header("Content-type: application/xml");
			echo $spaceItem->archaeoML;
		}
		else{
			$this->view->requestURI = $this->_request->getRequestUri(); 
			return $this->render('404error');
		}
		
	}//end atom function



	public function dcAction() {
		// get the space uuid from the uri
		
		$uuid_query = "uuid:" . $this->_request->getParam('uuid');
		$host = OpenContext_OCConfig::get_host_config();
		$XMLgood = false;
		// Connection to solr server
		$solr = new Apache_Solr_Service('localhost', 8983, '/solr');
		
		// test the connection to the solr server
		if ($solr->ping()) {
			try {
				$response = $solr->search($uuid_query, 0, 1, array (/* you can include other parameters here */));
			
				foreach (($response->response->docs) as $doc) {
					$doc->atom_full = str_replace('www.opencontext.org/subjects', 'ishmael.ischool.berkeley.edu/subjects', $doc->atom_full);	
					$doc->atom_full = str_replace('http://opencontext/subjects', $host.'/subjects', $doc->atom_full);
	
					$atomXML = simplexml_load_string($doc->atom_full);
					$atomXML->registerXPathNamespace("oc", "http://www.opencontext.org/database/schema/space_schema_v1.xsd");
					$atomXML->registerXPathNamespace("default", "http://www.w3.org/2005/Atom");
					$atomXML->registerXPathNamespace("arch", "http://ochre.lib.uchicago.edu/schema/SpatialUnit/SpatialUnit.xsd");
					//$atomXML = OpenContext_RDFannotate::spaceEntitiesCheck($atomXML);
			
					foreach ($atomXML->xpath("//arch:spatialUnit") as $act_archaeoML) {
						$output = $act_archaeoML->saveXML();
						$XMLgood = true;	
					}
				}
			} catch (Exception $e) {
				echo $e->getMessage(), "\n";

			}

		} else {
			die("unable to connect to the solr server. exiting...");
		}

		
	}//end unapi function




	public function atomAction() {
		// get the space uuid from the uri
		$host = OpenContext_OCConfig::get_host_config();
		$itemUUID = $this->_request->getParam('uuid');
		
		if($itemUUID){
			$spaceItem = New Subject;
			$itemFound = $spaceItem->getByID($itemUUID);
			
			if($itemFound){
				$atomString = $spaceItem->atomEntry;
				unset($spaceItem);
				@$atomXML = simplexml_load_string($atomString);
				$this->view->atom = $atomXML;
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
			$archiveFeed->set_up_feed_page($page, "spatial");
			
			header('Content-type: application/atom+xml', true);
			echo $archiveFeed->generateFeed();
		}
		
	}//end atom function


	
	public function viewAction() {
		
		OpenContext_SocialTracking::update_referring_link('space', $this->_request->getRequestUri(), @$_SERVER['HTTP_USER_AGENT'], @$_SERVER['HTTP_REFERER']);
		
		$host = OpenContext_OCConfig::get_host_config();
		
		$itemUUID = $this->_request->getParam('uuid');
		$spaceItem = New Subject;
		$itemFound = $spaceItem->getByID($itemUUID);
		
		if($itemFound){
			$spaceItem->getTableAssociations(); //find tables for download associated with the item
			@$XML = simplexml_load_string($spaceItem->archaeoML);
			if($XML){
				$crawler = OpenContext_SocialTracking::crawlerDetect(@$_SERVER['HTTP_USER_AGENT']);
						    
				if(!$crawler){
					OpenContext_SocialTracking::update_person_viewtracking($XML, $spaceItem->nameSpaces());
					OpenContext_SocialTracking::update_project_viewtracking($XML, $spaceItem->nameSpaces());
					$XML = OpenContext_SocialTracking::update_space_viewtracking($XML, $spaceItem->nameSpaces());
				}
						    
				$XML = OpenContext_RDFannotate::spaceEntitiesCheck($XML, $spaceItem->nameSpaces());
				$XML = OpenContext_ProjectReviewAnnotate::addProjectReviewStatus($spaceItem->projectUUID, $XML, $spaceItem->nameSpaces());
				$this->view->XML = $XML;
				$this->view->label = $spaceItem->label;
				$this->view->itemUUID = $itemUUID;
			}
			else{
				$this->view->requestURI = $this->_request->getRequestUri(); 
				return $this->render('404error');
			}
		}
		else{
			$this->view->requestURI = $this->_request->getRequestUri(); 
			return $this->render('404error');
		}
		
	}
	



		//CIDOC RDF view
		public function cidocAction() {
		
				OpenContext_SocialTracking::update_referring_link('space', $this->_request->getRequestUri(), @$_SERVER['HTTP_USER_AGENT'], @$_SERVER['HTTP_REFERER']);
				
				$host = OpenContext_OCConfig::get_host_config();
				
				$itemUUID = $this->_request->getParam('uuid');
				$spaceItem = New Subject;
				$itemFound = $spaceItem->getByID($itemUUID);
				
				if($itemFound){
					@$XML = simplexml_load_string($spaceItem->archaeoML);
					if($XML){
						$crawler = OpenContext_SocialTracking::crawlerDetect(@$_SERVER['HTTP_USER_AGENT']);
									
						if(!$crawler){
							OpenContext_SocialTracking::update_person_viewtracking($XML, $spaceItem->nameSpaces());
							OpenContext_SocialTracking::update_project_viewtracking($XML, $spaceItem->nameSpaces());
							$XML = OpenContext_SocialTracking::update_space_viewtracking($XML, $spaceItem->nameSpaces());
						}
						
						$XML = OpenContext_ProjectReviewAnnotate::addProjectReviewStatus($spaceItem->projectUUID, $XML, $spaceItem->nameSpaces());
						$XML = OpenContext_RDFannotate::spaceEntitiesCheck($XML, $spaceItem->nameSpaces());
						$this->view->XML = $XML;
						$this->view->itemClass = $spaceItem->getClassName($spaceItem->archaeoML);
					}
					else{
						$this->view->requestURI = $this->_request->getRequestUri(); 
						return $this->render('404error');
					}
				}
				else{
					$this->view->requestURI = $this->_request->getRequestUri(); 
					return $this->render('404error');
				}
				
			}








}