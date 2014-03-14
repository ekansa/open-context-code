<?php
/** Zend_Controller_Action */
//require_once 'Zend/Controller/Action.php';

class setsController extends Zend_Controller_Action {
    
   
   //XHTML view of search results
    public function indexAction() {
		  
		 //$this->_helper->viewRenderer->setNoRender();
		 
		 $requestParams =  $this->_request->getParams();
		 
		 /*
		 if(OpenContext_UserMessages::isSolrDown()){
			  return $this->render('down');
		 }
		 */
		 
		 
		 $protect = new Floodprotection; //check to make sure service is not abused by too many requests
		 $protect->initialize(getenv('REMOTE_ADDR'), $this->_request->getRequestUri());
		 $protect->addedRequests = 2;
		 $protect->userAgent = @$_SERVER['HTTP_USER_AGENT'];
		 $protect->check_ip();
		 if($protect->lock){
			  sleep($protect->sleepTime);
		 }
		 unset($protect);
		 OpenContext_SocialTracking::update_referring_link('sets', $this->_request->getRequestUri(), @$_SERVER['HTTP_USER_AGENT'], @$_SERVER['HTTP_REFERER']);
		 
		 
		 $SolrSearch = new SolrSearch;
		 $SolrSearch->initialize();
		 $SolrSearch->requestURI = $this->_request->getRequestUri();
		 $SolrSearch->requestParams = $requestParams;
		 $SolrSearch->PropToTaxaParameter(); // change depricated prop parameters to taxa parameters
		 $requestParams = $SolrSearch->requestParams; //make sure any changes to request parameters are there for the view page
		 
		 $SolrSearch->spatial = true; //do a search of spatial items in Open Context
		 $SolrSearch->buildSolrQuery();
		 $SolrSearch->execute_search();
		 
		 if($SolrSearch->solrDown){
		  return $this->render('down');
		 }
		 
		 
		 $SolrSearch->getLatestTime(); //get the last updated
		 //$SolrSearch->getLatestTime(false); //get the last published
		 
		 $this->view->requestURI = $this->_request->getRequestUri(); // for testing
		 $this->view->requestParams = $requestParams;  // for testing
		 $this->view->numFound = $SolrSearch->numFound;
		 $this->view->facets = $SolrSearch->facets;
		 $this->view->docs = $SolrSearch->documentsArray;
		 $this->view->offset = $SolrSearch->offset;
		 $this->view->number_recs = $SolrSearch->number_recs;
		 $this->view->sorting = $SolrSearch->sortType;
		 $this->view->lastUpdate = $SolrSearch->lastUpdate;
		 
		 $this->view->currentXHTML = $SolrSearch->currentXHTML;
		 $this->view->currentJSON = $SolrSearch->currentJSON;
		 $this->view->currentAtom = $SolrSearch->currentAtom;
		 $this->view->currentKML = $SolrSearch->currentKML;
		 $this->view->firstPage_XHTML = $SolrSearch->firstPage_XHTML;
		 $this->view->firstPage_JSON = $SolrSearch->firstPage_JSON;
		 $this->view->firstPage_Atom = $SolrSearch->firstPage_Atom;
		 $this->view->firstPage_KML = $SolrSearch->firstPage_KML;
		 $this->view->prevPage_XHTML = $SolrSearch->prevPage_XHTML;
		 $this->view->prevPage_JSON = $SolrSearch->prevPage_JSON;
		 $this->view->prevPage_Atom = $SolrSearch->prevPage_Atom;
		 $this->view->prevPage_KML = $SolrSearch->prevPage_KML;
		 $this->view->nextPage_XHTML = $SolrSearch->nextPage_XHTML;
		 $this->view->nextPage_JSON = $SolrSearch->nextPage_JSON;
		 $this->view->nextPage_Atom = $SolrSearch->nextPage_Atom;
		 $this->view->nextPage_KML = $SolrSearch->nextPage_KML;
		 $this->view->lastPage_XHTML = $SolrSearch->lastPage_XHTML;
		 $this->view->lastPage_JSON = $SolrSearch->lastPage_JSON;
		 $this->view->lastPage_Atom = $SolrSearch->lastPage_Atom;
		 $this->view->lastPage_KML = $SolrSearch->lastPage_KML;
		 
		 //these are links to facets only
		 $this->view->facetURI_Atom = $SolrSearch->facetURI_Atom;
		 $this->view->facetURI_KML = $SolrSearch->facetURI_KML;
		 $this->view->facetURI_JSON = $SolrSearch->facetURI_JSON;
		 $this->view->geoTileFacets = $SolrSearch->geoTileFacets;
		 
		 if($SolrSearch->numFound < 1){
			  return $this->render('noresults');
		 }
		 
		 //prep geotile facets
		 $FacetURLs = new FacetURLs;
		 $FacetURLs->setRequestParams($requestParams);
		 $FacetURLs->geoTileFacets = $SolrSearch->geoTileFacets;
		 $FacetURLs->chronoTileFacets = $SolrSearch->chronoTileFacets;
		 $FacetURLs->geoTileFacets();
		 $FacetURLs->chronoTileFacets();
		 $this->view->geoTileFacets = $FacetURLs->geoTileFacetURLs;
		 $this->view->chronoTileFacets = $FacetURLs->chronoTileFacetURLs;
		 
		 
		 //$this->view->result_output = OpenContext_ResultAtom::atom_to_html($SolrSearch->currentAtom, $SolrSearch->makeSpaceAtomFeed()); //generate xhtml result output
		 $atom_string = $SolrSearch->makeSpaceAtomFeed();
		 $this->view->spaceResults = $SolrSearch->atom_to_object($atom_string);
		 
		 if(isset($requestParams["map"])){
		  return $this->render("map");
		 }
		 
		 if(isset($requestParams["chrono"])){
		  return $this->render("chrono");
		 }
		 
		 /*
		 $output = array("numFound" => $SolrSearch->numFound,
				 "facets" => $SolrSearch->facets,
				 "docs" => $SolrSearch->documentsArray);
		 
		 $this->_helper->viewRenderer->setNoRender();
		 header('Content-Type: application/json; charset=utf8');
		 echo Zend_Json::encode($SolrSearch);
		 */                                                                                                          

    }//end index viewer


