<?php


//this class interacts with the database to help publish a paged feed of ALL items for injest by the CDL.
class SiteMap {
    
    
    public $db; //database object, used over and over so connection is established only once
    
	 public $allItems; //list of ALL items needed to make a site map
	 public $itemList; //list of items actively used in making a site map
    public $root;
	 public $dom;
	
	 public $imageArray;
	
	 const exportDir = "./data/"; //sitemap
	 const cacheTime = 10510020; //cache lifetime
	 const linksPerMap = 20000; //number of links in a sitemap
	
    public $priority = array(	'project' => array(	"baseRank" => .9,
													"table" => "projects",
													"uuid" => "project_id",
													"xml" => "proj_archaeoml"),
								'media' => array(	"baseRank" => .65,
													"table" => "resource"),
								'table' => array(	"baseRank" => .75,
													"table" => false),
								'document' => array("baseRank" => .6,
													"table" => "diary"),
								'spatial' => array(	"baseRank" => .5,
													"table" => "space"),
								'person' => array(	"baseRank" => .25,
													"table" => "persons"),
							 );
	
	
	 //get a previously saved site map
	 function getSiteMap($idKey){
		  
		  $sitemapFile = self::exportDir."/siteMap-".$idKey.".xml";
		  if(!file_exists($sitemapFile)){
				return false;
		  }
		  else{
				$xml = $this->readFile($sitemapFile);
				return $xml;
		  }
	 }
	
	
	
	 function get_make_sitemap(){
		 
		 
		  $frontendOptions = array('lifetime' => self::cacheTime,'automatic_serialization' => true );
		  $backendOptions = array('cache_dir' => self::exportDir );
				  
		  $cache = Zend_Cache::factory('Core','File',$frontendOptions,$backendOptions);
		  
		  $this->startDB();
		  $this->getMaxRankings();
		  $this->get_items();
		  
		  $allItems = $this->allItems;
		  $allItemCount = count($allItems);
		  $numberMaps = $allItemCount / self::linksPerMap;
		  if(round($numberMaps,0) != $numberMaps){
				$numberMaps = round($numberMaps,0) + 1;
		  }
		  $curMap = 1;
		  $i = 0;
		  $mapItems = array();
		  while($i < $allItemCount){

				$divCheck = $i / self::linksPerMap;
				if((round($divCheck, 0) == $divCheck) && $i >0){
					$curMap = $curMap + 1;
				}
				$mapItems[$curMap][] = $allItems[$i];
			  
		  $i++;	
		  }
		  
		  unset($allItems);
		  
		  foreach($mapItems as $mapKey => $itemList){
				$this->itemList = $itemList; //make an active item list
				$cache_id = "siteMap".$mapKey;
				$sitemapFile = self::exportDir."/siteMap-".$mapKey.".xml";
				
				if(!$cache_result = $cache->load($cache_id)) {
					 if(!file_exists($sitemapFile)){
						  //OK! make the sitemap
						  $this->root = false;
						  $this->dom = false;
						  $this->adjust_rankings();
						  $xml = $this->makeSiteMap();
						  @$xmlTest = simplexml_load_string( $xml);
						  if($xmlTest){
								unset($xmlTest);
								$this->saveFile($sitemapFile, $xml);
						  }
						  else{
								echo "error on ".$sitemapFile;
						  }
					 }
					 else{
						 $xml = $this->readFile($sitemapFile);
					 }
					 $cache->save($xml, $cache_id); //save result to the cache
				}
				else{
					 $xml = $cache_result;
				}
		  }
		  
		  return "<done>$numberMaps</done>";
	 }
	
	
	
	
	
     //this function gets the itemTypes and itemIDs for items
    function get_items(){
		  $db = $this->startDB();
	  
		  $sql = "SELECT *
			  FROM noid_bindings
			  ";
		  
		  $result = $db->fetchAll($sql, 2);
		  if($result){
				$this->allItems = $result;
		  }
		  else{
				$this->allItems = false;
		  }
	
    }//end function
    
	
	function getMaxRankings(){
		
		  $db = $this->startDB();
		  $priority = $this->priority;
		  $newProprity = array();
		  
		  foreach($priority as $key => $params){
			  
				$maxChar = false;
				if($params["table"] != false){
					
					 $actTable = $params["table"];
					 $xmlField = "archaeoML";
					 if(isset($params["xml"])){
						  $xmlField = $params["xml"];
					 }
					 
					 $sql = "SELECT CHAR_LENGTH($xmlField) as maxChar
					 FROM $actTable
					 ORDER BY CHAR_LENGTH($xmlField) DESC
					 LIMIT 1;
					 ";
					 
					 $result = $db->fetchAll($sql, 2);
					 //$result = false;
					 if($result){
						  $maxChar = $result[0]["maxChar"];
					 }
				}
			  $params["maxChar"] = $maxChar;
			  $newProprity[$key] = $params;
		  }
		  
		  $this->priority = $newProprity;
	}
	
	
	
