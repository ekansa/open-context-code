<?php


//this class looks up information about the user agent
class BrowserDetect { 
   
   public $browsers = array("firefox", "msie", "opera", "chrome", "safari", 
                            "mozilla", "seamonkey",    "konqueror", "netscape", 
                            "gecko", "navigator", "mosaic", "lynx", "amaya", 
                            "omniweb", "avant", "camino", "flock", "aol"); 
   
   public $agent;
   public $name; 
   public $version; 
   
    public function detect() { 
        $browsers = $this->browsers;
	$this->name = false;
	$this->version = false;

	 if(isset($_SERVER['HTTP_USER_AGENT'])){
	    $this->agent = strtolower($_SERVER['HTTP_USER_AGENT']);
	 }
	 else{
	     $this->agent = "unknown";
	 }
        
	foreach($browsers as $browser) 
        { 
            if (preg_match("#($browser)[/ ]?([0-9.]*)#", $this->agent, $match)) { 
                $this->name = $match[1] ; 
                $this->version = $match[2] ; 
                break ; 
            } 
        } 
    } 

}