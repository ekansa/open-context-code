<?php
/** Zend_Controller_Action */
//require_once 'Zend/Controller/Action.php';

class lightboxController extends Zend_Controller_Action {
    
    //XHTML view of search results
    public function indexAction() {
    
	//$this->_helper->viewRenderer->setNoRender();
	
	$requestParams =  $this->_request->getParams();
	if(OpenContext_UserMessages::isSolrDown()){
	    return $this->render('down');
	}
	$protect = new Floodprotection; //check to make sure service is not abused by too many requests
	$protect->initialize(getenv('REMOTE_ADDR'), $this->_request->getRequestUri());
	$protect->addedRequests = 2;
	$protect->userAgent = @$_SERVER['HTTP_USER_AGENT'];
	$protect->check_ip();
	if($protect->lock){
	    sleep($protect->sleepTime);
	}
	unset($protect);
	OpenContext_SocialTracking::update_referring_link('lightbox', $this->_request->getRequestUri(), @$_SERVER['HTTP_USER_AGENT'], @$_SERVER['HTTP_REFERER']);
	
	$SolrSearch = new SolrSearch;
	$SolrSearch->initialize();
	$SolrSearch->number_recs = 18; //number of results per page
	$SolrSearch->defaultSort = "interest_score desc";
	$SolrSearch->requestURI = $this->_request->getRequestUri();
	$SolrSearch->requestParams = $requestParams;
	$SolrSearch->PropToTaxaParameter(); // change depricated prop parameters to taxa parameters
	$requestParams = $SolrSearch->requestParams; //make sure any changes to request parameters are there for the view page
	
	$SolrSearch->image = true; //do a search of image items in Open Context
	$SolrSearch->buildSolrQuery();
	$SolrSearch->execute_search();
	
	if($SolrSearch->solrDown){
	    return $this->render('down');
	}
	
	$SolrSearch->getLatestTime(); //get the last updated
	$imageResults = $SolrSearch->makeImageObject();
	
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
	
	if($SolrSearch->numFound < 1){
	    return $this->render('noresults');
	}
	
	$this->view->result_output = $imageResults; //use to generate xhtml result output
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
	$SolrSearch->number_recs = 18; //number of results per page
	$SolrSearch->defaultSort = "interest_score desc";
	$SolrSearch->requestURI = $this->_request->getRequestUri();
	$SolrSearch->requestParams = $requestParams;
	$SolrSearch->PropToTaxaParameter(); // change depricated prop parameters to taxa parameters
	$requestParams = $SolrSearch->requestParams; //make sure any changes to request parameters are there for the view page
	
	$SolrSearch->image = true; //do a search of image items in Open Context
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

	$fixedParams = $requestParams;
	$fixedParams["action"] = "index";
	$host = OpenContext_OCConfig::get_host_config(); 
	$summaryObj = OpenContext_FacetOutput::active_filter_object($fixedParams, $host);
	
	$this->_helper->viewRenderer->setNoRender();
	
	header('Content-Type: application/atom+xml; charset=utf8');
	echo $SolrSearch->makeImageAtomFeed();
	    
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
	
	$SolrSearch->image = true; //do a search of image items in Open Context
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


    
    
    //get results as json, with links to new facets
    public function jsonAction() {
    
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
	$SolrSearch->number_recs = 18; //number of results per page
	$SolrSearch->defaultSort = "interest_score desc";
	$SolrSearch->requestURI = $this->_request->getRequestUri();
	$SolrSearch->requestParams = $requestParams;
	$SolrSearch->PropToTaxaParameter(); // change depricated prop parameters to taxa parameters
	$requestParams = $SolrSearch->requestParams; //make sure any changes to request parameters are there for the view page
	
	$SolrSearch->image = true; //do a search of image items in Open Context
	$SolrSearch->buildSolrQuery();
	$SolrSearch->execute_search();
	$SolrSearch->getLatestTime(); //get the last updated
	$SolrSearch->getLatestTime(false); //get the last published
	$imageResults = $SolrSearch->makeImageObject();
	
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
	$FacetURLs->setSolrFacets($SolrSearch->facets);
	$FacetURLs->doContextMetadata = true; //get date ranges for contexts
	$FacetURLs->default_context_path = $SolrSearch->default_context_path;
	$FacetURLs->original_default_context_path = $SolrSearch->original_default_context_path;
	$FacetURLs->facetLinking();
	
	$output = array("numFound" => $SolrSearch->numFound,
			"offset" => $SolrSearch->offset,
			"published" => $SolrSearch->lastPublished,
			"updateed" => $SolrSearch->lastUpdate,
			"sorting" => $SolrSearch->sortType,
			"summary" => $summaryObj,
			"facets" => $FacetURLs->FacetURLs,
			"paging" => $pagingArray,
			"results" => $imageResults
			//"solrFacets" => $SolrSearch->facets
		    );
	
	$this->_helper->viewRenderer->setNoRender();
	
	$JSONstring = Zend_Json::encode($output);
	if(isset($requestParams["callback"])){
	    $callback = $requestParams["callback"];
	}
	else{
	    $callback = false;
	}
	
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
    
    
    //open search service
    public function searchAction(){
	$this->view->requestURI = $this->_request->getRequestUri(); // for URI to search links
    }//end function
    
    
    
}