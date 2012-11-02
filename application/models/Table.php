<?php


//this class interacts with the database for accessing and changing table items
class Table {
    
    public $noid;
    public $id; //internal table cache ID
    public $tableID; //user visible id, part of the table URL
    public $tableData; //results of the query to get the table
    public $atomEntry; //atom entry
    public $label;
    public $jsonFile; //file for backup JSON
    public $metadata; //table metadata
    
    public $jsonData; //data, in a JSON string, for the table
    public $solrIndexingError; //error in solr indexing
    
    
    //get User data from database
    function getByID($tableId){
        
        $id = $this->security_check($tableId);
        $output = false; //no user
        $db_params = OpenContext_OCConfig::get_db_config();
        $db = new Zend_Db_Adapter_Pdo_Mysql($db_params);
		  $db->getConnection();
			
		  if(stristr($tableId, "/")){
			  $id = OpenContext_TableOutput::tableURL_toCacheID($tableId);
		  }
		  elseif(stristr($tableId, "_")){
			  $id = $tableId;
			  $tableId = OpenContext_TableOutput::tableID_toURL($id);
		  }
		  else{
			  $id = $tableId;
		  }
		
			$sql = 'SELECT *
					FROM dataset
					WHERE cache_id = "'.$id.'"
					OR cache_id = "'.$tableId.'"
					LIMIT 1';
		
        $result = $db->fetchAll($sql, 2);
        if($result){
            
				$this->tableData = $result[0];
				$this->id = $id;
				$this->tableID = $tableId;
				$this->label = $result["0"]["table_name"];
				$this->jsonFile = $result["0"]["json_file"];
				$this->metadata = $result[0]["metadata"];
				 
				$output = true;
				$this->noid = false;
				$this->noidCheck($db);
        }
        
		  $db->closeConnection();
    
        return $output;
    }
    
    
    function noidCheck($db){
		  $id = $this->id;
		  $tableId = $this->tableID;
		  $output = false;
		  
		  $sql = 'SELECT noid
							FROM noid_bindings
							WHERE itemUUID = "'.$id.'"
			  OR itemUUID = "'.$tableId.'"
							LIMIT 1';
			  
				 $result = $db->fetchAll($sql, 2);
				 if($result){
				$noid = $result[0]["noid"];
				if(strlen($noid)>1){
			  $this->noid = $noid;
			  $output = $noid;
				}
		  }
		  
		  return $output;
	
    }
    
    
    
    
    //this function gets an item's Atom entry. It's used for making the general
    //feed read by the CDL's archival services.
    function getItemEntry($id){
		  $this->getByID($id);
		  $this->makeAtomEntry();
		  return $this->atomEntry;
    }
    
    function get_jsonFile(){
	
		  $this->jsonData = false;
		  $tableDir = './tablefiles/';
		  if(!file_exists($tableDir)){
			  $tableDir = '../tablefiles/';
		  }
		  
		  $sFilename  = $tableDir.$this->jsonFile;
		  if(!file_exists($sFilename)){
				return false;
		  }
		  else{
				$fp = fopen($sFilename, 'r');
				$rHandle = fopen($sFilename, 'r');
				if (!$rHandle){
					 return false;
				}
				else{
			  
					 $sData = '';
					 while(!feof($rHandle)){
						  $sData .= fread($rHandle, filesize($sFilename));
					 }
					 fclose($rHandle);
					 $this->jsonData = $sData;
					 //$this->metadata_fix();
					 unset($rHandle);
					 return true;
				} 
		  }
        
	 }   //end function 
    
    function get_json_urlFile(){
	
		  $this->jsonData = false;
		  $sFilename  = './tablefiles/'.$this->jsonFile;
		  @$file = file_get_contents($sFilename);
		  if($file){
				$this->jsonData = $file;
				unset($file);
				return true;
		  }
		  else{
				return false;
		  }
    }   
    
    
    function metadata_fix(){
		  $oldMetadata = $this->metadata;
		  $jsonDataFile = $this->jsonData;
		  $oldMeta = Zend_Json::decode($oldMetadata);
		  $newData = Zend_Json::decode($jsonDataFile);
		  
		  $realRecords = count($newData["records"]);
		  
		  if($oldMeta['meta']['numFound'] != $newData['meta']['numFound']){
				
				$totalRecords = $newData['meta']['numFound'];
				$oldMeta['meta']['numFound'] = $totalRecords;
				
				$newString = Zend_Json::encode($oldMeta);
				unset($newData);
				unset($oldMeta);
				
				$db_params = OpenContext_OCConfig::get_db_config();
				$db = new Zend_Db_Adapter_Pdo_Mysql($db_params);
				$db->getConnection();
				
				$data = array(		"metadata" => $newString,
									 "num_records" => $totalRecords
					 );
				
				$where = array();
				$where[] = "cache_id = '".$this->tableID."' ";
				$where[] = "current != 'no' ";
				$db->update("dataset", $data, $where);
				
				$db->closeConnection();
		  }
    }
    
