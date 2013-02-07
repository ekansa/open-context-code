<?php

class Debug_ArchiveFeed  {
    
   
    public $db;
    //public $feedFile = "opencontext.xml";
    //public $dir = "C:\\GitHub\\open-context-code\\public\\comp_cache\\";
	 public $lastActivePage;
	 public $doTest = true;
	 
	 public $errors;
	 
	 public $nameSpaceArray = array("atom" => "http://www.w3.org/2005/Atom",
					  "opensearch" => "http://a9.com/-/spec/opensearch/1.1/");
	 
	 
	 public function getLastPage(){
		  $db = $this->startDB();
		  $sql = "SELECT max(page) AS maxPage FROM archiveFeed WHERE 1";
		  $result =  $db->fetchAll($sql);
		  if($result){
				return $result[0]["maxPage"] + 0;
		  }
		  else{
				return 0;
		  }
	 }
	 
	 //get the list of errors
	 public function getErrors($type = false){
		  
		  $typeLimit = "";
		  if($type != false){
				$typeLimit = " AND id LIKE '%/$type/%' ";
		  }
		  $output = array();
		  $db = $this->startDB();
		  $sql = "SELECT * FROM archiveFeed WHERE status = 'errors' $typeLimit ";
		  $result =  $db->fetchAll($sql);
		  if($result){
				foreach($result as $row){
					 $actOut = $row;
					 if(strlen($row["linkErrors"])>1){
						  $actOut["linkErrors"]=  Zend_Json::decode($row["linkErrors"]);
					 }
					 if(strlen($row["otherErrors"])>1){
						  $actOut["otherErrors"]=  Zend_Json::decode($row["otherErrors"]);
					 }
					 $actOut["checkLink"] = "http://".$_SERVER["SERVER_NAME"]."/test/update-archive-page?page=".$row["page"];
					 $actOut["feedLink"] = "http://opencontext.org/all/.atom?page=".$row["page"];
					 $output[] = $actOut;
				}
		  }

	 	  return $output; 
	 }
	 
	 //get the list of errors
	 public function getErrorPages($type = false){
		  
		  $typeLimit = "";
		  if($type != false){
				$typeLimit = " AND id LIKE '%/$type/%' ";
		  }
		  $output = array();
		  $db = $this->startDB();
		  $sql = "SELECT DISTINCT page FROM archiveFeed WHERE status = 'errors' $typeLimit ";
		  $result =  $db->fetchAll($sql);
		  if($result){
				foreach($result as $row){
					 $output[] = $row["page"];
				}
		  }

	 	  return $output; 
	 }
	 
	  //get the list pages that are short (not enough entries)
	 public function getShortPages($type = false){
		  
		  $typeLimit = "";
		  if($type != false){
				$typeLimit = " AND id LIKE '%/$type/%' ";
		  }
		  $output = array();
		  $db = $this->startDB();
		  $sql = "SELECT COUNT( id ) AS pageCount, page
					 FROM archivefeed
					 GROUP BY PAGE 
					 ORDER BY pageCount";
					 
		  $result =  $db->fetchAll($sql);
		  if($result){
				foreach($result as $row){
					 if($row["pageCount"] < 25){
						  $output[] = $row["page"];
					 }
					 else{
						  break;
					 }
				}
		  }

	 	  return $output; 
	 }
	 
	 
	 function repoSet(){
		  $db = $this->startDB();
		  $sql = "SELECT uuid FROM space WHERE project_id = 'A5DDBEA2-B3C8-43F9-8151-33343CBDC857' ORDER BY uuid";
		  $result =  $db->fetchAll($sql);
		  $maxRepoSize = 50000;
		  $i = 0;
		  foreach($result as $row){
				$uuid = $row["uuid"];
				$repoID = floor(($i /  $maxRepoSize))+1;
				$repo = "opencontext-A5DDBEA2-B3C8-43F9-8151-33343CBDC857";
				if($repoID > 1){
					 $repo .= "-".$repoID;
					 $data = array("repo" => $repo);
					 $where = "uuid = '$uuid' ";
					 $db->update("space", $data, $where);
				}
				$i++;
		  }
	 }
	 
	 
	 
