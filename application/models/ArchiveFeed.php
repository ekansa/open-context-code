<?php


//this class interacts with the database to help publish a paged feed of ALL items for injest by the CDL.
class ArchiveFeed {
    
    public $feedUpdated; //last update of feed
    public $feedPublished; //publication date of feed
    public $currentPage; //current page number
    public $firstPage; //first page
    public $lastPage; //last page
    public $prevPage; //previous page
    public $nextPage; //next page
    
    public $itemType; //limit to a specific item type
    
    public $totalItems; //total number of items
    public $recStart; //first item on page
    public $recEnd; //last item on page
    
    public $feedItems; //array of items that will be expressed as entries
    
    const entriesPerPage = 25; //number of entries per page
    public $db; //database object, used over and over so connection is established only once
    
    
    function set_up_feed_page($currentPage, $itemType = false){
	
	if(!$itemType){
	    $this->itemType = false;
	}
	else{
	    $this->itemType = $itemType;
	}
	
	$this->startDB();
	$this->totalItemCount();
	$this->pageCalculations($currentPage);
	$this->get_entries();
	$this->db->closeConnection();
    }
    
    
    function getItemList(){
	
	$feedItems = $this->feedItems;
	$newItems = array();
	foreach($feedItems as $item){
	    $actNewItem = $item;
	    $itemUUID = $item['itemUUID'];
	    if($item['itemType'] == 'spatial'){
		$itemObj = New Subject;
		$itemObj->getByID($itemUUID);
	    }
	    elseif($item['itemType'] == 'media'){
		$itemObj = New Media;
		$itemObj->getByID($itemUUID);
	    }
	    elseif($item['itemType'] == 'document'){
		$itemObj = New Document;
		$itemObj->getByID($itemUUID);
	    }
	    elseif($item['itemType'] == 'table'){
		$itemObj = New Table;
		$itemObj->getByID($itemUUID);
	    }
	    elseif($item['itemType'] == 'project'){
		$itemObj = New Project;
		$itemObj->getByID($itemUUID);
	    }
	    elseif($item['itemType'] == 'person'){
		$itemObj = New Person;
		$itemObj->getByID($itemUUID);
	    }
	    else{
		$itemObj = false;
	    }
	    
	    if($itemObj != false){
		$actNewItem["label"] = $itemObj->label;
		unset($itemObj);
		$newItems[] = $actNewItem;
	    }
	    else{
		$actNewItem["label"] = "(Working on it..)";
		unset($itemObj);
		$newItems[] = $actNewItem;
	    }
	    
	}//end loop through items
	
	$this->feedItems = $newItems;
	
    }
    
