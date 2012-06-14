<?php

/** Zend_Controller_Action */
require_once 'Zend/Controller/Action.php';


class arkController extends Zend_Controller_Action {

	public function indexAction() {

	}
	
	public function viewAction() {
		
		$this->_helper->viewRenderer->setNoRender();
		$host = OpenContext_OCConfig::get_host_config();
		
		// get the space uuid from the uri
		$noidPrefix =  $this->_request->getParam('noidPrefix');
		$noidSuffix =  $this->_request->getParam('noidSuffix');
		$noid = $noidPrefix.'/'.$noidSuffix;
		$noidDoc = OpenContext_ArkNoid::noidItem($noid);
		//echo $noidDoc["docType"].":".$noidDoc["uuid"];
		
		$host = OpenContext_OCConfig::get_host_config();
		if($noidDoc["docType"] == "space"){
			$reDirectURI = $host."/subjects/".$noidDoc["uuid"];
		}
		elseif($noidDoc["docType"] == "media"){
			$reDirectURI = $host."/media/".$noidDoc["uuid"];
		}
		elseif($noidDoc["docType"] == "project"){
			$reDirectURI = $host."/projects/".$noidDoc["uuid"];
		}
		
		header('Location: '.$reDirectURI);
		exit;
		
	}
	


	public function mintidAction() {
		$this->_helper->viewRenderer->setNoRender();
		$itemUUID = $this->_request->getParam('item_uuid');
		$itemType = $this->_request->getParam('item_type');
		
		$noid = OpenContext_ArkNoid::mintNoid($itemType, $itemUUID);
		$success = OpenContext_ArkNoid::bindNoid($itemType, $itemUUID, $noid);
		if($success){
			header("Content-type: application/xml");
			echo OpenContext_ArkNoid::updateXML_Noid($itemType, $itemUUID, $noid);
		}
		else{
			echo $itemType.": ".$itemUUID.": Noid=".$noid." Success? ".$success;
		}
	}


}