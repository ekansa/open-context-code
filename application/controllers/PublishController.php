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
		  
		  mb_internal_encoding( 'UTF-8' );
		  
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
		  mb_internal_encoding( 'UTF-8' );
		  
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
        
		  //echo $data->archaeoML;
    }
	 

    
    
    
	 public function tablePublishAction() {
		  $this->_helper->viewRenderer->setNoRender();	
		  mb_internal_encoding( 'UTF-8' );
		  
		  if(isset($_REQUEST["json"])){
				$json = $_REQUEST["json"];
				$tabObj = new ExportTable;
				$ok = $tabObj->createUpdate($json);
				unset($json);
				if($ok){
					 $output = $tabObj->metadata;
				}
				else{
					 $output = array("error" => "Bad JSON data");
				}
		  }
		  else{
				$output = array("error" => "no data");
		  }
		  
		  header('Content-Type: application/json; charset=utf8');
        echo Zend_Json::encode($output) ;
        
		  //echo $data->archaeoML;
    }
   
}