    public function resultsAction() {

		$requestParams =  $this->_request->getParams();
		if(OpenContext_UserMessages::isSolrDown()){
			return $this->render('down');
		}
		
		$protect = new Floodprotection; //check to make sure service is not abused by too many requests
		$protect->initialize(getenv('REMOTE_ADDR'), $this->_request->getRequestUri());
		$protect->check_ip();
		if($protect->lock){
			sleep($protect->sleepTime);
		}
		unset($protect);
		
		$SolrSearch = new SolrSearch;
		$SolrSearch->initialize();
		$SolrSearch->requestURI = $this->_request->getRequestUri();
		$SolrSearch->requestParams = $requestParams;
		$SolrSearch->PropToTaxaParameter(); // change depricated prop parameters to taxa parameters
		$requestParams = $SolrSearch->requestParams; //make sure any changes to request parameters are there for the view page
		
		$SolrSearch->spatial = true; //do a search of spatial items in Open Context
		$SolrSearch->buildSolrQuery();
		$SolrSearch->execute_search();
		$SolrSearch->getLatestTime(); //get the last updated
		$SolrSearch->getLatestTime(false); //get the last published
		
		$this->view->requestURI = $this->_request->getRequestUri(); // for testing
		$this->view->requestParams = $requestParams;  // for testing
		$this->view->numFound = $SolrSearch->numFound;
		$this->view->facets = $SolrSearch->facets;
		$this->view->docs = $SolrSearch->documentsArray;
		$this->view->offset = $SolrSearch->offset;
		$this->view->number_recs = $SolrSearch->number_recs;
		$this->view->sorting = $SolrSearch->sortType;
		$this->view->lastUpdate = $SolrSearch->lastUpdate;
		$this->view->lastPublished = $SolrSearch->lastPublished;
		
		$this->view->currentXHTML = $SolrSearch->currentXHTML;
		$this->view->currentJSON = $SolrSearch->currentJSON;
		$this->view->currentAtom = $SolrSearch->currentAtom;
		$this->view->currentKML = $SolrSearch->currentKML;
		$this->view->firstPage_XHTML = $SolrSearch->firstPage_XHTML;
		$this->view->firstPage_JSON = $SolrSearch->firstPage_JSON;
		$this->view->firstPage_Atom = $SolrSearch->firstPage_Atom;
		$this->view->firstPage_KML = $SolrSearch->firstPage_KML;
		$this->view->prevPage_XHTML = $SolrSearch->prevPage_XHTML;
		$this->view->prevPage_JSON = $SolrSearch->prevPage_JSON;
		$this->view->prevPage_Atom = $SolrSearch->prevPage_Atom;
		$this->view->prevPage_KML = $SolrSearch->prevPage_KML;
		$this->view->nextPage_XHTML = $SolrSearch->nextPage_XHTML;
		$this->view->nextPage_JSON = $SolrSearch->nextPage_JSON;
		$this->view->nextPage_Atom = $SolrSearch->nextPage_Atom;
		$this->view->nextPage_KML = $SolrSearch->nextPage_KML;
		$this->view->lastPage_XHTML = $SolrSearch->lastPage_XHTML;
		$this->view->lastPage_JSON = $SolrSearch->lastPage_JSON;
		$this->view->lastPage_Atom = $SolrSearch->lastPage_Atom;
		$this->view->lastPage_KML = $SolrSearch->lastPage_KML;
		
		//these are links to facets only
		$this->view->facetURI_Atom = $SolrSearch->facetURI_Atom;
		$this->view->facetURI_KML = $SolrSearch->facetURI_KML;
		$this->view->facetURI_JSON = $SolrSearch->facetURI_JSON;
		$this->view->geoTileFacets = $SolrSearch->geoTileFacets;
	
		$fixedParams = $requestParams;
		$fixedParams["action"] = "index";
		$host = OpenContext_OCConfig::get_host_config(); 
		$summaryObj = OpenContext_FacetOutput::active_filter_object($fixedParams, $host);
		
		$this->_helper->viewRenderer->setNoRender();
		
		header('Content-Type: application/atom+xml; charset=utf8');
		echo $SolrSearch->makeSpaceAtomFeed();
	    
    }//end Atom-results viewer


