<?php
/** Zend_Controller_Action */
require_once 'Zend/Controller/Action.php';
ini_set("memory_limit", "2048M");
ini_set("max_execution_time", "0");

mb_language("uni");
mb_detect_order("UTF-8, ISO-8859-1");
mb_internal_encoding('UTF-8');



class createtabController extends Zend_Controller_Action
{   
	
	
	
	//settings for table export
	private function getSettings(){
		$numRecsPerPage = 50;
		$tableMaxSize = $numRecsPerPage * 100;
		
	      $settings = array("numRecsPerPage" => $numRecsPerPage, //number of records to retieve / page
				"tableMaxSize" => $tableMaxSize //maximum size of an individual table
				);
      
	      return $settings;	
	}
	
	private function calc_numberTables($numFound){
		$settings = $this->getSettings();
		$tableMaxSize = $settings["tableMaxSize"];
		$rawCount = $numFound / $tableMaxSize;
		
		if(round($rawCount, 0) < $rawCount){
			$numTables = round($rawCount, 0) + 1;
		}
		else{
			$numTables = round($rawCount, 0);
		}
      
		return $numTables;	
	}
	
	private function get_currentTab($page){
		$settings = $this->getSettings();
		$tableMaxSize = $settings["tableMaxSize"];
		$recsPerPage = $settings["numRecsPerPage"];
		
		$pageTotal = $page * $recsPerPage;
		
		$rawCount = $pageTotal / $tableMaxSize;
		
		if(round($rawCount, 0) < $rawCount){
			$currentTab = round($rawCount, 0) + 1;
		}
		else{
			$currentTab = round($rawCount, 0);
		}
      
		return $currentTab;	
	}
	
	
	
	public function exportTableAction(){
		//$this->_helper->viewRenderer->setNoRender();
		
		$auth = Zend_Auth::getInstance();
		$settings = $this->getSettings();
		$this->view->numRecsToGet = $settings["numRecsPerPage"];
		
		if (!$auth->hasIdentity()){
			//$this->view->displayName = false;
			return $this->_helper->redirector('index', 'sets');
		} 
		else{
			$identity = $auth->getIdentity();
			$this->view->displayName = $identity->name;
		
			$set_uri = array_key_exists('setURI', $_REQUEST)? $_REQUEST['setURI']:false;
			
			$this->view->set_uri = $set_uri;
			
			if($set_uri !=false){
				//get the table fields headings
				$host = OpenContext_OCConfig::get_host_config();
				$table_fields_uri = str_replace("/sets", "/createtab/setfields", $set_uri);
				$table_fields_string = file_get_contents($table_fields_uri);
				//header('Content-Type: application/json; charset=utf8');
				//echo $table_fields_string ;
				$table_fields = Zend_Json::decode($table_fields_string);
				$this->view->table_fields = $table_fields;
			}	
			else{
				//$this->view->table_fields = false;
				return $this->_helper->redirector('index', 'sets');
			}
		}
      }
	
	
	
