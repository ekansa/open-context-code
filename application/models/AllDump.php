<?php


//this class interacts with the database to help publish a dump of all the XML data
class AllDump {
    
    public $totalItems; //total number of items
    public $recStart; //first item on page
    public $recEnd; //last item on page
    
    public $feedItems; //array of items that will be expressed as entries
    
    const exportDir = "./data"; //export directory
	 const exportPrefix = "opencontext-"; //prefix ahead of the project UUID
	
	 const localGitDirectory = "C:\GitHub\\";
	
    public $db; //database object, used over and over so connection is established only once
    
	 public $itemTypeDirs = array("spatial" => "subjects",
								  "person" => "person-organizations",
								  "table" => "tables",
								  "document" => "documents",
								  "project" => "projects",
								  "media" => "media"
								  );
	 
	 public $recodeXML;
	 public $exportCount;
	
	 public $projectDirs = array();
    
	 public $lookForUpdatedSubjects = false;
    
	 function exportAll(){
	
		  $this->exportCount = 0;
		  $db = $this->startDB();
		  $error = false;
		  $output = array();
		  $projects = $this->getReadyProjects();
		  if(is_array($projects)){
				
				$projectDirs = array();
				
				foreach($projects as $projectUUID){
					 
					 $this->GITsynch($projectUUID); //make sure that any existing GitHub repository files are in the proper structure
					 
					 $projectDirs[$projectUUID][] = self::exportPrefix.$projectUUID; //all projects will have one base root.
					 $this->projectDirs = $projectDirs;
					 
					 $xmlTypeRepositoryParts = array();
					 $xmlTypeRepositoryParts["subjects"] = $this->getSubjectsRepositoryParts($projectUUID);
					 $xmlTypeRepositoryParts["media"] = $this->getMediaRepositoryParts($projectUUID);
					 $xmlTypeRepositoryParts["documents"] = $this->getDocumentsRepositoryParts($projectUUID);
					 
					 $projectDirs = $this->projectDirs;
					 $repositoryParts = $projectDirs[$projectUUID];
					 $rootDirectoryParts = $this->createPartDirectories($repositoryParts); //make the root directory / directories for all repository parts of the current project
					 
					 $firstPart = true;
					 foreach($repositoryParts as $repoPart){
						  
						  $structure = $rootDirectoryParts[$repoPart];
						  if($firstPart){
								//this is the first repository, it gets the general project XML document and the README
								$itemObj = New Project;
								$itemObj->getByID($projectUUID);
								$xml = $itemObj->archaeoML;
								$readMeText = $this->makeProjectREADME($itemObj, $repositoryParts);
								
								$saveOK = $this->validateSaveXML($structure, $projectUUID, $xml);
								if(!$saveOK){
									$error = true;
								}
								
								if(!$error){
									 $this->DBnoteSaveOK($projectUUID);
								}
		  
								unset($itemObj);
								unset($xml);
								$firstPart = false;
						  }
						  
						  $readmeOK = $this->saveREADME($structure, $readMeText); //save the README (needed for all repository parts of a subdivided project)
						  if(!$readmeOK){
								$error = true;
						  }
						  
					 }//end loop through repository parts
					 
					 //now go through and create subdirectories for each XML type and then fill them with XML documents
					 foreach($xmlTypeRepositoryParts as $XMLtype => $typeRepositoryParts){
						  if(is_array($typeRepositoryParts)){
								$actDirectories = $this->createPartDirectories($typeRepositoryParts, $XMLtype); //make the appropriate directories for the current XML type
								if($XMLtype == "subjects"){
									 $this->exportSubjects($projectUUID, $actDirectories);
								}
								elseif($XMLtype == "media"){
									 $this->exportMedia($projects, $actDirectories);
								}
								elseif($XMLtype == "documents"){
									 $this->exportDocuments($projectUUID, $actDirectories);
								}
								unset($actDirectories);
						  }
					 }
					 
					 $output[$projectUUID] = $xmlTypeRepositoryParts;
				}//end loop through projects
		  }//end case with projects

		  return $output;
    }
	 
	 
	 
