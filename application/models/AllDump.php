<?php


//this class interacts with the database to help publish a dump of all the XML data
class AllDump {
    
    public $totalItems; //total number of items
    public $recStart; //first item on page
    public $recEnd; //last item on page
    
    public $feedItems; //array of items that will be expressed as entries
    
    const exportDir = "./data"; //export directory
	 const exportPrefix = "opencontext-"; //prefix ahead of the project UUID
	
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
	
	
	
	function startDB(){
		$db_params = OpenContext_OCConfig::get_db_config();
      $db = new Zend_Db_Adapter_Pdo_Mysql($db_params);
		$db->getConnection();
		$this->setUTFconnection($db);
		$this->db = $db;
	}
	
    
    function exportAll(){
	
		$this->exportCount = 0;
		$this->startDB();
		$this->exportProjects();
		
		
		$projects = $this->exportReadyProjects();
		foreach($projects as $projectUUID){
			$projectDir = self::exportDir."/".self::exportPrefix.$projectUUID;
			
			$this->exportSubjects($projectDir, $projectUUID); // export subject items
			$this->exportMedia($projectDir, $projectUUID); // export media items
			$this->exportDocuments($projectDir, $projectUUID); // export media items
			
		}//end loop through export-ready projects

    }
    
    
	function exportProjects(){
		$db = $this->db;
		
		$sql = "SELECT itemUUID FROM noid_bindings WHERE public = 0 AND itemType = 'project' ";
		
		$result = $db->fetchAll($sql, 2);
		  if($result){ 
			foreach($result as $row){
				$error = false;
				$itemUUID = $row["itemUUID"];	
				$itemObj = New Project;
				$itemObj->getByID($itemUUID);
				$xml = $itemObj->archaeoML;
				
				$projectParts = $this->getRepositoryParts($itemUUID);
				$readMeText = $this->makeProjectREADME($itemObj, $projectParts);
				unset($itemObj);
				
				if(!$projectParts){
					 $projectParts = array(self::exportPrefix.$itemUUID);
				}
				
				$partNum = 1;
				foreach($projectParts as $repoPart){
					 $structure = self::exportDir."/".$repoPart;
					 if(!file_exists($structure)){
						 if (!mkdir($structure, 777, true)) {
							 $error = true;
							 die('Failed to create folders...');
						 }
					 }
					 
					 if(!$error){
						  if($partNum == 1 ){
								//only save the item's xml in the first part of the project
								$saveOK = $this->validateSaveXML($structure, $itemUUID, $xml);
								if(!$saveOK){
									$error = true;
								}
						  }
						  $readmeOK = $this->saveREADME($structure, $readMeText);
					 }
					 $partNum++;
				}
				
				if(!$error){
					$this->DBnoteSaveOK($itemUUID);
				}
			}
		}
	}
	
	//some datasets need to be broken into multiple parts, because they're too large for git hub.
	function getRepositoryParts($projectUUID){
		  $output = false;
		  $db = $this->db;
		  $sql = "SELECT COUNT( uuid ) AS IDcount, repo
					 FROM space
					 WHERE project_id =  '$projectUUID'
					 GROUP BY repo
					 ORDER BY repo";
		  
		  $result = $db->fetchAll($sql, 2);
		  if($result){
				foreach($result as $row){
					 $repoPart = $row["repo"];
					 $output[] = $repoPart;
				}
		  }
		  return $output;
	}
	
	
	
