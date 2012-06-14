<?php

/** Zend_Controller_Action */
//require_once 'Zend/Controller/Action.php';
//require_once 'OpenContext/Controller/Action/Helper/SolrAccess.php';

//error_reporting(E_ALL ^ E_NOTICE);
ini_set("memory_limit", "2048M");
ini_set("max_execution_time", "0");
ini_set('default_socket_timeout',    120);

class linkController extends Zend_Controller_Action {


	function getLinksAction(){
		
		$this->_helper->viewRenderer->setNoRender();
		
		$db_params = OpenContext_OCConfig::get_db_config();
		$db = new Zend_Db_Adapter_Pdo_Mysql($db_params);
		$db->getConnection();
		
		
		$sql = "SELECT hashID, itemUUID
		FROM linked_data
		WHERE source_id = 'admin'
		AND itemType = 'property'
		"; //get new link references created by site administrator
		
		$result = $db->fetchAll($sql, 2);
		
		$output = false;
		if($result){
			$output = array();
			foreach($result as $row){
			    $propertyUUID = $row["itemUUID"];
			    //echo "<br/><a href='./link-prop?id=".$propertyUUID."'>".$propertyUUID."</a>";
			    $hashID = $row["hashID"];
			    $where = array();
			    $where[] = "hashID = '".$hashID."' ";
			    $updateData = array();
			    $updateData["source_id"] = "admin_complete";
			    //$db->update("linked_data", $updateData, $where);
			    $output[] = $propertyUUID;
			}
		}
		
		$db->closeConnection();
		
		header('Content-Type: application/json; charset=utf8');
		echo Zend_Json::encode($output);
	
	}


	
	function prepPropAction(){
		$this->_helper->viewRenderer->setNoRender();
		$propLink = new PropertyLink;
		$propLink->initialize();
		$propLink->propertyUUID = $_GET["id"];
		$propLink->get_property_relations();
		$propLink->get_spatial_refs();
		
		$output = array("propertyUUID" => $propLink->propertyUUID,
				"variableUUID" => $propLink->variableUUID,
				"varRelations" => $propLink->propVarRelations,
				"valRelations" => $propLink->propValRelations,
				"numItems" => $propLink->numItems
				);
		
		header('Content-Type: application/json; charset=utf8');
		echo Zend_Json::encode($output);
		
	}


	function linkPropAction(){
		
		$this->_helper->viewRenderer->setNoRender();
		
		$propLink = new PropertyLink;
		$propLink->initialize();
		$propLink->propertyUUID = $_GET["id"];
		//$propLink->limitedProcess = true;
		$propLink->get_property_relations();
		//$propLink->name_de_dupe();
		$propLink->process_prop_todo();
	
		$output = array("propertyUUID" => $propLink->propertyUUID,
				"varLabel" => $propLink->varLabel,
				"valText" => $propLink->valText,
				"varRelations" => $propLink->propVarRelations,
				"valRelations" => $propLink->propValRelations,
				"numDone" => $propLink->doneCount,
				"errors" => $propLink->errors
				);
		
		header('Content-Type: application/json; charset=utf8');
		echo Zend_Json::encode($output);
	}



	function allLinkPropAction(){
		
		$this->_helper->viewRenderer->setNoRender();
		
		$propLink = new PropertyLink;
		$propLink->initialize();
		$propLink->propertyUUID = $_GET["id"];
		$propLink->get_property_relations();
		$propLink->get_spatial_refs();
		$propLink->process_prop_todo();
	
		header('Content-Type: application/json; charset=utf8');
		echo Zend_Json::encode($propLink);
	}



