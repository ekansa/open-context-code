<?php
/** Zend_Controller_Action */
require_once 'Zend/Controller/Action.php';
//ini_set("memory_limit", "2048M");
ini_set("memory_limit", "512M");
// set maximum execution time to no limit
ini_set("max_execution_time", "0");


class publishController extends Zend_Controller_Action
{   
      
    public function indexAction()
    {
    
    }
    
    const xmlRoot = "http://penelope.oc/xml/";
    //const xmlRoot = "http://about.oc/oc_xmlgen/";
    //const xmlRoot = "http://ux.opencontext.org/xml/oc_xmlgen/"; 
    
    public function docAddAction() {
		  $this->_helper->viewRenderer->setNoRender();	
        $type = $_REQUEST["type"];
        $id = $_REQUEST["id"];
	
		  //update or add. default is adding new documents
		  if(!isset($_REQUEST["doUpdate"])){
            $doUpdate = false;
        }
        else{
            $doUpdate = $_REQUEST["doUpdate"];
			 $doUpdate = true;
        }
        
		  $data = false;
        if($type == "space"){
            //$xmlString = file_get_contents(self::xmlRoot."space_v3.php?imp=importer_etana&item=".$id);
			 //$xmlString = file_get_contents("http://opencontext.org/subjects/".$id.".xml");
				$xmlString = file_get_contents(self::xmlRoot."space?xml=1&id=".$id);
            $data = OpenContext_NewDocs::spaceAdd($xmlString, $doUpdate);
        }
        elseif($type == "person"){
            //$xmlString = file_get_contents(self::xmlRoot."person.php?imp=true&item=".$id);
				$xmlString = file_get_contents(self::xmlRoot."person?xml=1&id=".$id);
            $data = OpenContext_NewDocs::personAdd($xmlString);
        }
        elseif($type == "prop"){
            //$xmlString = file_get_contents(self::xmlRoot."property.php?imp=true&item=".$id);
				$xmlString = file_get_contents(self::xmlRoot."property?xml=1&id=".$id);
				$data = OpenContext_NewDocs::propertyAdd($xmlString, $doUpdate);
        }
		  elseif($type == "media"){
				  //$xmlString = file_get_contents(self::xmlRoot."media.php?imp=true&item=".$id);
			  //$xmlString = file_get_contents("http://opencontext.org/media/".$id.".xml");
			  $xmlString = file_get_contents(self::xmlRoot."media?xml=1&id=".$id);
				$data = OpenContext_NewDocs::mediaAdd($xmlString, $doUpdate);
			  
		  }
		  elseif($type == "doc"){
				  //$xmlString = file_get_contents(self::xmlRoot."media.php?imp=true&item=".$id);
			  //$xmlString = file_get_contents("http://opencontext.org/media/".$id.".xml");
			  $xmlString = file_get_contents(self::xmlRoot."document?xml=1&id=".$id);
			  $data = OpenContext_NewDocs::documentAdd($xmlString, true, $doUpdate);
			 }
		  elseif($type == "proj"){
				  //$xmlString = file_get_contents(self::xmlRoot."project.php?imp=true&item=".$id);
			  $xmlString = file_get_contents(self::xmlRoot."prokect?xml=1&id=".$id);
				  $data = OpenContext_NewDocs::projectAdd($xmlString);
			 }
        else{
				$data = false;
		  }
        echo Zend_Json::encode($data);
        
    }
	 
	 
	 public function indexUpdateAction(){
		  $this->_helper->viewRenderer->setNoRender();
		  
		  $output = array();
		  $siteIndexObj = new SitePageIndex;
		  $siteIndexObj->indexAll();
		  $output["sitePageArray"] = $siteIndexObj->pageArray;
		  $output["sitePages"] = $siteIndexObj->docsAdded;
		  $output["sitePageError"] = $siteIndexObj->error;
		  
		  $SolrDocsIndexer = new SolrDocsIndex;
		  $SolrDocsIndexer->forceIndexing = true;
		  $output["indexItems"] = $SolrDocsIndexer->checkRunIndex();
		  $output["indexError"] =false;
		  $output["errors"] = false;
		  if(is_array($SolrDocsIndexer->errors)){
				if(count($SolrDocsIndexer->errors)>0){
					 $output["indexError"] = true;
					 $output["errors"] = $SolrDocsIndexer->errors;
				}
		  }
		  
		  if(isset($_REQUEST["optimize"])){
				$solr = new Apache_Solr_Service('localhost', 8983, '/solr');
				$solr->optimize();
				$output["solrOptimize"] = true;
		  }
		  else{
				$output["solrOptimize"] = false;
		  }
		  
		  header('Content-Type: application/json; charset=utf8');
        echo Zend_Json::encode($output) ;
	 }
	 
    
    public function itemPublishAction() {
		  $this->_helper->viewRenderer->setNoRender();	
		  
		  
		  //id of the document to processed
		  if(!isset($_REQUEST["itemUUID"])){
				$itemUUID = false;
				$id = null;
		  }
		  else{
				$itemUUID = $_REQUEST["itemUUID"];
				$id = $itemUUID;
		  }
		  
		  //XML posted to be processed
		  if(!isset($_REQUEST["xml"])){
				$xmlString = false;
				if(isset($_REQUEST["useURI"])){
					 $xmlURI = $_REQUEST["useURI"];
					 @$xmlString = file_get_contents($xmlURI);
				}
		  }
		  else{
				$xmlString = $_REQUEST["xml"];
				$xmlString = stripslashes($xmlString); 
		  }
		  
		  
		  //type of document to be processed
		  if(!isset($_REQUEST["itemType"])){
				$itemType = false;
		  }
		  else{
				$itemType = $_REQUEST["itemType"];
		  }
		  
		  //update or add. default is adding new documents
		  if(!isset($_REQUEST["doUpdate"])){
				$doUpdate = false;
		  }
		  else{
				$doUpdate = $_REQUEST["doUpdate"];
		  }
				 
		  //$xmlString .= "garbage";
		  @$xml = simplexml_load_string($xmlString);
	 
		  $output = array();
		  if(!$xml){
				$output["itemType"] = $itemType;
				$output["itemUUID"] = $itemUUID;
				$output["pubOK"] = false;
				$output["error"] = true;
				$output["errors"][] = "XML invalid";
		  }
		  else{
				if($itemType == "space"){
					 $data = OpenContext_NewDocs::spaceAdd($xmlString, true, $doUpdate);
					 if(isset($data->errors)){
						  if(is_array($data->errors)){
								foreach($data->errors as $errorKey => $error){
									 if(stristr($error,'Observe Insert')){
										  $data = OpenContext_NewDocs::spaceAdd($xmlString, true, true);
									 }
								}
						  }
					 }
				}
				elseif($itemType == "person"){
					 $data = OpenContext_NewDocs::personAdd($xmlString, $doUpdate);
				}
				elseif($itemType == "prop"){
					 $data = OpenContext_NewDocs::propertyAdd($xmlString, $doUpdate);
				}
				elseif($itemType == "media"){
					 $data = OpenContext_NewDocs::mediaAdd($xmlString, true, $doUpdate);
				}
				elseif($itemType == "proj"){
					 $data = OpenContext_NewDocs::projectAdd($xmlString, $doUpdate);
				}
				elseif($itemType == "doc"){
					 $data = OpenContext_NewDocs::documentAdd($xmlString, $doUpdate);
				}
				
				if($data->itemUUID){
					 
					 $output = array(
							  "label" => $data->label,
							  "project_id" => $data->projectUUID,
							  "item_uuid" => $itemUUID,
							  "item_type" => $itemType,
							  "pubOK" => true,
						     "errors" => false);

					 if(isset($data->errors)){
						  if(is_array($data->errors)){
								foreach($data->errors as $errorKey => $error){
									 if($errorKey != "Small-to-do"){
										  $output["pubOK"] = false;
										  $output["errors"][] = $error;
									 }
								}
						  }
					 }
				}
				else{
					 $output = array(
							  "label" => $data->label,
							  "project_id" => $data->projectUUID,
							  "item_uuid" => $itemUUID,
							  "item_type" => $itemType,
							  "pubOK" => false,
							  "errors" => false);
					 
					 if(isset($data->errors)){
						  $output["errors"] = $data->errors;
					 }
				}
		  }
		  header('Content-Type: application/json; charset=utf8');
        echo Zend_Json::encode($output) ;
    }
	 

    
    
