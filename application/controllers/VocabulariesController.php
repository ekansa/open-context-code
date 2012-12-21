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
		  echo "<h1>Vocabulary: $vocab </h1>";
		  echo "<h2>Concept: $concept </h2>";
		  
		  $OWL = new OWL;
		  $OWL->getOntology($vocab, $concept);
		  echo "<h2>".$OWL->OWLfile."</h2>";
		  echo "<br/>";
		  echo "<p>".$OWL->hashConcept."</p>";
		  echo print_r($OWL->owlArray);
    }
    
    
    
    
}

