<?php

/** Zend_Controller_Action */
//require_once 'Zend/Controller/Action.php';
//require_once 'OpenContext/Controller/Action/Helper/SolrAccess.php';

//error_reporting(E_ALL ^ E_NOTICE);
ini_set("max_execution_time", "0");
ini_set('default_socket_timeout',    120);
ini_set("memory_limit", "1048M");

class homeController extends Zend_Controller_Action {



public function filegetAction(){
    $this->_helper->viewRenderer->setNoRender();
    $Final_cache_id = "37e4394bafa7f66f77313a64c1eca7b6";
    $filename = './tablefiles/'.$Final_cache_id.'.json';
    $handle = fopen($filename , "r");
    $contents = fread($handle, filesize($filename));
    fclose($handle);
    //$contents = mb_convert_encoding($contents, 'UTF-8');
    header('Content-Type: application/json; charset=utf8');
    echo $contents;
    
  }
  


	private function accentFix($xmlString){
	
	$stringArray = array(
			0 => array("bad" => "Christian Aug&amp;#233;", "good" => "Christian Augé"),
			1 => array("bad" => "G&#252;rdil", "good" => "Gürdil"),
			2 => array("bad" => "G&#xFC;rdil", "good" => "Gürdil"),
			3 => array("bad" => "G&amp;#252;rdil", "good" => "Gürdil")
			);
	
	//echo $xmlString;
	foreach($stringArray as $checks){
		$badString = $checks["bad"];
		$goodString = $checks["good"];
		//echo $badString ." ".$goodString;
		if(stristr($xmlString, $badString)){
		    
		    $xmlString = str_replace($badString, $goodString, $xmlString);
		}
	}
	
	
	return $xmlString;
	}//end function