	function addRemainingAction(){
		
		$this->_helper->viewRenderer->setNoRender();
		
		if(isset($_GET["field"])){
			$solrVar = $_GET["field"];
		}
		else{
			$solrVar = "Taxon";
		}
		
		$startURL = "http://opencontext.org/sets/facets/.json?cat=Animal+Bone&taxa[]=".urlencode($solrVar);
		echo "<br/>".$startURL;
		$string = file_get_contents($startURL);
		$facetObj = Zend_Json::decode($string);
		unset($string);
		$activeFacets = $facetObj["facets"]["sub-classification"];
		unset($facetObj);
		$startURL = true;
		foreach($activeFacets as $actFacet){
			$valueName = $actFacet["name"];
			$firstQuery = $actFacet["result_href"];
			
			echo "<br/>".$valueName." (".$actFacet["count"].") ".$firstQuery ;
			
			/*
			if( ($valueName == "Sus scrofa" && $solrVar == "Species")){
				$startURL = true;
			}
			if( ($valueName == "Vulpes vulpes" && $solrVar == "Taxon")){
				$startURL = true;
			}
			if( ($valueName == "Ovis/Capra " && $solrVar == "Taxonomic Id")){
				$startURL = true;
			}
			*/
			
			$firstQuery .= "&recs=75";
			if($actFacet["count"]>0 && $startURL){
				$propLink = new PropertyLink;
				$propLink->solrVar = $solrVar;
				$propLink->solrValue = $valueName;
				$propLink->json_results($firstQuery);
				unset($propLink);
			}
		}
		
	}
	
	
	