	 function cycleArchiveFeed(){
		  $errors = array();
		  $activeFeedPage = $this->getLastPage() + 1;
		  $continueCycle = true;
		  while($continueCycle){
				$this->lastActivePage = $activeFeedPage;
				$actURL = "http://opencontext.org/all/.atom?page=".$activeFeedPage;
				@$xmlString = file_get_contents($actURL);
				if(!$xmlString){
					 sleep(5);
					 @$xmlString = file_get_contents($actURL);
				}
				
				if(!$xmlString){
					 $this->recordError("HTTP error with ".$actURL);
					 $continueCycle = false;
					 break;
				}
				else{
					 @$xml = simplexml_load_string($xmlString);
					 if(!$xml){
						  sleep(5);
						  @$xmlString = file_get_contents($actURL);
						  if($xmlString){
								@$xml = simplexml_load_string($xmlString);
						  }
					 }
					 
					 if(!$xml){
						  $this->recordError("XML parse error with ".$actURL);
						  $continueCycle = false;
						  break;
					 }
					 else{
						  $this->checkFeed($xml, $activeFeedPage);
						  sleep(2);
						  $activeFeedPage++;
					 }
				}
				
				if($this->doTest){
					 if($activeFeedPage >= 3){
						  $continueCycle = false;
					 }
				}
		  }
		  
	 }
	 
	 
	  function updateStatusFeed($activeFeedPage){
		  
		  $this->lastActivePage = $activeFeedPage;
		  $actURL = "http://opencontext.org/all/.atom?page=".$activeFeedPage;
		  @$xmlString = file_get_contents($actURL);
		  if(!$xmlString){
				sleep(5);
				@$xmlString = file_get_contents($actURL);
		  }
		  
		  if(!$xmlString){
				$this->recordError("HTTP error with ".$actURL);
				return false;
		  }
		  else{
				@$xml = simplexml_load_string($xmlString);
				if(!$xml){
					 sleep(5);
					 @$xmlString = file_get_contents($actURL);
					 if($xmlString){
						  @$xml = simplexml_load_string($xmlString);
					 }
				}
					 
				if(!$xml){
					 $this->recordError("XML parse error with ".$actURL);
					 return false;
				}
				else{
					 $entryErrors = $this->checkFeed($xml, $activeFeedPage, true);
					 return  $entryErrors;
				}
		  }
	 }
	 
	 
	 function checkFeed($xml, $feedPage, $update = false){
		  $entryErrors = array();
		  $db = $this->startDB();
		  $xml = $this->registerNameSpaces($xml);
		  $entryIndex = 1;
		  foreach($xml->xpath("//atom:entry") as $entry){
				$entry = $this->registerNameSpaces($entry);
				$status = "ok";
				$id = false;
				foreach($entry->xpath("./atom:id") as $id){
					 $id = (string)$id;
				}
				$title = false;
				foreach($entry->xpath("./atom:title") as $title){
					 $title = (string)$title;
				}
				$authorContrib = false;
				foreach($entry->xpath("./atom:author") as $author){
					 $authorContrib = "found";
				}
				if(!$authorContrib){
					 foreach($entry->xpath("./atom:contributor") as $author){
						  $authorContrib = (string)$author;
					 }
				}
				$updated = false;
				foreach($entry->xpath("./atom:updated") as $updated){
					 $updated = (string)$updated;
				}
				$published = false;
				foreach($entry->xpath("./atom:published") as $published){
					 $published = (string)$published;
				}
				
				$okLinks = false;
				$linkErrors = array();
				$linkCount = 0;
				foreach($entry->xpath("./atom:link") as $link){
					 $link = $this->registerNameSpaces($link);
					 $href = $this->checkAttributes("href", $link);
					 $type = $this->checkAttributes("type", $link);
					 $rel = $this->checkAttributes("rel", $link);
					 
					 if(strstr($rel, "http://") && !$type){
						  $type = "Linked data HTML";
					 }
					 
					 if(!$href || !$type || !$rel){
						  $linkErrors[] = "@href = '$href' @rel = '$rel' @type = '$type' ";
					 }
					 if(strstr($href, "http://opencontext/")){
						  $linkErrors[] = "Bad @href = '$href' @rel = '$rel' @type = '$type' ";
					 }
					 $linkCount++;
				}
				if($linkCount < 1){
					 $linkErrors[] = "No links";
				}
				
				
				if(count($linkErrors) < 1){
					 $okLinks = true;
					 $linkErrors = false;
				}
				else{
					 $status = "errors";
					 $linkErrors = Zend_Json::encode($linkErrors);
				}
				
				if(!$id || !$title || !$authorContrib || !$updated || !$published){
					 $otherErrors = array("id" => $id,
												 "title" => $title,
												 "authorContrib" => $authorContrib,
												 "updated" => $updated,
												 "published" => $published
												 );
					 $otherErrors = Zend_Json::encode($otherErrors);
					 $status = "errors";
				}
				else{
					 $otherErrors = false;
				}
				
				$entryErrors[$id] = array("links" => $linkErrors, "other" => $otherErrors);
				
				$addNew = true; //default to creating a new record
				if($update){
					 $sql = "SELECT id FROM archivefeed WHERE id = '$id' AND page = $feedPage LIMIT 1; ";
					 $result =  $db->fetchAll($sql);
					 if($result){
						  $addNew = false; //item already found, so update it's status only
					 }
					 else{
						  $addNew = true; //item not found, so create it
					 }
				}
				
				if($addNew){
					 $data = array("id" => $id,
										"page" => $feedPage,
										"entryIndex" => $entryIndex,
										"status" => $status,
										"linkErrors" => $linkErrors,
										"otherErrors" => $otherErrors
										);
					 try{
						  $db->insert("archiveFeed", $data);
					 }
					 catch (Exception $e) {
						  $this->recordError((string)$e);	
					 }
				}
				else{
					 //only check the status
					 $where = " id = '$id' ";
					 $data = array("status" => $status);
					 $db->update("archiveFeed", $data, $where);
				}
				
				$entryIndex++;
		  }
		  unset($xml);
		  return $entryErrors;
	 }
	 
