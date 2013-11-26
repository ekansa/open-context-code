<?php

class OpenContext_XMLtoOpenContextItem {
		
	const maxRelatedLinks = 4; //maximum number of related spatial links to follow for media descriptions.
	const taxonomyPathDelim = "::"; //delimiator for taxonomy paths
	
	public static function solrEscape($stringToEscape) {
		/**  In addition to the space character, solr requires that we escape the following characters because
		they're part of solr/lucene's query language: + - && || ! ( ) { } [ ] ^ " ~ * ? : \
		*/
	
		//characters we need to escape
		$search = array('\\', ' ', ':', '\'', '&&', '||', '(', ')', '+', '-', '!', '{', '}','[', ']', '^', '~', '*', '"', '?');
	   
		// escaped version of characters
		$replace = array('\\\\', '\ ', '\:', '\\\'', '\&\&', '\|\|', '\(', '\)', '\+', '\-', '\!', '\{', '\}', '\[', '\]', '\^', '\~', '\*', '\\"', '\?');
	    return str_replace($search, $replace, $stringToEscape);
	}  
	
	
	public static function XMLspatialItemBasics($OpenContextItem, $spatialItem){
		
		$OpenContextItem->documentType = "spatial";
	
		// get the item's UUID
		foreach($spatialItem->xpath("//arch:spatialUnit/@UUID") as $spaceid) {
		    $itemUUID = (string)$spaceid;
		    $OpenContextItem->itemUUID = $itemUUID; // add it to the Open Contex item
		}
	
		// get the publication date (the date items are added to Open Context).
		foreach($spatialItem->xpath("//arch:spatialUnit/oc:metadata/oc:pub_date") as $pub_date) {
		    // Format the date as UTC (Solr requires this) 
		    $pub_date = date("Y-m-d\TH:i:s\Z", strtotime($pub_date));
		    $OpenContextItem->pubDate = $pub_date;
		}
	
		// get the item_label
		foreach ($spatialItem->xpath("//arch:spatialUnit/arch:name/arch:string") as $item_label) {
			$item_label = (string)$item_label;
			$item_label = trim($item_label);
			$OpenContextItem->itemLabel  = $item_label;
			
		}//end loop for item labels
	
		foreach($spatialItem->xpath("//arch:spatialUnit/@ownedBy") as $projUUID) {
			$projUUID = (string)$projUUID;
			$OpenContextItem->projectUUID  = $projUUID;
		}
	
		foreach ($spatialItem->xpath("//arch:spatialUnit/oc:metadata/oc:project_name") as $project_name) {
			$project_name = (string)$project_name;
			$project_name = trim($project_name);
			$OpenContextItem->projectName  = $project_name;
		}
	
		// get the item class
		foreach ($spatialItem->xpath("//arch:spatialUnit/oc:item_class/oc:name") as $item_class) {
			$item_class = (string)$item_class;
			$OpenContextItem->addSimpleArrayItem($item_class, "classes");
		}
	
		return $OpenContextItem;
	
	}//end reindex function
	
	
	
	public static function XMLmediaItemBasics($OpenContextItem, $mediaItem){
		
		// get the item's UUID
		foreach($mediaItem->xpath("//arch:resource/@UUID") as $media_id) {
		    $itemUUID = (string)$media_id;
		    $OpenContextItem->itemUUID = $itemUUID; // add it to the Open Contex item
		}
		
		//get item types
		foreach($mediaItem->xpath("//arch:resource/@type") as $media_type) {
		    $media_type = strtolower($media_type);
		    $OpenContextItem->documentType = $media_type;
		}
		
		// get the publication date (the date items are added to Open Context).
		$pub_date = false;
		foreach($mediaItem->xpath("//arch:resource/arch:DublinCoreMetadata/arch:Date") as $pub_date) {
		    // Format the date as UTC (Solr requires this) 
			$pub_date = date("Y-m-d\TH:i:s\Z", strtotime($pub_date));
			$OpenContextItem->pubDate = $pub_date;
		}
		
		if(!$pub_date){
			foreach($mediaItem->xpath("//oc:metadata/oc:pub_date") as $pub_date) {
				// Format the date as UTC (Solr requires this) 
				$pub_date = date("Y-m-d\TH:i:s\Z", strtotime($pub_date));
				$OpenContextItem->pubDate = $pub_date;
			}
		}
		
	
		// get the item_label
		foreach ($mediaItem->xpath("//arch:resource/arch:name/arch:string") as $item_label) {
			$item_label = (string)$item_label;
			$item_label = trim($item_label);
			$OpenContextItem->itemLabel  = $item_label;
		}//end loop for item labels
	
		foreach($mediaItem->xpath("//arch:resource/@ownedBy") as $projUUID) {
			$projUUID = (string)$projUUID;
			$OpenContextItem->projectUUID  = $projUUID;
		}
	
		foreach ($mediaItem->xpath("//arch:resource/oc:metadata/oc:project_name") as $project_name) {
			$project_name = (string)$project_name;
			$project_name = trim($project_name);
			$OpenContextItem->projectName  = $project_name;
		}
	
		
		//for documents / diaries
		if ($mediaItem->xpath("//arch:internalDocument/arch:string")) {
			$OpenContextItem->documentType = "document";
			foreach ($mediaItem->xpath("//arch:internalDocument/arch:string") as $docContent) {
				$docContent = (string)$docContent;
				$OpenContextItem->addSimpleArrayItem($docContent, "alphaNotes"); //add notes
			}
		}
	
		return 	$OpenContextItem;
	}//end function



