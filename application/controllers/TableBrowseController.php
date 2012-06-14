<?php

/** Zend_Controller_Action */

/*
This controller is primary aimed at full-text searches of all of Opentext content.

*/
class TableBrowseController extends Zend_Controller_Action {

	public function blubAction(){
		$this->_helper->viewRenderer->setNoRender();
		echo "blub";
	}


	//XHTML view of search results
	public function indexAction() {
    
		//$this->_helper->viewRenderer->setNoRender();
		
		$requestParams =  $this->_request->getParams();
		
		$protect = new Floodprotection; //check to make sure service is not abused by too many requests
		$protect->initialize(getenv('REMOTE_ADDR'), $this->_request->getRequestUri());
		$protect->addedRequests = 2;
		$protect->check_ip();
		if($protect->lock){
		    sleep($protect->sleepTime);
		}
		unset($protect);
		OpenContext_SocialTracking::update_referring_link('table-browse', $this->_request->getRequestUri(), @$_SERVER['HTTP_USER_AGENT'], @$_SERVER['HTTP_REFERER']);
	
			
		
		$solrSearch = new solrSearch;
		$solrSearch->initialize();
		$solrSearch->number_recs =10;
		//$solrSearch->defaultSort = "interest_score desc";
		$solrSearch->requestURI = $this->_request->getRequestUri();
		$solrSearch->requestParams = $requestParams;
		$solrSearch->PropToTaxaParameter(); // change depricated prop parameters to taxa parameters
		$requestParams = $solrSearch->requestParams; //make sure any changes to request parameters are there for the view page
		
		/*
		$solrSearch->spatial = true; //do a search for spatial items
		$solrSearch->image = true; //do a search for image items
		$solrSearch->media = true; //do a search for media items
		$solrSearch->document = true; //do a search for document items
		$solrSearch->project = true; //do a search for project items
		*/
		
		$solrSearch->table = true; //do a search for table items
		
		$solrSearch->buildSolrQuery();
		$solrSearch->execute_search();
		
		if($solrSearch->solrDown){
			return $this->render('down');
		}
		
		$solrSearch->getLatestTime(); //get the last updated
		//$solrSearch->getLatestTime(false); //get the last published
		
		$this->view->requestURI = $this->_request->getRequestUri(); // for testing
		$this->view->requestParams = $requestParams;  // for testing
		$this->view->numFound = $solrSearch->numFound;
		$this->view->facets = $solrSearch->facets;
		$this->view->docs = $solrSearch->documentsArray;
		$this->view->offset = $solrSearch->offset;
		$this->view->number_recs = $solrSearch->number_recs;
		$this->view->sorting = $solrSearch->sortType;
		$this->view->lastUpdate = $solrSearch->lastUpdate;
		
		$this->view->currentXHTML = str_replace("&", "&amp;", $solrSearch->currentXHTML);
		$this->view->currentJSON = str_replace("&", "&amp;", $solrSearch->currentJSON);
		$this->view->currentAtom = str_replace("&", "&amp;", $solrSearch->currentAtom);
		$this->view->firstPage_XHTML = str_replace("&", "&amp;", $solrSearch->firstPage_XHTML);
		$this->view->firstPage_JSON = str_replace("&", "&amp;", $solrSearch->firstPage_JSON);
		$this->view->firstPage_Atom = str_replace("&", "&amp;", $solrSearch->firstPage_Atom); 
		$this->view->prevPage_XHTML = str_replace("&", "&amp;", $solrSearch->prevPage_XHTML);
		$this->view->prevPage_JSON = str_replace("&", "&amp;", $solrSearch->prevPage_JSON);
		$this->view->prevPage_Atom = str_replace("&", "&amp;", $solrSearch->prevPage_Atom);
		$this->view->nextPage_XHTML = str_replace("&", "&amp;", $solrSearch->nextPage_XHTML);
		$this->view->nextPage_JSON = str_replace("&", "&amp;", $solrSearch->nextPage_JSON);
		$this->view->nextPage_Atom = str_replace("&", "&amp;", $solrSearch->nextPage_Atom);
		$this->view->lastPage_XHTML = str_replace("&", "&amp;", $solrSearch->lastPage_XHTML);
		$this->view->lastPage_JSON = str_replace("&", "&amp;", $solrSearch->lastPage_JSON);
		$this->view->lastPage_Atom = str_replace("&", "&amp;", $solrSearch->lastPage_Atom);
		
		//these are links to facets only
		$this->view->facetURI_Atom= $solrSearch->facetURI_Atom;
		$this->view->facetURI_JSON= $solrSearch->facetURI_JSON;
		
		if($solrSearch->numFound < 1){
			return $this->render('noresults');
		}
		
		$checkRequest = $requestParams;
		if(isset($checkRequest["controller"])){
			unset($checkRequest["controller"]);
		}
		if(isset($checkRequest["action"])){
			unset($checkRequest["action"]);
		}
		if(isset($checkRequest["module"])){
			unset($checkRequest["module"]);
		}
		
		
		
		
		/*
		$output = array("numFound" => $solrSearch->numFound,
				"facets" => $solrSearch->facets,
				"docs" => $solrSearch->documentsArray);
		
		$this->_helper->viewRenderer->setNoRender();
		header('Content-Type: application/json; charset=utf8');
		echo Zend_Json::encode($solrSearch);
		*/                                                                                                          

	}//end index viewer