   public function facetsAction() {
    
		  
		$requestParams =  $this->_request->getParams();
		if(OpenContext_UserMessages::isSolrDown()){
			return $this->render('down');
		}
		
		$protect = new Floodprotection; //check to make sure service is not abused by too many requests
		$protect->initialize(getenv('REMOTE_ADDR'), $this->_request->getRequestUri());
		$protect->check_ip();
		if($protect->lock){
			sleep($protect->sleepTime);
		}
		unset($protect);
		
		$SolrSearch = new SolrSearch;
		$SolrSearch->initialize();
		$SolrSearch->requestURI = $this->_request->getRequestUri();
		$SolrSearch->requestParams = $requestParams;
		$SolrSearch->PropToTaxaParameter(); // change depricated prop parameters to taxa parameters
		$requestParams = $SolrSearch->requestParams; //make sure any changes to request parameters are there for the view page
		
		$SolrSearch->spatial = true; //do a search of spatial items in Open Context
		$SolrSearch->buildSolrQuery();
		$SolrSearch->execute_search();
		$SolrSearch->getLatestTime(); //get the last updated
		$SolrSearch->getLatestTime(false); //get the last published
		
		$this->view->requestURI = $this->_request->getRequestUri(); // for testing
		$this->view->requestParams = $requestParams;  // for testing
		$this->view->numFound = $SolrSearch->numFound;
		$this->view->facets = $SolrSearch->facets;
		$this->view->docs = $SolrSearch->documentsArray;
		$this->view->offset = $SolrSearch->offset;
		$this->view->number_recs = $SolrSearch->number_recs;
		$this->view->sorting = $SolrSearch->sortType;
		$this->view->lastUpdate = $SolrSearch->lastUpdate;
		$this->view->lastPublished = $SolrSearch->lastPublished;
		
		$this->view->currentXHTML = $SolrSearch->currentXHTML;
		$this->view->currentJSON = $SolrSearch->currentJSON;
		$this->view->currentAtom = $SolrSearch->currentAtom;
		$this->view->firstPage_XHTML = $SolrSearch->firstPage_XHTML;
		$this->view->firstPage_JSON = $SolrSearch->firstPage_JSON;
		$this->view->firstPage_Atom = $SolrSearch->firstPage_Atom; 
		$this->view->prevPage_XHTML = $SolrSearch->prevPage_XHTML;
		$this->view->prevPage_JSON = $SolrSearch->prevPage_JSON;
		$this->view->prevPage_Atom = $SolrSearch->prevPage_Atom;
		$this->view->nextPage_XHTML = $SolrSearch->nextPage_XHTML;
		$this->view->nextPage_JSON = $SolrSearch->nextPage_JSON;
		$this->view->nextPage_Atom = $SolrSearch->nextPage_Atom;
		$this->view->lastPage_XHTML = $SolrSearch->lastPage_XHTML;
		$this->view->lastPage_JSON = $SolrSearch->lastPage_JSON;
		$this->view->lastPage_Atom = $SolrSearch->lastPage_Atom;
		
		//these are links to facets only
		$this->view->facetURI_Atom = $SolrSearch->facetURI_Atom;
		$this->view->facetURI_JSON = $SolrSearch->facetURI_JSON;
		$this->view->facetURI_KML = $SolrSearch->facetURI_KML;
		$this->view->geoTileFacets = $SolrSearch->geoTileFacets;
	
		$fixedParams = $requestParams;
		$fixedParams["action"] = "index";
		$host = OpenContext_OCConfig::get_host_config(); 
		$summaryObj = OpenContext_FacetOutput::active_filter_object($fixedParams, $host);
		
		$FacetURLs = new FacetURLs;
		$FacetURLs->setRequestParams($requestParams);
		$FacetURLs->setSolrFacets($SolrSearch->facets);
		$FacetURLs->doContextMetadata = true; //get date ranges for contexts
		$FacetURLs->default_context_path = $SolrSearch->default_context_path;
		$FacetURLs->original_default_context_path = $SolrSearch->original_default_context_path;
		$FacetURLs->facetLinking();
		
		$this->view->facets = $FacetURLs->FacetURLs;
		
    }//end atom-facets viewer


    
//jsonfacets
    public function jsonfacetsAction() {
		  ini_set('max_execution_time', 150); //300 seconds = 5 minutes
		  
		  $requestParams =  $this->_request->getParams();
		  if(OpenContext_UserMessages::isSolrDown()){
			  return $this->render('down');
		  }
		
		  $protect = new Floodprotection; //check to make sure service is not abused by too many requests
		  $protect->initialize(getenv('REMOTE_ADDR'), $this->_request->getRequestUri());
		  $protect->check_ip();
		  if($protect->lock){
			  sleep($protect->sleepTime);
		  }
		  unset($protect);
		  
		  if(isset($requestParams["callback"])){
			$callback = $requestParams["callback"];
		  }
		  else{
			$callback = false;
		  }
		
		
		$SolrSearch = new SolrSearch;
		$SolrSearch->initialize();
		  $SolrSearch->geoMany = true;
		$SolrSearch->requestURI = $this->_request->getRequestUri();
		$SolrSearch->requestParams = $requestParams;
		$SolrSearch->PropToTaxaParameter(); // change depricated prop parameters to taxa parameters
		$requestParams = $SolrSearch->requestParams; //make sure any changes to request parameters are there for the view page
		
		$SolrSearch->spatial = true; //do a search of spatial items in Open Context
		$SolrSearch->buildSolrQuery();
		$SolrSearch->execute_search();
		$SolrSearch->getLatestTime(); //get the last updated
		$SolrSearch->getLatestTime(false); //get the last published
		$atom_string = $SolrSearch->makeSpaceAtomFeed();
		$spaceResults = $SolrSearch->atom_to_object($atom_string);
		unset($atom_string);
		
		$this->view->requestURI = $this->_request->getRequestUri(); // for testing
		$this->view->requestParams = $requestParams;  // for testing
		$this->view->numFound = $SolrSearch->numFound;
		$this->view->facets = $SolrSearch->facets;
		$this->view->docs = $SolrSearch->documentsArray;
		$this->view->offset = $SolrSearch->offset;
		
		$this->view->currentXHTML = $SolrSearch->currentXHTML;
		$this->view->currentJSON = $SolrSearch->currentJSON;
		$this->view->currentAtom = $SolrSearch->currentAtom;
		$this->view->firstPage_XHTML = $SolrSearch->firstPage_XHTML;
		$this->view->firstPage_JSON = $SolrSearch->firstPage_JSON;
		$this->view->firstPage_Atom = $SolrSearch->firstPage_Atom; 
		$this->view->prevPage_XHTML = $SolrSearch->prevPage_XHTML;
		$this->view->prevPage_JSON = $SolrSearch->prevPage_JSON;
		$this->view->prevPage_Atom = $SolrSearch->prevPage_Atom;
		$this->view->nextPage_XHTML = $SolrSearch->nextPage_XHTML;
		$this->view->nextPage_JSON = $SolrSearch->nextPage_JSON;
		$this->view->nextPage_Atom = $SolrSearch->nextPage_Atom;
		$this->view->lastPage_XHTML = $SolrSearch->lastPage_XHTML;
		$this->view->lastPage_JSON = $SolrSearch->lastPage_JSON;
		$this->view->lastPage_Atom = $SolrSearch->lastPage_Atom;
		
		//these are links to facets only
		$this->view->facetURI_Atom= $SolrSearch->facetURI_Atom;
		$this->view->facetURI_JSON= $SolrSearch->facetURI_JSON;
	
		$fixedParams = $requestParams;
		$fixedParams["action"] = "index";
		$host = OpenContext_OCConfig::get_host_config(); 
		$summaryObj = OpenContext_FacetOutput::active_filter_object($fixedParams, $host);
		
		$pagingArray = array("self"=> $SolrSearch->currentJSON,
					 "first" => $SolrSearch->firstPage_JSON,
					 "prev" => $SolrSearch->prevPage_JSON,
					 "next" => $SolrSearch->nextPage_JSON,
					 "last" => $SolrSearch->lastPage_JSON,
					 );
		
		$FacetURLs = new FacetURLs;
		$FacetURLs->setRequestParams($requestParams);
		$FacetURLs->geoTileFacets = $SolrSearch->geoTileFacets;
		$FacetURLs->setSolrFacets($SolrSearch->facets);
		$FacetURLs->doContextMetadata = true; //get date ranges for contexts
		$FacetURLs->default_context_path = $SolrSearch->default_context_path;
		$FacetURLs->original_default_context_path = $SolrSearch->original_default_context_path;
		$FacetURLs->facetLinking();
		
		$output = array("numFound" => $SolrSearch->numFound,
				"offset" => $SolrSearch->offset,
				"published" => $SolrSearch->lastPublished,
				"updated" => $SolrSearch->lastUpdate,
				"sorting" => $SolrSearch->sortType,
				"summary" => $summaryObj,
				"facets" => $FacetURLs->FacetURLs,
				"geoTileFacets" => $FacetURLs->geoTileFacetURLs,
				"paging" => $pagingArray
				//"resultsA" => $SolrSearch->documentsArray,
				//"results" => $spaceResults["items"],
				);
		
		$this->_helper->viewRenderer->setNoRender();
		
		
		$JSONstring = Zend_Json::encode($output);
		if($callback){
			header('Content-Type: application/javascript; charset=utf8');
			$JSONstring = $callback."(".$JSONstring.");";
		}
		else{
			header('Content-Type: application/json; charset=utf8');
			header("Access-Control-Allow-Origin: *");
		}
		echo $JSONstring;

    }//end json-results viewer



//json reconcilation
    public function jsonreconciliationAction() {
    
		$requestParams =  $this->_request->getParams();
		if(OpenContext_UserMessages::isSolrDown()){
			return $this->render('down');
		}
		
		if(isset($requestParams["callback"])){
		 $callback = $requestParams["callback"];
		}
		else{
		 $callback = false;
		}
		
		
		$SolrSearch = new SolrSearch;
		$SolrSearch->initialize();
		$SolrSearch->requestURI = $this->_request->getRequestUri();
		$SolrSearch->requestParams = $requestParams;
		$SolrSearch->PropToTaxaParameter(); // change depricated prop parameters to taxa parameters
		$requestParams = $SolrSearch->requestParams; //make sure any changes to request parameters are there for the view page
		
		$SolrSearch->spatial = true; //do a search of spatial items in Open Context
		$SolrSearch->reconcile = true; //limit results to reconcilation
		$SolrSearch->buildSolrQuery();
		$SolrSearch->execute_search();
		$SolrSearch->getLatestTime(); //get the last updated
		$SolrSearch->getLatestTime(false); //get the last published
		
		
		$this->view->requestURI = $this->_request->getRequestUri(); // for testing
		$this->view->requestParams = $requestParams;  // for testing
		$this->view->numFound = $SolrSearch->numFound;
		$this->view->facets = $SolrSearch->facets;
		$this->view->docs = $SolrSearch->documentsArray;
		$this->view->offset = $SolrSearch->offset;
		
		$this->view->currentXHTML = $SolrSearch->currentXHTML;
		$this->view->currentJSON = $SolrSearch->currentJSON;
		$this->view->currentAtom = $SolrSearch->currentAtom;
		$this->view->firstPage_XHTML = $SolrSearch->firstPage_XHTML;
		$this->view->firstPage_JSON = $SolrSearch->firstPage_JSON;
		$this->view->firstPage_Atom = $SolrSearch->firstPage_Atom; 
		$this->view->prevPage_XHTML = $SolrSearch->prevPage_XHTML;
		$this->view->prevPage_JSON = $SolrSearch->prevPage_JSON;
		$this->view->prevPage_Atom = $SolrSearch->prevPage_Atom;
		$this->view->nextPage_XHTML = $SolrSearch->nextPage_XHTML;
		$this->view->nextPage_JSON = $SolrSearch->nextPage_JSON;
		$this->view->nextPage_Atom = $SolrSearch->nextPage_Atom;
		$this->view->lastPage_XHTML = $SolrSearch->lastPage_XHTML;
		$this->view->lastPage_JSON = $SolrSearch->lastPage_JSON;
		$this->view->lastPage_Atom = $SolrSearch->lastPage_Atom;
		
		//these are links to facets only
		$this->view->facetURI_Atom= $SolrSearch->facetURI_Atom;
		$this->view->facetURI_JSON= $SolrSearch->facetURI_JSON;
	
		$fixedParams = $requestParams;
		$fixedParams["action"] = "index";
		$host = OpenContext_OCConfig::get_host_config(); 
		$summaryObj = OpenContext_FacetOutput::active_filter_object($fixedParams, $host);
		
		$pagingArray = array("self"=> $SolrSearch->currentJSON,
					 "first" => $SolrSearch->firstPage_JSON,
					 "prev" => $SolrSearch->prevPage_JSON,
					 "next" => $SolrSearch->nextPage_JSON,
					 "last" => $SolrSearch->lastPage_JSON,
					 );
		
		$FacetURLs = new FacetURLs;
		$FacetURLs->setRequestParams($requestParams);
		$FacetURLs->geoTileFacets = $SolrSearch->geoTileFacets;
		$FacetURLs->setSolrFacets($SolrSearch->facets);
		$FacetURLs->doContextMetadata = true; //get date ranges for contexts
		$FacetURLs->default_context_path = $SolrSearch->default_context_path;
		$FacetURLs->original_default_context_path = $SolrSearch->original_default_context_path;
		$FacetURLs->facetLinking();
		
		$reconResults = false;
		$allFacets = $FacetURLs->FacetURLs;
		if(isset($allFacets["linking-relation-target"])){
			$reconResults = array();
			$linkedData = new LinkedDataRef;
			foreach($allFacets["linking-relation-target"] as $actFacet){
				$actResult["uri"] = $actFacet["name"];
				if($linkedData->lookup_refURI($actFacet["name"])){
					$actResult["label"] = $linkedData->refLabel." (".$linkedData->refVocabulary.")";
				}
				$actResult["count"] = $actFacet["count"];
				$actResult["proportion"] = round(($actFacet["count"] / $SolrSearch->numFound), 3);
				$reconResults[] = $actResult;
			}
		}
		
		$output = array("numFound" => $SolrSearch->numFound,
				"published" => $SolrSearch->lastPublished,
				"updated" => $SolrSearch->lastUpdate,
				"summary" => $summaryObj,
				"reconResults" => $reconResults
				//"qstring" => "http://localhost:8983/solr/select/?".$SolrSearch->queryString
				);
		
		$this->_helper->viewRenderer->setNoRender();
		
		
		$JSONstring = Zend_Json::encode($output);
		if($callback){
			header('Content-Type: application/javascript; charset=utf8');
			$JSONstring = $callback."(".$JSONstring.");";
		}
		else{
			header('Content-Type: application/json; charset=utf8');
			header("Access-Control-Allow-Origin: *");
		}
		echo $JSONstring;

    }//end json-results viewer


