	 //gets a list of project UUIDs for export
	 function getReadyProjects(){
		  
		  $output = false;
		  $db = $this->startDB();
		  $sql = "SELECT itemUUID FROM noid_bindings WHERE public = 0 AND itemType = 'project' ";

		  $result = $db->fetchAll($sql, 2);
		  if($result){
				$output = array();
				foreach($result as $row){
					 $output[] = $row["itemUUID"];
				}
				
				if($this->lookForUpdatedSubjects){
					 $output = $this->checkUpdatedSubjects($output);
				}
				
		  }
		  else{
				//no projects to export. so look to see if any individual space items need exporting
				$output = $this->checkUpdatedSubjects($output);
		  }
		  
		  return $output;
	 }
	 
	 
	 function checkUpdatedSubjects($projects){
		  
		  $db = $this->startDB();
			
		  if(!is_array($projects)){
				$projects = array();
		  }
		  
		  $sql = "SELECT DISTINCT space.project_id
		  FROM space
		  JOIN noid_bindings ON noid_bindings.itemUUID = space.uuid
		  WHERE noid_bindings.public = 0
		  AND noid_bindings.itemUpdated > timestampadd(day, -7, now());
		  ";
		  
		  $result = $db->fetchAll($sql, 2);
		  if($result){
				foreach($result as $row){
					 if(!in_array($row["project_id"], $projects)){
						  $projects[] = $row["project_id"];
					 }
				}
		  }
		  
		  return $projects; 
	 }
	 
	 
	 //some datasets need to be broken into multiple parts, because they're too large for git hub.
	 
	 
	 //creates the Repository Part directories for different XML document types
	 function createPartDirectories($repositoryParts, $typeSuffix = false){
		  $createdDirectories = array();
		  if(!$typeSuffix){
				$typeSuffix = "";
		  }
		  else{
				$typeSuffix = "/".$typeSuffix ;
		  }
		  
		  foreach($repositoryParts as $repoPart){
				$structure = self::exportDir."/".$repoPart.$typeSuffix;
				if(!file_exists($structure)){
					 if (!mkdir($structure, 777, true)) {
						 $error = true;
						 die('Failed to create folders...');
					 }
				}
				$createdDirectories[$repoPart] = $structure;
		  }
		  return $createdDirectories;
	 }
	 
	 
	 
	 //ensures that the repoPart noted in the database conforms to expectations
	 function validateRepo($repoPart, $projectUUID){
		  
		  if(!strstr($repoPart, self::exportPrefix)){
				$repoPart = self::exportPrefix.$projectUUID;
		  }
		  
		  return $repoPart;
	 }
	 
	 //makes sure that all the repository parts are validated and noted for a given subject, media, or diary query result
	 function validateRepoParts($result, $projectUUID){
		  $output = false;
		  if($result){
				$projectDirs = $this->projectDirs;
				$output = array();
				foreach($result as $row){
					 $repoPart = $row["repo"];
					 $repoPart = $this->validateRepo($repoPart, $projectUUID);
					 if(!in_array($repoPart, $output)){
						  $output[] = $repoPart;
					 }
					 if(!in_array($repoPart, $projectDirs[$projectUUID])){
						  $projectDirs[$projectUUID][] = $repoPart;
					 }
				}
				$this->projectDirs = $projectDirs;
		  }
	 
		  return $output;
	 }
	 
	 function getSubjectsRepositoryParts($projectUUID){
		  $db = $this->startDB();
		  $sql = "SELECT DISTINCT space.repo
					 FROM space
					 WHERE space.project_id =  '$projectUUID'
					 ORDER BY space.repo
					 ";
		  
		  $result = $db->fetchAll($sql, 2);
		  $output = $this->validateRepoParts($result, $projectUUID);
		  return $output;
	 }
	 
	 //some datasets have lots of media files, check for distinct repositories in the media resources
	 function getMediaRepositoryParts($projectUUID){
		  $db = $this->startDB();
		  $sql = "SELECT DISTINCT resource.repo
					 FROM resource
					 WHERE resource.project_id =  '$projectUUID'
					 ORDER BY resource.repo";
		 
		  $result = $db->fetchAll($sql, 2);
		  $output = $this->validateRepoParts($result, $projectUUID);
		  return $output;
	 }
	 
	 
	 //get a list of repo parts for diaries
	 function getDocumentsRepositoryParts($projectUUID){
		  $db = $this->startDB();
		  $sql = "SELECT DISTINCT diary.repo
					 FROM diary
					 WHERE diary.project_id =  '$projectUUID'
					 ORDER BY diary.repo";
		 
		  $result = $db->fetchAll($sql, 2);
		  $output = $this->validateRepoParts($result, $projectUUID);
		  return $output;
	 }
	 
	
	