	public function resultsAction() {
    
		$requestParams =  $this->_request->getParams();
		$protect = new Floodprotection; //check to make sure service is not abused by too many requests
		$protect->initialize(getenv('REMOTE_ADDR'), $this->_request->getRequestUri());
		$protect->check_ip();
		if($protect->lock){
		    sleep($protect->sleepTime);
		}
		unset($protect);
		
		
		$solrSearch = new solrSearch;
		$solrSearch->initialize();
		$solrSearch->number_recs =10;
		$solrSearch->requestURI = $this->_request->getRequestUri();
		$solrSearch->requestParams = $requestParams;
		$solrSearch->PropToTaxaParameter(); // change depricated prop parameters to taxa parameters
		$requestParams = $solrSearch->requestParams; //make sure any changes to request parameters are there for the view page
		
		/*
		$solrSearch->spatial = true; //do a search for spatial items
		$solrSearch->image = true; //do a search for image items
		$solrSearch->media = true; //do a search for media items
		$solrSearch->document = true; //do a search for document items
		$solrSearch->project = true; //do a search for project items
		*/
		
		$solrSearch->table = true; //do a search for table items
		$solrSearch->buildSolrQuery();
		$solrSearch->execute_search();
		$solrSearch->getLatestTime(); //get the last updated
		$solrSearch->getLatestTime(false); //get the last published
		
				$this->view->requestURI = $this->_request->getRequestUri(); // for testing
		$this->view->requestParams = $requestParams;  // for testing
		$this->view->numFound = $solrSearch->numFound;
		$this->view->facets = $solrSearch->facets;
		$this->view->docs = $solrSearch->documentsArray;
		$this->view->offset = $solrSearch->offset;
		$this->view->number_recs = $solrSearch->number_recs;
		$this->view->sorting = $solrSearch->sortType;
		$this->view->lastUpdate = $solrSearch->lastUpdate;
		$this->view->lastPublished = $solrSearch->lastPublished;
		
		$this->view->currentXHTML = $solrSearch->currentXHTML;
		$this->view->currentJSON = $solrSearch->currentJSON;
		$this->view->currentAtom = $solrSearch->currentAtom;
		$this->view->firstPage_XHTML = $solrSearch->firstPage_XHTML;
		$this->view->firstPage_JSON = $solrSearch->firstPage_JSON;
		$this->view->firstPage_Atom = $solrSearch->firstPage_Atom; 
		$this->view->prevPage_XHTML = $solrSearch->prevPage_XHTML;
		$this->view->prevPage_JSON = $solrSearch->prevPage_JSON;
		$this->view->prevPage_Atom = $solrSearch->prevPage_Atom;
		$this->view->nextPage_XHTML = $solrSearch->nextPage_XHTML;
		$this->view->nextPage_JSON = $solrSearch->nextPage_JSON;
		$this->view->nextPage_Atom = $solrSearch->nextPage_Atom;
		$this->view->lastPage_XHTML = $solrSearch->lastPage_XHTML;
		$this->view->lastPage_JSON = $solrSearch->lastPage_JSON;
		$this->view->lastPage_Atom = $solrSearch->lastPage_Atom;
		
		//these are links to facets only
		$this->view->facetURI_Atom = $solrSearch->facetURI_Atom;
		$this->view->facetURI_JSON = $solrSearch->facetURI_JSON;

		/*
		$fixedParams = $requestParams;
		$fixedParams["action"] = "index";
		$host = OpenContext_OCConfig::get_host_config(); 
		$summaryObj = OpenContext_FacetOutput::active_filter_object($fixedParams, $host);
		*/
		                                                                                                              
    }//end Atom-results viewer