    //get results as json, with links to new facets
    public function jsonAction() {
    
		  ini_set('max_execution_time', 150); //300 seconds = 5 minutes
		  $requestParams =  $this->_request->getParams();
		  if(OpenContext_UserMessages::isSolrDown()){
				return $this->render('down');
		  }
		  
		  $protect = new Floodprotection; //check to make sure service is not abused by too many requests
		  $protect->initialize(getenv('REMOTE_ADDR'), $this->_request->getRequestUri());
		  $protect->check_ip();
		  if($protect->lock){
				sleep($protect->sleepTime);
		  }
		  unset($protect);
		  
		  if(isset($requestParams["callback"])){
				$callback = $requestParams["callback"];
		  }
		  else{
				$callback = false;
		  }
		  
		  
		  $SolrSearch = new SolrSearch;
		  $SolrSearch->initialize();
		  $SolrSearch->geoMany = true;
		  $SolrSearch->requestURI = $this->_request->getRequestUri();
		  $output = $SolrSearch->cachedResult();
		  if(!is_array($output)){
				$SolrSearch->requestParams = $requestParams;
				$SolrSearch->PropToTaxaParameter(); // change depricated prop parameters to taxa parameters
				$requestParams = $SolrSearch->requestParams; //make sure any changes to request parameters are there for the view page
				
				$SolrSearch->spatial = true; //do a search of spatial items in Open Context
				$SolrSearch->buildSolrQuery();
				$SolrSearch->execute_search();
				$solrQuey = $SolrSearch->queryString;
				$solrSearchParams = $SolrSearch->param_array;
				$doPost = $SolrSearch->doPost;
				$SolrSearch->getLatestTime(); //get the last updated
				$SolrSearch->getLatestTime(false); //get the last published
				$atom_string = $SolrSearch->makeSpaceAtomFeed();
				$spaceResults = $SolrSearch->atom_to_object($atom_string);
				unset($atom_string);
				
				$fixedParams = $requestParams;
				$fixedParams["action"] = "index";
				$host = OpenContext_OCConfig::get_host_config(); 
				$summaryObj = OpenContext_FacetOutput::active_filter_object($fixedParams, $host);
				
				$pagingArray = array("self"=> $SolrSearch->currentJSON,
							 "first" => $SolrSearch->firstPage_JSON,
							 "prev" => $SolrSearch->prevPage_JSON,
							 "next" => $SolrSearch->nextPage_JSON,
							 "last" => $SolrSearch->lastPage_JSON,
							 );
				
				$FacetURLs = new FacetURLs;
				$FacetURLs->solrSearchParams = $solrSearchParams;
				$FacetURLs->doPost = $doPost;
				$FacetURLs->setRequestParams($requestParams);
				$FacetURLs->setSolrFacets($SolrSearch->facets);
				$FacetURLs->geoTileFacets = $SolrSearch->geoTileFacets;
				$FacetURLs->chronoTileFacets = $SolrSearch->chronoTileFacets;
				$FacetURLs->doContextMetadata = true; //get date ranges for contexts
				$FacetURLs->default_context_path = $SolrSearch->default_context_path;
				$FacetURLs->original_default_context_path = $SolrSearch->original_default_context_path;
				$FacetURLs->facetLinking();
				$FacetURLs->makeChronoData();
				
				$output = array("numFound" => $SolrSearch->numFound,
						"offset" => $SolrSearch->offset,
						"published" => $SolrSearch->lastPublished,
						"updated" => $SolrSearch->lastUpdate,
						"sorting" => $SolrSearch->sortType,
						"summary" => $summaryObj,
						"facets" => $FacetURLs->FacetURLs,
						"geoCount" => count($FacetURLs->geoTileFacetURLs),
						"geoTileFacets" => $FacetURLs->geoTileFacetURLs,
						"chronoTileFacets" => $FacetURLs->chronoTileFacetURLs,
						"paging" => $pagingArray,
						//"facetsA" => $SolrSearch->facets,
						"results" => $spaceResults["items"],
						"qstring" => $solrQuey,
						"paramCount" => count($requestParams)
						);
				
				if($SolrSearch->solrDown){
					 $output["params"] = $SolrSearch->requestParams; //show errors in params but don't save to the cache
				}
				else{
					 $SolrSearch->cacheSaveData($output); // solr in good shape, save to the cache
				}
		  }
		  
		  
		  $this->_helper->viewRenderer->setNoRender();
		  
		  
		  $JSONstring = Zend_Json::encode($output);
		  if($callback){
			  header('Content-Type: application/javascript; charset=utf8');
			  $JSONstring = $callback."(".$JSONstring.");";
		  }
		  else{
			  header('Content-Type: application/json; charset=utf8');
			  header("Access-Control-Allow-Origin: *");
		  }
		  echo $JSONstring;

    }//end json-results viewer

   

	
	
	
	
	
	
