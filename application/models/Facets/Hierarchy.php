<?php

/*
Manages hierarchy data for URIs to enable faceted search
*/

class Facets_Hierarchy {

	 public $db; //database connection object
	 public $requestParams; //request parameters
    public $countReqChildren; //count of the number of requested children (number of URIs that will be sent to SOLR for an OR search)
	 
	 
	 public $requestParentURIs = array(); //an array of URIs for requested in the hierarchy search. these are the root nodes to find children
	 public $activeVocabFacets; //facets made on the currently active hierarchic vocabulary
	 public $activeOWLsettings; 
	 
	 
	 public $relTypes = array("eol" => "http://purl.org/NET/biol/ns#term_hasTaxonomy"
									  );
	 public $owlSettingFiles = array("eol" => "zoo-eol.owl");
	 const settingsDirectory = "facetSettings";
	 
	 
	 function consolidateRawHierachicFacets($typeKey, $rawFacets){
		  $requestParams = $this->requestParams;
		  $actHierarchyURIs = $this->getActiveHierarchyURIs($typeKey);
		  $relType = $this->getVocabRelationType($typeKey);
		  if(isset($requestParams[$typeKey]) && $relType != false){
				
				$solrRelField = sha1($relType)."_lent_taxon";
				if(isset($rawFacets[$solrRelField])){
					 $rawRelFacets = $rawFacets[$solrRelField];
					 
					 if(!is_array($actHierarchyURIs)){
						  $actHierarchyURIs = array( 0=> $actHierarchyURIs);
					 }
					 
					 
					 $requestParentURIs = $this->requestParentURIs; //array of URI(s) of the requested parrent node(s)
					 
					 $consolidatedVocabFacets = array();
					 
					 $newFacets = array();
					 foreach($rawRelFacets as $facetURIkey => $count){
						  if(in_array($facetURIkey, $requestParentURIs)){
								//$consolidatedVocabFacets[$facetURIkey] = $count;
						  }
						  else{
								$newFacets[$facetURIkey] = $count; 
						  }
					 }
					 unset($rawRelFacets);
					 $rawRelFacets = $newFacets;
					 unset($newFacets);
					 
					 
					 foreach($actHierarchyURIs as $actParentURI){
						  $actChildrenURIs = $this->getLabeledListChildURIs($actParentURI); //get URIs for all the children of the parent URI
						  
						  if(is_array($actChildrenURIs)){
								$consolidatedVocabFacets[$actParentURI] = 0;
								$newFacets = array();
								foreach($rawRelFacets as $facetURIkey => $count){
									 if(array_key_exists($facetURIkey, $actChildrenURIs) && !in_array($facetURIkey, $requestParentURIs)){ //check to see if a facet is a child of the the current parent
										  $consolidatedVocabFacets[$actParentURI] = $consolidatedVocabFacets[$actParentURI] + $count; //add the count of the current
									 }
									 elseif($facetURIkey == $actParentURI){
										  $consolidatedVocabFacets[$actParentURI] = $consolidatedVocabFacets[$actParentURI] + $count; //add the count of the current
									 }
									 else{
										  if($facetURIkey != $actParentURI && !in_array($facetURIkey, $requestParentURIs)){
												$newFacets[$facetURIkey] = $count; //only add if the facet URI is not in the children list and not the parent.
										  }
									 }
								}//end loop through current facets
								unset($rawRelFacets);
								$rawRelFacets = $newFacets;
								unset($newFacets);
								
								if($consolidatedVocabFacets[$actParentURI] == 0){
									 unset($consolidatedVocabFacets[$actParentURI]);
								}
								
						  }
						  
					 }
					 // http://eol.org/pages/4445650
					 
					 //echo "<h2>cooked</h2>"; 
					 //echo print_r($consolidatedVocabFacets);
					 //echo "<h2>raw</h2>";
					 //echo print_r($rawRelFacets);
					 
					 
					 foreach($rawRelFacets as $facetURIkey => $count){
						  if($count>0){
								$consolidatedVocabFacets[$facetURIkey] = $count;
						  }
					 }
					 arsort($consolidatedVocabFacets);
					 
					 //echo "<h2>done</h2>"; 
					 //echo print_r($consolidatedVocabFacets);
					 //die;
					 
					 $newFacets = array();
					 $newFacets[$typeKey] = $consolidatedVocabFacets;
					 foreach($rawFacets as $typeKey => $typeFacets){
						  if($typeKey != $solrRelField){
								$newFacets[$typeKey] = $typeFacets;
						  }
						  else{
								//$newFacets[$typeKey] = $rawRelFacets; // add in the facets removed of those values that were consolidated
						  }
					 }
					 unset($rawFacets);
					 $rawFacets = $newFacets;
					 unset($newFacets);
					 
				}//end case with related facets
		  }
		  
		  return $rawFacets;
	 }
	 