    function getTableJSON($tableId){
	
		  $Final_frontendOptions = array('lifetime' => NULL,'automatic_serialization' => true );
		  $Final_backendOptions = array('cache_dir' => './tablecache/' );
		  $Final_cache = Zend_Cache::factory('Core','File',$Final_frontendOptions,$Final_backendOptions);
		  $cache_result = false;
		  if($cache_result = $Final_cache->load($tableId )){
				$this->jsonData = (string)$cache_result;
		  }
		  else{
				if(!$this->jsonFile){
					 $this->jsonFile = $tableId.".json";
				}
				
				$this->get_jsonFile();
		  }
	
    }
    
    
    
    //indexes tables using solr
    function solrIndex($OpenContextItem){
	
		  $this->metadata_fix();
		  $this->solrIndexingError = false;
		  
		  $tableData = Zend_Json::decode($this->jsonData);
		  
		  
		  $OpenContextItem->itemLabel = $tableData["meta"]["table_name"];
		  if(strlen($OpenContextItem->itemLabel)<2){
			  $OpenContextItem->itemLabel = "[[Table not titled]]";
		  }
		  
		  $OpenContextItem->itemUUID = $this->tableID; //user visible part
		  $OpenContextItem->documentType = "table";
		  $OpenContextItem->projectUUID = "tables";
		  $OpenContextItem->projectName = "Open Context Tables";
		  
		  $OpenContextItem->interestScore = 1;
		  $OpenContextItem->docLinkNum = 0;
		  $OpenContextItem->otherLinkNum = 0;
		  $OpenContextItem->imageLinkNum = 0;
		  
		  //data published date
		  if(isset($tableData["meta"]["setLastPublished"])){
				$value = date("Y-m-d\TH:i:s\Z", strtotime($tableData["meta"]["setLastPublished"]));
				$OpenContextItem->pubDate = $value;
		  }
		  elseif(isset($tableData["meta"]["TabCreated"])){
				$value = date("Y-m-d\TH:i:s\Z", strtotime($tableData["meta"]["TabCreated"]));
				$OpenContextItem->pubDate = $value;
		  }
		  else{
				$value = date("Y-m-d\TH:i:s\Z");
				$OpenContextItem->pubDate = $value;
		  }
		  
		  //data update date
		  if(isset($tableData["meta"]["setLastUpdate"])){
				$value = date("Y-m-d\TH:i:s\Z", strtotime($tableData["meta"]["setLastUpdate"]));
				$OpenContextItem->update = $value;
		  }
		  elseif(isset($tableData["meta"]["TabCreated"])){
				$value = date("Y-m-d\TH:i:s\Z", strtotime($tableData["meta"]["TabCreated"]));
				$OpenContextItem->update = $value;
		  }
		  else{
				$value = date("Y-m-d\TH:i:s\Z");
				$OpenContextItem->update = $value;
		  }
		  
		  
		  
		  
		  
		  //add related people
		  if(isset($tableData["meta"]["person_links"])){
				foreach($tableData["meta"]["person_links"] as $itemKey => $val){
			  $OpenContextItem->addSimpleArrayItem($itemKey, "linkedPersons");
				}
		  }
		  
		  
		  
		  //add creators
		  if(isset($tableData["meta"]["creators"])){
				foreach($tableData["meta"]["creators"] as $itemKey => $val){
			  $OpenContextItem->addSimpleArrayItem($itemKey, "creators");
				}
		  }
		  
		  
		  //add table creators
		  if(isset($tableData["meta"]["TabCreator"])){
				$OpenContextItem->addSimpleArrayItem($tableData["meta"]["TabCreator"], "userTagCreators");
		  }
		  
		  
		  //add table key words / tags
		  if(isset($tableData["meta"]["tagstring"])){
				$tagArray = explode(" ", $tableData["meta"]["tagstring"]);
				$OpenContextItem->addSimpleArrayItem($tableData["meta"]["tagstring"], "alphaNotes"); //so keywords show up in text searches
				foreach($tagArray  as $tag){
					 if(strlen($tag)>1){
						  $OpenContextItem->addSimpleArrayItem($tag, "userTags");
					 }
				}
		  }
		  
		  
			
		  //add table description
		  if(isset($tableData["meta"]["table_description"])){
				$OpenContextItem->addSimpleArrayItem($tableData["meta"]["table_description"], "alphaNotes");
		  }
		  
		  $numFound = false;
		  //add number of records
		  if(isset($tableData["meta"]["numFound"])){
				$value = $tableData["meta"]["numFound"];
				$numFound = $value;
				$OpenContextItem->addProperty("Number of Records", false, 'integer'); //add the variable name as top level prop
				$OpenContextItem->addProperty($value, array("Number of Records"), 'integer'); //add the value for the prop
				$OpenContextItem->addfullPropertyPath( array("Number of Records", $value) ); //add taxonomy path
		  }
		  
		  //add number of table segments or parts
		  if(isset($tableData["meta"]["table_segments"]["totalTabs"])){
				$value = $tableData["meta"]["table_segments"]["totalTabs"];
				$OpenContextItem->addProperty("Number of Parts", false, 'integer'); //add the variable name as top level prop
				$OpenContextItem->addProperty($value, array("Number of Parts"), 'integer'); //add the value for the prop
				$OpenContextItem->addfullPropertyPath( array("Number of Parts", $value) ); //add taxonomy path
		  }
		  
		  if(isset($tableData["meta"]["table_segments"]["currentTab"])){
				if($tableData["meta"]["table_segments"]["currentTab"] != 1 && $this->tableID != "f83076ff715703b6e501ec5ec3760da4"){
					 $this->solrIndexingError = true;
				}
		  }
		  
		  
		  //add table created time
		  if(isset($tableData["meta"]["TabCreated"])){
				$value = date("Y-m-d\TH:i:s\Z", strtotime($tableData["meta"]["TabCreated"]));
		  }
				 
		  
		  if($this->noid != false){
				$OpenContextItem->addSimpleArrayItem($this->noid, "alphaNotes");
		  }
		  
		  $fieldCount = 0;
		  //add field names / lables used in the table, a handy search criteria
		  if(isset($tableData["table_fields"])){
				$skipFields = array("proj",
					  "person",
					  "def_context_0",
					  "def_context_1",
					  "def_context_2",
					  "def_context_3",
					  "def_context_4",
					  "def_context_5",
					  "def_context_6",
					  "def_context_7",
					  "def_context_8",
					  "pub_date",
					  "update",
					  "category",
					  "label");
				
				$doneSkip = false;
				$OpenContextItem->addProperty("Table Fields", false, 'nominal'); //add the variable name as top level prop
				foreach($tableData["table_fields"] as $field){
					 if(!in_array($field, $skipFields)||$doneSkip){
						  $doneSkip = true; //done with the skiping. some fields may have the same name as default, standard fields
						  $OpenContextItem->addProperty($field, array("Table Fields"), 'nominal'); //add the variable name as top level prop
						  $OpenContextItem->addfullPropertyPath( array("Table Fields", $field) ); //add taxonomy path
					 }
					 $fieldCount++;
				}
				
				$OpenContextItem->addProperty("Number of Fields", false, 'integer'); //add the variable name as top level prop
				$OpenContextItem->addProperty($fieldCount, array("Number of Fields"), 'integer'); //add the value for the prop
				$OpenContextItem->addfullPropertyPath( array("Number of Fields", $fieldCount) ); //add taxonomy path
		  }
		  
		  $OpenContextItem->interestScore = $numFound * $fieldCount;
		  
				 
		  //add projects
		  $projectArray = false;
		  if(isset($tableData["meta"]["projects"])){
				$projectArray = $tableData["meta"]["projects"];
				$OpenContextItem->addProperty("Projects", false, 'nominal'); //add the variable name as top level prop
				foreach($projectArray as $project){
					 $OpenContextItem->addProperty($project, array("Projects"), 'nominal'); //add the variable name as top level prop
					 $OpenContextItem->addfullPropertyPath( array("Projects", $project) ); //add taxonomy path
				}
		  }

		  //now we need to do a query to get faceted search JSON for additional metadata
		  if(isset($tableData["meta"]["setURI"])){
					  
				$setURI = $tableData["meta"]["setURI"];
				$searchURI = str_replace("createtab/setfields/", "sets/", $setURI);
				$searchURI = str_replace("sets/", "sets/facets/", $searchURI);
				if(stristr($searchURI, "?")){
					 $searchURI = str_replace("?", ".json?", $searchURI);
					 if(!stristr($searchURI, "recs=")){
						  $searchURI = $searchURI."&recs=1";
					 }
				}
				else{
					 $searchURI = $searchURI.".json";
					 if(!stristr($searchURI, "recs=")){
						  $searchURI = $searchURI."?recs=1";
					 }
				}
				$searchURI = str_replace("http://opencontext/", "http://opencontext.org/", $searchURI);
				if(!stristr($searchURI,  "http://")){
					 $searchURI = "http://opencontext.org/".$searchURI;
				}
				$searchURI = str_replace("//sets/facets/", "/sets/facets/", $searchURI);
				echo " ".$searchURI." ";
				
				@$searchString = file_get_contents($searchURI);
				if($searchString){
					 echo " here";
					 $searchJSON = Zend_Json::decode($searchString);
					 unset($searchString);
							
					 $containedFilter = false;
					 $containedValue = false;
					 if(is_array($searchJSON["summary"])){
						  //this enables searches on filters used to generate tables.
								 
						  $OpenContextItem->addProperty("Defining Filters", false, 'nominal'); //add the variable name as top level prop
								 
						  foreach($searchJSON["summary"] as $filter){
								$parrentPropArray = array("Defining Filters");
								$filterVar = $filter["filter"];
								$filterVal = $filter["value"];
									  
								if(stristr($filterVar, "contained in")){
									 $containedFilter = true;
									 $containedValue = $filterVal;
								}
				  
								if(stristr($filterVar, "Classified as")){
									 $filterVal = str_replace("::", "-:-", $filterVal);
								}
									  
								$OpenContextItem->addProperty($filterVar, $parrentPropArray, 'nominal');
								$parrentPropArray[] = $filterVar;
								$OpenContextItem->addProperty($filterVal, $parrentPropArray, 'nominal');
									  
								$parrentPropArray[] = $filterVal;
								$OpenContextItem->addfullPropertyPath($parrentPropArray); //add taxonomy path
						  }//end loop
					 }
							
					 if(isset($searchJSON["facets"]["category"])){
						  foreach($searchJSON["facets"]["category"] as $class){
								if(strlen($class["name"]>0)){
									 $OpenContextItem->addSimpleArrayItem($class["name"], "classes");
								}
						  }
					 }
							
					 if(isset($searchJSON["facets"]["date range"])){
						  $minDate = 13600000000; //far future
						  $maxDate = -13600000000; //age of the known universe, nothing should be older (except for Lovecraftian deposits, but then Cthulhu would eat us)
						  foreach($searchJSON["facets"]["date range"] as $dateArray){
								$values = $dateArray["linkQuery"];
								$valArray = explode(" ", $values);
								if($valArray[0] < $minDate){
									 $minDate = $valArray[0];
								}
								if($valArray[1] > $maxDate){
									 $maxDate = $valArray[1];
								}
						  }
						  $OpenContextItem->addChrono($minDate, $maxDate);
					 }
							
							
					 if(isset($searchJSON["facets"]["context"]) && $containedFilter){
						  //get containment data from here, no need for an additional query
						  $sumLat = 0;
						  $sumLon = 0;
						  $geoCount = 0;
						  foreach($searchJSON["facets"]["context"] as $context){
								if(isset($context["geoTime"]["geoLat"]) && $geoCount == 0){
									 //just use the first point for now
									 $sumLat += $context["geoTime"]["geoLat"];
									 $sumLon += $context["geoTime"]["geoLong"];
									 $geoCount++;
								}
						  }
								 
						  if($geoCount>0){
								$useGeoLat = $sumLat / $geoCount;
								$useGeoLon = $sumLon / $geoCount;
								$OpenContextItem->addGeo($useGeoLat, $useGeoLon);
						  }
					 }
					 elseif(is_array($projectArray)){
								 
						  $db_params = OpenContext_OCConfig::get_db_config();
						  $db = new Zend_Db_Adapter_Pdo_Mysql($db_params);
						  $db->getConnection();
						  
						  $projectID = false;
						  foreach($projectArray as $useProject){
								if(!$projectID){
									 $sql = "SELECT project_id FROM projects WHERE proj_name like '$useProject' ";
									 
									 $result = $db->fetchAll($sql, 2);
									 if($result){
										  $projectID = $result[0]["project_id"];
										  @$projString = file_get_contents("http://opencontext.org/projects/".$projectID.".json");
										  if($projString){
												$projJSON = Zend_Json::decode($projString);
												unset($projString);
												if(isset($projJSON["contexts"])){
													 
													 $sumLat = 0;
													 $sumLon = 0;
													 $geoCount = 0;
													 foreach($projJSON["contexts"] as $context){
														  if(isset($context["geopoint"]) && $geoCount == 0){
																//just use the first point for now
																$sumLat += $context["geopoint"]["lat"];
																$sumLon += $context["geopoint"]["long"];
																$geoCount++;
														  }
													 }
													 
													 if($geoCount>0){
														  $useGeoLat = $sumLat / $geoCount;
														  $useGeoLon = $sumLon / $geoCount;
														  $OpenContextItem->addGeo($useGeoLat, $useGeoLon);
													 }  
												}
												unset($projJSON);
										  }
										  else{
												$projectID = false;
										  }
									 }//end case with a result
								}//end case with a project id
						  }//end loop through project array
					 }//eend case through project array

					 //now finish off with some context for the table
					 $contextArray = array();
					 if($containedValue != false){
						  if(stristr($containedValue, "/")){
								$contextArrayPrelim = explode("/",$containedValue);
								foreach($contextArrayPrelim as $context){
									 if(!strstr($context, " OR ")){
									$contextArray[] = trim($context);
									 }
								}
								unset($contextArrayPrelim);
						  }
						  else{
								$contextArray[] = trim($containedValue);
						  }
					 }
			  
					 //Now add facet context if available
					 if(isset($searchJSON["facets"]["context"])){
						  if(count($searchJSON["facets"]["context"]) < 2){
								$contextArray[] = trim($searchJSON["facets"]["context"][0]["name"]);
						  }
						  elseif(count($contextArray) == 0){
								$contextArray[] = "[Multiple Regions]";
						  }
						  else{
						 //do nothing. lots of different contexts below
						  }
					 }
	  
			  
					 if(count($contextArray)>0){
						  $OpenContextItem->addDefaultContext($contextArray);
					 }
			  
			  
					 unset($searchJSON); // don't need it any more
				}
				else{
					 $this->solrIndexingError = true;
				}
		  }
		  
		  if(!is_array($OpenContextItem->classes) || count($OpenContextItem->classes)<1){
				$classes = array();
				if(isset($tableData["records"])){
					 foreach($tableData["records"] as $key => $rec){
						  if(!in_array($rec["category"], $classes)){
								$classes[] = $rec["category"];
						  }
					 }
				}
				if(count($classes)>0){
					 $OpenContextItem->classes = $classes;
				}
				else{
					 $OpenContextItem->classes = array("table");
				}
		  }
		  

		  return $OpenContextItem;
    }//end function for solr indexing
    
    
    
    
    
    
    
    
    