    public function indexOmekaAction() {
	$this->_helper->viewRenderer->setNoRender();	
	
	$jsonString = false;
	$jsonObject = false;
	$OmekaURL = false;
	$errorArray = array();
	
	$omeka = new Omeka;
	
	
	if(isset($_REQUEST["url"])){
	    $OmekaURL = $_REQUEST["url"];
	}
	else{
	    $errorCode = 400;
	    $headerString = "HTTP/1.1 400 Bad Request";
	    $errorArray[] = array("error" => $errorCode,
			    "errorMessage" => "Please provide the URL to the Omeka item in the 'url' parameter.");
	}
	
	
	if(isset($_REQUEST["json"])){
	    $jsonString = $_REQUEST["json"];
	    $jsonString = stripslashes($jsonString);
	    $jsonObject = Zend_Json::decode($jsonString);
	    if($jsonObject == false){
		$errorCode = 400;
		$headerString = "HTTP/1.1 400 Bad Request";
		$errorArray[] = array("error" => $errorCode,
				"errorMessage" => "Could not parse JSON POSTed in the parameter 'json'.",
				"POSTed_json" => $jsonString);
	    }
	}
	else{
	    $errorCode = 400;
	    $headerString = "HTTP/1.1 400 Bad Request";
	    $errorArray[] = array("error" => $errorCode,
			    "errorMessage" => "Please POST Omeka-JSON in the parameter 'json'.");
	}
	
	if($jsonObject != false && $jsonString != false && $OmekaURL != false){
	    
	    $omeka->json = $jsonString;
	    $omeka->itemURI = $OmekaURL;
	    $omeka->label = $OmekaURL;
	    $omeka->baseURL = $OmekaURL;
	    $omeka->add_update_doc($OmekaURL);

	    $errorArray = false;
	    $headerString = "HTTP/1.1 200 OK";
	    $cache_id = md5($OmekaURL);
	    $frontendOptions = array(
		    'lifetime' => 72000, // cache lifetime, measured in seconds, 7200 = 2 hours
		    'automatic_serialization' => true
	    );
		    
	    $backendOptions = array(
		'cache_dir' => './omeka_cache/' // Directory where to put the cache files
	    );
		    
	    $cache = Zend_Cache::factory('Core',
				 'File',
				 $frontendOptions,
				 $backendOptions);
	    
	    
	    
	    //$cache->clean(Zend_Cache::CLEANING_MODE_OLD); // clean old cache records
	    if(!$cache_result = $cache->load($cache_id)) {
		$cache->save($jsonString, $cache_id); //save result to the cache
	    }
	    
	    
	    
	    
	}
	
	
	$output["errors"] = $errorArray;
	$output["url"] = $OmekaURL;
	$output["POSTed_json"] = $jsonString;
	
	header($headerString);
	echo Zend_Json::encode($output);
    }
    
    
    
    
    
    
    
    
    
