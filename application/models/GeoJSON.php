<?php


/*
 *
 this class processes faceted search results to create an array for GeoJSON expression
 *
*/
class GeoJSON {
    
	 public $requestParams; // array of the request parameters and values
	 public $numFound; //total number of items found
	 
	 public $geoTileFacetArray; //array of geotile facets
	 public $tileGeoJSONarray; //array of geotile facets as GeoJSON array
	 
	 public $facetArray; // array of standard facets
	 public $contextGeoJSONarray; //array of json data for contexts
	 
	 public $denominatorData; //array of data for the denominator, if doing a comparison. false if not
	 public $propOf; //string describing what defines the denominator, if doing a comparison
	 public $nominatorCurrentVal; //string describing the current nominator, if doing a comparison
	 
	 
	 public $contextPathArray; //array of context paths from the requestParams. Array generated from parsing "OR" (||) terms.
	 
	 public $db; //database object
	 
	  public $standardNamespaces = array("rdf" => "http://www.w3.org/1999/02/22-rdf-syntax-ns#",
					 "rdfs" => "http://www.w3.org/2000/01/rdf-schema#",
					 "label" => "rdfs:label",
					 "xsd" => "http://www.w3.org/2001/XMLSchema#",
					 "skos" => "http://www.w3.org/2004/02/skos/core#",
					 "owl" => "http://www.w3.org/2002/07/owl#",
					 "dc-elems" => "http://purl.org/dc/elements/1.1/",
					 "dc-terms" => "http://purl.org/dc/terms/",
					 "uuid" => "dc-terms:identifier",
					 "bibo" => "http://purl.org/ontology/bibo/",
					 "foaf" => "http://xmlns.com/foaf/0.1/",
					 "cidoc-crm" => "http://www.cidoc-crm.org/cidoc-crm/",
					 "oc-api" => "http://opencontext.org/about/services");
	 
	 
	 
	 function processGeoTileFacets(){
		  
		  $requestParams = $this->requestParams;
		  if(is_array($this->geoTileFacetArray)){
				$this->getDenominator(); //get comparative denominator data, if requested
				$tileGeoJSONarray = array();
				foreach($this->geoTileFacetArray as $geoTile){
					 
					 $actGeoJSON = array("type" => "Feature",
												"geometry" => array("type" => "Polygon")
												);
					 
					 $coordinateArray = array();
					 $polyArray = array();
					 $polyArray[] = array( $geoTile["geoBounding"][1], $geoTile["geoBounding"][0]);
					 $polyArray[] = array( $geoTile["geoBounding"][3], $geoTile["geoBounding"][0]);
					 $polyArray[] = array( $geoTile["geoBounding"][3], $geoTile["geoBounding"][2]);
					 $polyArray[] = array( $geoTile["geoBounding"][1], $geoTile["geoBounding"][2]);
					 $coordinateArray[] = $polyArray;
					 $actGeoJSON["geometry"]["coordinates"] = $coordinateArray;
					 
					 $properties = array();
					 $boundingBox = "Bounded by: ".$geoTile["geoBounding"][0]." Lat, ".$geoTile["geoBounding"][1]." Lon, and ".$geoTile["geoBounding"][2]." Lat, ".$geoTile["geoBounding"][3]." Lon";
					 
					 //$properties["name"] =  "Region ".$boundingBox." ";
					 $properties["name"] =  "Title: ".$geoTile["linkQuery"]." ";
					 $properties["href"] =  $geoTile["href"];
					 $properties["hrefGeoJSON"] =  str_replace(".json", ".geojson", $geoTile["result_href"]);
					 $properties["count"] =  $geoTile["count"];
					 $properties = $this->getTileDenominator($properties, $geoTile["linkQuery"]); //get a demoninator, if present
					 
					 $actGeoJSON["properties"] =  $properties;
					 $tileGeoJSONarray[] =  $actGeoJSON;
				}
				$this->tileGeoJSONarray = $tileGeoJSONarray;
		  }
		  else{
				
				$tileGeoJSONarray = array();
				$actGeoJSON = array("type" => "Feature",
												"geometry" => array("type" => "Polygon")
												);
				$coordinateArray = array();
				$polyArray = array();
				$geoObj = new GlobalMapTiles;
				$geoArray = $geoObj->QuadTreeToLatLon($requestParams["geotile"]);
				$polyArray[] = array( $geoArray[1], $geoArray[0]);
				$polyArray[] = array( $geoArray[3], $geoArray[0]);
				$polyArray[] = array( $geoArray[3], $geoArray[2]);
				$polyArray[] = array( $geoArray[1], $geoArray[2]);
				$coordinateArray[] = $polyArray;
				$actGeoJSON["geometry"]["coordinates"] = $coordinateArray;
				$properties = array();
				$boundingBox = "Bounded by: ".$geoArray[0]." Lat, ".$geoArray[1]." Lon, and ".$geoArray[2]." Lat, ".$geoArray[3]." Lon";
				$properties["name"] =  "Region ".$boundingBox."";
				$properties["href"] =  false;
				$properties["hrefGeoJSON"] =  false;
				$properties["count"] =  $this->numFound;
					 
				$actGeoJSON["properties"] =  $properties;
				$tileGeoJSONarray[] =  $actGeoJSON;
				
				$this->tileGeoJSONarray = $tileGeoJSONarray;
		  }
		  
		  return $this->tileGeoJSONarray;
	 }//end function
	 