	 function consolidatePreparedHierachicFacets($typeKey, $relFacets){
		  $requestParams = $this->requestParams;
		  $actHierarchyURIs = $this->getActiveHierarchyURIs($typeKey);
		  if($actHierarchyURIs != false){
				$host = OpenContext_OCConfig::get_host_config();
				
				if(!is_array($actHierarchyURIs)){
					 $actHierarchyURIs = array( 0=> $actHierarchyURIs);
				}
				
				$tempVocabFacets = array();
				$vocabFacetSorting = array();
				foreach($actHierarchyURIs as $actParentURI){
					 
					 $actChildrenURIs = $this->getLabeledListChildURIs($actParentURI); //get URIs for all the children of the parent URI
					 if(is_array($actChildrenURIs)){
						  $actParentFacet = array("name" => $actParentURI,
								 "href" => $host.OpenContext_FacetOutput::generateFacetURL($requestParams, $typeKey, $actParentURI),
								 "facet_href" => $host.OpenContext_FacetOutput::generateFacetURL($requestParams, $typeKey, $actParentURI, false, false, "facets_json"),
								 "result_href" => $host.OpenContext_FacetOutput::generateFacetURL($requestParams, $typeKey, $actParentURI, false, false, "results_json"),
								 "linkQuery" => urlencode($actParentURI),
								 "param" => $typeKey,
								 "count" => 0);

						  $newRelFacets = array();
						  foreach($relFacets as $facet){
								if(array_key_exists($facet["name"], $actChildrenURIs)){ //check to see if a facet is a child of the the current parent
									 $actParentFacet["count"] = $actParentFacet["count"] + $facet["count"]; //add the count of the current
									 $vocabFacetSorting[$actParentURI] = $actParentFacet["count"];
								}
								else{
									 if($facet["name"] != $actParentURI){
										  $newRelFacets[] = $facet; //only add if the facet URI is not in the children list and not the parent.
									 }
								}
						  }//end loop through current facets
						  unset($relFacets);
						  $relFacets = $newRelFacets;
						  unset($newRelFacets);
						  $tempVocabFacets[$actParentURI] = $actParentFacet;
					 }//end case where parentURI has children
				}//end loop through parents
				
				arsort($vocabFacetSorting); //sort the vocab facets from high to low of counts;
				$activeVocabFacets = array();
				foreach($vocabFacetSorting as $parentURIkey => $count){
					 $activeVocabFacets[] = $tempVocabFacets[$parentURIkey];
				}
				$this->activeVocabFacets = $activeVocabFacets;
		  }
		  return $relFacets; //return the trimmed down facets
	 }
	 
	 
	 //get the active URIs to colsolidate. The active URIs are child nodes of the current request node
	 function getActiveHierarchyURIs($typeKey){
		  
		  $actHierarchyURIs = false;
		  $requestParams = $this->requestParams;
		  if(isset($requestParams[$typeKey])){
				$rawParent = $requestParams[$typeKey];
				$owlSettingFiles = $this->owlSettingFiles;
				$relTypes = $this->relTypes;
				if(array_key_exists($typeKey, $owlSettingFiles)){
					 $actRelType = $this->getVocabRelationType($typeKey); //what type of linking relation is associated with the current vocabulary, as expressed in $typeKey
					 $owlSettings = $this->getSettingsFromOWL($owlSettingFiles[$typeKey]); //get the hierarchy settings for the current vocabulary
					 if(is_array($owlSettings)){
						  $this->activeOWLsettings = $owlSettings; //may need it later!
						  $OWLobj = new OWL;
						  if($rawParent != "root"){
								if(strstr($rawParent, "||")){
									 $requestParentURIs = explode("||", $rawParent);
								}
								else{
									 $requestParentURIs = array(0 => $rawParent);
								}
								
								if($typeKey == "eol"){
									 $eolObj = new Facets_EOL;
									 $requestParentURIs = $eolObj->validateURIs($requestParentURIs);
								}
								
								$this->requestParentURIs = $requestParentURIs; //array of URI(s) of the requested parrent node(s)
								$OWLrootIRIs = array();
								foreach($requestParentURIs as $actParentURI){
									 $OWLactIRIs = $OWLobj->getIRIfromDefinedBy($actParentURI, $owlSettings["classes"]); 
									 if($OWLactIRIs != false){
										  $OWLrootIRIs = array_merge($OWLrootIRIs, $OWLactIRIs);
									 }
								}
								
						  }
						  else{
								$OWLrootIRIs = $owlSettings["rootParents"]; //the default, is the first root of the owl setting
						  }
					 
						  //now convert the OWL root IRIs of the current parent (nodes) into URIs of child nodes
						  foreach($OWLrootIRIs as $OWLrootIRI){
								$actURIs = $OWLobj->getDefinedByViaHierachy($OWLrootIRI, $owlSettings["classes"], $owlSettings["hierarchy"]);
								if($actURIs != false){
									 if(is_array($actHierarchyURIs)){
										  $actHierarchyURI = array_merge($actHierarchyURIs, $actURIs);
									 }
									 else{
										  $actHierarchyURIs = $actURIs;
									 }
								}
						  }//end loop generateing $actHierarchyURIs
		  
					 }//end case with owlsettings converted to an array
				}//end case with owlSettings
		  }//end case with a request using the typeKey
		  
		  return $actHierarchyURIs;
	 }
	 
	 
	 
	 
	 
	 
	 
	 
	 
	 
	 
	 
	 
	 
	 
