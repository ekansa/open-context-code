<?php

class OpenContext_GeoTile {
	
	/*
	 
	These functions are tested and they WORK.
	The functions work with lat / lon coordinates and map them to geospatial tiles,
	and then take geospatial tiles and turn them back into map coordinates
	at zoom level 20, geospatial tiles to coordinates is only a few meters off
	
	tileToLatLon($tilePath) turns a path of tiles into a lat lon coordinate pair (space seperated)
	 assignTile($lat, $lon) turns a lat / lon pair into a tile path, upto zoom-level 20.
	
	Geospatial tiles are defined as follows:
	 '0' is the north-west quadrant
	 '1' is the north-east quadrant
	 '2' is the south-west quadrant
	 '3' is the south-east quadrant
	
	*/
	
	const seperator = "::";
	
	
	public static function relatedTiles($tilePath){
		
		if(stristr($tilePath, self::seperator)){
			$tileArray = explode(self::seperator, $tilePath);
			$doPathDelim = true;
		}
		else{
			$tileArray = str_split($tilePath);
			$doPathDelim = false;
		}
		
		$zoomLevel = count($tileArray)-1;
		$degreeChangeLat = 180 / ($zoomLevel * 2);
		$degreeChangeLon = 360 / ($zoomLevel * 2);
		
		
		//$degreeChange = $degreeChange + $degreeChange/2;
		
		//current coordinates
		$actCoordinates = OpenContext_GeoTile::tileToLatLon($tilePath);
		$actCoordArray = explode(" ", $actCoordinates);
		$actLat = $actCoordArray[0] ;
		$actLon = $actCoordArray[1] ;
		
		//echo "$actLat $degreeChangeLat";
		if(OpenContext_GeoTile::degreeSane(($actLat + $degreeChangeLat), true)){
			
			$north = OpenContext_GeoTile::assignTile($actLat + $degreeChangeLat, $actLon);
		}
		else{
			$north = false;
		}
		
		if(OpenContext_GeoTile::degreeSane(($actLat - $degreeChangeLat), true)){
			$south = OpenContext_GeoTile::assignTile($actLat - $degreeChangeLat, $actLon);
		}
		else{
			$south = false;
		}
		
		if(OpenContext_GeoTile::degreeSane(($actLon + $degreeChangeLon), false)){
			$east = OpenContext_GeoTile::assignTile($actLat, $actLon + $degreeChangeLon);
		}
		else{
			$east = false;
		}
		
		if(OpenContext_GeoTile::degreeSane(($actLon - $degreeChangeLon), false)){
			$west = OpenContext_GeoTile::assignTile($actLat, $actLon - $degreeChangeLon);
		}
		else{
			$west = false;
		}
		
		
		
		$outputPrelim = array("north" => $north, "south" => $south, "east" => $east, "west" => $west);
		if(!$doPathDelim){
			$output = array();
			foreach($outputPrelim as $key => $value){
				//$value = "0".$value;
				$output[$key] = str_replace(self::seperator, "", $value);
			}
		}
		else{
			$output = $outputPrelim;
		}
		
		/*
		$originalTile = OpenContext_GeoTile::assignTile($actLat, $actLon);
		$originalTile = str_replace(self::seperator, "", $originalTile);
		//$output["data"] = "cord: ".$actCoordinates."act tile:".$tilePath." offset: ".$degreeChangeLon." otile:".$originalTile;
		$testLatLon = "-46.679594,-69.960937";
		$testing = explode(",", $testLatLon );
		$testLat = $testing[0];
		$testLon = $testing[1];
		$data = "test_".$testLat."_".$testLon;
		$ttile = OpenContext_GeoTile::assignTile($testLat, $testLon);
		$ttile = str_replace(self::seperator, "", $ttile);
		$data .= "_tttile_".$ttile;
		$data .= "_tcoords_".OpenContext_GeoTile::tileToLatLon($ttile);
		$tile1 = substr($ttile,0,10);
		$data .= "_tile1_".$tile1;
		$data .= "_tcoords1_".OpenContext_GeoTile::tileToLatLon($tile1);
		$tile2 = substr($ttile,0,2);
		$data .= "_tile2_".$tile2;
		$data .= "_tcoords2_".OpenContext_GeoTile::tileToLatLon($tile2);
		
		$output["data"] = $data;
		*/
		
		
		return $output;
	}//end function
	
	
	public static function degreeSane($degree, $lat = true){
		
		if($lat){
			$maxMin = 90;
		}
		else{
			$maxMin = 180;
		}
		
		if($degree > $maxMin || $degree < ($maxMin * -1)){
			return false;
		}
		else{
			return true;
		}
		
	}//end function
	
	
	
