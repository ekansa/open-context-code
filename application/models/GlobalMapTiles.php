<?php

/*
	GlobalMapTiles - part of Aggregate Map Tools
	Version 1.0
	Copyright (c) 2009 The Bivings Group
	All rights reserved.
	Author: John Bafford
	
	http://www.bivings.com/
	http://bafford.com/softare/aggregate-map-tools/
	
	Based on GDAL2Tiles / globalmaptiles.py
	Original python version Copyright (c) 2008 Klokan Petr Pridal. All rights reserved.
	http://www.klokan.cz/projects/gdal2tiles/
	
	Permission is hereby granted, free of charge, to any person obtaining a
	copy of this software and associated documentation files (the "Software"),
	to deal in the Software without restriction, including without limitation
	the rights to use, copy, modify, merge, publish, distribute, sublicense,
	and/or sell copies of the Software, and to permit persons to whom the
	Software is furnished to do so, subject to the following conditions:
	
	The above copyright notice and this permission notice shall be included
	in all copies or substantial portions of the Software.
	
	THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS
	OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
	FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL
	THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
	LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
	FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER
	DEALINGS IN THE SOFTWARE.
*/

class GlobalMapTiles
{
	var $tileSize;
	var $initialResolution;
	var $originShift;
	
	//Initialize the TMS Global Mercator pyramid
	function __construct($tileSize = 256)
	{
		$this->tileSize = $tileSize;
		$this->initialResolution = 2 * M_PI * 6378137 / $this->tileSize;
		# 156543.03392804062 for tileSize 256 Pixels
		$this->originShift = 2 * M_PI * 6378137 / 2.0;
		# 20037508.342789244
	}
	
	//Converts given lat/lon in WGS84 Datum to XY in Spherical Mercator EPSG:900913
	function LatLonToMeters($lat, $lon)
	{
		$mx = $lon * $this->originShift / 180.0;
		$my = log( tan((90 + $lat) * M_PI / 360.0 )) / (M_PI / 180.0);
	
		$my *= $this->originShift / 180.0;
		
		return array($mx, $my);
	}
	
	//Converts XY point from Spherical Mercator EPSG:900913 to lat/lon in WGS84 Datum
	function MetersToLatLon($mx, $my)
	{
		$lon = ($mx / $this->originShift) * 180.0;
		$lat = ($my / $this->originShift) * 180.0;
	
		$lat = 180 / M_PI * (2 * atan( exp( $lat * M_PI / 180.0)) - M_PI / 2.0);
		
		return array($lat, $lon);
	}
	
	//Converts pixel coordinates in given zoom level of pyramid to EPSG:900913
	function PixelsToMeters($px, $py, $zoom)
	{
		$res = $this->Resolution($zoom);
		$mx = $px * $res - $this->originShift;
		$my = $py * $res - $this->originShift;
		
		return array($mx, $my);
	}
	
	//Converts EPSG:900913 to pyramid pixel coordinates in given zoom level
	function MetersToPixels($mx, $my, $zoom)
	{
		$res = $this->Resolution( $zoom );
		
		$px = ($mx + $this->originShift) / $res;
		$py = ($my + $this->originShift) / $res;
		
		return array($px, $py);
	}

	//Returns a tile covering region in given pixel coordinates
	function PixelsToTile($px, $py)
	{
		$tx = ceil( $px / $this->tileSize ) - 1;
		$ty = ceil( $py / $this->tileSize ) - 1;
		
		return array($tx, $ty);
	}
	
	//Returns tile for given mercator coordinates
	function MetersToTile($mx, $my, $zoom)
	{
		list($px, $py) = $this->MetersToPixels($mx, $my, $zoom);
		
		return $this->PixelsToTile($px, $py);
	}
	
	//Returns bounds of the given tile in EPSG:900913 coordinates
	function TileBounds($tx, $ty, $zoom)
	{
		list($minx, $miny) = $this->PixelsToMeters( $tx*$this->tileSize, $ty*$this->tileSize, $zoom );
		list($maxx, $maxy) = $this->PixelsToMeters( ($tx+1)*$this->tileSize, ($ty+1)*$this->tileSize, $zoom );
		
		return array($minx, $miny, $maxx, $maxy);
	}
	