	public function newtableAction(){
		$auth = Zend_Auth::getInstance();
		$settings = $this->getSettings();
		$this->view->numRecsToGet = $settings["numRecsPerPage"];
		
		if (!$auth->hasIdentity()){
			$this->view->displayName = false;
		} 
		else{
			$identity = $auth->getIdentity();
			$this->view->displayName = $identity->name;

			$set_uri = array_key_exists('setURI', $_REQUEST)? $_REQUEST['setURI']:false;
			$this->view->set_uri = $set_uri;
				
			if($set_uri !=false){
				//get the table fields headings
				$host = OpenContext_OCConfig::get_host_config();
				$table_fields_uri = str_replace("/sets", "/createtab/setfields", $set_uri);
				$table_fields_string = file_get_contents($table_fields_uri);
				$table_fields = Zend_Json::decode($table_fields_string);
				$this->view->table_fields = $table_fields;
		        }
			else{
				$this->view->table_fields = false;
			}
		}
      }//end function   
    
    
	public function allrecordsAction() {
		$this->_helper->viewRenderer->setNoRender();
		
		$rec_set_uri = $_REQUEST['recsetURI'];
		$setMetaURI = $_REQUEST['setMetaURI'];
		$page = $_REQUEST['page'];
		
		if(isset($_REQUEST['username'])){
			$UserName = $_REQUEST['username'];
		}else{
			$UserName = "Open Context Editors";
		}
		
		$settings = $this->getSettings();
		$numRecsPerPage = $settings["numRecsPerPage"];
		$numRecsPerTable = $settings["tableMaxSize"];
		$numTables = $this->calc_numberTables($_REQUEST['setTotal']);
		$currentTab = $this->get_currentTab($page);
		
		
		$auth = Zend_Auth::getInstance();
		if (!$auth->hasIdentity()){
				$UserName = "Guest";
		} 
		else{
			$identity = $auth->getIdentity();
			$UserName = $identity->name;
		}
	 
		$responseOutput="";
		if (array_key_exists('tname',$_REQUEST)){ 
			$table_name=$_REQUEST['tname'];
		}
		else{
			$table_name="unnamed table"; 
		}
		if (array_key_exists('tdes',$_REQUEST)) {
			$table_description=$_REQUEST['tdes']; 
		}
		else{
			$table_description="some arbit description";
		}
		if (array_key_exists('ttags',$_REQUEST)){
			$tagstring = $_REQUEST['ttags']; 
		}
		
		$rec_set_uri = str_replace('%E2%88%9D', "&prop", $rec_set_uri);
		//echo $rec_set_uri;
		$solr_data = Zend_Json::decode(file_get_contents($rec_set_uri));
		
		
		if(!isset($solr_data["records"])){
			sleep(.75); //try again if it doesn't work
			$solr_data = Zend_Json::decode(file_get_contents($rec_set_uri));
		}
		
		if(!isset($solr_data["records"])){
			$status = OpenContext_TableOutput::log_error_note($rec_set_uri, "No records");	
		}
		
		
		
		//echo file_get_contents($rec_set_uri);
		
		$newRecs = $solr_data["meta"]["numRecs"];
		$DonePage = $solr_data["meta"]["page"];
		$DoneOffset = $solr_data["meta"]["offset"];
		
		//echo var_dump($solr_data);
		
		$frontendOptions = array('lifetime' => 7200,'automatic_serialization' => true );
		$backendOptions = array('cache_dir' => './temp_tables_cache/' );
				
		$cache = Zend_Cache::factory('Core','File',$frontendOptions,$backendOptions);
		$cache_id = "AllSetRecs_".$currentTab."_".md5($setMetaURI);
		$newRecs = $solr_data["meta"]["numRecs"];
	
		if(!$cache_result = $cache->load($cache_id)) {
			$JSONstring = Zend_Json::encode($solr_data["records"]);
			//if(count($solr_data["records"])>0){
			$cache->save($JSONstring, $cache_id); //save intial results to the cache
			//}
			$totalRecs = count($solr_data["records"]) + (($page -1) * $numRecsPerPage);
		}
		else{
		  //case with existing version cached.
		  
		  
			$existing_records = Zend_Json::decode($cache_result);
			$new_records = array_merge($existing_records, $solr_data["records"]);
			
			
			//$totalRecs = count($new_records);
			$totalRecs = count($solr_data["records"]) + (($page -1) * $numRecsPerPage);
			
			$JSONstring = Zend_Json::encode($new_records);
			//if(count($totalRecs)>0){
			$cache->save($JSONstring, $cache_id); //save expanded results to the cache
			//}
		}
		
		if($totalRecs < $solr_data["meta"]["numFound"]){
			$Final_cache_id = false;
		}
		
		if($totalRecs > $solr_data["meta"]["numFound"]){
			$Final_cache_id = "error";
		}
		
		
		$responseOutput = array("totalRecs" => $totalRecs, "recURI"=>$rec_set_uri, "currentTab" => $currentTab);
		
		//fancy things happen when the table is finished!
		//this next step writes a final table to the tablecache lifetime is set to NULL to make them never expire.
		//this may make more sense to store in MySQL
		$errorLog = array();
		
		if(($totalRecs == $solr_data["meta"]["numFound"])&& $totalRecs>0){
		  
			$fieldsCacheID = $solr_data["meta"]["fieldsCacheID"];
			
			unset($solr_data);
			$finalTabfields = array();
			
			
			  $createdTime = date("Y-m-d\TH:i:s\-07:00");
			  $set_metadata_string = file_get_contents($setMetaURI);
			  $all_set_metadata = Zend_Json::decode($set_metadata_string);
			  $all_set_metadata["meta"]["TabCreated"] = $createdTime;
			  $all_set_metadata["meta"]["TabCreator"] = $UserName;
			  $all_set_metadata["meta"]["table_name"]= $table_name;
			  $all_set_metadata["meta"]["table_description"]= $table_description;
			  $all_set_metadata["meta"]["tagstring"]= $tagstring;
			  unset($all_set_metadata["contexts"]);
			
			
			
			$i = 1;
			while($i <= $numTables){
				
				//get current set of records
				$cache_id = "AllSetRecs_".$i."_".md5($setMetaURI);
				
				if($cache_result = $cache->load($cache_id)) {
					$existing_records = Zend_Json::decode($cache_result);
				}
				else{
					$errorLog[] = array("cache"=>$cache_id, "error" => "not found");
					$existing_records = array();
				}
				
				$act_record = end($existing_records);  //go to the last record, which should have all the fields found in the entire output
				foreach($act_record as $field=>$value){
					if(!in_array($field, $finalTabfields)){
					$finalTabfields[] = $field;
					}
				}
				//$finalTabfields = $this->get_store_table_fields($fieldsCacheID, md5($setMetaURI), $finalTabfields); // make sure you have all fields
				
				$all_set_metadata["table_fields"] = $finalTabfields;
				if($numTables > 1){
					$all_set_metadata["meta"]["table_name"]= $table_name." [".$i." of ".$numTables."]";
				}
				
				if($i>1){
					$Final_cache_id = $i."_".md5($setMetaURI); //unique ids for tables
					
					if(strstr($all_set_metadata["meta"]["setURI"], "?")){
						$all_set_metadata["meta"]["setURI"] = $setMetaURI."&tab=".$i; //unique ids for sets
					}
					else{
						$all_set_metadata["meta"]["setURI"] = $setMetaURI."?tab=".$i; 
					}
				}
				else{
					$Final_cache_id = md5($setMetaURI);
					$first_cache_id = $Final_cache_id;
				}
				  
					$all_set_metadata["meta"]["table_segments"] = array("currentTab" => $i, "recsPerTable" => $numRecsPerTable, "totalTabs" => $numTables);
					
				
				$Final_frontendOptions = array('lifetime' => NULL, 'automatic_serialization' => true );
				$Final_backendOptions = array('cache_dir' => './tablecache/');
				$Final_cache = Zend_Cache::factory('Core','File',$Final_frontendOptions,$Final_backendOptions);
				
				try{  
					$status = OpenContext_TableOutput::save_data($Final_cache_id, $UserName, $all_set_metadata); //add record of this to database
					$errorLog[] = array("id" => $Final_cache_id, "status" => $status);
				} 
				catch (Zend_Exception $e){
					//echo get_class($e);
					//Zend_Db_Statement_Exception;
					$errorLog[] = array("id" => $Final_cache_id, "error" => $e);
				}
				
				try{
					$all_set_metadata["records"] = $existing_records;
					$finalJSON = Zend_Json::encode($all_set_metadata);
					$Final_cache->save($finalJSON, $Final_cache_id ); //save expanded results to the cache
					unset($all_set_metadata["records"]);
					
					$fp = fopen('./tablefiles/'.$Final_cache_id.'.json', 'w');
					//fwrite($fp, utf8_encode($finalJSON));
					fwrite($fp, iconv("ISO-8859-7","UTF-8",$finalJSON));
					//fwrite($fp, ($finalJSON));
					  fclose($fp);
				  
				}
				catch (Zend_Exception $e){
					//echo get_class($e);
					//Zend_Db_Statement_Exception;
					$errorLog[] = array("id" => $Final_cache_id, "error" => $e);
				}
				
				
				$i++;
			  }//end case with cache saved
			  
			  $responseOutput = array("DonePage"=> $DonePage,"DoneOffset"=> $DoneOffset,"newRecs" => $existing_records, "totalRecs" => $totalRecs, "DoneID" => $first_cache_id, "currentTab" => $currentTab);
		
		}//end case with cache saved
	
	
		$responseOutput["errors"] = $errorLog;
		
		header('Content-Type: application/json; charset=utf8');
		echo Zend_Json::encode($responseOutput);
  }
  
  
  
  
  /*
   This function helps store fields as they are found
   It is used to make sure that a comeplete field list is generated on a table export
  */
  private function get_store_table_fields($cacheID, $setHash, $newFields = false){
	$allFields = false; //no fields found
	
	$db_params = OpenContext_OCConfig::get_db_config();
        $db = new Zend_Db_Adapter_Pdo_Mysql($db_params);
        $db->getConnection();
	$sql = "SELECT * FROM dataset_process WHERE cache_id = '$cacheID' OR setHash = '$setHash' LIMIT 1";
	$result = $db->fetchAll($sql, 2);
	if($result){
		$allFields = Zend_Json::decode($result[0]["fields"]);
		$cacheID = $result[0]["cache_id"];
		$useCount = $result[0]["use_count"] + 1;
		
		$data = array("use_count" => $useCount);
		$where = array(0=>"cache_id = '$cacheID'");
		$db->update("dataset_process", $data, $where);
		
		if(is_array($newFields)){
			$addedFields = false; // no added fields found
			
			foreach($newFields as $newField){
				if(!in_array($newField, $allFields)){
					$allFields[] = $newField;
					$addedFields = true;
				}
			}
			
			if($addedFields){
				$StringAllFields = Zend_Json::encode($allFields);
				$data = array("fields" => $StringAllFields);
				$where = array(0=>"cache_id = '$cacheID'");
				$db->update("dataset_process", $data, $where);
			}
		}
	}
	elseif(is_array($newFields)){
		$allFields = $newFields; 
		$StringAllFields = Zend_Json::encode($allFields);
		$data = array("cache_id" => $cacheID, "setHash" => $setHash, "fields" => $StringAllFields);
		$db->insert("dataset_process", $data);
	}
	
	return $allFields;
  }
  
  
  
    
  /*
  *
  *
  * This function populates a table with records
  * Some table fields are standard, others are custom for a given result set
  * the custom fields are requested from a JSON string from tables/setfields (followed by the same parameters)
  */
  public function tablepopulateAction() {
    // the offset number we send to solr when requesting results
    $this->_helper->viewRenderer->setNoRender(); //no need for a view, data output as JSON string only
    $offset = 0;
    $maxrecs = 100;
    $page = $this->_request->getParam('page');
    $number_recs = $this->_request->getParam('recs');
    $requestURI = $this->_request->getRequestUri();
    $prop = $this->_request->getParam('prop');
    
    //echo var_dump($prop);
    
    //$number_recs = 25;
    
    if(!$number_recs)
      $number_recs = 10;
    else{
      if(is_integer($number_recs+0))
        $number_recs = abs($number_recs);
      else
        $number_recs = 10;
      }
    if($number_recs < 1)
      $number_recs = 1;
    if($number_recs > $maxrecs)
      $number_recs = $maxrecs;
    if (is_numeric($page) && $page > 0 ) 
      $offset = ($page - 1) * $number_recs;
    
    //To make a table, a list of the set-specific fields is needed
    //the action 'setfields' gets the set-specific fields for a table
    //get the table fields headings
    $host = OpenContext_OCConfig::get_host_config();
    $table_fields_uri = str_replace("/createtab/tablepopulate", "/createtab/setfields", $requestURI);
    $table_fields_string = file_get_contents($host.$table_fields_uri);
    
    //echo $table_fields_string;
    
    $table_fields = Zend_Json::decode($table_fields_string);
    if(count($table_fields['table_fields'])<1){
	$table_fields['table_fields'] = $this->get_store_table_fields($tablefields["cache_id"], $tablefields["setHash"], false);
    }
    
    //echo var_dump($table_fields);
    
    
    //**************************************************
    //the following code is very similar to the SETs code from the Sets controller
    $requestParams =  $this->_request->getParams();
    
	$solrSearch = new solrSearch;
	$solrSearch->initialize();
	$solrSearch->requestURI = $this->_request->getRequestUri();
	$solrSearch->requestParams = $requestParams;
	$solrSearch->PropToTaxaParameter(); // change depricated prop parameters to taxa parameters
	$requestParams = $solrSearch->requestParams; //make sure any changes to request parameters are there for the view page
	
	$solrSearch->spatial = true; //do a search of spatial items in Open Context
	$solrSearch->rawDocsArray = true; //get the raw document output
	$solrSearch->buildSolrQuery();
	
	$param_array = $solrSearch->param_array;
	$param_array['sort'] ="label_sort desc, interest_score desc";
	unset($param_array["facet.field"]); //Facets are not needed!
	unset($param_array["facet"]);       //Facets are not needed!
	unset($param_array["facet.mincount"]);  //Facets are not needed!
	$solrSearch->param_array = $param_array;
	
	$solrSearch->execute_search();
	//$solrSearch->getLatestTime(); //get the last updated
	//$solrSearch->getLatestTime(false); //get the last published
	
	$numFound = $solrSearch->numFound;
        $record_array = array();
        $newFields = array();
        foreach($solrSearch->documentsArray as $doc){
		$item_array = array();
		$act_id = $doc['uuid'];
		$item_array["proj"] = $doc['project_name'];
		$uniquePerson = array();
		//echo print_r($doc);
		if(array_key_exists("creator", $doc)){
		      if(is_array($doc["creator"])){
			foreach($doc["creator"] as $actPerson){
				if($actPerson == "Sarah W. Kansa"){
					$actPerson = "Sarah Whitcher Kansa";
				}
				
				if(!in_array($actPerson, $uniquePerson)){
					$uniquePerson[] = $actPerson;
				}
			}
		      }
		      else{
				if(!in_array($doc["creator"], $uniquePerson)){
					$uniquePerson[] = $doc["creator"];
				}
		      }
		      
		  $item_array["person"] = implode(", ", $uniquePerson);
		}
		if(array_key_exists("contributor", $doc)){
			if(is_array($doc["contributor"])){
				foreach($doc["contributor"] as $actPerson){
					if($actPerson == "Sarah W. Kansa"){
						$actPerson = "Sarah Whitcher Kansa";
					}
					if(!in_array($actPerson, $uniquePerson)){
						$uniquePerson[] = $actPerson;
					}
				}
			}
			else{
				if(!in_array($doc["contributor"], $uniquePerson)){
					$uniquePerson[] = $doc["contributor"];
				}
			}
		      
		  $item_array["person"] = implode(", ", $uniquePerson);
		}
		else{
		  $item_array["person"] = null;
		}
		foreach($table_fields['contexts'] as $act_context){
		  if(array_key_exists($act_context, $doc))
		    $item_array[$act_context] = $doc[$act_context];
		  else
		    $item_array[$act_context] = null;
		  }
		$item_array["pub_date"] = date("F j, Y, g:i a", strtotime($doc["pub_date"])); 
		$item_array["update"] = date("F j, Y, g:i a", strtotime($doc["update"]));
		$item_array["category"] = $doc['item_class'];
		$item_array["label"] = $doc['item_label'];
		      
		$itemUUID = $doc['uuid'];
		
		$spaceItem = New Subject;
		$spaceItem->getByID($itemUUID);
		@$xml=simplexml_load_string($spaceItem->archaeoML);
		if(!$xml){
			sleep(.5); //take a break
			unset($spaceItem);
			$spaceItem = New Subject;
			$spaceItem->getByID($itemUUID);
		}
		
		if($spaceItem->archaeoML){
			$atom_out = OpenContext_TableOutput::archaeoML_to_array($table_fields['table_fields'], $spaceItem->archaeoML);
		}
		else{
			$atom_out = array();
		}
		unset($spaceItem);
		
		if(array_key_exists("newFields", $atom_out)){
			if($atom_out["newFields"] != false){
			      foreach($atom_out["newFields"] as $new_item_field){
				      if(!in_array($new_item_field, $newFields)){
					      $newFields[] = $new_item_field;
				      }
			      }
			}
		}
		
		
		if(array_key_exists("props",$atom_out)){
		  foreach($atom_out["props"] AS $var_label => $val)
		    $item_array[$var_label] = $val;
		  }    
		if(array_key_exists("notes", $doc))
		  $item_array["notes"] = $doc["notes"];
		else
		  $item_array["notes"] = null;
		      
		$record_array[$act_id] = $item_array;
        }//end loop through docs
	
	 
	if(count($newFields)>0){
		//echo "bang";
		$this->tableFieldsUpdate($newFields, $table_fields);
		$this->get_store_table_fields($table_fields["meta"]["cache_id"], $table_fields["meta"]["setHash"], $newFields);
	}
	
	
        $all_array = array("meta"=> array("set_uri"=> $table_fields["meta"]["uri"],
											"numFound" => $numFound,
											"numRecs" => count($record_array),
											"page" => $page,
											"offset" => $offset,
											"fieldsCacheID" => $table_fields["meta"]["cache_id"]
                            ),
                           "records" => $record_array
                          );
		
		header('Content-Type: application/json; charset=utf8');
        echo Zend_Json::encode($all_array);
        
    }//end action for tables




