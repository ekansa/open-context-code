<?php


//this class interacts with solr to run searches
class AtomResults{
    
    
    public $page;        //page of result request
    public $number_recs; //number of results per page
    public $offset; // page offset value
    public $defaultSort; //default sort value
    public $error;
    
    public $substance; //do for substances (boolean default = false)
    public $allDocs; //do all documents (boolean default = false)
    public $spatial; //boolean, search spatial docs (default = false)
    public $image; //boolean, search image docs (default = false)
    public $media; //boolean, search media docs (default = false)
    public $person; //boolean, search person docs (default = false)
    public $project; //boolean, search project docs (default = false)
    public $document; //boolean, search document docs (default = false)
    public $table; //boolean, search table docs (default = false)
    public $site; //boolean, search site documentation docs (default = false)
    
    public $requestURI; //request URI
    public $requestParams; // array of the request parameters and values
    
    //these are for paging through result sets
    
    public $currentXHTML;
    public $currentJSON;
    public $currentAtom;
    
    public $firstPage_XHTML;
    public $firstPage_JSON;
    public $firstPage_Atom;
    public $prevPage_XHTML;
    public $prevPage_JSON;
    public $prevPage_Atom;
    public $nextPage_XHTML;
    public $nextPage_JSON;
    public $nextPage_Atom;
    public $lastPage_XHTML;
    public $lastPage_JSON;
    public $lastPage_Atom;
    
    //these are links to facets only
    public $facetURI_Atom;
    public $facetURI_JSON;
    public $openSearchURI;
    
    public $numFound; //total number of records found in Solr search
    public $lastPage;
    

    const MaxsRecords = 100;
    const geoLevelDeep = 3; //how many levels in geo tiles down will we go?
    
    //initialize the search, set search document types to false
    function initialize(){
	
	$this->number_recs = 10; //default number of records to show
	$this->defaultSort = "label";
	$this->substance = true;
	
	$this->allDocs = false;
	$this->spatial = false; //boolean, search spatial docs (default = false)
	$this->image = false; //boolean, search image docs (default = false)
	$this->media = false; //boolean, search media docs (default = false)
	$this->person = false; //boolean, search person docs (default = false)
	$this->project = false; //boolean, search project docs (default = false)
	$this->document = false; //boolean, search document docs (default = false)
	$this->table = false; //boolean, search table docs (default = false)
	$this->site = false; //boolean, search site documentation docs (default = false)
	$this->showTaxaFacets = false;
	$this->showPeopleFacets = false;
	$this->geoFacets = false;
	$this->geoParam = false;
	$this->geoPath = false;
	
	$this->currentXHTML = false;
	$this->currentJSON = false;
	$this->currentAtom = false;
	$this->firstPage_XHTML= false;
	$this->firstPage_JSON= false;
	$this->firstPage_Atom= false;
	$this->prevPage_XHTML= false;
	$this->prevPage_JSON= false;
	$this->prevPage_Atom= false;
	$this->nextPage_XHTML= false;
	$this->nextPage_JSON= false;
	$this->nextPage_Atom= false;
	$this->lastPage_XHTML= false;
	$this->lastPage_JSON= false;
	$this->lastPage_Atom= false;
	
	//these are links to facets only
	$this->openSearchURI = false;
	$this->facetURI_Atom= false;
	$this->facetURI_JSON= false;
    }//end function


    //a general text search is requested, look in all document types
    function doAllSearch(){
	$this->allDocs = true;
	$this->spatial = true; //boolean, search spatial docs (default = false)
	$this->image = true; //boolean, search image docs (default = false)
	$this->media = true; //boolean, search media docs (default = false)
	$this->person = true; //boolean, search person docs (default = false)
	$this->project = true; //boolean, search project docs (default = false)
	$this->document = true; //boolean, search document docs (default = false)
	$this->table = true; //boolean, search table docs (default = false)
	$this->site = true; //boolean, search site documentation docs (default = false)
    }


    //set up paging dependind on the number of items requested
    //uses requestParameters as the main input
    function queryPageRecs(){
	
	$requestParams = $this->requestParams;
	if(isset($requestParams["page"])){
	    $page = $requestParams["page"];
	    if(is_numeric($page)){
		$page = round($page, 0);
		$this->page = $page;
	    }
	}
	if(isset($requestParams["recs"])){
	    $recs = $requestParams["recs"];
	    $this->set_number_records($recs);
	}
    }



    //this modifies a depricated "prop" parameter so that it is
    //a taxa parameter and can be processed nicely
    function PropToTaxaParameter(){
	$requestParams = $this->requestParams;
	if(array_key_exists("prop", $requestParams)){
	    $propsArray = $requestParams["prop"];
	    
	    unset($requestParams["prop"]);
	    $newTaxaArray = array();
	    foreach($propsArray as $varKey => $value){
		$newTaxaArray[] = $varKey."::".$value;
	    }
	    
	    if(!array_key_exists("taxa", $requestParams)){
		$requestParams["taxa"] = array();
	    }
	    
	    foreach($newTaxaArray as $newTaxon){
		$requestParams["taxa"][] = $newTaxon;
	    }
	    
	    $this->requestParams = $requestParams;
	}
    }



    //sets number of records to a managable level, defaults to 10
    function set_number_records($number_recs){
	
	if(!$number_recs){
            $number_recs = 10;
        }
        else{
            if(is_integer($number_recs+0)){
                $number_recs = abs($number_recs);
            }
            else{
                $number_recs = 10;
            }
        }
	
	if($number_recs > self::MaxsRecords){
	    $number_recs = self::MaxsRecords;
	}
	
	$this->number_recs = $number_recs;
	$this->set_offset();
	return $number_recs;
    }
    