	public static function tileToLatLon($tilePath, $giveRange = false){
		
		if(stristr($tilePath, self::seperator)){
			$tileArray = explode(self::seperator, $tilePath);
		}
		else{
			$tileArray = str_split($tilePath);
		}
		
		$minLat = -90;
		$maxLat = 90;
		$minLon = -180;
		$maxLon = 180;
		$meanLon = ($minLon + $maxLon) / 2;
		$meanLat = ($minLat + $maxLat) / 2;
		
		$i=0;
		foreach($tileArray as $tile){
			if($i>0){
				if($tile == 0){
					$minLat = ($minLat + $maxLat) / 2;
					$maxLat = $maxLat;
					$minLon = $minLon;
					$maxLon = ($minLon + $maxLon) / 2;
				}
				elseif($tile == 1){
					$minLat = ($minLat + $maxLat) / 2;
					$maxLat = $maxLat;
					$minLon = ($minLon + $maxLon) / 2;
					$maxLon = $maxLon;
				}
				elseif($tile == 2){
					$minLat = $minLat;
					$maxLat = ($minLat + $maxLat) / 2;
					$minLon = $minLon;
					$maxLon = ($minLon + $maxLon) / 2;
				}
				elseif($tile == 3){
					$minLat = $minLat;
					$maxLat = ($minLat + $maxLat) / 2;
					$minLon = ($minLon + $maxLon) / 2;
					$maxLon = $maxLon;
				}
			}
		$i++;
		}//end loop
		
		if($i>1){
			$meanLon = ($minLon + $maxLon) / 2;
			$meanLat = ($minLat + $maxLat) / 2;
		}
		
		if(!$giveRange){
			return $meanLat." ".$meanLon;
		}
		else{
			return array("meanLat"=> $meanLat,
				     "meanLon"=>$meanLon,
				     "minLat"=>$minLat,
				     "maxLat"=>$maxLat,
				     "minLon"=>$minLon,
				     "maxLon"=>$maxLon);
		}
	}//end function
	
	
	
	
	public static function tileToLatLon_old($tilePath){
		
		if(stristr($tilePath, self::seperator)){
			$tileArray = explode(self::seperator, $tilePath);
		}
		else{
			$tileArray = str_split($tilePath);
		}
		
		
		$minLat = 0;
		$maxLat = 180;
		$minLon = 0;
		$maxLon = 180;
		$zoomLevel = 0;
		$zoomFraction = 0;
		$degreeChangeLat = 180;
		$degreeChangeLon = 180;
		foreach($tileArray as $tile){
			
			if($zoomLevel >0){
				
				if($tile == 0){ //north-west
					$minLat = $minLat + $degreeChangeLat;
					$maxLat = $maxLat;
					$minLon = $minLon;
					$maxLon = $minLon + $degreeChangeLon;
				}
				elseif($tile == 1){ //north-east
					$minLat = $minLat + $degreeChangeLat;
					$maxLat = $maxLat;
					$minLon = $minLon + $degreeChangeLon;
					$maxLon = $maxLon;
				}
				elseif($tile == 2){ //south-west
					$minLat = $minLat;
					$maxLat = $minLat + $degreeChangeLat;
					$minLon = $minLon;
					$maxLon = $minLon + $degreeChangeLon;
				}
				elseif($tile == 3){ //south-east
					$minLat = $minLat;
					$maxLat = $minLat + $degreeChangeLat;
					$minLon = $minLon + $degreeChangeLon;
					$maxLon = $maxLon;
				}
				
				$degreeChangeLat = $degreeChangeLat / 2;
				$degreeChangeLon = $degreeChangeLon / 2;
				
			}
			
			//echo "<br/>Tile: ($tile) Degree Change ($degreeChange) ".(($minLat+$maxLat)/2)." ".($minLon+$maxLon)/2;
		
		$zoomFraction++;
		$zoomLevel++;	
		}//end loop
		
		$meanLon = ($minLon + $maxLon) / 2;
		$meanLat = ($minLat + $maxLat) / 2;
		$meanLon = $meanLon - 90;
		$meanLat = $meanLat - 180;
		
		
		if(count($tileArray) == 2){
			if($tileArray[1] == 0){
				$meanLat = 45;
				$meanLon = -90;
			}
			elseif($tileArray[1] == 1){
				$meanLat = 45;
				$meanLon = 90;
			}
			elseif($tileArray[1] == 2){
				$meanLat = -45;
				$meanLon = -90;
			}
			elseif($tileArray[1] == 3){
				$meanLat = -45;
				$meanLon = 90;
			}
		}
		
		//echo "<br/>FINAL for $tilePath is ".($meanLat)." ".$meanLon;
		
		return $meanLat." ".$meanLon;
	}
	
	
	