	function addSolrPropAction(){
		
		$this->_helper->viewRenderer->setNoRender();
		
		/*
		 INSERT INTO `oc_working`.`linked_data` (`hashID`, `fk_project_uuid`, `source_id`, `itemUUID`, `itemType`, `linkedLabel`, `linkedURI`, `vocabulary`, `vocabURI`, `created`) VALUES ('b9f61f5cae684239d34c5338c20b1b02', '497ADEAD-0C2A-4C62-FEEF-9079FB09B1A5', 'admin', '204258D6-1D19-48B8-9055-40495B526D81', 'property', 'Hemiechinus', 'http://www.eol.org/pages/34867', 'Encyclopedia of Life', 'http://www.eol.org/', CURRENT_TIMESTAMP);
		*/
		
		$addArray = array();
		
		/*
		$addArray[] = array("fk_project_uuid" => "" // add project,
				    "source_id" => "admin",
				    "itemUUID" => "", //add property
				    "itemType" => "property",
				    "linkedLabel" => "", // add label
				    "linkedURI" => "", //add URI
				    "vocabulary" => "Encyclopedia of Life",
				    "vocabURI" => "http://www.eol.org/"
				    );
		*/
		
		/*
		$addArray[] = array("fk_project_uuid" => "497ADEAD-0C2A-4C62-FEEF-9079FB09B1A5",// add project,
				    "source_id" => "admin",
				    "itemUUID" => "204258D6-1D19-48B8-9055-40495B526D81", //add property
				    "itemType" => "property",
				    "linkedLabel" => "Hemiechinus", // add label
				    "linkedURI" => "http://www.eol.org/pages/34867", //add URI
				    "vocabulary" => "Encyclopedia of Life",
				    "vocabURI" => "http://www.eol.org/",
				    "qval" => "Hemiechinus sp."
				    );
		
		
		$addArray[] = array("fk_project_uuid" => "497ADEAD-0C2A-4C62-FEEF-9079FB09B1A5",// add project,
				    "source_id" => "admin",
				    "itemUUID" => "870C5467-7A1F-4FDF-17C1-56814EE96F9E", //add property
				    "itemType" => "property",
				    "linkedLabel" => "Gerbillus", // add label
				    "linkedURI" => "http://www.eol.org/pages/111264", //add URI
				    "vocabulary" => "Encyclopedia of Life",
				    "vocabURI" => "http://www.eol.org/",
				    "qval" => "Gerbillus sp."
				    );
		
		$addArray[] = array("fk_project_uuid" => "497ADEAD-0C2A-4C62-FEEF-9079FB09B1A5",// add project,
				    "source_id" => "admin",
				    "itemUUID" => "F5792DC3-656D-446C-4C77-BEFEA6A3FBCD", //add property
				    "itemType" => "property",
				    "linkedLabel" => "Sus", // add label
				    "linkedURI" => "http://www.eol.org/pages/42318", //add URI
				    "vocabulary" => "Encyclopedia of Life",
				    "vocabURI" => "http://www.eol.org/",
				    "qval" => "Sus sp."
				    );
		
		$addArray[] = array("fk_project_uuid" => "497ADEAD-0C2A-4C62-FEEF-9079FB09B1A5",// add project,
				    "source_id" => "admin",
				    "itemUUID" => "46E0DDF0-252D-4FA6-CE44-9A8EBEABB26C", //add property
				    "itemType" => "property",
				    "linkedLabel" => "Dama mesopotamica (Brooke, 1875)", // add label
				    "linkedURI" => "http://www.eol.org/pages/308402", //add URI
				    "vocabulary" => "Encyclopedia of Life",
				    "vocabURI" => "http://www.eol.org/",
				    "qval" => "Dama mesopotamica"
				    );
		
		$addArray[] = array("fk_project_uuid" => "497ADEAD-0C2A-4C62-FEEF-9079FB09B1A5",// add project,
				    "source_id" => "admin",
				    "itemUUID" => "B5175D50-D5F6-4EB3-A87C-BA00DBB11461", //add property
				    "itemType" => "property",
				    "linkedLabel" => "Mammalia", // add label
				    "linkedURI" => "http://www.eol.org/pages/1642", //add URI
				    "vocabulary" => "Encyclopedia of Life",
				    "vocabURI" => "http://www.eol.org/",
				    "qval" => "Large Mammal"
				    );
		
		$addArray[] = array("fk_project_uuid" => "497ADEAD-0C2A-4C62-FEEF-9079FB09B1A5",// add project,
				    "source_id" => "admin",
				    "itemUUID" => "4393BA98-916A-48A4-E43B-696ECC07882B", //add property
				    "itemType" => "property",
				    "linkedLabel" => "Canidae", // add label
				    "linkedURI" => "http://www.eol.org/pages/7676", //add URI
				    "vocabulary" => "Encyclopedia of Life",
				    "vocabURI" => "http://www.eol.org/",
				    "qval" => "Medium Canid"
				    );
		
		$addArray[] = array("fk_project_uuid" => "497ADEAD-0C2A-4C62-FEEF-9079FB09B1A5",// add project,
				    "source_id" => "admin",
				    "itemUUID" => "7A9BB268-BAFB-444D-560D-4CDCD2AF5B34", //add property
				    "itemType" => "property",
				    "linkedLabel" => "Camelus bactrianus Linnaeus, 1758", // add label
				    "linkedURI" => "http://www.eol.org/pages/344581", //add URI
				    "vocabulary" => "Encyclopedia of Life",
				    "vocabURI" => "http://www.eol.org/",
				    "qval" => "Camelus bactrianus"
				    );
		
		$addArray[] = array("fk_project_uuid" => "497ADEAD-0C2A-4C62-FEEF-9079FB09B1A5",// add project,
				    "source_id" => "admin",
				    "itemUUID" => "7791D8E7-B099-454B-2A82-16F451A90479", //add property
				    "itemType" => "property",
				    "linkedLabel" => "Caprinae", // add label
				    "linkedURI" => "http://www.eol.org/pages/2851411", //add URI
				    "vocabulary" => "Encyclopedia of Life",
				    "vocabURI" => "http://www.eol.org/",
				    "qval" => "Ovis/Capra"
				    );
		
		$addArray[] = array("fk_project_uuid" => "497ADEAD-0C2A-4C62-FEEF-9079FB09B1A5",// add project,
				    "source_id" => "admin",
				    "itemUUID" => "F9D3EEB2-9E3F-48A9-DFB0-D05D3554B4ED", //add property
				    "itemType" => "property",
				    "linkedLabel" => "Tatera indica (Hardwicke, 1807)", // add label
				    "linkedURI" => "http://www.eol.org/pages/1179780", //add URI
				    "vocabulary" => "Encyclopedia of Life",
				    "vocabURI" => "http://www.eol.org/",
				    "qval" => "Tatera indica"
				    );
		
		$addArray[] = array("fk_project_uuid" => "497ADEAD-0C2A-4C62-FEEF-9079FB09B1A5",// add project,
				    "source_id" => "admin",
				    "itemUUID" => "35CC0E4C-ACAA-42A3-60CF-45334A6E4A4C", //add property
				    "itemType" => "property",
				    "linkedLabel" => "Canidae", // add label
				    "linkedURI" => "http://www.eol.org/pages/7676", //add URI
				    "vocabulary" => "Encyclopedia of Life",
				    "vocabURI" => "http://www.eol.org/",
				    "qval" => "Small Canid"
				    );
				    
		$addArray[] = array("fk_project_uuid" => $projectID,// add project,
				    "source_id" => "admin",
				    "itemUUID" => "TESTPRO0000023733", //add property
				    "itemType" => "property",
				    "linkedLabel" => "Caprinae", // add label
				    "linkedURI" => "http://www.eol.org/pages/2851411", //add URI
				    "vocabulary" => "Encyclopedia of Life",
				    "vocabURI" => "http://www.eol.org/",
				    "qval" => "Ovis/Capra"
				    );
		
		
		$addArray[] = array("fk_project_uuid" => $projectID,// add project,
				    "source_id" => "admin",
				    "itemUUID" => "TESTPRO0000020939", //add property
				    "itemType" => "property",
				    "linkedLabel" => "Mammalia", // add label
				    "linkedURI" => "http://www.eol.org/pages/1642", //add URI
				    "vocabulary" => "Encyclopedia of Life",
				    "vocabURI" => "http://www.eol.org/",
				    "qval" => "Large Mammal"
				    );
		
		$addArray[] = array("fk_project_uuid" => $projectID,// add project,
				    "source_id" => "admin",
				    "itemUUID" => "TESTPRO0000024212", //add property
				    "itemType" => "property",
				    "linkedLabel" => "Vulpes vulpes (Linnaeus, 1758)", // add label
				    "linkedURI" => "http://www.eol.org/pages/328609", //add URI
				    "vocabulary" => "Encyclopedia of Life",
				    "vocabURI" => "http://www.eol.org/",
				    "qval" => "Vulpes vulpes"
				    );
		
		$addArray[] = array("fk_project_uuid" => $projectID,// add project,
				    "source_id" => "admin",
				    "itemUUID" => "TESTPRO0000022545", //add property
				    "itemType" => "property",
				    "linkedLabel" => "Ovis", // add label
				    "linkedURI" => "http://www.eol.org/pages/39510", //add URI
				    "vocabulary" => "Encyclopedia of Life",
				    "vocabURI" => "http://www.eol.org/",
				    "qval" => "Ovis sp."
				    );
		
		$addArray[] = array("fk_project_uuid" => $projectID,// add project,
				    "source_id" => "admin",
				    "itemUUID" => "34E38462-4D8A-460A-2EFB-6A7D21A15897", //add property
				    "itemType" => "property",
				    "linkedLabel" => "Felidae", // add label
				    "linkedURI" => "http://www.eol.org/pages/7674", //add URI
				    "vocabulary" => "Encyclopedia of Life",
				    "vocabURI" => "http://www.eol.org/",
				    "qval" => "Large felid"
				    );
		$addArray[] = array("fk_project_uuid" => $projectID,// add project,
				    "source_id" => "admin",
				    "itemUUID" => "C6A7B48C-F792-493E-C272-6539E528FD2D", //add property
				    "itemType" => "property",
				    "linkedLabel" => "Canis lupus familiaris Linnaeus, 1758", // add label
				    "linkedURI" => "http://www.eol.org/pages/1228387", //add URI
				    "vocabulary" => "Encyclopedia of Life",
				    "vocabURI" => "http://www.eol.org/",
				    "qval" => "Canis familiaris"
				    );
		
		$addArray[] = array("fk_project_uuid" => $projectID,// add project,
				    "source_id" => "admin",
				    "itemUUID" => "A3DAABDE-2181-4FC7-8F92-CBB7F8BF0EAB", //add property
				    "itemType" => "property",
				    "linkedLabel" => "Canidae", // add label
				    "linkedURI" => "http://www.eol.org/pages/7676", //add URI
				    "vocabulary" => "Encyclopedia of Life",
				    "vocabURI" => "http://www.eol.org/",
				    "qval" => "Medium canid"
				    );		    
				    
				    
		
		*/
		
		
		
		
		
		$projectID = "3";
		
		$addArray[] = array("fk_project_uuid" => $projectID,// add project,
				    "source_id" => "admin",
				    "itemUUID" => "11_47_DT_VarVal", //add property
				    "itemType" => "property",
				    "linkedLabel" => "Sus scrofa domesticus", // add label
				    "linkedURI" => "http://www.eol.org/pages/4445655", //add URI
				    "vocabulary" => "Encyclopedia of Life",
				    "vocabURI" => "http://www.eol.org/",
				    "qval" => "Sus scrofa"
				    );
		
		$addArray[] = array("fk_project_uuid" => $projectID,// add project,
				    "source_id" => "admin",
				    "itemUUID" => "11_53_DT_VarVal", //add property
				    "itemType" => "property",
				    "linkedLabel" => "Caprinae", // add label
				    "linkedURI" => "http://www.eol.org/pages/2851411", //add URI
				    "vocabulary" => "Encyclopedia of Life",
				    "vocabURI" => "http://www.eol.org/",
				    "qval" => "Ovis aries / Capra hircus"
				    );
		
		$db_params = OpenContext_OCConfig::get_db_config();
		$db = new Zend_Db_Adapter_Pdo_Mysql($db_params);
		$db->getConnection();
		
		foreach($addArray as $data){
			$propID = $data["itemUUID"];
			$qval = $data["qval"];
			unset($data["qval"]);
			$data["hashID"] = md5($data["itemUUID"]."_".$data["linkedURI"]);
			try{
				$db->insert("linked_data", $data);
			}catch(Exception $e) {
				  
			}
			
			$firstQuery = "http://opencontext.org/sets/.json?taxa%5B%5D=Taxon||Species||Taxonomic+Id%3A%3A".urlencode($qval);
			$firstQuery .= "&rel%5B%5D=%21%3Dhttp%3A%2F%2Fpurl.org%2FNET%2Fbiol%2Fns%23term_hasTaxonomy&proj=Domuztepe+Excavations";
			$firstQuery .= "&recs=75";
			
			$propLink = new PropertyLink;
			$propLink->solrVar = $propID;
			$propLink->solrValue = "to do";
			$propLink->json_results($firstQuery);
			
			
			$process = file_get_contents("http://opencontext.org/link/link-prop?id=".$propID);
			$output[] = Zend_Json::decode($process);
			sleep(.5);
		}
		
		header('Content-Type: application/json; charset=utf8');
		echo Zend_Json::encode($output);
		/*
		$propID = "E04FCA67-8BE4-4B81-DB15-D2504D67905A";
		
		$firstQuery = "http://opencontext.org/sets/.json?taxa%5B%5D=Taxon||Species||Taxonomic+Id%3A%3AEquus+spp.&rel%5B%5D=%21%3Dhttp%3A%2F%2Fpurl.org%2FNET%2Fbiol%2Fns%23term_hasTaxonomy&proj=Chogha+Mish+Fauna";
		$firstQuery .= "&recs=75";
		$propLink = new PropertyLink;
		$propLink->solrVar = $propID;
		$propLink->solrValue = "to do";
		$propLink->json_results($firstQuery);
		
		$output = file_get_contents("http://opencontext.org/link/link-prop?id=".$propID);
		
		header('Content-Type: application/json; charset=utf8');
		echo $output;
		
		*/
		
	}
	
	
	

