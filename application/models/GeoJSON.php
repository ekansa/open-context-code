<?php


/*
 *
 this class processes faceted search results to create an array for GeoJSON expression
 *
*/
class GeoJSON {
    
	 public $requestParams; // array of the request parameters and values
	 
	 public $geoTileFacetArray; //array of geotile facets
	 public $tileGeoJSONarray; //array of geotile facets as GeoJSON array
	 
	 public $facetArray; // array of standard facets
	 public $contextGeoJSONarray; //array of json data for contexts
	 
	 function processGeoTileFacets(){
		  
		  if(is_array($this->geoTileFacetArray)){
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
					 $properties["name"] =  "Open Context ".$geoTile["name"];
					 $properties["href"] =  $geoTile["href"];
					 $properties["hrefGeoJSON"] =  str_replace(".json", ".geojson", $geoTile["result_href"]);
					 $properties["count"] =  $geoTile["count"];
					 
					 $actGeoJSON["properties"] =  $properties;
					 $tileGeoJSONarray[] =  $actGeoJSON;
				}
				$this->tileGeoJSONarray = $tileGeoJSONarray;
		  }
		  else{
				$this->tileGeoJSONarray = false;
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
	 
   
}//end class
