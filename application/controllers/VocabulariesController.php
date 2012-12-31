<?php
/** Zend_Controller_Action */
require_once 'Zend/Controller/Action.php';


class VocabulariesController extends Zend_Controller_Action
{   
      
    public function indexAction()
    {
		  OpenContext_SocialTracking::update_referring_link('vocabs', $this->_request->getRequestUri(), @$_SERVER['HTTP_USER_AGENT'], @$_SERVER['HTTP_REFERER']);
	
    }
    
    public function viewAction() {
		
		  $host = OpenContext_OCConfig::get_host_config();
        $this->view->ok = true;
        OpenContext_SocialTracking::update_referring_link('vocabs', $this->_request->getRequestUri(), @$_SERVER['HTTP_USER_AGENT'], @$_SERVER['HTTP_REFERER']);        
        
		  $vocab =  $this->_request->getParam('vocab');
		  $concept =  $this->_request->getParam('concept');
	 
		  $OWL = new OWL;
		  $OWL->requestURI = $host.$this->_request->getRequestUri();
		  
		  if($OWL->getOntology($vocab, $concept)){
				if(isset($_GET["format"])){
					 $this->_helper->viewRenderer->setNoRender();
					 header('Content-Type: application/json; charset=utf8');
					 $output = Zend_Json::encode($OWL->owlArray);
					 echo $output;
				}
				elseif($concept){
					 if($OWL->conceptFound){
						  $this->view->requestURI = $host.$this->_request->getRequestUri();
						  $this->view->OWL = $OWL;
						  return $this->render('view-part'); // re-render the login form
					 }
					 else{
						  $this->view->requestURI = $host.$this->_request->getRequestUri();
						  return $this->render('404error'); // concept not found in ontology
					 }
				}
				else{
					 $this->view->OWL = $OWL;
				}
		  }
		  else{
				$this->view->requestURI = $host.$this->_request->getRequestUri();
				return $this->render('404error'); // ontology not found
		  }
    }
    
    
    
    
}

