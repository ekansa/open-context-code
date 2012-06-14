<?php

class OpenContext_RDFannotate {
		
	public static function findExternalEntity($uuid, $itemType = false){
		
		$entityData = false;
		$db_params = OpenContext_OCConfig::get_db_config();
		$db = new Zend_Db_Adapter_Pdo_Mysql($db_params);
		$db->getConnection();
			
		if(!$itemType){
			$sql = 'SELECT external_entities.entityURI,
			external_entities.entity_store
			FROM external_entities
			WHERE external_entities.itemUUID = "'.$uuid.'" 
			LIMIT 1
			';
		}
		else{
			$sql = 'SELECT external_entities.entityURI,
			external_entities.entity_store
			FROM external_entities
			WHERE external_entities.itemUUID = "'.$uuid.'"
			AND external_entities.item_type = "'.$itemType.'"
			LIMIT 1
			';
		}
		
		//echo $sql;
		
		$result = $db->fetchAll($sql, 2);
		if($result){
			$entityData = array("uri"=>$result[0]["entityURI"],
					    "store"=>$result[0]["entity_store"]);
		}
		
		$db->closeConnection();
		return $entityData;
		
	}//end function
	
	
	public static function spaceEntitiesCheck($spatialItem, $nameSpaceArray){
		
		foreach($nameSpaceArray as $prefix => $uri){
			@$spatialItem->registerXPathNamespace($prefix, $uri);
		}
		
		foreach ($spatialItem->xpath("//arch:spatialUnit/@UUID") as $spUUID_xml){
			$spaceUUID = $spUUID_xml."";
		}
		
		$entityData = OpenContext_RDFannotate::findExternalEntity($spaceUUID, "space");
		
		if($entityData != false){
			//get the view count from the XML doc
			foreach ($spatialItem->xpath("//oc:metadata") as $metadataElement){
				$entityXML = $metadataElement->addChild("oc:externalEntity", "");
				$entityXML->addAttribute("href", $entityData["uri"]);
				$entityXML->addAttribute("store", $entityData["store"]);
			}
		}
		
		return $spatialItem;
	}
	
	
	
	
}//end class declaration

?>
