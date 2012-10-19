<?php

class OpenContext_ProjectReviewAnnotate {
	
	public static function getStatus($projectEditStatus){
	
		//default status
		$output = array("uri" => "http://purl.org/ontology/bibo/status/unpublished",
														 "statusDes" => "Not published or placeholder content",
														 "label" => "Placeholder");
		
		$statusArray = array( 	1 => array("uri" => "http://purl.org/ontology/bibo/status/nonPeerReviewed",
														 "statusDes" => "Demonstration, minimal editorial acceptance",
														 "label" => "Demonstration, Minimal editorial acceptance"),
										2 => array("uri" => "http://purl.org/ontology/bibo/status/nonPeerReviewed",
														 "statusDes" => "Minimal editorial acceptance",
														 "label" => "Minimal editorial acceptance"),
										3 => array("uri" => "http://purl.org/ontology/bibo/status/peerReviewed",
														 "statusDes" => "Managing editor edited and reviewed",
														 "label" => "Managing editor reviewed"),
										4 => array("uri" => "http://purl.org/ontology/bibo/status/peerReviewed",
														 "statusDes" => "Editorial board reviewed",
														 "label" => "Editorial board reviewed"),
										5 => array("uri" => "http://purl.org/ontology/bibo/status/peerReviewed",
														 "statusDes" => "Editorial board and outside peer reviewed",
														 "label" => "Peer reviewed")
										);
	
		if(array_key_exists($projectEditStatus, $statusArray)){
			$output = $statusArray[$projectEditStatus];
		}
	
		return $output;
	}//end function
	
	//$itemXML is a simple xml object
	public static function addProjectReviewStatus($projectUUID, $itemXML, $nameSpaceArray){
		
		$projObj = new Project;
		$projectEditStatus = $projObj->getEditStatusByID($projectUUID);
		
		$itemXML = OpenContext_ProjectReviewAnnotate::XMLmodify($projectEditStatus, $itemXML, $nameSpaceArray);
		
		return $itemXML;
	}
	
	public static function XMLmodify($projectEditStatus, $itemXML, $nameSpaceArray){
		
		foreach($nameSpaceArray as $prefix => $uri){
			@$itemXML->registerXPathNamespace($prefix, $uri);
		}
		
		foreach($itemXML->xpath("//oc:metadata/oc:project_name") as $projMetadata){
			$status = OpenContext_ProjectReviewAnnotate::getStatus($projectEditStatus);
			$projMetadata->addAttribute("editStatus", $projectEditStatus);
			$projMetadata->addAttribute("statusURI", $status["uri"]);
			$projMetadata->addAttribute("statusDes", $status["statusDes"]);
			$projMetadata->addAttribute("statusLabel", $status["label"]);
		}
		
		return $itemXML;
	}
	
	
}//end class declaration

?>