    function makeAtomEntry(){
	
		  $host = OpenContext_OCConfig::get_host_config();
		  $base_hostname = OpenContext_OCConfig::get_host_config(false);
		  $baseURI = OpenContext_OCConfig::get_host_config();
		  
		  $atomFullDoc = new DOMDocument("1.0", "utf-8");	
		  $entry = $atomFullDoc->createElementNS("http://www.w3.org/2005/Atom", "entry");	
		  // add newlines and indent the output - this is at least useful for debugging and making the output easier to read
		  $atomFullDoc->formatOutput = true;
		  $entry->setAttribute("xmlns:dc", "http://purl.org/dc/elements/1.1/");
		  
		  $atomFullDoc->appendChild($entry);
		  
		  $table = $this->tableData;
		  $entryTitle = $atomFullDoc->createElement("title");
		  $entryTitleText = $atomFullDoc->createTextNode($table['table_name']);
		  $entryTitle->appendChild($entryTitleText);
		  $entry->appendChild($entryTitle);
		  
		  $tableURL = $host.'/tables/'.OpenContext_TableOutput::tableID_toURL($table['cache_id']);
		  $entryID = $atomFullDoc->createElement("id");
		  $entryIDText = $atomFullDoc->createTextNode($tableURL);
		  $entryID->appendChild($entryIDText);
		  $entry->appendChild($entryID);
		  
		  $selfLink = $atomFullDoc->createElement("link");
		  $selfLink->setAttribute("rel", "alternate");
		  $selfLink->setAttribute("type", "application/xhtml+xml");
		  $selfLink->setAttribute("href", $tableURL);
		  $entry->appendChild($selfLink);
		  
		  $csvLink = $atomFullDoc->createElement("link");
		  $csvLink->setAttribute("rel", "alternate");
		  $csvLink->setAttribute("type", "text/csv");
		  $csvLink->setAttribute("href", $tableURL.".csv");
		  $entry->appendChild($csvLink);
		  
		  $jsonLink = $atomFullDoc->createElement("link");
		  $jsonLink->setAttribute("rel", "alternate");
		  $jsonLink->setAttribute("type", "application/json");
		  $jsonLink->setAttribute("href", $tableURL.".json");
		  $entry->appendChild($jsonLink);
		  
		  
		  //Publication / creation time Format it in RFC 3339 format. Store it in a text node
		  $entryPub = $atomFullDoc->createElement("published");
		  $entryPubText = $atomFullDoc->createTextNode(date("Y-m-d\TH:i:s\-07:00", strtotime($table['created_on'])));
		  $entryPub->appendChild($entryPubText);
		  $entry->appendChild($entryPub);
		  
		  //Update time Format it in RFC 3339 format. Store it in a text node
		  $entUpdate = $table['updated'];
		  if($entUpdate == "0000-00-00 00:00:00"){
			  $entUpdate = $table['created_on'];
		  }
		  $entryUp = $atomFullDoc->createElement("updated");
		  $entryUpText = $atomFullDoc->createTextNode(date("Y-m-d\TH:i:s\-07:00", strtotime($entUpdate)));
		  $entryUp->appendChild($entryUpText);
		  $entry->appendChild($entryUp);
		  
		  
		  //prepare data for contributor / author information
		  $numRecords = $table['num_records'];
		  $table_metadata_all = Zend_Json::decode($table['metadata']);
		  $table_metadata = $table_metadata_all['meta'];
		  unset($table_metadata_all);
		  
		  $table_metadata = OpenContext_TableOutput::noid_check($table['cache_id'], $table_metadata);
		  
		  $creatorArray = array();
		  foreach($table_metadata["creators"] as $creator => $num){
				$creatorArray[] = $creator;
		  }
		  
		  $projectArray = array();
		  foreach($table_metadata["projects"] as $project){
				$projectArray[] = $project;
		  }
		  $contribArray = array();
		  $contribArrayShow = array();
		  $jjj=0;
		  foreach($table_metadata["person_links"] as $subkey => $subvalue){
				
			  
				$bigContributor = false;
				$contribOK = false;
				if($subvalue >= ($numRecords/4)){
			  $contribOK = true;
			  $bigContributor = true;
				}
				elseif((count($contribArray)<= 10)&&($subvalue >= ($numRecords * .05 ))){
			  //the top 10 ranked persons on a long list of conributors, even if they contributed only 5% of the records
			  $contribOK = true;
				}
				else{
			  $contribOK = false;
				}
				
				$contribArray[] = $subkey;
				if($contribOK){
			  $contribArrayShow[] = $subkey;
				}
				$jjj++;
		  }
		  
		  if(count($contribArrayShow)< count($contribArray)){
				$contribArrayShow[] = (count($contribArray) - count($contribArrayShow))." Others";
		  }
		  
		  foreach($creatorArray as $creator){
			  $entryCreator = $atomFullDoc->createElement("author");
			  $entryName = $atomFullDoc->createElement("name");
			  $entryNameText = $atomFullDoc->createTextNode($creator);
			  $entryName->appendChild($entryNameText);
			  $entryCreator->appendChild($entryName);
			  $entry->appendChild($entryCreator);
		  }
		  
		  foreach($contribArrayShow as $contrib){
			  $entryContrib = $atomFullDoc->createElement("contributor");
			  $entryName = $atomFullDoc->createElement("name");
			  $entryNameText = $atomFullDoc->createTextNode($contrib);
			  $entryName->appendChild($entryNameText);
			  $entryContrib->appendChild($entryName);
			  $entry->appendChild($entryContrib);
		  }
		  
		  if(array_key_exists("noid", $table_metadata)){
			  $archiveID = $table_metadata["noid"];
			  $table_metadata["noid"] = "ark:/".$archiveID;
			  //echo var_dump($table_metadata["noid"]);
				}
				else{
			  $archiveID = false;
				}
		  
		  $citation = array("contributors" => $contribArrayShow,
					 "creators" => $creatorArray,
					 "generator" => $table_metadata["TabCreator"],
					 "projects" => $projectArray,
					 "date" => date("Y", strtotime($table_metadata["TabCreated"])),
					 "title" => $table_metadata["table_name"],
					 "archive" => $archiveID,
					 "uri" => $tableURL);
		  
		  $citationString = implode(", ", $citation["contributors"])."\n";
		  $citationString .= $citation["date"]." '".$citation["title"]."' From projects: ".implode(", ", $citation["projects"]).". ";
		  $citationString .= "Led by: ".implode(", ", $citation["creators"]).". Table generated by: ".$citation["generator"].". Open Context. ";
		  $citationString .= "(".$citation["uri"].")\n ";
		  if($archiveID != false){
			  $citationString .= "California Digial Library Archival Identifier, (ark:/".$archiveID.")";
		  }
		  
		  //prepare data for the summary of the entry
		  $currentTab = $table['table_num'];
		  $recsPerTable = $table['recs_per_table'];
		  $totalTab = $table['total_tabs'];
		  $tableMin = (($currentTab -1 ) * $recsPerTable) + 1;
		  $tableMax = $currentTab * $recsPerTable;
		  if($tableMax > $numRecords){
			  $tableMax = $numRecords;
		  }
		  $table_Part_Of = "This table is part ".$currentTab." of ".$totalTab.", containing ".$tableMin." to ".$tableMax." of ".$numRecords ." total records.";
		  
		  $summary = $table['description']." \n";
		  
		  if($totalTab>1){
			  $summary .= $table_Part_Of." \n\n";
		  }
		  
		  $summary .= "----CITATION:----\n";
		  $summary .= $citationString;
		  
		  $entrySummary = $atomFullDoc->createElement("summary");
		  $entrySummaryText = $atomFullDoc->createTextNode($summary);
		  $entrySummary->appendChild($entrySummaryText);
		  $entry->appendChild($entrySummary);
		  
		  if($archiveID != false){
			  $archiveLink = $atomFullDoc->createElement("link");
			  $archiveLink->setAttribute("rel", "archival");
			  $archiveLink->setAttribute("type", "application/xhtml+xml");
			  $archiveLink->setAttribute("href", "http://n2t.net/ark:/".$archiveID);
			  $entry->appendChild($archiveLink);
		  }
		  
		  $this->atomEntry = $atomFullDoc->saveXML();
	
    }//end function
    
    
    //turns table fields into human readable data
    function tableFieldFormat($tableFields){
		  $output = array();
		  foreach($tableFields as $tfield){
				if($tfield == "proj"){
					 $tfield = "Project";
				}
				elseif($tfield == "person"){
					 $tfield = "Linked Persons";
				}
				elseif($tfield == "label"){
					 $tfield = "Item Name";
				}
				elseif($tfield == "pub_date"){
					 $tfield = "Publication Date";
				}
				elseif($tfield == "update"){
					 $tfield = "Last Updated";
				}
				elseif($tfield == "category"){
					 $tfield = "Category";
				}
			 
				if(substr_count($tfield, "def_context_")>0){
					 $tfield = str_replace("def_context_", "", $tfield);
					 $tfield++;
					 $tfield = "Context (".$tfield.")";
				}
			 
				$output[] = $tfield;
		  }
		  
		  return $output;
	 }
    
    
	 public $showTabName; //public name for table
	 public $titleMessage; //additional title message
	 public $dataCurrent; //is the dataset current or has it been updated?
	 public $tabProjects; //array of projects to display
	 public $DCcreators; //array of Dublin Core creators
	 public $DCcontributors; //array of ALL Dublin Core contributors
	 public $showContributors; //array of Dublin Core contributors to display
	 public $archiveID;
	 