	function missingLinkAction(){
		
		$this->_helper->viewRenderer->setNoRender();
		$nameArray = array();
		$nameArray[] = array('ocName'=>'Aves', 'uri'=>'http://www.eol.org/pages/695'); 
		$nameArray[] = array('ocName'=>'Bos primigenius', 'uri'=>'http://www.eol.org/pages/11021570'); 
		$nameArray[] = array('ocName'=>'Bos taurus', 'uri'=>'http://www.eol.org/pages/328699'); 
		$nameArray[] = array('ocName'=>'Bovid', 'uri'=>'http://www.eol.org/pages/7687'); 
		$nameArray[] = array('ocName'=>'Camelus', 'uri'=>'http://www.eol.org/pages/38902'); 
		$nameArray[] = array('ocName'=>'Camelus bactrianus', 'uri'=>'http://www.eol.org/pages/344581'); 
		$nameArray[] = array('ocName'=>'Camelus cf. dromedarius', 'uri'=>'http://www.eol.org/pages/309019'); 
		$nameArray[] = array('ocName'=>'Camelus dromedarius', 'uri'=>'http://www.eol.org/pages/309019'); 
		$nameArray[] = array('ocName'=>'Canis familiaris', 'uri'=>'http://www.eol.org/pages/1228387'); 
		$nameArray[] = array('ocName'=>'Canis sp.', 'uri'=>'http://www.eol.org/pages/14460'); 
		$nameArray[] = array('ocName'=>'Capra hircus', 'uri'=>'http://www.eol.org/pages/328660'); 
		$nameArray[] = array('ocName'=>'Capra sp.', 'uri'=>'http://www.eol.org/pages/42403'); 
		$nameArray[] = array('ocName'=>'Carnivore', 'uri'=>'http://www.eol.org/pages/7662'); 
		$nameArray[] = array('ocName'=>'Clarias', 'uri'=>'http://www.eol.org/pages/23806'); 
		$nameArray[] = array('ocName'=>'Crab', 'uri'=>'http://www.eol.org/pages/10948079'); 
		$nameArray[] = array('ocName'=>'Dama mesopotamica', 'uri'=>'http://www.eol.org/pages/308402'); 
		$nameArray[] = array('ocName'=>'Equid', 'uri'=>'http://www.eol.org/pages/11018612'); 
		$nameArray[] = array('ocName'=>'Equus caballus', 'uri'=>'http://www.eol.org/pages/328648'); 
		$nameArray[] = array('ocName'=>'Equus hemionus', 'uri'=>'http://www.eol.org/pages/311507'); 
		$nameArray[] = array('ocName'=>'Equus hemionus/asinus', 'uri'=>'http://www.eol.org/pages/11018612'); 
		$nameArray[] = array('ocName'=>'Equus sp.', 'uri'=>'http://www.eol.org/pages/11018612'); 
		$nameArray[] = array('ocName'=>'Equus spp.', 'uri'=>'http://www.eol.org/pages/15580'); 
		$nameArray[] = array('ocName'=>'Fish', 'uri'=>'http://www.eol.org/pages/2775704'); 
		$nameArray[] = array('ocName'=>'Gallus gallus', 'uri'=>'http://www.eol.org/pages/1049263'); 
		$nameArray[] = array('ocName'=>'Gazella sp.', 'uri'=>'http://www.eol.org/pages/15584'); 
		$nameArray[] = array('ocName'=>'Gerbillus sp.', 'uri'=>'http://www.eol.org/pages/111264'); 
		$nameArray[] = array('ocName'=>'goat', 'uri'=>'http://www.eol.org/pages/328660'); 
		$nameArray[] = array('ocName'=>'Hemiechinus sp.', 'uri'=>'http://www.eol.org/pages/34867'); 
		$nameArray[] = array('ocName'=>'Large Canid', 'uri'=>'http://www.eol.org/pages/7676'); 
		$nameArray[] = array('ocName'=>'Large mammal', 'uri'=>'http://www.eol.org/pages/1642'); 
		$nameArray[] = array('ocName'=>'Large Mammal', 'uri'=>'http://www.eol.org/pages/1642'); 
		$nameArray[] = array('ocName'=>'Lepus sp.', 'uri'=>'http://www.eol.org/pages/10840'); 
		$nameArray[] = array('ocName'=>'Lepus spp.', 'uri'=>'http://www.eol.org/pages/10840'); 
		$nameArray[] = array('ocName'=>'Medium Artiodactyl', 'uri'=>'http://www.eol.org/pages/7678'); 
		$nameArray[] = array('ocName'=>'Medium Canid', 'uri'=>'http://www.eol.org/pages/7676'); 
		$nameArray[] = array('ocName'=>'Medium Carnivore', 'uri'=>'http://www.eol.org/pages/7662'); 
		$nameArray[] = array('ocName'=>'Medium mammal', 'uri'=>'http://www.eol.org/pages/1642'); 
		$nameArray[] = array('ocName'=>'Medium Mammal', 'uri'=>'http://www.eol.org/pages/1642'); 
		$nameArray[] = array('ocName'=>'Medium-large mammal', 'uri'=>'http://www.eol.org/pages/1642'); 
		$nameArray[] = array('ocName'=>'medium-sized mammal', 'uri'=>'http://www.eol.org/pages/1642'); 
		$nameArray[] = array('ocName'=>'medium-sized ungulate', 'uri'=>'http://www.eol.org/pages/7678'); 
		$nameArray[] = array('ocName'=>'Odocoileus hemionus', 'uri'=>'http://www.eol.org/pages/328651'); 
		$nameArray[] = array('ocName'=>'Ovis aries', 'uri'=>'http://www.eol.org/pages/311906'); 
		$nameArray[] = array('ocName'=>'Ovis aries / Capra hircus', 'uri'=>'http://www.eol.org/pages/2851411'); 
		$nameArray[] = array('ocName'=>'Ovis aries/Capra hircus', 'uri'=>'http://www.eol.org/pages/2851411'); 
		$nameArray[] = array('ocName'=>'Ovis orientalis', 'uri'=>'http://www.eol.org/pages/13845095'); 
		$nameArray[] = array('ocName'=>'Ovis sp.', 'uri'=>'http://www.eol.org/pages/39510'); 
		$nameArray[] = array('ocName'=>'Ovis/Capra', 'uri'=>'http://www.eol.org/pages/2851411'); 
		$nameArray[] = array('ocName'=>'Ovis/Capra/Gazella', 'uri'=>'http://www.eol.org/pages/7678'); 
		$nameArray[] = array('ocName'=>'pig', 'uri'=>'http://www.eol.org/pages/4445655'); 
		$nameArray[] = array('ocName'=>'Rodent', 'uri'=>'http://www.eol.org/pages/8677'); 
		$nameArray[] = array('ocName'=>'Rodentia', 'uri'=>'http://www.eol.org/pages/8677'); 
		$nameArray[] = array('ocName'=>'sapiens', 'uri'=>'http://www.eol.org/pages/327955'); 
		$nameArray[] = array('ocName'=>'scrofa', 'uri'=>'http://www.eol.org/pages/4445655'); 
		$nameArray[] = array('ocName'=>'sheep', 'uri'=>'http://www.eol.org/pages/311906'); 
		$nameArray[] = array('ocName'=>'sheep/goat', 'uri'=>'http://www.eol.org/pages/2851411'); 
		$nameArray[] = array('ocName'=>'Small Artiodactyl', 'uri'=>'http://www.eol.org/pages/7678'); 
		$nameArray[] = array('ocName'=>'Small Canid', 'uri'=>'http://www.eol.org/pages/7676'); 
		$nameArray[] = array('ocName'=>'Small Carnivore', 'uri'=>'http://www.eol.org/pages/7662'); 
		$nameArray[] = array('ocName'=>'Small Felid', 'uri'=>'http://www.eol.org/pages/7674'); 
		$nameArray[] = array('ocName'=>'Small Mammal', 'uri'=>'http://www.eol.org/pages/1642'); 
		$nameArray[] = array('ocName'=>'Small Phasianidae', 'uri'=>'http://www.eol.org/pages/7591'); 
		$nameArray[] = array('ocName'=>'Small to medium-sized mammal (rabbit)', 'uri'=>'http://www.eol.org/pages/1642'); 
		$nameArray[] = array('ocName'=>'Sus scrofa', 'uri'=>'http://www.eol.org/pages/4445655'); 
		$nameArray[] = array('ocName'=>'Sus sp.', 'uri'=>'http://www.eol.org/pages/42318'); 
		$nameArray[] = array('ocName'=>'Tatera indica', 'uri'=>'http://www.eol.org/pages/1179780'); 
		$nameArray[] = array('ocName'=>'Vulpes vulpes', 'uri'=>'http://www.eol.org/pages/328609'); 


		$output = array();
		foreach($nameArray as $nameItem){
			$name = $nameItem['ocName'];
			
			$propLink = new PropertyLink;
			$propLink->initialize();
			$propLink->solrValue = $name;
			//$propLink->name_de_dupe();
			$propLink->count_names_to_process();
			//echo "<br/>".$name.": ".$propLink->numItems;
			$numItems = $propLink->numItems;
			//$output[] = array("name"=> $name , "count"=> $numItems);
			//$output[] = $propLink;
			unset($propLink);
			$jsonURL = "http://opencontext.org/link/do-missing-link?name=".urlencode($name);
			if(1==2){
			//if($numItems !=0 && $numItems <= 10){
				$jsonString = file_get_contents($jsonURL);
				$jsonOut = Zend_Json::decode($jsonString);
			}
			else{
				$jsonOut = false;
			}
			$output[] = array("name"=> $name , "count"=> $numItems, "jsonURL" => $jsonURL, "output" => $jsonOut);
		}


		header('Content-Type: application/json; charset=utf8');
		echo Zend_Json::encode($output);
	
		
	}//end function

    
    