	public static function XMLprojectItemBasics($OpenContextItem, $xmlItem){
		
		// get the item's UUID
		foreach($xmlItem->xpath("//arch:project/@UUID") as $media_id) {
		    $itemUUID = (string)$media_id;
		    $OpenContextItem->itemUUID = $itemUUID; // add it to the Open Contex item
		}
		
		$OpenContextItem->documentType = "project";
		
		foreach($xmlItem->xpath("//oc:metadata/oc:pub_date") as $pub_date) {
			// Format the date as UTC (Solr requires this) 
			$pub_date = date("Y-m-d\TH:i:s\Z", strtotime($pub_date));
			$OpenContextItem->pubDate = $pub_date;
		}
		
	
		// get the item_label
		foreach ($xmlItem->xpath("//arch:project/arch:name/arch:string") as $item_label) {
			$item_label = (string)$item_label;
			$item_label = trim($item_label);
			$OpenContextItem->itemLabel  = $item_label;
		}//end loop for item labels
	
		foreach($xmlItem->xpath("//arch:project/@ownedBy") as $projUUID) {
			$projUUID = (string)$projUUID;
			$OpenContextItem->projectUUID  = $projUUID;
		}
	
		foreach ($xmlItem->xpath("//arch:project/oc:metadata/oc:project_name") as $project_name) {
			$project_name = (string)$project_name;
			$project_name = trim($project_name);
			$OpenContextItem->projectName  = $project_name;
		}
	
		if($project_name != $item_label){
			$OpenContextItem->addSimpleArrayItem("Sub-Project", "classes");
		}
		else{
			$OpenContextItem->addSimpleArrayItem("Project", "classes");
		}
	
		
		foreach ($xmlItem->xpath("//arch:project/oc:manage_info/oc:projGeoPoint") as $projectGeo) {
			$projectGeo = (string)$projectGeo;
			$geoArray = explode(" ", $projectGeo);
			$OpenContextItem->addGeo($geoArray[0], $geoArray[1]);  //lat, lon
		}
	
		return 	$OpenContextItem;
	}//end function


	
	/*
	This function gets information from person items
	*/
	public static function XMLpersonItemBasics($OpenContextItem, $xmlItem){
		
		// get the item's UUID
		foreach($xmlItem->xpath("//arch:person/@UUID") as $media_id) {
		    $itemUUID = (string)$media_id;
		    $OpenContextItem->itemUUID = $itemUUID; // add it to the Open Contex item
		}
		
		$OpenContextItem->documentType = "person";

		
		foreach($xmlItem->xpath("//oc:metadata/oc:pub_date") as $pub_date) {
			// Format the date as UTC (Solr requires this) 
			$pub_date = date("Y-m-d\TH:i:s\Z", strtotime($pub_date));
			$OpenContextItem->pubDate = $pub_date;
		}
		
	
		// get the item_label
		foreach ($xmlItem->xpath("//arch:person/arch:name/arch:string") as $item_label) {
			$item_label = (string)$item_label;
			$item_label = trim($item_label);
			$OpenContextItem->itemLabel  = $item_label;
		}//end loop for item labels
	
	
		if($xmlItem->xpath("//arch:personInfo/arch:lastName")){
			foreach ($xmlItem->xpath("//arch:personInfo/arch:lastName") as $lastName) {
				$lastName = (string)$lastName;
				$lastName = trim($lastName);
				if(strlen($lastName)>1){
					$OpenContextItem->alphaNameSortOrder($lastName);
				}
			}//end loop for item labels
		}
		
		if($OpenContextItem->labelSort <= 0){
			if(substr_count($item_label, " ")>0){
				$nameArray = explode(" ", $item_label);
				$nameCounts = count($nameArray);
				//$OpenContextItem->labelSort = ord($nameArray[$nameCounts - 1]);
				$OpenContextItem->alphaNameSortOrder($nameArray[$nameCounts - 1]);
			}
		}
	
		//echo "space count ".(substr_count($item_label, " "));
		//echo "first sort: ".$OpenContextItem->labelSort;
	
		$interestScore = 0;
		if($xmlItem->xpath("//arch:personInfo/@spaceCount")){
			foreach ($xmlItem->xpath("//arch:personInfo/@spaceCount") as $itemCount) {
				$itemCount = (string)$itemCount;
				$itemCount = $itemCount + 0;
				$interestScore = $interestScore + round($itemCount / 100, 0);
			}//end loop for item labels
		}
		
		$OpenContextItem->interestScore = $interestScore;
		
		if($xmlItem->xpath("//oc:manage_info/oc:type")){
			foreach ($xmlItem->xpath("//oc:manage_info/oc:type") as $type) {
				$type = (string)$type;
				$type = trim($type);
				$OpenContextItem->addSimpleArrayItem($type, "classes");
			}//end loop for item labels
		}
		else{
			$OpenContextItem->addSimpleArrayItem("Person", "classes"); //required class
		}
		
		
		foreach($xmlItem->xpath("//arch:person/@ownedBy") as $projUUID) {
			$projUUID = (string)$projUUID;
			$OpenContextItem->projectUUID  = $projUUID;
		}
	
		foreach ($xmlItem->xpath("//arch:person/oc:metadata/oc:project_name") as $project_name) {
			$project_name = (string)$project_name;
			$project_name = trim($project_name);
			$OpenContextItem->projectName  = $project_name;
		}
	
		return 	$OpenContextItem;
	}//end function



	/*
	This function gets class information from spatial items
	*/
	public static function XMLtoSpatialClass($OpenContextItem, $xmlItem){
		// get the item class, from the related spatial item
		foreach ($xmlItem->xpath("//oc:space_links/oc:link/oc:item_class/oc:name") as $item_class) {
			$item_class = (string)$item_class;
			$OpenContextItem->addSimpleArrayItem($item_class, "classes");
		}
		return $OpenContextItem;
	}//end function




