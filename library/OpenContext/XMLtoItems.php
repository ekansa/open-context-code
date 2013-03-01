<?php

class OpenContext_XMLtoItems {
	
	const fullContextDelimiter = "|xx|"; // deliminator for context paths

	/*
	read a spatialUnit (location / object) XML document and insert into the database
	$itemObj is an instance of the subject class
	$itemXML is a simpleXML ArchaeoML spatialunit document
	*/
	public static function XMLsubjectBasic($itemObj, $itemXML){
		
		//get item UUID
		foreach ($itemXML->xpath("/arch:spatialUnit/@UUID") as $xpathResult){
			$itemObj->itemUUID = (string)$xpathResult;
		}
		
		//item label
		foreach ($itemXML->xpath("/arch:spatialUnit/arch:name/arch:string") as $xpathResult){
			$itemObj->label = (string)$xpathResult;
		}
		
		//original data source for the item
		$itemObj->sourceID = "WebService";
		foreach ($itemXML->xpath("//oc:metadata/oc:sourceID") as $xpathResult){
			$itemObj->sourceID = (string)$xpathResult;
		}
		
		//project id
		foreach ($itemXML->xpath("/arch:spatialUnit/@ownedBy") as $xpathResult){
			$itemObj->projectUUID = (string)$xpathResult;
		}
		
		//item class id
		foreach ($itemXML->xpath("/arch:spatialUnit/oc:item_class/oc:name") as $xpathResult){
			$space_class = (string)$xpathResult;
			$itemObj->className = $space_class;
			$itemObj->classID = OpenContext_XMLtoItems::class_id_lookup($space_class);
		}
		
		//come up with hash value to insure unique context
		$default_context_path = "";
		if($itemXML->xpath("/arch:spatialUnit/oc:context/oc:tree[@id='default']/oc:parent/oc:name")){
			foreach ($itemXML->xpath("/arch:spatialUnit/oc:context/oc:tree[@id='default']/oc:parent/oc:name") as $path) {
				$default_context_path .= $path . self::fullContextDelimiter;
			}	
		}
		else{
			//case where no context is found. this can only exist for items in project 0 (Open Context)
			if($itemObj->projectUUID != '0'){
				//stop if the item is not in the open context project
				$output = array("itemUUID" => $itemObj->itemUUID, "pubOK" => false, "error" => "bad context");
				return $output;
				break;
			}
		}
		$itemObj->contextPath = $default_context_path;
	    
		foreach($itemXML->xpath("//oc:metadata/oc:pub_date") as $xpathResult) {
			// Format the date as UTC (Solr requires this)
			$pub_date = (string)$xpathResult;
			$pub_date = date("Y-m-d H:i:s", strtotime($pub_date)); //mysql formated
			$itemObj->createdTime = $pub_date;
		}
	
		$default_context_path .= $itemObj->label; //Penelope see state manager line 564
		//this is the primary key
		$itemObj->contain_hash = md5($itemObj->projectUUID . "_" . $default_context_path);
		
		return $itemObj;
		
	}//end function
	
	
	/*
	read a media resource XML document and insert into the database
	$itemObj is an instance of the Media class
	$itemXML is a simpleXML ArchaeoML media resource document
	*/
	public static function XMLmediaBasic($itemObj, $itemXML){
		
		//get item UUID
		foreach ($itemXML->xpath("/arch:resource/@UUID") as $xpathResult){
			$itemObj->itemUUID = (string)$xpathResult;
		}
		
		//item label
		foreach ($itemXML->xpath("/arch:resource/arch:name/arch:string") as $xpathResult){
			$itemObj->label = (string)$xpathResult;
		}
		
		//original data source for the item
		$itemObj->sourceID = "WebService";
		foreach ($itemXML->xpath("//oc:metadata/oc:sourceID") as $xpathResult){
			$itemObj->sourceID = (string)$xpathResult;
		}
		
		//project id
		foreach ($itemXML->xpath("/arch:resource/@ownedBy") as $xpathResult){
			$itemObj->projectUUID = (string)$xpathResult;
		}
		
		//get media mime_type
		foreach ($itemXML->xpath("/arch:resource/arch:content/arch:externalFileInfo/arch:fileFormat") as $xpathResult){
			$itemObj->mimeType = (string)$xpathResult;
		}
		
		//get full URI
		foreach ($itemXML->xpath("//arch:externalFileInfo/arch:resourceURI") as $xpathResult){
			$itemObj->fullURI = (string)$xpathResult;
		}
		//get preview URI
		foreach ($itemXML->xpath("//arch:externalFileInfo/arch:previewURI") as $xpathResult){
			$itemObj->previewURI = (string)$xpathResult;
		}
		//get thumbnail URI
		foreach ($itemXML->xpath("//arch:externalFileInfo/arch:thumbnailURI") as $xpathResult){
			$itemObj->thumbnailURI = (string)$xpathResult;
		}
		
		//get file Name
		foreach ($itemXML->xpath("/arch:resource/arch:content/arch:externalFileInfo/oc:FileInfo/oc:FileName") as $xpathResult){
			$itemObj->filename = (string)$xpathResult;
		}
		
		//get ArchaeoML file type
		foreach ($itemXML->xpath("/arch:resource/@type") as $xpathResult){
			$itemObj->archType = (string)$xpathResult;
		}
		
		foreach($itemXML->xpath("//oc:metadata/oc:pub_date") as $xpathResult) {
			// Format the date as UTC (Solr requires this)
			$pub_date = (string)$xpathResult;
			$pub_date = date("Y-m-d H:i:s", strtotime($pub_date)); //mysql formated
			$itemObj->createdTime = $pub_date;
		}
		
		//for documents / diaries
		if ($itemXML->xpath("//arch:internalDocument/arch:string")) {
			foreach ($itemXML->xpath("//arch:internalDocument/arch:string") as $docContent) {
				$itemObj->internalDoc = (string)$docContent;
			}
		}
		
		return $itemObj;
	}//end function
		
	
	/*
	read a media resource XML document and insert into the database
	$itemObj is an instance of the Person class
	*/
	public static function XMLpersonBasic($itemObj, $itemXML){
		
		//get item UUID
		foreach ($itemXML->xpath("/arch:person/@UUID") as $xpathResult){
			$itemObj->itemUUID = (string)$xpathResult;
		}
		
		//item label
		foreach ($itemXML->xpath("/arch:person/arch:name/arch:string") as $xpathResult){
			$itemObj->label = (string)$xpathResult;
		}
		
		//original data source for the item
		$itemObj->sourceID = "WebService";
		foreach ($itemXML->xpath("//oc:metadata/oc:sourceID") as $xpathResult){
			$itemObj->sourceID = (string)$xpathResult;
		}
		
		//project id
		foreach ($itemXML->xpath("/arch:person/@ownedBy") as $xpathResult){
			$itemObj->projectUUID = (string)$xpathResult;
		}
		
		//get person first name
		foreach ($itemXML->xpath("/arch:person/arch:personInfo/arch:firstName") as $xpathResult){
			$itemObj->firstName  = (string)$xpathResult;
		}
		
		//get person last name
		foreach ($itemXML->xpath("/arch:person/arch:personInfo/arch:lastName") as $xpathResult){
			$itemObj->lastName = (string)$xpathResult;
		}
		
		foreach($itemXML->xpath("//oc:metadata/oc:pub_date") as $xpathResult) {
			// Format the date as UTC (Solr requires this)
			$pub_date = (string)$xpathResult;
			$pub_date = date("Y-m-d H:i:s", strtotime($pub_date)); //mysql formated
			$itemObj->createdTime = $pub_date;
		}
		
		return $itemObj;
	}//end function


