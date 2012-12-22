<?php

class OpenContext_NewDocs {
	
	
	const fullContextDelimiter = "|xx|"; // deliminator for context paths
	const SolrAddSize = 25; //number of solr docs to add before processing the cached solr documents
	const cacheLife = 72000; //lifetime fo the solr cache
	const SpaceSolrCacheID = "AddSolrSpace";
	const MediaSolrCacheID = "AddSolrMedia";
	const DocSolrCacheID = "AddSolrDoc";
	
	/*
	Work-flow
	
	(1) Valid XML of the right type (spatial, media, document, project, person)
		is sent to a "spaceAdd" (etc.) function
	(2) An object of the right type is created (Subject=spatial, media, document, project, person)
	(3) Atom entries are made using function suplied by the object
	(4) The class OpenContext_XMLtoItems has functions for reading the ArchaeoML and
		populating the database
	(5) Once basic data is read using OpenContext_XMLtoItems, the item, it's ArchaeoML
		and it's atom entry data are saved in the appropriate table
	(6) OpenContext_XMLtoItems then save observations and linking relations for the item
	(7) The item is then cued for indexing with Solr
	(8) If the Solr index cue has enough items, the SolrDocsIndex class object then
		retrieves XML data for each item in the cue. 
	(9) An "OpenContextItem" object is then made from the XML data for Solr-cued item. Functions in the XMLtoOpenContextItem class
	are used to construct the OpenContextItem. An OpenContextItem is the standard default object used for creating a document for Solr
	to index. OpenContextItem should be the same for EACH deployment of OpenContext, regardless of the application
	(public health, chemistry, archaeology)
	
	*/
	
	public static function spaceAdd($itemXML_string, $versionUpdate = false){
		
		//if $versionUpdate = true, then store the old version of the data. if not, then over write existing data 
		
		$db_params = OpenContext_OCConfig::get_db_config();
		$db = new Zend_Db_Adapter_Pdo_Mysql($db_params);
		$db->getConnection();
		
		$errors = array();
		$itemObj = new Subject; //start subject (location item, spatial) class
		$itemObj->archaeoML = $itemXML_string;
		$itemObj->createdTime = date("Y-m-d\TH:i:s\Z");
		$itemObj->DOM_spatialAtomCreate($itemXML_string);
		
		$itemXML = simplexml_load_string($itemXML_string);
		$itemXML->registerXPathNamespace("arch", OpenContext_OCConfig::get_namespace("arch", "spatial"));
		$itemXML->registerXPathNamespace("oc", OpenContext_OCConfig::get_namespace("oc", "spatial"));
		$itemXML->registerXPathNamespace("dc", OpenContext_OCConfig::get_namespace("dc"));
		$itemXML->registerXPathNamespace("gml", OpenContext_OCConfig::get_namespace("gml"));
		
		$itemObj = OpenContext_XMLtoItems::XMLsubjectBasic($itemObj, $itemXML);
		
		$updateInsertSuccess = false;
		/*
		if(!isset($itemObj->contextPath)){
			echo $itemXML_string;
			break;
		}
		*/
		
		if(strlen($itemObj->contextPath)>1){
			$updateInsertSuccess = $itemObj->createUpdate($versionUpdate);
		}
		
		
		if($updateInsertSuccess){
			//insert links, properties, children items. modify parent if link to child is not found
			$OKlinks = OpenContext_XMLtoItems::linksRetrieve($itemObj->projectUUID, $itemObj->sourceID, $itemObj->itemUUID, "spatial", $itemXML, $db);
			
			//Turn this on if adding properties via XML docs, works faster if you're not
			$OKprops = OpenContext_XMLtoItems::obs_props_Retrieve($itemObj->projectUUID, $itemObj->sourceID, $itemObj->itemUUID, "spatial", $itemXML, $db);
			
			$OKchilds = OpenContext_XMLtoItems::childrenRetrieve($itemObj->projectUUID, $itemObj->sourceID, $itemObj->itemUUID, $itemXML, $db);
			$OKparents = OpenContext_XMLtoItems::parentCheck($itemObj->projectUUID, $itemObj->sourceID, $itemObj->itemUUID, $itemObj->label, $itemObj->className, $itemXML, $db);
		
			/*
			 Now do the solr indexing (if batch size is more than the minimum)
			*/
			$toDoAdd = OpenContext_NewDocs::regItemForIndexing($itemObj->itemUUID, "spatial", $itemObj->createdTime, $db);
			$SolrDocsIndexer = new SolrDocsIndex;
			$SolrDocsIndexer->checkRunIndex();
			$itemObj->errors = $SolrDocsIndexer->errors;
		}
		else{
			$errors[] = "Update Fail"; 
			$itemObj->errors = $errors;
			OpenContext_NewDocs::regImportError($itemObj->itemUUID, "spatial", $db); //note the error
		}
		
		return $itemObj;
		
	}//end function
	
