<?php


//this class calculates a chronology path for making time-span facets
class ChronoPath {
    
    //const defaultMaximumDateBP = 5000000000; //5 Billion Year Ago
	 const defaultMaximumDateBP = 10000000; //10 million years ago
	 const maxPathDepth = 30;
	 const minumumIntervalSpan = 1; //smallest size of a time interval that we care about
	 
	 const prefixDelim = "-";
	 
	 public $blockStart;
	 public $blockEnd;
	 public $blockMiddle;
	 public $pathMaximiumBP;
	 
	 //create a path from BCE dates, first by converting into BP dates
	 function createPathBCEdates($lateDateBCE, $earlyDateBCE, $path = ""){
		  if($lateDateBCE > 1950 ){
				return false;
		  }
		  else{
				if($earlyDateBCE > 1950){
					 $earlyDateBCE = 1950;
				}
		  }
		  
		  $lateDateBP = 1950 - $lateDateBCE;
		  $earlyDateBP = 1950 - $earlyDateBCE;
		  if($earlyDateBP < $lateDateBP){
				$temp = $earlyDateBP;
				$earlyDateBP = $lateDateBP;
				$lateDateBP = $temp;
		  }
		 
		  return $this->createPath($lateDateBP, $earlyDateBP, $path);
	 }
	 
	 //Creates a chronology path as a string of numbers between 0-3, representing time spans for start and end dates BP
	 //you can pass a path prefix like "10M-" to the $path to set the maxium BP for the current chronogy path
	 function createPath($lateDateBP, $earlyDateBP, $path = ""){
		  
		  if($lateDateBP > $earlyDateBP){
				return false; //start date can't be the same as the end date
		  }
		  else{
				
				$levelIntervalSpan = $this->pathConvertBeginEnd($path);
				if($levelIntervalSpan > self::minumumIntervalSpan){
					 $halfSpan = $levelIntervalSpan/2;
					 
					 if($earlyDateBP > $this->pathMaximiumBP){
						  //you've got a data range that doesn't fit into the current Maxiumum BP for this path
						  return false;
					 }
					 
					 if($lateDateBP < $this->blockStart + $halfSpan){
						  $Npath = "0";
						  if($earlyDateBP >= $this->blockEnd - $halfSpan ){
								$Npath = "1";
						  }
					 }
					 else{
						  $Npath = "2";
						  if($earlyDateBP >= $this->blockEnd - $halfSpan ){
								$Npath = "3";
						  }
					 }
					 $path .= $Npath;
					 
					 if($this->getPathLevel($path) <= self::maxPathDepth){
						  $path = $this->createPath($lateDateBP, $earlyDateBP, $path);
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
		  
		  $levelIntervalSpan = $this->pathMaximiumBP;
		  $i = 0;
		  while($i < $level){
		  
				$levelIntervalSpan = $levelIntervalSpan / 2;
				$actPathSquare = substr($path, $i, 1);
				if($actPathSquare == "0"){
					 $this->blockEnd = $this->blockEnd - $levelIntervalSpan;
				}
				elseif($actPathSquare == "1"){
					 //do nothing to change start and ends
				}
				elseif($actPathSquare == "2"){
					 $this->blockStart = $this->blockStart + $levelIntervalSpan;
					 $this->blockEnd = $this->blockEnd - $levelIntervalSpan;
				}
				elseif($actPathSquare == "3"){
					 $this->blockStart = $this->blockStart + $levelIntervalSpan;
				}
				else{
					 
				}
				
				$i++;
		  }
		  
		  return $levelIntervalSpan;
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
