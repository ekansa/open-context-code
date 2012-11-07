<?php

/*
this class creates a standard object that can be indexed by Solr
It is indended to be as generalized as feasible,
*/
class OpenContextItem {
    
    public $itemUUID; //single item id main document id.
    public $itemLabel; //item lable
    public $labelSort; //item label sort order
    public $documentType; //singe item, string
    public $projectUUID; //singe item id, string
    public $projectName; //single item name, string
    
    public $pubDate; //date of publication of the item
    public $update; //date of last content update for the item

    public $classes; //array of item classes
    public $defaultContextArray; //array of default context path, for most emphasized search parameter (slash seperated)
    public $defaultContextPath; //string of context
    
    public $linkedPersons; //array of linked persons (that will be search criteria)
    public $linkedPersonURIs; //array of URIs for linked persons
    public $docLinkNum; //number of links to documents
    public $otherLinkNum; //number of links to non-image media
    public $imageLinkNum; //number of links to image media
    
    public $propHashArray; //array of properties unique hash values
    public $properties; //array of properties
    public $taxonPaths; //array of property / taxonomy paths
    public $variables; //array of variables
    public $mesUnits; //array of units of measurement
    
    public $alphaNotes; //array of alphanumeric notes used for key word searches
    public $creators; //array of Dublin Core creators
    public $contributors; //array of Dublin Core contributors
    public $coverages; //array of Dublin Core coverages
    public $subjects; //array of Dublin Core key-words / subjects metadata
    
    public $license; //URI to Creative Commons or other license. CC-Zero OK too.
    
    public $userTags; //User generated tags
    public $userTagCreators; //User generated taggers

    /*
     $socialUse = array("item_views" => integer, "ext_refs" => integer);
    */
    public $socialUse; //array of social use data , see above
	
    
    public $interestScore; //score for interest of the item.
    
    /*
    Time Spans:
    $timeSpan = array("timeStart" => earliest date,
		      "timeEnd" => latest Date
		      "timeSpan" => earlydate :: latedate,
		      "chronoSet" => chrono tag set label,
		      "chronoTagger" => name of person making chronotag)
    
    earlydate :: latedate can be parsed to make more general facets.
    */
    public $chrono; //array (see above)
    public $chronoSelf; //boolean, was the item itself attributable to a time range (true)? or was it inherited (false)
    
    /*
    Time Spans:
    $timeSpan = array("lat" => geo latitude,
		      "lon" => geo longitude,
		      "geoTile" => geotile)
    
    geoTile useful for rapid facet counts on geography.
    */
    public $geo; //array (see above)
    public $geoSelf; //boolean, was the item itself given a geo location (true), or was it inherited (false)?
    public $geoPoly; //boolean, was the item given a geoPolygon (true)
    
    public $solrDocument; //solrDocument
    
    const maxZoom = 20; //max level of zoom
    const taxonDelim = "::"; //deliminator for taxonomies
    const chronoDelim = " "; //deliminator for chronological spans
    const geoDelim = " "; //deliminator for geographic lat / lons
    
    function initialize(){
	$this->classes = false;
	$this->defaultContextArray = false;
	$this->defaultContextPath = false;
	$this->linkedPersons = false;
	$this->linkedPersonURIs = false;
	$this->propHashArray = array();
	$this->properties = false;
	$this->taxonPaths = false;
	$this->variables = false;
	$this->mesUnits = false;
	$this->alphaNotes = false;
	$this->chrono = false;
	$this->chronoSelf = false;
	$this->geo = false;
	$this->geoSelf = false;
	$this->geoPoly = false;
	$this->creators = false;
	$this->contributors = false;
	$this->coverages = false;
	$this->subjects = false;
	$this->license = false;
	$this->docLinkNum = false;
	$this->otherLinkNum = false;
	$this->imageLinkNum = false;
	$this->userTags = false;
	$this->userTagCreators = false;
	$this->socialUse = false;
	$this->interestScore = false;
	$this->labelSort = false;
    }
    
    
    
