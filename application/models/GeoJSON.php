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
					 $properties["name"] =  "Region ".$boundingBox." ";
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
					 
					 $contextGeoJSONarray = array();
					 foreach($facets["context"] as $context){
					 
						  $actGeoJSON = array("type" => "Feature");
						  
						  
						  $coordinateArray = array();
						  
						  if(isset($context["geoTime"]["geoPoly"])){
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
	 }
	 
   
}//end class
