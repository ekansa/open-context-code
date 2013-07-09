<?php

class OpenContext_DeleteDocs {
	
	
	const fullContextDelimiter = "|xx|"; // deliminator for context paths
	const SolrAddSize = 25; //number of solr docs to add before processing the cached solr documents
	const cacheLife = 72000; //lifetime fo the solr cache
	const SpaceSolrCacheID = "AddSolrSpace";
	const MediaSolrCacheID = "AddSolrMedia";
	
	
	
	//recursive function to find all children of an item to be deleted
	public static function getChildren($id, $childrenIDs){
	
		$db_params = OpenContext_OCConfig::get_db_config();
		$db = new Zend_Db_Adapter_Pdo_Mysql($db_params);
				       
		$db->getConnection();
		$sql = "SELECT space_contain.child_uuid
		FROM space_contain
		WHERE space_contain.parent_uuid = '$id' ";
			
		$result = $db->fetchAll($sql, 2);
		if($result){
		    foreach($result as $row){
			$childrenIDs = OpenContext_DeleteDocs::getChildren($row["child_uuid"], $childrenIDs);
			if(!in_array($row["child_uuid"], $childrenIDs)){
				$childrenIDs[] = $row["child_uuid"];
			}
		    }
		    unset($result);
		}
	
		$db->closeConnection();
		return $childrenIDs;
	}
	
	
	
	
	
	//read a spatialUnit (location / object) document and insert into the database
	public static function spaceDeleteDB($id, $reasonID = 1){
		
		$output = false;
		$db_params = OpenContext_OCConfig::get_db_config();
		$db = new Zend_Db_Adapter_Pdo_Mysql($db_params);
				       
		$db->getConnection();
		$sql = "SELECT space.space_label, space.project_id, space.source_id, space.view_count, space.space_archaeoml
		FROM space
		WHERE space.uuid = '$id'
		LIMIT 0,1";
			
		$result = $db->fetchAll($sql, 2);
		if($result){
			$itemLabel = $result[0]["space_label"]; 
			$itemProjID = $result[0]["project_id"];
			$itemSourceID = $result[0]["source_id"];
			$itemViews = $result[0]["view_count"];
			$itemXML = $result[0]["space_archaeoml"];
			
			$data = array("itemUUID" => $id,
				      "itemType" => "space",
				      "project_id" => $itemProjID,
				      "source_id" => $itemSourceID,
				      "item_label" => $itemLabel,
				      "view_count" => $itemViews,
				      "itemXML" => $itemXML,
				      "reason_id" => $reasonID
				      );
			
			try{
				$db->insert('deleted', $data);
				$output = true;
			}
			catch (Exception $e) {
				$output = false;
			}
				
		}
		
		if($output){
			$db->delete("space", "uuid = '".$id."'");
			$db->delete("observe", "subject_uuid = '".$id."'");
			$db->delete("links", "targ_uuid = '".$id."'");
			$db->delete("links", "origin_uuid = '".$id."'");
			$db->delete("space_contain", "parent_uuid = '".$id."'");
			$db->delete("space_contain", "child_uuid = '".$id."'");
		}
		
		
		$db->closeConnection();
		
		return $output;
	}
	
	
	
	
	//read a media resource document and insert into the database
	public static function mediaDeleteDB($id, $reasonID = 1){
		
		$output = false;
		$db_params = OpenContext_OCConfig::get_db_config();
		$db = new Zend_Db_Adapter_Pdo_Mysql($db_params);
				       
		$db->getConnection();
		$sql = "SELECT resource.res_label,
		resource.project_id,
		resource.source_id,
		
		resource.archaeoML
		FROM resource
		WHERE resource.uuid = '$id'
		LIMIT 0,1";
			
		$result = $db->fetchAll($sql, 2);
		if($result){
			$itemLabel = $result[0]["res_label"]; 
			$itemProjID = $result[0]["project_id"];
			$itemSourceID = $result[0]["source_id"];
			$itemXML = $result[0]["archaeoML"];
			
			$data = array("itemUUID" => $id,
				      "itemType" => "media",
				      "project_id" => $itemProjID,
				      "source_id" => $itemSourceID,
				      "item_label" => $itemLabel,
				      "itemXML" => $itemXML,
				      "reason_id" => $reasonID
				      );
			
			try{
				$db->insert('deleted', $data);
				$output = true;
			}
			catch (Exception $e) {
				$output = false;
			}
				
		}
		
		if($output){
			$db->delete("resource", "uuid = '".$id."'");
			$db->delete("observe", "subject_uuid = '".$id."'");
			$db->delete("links", "targ_uuid = '".$id."'");
			$db->delete("links", "origin_uuid = '".$id."'");
		}
		
		
		$db->closeConnection();
		
		return $output;
	}
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	//find ids of parent items, so their xml can be edited later
	public static function findParents($childIDs){
		
		$db_params = OpenContext_OCConfig::get_db_config();
		$db = new Zend_Db_Adapter_Pdo_Mysql($db_params);
				       
		$db->getConnection();
		
		$whereData = "";
		if(is_array($childIDs)){
			$i=0;
			foreach($childIDs as $childID){
				if($i==0){
					$whereData = "space_contain.child_uuid = '".$childID."'";
				}
				else{
					$whereData .= " OR space_contain.child_uuid = '".$childID."'";
				}
				$i++;
			}
		}
		else{
			$whereData = "child_uuid = '".$childIDs."'";
		}
		$whereData = "(".$whereData.")";
		
		$sql = "SELECT DISTINCT space_contain.parent_uuid
		FROM space_contain
		WHERE ".$whereData;
		
		$parentIDs = array();
		$result = $db->fetchAll($sql, 2);
		if($result){
			foreach($result as $row){
				$parentIDs[] = $row["parent_uuid"];
			}
		}
		else{
			$parentIDs = false;
		}
		
		$db->closeConnection();
		return $parentIDs;
	}
	
	
	
	
	//this function updates a previously created parent item of a new spatialUnit to add child relationship
	public static function childNodeRemove($childID, $parentXML){
		
		@$xmlParent = simplexml_load_string($parentXML);
			
		if($xmlParent){
			
			unset($xmlParent);
				
			//$parentXML = str_replace("http://www.opencontext.org/database/schema/space_schema_v1.xsd", OpenContext_OCConfig::get_namespace("oc", "spatial"), $parentXML);
			$parent_dom = new DOMDocument("1.0", "utf-8");
			$parent_dom->formatOutput = true;
			$parent_dom->loadXML($parentXML);
			$xpath = new DOMXpath($parent_dom);
			$xpath->registerNamespace("arch", OpenContext_OCConfig::get_namespace("arch", "spatial"));
			$xpath->registerNamespace("oc", OpenContext_OCConfig::get_namespace("oc", "spatial"));
			$xpath->registerNamespace("dc", OpenContext_OCConfig::get_namespace("dc"));
			//$xpath->registerNamespace("atom", OpenContext_OCConfig::get_namespace("atom"));
			
			//get parent name
			$query = "//arch:spatialUnit/arch:name/arch:string";
			$result_name = $xpath->query($query, $parent_dom);
			$parent_name = $result_name->item(0)->nodeValue;
			
			//first check to see if the specific child exists
			$childExisting = false;
			$badChild = false;
			$query = "//oc:children/oc:tree/oc:child[oc:id = '".$childID."']";
			$badChild = $xpath->query($query, $parent_dom);
			if (!is_null($badChild->item(0))) {
				//echo " found!";
				OpenContext_DeleteDocs::deleteNode($badChild->item(0));
			}
			$parentXML = $parent_dom->saveXML(); 
		}
		else{
			$parentXML = false;
		}
		return $parentXML;
	}//end function
	
	
	public static function deleteNode($node) {
		OpenContext_DeleteDocs::deleteChildren($node);
		$parent = $node->parentNode;
		$oldnode = $parent->removeChild($node);
	}

