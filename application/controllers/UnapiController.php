<?php

/** Zend_Controller_Action */
require_once 'Zend/Controller/Action.php';


class unapiController extends Zend_Controller_Action {

	public function indexAction() {
		
		if($this->_request->getParam('id')){
			$itemURI = $this->_request->getParam('id');
			$this->view->itemURI = $itemURI;
			if($this->_request->getParam('format')){	
				$metaFormat =  $this->_request->getParam('format');
			}
			else{
				$metaFormat = false;
			}
			$this->view->metaFormat = $metaFormat;
			$this->render('view');
		}
		
	}
	
	public function viewAction() {
		
		
		$host = OpenContext_OCConfig::get_host_config();
		
		// get the space uuid from the uri
		$itemURI =  $this->_request->getParam('id');
		$this->view->itemURI = $itemURI;
		
		if($this->_request->getParam('format')){	
			$metaFormat =  $this->_request->getParam('format');
		}
		else{
			$metaFormat = false;
		}
		
		$this->view->metaFormat = $metaFormat;
		
		
		
	}


}