	 public $citeString; //string of HTML for citation
	 public $citeURI; //citation URL for the table
	 public $csvURI; //link to CSV representation of the table
	 public $queryURL; //link to the query that makes the table
	 
	 //process table metadata for display
	 function tableMetadataFormat($tableID, $table_metadata){
		  
		  $host = OpenContext_OCConfig::get_host_config();
		  //a few temporary hacks to 'fix' some bad data
		  $table_metadata["table_name"] = str_replace(" Searh", " Search", $table_metadata["table_name"]);
		  if(strlen($table_metadata["table_name"])<2){
				$table_metadata["table_name"] = "[Not labeled, Records: ".$table_metadata["numFound"]."]";
		  }
		  
		  $qLink = htmlentities($table_metadata['setURI']);
		  if(!stristr($qLink, $host)){
				$qLink = $host.$qLink;
		  }
		  else{
				$qLink = str_replace('http://opencontext.org/createtab/setfields', $host.'/sets', $qLink);
		  }
		  
		  $this->queryURL = $qLink; //link to query to create the table
		  $this->showTabName = $table_metadata["table_name"];
		  $this->checkCurrentData($tableID);
		  
		  
		  $creatorArray = array();
		  foreach($table_metadata["creators"] as $creator => $num){
				$creatorArray[] = $creator;
		  }
		  $this->DCcreators = $creatorArray;
		  
		  $projectArray = array();
		  foreach($table_metadata["projects"] as $project){
				$projectArray[] = $project;
		  }
		  $this->tabProjects = $projectArray;
		  
		  
		  $contribArray = array();
		  $contribArrayShow = array();
		  $jjj=0;
		  $numRecords = $table_metadata["numFound"];
		  foreach($table_metadata["person_links"] as $subkey => $subvalue){
				$bigContributor = false;
				$contribOK = false;
				if($subvalue >= ($numRecords/4)){
					 $contribOK = true;
					 $bigContributor = true;
				}
				elseif((count($contribArray)<= 10)&&($subvalue >= ($numRecords * .05 ))){
					 //the top 10 ranked persons on a long list of conributors, even if they contributed only 5% of the records
					 $contribOK = true;
				}
				else{
					 $contribOK = false;
				}
				
				$contribArray[] = $subkey;
				if($contribOK){
					 $contribArrayShow[] = $subkey;
				}
				$jjj++;
		  }
		  
		  if(count($contribArrayShow)< count($contribArray)){
				$contribArrayShow[] = (count($contribArray) - count($contribArrayShow))." Others";
		  }
		  
		  $this->DCcontributors =  $contribArray;
		  $this->showContributors = $contribArrayShow;
		  
		  $tableURLid = OpenContext_TableOutput::tableID_toURL($tableID);
		  $this->citeURI = $host."/tables/".$tableURLid;
		  $this->csvURI = $host."/tables/".$tableURLid.".csv";
		  
		  //now indicate links to other segments for download
		  $this->tableSegments($tableID, $host, $table_metadata);
		  
		  //now format the table citation
		  $this->makeCitationString($table_metadata);
	 
		  if(!isset($table_metadata["setLastPublished"])){
				$table_metadata["setLastPublished"] = false;
		  }
		  if(!isset($table_metadata["setLastUpdate"])){
				$table_metadata["setLastUpdate"] = false;
		  }
	 
		  return $table_metadata;
	 }//end function
	 
	 
	 //make a bibliographic citation for the current table
	 function makeCitationString($table_metadata){
	 
		  $citationString = "<p class=\"authors\">".implode(", ", $this->showContributors)."</p>".chr(13);
		  $citationString .= "<div>".chr(13);
		  $citationString .= "<div class=\"pub-date\">".date("Y", strtotime($table_metadata["TabCreated"]))."</div>".chr(13);
		  $citationString .= "<div class=\"ref-title-pub\">&quot;".$this->showTabName."&quot; From Projects: ".implode(", ", $this->tabProjects);
		  $citationString .= ". Led by: ".implode(", ", $this->DCcreators).". Table generated by: ".$table_metadata["TabCreator"];
		  $citationString .= " <span class=\"collection-title\">Open Context</span>.";
		  $citationString .= "&lt;".$this->citeURI."&gt; ";
		  $citationString .= "</div>".chr(13);
		  $citationString .= "<div class=\"ref-end\"></div>".chr(13);
		  $citationString .= "</div>".chr(13);
		  $this->citeString = $citationString;
	 }
	 
	 
	 public $showSegmentNote;
	 