	function doMissingLinkAction(){
		$this->_helper->viewRenderer->setNoRender();
		
		$name = $_GET["name"];
		
		$propLink = new PropertyLink;
		$propLink->initialize();
		$propLink->solrValue = $name;
		$propLink->limitedProcess = true;
		$propLink->name_getLinkedData();
		$propLink->name_process_prop_todo();
	
		$output = array("propertyUUID" => $propLink->propertyUUID,
				"varLabel" => $propLink->varLabel,
				"valText" => $propLink->valText,
				"varRelations" => $propLink->propVarRelations,
				"valRelations" => $propLink->propValRelations,
				"numItems" => $propLink->numItems,
				"numDone" => $propLink->doneCount,
				"errors" => $propLink->errors,
				"xml" => $propLink->lastXML
				);
		
		header('Content-Type: application/json; charset=utf8');
		echo Zend_Json::encode($output);
		
	}
    
    function notDoneLinksAction(){
	$this->_helper->viewRenderer->setNoRender();
	$db_params = OpenContext_OCConfig::get_db_config();
        $db = new Zend_Db_Adapter_Pdo_Mysql($db_params);
        $db->getConnection();
	
	$sql = "SELECT DISTINCT propertyUUID FROM prop_docs_log WHERE status = 'to do'; ";
	$result = $db->fetchAll($sql, 2);
	
	$output = array();
	foreach($result as $row){
	    $propertyUUID = $row["propertyUUID"];
	    $output[] = $propertyUUID;
	}
	
	header('Content-Type: application/json; charset=utf8');
	echo Zend_Json::encode($output);
    }
    
    
	function harvardAction(){
		$this->_helper->viewRenderer->setNoRender();
		$db_params = OpenContext_OCConfig::get_db_config();
		$db = new Zend_Db_Adapter_Pdo_Mysql($db_params);
		$db->getConnection();
		
		$propLink = new PropertyLink;
		$propLink->initialize();
		$propLink->genus_species_process_prop_todo();
		
		$output = array(
				"numDone" => $propLink->doneCount,
				"errors" => $propLink->errors,
				"xml" => $propLink->lastXML
				);
		
		$jsonXML = str_replace("<oc:", "<oc-", $propLink->lastXML);
		$jsonXML = str_replace("</oc:", "</oc-", $jsonXML);
		$jsonXML = str_replace("<dc:", "<dc-", $jsonXML);
		$jsonXML = str_replace("</dc:", "</dc-", $jsonXML);
		
		$output = Zend_Json::fromXml($jsonXML , false); 
		header('Content-Type: application/json; charset=utf8');
		//echo Zend_Json::encode($output);
		echo $output;
	}




}