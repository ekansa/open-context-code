<?php

class OpenContext_GoogleEarth {
	
	public static function geo_max_distance($lat_array, $lon_array){
	
		$max_dist = 0;
		$item_count = count($lat_array);
		
		if($item_count != count($lon_array)){
			$error = true;
		}//skip function in error
		else{
			$error = false;
		}
		
		
		$i=0;
		while($i<$item_count){
		
			$act_lat = $lat_array[$i];
			$act_lon = $lon_array[$i];
		
			$j = $i + 1;
			
			while($j<$item_count){	
					
				$dif_lat = $act_lat - $lat_array[$j];
				$dif_lon = $act_lon - $lon_array[$j];
				
				$act_dist_sq = ($dif_lat*$dif_lat) + ($dif_lon*$dif_lon);
				
				if($act_dist_sq > $max_dist){
					$max_dist = $act_dist_sq;
				}
				
			$j++;
			}//end inner loop
		
		$i++;
		}//end outer loop
		
		
		if(($max_dist>0)&&(!$error)){
			$max_dist = sqrt($max_dist);
			return $max_dist;
		}
		elseif(($max_dist==0)&&(!$error)){
			return 0;
		}
		elseif($error){
			return false;
		}
	
	}//end function, returns false if there's a problem



	//this function sets the color of for a given context's kml shape
	//the color ranges from blue to red, from high values to low values
	public static function kml_set_color($act_fac_count, $max_count){
		
		$act_portion = round((($act_fac_count/$max_count)*255),0); // varies from 0 to 255, for hex color generation
		$alpha = 180 + round(($act_portion*.2),0); //alpha, transparency values in decimal form
		$blue = 255 - $act_portion; //blue color value in decimal form
		$red = $act_portion; //RED color value in decimal form 
		
		$green = 0;
		
		$kml_color = sprintf('%02X%02X%02X%02X', $alpha, $blue, $green, $red);
		
		return $kml_color;
	
	}//end kml_set_color function


	
	
	
	public static function kml_set_height($act_fac_count, $max_count, $max_distance){
	
		if($max_distance == 0){
			
			$proportion_height = $act_fac_count/$max_count*2500;
			
			//$new_height = round(($act_fac_count*.0125),0);
			$new_height = round(($proportion_height*.0125),0);
			
			if($new_height<1){
				$new_height = 1;
			}
			
		}
		else{
			$new_height = ($act_fac_count/$max_count)*$max_distance*15725*1.5;
			$new_height = round($new_height,0);
		}
		
		return $new_height;
	}//end kml_set_height function
	
	
	//this function updates the KML for an item to use height to visualize 
	//quantity of items
	//it greates square polygons from points with handy sizing to show quantities
	public static function kml_gen_polypoints($square_size, $lon_offset, $act_lat, $act_lon, $context_height){
		
		$max_lon = $act_lon + ($square_size/2)+$lon_offset;
		$min_lon = $act_lon - ($square_size/2)+$lon_offset;
		$max_lat = $act_lat + ($square_size/2);
		$min_lat = $act_lat - ($square_size/2);
		
		$new_poly = $min_lon.",".$max_lat.",".$context_height." ";
		$new_poly .= $min_lon.",".$min_lat.",".$context_height." ";
		$new_poly .= $max_lon.",".$min_lat.",".$context_height." ";
		$new_poly .= $max_lon.",".$max_lat.",".$context_height." ";
		
		$meanLon = ($min_lon + $max_lon)/2;
		$meanLat = ($min_lat + $max_lat)/2;
		
		$context_height_plus = $context_height + ($context_height/200);
		$new_point = $meanLon.",".$meanLat.",".$context_height_plus;
		
		$output = array();
		$output["poly"] = $new_poly;
		$output["point"] = $new_point;
		
		return $output;
	}//end function kml_gen_polypoints