    function set_offset(){
	$page = $this->page;
	$number_recs = $this->number_recs;
	if (is_numeric($page) && $page > 0 ) {
            $this->offset = ($page - 1) * $number_recs;
        }
	else{
	    $this->offset = 0;
	}
    }
    
    
    function set_default_context($default_context_path = false){
	//removes trailing "/"
	
	if(!$default_context_path){
	    $requestParams = $this->requestParams;
	    if(isset($requestParams["default_context_path"])){
		$default_context_path = $requestParams["default_context_path"];
	    }
	    else{
		$default_context_path = false;
	    }
	}
	
	if($default_context_path != false){
	    $this->original_default_context_path = $default_context_path;
	    if(substr($default_context_path, -1, 1) == "/"){
		$default_context_path = substr($default_context_path, 0, (strlen($default_context_path)-1));
	    }
	    
	    $default_context_path = OpenContext_FacetQuery::clean_context_path($default_context_path);
	    
	    $this->slashCount =  substr_count($default_context_path, "/"); // note:  $slashCount is used later to determine whether or not to display properties
	    $this->context_depth = "def_context_" . $this->slashCount;
	    
	    $this->default_context_path = $default_context_path;
	}
	
	return $default_context_path;
    }
    
    
    //geo tile facets from the request parameter
    function makeGeoFromRequestParam(){
	$requestParams = $this->requestParams;
	if(isset($requestParams["geotile"])){
	    $geoPath = $requestParams["geotile"];
	    if(is_numeric($geoPath)){
		$this->makeGeoTileParameters($geoPath);
	    }
	}
    }
    
    //make geo tile facet from a geopath
    function makeGeoTileParameters($geoPath){
	
	$okGeo = true;
	$geoPathSplit = str_split($geoPath);
	foreach($geoPathSplit as $geoItem){
	    if(is_numeric($geoItem)){
		if($geoItem < 0 || $geoItem > 3){
		    $okGeo = false;
		}
	    }
	    else{
		$okGeo = false;
	    }
	    
	}
    
	if($okGeo){
	    $this->geoPath = $geoPath;
	    $this->geoParam = "geo_path:" . $geoPath . "*";
	    
	    $level = 0;
	    $maxLevel = 3;
	    $facetArray = array(0=> $geoPath,
				1=> $geoPath,
				2=> $geoPath,
				3=> $geoPath
				);
	    
	    $baseTileArray = array(0,
			       1,
			       2,
			       3);
	    $level = 0;
	    $nextLength = strlen($geoPath) + self::geoLevelDeep;
	    if($nextLength >= 20){
		$nextLength = 20;
	    }
	    
	    $tileSuffixArray = $facetArray ;
	    while($level < self::geoLevelDeep){
		$newSuffixArray = array();
		foreach($baseTileArray as $baseTile){
		    foreach($tileSuffixArray as $oldSuffix){
			
			if(!in_array($oldSuffix, $newSuffixArray)){
			    $newSuffixArray[] = $oldSuffix;
			}
			
			$newTile = $oldSuffix.$baseTile;
			if(!in_array($newTile, $newSuffixArray)){
			    $newSuffixArray[] = $newTile;
			}
		    }
		}
		unset($tileSuffixArray);
		$tileSuffixArray = $newSuffixArray;
		unset($newSuffixArray);
	    $level++;   
	    }
	    
	    $geoFacetFields = array();
	    foreach($tileSuffixArray as $tile){
		if(strlen($tile) == $nextLength){
		    //only compute facets for deeper tiles, no need for shallow tiles, since these won't be interesting to map
		    $geoFacetFields[] = $tile."_geo_tile";
		}
	    }
	    
	    $this->geoFacets = $geoFacetFields;
	}//end case with valid numeric path
    }//end function
    
    
    
    //create an array of document types to search in
    function makeDocumentTypeArray(){
	
	$typeParameter = "";
	$DocumentTypes = array();
	if($this->substance){
	    $DocumentTypes[] = "substance";
	}
	if($this->spatial){
	    $DocumentTypes[] = "spatial";
	}
	if($this->image){
	    $DocumentTypes[] = "image";
	}
	if($this->person){
	    $DocumentTypes[] = "person";
	}
	if($this->project){
	    $DocumentTypes[] = "project";
	}
	if($this->document){
	    $DocumentTypes[] = "document";
	}
	if($this->table){
	    $DocumentTypes[] = "table";
	}
	if($this->site){
	    $DocumentTypes[] = "site";
	}
	if($this->media){
	    $DocumentTypes[] = "acrobat pdf";
	    $DocumentTypes[] = "external";
	    $DocumentTypes[] = "KML";
	    $DocumentTypes[] = "GIS";
	}

	return $DocumentTypes;
    }
    
    
    
    
    //sort the results. default is sort by "interest score "
    function requestSorting(){
	
	$requestParams = $this->requestParams;
	
	if(isset($requestParams['sort'])){
	    $sort = $requestParams['sort'];
	}
	else{
	    $sort = $this->defaultSort;
	}

	if(!$sort){
	    $sortOutput = "interest_score desc";
	}//use default sort
	else{
	    
	    if(stristr($sort, ",")){
		$sortArray = explode(",", $sort);
	    }
	    else{
		$sortArray = array(0=>$sort);
	    }
	    
	    $sortOutput = "";
	    $firstLoop = true;
	    foreach($sortArray as $sortTypeRaw){
		
		$ordering = "asc";
		if(stristr($sortTypeRaw, ":")){
		    $sortTypeArray = explode(":", $sortTypeRaw);
		    $sortType = $sortTypeArray[0];
		    if($sortTypeArray[1] == "desc"){
			$ordering = "desc";
		    }
		}
		else{
		    $sortType = $sortTypeRaw;
		}
		
		if($sortType == "label"){
		    $actSort = "label_sort ".$ordering.", item_label ".$ordering;
		}
		elseif($sortType == "cat"){
		    $actSort = "item_class ".$ordering; 
		}
		elseif($sortType == "context"){
		    $actSort = "default_context_path ".$ordering; 
		}
		elseif($sortType == "proj"){
		    $actSort = "project_name ".$ordering;
		}
		else{
		    $actSort = "interest_score desc";
		}
		
		if($firstLoop){
		    $sortOutput = $actSort;
		}
		else{
		    $sortOutput .= ", ".$actSort;
		}
		
		$firstLoop = false;
	    }
	    
	}//end case with sorting requested


	return $sortOutput;
    }
    
    
    