	public static function mediaAdd($itemXML_string, $versionUpdate = false){
		
		$db_params = OpenContext_OCConfig::get_db_config();
		$db = new Zend_Db_Adapter_Pdo_Mysql($db_params);
		$db->getConnection();
		
		$itemObj = new Media; //start media resource class
		$itemObj->archaeoML = $itemXML_string;
		$itemObj->DOM_spatialAtomCreate($itemXML_string);
		
		$itemXML = simplexml_load_string($itemXML_string);
		$itemXML->registerXPathNamespace("arch", OpenContext_OCConfig::get_namespace("arch", "media"));
		$itemXML->registerXPathNamespace("oc", OpenContext_OCConfig::get_namespace("oc", "media"));
		
		$itemObj = OpenContext_XMLtoItems::XMLmediaBasic($itemObj, $itemXML);
		$updateInsertSuccess = $itemObj->createUpdate($versionUpdate);
		
		if($updateInsertSuccess){
			//insert links, properties, children items. modify parent if link to child is not found
			$OKlinks = OpenContext_XMLtoItems::linksRetrieve($itemObj->projectUUID, $itemObj->sourceID, $itemObj->itemUUID, "media", $itemXML, $db);
			
			//Turn this on if adding properties via XML docs, works faster if you're not
			$OKprops = OpenContext_XMLtoItems::obs_props_Retrieve($itemObj->projectUUID, $itemObj->sourceID, $itemObj->itemUUID, "media", $itemXML, $db);
			
			/*
			 Now do the solr indexing (if batch size is more than the minimum)
			*/
			$toDoAdd = OpenContext_NewDocs::regItemForIndexing($itemObj->itemUUID, "media", $itemObj->createdTime, $db);
			$SolrDocsIndexer = new SolrDocsIndex;
			$SolrDocsIndexer->checkRunIndex();
		}
		else{
			OpenContext_NewDocs::regImportError($itemObj->itemUUID, "media", $db); //note the error
		}
		
		return $itemObj;
		
	}//end function
	
	
	/*
	Add new diary document items
	*/
	public static function documentAdd($itemXML_string, $versionUpdate = false){
		
		$db_params = OpenContext_OCConfig::get_db_config();
		$db = new Zend_Db_Adapter_Pdo_Mysql($db_params);
		$db->getConnection();
		
		$itemObj = new Document; //start media resource class
		$itemObj->archaeoML = $itemXML_string;
		$itemObj->DOM_spatialAtomCreate($itemXML_string);
		
		$itemXML = simplexml_load_string($itemXML_string);
		$itemXML->registerXPathNamespace("arch", OpenContext_OCConfig::get_namespace("arch", "media"));
		$itemXML->registerXPathNamespace("oc", OpenContext_OCConfig::get_namespace("oc", "media"));
		
		$itemObj = OpenContext_XMLtoItems::XMLmediaBasic($itemObj, $itemXML);
		$updateInsertSuccess = $itemObj->createUpdate($versionUpdate);
		if($updateInsertSuccess){
			//insert links, properties, children items. modify parent if link to child is not found
			$OKlinks = OpenContext_XMLtoItems::linksRetrieve($itemObj->projectUUID, $itemObj->sourceID, $itemObj->itemUUID, "document", $itemXML, $db);
			$OKprops = OpenContext_XMLtoItems::obs_props_Retrieve($itemObj->projectUUID, $itemObj->sourceID, $itemObj->itemUUID, "document", $itemXML, $db);
			
			/*
			 Now do the solr indexing (if batch size is more than the minimum)
			*/
			$toDoAdd = OpenContext_NewDocs::regItemForIndexing($itemObj->itemUUID, "document", $itemObj->createdTime, $db);
			$SolrDocsIndexer = new SolrDocsIndex;
			$SolrDocsIndexer->checkRunIndex();
		}
		else{
			OpenContext_NewDocs::regImportError($itemObj->itemUUID, "document", $db); //note the error
		}
		
		return $itemObj;
		
	}//end function
	
	
	/*
	Add new project items
	*/
	public static function projectAdd($itemXML_string, $versionUpdate = false){
		
		$db_params = OpenContext_OCConfig::get_db_config();
		$db = new Zend_Db_Adapter_Pdo_Mysql($db_params);
		$db->getConnection();
		
		$itemObj = new Project; //start project class
		$itemObj->archaeoML = $itemXML_string;
		$itemObj->DOM_AtomCreate($itemXML_string);
		
		
		$itemXML = simplexml_load_string($itemXML_string);
		$itemXML->registerXPathNamespace("arch", OpenContext_OCConfig::get_namespace("arch", "project"));
		$itemXML->registerXPathNamespace("oc", OpenContext_OCConfig::get_namespace("oc", "project"));
		$itemXML->registerXPathNamespace("dc", OpenContext_OCConfig::get_namespace("dc"));
		
		$itemObj = OpenContext_XMLtoItems::XMLprojectBasic($itemObj, $itemXML);
		$itemObj->fullAtomCreate($itemXML_string); //requires a string, makes the full Atom representation needed for the project feed
		
		$updateInsertSuccess = $itemObj->createUpdate($versionUpdate);
		
		if($updateInsertSuccess){
			//insert links, properties, children items. modify parent if link to child is not found
			$OKlinks = OpenContext_XMLtoItems::linksRetrieve($itemObj->projectUUID, $itemObj->sourceID, $itemObj->itemUUID, "project", $itemXML, $db);
			$OKprops = OpenContext_XMLtoItems::obs_props_Retrieve($itemObj->projectUUID, $itemObj->sourceID, $itemObj->itemUUID, "project", $itemXML, $db);
			$OKmeta = OpenContext_XMLtoItems::metadata_project($itemObj->projectUUID, $itemXML, $db);
			
			// Now do the solr indexing (if batch size is more than the minimum)
			
			$toDoAdd = OpenContext_NewDocs::regItemForIndexing($itemObj->itemUUID, "project", $itemObj->createdTime, $db);
			$SolrDocsIndexer = new SolrDocsIndex;
			$SolrDocsIndexer->checkRunIndex();
		}
		else{
			OpenContext_NewDocs::regImportError($itemObj->itemUUID, "project", $db); //note the error
		}
		
		
		return $itemObj;
		
	}//end function
	
	
	