	 //this generates geoJSON features from context facets
	 function processContextFacets(){
		  $this->contextGeoJSONarray = false;
		  
		  if(is_array($this->facetArray)){
				$facets = $this->facetArray;
				
				if(array_key_exists("context", $facets)){
					 
					 $minLat = 400;
					 $minLon = 400;
					 $maxLat = -400;
					 $maxLon = -400;
					 
					 foreach($facets["context"] as $context){
						  if($context["geoTime"]["geoLat"] > $maxLat){
								$maxLat = $context["geoTime"]["geoLat"];
						  }
						  if($context["geoTime"]["geoLat"] < $minLat){
								$minLat = $context["geoTime"]["geoLat"];
						  }
						  if($context["geoTime"]["geoLong"] > $maxLon){
								$maxLon = $context["geoTime"]["geoLong"];
						  }
						  if($context["geoTime"]["geoLong"] < $minLon){
								$minLon = $context["geoTime"]["geoLong"];
						  }
					 }
					 
					 $latDif = $maxLat - $minLat;
					 $lonDif = $maxLon - $minLon;
					 
					 $polyCount = 0;
					 $pointCount = 0;
					 foreach($facets["context"] as $context){
						  if(isset($context["geoTime"]["geoPoly"])){
								$polyCount++;
						  }
						  else{
								$pointCount++;
						  }
					 }
					 
					 $allowPoly = false;
					 if($polyCount > $pointCount * 4){
						  $allowPoly = true;
					 }
					 
					 $contextGeoJSONarray = array();
					 foreach($facets["context"] as $context){
					 
						  $actGeoJSON = array("type" => "Feature");
						  
						  
						  $coordinateArray = array();
						  
						  if(isset($context["geoTime"]["geoPoly"]) && $allowPoly){
								$actGeoJSON["geometry"] = array("type" => "Polygon");
								$polyArray =  $this->contextPolyGeoJSON($context["geoTime"]["geoPoly"]);
								$coordinateArray[] = $polyArray;
						  }
						  else{
								$actGeoJSON["geometry"] = array("type" => "Point");
								//$polyArray = $this->pointPolyGeoJSON($context["geoTime"], $latDif, $lonDif);
								//$polyArray = false;
								
								$pointArray = array($context["geoTime"]["geoLong"], $context["geoTime"]["geoLat"]);
								$coordinateArray = $pointArray;
						  }
						  
						  $actGeoJSON["geometry"]["coordinates"] = $coordinateArray;
						  $contextPoly = $this->getContextPolygon($context["name"]);
						  if(is_array($contextPoly )){
								$actGeoJSON["geometry"] = $contextPoly;
						  }
						  
						  $properties = array();
						  $properties["name"] =  $context["name"];
						  $properties["href"] =  $context["href"];
						  $properties["hrefGeoJSON"] =  str_replace(".json", ".geojson", $context["result_href"]);
						  $properties["count"] =  $context["count"];
						  
						  $actGeoJSON["properties"] =  $properties;
						  
						  
						  $contextGeoJSONarray[] =  $actGeoJSON;
						  
					 }
					 $this->contextGeoJSONarray = $contextGeoJSONarray;
				}
		  }
		  
		  return $this->contextGeoJSONarray;
	 }//end function
	 
	 
	 //get geospatial feature data about a context path
	 function getContextPolygon($contextName){
		  $output = false;
		  $db = $this->startDB();
		  
		  $contextPathArray = $this->getContextPathArray();
		  $contextCondition = $this->geoPathCondition($contextPathArray, $contextName);
		  
		  $sql = "SELECT * FROM geodata WHERE $contextCondition LIMIT 1; ";
		  
		  $result = $db->fetchAll($sql, 2);
		  if($result){
				$jsonString = $result[0]["geoJSON"];
				$geoJSONfrag = Zend_Json::decode($jsonString);
				if(is_array($geoJSONfrag)){
					 if(isset($geoJSONfrag["geometry"])){
						  $output = $geoJSONfrag["geometry"];
					 }
					 else{
						  $output = $geoJSONfrag;
					 }
				}
		  }
		  return $output;
	 }
	 
	 
	 //makes an array of context paths, with one if there are no "||" (or) terms in the context, otherwise this returns multiple paths
	 function getContextPathArray(){
		  if(!is_array($this->contextPathArray)){
				$output = array();
				$requestParams = $this->requestParams;
				if(isset($requestParams["default_context_path"])){
					 $rawDefaultPath = $requestParams["default_context_path"];
					 
					 //this fixes a problem of a trailing "/" at the end of some requests for default contexts
					 if(substr($rawDefaultPath, -1, 1) == "/"){
						  $rawDefaultPath = substr($rawDefaultPath, 0, (strlen($rawDefaultPath)-1));
					 }
				
					 if(strlen($rawDefaultPath)>0){
						  $allPaths = array(0 => "");
						  if(strstr($rawDefaultPath, "/")){
								$rawPathArray = explode("/", $rawDefaultPath);
						  }
						  else{
								$rawPathArray = array( 0 => $rawDefaultPath);
						  }
						  
						  foreach($rawPathArray as $rawPathItem){
								if(strstr($rawPathItem, "||")){
									 //an OR term is present!
									 $pathItems = explode("||", $rawPathItem);
								}
								else{
									 $pathItems = array(0 => $rawPathItem);
								}
								
								$newAllPaths = array();
								foreach($pathItems as $pathItem){
									 foreach($allPaths as $prevPaths){
										  if(strlen($prevPaths) > 1){
												$newAllPaths[] = $prevPaths."/".$pathItem;
										  }
										  else{
												$newAllPaths[] = $pathItem;
										  }
									 }
								}
								$allPaths = $newAllPaths;
								unset($newAllPaths);
						  }
						  
						  $output = $allPaths;
					 }//case where there's stringlength > 0
				
				}
				$this->contextPathArray = $output;
		  }
		  else{
				$output = $this->contextPathArray; //already made the contextPathArray.
		  }
		  
		  return $output;
	 }
	 
	 
	 //generate a query condition based on the context path and the active context name (context name from the facet needing a polygon)
	 function geoPathCondition($contextPathArray, $contextName){
		  $firstLoop = true;
		  if(count($contextPathArray)>0){
				$output = "";
				foreach($contextPathArray as $contextPath){
					 if(substr($contextPath, -1, 1) == "/"){
						  $contextPath = substr($contextPath, 0, (strlen($contextPath)-1)); //strip trailing "/" if present
					 }
					 $queryTerm = $contextPath."/".$contextName;
					 if($firstLoop){
						  $output = " path = '$queryTerm' ";
						  $firstLoop = false;
					 }
					 else{
						  $output .= " OR path = '$queryTerm' ";
					 }
					 
				}
		  }
		  else{
				$output = " path = '$contextName' ";
		  }
		  
		  $output ="(".$output.")";
		  
		  return $output;
	 }
	 
	 
	 