    /*
     add item to the appropriate array
     does some simple validation
    */
    function addSimpleArrayItem($item, $arrayType){
	$item = trim($item);
	
	$okTypes = array("linkedPersons",
			 "userTags",
			 "userTagCreators",
			 "creators",
			 "contributors",
			 "subjects",
			 "coverages",
			 "classes",
			 "alphaNotes");
	
	if(in_array($arrayType, $okTypes)){
	    $actArray = $this->$arrayType;
	    if(!$actArray){
		$actArray = array();
	    }
	    if(!in_array($item, $actArray)){
		$actArray[] = $item;
		$this->$arrayType = $actArray;
	    }
	    
	}
    }
    
    
    function addGeo($lat, $lon){
	$lat = $lat + 0;
	$lon = $lon + 0;
	
	$geoObj = new GlobalMapTiles;
	$zoom = 20;
	$geoTilePath = $geoObj->LatLonToQuadTree($lat, $lon, $zoom);
	$i=0;
	$geoTilePathArray = array();
	while($i < $zoom){
	    $geoTilePathArray[] = substr($geoTilePath, $i, 1);
	    $i++;
	}
	
	$geoTileArray = array();
	$firstLoop = true;
	$tilePath = "root";
	foreach($geoTilePathArray as $tile){
	    
	    $actTile = array("field" => $tilePath, "value"=> $tile);
	    
	    if($firstLoop){
		$tilePath = $tile;
	    }
	    else{
		$geoTileArray[] =  $actTile ;
		//$tilePath .= "_".$tile;
		$tilePath .= $tile;//uses an underscore, so we don't have to worry about escaping solr
	    }
	    $firstLoop = false;
	}
	
	$geoArray = array("lat" => $lat,
			  "lon" => $lon,
			  "geoTile" => array("path"=>$geoTilePath,
					     "tiles"=>$geoTileArray)
			  );
	$this->geo = $geoArray;
    }
    
    
    function addChrono($start, $end, $chronoTagger= false, $chronoSet = false){
	
	$start = $start + 0;
	$end = $end + 0;
	if($end < $start){ //insure that the start time is earlier than the end time
	    $temp = $start;
	    $start = $end;
	    $end = $temp;
	}
	
	$seperator = self::chronoDelim;
	$timeSpan = $start.$seperator.$end;
	$time = array("timeStart" => $start,
		      "timeEnd" => $end,
		      "timeSpan" => $timeSpan,
		      "chronoTagger" => $chronoTagger,
		      "chronoSet" => $chronoSet
		      );
	$this->chrono = $time;
    }
    
    
    function addDefaultContext($contextArray){
	
	if(!is_array($contextArray)){
	    $this->defaultContextPath = false;
	    $this->defaultContextArray = false;
	}
	else{
	    $j = 0; //used to generate 'def_context_*' fields in solr
	    $default_context_path = "";
	    $defaultContextArray = array();
	    foreach($contextArray as $context){
		$context = trim($context);
		$dynamicDefaultContextField = "def_context_" . $j;
		$default_context_path .= $context . "/";
		$defaultContextArray[$j] = array("field"=>$dynamicDefaultContextField, "value"=>$context);
	    $j++;
	    }
	    
	    $this->defaultContextPath = $default_context_path;
	    $this->defaultContextArray = $defaultContextArray;
	}
	
    }
    
    
    /* This function finds numeric characters in a name
     so that they can be used for sorting
    */
    function explodeX($delimiters,$string){
	$return_array = Array($string); // The array to return
	$d_count = 0;
	while (isset($delimiters[$d_count])) // Loop to loop through all delimiters
	{
	    $new_return_array = Array(); 
	    foreach($return_array as $el_to_split) // Explode all returned elements by the next delimiter
	    {
		$put_in_new_return_array = explode($delimiters[$d_count],$el_to_split);
		foreach($put_in_new_return_array as $substr) // Put all the exploded elements in array to return
		{
		    $new_return_array[] = $substr;
		}
	    }
	    $return_array = $new_return_array; // Replace the previous return array by the next version
	    $d_count++;
	}
	return $return_array; // Return the exploded elements
    }