	//read a property XML documet and insert into database
	public static function XMLpropertyBasic($itemObj, $itemXML){
			
		
		$data = array(); //array for data insert
		$varData = array();
		$valData = array();
		$valueInsert = true;
		
		//get project UUID
		foreach ($itemXML->xpath("/arch:property/@ownedBy") as $xpathResult){
			$itemObj->projectUUID = (string)$xpathResult;
		}
		
		//original data source for the item
		$itemObj->sourceID = "WebService";
	
		foreach ($itemXML->xpath("//oc:metadata/oc:sourceID") as $xpathResult){
			$itemObj->sourceID = (string)$xpathResult;
		}
		
		//get property UUID
		foreach ($itemXML->xpath("/arch:property/@UUID") as $xpathResult){
			$itemObj->itemUUID = (string)$xpathResult;
		}
		
		//get variable UUID
		foreach ($itemXML->xpath("//oc:manage_info/@variableID") as $xpathResult){
			$itemObj->varUUID = (string)$xpathResult;
		}
		
		//get value UUID
		foreach ($itemXML->xpath("//oc:manage_info/@valueID") as $xpathResult){
			$itemObj->valUUID = (string)$xpathResult;
		}
		
		//get variable label
		foreach ($itemXML->xpath("//oc:manage_info/oc:propVariable") as $xpathResult){
			$itemObj->varLabel = (string)$xpathResult; // to DO change to title case!!
		}
		
		//get variable sorting
		foreach ($itemXML->xpath("//oc:manage_info/oc:propVariable/@sortOrder") as $xpathResult){
			$sortOrder = (string)$xpathResult;
			$itemObj->varSort = $sortOrder + 0; 
		}
		
		//get variable type
		foreach ($itemXML->xpath("//oc:manage_info/oc:varType") as $xpathResult){
			$varType = (string)$xpathResult;
			$varType = strtolower($varType);
			$itemObj->varType = ucfirst($varType); 
		}
		
		//get value
		foreach ($itemXML->xpath("//oc:manage_info/oc:propValue") as $xpathResult){
			$itemObj->value = (string)$xpathResult;
		}
		
		if((($varType == "integer")||($varType == "decimal"))&&(is_numeric($itemObj->value))){
			$itemObj->valNumeric = $itemObj->value + 0;
		}
		
		//test for calendrical date
		$cal_test_string = str_replace("/", "-", $itemObj->value);
		if (($timestamp = strtotime($cal_test_string)) === false) {
			$calendardTest = false;
		}
		else{
			$calendardTest = true;
		}
		
		if(($varType == "calendric")&& $calendardTest){
			$itemObj->valCalendric = date("Y-m-d H:i:s", strtotime($cal_test_string)); //mysql formated
		}
		
		//get variable description
		if($itemXML->xpath("/arch:property/arch:notes/arch:note[@type='var_des']/arch:string")){
			foreach ($itemXML->xpath("/arch:property/arch:notes/arch:note[@type='var_des']/arch:string") as $xpathResult){
				$itemObj->varDescription = (string)$xpathResult;
				if($itemObj->varDescription == "This variable currently has no explanatory description."){
					$itemObj->varDescription = "";
				}
			}
		}
		else{
			$itemObj->varDescription = "";
		}
		
		//get property (variable:value) description
		if($itemXML->xpath("/arch:property/arch:notes/arch:note[@type='prop_des']/arch:string")){
			foreach ($itemXML->xpath("/arch:property/arch:notes/arch:note[@type='prop_des']/arch:string") as $xpathResult){
				$itemObj->propDescription = (string)$xpathResult;
				if($itemObj->propDescription == "This property currently has no explanatory description."){
					$itemObj->propDescription = "";
				}
			}
		}
		else{
			$itemObj->propDescription = "";
		}
		
		foreach($itemXML->xpath("//oc:metadata/oc:pub_date") as $xpathResult) {
			// Format the date as UTC (Solr requires this)
			$pub_date = (string)$xpathResult;
			$pub_date = date("Y-m-d H:i:s", strtotime($pub_date)); //mysql formated
			$itemObj->createdTime = $pub_date;
		}
		
		return $itemObj;
		
	}//end function