	public static function deleteChildren($node) {
		while (isset($node->firstChild)) {
			OpenContext_DeleteDocs::deleteChildren($node->firstChild);
			$node->removeChild($node->firstChild);
		}
	} 
	
	
	//store an XML document before update
	public static function saveBeforeUpdate($itemUUID, $itemType, $xmlString){
		$db_params = OpenContext_OCConfig::get_db_config();
		$db = new Zend_Db_Adapter_Pdo_Mysql($db_params);
				       
		$db->getConnection();
		
		//check for version number to be saved
		$sql = "SELECT updated.version
		FROM updated
		WHERE updated.itemUUID = '$itemUUID'
		ORDER BY updated.version DESC
		LIMIT 0,1";
		
		$result = $db->fetchAll($sql, 2);
		if($result){
			$currentVersion = $result[0]["version"] + 1; // current version is last version +1 	
		}
		else{
			$currentVersion = 1;
		}
		
		//value is small enough to save in single field
		$versionID = sha1($xmlString); 
		
		//check if the update does NOT need to be treated as a big string
		if(!OpenContext_OCConfig::need_bigString($xmlString)){ 
			$data = array("id_version" => $versionID,
				      "itemUUID" => $itemUUID,
				      "itemType" => $itemType,
				      "itemXML" => $xmlString,
				      "version" => $currentVersion
				      );
			
			try{
				$db->insert('updated', $data);
				$output = true;
			}
			catch (Exception $e) {
				$output = false;
			}
		}
		else{
			//too big to save in one place, need to break and save elsewhere
			$bigStringObj = new BigString;
			$bigStringObj->saveVersionBigString($itemUUID, $versionID, $currentVersion, $itemType, "archaeoML", $xmlString, $db);
			$output = true;
			if($output){
				//save note that a big value was saved
				$data = array("id_version" => $versionID,
				      "itemUUID" => $itemUUID,
				      "itemType" => $itemType,
				      "itemXML" => OpenContext_OCConfig::get_bigStringValue(),
				      "version" => $currentVersion
				      );
			
				try{
					$db->insert('updated', $data);
					$output = true;
				}
				catch (Exception $e) {
					$output = false;
				}
			}
		}
		
		
		$db->closeConnection();
		
		return $output;
	}
	
	
	public static function solrDocCache($atomSpaceString, $itemType, $atomMediaString = false){
		
		$frontendOptions = array('lifetime' => self::cacheLife,'automatic_serialization' => true );
		$backendOptions = array('cache_dir' => './cache/' );
            
		//$solrDoc = "smurf";
		//echo var_dump($solrDoc);
	    
		$cache = Zend_Cache::factory('Core','File',$frontendOptions,$backendOptions);
		
		if($itemType == "space"){
			$cache_id = self::SpaceSolrCacheID;
			$cache_item = $atomSpaceString;
		}
		else{
			$cache_id = self::MediaSolrCacheID;
			$cache_item = array("space"=> $atomSpaceString, "media"=>$atomMediaString);
		}
		
		if(!$cache_result = $cache->load($cache_id)) {
			$SolrDocArray = array();
			$SolrDocArray[0] = $cache_item;
			//$JSONstring = Zend_Json::encode($SolrDocArray);
			//$cache->save($JSONstring, $cache_id); //save intial results to the cache
			$cache->save($SolrDocArray, $cache_id); //save intial results to the cache
			$solrDocCount = 1;
		}
		else{
			$SolrDocArray = $cache_result;
			$SolrDocArray[] = $cache_item;
			//$JSONstring = Zend_Json::encode($SolrDocArray);
			//$cache->save($JSONstring, $cache_id); //save intial results to the cache
			$cache->save($SolrDocArray, $cache_id); //save intial results to the cache
			$solrDocCount = count($SolrDocArray);
		}
		
		return $solrDocCount;
		
	}//end function
	
	
	
