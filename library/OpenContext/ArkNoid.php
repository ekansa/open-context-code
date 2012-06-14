<?php

class OpenContext_ArkNoid {
		
	public static function noidItem($noid){
		
		$NoidDoc = false;
		$db_params = OpenContext_OCConfig::get_db_config();
		$db = new Zend_Db_Adapter_Pdo_Mysql($db_params);
		$db->getConnection();
			
			
		$sql = 'SELECT "media" as docType, resource.archaeoML AS xml,
		resource.uuid AS item_uuid
		FROM resource
		WHERE resource.noid = "'.$noid.'" 
		LIMIT 1
		UNION
		SELECT "space" as docType, space.archaeoML AS xml,
		space.uuid AS item_uuid
		FROM space
		WHERE space.noid = "'.$noid.'" 
		LIMIT 1
		UNION
		SELECT "project" as docType, projects.proj_atom  AS xml,
		projects.project_id AS item_uuid
		FROM projects
		WHERE projects.noid = "'.$noid.'" 
		LIMIT 1
		';
		
		//echo $sql;
		
		$result = $db->fetchAll($sql, 2);
		if($result){
			$itemType = $result[0]["docType"];
			$itemXML = $result[0]["xml"];
			$itemUUID = $result[0]["item_uuid"];
			
			if($itemType == "media" && strlen($itemXML)<3){
				$mediaItem = New Media;
				$mediaItem->getByID($itemUUID);
				$mediaItem->archaeoML_update($mediaItem->archaeoML);
				$fullAtom = $mediaItem->DOM_spatialAtomCreate($mediaItem->newArchaeoML);
				$mediaItem->update_atom_entry();
				$itemXML = $mediaItem->newArchaeoML;
			}
			elseif($itemType == "space" && strlen($itemXML)<3){
				$spaceItem = New Subject;
				$spaceItem->getByID($itemUUID);
				if(strlen($spaceItem->newArchaeoML)<10){
					$spaceItem->solr_getArchaeoML();
					$spaceItem->archaeoML_update($spaceItem->archaeoML);
				}
				$fullAtom = $spaceItem->DOM_spatialAtomCreate($spaceItem->newArchaeoML);
				$spaceItem->kml_in_Atom = true; // it doesn't validate, but it is really useful
				$spaceItem->update_atom_entry();
				$itemXML = $spaceItem->newArchaeoML;
			}
			
			
			$NoidDoc = array("docType" => $itemType,
					 "uuid" => $itemUUID,
					 "xml" => $itemXML);
		}
		
		$db->closeConnection();
		return $NoidDoc;
		
	}//end function
	
	
	public static function mintNoid($itemType, $itemUUID){
		
		$noid = false;
		$noidCheck = OpenContext_ArkNoid::noidCheck($itemType, $itemUUID);
		
		if(!$noidCheck){
			//if noidCheck is false, mint a NOID
			$minterURI = OpenContext_OCConfig::get_ArkMinterURI();
			$rawNewNoid = file_get_contents($minterURI."?mint+1");
			$noid = OpenContext_ArkNoid::parse_newNoid($rawNewNoid);
			OpenContext_ArkNoid::saveNoid($itemType, $itemUUID, $noid);
			
			//once the noid is saved to the DB, bind the URI of the item with the NOID 
			
		}
		
		return $noid;
	}
	
	
	//check to see if the item already has a noid
	public static function noidCheck($itemType, $itemUUID){
		$db_params = OpenContext_OCConfig::get_db_config();
		$db = new Zend_Db_Adapter_Pdo_Mysql($db_params);
		$db->getConnection();
		
		$noid = "missing ID"; // default to ID not valid
		
		if($itemType == "media"){
			$sql = "SELECT resource.noid
			FROM resource
			WHERE resource.uuid = '".$itemUUID."'
			LIMIT 1
			";	
		}
		elseif($itemType == "project"){
			$sql = "SELECT projects.noid
			FROM projects
			WHERE projects.project_id = '".$itemUUID."'
			LIMIT 1
			";
		}
		else{
			$sql = "SELECT space.noid
			FROM space
			WHERE space.uuid = '".$itemUUID."'
			LIMIT 1
			";
		}
		
		$result = $db->fetchAll($sql, 2);
		if($result){
			$noid = $result[0]["noid"];
			if(strlen($noid)<1){
				$noid = false; //null result, return false
			}
		}
		
		$db->closeConnection();
		return $noid;
	}
	
	
	
	//parse results of noid generation
	public static function parse_newNoid($rawNewNoid){
		if(substr_count($rawNewNoid, "id:")>0){
			$noid = trim(str_replace("id:", "", $rawNewNoid));
		}
		else{
			$noid = false;
		}
		return $noid;
	}
	
	
	