	/*
	 This function gets Default Context and alternative contexts from an XML item
	 A default context is a slash seperated "/" hierarchy. It's meant to be the most important hierachic dimension
	 in a collection
	 Alternative paths are "::" seperated hierarchic items. These are treated as in a "taxon"
	*/
	public static function XMLtoContextData($OpenContextItem, $xmlItem, $parentItems = false){
		
		$baseParents = array();
		if($parentItems != false){
			if(is_array($parentItems)){
				foreach($parentItems as $parentItem){
					$baseParents[] = $parentItem; //add the array to the base parent
				}
			}
			else{
				$baseParents[] = $parentItems; //add the string as the one parent item
			}
		}
		
		
		if (!$xmlItem->xpath("//oc:context/oc:tree")) {
			$default_context_path = "ROOT";  // note: variable $default_context_path used later in abreviated Atom feed
		}
		
		
		// Get the default context path
		if($xmlItem->xpath("//arch:spatialUnit/oc:context")){
			$xpathPrefix = "//arch:spatialUnit/oc:context";
		}
		else{
			$xpathPrefix = "//oc:context";
		}
		
		if ($xmlItem->xpath($xpathPrefix."/oc:tree[@id='default']")) {
			$contextArray = array();
			foreach ($xmlItem->xpath($xpathPrefix."/oc:tree[@id='default']") as $default_tree) {
				foreach ($default_tree->xpath("oc:parent/oc:name") as $pathItem) {
				$pathItem = (string)$pathItem;
				$contextArray[] = $pathItem;
				}
				
				break;
			}
			$OpenContextItem->addDefaultContext($contextArray);
		}//end condition with default context tree
		
		
		
		// Get the additional context paths
		// first check for the presence of additional paths
		if ($xmlItem->xpath($xpathPrefix."/oc:tree[not(@id='default')]")) {
	
			$treeCount = 1; //differentiate between different context trees
			foreach ($xmlItem->xpath($xpathPrefix."/oc:tree[not(@id='default')]") as $non_default_tree) {
				
				$treeID = false;
				foreach ($non_default_tree->xpath("@id") as $treeID) {
					$treeID = (string)$treeID;
					if(strlen($treeID)>4){
						$treeID = false;
					}
				}
				
				//echo "additional_context_path: ";
				// iterate through the trees to build the context path(s)
				if(!$treeID){
					$context_tree_name = "Alternate Context ".$treeCount;
				}
				else{
					$context_tree_name = "Alternate Context ".$treeID;
				}
				
				$OpenContextItem->addProperty($context_tree_name, $baseParents, 'nominal', false);
				$parentArray = $baseParents;
				$parentArray[] = $context_tree_name;
				foreach ($non_default_tree->xpath("oc:parent/oc:name") as $alt_path_item) {
					$alt_path_item = (string)$alt_path_item;
					$OpenContextItem->addProperty($alt_path_item, $parentArray, 'nominal', false);
					$parentArray[] = $alt_path_item; //add item to parent array to be parent of next item in hierarchy
				}
				
				$OpenContextItem->addfullPropertyPath($parentArray); //add taxonomy path
				unset($parentArray);
			$treeCount++;
			}
		
		}//end condition with another context tree


		return $OpenContextItem;
	}//return function


	/*
	 This function returns the count of linked images, linked diary / documents, and linke other kinds of
	 media. It's important to inlcude to generate Interest Scores
	*/
	public static function XMLtoMediaLinkData($OpenContextItem, $xmlItem){
		
		$image_media = $xmlItem->xpath("//arch:links/oc:media_links/oc:link[oc:type='image']");  
		// if the xpath above returns either false or null... 
		if (!$image_media) {
			$image_media_count = 0;
			
		} else { // otherwise, the xpath above returns a non-empty array which we can count.
			$image_media_count = count($image_media);
		}
		
		$other_binary_media = $xmlItem->xpath("//arch:links/oc:media_links/oc:link[oc:type!='image']");
		if (!$other_binary_media) { // if there are no other binary media, set the count to 0.
			$other_binary_media_count = 0;
			
		} else { // otherwise, the xpath above returns a non-empty array which we can count.
			$other_binary_media_count =  count($other_binary_media);
		}
		
		$diary = $xmlItem->xpath("//arch:links/oc:diary_links/oc:link");
		if (!$diary) {
			$diary_count = 0;
		}
		else { // otherwise, the xpath above returns a non-empty array which we can count.
			$diary_count = count($diary);
		}
		
		$OpenContextItem->imageLinkNum = $image_media_count;
		$OpenContextItem->otherLinkNum = $other_binary_media_count;
		$OpenContextItem->docLinkNum = $diary_count;
		
		return $OpenContextItem;
	}//end function



	/*
	 This only applies to Media items that have images. it gets the image size and 
	creates a property based on the number of pixels for the full sized image
	*/
	public static function XMLtoImageSizeData($OpenContextItem, $mediaItem, $parentItems = false){
		
		$baseParents = array();
		if($parentItems != false){
			if(is_array($parentItems)){
				foreach($parentItems as $parentItem){
					$baseParents[] = $parentItem; //add the array to the base parent
				}
			}
			else{
				$baseParents[] = $parentItems; //add the string as the one parent item
			}
		}
		
		$pixels = false;
		foreach ($mediaItem->xpath("//arch:externalFileInfo/oc:FileInfo/oc:ImageSize") as $media_result){
			$pixels = $media_result."";
		}
		if(!$pixels){
			$full_media_uri = false;
			foreach ($mediaItem->xpath("//arch:externalFileInfo/arch:resourceURI") as $media_result){
				$full_media_uri = $media_result."";
			}
			$image_size_array = false;
			if($full_media_uri != false){
				@$image_size_array = getimagesize($full_media_uri);
			}
			if($image_size_array){
				$pixels = $image_size_array[0]*$image_size_array[1];
			}
		}
		
		$image_size = "Not determined";
		if($pixels > 4000000){
			$image_size = "Extra Large";
			$OpenContextItem->interestScore = $OpenContextItem->interestScore + 16;
		}
		elseif(($pixels > 2000000)&&($pixels <= 3999999)){
			$image_size = "Large";
			$OpenContextItem->interestScore = $OpenContextItem->interestScore + 8;
		}
		elseif(($pixels > 750000)&&($pixels <= 1999999)){
			$image_size = "Medium";
			$OpenContextItem->interestScore = $OpenContextItem->interestScore + 4;
		}
		elseif(($pixels > 40000)&&($pixels <= 649999)){
			$image_size = "Small";
			$OpenContextItem->interestScore = $OpenContextItem->interestScore + 1;
		}
		elseif(($pixels > 1)&&($pixels <= 39999)){
			$image_size = "Tiny";
		}
		
		//add data as a property "Image Size"
		$OpenContextItem->addProperty("Image Size", $baseParents, 'nominal');
		$parentArray = $baseParents;
		$parentArray[] = "Image Size";
		$OpenContextItem->addProperty($image_size, $parentArray, 'nominal');
		
		$taxonomyArray = $parentArray;
		$taxonomyArray[] = $image_size;
		$OpenContextItem->addfullPropertyPath($taxonomyArray); //add taxonomy path
		
		return $OpenContextItem;
		
	}//end function