    //name sorting with MB functions
    function nameSorter($name){
	
	$delimiters = array(" ",
				    ",",
				    ":",
				    "|",
				    "-",
				    "_",
				    "/");
	
	
	$doSingleDecimal = true;
	if(mb_substr_count($name, ".")>1){
	    $output = 0;
	    $dotParts = explode(".", $name);
	    $level = 0;
	    $allParts = "";
	    foreach($dotParts as $dotPart){
		$partOutput = mb_ereg_replace("[^0-9]","",$dotPart);
		if($partOutput != false){
		    $doSingleDecimal = false;
		    if($level == 0){
			$output = $partOutput + 0;
			$allParts = "";
		    }
		    else{
			$allParts .= $partOutput;
			$partLen = strlen($allParts);
			$output = $output + ($partOutput / pow(10, $partLen));
		    }
		}
	    $level++;
	    }
	}
		
	if($doSingleDecimal){
	    $outputAlpha = mb_ereg_replace("(?!^0*$)(?!^0*\.0*$)^\d{1,5}(\.\d{1,3})","",$name);
	    $output = str_replace($outputAlpha, "", $name);
	    if(strlen($output)>0){
		if(mb_stristr($name, $output)){
		    $output = $output +0;
		    $singleDecimal = true;
		}
		else{
		    $output = false;
		}
		//echo "existing output: ".$output;
	    }
	}
	
	if($output == false){
	    $output = mb_ereg_replace("[^0-9]","",$name);
	}
	
	if($output != false){
	    $output = "0000000000".$output;
	    $output = $output + 0;	
	    if($output == 0){
		$nameExp = $this->explodeX ($delimiters, $name);
		if(is_array($nameExp)){
		    $i = 1;
		    foreach($nameExp as $namePart){
			$output = $output + (ord($namePart) * $i);
			$i = $i * .1;
		    }
		}
		else{
		    $output = ord($name);
		}
	    }
	}
	
	return $output;
    }


//name sorting with NO MB functions
    function nameSorter_noMB($name){
	
	$delimiters = array(" ",
				    ",",
				    ":",
				    "|",
				    "-",
				    "_",
				    "/");
	
	
	$doSingleDecimal = true;
	if(substr_count($name, ".")>1){
	    $output = 0;
	    $dotParts = explode(".", $name);
	    $level = 0;
	    $allParts = "";
	    foreach($dotParts as $dotPart){
		$partOutput = ereg_replace("[^0-9]","",$dotPart);
		if($partOutput != false){
		    $doSingleDecimal = false;
		    if($level == 0){
			$output = $partOutput + 0;
			$allParts = "";
		    }
		    else{
			$allParts .= $partOutput;
			$partLen = strlen($allParts);
			$output = $output + ($partOutput / pow(10, $partLen));
		    }
		}
	    $level++;
	    }
	}
		
	if($doSingleDecimal){
	    @$outputAlpha = ereg_replace("(?!^0*$)(?!^0*\.0*$)^\d{1,5}(\.\d{1,3})","",$name);
	    $output = str_replace($outputAlpha, "", $name);
	    if(strlen($output)>0){
		if(stristr($name, $output)){
		    $output = $output +0;
		    $singleDecimal = true;
		}
		else{
		    $output = false;
		}
		//echo "existing output: ".$output;
	    }
	}
	
	if($output == false){
	    $output = ereg_replace("[^0-9]","",$name);
	}
	
	if($output != false){
	    $output = "0000000000".$output;
	    $output = $output + 0;	
	    if($output == 0){
		$nameExp = $this->explodeX ($delimiters, $name);
		if(is_array($nameExp)){
		    $i = 1;
		    foreach($nameExp as $namePart){
			$output = $output + (ord($namePart) * $i);
			$i = $i * .1;
		    }
		}
		else{
		    $output = ord($name);
		}
	    }
	}
	
	return $output;
    }

    
    //add alphanumeric variable / value
    function addAlphaVarVal($var, $value){
	$var = trim($var);
	$value = trim($value);
	$variables = $this->variables;
	if(!$variables){
	    $variables = array();
	}
	$varValKey = sha1($var.self::taxonDelim.$value);
	if(!array_key_exists($varValKey, $variables)){
	    $variables[$varValKey] = array("var" => $var, "val" => $value);
	}
	$this->variables = $variables;
    }
    

    //add property to be indexed, for faceted search
    
    
    
    //turn a taxonomy array into a full path
    function addfullPropertyPath($taxonomyArray){
	$taxonPaths = $this->taxonPaths;
	if(!is_array($taxonPaths)){
	    $taxonPaths = array();
	}
	
	if(!is_array($taxonomyArray)){
	    $newArray = array();
	    $newArray[] = $taxonomyArray;
	    $taxonomyArray = $newArray;
	    unset($newArray);
	}
	
	$actPath = "";
	$i = 0;
	foreach($taxonomyArray as $taxon){
	    
	    $taxon = trim($taxon);
	    if(stristr($taxon, self::taxonDelim)){
		//$taxon = str_replace(self::taxonDelim, urlencode(self::taxonDelim), $taxon);
	    }
	    
	
	    if($i == 0){
		$actPath = $taxon;
	    }
	    else{
		$actPath .= (self::taxonDelim).$taxon;
	    }
	
	$i++;   
	}
	
	$taxonPaths[] = $actPath;
	$this->taxonPaths = $taxonPaths;
    }//end function
    
    
    