	function exportReadyProjects(){
		  $db = $this->db;
		  
		  //get OK exported projects
		  $sql = "SELECT itemUUID FROM noid_bindings WHERE public = 1 AND itemType = 'project' ";
		  
		  $result = $db->fetchAll($sql, 2);
		  $projects = array();
		  if($result){
				foreach($result as $row){
					 $projects[] = $row["itemUUID"];
				}
		  }
		  return $projects; //list of exported projects
	}
	
	
	//generate a README file for a project
	function makeProjectREADME($itemObj, $projectParts = false){
	 
		  $readMeText = "OPEN CONTEXT GITHUB DATA REPOSITORY\r\n\r\n";
		  $readMeText .= "Project: '".$itemObj->label."' \r\n";
		  $readMeText .= "Project ID: '".$itemObj->itemUUID."' \r\n\r\n\r\n";
		  if(is_array($projectParts)){
				if(count($projectParts)>1){
					 $partNum = 1;
					 $readMeText .= "GitHub repository size restrictions require that this project be divided into ".count($projectParts)." parts. The following repositories contain this project's data: \r\n\r\n";
					 foreach($projectParts as $repoPart){
						  $readMeText .= "(".$partNum.") https://github.com/ekansa/".$repoPart."\r\n";
						  $partNum++;
					 }
					 $readMeText .= "\r\n\r\n";
				}
		  }
		  
		  $readMeText .= "Open Context <http://opencontext.org> is an open access data publishing service that primarily serves the archaeological community. Open Context uses GitHub for dataset version control and as another channel for data dissemination. While GitHub offers excellent services, Open Context does not regard GitHub as a long-term preservation repository. For data archiving purposes, Open Context works with digital libraries and other dedicated institutional repositories.\r\n\r\n";
		  $readMeText .= "Open Context encourages reuse of these data and adaptation of these data, provided data creators are properly cited and credited.\r\n\r\n";
		  $readMeText .= "Please refer to this project's overview in Open Context at <http://opencontext.org/projects/".$itemObj->itemUUID."> for more information on licensing and how to cite these data.\r\n";

		  return $readMeText;
	}
	
	
	
	
	
	
	function exportSubjects($projectDir, $projectUUID, $projectParts = false){
		$db = $this->db;
		
		$sql = "SELECT noid_bindings.itemUUID, space.repo
		FROM noid_bindings
		JOIN space ON space.uuid = noid_bindings.itemUUID
		WHERE public = 0
		AND space.project_id = 	'$projectUUID';
		";
		
		$result = $db->fetchAll($sql, 2);
        if($result){
			
			$error = false;
			$projectParts = $this->getRepositoryParts($projectUUID);
			$dirArray = array();
			foreach($projectParts as $repoPart){
				$subjectDir = self::exportDir."/".$repoPart."/subjects";
				if(!file_exists($subjectDir)){
					if (!mkdir($subjectDir, 777, true)) {
						$error = true;
						die('Failed to create subject folder...');
					}
				}
				$dirArray[$repoPart] = $subjectDir ;
			}
			
			if(!$error){
				foreach($result as $row){
					$itemUUID = $row["itemUUID"];
					$repoPart = $row["repo"];
					$itemObj = New Subject;
					$itemObj->getByID($itemUUID);
					$xml = $itemObj->archaeoML;
					//$xml = mb_convert_encoding( $itemObj->archaeoML, 'UTF-8');
					//$xml = utf8_encode( $itemObj->archaeoML);
					unset($itemObj);
					
					$subjectDir = $dirArray[$repoPart]; //get the full directory for the correct subject part
					$saveOK = $this->validateSaveXML($subjectDir, $itemUUID, $xml);
					if($saveOK){
						$this->DBnoteSaveOK($itemUUID);
					}
				}
			}
		}

	}//end export of subjects
	
	
	function exportMedia($projectDir, $projectUUID){
		$db = $this->db;
		
		$sql = "SELECT noid_bindings.itemUUID
		FROM noid_bindings
		JOIN resource ON resource.uuid = noid_bindings.itemUUID
		WHERE public = 0
		AND resource.project_id = 	'$projectUUID';
		";
		
		$result = $db->fetchAll($sql, 2);
        if($result){
			
			$error = false;
			$mediaDir = $projectDir."/media";
			if(!file_exists($mediaDir)){
				if (!mkdir($mediaDir, 777, true)) {
					$error = true;
					die('Failed to create subject folder...');
				}
			}
			
			if(!$error){
				foreach($result as $row){
					$itemUUID = $row["itemUUID"];
					$itemObj = New Media;
					$itemObj->getByID($itemUUID);
					$xml = $itemObj->archaeoML;
					//$xml = mb_convert_encoding( $itemObj->archaeoML, 'UTF-8');
					
					unset($itemObj);
					
					$saveOK = $this->validateSaveXML($mediaDir, $itemUUID, $xml);
					if($saveOK){
						$this->DBnoteSaveOK($itemUUID);
					}
				}
			}
		}

	}//end export of media
	
	
	
	function exportDocuments($projectDir, $projectUUID){
		$db = $this->db;
		
		$sql = "SELECT noid_bindings.itemUUID
		FROM noid_bindings
		JOIN diary ON diary.uuid = noid_bindings.itemUUID
		WHERE public = 0
		AND diary.project_id = 	'$projectUUID';
		";
		
		$result = $db->fetchAll($sql, 2);
        if($result){
			
			$error = false;
			$docsDir = $projectDir."/documents";
			if(!file_exists($docsDir)){
				if (!mkdir($docsDir, 777, true)) {
					$error = true;
					die('Failed to create subject folder...');
				}
			}
			
			if(!$error){
				foreach($result as $row){
					$itemUUID = $row["itemUUID"];
					$itemObj = New Document;
					$itemObj->getByID($itemUUID);
					$xml = $itemObj->archaeoML;
					unset($itemObj);
					
					$saveOK = $this->validateSaveXML($docsDir, $itemUUID, $xml);
					if($saveOK){
						$this->DBnoteSaveOK($itemUUID);
					}
				}
			}
		}

	}//end export of media
	
	
	
	//update the database to note a successfully saved item
	function DBnoteSaveOK($itemUUID){
		$db = $this->db;
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
	
	
	
	//make sure character encoding is set, so greek characters work
    function setUTFconnection($db){
	    $sql = "SET collation_connection = utf8_unicode_ci;";
	    $db->query($sql, 2);
	    $sql = "SET NAMES utf8;";
	    $db->query($sql, 2);
    }
    
    
}