	/*
	Add new person items
	*/
	public static function personAdd($itemXML_string, $versionUpdate = false){
		
		$db_params = OpenContext_OCConfig::get_db_config();
		$db = new Zend_Db_Adapter_Pdo_Mysql($db_params);
		$db->getConnection();
		
		$itemObj = new Person; //start person class
		$itemXML_string = $itemObj->namespace_fix($itemXML_string);
		$itemObj->archaeoML = $itemXML_string;
		
		$itemXML = simplexml_load_string($itemXML_string);
		$itemXML->registerXPathNamespace("arch", OpenContext_OCConfig::get_namespace("arch", "person"));
		$itemXML->registerXPathNamespace("oc", OpenContext_OCConfig::get_namespace("oc", "person"));
		$itemXML->registerXPathNamespace("dc", OpenContext_OCConfig::get_namespace("dc"));
		
		$itemObj = OpenContext_XMLtoItems::XMLpersonBasic($itemObj, $itemXML);
		$itemObj->fullAtomCreate($itemXML_string); //requires a string, makes the full Atom representation needed for the person feed
		$updateInsertSuccess = $itemObj->createUpdate($versionUpdate);
		if($updateInsertSuccess){
			//insert links, properties, children items. modify parent if link to child is not found
			$OKlinks = OpenContext_XMLtoItems::linksRetrieve($itemObj->projectUUID, $itemObj->sourceID, $itemObj->itemUUID, "person", $itemXML, $db);
			$OKprops = OpenContext_XMLtoItems::obs_props_Retrieve($itemObj->projectUUID, $itemObj->sourceID, $itemObj->itemUUID, "person", $itemXML, $db);
			/*
			 Now do the solr indexing (if batch size is more than the minimum)
			*/
			$toDoAdd = OpenContext_NewDocs::regItemForIndexing($itemObj->itemUUID, "person", $itemObj->createdTime, $db);
			$SolrDocsIndexer = new SolrDocsIndex;
			$SolrDocsIndexer->checkRunIndex();
		}
		else{
			OpenContext_NewDocs::regImportError($itemObj->itemUUID, "person", $db); //note the error
		}
		
		//return $SolrDocsIndexer;
		return $itemObj;
		
	}//end function
	
	
	