    function addProperty($value, $parentArray = false, $setType = false, $addVars = true){
		//$value is the value for this specific property
		//$parent array is the value for parents of this value, up different levels in the hierarchy
		
		$value = trim($value);
		$type = false;
		
		//figure out if the value is integer, decimal, or calendar. if not, default to nominal
		if(is_numeric($value)){
			if(intval($value) === $value){
				$int_ok = true;
			}
			elseif(round($value,0) == $value +0){
				$int_ok = true;
			}
			else{
				$int_ok = false;
			}
			if((is_int($value))||($int_ok)){
				$type = "integer";
			}
			else{
				$type = "decimal";
			}
		}
			
		if(!$type){
			$cal_test_string = str_replace("/", "-", $value);
			
			if (($timestamp = strtotime($cal_test_string)) === false) {
			$calendardTest = false;
			}
			else{
			$calendardTest = true;
			}
			
			if($calendardTest){
			$type = "calendar";
			}
			else{
			$type = "nominal"; 
			}
		}
		
		
		
		$properties = $this->properties;
		if(!$properties){
			$properties = array();
		}
		$variables = $this->variables;
		if(!$variables){
			$variables = array();
		}
		
		if(!$parentArray || count($parentArray) == 0){
			$parentArray = false;
			$parentPath = "";
			
			if($setType != "standard" && $setType != "reconciled"){
			    $hashPath = "top";
			}
			else{
			    if($setType == "standard"){
				$hashPath = "standard"; //for standard units of measurement
			    }
			    elseif($setType =="reconciled"){
				$hashPath = "reconciled"; //for reconciled propeties to a 
			    }
			    $setType = "nominal";
			}
		}
		else{
			
			if(!is_array($parentArray)){
				$newArray = array();
				$newArray[] = $parentArray;
				$parentArray = $newArray;
				unset($newArray);
			}
			
			$parentPath = "";
			foreach($parentArray as $parent){
			
				$parent = trim($parent);
				
				if(strlen($parentPath)<1){
					$addString = $parent;
				}
				else{
					$addString = (self::taxonDelim).$parent;
				}
				
				$parentPath .= $addString;
			}//end loop.
			$hashPath = sha1($parentPath);
		}
		
		$actProp = array("value" => $value,
			"type" => $type,
			"path" => $parentPath,
			"hashPath" => $hashPath,
			"setType" => $setType,
			"parentArray" => $parentArray
			);
		
		
		if($addVars && strlen($parentPath)>0){
			//sometimes you don't want to add variables, so this is optional
			$addVars = true;
			foreach($variables as $oldvar){
			    if($oldvar["var"] == $parentPath && $oldvar["val"] == $value){
				$addVars = false;
			    }
			}
			if($addVars){
			    $varValKey = sha1($parentPath.self::taxonDelim.$value);
			    if(!array_key_exists($varValKey, $variables)){
				$variables[$varValKey] = array("var" => $parentPath, "val" => $value);
			    }
			}
			$this->variables = $variables;
		}
		
		$actPropHash = sha1(Zend_Json::encode($actProp));
		$propHashArray = $this->propHashArray;
		
		//only add unique properties
		if(!in_array($actPropHash, $propHashArray)){
			$propHashArray[] = $actPropHash;
			$properties[] = $actProp;
			$this->properties = $properties;
			$this->propHashArray = $propHashArray;
		}
	
    }


    
    
