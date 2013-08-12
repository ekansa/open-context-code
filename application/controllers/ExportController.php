<?php

/** Zend_Controller_Action */
require_once 'Zend/Controller/Action.php';
ini_set("max_execution_time", "0");

class exportController extends Zend_Controller_Action {

	public function indexAction() {

	}
	
	public function siteMapAction(){
		
		$this->_helper->viewRenderer->setNoRender();
		$id = $this->_request->getParam('id');
		$siteMapObj = new SiteMap;
		$xml = $siteMapObj->getSiteMap($idKey); 
		if(!$xml){
			$this->view->requestURI = $this->_request->getRequestUri(); 
			return $this->render('404error');
		}
		else{
			header("Content-type: application/xml");
			echo $xml;
		}
	}
	
	
	public function pelagiosAction() {
		
		OpenContext_SocialTracking::update_referring_link('export-pelagios', $this->_request->getRequestUri(), @$_SERVER['HTTP_USER_AGENT'], @$_SERVER['HTTP_REFERER']);
		$atomPelagios = new AtomToPelagios;
		
		if(isset($_GET["new"])){
			$atomPelagios->clearCache = true;
		}
		else{
			$atomPelagios->clearCache = false;
		}
		
		$atomPelagios->getData();
		$this->view->data = $atomPelagios->data;
		unset($atomPelagios);
	}
	
	public function pelagiosVoidAction() {
		
		OpenContext_SocialTracking::update_referring_link('export-pelagios-void', $this->_request->getRequestUri(), @$_SERVER['HTTP_USER_AGENT'], @$_SERVER['HTTP_REFERER']);
		
	}
	
	
	
	//iterate through and export all of the projects, subjects, media, and diary items
	public function xmlDumpAction() {
		mb_internal_encoding( 'UTF-8' );
		$this->_helper->viewRenderer->setNoRender();
		$output = array();
		$AllDumpObj = new AllDump;
		$outputDirs = $AllDumpObj->exportAll();
		$output["count"] = $AllDumpObj->exportCount;
		$output["directories"] = $outputDirs;
		header('Content-Type: application/json; charset=utf8');
		echo Zend_Json::encode($output);
	}
	
	
	
}