<?php


//this class highlights search terms
class SitePageIndex{
   
   
   public $xml; //act xml for an item
   public $docsAdded; // count of added docs
   public $error; //error message
   
   public $pageArray = array(
					"/about/",
			     "/about/uses",
			     "/about/concepts",
			     "/about/technology",
			     "/about/services",
			     "/about/publishing",
			     "/about/estimate",
			     "/about/bibliography",
			     "/about/privacy",
			     "/about/people",
			     "/about/sponsors",
				  "/about/intellectual-property"
			     );

    public $ocItems; //array of OC items

    public function indexAll(){
		$host = OpenContext_OCConfig::get_host_config();
		
		$db_params = OpenContext_OCConfig::get_db_config();
		$db = new Zend_Db_Adapter_Pdo_Mysql($db_params);
		$db->getConnection(); 
		
		$ocItems = array();
		$solrDocs = array();
		$i=1;
		foreach($this->pageArray as $page){
			sleep(.5);
			$pageString = file_get_contents($host.$page);
			
			@$xml = simplexml_load_string($pageString);
			
			if($xml){
			
				$xml->registerXPathNamespace("xhtml", "http://www.w3.org/1999/xhtml");
				
				$OpenContextItem = new OpenContextItem;
				$OpenContextItem->initialize();
				
				$OpenContextItem->itemUUID = $host.$page; //page URL/URI is the ID
				$OpenContextItem->documentType = "site";
				$OpenContextItem->projectUUID = "Open Context";
				$OpenContextItem->projectName = "Open Context";
				
				foreach ($xml->xpath("//xhtml:title") AS $xpathResult){
					$title = (string)$xpathResult;
					$OpenContextItem->itemLabel = $title;
				}
				
				foreach ($xml->xpath("//xhtml:meta[@name='DC.date']/@content") AS $xpathResult){
					$update = (string)$xpathResult;
					$OpenContextItem->update = date("Y-m-d\TH:i:s\Z", strtotime($update));
				}
				
				foreach ($xml->xpath("//xhtml:meta[@name='DC.created']/@content") AS $xpathResult){
					$pubDate = (string)$xpathResult;
					$OpenContextItem->pubDate = date("Y-m-d\TH:i:s\Z", strtotime($pubDate));
				}
				
				foreach ($xml->xpath("//xhtml:meta[@name='DC.creator']/@content") AS $xpathResult){
					$creators = (string)$xpathResult;
					$OpenContextItem->addSimpleArrayItem($creators, "creators");
				}
				
				$OpenContextItem->addSimpleArrayItem("documentation", "subjects");
				$OpenContextItem->addSimpleArrayItem("privacy", "subjects");
				$OpenContextItem->addSimpleArrayItem("web services", "subjects");
				$OpenContextItem->addSimpleArrayItem("technology", "subjects");
				$OpenContextItem->addSimpleArrayItem("archiving", "subjects");
				$OpenContextItem->addSimpleArrayItem("Site Documentation", "classes");
				
				foreach ($xml->xpath("//xhtml:body") AS $xpathResult){
					$bodyXMLobj = $xpathResult;
					$bodyXML = $bodyXMLobj->asXML();
					$body = strip_tags($bodyXML);
					$OpenContextItem->addSimpleArrayItem($body, "alphaNotes");
				}
				
				$where = array();
				$where[] = "uri = '".$host.$page."' ";
				$db->delete("oc_pages", $where);
				$data = array("uri" => ($host.$page),
						 "created" => date("Y-m-d\TH:i:s\Z", strtotime($pubDate)),
						 "xhtml" => $bodyXML
						 );
				$db->insert("oc_pages", $data);
				
				$OpenContextItem->interestCalc();
				$OpenContextItem->labelSort = $i / 100;
				$OpenContextItem->interestScore += 20000; 
				$ocItems[] = $OpenContextItem; 
				
				$solrDocument = new Apache_Solr_Document();
				$solrDocument = $OpenContextItem->makeSolrDocument($solrDocument);
				//echo "bost:". $solrDocument->getBoost();
		
				$solrDocument->setBoost(10);
				//echo "bost:". $solrDocument->getBoost();
				$solrDocs[] = $solrDocument;
	 
			}
			else{
				$this->error .= "::".$page;
			}
			
		$i++;
		}//end loop
    
    
		$this->executeIndexing($solrDocs);
		 
		$this->ocItems = $ocItems;
    }//end function



    function executeIndexing($solrDocArray, $doPingB = true){
		$error = false;
		if(is_array($solrDocArray)){
			$solr = new Apache_Solr_Service('localhost', 8983, '/solr');
		 
			if ($solr->ping()) { // if we can ping the solr server...
				 
				 $deleteIDs = $this->pageArray;
				 foreach($deleteIDs as $pageSuffix){
					$badPage = "http://opencontext".$pageSuffix;
					//$solr->deleteById($badPage);
				 }//end delete bad
				 
	
				 try{
					$updateResponse = $solr->addDocuments($solrDocArray);
					$solr->commit();
					$this->docsAdded = count($solrDocArray);
				 }
				 catch (Exception $e) {
					$error = $e->getMessage();
					echo print_r($e);
					$this->error = $error;
				 }
			}
			else{
				 //$error = "Solr Down: failed to respond to ping.";
				 sleep(1);
				 if($doPingB){
					$this->executeIndexing($solrDocArray, false);
				 }
				 else{
					$error = "Solr fails to respond to ping, twice.";
					$this->error = $error;
				 }
				 
			}
		}//yes, we do have docs to add
		return $error;
	}//end function

    /*
    Get page XHTML so you don't have to bother apache with a request.
    */
    function pageXHTML($uri){
	
		$db_params = OpenContext_OCConfig::get_db_config();
		$db = new Zend_Db_Adapter_Pdo_Mysql($db_params);
		$db->getConnection(); 
		
		/*
		$sql= "SELECT * FROM oc_pages WHERE uri = '$uri' LIMIT 1";
		
		$result = $db->fetchAll($sql, 2);
		$this->xml = false;
		if($result){
		  $this->xml = $result[0]["xhtml"];
		}
		*/
		$this->xml = file_get_contents($uri);
		
		//$db->closeConnection();
		return $this->xml;
    }


    function deletePage($page){
	
	/*
	$solr = new Apache_Solr_Service('localhost', 8983, '/solr');
	// test the connection to the solr server
	if ($solr->ping()) { // if we can ping the solr server...
	    //echo "connected to the solr server...<br/><br/>";
	    try{
		
		$solr->deleteById($page);
		$solr->commit();
		$solr->optimize();
		
		return true;
	    }
	    catch (Exception $e) {
		
		return $e->getMessage();
		
	    }
				
	//end case where solr document
	}
	*/
	
    }

}//end class