	/*
	read a project XML document and insert into the database
	$itemObj is an instance of the Person class
	*/
	public static function XMLprojectBasic($itemObj, $itemXML){
		
		//get item UUID
		foreach ($itemXML->xpath("/arch:project/@UUID") as $xpathResult){
			$itemObj->itemUUID = (string)$xpathResult;
		}
		
		//item label
		foreach ($itemXML->xpath("/arch:project/arch:name/arch:string") as $xpathResult){
			$itemObj->label = (string)$xpathResult;
		}
		
		//original data source for the item
		$itemObj->sourceID = "WebService";
		foreach ($itemXML->xpath("//oc:metadata/oc:sourceID") as $xpathResult){
			$itemObj->sourceID = (string)$xpathResult;
		}
		
		//project id
		foreach ($itemXML->xpath("/arch:project/@ownedBy") as $xpathResult){
			$itemObj->projectUUID = (string)$xpathResult;
		}
		
		foreach($itemXML->xpath("//oc:metadata/oc:pub_date") as $xpathResult) {
			// Format the date as UTC (Solr requires this)
			$pub_date = (string)$xpathResult;
			$pub_date = date("Y-m-d H:i:s", strtotime($pub_date)); //mysql formated
			$itemObj->createdTime = $pub_date;
		}
		
		// get project short description
		foreach($itemXML->xpath("//arch:notes/arch:note[@type='short_des']/arch:string") as $xpathResult){
			$itemObj->shortDes = (string)$xpathResult;
		}
		// get project long description
		foreach($itemXML->xpath("//arch:notes/arch:note[@type='long_des']/arch:string") as $xpathResult){
			$itemObj->longDes = (string)$xpathResult;
		}
		
		// get project Space count
		foreach ($itemXML->xpath("//oc:manage_info/oc:spaceCount") as $xpathResult){
			$itemCount = (string)$xpathResult;
			$itemObj->spaceCount = $itemCount+0;
		}
		
		// get project Diary count
		foreach ($itemXML->xpath("//oc:manage_info/oc:diaryCount") as $xpathResult){
			$itemCount = (string)$xpathResult;
			$itemObj->docCount = $itemCount+0;
		}
		
		// get project Media count
		foreach ($itemXML->xpath("//oc:manage_info/oc:mediaCount") as $xpathResult){
			$itemCount = (string)$xpathResult;
			$itemObj->mediaCount = $itemCount+0;
		}
		
		// get project Root Item ID
		foreach ($itemXML->xpath("//arch:links/oc:space_links/oc:link[oc:relation = 'project root']/oc:id") as $xpathResult){
			$itemObj->rootUUID = (string)$xpathResult;
		}
		
		// get project Root Path (the context path that contains all of the project's toplevel locations and objects)
		foreach ($itemXML->xpath("//oc:manage_info/oc:rootPath") as $xpathResult){
			$itemObj->rootPath = (string)$xpathResult;
		}
		
		// get project No properties message
		foreach ($itemXML->xpath("//oc:metadata/oc:no_props") as $xpathResult){
			$itemObj->noProps = (string)$xpathResult;
		}
		
		// get license ID from URI
		foreach ($itemXML->xpath("//oc:metadata/oc:copyright_lic/oc:lic_URI") as $xpathResult){
			$itemObj->licenseURI = (string)$xpathResult;
		}
		
		// get a DOI
		foreach ($itemXML->xpath("//oc:metadata/dc:identifier[@type='doi']") as $xpathResult){
			$itemObj->doi = (string)$xpathResult;
		}
		
		return $itemObj;
	}//end function
	