	 //indicate other segments of the table (for large sets)
	 function tableSegments($tableID, $host, $table_metadata){
		  
		  $numRecords = $table_metadata["numFound"];
		  if(array_key_exists("table_segments", $table_metadata)){
				$currentTab = $table_metadata["table_segments"]["currentTab"];
				$recsPerTable = $table_metadata["table_segments"]["recsPerTable"];
				$totalTab = $table_metadata["table_segments"]["totalTabs"];
				$tableMin = (($currentTab -1 ) * $recsPerTable) + 1;
				$tableMax = $currentTab * $recsPerTable;
				if($tableMax > $numRecords){
					 $tableMax = $numRecords;
				}
				$table_Part_Of = "This table is part ".$currentTab." of ".$totalTab.", containing ".$tableMin." to ".$tableMax." of ".$numRecords ." total records.";
				$table_Part_Of .= "<br/>";
				
				$urlID = OpenContext_TableOutput::tableID_toURL($tableID);
				if(stristr($urlID, "/")){
					 $idArray = explode("/",$urlID);
					 $idPrefix = $idArray[0];
				}
				else{
					 $idPrefix = $urlID;
				}
				
				if($currentTab > 1){
					 $previousTabNum = $currentTab - 1;
					 if($previousTabNum > 1){
						  $previousTabID = "/".$previousTabNum;
					 }
					 else{
						  $previousTabID = "";
					 }
					 $previousURL =  $host."/tables/".$idPrefix.$previousTabID;
					 $table_Part_Of .= "(<a href=\"".$previousURL."\">Previous Part [$previousTabNum]</a>)";
				}
				
				if($currentTab < $totalTab){
					 $nextTabNum = $currentTab+1;
					 $nextTabID = "/".$nextTabNum;
					 $nextURL =  $host."/tables/".$idPrefix.$nextTabID;
					 $table_Part_Of .= " (<a href=\"".$nextURL."\">Next Part [$nextTabNum]</a>)";
				}
				
				if($totalTab < 2){
					 $this->showSegmentNote = "This download has all $numRecords records.";
				}
				else{
					 $this->showSegmentNote = $table_Part_Of;
				}
				
		  }
		  else{
				$this->showSegmentNote = "This download has all $numRecords records.";
		  }

	 }//end function
	 
	 
	 
    
    function checkCurrentData($tableID){
		  $this->dataCurrent = true;
		  $tableID = $this->security_check($tableID);
		  //Give most recent table links
		  $db_params = OpenContext_OCConfig::get_db_config();
		  $db = new Zend_Db_Adapter_Pdo_Mysql($db_params);
		  $db->getConnection();
		 
		  $sql = 'SELECT current
		  FROM dataset
		  WHERE cache_id = "'.($tableID).'"; ';
		  
		  $results = $db->fetchAll($sql, 2);
		  $dataCurrent = true;
		  if($results){
				if($results[0]['current'] == 'no'){
					 $this->dataCurrent = false;
					 $this->titleMessage = '(Archived, Not Current)';
				}
				else{
					 $this->titleMessage = '';
				}
		  }
		  else{
				$this->titleMessage = '';
		  }
	 }
	 