    function generateFeed(){
	
	
	$feedItems = $this->feedItems;
	$host = OpenContext_OCConfig::get_host_config();
	$base_hostname = OpenContext_OCConfig::get_host_config(false);
	$baseURI = OpenContext_OCConfig::get_host_config();
	
	$atomFullDoc = new DOMDocument("1.0", "utf-8");
		
	$root = $atomFullDoc->createElementNS("http://www.w3.org/2005/Atom", "feed");
		
	// add newlines and indent the output - this is at least useful for debugging and making the output easier to read
	$atomFullDoc->formatOutput = true;
		
	$root->setAttribute("xmlns:dc", "http://purl.org/dc/elements/1.1/");
	$root->setAttribute("xmlns:opensearch", "http://a9.com/-/spec/opensearch/1.1/");
		
	$atomFullDoc->appendChild($root);
	
	
	// Feed Title 
	$feedTitle = $atomFullDoc->createElement("title");
	$feedTitleText = $atomFullDoc->createTextNode("Open Context: Feed of All Content");
	$feedTitle->appendChild($feedTitleText);
	$root->appendChild($feedTitle);
	
	if(!$this->itemType){
	    $contentType = " ";
	}
	else{
	    $contentType = " ".$this->itemTypeHumanRead($this->itemType)." ";
	}
	
	$subtitleContent = "This update-time sorted (most recent first) feed provides a comprehensive list of all".$contentType."content in Open Context. Digital archives can use this paged feed to retrieve all resources relevant to data curation from Open Context.";
	
	$subtitleContent .= chr(13).' This page includes entries ' . ($this->recStart +1) . ' to ' . ($this->recEnd +1) . ' out of ' . $this->totalItems . ' entries';
	
	$feedTitle = $atomFullDoc->createElement("subtitle");
	$feedTitleText = $atomFullDoc->createTextNode($subtitleContent);
	$feedTitle->appendChild($feedTitleText);
	$root->appendChild($feedTitle);
	
	// Feed updated element (as opposed to the entry updated element)
	$feedUpdated = $atomFullDoc->createElement("updated");
	$updatedTime = $feedItems[0]["ItemUpdated"];
	
	// Retrieve the current date and time. Format it in RFC 3339 format. Store it in a text node 
	$feedUpdatedText = $atomFullDoc->createTextNode(date("Y-m-d\TH:i:s\-07:00", strtotime($updatedTime)));
	// Append the text node the updated element
	$feedUpdated->appendChild($feedUpdatedText);
	// Append the updated node to the root element
	$root->appendChild($feedUpdated);
	
	$totalResults = $atomFullDoc->createElement('opensearch:totalResults');
	$totalResultsText = $atomFullDoc->createTextNode($this->totalItems);
	$totalResults->appendChild($totalResultsText);
	$root->appendChild($totalResults);
	
	$startIndex = $atomFullDoc->createElement('opensearch:startIndex');
	$startIndexText = $atomFullDoc->createTextNode(($this->recStart+1));
	$startIndex->appendChild($startIndexText);
	$root->appendChild($startIndex);
	
	$itemsPerPage = $atomFullDoc->createElement('opensearch:itemsPerPage');
	$itemsPerPageText = $atomFullDoc->createTextNode(self::entriesPerPage);
	$itemsPerPage->appendChild($itemsPerPageText);
	$root->appendChild($itemsPerPage);
	
	
	
	$linkURI = $host . "/all/.atom";
	
	// feed (self) link element
	if ($this->prevPage != false) {
	    $currentPage = $this->prevPage + 1;
	    if (stristr($linkURI,'?')) {
		$selfURI = $linkURI . '&page='.$currentPage;
	    } else {
		$selfURI = $linkURI . '?page='.$currentPage;
	    }
	}
	else{
	    $selfURI = $linkURI;
	}
	
	
	$feedLink = $atomFullDoc->createElement("link");
	$feedLink->setAttribute("rel", "self");
	$feedLink->setAttribute("href", $selfURI);
	$root->appendChild($feedLink);
	
	// feed license link element
	/*
	$feedLink = $atomFullDoc->createElement("link");
	$feedLink->setAttribute("rel", "license");
	$feedLink->setAttribute("type", "text/html");
	$feedLink->setAttribute("href", "http://creativecommons.org/licenses/by/3.0/");
	$root->appendChild($feedLink);
	*/
	
	
	
	// feed (HTML representation) link element
	/*
	$feedHTML_URI = $host."/all/";
	//$facetURI = str_replace("facets/.atom", "facets.atom", $facetURI);
	$feedLink = $atomFullDoc->createElement("link");
	$feedLink->setAttribute("rel", "alternate");
	$feedLink->setAttribute("type", "application/xhtml+xml");
	$feedLink->setAttribute("href", $feedHTML_URI);
	$root->appendChild($feedLink);
	*/
	
	
	//prepare the first link
	$feedFirstLink = $atomFullDoc->createElement("link");
	$feedFirstLink->setAttribute("rel", "first");
	if (stristr($linkURI,'?')) {
	    $firstURI = $linkURI . '&page=1';
	} else {
	    $firstURI = $linkURI . '?page=1';
	}
	$feedFirstLink->setAttribute("href", $firstURI);
	$root->appendChild($feedFirstLink);
	
	
	// create last link
	$lastPage = $this->lastPage;
	$feedLastLink = $atomFullDoc->createElement('link');
	$feedLastLink->setAttribute('rel', 'last');
	if (stristr($linkURI,'?')) {
	    $lastURI = $linkURI . '&page='.$lastPage;
	} else {
	    $lastURI = $linkURI . '?page='.$lastPage;
	}
	$feedLastLink->setAttribute('href', $lastURI);
	$root->appendChild($feedLastLink);
	
	
	
	// create previous link
	
	if ($this->prevPage != false) {
	    if (stristr($linkURI,'?')) {
		$previousURI = $linkURI . '&page='.$this->prevPage;
	    } else {
		$previousURI = $linkURI . '?page='.$this->prevPage;
	    }
	    $previousLink = $atomFullDoc->createElement('link');
	    $previousLink->setAttribute('rel', 'previous');
	    $previousLink->setAttribute('href', $previousURI);
	    $root->appendChild($previousLink);    
	}
	
	// create next link
	//get page number and add 1; check to see that page + 1 is not greater than $lastPage
	if ($this->nextPage != false) {
	    if (stristr($linkURI,'?')) {
		$nextURI = $linkURI . '&page='.$this->nextPage;
	    } else {
		$nextURI = $linkURI . '?page='.$this->nextPage;
	    }
	    $nextLink = $atomFullDoc->createElement('link');
	    $nextLink->setAttribute('rel', 'next');
	    $nextLink->setAttribute('href', $nextURI);
	    $root->appendChild($nextLink);    
	}
	
	$feedId = $atomFullDoc->createElement("id");
	$feedIdText = $atomFullDoc->createTextNode($linkURI);
	$feedId->appendChild($feedIdText);
	$root->appendChild($feedId);
	
	$contentFragment = $atomFullDoc->createDocumentFragment();
	$errorMessage = "Error on: \n\n";
	foreach($feedItems as $item){
	    $itemUUID = $item['itemUUID'];
	    if($item['itemType'] == 'spatial'){
		$itemObj = New Subject;
		$entryXML = $itemObj->getItemEntry($itemUUID);
		if(stristr($entryXML, 'rel=""')){
		    $itemObj->getItemEntry($itemUUID);
		    $itemObj->DOM_spatialAtomCreate($itemObj->newArchaeoML);
		    $itemObj->update_atom_entry();
		}
		$entryXML = $itemObj->getItemEntry($itemUUID);
	    }
	    elseif($item['itemType'] == 'media'){
		$itemObj = New Media;
		$entryXML = $itemObj->getItemEntry($itemUUID);
		$entryXML = str_replace('rel="preview"', 'rel="http://purl.org/dc/terms/hasPart"',  $entryXML);
		$entryXML = str_replace('rel="thumbnail"', 'rel="http://purl.org/dc/terms/hasPart"',  $entryXML);
	    }
	    elseif($item['itemType'] == 'person'){
		$itemObj = New Person;
		$entryXML = $itemObj->getItemEntry($itemUUID);
	    }
	    elseif($item['itemType'] == 'document'){
		$itemObj = New Document;
		$entryXML = $itemObj->getItemEntry($itemUUID);
	    }
	    elseif($item['itemType'] == 'table'){
		$itemObj = New Table;
		$entryXML = $itemObj->getItemEntry($itemUUID);
	    }
	    elseif($item['itemType'] == 'project'){
		$itemObj = New Project;
		$entryXML = $itemObj->getItemEntry($itemUUID);
	    }
	    else{
		$entryXML = false;
	    }
	    
	    //make sure the update time matches what's in the noid-binding table
	    $entryXML = $this->publish_updateFix($entryXML, $item['ItemUpdated'], $item['ItemCreated']);
	    
	    if($entryXML != false){
		$doc = str_replace('<?xml version="1.0" encoding="utf-8"?>', "", $entryXML);
		$contentFragment->appendXML($doc);
	    }
	    else{
		$errorMessage .= $item['itemType'].": ".$item['itemUUID'];
		$errorMessage .= "\n\n";
	    }
	    
	}//end loop through items
	
	
	@$success = $root->appendChild($contentFragment);
	if(!$success){
	    $error = $atomFullDoc->createElement('error');
	    $errorText = $atomFullDoc->createTextNode($errorMessage);
	    $error->appendChild($errorText);
	    $root->appendChild($error);
	}
	
	
	return $atomFullDoc->saveXML();
    }
    
    
    /*
    The "Update" time on a feed entry may be different from the "all feed update time", because
    some updates may be not very significant, like a change in the view count of an item.
    
    This fixes update times for the archive feed to reflect the last most significant change to an item,
    as stored in the noid-binding table
    */
    function publish_updateFix($xmlString, $goodUpdate, $goodPubllish){
	$goodUpdate = date("Y-m-d\TH:i:s\-07:00", strtotime($goodUpdate));
	$goodPubllish = date("Y-m-d\TH:i:s\-07:00", strtotime($goodPubllish));
	
	$change = false;
	
	@$xml = simplexml_load_string($xmlString);
	if($xml){
	    $xml->registerXPathNamespace("atom", OpenContext_OCConfig::get_namespace("atom"));
	    foreach($xml->xpath("//atom:updated") as $xpathResult){
		$oldUpdate = (string)$xpathResult;
		if($goodUpdate != $oldUpdate){
		    $xpathResult[0] = $goodUpdate;
		    $change = true;
		}
	    }
	     foreach($xml->xpath("//atom:published") as $xpathResult){
		$oldPublished = (string)$xpathResult;
		if($goodPubllish != $oldPublished){
		    $xpathResult[0] = $goodPubllish;
		    $change = true;
		}
	    }
	    
	    if($change){
		$newXMLstring = $xml->asXML();
		unset($xml);
		@$xmlB = simplexml_load_string($newXMLstring);
		if($xmlB){
		    return $newXMLstring;
		}
		else{
		    return $xmlString;
		}
		
	    }
	    else{
		return $xmlString;
	    }
	    
	}
	else{
	    //xml is BAD!
	    return false;
	}
    }
    
    
    
    
    function itemTypeHumanRead($itemType){
	
	$itemTypeMap = array("spatial"=> "Location / Object",
				     "media"=> "Media Resource",
				     "document"=> "Document / Narrative",
				     "person"=> "Person / Organization",
				     "project"=> "Project / Collection",
				     "table"=> "Data table"
				     );
	if(array_key_exists($itemType, $itemTypeMap)){
	    return $itemTypeMap[$itemType];
	}
	else{
	    return $itemType;
	}
	
    }//end function
    
    
    