	//read a project XML document and insert into database	
	public static function metadata_project($projectUUID, $itemXML, $db){
		
		$metaData = array(); // array for meta inserts
		
		//get dc creator metadata
		foreach ($itemXML->xpath("//oc:metadata/dc:creator") as $xpathResult){
			$xpathResult = (string)$xpathResult;
			$metaData[] = array("project_id"=> $projectUUID, "dc_field"=>"<dc:creator>", "dc_value" => $xpathResult );
		}
		//get dc creator metadata
		foreach ($itemXML->xpath("//oc:metadata/dc:contributor") as $xpathResult){
			$xpathResult = (string)$xpathResult;
			$metaData[] = array("project_id"=> $projectUUID, "dc_field"=>"<dc:contributor>", "dc_value" => $xpathResult );
		}
		//get dc subject metadata
		foreach ($itemXML->xpath("//oc:metadata/dc:subject") as $xpathResult){
			$xpathResult = (string)$xpathResult;
			$metaData[] = array("project_id"=> $projectUUID, "dc_field"=>"<dc:subject>", "dc_value" => $xpathResult );
		}
		//get dc coverage metadata
		foreach ($itemXML->xpath("//oc:metadata/dc:coverage") as $xpathResult){
			$xpathResult = (string)$xpathResult;
			$metaData[] = array("project_id"=> $projectUUID, "dc_field"=>"<dc:coverage>", "dc_value" => $xpathResult );
		}
		
		$outcomes = 0;
		$where = array();
		$where[] = " project_id = '$projectUUID' ";
		$db->delete('dcmeta_proj', $where);
		
		foreach($metaData as $act_metadata){
			if(strlen($act_metadata["dc_value"])>0){
				try{
					$db->insert('dcmeta_proj', $act_metadata);
					$outcomes++;
				}
				catch (Exception $e) {
						
				}
			}
		}//end loop
		
		return $outcomes;
		
	}//end function
	
	
	
	//this function creates records to add to the links table
	public static function linksRetrieve($projectUUID, $sourceID, $originUUID, $originType, $itemXML, $db){
		$linksData = array();
		
		foreach ($itemXML->xpath("//arch:links/arch:docID") as $links_result){
			$actLink = array();
			$actLink["project_id"] = $projectUUID;
			$actLink["source_id"] = $sourceID;
			$actLink["link_uuid"] = OpenContext_OCConfig::gen_UUID();
			$actLink["origin_type"] = $originType;
			$actLink["origin_uuid"] = $originUUID;
			$actLink["origin_obs"] = 1;
			$actLink["targ_uuid"] = $links_result."";
			foreach ($links_result->xpath("@type") as $sub_result){
				$actLink["targ_type"] = $sub_result."";
			}
			foreach ($links_result->xpath("@info") as $sub_result){
				$actLink["link_type"] = $sub_result."";
			}
			$actLink["targ_obs"] = 1;
			$linksData[] = $actLink;
			unset($actLink);
		}
		
		
		//get rid of old observations
		$where = array();
		$where[] = "project_id = '$projectUUID' ";
		$where[] = "origin_uuid = '$originUUID' ";
		$db->delete('links', $where);
		
		$okInserts = 0;
		foreach($linksData as $actData){
			try{
				$db->insert('links', $actData);
				$okInserts++;
			}
			catch (Exception $e) {
					
			}
		}//end loop
		
		return $okInserts;
	}
	
	
	//this function creates records to add to the observation table
	public static function obs_props_Retrieve($projectUUID, $sourceID, $originUUID, $originType, $itemXML, $db){
		
		$obsData = array();
		
		$obsCount = 1;
		
		if ($itemXML->xpath("//arch:observation")) {   //case where properties are in observations
			foreach ($itemXML->xpath("//arch:observation") as $act_obs){
				
				$obsNumber = 1;
				if($act_obs->xpath("@obsNumber")){
					foreach($act_obs->xpath("@obsNumber") as $obsNumber){
						$obsNumber = $obsNumber +0;
					}
				}
				
				foreach ($act_obs->xpath("//arch:property") as $act_prop){
					$actObsData = array();
					$actObsData["project_id"] = $projectUUID;
					$actObsData["source_id"] = $sourceID;
					$actObsData["subject_type"] = $originType;
					$actObsData["subject_uuid"] = $originUUID;
					$actObsData["obs_num"] = $obsNumber;
					
					foreach($act_prop->xpath("oc:propid") as $act_prop_id){
						$actObsData["property_uuid"] = $act_prop_id."";
					}
					$obsData[] = $actObsData;
					unset($actObsData);
				}
				$obsCount++;
			}
		}
		else{
			foreach ($itemXML->xpath("//arch:property") as $act_prop){
				$actObsData = array();
				$actObsData["project_id"] = $projectUUID;
				$actObsData["source_id"] = $sourceID;
				$actObsData["subject_type"] = $originType;
				$actObsData["subject_uuid"] = $originUUID;
				$actObsData["obs_num"] = $obsCount;
					
				foreach($act_prop->xpath("oc:propid") as $act_prop_id){
					$actObsData["property_uuid"] = $act_prop_id."";
				}
				$obsData[] = $actObsData;
				unset($actObsData);
			}
		}
		
		//get rid of old observations
		$where = array();
		$where[] = "project_id = '$projectUUID' ";
		$where[] = "subject_uuid = '$originUUID' ";
		$db->delete('observe', $where);
		
		$okInserts = 0;
		foreach($obsData as $act_obs){
			try{
				$db->insert('observe', $act_obs);
				$okInserts++;
			}
			catch (Exception $e) {
					
			}
		}//end loop
		
		return $okInserts;
	}
	