	 //make an array of geoJSON polygon vertices from a point
	 function pointPolyGeoJSON($geoTime, $latDif, $lonDif){
		  $polyArray = array();
		  
		  if($latDif == 0){
				$latDif = .0001;
		  }
		  if($lonDif == 0){
				$lonDif = .0001;
		  }
		  
		
		  $polyArray[] = array(($geoTime["geoLong"] - $lonDif), ($geoTime["geoLat"] - $latDif));
		  $polyArray[] = array(($geoTime["geoLong"] - $lonDif), ($geoTime["geoLat"] + $latDif));
		  $polyArray[] = array(($geoTime["geoLong"] + $lonDif), ($geoTime["geoLat"] + $latDif));
		  $polyArray[] = array(($geoTime["geoLong"] + $lonDif), ($geoTime["geoLat"] - $latDif));
		  
		  return $polyArray;
	 }
	 
	 
	 //make an array of geoJSON polygon vertices
	 function contextPolyGeoJSON($contextPoly){
		  $polyArray = array();
		  $contextGeoPolyEx = explode(" ", $contextPoly);
		  $polyArray[] = $this->latLonGeoJSON($contextGeoPolyEx[0]);
		  $polyArray[] = $this->latLonGeoJSON($contextGeoPolyEx[2]);
		  $polyArray[] = $this->latLonGeoJSON($contextGeoPolyEx[3]);
		  $polyArray[] = $this->latLonGeoJSON($contextGeoPolyEx[1]);
		  foreach($contextGeoPolyEx as $commaSepVertex){
				//$polyArray[] = $this->latLonGeoJSON($commaSepVertex);
		  }
		  return $polyArray;
	 }
	 
	 
	 //get comma sep coordates, convert to GeoJSON ordered x, y array
	 function latLonGeoJSON($commaLatLon, $flipCoordinates = true){
		  if(stristr($commaLatLon, ",")){
				$latLonEx = explode(",", $commaLatLon);
				if( $flipCoordinates){
					 return array($latLonEx[1]+0, $latLonEx[0]+0);
				}
				else{
					 return array($latLonEx[0]+0, $latLonEx[1]+0);
				}
		  }
		  else{
				return false;
		  }
	 }//end function 
	 
	 
	 function getTileDenominator($properties, $tileID){
		  $denominatorData = $this->denominatorData;
		  if(is_array($denominatorData)){
				
				if(isset($denominatorData["geoTileFacets"])){
					 foreach($denominatorData["geoTileFacets"] as $geoTile){
						  $actTileID = $geoTile["linkQuery"];
						  if($actTileID === $tileID){
								$properties["denominator"] = $geoTile["count"];
								$properties["propOf"] = $this->propOf;
								$properties["nominatorCurrentVal"] = $this->nominatorCurrentVal;
								break;
						  }
					 }
				}
				
		  }
		  return $properties;
	 }
	 
	 
	 