	//updates the database to save the NOID data
	public static function saveNoid($itemType, $itemUUID, $noid){
		
		$n = false;
		
		$db_params = OpenContext_OCConfig::get_db_config();
		$db = new Zend_Db_Adapter_Pdo_Mysql($db_params);
		$db->getConnection();
		
		if($itemType == "media"){
			$where = "uuid = '".$itemUUID."'";
			$table = "resource";
		}
		elseif($itemType == "project"){
			$where = "project_id = '".$itemUUID."'";
			$table = "projects";
		}
		else{
			$where = "uuid = '".$itemUUID."'";
			$table = "space";
		}
		
		
		$data = array('noid' => $noid); 
		$n = $db->update($table, $data, $where);
		
		
		$db->closeConnection();
		
		return $n;
	}
	
	
	public static function getProjectSlug($itemUUID){
		$db_params = OpenContext_OCConfig::get_db_config();
		$db = new Zend_Db_Adapter_Pdo_Mysql($db_params);
		$db->getConnection();
		
		$slug = false;
		$sql = "SELECT projects.slug, projects.proj_name
			FROM projects
			WHERE projects.project_id = '".$itemUUID."'
			LIMIT 1
			";
		
		
		$result = $db->fetchAll($sql, 2);
		if($result){
			$slug = $result[0]["slug"];
			
			if(strlen($slug)<2){
				//$slug = $result[0]["proj_name"];
				$slug = false;
			}
		}
		
		$db->closeConnection();
		return urlencode(strtolower($slug));
	}
	
	
	//updates the database to save the NOID data
	public static function bindNoid($itemType, $itemUUID, $noid){
		
		$output = false;
		
		if($noid != false){
			$URIalt = "http://opencontext.org/ref/ark:/".$noid;
			$slug = false;
			if($itemType == "media"){
				$URImain = "http://opencontext.org/media/".$itemUUID;
			}
			elseif($itemType == "project"){
				$URImain = "http://opencontext.org/projects/".$itemUUID;
				$slug = OpenContext_ArkNoid::getProjectSlug($itemUUID);
				if(!$slug){
					$URImain = "http://opencontext.org/projects/".$itemUUID;
				}
				else{
					$slug = "http://opencontext.org/projects/".$slug;
				}
				
			}
			else{
				$URImain = "http://opencontext.org/subjects/".$itemUUID;
			}
			
			$URIxml = $URImain.".xml";
			$URIatom = $URImain.".atom";
			
			$minterURI = OpenContext_OCConfig::get_ArkMinterURI();
			$fullMinterURI = $minterURI."?bind+set+".$noid."+locations+".$URImain."|".$URIalt."|".$URIxml."|".$URIatom;
			if($slug != false){
				$fullMinterURI .= "|".$slug;
			}
			
			$rawBindNoid = file_get_contents($fullMinterURI);
			
			
			
			
			if(OpenContext_ArkNoid::parse_bindNoid($rawBindNoid)){
				//if the NOID service says that the binding was successful, then add to database
				$output = OpenContext_ArkNoid::saveNoidBinding($itemUUID, $URImain, $noid);
				//output from saving the binding is true, if successful, or false if not.
			}
		}
		
		return $output;
		
	}
	
	//updates the database to save the NOID data
	public static function saveNoidBinding($itemUUID, $itemURI, $noid){
		
		$output = false;
		
		$db_params = OpenContext_OCConfig::get_db_config();
		$db = new Zend_Db_Adapter_Pdo_Mysql($db_params);
		$db->getConnection();
		
		$data = array('noid' => $noid,
			      'itemUUID' => $itemUUID,
			      'itemURI' => $itemURI); 
		
		try{
			$db->insert('noid_bindings', $data);
			$output = true;
		}
		catch (Exception $e) {
			$output = false;    
		}
		
		$db->closeConnection();
		
		return $output;
	}
	
	
	