 private function updateToDoList($toDoList, $db = false){
	
	if(!$db){
		$db_params = OpenContext_OCConfig::get_db_config();
		$db = new Zend_Db_Adapter_Pdo_Mysql($db_params);
		$db->getConnection();
	}
	
	if(is_array($toDoList)){ //no error, to do list is an array
	    
	    foreach($toDoList as $itemUUID){
		
		$where = array();
		$where[] = "itemUUID = '$itemUUID' ";
		$data = array('solr_indexed' => 1);
		$db->update('noid_bindings', $data, $where);
	    }
	
	}
    }//end function



private function prepURL($url){
	$url = str_replace(".org/", "/", $url);
	return $url.".xml";
}

//save this as an error and move on.
private function recordError($url){
	$itemURIarray = explode("/", $url);
	$countSlashes = count($itemURIarray);
	$itemUUID = $itemURIarray[$countSlashes -1];	
	$db_params = OpenContext_OCConfig::get_db_config();
	$db = new Zend_Db_Adapter_Pdo_Mysql($db_params);
	$db->getConnection();
	$where = array();
	$where[] = "itemUUID = '$itemUUID' ";
	$data = array('solr_indexed' => 100);
	$db->update('noid_bindings', $data, $where);
}



public function restoreDataAction(){
	$this->_helper->viewRenderer->setNoRender();
	$db_params = OpenContext_OCConfig::get_db_config();
	//$db2Params = $db_params;
	//$db2Params['dbname'] = "opencontext";
	$db = new Zend_Db_Adapter_Pdo_Mysql($db_params);
	$db->getConnection();
	//$db2 = new Zend_Db_Adapter_Pdo_Mysql($db2Params);
	//$db2->getConnection();
	$sql = "SELECT id, location, note FROM dataset_errors ";
	
	$result = $db->fetchAll($sql, 2);
	foreach($result as $row){
		if($row["note"] == "no xml"){
			$id = $row['id'];
			$itemUUID = str_replace("http://opencontext.org/subjects/", "", $row["location"]);
			$itemUUID = str_replace(".xml", "",$itemUUID);
			$url = "http://penelope2.oc/test/space?xml=true&id=".$itemUUID;
			echo "<br/><a href='".$url."'>".$itemUUID."</a>";
			$xmlString = file_get_contents($url);
			@$xml = simplexml_load_string($xmlString);
			if($xml){
				$data = array("archaeoML" => $xmlString);
				$where = array();
				$where[] = "uuid = '".$itemUUID."' ";
				$where[] = "archaeoML = '' ";
				$n = $db->update("space", $data, $where);
				if($n){
					$data = array("note" => "new XML");
					$where = array();
					$where[] = "id = '$id' ";
					$db->update("dataset_errors", $data, $where);
					echo " new XML created!";
				}
			}
			else{
				echo " <strong>No XML!!!!</strong>";
			}
			
		}//end case with bad xml
	}//end loop
	
	
}//end case


public function solrReIndexAction(){
	$this->_helper->viewRenderer->setNoRender();
	$db_params = OpenContext_OCConfig::get_db_config();
	$db = new Zend_Db_Adapter_Pdo_Mysql($db_params);
	$db->getConnection();
	
	
	$limit = "";
	
	if(isset($_GET["start"])){
		$limit = $_GET["start"];
		$limit = " WHERE id >= ".$limit;
	}
	
	$sql = "SELECT itemURI as uri, itemType, ItemCreated as created, ItemUpdated FROM noid_bindings WHERE solr_indexed = 0 ";
	
	$result = $db->fetchAll($sql, 2);
	
	$i=1;
	
	$solrMax = 50;
	$solrDocArray = array();
	
	$itemArray = array();
	foreach($result as $row){
		
		$id = false;
		$itemURI = $row["uri"];
		$itemType = $row["itemType"];
		$pubDate = $row["created"];
		$itemUpdated = $row["ItemUpdated"];
		
		$itemURIarray = explode("/", $itemURI);
		$countSlashes = count($itemURIarray);
		$itemUUID = $itemURIarray[$countSlashes -1];
		$itemArray[] = $itemUUID;
		
		if(stristr($itemURI, "/subjects/") || $itemType == "spatial"){
			$itemType = "spatial";
			$solrDocument = $this->spaceSolrDoc($itemUpdated, $itemURI);
		}
		elseif(stristr($itemURI, "/projects/") || $itemType == "project"){
			$solrDocument = $this->projectSolrDoc($itemUpdated, $itemURI);
		}
		elseif(stristr($itemURI, "/documents/") || $itemType == "document"){
			sleep(1);
			$solrDocument = $this->documentSolrDoc($itemUpdated, $itemURI);
		}
		elseif(stristr($itemURI, "/media/") || $itemType == "media"){
			sleep(1);
			$solrDocument = $this->mediaSolrDoc($itemUpdated, $itemURI);
		}
		elseif(stristr($itemURI, "/persons/") || $itemType == "person"){
			$solrDocument = $this->personSolrDoc($itemUpdated, $itemURI);
		}
		
		
		if($solrDocument != false){
			$solrDocArray[] = $solrDocument;
		}
		
		if(count($solrDocArray)>= $solrMax){
			$error = $this->executeIndexing($solrDocArray);
			if(!$error){
				unset($solrDocArray);
				$solrDocArray = array();
				$this->updateToDoList($itemArray);
				$itemArray = array();
			}
			else{
				echo "Solr Error:".$error;
				echo "\n\n\n\n";
				echo print_r($solrDocArray);
				break;
			}
		}
		
		//echo print_r($solrDocument);
		//break;
		if($i>=20){
			//break;
		}
	
	$i++;	
	}
	
	if(count($solrDocArray)>0){
		$error = $this->executeIndexing($solrDocArray);
		if(!$error){
			unset($solrDocArray);
			$this->updateToDoList($itemArray);
			$solrDocArray = array();
		}
		else{
			echo "Solr Error:".$error;
			echo "\n\n\n\n";
			echo print_r($solrDocArray);
			break;
		}
	}
	
	echo "<br/><br/><br/>".chr(13).chr(13).chr(13)."Done: ".$i;
	
    }//end funciton


	public function solrDeleteAction(){
	
		$this->_helper->viewRenderer->setNoRender();
		echo "Deleting old docs: ".$this->deleteSolr();
	
	}

