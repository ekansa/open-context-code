<?php

/** Zend_Controller_Action */

//This controller redirects requests based on the old Open Context URL structure
//It makes sure links / citations to the old site work with the new site
class databaseController extends Zend_Controller_Action {

	public function spacePhpAction() {
		$this->_helper->viewRenderer->setNoRender();
		$itemUUID    = $_REQUEST['item'];
		$host = OpenContext_OCConfig::get_host_config();
		$reDirectURI = $host."/subjects/".$itemUUID;
		header('Location: '.$reDirectURI);
		exit;
	}

	public function resourcePhpAction() {
		$this->_helper->viewRenderer->setNoRender();
		$itemUUID    = $_REQUEST['item'];
		$host = OpenContext_OCConfig::get_host_config();
		$reDirectURI = $host."/media/".$itemUUID;
		header('Location: '.$reDirectURI);
		exit;
	}
	
	public function projectPhpAction() {
		$this->_helper->viewRenderer->setNoRender();
		$itemUUID    = $_REQUEST['item'];
		$host = OpenContext_OCConfig::get_host_config();
		$reDirectURI = $host."/projects/".$itemUUID;
		header('Location: '.$reDirectURI);
		exit;
	}
	
	public function browsePhpAction() {
		$this->_helper->viewRenderer->setNoRender();
		$host = OpenContext_OCConfig::get_host_config();
		$reDirectURI = $host."/sets/";
		header('Location: '.$reDirectURI);
		exit;
	}

	public function diaryPhpAction() {
		$this->_helper->viewRenderer->setNoRender();
		$itemUUID    = $_REQUEST['item'];
		$host = OpenContext_OCConfig::get_host_config();
		$reDirectURI = $host."/documents/".$itemUUID;
		header('Location: '.$reDirectURI);
		exit;
	}
	
	public function ssharepropPhpAction() {
		$this->_helper->viewRenderer->setNoRender();
		$itemUUID    = $_REQUEST['prop'];
		$host = OpenContext_OCConfig::get_host_config();
		$reDirectURI = $host."/properties/".$itemUUID;
		header('Location: '.$reDirectURI);
		exit;
	}
}