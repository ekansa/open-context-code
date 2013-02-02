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
	 
	 
	 function checkFeed($xml, $feedPage){
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
					 $authorContrib = (string)$author;
				}
				if(!$authorContrib){
					 foreach($entry->xpath("./atom:contributor") as $author){
						  $authorContrib = (string)$author;
					 }
				}
				$updated = false;
				foreach($entry->xpath("./atom:updated") as $updated){
					 $updated = (string)$author;
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
				$entryIndex++;
		  }
		  unset($xml);
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
