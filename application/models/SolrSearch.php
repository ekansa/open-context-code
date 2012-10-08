<?php


//this class interacts with solr to run searches
class SolrSearch{
    
    public $page;        //page of result request
    public $number_recs; //number of results per page
    public $offset; // page offset value
    public $defaultSort; //default sort value
    public $error;
    public $sortType; //human readable sorting
    public $facetSort; //sort for facets (solr default is count)
    
    public $solrDown; //solr status
    
    public $substance; //do for substances (boolean default = false)
    public $allDocs; //do all documents (boolean default = false)
    public $spatial; //boolean, search spatial docs (default = false)
    public $image; //boolean, search image docs (default = false)
    public $media; //boolean, search media docs (default = false)
	public $video; //boolean, do video search (default = false)
    public $person; //boolean, search person docs (default = false)
    public $project; //boolean, search project docs (default = false)
    public $document; //boolean, search document docs (default = false)
    public $table; //boolean, search table docs (default = false)
    public $site; //boolean, search site documentation docs (default = false)
    
	public $reconcile; //are we attempting entity reconcilation? (default = false)
    public $requestURI; //request URI
    public $requestParams; // array of the request parameters and values
    
    //these are for paging through result sets
    
    public $currentXHTML;
    public $currentJSON;
    public $currentAtom;
    public $currentKML;
    
    public $firstPage_XHTML;
    public $firstPage_JSON;
    public $firstPage_Atom;
    public $firstPage_KML;
    public $prevPage_XHTML;
    public $prevPage_JSON;
    public $prevPage_Atom;
    public $prevPage_KML;
    public $nextPage_XHTML;
    public $nextPage_JSON;
    public $nextPage_Atom;
    public $nextPage_KML;
    public $lastPage_XHTML;
    public $lastPage_JSON;
    public $lastPage_Atom;
    public $lastPage_KML;
    
    //these are links to facets only
    public $facetURI_Atom;
    public $facet_KML;
    public $facetURI_JSON;
    public $openSearchURI;
    
    public $original_default_context_path;  //default context path, never modified
    public $default_context_path; // default context path, gets modified
    public $slashCount; //number of slashes or depth of context
    public $context_depth; //used in query
   
    public $showTaxaFacets; //boolean do taxonomy facets
    public $showPeopleFacets; //boolean show people facets
   
   
    public $geoPath; // path for geo tile
    public $geoParam; // query parameter for geoTile
    public $geoFacets; // facets to display for geotile
   
    public $param_array; //SOLR query parameter array 
    public $query; //SOLR query
    public $queryString; //raw solr query string
    public $rawDocsArray; //expose full raw docs
   
    public $lastUpdate; //last update date
    public $lastPublished; //last published
    public $facets; //solr facet counts found in query
    public $geoTileFacets; //solr output of geotile facets
    public $documentsArray; //solr records found in query
    public $numFound; //total number of records found in Solr search
    public $lastPage;
    
    public $docTypeArray = array("spatial" => array("href" => "/subjects/"),
				 "project" => array("href" => "/projects/"),
				 "person" => array("href" => "/persons/"),
				 "media" => array("href" => "/media/"),
				 "image" => array("href" => "/media/"),
				 "video" => array("href" => "/media/"),
				 "external" => array("href" => "/media/"),
				 "acrobat pdf" => array("href" => "/media/"),
				 "KML" => array("href" => "/media/"),
				 "kml" => array("href" => "/media/"),
				 "document" => array("href" => "/documents/"),
				 "table" => array("href" => "/tables/"),
				 "site" => array("href" => false)
				 );



    const MaxsRecords = 150;
    const geoLevelDeep = 3; //how many levels in geo tiles down will we go?
    
    //initialize the search, set search document types to false
    function initialize(){
	
		$this->number_recs = 10; //default number of records to show
		$this->defaultSort = "label";
		$this->substance = false;
		$this->context_depth = "def_context_0"; //default setting
		$this->lastUpdate = false;
		$this->lastPublished = false;
		$this->solrDown = false;
		$this->facetSort = false;
		
		$this->geoTileFacets = false;
		$this->rawDocsArray = false;
		$this->allDocs = false;
		$this->spatial = false; //boolean, search spatial docs (default = false)
		$this->image = false; //boolean, search image docs (default = false)
		$this->media = false; //boolean, search media docs (default = false)
		$this->video = false; //boolean, search video docs (default = false)
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
		
		$this->reconcile = false;
		
		$this->currentXHTML = false;
		$this->currentJSON = false;
		$this->currentAtom = false;
		$this->currentKML = false;
		$this->firstPage_XHTML= false;
		$this->firstPage_JSON= false;
		$this->firstPage_Atom= false;
		$this->firstPage_KML= false;
		$this->prevPage_XHTML= false;
		$this->prevPage_JSON= false;
		$this->prevPage_Atom= false;
		$this->prevPage_KML= false;
		$this->nextPage_XHTML= false;
		$this->nextPage_JSON= false;
		$this->nextPage_Atom= false;
		$this->nextPage_KML= false;
		$this->lastPage_XHTML= false;
		$this->lastPage_JSON= false;
		$this->lastPage_Atom= false;
		$this->lastPage_KML= false;
		
		//these are links to facets only
		$this->openSearchURI = false;
		$this->facetURI_Atom= false;
		$this->facetURI_JSON= false;
		$this->facetURI_KML= false;
	
    }//end function


    //a general text search is requested, look in all document types
    function doAllSearch(){
		
		//to avoid an error on sorting
		$requestParams = $this->requestParams;
		if(!isset($requestParams["q"]) && !isset($requestParams["sort"]) ){
		  $requestParams["sort"] = "label";
		  $this->requestParams = $requestParams;
		}
		
		$this->allDocs = true;
		$this->spatial = true; //boolean, search spatial docs (default = false)
		$this->image = true; //boolean, search image docs (default = false)
		$this->media = true; //boolean, search media docs (default = false)
		$this->video = true; //boolean, search media docs (default = false)
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
            if(strlen($value)>1){
                $newTaxaArray[] = $varKey."::".$value;
            }
            else{
                $newTaxaArray[] = $varKey;
            }
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
			$this->context_depth = "def_context_0";
		}
		