	//this function generates space contain records from child items of a spatialUnit
	public static function childrenRetrieve($projectUUID, $sourceID, $originUUID, $itemXML, $db){
		
		$childData = array();
		foreach ($itemXML->xpath("//oc:children/oc:tree") as $act_tree){
			
			foreach ($act_tree->xpath("@id") as $act_tree_id){
				$actTreeId = $act_tree_id."";
			}
			
			foreach($act_tree->xpath("oc:child") as $act_child){
			
				foreach($act_child->xpath("oc:id") as $act_child_result){
					$actChild_uuid = (string)$act_child_result;
				}
				
				$actChildData = array();
				$actChildData["project_id"] = $projectUUID;
				$actChildData["hashCon"] = sha1($originUUID."_".$actChild_uuid);
				$actChildData["source_id"] = $sourceID;
				$actChildData["tree_uuid"] = $actTreeId;
				$actChildData["parent_uuid"] = $originUUID;
				$actChildData["parent_obs"] = 1;
				$actChildData["child_uuid"] = $actChild_uuid;
				$actChildData["child_obs"] = 1;
				
				$childData[] = $actChildData;
				unset($actChildData);
			}//end loop through children
		}//end loop through trees
		
		$okInserts = 0;
		foreach($childData as $conData){
			
			try{
				$db->insert('space_contain', $conData);
				$okInserts++;
			}
			catch (Exception $e) {
						
			}
		}
		return $okInserts ;

	}//end function
	
	
	//this function updates a previously created parent item of a new spatialUnit to add child relationship
	public static function parentCheck($projectUUID, $sourceID, $originUUID, $originName, $originClass, $itemXML, $db){
		
		$parentID = false;
		$maxLevel = 0;
		foreach ($itemXML->xpath("//oc:context/oc:tree/oc:parent") as $act_parent){
			foreach ($act_parent->xpath("oc:level") as $act_result){
				$act_level = $act_result+0;
				if($act_level > $maxLevel){
					$maxLevel = $act_level;
				}
			}
			
			if($act_level <= $maxLevel){
				foreach ($act_parent->xpath("oc:id") as $act_result){
					$parentID = $act_result."";	
				}
			}
			
		}//end loop through parents
		
		if($parentID != false){
			
			
			$host = OpenContext_OCConfig::get_host_config();
			$parentXML = file_get_contents($host."/subjects/".$parentID.".xml");
			@$xmlParent = simplexml_load_string($parentXML);
			
			if($xmlParent){
				$output = true;
				unset($xmlParent);
				//$parentXML = str_replace("http://www.opencontext.org/database/schema/space_schema_v1.xsd", OpenContext_OCConfig::get_namespace("oc", "spatial"), $parentXML);
					
				$parent_dom = new DOMDocument("1.0", "utf-8");
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
				$query = "//oc:children/oc:tree/oc:child/oc:id";
				$result_children = $xpath->query($query, $parent_dom);
				foreach($result_children as $act_id){
					$checkID = $act_id->nodeValue;
					if($checkID == $originUUID){
						$childExisting = true;
					}
				}
				
				$child_node = false;
				
				//if the child is not already registered, create the new child node
				if(!$childExisting){
					$child_node = $parent_dom->createElementNS(OpenContext_OCConfig::get_namespace("oc", "spatial"), "oc:child");
					$child_href = $parent_dom->createAttribute('href'); 
					$child_hrefText = $parent_dom->createTextNode($host."/subjects/".$originUUID);
					$child_href->appendChild($child_hrefText);
					$child_node->appendChild($child_href);
					$child_name = $parent_dom->createElementNS(OpenContext_OCConfig::get_namespace("oc", "spatial"), "oc:name");
					$child_nameText = $parent_dom->createTextNode($originName);
					$child_name->appendChild($child_nameText);
					$child_node->appendChild($child_name);
					$child_id = $parent_dom->createElementNS(OpenContext_OCConfig::get_namespace("oc", "spatial"), "oc:id");
					$child_idText = $parent_dom->createTextNode($originUUID);
					$child_id->appendChild($child_idText);
					$child_node->appendChild($child_id);
					
					$class_icon = OpenContext_ProjectAtomJson::class_icon_lookup($originClass);
					$class_icon = str_replace("http://www.opencontext.org/database/ui_images/oc_icons/", "", $class_icon);
					$child_class = $parent_dom->createElementNS(OpenContext_OCConfig::get_namespace("oc", "spatial"), "oc:item_class");
					$child_class_name = $parent_dom->createElementNS(OpenContext_OCConfig::get_namespace("oc", "spatial"), "oc:name");
					$child_nameText = $parent_dom->createTextNode($originClass);
					$child_class_name->appendChild($child_nameText);
					$child_class->appendChild($child_class_name);
					$child_class_icon = $parent_dom->createElementNS(OpenContext_OCConfig::get_namespace("oc", "spatial"), "oc:iconURI");
					$child_iconText = $parent_dom->createTextNode($class_icon);
					$child_class_icon->appendChild($child_iconText);
					$child_class->appendChild($child_class_icon);
					$child_node->appendChild($child_class);
				}
				
				
				if($child_node != false) {
				//Now check to see if the new child node should be added to an exising tree
					$query = "//oc:children/oc:tree";
					$result_tree = $xpath->query($query, $parent_dom);
					if(!$result_tree->item(0)){
						$result_tree = null;
					}
					
					$tree_id = "default";
					
					if($result_tree != null){
						$query = "//oc:children/oc:tree/@id";
						$result_tree_id = $xpath->query($query, $parent_dom);
						$tree_id = $result_tree_id->item(0)->nodeValue;
						$act_tree = $result_tree->item(0); 
						$act_tree->appendChild($child_node);
						
					}
					else{ //create a new set of nodes for this child item
						$tree_id = 'default';
						$query = "//arch:spatialUnit/oc:metadata";
						$result_nodes = $xpath->query($query, $parent_dom);
						$refNode = $result_nodes->item(0);
						$children_node = $parent_dom->createElementNS(OpenContext_OCConfig::get_namespace("oc", "spatial"), "oc:children");
						$tree_node = $parent_dom->createElementNS(OpenContext_OCConfig::get_namespace("oc", "spatial"), "oc:tree");
						$tree_idNode = $parent_dom->createAttribute('id');
						$tree_idText = $parent_dom->createTextNode($tree_id);
						$tree_idNode->appendChild($tree_idText);
						$tree_node->appendChild($tree_idNode);
						$tree_node->appendChild($child_node);
						$children_node->appendChild($tree_node);
						$refNode->parentNode->insertBefore($children_node, $refNode);
					}
				
					$query ="//arch:spatialUnit";
					$result_arch = $xpath->query($query, $parent_dom);
					
					//get only the archaeoML part of the data
					$newArchaeoML = $parent_dom->saveXML($result_arch->item(0));
					$parData["archaeoML"]= $newArchaeoML;
					
					$parentObj = new Subject;
					$newSaved = $parentObj->updateArchaeoML($parentID, $newArchaeoML, $db); //save new archaeoML to database, version old version
					
					//echo htmlentities($parData["space_archaeoml"]);
					
					$conData = array("project_id" => $projectUUID,
							 "hashCon" => sha1($parentID."_".$originUUID),
							 "source_id" => $sourceID,
							 "tree_uuid" => $tree_id,
							 "parent_uuid" => $parentID,
							 "parent_obs" => 1,
							 "child_uuid" => $originUUID,
							 "child_obs" => 1
							 );
					
					$okInserts = 0;
					try{
						$db->insert('space_contain', $conData);
						$okInserts++;
					}
					catch (Exception $e) {
					
					}
					
					//$output = array("xmlUpdated" => $newSaved, "newContain" => $okInserts);
					$output = $okInserts;
				}
				else{ //Child node is alread there, no need for any updates
					$output = false;
				}
				
			}
			else{ //no parent item found (item is a child, and parent is yet to be imported)
				$output = false;
			}
		}
		else{ // no parent items
			$output = false;
		}

		return $output;
	}//end function
	
	
	
	
	public static function itemPropsRetrieve($projectUUID, $sourceID, $itemXML){
		$propData = array();
		$varData = array();
		$valData = array();
		
		foreach ($itemXML->xpath("//arch:property") as $act_prop){
			$actPropData = array();
			$actPropData["project_id"] = $projectUUID;
			$actPropData["source_id"] = $sourceID;
			$actVarData = $actPropData;
			$actValData = $actPropData;
			
			$doVal = true;
					
					
			foreach($act_prop->xpath("oc:propid") as $act_prop_result){
				$actPropData["property_uuid"] = $act_prop_result."";
			}
			
			foreach($act_prop->xpath("arch:variableID") as $act_prop_result){
				$actPropData["variable_uuid"] = $act_prop_result."";
				$actVarData["variable_uuid"] = $actPropData["variable_uuid"];
			}
			
			
			if($act_prop->xpath("arch:valueID")){
				foreach($act_prop->xpath("arch:valueID") as $act_prop_result){
					$actPropData["value_uuid"] = $act_prop_result."";
					$actValData["value_uuid"] = $actPropData["value_uuid"];
				}
			}
			else{
				if(($act_prop->xpath("arch:integer"))||($act_prop->xpath("arch:decimal"))){
					$actPropData["value_uuid"] = "number";
					foreach($act_prop->xpath("arch:integer") as $act_prop_result){
						$actPropData["val_num"] = $act_prop_result."";
					}
					foreach($act_prop->xpath("arch:decimal") as $act_prop_result){
						$actPropData["val_num"] = $act_prop_result."";
					}
					$doVal = false;
					
					if(($act_prop->xpath("arch:integer/@href"))||($act_prop->xpath("arch:decimal/@href"))){
						foreach($act_prop->xpath("arch:integer/@href") as $act_prop_result){
							$actVarData["unitURI"] = $act_prop_result."";
						}
						foreach($act_prop->xpath("arch:decimal/@href") as $act_prop_result){
							$actVarData["unitURI"] = $act_prop_result."";
						}
					}
				}
			}
			
			if($act_prop->xpath("oc:var_label")){
				foreach($act_prop->xpath("oc:var_label") as $act_prop_result){
					$actVarData["var_label"] = $act_prop_result."";
				}
			}
			if($act_prop->xpath("oc:var_label/@type")){
				foreach($act_prop->xpath("oc:var_label/@type") as $act_prop_result){
					$actVarData["var_type"] = $act_prop_result."";
					$actVarData["var_type"] = strtolower($actVarData["var_type"]);
				}
			}
			if($act_prop->xpath("oc:show_val")){
				foreach($act_prop->xpath("oc:show_val") as $act_prop_result){
					$actValData["val_text"] = $act_prop_result."";
				}
			}
			
			
			$propData[] = $actPropData;
			unset($actPropData);
			$varData[] = $actVarData;
			unset($actVarData);
			if($doVal){
				$valData[] = $actValData;
			}
			unset($actValData);
		}
		
		return array("props"=>$propData, "vars" => $varData, "vals" => $valData);
	}
	
	
	