	//parse results of noid binding
	public static function parse_bindNoid($rawBindNoid){
		if(substr_count($rawBindNoid, "Status:  ok")>0){
			$output = true;
		}
		else{
			$output = false;
		}
		return $output;
	}
	
	
	public static function updateXML_Noid($itemType, $itemUUID, $noid){
		$output = false;
		if($noid != false){
		
			$db_params = OpenContext_OCConfig::get_db_config();
			$db = new Zend_Db_Adapter_Pdo_Mysql($db_params);
			$db->getConnection();
			
			if($itemType == "media"){
				$sql = "SELECT resource.noid,
				resource.archaeoML AS itemXML,
				resource.atom_entry AS atom
				FROM resource
				WHERE resource.uuid = '".$itemUUID."'
				LIMIT 1
				";
				$xmlField = "archaeoML";
				$atomField = "atom_entry";
				$table = "resource";
				$where = "uuid = '".$itemUUID."'";
			}
			elseif($itemType == "project"){
				$sql = "SELECT projects.noid,
				projects.proj_archaeoml AS itemXML,
				projects.proj_atom AS atom
				FROM projects
				WHERE projects.project_id = '".$itemUUID."'
				LIMIT 1
				";
				$xmlField = "proj_archaeoml";
				$atomField = "proj_atom";
				$table = "projects";
				$where = "project_id = '".$itemUUID."'";
			}
			else{
				$sql = "SELECT space.noid,
				space.archaeoML AS itemXML,
				space.atom_entry AS atom
				FROM space
				WHERE space.uuid = '".$itemUUID."'
				LIMIT 1
				";
				$xmlField = "archaeoML";
				$atomField = "atom_entry";
				$table = "space";
				$where = "uuid = '".$itemUUID."'";
			}
			
			$result = $db->fetchAll($sql, 2);
			if($result){
				$noidDB = $result[0]["noid"];
				$itemXML = $result[0]["itemXML"];
				$itemAtom = $result[0]["atom"];
				//$newItemAtom = OpenContext_ArkNoid::XML_add_Noid($itemAtom, $itemType, $noid);
				if(strlen($itemXML)<2 && $itemType == "project"){
					//this gets the ArchaeoML xml from an Atom document
					$newItemXML = OpenContext_ArkNoid::XML_Atom_Extract($itemAtom, $itemType);
				}
				elseif(strlen($itemXML)<2 && $itemType == "space"){
					$spaceItem = New Subject;
					$spaceItem->getByID($itemUUID);
					if(strlen($spaceItem->newArchaeoML)<10){
						$spaceItem->solr_getArchaeoML();
						$spaceItem->archaeoML_update($spaceItem->archaeoML);
					}
					$fullAtom = $spaceItem->DOM_spatialAtomCreate($spaceItem->newArchaeoML);
					$spaceItem->kml_in_Atom = true; // it doesn't validate, but it is really useful
					$spaceItem->update_atom_entry();
					$itemXML = $spaceItem->newArchaeoML;
					//echo $itemUUID.":".$itemXML;
					$newItemXML = OpenContext_ArkNoid::XML_add_Noid($itemXML, $itemType, $noid);
				}
				elseif(strlen($itemXML)<2 && $itemType == "media"){
					$mediaItem = New Media;
					$mediaItem->getByID($itemUUID);
					$mediaItem->archaeoML_update($mediaItem->archaeoML);
					$fullAtom = $mediaItem->DOM_spatialAtomCreate($mediaItem->newArchaeoML);
					$mediaItem->update_atom_entry();
					$itemXML = $mediaItem->newArchaeoML;
					$newItemXML = OpenContext_ArkNoid::XML_add_Noid($itemXML, $itemType, $noid);
				}
				else{
					$newItemXML = OpenContext_ArkNoid::XML_add_Noid($itemXML, $itemType, $noid);
				}
				
				$data = array($xmlField => $newItemXML);
				
				//@$xmlAtom = simplexml_load_string($newItemAtom);
				
				$xmlAtom  = true;
				@$xmlArchaeoML = simplexml_load_string($newItemXML);
        
				if(!$xmlAtom || !$xmlArchaeoML){
					//problem with new XML, don't save
				}
				else{
					$n = $db->update($table, $data, $where);
				}
				
				$output = $newItemXML;	
			}
			
			$db->closeConnection();
			
		}
		
		return $output;
	}
	
	
	
	//get an ArchaeoML doc from an atom xml doc
	public static function XML_Atom_Extract($AtomString, $itemType){
		
		$output = false;
		$itemDom = new DOMDocument("1.0", "utf-8");
		$itemDom->loadXML($AtomString);
                $xpath = new DOMXpath($itemDom);
		
		// Register OpenContext's namespace
		$xpath->registerNamespace("arch", OpenContext_OCConfig::get_namespace("arch", $itemType));
		$xpath->registerNamespace("oc", OpenContext_OCConfig::get_namespace("oc", $itemType));
		$xpath->registerNamespace("dc", OpenContext_OCConfig::get_namespace("dc", $itemType));
		
		if($itemType == "media"){
			$query = "//arch:resource";
			$tagName = "resource";
		}
		elseif($itemType == "project"){
			$query = "//arch:project";
			$tagName = "project";
		}
		else{
			$query = "//arch:spatialUnit";
			$tagName = "spatialUnit";
		}
		
		$resultItem = $itemDom->getElementsByTagName($tagName);
		
		//$resultItem = $xpath->query($query, $itemDom);
		if($resultItem != null){
			$outputNode = $resultItem->item(0);
			$outputString = $itemDom->saveXML($outputNode);
			$archDom = new DOMDocument("1.0", "utf-8");
			$archDom->formatOutput = true;
			$archDom->loadXML($outputString);
			$output = $archDom->saveXML();
		}
		
		return $output;
	}
	
	
	