	private function deleteSolr(){
	$solr = new Apache_Solr_Service('localhost', 8983, '/solr');
	// test the connection to the solr server
	if ($solr->ping()) { // if we can ping the solr server...
	    //echo "connected to the solr server...<br/><br/>";
	    try{
		
		/*
		$solr->deleteByQuery("[* TO *]");
		$solr->commit();
		$solr->optimize();
		*/
		return true;
	    }
	    catch (Exception $e) {
		
		return $e->getMessage();
		
	    }
				
	//end case where solr document
	}
	else{
	    return  "<br/><strong>solr down</strong>";
	}// end case with a bad ping
	
    }


	private function executeIndexing($solrDocArray, $doPingB = true){
		$error = false;
		if(is_array($solrDocArray)){
			$solr = new Apache_Solr_Service('localhost', 8983, '/solr');
	    
			if ($solr->ping()) { // if we can ping the solr server...
			    try{
				$updateResponse = $solr->addDocuments($solrDocArray);
				$solr->commit();
			    }
			    catch (Exception $e) {
				$error = $e->getMessage();
				echo print_r($e);
			    }
			}
			else{
			    //$error = "Solr Down: failed to respond to ping.";
			    sleep(1);
			    if($doPingB){
				$this->executeIndexing($solrDocArray, false);
			    }
			    else{
				$error = "Solr fails to respond to ping, twice.";
			    }
			    
			}
		}//yes, we do have docs to add
		return $error;
	}//end function
	





	private function spaceSolrDoc($itemUpdated, $url){
		
		$url = $this->prepURL($url);
		@$xmlString = file_get_contents($url);
		$ocSite = false;
		if(!$xmlString){
			sleep(.5);
			$url = str_replace("opencontext", "opencontext.org", $url);
			@$xmlString = file_get_contents($url);
			if(!$xmlString){
				sleep(1);
				@$xmlString = file_get_contents($url);
				if(!$xmlString){
					$this->recordError($url);
					return false;
				}
			}
			
			$ocSite = true;
		}
		
		//echo "<br/>".$url;
		
		$xmlString = $this->accentFix($xmlString);
		@$itemXML = simplexml_load_string($xmlString);
		if(!$itemXML && !$ocSite){
			sleep(.5);
			$url = str_replace("opencontext", "opencontext.org", $url);
			@$xmlString = file_get_contents($url);
			if(!$xmlString){
				sleep(1);
				$xmlString = file_get_contents($url);
				if(!$xmlString){
					$this->recordError($url);
					return false;
				}
			}
			@$itemXML = simplexml_load_string($xmlString);
			if(!$itemXML){
				echo "ERROR ON URL: ".$url;
				echo "<br/>";
				$db_params = OpenContext_OCConfig::get_db_config();
				$db = new Zend_Db_Adapter_Pdo_Mysql($db_params);
				$db->getConnection();
				$data = array("location" => $url,
					      "note" => "no xml");
				$db->insert("dataset_errors", $data);
			}
		}
		
		if($itemXML){
			$itemXML->registerXPathNamespace("arch", OpenContext_OCConfig::get_namespace("arch", "spatial"));
			$itemXML->registerXPathNamespace("oc", OpenContext_OCConfig::get_namespace("oc", "spatial"));
			$itemXML->registerXPathNamespace("dc", OpenContext_OCConfig::get_namespace("dc"));
			$itemXML->registerXPathNamespace("gml", OpenContext_OCConfig::get_namespace("gml"));
			$OpenContextItem = new OpenContextItem;
			$OpenContextItem->initialize();
			$OpenContextItem->update = date("Y-m-d\TH:i:s\Z", strtotime($itemUpdated));
			$OpenContextItem = OpenContext_XMLtoOpenContextItem::XMLspatialItemBasics($OpenContextItem, $itemXML);
			$OpenContextItem = OpenContext_XMLtoOpenContextItem::XMLtoContextData($OpenContextItem, $itemXML);
			$OpenContextItem = OpenContext_XMLtoOpenContextItem::XMLtoMediaLinkData($OpenContextItem, $itemXML);
			$OpenContextItem = OpenContext_XMLtoOpenContextItem::XMLtoPersonLinksData($OpenContextItem, $itemXML);
			$OpenContextItem = OpenContext_XMLtoOpenContextItem::XMLtoSocialData($OpenContextItem, $itemXML);
			$OpenContextItem = OpenContext_XMLtoOpenContextItem::XMLtoGeoData($OpenContextItem, $itemXML);
			$OpenContextItem = OpenContext_XMLtoOpenContextItem::XMLtoChronoData($OpenContextItem, $itemXML);
			$OpenContextItem = OpenContext_XMLtoOpenContextItem::XMLtoMetadata($OpenContextItem, $itemXML);
			$OpenContextItem = OpenContext_XMLtoOpenContextItem::XMLtoProperties($OpenContextItem, $itemXML);
			$OpenContextItem->interestCalc();
					
			$solrDocument = new Apache_Solr_Document();
			$solrDocument = $OpenContextItem->makeSolrDocument($solrDocument);
			
			return $solrDocument;
		}
		
	}//end function


	
	public function mediaTestAction(){
		$this->_helper->viewRenderer->setNoRender();
		$itemUpdated = date("Y-m-d\TH:i:s\Z");
		$url = "http://opencontext/media/1697_DT_Res";
		$solrDoc = $this->mediaSolrDoc($itemUpdated, $url);
		echo print_r($solrDoc);
	}
	