    //this function gets the itemTypes and itemIDs for feed entries
    function get_entries(){
	$db = $this->db;
	
	if(!$this->itemType){
	    $whereCondition = true;
	}
	else{
	    $whereCondition = " itemType = '".$this->itemType."' ";
	}
	
	$sql = "SELECT *
		FROM noid_bindings
		WHERE ".$whereCondition."
		ORDER BY ItemUpdated DESC
		LIMIT ".($this->recStart ).",".self::entriesPerPage."
		";
		
	//note: I did check this. Order ItemUpdated by DESC makes the most recently updated item 1st in the feed.
	
	$result = $db->fetchAll($sql, 2);
        if($result){
	    $this->feedItems = $result;
	}
	else{
	    $this->feedItems = false;
	}
	
    }//end function
    
    function pageCalculations($currentPage = false){
	if(!$currentPage){
	    $currentPage = $this->currentPage;
	}
	
	if($currentPage < 1){
	    $currentPage = 1;
	}
	
	$totalItems = $this->totalItems;
	$lastPage = $totalItems / self::entriesPerPage;
	if(round($lastPage, 0) < $lastPage){
	    $lastPage = round($lastPage, 0) + 1;
	}
	else{
	    $lastPage = round($lastPage, 0);
	}
	$this->lastPage = $lastPage;
	$this->firstPage = 1;
	
	if($currentPage > 1){
	    $this->prevPage = $currentPage - 1;
	}
	else{
	    $this->prevPage = false;
	}
	
	if($currentPage < $lastPage){
	    $this->nextPage = $currentPage + 1;
	}
	else{
	    $this->nextPage = false;
	}
	
	$this->recStart = ($currentPage - 1) * self::entriesPerPage;	
	$this->recEnd = ($currentPage * self::entriesPerPage) - 1;

    }//end function
    
    
    function startDB(){
	
	$db_params = OpenContext_OCConfig::get_db_config();
        $db = new Zend_Db_Adapter_Pdo_Mysql($db_params);
	$db->getConnection();
	$this->db = $db;
    
    }//end function
    
