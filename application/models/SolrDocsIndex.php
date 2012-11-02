<?php


/*
 this class caches archaeoML documents so that they can be indexed in bulk by solr.
 indexing an array of solr documents is faster than doing them individually
*/

class SolrDocsIndex {
    
    const indexDoSize = 50; //number of items to be cached before being indexed
    
    public $solrDocArray; //array of solrDocs to index
    public $db; //database object
    
    public $forceIndexing = false; //boolean, if true then do an indexing even if less than the indexDoSize
    public $toDoList; //list if items needing to be indexed by Solr
    public $toDoCount; //count of items in toDoList
    
    public $totalIndexed; //number of items successfully indexed
    public $errors = array(); //any errors with solr go here
    
    public function initialize(){
		  $db_params = OpenContext_OCConfig::get_db_config();
		  $db = new Zend_Db_Adapter_Pdo_Mysql($db_params);
		  $db->getConnection();
		  $this->setUTFconnection($db);
		  $this->db = $db;
		  $this->checkTableIndex();
		  $this->solrDocArray = false;
		  $this->toDoList = false;
		  $this->toDoCount = 0;
		  $this->errors = array();
		  $this->totalIndexed = 0;
    }

    /*

    */
    public function checkRunIndex($only_full_list = true){
		  $this->initialize();
		  $this->getToDoList();
		  if($only_full_list){
				if(($this->toDoCount >= self::indexDoSize) || $this->forceIndexing){
					$this->makeSolrDocArray();
					$this->indexSolrDocs();
					$this->updateToDoList();
				}
				else{
					$errors = $this->errors;
					$errors[] = $this->toDoCount." is less than min-batch size: ".self::indexDoSize;
					$this->errors = $errors;
				}
		  }
    }//end function



    /*
     This function (finally) interacts with Solr to index the documents in the document array
    */
    public function indexSolrDocs(){
		$solrDocArray = $this->solrDocArray;
		if(is_array($solrDocArray)){
			$solr = new Apache_Solr_Service('localhost', 8983, '/solr');
			
			if ($solr->ping()) { // if we can ping the solr server...
				try{
					$updateResponse = $solr->addDocuments($solrDocArray);
					$solr->commit();
				}
				catch (Exception $e) {
					$errors = $this->errors;
					$errors[] = $e->getMessage(); 
					$this->errors = $errors;
				}
			}
			else{
			$errors = $this->errors;
			$errors[] = $this->errors = "Solr Down: failed to respond to ping."; 
			$this->errors = $errors;
			}
			
		}//yes, we do have docs to add
		
    }//end function



    //after updating Solr successfully, we can now update the noid_bindings table to show the items indexed.
    public function updateToDoList(){
		$db = $this->db;
		if(count($this->errors)<1 && is_array($this->toDoList)){ //no error, to do list is an array
			
			foreach($this->toDoList as $item){
				$itemUUID = $item["itemUUID"];
				$where = array();
				$where[] = "itemUUID = '$itemUUID' ";
				$data = array('solr_indexed' => 1);
				$db->update('noid_bindings', $data, $where);
			}
			
			$this->totalIndexed = count($this->toDoList);
		}
    }




    public function getToDoList(){
		  $db = $this->db;
		  
		  $itemTypes = "itemType = 'spatial'
		  OR itemType = 'media'
		  OR itemType = 'document'
		  OR itemType = 'project'
		  OR itemType = 'person'
		  OR itemType = 'table'
		  ";
		  
		  $sql = "SELECT itemType, itemUUID
			  FROM noid_bindings
			  WHERE solr_indexed = 0 AND ($itemTypes)
			  ORDER BY ItemCreated ASC, ItemUpdated ASC
			  LIMIT 0,".self::indexDoSize."
			  ";
			  
		  //note: I did check this. Order ItemUpdated by ASC makes the OLDEST updated item 1st in the feed.
		  //echo $sql;
		  $result = $db->fetchAll($sql, 2);
		  if($result){
			  $this->toDoList = $result;
			  $this->toDoCount = count($result);
			  //echo "todo count ".$this->toDoCount;
		  }
		  else{
			  $this->toDoList = false;
			  $this->toDoCount = 0;
		  }
	 }//end function
		
		
	//check for tables to index
	public function checkTableIndex(){
		$db = $this->db;
		
		$sql = "SELECT * FROM dataset WHERE table_num <= 1";
		
		$result = $db->fetchAll($sql, 2);
		
		foreach($result AS $row){
			$cacheID = $row["cache_id"];
			$tableId = OpenContext_TableOutput::tableID_toURL($cacheID);
			$noid = $row["noid"];
			$created = $row["created_on"];
			
			if(!stristr($tableId, "http://")){
				$host = OpenContext_OCConfig::get_host_config();
				$tableId = $host."/tables/".$tableId;
			}
			
			$sql = "SELECT * FROM noid_bindings WHERE itemUUID = '$cacheID' LIMIT 1";
			$resultB = $db->fetchAll($sql, 2);
			
			if(!$resultB){
				$data = array("noid" => $noid,
							  "itemType" => "table",
							  "itemUUID" => $cacheID,
							  "itemURI" => $tableId,
							  "public" => 0,
							  "solr_indexed" => 0
							  );
				$db->insert("noid_bindings", $data);
			}
			
		}//end loop
	
	}//end function
		
		
		