	 //get the facet search hieararchy settings from an OWL file
	 function getSettingsFromOWL($owlFile){
		  $settings = false;
		  $OWLstring = $this->loadSettingsFile($owlFile);
		  if($OWLstring != false){
				@$xml = simplexml_load_string($OWLstring);
				if($xml){
					 unset($OWLstring);
					 
					 $OWLobj = new OWL;
					 $OWLobj->xml = $xml;
					 $OWLobj->OWLtoArray();
					 $settings = $OWLobj->owlArray;
					 unset($OWLobj);
				}
				
		  }
		  
		  return $settings;
	 }
	 
	 
	 
	 //this gets an identifier from a GET request and creates a search string
	 //equivalent to a "rel[]=..." search. Or (||) deliminate between various URI that are children concepts in vocabulary hierarchy
	 function generateRelSearchEquivalent($rawParent, $typeKey){
		  $output = false;
		  $relTypes = $this->relTypes;
		  if(isset($relTypes[$typeKey])){
				$actRel = $relTypes[$typeKey];
				if($rawParent != "root"){
					 if(strstr($rawParent, "||")){
						  $parentURIs = explode("||", $rawParent);
					 }
					 else{
						  $parentURIs = array(0 => $rawParent);
					 }
					 
					 if($typeKey == "eol"){
						  $eolObj = new Facets_EOL;
						  $parentURIs = $eolObj->validateURIs($parentURIs);
					 }
					 
					 $searchURIs = $this->getLabeledListChildURIs($parentURIs);
					 $output = $actRel."::";
					 $firstParent = true;
					 foreach($parentURIs as $parentURI){
						  if(!$firstParent){
								 $output .= "||";
						  }
						  $output .= $parentURI;
						  $firstParent = false;  
					 }
					 
					 
					 if(is_array($searchURIs)){
						  $this->countReqChildren = count($searchURIs);
						  if($this->countReqChildren > 0){
								foreach($searchURIs as $searchURIkey => $label){
									 $output .= "||".$searchURIkey; //added after the requested parent
								}
						  }
					 }
				}
				else{
					 $output = $actRel;
				}
		  }
		  
		  return $output;
	 }
	 
	 
	 