	/* This function generates kml polygons in cases where
	  there are multiple contexts that have no specific locational information given.
	  
	  The kml_make_offsets function helps position the polygons appropriately.
	*/
	public static function kml_gen_polygon_points($square_size, $act_rank, $contextCount, $act_lat, $act_lon, $context_height){
		
		$offsets = array();
		$offsets = OpenContext_GoogleEarth::kml_make_offsets($square_size, $act_rank, $contextCount);
		
		$max_lon = $act_lon + ($square_size/2)+$offsets["lon_offset"];
		$min_lon = $act_lon - ($square_size/2)+$offsets["lon_offset"];
		$max_lat = $act_lat + ($square_size/2)+$offsets["lat_offset"];
		$min_lat = $act_lat - ($square_size/2)+$offsets["lat_offset"];
		
		$new_poly = $min_lon.",".$max_lat.",".$context_height." ";
		$new_poly .= $min_lon.",".$min_lat.",".$context_height." ";
		$new_poly .= $max_lon.",".$min_lat.",".$context_height." ";
		$new_poly .= $max_lon.",".$max_lat.",".$context_height." ";
		
		$meanLon = ($min_lon + $max_lon)/2;
		$meanLat = ($min_lat + $max_lat)/2;
		
		$context_height_plus = $context_height + ($context_height/200);
		$new_point = $meanLon.",".$meanLat.",".$context_height_plus;
		
		$output = array();
		$output["poly"] = $new_poly;
		$output["point"] = $new_point;
		
		return $output;
	}//end function kml_gen_polypoints



	/* This function is used to position generated kml polygons in cases where
	  there are multiple contexts that have no specific locational information given.
	  
	  The highest ranking context, meaning the context with the greatest facet count
	  is assigned to the north-east corner. The lowest ranking context goes to the
	  south-west corner. 
	*/
	public static function kml_make_offsets($square_size, $act_rank, $contextCount){
		
		if($contextCount>5){
			$square_sides = sqrt($contextCount);
			if($square_sides > round($square_sides,0)){
				$square_sides = round($square_sides,0) + 1;
				$center_y = ($square_sides - 1) / 2; 
			}
			else{
				$center_y = $square_sides / 2;
			}
			
			if(($act_rank / $square_sides) < round(($act_rank / $square_sides),0)){
				$y_value = round(($act_rank / $square_sides),0) - 1;
			}
			else{
				$y_value = round(($act_rank / $square_sides),0);
			}
			
			$x_value = $act_rank -($y_value * $square_sides); // where in the x (lon) direction
			$center_x = $square_sides / 2;
		}
		else{
			$y_value = 0;
			$x_value = $act_rank;
			$center_x = $contextCount / 2;
			$center_y = 0;
		}
		
		$lon_offset = ($x_value * $square_size) - ($center_x * $square_size);
		$lat_offset = ($y_value * $square_size) - ($center_y * $square_size);
		
		$output = array("lon_offset"=>$lon_offset, "lat_offset"=>$lat_offset);
		return $output;
		
	}//end function


	public static function get_context_description($default_context_path, $item_label){
	
		// handle root level items
		if (!$default_context_path) {
			$path_query = "default_context_path:ROOT + item_label:" . $item_label;
		} else {
			$path_query = "default_context_path:\"" . $default_context_path . "\" + item_label:\"" . $item_label . "\"";
		}
	
		// Connection to solr server
		$solr = new Apache_Solr_Service('localhost', 8983, '/solr');

		// test the connection to the solr server
		if ($solr->ping()) {
			try {
				$response = $solr->search($path_query, 0, 1, array (/* you can include other parameters here */));
				foreach (($response->response->docs) as $doc) {

					$atom = simplexml_load_string($doc->atom_full);
					if ($atom) {
						return $atom;
					} else {
						return false;
					}
				
				}
			} catch (Exception $e) {
				echo $e->getMessage(), "\n";
			}

		} else {
			return false;
		}
	
	}//end function



	public static function kml_from_gml_polypoints($lon_offset, $act_lat, $act_lon, $context_height){
	
	}//end function



	//this function generates a link for showing results as a percentage
	public static function makePercentLink($requestParams){
		$host = OpenContext_OCConfig::get_host_config();
		
		if(array_key_exists("prop", $requestParams)){
			$propArray = $requestParams["prop"];
			
			//get the last property where there is also a value,
			//this will be the property where percentages will most likely make sense
			$propKey = false;
			
			foreach($propArray as $ActPropKey => $ActPropVal){
				if(strlen($ActPropVal)>0){
					$propKey = $ActPropKey;
				}
			}
			
			if($propKey != false){
				$compVal = "prop::".$propKey;
				$requestParams["comp"] = $compVal;
				$percentLink_raw = $host.OpenContext_FacetOutput::generateFacetURL($requestParams, null, null, false, false, "facets_kml");
				$percentLink = OpenContext_GoogleEarth::makeFeatureAnchor($percentLink_raw);
				$output = array("link"=>$percentLink, "comp" => $propKey);
			}
			else{
				$output = false;
			}
			
		}
		else{
			$output = false;
		}
		
		return $output;
	}