	function adjust_rankings(){
		  $itemList = $this->itemList;
		  $priority = $this->priority ;
		  $newItemList = array();
		  foreach($itemList as $item){
			  $actNewItem = $item;
			  
			  if($item['sitePriority'] == 0){
					 $itemUUID = $item['itemUUID'];
					 $ok = false;
					 if($item['itemType'] == 'spatial'){
						  $itemObj = New Subject;
						  $ok = $itemObj->getByID($itemUUID);
					 }
					 elseif($item['itemType'] == 'media'){
						  $itemObj = New Media;
						  $ok = $itemObj->getByID($itemUUID);
					 }
					 elseif($item['itemType'] == 'document'){
						  $itemObj = New Document;
						  $ok = $itemObj->getByID($itemUUID);
					 }
					 elseif($item['itemType'] == 'project'){
						  $itemObj = New Project;
						  $ok = $itemObj->getByID($itemUUID);
					 }
					 elseif($item['itemType'] == 'person'){
						  $itemObj = New Person;
						  $ok = $itemObj->getByID($itemUUID);
					 }
					 else{
						  $itemObj = false;
					 }

					 $actPriority = $priority[$item['itemType']];
					 $baseRank = $actPriority["baseRank"];
					 $rank = $baseRank;
			  
					 if($itemObj != false && $ok){
						 
						 $charLen = strlen($itemObj->archaeoML);
						 
						  if($item['itemType'] == "media"){
								///echo print_r($itemObj);
								if(stristr($itemObj->mimeType, "image")){
									$actNewItem["image"] = true;
									//break;
								}
						  }
						 
						  if($actPriority["maxChar"] != false){
								$lenProp = $charLen / $actPriority["maxChar"];
						  }
						  else{
								$lenProp = 0;
						  }
						  $rank =  $baseRank + (($baseRank * .2) * $lenProp);
						  if($rank > 1){
								$rank = 1;
						  }
					 }
					 else{
						 $propMax  =  1;
					 }
					 
					 $actNewItem["sitePriority"] = $rank;
					 
					 if($rank != 0){
						  $db = $this->db;
						  $data = array("sitePriority" => $rank);
						  $where = "itemUUID = '$itemUUID' ";
						  $db->update("noid_bindings", $data, $where);
					 }
				  
			  }
  
			  unset($itemObj);
			  $newItemList[] = $actNewItem;
		  }//end loop through items
	
		$this->itemList = $newItemList;
		
	}
	
	
	
	function makeSiteMap(){
		
		  $dom = new DOMDocument("1.0", "utf-8");
		  $dom->formatOutput = true;
		  
		  $root = $dom->createElementNS("http://www.sitemaps.org/schemas/sitemap/0.9", "urlset");
		  $root->setAttribute("xmlns:xsi", "http://www.w3.org/2001/XMLSchema-instance");
		  $root->setAttribute("xsi:schemaLocation", "http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd");
		  $root->setAttribute("xmlns:image", "http://www.google.com/schemas/sitemap-image/1.1");
		  $dom->appendChild($root);
		  
		  $this->dom = $dom;
		  $this->root = $root;
		
		  foreach($this->itemList as $item){
			  
				$url = $item["itemURI"];
				if(strstr($url, "http://opencontext/")){
					 $url = str_replace("http://opencontext/", "http://opencontext.org/", $url);
				}
				
				$upDate = $item["RecordUpdated"];
				$changeTime = "yearly";
				$rank = round($item["sitePriority"], 3);
				
				if($item["itemType"] == "project" || $item["itemType"] == "person"){
					 $changeTime = "monthly";
				}
				elseif($item["itemType"] == "media"){
					
					 $itemObj = New Media;
					 $itemObj->getByID($item["itemUUID"]);
					 if(stristr($itemObj->mimeType, "image")){
						  $this->siteMapItem($url."/full", $upDate, $changeTime, ($rank + .2)); //also add a link the full version, with higher ranking
					 }
					 unset($itemObj);
				}
				
				$this->siteMapItem($url, $upDate, $changeTime, $rank);
			  
		  }
		  
		  return $dom->saveXML();
	 }
	
	
	function siteMapItem($url, $upDate, $changeTime, $rank){
		
		  $dom = $this->dom;
		  $root = $this->root;
		  
		  $element = $dom->createElement('url');
		  
		  $elementB = $dom->createElement('loc');
		  $elementBText = $dom->createTextNode($url);
		  $elementB->appendChild($elementBText);
		  $element->appendChild($elementB);
		  
		  $elementB = $dom->createElement('lastMod');
		  $elementBText = $dom->createTextNode(date("Y-m-d", strtotime($upDate)));
		  $elementB->appendChild($elementBText);
		  //$element->appendChild($elementB);
		  
		  $elementB = $dom->createElement('changefreq');
		  $elementBText = $dom->createTextNode($changeTime);
		  $elementB->appendChild($elementBText);
		  $element->appendChild($elementB);
		  
		  $elementB = $dom->createElement('priority');
		  $elementBText = $dom->createTextNode($rank);
		  $elementB->appendChild($elementBText);
		  $element->appendChild($elementB);
		  
		  $root->appendChild($element);
		  
		  $this->root = $root;
	 }
	
	
	 function setImageArray($itemUUID){
		 return true;
	 }
	
	
	 function saveFile($filename, $xml){
		 
		 $success = false;
		 if($xml != false){
				try{
					iconv_set_encoding("internal_encoding", "UTF-8");
					iconv_set_encoding("output_encoding", "UTF-8");
					$fp = fopen($filename, 'w');
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
	
	
	function readFile($sFilename){
	
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
				  return $sData;
			  } 
		  }
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