	public static function linkedDataRetrieve($projectUUID, $sourceID, $itemXML){
		$linkedData = array();
		
		foreach($itemXML->xpath("//arch:property") as $act_prop){
			
			foreach($act_prop->xpath("arch:variableID") as $act_prop_result){
				$varUUID = $act_prop_result."";
			}
			
			//get units of measurement links
			if(($act_prop->xpath("arch:integer/@href"))||($act_prop->xpath("arch:decimal/@href"))){
				$actVarLink = array();
				$actVarLink["fk_project_uuid"] = $projectUUID;
				$actVarLink["source_id"] = $sourceID;
				$actVarLink["linkedType"] = "unit";
				$actVarLink["itemUUID"] = $varUUID;
				$actVarLink["itemType"] = "variable";
				foreach($act_prop->xpath("arch:integer/@href") as $act_prop_result){
					$actVarLink["linkedURI"] = $act_prop_result."";
				}
				foreach($act_prop->xpath("arch:decimal/@href") as $act_prop_result){
					$actVarLink["linkedURI"] = $act_prop_result."";
				}
				foreach($act_prop->xpath("arch:integer/@name") as $act_link_result){
					$actVarLink["linkedLabel"] = $act_link_result."";
				}
				foreach($act_prop->xpath("arch:decimal/@name") as $act_link_result){
					$actVarLink["linkedLabel"] = $act_link_result."";
				}
				foreach($act_prop->xpath("arch:integer/@abrv") as $act_link_result){
					$actVarLink["linkedAbrv"] = $act_link_result."";
				}
				foreach($act_prop->xpath("arch:decimal/@abrv") as $act_link_result){
					$actVarLink["linkedAbrv"] = $act_link_result."";
				}
				
				$actVarLink["hashID"] = md5($actVarLink["itemUUID"]."_".$actVarLink["linkedURI"]);
				$linkedData[] = $actVarLink;
			}
			
			//get type linked data
			foreach ($act_prop->xpath("oc:linkedData/oc:relationLink") as $act_link){
				$actVarLink = array();
				
				$actVarLink["fk_project_uuid"] = $projectUUID;
				$actVarLink["source_id"] = $sourceID;
				$actVarLink["linkedType"] = "type";
				$actPropLink = $actVarLink;
				
				
				foreach($act_link->xpath("@localID") as $act_link_result){
					$actVarLink["itemUUID"] = $act_link_result."";
				}
				foreach($act_link->xpath("@localType") as $act_link_result){
					$actVarLink["itemType"] = $act_link_result."";
				}
				foreach($act_link->xpath("@href") as $act_link_result){
					$actVarLink["linkedURI"] = $act_link_result."";
				}
				foreach($act_link->xpath("oc:label") as $act_link_result){
					$actVarLink["linkedLabel"] = $act_link_result."";
				}
				foreach($act_link->xpath("oc:vocabulary") as $act_link_result){
					$actVarLink["vocabulary"] = $act_link_result."";
				}
				foreach($act_link->xpath("oc:vocabulary/@href") as $act_link_result){
					$actVarLink["vocabURI"] = $act_link_result."";
				}
				
				$actVarLink["hashID"] = md5($actVarLink["itemUUID"]."_".$actVarLink["linkedURI"]);
				
				
				foreach($act_link->xpath("oc:targetLink/@localID") as $act_link_result){
					$actPropLink["itemUUID"] = $act_link_result."";
				}
				foreach($act_link->xpath("oc:targetLink/@localType") as $act_link_result){
					$actPropLink["itemType"] = $act_link_result."";
				}
				foreach($act_link->xpath("oc:targetLink/@href") as $act_link_result){
					$actPropLink["linkedURI"] = $act_link_result."";
				}
				foreach($act_link->xpath("oc:targetLink/oc:label") as $act_link_result){
					$actPropLink["linkedLabel"] = $act_link_result."";
				}
				foreach($act_link->xpath("oc:targetLink/oc:vocabulary") as $act_link_result){
					$actPropLink["vocabulary"] = $act_link_result."";
				}
				foreach($act_link->xpath("oc:targetLink/oc:vocabulary/@href") as $act_link_result){
					$actPropLink["vocabURI"] = $act_link_result."";
				}
				
				$actPropLink["hashID"] = md5($actPropLink["itemUUID"]."_".$actPropLink["linkedURI"]);
				
				$linkedData[] = $actVarLink;
				$linkedData[] = $actPropLink;
			}
		}
		
		return $linkedData;
	}
	
	
	
