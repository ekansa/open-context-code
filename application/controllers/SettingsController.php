<?php

/** Zend_Controller_Action */
require_once 'Zend/Controller/Action.php';
ini_set("memory_limit", "6024M");
ini_set("max_execution_time", "0");

class settingsController extends Zend_Controller_Action {

	public function indexAction() {

	}
	
	function getEolHierarchyAction(){
		  
		$this->_helper->viewRenderer->setNoRender();
		 
		$eolObj = new Facets_EOL;
		$output = $eolObj->accessionEOLhierarchy();
	  
		header('Content-Type: application/json; charset=utf8');
		echo Zend_Json::encode($output);
	}
	
	
	function uriTreeAction(){
		
		$this->_helper->viewRenderer->setNoRender();
		$time_start = $this->microtime_float();
		$parentURI = "http://eol.org/pages/7687";
		$hierarchyObj = new Facets_Hierarchy ;
		$data = $hierarchyObj->getNestedChildURIs($parentURI);
		$time_end = $this->microtime_float();
		$time = $time_end - $time_start;
		$output = array("time" => $time, "data" => $data);
		header('Content-Type: application/json; charset=utf8');
		echo Zend_Json::encode($output);
	}
	
	function uriChildrenAction(){
		
		$this->_helper->viewRenderer->setNoRender();
		$time_start = $this->microtime_float();
		$parentURI = $_GET["eol"];
		$hierarchyObj = new Facets_Hierarchy ;
		$data = $hierarchyObj->getLabeledListChildURIs($parentURI);
		$time_end = $this->microtime_float();
		$time = $time_end - $time_start;
		$output = array("time" => $time, "data" => $data);
		header('Content-Type: application/json; charset=utf8');
		echo Zend_Json::encode($output);
	}
	
	function uriParentsAction(){
		
		$this->_helper->viewRenderer->setNoRender();
		$time_start = $this->microtime_float();
		$childURI = $_GET["eol"];
		$hierarchyObj = new Facets_Hierarchy ;
		$data = $hierarchyObj->getListParentURIs($childURI);
		$time_end = $this->microtime_float();
		$time = $time_end - $time_start;
		$output = array("time" => $time, "data" => $data);
		header('Content-Type: application/json; charset=utf8');
		echo Zend_Json::encode($output);
	}
	
	function addRelAction(){
		
		$this->_helper->viewRenderer->setNoRender();
		$time_start = $this->microtime_float();
		
		$parentURI = "http://eol.org/pages/2195";
		$childURI = "http://eol.org/pages/14022305";
		$vocabURI = "http://eol.org";
		$tree = "default";
		
		$hierarchyObj = new Facets_Hierarchy ;
		$data = $hierarchyObj->addHierarchyFromParams($parentURI, $childURI, $vocabURI, $tree);
		$time_end = $this->microtime_float();
		$time = $time_end - $time_start;
		$output = array("time" => $time, "data" => $data);
		header('Content-Type: application/json; charset=utf8');
		echo Zend_Json::encode($output);
	}
	
	function relEquivAction(){
		
		$this->_helper->viewRenderer->setNoRender();
		$time_start = $this->microtime_float();
		$items = $_GET["eol"];
		$hierarchyObj = new Facets_Hierarchy ;
		$data = $hierarchyObj->generateRelSearchEquivalent($items, "eol");
		
		$time_end = $this->microtime_float();
		$time = $time_end - $time_start;
		$output = array("time" => $time, "data" => $data);
		header('Content-Type: application/json; charset=utf8');
		echo Zend_Json::encode($output);
	}
	
	
	function vocabTreeAction(){
		
		$this->_helper->viewRenderer->setNoRender();
		$time_start = $this->microtime_float();
		$vocab = $_GET["vocab"];
		$hierarchyObj = new Facets_Hierarchy ;
		$data = $hierarchyObj->loadActiveHierarchySettings($vocab , true);
		
		$time_end = $this->microtime_float();
		$time = $time_end - $time_start;
		$output = array("time" => $time, "data" => $data);
		header('Content-Type: application/json; charset=utf8');
		echo Zend_Json::encode($output);
	}
	
	
	private function microtime_float(){
		list($usec, $sec) = explode(" ", microtime());
		return ((float)$usec + (float)$sec);
	}

	
}//end class