	 //gets a list of children where there are labels for the items
	 //this filters down a list to only those ChildURIs that are actually used
	 function getLabeledListChildURIs($parentURIs, $recursive = true, $tree = false){
		  $children = array();
		  $rawChildren = $this->getListChildURIs($parentURIs, $recursive, $tree);
		  if(is_array($rawChildren)){
				foreach($rawChildren as $uriKey => $label){
					 $label = trim($label);
					 if(strlen($label)>1){
						  $children[$uriKey] = $label;
					 }
					 
				}
		  }
		  return $children;	 
	 }
	 
	 //gets children of a given URI as a nested / tree array
	 function getNestedChildURIs($parentURIs, $recursive = true, $tree = false){
		  
		  $db = $this->startDB();
		  
		  if($tree != false){
				$treeCondition = " AND hi.tree = '$tree' ";
		  }
		  else{
				$treeCondition = " ";
		  }
		  
		  if(!is_array($parentURIs)){
				$parentURIs = array(0 => $parentURIs);
		  }
		  $parentCondition = " (";
		  $pFirst = true;
		  foreach($parentURIs as $parentURI){
				if(!$pFirst){
					 $parentCondition .= " OR ";
				}
				$parentCondition .= " hi.parentURI = '$parentURI' ";
				$pFirst = false;
		  }
		  $parentCondition .= ") ";
		  
		  
		  $sql = "SELECT DISTINCT hi.childURI, ld.linkedLabel
		  FROM hierarchies AS hi
		  LEFT JOIN linked_data AS ld ON hi.childURI = ld.linkedURI
		  WHERE $parentCondition
		  $treeCondition
		  ";
		  
		  
		  $result =  $db->fetchAll($sql);
		  if($result){
				$children = array();
				foreach($result as $row){
					 $childURI = $row["childURI"];
					 if($recursive){
						  $row["children"] = $this->getNestedChildURIs($childURI, $recursive, $tree);
					 }
					 $children[] = $row;
				}
		  }
		  else{
				$children = false;
		  }
		  
		  return $children;
	 }
	 
	 
	 
	 //gets children of a given URI as a List
	 function getListChildURIs($parentURIs, $recursive = true, $tree = false){
		  
		  $children = array();
		  $db = $this->startDB();
		  
		  if($tree != false){
				$treeCondition = " AND hi.tree = '$tree' ";
		  }
		  else{
				$treeCondition = " ";
		  }
		  
		  if(!is_array($parentURIs)){
				$parentURIs = array(0 => $parentURIs);
		  }
		  $parentCondition = " (";
		  $pFirst = true;
		  foreach($parentURIs as $parentURI){
				if(!$pFirst){
					 $parentCondition .= " OR ";
				}
				$parentCondition .= " hi.parentURI = '$parentURI' ";
				$pFirst = false;
		  }
		  $parentCondition .= ") ";
		  
		  
		  $sql = "SELECT DISTINCT hi.childURI, ld.linkedLabel
		  FROM hierarchies AS hi
		  LEFT JOIN linked_data AS ld ON hi.childURI = ld.linkedURI
		  WHERE $parentCondition 
		  $treeCondition
		  ";
		  
		  $result =  $db->fetchAll($sql);
		  if($result){
				foreach($result as $row){
					 $childURI = $row["childURI"];
					 if(!array_key_exists($childURI, $children)){
						  $children[$childURI] = $row["linkedLabel"];
					 }
					 if($recursive){
						  $subChildren = $this->getListChildURIs($childURI, $recursive, $tree);
						  foreach($subChildren as $uriKey => $label){
								 if(!array_key_exists($uriKey, $children)){
									 $children[$uriKey] = $label;
								}
						  }
					 }
				}
		  }
		  
		  return $children;
	 }
	 
	 
	 
	 
	 
	 
	  //gets children of a given URI as a List
	 function getListParentURIs($chilrenURIs, $recursive = true, $tree = false){
		  
		  $parents = array();
		  $db = $this->startDB();
		  
		  if($tree != false){
				$treeCondition = " AND hi.tree = '$tree' ";
		  }
		  else{
				$treeCondition = " ";
		  }
		  
		  if(!is_array($chilrenURIs)){
				$chilrenURIs = array(0 => $chilrenURIs);
		  }
		  $childrenCondition = " (";
		  $pFirst = true;
		  foreach($chilrenURIs as $childURI){
				if(!$pFirst){
					 $childrenCondition .= " OR ";
				}
				$childrenCondition .= " hi.childURI = '$childURI' ";
				$pFirst = false;
		  }
		  $childrenCondition .= ") ";
		  
		  
		  $sql = "SELECT DISTINCT hi.parentURI, ld.linkedLabel
		  FROM hierarchies AS hi
		  LEFT JOIN linked_data AS ld ON hi.parentURI = ld.linkedURI
		  WHERE $childrenCondition 
		  $treeCondition
		  ";
		  
		  $result =  $db->fetchAll($sql);
		  if($result){
				foreach($result as $row){
					 $parentURI = $row["parentURI"];
					 if(!array_key_exists($childURI, $parents)){
						  $parents[$parentURI] = $row["linkedLabel"];
					 }
					 if($recursive){
						  $grandParents = $this->getListParentURIs($parentURI, $recursive, $tree);
						  foreach($grandParents as $uriKey => $label){
								 if(!array_key_exists($uriKey, $parents)){
									 $parents[$uriKey] = $label;
								}
						  }
					 }
				}
		  }
		  
		  return $parents;
	 }
	 
	 
	 
	 
	 
	 
	 
	 
	 
	 
	 //add a hiearchy record form parameters
	 function addHierarchyFromParams($parentURI, $childURI, $vocabURI, $tree){
		  $output = false;
		  $db = $this->startDB();
		  $hashID = md5($tree."_".$parentURI."_". $childURI);
		  
		  $data = array("hashID" => $hashID,
							 "parentURI" => $parentURI,
							 "childURI" => $childURI,
							 "vocabURI" => $vocabURI,
							 "tree" => $tree
							 );
		  
		  try{
				$db->insert("hierarchies", $data);
				$output = true;
		  }
		  catch (Exception $e) {
				$error = (string)$e;
				$output = false;
		  }
		  
		  return $output;
	 }
	 