	 //use this function to check if an attribute exists or is blank
	 function checkAttributes($attribute, $xml){
		  $output = false;
		  foreach($xml->xpath("@".$attribute) as $res){
				$res = (string)$res;
				if(strlen($res)<1){
					 $output = false;
				}
				else{
					 $output = $res;
				}
		  }
		  return $output;
	 }
	 
	 function recordError($error){
		  if(is_array($this->errors)){
				$errors = $this->errors;
		  }
		  else{
				$errors = array();
		  }
		  $errors[] = $error;
		  $this->errors = $errors;
	 }
	 
	 //register the namespaces
	 function registerNameSpaces($xml){
		  foreach($this->nameSpaceArray as $NSkey => $uri){
				$xml->registerXPathNamespace($NSkey, $uri);
		  }
		  return $xml;
	 }

	 //get namespaces
	 function nameSpaces(){
		  return $this->nameSpaceArray;
    }
	 
	 //load the file
	 function loadFile($sFilename, $sCharset = 'UTF-8'){
        
        if (!file_exists($sFilename)){
            return false;
        }
        $rHandle = fopen($sFilename, 'r');
        if (!$rHandle){
            return false;
        }
        $sData = '';
        while(!feof($rHandle)){
            $sData .= fread($rHandle, filesize($sFilename));
        }
        fclose($rHandle);
        
        if ($sEncoding = mb_detect_encoding($sData, 'auto', true) != $sCharset){
            $sData = mb_convert_encoding($sData, $sCharset, $sEncoding);
        }
        return $sData;
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
	 
	 
	 //preps for utf8
	 function setUTFconnection($db){
		  $sql = "SET collation_connection = utf8_unicode_ci;";
		  $db->query($sql, 2);
		  $sql = "SET NAMES utf8;";
		  $db->query($sql, 2);
	 }

    
}  
