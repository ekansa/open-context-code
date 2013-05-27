<?php


//this class interacts with the database for accessing and changing table items
class ExportTable {
    
	 public $tableID;
	 public $title;
	 public $updated;
	 public $created;
	 public $recordCount; //number of records in the total set
	 public $fieldCount; //number of field counts
	 public $metadata; //array for expression as JSON-LD
	 
	 public $citation; //citation generated from metadata
	 
	 public $db;
	 
	 
	 function getByID($tableID, $page = false){
		  
		  $tableID = $this->security_check($tableID);
		  $page = $this->security_check($page);
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
