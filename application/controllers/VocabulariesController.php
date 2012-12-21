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
		
		
        $this->view->ok = true;
        OpenContext_SocialTracking::update_referring_link('vocabs', $this->_request->getRequestUri(), @$_SERVER['HTTP_USER_AGENT'], @$_SERVER['HTTP_REFERER']);        
        
		  $vocab =  $this->_request->getParam('vocab');
		  $concept =  $this->_request->getParam('concept');
		  
		  $this->_helper->viewRenderer->setNoRender();
	 
		  $OWL = new OWL;
		  $OWL->getOntology($vocab, $concept);
		  header('Content-Type: application/json; charset=utf8');
		  $output = Zend_Json::encode($OWL->owlArray);
		  echo $output;
    }
    
    
    
    
}