	/*This gets people linked to a given item
	*/
	public static function XMLtoPersonLinksData($OpenContextItem, $xmlItem){
		//person links from the media item directly
		if($xmlItem->xpath("//arch:links/oc:person_links/oc:link")){
			foreach ($xmlItem->xpath("//arch:links/oc:person_links/oc:link") as $person_link) {
				foreach ($person_link->xpath("oc:name") as $person_name) {
					$person_name = (string)$person_name;
					$OpenContextItem->addSimpleArrayItem($person_name, "linkedPersons");
				}
				foreach ($person_link->xpath("oc:relation") as $relation) {
					//$dynamic_field_name = OpenContext_SolrIndexer::solrEscape($person_name) . "_person";
					//$solrDocument->setMultiValue($dynamic_field_name, $relation);
					//echo $dynamic_field_name . ": " . $relation;
					//echo "<br/>";
				}
			}
		}
		
		return $OpenContextItem;
	} //end function

	
	//Add Tagging, View Counts, and External Refs data to OpenContextItem for Solr indexing
	public static function XMLtoSocialData($OpenContextItem, $xmlItem){
	
		if ($xmlItem->xpath("//oc:social_usage/oc:user_tags/oc:tag")) {
			foreach ($xmlItem->xpath("//oc:social_usage/oc:user_tags/oc:tag[@type='text' and @status='public']") as $tag) { // loop through and get the tags. 
				foreach ($tag->xpath("oc:name") as $user_tag) {
					$user_tag = (string)$user_tag;
					$OpenContextItem->addSimpleArrayItem($user_tag, "userTags");
				}
				foreach ($tag->xpath("oc:tag_creator/oc:creator_name") as $tag_creator_name) {
					$tag_creator_name = (string)$tag_creator_name;
					$OpenContextItem->addSimpleArrayItem($tag_creator_name, "userTagCreators");
				}
				foreach ($tag->xpath("oc:tag_creator/oc:set_label") as $tag_set_label) {
					//$solrDocument->setMultiValue("tag_set_label", $tag_set_label);
				}	
			}
		}
	
	
		$item_views = 0; // used to help calculate interest_score
		foreach ($xmlItem->xpath("//oc:social_usage/oc:item_views/oc:count") as $item_views) {
			$item_views = (string)$item_views;
			$item_views = $item_views + 0;
		}
		
		$ext_refs = 0; // used to help calculate interest_score
		if ($xmlItem->xpath("//oc:social_usage/oc:external_references")) {		
			foreach ($xmlItem->xpath("//oc:social_usage/oc:external_references/oc:reference/oc:name") as $external_reference) {
				$external_reference = (string)$external_reference;
				$ext_refs++;  // used to help calculate interest_score
			}
	
		}
	
		$OpenContextItem->socialUse = array("item_views"=>$item_views, "ext_refs"=> $ext_refs);
		return $OpenContextItem;
	}//end function
	
	
	//Add GeoSpatial data to OpenContextItem for Solr indexing
	public static function XMLtoGeoData($OpenContextItem, $xmlItem){
	
		foreach ($xmlItem->xpath("//oc:metadata/oc:geo_reference") as $geo_reference) {
			foreach ($geo_reference->xpath("oc:geo_lat") as $geo_lat) {
				$geoLat = (string)$geo_lat;
			}
			foreach ($geo_reference->xpath("oc:geo_long") as $geo_long) {
				$geoLon = (string)$geo_long;
			}
			
			$geoSpecificity = false;
			foreach ($geo_reference->xpath("oc:metasource/@specificity") as $specificity) {
				$geoSpecificity = (string)$specificity;
			}
			
			$OpenContextItem->addGeo($geoLat, $geoLon, $geoSpecificity);
		}
		
		// check for inherited geospatial data and for polygons 
		if ($xmlItem->xpath("//oc:metadata/oc:geo_reference/oc:metasource[@ref_type='self']")) {
			$OpenContextItem->geoSelf = true; // this value is used to calculate interesting_score.
			
			if ($xmlItem->xpath("//oc:metadata/oc:geo_reference/gml:Polygon/gml:exterior/gml:LinearRing/gml:posList")) {
				$OpenContextItem->geoPoly= true; // this value is used to calculate interesting_score. and also in the Atom generation code
				foreach ($xmlItem->xpath("//oc:metadata/oc:geo_reference/gml:Polygon/gml:exterior/gml:LinearRing/gml:posList") as $polygon_pos_list ) {
					//echo "polygon_pos_list: " . $polygon_pos_list;
					//echo "<br/>";
				}
			}
		}
	
		return $OpenContextItem;
	}//end function
	
	
	//Add Chronological data to OpenContextItem for Solr indexing
	public static function XMLtoChronoData($OpenContextItem, $xmlItem){
		
		if($xmlItem->xpath("//oc:social_usage/oc:user_tags/oc:tag")) {
			foreach ($xmlItem->xpath("//oc:social_usage/oc:user_tags/oc:tag[@type='chronological' and @status='public']") as $chrono_tag) {
				foreach ($chrono_tag->xpath("oc:chrono/oc:time_start") as $time_start) {
					$timeStart = $time_start +0;
				}
				foreach ($chrono_tag->xpath("oc:chrono/oc:time_finish") as $time_end) {
					$timeEnd = $time_end +0;
				}
				foreach ($chrono_tag->xpath("oc:tag_creator/oc:creator_name") as $chrono_creator_name) {
					$chrono_creator_name = $chrono_creator_name."";
				}
				foreach ($chrono_tag->xpath("oc:tag_creator/oc:set_label") as $chrono_set_label) {
					$chrono_set_label = $chrono_set_label."";
				}
				$OpenContextItem->addChrono($timeStart, $timeEnd, $chrono_creator_name, $chrono_set_label);
			}
		}
		
		return $OpenContextItem;
	}//end function	
	
	
	

	
	//Add Dublin Core Metadata to OpenContextItem for Solr indexing
	public static function XMLtoMetadata($OpenContextItem, $xmlItem){
		
		foreach ($xmlItem->xpath("//oc:metadata/dc:creator") as $meta) {
			$OpenContextItem->addSimpleArrayItem($meta."", "creators"); //add metadata
		}
	
		foreach ($xmlItem->xpath("//oc:metadata/dc:contributor") as $meta) {
			$OpenContextItem->addSimpleArrayItem($meta."", "contributors"); //add metadata
		}
	
		foreach ($xmlItem->xpath("//oc:metadata/dc:subject") as $meta) {
			$OpenContextItem->addSimpleArrayItem($meta."", "subjects"); //add metadata
		}
	
		foreach ($xmlItem->xpath("//oc:metadata/dc:coverage") as $meta) {
			$OpenContextItem->addSimpleArrayItem($meta."", "coverages"); //add metadata
		}
	
		foreach ($xmlItem->xpath("//oc:metadata/oc:copyright_lic/oc:lic_URI") as $license_uri) {
			$OpenContextItem->license = $license_uri."";
		}
	
		foreach ($xmlItem->xpath("//oc:metadata/oc:linkedData") as $linkedData) {
			$relationURI = false;
			$targetURI = false;
			foreach ($linkedData->xpath("//oc:relationLink/@href") as $relationURI) {
				$relationURI = $relationURI."";
			}
			foreach ($linkedData->xpath("//oc:targetLink/@href") as $targetURI) {
				$targetURI = $targetURI."";
			}
			foreach ($linkedData->xpath("//oc:targetLink/@href") as $targetURI) {
				$targetURI = $targetURI."";
			}
			
			$LinkNote = "";
			foreach ($linkedData->xpath("//oc:relationLink/oc:vocabulary") as $vocab) {
				$LinkNote .= (string)$vocab."";
			}
			foreach ($linkedData->xpath("//oc:relationLink/oc:label") as $label) {
				$LinkNote .= (string)$label."";
			}
			foreach ($linkedData->xpath("//oc:targetLink/oc:vocabulary") as $vocab) {
				$LinkNote .= (string)$vocab."";
			}
			foreach ($linkedData->xpath("//oc:targetLink/oc:label") as $label) {
				$LinkNote .= (string)$label."";
			}
			
			if($relationURI != false && $targetURI != false){
				$OpenContextItem->addURIreference($relationURI, $targetURI);
				$OpenContextItem->addSimpleArrayItem($LinkNote, "alphaNotes"); //add notes
			}
		}
		
		return $OpenContextItem;
	
	}//end reindex function
	
	
	
	
	/*
	This is somewhat confusing, since media items often lack much description / metadata of their own.
	To deal with this, it's good to enable searches based on descriptions of items that are linked to a media item
	*/
	public static function XMLtoLinkedSpatialProps($OpenContextItem, $xmlItem, $parentItems = false, $obsPrefix = false){
		
		$baseParents = array();
		if($parentItems != false){
			if(is_array($parentItems)){
				foreach($parentItems as $parentItem){
					$baseParents[] = $parentItem; //add the array to the base parent
				}
			}
			else{
				$baseParents[] = $parentItems; //add the string as the one parent item
			}
		}
		
		$baseParents[] = "Description of Linked Items"; // taxonomic root for properties based on linked items
		
		$linksData = array();
		foreach ($xmlItem->xpath("//arch:links/arch:docID") as $links_result){
			$linkUUID = (string)$links_result;
			foreach ($links_result->xpath("@type") as $sub_result){
				$targ_type = (string)$sub_result;
			}
			if(stristr($targ_type, "spatial")){
				$linksData[] = 	$linkUUID;
			}
			if(count($linksData)>= self::maxRelatedLinks){
				break; //stop after you've reached the max number to index
			}
		}
		
		foreach($linksData as $linkUUID){
			$linkedSubject = new Subject;
			$linkedXMLstring = $linkedSubject->getItemXML($linkUUID);
			@$LinkItemXML = simplexml_load_string($linkedXMLstring);
			unset($linkedSubject);
			unset($linkedXMLstring);
			if($LinkItemXML){
				$LinkItemXML->registerXPathNamespace("arch", OpenContext_OCConfig::get_namespace("arch", "spatial"));
				$LinkItemXML->registerXPathNamespace("oc", OpenContext_OCConfig::get_namespace("oc", "spatial"));
				$OpenContextItem = OpenContext_XMLtoOpenContextItem::XMLtoProperties($OpenContextItem, $LinkItemXML, $baseParents, $obsPrefix, true);
			}
		}//end loop through spatial links

		return $OpenContextItem;
	}//end function
	
	
	
	
	
	
	
	
	public static function get_oc_spatial_xml($uuid){
		
		$db_params = OpenContext_OCConfig::get_db_config();
		$db = new Zend_Db_Adapter_Pdo_Mysql($db_params);
		$db->getConnection();
		
		$uri = "http://opencontext.org/subjects/".$uuid;
		$sql = "SELECT xml FROM sitedata1 WHERE uri = '$uri' LIMIT 1";
		
		$result = $db->fetchAll($sql, 2);
		if(!$result){
			$sql = "SELECT xml FROM sitedata2 WHERE uri = '$uri' LIMIT 1";
			$result = $db->fetchAll($sql, 2);
			if(!$result){
				$sql = "SELECT xml FROM sitedata3 WHERE uri = '$uri' LIMIT 1";
				$result = $db->fetchAll($sql, 2);
				if(!$result){
					$sql = "SELECT xml FROM sitedata4 WHERE uri = '$uri' LIMIT 1";
					$result = $db->fetchAll($sql, 2);
				}
			}
		}
		
		if($result){
			return $result[0]["xml"];
		}
		else{
			@$linkedXMLstring = file_get_contents($uri.".xml");
			return $linkedXMLstring;
		}
		
	}//end function
	