    /*
    Sample Bad XML:
    
    <opencont_penimporter>
    <!-- Table w_space -->
    <w_space>
        <Bad_UUID>9CD6A5A1-CB3E-4756-0344-AC7F9054416A</Bad_UUID>
        <Bad_UUID_name>SDI-16774</Bad_UUID_name>
        <space_label>SDAC 203</space_label>
        <uuid>CC27BA46-6B7A-4870-10CF-53F00F3DE36B</uuid>
    </w_space> 
     
     
    */
    
    
    /*
    //is deletes items either, and updates parent items of deleted items to not contain links to deleted children
    //it saves deleted items in a table so the deletes can be undone and / or archived
    public function docDeleteAction(){
	
	$this->_helper->viewRenderer->setNoRender();	
	$host = OpenContext_OCConfig::get_host_config();
        
	$auth = $_REQUEST["auth"];
	
	if($auth != "zapper1"){
	    echo "not authorized";
	    break;
	}
	else{
	    
	    $badIDs = array();
	    $type = $_REQUEST["type"];
	
	    if($type == "space"){
		$URIbase = "subjects";
	    }
	    elseif($type == "media"){
		$URIbase = "media";
	    }
	
	
	    if(!isset($_REQUEST["id"])){
		$xmlURI = $_REQUEST["xmlURI"];
		$xmlURI = 'http://ux.opencontext.org/XMLdocs/bad_bones.xml';
		$xmlString = file_get_contents($xmlURI);
		$xml = simplexml_load_string($xmlString);
		
		foreach($xml->xpath("//Bad_UUID") as $BadIdVal) {
		    $BadId = $BadIdVal."";
		    if(!in_array($BadId, $badIDs)){
			$badIDs[] = $BadId;
			echo "<br/>Bad item: <a href='../".$URIbase."/".$BadId."'>".$BadId."</a>";
		    }
		}
	    }
	    else{
		$badIDs[] = $_REQUEST["id"];
	    }
	
	
	    if(!isset($_REQUEST["simple"])){
		$doComplex = true;
	    }
	    else{
		$doComplex = false;
	    }
	    
	    if(($type == "space")&& $doComplex){
		//get array of parent ids, so that their xml can be edited
		$parentIDs = OpenContext_DeleteDocs::findParents($badIDs);
		if(is_array($parentIDs)){
		    foreach($parentIDs as $parentID){
			
			echo "<br/>Change Parent: <a href='../subjects/".$parentID."'>".$parentID."</a>";
			$parentXML = file_get_contents($host."/subjects/".$parentID.".xml");
			$originalXML = $parentXML;
			
			foreach($badIDs as $badChild){
			    //remove children from the parent xml
			    echo "<br/>Checking on: ".$badChild;
			    $parentXML = OpenContext_DeleteDocs::childNodeRemove($badChild, $parentXML);
			}
			
			if(md5($originalXML)!=md5($parentXML)){
			    //if parent xml is different, save update
			    $oldSaved = false;
			    $oldSaved = OpenContext_DeleteDocs::saveBeforeUpdate($parentID, "space", $originalXML);
			    
			    if($oldSaved){
				//if a record is saved of the old version, now go update the existing version
				$data = OpenContext_NewDocs::spaceAdd($parentXML, true, true);
				$indexUpdate = file_get_contents($host."/publish/itempublish?index=true");
				//echo $indexUpdate;
				
				//header ("content-type: text/xml");
				//echo $parentXML;  
			    }
			    
			}
			
		    }//end loop
		
		}//end case of parent to edit
	    }
	
	
	    $solr = new Apache_Solr_Service('localhost', 8983, '/solr');
		// test the connection to the solr server
	    if (@$solr->ping()) {
	    	
		foreach($badIDs as $actBadID){
		    if(($type == "space")&& $doComplex){
			$childrenIDs = array();
			$childrenIDs = OpenContext_DeleteDocs::getChildren($actBadID, $childrenIDs);
			
			foreach($childrenIDs as $badChild){
			    //echo "<br/>".$badChild;
			    echo "<br/>Bad Child: <a href='../subjects/".$badChild."'>".$badChild."</a>";
			    OpenContext_DeleteDocs::spaceDeleteDB($badChild);
			}
			
			try{
			    $solr->deleteByMultipleIds($childrenIDs);
			}
			catch (Exception $e) {
			    $output["error"] = "error deleting children items";
			}
			
			unset($childrenIDs);
			
			OpenContext_DeleteDocs::spaceDeleteDB($actBadID);
		    }//end case of space
		    elseif($type == "media"){
			OpenContext_DeleteDocs::mediaDeleteDB($actBadID);
		    }

		}//end loop through bad ids
		
		try{
		    $solr->deleteByMultipleIds($badIDs);
		    if(!isset($_REQUEST["id"])){
			$solr->optimize();
		    }
		    else{
			$solr->optimize();
		    }
		}
		catch (Exception $e) {
		    $output["error"] = "error deleting items";
		}
		
	    }//end case of solr ping
	    
	    
	}//end case with authorization
    }//end function
    
    
    */
    
    
    
    
   
}