		if($default_context_path != false && strlen($default_context_path)>=1){
			$this->original_default_context_path = $default_context_path;
			if(substr($default_context_path, -1, 1) == "/"){
			$default_context_path = substr($default_context_path, 0, (strlen($default_context_path)-1));
			}
			
			$default_context_path = OpenContext_FacetQuery::clean_context_path($default_context_path);
			
			$this->slashCount =  substr_count($default_context_path, "/"); // note:  $slashCount is used later to determine whether or not to display properties
			
			/*
			if(stristr($default_context_path, "/")){
			$this->slashCount =  count(explode("/", $default_context_path));
			}
			else{
			$this->slashCount = 1;
			}
			*/
			
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
		//validate numbers, make sure they are less than or = 3
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
		$documentTypes = array();
		if($this->substance){
			$documentTypes[] = "substance";
		}
		if($this->spatial){
			$this->defaultSort = false; //sort by interest score
			$documentTypes[] = "spatial";
		}
		if($this->image){
			$documentTypes[] = "image";
		}
		if($this->video){
			$documentTypes[] = "video";
		}
		if($this->person){
			$documentTypes[] = "person";
		}
		if($this->project){
			$documentTypes[] = "project";
		}
		if($this->document){
			$documentTypes[] = "document";
		}
		if($this->table){
			$documentTypes[] = "table";
		}
		if($this->site){
			$documentTypes[] = "site";
		}
		if($this->media){
			$documentTypes[] = "acrobat pdf";
			$documentTypes[] = "external";
			$documentTypes[] = "KML";
			$documentTypes[] = "GIS";
		}
	
		return $documentTypes;
    }
    
    
    
    
    //sort the results. default is sort by "interest score "
    function requestSorting($param_array){
	
		$requestParams = $this->requestParams;
		
		if(isset($requestParams['sort'])){
			$sort = $requestParams['sort'];
		}
		else{
			$sort = $this->defaultSort;
		}
	
		if(isset($requestParams['facetSort'])){
		    $this->facetSort = true;
		}
	
	
		$sortTypeOutput = null;
		if(!$sort){
			$sortOutput = "interest_score desc";
			$sortTypeOutput = "Interest score (desc)";
		}//use default sort
		else{
			
			if(stristr($sort, ",")){
			$sortArray = explode(",", $sort);
			}
			else{
			$sortArray = array(0=>$sort);
			}
			
			$sortOutput = "";
			$sortTypeOutput = null;
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
				$actSortType = "Label ($ordering)";
			}
			elseif($sortType == "cat"){
				$actSort = "item_class ".$ordering;
				$actSortType = "Category ($ordering)";
			}
			elseif($sortType == "context"){
				$actSort = "default_context_path ".$ordering;
				$actSortType = "Context ($ordering)";
			}
			elseif($sortType == "proj"){
				$actSort = "project_name ".$ordering;
				$actSortType = "Project Name ($ordering)";
			}
			elseif($sortType == "updated"){
				$actSort = "update ".$ordering;
				$actSortType = "Update time ($ordering)";
			}
			elseif($sortType == "created"){
				$actSort = "pub_date ".$ordering;
				$actSortType = "Creation / publication time ($ordering)";
			}
			else{
				$actSort = "interest_score desc";
				$actSortType = "Interest Score (desc)";
			}
			
			if($firstLoop){
				$sortOutput = $actSort;
				$sortTypeOutput = $actSortType;
			}
			else{
				$sortOutput .= ", ".$actSort;
				$sortTypeOutput .= ", ".$actSortType;
			}
			
			$firstLoop = false;
	    }
	    
	}//end case with sorting requested

	
	if(isset($requestParams["q"]) && !isset($requestParams["sort"])){
	    //sort by relevancy, the default without parameter if you're doing a key-word search
	    $this->sortType = "Full-text search relevancy";
	    if(isset($param_array['sort'])){
			unset($param_array['sort']);
	    }
	}
	else{
	    $this->sortType = $sortTypeOutput;
	    $param_array['sort'] = $sortOutput;
	}


	return $param_array;
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
	if($this->table && !$this->allDocs){
	    $param_array["facet.field"][] = "top_taxon";
	    $requestParams['proj'] = "Open Context Tables";
	}
	if($this->allDocs){
	    $param_array["facet.field"][] = "item_type";
	}
	
	$requestParams = $this->requestParams;
	if(isset($requestParams['rel']) || isset($requestParams['targURI'])){
	    $param_array["facet.field"][] = "top_taxon";
	}
	if(isset($requestParams['t-start']) || isset($requestParams['t-end'])){
	    $param_array["facet.field"][] = "top_taxon";
	}
	if(isset($requestParams['default_context_path']) && isset($requestParams['cat'])){
	    $param_array["facet.field"][] = "top_taxon";
		//$param_array["facet.field"][] = "standard_taxon";
	}
		return $param_array;
    }
    
    
    function addResultFields($doc, $actDocOutput){
	
	$actDocOutput["pub_date"] = $doc->pub_date;
	$actDocOutput["update"] = $doc->pub_date;
	
	if($this->allDocs || $this->table){
	    $actDocOutput["item_label"] = $doc->item_label;
	    $actDocOutput["item_type"] = $doc->item_type;
	    
	    if(!empty($doc->item_class)){
			$actDocOutput["item_class"] = $doc->item_class;
	    }
	    else{
			$actDocOutput["item_class"] = null;
	    }
	    if(!empty($doc->creator)){
			$actDocOutput["creator"] = $doc->creator;
	    }
	    else{
			$actDocOutput["creator"] = null;
	    }
	    if(!empty($doc->contributor)){
			$actDocOutput["contributor"] = $doc->contributor;
	    }
	    else{
			$actDocOutput["contributor"] = null;
	    }
	    if(!empty($doc->project_name)){
			$actDocOutput["project_name"] = $doc->project_name;
	    }
	    else{
			$actDocOutput["project_name"] = null;
	    }
	    
	    $docTypeArray = $this->docTypeArray;
	    if(array_key_exists($doc->item_type, $docTypeArray)){
		$host = OpenContext_OCConfig::get_host_config();
		if(!stristr($doc->uuid, "http://")){
			$actDocOutput["href"] = $host.$docTypeArray[$doc->item_type]["href"].$doc->uuid;
		}
		else{
			$actDocOutput["href"] = $doc->uuid;
		}
	    }
	}
	if($this->substance){
	    $actDocOutput["item_label"] = $doc->item_label;
	    $actDocOutput["pub_date"] = $doc->pub_date;
	    $actDocOutput["update"] = $doc->pub_date;
	}
	
	if($this->spatial || $this->image || $this->document || $this->project){
	    
	    $docTypeArray = $this->docTypeArray;
	    if(array_key_exists($doc->item_type, $docTypeArray)){
		$host = OpenContext_OCConfig::get_host_config();
		if(!stristr($doc->uuid, "http://")){
			$actDocOutput["href"] = $host.$docTypeArray[$doc->item_type]["href"].$doc->uuid;
		}
		else{
			$actDocOutput["href"] = $doc->uuid;
		}
	    }
	    
	    if(!empty($doc->time_span)){
		$actDocOutput["time_span"] = $doc->time_span;
	    }
	    if(!empty($doc->geo_lat)){
		$actDocOutput["geo_lat"] = $doc->geo_lat;
	    }
	    if(!empty($doc->geo_long)){
		$actDocOutput["geo_long"] = $doc->geo_long;
	    }
	    if(!empty($doc->geo_point)){
		$actDocOutput["geo_point"] = $doc->geo_point;
	    }
	    if(!empty($doc->project_name)){
		$actDocOutput["project_name"] = $doc->project_name;
	    }
	    if(!empty($doc->item_class)){
		$actDocOutput["item_class"] = $doc->item_class;
	    }
	    if(!empty($doc->creator)){
		$actDocOutput["creator"] = $doc->creator;
	    }
	    if(!empty($doc->contributor)){
		$actDocOutput["contributor"] = $doc->contributor;
	    }
	    if(!empty($doc->var_vals)){
		$actDocOutput["var_vals"] = $doc->var_vals;
	    }
	}
	
	return $actDocOutput;
    }
    
    
    
    function checkTaxaPeopleFacets(){
		$requestParams = $this->requestParams;
		$slashCount = $this->slashCount;
		
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
		
		
		$documentTypes = $this->makeDocumentTypeArray();
		
		$param_array = array();
		$param_array = OpenContext_FacetQuery::build_simple_parameters($requestParams, $documentTypes);
		$param_array = OpenContext_FacetQuery::build_complex_parameters($requestParams, $param_array, $context_depth);
			
		//echo "here's the context depth:".print_r($param_array); 
		
		
		$param_array = $this->requestSorting($param_array); //set sorting (if needed)
		$param_array = $this->addFacetFields($param_array);
		
		if($this->table && !$this->allDocs){
			//get rid of the project facet for tables, it's not needed or wanted.
			
			$fixedFacets = array();
			foreach($param_array["facet.field"] as $facetField){
			if($facetField != "project_name" && $facetField != "creator"){
				$fixedFacets[] = $facetField ;
			}
			}
			$param_array["facet.field"] = $fixedFacets;
			unset($fixedFacets);
			$this->showPeopleFacets = true;
		}
		
		if($this->showTaxaFacets){
			$param_array["facet.field"][] = "top_taxon";
		}
		if($this->showPeopleFacets){
			$param_array["facet.field"][] = "person_link";
		}
		else{
			if(array_search("creator", $param_array["facet.field"])){
				$badKey = array_search("creator", $param_array["facet.field"]);
				unset($param_array["facet.field"][$badKey]);
			}
		}
		
		
		if($this->reconcile){
			//reconcilation doesn't need many facets, remove superfluous ones
			$skipFacets = array("project_name", "creator", "item_class", "person_link",
								"contributor", "image_media_count", "other_binary_media_count",
								"diary_count");
				
			$fixedFacets = array();
			foreach($param_array["facet.field"] as $facetField){
				if(!in_array($facetField, $skipFacets)){
					$fixedFacets[] = $facetField ;
				}
			}
			$param_array["facet.field"] = $fixedFacets;
			unset($fixedFacets);
		}

		
		
		$default_context_path = $this->default_context_path;
		if (!$default_context_path) {
		    $query = "*:*";
			// otherwise, query for the default context path.
		} else {
		    $query = "default_context_path:" . $default_context_path . "*";
		}    
			
		$contextArray = Opencontext_FacetQuery::defaultContextORparser("default_context_path", $this->original_default_context_path);
		$query = $contextArray["query"];
		if(!stristr($query, "default_context_path")){
		    $query = "*:*";
		}
		
		if($this->geoParam != false){
			$query = "(".$query.") && (".$this->geoParam.")";
			if(is_array($this->geoFacets)){
				foreach($this->geoFacets as $geoFacet){
					 $param_array["facet.field"][] = $geoFacet;
				}
			}
		}
		
		unset($param_array["bq"]);
		//$param_array["bq"][] = "{item_type:site^10}";
		//$param_array["fq"] .= " && item_type:site^10";
		//$param_array["bq"][] = "!item_type:project^5";
		
		
		if($this->allDocs){
			//$query .= "&hl=true";
			$param_array["hl"] = "true";
			$param_array["hl.fl"] = "full_text";
			$param_array["hl.fragsize"] = 140;
			$param_array['hl.simple.pre'] = '<strong>';
			$param_array['hl.simple.post'] = '</strong>';
		}
		
		if($this->facetSort){
		    $param_array["facet.sort"] = "index";
		}
		
		
		//$query .= " item_type:site^10";
		$this->param_array = $param_array;
		$this->query = $query;
		
		//echo print_r($param_array);
	
    }
    
    
	//this gives Solr two chances to respond, with a .5 second delay
	function pingSolr($solr){
	    
		if ($solr->ping()) {
			return true;
		}
		else{
			sleep(.5);
			if ($solr->ping()) {
			    return true;
			}
			else{
			    
			    $solrError = new SolrError;
			    $solrError->restartSolr();
			    sleep(.75);
			    if ($solr->ping()) {
				return true;
			    }
			    else{
				return false;
			    }
			}
		}
		
	}
	
	
    function execute_search(){
	/*
	echo "<br/>Query: ".$this->query;
	echo "<br/>".$this->number_recs;
	echo "<br/>".var_dump($this->param_array);
	*/
	
	$solr = new Apache_Solr_Service('localhost', 8983, '/solr');
	//$solr->setDefaultTimeout(5.0);
	if ($this->pingSolr($solr)) {
	//if (true) {
	    try {
	
            $response = $solr->search(	$this->query,
                                 $this->offset,
                                 $this->number_recs,
                                 $this->param_array);
                      
            $this->queryString = $solr->queryString;
            $docs_array = array();
            
            foreach (($response->response->docs) as $doc) {
                
                $actDocOutput = array("uuid" => $doc->uuid);
                if(!$this->rawDocsArray){
                  $actDocOutput = $this->addResultFields($doc, $actDocOutput);
                }
                else{
                  $allDoc = (array)$doc;
                  $arrayKey = array_keys($allDoc);
                  $actDocOutput = $allDoc[$arrayKey[1]]; //second key has the fields and data we want in an array
                  //echo print_r($actDocOutput);
                }
                
                $docs_array[] = $actDocOutput ;
            }
            
            $rawResponse = Zend_Json::decode($response->getRawResponse());
            $reponse = $rawResponse['response'];
            $numFound = $reponse['numFound'];
            $this->numFound = $numFound;
            $this->documentsArray =  $docs_array;
            
            if(isset($rawResponse['facet_counts'])){
                $this->facets =  $rawResponse['facet_counts'];
            }
            $this->pseudoBoost();
            $this->getGeoTiles();
		
	    } catch (Exception $e) {
            $this->solrDown = true;
            $this->queryString = $solr->queryString;
            $solrError = new SolrError;
            $requestParams = $this->requestParams;
            $requestParams["solrError"] = (string)$e;
            $this->requestParams = $requestParams;
            $solrError->initialize($this->requestParams);
	    }

	} else {
	    //die("unable to connect to the solr server. exiting...");
	    //echo OpenContext_OCConfig::getSolrDownMessage();
	    //die("Upgrade in progress, unable to connect to the solr server. exiting...");
	    
	    $this->solrDown = true;
	    $solrError = new SolrError;
	    $solrError->initialize($this->requestParams);

	}
	
	
	$this->makeAltLinks(); //now that search results are found, make some links
    }
    
    
    /*
    Since boosting doesn't seem to work yet, this is a work around for boosting site and project descriptions in search results 
    */
    function pseudoBoost(){
	
	if($this->allDocs){
	    $highRankArray = array(); //highest ranking array
	    $secRankArray = array(); //next ranking
	    $restArray = array(); //everything else
	    foreach($this->documentsArray as $doc){
		if($doc["item_type"] == "site"){
		    $highRankArray[] = $doc;
		}
		elseif($doc["item_type"] == "project"){
		    $secRankArray[] = $doc;
		}
		else{
		    $restArray[] = $doc;
		}
	    }
	    
	    $newDocs = array_merge($highRankArray, $secRankArray, $restArray);
	    $this->documentsArray = $newDocs;
	}
    }
    
    
    
    //another Solr Query to get the last updated time
    function getLatestTime($doUpdate = true){
	
	$param_array = $this->param_array;
	$query = $this->query;
	
	if(isset($param_array["facet"])){
	    unset($param_array["facet"]);
	}
	if(isset($param_array["facet.mincount"])){
	    unset($param_array["facet.mincount"]);
	}
	if(isset($param_array["facet.field"])){
	    unset($param_array["facet.field"]);
	}
	if(isset($param_array["facet.field"])){
	    unset($param_array["facet.query"]);
	}
	
	if($doUpdate){
	    $param_array["sort"] = "update desc";
	}
	else{
	    $param_array["sort"] = "pub_date desc";
	}
	
	$lastUpdate = false;
	$solr = new Apache_Solr_Service('localhost', 8983, '/solr');
	//if ($this->pingSolr($solr)) {
	if (true) {
	    try {
	
		$response = $solr->search($query,
					0, //offset
					1, //number of records
					$param_array);
                
		foreach (($response->response->docs) as $doc) {
		    if($doUpdate){
				$this->lastUpdate = $doc->update;
		    }
		    else{
				$this->lastPublished = $doc->pub_date;
		    }
		}
		
		$rawResponse = Zend_Json::decode($response->getRawResponse());
		
	    } catch (Exception $e) {
		$this->solrDown = true;
		$solrError = new SolrError;
		$requestParams = $this->requestParams;
		$requestParams["solrError"] = $e;
		$this->requestParams = $requestParams;
		$solrError->initialize($this->requestParams);
	    }

	} else {
	    //die("unable to connect to the solr server. exiting...");
	    //echo OpenContext_OCConfig::getSolrDownMessage();
	    //die("Upgrade in progress, unable to connect to the solr server. exiting...");
	    $this->solrDown = true;
	}
	
    }//end funciton
    
    
    //another Solr Query to get available GeoTiles
    function getGeoTiles(){
		$solrFacets = $this->facets;
		$geoTileFacets = array();
		if(isset($solrFacets["facet_fields"])){
			$geoTileFacets = array();
			foreach($solrFacets["facet_fields"] as $key => $valueArray){
				if(stristr($key, "_geo_tile")){
					if(count($valueArray)>0){
						$geoKeyPrefix = str_replace("_geo_tile", "", $key);
						foreach($valueArray as $tileKey => $count){
							$geoKey = $geoKeyPrefix.$tileKey;
							$geoTileFacets[$geoKey] = $count;
						}
					}
				}
			}
			if(count($geoTileFacets)>0){
				$this->geoTileFacets = $geoTileFacets;
			}
		}
    }//end funciton
    
    
    
    
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
					"results_kml" => $link.".kml",
					"facets_atom" => $facetLink.".atom",
					"facets_json" => $facetLink.".atom",
					"facets_kml" => $facetLink.".kml",
					"opensearch" => $openSearchLink.".xml"
					);
	
		
		$this->firstPage_XHTML= $this->add_url_parameters($linksRootArray["xhtml"], $requestParams);
		$this->firstPage_JSON= $this->add_url_parameters($linksRootArray["results_json"], $requestParams);
		$this->firstPage_Atom= $this->add_url_parameters($linksRootArray["results_atom"], $requestParams);
		$this->firstPage_KML= $this->add_url_parameters($linksRootArray["results_kml"], $requestParams);
		
		if($page > 1){
			$this->prevPage_XHTML= $this->add_url_parameters($linksRootArray["xhtml"], $requestParams, "page", $page -1 );
			$this->prevPage_JSON= $this->add_url_parameters($linksRootArray["results_json"], $requestParams, "page", $page -1 );
			$this->prevPage_Atom= $this->add_url_parameters($linksRootArray["results_atom"], $requestParams, "page", $page -1 );
			$this->prevPage_KML= $this->add_url_parameters($linksRootArray["results_kml"], $requestParams, "page", $page -1 );
			
			//current page
			$this->currentXHTML = $this->add_url_parameters($linksRootArray["xhtml"], $requestParams, "page", $page );
			$this->currentJSON = $this->add_url_parameters($linksRootArray["results_json"], $requestParams, "page", $page );
			$this->currentAtom = $this->add_url_parameters($linksRootArray["results_atom"], $requestParams, "page", $page );
			$this->currentKML = $this->add_url_parameters($linksRootArray["results_kml"], $requestParams, "page", $page );
		}
		else{
			$this->prevPage_XHTML= false;
			$this->prevPage_JSON= false;
			$this->prevPage_Atom= false;
			
			//current page (no parameter)
			$this->currentXHTML = $this->add_url_parameters($linksRootArray["xhtml"], $requestParams);
			$this->currentJSON = $this->add_url_parameters($linksRootArray["results_json"], $requestParams);
			$this->currentAtom = $this->add_url_parameters($linksRootArray["results_atom"], $requestParams);
			$this->currentKML = $this->add_url_parameters($linksRootArray["results_kml"], $requestParams);
		}
		
		if($page < $lastPage){
			$this->nextPage_XHTML = $this->add_url_parameters($linksRootArray["xhtml"], $requestParams, "page", $page + 1 );
			$this->nextPage_JSON = $this->add_url_parameters($linksRootArray["results_json"], $requestParams, "page", $page + 1 );
			$this->nextPage_Atom =$this->add_url_parameters($linksRootArray["results_atom"], $requestParams, "page", $page + 1 );
			$this->nextPage_KML =$this->add_url_parameters($linksRootArray["results_kml"], $requestParams, "page", $page + 1 );
		}
		else{
			$this->nextPage_XHTML = false;
			$this->nextPage_JSON = false;
			$this->nextPage_Atom = false;
		}
		
		$this->lastPage_XHTML = $this->add_url_parameters($linksRootArray["xhtml"], $requestParams, "page", $lastPage );
		$this->lastPage_JSON = $this->add_url_parameters($linksRootArray["results_json"], $requestParams, "page", $lastPage );
		$this->lastPage_Atom =$this->add_url_parameters($linksRootArray["results_atom"], $requestParams, "page", $lastPage );
		$this->lastPage_KML =$this->add_url_parameters($linksRootArray["results_kml"], $requestParams, "page", $lastPage );
		
		//these are links to facets only
		
		if(isset($requestParams["recs"])){
			unset($requestParams["recs"]);
		}
		
		$this->facetURI_Atom= $this->add_url_parameters($linksRootArray["facets_atom"], $requestParams );
		$this->facetURI_JSON= $this->add_url_parameters($linksRootArray["facets_json"], $requestParams );
		$this->facetURI_KML= $this->add_url_parameters($linksRootArray["facets_kml"], $requestParams );
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
    
    
    
    
    function geoTileDirections($currentTile, $direction){
		$output = "up";
		if($direction == "north"){
			if($currentTile == 2){
			$output = 0;
			}
			if($currentTile == 3){
			$output = 1;
			}
		}
		elseif($direction == "south"){
			if($currentTile == 0){
			$output = 2;
			}
			if($currentTile == 1){
			$output = 3;
			}
		}
		elseif($direction == "west"){
			if($currentTile == 1){
			$output = 0;
			}
			if($currentTile == 3){
			$output = 2;
			}
		}
		elseif($direction == "east"){
			if($currentTile == 0){
			$output = 1;
			}
			if($currentTile == 2){
			$output = 3;
			}
		}
		
		return $output;
    }//end function
    
    
     
    
    
    
    //makes an atom feed of spatial (location / object) items
    function makeSpaceAtomFeed(){
	
	// the number of results per page. Note: the actual number of results per page is set in the controller as an argument to the solr query.
	// the resulstPerPage variable helps us calculate the link and opensearch elements
	$resultsPerPage = $this->number_recs;
	$requestParams =$this->requestParams;
	$fixedParams = $requestParams;
	$fixedParams["action"] = "index";
	$host = OpenContext_OCConfig::get_host_config();
	$summaryObj = OpenContext_FacetOutput::active_filter_object($fixedParams, $host);
	
	$filters = "";
	$firstLoop = true;
	if(count($summaryObj)<1){
	    $filters = "[None]";
	}//case without filters
	else{
	    foreach($summaryObj as $filter){
		
		$filter['value'] = str_replace("&#8220; ", "'", $filter['value']);
		$filter['value'] = str_replace(" &#8221;", "'", $filter['value']);
		$actFilter = $filter['filter'].": ".$filter['value'];
		if($firstLoop){
		    $filters = $actFilter;
		}
		else{
		     $filters .= "; ".$actFilter;
		}
	    $firstLoop = false;
	    }//end loop
	}//case with filters

	
	
	
	
	
	
	
	
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
	
	
	
	// Display the number of items found and handle paging. 
	$first = $offset + 1;
	$last = $offset + $resultsPerPage;
	
	// make sure the last page, which will usually contain fewer than 10 items, displays the correct number of items.
	if ($numFound < $last) {
	   $subTitleText = 'items ' . $first . ' to ' . $numFound . ' out of ' . $numFound . ' items'; 
	} else {
	    $subTitleText = 'items ' . $first . ' to ' . $last . ' out of ' . $numFound . ' items';
	}
	$subTitleText .= ". Sorted by: ".$this->sortType;
	//$subTitleText .= ". Filtered by -- ".$filters;
	
	$feedSubtitle = $atomFullDoc->createElement("subtitle");
	$feedSubtitleText = $atomFullDoc->createTextNode($subTitleText);
	$feedSubtitle->appendChild($feedSubtitleText);
	$root->appendChild($feedSubtitle);
	
	
	// Feed updated element (as opposed to the entry updated element)
	$feedUpdated = $atomFullDoc->createElement("updated");
	$updatedTime = OpenContext_OCConfig::last_update();
	// Retrieve the current date and time. Format it in RFC 3339 format. Store it in a text node 
	$feedUpdatedText = $atomFullDoc->createTextNode(date("Y-m-d\TH:i:s\-07:00", strtotime($this->lastUpdate)));
	$feedUpdated->appendChild($feedUpdatedText);
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
	
	// prepare link elements
    
	// feed (self) link element
	$feedLink = $atomFullDoc->createElement("link");
	$feedLink->setAttribute("rel", "self");
	$feedLink->setAttribute("href", $this->currentAtom);
	$root->appendChild($feedLink);
	
	// feed license link element
	$feedLink = $atomFullDoc->createElement("link");
	$feedLink->setAttribute("rel", "license");
	$feedLink->setAttribute("type", "text/html");
	$feedLink->setAttribute("href", "http://creativecommons.org/licenses/by/3.0/");
	$root->appendChild($feedLink);
	    
	// feed (facets) link element
	$feedLink = $atomFullDoc->createElement("link");
	$feedLink->setAttribute("rel", "http://opencontext.org/about/services#atom-facets");
	$feedLink->setAttribute("type", "application/atom+xml");
	$feedLink->setAttribute("href", $this->facetURI_Atom);
	$root->appendChild($feedLink);
	    
	    
	// feed (HTML representation) link element
	$feedLink = $atomFullDoc->createElement("link");
	$feedLink->setAttribute("rel", "alternate");
	$feedLink->setAttribute("type", "application/xhtml+xml");
	$feedLink->setAttribute("href", $this->currentXHTML);
	$root->appendChild($feedLink);
	
	// feed (JSON representation) link element
	$feedLink = $atomFullDoc->createElement("link");
	$feedLink->setAttribute("rel", "alternate");
	$feedLink->setAttribute("type", "application/json");
	$feedLink->setAttribute("href", $this->currentJSON);
	$root->appendChild($feedLink);
	
	    
	    
	//prepare the first link
	$feedFirstLink = $atomFullDoc->createElement("link");
	$feedFirstLink->setAttribute("rel", "first");
	$feedFirstLink->setAttribute("href", $this->firstPage_Atom);
	$feedFirstLink->setAttribute("type", "application/atom+xml");
	$root->appendChild($feedFirstLink);
	    
	    
	// create last link
	$feedLastLink = $atomFullDoc->createElement('link');
	$feedLastLink->setAttribute('rel', 'last');
	$feedLastLink->setAttribute('href', $this->lastPage_Atom);
	$feedLastLink->setAttribute("type", "application/atom+xml");
	$root->appendChild($feedLastLink);
    
	//previous page link
	if($this->prevPage_Atom != false){
	    $previousLink = $atomFullDoc->createElement('link');
	    $previousLink->setAttribute('rel', 'previous');
	    $previousLink->setAttribute('href', $this->prevPage_Atom);
	    $previousLink->setAttribute("type", "application/atom+xml");
	    $root->appendChild($previousLink);    
	}
	
	//next page link
	if($this->nextPage_Atom != false){
	    $nextLink = $atomFullDoc->createElement('link');
	    $nextLink->setAttribute('rel', 'next');
	    $nextLink->setAttribute('href', $this->nextPage_Atom);
	    $nextLink->setAttribute("type", "application/atom+xml");
	    $root->appendChild($nextLink);    
	}
    
	//add feed id, use current link
	$feedId = $atomFullDoc->createElement("id");
	$feedIdText = $atomFullDoc->createTextNode($this->currentAtom);
	$feedId->appendChild($feedIdText);
	$root->appendChild($feedId);
	
	//add experimental geo-tile feed links
	if(isset($requestParams["geotile"])){
	    
	    $actTile = $requestParams["geotile"];
	    $actTileLen = strlen($actTile);
	    
	    $downTileArray = array(0 => "http://tiledfeeds.yimingliu.com/tiledfeeds/relation/down-northwest",
				   1 => "http://tiledfeeds.yimingliu.com/tiledfeeds/relation/down-northeast",
				   2 => "http://tiledfeeds.yimingliu.com/tiledfeeds/relation/down-southwest",
				   3 => "http://tiledfeeds.yimingliu.com/tiledfeeds/relation/down-southeast");
	    
	    if(strlen($actTile)<=20){
		foreach($downTileArray as $tileKey => $linkRel){
		    $downTile = $actTile.$tileKey;
		    $feedParams = $fixedParams;
		    $feedParams["geotile"] = $downTile;
		    $feedHref = $host.OpenContext_FacetOutput::generateFacetURL($feedParams, false, false, false, false, "results_atom");
		    
		    $tileLink = $atomFullDoc->createElement('link');
		    $tileLink->setAttribute('rel', $linkRel);
		    $tileLink->setAttribute('href', $feedHref);
		    $tileLink->setAttribute("type", "application/atom+xml");
		    $root->appendChild($tileLink);
		}
	    }
	    if($actTileLen >1){
		$upTile = substr($actTile, 0, -1);
		$feedParams = $fixedParams;
		$feedParams["geotile"] = $upTile;
		$feedHref = $host.OpenContext_FacetOutput::generateFacetURL($feedParams, false, false, false, false, "results_atom");
		
		$tileLink = $atomFullDoc->createElement('link');
		$tileLink->setAttribute('rel', "http://tiledfeeds.yimingliu.com/tiledfeeds/relation/up");
		$tileLink->setAttribute('href', $feedHref);
		$tileLink->setAttribute("type", "application/atom+xml");
		$root->appendChild($tileLink);
	    
		$relatedTiles = OpenContext_GeoTile::relatedTiles($actTile);
		foreach($relatedTiles as $directionKey => $rawTile){
		
		    if($rawTile != false){
			if($directionKey != "data"){
			    $tile = substr($rawTile, 0, $actTileLen); //only get the same length as active tile
			}
			else{
			    $tile = $rawTile;
			}
			$feedParams = $fixedParams;
			$feedParams["geotile"] = $tile;
			$feedHref = $host.OpenContext_FacetOutput::generateFacetURL($feedParams, false, false, false, false, "results_atom");
			
			$tileLink = $atomFullDoc->createElement('link');
			$tileLink->setAttribute('rel', "http://tiledfeeds.yimingliu.com/tiledfeeds/relation/".$directionKey);
			$tileLink->setAttribute('href', $feedHref);
			$tileLink->setAttribute("type", "application/atom+xml");
			$root->appendChild($tileLink);    
		    }
		    
		}
		
	    
	    }
	   
	    
	}
	
	if($numFound>0){
	   $docs_array = $this->documentsArray;
	   
	   $okEntries = false; //no atom entries found, don't make a document fragment, fail gracefully
	   $contentFragment = $atomFullDoc->createDocumentFragment();
	   
	   
	   if ($docs_array) {
	      $idArray = array();
	      foreach ($docs_array as $docArray) {
		 $idArray[] = $docArray["uuid"];
	      }
	      
	      
	      
	      $itemEntries = new SubjectsEntries;
	      $idEntryArray = $itemEntries->getByIDArray($idArray);
	    
	      foreach($idEntryArray as $itemUUID => $atomEntry){
			
			//$atomEntry = mb_convert_encoding($atomEntry, 'UTF-8');
		 
			if(strlen($atomEntry)<10){
				$spaceItem = New Subject;
				$spaceItem->getByID($itemUUID);
				if(strlen($spaceItem->atomEntry)<10){
				   $spaceItem->solr_getArchaeoML();
				   $fixed_ArchaeoML = $spaceItem->archaeoML_update($spaceItem->archaeoML);
				   $spaceItem->archaeoML_update($fixed_ArchaeoML);
				   $spaceItem->kml_in_Atom = true; // it doesn't validate, but it is really useful
				   $fullAtom = $spaceItem->DOM_spatialAtomCreate($spaceItem->archaeoML);
				   $spaceItem->update_atom_entry();
		       
					//echo var_dump($spaceItem);
				}
				 //echo var_dump($spaceItem);
				$doc = str_replace('<?xml version="1.0" encoding="utf-8"?>', "", $spaceItem->atomEntry);
				 
				if(strlen($doc)>10){
					$contentFragment->appendXML($doc);  // $atom_content from short atom entry
					$okEntries = true;
				}
				unset($spaceItem);
			}
			else{
			   $doc = str_replace('<?xml version="1.0" encoding="utf-8"?>', "", $atomEntry);
			   $contentFragment->appendXML($doc);  // $atom_content from short atom entry
			   $okEntries = true;
			}
		 
			unset($itemEntries);
		 
	    }
	      
		/*
		foreach($idArray as $itemUUID){
		    $spaceItem = New Subject;
		    $spaceItem->getByID($itemUUID);
		    $doc = str_replace('<?xml version="1.0" encoding="utf-8"?>', "", $spaceItem->atomEntry);
		    if(strlen($doc)>10){
			$contentFragment->appendXML($doc);  // $atom_content from short atom entry
			$okEntries = true;
		    }
		    unset($spaceItem);
		}
		*/
		   
		if($okEntries){ //only add if there's something to add, else fail gracefully
		    $root->appendChild($contentFragment);
		}
	       
	   }
	}
	
	$resultString = $atomFullDoc->saveXML();
	
	// Note: simpleXML will add a 'default:' prefix to the XHTML content.  We don't want this, so remove it.
	$resultString = str_replace('default:', '' , $resultString);
	
	return $resultString;
    }//end function
    
    
    
    //Use some of this object's properties and atom-feed-string to make an array (for JSON output) of spatial (location/object) item search results
    function atom_to_object($atom_string){
	
		$allResults = array();
		$host = OpenContext_OCConfig::get_host_config();	
	
		@$atomXML = simplexml_load_string($atom_string);
	
		if($atomXML){
	
			$atomXML->registerXPathNamespace("default", "http://www.w3.org/2005/Atom");
			$atomXML->registerXPathNamespace("opensearch", "http://a9.com/-/spec/opensearch/1.1/");
			
			$resultCount = $this->numFound;
			
			$resultSubTitle = OpenContext_ResultAtom::get_xml_string($atomXML, "/default:feed/default:subtitle");
			$first_PageURI = $this->firstPage_XHTML;
			$last_PageURI = $this->lastPage_XHTML;
			$next_PageURI = $this->nextPage_XHTML;
			$prev_PageURI = $this->prevPage_XHTML;
			
			$docTypeArray = $this->docTypeArray;
			$resultArrayURIKey = array();
			if(is_array($this->documentsArray)){
				foreach($this->documentsArray as $doc){
					$uriKey = $host.$docTypeArray["spatial"]["href"].$doc["uuid"];
					$resultArrayURIKey[$uriKey] = $doc;
				}//end loop
			}
			
			
			$eee = count($resultArrayURIKey);
			$iii = 0;
			if($eee>0){
				
				foreach ($atomXML->xpath("/default:feed/default:entry") as $AtomEntry) {
					$AtomEntry->registerXPathNamespace("default", "http://www.w3.org/2005/Atom");
					$AtomEntry->registerXPathNamespace("xhtml", "http://www.w3.org/1999/xhtml");
					$AtomEntry->registerXPathNamespace("geo", OpenContext_OCConfig::get_namespace("georss"));
					$AtomEntry->registerXPathNamespace("kml", OpenContext_OCConfig::get_namespace("kml"));
					
					$geoLat = false;
					$geoLon = false;
					$kmlBegin = false;
					$kmlEnd = false;
					
					$entryURI = false;
					foreach($AtomEntry->xpath("./default:id") as $idNode){
						$entryURI = (string)$idNode;
					}
					
					if($AtomEntry->xpath("./geo:point")){
						foreach($AtomEntry->xpath("./geo:point") as $geoNode){
							$geo = (string)$geoNode;
							$geoData = explode(" ", $geo);
							$geoLat = $geoData[0] +0;
							$geoLon = $geoData[1] +0;
						}
					}
					else{
						$resultArrayURIKey[$entryURI] = $doc;
						if(stristr($doc["geo_point"], ",")){
							$geoData = explode(",", $doc["geo_point"]);
						}
						else{
							$geoData = explode(" ", $doc["geo_point"]);
						}
						$geoLat = $geoData[0] +0;
						$geoLon = $geoData[1] +0;
					}
					
					if(isset($doc["time_span"])){
						$timeData = explode(" ", $doc["time_span"]);
						$kmlBegin = $timeData[0] +0;
						$kmlEnd = $timeData[1] +0;
					}
					else{
						$kmlBegin = false;
						$kmlEnd = false;
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
							foreach($act_content->xpath(".//xhtml:div[@class='context']") as $itemContextXML){
								$itemContext = $itemContextXML->asXML();
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
						
						$var_vals = false;
						if(is_array($this->documentsArray)){
						    foreach($this->documentsArray as $doc){
							if(isset($doc["href"])){
							    if($doc["href"] == $entryURI){
								if(isset($doc["var_vals"])){
								    $var_vals = Zend_json::decode($doc["var_vals"]);
								}
								break;
							    }
							}
							else{
							    $var_vals = "no href";
							}
						    }
						}
						else{
						    $var_vals = "no docs";
						}
						
						
						$resultItem = array("uri"=>$entryURI,
									"category"=>$itemCat,
									"catIcon"=>$itemIcon,
									"project"=>$itemProject,
									"label"=>$itemLabel,
									"context"=> $itemContext,
									"thumbIcon"=>$itemThumb,
									"var_vals" => $var_vals,
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






    //makes an atom feed of spatial (location / object) items
    function makeImageAtomFeed(){
	
	// the number of results per page. Note: the actual number of results per page is set in the controller as an argument to the solr query.
	// the resulstPerPage variable helps us calculate the link and opensearch elements
	$resultsPerPage = $this->number_recs;
	$requestParams =$this->requestParams;
	$fixedParams = $requestParams;
	$fixedParams["action"] = "index";
	$host = OpenContext_OCConfig::get_host_config();
	$summaryObj = OpenContext_FacetOutput::active_filter_object($fixedParams, $host);
	
	$filters = "";
	$firstLoop = true;
	if(count($summaryObj)<1){
	    $filters = "[None]";
	}//case without filters
	else{
	    foreach($summaryObj as $filter){
		
		$filter['value'] = str_replace("&#8220; ", "'", $filter['value']);
		$filter['value'] = str_replace(" &#8221;", "'", $filter['value']);
		$actFilter = $filter['filter'].": ".$filter['value'];
		if($firstLoop){
		    $filters = $actFilter;
		}
		else{
		     $filters .= "; ".$actFilter;
		}
	    $firstLoop = false;
	    }//end loop
	}//case with filters
	
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
	$feedTitleText = $atomFullDoc->createTextNode("Open Context Image Query Results");
	$feedTitle->appendChild($feedTitleText);
	$root->appendChild($feedTitle);
	
	
	// Prepare the feed's subtitle
	$offset = $this->offset;
	$numFound = $this->numFound;
	
	
	
	// Display the number of items found and handle paging. 
	$first = $offset + 1;
	$last = $offset + $resultsPerPage;
	
	// make sure the last page, which will usually contain fewer than 10 items, displays the correct number of items.
	if ($numFound < $last) {
	   $subTitleText = 'images ' . $first . ' to ' . $numFound . ' out of ' . $numFound . ' images'; 
	} else {
	    $subTitleText = 'images ' . $first . ' to ' . $last . ' out of ' . $numFound . ' images';
	}
	$subTitleText .= ". Sorted by: ".$this->sortType;
	//$subTitleText .= ". Filtered by -- ".$filters;
	
	$feedSubtitle = $atomFullDoc->createElement("subtitle");
	$feedSubtitleText = $atomFullDoc->createTextNode($subTitleText);
	$feedSubtitle->appendChild($feedSubtitleText);
	$root->appendChild($feedSubtitle);
	
	
	// Feed updated element (as opposed to the entry updated element)
	$feedUpdated = $atomFullDoc->createElement("updated");
	$updatedTime = OpenContext_OCConfig::last_update();
	// Retrieve the current date and time. Format it in RFC 3339 format. Store it in a text node 
	$feedUpdatedText = $atomFullDoc->createTextNode(date("Y-m-d\TH:i:s\-07:00", strtotime($this->lastUpdate)));
	$feedUpdated->appendChild($feedUpdatedText);
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
	
	// prepare link elements
    
	// feed (self) link element
	$feedLink = $atomFullDoc->createElement("link");
	$feedLink->setAttribute("rel", "self");
	$feedLink->setAttribute("href", $this->currentAtom);
	$root->appendChild($feedLink);
	
	// feed license link element
	$feedLink = $atomFullDoc->createElement("link");
	$feedLink->setAttribute("rel", "license");
	$feedLink->setAttribute("type", "text/html");
	$feedLink->setAttribute("href", "http://creativecommons.org/licenses/by/3.0/");
	$root->appendChild($feedLink);
	    
	// feed (facets) link element
	$feedLink = $atomFullDoc->createElement("link");
	$feedLink->setAttribute("rel", "http://opencontext.org/about/services#atom-facets");
	$feedLink->setAttribute("type", "application/atom+xml");
	$feedLink->setAttribute("href", $this->facetURI_Atom);
	$root->appendChild($feedLink);
	    
	    
	// feed (HTML representation) link element
	$feedLink = $atomFullDoc->createElement("link");
	$feedLink->setAttribute("rel", "alternate");
	$feedLink->setAttribute("type", "application/xhtml+xml");
	$feedLink->setAttribute("href", $this->currentXHTML);
	$root->appendChild($feedLink);
	
	// feed (JSON representation) link element
	$feedLink = $atomFullDoc->createElement("link");
	$feedLink->setAttribute("rel", "alternate");
	$feedLink->setAttribute("type", "application/json");
	$feedLink->setAttribute("href", $this->currentJSON);
	$root->appendChild($feedLink);
	
	    
	    
	//prepare the first link
	$feedFirstLink = $atomFullDoc->createElement("link");
	$feedFirstLink->setAttribute("rel", "first");
	$feedFirstLink->setAttribute("href", $this->firstPage_Atom);
	$feedFirstLink->setAttribute("type", "application/atom+xml");
	$root->appendChild($feedFirstLink);
	    
	    
	// create last link
	$feedLastLink = $atomFullDoc->createElement('link');
	$feedLastLink->setAttribute('rel', 'last');
	$feedLastLink->setAttribute('href', $this->lastPage_Atom);
	$feedLastLink->setAttribute("type", "application/atom+xml");
	$root->appendChild($feedLastLink);
    
	//previous page link
	if($this->prevPage_Atom != false){
	    $previousLink = $atomFullDoc->createElement('link');
	    $previousLink->setAttribute('rel', 'previous');
	    $previousLink->setAttribute('href', $this->prevPage_Atom);
	    $previousLink->setAttribute("type", "application/atom+xml");
	    $root->appendChild($previousLink);    
	}
	
	//next page link
	if($this->nextPage_Atom != false){
	    $nextLink = $atomFullDoc->createElement('link');
	    $nextLink->setAttribute('rel', 'next');
	    $nextLink->setAttribute('href', $this->nextPage_Atom);
	    $nextLink->setAttribute("type", "application/atom+xml");
	    $root->appendChild($nextLink);    
	}
    
	//add feed id, use current link
	$feedId = $atomFullDoc->createElement("id");
	$feedIdText = $atomFullDoc->createTextNode($this->currentAtom);
	$feedId->appendChild($feedIdText);
	$root->appendChild($feedId);
	
	if($numFound>0){
	   $docs_array = $this->documentsArray;
	   
	   $okEntries = false; //no atom entries found, don't make a document fragment, fail gracefully
	   $contentFragment = $atomFullDoc->createDocumentFragment();
	   
	   
	   if ($docs_array) {
	      
		foreach($docs_array as $mediaDoc){
		   
		    $itemUUID = $mediaDoc["uuid"];
		    $mediaItem = New Media;
		    $mediaItem->getByID($itemUUID);
		    
		    $doc = str_replace('<?xml version="1.0" encoding="utf-8"?>', "", $mediaItem->atomEntry);
		    if(strlen($doc)>10){
			$contentFragment->appendXML($doc);  // $atom_content from short atom entry
			$root->appendChild($contentFragment);
			$okEntries = true;
		    }
		    unset($mediaItem);
		   
		}//end loop
	       
	    }//end doc array
	}//num found low
	
	$resultString = $atomFullDoc->saveXML();
	
	// Note: simpleXML will add a 'default:' prefix to the XHTML content.  We don't want this, so remove it.
	$resultString = str_replace('default:', '' , $resultString);
	
	return $resultString;
    }//end function



/*
Fix for atom feeds of images:

UPDATE resource
SET atom_entry =  REPLACE(atom_entry, '<link rel="preview"', '<link rel="http://purl.org/dc/terms/hasPart"')

UPDATE resource
SET atom_entry =  REPLACE(atom_entry, '<link rel="thumbnail"', '<link rel="http://purl.org/dc/terms/hasPart"')

UPDATE resource
SET atom_entry =  REPLACE(atom_entry, '(tumbnail file)', '(thumbnail file)')

*/

//make JSON object of image search results
    function makeImageObject(){
    
	$host = OpenContext_OCConfig::get_host_config();
	$docTypeArray = $this->docTypeArray;
	$itemResults = array();
	foreach($this->documentsArray as $doc){
	    
	    $mediaItem = New Media;
	    $mediaItem->getByID($doc["uuid"]);
	    $mediaItem->XML_fileURIs(); //get the file URIs from the XML document
	    
	    $uriItem = $host.$docTypeArray["media"]["href"].$doc["uuid"];
	    $geoLat = false;
	    $geoLon = false;
	    $kmlBegin = false;
	    $kmlEnd = false;
	    if(isset($doc["geo_point"])){
		if(stristr($doc["geo_point"], ",")){
		    $geoData = explode(",", $doc["geo_point"]);
		}
		else{
		    $geoData = explode(" ", $doc["geo_point"]);
		}
		
		$geoLat = $geoData[0] +0;
		$geoLon = $geoData[1] +0;
	    }
	    if(isset($doc["time_span"])){
		$timeData = explode(" ", $doc["time_span"]);
		$kmlBegin = $timeData[0] +0;
		$kmlEnd = $timeData[1] +0;
	    }
	    
	    $resultItem = array("uri"=> $uriItem,
				"project"=>$doc["project_name"],
				"label"=> $mediaItem->label." (".$doc["project_name"].": ".$doc["item_class"][0].")",
				"thumbURI"=> $mediaItem->thumbnailURI,
				"previewURI" => $mediaItem->previewURI,
				"fullURI" => $mediaItem->previewURI,
				"geoTime" => array("geoLat" => $geoLat,
						   "geoLong" => $geoLon,
						   "timeBegin" => $kmlBegin,
						   "timeEnd" => $kmlEnd));
	    
	    
	    $itemResults[] = $resultItem;
	}//end loop
    
	return $itemResults;    
    }//end function

}//end class