	public static function assignTile($lat, $lon, $tile = "", $minLat = 0, $maxLat = 180, $minLon = 0, $maxLon = 360){
		
		$maxZoom = 20;
			
		//$tileLevel = substr_count($tile, self::seperator);
		
		if($tile == ""){
			$tileLevel = 0;
		}
		else{
			$tileLevel = count(explode(self::seperator, $tile));
		}
		
		$seperator = self::seperator;
		
		//convert negative degree numbers into 360 degrees
		if($tileLevel == 0){
			$lat = $lat + 90;
			$lon = $lon + 180;
		}
		
		
		//echo "<br/>Lat is: $lat, between ($minLat and $maxLat)";
		//echo "<br/>Lon is: $lon, between ($minLon and $maxLon)";
		
		
		if($lon <= $maxLon){
			$minLon = $minLon;
			$maxLon = $maxLon - (($maxLon - $minLon)/2);
			$west = true;
		}
		else{
			$minLonNew = $maxLon;
			$maxLon = $maxLon + (($maxLon - $minLon)/2);
			$minLon = $minLonNew;
			$west = false;
		}
		
		
		//echo "<br/>Lat is: $lat, between ($minLat and $maxLat)";	
		if($lat >= $minLat){
			$minLat = $minLat + (($maxLat - $minLat)/2);
			$maxLat = $maxLat;
			$north = true;
		}
		else{
			$newMaxLat = $minLat;
			$minLat = $minLat - (($maxLat - $minLat)/2);
			$maxLat = $newMaxLat;
			$north = false;
		}
		
		
		if($west && $north){
			$newTile = "0";
		}
		if(!$west && $north){
			$newTile = "1";
		}
		if($west && !$north){
			$newTile =  "2";
		}
		if(!$west && !$north){
			$newTile = "3";
		}
		
		if($tileLevel == 0){
			$tile = $newTile;
		}
		else{
			$tile .= $seperator.$newTile;
		}
		
		
		//$table = OpenContext_GeoTile::printTile($tile);
		//echo "<br/>".$table;
		
		//echo "<br/>Tile: $tile <br/><br/><br/>";
		
		if((substr_count($tile, self::seperator)) < $maxZoom){
			$tile = OpenContext_GeoTile::assignTile($lat, $lon, $tile, $minLat, $maxLat, $minLon, $maxLon);
		}
		else{
			//$tile .= self::seperator.(OpenContext_GeoTile::tileToLatLon($tile));
		}
		
		return $tile;
	}//end function



