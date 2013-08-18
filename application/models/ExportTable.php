<?php


//this class interacts with the database for accessing and changing table items
class ExportTable {
    
	 public $db;
	 public $tableID;
	 public $title;
	 public $updated;
	 public $created;
	 public $recordCount; //number of records in the total set
	 public $fieldCount; //number of field counts
	 public $metadata; //array for expression as JSON-LD
	 
	 public $citation; //citation generated from metadata
	 
	 public $records; //array of records in field-label => value format
	 public $recPage; //for pagination through records
	 public $recStartIndex; //start record index for paging through records
	 public $recEndIndex; //end record index for paging though records
	 public $recCountPerPage; //number of records per page
	 
	 public $formatArray = array("csv" => "Comma Seperated Value (CSV)",
										  "zip" => "ZIP-compressed CSV file",
										  "gzip" => "GZIP-compressed CSV file",
										  "json" => "Javascript Object Notation (JSON) file",
										  "json-prev" => "Javascript Object Notation (JSON) file [Sample, limited records]"
										  );
	 
	 
	 const defaultRecCountPerPage = 100;
	 const fileDirectory = "./exports";
	 const altFileDirectory = "../exports";
	 
	 //gets table metadata by id
	 function getByID($tableID, $setPage = false){
		  
		  $tableID = $this->security_check($tableID);
		  $setPage = $this->security_check($setPage);
		  $db = $this->startDB();
		  
		  $sql = "SELECT * FROM export_tabs WHERE tableID = '$tableID' LIMIT 1; ";
		  $result = $db->fetchAll($sql);
		  if($result){
				
				$this->title = $result[0]["title"];
				$this->tableID = $tableID;
				$this->updated = $result[0]["updated"];
				$this->created = $result[0]["created"];
				$this->recordCount = $result[0]["recordCount"];
				$this->fieldCount = $result[0]["fieldCount"];
				$metadataJSON = $result[0]["metadata"];
				$metadata = Zend_Json::decode($metadataJSON);
				$this->metadata = $metadata;
				$this->generateCitation();
				return true;
		  }
		  else{
				return false;
		  }
	 }
	 
	 
	 //get paginagion for sets of preview records.
	 function actRecordPage(){
		  if(!$this->recPage){
				$this->recPage = 1;
		  }
		  if(!$this->recCountPerPage){
				$this->recCountPerPage = self::defaultRecCountPerPage;
		  }
		  
		  $this->recStartIndex = ($this->recPage - 1) * $this->recCountPerPage;
		  $this->recEndIndex = $this->recStartIndex + $this->recCountPerPage;
		  
	 }
	 
	 
	 //output a format in a human readable form
	 function getHumanReadFormat($formatKey){
		  $formats = $this->formatArray;
		  if(array_key_exists($formatKey, $formats)){
				return $formats[$formatKey];
		  }
		  else{
				return $formatKey;
		  }
	 }
	 
	 
	 