	//generate a README file for a project
	 function makeProjectREADME($itemObj, $repositoryParts = false){
		  $license = $itemObj->getProjectLicenseByID();
	 
		  $readMeText = "OPEN CONTEXT GITHUB DATA REPOSITORY\r\n\r\n";
		  $readMeText .= "Project: '".$itemObj->label."' \r\n";
		  if(is_array($license)){
				$readMeText .= "License: ".$license["name"]." <".$license["uri"]."> \r\n";
		  }
		  $readMeText .= "Project ID: '".$itemObj->itemUUID."' \r\n\r\n\r\n";
		  
		  if(is_array($repositoryParts)){
				if($repositoryParts[0] == false){
					 unset($repositoryParts[0]);
				}
				
				if(count($repositoryParts)>1){
					 $partNum = 1;
					 $readMeText .= "GitHub repository size restrictions require that this project be divided into ".count($repositoryParts)." parts. The following repositories contain this project's data: \r\n\r\n";
					 foreach($repositoryParts as $repoPart){
						  $readMeText .= "(".$partNum.") https://github.com/ekansa/".$repoPart."\r\n";
						  $partNum++;
					 }
					 $readMeText .= "\r\n\r\n";
				}
		  }
		  
		  $readMeText .= "Open Context <http://opencontext.org> is an open access data publishing service that primarily serves the archaeological community. Open Context uses GitHub for dataset version control and as another channel for data dissemination. While GitHub offers excellent services, Open Context does not regard GitHub as a long-term preservation repository. For data archiving purposes, Open Context works with digital libraries and other dedicated institutional repositories.\r\n\r\n";
		  $readMeText .= "Open Context encourages reuse of these data and adaptation of these data, provided data creators are properly cited and credited.\r\n\r\n";
		  
		  if(!is_array($license)){
				$readMeText .= "Please refer to this project's overview in Open Context at <http://opencontext.org/projects/".$itemObj->itemUUID."> for more background information about these data, including licensing and citation requirements.\r\n";
		  }
		  else{
				$readMeText .= "Please refer to this project's overview in Open Context at <http://opencontext.org/projects/".$itemObj->itemUUID."> for more background information about these data.\r\n";
		  }
		  

		  return $readMeText;
	 }
	
	
	 //export the subjects, adding to the correct directory
	 function exportSubjects($projectUUID, $actDirectories){
		  
		  $db = $this->startDB();
		  
		  $sql = "SELECT noid_bindings.itemUUID, space.repo
		  FROM noid_bindings
		  JOIN space ON space.uuid = noid_bindings.itemUUID
		  WHERE public = 0
		  AND space.project_id = 	'$projectUUID';
		  ";
		  
		  $result = $db->fetchAll($sql, 2);
		  if($result){
				foreach($result as $row){
					 $itemUUID = $row["itemUUID"];
					 $repoPart = $row["repo"];
					 $repoPart = $this->validateRepo($repoPart, $projectUUID);
					 $itemObj = New Subject;
					 $itemObj->getByID($itemUUID);
					 $xml = $itemObj->archaeoML;
					 //$xml = mb_convert_encoding( $itemObj->archaeoML, 'UTF-8');
					 //$xml = utf8_encode( $itemObj->archaeoML);
					 unset($itemObj);
					 
					 $actDir =  $actDirectories[$repoPart]; //get the full directory for the correct subject part
					 
					 $saveOK = $this->validateSaveXML($actDir, $itemUUID, $xml);
					 if($saveOK){
						 $this->DBnoteSaveOK($itemUUID);
					 }
				}
		  }

	 }//end export of subjects
	
	
	