	public static function printTile($tile, $level = 0){
	
		$actItem = substr($tile, $level, 1);
		$numLevels = strlen($tile);
		
		if($level>0){
			$allWidth = (round((2400 / (2*$level)),0));
			$allHeight = (round((1800 / (2*$level)),0));
			$cellWidth = round($allWidth/2,0);
			$cellHeight = round($allHeight/2,0);
		} 
		
		
		$color = "#F7F7F7";
		$fontSize = round(300/($level + 1),0);
		if($fontSize<8){
			$fontSize = 8;	
		}
		$fontSize = $fontSize."%";
		
		switch($level){
			case 1:
			$color = "#FFFFFF";
			break;
			case 2:
			$color = "#F0F0F0";
			break;
			case 3:
			$color = "#E5E5E5";
			break;
			case 4:
			$color = "#D1D1D1";
			break;
			case 5:
			$color = "#BABABA";
			break;
			case 6:
			$color = "#9C9C9C";
			break;
			case 7:
			$color = "#8C8C8C";
			break;
			case 8:
			$color = "#7F7F7F";
			break;
			case 9:
			$color = "#707070";
			break;
			case 10:
			$color = "#666666";
			break;
			case ($level > 10):
			$color = "#303030";
			break;
		}
		
		
		
		
		if($level==0){
			$output = "<div ";
			$output .= " style='height:599px; ";
			$output .= "width:773px; text-align:center; ";
			$output .= " background-image:url(\"http://www.intermap.com/blog/wp-content/uploads/mercator-projection-image.jpg\"); ";
			$output .= "'>".chr(13);
			$output .= OpenContext_GeoTile::printTile($tile, 1);
			$output .= "</div>";
		}
		else{
			$newLevel = $level + 1;
			
			$actCell = array();
			$cc = 0;
			while($cc < 4){
				$actCell[$cc] = " overflow: hidden; margin:0px; padding:0px; ";
				$cellVal[$cc] = $cc."[".$level."]";
				$cc++;
			}
			
			if($level>1){
				$actCellColor = " background-color:".$color."; ";
			}
			else{
				$actCellColor = " ";
			}
			
			$cellVal[$actItem] = $actItem."(".$level.")";
			
			if(($newLevel <= $numLevels)&&(($newLevel <= 10))){
				$cellTable = OpenContext_GeoTile::printTile($tile, $newLevel);
				$cellTable .= chr(13);
			}
			else{
				$cellTable = "<strong>$actItem</strong>";
			}
			
			$opacity = ($level/10)+.05;
			if($opacity >1){
				$opacity  = 1;
			}
			
			$opacity = .85;
			
			$opacity = "".$opacity;
			//echo "<br/>".$opacity; 
			
			$allTab = new SimpleXMLElement("<div></div>");
			$allTab->addAttribute('style', "height:100%; width:100%; z-index:".$level."; position:relative; opacity:".$opacity."; overflow:hidden; margin:0px; border-color:#D7D7D7; border-style:solid; border-width:thin; font-size:".$fontSize."; ".$actCellColor);
			$allTab->addAttribute('id', 'outer-'.$level); 
			$topRow = $allTab->addChild('div');
			$topRow->addAttribute('style', "height:50%; width:100%; overflow:hidden; margin:0px; float:left;");
			$topRow->addAttribute('id', 'top-'.$level);
			$lowRow = $allTab->addChild('div');
			$lowRow->addAttribute('style', "height:50%; width:100%; overflow:hidden; margin:0px; float:left;");
			$lowRow->addAttribute('id', 'low-'.$level);
			$cell_0 = $topRow->addChild('div', $cellVal[0]);
			$cell_0->addAttribute('style', "height:100%; width:50%; float:left; ".$actCell[0]);
			$cell_0->addAttribute('id', 'cell-0-'.$level);
			$cell_1 = $topRow->addChild('div', $cellVal[1]);
			$cell_1->addAttribute('style', "height:100%; width:50%; float:left; ".$actCell[1]);
			$cell_1->addAttribute('id', 'cell-1-'.$level);
			$cell_2 = $lowRow->addChild('div', $cellVal[2]);
			$cell_2->addAttribute('style', "height:100%; width:50%; float:left; ".$actCell[2]);
			$cell_2->addAttribute('id', 'cell-2-'.$level);
			$cell_3 = $lowRow->addChild('div', $cellVal[3]);
			$cell_3->addAttribute('style', "height:100%; width:50%; float:left; ".$actCell[3]);
			$cell_3->addAttribute('id', 'cell-3-'.$level);
			
			/*
			$allTab->addAttribute('style', "height:".$allHeight."px; width:".$allWidth."px; font-size:".$fontSize."; ");
			$allTab->addAttribute('id', 'outer-'.$level); 
			$topRow = $allTab->addChild('div');
			$topRow->addAttribute('style', "height:".$cellHeight."px; width:".$allWidth."px; clear:both;");
			$topRow->addAttribute('id', 'top-'.$level);
			$lowRow = $allTab->addChild('div');
			$lowRow->addAttribute('style', "height:".$cellHeight."px; width:".$allWidth."px; clear:both;");
			$lowRow->addAttribute('id', 'low-'.$level);
			$cell_0 = $topRow->addChild('div', $cellVal[0]);
			$cell_0->addAttribute('style', "height:".$cellHeight."px; width:".$cellWidth."px; float:left; ".$actCell[0]);
			$cell_0->addAttribute('id', 'cell-0-'.$level);
			$cell_1 = $topRow->addChild('div', $cellVal[1]);
			$cell_1->addAttribute('style', "height:".$cellHeight."px; width:".$cellWidth."px; float:right; ".$actCell[1]);
			$cell_1->addAttribute('id', 'cell-1-'.$level);
			$cell_2 = $lowRow->addChild('div', $cellVal[2]);
			$cell_2->addAttribute('style', "height:".$cellHeight."px; width:".$cellWidth."px; float:left; ".$actCell[2]);
			$cell_2->addAttribute('id', 'cell-2-'.$level);
			$cell_3 = $lowRow->addChild('div', $cellVal[3]);
			$cell_3->addAttribute('style', "height:".$cellHeight."px; width:".$cellWidth."px; float:right; ".$actCell[3]);
			$cell_3->addAttribute('id', 'cell-3-'.$level);
			*/
			
			$dom = dom_import_simplexml($allTab)->ownerDocument;
			$dom->formatOutput = true; 
			
			$output = $dom->saveXML(); 
			$output = str_replace('<?xml version="1.0"?>', '', $output);
			$output = str_replace( ($actItem."(".$level.")"), $cellTable, $output);
			
		}

		return $output;
	}
	
}//end class declaration

?>