	 public function makeSolrDocArray(){
		  $solrDocArray = array();
		  
		  if(is_array($this->toDoList)){
				foreach($this->toDoList as $item){
					 $itemUUID = $item['itemUUID'];
					 $solrDoc = false;
					 echo "Item: ".$itemUUID." .<br/>";
					 if($item['itemType'] == 'spatial'){
						  $itemObj = New Subject;
						  $itemXMLstring = $itemObj->getItemXML($itemUUID);
						  if(strlen($itemXMLstring)>10){
								$solrDoc = $this->spatialItemSolrDoc($itemXMLstring);
						  }
					 }
					 elseif($item['itemType'] == 'media'){
						 $itemObj = New Media;
						 $itemXMLstring = $itemObj->getItemXML($itemUUID);
						 $solrDoc = $this->mediaItemSolrDoc($itemXMLstring);
					 }
					 elseif($item['itemType'] == 'person'){
						 $itemObj = New Person;
						 $itemXMLstring = $itemObj->getItemXML($itemUUID);
						 $solrDoc = $this->personItemSolrDoc($itemXMLstring);
					 }
					 elseif($item['itemType'] == 'document'){
						 $itemObj = New Document;
						 $itemXMLstring = $itemObj->getItemXML($itemUUID);
						 $solrDoc = $this->docItemSolrDoc($itemXMLstring);
					 }
					 elseif($item['itemType'] == 'project'){
						 $itemObj = New Project;
						 $itemXMLstring = $itemObj->getItemXML($itemUUID);
						 $solrDoc = $this->projectItemSolrDoc($itemXMLstring);
					 }
					 elseif($item['itemType'] == 'table'){
						 
						 if(!stristr($itemUUID, "/")){
							 $OpenContextItem = new OpenContextItem;
							 $OpenContextItem->initialize();
							 $tableObj = new Table;
							 $tableObj->getByID($itemUUID);
							 $tableObj->getTableJSON($itemUUID);
							 $OpenContextItem = $tableObj->solrIndex($OpenContextItem);
							 if($tableObj->solrIndexingError == false){
								 $OpenContextItem->interestCalc();
								 $solrDoc = new Apache_Solr_Document();
								 $solrDoc = $OpenContextItem->makeSolrDocument($solrDoc);
							 }
							 else{
								 $errors = $this->errors;
								 $errors[] = "Problem with $itemUUID.";
								 $this->errors = $errors;
							 }
						 }
						 else{
							 //we only need to index the first table of a set. 
							 $db = $this->db;
							 $where = array();
							 $where[] = "itemUUID = '$itemUUID' ";
							 $data = array('solr_indexed' => 1);
							 $db->update('noid_bindings', $data, $where);
						 }
						 
					 }
					 else{
						 $itemXMLstring = false;
					 }
					 
					 if($solrDoc != false){
						 $solrDocArray[] = $solrDoc;
					 }
					 
					 unset($itemObj);
					 unset($itemXMLstring);
					 unset($solrDoc);
				}//end loop through items	
		  }//end case of array to loop through
		  
		  $this->solrDocArray = false;
		  
		  if(count($solrDocArray)>0){
			  $this->solrDocArray = $solrDocArray;
		  }
	
    }
    
    /*
    Read ArchaeoML spatial string, make generalized OpenContextItem, generate SolrDocument
    */
    public function spatialItemSolrDoc($itemXMLstring){
	
	$itemXML = simplexml_load_string($itemXMLstring);
	$itemXML->registerXPathNamespace("arch", OpenContext_OCConfig::get_namespace("arch", "spatial"));
	$itemXML->registerXPathNamespace("oc", OpenContext_OCConfig::get_namespace("oc", "spatial"));
	$itemXML->registerXPathNamespace("dc", OpenContext_OCConfig::get_namespace("dc"));
	$itemXML->registerXPathNamespace("gml", OpenContext_OCConfig::get_namespace("gml"));
	$OpenContextItem = new OpenContextItem;
	$OpenContextItem->initialize();
        $OpenContextItem->update = date("Y-m-d\TH:i:s\Z");
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
    }//end function
    
    
    /*
    Read ArchaeoML media resource string, make generalized OpenContextItem, generate SolrDocument
    */
    public function mediaItemSolrDoc($itemXMLstring){
	
	$OpenContextItem = new OpenContextItem;
	$OpenContextItem->initialize();
	$OpenContextItem->update = date("Y-m-d\TH:i:s\Z");

	$itemXML = simplexml_load_string($itemXMLstring);
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
    
    
    /*
    Read ArchaeoML document / diary resource string, make generalized OpenContextItem, generate SolrDocument
    */
    public function docItemSolrDoc($itemXMLstring){
	
	$OpenContextItem = new OpenContextItem;
	$OpenContextItem->initialize();
	$OpenContextItem->update = date("Y-m-d\TH:i:s\Z");

	$itemXML = simplexml_load_string($itemXMLstring);
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
    
    
    /*
    Read ArchaeoML project string, make generalized OpenContextItem, generate SolrDocument
    */
    public function projectItemSolrDoc($itemXMLstring){
	
	//echo htmlentities($itemXMLstring);
	
	$OpenContextItem = new OpenContextItem;
	$OpenContextItem->initialize();
	$OpenContextItem->update = date("Y-m-d\TH:i:s\Z");

	$itemXML = simplexml_load_string($itemXMLstring);
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
    Read ArchaeoML project string, make generalized OpenContextItem, generate SolrDocument
    */
    public function personItemSolrDoc($itemXMLstring){
	
	$OpenContextItem = new OpenContextItem;
	$OpenContextItem->initialize();
	$OpenContextItem->update = date("Y-m-d\TH:i:s\Z");

	$itemXML = simplexml_load_string($itemXMLstring);
	
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
    
    
    private function setUTFconnection($db){
	$sql = "SET collation_connection = utf8_unicode_ci;";
	$db->query($sql, 2);
	$sql = "SET NAMES utf8;";
	$db->query($sql, 2);
    } 
    
    
}