	public static function XML_add_Noid($xmlString, $itemType, $noid){
		
		$itemDom = new DOMDocument("1.0", "utf-8");
		$itemDom->loadXML($xmlString);
                $xpath = new DOMXpath($itemDom);
		
		// Register OpenContext's namespace
		$xpath->registerNamespace("arch", OpenContext_OCConfig::get_namespace("arch", $itemType));
		$xpath->registerNamespace("oc", OpenContext_OCConfig::get_namespace("oc", $itemType));
		$xpath->registerNamespace("dc", OpenContext_OCConfig::get_namespace("dc", $itemType));
		
		$query = "//oc:metadata";
		//$resultMeta = $xpath->query($query, $itemDom);
		
		$resultMeta = $itemDom->getElementsByTagName( "metadata" );
		$resultArchive = $itemDom->getElementsByTagName( "archive" );
		$archiveNode = null;
		if($resultArchive != false){
			$archiveNode = $resultArchive->item(0);
		}
		
                if(($resultMeta != null)&&($archiveNode == null)){
			
			$meta_node = $resultMeta->item(0);
			$archiveNode = $itemDom->createElement("oc:archive");
			//$archiveNode = $itemDom->createElementNS(OpenContext_OCConfig::get_namespace("oc", $itemType), "archive");
			$meta_node->appendChild($archiveNode);
			$archiveComment = $itemDom->createComment("This describes metadata relating to archival services provided by an institutional repository.");
			$archiveNode->appendChild($archiveComment);
			
			$archiveOrg = $itemDom->createElement("oc:repository");
			$archiveOrg->setAttribute("href", OpenContext_OCConfig::get_PrimaryArchiveURI());
			$archiveName = $itemDom->createElement("oc:name");
			$archiveName_val = $itemDom->createTextNode(OpenContext_OCConfig::get_PrimaryArchive());
			$archiveName->appendChild($archiveName_val);
			$archiveOrg->appendChild($archiveName);
			
			$arkNode = $itemDom->createElement("oc:ark_identifier");
			$arkNode->setAttribute("time_assigned", date("Y-m-d\TH:i:s\-07:00"));
			$arkNode_val  = $itemDom->createTextNode("ark:/".$noid);
			$arkNode->appendChild($arkNode_val);
			if($itemType == "media"){
				$idComment = $itemDom->createComment("The ark_identifier element contains an ARK (Archival Resource Key) provided by an institutional repository.
								     \nThe repository has taken on responsibility for maintaining the persistent identification and curation of this document and associated media files.");
			}
			else{
				$idComment = $itemDom->createComment("The ark_identifier element contains an ARK (Archival Resource Key) provided by an institutional repository.
								     \nThe repository has taken on responsibility for maintaining the persistent identification and curation of this document.");
			}
			$archiveOrg->appendChild($arkNode);
			$archiveOrg->appendChild($idComment);
			
			$archiveNode->appendChild($archiveOrg);
			
		}
		$itemDom->formatOutput = true;
		return $itemDom->saveXML();
	}
	
	
	
	
/*
 	
Hi Eric,

There are two minters you may be interested in.  The first is for testing
only, eg,

    noid.cdlib.org/nd/noidu_fake?mint+1000

mints 1000 "throw-away" identifiers.  Feel free to mint and bind to your
heart's content.  Documentation is always available at
https://confluence.ucop.edu/download/attachments/16744482/noid.pdf?version=1 .

The second minter is for real, and for your project's exclusive use:

    noid.cdlib.org/nd/noidu_ucbek

Once you mint an identifier, it can't be used again.  That makes me a
little nervous, but honestly, there are over 70 million identifiers in
the namespace, so if you waste a couple thousand here and there it won't
matter.  They'll look like "28722/k2nw0wb6m" and "28722/k2fx8384r",
coming out in random order.  The last character is a check character.
You just put "ark:/" in front of an id to make an ARK, and put
"http://example.org/ark:/" in front to use it in a web context.


http://noid.cdlib.org/nd/noidu_ucbek?get+

-John 
 	
 	
*/
	
	
	
}//end class declaration

?>