	 //get results as GeoJSON, with links to new facets
    public function geojsonAction() {
		  ini_set('max_execution_time', 150); //300 seconds = 5 minutes
		  $requestParams =  $this->_request->getParams();
		  
		  if(OpenContext_UserMessages::isSolrDown()){
			  return $this->render('down');
		  }
		  
		  $protect = new Floodprotection; //check to make sure service is not abused by too many requests
		  $protect->initialize(getenv('REMOTE_ADDR'), $this->_request->getRequestUri());
		  $protect->check_ip();
		  if($protect->lock){
			  sleep($protect->sleepTime);
		  }
		  unset($protect);
		  
		  if(isset($requestParams["callback"])){
				$callback = $requestParams["callback"];
		  }
		  else{
				$callback = false;
		  }
		  
		  
		  $SolrSearch = new SolrSearch;
		  $SolrSearch->initialize();
		  $SolrSearch->requestURI = $this->_request->getRequestUri();
		  $output = $SolrSearch->cachedResult();
		  if(!is_array($output)){
				$SolrSearch->requestParams = $requestParams;
				$SolrSearch->geoMany = true;
				$SolrSearch->PropToTaxaParameter(); // change depricated prop parameters to taxa parameters
				$requestParams = $SolrSearch->requestParams; //make sure any changes to request parameters are there for the view page
				
				$SolrSearch->spatial = true; //do a search of spatial items in Open Context
				$SolrSearch->buildSolrQuery();
				$SolrSearch->execute_search();
				$solrSearchParams = $SolrSearch->param_array;
				$doPost = $SolrSearch->doPost;
				$solrQuey = $SolrSearch->queryString;
				$SolrSearch->getLatestTime(); //get the last updated
				$SolrSearch->getLatestTime(false); //get the last published
				$atom_string = $SolrSearch->makeSpaceAtomFeed();
				$spaceResults = $SolrSearch->atom_to_object($atom_string);
				unset($atom_string);
				
				$fixedParams = $requestParams;
				$fixedParams["action"] = "index";
				$host = OpenContext_OCConfig::get_host_config(); 
				$summaryObj = OpenContext_FacetOutput::active_filter_object($fixedParams, $host);
				
				$pagingArray = array("self"=> $SolrSearch->currentJSON,
							 "first" => $SolrSearch->firstPage_JSON,
							 "prev" => $SolrSearch->prevPage_JSON,
							 "next" => $SolrSearch->nextPage_JSON,
							 "last" => $SolrSearch->lastPage_JSON,
							 );
				
				$FacetURLs = new FacetURLs;
				$FacetURLs->solrSearchParams = $solrSearchParams;
				$FacetURLs->doPost = $doPost;
				$FacetURLs->setRequestParams($requestParams);
				$FacetURLs->setSolrFacets($SolrSearch->facets);
				$FacetURLs->geoTileFacets = $SolrSearch->geoTileFacets;
				$FacetURLs->doContextMetadata = true; //get date ranges for contexts
				$FacetURLs->default_context_path = $SolrSearch->default_context_path;
				$FacetURLs->original_default_context_path = $SolrSearch->original_default_context_path;
				$FacetURLs->facetLinking();
				
				
				$GeoJSON = new GeoJSON;
				$GeoJSON->numFound = $SolrSearch->numFound;
				$GeoJSON->requestParams = $requestParams;
				if(isset($requestParams["geotile"])){
					 $GeoJSON->geoTileFacetArray = $FacetURLs->geoTileFacetURLs;
					 $geoJSONfeatures = $GeoJSON->processGeoTileFacets();
				}
				else{
					 $GeoJSON->facetArray = $FacetURLs->FacetURLs;
					 $geoJSONfeatures = $GeoJSON->processContextFacets();
				}
		  
				$output = array(
					 "type" => "FeatureCollection",
					 "features" => $geoJSONfeatures
				);
				
				
				if($SolrSearch->solrDown){
					 $output["params"] = $SolrSearch->requestParams; //show errors in params
				}
				else{
					 $SolrSearch->cacheSaveData($output); // solr in good shape, save to the cache
				}
		  }
		  
		  $this->_helper->viewRenderer->setNoRender();
		  $JSONstring = Zend_Json::encode($output);
		  if($callback){
			  header('Content-Type: application/javascript; charset=utf8');
			  $JSONstring = $callback."(".$JSONstring.");";
		  }
		  else{
			  header('Content-Type: application/json; charset=utf8');
			  header("Access-Control-Allow-Origin: *");
		  }
		  echo $JSONstring;

    }//end json-results viewer
	