    //depending on the type of query, add some new facet fields
    function addFacetFields($param_array){
	
	if($this->spatial && !$this->allDocs){
	    $param_array["facet.field"][] = "time_span";
	    $param_array["facet.field"][] = "geo_point";
	}
	if($this->image && !$this->allDocs){
	    $param_array["facet.field"][] = "time_span";
	    $param_array["facet.field"][] = "geo_point";
	}
	if($this->media && !$this->allDocs){
	    $param_array["facet.field"][] = "time_span";
	    $param_array["facet.field"][] = "geo_point";
	}
	if($this->document && !$this->allDocs){
	    $param_array["facet.field"][] = "time_span";
	    $param_array["facet.field"][] = "geo_point";
	}
	if($this->project && !$this->allDocs){
	    $param_array["facet.field"][] = "time_span";
	    $param_array["facet.field"][] = "geo_point";
	    $param_array["facet.field"][] = "subject";
	    $param_array["facet.field"][] = "coverage";
	}
	if($this->allDocs){
	    $param_array["facet.field"][] = "item_type";
	}
	
	
	return $param_array;
    }
    
    
    function addResultFields($doc, $actDocOutput){
	
	if($this->allDocs){
	    $actDocOutput["item_label"] = $doc->item_label;
	    $actDocOutput["item_type"] = $doc->item_type;
	    $actDocOutput["pub_date"] = $doc->pub_date;
	    $actDocOutput["update"] = $doc->pub_date;
	    
	    if(!empty($doc->creator)){
		$actDocOutput["creator"] = $doc->creator;
	    }
	    if(!empty($doc->contributor)){
		$actDocOutput["contributor"] = $doc->contributor;
	    }
	
	}
	if($this->substance){
	    $actDocOutput["item_label"] = $doc->item_label;
	    $actDocOutput["pub_date"] = $doc->pub_date;
	    $actDocOutput["update"] = $doc->pub_date;
	}
	
	if($this->spatial || $this->image || $this->document || $this->project){
	    if(!empty($doc->time_span)){
		$actDocOutput["time_span"] = $doc->time_span;
	    }
	    if(!empty($doc->geo_lat)){
		$actDocOutput["geo_lat"] = $doc->geo_lat;
	    }
	    if(!empty($doc->geo_long)){
		$actDocOutput["geo_long"] = $doc->geo_long;
	    }  
	}
	
	return $actDocOutput;
    }
    
    
    