	public function mediaLookAction(){
		$this->_helper->viewRenderer->setNoRender();
		$itemUpdated = date("Y-m-d\TH:i:s\Z");
		$url = "http://opencontext.org/subjects/GHF1SPA0000077841.xml";
		$string = file_get_contents($url);
		$string = strip_tags($string);
		$string = html_entity_decode($string);
		$string = strip_tags($string);
		echo $string;
	}

	public function aboutLookAction(){
		$this->_helper->viewRenderer->setNoRender();
		$sitePages = new SitePageIndex;
		$sitePages->indexAll();
		
		header('Content-Type: application/json; charset=utf8');
		echo Zend_Json::encode($sitePages->ocItems);
	}

	
	public function mediaSolrDoc($itemUpdated, $url){
	
		$url = $this->prepURL($url);
		$xmlString = file_get_contents($url);
		$xmlString = $this->accentFix($xmlString);
		$itemXML = simplexml_load_string($xmlString);
		
		$OpenContextItem = new OpenContextItem;
		$OpenContextItem->initialize();
		$OpenContextItem->update = date("Y-m-d\TH:i:s\Z", strtotime($itemUpdated));
	
		$itemXML->registerXPathNamespace("oc", OpenContext_OCConfig::get_namespace("oc", "media"));
		$itemXML->registerXPathNamespace("arch", OpenContext_OCConfig::get_namespace("arch", "media"));
		$itemXML->registerXPathNamespace("dc", OpenContext_OCConfig::get_namespace("dc"));
		$itemXML->registerXPathNamespace("gml", OpenContext_OCConfig::get_namespace("gml"));
		
		$OpenContextItem = OpenContext_XMLtoOpenContextItem::XMLmediaItemBasics($OpenContextItem, $itemXML);
		$OpenContextItem = OpenContext_XMLtoOpenContextItem::XMLtoSpatialClass($OpenContextItem, $itemXML);
		if(strtolower($OpenContextItem->documentType) == "image"){
		    $OpenContextItem = OpenContext_XMLtoOpenContextItem::XMLtoImageSizeData($OpenContextItem, $itemXML);
		}
		$OpenContextItem = OpenContext_XMLtoOpenContextItem::XMLtoContextData($OpenContextItem, $itemXML);
		$OpenContextItem = OpenContext_XMLtoOpenContextItem::XMLtoMediaLinkData($OpenContextItem, $itemXML);
		$OpenContextItem = OpenContext_XMLtoOpenContextItem::XMLtoPersonLinksData($OpenContextItem, $itemXML);
		$OpenContextItem = OpenContext_XMLtoOpenContextItem::XMLtoSocialData($OpenContextItem, $itemXML);
		$OpenContextItem = OpenContext_XMLtoOpenContextItem::XMLtoGeoData($OpenContextItem, $itemXML);
		$OpenContextItem = OpenContext_XMLtoOpenContextItem::XMLtoChronoData($OpenContextItem, $itemXML);
		$OpenContextItem = OpenContext_XMLtoOpenContextItem::XMLtoMetadata($OpenContextItem, $itemXML);
		$OpenContextItem = OpenContext_XMLtoOpenContextItem::XMLtoProperties($OpenContextItem, $itemXML);
		$OpenContextItem = OpenContext_XMLtoOpenContextItem::XMLtoLinkedSpatialProps($OpenContextItem, $itemXML);
		$OpenContextItem->interestCalc();
		
		if(!is_array($OpenContextItem->classes)){
			$OpenContextItem->addSimpleArrayItem("No Object", "classes");
		}
		
		$solrDocument = new Apache_Solr_Document();
		$solrDocument = $OpenContextItem->makeSolrDocument($solrDocument);
		
		
		return $solrDocument;
		
	}//end function

	
	
	
	public function documentSolrDoc($itemUpdated, $url){
	
		
		$url = $this->prepURL($url);
		$xmlString = file_get_contents($url);
		$xmlString = $this->accentFix($xmlString);
		$itemXML = simplexml_load_string($xmlString);
		
		//echo "<br/>".$url;
		
		$OpenContextItem = new OpenContextItem;
		$OpenContextItem->initialize();
		$OpenContextItem->update = date("Y-m-d\TH:i:s\Z", strtotime($itemUpdated));
	
		$itemXML->registerXPathNamespace("oc", OpenContext_OCConfig::get_namespace("oc", "media"));
		$itemXML->registerXPathNamespace("arch", OpenContext_OCConfig::get_namespace("arch", "media"));
		$itemXML->registerXPathNamespace("dc", OpenContext_OCConfig::get_namespace("dc"));
		$itemXML->registerXPathNamespace("gml", OpenContext_OCConfig::get_namespace("gml"));
		
		$OpenContextItem = OpenContext_XMLtoOpenContextItem::XMLmediaItemBasics($OpenContextItem, $itemXML);
		$OpenContextItem = OpenContext_XMLtoOpenContextItem::XMLtoSpatialClass($OpenContextItem, $itemXML);
		
		if(strtolower($OpenContextItem->documentType) == "image"){
		    $OpenContextItem = OpenContext_XMLtoOpenContextItem::XMLtoImageSizeData($OpenContextItem, $itemXML);
		}
		
		$OpenContextItem = OpenContext_XMLtoOpenContextItem::XMLtoContextData($OpenContextItem, $itemXML);
		$OpenContextItem = OpenContext_XMLtoOpenContextItem::XMLtoMediaLinkData($OpenContextItem, $itemXML);
		$OpenContextItem = OpenContext_XMLtoOpenContextItem::XMLtoPersonLinksData($OpenContextItem, $itemXML);
		$OpenContextItem = OpenContext_XMLtoOpenContextItem::XMLtoSocialData($OpenContextItem, $itemXML);
		$OpenContextItem = OpenContext_XMLtoOpenContextItem::XMLtoGeoData($OpenContextItem, $itemXML);
		$OpenContextItem = OpenContext_XMLtoOpenContextItem::XMLtoChronoData($OpenContextItem, $itemXML);
		$OpenContextItem = OpenContext_XMLtoOpenContextItem::XMLtoMetadata($OpenContextItem, $itemXML);
		$OpenContextItem = OpenContext_XMLtoOpenContextItem::XMLtoProperties($OpenContextItem, $itemXML);
		$OpenContextItem->interestCalc();
		
		if(!is_array($OpenContextItem->classes)){
			$OpenContextItem->addSimpleArrayItem("No Object", "classes");
		}
		
		$solrDocument = new Apache_Solr_Document();
		$solrDocument = $OpenContextItem->makeSolrDocument($solrDocument);
		
		return $solrDocument;
		
	}//end function

	
	
