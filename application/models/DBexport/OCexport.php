<?php
/* This class creates files based on data saved for export.
 * It makes csv, zip (with csv), and gzipped csv files.
 * 
 */

class DBexport_OCexport  {
    
	 public $db; //database connection object
	 
	 public $limitingProjArray = false; //make an array of project UUIDs to limit the results to
	 
	 public $testing = false;
	 
	 public $fileExtensions = array("sql" => ".sql",
											  "zip" => ".zip",
											  "gzip" => ".sql.gz"
											  );
	 
	 const DBdirectory = "data";
	 const testLimit = 5;
	 
	 public $actFileHandle; //the active file handle;
	 public $files = array(); //done files
	 
	 function makeSaveSQL(){
		  $output = array();
		  
		  $output["space"] = $this->makeSaveSpace();
		  $output["big-values"] = $this->makeSaveBigValues();
		  $output["links"] = $this->makeSaveLinks();
		  $output["n-bindings"] = $this->makeSaveNBs();
		  $output["persons"] = $this->makeSavePersons();
		 
		  return $output;
	 }
	 
	 
	 function addTestingLimit(){
		  if($this->testing){
				return " LIMIT ".self::testLimit." ";
		  }
		  else{
				return "";
		  }
	 }
	 
	 
	 
	 function compressSpace(){
		  
		  $output = array();
		  $db = $this->startDB();
		  
		  $sql = "SELECT space.uuid
		  FROM space
		  WHERE 1
		  ";
		  
		  $resultA = $db->fetchAll($sql);
		  //$resultA = false;
		  
		  if($resultA){
				foreach($resultA as $rowA){
					 $uuid = $rowA["uuid"];
					 
					 $sql = "SELECT uuid FROM repository WHERE uuid = '$uuid' LIMIT 1; ";
					 $result = $db->fetchAll($sql);
					 if(!$result ){
						  
						  $sql = "INSERT IGNORE INTO repository (`uuid`,  `project_id`, `type`, `compressed`)
						  SELECT space.uuid, space.project_id, 'space', compress(space.archaeoML)
						  FROM space
						  WHERE space.uuid = '$uuid'
						  LIMIT 1;
						  ";
						  
						  $db->query($sql, 2);
						  $output["normal"][] = $uuid ;
					 }
				}
		  }
		  
		  $sql = "SELECT DISTINCT itemUUID AS uuid
		  FROM big_values
		  WHERE itemType = 'space'
		  ";
		  
		  $resultA = $db->fetchAll($sql);
		  
		  if($resultA){
				$host = OpenContext_OCConfig::get_host_config();
				
				$opts = array('http' =>
					 array(
						'timeout' => 120
					 )
				  );
												  
				$context  = stream_context_create($opts);
				
				
				foreach($resultA as $rowA){
					 $uuid = $rowA["uuid"];
					 
					 $sql = "SELECT uuid, project_id FROM space WHERE uuid = '$uuid' LIMIT 1; ";
					 $result = $db->fetchAll($sql);
					 $projectID = $result[0]["project_id"];
				
					 $url = $host."/subjects/".$uuid.".xml";
					 @$xmlString = file_get_contents($url, false, $context);
					 
					 if($xmlString){
					 
						  $where = " uuid = '$uuid' ";
						  $db->delete("repository", $where);
						  $qXML = mysql_real_escape_string(gzcompress($xmlString, 9));
						  
						  $sql = "INSERT INTO repository (`uuid`,  `project_id`, `type`, `compressed`)
						  VALUES ('$uuid', '$projectID', 'space', compress('".$qXML."');
						  ";
						  
						  $sql = "INSERT INTO repository (`uuid`,  `project_id`, `type`, `compressed`)
						  SELECT '$uuid', '$projectID', 'space', compress(GROUP_CONCAT(big_values.value_frag))
						  FROM big_values
						  WHERE itemUUID = '$uuid'
						  ORDER BY id
						  ";
						  
						  //echo  $sql;
						  //die;
						  
						  $db->query($sql, 2);
						  
						  $output["big"][] = $uuid ;
						  
					 }
				}
		  }
		  
		  return $output;
	 }
	 
	 
	 function compressSubjects(){
		  
		  $output = array();
		  $output["prior-done"] = 0;
		  $db = $this->startDB();
		  
		  $sql = 'SELECT space.uuid, space.project_id
		  FROM space
		  WHERE updated  >=  "2013-08-24 00:00:00"
		  ';
		  
		  $result = $db->fetchAll($sql);
		  //$resultA = false;
		  
		  if($result){
				
				$host = OpenContext_OCConfig::get_host_config();
				
				$opts = array('http' =>
					 array(
						'timeout' => 120
					 )
				  );
												  
				$context  = stream_context_create($opts);
				
				foreach($result as $row){
					 $uuid = $row["uuid"];
					 $projectID = $row["project_id"];
					 
					 
					 $sql = "SELECT uuid FROM repository WHERE uuid = '$uuid' LIMIT 1; ";
					 
					 //$repoResult = $db->fetchAll($sql);
					 $repoResult = false;
					 if(!$repoResult){
						  
						  $sql = "SELECT itemUUID FROM noid_bindings WHERE itemUUID = '$uuid' LIMIT 1; ";
						  $noidResult = $db->fetchAll($sql);
						  if($noidResult){
								$url = $host."/subjects/".$uuid.".xml";
								@$xmlString = file_get_contents($url, false, $context);
								if(!@$xmlString){
									 $url = "http://opencontext.org/subjects/".$uuid.".xml";
									 @$xmlString = file_get_contents($url, false, $context);
								}
								
								if($xmlString){
									 //$where = " uuid = '$uuid' ";
									 //$db->delete("repository", $where);
								
									 $data = array("uuid" => $uuid,
														"project_id" => $projectID,
														"type" => "subjects",
														"compressed" => gzcompress($xmlString, 9)
														);
									 
									 try{
										  $db->insert("repository", $data);
										  $output["subjects"][] = $uuid;
									 }
									 catch (Exception $e) {
										  
										  unset($data["uuid"]);
										  $where = "uuid = '$uuid' ";
										  $db->update("repository", $data, $where);
										  $output["up-subjects"][] = $uuid;
										  //$e = (string)$e;
										  //echo $e;
										  //die;
									 }
									 
								}
								else{
									 $output["xml-error"][] = array("uuid" => $uuid, "projectID" => $projectID, "url" => $url  );
								}
						  }
						  else{
								$output["xml-no-noid"][] = array("uuid" => $uuid, "projectID" => $projectID);
						  }
		  
					 }//not in repository
					 else{
						  $output["prior-done"] = $output["prior-done"] + 1;
					 }
				}
				
		  }
		  
		  
		  return $output;
	 }
	 
	 
	 function compressMedia(){
		  
		  $output = array();
		  $output["prior-done"] = 0;
		  $db = $this->startDB();
		  
		  $sql = 'SELECT uuid, project_id
		  FROM resource
		  WHERE 1
		  ';
		  
		  $result = $db->fetchAll($sql);
		  //$resultA = false;
		  
		  if($result){
				
				$host = OpenContext_OCConfig::get_host_config();
				
				$opts = array('http' =>
					 array(
						'timeout' => 120
					 )
				  );
												  
				$context  = stream_context_create($opts);
				
				foreach($result as $row){
					 $uuid = $row["uuid"];
					 $projectID = $row["project_id"];
					 
					 
					 $sql = "SELECT uuid FROM repository WHERE uuid = '$uuid' LIMIT 1; ";
					 
					 $repoResult = $db->fetchAll($sql);
					 
					 if(!$repoResult){
						  
						  $sql = "SELECT itemUUID FROM noid_bindings WHERE itemUUID = '$uuid' LIMIT 1; ";
						  $noidResult = $db->fetchAll($sql);
						  if($noidResult){
								$url = $host."/media/".$uuid.".xml";
								@$xmlString = file_get_contents($url, false, $context);
								if(!@$xmlString){
									 $url = "http://opencontext.org/media/".$uuid.".xml";
									 @$xmlString = file_get_contents($url, false, $context);
								}
								
								if($xmlString){
									 //$where = " uuid = '$uuid' ";
									 //$db->delete("repository", $where);
								
									 $data = array("uuid" => $uuid,
														"project_id" => $projectID,
														"type" => "media",
														"compressed" => gzcompress($xmlString, 9)
														);
									 
									 try{
										  $db->insert("repository", $data);
										  $output["media"][] = $uuid;
									 }
									 catch (Exception $e) {
										  $e = (string)$e;
										  echo $e;
										  die;
									 }
									 
								}
								else{
									 $output["xml-error"][] = array("uuid" => $uuid, "projectID" => $projectID, "url" => $url  );
								}
						  }
						  else{
								$output["xml-no-noid"][] = array("uuid" => $uuid, "projectID" => $projectID);
						  }
		  
					 }//not in repository
					 else{
						  $output["prior-done"] = $output["prior-done"] + 1;
					 }
				}
				
		  }
		  
		  
		  return $output;
	 }
	 
	 
	 	 function compressDocuments(){
		  
		  $output = array();
		  $output["prior-done"] = 0;
		  $db = $this->startDB();
		  
		  $sql = 'SELECT uuid, project_id
		  FROM diary
		  WHERE 1
		  ';
		  
		  $result = $db->fetchAll($sql);
		  //$resultA = false;
		  
		  if($result){
				
				$host = OpenContext_OCConfig::get_host_config();
				
				$opts = array('http' =>
					 array(
						'timeout' => 120
					 )
				  );
												  
				$context  = stream_context_create($opts);
				
				foreach($result as $row){
					 $uuid = $row["uuid"];
					 $projectID = $row["project_id"];
					 
					 
					 $sql = "SELECT uuid FROM repository WHERE uuid = '$uuid' LIMIT 1; ";
					 
					 $repoResult = $db->fetchAll($sql);
					 
					 if(!$repoResult){
						  
						  $sql = "SELECT itemUUID FROM noid_bindings WHERE itemUUID = '$uuid' LIMIT 1; ";
						  $noidResult = $db->fetchAll($sql);
						  if($noidResult){
								$url = $host."/documents/".$uuid.".xml";
								@$xmlString = file_get_contents($url, false, $context);
								if(!@$xmlString){
									 $url = "http://opencontext.org/documents/".$uuid.".xml";
									 @$xmlString = file_get_contents($url, false, $context);
								}
								
								if($xmlString){
									 //$where = " uuid = '$uuid' ";
									 //$db->delete("repository", $where);
								
									 $data = array("uuid" => $uuid,
														"project_id" => $projectID,
														"type" => "documents",
														"compressed" => gzcompress($xmlString, 9)
														);
									 
									 try{
										  $db->insert("repository", $data);
										  $output["documents"][] = $uuid;
									 }
									 catch (Exception $e) {
										  $e = (string)$e;
										  echo $e;
										  die;
									 }
									 
								}
								else{
									 $output["xml-error"][] = array("uuid" => $uuid, "projectID" => $projectID, "url" => $url  );
								}
						  }
						  else{
								$output["xml-no-noid"][] = array("uuid" => $uuid, "projectID" => $projectID);
						  }
		  
					 }//not in repository
					 else{
						  $output["prior-done"] = $output["prior-done"] + 1;
					 }
				}
				
		  }
		  
		  
		  return $output;
	 }
	 
	 
	 
	  //saves a file for moving space / subject items
	 function makeSaveSpace(){
		  
		  $db = $this->startDB();
		  
		  $limit = $this->addTestingLimit();
		  $projCondition = "1";
		  if(is_array($this->limitingProjArray)){
				$projCondition = $this->makeORcondition($this->limitingProjArray, "project_id", "space");
		  }
		  
		  
		  
		  $sql = "SELECT uuid
		  FROM space
		  WHERE $projCondition
		  $limit
		  ;
		  
		  ";
		  
		  $resultA = $db->fetchAll($sql);
		  
		  $data = "";
		  if($resultA){
				$prefix = false;
				$this->startFileHandle(self::DBdirectory, "oc-space");
				foreach($resultA as $rowA){
					 $uuid = $rowA["uuid"];
					 $sql = "SELECT * FROM space WHERE uuid = '$uuid' LIMIT 1; ";
					 
					 $result = $db->fetchAll($sql);
					 if(!$prefix){
						  $prefix = $this->makeInsertPrefix($result[0], "space");
					 }
					 $insertVals = $this->makeInsertValues($result[0]);
					 $data = $prefix.$insertVals;
					 $this->saveAppendSQL($data); //save the data to a file
					 unset($data);
					 unset($result);
				}
				$this->closeFileHandle();
		  }
		  
		  return count($resultA);
	 }
	 
	 
	  //saves a file for moving big value items
	 function makeSaveBigValues(){
		  
		  $db = $this->startDB();
		  
		  $limit = $this->addTestingLimit();
		  $projCondition = "1";
		  if(is_array($this->limitingProjArray)){
				$projCondition = $this->makeORcondition($this->limitingProjArray, "project_id", "space");
		  }
		  
		  
		  
		  $sql = "SELECT big_values.id
		  FROM big_values
		  JOIN space ON ( space.uuid = big_values.itemUUID AND big_values.itemType = 'space')
		  WHERE $projCondition
		  $limit
		  ;
		  
		  ";
		  
		  $resultA = $db->fetchAll($sql);
		  $data = "";
		  if($resultA){
				$prefix = false;
				$this->startFileHandle(self::DBdirectory, "oc-bv");
				foreach($resultA as $rowA){
					 $uuid = $rowA["id"];
					 $sql = "SELECT * FROM big_values WHERE id = '$id' LIMIT 1; ";
					 
					 $result = $db->fetchAll($sql);
					 if(!$prefix){
						  $prefix = $this->makeInsertPrefix($result[0], "big_values");
					 }
					 $insertVals = $this->makeInsertValues($result[0]);
					 $data = $prefix.$insertVals;
					 $this->saveAppendSQL($data); //save the data to a file
					 unset($data);
					 unset($result);
				}
				$this->closeFileHandle();
		  }
		  
		  return count($resultA);
	 }
	 
	 
	 
	 
	 
	  //saves a file for moving person items
	 function makeSavePersons(){
		  
		  $db = $this->startDB();
		  $limit = $this->addTestingLimit();
		  
		  $projCondition = "1";
		  if(is_array($this->limitingProjArray)){
				$projCondition = $this->makeORcondition($this->limitingProjArray, "project_id", "persons");
		  }
		  
		  
		  $sql = "SELECT *
		  FROM persons
		  WHERE $projCondition
		  $limit
		  ;
		  
		  ";
		  
		  $result = $db->fetchAll($sql);
		  if($result){
				$prefix = $this->makeInsertPrefix($result[0], "persons");
				$this->startFileHandle(self::DBdirectory, "oc-pers");
				foreach($result as $row){
					 $insertVals = $this->makeInsertValues($row);
					 $data = $prefix.$insertVals;
					 $this->saveAppendSQL($data); //save the data to a file
				}
				$this->closeFileHandle();
		  }
		  
		  return count($result);
	 }
	 
	 
	 
	  //saves a file for moving person items
	 function makeSaveLinks(){
		  
		  $db = $this->startDB();
		  $limit = $this->addTestingLimit();
		  $projCondition = "1";
		  if(is_array($this->limitingProjArray)){
				$projCondition = $this->makeORcondition($this->limitingProjArray, "project_id", "links");
		  }
		  
		  
		  $sql = "SELECT *
		  FROM links
		  WHERE $projCondition
		  $limit
		  ;
		  
		  ";
		  
		  $result = $db->fetchAll($sql);
		  if($result){
				$prefix = $this->makeInsertPrefix($result[0], "links");
				$this->startFileHandle(self::DBdirectory, "oc-links");
				foreach($result as $row){
					 $insertVals = $this->makeInsertValues($row);
					 $data = $prefix.$insertVals;
					 $this->saveAppendSQL($data); //save the data to a file
				}
				$this->closeFileHandle();
		  }
		  
		  return count($result);
	 }
	 
	 
	 
	  //saves a file for moving person items
	 function makeSaveNBs(){
		  
		  $db = $this->startDB();
		  $limit = $this->addTestingLimit();
		  
		  $sql = "SELECT *
		  FROM noid_bindings
		  WHERE 1
		  $limit
		  ;
		  ";
		  
		  $result = $db->fetchAll($sql);

		  if($result){
				$prefix = $this->makeInsertPrefix($result[0], "noid_bindings");
				$this->startFileHandle(self::DBdirectory, "oc-nb");
				foreach($result as $row){
					 $insertVals = $this->makeInsertValues($row);
					 $data = $prefix.$insertVals;
					 $this->saveAppendSQL($data); //save the data to a file
				}
				
				$this->closeFileHandle();
		  }
		  
		  return count($result);
	 }
	 
	 
	 
	 
	 
	 function makeInsertPrefix($row, $insertTabName, $insertIgnore = true){
		  
		  if($insertIgnore){
				$output = "INSERT IGNORE INTO `".$insertTabName."` ";
		  }
		  else{
				$output = "INSERT INTO `".$insertTabName."` ";
		  }
		  
		  $firstLoop = true;
		  foreach($row as $fieldKey => $value){
				$field = "`".$fieldKey."`";
				if($firstLoop){
					 $output .= "(".$field;
					 $firstLoop = false;
				}
				else{
					 $output .= ", ".$field;
				}
				
		  }
		  
		  $output .= ") \n";
		  
		  return $output;
	 }
	 
	 function makeInsertValues($row){
		  
		  $firstLoop = true;
		  foreach($row as $fieldKey => $value){
				$value= "'".addslashes($value)."'";
				if($firstLoop){
					 $output = "VALUES (".$value;
					 $firstLoop = false;
				}
				else{
					 $output .= ", ".$value;
				}
				
		  }
		  $output .= "); \n\n";
		  return $output;
	 }
	 
	 
	 //makes an OR condition for a given value array, field, and maybe table
	 function makeORcondition($valueArray, $field, $table = false){
		  
		  if(!is_array($valueArray)){
				$valueArray = array(0 => $valueArray);
		  }
		  
		  if(!$table){
				$fieldPrefix = $field;
		  }
		  else{
				$fieldPrefix = $table.".".$field;
		  }
		  $allCond = false;
		  foreach($valueArray as $value){
				$actCond = "$fieldPrefix = '$value'";
				if(!$allCond ){
					 $allCond  = $actCond;
				}
				else{
					 $allCond  .= " OR ".$actCond;
				}
		  }
		  return $allCond ;
	 }
	 
	 
	 

	 //save the file in the correct correct directory
	 function saveSQL($itemDir, $baseFilename, $fileText){
		
		  $fileExtensions = $this->fileExtensions;
		  $success = false;
		
		  try{
				iconv_set_encoding("internal_encoding", "UTF-8");
				iconv_set_encoding("output_encoding", "UTF-8");
				$fp = fopen($itemDir."/".$baseFilename.$fileExtensions["sql"], 'w');
				//fwrite($fp, iconv("ISO-8859-7","UTF-8",$xml));
				//fwrite($fp, utf8_encode($xml));
				fwrite($fp, $fileText);
				fclose($fp);
				$success = true;
		  }
		  catch (Zend_Exception $e){
				$success = false; //save failure
				echo (string)$e;
				die;
		  }
		
		  return $success;
	 }
	 
	 
	 
	 function buildFileName($itemDir, $baseFilename, $i = 0){
		  //this builds a file name, make sures it does not yet exist
		  
		  $fileExtensions = $this->fileExtensions;
		  
		  if($i == 0){
				$actFileName = $itemDir."/".$baseFilename.$fileExtensions["sql"];
		  }
		  else{
				$actFileName = $itemDir."/".$baseFilename."-".$i.$fileExtensions["sql"];
		  }
		  
		  if(file_exists($actFileName)){
				$iPlus = $i + 1;
				$actFileName = $this->buildFileName($itemDir, $baseFilename, $iPlus);
		  }
		  
		  return $actFileName;
	 }
	 
	 
	 // open a new file handle to append
	 function startFileHandle($itemDir, $baseFilename){
		  $files = $this->files;
		  iconv_set_encoding("internal_encoding", "UTF-8");
		  iconv_set_encoding("output_encoding", "UTF-8");
		  
		  $actFileName = $this->buildFileName($itemDir, $baseFilename); //build a new, non existing filename
		  $fh = fopen($actFileName, 'a') or die("can't open file");
		  $files[] = $actFileName;
		  $this->files = $files;
		  
		  //add the UTF-8 code to make sure MySQL handles UTF-8 OK
		  $data = " SET collation_connection = utf8_unicode_ci; ";
		  $data .= " SET NAMES utf8; ";
		  $this->saveAppendSQL($data);
		  
		  $this->actFileHandle = $fh;
	 }
	 
	 //now append the data
	 function saveAppendSQL($data){
		  
		  $fh = $this->actFileHandle;
		  fwrite($fh, $data);
		  $this->actFileHandle = $fh;
		  
	 }
	 
	 // close the file handle
	 function closeFileHandle(){
		  $fh = $this->actFileHandle;
		  fclose($fh);
		  $this->actFileHandle = false;
	 }
	 
	 
	 
	 //save the file as a GZIP file
	 function saveGZIP($itemDir, $baseFilename, $fileText){
		
		  $fileExtensions = $this->fileExtensions;
		  $success = false;
		
		  try{
				
				$gzFileName = $itemDir."/".$baseFilename.$fileExtensions["gzip"];
				
				iconv_set_encoding("internal_encoding", "UTF-8");
				iconv_set_encoding("output_encoding", "UTF-8");
				
				$gz = gzopen($gzFileName,'w9');
				gzwrite($gz, $fileText);
				gzclose($gz);
				
				$success = true;
		  }
		  catch (Zend_Exception $e){
				$success = false; //save failure
				echo (string)$e;
				die;
		  }
		
		  return $success;
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
	 

    
}  