	 function otherTables($tableID){
		  
		  $host = OpenContext_OCConfig::get_host_config(); 
		  $db_params = OpenContext_OCConfig::get_db_config();
		  $db = new Zend_Db_Adapter_Pdo_Mysql($db_params);
		  $db->getConnection();
		  
		  $select = $db->select()
				->from(array('t' => 'dataset'),
				array('cache_id', 'table_name', 'num_records'))
				->where('cache_id != ?', $tableID)
				->limit(5)
				->order('created_on DESC');
				
				/*  
				$sql = $select->__toString();
				echo "$sql\n";
				*/
	 
		  $stmt = $select->query();
		  $result = $stmt->fetchAll();
		  $output = array();
		  if($result){
				foreach($result as $row){
					 $newRecord = array();
					 $newRecord["num_records"] = $row["num_records"];
					 $newRecord["cache_id"] = OpenContext_TableOutput::tableID_toURL($row["cache_id"]);
					 $newRecord["uri"] = $host."/tables/".$newRecord["cache_id"];
					 if(strlen($row["table_name"])<1){
						  $newRecord["table_name"] = "[Not labeled, Records: ".$row["num_records"]." ]";
					 }
					 else{
						  $newRecord["table_name"] = $row["table_name"];
					 }
					 $output[] = $newRecord;
				}
		  }
		  
		  $db->closeConnection();
		  return $output;
	 }
	 
	 
    
    function db_find_personID($personName){
	
		  $personID = false;
		  $db_params = OpenContext_OCConfig::get_db_config();
				 $db = new Zend_Db_Adapter_Pdo_Mysql($db_params);
		  $db->getConnection();
		  
		  $sql = "SELECT persons.person_uuid
		  FROM persons
		  WHERE persons.project_id = '".$this->projectUUID."'
		  AND persons.combined_name LIKE '%".$personName."%'	
		  LIMIT 1;
		  ";
		  
		  $result = $db->fetchAll($sql, 2);
				 if($result){
				$personID = $result[0]["person_uuid"];
		  }
		  
		  $db->closeConnection();
		  return $personID;
    }
    
    
    
    function security_check($input){
        $badArray = array("DROP", "SELECT", "#", "--", "DELETE", "INSERT", "UPDATE", "ALTER", "=");
        foreach($badArray as $bad_word){
            if(stristr($input, $bad_word) != false){
                $input = str_ireplace($bad_word, "XXXXXX", $input);
            }
        }
        return $input;
    }
    
    
    
    
    
    
}