    function checkTaxaPeopleFacets(){
	$requestParams = $this->requestParams;
	$slashCount = $this->slashCount;
	$context_depth = $this->context_depth;
	
	if($slashCount >= 3){
	    $this->showTaxaFacets = true;
	    $this->showPeopleFacets = true;
	}
	if(isset($requestParams["proj"]) && isset($requestParams["cat"])){
	    $this->showTaxaFacets = true;
	    $this->showPeopleFacets = true;
	}
	if($slashCount >= 2 && isset($requestParams["cat"])){
	    $this->showTaxaFacets = true;
	    $this->showPeopleFacets = true;
	}
    }
    
    
    
    
    //composes query for SOLR
    function buildSolrQuery(){
	
	$this->queryPageRecs(); //set page and offset parameters for query
	$this->set_offset();
	$this->set_default_context(); //set default context path
	$this->checkTaxaPeopleFacets(); //check to see if we need to get taxa and people facets
	$this->makeGeoFromRequestParam(); //make geo parameters, if needed	
	
	$requestParams = $this->requestParams;
	$slashCount = $this->slashCount;
	$context_depth = $this->context_depth;
	
	$extendedFacets = OpenContext_FacetQuery::unfold_deep_parameters($requestParams, $slashCount);
	
	
	$DocumentTypes = $this->makeDocumentTypeArray();
	
	$param_array = array();
        $param_array = OpenContext_FacetQuery::build_simple_parameters($requestParams, $DocumentTypes);
	$param_array = OpenContext_FacetQuery::build_complex_parameters($requestParams, $param_array, $extendedFacets, $context_depth, false);
	
	
	
	$param_array['sort'] = $this->requestSorting();
	if($this->allDocs){
	    unset($param_array['sort']); //sort by relevancy, the default
	}
	
	$param_array = $this->addFacetFields($param_array);
	if($this->showTaxaFacets){
	    $param_array["facet.field"][] = "top_taxon";
	}
	if($this->showPeopleFacets){
	    $param_array["facet.field"][] = "person_link";
	}
	
	$default_context_path = $this->default_context_path;
	if (!$default_context_path) {
            $query = "[* TO *]";
        // otherwise, query for the default context path.
        } else {
            $query = "default_context_path:" . $default_context_path . "*";
        }    
        
	$contextArray = Opencontext_FacetQuery::defaultContextORparser("default_context_path", $this->original_default_context_path);
	$query = $contextArray["query"];
	
	if($this->geoParam != false){
	    $query = "(".$query.") && (".$this->geoParam.")";
	    if(is_array($this->geoFacets)){
		foreach($this->geoFacets as $geoFacet){
		     $param_array["facet.field"][] = $geoFacet;
		}
	    }
	}
	
	
	$this->param_array = $param_array;
	$this->query = $query;
	
	//echo print_r($param_array);
	
    }
    
    
    function execute_search(){
	/*
	echo "<br/>Query: ".$this->query;
	echo "<br/>".$this->number_recs;
	echo "<br/>".var_dump($this->param_array);
	*/
	
	$solr = new Apache_Solr_Service('localhost', 8983, '/solr');
	if ($solr->ping()) {
	    try {
	
		$response = $solr->search($this->query,
					$this->offset,
					$this->number_recs,
					$this->param_array);
                
		$this->queryString = $solr->queryString;
		$docs_array = array();
		
		foreach (($response->response->docs) as $doc) {
		    
		    $actDocOutput = array("uuid" => $doc->uuid);
		    $actDocOutput = $this->addResultFields($doc, $actDocOutput);
		    
		    $docs_array[] = $actDocOutput ;
		}
		
		$rawResponse = Zend_Json::decode($response->getRawResponse());
		$reponse = $rawResponse['response'];
		$numFound = $reponse['numFound'];
		$this->numFound = $numFound;
		$this->documentsArray =  $docs_array;
		$this->facets =  $rawResponse['facet_counts'];
		
	    } catch (Exception $e) {
		echo "Problem:". $e->getMessage(), "\n";
	    }

	} else {
	    die("unable to connect to the solr server. exiting...");
	}
	
	
	$this->makeAltLinks(); //now that search results are found, make some links
    }
    
    
    
    function makeAltLinks(){
	
	$requestParams = $this->requestParams;
	$numFound = $this->numFound;
	$page = $this->page;
	$numRecs = $this->number_recs;
	$lastPage = intval($numFound/$numRecs);
	if($lastPage * $numRecs < $numFound){
	    $lastPage++;
	}
	
	$this->lastPage = $lastPage;
    
	$host = OpenContext_OCConfig::get_host_config();
	$baseController = "/".$requestParams["controller"]."/";
	
	if(!$page){
	    $page = 1;
	    $this->page = 1;
	}
	
	unset($requestParams["controller"]);
	unset($requestParams["action"]);
	unset($requestParams["module"]);
	if(isset($requestParams["page"])){
	    unset($requestParams["page"]);
	}
	
	$link = $host.$baseController;
	$facetLink = $host.$baseController."facets/";
	$openSearchLink = $host.$baseController."search/";
	
	if(isset($requestParams["default_context_path"])){
	    $contexts = OpenContext_FacetOutput::url_encode_noDelim($requestParams["default_context_path"], "/");
	    $link .= $contexts;
	    $facetLink .= $contexts;
	    $openSearchLink .= $contexts;
	    unset($requestParams["default_context_path"]);
	}

	$linksRootArray = array("xhtml" => $link,
			    "results_atom" => $link.".atom",
			    "results_json" => $link.".json",
			    "facets_atom" => $facetLink.".atom",
			    "facets_json" => $facetLink.".atom",
			    "opensearch" => $openSearchLink.".xml"
			    );

    
	$this->firstPage_XHTML= $this->add_url_parameters($linksRootArray["xhtml"], $requestParams);
	$this->firstPage_JSON= $this->add_url_parameters($linksRootArray["results_json"], $requestParams);
	$this->firstPage_Atom= $this->add_url_parameters($linksRootArray["results_atom"], $requestParams);
	
	if($page > 1){
	    $this->prevPage_XHTML= $this->add_url_parameters($linksRootArray["xhtml"], $requestParams, "page", $page -1 );
	    $this->prevPage_JSON= $this->add_url_parameters($linksRootArray["results_json"], $requestParams, "page", $page -1 );
	    $this->prevPage_Atom= $this->add_url_parameters($linksRootArray["results_atom"], $requestParams, "page", $page -1 );
	    
	    //current page
	    $this->currentXHTML = $this->add_url_parameters($linksRootArray["xhtml"], $requestParams, "page", $page );
	    $this->currentJSON = $this->add_url_parameters($linksRootArray["results_json"], $requestParams, "page", $page );
	    $this->currentAtom = $this->add_url_parameters($linksRootArray["results_atom"], $requestParams, "page", $page );
	}
	else{
	    $this->prevPage_XHTML= false;
	    $this->prevPage_JSON= false;
	    $this->prevPage_Atom= false;
	    
	    //current page (no parameter)
	    $this->currentXHTML = $this->add_url_parameters($linksRootArray["xhtml"], $requestParams);
	    $this->currentJSON = $this->add_url_parameters($linksRootArray["results_json"], $requestParams);
	    $this->currentAtom = $this->add_url_parameters($linksRootArray["results_atom"], $requestParams);
	}
	
	if($page < $lastPage){
	    $this->nextPage_XHTML = $this->add_url_parameters($linksRootArray["xhtml"], $requestParams, "page", $page + 1 );
	    $this->nextPage_JSON = $this->add_url_parameters($linksRootArray["results_json"], $requestParams, "page", $page + 1 );
	    $this->nextPage_Atom =$this->add_url_parameters($linksRootArray["results_atom"], $requestParams, "page", $page + 1 );
	}
	else{
	    $this->nextPage_XHTML = false;
	    $this->nextPage_JSON = false;
	    $this->nextPage_Atom = false;
	}
	
	$this->lastPage_XHTML = $this->add_url_parameters($linksRootArray["xhtml"], $requestParams, "page", $lastPage );
	$this->lastPage_JSON = $this->add_url_parameters($linksRootArray["results_json"], $requestParams, "page", $lastPage );
	$this->lastPage_Atom =$this->add_url_parameters($linksRootArray["results_atom"], $requestParams, "page", $lastPage );
	
	//these are links to facets only
	
	if(isset($requestParams["recs"])){
	    unset($requestParams["recs"]);
	}
	
	$this->facetURI_Atom= $this->add_url_parameters($linksRootArray["facets_atom"], $requestParams );
	$this->facetURI_JSON= $this->add_url_parameters($linksRootArray["facets_json"], $requestParams );
	$this->openSearchURI = $this->add_url_parameters($linksRootArray["opensearch"], $requestParams );
    }
    
    
    function add_url_parameters($rootURL, $requestParams, $newParam = false, $newParmValue = false){
	
	if($newParam != false){
	    $requestParams[$newParam] = $newParmValue;
	}
	
	$parameterArgument = '?';
	foreach($requestParams as $parameter => $value){
	    
	    if(is_array($value)){
		foreach($value as $paramKey => $paramValue){
		    if(is_numeric($paramKey)){
			$paramKey = "";
		    }
		    else{
			$paramKey = urlencode($paramKey);
		    }
		    if(($paramValue != null)||(strlen($paramValue)>0)){
			$rootURL .= $parameterArgument.$parameter."%5B".$paramKey."%5D=".urlencode($paramValue);	
		    }
		    else{
			$rootURL .= $parameterArgument.$parameter."%5B".$paramKey."%5D";
		    }
		    $parameterArgument = '&';
		}
	    }
	    else{
		if(($value != null)||(strlen($value)>0)){
		    $rootURL .= $parameterArgument.$parameter."=".urlencode($value);
		}
	    }
	    $parameterArgument = '&';   
	}//end loop through parameters
	
	return $rootURL;
    }
    
    
    
    
    