	/*
	This is somewhat confusing, since media items often lack much description / metadata of their own.
	To deal with this, it's good to enable searches based on descriptions of items that are linked to a media item
	
	this looks for xml data from the site data tables, it's meant for reindexing the live site.
	
	*/
	public static function oc_XMLtoLinkedSpatialProps($OpenContextItem, $xmlItem, $parentItems = false, $obsPrefix = false){
		
		$baseParents = array();
		if($parentItems != false){
			if(is_array($parentItems)){
				foreach($parentItems as $parentItem){
					$baseParents[] = $parentItem; //add the array to the base parent
				}
			}
			else{
				$baseParents[] = $parentItems; //add the string as the one parent item
			}
		}
		
		$baseParents[] = "Description of Linked Items"; // taxonomic root for properties based on linked items
		
		$linksData = array();
		foreach ($xmlItem->xpath("//arch:links/arch:docID") as $links_result){
			$linkUUID = (string)$links_result;
			foreach ($links_result->xpath("@type") as $sub_result){
				$targ_type = (string)$sub_result;
			}
			if(stristr($targ_type, "spatial")){
				$linksData[] = 	$linkUUID;
			}
			if(count($linksData)>= self::maxRelatedLinks){
				break; //stop after you've reached the max number to index
			}
		}
		
		foreach($linksData as $linkUUID){
			
			/*
			$linkedSubject = new Subject;
			$linkedXMLstring = $linkedSubject->getItemXML($linkUUID);
			*/
			$linkedXMLstring = OpenContext_XMLtoOpenContextItem::get_oc_spatial_xml($linkUUID);
			@$LinkItemXML = simplexml_load_string($linkedXMLstring);
			unset($linkedSubject);
			unset($linkedXMLstring);
			if($LinkItemXML){
				$LinkItemXML->registerXPathNamespace("arch", OpenContext_OCConfig::get_namespace("arch", "spatial"));
				$LinkItemXML->registerXPathNamespace("oc", OpenContext_OCConfig::get_namespace("oc", "spatial"));
				$OpenContextItem = OpenContext_XMLtoOpenContextItem::XMLtoProperties($OpenContextItem, $LinkItemXML, $baseParents, $obsPrefix, true);
			}
		}//end loop through spatial links

		return $OpenContextItem;
	}//end function
	
	
	