	public static function license_lookup($licURI){
		$db_params = OpenContext_OCConfig::get_db_config();
		$db = new Zend_Db_Adapter_Pdo_Mysql($db_params);
		$db->getConnection();
		
		$output = false;
		
		$sql = "SELECT licenses.license_id
		FROM licenses
		WHERE licenses.license_url = '".$licURI."' ";
		
		$results = $db->fetchAll($sql, 2);
		foreach($results as $result){
                        $output = $result["license_id"];
		}
		
		$db->closeConnection();
		return $output;
	}
	
	
	public static function class_id_lookup($class_name){
		
		$class_icon_uri = false;
		$class_id = false;
		$db_params = OpenContext_OCConfig::get_db_config();
                $db = new Zend_Db_Adapter_Pdo_Mysql($db_params);
		$db->getConnection();
                $sql = 'SELECT sp_classes.class_uuid
                    FROM sp_classes
                    WHERE sp_classes.class_label LIKE "'.$class_name.'"
                    LIMIT 1';
		
                $result = $db->fetchAll($sql, 2);
		$db->closeConnection();
		
		if($result){
                    $class_id = $result[0]["class_uuid"];
		}
		
		return $class_id;
	}//end function
	
	public static function light_parseXMLcoding($string)
	{
	    if ( strlen($string) == 0 )
		return $string;
	    
	    libxml_use_internal_errors(true);
	    $test_string = "<test>".$string."</test>";
	    $doc = simplexml_load_string($test_string);
	    
	    if(!($doc)){
		// convert problematic characters to XML entities ('&' => '&amp;')
		$string = htmlentities($string);
		
		// convert ISO-8859-1 entities to numerical entities ('&eacute;' => '&#233;')       
		$mapping = array();
		foreach (get_html_translation_table(HTML_ENTITIES, ENT_QUOTES) as $char => $entity){
		    $mapping[$entity] = '&#' . ord($char) . ';';
		}
		$string = str_replace(array_keys($mapping), $mapping, $string);
	       
		// encode as UTF-8
		$string = utf8_encode($string);
	    }
	    //$string = str_replace("&amp;#", "&#", $string);
	    //$string = str_replace("amp;#", "#", $string);
	    return $string;       
	}
	
	
	