	public static function solrDocCacheAdd($itemType){
		
		$frontendOptions = array('lifetime' => self::cacheLife,'automatic_serialization' => true );
		$backendOptions = array('cache_dir' => './cache/' );
            
		$cache = Zend_Cache::factory('Core','File',$frontendOptions,$backendOptions);
		
		if($itemType == "space"){
			$cache_id = self::SpaceSolrCacheID;
		}
		else{
			$cache_id = self::MediaSolrCacheID;
		}
		
		$output["indexOK"] = true;
		
		if(!$cache_result = $cache->load($cache_id)) {
			$output["indexOK"] = false;
			$output["note"] = "cache error, solr documents gone!";
		}
		else{
			
			//get the array of Atom XML strings from the cach
			$SolrAtomArray = $cache_result;
			$SolrDocArray = array();
			$docCount = 0;
			
			if($itemType == "space"){
				foreach($SolrAtomArray as $atom_string){
					$atomXML = simplexml_load_string($atom_string); 
					$solrDocument = OpenContext_SolrIndexer::reindex_item($atomXML); //make a Solr Document
					if(!$solrDocument->geo_lat){
						OpenContext_NewDocs::regImportError($solrDocument->uuid, $itemType);
					}
					else{
						//echo $solrDocument->geo_lat." ".$solrDocument->geo_lon;
						$SolrDocArray[] = $solrDocument;
					}
					$docCount++;
				}
			}
			else{
				//media items. these need an resource atom string and a related spatial atom string
				foreach($SolrAtomArray as $act_cache){
					$space_atom_string = $act_cache["space"];
					$res_atom_string = $act_cache["media"];
					@$space_atomXML = simplexml_load_string($space_atom_string);
					@$res_atomXML = simplexml_load_string($res_atom_string);
					$solrDocument = OpenContext_SolrIndexer::reindex_resource($res_atomXML, $space_atomXML);
					
					if(!$solrDocument->geo_lat){
						OpenContext_NewDocs::regImportError($solrDocument->uuid, $itemType);
					}
					else{
						//echo $solrDocument->geo_lat." ".$solrDocument->geo_lon;
						$SolrDocArray[] = $solrDocument;
					}
					
					$docCount++;
				}
			}
			
			$SolrCacheReset = true;
			
			$solr = new Apache_Solr_Service('localhost', 8983, '/solr');
			// test the connection to the solr server
			if ($solr->ping()) { // if we can ping the solr server...
				//echo "connected to the solr server...<br/><br/>";
				try{
					//$updateResponse = new Apache_Solr_Response();
					$updateResponse = $solr->addDocuments($SolrDocArray);
					$solr->commit();
					//$solr->optimize();
				}
				catch (Exception $e) {
				    $output["indexOK"] = false;
				    $output["note"]  = $e->getMessage();;
				    $SolrCacheReset = false;
				    $updateResponse = false;
				}
				
			//end case where solr document
			} else {
				$output["indexOK"] = false;
				$output["note"] = "Solr down";
				$SolrCacheReset = false;
			}// end case with a bad ping
			
			
			if($SolrCacheReset){
				$output["note"] = $docCount." indexed";
				$output["note"] .= $updateResponse->getRawResponse();
				//$output["note"] .= var_dump($updateResponse);
				$cache->remove($cache_id); // now remove the cached documents
			}
			
			unset($solrDocArray);
			unset($solr);
			unset($SolrAtomArray);
			unset($cache_result);
			
		}
		
		return $output;
		
	}//end function
	
	
	
	
	
	
}//end class declaration

?>