	/*
	Add new person items
	*/
	public static function propertyAdd($itemXML_string, $versionUpdate = false){
		
		$db_params = OpenContext_OCConfig::get_db_config();
		$db = new Zend_Db_Adapter_Pdo_Mysql($db_params);
		$db->getConnection();
		
		$itemObj = new Property; //start property class
		$itemObj->archaeoML = $itemXML_string;
		
		$itemXML = simplexml_load_string($itemXML_string);
		$itemXML->registerXPathNamespace("arch", OpenContext_OCConfig::get_namespace("arch", "property"));
		$itemXML->registerXPathNamespace("oc", OpenContext_OCConfig::get_namespace("oc", "property"));
		$itemXML->registerXPathNamespace("dc", OpenContext_OCConfig::get_namespace("dc"));
		
		$itemObj = OpenContext_XMLtoItems::XMLpropertyBasic($itemObj, $itemXML);
		$itemObj->fullAtomCreate($itemXML_string); //requires a string, makes the full Atom representation needed for the person feed
		$updateInsertSuccess = $itemObj->createUpdate($versionUpdate);
		if($updateInsertSuccess){
			//echo print_r($itemObj);
			//insert links, properties, children items. modify parent if link to child is not found
			$OKlinks = OpenContext_XMLtoItems::linksRetrieve($itemObj->projectUUID, $itemObj->sourceID, $itemObj->itemUUID, "property", $itemXML, $db);
			$OKprops = OpenContext_XMLtoItems::obs_props_Retrieve($itemObj->projectUUID, $itemObj->sourceID, $itemObj->itemUUID, "property", $itemXML, $db);
		}
		else{
			OpenContext_NewDocs::regImportError($itemObj->itemUUID, "property", $db); //note the error
		}
		
		return $itemObj;
		
	}//end function
	
	
	
	
	
	
	/*
	Once an item has successfully been added and processed in to the MySQL database, it's ready
	for adding to a "to do" list for indexing with Solr.
	*/
	public static function regItemForIndexing($itemUUID, $itemType, $pubDate, $db = false){
		
		$outcome = false;
		if(!$db){
			$db_params = OpenContext_OCConfig::get_db_config();
			$db = new Zend_Db_Adapter_Pdo_Mysql($db_params);
			$db->getConnection();
		}
		
		$itemTypeURIs = array("spatial" => "/subjects/",
				      "media" => "/media/",
				      "project" => "/projects/",
				      "document" => "/documents/",
				      "person" => "/persons/"
				      );
		if(array_key_exists($itemType, $itemTypeURIs)){
			
			$host = OpenContext_OCConfig::get_host_config();
			
			//$host = "http://opencontext.org";
			
			$itemURI = $host.$itemTypeURIs[$itemType].$itemUUID;
			$data = array("itemType" => $itemType,
				      "itemUUID" => $itemUUID,
				      "itemURI" => $itemURI,
				      "ItemCreated" => $pubDate,
				      "ItemUpdated" => date("Y-m-d\TH:i:s\Z"),
				      "solr_indexed" => 0
				      );
		
			try{
				$db->insert('noid_bindings', $data);
				$outcome = true;
			}
			catch (Exception $e) {
				//echo $e;
				$where = array();
				$where[] = "itemUUID = '$itemUUID' ";
				$data = array(	"ItemUpdated" => date("Y-m-d\TH:i:s\Z"),
						"solr_indexed" => 0);
				
				$db->update('noid_bindings', $data, $where);
			}
		}
		
		return $outcome;
	
	}//end function
	
	
	
	public static function regImportError($itemUUID, $itemType, $db = false){
		
		if(!$db){
			$db_params = OpenContext_OCConfig::get_db_config();
			$db = new Zend_Db_Adapter_Pdo_Mysql($db_params);
			$db->getConnection();
		}
		$data = array("uuid" => $itemUUID, "type" => $itemType);
		
		try{
			$db->insert('importer_error', $data);	
		}
		catch (Exception $e) {
					
		}
                
	}//end function
	
	
	
	public static function generateUUID()    {
      $rawid = strtoupper(md5(uniqid(rand(), true)));
		$workid = $rawid;
		$byte = hexdec( substr($workid,12,2) );
		$byte = $byte & hexdec("0f");
		$byte = $byte | hexdec("40");
		$workid = substr_replace($workid, strtoupper(dechex($byte)), 12, 2);
		 
		// build a human readable version
		$rid = substr($rawid, 0, 8).'-'
			  .substr($rawid, 8, 4).'-'
			  .substr($rawid,12, 4).'-'
			  .substr($rawid,16, 4).'-'
			  .substr($rawid,20,12);
					
					
		// build a human readable version
		$wid = substr($workid, 0, 8).'-'
			  .substr($workid, 8, 4).'-'
			  .substr($workid,12, 4).'-'
			  .substr($workid,16, 4).'-'
			  .substr($workid,20,12);
				
		return $wid;   
   }//end function
	
	
}//end class declaration

?>