    /*
	This function adds properties for linked data.
	For each target URI, there will be two properties added. One that enables
	searches for the target uri, then returns facets of link types.
	The second property will start with link types, then return target uris as facets.
	
	We make all of these properties because it facilitates searches, a user can search
	for a target entity URI even without a known relationship. Or a user can do searches based on the URI of the relationship
    */
    function addURIreference($relURI, $targURI){
		$properties = $this->properties;
		$propHashArray = $this->propHashArray;
		
		
		if(!stristr($relURI, "http://") || !stristr($targURI, "http://")){
			return false; // don't accept linked data refs without http uris
		}
	
	
		if(!$properties){
		    $properties = array();
		}
		$variables = $this->variables;
		if(!$variables){
		    $variables = array();
		}
	
		//first create a root property for the link relationship
		$path = "linkRel";
		$linkProp = array("value" => $relURI,
				"type" => "linkRel",
				"path" => $path,
				"hashPath" => "top_lrel",
				"parentArray" => false
				  );
	
		//only add unique properties
		$actPropHash = sha1(Zend_Json::encode($linkProp));
		if(!in_array($actPropHash, $propHashArray)){
			$propHashArray[] = $actPropHash;
			$properties[] = $linkProp;
			$this->properties = $properties;
			$this->propHashArray = $propHashArray;
		}
	
		//first create a root property for the link relationship
		$path = $targURI;
		$linkProp = array("value" => $relURI,
				"type" => "linkRel",
				"path" => $path,
				"hashPath" => sha1($path)."_lrel",
				"parentArray" => array(0=>$targURI)
				  );
	
		//only add unique properties
		$actPropHash = sha1(Zend_Json::encode($linkProp));
		if(!in_array($actPropHash, $propHashArray)){
			$propHashArray[] = $actPropHash;
			$properties[] = $linkProp;
			$this->properties = $properties;
			$this->propHashArray = $propHashArray;
		}
	
	
	
		//next create a root property for the target URI
		$path = "linkEnt";
		$linkProp = array("value" => $targURI,
				"type" => "linkEnt",
				"path" => $path,
				"hashPath" => "top_lent",
				"parentArray" => false
				  );
	
		//only add unique properties
		$actPropHash = sha1(Zend_Json::encode($linkProp));
		if(!in_array($actPropHash, $propHashArray)){
			$propHashArray[] = $actPropHash;
			$properties[] = $linkProp;
			$this->properties = $properties;
			$this->propHashArray = $propHashArray;
		}
	
	
	
		//next create a root property for the target URI
		$path = $relURI;
		$linkProp = array("value" => $targURI,
			"type" => "linkEnt",
			"path" => $path,
			"hashPath" => sha1($path)."_lent",
			"parentArray" => array(0=>$relURI)
			);
	
		$actPropHash = sha1(Zend_Json::encode($linkProp));
		if(!in_array($actPropHash, $propHashArray)){
			$propHashArray[] = $actPropHash;
			$properties[] = $linkProp;
			$this->properties = $properties;
			$this->propHashArray = $propHashArray;
		}
		
		$varValKey = sha1("Rel: ".$relURI.self::taxonDelim.$targURI);
		if(!array_key_exists($varValKey, $variables)){
		    $variables[$varValKey] = array("var" => "Rel: ".$relURI,
			     "val" => $targURI);
		}
		$this->variables = $variables;
    }
    


    

    
    
    
    //preliminary interst score calculate
    function interestCalc($socialData = false){
		  $interest_score = $this->interestScore; //default to false / 0.
		  
		  $number_properties = 0;
		  $total_character_length_note = 0;
		  
		  if(!$socialData){
				$socialData = $this->socialUse;
		  }
		  
		  //get data about properties, among of text in properties
		  if(is_array($this->variables)){
				$number_properties = count($this->variables);
				foreach($this->variables as $varVal){
			  $total_character_length_note += strlen($varVal["var"]) + strlen($varVal["val"]);
				}
		  }
		  
		  if(is_array($this->alphaNotes)){
				foreach($this->alphaNotes as $note){
			  $total_character_length_note += strlen($note);
				}
		  }
		  
		  $numPersons = 0;
		  if(is_array($this->linkedPersons)){
				$numPersons = count($this->linkedPersons);
		  }
		  
		  $interest_score += $number_properties; 
		  $interest_score += ($total_character_length_note / 100); 
		  $interest_score += ($this->imageLinkNum * 4);
		  $interest_score += ($this->otherLinkNum * 4);
		  $interest_score += ($this->docLinkNum * 2); 
		  $interest_score += ($numPersons * .5); 
		  
		  
		  if($this->geoSelf){ 
				$interest_score += 4;
				if($this->geoPoly){  
					 $interest_score += 4;
				}
		  }
		  
		  if(is_array($this->userTags)){
				$interest_score += count($this->userTags) * 1.5;
		  }
		  
		  if(is_array($socialData)){
				if(array_key_exists("item_views", $socialData)){
					 $interest_score += $socialData["item_views"] / 100;
				}
			  if(array_key_exists("ext_refs", $socialData)){
					 $interest_score += $socialData["ext_refs"] * 2;
				}
		  }
		  
		  $this->interestScore = $interest_score;
	 }//end function
			
			
			