	public function projectSolrDoc($itemUpdated, $url){
	
				
		$url = $this->prepURL($url);
		$xmlString = file_get_contents($url);
		$xmlString = $this->accentFix($xmlString);
		$itemXML = simplexml_load_string($xmlString);
		
		$OpenContextItem = new OpenContextItem;
		$OpenContextItem->initialize();
		$OpenContextItem->update = date("Y-m-d\TH:i:s\Z", strtotime($itemUpdated));
	
		$itemXML->registerXPathNamespace("oc", OpenContext_OCConfig::get_namespace("oc", "project"));
		$itemXML->registerXPathNamespace("arch", OpenContext_OCConfig::get_namespace("arch", "project"));
		$itemXML->registerXPathNamespace("dc", OpenContext_OCConfig::get_namespace("dc"));
		$itemXML->registerXPathNamespace("gml", OpenContext_OCConfig::get_namespace("gml"));
		
		$OpenContextItem = OpenContext_XMLtoOpenContextItem::XMLprojectItemBasics($OpenContextItem, $itemXML);
		$OpenContextItem = OpenContext_XMLtoOpenContextItem::XMLtoContextData($OpenContextItem, $itemXML);
		$OpenContextItem = OpenContext_XMLtoOpenContextItem::XMLtoMediaLinkData($OpenContextItem, $itemXML);
		$OpenContextItem = OpenContext_XMLtoOpenContextItem::XMLtoPersonLinksData($OpenContextItem, $itemXML);
		$OpenContextItem = OpenContext_XMLtoOpenContextItem::XMLtoSocialData($OpenContextItem, $itemXML);
		$OpenContextItem = OpenContext_XMLtoOpenContextItem::XMLtoGeoData($OpenContextItem, $itemXML);
		$OpenContextItem = OpenContext_XMLtoOpenContextItem::XMLtoChronoData($OpenContextItem, $itemXML);
		$OpenContextItem = OpenContext_XMLtoOpenContextItem::XMLtoMetadata($OpenContextItem, $itemXML);
		$OpenContextItem = OpenContext_XMLtoOpenContextItem::XMLtoProperties($OpenContextItem, $itemXML);
		$OpenContextItem->interestCalc();
		
		$solrDocument = new Apache_Solr_Document();
		$solrDocument = $OpenContextItem->makeSolrDocument($solrDocument);
		
		return $solrDocument;
		
	}//end function
	
