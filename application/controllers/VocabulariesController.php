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
        //$this->view->result = $result;
                
    }
    
    
    
    
}