	 /*
	 make nice variables
	 */
	 function remove_redundant_parent_taxa_from_variables(){
		  $variables = $this->variables;
		  $delimCounts = array();
		  $sortOrder = array();
		  $i =0;
		  foreach($variables as $varVal){
				$var = trim($varVal['var']);
				$delimCounts[$var] = substr_count($var, self::taxonDelim);
				$sortOrder[] = $var;
		  }
		  
		  krsort($delimCounts);
		  
		  //echo print_r($delimCounts);
		  
		  $finalVars = array();
		  foreach($delimCounts as $varKey => $delimCount){
				
				$addVar = true;
				$checkKey = $varKey.self::taxonDelim;
				$checkKeyLen = strlen($checkKey);
				
				if(count($finalVars) > 0){
					 foreach($finalVars as $addedVar){   
						  $varSegment = substr($addedVar, 0, $checkKeyLen);
						  if($varSegment == $checkKey){ //don't add to the final variable list if variable is a part of the path of an existing item
								$addVar = false;
						  }
					 }
				}
				
				if($addVar){
					 $finalVars[] = $varKey;
				}
		  }//end loop
		  
		  //now sort them
		  $finalOrderedVars = array();
		  foreach($sortOrder as $actVar){
				if(in_array($actVar, $finalVars)){
					 $finalOrderedVars[] = $actVar;
				}
		  }
	  
		  return $finalOrderedVars;
    }//end function
    
    
    
    /*
    Use this to sort by last name, this assumes no spaces
    */
    function alphaNameSortOrder($alphaName){
	
		  $alphaName = strtoupper($alphaName);
		  $alphaName  = trim($alphaName);
		  
		  $nameLen = strlen($alphaName);
		  if($nameLen >6){
				$nameLen = 6;
		  }
		  
		  $i=0;
		  $sort = 0;
		  while($i < $nameLen){
				$character = substr($alphaName, $i, 1);
				$ordVal = ord($character)-64;
				//echo "$character($ordVal)";
				
				if($i >0){
			  $ordVal = ($ordVal / 26);
			  $ordVal = $ordVal / pow(10,$i);
				}
				//echo "($ordVal) ";
				
				$sort =  $sort + $ordVal;
				
		  $i++;
		  }
		  
		  $this->labelSort = $sort;
    }
    
    
    