	/*
	
	DELETE noid_bindings FROM `noid_bindings` 
JOIN space ON (noid_bindings.itemUUID = space.uuid AND noid_bindings.itemType = 'spatial')
WHERE space.project_id = '2'
	
	*/
	
	
	
	public function personSolrDoc($itemUpdated, $url){
	
		$url = $this->prepURL($url);
		$xmlString = file_get_contents($url);
		$xmlString = $this->accentFix($xmlString);
		$itemXML = simplexml_load_string($xmlString);
		
		echo "<br/>".$url;
		
		$OpenContextItem = new OpenContextItem;
		$OpenContextItem->initialize();
		$OpenContextItem->update = date("Y-m-d\TH:i:s\Z", strtotime($itemUpdated));
	
		$itemXML->registerXPathNamespace("oc", OpenContext_OCConfig::get_namespace("oc", "person"));
		$itemXML->registerXPathNamespace("arch", OpenContext_OCConfig::get_namespace("arch", "person"));
		$itemXML->registerXPathNamespace("dc", OpenContext_OCConfig::get_namespace("dc"));
		$itemXML->registerXPathNamespace("gml", OpenContext_OCConfig::get_namespace("gml"));
		
		$OpenContextItem = OpenContext_XMLtoOpenContextItem::XMLpersonItemBasics($OpenContextItem, $itemXML);
		$OpenContextItem = OpenContext_XMLtoOpenContextItem::XMLtoMediaLinkData($OpenContextItem, $itemXML);
		$OpenContextItem = OpenContext_XMLtoOpenContextItem::XMLtoPersonLinksData($OpenContextItem, $itemXML);
		$OpenContextItem = OpenContext_XMLtoOpenContextItem::XMLtoSocialData($OpenContextItem, $itemXML);
		$OpenContextItem = OpenContext_XMLtoOpenContextItem::XMLtoMetadata($OpenContextItem, $itemXML);
		$OpenContextItem = OpenContext_XMLtoOpenContextItem::XMLtoProperties($OpenContextItem, $itemXML);
		$OpenContextItem->interestCalc();
		
		$solrDocument = new Apache_Solr_Document();
		$solrDocument = $OpenContextItem->makeSolrDocument($solrDocument);
		
		return $solrDocument;
		
	}//end function
	
    
    

}