	 //generate citation from metadata
	 function generateCitation(){
		  
		  $metadata = $this->metadata;
		  $citation = "";
		  $first = true;
		  foreach($metadata["contributorList"] as $nArray){
				if(!$first){
					 $citation .= ", ";
				}
				$citation .= $nArray["contributor"]["name"];
				$first = false;
		  }
		  
		  $citation .= " (".date("Y-m-d", strtotime($metadata["published"])).") ";
		  $citation .= "\"".$metadata["title"]."\" ";
		  
		  $first = true;
		  foreach($metadata["editorList"] as $nArray){
				if(!$first){
					 $citation .= ", ";
				}
				$citation .= $nArray["editor"]["name"];
				$first = false;
		  }
		  
		  if(count($metadata["editorList"]>1)){
				$citation .= " (Eds). Open Context. ";
		  }
		  else{
				$citation .= " (Ed). Open Context. ";
		  }
		  
		  $citation .= htmlentities("<".$metadata["id"]."> ");
		  
		  if(isset($metadata["doi"])){
				$citation .= "DOI <a href=\"http://dx.doi.org/".$metadata["doi"]."\">".$metadata["doi"]."</a>";
		  }
		  elseif(isset($metadata["ark"])){
				$citation .= "ARK <a href=\"http://dx.doi.org/".$metadata["ark"]."\">".$metadata["ark"]."</a>";
		  }
		  
		  $this->citation = $citation;
		  return $citation;
	 }
	 
	 
	 function loadSampleRecords(){
		  
		  $this->actRecordPage();
		  $output = false;
		  $metadata = $this->metadata;
		  
		  $sFilename  = false;
		  if(isset($metadata["files"]["json-prev"]["filename"])){
				$sFilename = self::fileDirectory."/".$metadata["files"]["json-prev"]["filename"];
				$altSfilename = self::altFileDirectory."/".$metadata["files"]["json-prev"]["filename"];
		  }
		  elseif(isset($metadata["files"]["json"]["filename"])){
				$sFilename = self::fileDirectory."/".$metadata["files"]["json"]["filename"];
				$altSfilename = self::altFileDirectory."/".$metadata["files"]["json"]["filename"];
		  }
		  
		  if($sFilename != false){
				
				$fileOK = file_exists($sFilename);
				
				if(!$fileOK){
					 $fileOK = file_exists($altSfilename );
				}
				
				if($fileOK){
					 
					 $fp = fopen($sFilename, 'r');
					 $rHandle = fopen($sFilename, 'r');
					 if ($rHandle){
						  
						  $sData = '';
						  while(!feof($rHandle)){
								$sData .= fread($rHandle, filesize($sFilename));
						  }
						  fclose($rHandle);
						  
						  unset($rHandle);
						  $jsonArray = Zend_Json::decode($sData);
						  unset($sData);
						  $numRecs = count($jsonArray);
						  $records = array();
						  if($this->recStartIndex >= $numRecs){
								return false;
						  }
						  if($this->recEndIndex > $numRecs){
								$this->recEndIndex = $numRecs;
						  }
						  
						  
						  $i = $this->recStartIndex;
						  while($i < $this->recEndIndex){
								$records[] = $jsonArray[$i];
								$i++;
						  }
						  $this->records = $records;
						  unset($jsonArray);
						  $output = $records;
						  
					 }
				}
		  }
		  
		  return $output;
	 }
	 
	 
	 //generates XHTML OK values for review / sample table records.
	 function recXHTMLvalue($value){
		  
		  $output = $value;
		  
		  if(substr($value, 0, 7) == "http://"){
				$value = "<a href=\"".$value."\">".$value."</a>";
				$output = $value;
		  }
		  
		  //attempt some cleanup to make valid XHTML
		  $xmlVal = "<div>".$value."</div>";
		  @$xml = simplexml_load_string($xmlVal);
		  if(!$xml){
				if(function_exists('tidy_repair_string')){
					 $output = tidy_repair_string($xmlVal,
										  array( 
												'doctype' => "omit",
												'input-xml' => true,
												'output-xml' => true 
										  ));
				}
		  }  
		  return $output;
	 }
	 
	 
	 
	 
	 
	 
	 function createUpdate($metadataString){
		  $output = false;
		  
		  $valid = $this->readValidateMetadata($metadataString);
		  unset($metadataString);
		  if($valid){
				
				$output = true;
				$db = $this->startDB();
				$metadata = $this->metadata;
				$tableID = $this->tableID;

				$updatedDB = date("Y-m-d H:i:s", time());
				$createdDB = $updatedDB;
				
				$data = array("title" => $this->title,
								  "recordCount" => $this->recordCount,
								  "updated" => $updatedDB,
								  "recordCount" => $this->recordCount,
								  "fieldCount" => $this->fieldCount
								  );
				
				$recordExists = $this->getByID($tableID); //check to see if the record already exists
				if($recordExists){
					 
					 $metadata["published"] = date("Y-m-d\TH:i:s\-07:00", strtotime($this->created)); //from the old record! Keep creation time same
					 $metadata["updated"] = date("Y-m-d\TH:i:s\-07:00", strtotime($updatedDB));
					 $data["metadata"] = Zend_Json::encode($metadata);
					 
					 $where = "tableID = '$tableID' ";
					 
					 try{
						  $db->update("export_tabs", $data, $where);
					 }
					 catch (Exception $e)  {
						  $output = array("error" => true, "message" => (string)$e );
					 }
				}
				else{
					 
					 $metadata["published"] = date("Y-m-d\TH:i:s\-07:00", strtotime($createdDB));
					 $metadata["updated"] = date("Y-m-d\TH:i:s\-07:00", strtotime($updatedDB));
					 $data["tableID"] = $tableID;
					 $data["created"] = $createdDB;
					 $data["num_views"] = 0;
					 $data["num_downloads"] = 0;
					 $data["metadata"] = Zend_Json::encode($metadata);
					 
					 try{
						  $db->insert("export_tabs", $data);
					 }
					 catch (Exception $e)  {
						  $output = array("error" => true, "message" => (string)$e );
					 }
				}

		  }
		  else{
				$output = array("error" => true, "message" => "JSON not valid");
		  }
		  return $output;
	 }
	 
	 
	 //reads and validates a JSON metadata string
	 function readValidateMetadata($metadataString){
		  
		  $ouput = false;
		  $metadata = Zend_Json::decode($metadataString);
		  
		  if(is_array($metadata)){
				
				$error = false;
				if(!isset($metadata["@context"])){
					 $error = true; //check if this is JSON-LD data
				}
				
				if(isset($metadata["tableID"])){
					 $this->tableID = $metadata["tableID"];
				}
				else{
					 $error = true;
				}
				
				if(isset($metadata["title"])){
					 $this->title = $metadata["title"];
				}
				else{
					 $error = true;
				}
				
				
				if(isset($metadata["recordCount"])){
					 $this->recordCount = $metadata["recordCount"];
				}
				else{
					 $error = true;
				}
				
				if(isset($metadata["fieldCount"])){
					 $this->fieldCount = $metadata["fieldCount"];
				}
				else{
					 $error = true;
				}
				
				
				if(!$error){
					 $this->metadata = $metadata;
					 $output = true;
				}
		  }

		  return $output;
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