	public function facetsAction() {
    
		$requestParams =  $this->_request->getParams();
		
		$solrSearch = new solrSearch;
		$solrSearch->initialize();
		$solrSearch->requestURI = $this->_request->getRequestUri();
		$solrSearch->requestParams = $requestParams;
		$solrSearch->PropToTaxaParameter(); // change depricated prop parameters to taxa parameters
		$requestParams = $solrSearch->requestParams; //make sure any changes to request parameters are there for the view page
		
		/*
		$solrSearch->spatial = true; //do a search for spatial items
		$solrSearch->image = true; //do a search for image items
		$solrSearch->media = true; //do a search for media items
		$solrSearch->document = true; //do a search for document items
		$solrSearch->project = true; //do a search for project items
		*/
		
		$solrSearch->table = true; //do a search for table items
		$solrSearch->buildSolrQuery();
		$solrSearch->execute_search();
		$solrSearch->getLatestTime(); //get the last updated
		$solrSearch->getLatestTime(false); //get the last published
		
		$this->view->requestURI = $this->_request->getRequestUri(); // for testing
		$this->view->requestParams = $requestParams;  // for testing
		$this->view->numFound = $solrSearch->numFound;
		$this->view->facets = $solrSearch->facets;
		$this->view->docs = $solrSearch->documentsArray;
		$this->view->offset = $solrSearch->offset;
		$this->view->number_recs = $solrSearch->number_recs;
		$this->view->sorting = $solrSearch->sortType;
		$this->view->lastUpdate = $solrSearch->lastUpdate;
		$this->view->lastPublished = $solrSearch->lastPublished;
		
		$this->view->currentXHTML = $solrSearch->currentXHTML;
		$this->view->currentJSON = $solrSearch->currentJSON;
		$this->view->currentAtom = $solrSearch->currentAtom;
		$this->view->firstPage_XHTML = $solrSearch->firstPage_XHTML;
		$this->view->firstPage_JSON = $solrSearch->firstPage_JSON;
		$this->view->firstPage_Atom = $solrSearch->firstPage_Atom; 
		$this->view->prevPage_XHTML = $solrSearch->prevPage_XHTML;
		$this->view->prevPage_JSON = $solrSearch->prevPage_JSON;
		$this->view->prevPage_Atom = $solrSearch->prevPage_Atom;
		$this->view->nextPage_XHTML = $solrSearch->nextPage_XHTML;
		$this->view->nextPage_JSON = $solrSearch->nextPage_JSON;
		$this->view->nextPage_Atom = $solrSearch->nextPage_Atom;
		$this->view->lastPage_XHTML = $solrSearch->lastPage_XHTML;
		$this->view->lastPage_JSON = $solrSearch->lastPage_JSON;
		$this->view->lastPage_Atom = $solrSearch->lastPage_Atom;
		
		//these are links to facets only
		$this->view->facetURI_Atom = $solrSearch->facetURI_Atom;
		$this->view->facetURI_JSON = $solrSearch->facetURI_JSON;

		$fixedParams = $requestParams;
		$fixedParams["action"] = "index";
		$host = OpenContext_OCConfig::get_host_config(); 
		$summaryObj = OpenContext_FacetOutput::active_filter_object($fixedParams, $host);
		
		$facetURLs = new facetURLs;
		$facetURLs->setRequestParams($requestParams);
		$facetURLs->setSolrFacets($solrSearch->facets);
		$facetURLs->doContextMetadata = true; //get date ranges for contexts
		$facetURLs->default_context_path = $solrSearch->default_context_path;
		$facetURLs->original_default_context_path = $solrSearch->original_default_context_path;
		$facetURLs->facetLinking();
		
		$this->view->facets = $facetURLs->facetURLs;
		
    }//end atom-facets viewer