	 //export the subjects, adding to the correct directory
	 function exportMedia($projectUUID,  $actDirectories){
		  
		  $db = $this->startDB();
		  
		  $sql = "SELECT noid_bindings.itemUUID, resource.repo
		  FROM noid_bindings
		  JOIN resource ON resource.uuid = noid_bindings.itemUUID
		  WHERE public = 0
		  AND resource.project_id = 	'$projectUUID';
		  ";
		  
		  $result = $db->fetchAll($sql, 2);
		  if($result){
				foreach($result as $row){
					 $itemUUID = $row["itemUUID"];
					 $repoPart = $row["repo"];
					 $repoPart = $this->validateRepo($repoPart, $projectUUID);
					 
					 $itemObj = New Media;
					 $itemObj->getByID($itemUUID);
					 $xml = $itemObj->archaeoML;
					 //$xml = mb_convert_encoding( $itemObj->archaeoML, 'UTF-8');
					 //$xml = utf8_encode( $itemObj->archaeoML);
					 unset($itemObj);
					 
					 $actDir =  $actDirectories[$repoPart]; //get the full directory for the correct subject part
					 
					 $saveOK = $this->validateSaveXML($actDir, $itemUUID, $xml);
					 if($saveOK){
						 $this->DBnoteSaveOK($itemUUID);
					 }
				}
		  }

	 }//end export of media
	
	
	 //export the subjects, adding to the correct directory
	 function exportDocuments($projectUUID,  $actDirectories){
		  
		  $db = $this->startDB();
		  
		  $sql = "SELECT noid_bindings.itemUUID, diary.repo
		  FROM noid_bindings
		  JOIN diary ON diary.uuid = noid_bindings.itemUUID
		  WHERE public = 0
		  AND diary.project_id = 	'$projectUUID';
		  ";
		  
		  $result = $db->fetchAll($sql, 2);
		  if($result){
				foreach($result as $row){
					 $itemUUID = $row["itemUUID"];
					 $repoPart = $row["repo"];
					 $repoPart = $this->validateRepo($repoPart, $projectUUID);
					 
					 $itemObj = New Document;
					 $itemObj->getByID($itemUUID);
					 $xml = $itemObj->archaeoML;
					 unset($itemObj);
					 //$xml = mb_convert_encoding( $itemObj->archaeoML, 'UTF-8');
					 //$xml = utf8_encode( $itemObj->archaeoML);
					 
					 $actDir =  $actDirectories[$repoPart]; //get the full directory for the correct subject part
					 
					 $saveOK = $this->validateSaveXML($actDir, $itemUUID, $xml);
					 if($saveOK){
						 $this->DBnoteSaveOK($itemUUID);
					 }
				}
		  }

	 }//end export of media
	
	 //update the database to note a successfully saved item
	 function DBnoteSaveOK($itemUUID){
		  $db = $this->startDB();
		  $data = array("public" => true);
		  $where = array();
		  $where[] = "itemUUID = '$itemUUID' ";
		  $db->update("noid_bindings", $data, $where);
		  $this->exportCount++;
	 }
	
	
	
	
	 function validateSaveXML($itemDir, $filename, $xml){
		 
		  $success = false;
		  $xml = $this->xmlOutput($xml);
		  if($xml != false){
				try{
					iconv_set_encoding("internal_encoding", "UTF-8");
					iconv_set_encoding("output_encoding", "UTF-8");
					$fp = fopen($itemDir."/".$filename.'.xml', 'w');
					//fwrite($fp, iconv("ISO-8859-7","UTF-8",$xml));
					//fwrite($fp, utf8_encode($xml));
					fwrite($fp, $xml);
					fclose($fp);
					$success = true;
				}
				catch (Zend_Exception $e){
					$success = false; //save failure
				}
		  }
		  else{
				$success = false; //bad XML
		  }
		  
		  return $success;
	 }
	
	
	