	public static function itemChecker($uuid, $itemType){
		
		$db_params = OpenContext_OCConfig::get_db_config();
                $db = new Zend_Db_Adapter_Pdo_Mysql($db_params);
		$db->getConnection();
		$table = "space";
		$idField = "uuid";
		
		if($itemType == 'media'){
			$table = "resource";
			$idField = "uuid";
		}
		
		$sql = "SELECT $table.$idField AS item_uuid FROM $table WHERE $idField = '$uuid' ";
		$result = $db->fetchOne($sql);
                
		$db->closeConnection();
		
		if($result){
			return true;
		}
		else{
			return false;	
		}
		
	}//end function
	
	public static function regImportError($uuid, $itemType){
		
		$db_params = OpenContext_OCConfig::get_db_config();
                $db = new Zend_Db_Adapter_Pdo_Mysql($db_params);
		$db->getConnection();
		$data = array("uuid" => $uuid, "type" => $itemType);
		
		try{
			$db->insert('importer_error', $data);	
				}
			catch (Exception $e) {
					
			}
                
		$db->closeConnection();
		
		
	}//end function
	
	
	public static function geoRedo($itemID, $itemXML){
		
		$itemXML_string = false;
		
		$itemXML_string = OpenContext_NewDocs::newLocation($itemID, 36.099243, 43.3276, "Nimrud", "GHF1SPA0000077841", $itemXML);
		
		return $itemXML_string;
	}
	
	//this function puts a new location on an item
	//it return false if the item is not contained in the passed $spaceID
	public static function newLocation($itemID, $newLat, $newLon, $spaceName, $spaceID, $itemXML){
		
		$inSpace = false;
		$itemXML_string = false;
		foreach ($itemXML->xpath("//oc:context/oc:tree/oc:parent/oc:id") as $parentID){
			$parentID_val = $parentID.""; 
			if($parentID_val == $spaceID){
				$inSpace = true;
			}
		}
		
		if($inSpace || ($itemID == $spaceID)){
			foreach ($itemXML->xpath("//oc:geo_reference/oc:geo_lat") as $geoLat){
				$geoLat[0] = $newLat;
			}
			foreach ($itemXML->xpath("//oc:geo_reference/oc:geo_long") as $geoLong){
				$geoLong[0] = $newLon;
			}
			foreach ($itemXML->xpath("//oc:geo_reference/oc:metasource/@ref_type") as $refType){
				$refType[0] = "self";
				if($inSpace){
					$refType[0] = "contained";
				}
			}
			foreach ($itemXML->xpath("//oc:geo_reference/oc:metasource/oc:source_name") as $sourceName){
				$sourceName[0] = $spaceName;
			}
			foreach ($itemXML->xpath("//oc:geo_reference/oc:metasource/oc:source_id") as  $sourceID){
				$sourceID[0] = $spaceID;
			}
			
			$itemXML_string = $itemXML->asXML();
		}
		
		
		return $itemXML_string;
		
		
	}
	
	
}//end class declaration

?>