	//this adds properties to the OpenContextItem for Solr Indexing
	public static function XMLtoProperties($OpenContextItem, $xmlItem, $parentItems = false, $obsPrefix = false, $linkedSpatial = false){
		
		//$linkedSpatial is boolean, if true, then the $xmlItem being queried is from a linked spatial item.
		
		//the base parent is an array that contains items that are parents in the taxonomy
		//for example, a media item may be described by a linked spatial item, it which case
		//the parent items may be "Linked Subject" or something similar.
		$baseParents = array();
		if($parentItems != false){
			if(is_array($parentItems)){
				$OpenContextItem->addProperty($parentItems[0], false, 'nominal'); //add the variable name as top level prop
				foreach($parentItems as $parentItem){
					$baseParents[] = $parentItem; //add the array to the base parent
				}
			}
			else{
				$baseParents[] = $parentItems; //add the string as the one parent item
				$OpenContextItem->addProperty($parentItems, false, 'nominal'); //add the variable name as top level prop
			}
		}
		
		
		$note = "";
		
		// Properties and variables are found in different places, sometimes in observations, sometimes not
		if(!$obsPrefix){
			//use preset or default prefixes for querying for properties
			if($OpenContextItem->documentType == "space" || $OpenContextItem->documentType == "spatial" || $linkedSpatial){
				$xpathPrefix = "//arch:spatialUnit/arch:observations/arch:observation[@obsNumber != '100']";
			}
			else{
				$xpathPrefix = "/";
			}
		
		}
		else{
			$xpathPrefix = $obsPrefix;
		}
		
		
		//notes need to be added to the OpenContext item
		if ($xmlItem->xpath("//arch:notes/arch:note")) {
			$stringNote = false;
			$xmlItem->registerXPathNamespace("xhtml", OpenContext_OCConfig::get_namespace("xhtml"));
			if($xmlItem->xpath("//arch:notes/arch:note/arch:string/xhtml:div")){
				foreach ($xmlItem->xpath("//arch:notes/arch:note/arch:string/xhtml:div") as $divNote) {
					$stringNote = $divNote->asXML();
					$OpenContextItem->addSimpleArrayItem($stringNote, "alphaNotes"); //add notes
				}
			}
			else{
				foreach ($xmlItem->xpath("//arch:notes/arch:note/arch:string") as $note) {
					$stringNote = (string)$note;
					$OpenContextItem->addSimpleArrayItem($stringNote, "alphaNotes"); //add notes
				}
			}
		}
		
		
		
		// Verify that there are properties associated with this item.
		if ($xmlItem->xpath($xpathPrefix."/arch:properties/arch:property")) {
			
			foreach ($xmlItem->xpath($xpathPrefix."/arch:properties/arch:property/oc:var_label[@type='nominal']") as $var_label) {
				foreach ($var_label->xpath(".") as $var_act) {
					$var_act = $var_act."";
					$OpenContextItem->addProperty($var_act, $baseParents, 'nominal'); //add the variable name as top level prop
					$parentArray = $baseParents;
					$parentArray[] = $var_act;
				}
				
				$propValue = false;
				foreach ($var_label->xpath("../oc:show_val") as $show_val) {
					$propValue = (string)$show_val;
					if(strstr($propValue, self::taxonomyPathDelim)){
						//property has a taxonomy path
						$propValues = explode(self::taxonomyPathDelim, $propValue);
						foreach($propValues as $propValue){
							$OpenContextItem->addProperty($propValue, $parentArray, 'nominal'); //add the value as the next level in the prop
							$parentArray[] = $propValue;
						}
						$taxonomyArray = $parentArray;
						$OpenContextItem->addfullPropertyPath($taxonomyArray); //add taxonomy path
					}
					else{
						//property has no taxonomy paths 
						$OpenContextItem->addProperty($propValue, $parentArray, 'nominal'); //add value as the next level in the prop
						$taxonomyArray = $parentArray;
						$taxonomyArray[] = $propValue;
						$OpenContextItem->addfullPropertyPath($taxonomyArray); //add taxonomy path
					}
				}
				
				
				/*
				if($propValue != false){
						//this section enables reconcilation service searches,
						//by associating a property value with a URI to a widely used link relation (e.g. "has biological taxonomy")
						$relationURI = false;
						foreach ($var_label->xpath("../oc:linkedData") as $linkedData) {
								foreach ($linkedData->xpath("oc:relationLink/@href") as $relationURI) {
										$relationURI = $relationURI."";
								}
						}
						if($relationURI != false){
								$OpenContextItem->addProperty($relationURI, $baseParents, 'reconciled', false); //add the relation URI as a top-level taxon, type reconciled
								$parentArrayRecon = $baseParents;
								$parentArrayRecon[] = $relationURI;
								$OpenContextItem->addProperty($propValue, $parentArrayRecon, 'nominal', false); //add the relation URI as a top-level taxon, type reconciled
								unset($parentArrayRecon);
						}
				}
				*/
			}
	
			foreach ($xmlItem->xpath($xpathPrefix."/arch:properties/arch:property/oc:var_label[@type='calendar' or @type='calendric']") as $var_label) {
				
				foreach ($var_label->xpath(".") as $var_act) {
					$var_act = (string)$var_act;
					$show_val = $var_act;
					$OpenContextItem->addProperty($var_act, $baseParents, 'calendric'); //add the variable name as top level prop
					$parentArray = $baseParents;
					$parentArray[] = $var_act;
				}
				$show_val = false;
				foreach ($var_label->xpath("../arch:date") as $cal_val) {
					$show_val = (string)$cal_val;
					$OpenContextItem->addProperty($show_val, $parentArray, 'calendric');
				}
				if(!$show_val){
					foreach ($var_label->xpath("../oc:show_val") as $cal_val) {
						$show_val = (string)$cal_val;
						$OpenContextItem->addProperty($show_val, $parentArray, 'calendric');
					}	
				}
				
				$taxonomyArray = $parentArray;
				$taxonomyArray[] = $show_val;
				$OpenContextItem->addfullPropertyPath($taxonomyArray); //add taxonomy path
			}
	
			// check for integer variables 
			foreach ($xmlItem->xpath($xpathPrefix."/arch:properties/arch:property/oc:var_label[@type='integer']") as $var_label) {
				
				foreach ($var_label->xpath(".") as $var_act) {
					$var_act = $var_act."";
					$OpenContextItem->addProperty($var_act, $baseParents, 'integer'); //add the variable name as top level prop
					$parentArray = $baseParents;
					$parentArray[] = $var_act;
				}
				
				$refStandardUnit = false; //is there a standard unit of measurement referenced?
				foreach ($var_label->xpath("../arch:integer") as $show_val) {
					
					foreach($show_val->xpath("@href") as $unitURI){
						$unitURI = $unitURI."";
						$mesObj = new MeasurementUnits;
						$mArray = $mesObj->URI_toUnit($unitURI);
						unset($mesObj);
						$unitType = $mArray["sType"];
						unset($mArray);
						$refStandardUnit = true;
					}
					
					if($refStandardUnit){
						$unitName = false;
						foreach($show_val->xpath("@name") as $unitName){
							$unitName = $unitName."";
						}
						
						if(!$unitName != false){
							$refStandardUnit = false;	
						}
					}
					
					
					$show_val = $show_val."";
					if(is_numeric($show_val)){
						if(round($show_val,0) == $show_val){
							$good_int = true;
						}
					}
					else{
						$good_int = false;
					}
					// test whether $show_val's value is non-null and really is an integer. It may be neither.
					if ((($show_val && (intval($show_val) === $show_val)))||($good_int)) {
						// $show_val is non-null and is an integer
						$OpenContextItem->addProperty($show_val, $parentArray, 'integer'); //add the variable name as top
						
						if($refStandardUnit){
							$OpenContextItem->addProperty($unitType, $baseParents, 'standard', false); //add the standard measurement type as top level prop
							$parentArrayMes = $baseParents;
							$parentArrayMes[] = "[[standard]]";
							$parentArrayMes[] = $unitType;
							if($unitType != "Count"){
								$OpenContextItem->addProperty($unitName, $parentArrayMes, 'nominal' , false); //add the standard measurement name 
								$parentArrayMes[] = $unitName;
							}
							
							//now add the standard measurement to allow comparison of all values using the standard, no matter what variable name
							$OpenContextItem->addProperty($show_val, $parentArrayMes, 'integer' , false);
							
							//now add the variable names, to specify what variable is used of those that are in the same standard.
							$OpenContextItem->addProperty($var_act, $parentArrayMes, 'nominal' , false);
							$parentArrayMes[] = $var_act;
							
							//now add the value again
							$OpenContextItem->addProperty($show_val, $parentArrayMes, 'integer' , false);
						}
						
					} else {
						// $show_val is not an intger
						$note = $var_act . " " . $show_val;
						$OpenContextItem->addSimpleArrayItem($note, "alphaNotes"); //add notes
						
					}
				}
				
				$taxonomyArray = $parentArray;
				$taxonomyArray[] = $show_val;
				$OpenContextItem->addfullPropertyPath($taxonomyArray); //add taxonomy path
			}
	
			// get the decimal variable
			foreach ($xmlItem->xpath($xpathPrefix."/arch:properties/arch:property/oc:var_label[@type='decimal']") as $var_label) {
				
				$refStandardUnit = false; //is there a standard unit of measurement referenced?
				foreach ($var_label->xpath(".") as $var_act) {
					$var_act = $var_act."";
					$OpenContextItem->addProperty($var_act, $baseParents, 'decimal'); //add the variable name as top level prop
					$parentArray = $baseParents;
					$parentArray[] = $var_act;
				}
				
				foreach ($var_label->xpath("../arch:decimal") as $show_val) {
					// make sure the value is numeric (so it can be indexed as a float).
					
					
					foreach($show_val->xpath("@href") as $unitURI){
						$unitURI = $unitURI."";
						$mesObj = new MeasurementUnits;
						$mArray = $mesObj->URI_toUnit($unitURI);
						unset($mesObj);
						$unitType = $mArray["sType"];
						unset($mArray);
						$refStandardUnit = true;
					}
					
					if($refStandardUnit){
						$unitName = false;
						foreach($show_val->xpath("@name") as $unitName){
							$unitName = $unitName."";
						}
						
						if(!$unitName != false){
							$refStandardUnit = false;	
						}
					}
					
					$show_val = $show_val."";
					
					if(is_numeric($show_val)){
						$good_dec = true;
					}
					else{
						$good_dec = false;
					}
					
					if ((is_numeric((string) ($show_val)))||($good_dec)) {
						$OpenContextItem->addProperty($show_val, $parentArray, 'decimal'); //add the variable name as top
						
						if($refStandardUnit){
							$OpenContextItem->addProperty($unitType, $baseParents, 'standard' , false); //add the standard measurement type as top level prop
							$parentArrayMes = $baseParents;
							$parentArrayMes[] = "[[standard]]";
							$parentArrayMes[] = $unitType;
							if($unitType != "Count"){
								$OpenContextItem->addProperty($unitName, $parentArrayMes, 'nominal' , false); //add the standard measurement name 
								$parentArrayMes[] = $unitName;
							}
							
							//now add the standard measurement to allow comparison of all values using the standard, no matter what variable name
							$OpenContextItem->addProperty($show_val, $parentArrayMes, 'decimal' , false);
							
							//now add the variable names, to specify what variable is used of those that are in the same standard.
							$OpenContextItem->addProperty($var_act, $parentArrayMes, 'nominal' , false);
							$parentArrayMes[] = $var_act;
							
							//now add the value again
							$OpenContextItem->addProperty($show_val, $parentArrayMes, 'decimal' , false);
						}
						
					} else {
						// if the value does not validate as numeric, index it as a notes field
						$note = $var_act . " " . $show_val;
						$OpenContextItem->addSimpleArrayItem($note, "alphaNotes"); //add notes
					}
	
				}
				
				foreach ($xmlItem->xpath($xpathPrefix."/arch:properties/arch:property/oc:var_label[@type='alphanumeric']") as $var_label) {
					foreach ($var_label->xpath(".") as $var_alpha) {
							//$OpenContextItem->addSimpleArrayItem($var_alpha."", "alphaNotes"); //add notes
					}
					foreach ($var_label->xpath("../oc:show_val") as $show_val) {
						$OpenContextItem->addAlphaVarVal($var_alpha."", $show_val."");
					}
				}//end loop through alpha_numeric variables
				
				
				$taxonomyArray = $parentArray;
				$taxonomyArray[] = $show_val;
				$OpenContextItem->addfullPropertyPath($taxonomyArray); //add taxonomy path
			}
	
			foreach ($xmlItem->xpath($xpathPrefix."/arch:properties/arch:property/oc:var_label[@type='ordinal']") as $var_label) {
				foreach ($var_label->xpath(".") as $var_act) {
					$var_act = $var_act."";
					$OpenContextItem->addProperty($var_act, $baseParents, 'nominal'); //add the variable name as top level prop
					$parentArray = $baseParents;
					$parentArray[] = $var_act;
				}
				foreach ($var_label->xpath("../oc:show_val") as $show_val) {
					$show_val = (string)$show_val;
					$OpenContextItem->addProperty($show_val, $parentArray, 'nominal'); //add the variable name as top level prop
				}
				
				$taxonomyArray = $parentArray;
				$taxonomyArray[] = $show_val;
				$OpenContextItem->addfullPropertyPath($taxonomyArray); //add taxonomy path
			}
	
			foreach ($xmlItem->xpath($xpathPrefix."/arch:properties/arch:property/oc:var_label[@type='boolean']") as $var_label) {
				foreach ($var_label->xpath(".") as $var_act) {
					$var_act = $var_act."";
					$OpenContextItem->addProperty($var_act, $baseParents, 'nominal'); //add the variable name as top level prop
					$parentArray = $baseParents;
					$parentArray[] = $var_act;
				}
				foreach ($var_label->xpath("../oc:show_val") as $show_val) {
					$OpenContextItem->addProperty($show_val."", $parentArray, 'nominal'); //add the variable name as top level prop
				}
				
				$taxonomyArray = $parentArray;
				$taxonomyArray[] = $show_val;
				$OpenContextItem->addfullPropertyPath($taxonomyArray); //add taxonomy path
			}
			
			$linkedDataArray = array();
			foreach ($xmlItem->xpath($xpathPrefix."/arch:properties/arch:property/oc:linkedData") as $linkedData) {
				$relationURI = false;
				$targetURI = false;
				foreach ($linkedData->xpath(".//oc:relationLink/@href") as $relationURIxpath) {
					$relationURI = (string)$relationURIxpath;
				}
				if($linkedData->xpath(".//oc:targetLink/@href")){
					if(!array_key_exists($relationURI, $linkedDataArray)){
						$linkedDataArray[$relationURI] = array();
					}
					foreach ($linkedData->xpath(".//oc:targetLink/@href") as $targetURI) {
						$targetURI = $targetURI."";
						if($relationURI != false && $targetURI != false){
							if(!in_array($targetURI, $linkedDataArray[$relationURI])){
								$linkedDataArray[$relationURI][] = $targetURI;
								//$OpenContextItem->addURIreference($relationURI, $targetURI);
							}
						}
					}
				}
				$LinkNote = "";
				foreach ($linkedData->xpath(".//oc:relationLink/oc:vocabulary") as $vocab) {
					$LinkNote .= (string)$vocab." ";
				}
				foreach ($linkedData->xpath(".//oc:relationLink/oc:label") as $label) {
					$LinkNote .= (string)$label." ";
				}
				foreach ($linkedData->xpath(".//oc:targetLink/oc:vocabulary") as $vocab) {
					$LinkNote .= (string)$vocab." ";
				}
				foreach ($linkedData->xpath(".//oc:targetLink/oc:label") as $label) {
					$LinkNote .= (string)$label." ";
				}
				
				if($relationURI != false && $targetURI != false){
					$OpenContextItem->addSimpleArrayItem($LinkNote, "alphaNotes"); //add notes
				}
			}
			
			//echo print_r($linkedDataArray);
			//die;
			
			if(count($linkedDataArray)>0){
				$hierObj = new Facets_Hierarchy;
				foreach($linkedDataArray as $relationURI => $targArray){
					$actTargArray = $targArray;
					$vocabType = $hierObj->getVocabTypeFromRelation($relationURI);
					if($vocabType  != false){
						//if the relation URI is a property used for a hierarchic taxonomy,
						//do this to remove target URIs to entities higher up in the hierarchy. We'll
						//only index the most specific classification
						$targParentCounts = array();
						foreach($targArray as $targURI){
							$targParentCounts[$targURI] = count($hierObj->getListParentURIs($targURI));
						}
						arsort($targParentCounts); //sort from biggest to largest, largest has the most parents, is deepest in the hierarchy
						unset($actTargArray);
						$actTargArray = array();
						foreach($targParentCounts as $targetURI => $count){
							$OpenContextItem->addURIreference($relationURI, $targetURI);
							break;
						}
					}
					else{
						foreach($targArray as $targetURI){
							$OpenContextItem->addURIreference($relationURI, $targetURI);
						}
					}
				}
				unset($hierObj);
			}
			
	
	
		}
		
		return $OpenContextItem;
	}
	
	
	
}//end class declaration

?>