	//this function is a hack to get over GoogleEarth's fussiness with Feature Anchors
	//this was needed to make sure that GoogleEarth can open Feature Anchors with parameters after ".kml"
	//otherwise, GoogleEarth goes into a tizzy
	public static function makeFeatureAnchor($facet_href){
		$host = OpenContext_OCConfig::get_host_config();
		
		$actURI = str_replace(".json", ".kml", $facet_href);

		/*
		$i = 0;
		$actLen = strlen($actURI);
		$codeURI = "";
		while($i<$actLen){
			$actChar = substr($actURI, $i, 1);
			if($i == 0){
				$codeURI = ord($actChar);
			}
			else{
				$codeURI .= "-".ord($actChar);
			}
		$i++;
		}
		*/
		
		$codeURI = OpenContext_GoogleEarth::storeLinkCode($actURI);
		$goodURI = $host."/sets/facets/__ge__".$codeURI."__ge__";
		$goodURI .= ".kml";
		return $goodURI; // will have format of http://opencontext.org/sets/facets/__ge__urlencodedcrap__ge__.kml;ballonFlyto
	}

	//decode Feature-anchor
	public static function decodeFeatureAnchor($requestURI, $requestParams){
		
		$host = OpenContext_OCConfig::get_host_config();
		$goodURI = $host.$requestURI;
		
		if(substr_count($requestURI, "__ge__")>1){
			$reqArray = explode("__ge__", $requestURI);
			$codedURI = $reqArray[1]; //after the first seperator
			
			/*
			$codedArray = explode("-", $codedURI);
			$goodURI = "";
			foreach($codedArray as $actCode){
				$goodURI .= chr($actCode);
			}
			*/
			$goodURI = OpenContext_GoogleEarth::getStoredLink($codedURI);
			$requestParams = OpenContext_GoogleEarth::make_decode_requestParams($goodURI, $requestParams);
		}
		
		
		return array("requestURI" =>$goodURI, "params"=> $requestParams);     //will have format of http://opencontext.org/sets/facets/x.kml?params
	}

	
	public static function make_decode_requestParams($goodURI, $requestParams){
		$cleanPath = array(".json", ".atom", ".kml", "/sets/facets/", "/sets/");
		
		$parse_params = parse_url($goodURI);
		//echo var_dump($parse_params);
		
		$default_context_path = false;
		if(array_key_exists("path", $parse_params)){
			$default_context_path = $parse_params["path"];
			//clean the path to turn it into a default context path
			foreach($cleanPath as $actRemove){
				$default_context_path = str_replace($actRemove, "", $default_context_path);	
			}
			$default_context_path = str_replace("+", " ", $default_context_path); //fix plus issue
			$default_context_path = str_replace("%7C%7C", "||", $default_context_path); //fix Pipes issue
		}
		$query = false;
		if(array_key_exists("query", $parse_params)){
			$query = true;
			parse_str($parse_params["query"], $queryArray);
		}
		
		if($default_context_path != false){
			$requestParams["default_context_path"] = $default_context_path;
		}
		else{
			unset($requestParams["default_context_path"]);
		}
		
		if($query){
			foreach($queryArray as $paramKey => $value){
				$requestParams[$paramKey] = $value;
			}
		}
		
		return $requestParams;
	}
	
	
	public static function storeLinkCode($link){
		
		$linkHash = md5($link);
		$db_params = OpenContext_OCConfig::get_db_config();
		$db = new Zend_Db_Adapter_Pdo_Mysql($db_params);
		$db->getConnection();
		$date = Zend_Date::now();
		$dbDate = $date->toString('YYYY-MM-dd HH:mm:ss'); 
				
		$data = array('linkHash'=> $linkHash,
				'linkURI' => $link,
				'numViews' => 0,
				'created' => $dbDate
			);
		try{
			$n = $db->insert('googleearthrefs', $data);
		}
		catch (Exception $e){
			//do nothing if already in
		}
		
		return $linkHash;
	}
	