    //this function adds fields that were not found (for some reason by the set fields action)
    public function tableFieldsUpdate($newFields, $table_fields){
	$frontendOptions = array('lifetime' => 7200, 'automatic_serialization' => true);
	$backendOptions = array('cache_dir' => './temp_tables_cache/'); // Directory where to put the cache files);
	$cache = Zend_Cache::factory('Core', 'File', $frontendOptions, $backendOptions);
	
	if(count($newFields)>0){
		foreach($newFields as $newField){
			$table_fields["table_fields"][] = $newField;
		}
		$JSONstring = Zend_Json::encode($table_fields);
		$cache->save($JSONstring, ($table_fields["meta"]["cache_id"]));
		//save result to the cache
	}
	
    }


   /*
   *
   * JSON Table Field Names for a Set
   * This function gets the set specific field names for a table
   * it also generates some metadata needed for citation
   */  

  // facets (but no results) in JSON format
  public function setfieldsAction() {
    $this->_helper->viewRenderer->setNoRender(); //no need for a view, data output as JSON string only
    $requestURI = $this->_request->getRequestUri(); 
    $requested_params = $this->_request->getParams();
    //get rid of the page parameter, since this is not needed
    if(array_key_exists("page", $requested_params)){ 
      unset($requested_params["page"]);
	}
    //get rid of the recs paramater, sinc this is not needed
    if(array_key_exists("recs", $requested_params)) {
      unset($requested_params["recs"]);
	}
	
    //make a string that will be hashed and used as the cache ID
    $request_hasher = "";
    foreach($requested_params as $key=>$value){
      $request_hasher .= "_".$key."=".$value;
    }
    
    //echo var_dump($requested_params);
    
    
    $frontendOptions = array('lifetime' => 7200, 'automatic_serialization' => true);
    $backendOptions = array('cache_dir' => './temp_tables_cache/'); // Directory where to put the cache files);
    $cache = Zend_Cache::factory('Core', 'File', $frontendOptions, $backendOptions);
    $cache_id = md5($request_hasher);
    if(!$cache_result = $cache->load($cache_id)){
	//if(true){
		$requestParams =  $this->_request->getParams();
		
		$solrSearch = new solrSearch;
		$solrSearch->initialize();
		$solrSearch->requestURI = $this->_request->getRequestUri();
		$solrSearch->requestParams = $requestParams;
		$solrSearch->PropToTaxaParameter(); // change depricated prop parameters to taxa parameters
		$requestParams = $solrSearch->requestParams; //make sure any changes to request parameters are there for the view page
		
		$solrSearch->spatial = true; //do a search of spatial items in Open Context
		$solrSearch->rawDocsArray = true; //get the raw document output
		$solrSearch->buildSolrQuery();
		
		$param_array = $solrSearch->param_array;
		//$param_array['sort'] = array("item_class desc", "interest_score desc");
		//$param_array['sort'] = "item_class desc, interest_score desc";
	
		//get facets specific for tables, including all def_context paths
		$variable_params = array("variables");
		$context_params = array("contributor",
					"pub_date",
					"update",
					"project_name", "project_id", "creator", "def_context_0", "def_context_1", "def_context_2",
							"def_context_3", "def_context_4", "def_context_5","def_context_6", "def_context_7", "def_context_8",
							"def_context_9");
				
		$param_array["facet.field"] = array_merge($context_params, $variable_params);
		$solrSearch->param_array = $param_array;
		$solrSearch->execute_search();
		$queryString = $solrSearch->queryString;
		$solrSearch->getLatestTime(); //get the last updated
		$solrSearch->getLatestTime(false); //get the last published
	
		$allFacets = $solrSearch->facets;
		$facets = $allFacets["facet_fields"];
		//echo print_r($facets);
		
		$person_links = $facets["contributor"];                 
		$creators = $facets["creator"];                 
	
		/*
		$person_links = array();
		foreach($facets["contributor"] as $actPerson => $value){
			if($actPerson == "Sarah W. Kansa"){
				$actPerson = "Sarah Whitcher Kansa";
			}
			if(in_array($actPerson, $person_links)){
				$person_links[$actPerson] = $person_links[$actPerson] + $value;
			}
			else{
				$person_links[$actPerson] = $value;
			}
		}
	
		$creators = array();
		foreach($facets["creator"] as $actPerson => $value){
			if($actPerson == "Sarah W. Kansa"){
				$actPerson = "Sarah Whitcher Kansa";
			}
			if(in_array($actPerson, $creators)){
				$creators[$actPerson] = $creators[$actPerson] + $value;
			}
			else{
				$creators[$actPerson] = $value;
			}
		}
		*/
	
			
			$cleaned_output = OpenContext_TableOutput::getContexts_CleanFacets($facets);
			$contexts = $cleaned_output["contexts"]; 
			$projects = $cleaned_output["projects"]; 
			$ranked_vars_array = $cleaned_output["ranked_vars"]; 
			
			/*
			$contexts = false;
			$projects = false;
			$ranked_vars_array = false;
			*/
			
			//get numbers of documents found
			$numFound = $solrSearch->numFound;
				  
			$set_uri = str_replace("/createtab/setfields", "/sets", $requestURI);
			$set_uri = str_replace(".json", "", $set_uri);
			$setHash = md5($set_uri);
				  
			$requestURI = str_replace("/sets", "/createtab/setfields", $requestURI);
			$requestURI = str_replace(".json", "", $requestURI);
			  
			//$this->get_store_table_fields($cache_id, $setHash, $contexts);
			$this->get_store_table_fields($cache_id, $setHash, $ranked_vars_array);
			  
			$all_array = array("meta"=> array(
									"uri"=>$requestURI,
									"setURI" => $set_uri,
									"setHash" => $setHash,
									"numFound" => $numFound,
									"person_links" => $person_links,
									"creators" => $creators,
									"projects" => $projects,
					"cache_id" => $cache_id,
					"setLastPublished" => $solrSearch->lastPublished,
					"setLastUpdate" => $solrSearch->lastUpdate
									),
								 "contexts" => $contexts,
								 "table_fields" => $ranked_vars_array,
								 "queryString" => "http://localhost:8983/solr/select/?".$solrSearch->queryString
								 );
          $JSONstring = Zend_Json::encode($all_array);
          $cache->save($JSONstring, $cache_id); //save result to the cache
          header('Content-Type: application/json; charset=utf8');
		  echo $JSONstring;
      }//end case with no cached results
    
    else{
      $JSONstring = $cache_result;
	  header('Content-Type: application/json; charset=utf8');
      echo $JSONstring;
      }//end cached case
    }//end function
  }