    function totalItemCount(){
	$db = $this->db;
	
	if(!$this->itemType){
	    $whereCondition = true;
	}
	else{
	    $whereCondition = " itemType = '".$this->itemType."' ";
	}
	
	$sql = "SELECT count(*) as rowCount
		FROM noid_bindings
		WHERE ".$whereCondition;
	$result = $db->fetchAll($sql, 2);
        if($result){
	    $this->totalItems = $result[0]["rowCount"];
	}
	else{
	    $this->totalItems = false;
	}
	
	
	
    }//end function
    
    /*
    Functions listed below are not commonly used. These are for the initial populating of the table
    for all item types.
    
    function get_table_size($itemType){
	$db = $this->db;
	if($itemType == "spatial"){
	    $sql = "SELECT count(*) as recCount FROM space";
	}
	elseif($itemType == "media"){
	    $sql = "SELECT count(*) as recCount FROM resource";
	}
	elseif($itemType == "project"){
	    $sql = "SELECT count(*) as recCount FROM projects";
	}
	elseif($itemType == "document"){
	    $sql = "SELECT count(*) as recCount FROM diary";
	}
	elseif($itemType == "person"){
	    $sql = "SELECT count(*) as recCount FROM persons";
	}
	elseif($itemType == "variable"){
	    $sql = "SELECT count(*) as recCount FROM var_tab";
	}
	elseif($itemType == "property"){
	    $sql = "SELECT count(*) as recCount FROM properties";
	}
	elseif($itemType == "table"){
	    $sql = "SELECT count(*) as recCount FROM dataset";
	}
	else{
	    //bummer
	    $sql = false;
	}
	
	$output = false;
	if($sql != false){
	    $result = $db->fetchAll($sql, 2);
	    if($result){
		$output = $result[0]["recCount"];
	    }
	}
	return $output;
    }
    
    */
    