    function makeAtomFeed(){
	
	// the number of results per page. Note: the actual number of results per page is set in the controller as an argument to the solr query.
	// the resulstPerPage variable helps us calculate the link and opensearch elements
	$resultsPerPage = $this->number_recs;
	$requestParams =$this->requestParams;
	$host = OpenContext_OCConfig::get_host_config();
	$base_hostname = OpenContext_OCConfig::get_host_config(false);
	$baseURI = OpenContext_OCConfig::get_host_config();
	$requestURI = $this->requestURI;
	$request_array = explode('?', $requestURI, 2);
	
	$atomFullDoc = new DOMDocument("1.0", "utf-8");
		
	$root = $atomFullDoc->createElementNS("http://www.w3.org/2005/Atom", "feed");
		
	// add newlines and indent the output - this is at least useful for debugging and making the output easier to read
	$atomFullDoc->formatOutput = true;
		
	$root->setAttribute("xmlns:georss", "http://www.georss.org/georss");
	$root->setAttribute("xmlns:gml", "http://www.opengis.net/gml");
	$root->setAttribute("xmlns:dc", "http://purl.org/dc/elements/1.1/");
	$root->setAttribute("xmlns:opensearch", "http://a9.com/-/spec/opensearch/1.1/");
	//xmlns:opensearch="http://a9.com/-/spec/opensearch/1.1/"
	//$root->setAttribute("xmlns:xhtml", "http://www.w3.org/1999/xhtml");
		
	$atomFullDoc->appendChild($root);
	
	
	// Feed Title 
	$feedTitle = $atomFullDoc->createElement("title");
	$feedTitleText = $atomFullDoc->createTextNode("Open Context Query Results");
	$feedTitle->appendChild($feedTitleText);
	$root->appendChild($feedTitle);
	
	
	// Prepare the feed's subtitle
	$offset = $this->offset;
	$numFound = $this->numFound;
	
	/* for testing
	if (isset($offset)) {
	    echo 'offset: ';
	    echo $offset;
	    echo '<br/>';  
	}
	*/
	
	
	// Display the number of items found and handle paging. 
	$first = $offset + 1;
	$last = $offset + $resultsPerPage;
	
	// make sure the last page, which will usually contain fewer than 10 items, displays the correct number of items.
	if ($numFound < $last) {
	   $subTitleText = 'items ' . $first . ' to ' . $numFound . ' out of ' . $numFound . ' items'; 
	} else {
	    $subTitleText = 'items ' . $first . ' to ' . $last . ' out of ' . $numFound . ' items';
	}
	//echo $subTitleText;
	/*
	$filterArray = OpenContext_FacetOutput::active_filter_object($requestParams, $request_array, $host);
	if(count($filterArray)>0){
	    $subTitleText .= " FILTERED BY- ";
	    $ff=0;
	    foreach($filterArray as $filterItem){
		if($ff>0){
		    $subTitleText .= ", ";    
		}
		$subTitleText .= "'".$filterItem["filter"]."': '".$filterItem["value"]."'";
		$ff++;
	    }
	}
	*/
	
	$feedSubtitle = $atomFullDoc->createElement("subtitle");
	$feedSubtitleText = $atomFullDoc->createTextNode($subTitleText);
	$feedSubtitle->appendChild($feedSubtitleText);
	$root->appendChild($feedSubtitle);
	
	
	// Feed updated element (as opposed to the entry updated element)
	$feedUpdated = $atomFullDoc->createElement("updated");
	$updatedTime = OpenContext_OCConfig::last_update();
	// Retrieve the current date and time. Format it in RFC 3339 format. Store it in a text node 
	$feedUpdatedText = $atomFullDoc->createTextNode(date("Y-m-d\TH:i:s\-07:00", strtotime($updatedTime)));
	// Append the text node the updated element
	$feedUpdated->appendChild($feedUpdatedText);
	// Append the updated node to the root element
	$root->appendChild($feedUpdated);
	
	$totalResults = $atomFullDoc->createElement('opensearch:totalResults');
	$totalResultsText = $atomFullDoc->createTextNode($numFound);
	$totalResults->appendChild($totalResultsText);
	$root->appendChild($totalResults);
	
	$startIndex = $atomFullDoc->createElement('opensearch:startIndex');
	$startIndexText = $atomFullDoc->createTextNode($first);
	$startIndex->appendChild($startIndexText);
	$root->appendChild($startIndex);
	
	$itemsPerPage = $atomFullDoc->createElement('opensearch:itemsPerPage');
	$itemsPerPageText = $atomFullDoc->createTextNode($resultsPerPage);
	$itemsPerPage->appendChild($itemsPerPageText);
	$root->appendChild($itemsPerPage);
	
	// prepare link element
	$requestURI = $this->requestURI;
	
	$linkURI = $baseURI . $requestURI;
	$linkURI = str_replace("[", "%5B", $linkURI);
	$linkURI = str_replace("]", "%5D", $linkURI);
	
	// feed (self) link element
	$feedLink = $atomFullDoc->createElement("link");
	$feedLink->setAttribute("rel", "self");
	$feedLink->setAttribute("href", $linkURI);
	$root->appendChild($feedLink);
	
	// feed license link element
	$feedLink = $atomFullDoc->createElement("link");
	$feedLink->setAttribute("rel", "license");
	$feedLink->setAttribute("type", "text/html");
	$feedLink->setAttribute("href", "http://creativecommons.org/licenses/by/3.0/");
	$root->appendChild($feedLink);
	
	// feed (facets) link element
	$facetURI = $linkURI;
	$facetURI = str_replace("/sets","/sets/facets", $linkURI);
	//$facetURI = str_replace("facets/.atom", "facets.atom", $facetURI);
	$feedLink = $atomFullDoc->createElement("link");
	$feedLink->setAttribute("rel", "http://opencontext.org/about/services#atom-facets");
	$feedLink->setAttribute("type", "application/atom+xml");
	$feedLink->setAttribute("href", $facetURI);
	$root->appendChild($feedLink);
	
	
	// feed (HTML representation) link element
	$feedHTML_URI = str_replace(".atom","", $linkURI);
	//$facetURI = str_replace("facets/.atom", "facets.atom", $facetURI);
	$feedLink = $atomFullDoc->createElement("link");
	$feedLink->setAttribute("rel", "alternate");
	$feedLink->setAttribute("type", "application/xhtml+xml");
	$feedLink->setAttribute("href", $feedHTML_URI);
	$root->appendChild($feedLink);
	
	
	
	//prepare the first link
	$feedFirstLink = $atomFullDoc->createElement("link");
	$feedFirstLink->setAttribute("rel", "first");
	// remove any existing page number
	$firstURI = preg_replace('/(\?|&)page=\d*/', '', $linkURI);
	// append a '(?|&)page=1'
	if (strpos($firstURI,'?')) {
	    $firstURI = $firstURI . '&page=1';
	} else {
	    $firstURI = $firstURI . '?page=1';
	}
	
	$feedFirstLink->setAttribute("href", $firstURI);
	$root->appendChild($feedFirstLink);
	//echo $firstURI;
	//exit;
	
	
	
	// create last link
	$feedLastLink = $atomFullDoc->createElement('link');
	$feedLastLink->setAttribute('rel', 'last');
	// remove any page number from the URI so we can replace it with the last page.
	$lastURI = preg_replace('/(\?|&)page=\d*/', '', $linkURI);
	
	// calculate the number of pages
	$lastPage = intval($numFound/$resultsPerPage);
	// if there's a remainder, add a page. For example, 13 items should result in two pages.
	if ($numFound % $resultsPerPage) {
	    $lastPage = $lastPage + 1;
	}
	
	if (strpos($lastURI,'?')) {
	    if($lastPage>0){
	      $lastURI = $lastURI . '&page=' . $lastPage;
	    }
	} else {
	   if($lastPage>0){
	      $lastURI = $lastURI . '?page=' . $lastPage;
	   }
	}
	
	$feedLastLink->setAttribute('href', $lastURI);
	
	$root->appendChild($feedLastLink);
	
	//echo $lastURI;
	
	//exit;
	
	// get the page number from the controller; we'll use this to create the 'previous' and 'next' links
	$page = $this->page;
	
	// if there is no page number in the URI, we're on page 1, so set page number accrordingly
	if (!$page) {
	    $page = 1;
	    // update the linkURI so the previous and next links will be correct
	    if (strpos($linkURI,'?')) {
		$linkURI = $linkURI . '&page=1';
	    } else {
		$linkURI = $linkURI . '?page=1';
	    }
	}
	
	// create previous link
	
	if ($page > 1) {
	    $previous = $page - 1;
	    $previousURI = preg_replace('/page=\d*/', 'page=' . $previous , $linkURI);
	    //echo $previousURI;
	    $previousLink = $atomFullDoc->createElement('link');
	    $previousLink->setAttribute('rel', 'previous');
	    $previousLink->setAttribute('href', $previousURI);
	    $root->appendChild($previousLink);    
	}
	
	// create next link
	//get page number and add 1; check to see that page + 1 is not greater than $lastPage
	if ($page < $lastPage) {
	    $next = $page + 1;
	    $nextURI = preg_replace('/page=\d*/', 'page=' . $next , $linkURI);
	    $nextLink = $atomFullDoc->createElement('link');
	    $nextLink->setAttribute('rel', 'next');
	    $nextLink->setAttribute('href', $nextURI);
	    $root->appendChild($nextLink);
	}
	
	$feedId = $atomFullDoc->createElement("id");
	$feedIdText = $atomFullDoc->createTextNode($linkURI);
	$feedId->appendChild($feedIdText);
	$root->appendChild($feedId);
	
	
	if($numFound>0){
	   $docs_array = $this->docs_array;
	   
	   $contentFragment = $atomFullDoc->createDocumentFragment();
	   
	   if ($docs_array) {
	      $idArray = array();
	      foreach ($docs_array as $docArray) {
		 $idArray[] = $docArray["uuid"];
	      }
	      
	      $itemEntries = new SubjectsEntries;
	      $idEntryArray = $itemEntries->getByIDArray($idArray);
	      
	      //echo $itemEntries->sql;
	      
	      foreach($idEntryArray as $itemUUID => $atomEntry){
		 
		 if(strlen($atomEntry)<10){
		    $spaceItem = New Subject;
		    $spaceItem->getByID($itemUUID);
		    if(strlen($spaceItem->atomEntry)<10){
		       $spaceItem->solr_getArchaeoML();
		       $fixed_ArchaeoML = $spaceItem->archaeoML_update($spaceItem->archaeoML);
		       $spaceItem->archaeoML_update($fixed_ArchaeoML);
		       $spaceItem->kml_in_Atom = true; // it doesn't validate, but it is really useful
		       $fullAtom = $spaceItem->DOM_spatialAtomCreate($spaceItem->newArchaeoML);
		       $spaceItem->update_atom_entry();
		       
		       //echo var_dump($spaceItem);
		    }
		    //echo var_dump($spaceItem);
		    $doc = str_replace('<?xml version="1.0" encoding="utf-8"?>', "", $spaceItem->atomEntry);
		    $contentFragment->appendXML($doc);  // $atom_content from short atom entry
		    unset($spaceItem);
		 }
		 else{
		    $doc = str_replace('<?xml version="1.0" encoding="utf-8"?>', "", $atomEntry);
		    $contentFragment->appendXML($doc);  // $atom_content from short atom entry
		 }
		 
		 unset($itemEntries);
		 
	       }
		   
	     
	       $root->appendChild($contentFragment);
	       
	   }
	}
	
	$resultString = $atomFullDoc->saveXML();
	
	// Note: simpleXML will add a 'default:' prefix to the XHTML content.  We don't want this, so remove it.
	$resultString = str_replace('default:', '' , $resultString);
	
	return $resultString;
    }//end function
    