	 //get denominator data, if requested, else false
	 function getDenominator(){
		  $requestParams = $this->requestParams;
		  $PropotionObj = new ProportionalData;
		  $PropotionObj->requestParams = $requestParams;
		  $this->denominatorData = $PropotionObj->getDenominatorData();
		  $this->propOf = $PropotionObj->propOf;
		  $this->nominatorCurrentVal = $PropotionObj->nominatorCurrentVal;
	 }
	 
   
	 //make a GeoJSON-LD service
	 function jsonLD($facetsResults, $GeoJSON){
		  
		  if(is_array($facetsResults) && is_array($GeoJSON)){
				$JSON_LD = array();
				$JSON_LD["@context"] = array(
					 "id" => "@id",
					 "type" => "@type");
				
				//add standard namespaces
				foreach($this->standardNamespaces as $abrevKey => $actURI){
					 $JSON_LD["@context"][$abrevKey] =  $actURI;
				}
				$JSON_LD["@context"]["published"] = "dc-terms:created";
				$JSON_LD["@context"]["updated"] = "oc-api:updated";
				$JSON_LD["@context"]["count"] = "oc-api:facet-count";
				
				$JSON_LD = $this->arrayKeyCopy("numFound", $facetsResults, $JSON_LD);
				$JSON_LD = $this->arrayKeyCopy("offset", $facetsResults, $JSON_LD);
				$JSON_LD = $this->arrayKeyCopy("published", $facetsResults, $JSON_LD);
				$JSON_LD = $this->arrayKeyCopy("updated", $facetsResults, $JSON_LD);
				if(array_key_exists("facets", $facetsResults)){
					 $facetKeyIndex = 1;
					 
					 
					 foreach($facetsResults["facets"] as $facetKey => $facetValues){
						  $newFacetValues = false;
						  if(is_array($facetValues)){
								$newFacetValues = array();
								foreach($facetValues as $fValue){
									 $newFvalue = array();
									 $newFvalue["id"] = $fValue["href"];
									 $newFvalue["count"] = $fValue["count"];
									 $newFvalue["oc-api:api-url"] = $this->jsonToGeoJSONldLink($fValue["result_href"]);
									 $newFvalue["oc-api:facet-value"] = $fValue["name"];
									 $newFvalue["oc-api:facet-display"] = $fValue["name"];
									 $newFacetValues[] = $newFvalue;
								}
						  }
						  $JSON_LD["oc-api:has-facets"][] = array("id" => "#facet-".$facetKeyIndex,
																				"oc-api:key" => $facetKey,
																				"oc-api:facet-values" => $newFacetValues
																				);
						  $facetKeyIndex++;
					 }
				}
				
				
				$JSON_LD = array_merge_recursive($JSON_LD, $GeoJSON);
		  }
		  else{
				$JSON_LD = false;
		  }
		  
		  return $JSON_LD;
	 }
	
	
	 //copy an array element from an old array to a new, with an optional new key
	 function arrayKeyCopy($oldKey, $arrayOld, $arrayNew, $newKey = false){
		  if(array_key_exists($oldKey, $arrayOld)){
				if(!$newKey){
					 $newKey = $oldKey;
				}
				
				$arrayNew[$newKey] = $arrayOld[$oldKey];
		  }
		  
		  return $arrayNew;
	 }
	
	 //turn a JSON link into a GeoJSON-LD link
	 function jsonToGeoJSONldLink($url){
		  if(strstr($url, ".json")){
				$url = str_replace(".json", ".geojson-ld", $url);
		  }
		  
		  return $url;
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
	 
	 function setUTFconnection($db){
		  $sql = "SET collation_connection = utf8_unicode_ci;";
		  $db->query($sql, 2);
		  $sql = "SET NAMES utf8;";
		  $db->query($sql, 2);
    }
	 
	 
	 
	 
}//end class