    //initial populate of spatial items
    function insertSpatial($batch = 0){
	
	$itemType = "spatial";
	$host = OpenContext_OCConfig::get_host_config();
	//$tableSize = $this->get_table_size($itemType);
	$tableSize = 300000;
	$fetchSize = 10000;
	$numFetches = round($tableSize / $fetchSize, 0)+1;
	
	$db = $this->db;
	$whereCondition = true;
	$start = microtime(true);
	$i = $batch;
	while($i < $numFetches){
	    
	    $startNum = $i * $fetchSize;
	    
	    $sql = "SELECT space.uuid as itemUUID, space.created, space.updated
	    FROM space
	    LIMIT $startNum, $fetchSize
	    ";
	    
	    $result = $db->fetchAll($sql, 2);
	    foreach($result as $row){
		
		$itemUUID = $row["itemUUID"];
		$created = $row["created"];
		$updated = $row["updated"];
		$itemURI = $host."/subjects/".$itemUUID;
		$data = array("itemType" => $itemType,
			      "itemUUID" => $itemUUID,
			      "itemURI" => $itemURI,
			      "NOIDcreated" => 0,
			      "itemCreated" => $created,
			      "itemUpdated" => $updated
			      );
		try{
		    $db->insert("noid_bindings", $data);
		}catch (Exception $e) {
		    //skip
		    //echo $e;
		}
		
		
	    }//end loop thorugh items
	
	    $i++;
	    $now = microtime(true);
	    $parseTime = $now - $start;
	    if($parseTime >= 30){
		break;
	    }
	    
	
	}//end loop through fetches
	
	return $i;
    }
    
    
    
    
    //initial populate of media items
    function insertMedia(){
	
	$itemType = "media";
	$host = OpenContext_OCConfig::get_host_config();
	//$tableSize = $this->get_table_size($itemType);
	$tableSize = 209899;
	$fetchSize = 1000;
	$numFetches = round($tableSize / $fetchSize, 0)+1;
	
	$db = $this->db;
	$whereCondition = true;
	
	$i = 0;
	while($i < $numFetches){
	    
	    $startNum = $i * $fetchSize;
	    
	    $sql = "SELECT resource.uuid as itemUUID, resource.created, resource.updated, projects.accession
	    FROM resource
	    JOIN projects ON resource.project_id = projects.project_id 
	    WHERE ".$whereCondition."
	    LIMIT $startNum, $fetchSize
	    ";
	    
	    $result = $db->fetchAll($sql, 2);
	    foreach($result as $row){
		
		$itemUUID = $row["itemUUID"];
		$created = $row["created"];
		$updated = $row["updated"];
		
		if($created == "0000-00-00 00:00:00"){
		    $created = $row["accession"];
		}
		
		
		$itemURI = $host."/media/".$itemUUID;
		$data = array("itemType" => $itemType,
			      "itemUUID" => $itemUUID,
			      "itemURI" => $itemURI,
			      "NOIDcreated" => 0,
			      "itemCreated" => $created,
			      "itemUpdated" => $updated
			      );
		try{
		    $db->insert("noid_bindings", $data);
		}catch (Exception $e) {
		    //skip
		    //echo $e;
		}
		
	    }//end loop thorugh items
	    
	$i++;
	}//end loop through fetches
	
	return $i;
    }
    
    
    /*
    
    //initial populate of project items
    function insertProject(){
	
	$itemType = "project";
	$host = OpenContext_OCConfig::get_host_config();
	//$tableSize = $this->get_table_size($itemType);
	$tableSize = 500000;
	$fetchSize = 1000;
	$numFetches = round($tableSize / $fetchSize, 0)+1;
	
	$db = $this->db;
	$whereCondition = true;
	
	$i = 0;
	while($i < $numFetches){
	    
	    $startNum = $i * $fetchSize;
	    
	    $sql = "SELECT project_id as itemUUID,
		accession AS created,
		accession AS updated
	    FROM projects
	    WHERE ".$whereCondition."
	    LIMIT $startNum, $fetchSize
	    ";
	    
	    $result = $db->fetchAll($sql, 2);
	    foreach($result as $row){
		
		$itemUUID = $row["itemUUID"];
		$created = $row["created"];
		$updated = $row["updated"];
		$itemURI = $host."/projects/".$itemUUID;
		$data = array("itemType" => $itemType,
			      "itemUUID" => $itemUUID,
			      "itemURI" => $itemURI,
			      "NOIDcreated" => 0,
			      "itemCreated" => $created,
			      "itemUpdated" => $updated
			      );
		try{
		    $db->insert("noid_bindings", $data);
		}catch (Exception $e) {
		    //skip
		    //echo $e;
		}
		
	    }//end loop thorugh items
	    
	$i++;
	}//end loop through fetches
	
    }
    
    
    //initial populate of document / diary items
    function insertDocument(){
	
	$itemType = "document";
	$host = OpenContext_OCConfig::get_host_config();
	//$tableSize = $this->get_table_size($itemType);
	$tableSize = 500000;
	$fetchSize = 1000;
	$numFetches = round($tableSize / $fetchSize, 0)+1;
	
	$db = $this->db;
	$whereCondition = true;
	
	$i = 0;
	while($i < $numFetches){
	    
	    $startNum = $i * $fetchSize;
	    
	    $sql = "SELECT diary.uuid as itemUUID,
		projects.accession AS created,
		projects.accession AS updated
	    FROM diary
	    JOIN projects ON diary.project_id = projects.project_id
	    WHERE ".$whereCondition."
	    LIMIT $startNum, $fetchSize
	    ";
	    
	    $result = $db->fetchAll($sql, 2);
	    foreach($result as $row){
		
		
		$itemUUID = $row["itemUUID"];
		$created = $row["created"];
		$updated = $row["updated"];
		
		$itemURI = $host."/documents/".$itemUUID;
		$data = array("itemType" => $itemType,
			      "itemUUID" => $itemUUID,
			      "itemURI" => $itemURI,
			      "NOIDcreated" => 0,
			      "itemCreated" => $created,
			      "itemUpdated" => $updated
			      );
		try{
		    $db->insert("noid_bindings", $data);
		}catch (Exception $e) {
		    //skip
		    //echo $e;
		}
		
	    }//end loop thorugh items
	    
	$i++;
	}//end loop through fetches
	
    }
    
    //initial populate of person items
    function insertPerson(){
	
	$itemType = "person";
	$host = OpenContext_OCConfig::get_host_config();
	//$tableSize = $this->get_table_size($itemType);
	$tableSize = 500000;
	$fetchSize = 1000;
	$numFetches = round($tableSize / $fetchSize, 0)+1;
	
	$db = $this->db;
	$whereCondition = true;
	
	$i = 0;
	while($i < $numFetches){
	    
	    $startNum = $i * $fetchSize;
	    
	    $sql = "SELECT persons.person_uuid as itemUUID,
		projects.accession AS created,
		persons.updated AS updated
	    FROM persons
	    JOIN projects ON persons.project_id = projects.project_id
	    WHERE ".$whereCondition."
	    LIMIT $startNum, $fetchSize
	    ";
	    
	    $result = $db->fetchAll($sql, 2);
	    foreach($result as $row){
		
		
		$itemUUID = $row["itemUUID"];
		$created = $row["created"];
		$updated = $row["updated"];
		
		$itemURI = $host."/persons/".$itemUUID;
		$data = array("itemType" => $itemType,
			      "itemUUID" => $itemUUID,
			      "itemURI" => $itemURI,
			      "NOIDcreated" => 0,
			      "itemCreated" => $created,
			      "itemUpdated" => $updated
			      );
		try{
		    $db->insert("noid_bindings", $data);
		}catch (Exception $e) {
		    //skip
		    //echo $e;
		}
		
	    }//end loop thorugh items
	    
	$i++;
	}//end loop through fetches
	
    }
    
    //initial populate of person items
    function insertTable(){
	
	$itemType = "table";
	$host = OpenContext_OCConfig::get_host_config();
	//$tableSize = $this->get_table_size($itemType);
	$tableSize = 500000;
	$fetchSize = 1000;
	$numFetches = round($tableSize / $fetchSize, 0)+1;
	
	$db = $this->db;
	$whereCondition = true;
	
	$i = 0;
	while($i < $numFetches){
	    
	    $startNum = $i * $fetchSize;
	    
	    $sql = "SELECT cache_id as itemUUID,
		created_on AS created,
		updated AS updated
	    FROM dataset
	    WHERE ".$whereCondition."
	    LIMIT $startNum, $fetchSize
	    ";
	    
	    $result = $db->fetchAll($sql, 2);
	    foreach($result as $row){
		
		
		$itemUUID = $row["itemUUID"];
		$created = $row["created"];
		$updated = $row["updated"];
		
		$itemURI = $host."/tables/".$itemUUID;
		$data = array("itemType" => $itemType,
			      "itemUUID" => $itemUUID,
			      "itemURI" => $itemURI,
			      "NOIDcreated" => 0,
			      "itemCreated" => $created,
			      "itemUpdated" => $updated
			      );
		try{
		    $db->insert("noid_bindings", $data);
		}catch (Exception $e) {
		    //skip
		    //echo $e;
		}
		
	    }//end loop thorugh items
	    
	$i++;
	}//end loop through fetches
	
    }
    
    */
    
    
}