	public function jsonAction() {
    
		$requestParams =  $this->_request->getParams();
		$protect = new Floodprotection; //check to make sure service is not abused by too many requests
		$protect->initialize(getenv('REMOTE_ADDR'), $this->_request->getRequestUri());
		$protect->check_ip();
		if($protect->lock){
		    sleep($protect->sleepTime);
		}
		unset($protect);
		
		$solrSearch = new solrSearch;
		$solrSearch->initialize();
		$solrSearch->number_recs =10;
		$solrSearch->requestURI = $this->_request->getRequestUri();
		$solrSearch->requestParams = $requestParams;
		$solrSearch->PropToTaxaParameter(); // change depricated prop parameters to taxa parameters
		$requestParams = $solrSearch->requestParams; //make sure any changes to request parameters are there for the view page
		
		/*
		$solrSearch->spatial = true; //do a search for spatial items
		$solrSearch->image = true; //do a search for image items
		$solrSearch->media = true; //do a search for media items
		$solrSearch->document = true; //do a search for document items
		$solrSearch->project = true; //do a search for project items
		*/
		
		$solrSearch->table = true; //do a search for table items
		$solrSearch->buildSolrQuery();
		$solrSearch->execute_search();
		$solrSearch->getLatestTime(); //get the last updated
		$solrSearch->getLatestTime(false); //get the last published
		
		$this->view->requestURI = $this->_request->getRequestUri(); // for testing
		$this->view->requestParams = $requestParams;  // for testing
		$this->view->numFound = $solrSearch->numFound;
		$this->view->facets = $solrSearch->facets;
		$this->view->docs = $solrSearch->documentsArray;
		$this->view->offset = $solrSearch->offset;
		
		$this->view->currentXHTML = $solrSearch->currentXHTML;
		$this->view->currentJSON = $solrSearch->currentJSON;
		$this->view->currentAtom = $solrSearch->currentAtom;
		$this->view->firstPage_XHTML = $solrSearch->firstPage_XHTML;
		$this->view->firstPage_JSON = $solrSearch->firstPage_JSON;
		$this->view->firstPage_Atom = $solrSearch->firstPage_Atom; 
		$this->view->prevPage_XHTML = $solrSearch->prevPage_XHTML;
		$this->view->prevPage_JSON = $solrSearch->prevPage_JSON;
		$this->view->prevPage_Atom = $solrSearch->prevPage_Atom;
		$this->view->nextPage_XHTML = $solrSearch->nextPage_XHTML;
		$this->view->nextPage_JSON = $solrSearch->nextPage_JSON;
		$this->view->nextPage_Atom = $solrSearch->nextPage_Atom;
		$this->view->lastPage_XHTML = $solrSearch->lastPage_XHTML;
		$this->view->lastPage_JSON = $solrSearch->lastPage_JSON;
		$this->view->lastPage_Atom = $solrSearch->lastPage_Atom;
		
		//these are links to facets only
		$this->view->facetURI_Atom= $solrSearch->facetURI_Atom;
		$this->view->facetURI_JSON= $solrSearch->facetURI_JSON;

		$fixedParams = $requestParams;
		$fixedParams["action"] = "index";
		$host = OpenContext_OCConfig::get_host_config(); 
		$summaryObj = OpenContext_FacetOutput::active_filter_object($fixedParams, $host);
		
		$pagingArray = array("self"=> $solrSearch->currentJSON,
				     "first" => $solrSearch->firstPage_JSON,
				     "prev" => $solrSearch->prevPage_JSON,
				     "next" => $solrSearch->nextPage_JSON,
				     "last" => $solrSearch->lastPage_JSON,
				     );
		
		$facetURLs = new facetURLs;
		$facetURLs->setRequestParams($requestParams);
		$facetURLs->setSolrFacets($solrSearch->facets);
		$facetURLs->doContextMetadata = true; //get date ranges for contexts
		$facetURLs->default_context_path = $solrSearch->default_context_path;
		$facetURLs->original_default_context_path = $solrSearch->original_default_context_path;
		$facetURLs->facetLinking();
		
		$output = array("numFound" => $solrSearch->numFound,
				"offset" => $solrSearch->offset,
				"published" => $solrSearch->lastPublished,
				"updateed" => $solrSearch->lastUpdate,
				"sorting" => $solrSearch->sortType,
				"summary" => $summaryObj,
				"facets" => $facetURLs->facetURLs,
				"paging" => $pagingArray,
				"results" => $solrSearch->documentsArray,
				"query" => $solrSearch->queryString
				);
		
		$this->_helper->viewRenderer->setNoRender();
		header('Content-Type: application/json; charset=utf8');
		echo Zend_Json::encode($output);
		                                                                                                              

    }//end json-results viewer


    //open search service
    public function searchAction(){
	$this->view->requestURI = $this->_request->getRequestUri(); // for URI to search links
    }//end function
	
}//end controller class