	 //get results as GeoJSON, with links to new facets
    public function geojsonldAction() {
		  ini_set('max_execution_time', 150); //300 seconds = 5 minutes
		  $requestParams =  $this->_request->getParams();
		  
		  if(OpenContext_UserMessages::isSolrDown()){
			  return $this->render('down');
		  }
		  
		  $protect = new Floodprotection; //check to make sure service is not abused by too many requests
		  $protect->initialize(getenv('REMOTE_ADDR'), $this->_request->getRequestUri());
		  $protect->check_ip();
		  if($protect->lock){
			  sleep($protect->sleepTime);
		  }
		  unset($protect);
		  
		  if(isset($requestParams["callback"])){
				$callback = $requestParams["callback"];
		  }
		  else{
				$callback = false;
		  }
		  
		  
		  $SolrSearch = new SolrSearch;
		  $SolrSearch->initialize();
		  $SolrSearch->requestURI = $this->_request->getRequestUri();
		  $output = $SolrSearch->cachedResult();
		  if(!is_array($output)){
				$SolrSearch->requestParams = $requestParams;
				$SolrSearch->geoMany = true;
				$SolrSearch->PropToTaxaParameter(); // change depricated prop parameters to taxa parameters
				$requestParams = $SolrSearch->requestParams; //make sure any changes to request parameters are there for the view page
				
				$SolrSearch->spatial = true; //do a search of spatial items in Open Context
				$SolrSearch->buildSolrQuery();
				$SolrSearch->execute_search();
				$solrSearchParams = $SolrSearch->param_array;
				$doPost = $SolrSearch->doPost;
				$solrQuey = $SolrSearch->queryString;
				$SolrSearch->getLatestTime(); //get the last updated
				$SolrSearch->getLatestTime(false); //get the last published
				$atom_string = $SolrSearch->makeSpaceAtomFeed();
				$spaceResults = $SolrSearch->atom_to_object($atom_string);
				unset($atom_string);
				
				$fixedParams = $requestParams;
				$fixedParams["action"] = "index";
				$host = OpenContext_OCConfig::get_host_config(); 
				$summaryObj = OpenContext_FacetOutput::active_filter_object($fixedParams, $host);
				
				$pagingArray = array("self"=> $SolrSearch->currentJSON,
							 "first" => $SolrSearch->firstPage_JSON,
							 "prev" => $SolrSearch->prevPage_JSON,
							 "next" => $SolrSearch->nextPage_JSON,
							 "last" => $SolrSearch->lastPage_JSON,
							 );
				
				
				//consolidate hiearchic facets
				$hierarchyObj = new Facets_Hierarchy ;
			     $hierarchyObj->requestParams = $requestParams;
				$solrFacets = $SolrSearch->facets;
			     $solrFacets["facet_fields"] = $hierarchyObj->consolidateRawHierachicFacetsAllTypes($solrFacets["facet_fields"]);
				$SolrSearch->facets = $solrFacets;
				unset($solrFacets);
				
				$FacetURLs = new FacetURLs;
				$FacetURLs->solrSearchParams = $solrSearchParams;
				$FacetURLs->doPost = $doPost;
				$FacetURLs->setRequestParams($requestParams);
				$FacetURLs->setSolrFacets($SolrSearch->facets);
				$FacetURLs->geoTileFacets = $SolrSearch->geoTileFacets;
				$FacetURLs->doContextMetadata = true; //get date ranges for contexts
				$FacetURLs->default_context_path = $SolrSearch->default_context_path;
				$FacetURLs->original_default_context_path = $SolrSearch->original_default_context_path;
				$FacetURLs->facetLinking();
				
				
				$GeoJSON = new GeoJSON;
				$GeoJSON->facetCatLabels = $FacetURLs->facetCatLabels;
				$GeoJSON->facetFeedLabels = $FacetURLs->facetFeedLabels;
				$GeoJSON->numFound = $SolrSearch->numFound;
				$GeoJSON->requestParams = $requestParams;
				if(isset($requestParams["geotile"])){
					 $GeoJSON->geoTileFacetArray = $FacetURLs->geoTileFacetURLs;
					 $geoJSONfeatures = $GeoJSON->processGeoTileFacets();
				}
				else{
					 $GeoJSON->facetArray = $FacetURLs->FacetURLs;
					 $geoJSONfeatures = $GeoJSON->processContextFacets();
				}
		  
		  
				$generalFacetOutput= array("numFound" => $SolrSearch->numFound,
						"offset" => $SolrSearch->offset,
						"published" => $SolrSearch->lastPublished,
						"updated" => $SolrSearch->lastUpdate,
						"sorting" => $SolrSearch->sortType,
						"summary" => $summaryObj,
						"facets" => $FacetURLs->FacetURLs,
						"paging" => $pagingArray,
						"raw" => $SolrSearch->facets);
		  
				$output =  $GeoJSON->jsonLD($generalFacetOutput, $spaceResults["items"], $geoJSONfeatures);
				if($SolrSearch->solrDown){
					 $output["params"] = $SolrSearch->requestParams; //show errors in params
				}
				else{
					 $SolrSearch->cacheSaveData($output); // solr in good shape, save to the cache
				}
		  }
		  
		  $this->_helper->viewRenderer->setNoRender();
		  $JSONstring = json_encode($output, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
		  if($callback){
			  header('Content-Type: application/javascript; charset=utf8');
			  $JSONstring = $callback."(".$JSONstring.");";
		  }
		  else{
			  header('Content-Type: application/json; charset=utf8');
			  header("Access-Control-Allow-Origin: *");
		  }
		  echo $JSONstring;

    }//end json-results viewer
	
	
	
	 //chrono json
	 public function chronojsonAction() {
    
		  $requestParams =  $this->_request->getParams();
		  if(OpenContext_UserMessages::isSolrDown()){
				return $this->render('down');
		  }
		  
		  if(isset($requestParams["callback"])){
				$callback = $requestParams["callback"];
		  }
		  else{
				$callback = false;
		  }
		  
		  
		  $SolrSearch = new SolrSearch;
		  $SolrSearch->initialize();
		  $SolrSearch->requestURI = $this->_request->getRequestUri();
		  $output = $SolrSearch->cachedResult();
		  if(!is_array($output)){
				$SolrSearch->requestParams = $requestParams;
				$SolrSearch->PropToTaxaParameter(); // change depricated prop parameters to taxa parameters
				$requestParams = $SolrSearch->requestParams; //make sure any changes to request parameters are there for the view page
				
				$SolrSearch->spatial = true; //do a search of spatial items in Open Context
				$SolrSearch->buildSolrQuery();
				$SolrSearch->execute_search();
				$solrQuey = $SolrSearch->queryString;
				
				$FacetURLs = new FacetURLs;
				$FacetURLs->setRequestParams($requestParams);
				$FacetURLs->setSolrFacets($SolrSearch->facets);
				$FacetURLs->geoTileFacets = $SolrSearch->geoTileFacets;
				$FacetURLs->chronoTileFacets = $SolrSearch->chronoTileFacets;
				$FacetURLs->doContextMetadata = true; //get date ranges for contexts
				$FacetURLs->default_context_path = $SolrSearch->default_context_path;
				$FacetURLs->original_default_context_path = $SolrSearch->original_default_context_path;
				$FacetURLs->facetLinking();
				$FacetURLs->makeChronoData();
				
				$output = $FacetURLs->chronoData;
				
				if($SolrSearch->solrDown){
					 $output["params"] = $SolrSearch->requestParams; //show errors in params
				}
				else{
					 $SolrSearch->cacheSaveData($output); // solr in good shape, save to the cache
				}
		  }
		  
		  $this->_helper->viewRenderer->setNoRender();
		  
		  
		  $JSONstring = Zend_Json::encode($output);
		  if($callback){
			  header('Content-Type: application/javascript; charset=utf8');
			  $JSONstring = $callback."(".$JSONstring.");";
		  }
		  else{
			  header('Content-Type: application/json; charset=utf8');
			  header("Access-Control-Allow-Origin: *");
		  }
		  echo $JSONstring;

    }//end json-results viewer
	
	
	
	
	
	
	
	
    
    public function googearthAction(){
        
		if(OpenContext_UserMessages::isSolrDown()){
			return $this->render('down');
		}
		
		$protect = new Floodprotection; //check to make sure service is not abused by too many requests
		$protect->initialize(getenv('REMOTE_ADDR'), $this->_request->getRequestUri());
		$protect->check_ip();
		if($protect->lock){
			sleep($protect->sleepTime);
		}
		unset($protect);
		
		//$this->_helper->viewRenderer->setNoRender();
        $requestURI = $this->_request->getRequestUri();
		$this->view->requestParams = $this->_request->getParams();
		$requestParams = $this->_request->getParams();
		
		$requestURI = str_replace(".kml;balloonFlyto", "", $requestURI);
		$requestURI = str_replace(".kml;balloon", "", $requestURI);
		$requestURI = str_replace(".kml;flyto", "", $requestURI);
		
		$requestDecode = OpenContext_GoogleEarth::decodeFeatureAnchor($requestURI, $requestParams);
		$requestURI = $requestDecode["requestURI"];
		$requestParams = $requestDecode["params"];
		
		$checkCompData = OpenContext_GoogleEarth::checkCompData($requestURI, $requestParams);
		$requestURI = $checkCompData["uri"];
		$comp = $checkCompData["comp"];
		
		//echo $requestURI." <br/><br/>";
		//echo "<br/>comp: ".$comp;
		//echo var_dump($requestParams);
	
        $this->view->requestURI = $requestURI;
        $JSONdataURI = str_replace(".kml", ".json", $requestURI); 
		$JSONdataURI = str_replace("sets/facets/", "sets/", $JSONdataURI); // uri for the JSON version of search results
		
		$frontendOptions = array(
					'lifetime' => 72000, // cache lifetime, measured in seconds, 7200 = 2 hours
					'automatic_serialization' => true
			);
                
        $backendOptions = array(
            'cache_dir' => './cache/' // Directory where to put the cache files
        );
                
        $cache = Zend_Cache::factory('Core',
                             'File',
                             $frontendOptions,
                             $backendOptions);
        
        
        $cache->clean(Zend_Cache::CLEANING_MODE_OLD); // clean old cache records
        $cache_id = "setJS_".md5($JSONdataURI);
        
        if(!$cache_result = $cache->load($cache_id)) {
	    $JSON_string = file_get_contents($JSONdataURI);
	}
	else{
	    $JSON_string = $cache_result;
	}
	
	
	$OCData = Zend_Json::decode($JSON_string);
	
	$this->view->comp = $comp;
	if($comp != false){
	    
	    $compObject = OpenContext_GoogleEarth::getCompData($comp, $JSONdataURI, $requestParams);
	    
	    /*
	    echo "<br/>-- ".$comp.chr(13).chr(13);
	    echo "<br/>-- ".$JSONdataURI.chr(13).chr(13);
	    echo chr(13).chr(13).var_dump($compObject);
	     */
	    
	    $compDenominators = array();
	    
	    foreach($compObject["facets"]["context"] as $actCompContext){
			$key = $actCompContext["name"];
			$compDenominators[$key] = $actCompContext["count"];
	    }
	    unset($compObject);
	    //echo var_dump($compDenominators);
	    
	    $maxPercent = 100;
	    foreach($OCData["facets"]["context"] as $actContext){
			@$compTotal = $compDenominators[$actContext["name"]];
			if(!$compTotal){
				$countPercent = 100;
				$compTotal = 0;
			}
			else{
				$countPercent = round((($actContext["count"] / $compTotal )*100),0);
			}
			
			if($countPercent>$maxPercent){
				$maxPercent = $countPercent; //for cases where the numerator is larger than the denominator
			}
	    }
	    
	    
	}
	
	
	$pointArray = false;
	$contextLats = array();
	$contextLons = array();
	$contextFCount = array();
	$contextCount = 0;
	if(is_array($OCData)){
	 if(array_key_exists("context", $OCData["facets"])){
	     $contextCount = count($OCData["facets"]["context"]);
	 }
	 else{
	     echo "<!-- ".$requestURI." -->";
	 }
	}
	
	if($contextCount>0){
	    $pointArray = array();
	    $coordArray = array();
	    //loop through and count numbers of instances for each point
	    foreach($OCData["facets"]["context"] as $actContext){
		$actCoords = $actContext["geoTime"]["geoLat"]." ".$actContext["geoTime"]["geoLong"];
		if(array_key_exists($actCoords, $coordArray)){
		    $coordArray[$actCoords]++;
		}
		else{
		    $coordArray[$actCoords] = 1;
		}
	    }
	    
	    $i=0;
	    foreach($OCData["facets"]["context"] as $actContext){
		$contextLats[] = $actContext["geoTime"]["geoLat"]; //latidude
		$contextLons[] = $actContext["geoTime"]["geoLong"]; //longitude
		$contextFCount[] = $actContext["count"] + ($i / 10000); // small number added so that sorting can happen
		$actCoords = $actContext["geoTime"]["geoLat"]." ".$actContext["geoTime"]["geoLong"];
		if(array_key_exists($actCoords, $coordArray)){
		    $pointArray[$actContext["href"]] = $coordArray[$actCoords];
		}
		else{
		   $pointArray[$actContext["href"]] = 1;
		}
		
	    $i++;
	    }
	    
	    //$Ranked_FCounts = $contextFCount;
	    //sort($Ranked_FCounts);
	    
	    $maxDistance = OpenContext_GoogleEarth::geo_max_distance($contextLats, $contextLons);
	    $maxCount = max($contextFCount);
	    
	    $square_size_factor = .05; //this number represents the size of square polygons as a proportion of the maximum distance between points 
    
	    if($maxDistance == 0){	
		//determine where to put auto-generated polygons
		$square_size = (.0001);
		$doOffsets = true;
	    }
	    else{
		$square_size = ($square_size_factor*$maxDistance)+($square_size_factor*$maxDistance*.5*($contextCount/$maxCount));
		$doOffsets = false;
	    }
	    
	    //$rank = $contextCount;
	    $rank = 0;
	    $genPolys = array();
	    $maxHeight = 0;
	    $maxTime = -4500000000;
	    $minTime = 30000;
	    foreach($OCData["facets"]["context"] as $actContext){
		
		$key = $actContext["href"];
		
		if(!$comp){ //set color and heights based on absolute counts
		    //sets the KML color for a context
		    $countPercent = false;
		    $compTotal = false;
		    
		    $contextColor = OpenContext_GoogleEarth::kml_set_color($actContext["count"], $maxCount);
		    //sets the KML height for a given context
		    $act_height = OpenContext_GoogleEarth::kml_set_height($actContext["count"], $maxCount, $maxDistance);
		}
		else{
		    @$compTotal = $compDenominators[$actContext["name"]];
		    if(!$compTotal){
			$countPercent = 100;
			$compTotal = -1;
		    }
		    else{
			$countPercent = round((($actContext["count"] / $compTotal)*100),0);
		    }
		    $contextColor = OpenContext_GoogleEarth::kml_set_color($countPercent , $maxPercent);
		    $act_height = OpenContext_GoogleEarth::kml_set_height($countPercent, $maxPercent, $maxDistance);
		}
		
		if($act_height>$maxHeight){
		    $maxHeight = $act_height;
		}
		
		$act_lat = $actContext["geoTime"]["geoLat"]; 
		$act_lon = $actContext["geoTime"]["geoLong"]; 
		
		if($actContext["geoTime"]["timeBegin"] < $minTime){
		    $minTime = $actContext["geoTime"]["timeBegin"];
		}
		
		if($actContext["geoTime"]["timeEnd"] > $maxTime){
		    $maxTime = $actContext["geoTime"]["timeEnd"];
		}
		
		if($doOffsets){
		    $act_genout = OpenContext_GoogleEarth::kml_gen_polygon_points($square_size, $rank, $contextCount, $act_lat, $act_lon, $act_height);
		    $contextPolygon = $act_genout["poly"];
		    $contextPoint = $act_genout["point"]; 
		}//end case of making a longitude offset for autogenerating polygons
		else{
		    $act_lon_offset = 0;
		    $act_genout = OpenContext_GoogleEarth::kml_gen_polypoints($square_size, $act_lon_offset, $act_lat, $act_lon, $act_height);
		    $contextPolygon = $act_genout["poly"];
		    $contextPoint = $act_genout["point"];
		}
		
		$genPolys[$key] = array("point"=>$contextPoint,
					"poly"=>trim($contextPolygon),
					"height"=> $act_height,
					"color"=> $contextColor,
					"cntPercent" => $countPercent,
					"cntTotal" => $compTotal);
		
	    //$rank = $rank - 1;
	    $rank++;
	    }
	    
	    
	    $midLat = (max($contextLats) + min($contextLats))/2;
	    $midLon = (max($contextLons) + min($contextLons))/2;
	    
	    if($doOffsets){
		$viewRange = sqrt(($maxHeight*$maxHeight)+ ($maxHeight*$maxHeight));
	    }
	    else{
		$viewRange = 2*(sqrt(($maxHeight*$maxHeight)+ ($maxDistance*$maxDistance)));
	    }
	    
	    $this->view->lookPos = array("lat" => $midLat,
					 "lon" => $midLon,
					 "range"=> $viewRange,
					 "maxAlt"=> $maxHeight,
					 "minTime"=> $minTime,
					 "maxTime"=> $maxTime);
	    
	    //$genPolys["maxDist"] = $maxDistance;
	    //$genPolys["maxCount"] = $maxCount;
	    //$genPolys["sqSize"] = $square_size;
	    
	}
	else{
	    $OCData = false;
	    $genPolys = false;
	}
	
	$this->view->OCData = $OCData;
	$this->view->GenPolys = $genPolys;
	$this->view->pointArray = $pointArray;
	//header('Content-Type: application/json; charset=utf8');
        //echo Zend_Json::encode($genPolys);
	
    }//end Googleearth Action function
    
    
    
    
    public function kmlresultsAction(){
        
		if(OpenContext_UserMessages::isSolrDown()){
			return $this->render('down');
		}
		
		$protect = new Floodprotection; //check to make sure service is not abused by too many requests
		$protect->initialize(getenv('REMOTE_ADDR'), $this->_request->getRequestUri());
		$protect->check_ip();
		if($protect->lock){
			sleep($protect->sleepTime);
		}
		unset($protect);
		
		//$this->_helper->viewRenderer->setNoRender();
		$requestURI = $this->_request->getRequestUri();
		$this->view->requestParams = $this->_request->getParams();
		$requestParams = $this->_request->getParams();
		
		$requestURI = str_replace(".kml;balloonFlyto", "", $requestURI);
		$requestURI = str_replace(".kml;balloon", "", $requestURI);
		$requestURI = str_replace(".kml;flyto", "", $requestURI);
		
		$requestDecode = OpenContext_GoogleEarth::decodeFeatureAnchor($requestURI, $requestParams);
		$requestURI = $requestDecode["requestURI"];
		$requestParams = $requestDecode["params"];
		
		//echo $requestURI;
		$this->view->requestURI = $requestURI;
		$JSONdataURI = str_replace(".kml", ".json", $requestURI); // uri for the JSON version of search results
		
		$frontendOptions = array(
					'lifetime' => 72000, // cache lifetime, measured in seconds, 7200 = 2 hours
					'automatic_serialization' => true
			);
					
			$backendOptions = array(
				'cache_dir' => './cache/' // Directory where to put the cache files
			);
					
			$cache = Zend_Cache::factory('Core',
								 'File',
								 $frontendOptions,
								 $backendOptions);
			
			
			$cache->clean(Zend_Cache::CLEANING_MODE_OLD); // clean old cache records
			$cache_id = "setJS_".md5($JSONdataURI);
			
			if(!$cache_result = $cache->load($cache_id)) {
			$JSON_string = file_get_contents($JSONdataURI);
		}
		else{
			$JSON_string = $cache_result;
		}
		
		
		$OCData = Zend_Json::decode($JSON_string);
		
		$itemPoint = array();
		$resultCount = 0;
		if(array_key_exists("results", $OCData)){
			$resultCount = count($OCData["results"]);
		}
		else{
			echo "<!-- ".$requestURI." -->";
		}
		
		
		$genPoints = false;
		if($resultCount>0){
			$genPoints = array();
			$lonArray = array();
			$latArray = array();
			$minTime = 30000;
			$maxTime = -4500000000;
			foreach($OCData["results"] as $actItem){
			
			$act_lat = $actItem["geoTime"]["geoLat"]; 
			$act_lon = $actItem["geoTime"]["geoLong"];
			$latArray[] = $act_lat;
			$lonArray[] = $act_lon;
			$act_point = $act_lon.",".$act_lat;
			
			if($minTime > $actItem["geoTime"]["timeBegin"]){
			   $minTime = $actItem["geoTime"]["timeBegin"];
			}
			if($maxTime < $actItem["geoTime"]["timeEnd"]){
			   $maxTime = $actItem["geoTime"]["timeEnd"];
			}
			
			
			if(!in_array($act_point, $itemPoint)){
				$itemPoint[] = $act_point;
				$truePoint = true;
			}
			else{
				$randLat = (rand(-500,500))/100; //10 meter random offset
				$randLon = (rand(-500,500))/100; //10 meter random offset
				$degreeFactor = 1 /(111319.5);  // 1 degree = 111319.5 meters at the equator
				$act_lat = $act_lat + ($randLat*$degreeFactor);
				$act_lon = $act_lon + ($randLon*$degreeFactor);   
				$truePoint = false;
				$act_point = $act_lon.",".$act_lat;
			}
			
			
			$key = $actItem["uri"];
			$genPoints[$key] = array("point"=>$act_point, "truePoint"=>$truePoint);
			
			}
			
			$midLat = array_sum($latArray)/$resultCount;
			$midLon = array_sum($lonArray)/$resultCount;
			$viewRange = 100;
			
			$this->view->lookPos = array("lat" => $OCData["results"][0]["geoTime"]["geoLat"],
						 "lon" => $OCData["results"][0]["geoTime"]["geoLong"],
						 "range"=> $viewRange,
						 "maxAlt"=> 100,
						 "minTime"=> $minTime,
						 "maxTime"=> $maxTime);
			
		}
		else{
			$OCData = false;
			$genPoints = false;
		}
		
		$this->view->OCData = $OCData;
		$this->view->GenPoints = $genPoints;
		
		//echo var_dump($genPoints);
		
		//header('Content-Type: application/json; charset=utf8');
			//echo Zend_Json::encode($genPolys);
	
    }//end Googleearth Action function
    
    
    //open search service
    public function searchAction(){
		  $this->view->requestURI = $this->_request->getRequestUri(); // for URI to search links
    }//end function
    
    
}//end class