    function space_atom_to_object($atom_string){
		
	$host = OpenContext_OCConfig::get_host_config();	

	@$atomXML = simplexml_load_string($atom_string);

	if($atomXML){

		$atomXML->registerXPathNamespace("default", "http://www.w3.org/2005/Atom");
		$atomXML->registerXPathNamespace("opensearch", "http://a9.com/-/spec/opensearch/1.1/");
		
		$resultCount = $atomXML->xpath("/default:feed/opensearch:totalResults");
		$resultCount = $resultCount[0]+0;
		
		$resultSubTitle = OpenContext_ResultAtom::get_xml_string($atomXML, "/default:feed/default:subtitle");
		$first_PageURI = OpenContext_ResultAtom::get_xml_string($atomXML, "/default:feed/default:link[@rel='first']/@href");
		$last_PageURI = OpenContext_ResultAtom::get_xml_string($atomXML, "/default:feed/default:link[@rel='last']/@href");
		$next_PageURI = OpenContext_ResultAtom::get_xml_string($atomXML, "/default:feed/default:link[@rel='next']/@href");
		$prev_PageURI = OpenContext_ResultAtom::get_xml_string($atomXML, "/default:feed/default:link[@rel='previous']/@href");
		
		/*
		$last_PageURI = htmlentities($last_PageURI);
		$next_PageURI = htmlentities($next_PageURI);
		$prev_PageURI = htmlentities($prev_PageURI);
		*/
		
		if((!$first_PageURI)||($first_PageURI == $atomURI)||((substr_count($atomURI,"page=")<1))){
			
		}//case with deactivated first link
		else{
			$first_PageURI = str_replace(".atom", "", $first_PageURI);
			$first_PageURI = str_replace("sets/?page=1&", "sets/?", $first_PageURI);
			$first_PageURI = str_replace("?page=1", "", $first_PageURI);
			//$first_PageURI = htmlentities($first_PageURI);
		}//case with active first link
		
		if((!$prev_PageURI)||($prev_PageURI == $atomURI)||((substr_count($atomURI,"page=")<1))){
			
		}//case with deactivated previous link
		else{
			$prev_PageURI = str_replace(".atom", "", $prev_PageURI);
		}//case with active previous link
		
		if((!$next_PageURI)||($next_PageURI == $atomURI)){
			
		}//case with deactivated previous link
		else{
			$next_PageURI = str_replace(".atom", "", $next_PageURI);
		}//case with active previous link

		if((!$last_PageURI)||($last_PageURI == $atomURI)){

		}//case with deactivated previous link
		else{
			$last_PageURI = str_replace(".atom", "", $last_PageURI);
		}//case with active previous link		
		
		
		$eee = 0;
		foreach ($atomXML->xpath("/default:feed/default:entry") as $all_entry) {
			
			$all_entry->registerXPathNamespace("default", "http://www.w3.org/2005/Atom");
			$entryID = $all_entry->id;
			$entryURI_all[$eee] = $entryID[0]."";
			$eee++;
		}//end loop through all entries
		
		$iii = 0;
		if($eee>0){
			$allResults = array();
			foreach ($atomXML->xpath("/default:feed/default:entry") as $AtomEntry) {
				$AtomEntry->registerXPathNamespace("default", "http://www.w3.org/2005/Atom");
				$AtomEntry->registerXPathNamespace("xhtml", "http://www.w3.org/1999/xhtml");
				$AtomEntry->registerXPathNamespace("georss", OpenContext_OCConfig::get_namespace("georss"));
				$AtomEntry->registerXPathNamespace("kml", OpenContext_OCConfig::get_namespace("kml"));
				
				$geoLat = false;
				$geoLon = false;
				foreach($AtomEntry->xpath("./georss:point") as $geoNode){
					$geoString = $geoNode."";
					
					if($geoString == "30 35"){
						$geoString = "30.3287 35.4421"; //petra rounding fix	
					}
					
					$geoArray = explode(" ", $geoString);
					$geoLat = $geoArray[0]+0;
					$geoLon = $geoArray[1]+0;
				}
				
				
				
				
				$kmlBegin = false;
				$kmlEnd = false;
				foreach($AtomEntry->xpath("./kml:TimeSpan/kml:begin") as $beginNode){
					$kmlBegin = ($beginNode."") + 0;
				}
				foreach($AtomEntry->xpath("./kml:TimeSpan/kml:end") as $endNode){
					$kmlEnd = ($endNode."") + 0;
				}
				
				$entryURI = $entryURI_all[$iii];
				if(!$entryURI){
					foreach($AtomEntry->xpath("./default:id") as $idNode){
						$entryURI = $idNode."";
					}
				}
				
				
				foreach ($AtomEntry->xpath("./default:content") as $entry) {
				
					
					$entry->registerXPathNamespace("default", "http://www.w3.org/2005/Atom");
					$entry->registerXPathNamespace("xhtml", "http://www.w3.org/1999/xhtml");
					
					foreach ($entry->xpath("./xhtml:div") AS $act_content){
						$act_content->registerXPathNamespace("xhtml", "http://www.w3.org/1999/xhtml");
						foreach($act_content->xpath(".//xhtml:div[@class='class_name']") as $act_class){
							$itemCat = $act_class."";
						}
						foreach($act_content->xpath(".//xhtml:div[@class='project_name']") as $act_proj){
							$itemProject = $act_proj."";
						}
						foreach($act_content->xpath(".//xhtml:div[@class='class_icon']/xhtml:img/@src") as $act_icon){
							$itemIcon = $act_icon."";
						}
						foreach($act_content->xpath(".//xhtml:div[@class='item_label']") as $act_label){
							$itemLabel = $act_label."";
						}
						foreach($act_content->xpath(".//xhtml:div[@class='item_label']") as $act_label){
							$itemLabel = $act_label."";
						}
						$itemThumb = false;
						if($act_content->xpath(".//xhtml:div[@class='item_thumb']//xhtml:img/@src")){
							foreach($act_content->xpath(".//xhtml:div[@class='item_thumb']//xhtml:img/@src") as $act_thumb){
								$itemThumb = $act_thumb."";
							}
						}
						if(!$itemThumb){
							$itemThumb = $itemIcon;
						}
						
					}
						
					
					
					
					$resultItem = array("uri"=>$entryURI,
							    "category"=>$itemCat,
							    "catIcon"=>$itemIcon,
							    "project"=>$itemProject,
							    "label"=>$itemLabel,
							    "thumbIcon"=>$itemThumb,
							    "geoTime" => array("geoLat" => $geoLat,
									       "geoLong" => $geoLon,
									       "timeBegin" => $kmlBegin,
									       "timeEnd" => $kmlEnd
									       ));
					
					
				
				}//end loop through entries
		
			$allResults[] = $resultItem;
			$iii++;
			}//Atom Entries
		}//end case with entries
		
	}//end case with atom content
	
	
	$resultObject = array("resultCount"=>$resultCount,
			      "firstURI"=>$first_PageURI,
			      "lastURI"=>$last_PageURI,
			      "prevURI"=>$prev_PageURI,
			      "nextURI"=>$next_PageURI,
			      "items"=>$allResults
			      );
	
	return $resultObject;
    }//end function atom_to_object



}