    /*
    This function makes a solr document for a generic Open Context item 
     
    
    */
    function makeSolrDocument($solrDocument = false){
	
		  if(!$solrDocument){
				 $solrDocument = new Apache_Solr_Document();
		  }
		  
		  //$solrDocument->id = $this->itemUUID;
		  $solrDocument->uuid = $this->itemUUID;
		  $solrDocument->item_label = $this->itemLabel;  //primary label for item
		  
		  if((!$this->labelSort) || !is_numeric($this->labelSort)){
				
				if(function_exists("mb_substr_count")){
					 $sort = $this->nameSorter($this->itemLabel); //generate sort based on item label
				}
				else{
					 $sort = $this->nameSorter_noMB($this->itemLabel); //generate sort based on item label, no multibyte working
				}
				
				if(!is_numeric($sort)){
					 $sort = ord($this->itemLabel);
				}
				if(!is_numeric($sort)){
					 $sort = 0;
				}
				$solrDocument->label_sort = $sort; //generate sort based on item label
		  }
		  else{
				$solrDocument->label_sort = $this->labelSort; //sort on this score
		  }
		  
		  
		  $solrDocument->pub_date = $this->pubDate; //publication date. this is only created once
		  $solrDocument->update = $this->update; //last date of significant update, a significant update is a change on the item
		  
		  /*
		  $solrDocument->pubdateNum = strtotime($this->pubDate); //publication date. this is only created once
		  $solrDocument->updateNum = strtotime($this->update); //last date of significant update, a significant update is a change on the item
		  */
		  
		  $solrDocument->project_name = $this->projectName;
		  $solrDocument->project_id = $this->projectUUID;
		  $solrDocument->item_type = $this->documentType; //type of document (spatial, media, etc.)
		  
		  
		  /*
		  Default Contexts: Default contexts are the most important / signifiant hierarchy in a collection's taxonomy
		  They are slash ("/") seperated values, and used to make pretty URLs
		  */
		  if(!$this->defaultContextPath){
				$solrDocument->default_context_path = "ROOT";
		  }
		  else{
				$solrDocument->default_context_path = $this->defaultContextPath;
		  }
		  
		  if(is_array($this->defaultContextArray)){
				foreach($this->defaultContextArray as $contextItem){
					 $solrField = $contextItem["field"];
					 $solrValue = $contextItem["value"];
					 $solrDocument->$solrField = $solrValue;
				}
		  }
		  
		  /*
		  
		  UPDATE linked_data
		  SET linkedURI = REPLACE(linkedURI, 'http://www.eol.org/', 'http://eol.org/')
		  
		  
		  */
		  
		  /*
		  This enables indexing of hierarchic taxonomies, these are used for faceted searches
		  */
		  if(is_array($this->properties)){
				foreach($this->properties as $prop){
					 $value = $prop["value"];
					 $prefix = $prop["hashPath"];
					 
					 if(!array_key_exists('setType', $prop)){
						  $prop['setType'] = "nominal";
					 }
					 
					 if($prop['type'] == "integer" && $prop['setType'] == "integer"){
						  //$solrDocument->setMultiValue($prefix."_tax_int_hr", $value); //human readable variant
						  $suffix = "_tax_int";
					 }
					 elseif($prop['type'] == "decimal" && $prop['setType'] == "decimal"){
						  //$solrDocument->setMultiValue($prefix."_tax_dec_hr", $value); //human readable variant
						  $suffix = "_tax_dec";
					 }
					 elseif($prop['type'] == "integer" && $prop['setType'] == "decimal"){
						  //$solrDocument->setMultiValue($prefix."_tax_dec_hr", $value); //human readable variant
						  $suffix = "_tax_dec";
					 }
					 elseif($prop['type'] == "decimal" && $prop['setType'] == "integer"){
						  //$solrDocument->setMultiValue($prefix."_tax_int_hr", $value); //human readable variant
						  $solrDocument->setMultiValue($prefix."_tax_int", round($value,0));
						  $suffix = "_tax_dec";
					 }
					 elseif($prop['type'] == "calendar" && $prop['setType'] == "calendar" ){
						  $value = date("Y-m-d\TH:i:s\Z", strtotime($value));
						  //$value = str_replace("Z", ".000Z",  $value);
						  $suffix = "_tax_cal";
					 }
					 elseif(($prop['setType'] == "integer") && $prop['type'] == "nominal"){
						  $suffix = "_int_taxon";
					 }
					 elseif(($prop['setType'] == "decimal") && $prop['type'] == "nominal"){
						  $suffix = "_dec_taxon";
					 }
					 elseif(($prop['setType'] == "calendar") && $prop['type'] == "nominal"){
						  $suffix = "_cal_taxon";
					 }
					 elseif($prop['setType'] == "alphanumeric"){
						  $suffix = "_tax_alpha";
					 }
					 else{
						  $suffix = "_taxon";
					 }
					 $taxonField = $prefix.$suffix;
					 $solrDocument->setMultiValue($taxonField, $value);
				}
		  }
		  
		  /*
		  This is used to index different variables and associated values, these are mainly used for table outputs
		  */
		  if(is_array($this->variables)){
				$keyVarArray = array();
			  
				$niceVariables = $this->remove_redundant_parent_taxa_from_variables(); // removes parent taxon levels, leaves only full taxon
				foreach($niceVariables as $niceVar){
					 $solrDocument->setMultiValue("variables", $niceVar); //index list of variable names
					 foreach($this->variables as $varVal){
						  $var = trim($varVal['var']);
						  $val = trim($varVal['val']);
						  if($niceVar == $var){
								$keyVarArray[$var] = $val;
						  }
					 }
				}
				
				$solrDocument->var_vals = Zend_Json::encode($keyVarArray);
				foreach($keyVarArray as $varKey => $val){
					 $solrDocument->setMultiValue("notes", $varKey.": ".$val); //add notes for full-text searches
				}
		  }
		  
		  
		  
		  
		  
		  
		  if(is_array($this->classes)){
				foreach($this->classes as $class){
					 $solrDocument->setMultiValue("item_class", $class); //class of item (can have multiple)
				}
		  }
		  
		  if(is_array($this->linkedPersons)){
				foreach($this->linkedPersons as $person){
					 $solrDocument->setMultiValue("person_link", $person);
				}
		  }
		  
		  if(is_array($this->linkedPersonURIs)){
				foreach($this->linkedPersonURIs as $personURI){
					 $solrDocument->setMultiValue("person_uri", $personURI);
				}
		  }
			
		  if(is_array($this->alphaNotes)){
				foreach($this->alphaNotes as $note){
					 $solrDocument->setMultiValue("notes", $note); //add notes for full-text searches
				}
		  }
		  
		  
			
		  //take care of geographic data
		  if(!$this->geo){
				$solrDocument->geo_lat = 0; //no geo data, stick in Atlantic
				$solrDocument->geo_long = 0; //no geo data, stick in Atlantic
				$solrDocument->geo_point = "0,0"; //geo point
				$solrDocument->geo_coord = "0,0"; //geo point
		  }
		  else{
				$geoData = $this->geo;
				$solrDocument->geo_lat = $geoData['lat']; //geo data, latitude
				$solrDocument->geo_long = $geoData['lon']; //geo data, longitude
				
				$geoPoint = "0,0";
				if(isset( $geoData['lat']) && isset( $geoData['lon'])){
					 $geoPoint = $geoData['lat'].",".$geoData['lon']; //geo point
				}
				
				$solrDocument->geo_coord = $geoPoint; //geo point, for geospatial queries
				$solrDocument->geo_point = $geoPoint; //geo point, for facets
				$solrDocument->geo_path = $geoData["geoTile"]["path"]; //geo path
				$i = 1;
				foreach($geoData["geoTile"]["tiles"] as $tileArray){
					 //$dynamicGeoTileField = $i."_geo_tile";
					 $dynamicGeoTileField = $tileArray["field"]."_geo_tile";
					 $tile = $tileArray["value"];
					 $solrDocument->$dynamicGeoTileField = $tile;
					 if($i >= self::maxZoom){
						  break; //stop, no need to make tiles deeper than this
					 }
					 $i++;
				}
		  }
			
		  //take care of chronological data
		  if($this->chrono != false){
				$chrono = $this->chrono;
				$solrDocument->setMultiValue("time_start", $chrono["timeStart"]);
				$solrDocument->setMultiValue("time_start_hr", $chrono["timeStart"]);
				$solrDocument->setMultiValue("time_end", $chrono["timeEnd"]);
				$solrDocument->setMultiValue("time_end_hr", $chrono["timeEnd"]);
				$solrDocument->setMultiValue("time_span", $chrono["timeSpan"]);
				
				if($chrono["chronoTagger"] != false){
				  //$solrDocument->setMultiValue("chrono_creator_name", $chrono["chronoTagger"]); //chrono tag creator
				}
				if($chrono["chronoSet"] != false){
				  //$solrDocument->setMultiValue("chrono_set_label", $chrono["chronoSet"]); //chrono tag creator
				}
		  }
			
		  //other Dublin Core metadata
		  if(is_array($this->creators)){
				foreach($this->creators as $creator){
					 $solrDocument->setMultiValue("creator", $creator);
				}
		  }
		  if(is_array($this->contributors)){
				foreach($this->contributors as $contributor){
					 $solrDocument->setMultiValue("contributor", $contributor);
				}
		  }
		  if(is_array($this->coverages)){
				foreach($this->coverages as $coverage){
					 $solrDocument->setMultiValue("coverage", $coverage);
				}
		  }
		  if(is_array($this->subjects)){
				foreach($this->subjects as $subject){
					 $solrDocument->setMultiValue("subject", $subject);
				}
		  }
		  if($this->license != false){
				$solrDocument->license_uri = $this->license;
		  }
		  
		  //media counts
		  if($this->imageLinkNum != false){
				$solrDocument->image_media_count = $this->imageLinkNum;
		  }
		  else{
				$solrDocument->image_media_count = 0;
		  }
		  if($this->otherLinkNum != false){
				$solrDocument->other_binary_media_count = $this->otherLinkNum;
		  }
		  else{
				$solrDocument->other_binary_media_count = 0;
		  }
		  if($this->docLinkNum != false){
				$solrDocument->diary_count = $this->docLinkNum;
		  }
		  else{
				$solrDocument->diary_count = 0;
		  }
		  
		  //user generated tags
		  if(is_array($this->userTags)){
				foreach($this->userTags as $tag){
				  //$solrDocument->setMultiValue("user_tag", $tag);
				}
		  }
		  if(is_array($this->userTagCreators)){
				foreach($this->userTagCreators as $tagCreator){
				  //$solrDocument->setMultiValue("tag_creator_name", $tagCreator);
				}
		  }
		  
		  if(!$this->interestScore){
				$this->interestScore = $this->interestCalc(); //if no interest score, calculate it
		  }
		  if(!$this->interestScore){
				$this->interestScore = 0; //if no interest score, calculate it
		  }
		  $solrDocument->interest_score = $this->interestScore; //interest score, for complicated ranking of results
		  
		  return $solrDocument;
    }//end function for making new solr document
    
    
    
    
    
    
    
}