	public static function getStoredLink($code){
		
		$link = OpenContext_OCConfig::get_host_config()."/sets/facets/.kml";
		$db_params = OpenContext_OCConfig::get_db_config();
		$db = new Zend_Db_Adapter_Pdo_Mysql($db_params);
		$db->getConnection();
		$sql = 'SELECT linkURI, numViews
				FROM googleearthrefs
				WHERE linkHash = "'.$code.'" 
				LIMIT 1';
		
		$result = $db->fetchAll($sql, 2);
		if($result){
			$link = $result[0]["linkURI"];
			$ref_count = $result[0]["numViews"];
			$ref_count++; // increment it up one.
					
			$data = array('numViews' => $ref_count);
			$n = $db->update('googleearthrefs', $data, 'linkHash = "'.$code.'"');
					
		}//end case with a result
		
		return $link;
	}
	
	



	//this checks to see if a request needs comparative data (for generating a percentage)
	//if not, compData = false
	//if so, compData holds the parameter for determining the denominator for the comparative data 
	public static function checkCompData($requestURI, $requestParams){
		$compData = false;
		if(array_key_exists("comp",$requestParams)){
			$compData = $requestParams["comp"];
			$requestURI = OpenContext_GoogleEarth::remove_querystring_var($requestURI, "comp");
		}
		if($compData != false){
			$compData = str_replace("%3A%3A", "::", $compData); //fix a URL encoding issue
			$requestURI = str_replace("&comp=".$compData, "", $requestURI);
			$requestURI = str_replace("?comp=".$compData, "?", $requestURI);
			$requestURI = OpenContext_GoogleEarth::cleanRequestLink($requestURI);	
		}
		return array("uri" => $requestURI, "comp" => $compData);
	}
	
	public static function cleanRequestLink($requestURI){
		
		$requestURI = str_replace("?&", "?", $requestURI);
		$request_len = strlen($requestURI);
		if(substr($requestURI, ($request_len-1), 1) == "?"){
			$requestURI = substr($requestURI, 0, ($request_len-1));
		}
		
		return $requestURI;
	}
	
	
	//this function removes query strings from variables
	public static function remove_querystring_var($url, $key) {
		
		$ampfix = false;
		if(substr_count($url, "&amp;")>0){
			$ampfix = true;
			$url = str_replace("&amp;", "&", $url);
		}
		
		$url = preg_replace('/(.*)(\?|&)' . $key . '=[^&]+?(&)(.*)/i', '$1$2$4', $url . '&');
		$url = substr($url, 0, -1);
		/*
		if($ampfix){
			$url = str_replace("&", "&amp;", $url);
			$url = str_replace("&amp;amp", "&amp;", $url);
		}
		*/
		return ($url);
	}
	
	
	public static function getCompDataLink($compData, $JSONrequestURI, $requestParams){
		$host = OpenContext_OCConfig::get_host_config();
		unset($requestParams["comp"]);
		
		if(substr_count($compData, "prop::")>0){
			$propKey = str_replace("prop::", "", $compData);
			$propVal = null;
			if(substr_count($propKey, "::")>0){
				$propArray = explode("::", $propKey);
				$propKey = $propArray[0];
				$propVal = $propArray[1];
			}
			$newJSONrequestURI = $host.(OpenContext_FacetOutput::generateFacetURL($requestParams, "prop", $propVal, $propKey));
		}
		elseif(substr_count($compData, "taxa::")>0){
			$lookCompData = str_replace("taxa::", "", $compData);
			$foundNumber = false;
			$foundIndex = 0;
			$i=0;
			foreach($requestParams["taxa"] as $actRequestTaxa){
				if(substr_count($actRequestTaxa, $lookCompData)>0){
					$foundNumber = true;
					$foundIndex  = $i;
				}
			$i++;
			}
			
			if($foundNumber){
				$requestParams["taxa"][$foundIndex] = $lookCompData;
				$newJSONrequestURI = $host.(OpenContext_FacetOutput::generateFacetURL($requestParams, null, null));
			}
		}
		elseif(substr_count($compData, "rel::")>0){
			$lookCompData = str_replace("rel::", "", $compData);
			
			$foundNumber = false;
			$foundIndex = false;
			$i=0;
			foreach($requestParams["rel"] as $actRequestTaxa){
				
				//echo "<br/>".$lookCompData. " looking at: ".$actRequestTaxa;
				
				if(substr_count($actRequestTaxa, $lookCompData)>0){
					$foundNumber = true;
					$foundIndex  = $i;
					//echo "<br/>FOUND at $i ";
				}
			$i++;
			}
			
			//break;
			
			if($foundNumber  != false){
				$requestParams["rel"][$foundIndex] = $lookCompData;
				$newJSONrequestURI = $host.(OpenContext_FacetOutput::generateFacetURL($requestParams, null, null));
			}
		}
		else{
			unset($requestParams[$compData]);
			$newJSONrequestURI = $host.(OpenContext_FacetOutput::generateFacetURL($requestParams, null, null));
		}
		
		if(substr_count($newJSONrequestURI,".json")<1){
			if(substr_count($newJSONrequestURI,"?")<1){
				$newJSONrequestURI = $newJSONrequestURI.".json";
			}
			else{
				$newJSONrequestURI = str_replace("?", ".json?", $newJSONrequestURI);
								
			}
		}
		
		//echo "new request:".$newJSONrequestURI.chr(13);
		return $newJSONrequestURI;
	}