	 //add a hierarchy record from an array
	 function  addHierarchyFromArray($data){
		  $output = false;
		  $db = $this->startDB();
		  
		  if(isset($data["tree"]) && isset($data["parentURI"]) && isset($data["childURI"])){
					 
				$hashID = md5($data["tree"]."_".$data["parentURI"]."_". $data["childURI"]);
				$data["hashID"] = $hashID;
				
				try{
					 $db->insert("hierarchies", $data);
					 $output = true;
				}
				catch (Exception $e) {
					 $error = (string)$e;
					 $output = false;
					 
					 //echo $error;
					 //die;
				}
		  }
		  
		  return $output;
	 }
	 
	 //gets the linking relationship associated with a given vocabulary type
	 function getVocabRelationType($typeKey){
		  $relTypes = $this->relTypes;
		  if(array_key_exists($typeKey, $relTypes)){
				$actRelType = $relTypes[$typeKey]; //what type of linking relation is associated with the current vocabulary, as expressed in $typeKey
		  }
		  else{
				return false;
		  }
		  return $actRelType;
	 }
	 
	 
	 //load a settings file from the settings directory
	 function loadSettingsFile($baseFilename, $itemDir = self::settingsDirectory){
		  iconv_set_encoding("internal_encoding", "UTF-8");
		  iconv_set_encoding("output_encoding", "UTF-8");
		  $data = false;
		  $fileDirName = $itemDir."/".$baseFilename;
		  $fileOK = file_exists($fileDirName);
		  if($fileOK){
				$rHandle = fopen($fileDirName, 'r');
				if($rHandle){
					 $data = '';
					 while(!feof($rHandle)){
						  $data  .= fread($rHandle, filesize($fileDirName));
					 }
					 fclose($rHandle);
					 unset($rHandle);
				}
		  }
		  return $data;
	 }
	 
	 
	 function startDB(){
		  if(!$this->db){
				$db_params = OpenContext_OCConfig::get_db_config();
				$db = new Zend_Db_Adapter_Pdo_Mysql($db_params);
				$db->getConnection();
				$this->setUTFconnection($db);
				$this->db = $db;
		  }
		  else{
				$db = $this->db;
		  }
		  
		  return $db;
	 }
	 
	 function setUTFconnection($db){
		  $sql = "SET collation_connection = utf8_unicode_ci;";
		  $db->query($sql, 2);
		  $sql = "SET NAMES utf8;";
		  $db->query($sql, 2);
    }
	 
}//end class

?>
