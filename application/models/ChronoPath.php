<?php


//this class calculates a chronology path for making time-span facets
class ChronoPath {
    
    const defaultMaximumDateBP = 5000000000; //5 Billion Year Ago
	 const maxPathDepth = 30;
	 const minumumIntervalSpan = .5; //smallest size of a time interval that we care about
	 
	 const prefixDelim = "-";
	 
	 public $blockStart;
	 public $blockEnd;
	 public $blockMiddle;
	 public $pathMaximiumBP;
	 
	 //Creates a chronology path as a string of numbers between 0-3, representing time spans for start and end dates BP
	 //you can pass a path prefix like "10M-" to the $path to set the maxium BP for the current chronogy path
	 function createPath($startDateBP, $endDateBP, $path = ""){
		  
		  if($startDateBP > $endDateBP){
				return false; //start date can't be the same as the end date
		  }
		  else{
				
				$intervalSpan = $this->pathConvertBeginEnd($path);
				if($intervalSpan > self::minumumIntervalSpan){
					 $halfSpan = $intervalSpan/2;
					 
					 if($endDateBP > $this->pathMaximiumBP){
						  //you've got a data range that doesn't fit into the current Maxiumum BP for this path
						  return false;
					 }
					 
					 if($startDateBP < $this->blockStart + $halfSpan){
						  $Npath = "0";
						  if($endDateBP >= $this->blockEnd - $halfSpan ){
								$Npath = "1";
						  }
					 }
					 else{
						  $Npath = "2";
						  if($endDateBP >= $this->blockEnd - $halfSpan ){
								$Npath = "3";
						  }
					 }
					 $path .= $Npath;
					 
					 if($this->getPathLevel($path) <= self::maxPathDepth){
						  $path = $this->createPath($startDateBP, $endDateBP, $path);
					 }
				}
				return $path;
		  }
	 }//end function
    
	 //decodes a chronology path and gets a start and end date for the range indicated by the path
	 function pathConvertBeginEnd($rawPath){

		  $this->getPathMaximum($rawPath);
		  $level = $this->getPathLevel($rawPath);
		  $path = $this->trimPathPrefix($rawPath);
		  
		  $this->blockStart = 0;
		  $this->blockEnd = $this->pathMaximiumBP;
		  
		  $intervalSpan = $this->pathMaximiumBP;
		  $i = 0;
		  while($i < $level){
		  
				$intervalSpan = $intervalSpan / 2;
				$actPathSquare = substr($path, $i, 1);
				if($actPathSquare == "0"){
					 $this->blockEnd = $this->blockEnd - $intervalSpan;
				}
				elseif($actPathSquare == "1"){
					 //do nothing to change start and ends
				}
				elseif($actPathSquare == "2"){
					 $this->blockStart = $this->blockStart + $intervalSpan;
					 $this->blockEnd = $this->blockEnd - $intervalSpan;
				}
				elseif($actPathSquare == "3"){
					 $this->blockStart = $this->blockStart + $intervalSpan;
				}
				else{
					 
				}
				
				$i++;
		  }
		  
		  return $intervalSpan;
	 }
	 
	 
	 //checks if a prefix is on the path, if so, uses the prefix
	 //to set the maximum time interval represented in the chronology path
	 function getPathMaximum($rawPath){
		  $doDefault = true;
		  $expArray = array("k" => 3,
								  "m" => 6,
								  "g" => 9
								  );
		  
		  if(strstr($rawPath, self::prefixDelim)){
				$pathEx = explode(self::prefixDelim, $rawPath);
				$prefix = $pathEx[0];
				$prefix = strtolower($prefix);
				$lastChar = preg_replace("/[^a-zA-Z]/", '', $prefix);
				$numericPrefix = preg_replace("/[^0-9.]/", '', $prefix);
				if(is_numeric($numericPrefix)){
					 $doDefault = false;
					 if(array_key_exists($lastChar, $expArray)){
						  $this->pathMaximiumBP = $numericPrefix * (pow(10, $expArray[$lastChar]));
					 }
					 else{
						  $this->pathMaximiumBP = $numericPrefix;
					 }
				}
		  }
		  
		  if($doDefault){
				$this->pathMaximiumBP = self::defaultMaximumDateBP;
		  }
	 }
	 
	 //get's the path's level, tacking into account a path prefix
	 function getPathLevel($rawPath){
		  $path = $this->trimPathPrefix($rawPath);
		  return strlen($path);
	 }
	 
	 //remove a prefix from a raw path
	 function trimPathPrefix($rawPath){
		  if(strstr($rawPath, self::prefixDelim)){
				$pathEx = explode(self::prefixDelim, $rawPath);
				if(isset($pathEx[1])){
					 $path = $pathEx[1];
				}
				else{
					 $path = "";
				}
				return $path;
		  }
		  else{
				return $rawPath;
		  }
	 }
	 
}