	 //save readme
	 function saveREADME($itemDir, $readMeText){
		  $success = false; //save failure
		  try{
			  iconv_set_encoding("internal_encoding", "UTF-8");
			  iconv_set_encoding("output_encoding", "UTF-8");
			  $fp = fopen($itemDir."/README.txt", 'w');
			  fwrite($fp, $readMeText);
			  fclose($fp);
			  $success = true;
		  }
		  catch (Zend_Exception $e){
			  $success = false; //save failure
		  }
		  return $success;
	 }
	
	
	
   
	 //this function makes pretty XML
	  function xmlOutput($xml){
		 
		 /*
		 $newXML = mb_convert_encoding($xml, "UTF-8");
		 $this->recodeXML = false;
		 if($newXML != $xml){
			 $this->recodeXML = true;
			 $xml = $newXML;
			 unset($newXML);
		 }
		 */
		 
		 @$SimpXML = simplexml_load_string($xml);
		 if($SimpXML){
			 unset($SimpXML);
			 $doc = new DOMDocument('1.0', 'UTF-8');
			 $doc->formatOutput = true;
			 $doc->loadXML($xml);
			 $output = $doc->saveXML();
		 }
		 else{
			 $this->recodeXML = false;
			 unset($SimpXML);
			 $output = false;
		 }
		 return $output;
	 }
	
	
	
	 //updates the repo field to reflect where an XML file really is (incase of edits)
	 function GITsynch($projectUUID){
		  $output = false;
		  $directories = $this->directoryToArray(self::localGitDirectory, false);
		  if(is_array($directories)){
				$rawProjectDirs = array();
				foreach($directories as $dir){
					 if(strstr($dir, $projectUUID)){
						  $rawProjectDirs[$dir] = $dir;
					 }
				}
				
				if(count($rawProjectDirs)>0){
					 $projectDirs = array();
					 foreach($rawProjectDirs as $path){
						  $pathEx = explode("/", $path);
						  $repoName = $pathEx[count($pathEx)-1];
						  $projectDirs[$repoName] = $path;
					 }
					 
					 $outProject = array();
					 foreach($projectDirs as $repoName => $path){
						  $outProject[$repoName]["subjects"] = $this->GITsynchSubjects($projectUUID, $repoName, $path);
					 }
					 
					 $output = $outProject;
				}
		  }
		  
		  return $output;
	  
	 }
	
	 
	 function GITsynchSubjects($projectUUID, $repoName, $path){
		  
		  $output = array("OK" => 0, "Changed" => 0);
		  $repoFiles = $this->directoryToArray($path."/subjects/", false);
		  $db = $this->startDB();
		  foreach($repoFiles as $filepathName){
				
				$uuid = $this->UUIDfromFileName($filepathName);
				$sql = "SELECT repo FROM space WHERE uuid = '$uuid' LIMIT 1; ";
				$result = $db->fetchAll($sql, 2);
				if($result){
					 $dbRepo = $result[0]["repo"];
					 if($dbRepo != $repoName){
						  $sql = "UPDATE space SET repo = '$repoName' WHERE uuid = '$uuid' LIMIT 1; ";
						  $db->query($sql);
						  $output["Changed"] = $output["Changed"] + 1;
					 }
					 else{
						  $output["OK"] = $output["OK"] + 1;
					 }
				}
				else{
					 echo "Holly crap! $filepathName not in the database! ";
					 die;
				}
		  }
		  
		  return $output;
	 }
	 
	 //get the UUID from the filename
	 function UUIDfromFileName($filepathName){
		  $pathEx = explode("/", $filepathName);
		  $filename = $pathEx[count($pathEx)-1];
		  $fileEx = explode(".", $filename);
		  $uuid = $fileEx[0];
		  return $uuid;
	 }
	 
	 
	 //make an array of files in a directory
	 function directoryToArray($directory, $recursive = true) {
		  $array_items = array();
		  if ($handle = opendir($directory)) {
			  while (false !== ($file = readdir($handle))) {
				  if ($file != "." && $file != "..") {
					  if (is_dir($directory. "/" . $file)) {
						  if($recursive) {
							  $array_items = array_merge($array_items, $this->directoryToArray($directory. "/" . $file, $recursive));
						  }
						  $file = $directory . "/" . $file;
						  $array_items[] = preg_replace("/\/\//si", "/", $file);
					  } else {
						  $file = $directory . "/" . $file;
						  $array_items[] = preg_replace("/\/\//si", "/", $file);
					  }
				  }
			  }
			  closedir($handle);
		  }
		  return $array_items;
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