	//Returns bounds of the given tile in latutude/longitude using WGS84 datum
	function TileLatLonBounds($tx, $ty, $zoom)
	{
		$bounds = $this->TileBounds($tx, $ty, $zoom);
		
		list($minLat, $minLon) = $this->MetersToLatLon($bounds[0], $bounds[1]);
		list($maxLat, $maxLon) = $this->MetersToLatLon($bounds[2], $bounds[3]);
		 
		return array($minLat, $minLon, $maxLat, $maxLon);
	}
	
	//Resolution (meters/pixel) for given zoom level (measured at Equator)
	function Resolution($zoom)
	{
		return $this->initialResolution / (1 << $zoom);
	}
	
	//Converts TMS tile coordinates to Microsoft QuadTree
	function QuadTree($tx, $ty, $zoom)
	{
		$quadKey = '';
		
		$ty = ((1 << $zoom) - 1) - $ty;
		foreach(range($zoom, 1, -1) as $i)
		{
			$digit = 0;
			
			$mask = 1 << ($i-1);
			
			if(($tx & $mask) != 0)
				$digit += 1;
			
			if(($ty & $mask) != 0)
				$digit += 2;
			
			$quadKey .= $digit;
		}
		
		return $quadKey;
	}
	
	//Converts a quadkey to tile coordinates
	function QuadTreeToTile($quadtree, $zoom)
	{
		$tx = 0;
		$ty = 0;
		
		for($i = $zoom; $i >= 1; $i--)
		{
			$ch = $quadtree[$zoom - $i];
			$mask = 1 << ($i-1);
			
			$digit = $ch - '0';
			
			if($digit & 1)
				$tx += $mask;
			
			if($digit & 2)
				$ty += $mask;
		}
		
		$ty = ((1 << $zoom) - 1) - $ty;
		
		return array($tx, $ty);
	}
	
	//Converts a latitude and longitude to quadtree at the specified zoom level 
	function LatLonToQuadTree($lat, $lon, $zoom)
	{
		list($mx, $my) = $this->LatLonToMeters($lat, $lon);
		list($tx, $ty) = $this->MetersToTile($mx, $my, $zoom);
		
		return $this->QuadTree($tx, $ty, $zoom);
	}
	
	//Converts a quadtree location into a latitude/longitude bounding rectangle
	function QuadTreeToLatLon($quadtree)
	{
		$zoom = strlen($quadtree);
		
		list($tx, $ty) = $this->QuadTreeToTile($quadtree, $zoom);
		return $this->TileLatLonBounds($tx, $ty, $zoom);
	}
	
	//Returns a list of all of the quadtree locations at a given zoom level within a latitude/longude box
	function GetQuadTreeList($zoom, array $latLon, array $latLonMax = array())
	{
		list($lat, $lon) = $latLon;
		
		if($latLonMax)
		{
			list($latMax, $lonMax) = $latLonMax;
			
			if($latMax < $lat || $lonMax < $lon)
				return false;
		}
		
		list($mx, $my) = $this->LatLonToMeters($lat, $lon);
		list($tminx, $tminy) = $this->MetersToTile($mx, $my, $zoom);
		
		if($latLonMax)
		{
			list($mx, $my) = $this->LatLonToMeters($latMax, $lonMax);
			list($tmaxx, $tmaxy) = $this->MetersToTile($mx, $my, $zoom);
		}
		else
		{
			$tmaxx = $tminx;
			$tmaxy = $tminy;
		}
		
		$arr = array();
		foreach(range($tminy, $tmaxy) as $ty)
			foreach(range($tminx, $tmaxx) as $tx)
			{
				$quadtree = $this->QuadTree($tx, $ty, $zoom);
				
				$arr[$quadtree] = $this->TileLatLonBounds($tx, $ty, $zoom);
			}
		
		return $arr;
	}
}


?>