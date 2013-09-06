<?php


//this class calculates a chronology path for making time-span facets
class ChronoPath {
    
    const maxDateBP = 5000000000; //5 Billion Year Ago
	 const maxPathDepth = 34;
	 
	 public $blockStart;
	 public $blockEnd;
	 public $blockMiddle;
	 
	 //Creates a chronology path as a string of numbers between 0-3, representing time spans for start and end dates BP
	 function createPath($startDateBP, $endDateBP, $path = ""){
		  
		  if($startDateBP > $endDateBP){
				return false; //start date can't be the same as the end date
		  }
		  else{
				
				$intervalSpan = $this->pathConvertBeginEnd($path);
				$halfSpan = $intervalSpan/2;
				//echo "<br/>".$intervalSpan." (".$this->blockStart." - ".$this->blockEnd."): $path";
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
				//echo "<br/>".$path;
				if(strlen($path)< self::maxPathDepth){
					 $path = $this->createPath($startDateBP, $endDateBP, $path);
				}
				
				
				return $path;
		  }
	 }//end function
    
	 //decodes a chronology path and gets a start and end date for the range indicated by the path
	 function pathConvertBeginEnd($path){

		  $level = strlen($path);
		  $this->blockStart = 0;
		  $this->blockEnd = self::maxDateBP;
		  
		  $intervalSpan = self::maxDateBP;
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
	 
	 
	 function getLevelTimeLength($level){
		  $i = 0;
		  $span = self::maxDateBP;
		  while($i < $level){
				$span = $span / 2;
				$i++;
		  }
		  
		  return $span;
	 }
	 
	 
}