	public static function getCompData($compData, $JSONrequestURI, $requestParams){
		
		$compLink = OpenContext_GoogleEarth::getCompDataLink($compData, $JSONrequestURI, $requestParams);
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
		$cache_id = "setJS_".md5($compLink);
		
		if(!$cache_result = $cache->load($cache_id)) {
		    $JSON_string = file_get_contents($compLink);
		}
		else{
		    $JSON_string = $cache_result;
		}
		
		return Zend_Json::decode($JSON_string);
		//return $compLink;
	}// end function


	public static function gcd($a, $b){
		return ($b) ? OpenContext_GoogleEarth::gcd($b, $a % $b) : $a;
	} 

	
	public static function makeRatio($number, $divisor){
		$ratio = false;
		
		$places = 0;
		$testNumerator = $number;
		$testDenom = $divisor;
		$goodRatio = false;
		if($divisor > 0){
			$loopCount = 0;
			while($goodRatio != true){
			
				$gcd = OpenContext_GoogleEarth::gcd($testNumerator, $testDenom);
				if($gcd < 1){
					$gcd = 1;
				}
				
				if(($testNumerator / $gcd > 100)&&($testDenom / $gcd > 100)){
					$places = $places - 1;
					$testNumerator = round($testNumerator, $places);
					$testDenom = round($testDenom, $places);
				}
				elseif($gcd == 1){
					$places = $places - 1;
					$testNumerator = round($testNumerator, $places);
					$testDenom = round($testDenom, $places);
				}
				else{
					$goodRatio = true;
				}
				
				if($testNumerator / $gcd == 0){
					$testNumerator = $number;
					$testDenom = $divisor;
					$goodRatio = true;
				}
				
				if($loopCount>25){
					$goodRatio = true;
				}
				
			$loopCount++;
			}
			
			if($testNumerator / $gcd == 0){
				$testNumerator = $number;
				$testDenom = $divisor;
			}
			
			$numerator = $testNumerator / $gcd;
			$denom = $testDenom/$gcd;
			
			if($numerator > 10 && $denom > 10){
				$numerator = $numerator / 10;
				$denom = $denom / 10;
			}
			
			$ratio = $numerator." : ".$denom;
		}
		else{
			$ratio = $number." : (none)";
		}
		
		if($numerator == 0){
			$ratio = $number." : $divisor";
		}
		
		return $ratio;
	}



	public static function make_result_paging($pagingURI, $requestParams){
		$cleanPath = array(".json", ".atom", ".kml", "/sets/facets/", "/sets/");
		$host = OpenContext_OCConfig::get_host_config();
		
		if($pagingURI != false){
			$parse_params = parse_url($pagingURI);
			if(array_key_exists("query", $parse_params)){
				parse_str($parse_params["query"], $queryArray);
				if(array_key_exists("page", $queryArray)){
					$pageValue = $queryArray["page"];
				}
				else{
					$pageValue = 1;
				}
			}
			else{
				$pageValue = 1;
			}
		
			$pagingURI = $host.(OpenContext_FacetOutput::generateFacetURL($requestParams, "page", $pageValue, false, false, "results_kml"));
		}
		return $pagingURI;
	}









}//end class declaration

?>
