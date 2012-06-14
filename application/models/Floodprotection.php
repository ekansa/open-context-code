<?php

/*
This class monitors IP addresses to limit requests to keep the
API from being over used
*/

class Floodprotection {
    
    public $db;
    public $ip;
    public $url;
    public $locked;
    public $HTTPdownUntil; //HTTP formatted time when the service will be down
    public $sleepTime; //recommended sleep
    public $addedRequests; //add additional requests, for browsers
    public $userAgent;
    
    const publishEmail = "publish@opencontext.org";
    
    const request_second_limit = 2.5; // Number of allowed requests per second
    const keep_seconds = 90; // number of seconds to make an IP address wait 
    
    public $OKips = array("64.13.230.23", "74.50.51.130");
    public $badIPS =  array("180.76.5");
    
    
    //initiallize the database
    public function initialize($ip, $url, $db = false){
        
	$this->ip =  $this->security_check($ip);
	$this->url = $this->security_check($url);
	$this->lock = false; //by default, they have access
	$this->addedRequests = 0;
	$this->userAgent = false;
	
	if(!$db){
            $db_params = OpenContext_OCConfig::get_db_config();
            $db = new Zend_Db_Adapter_Pdo_Mysql($db_params);
            $db->getConnection();
        }
        
        $this->db = $db;
	$this->clean_old_ip();
    }
    
    
    public function check_ip(){
	$db = $this->db;
	$ip = $this->ip;
	
	
	/*
	
	$badIPfound = false;
	foreach($this->badIPS as $badIP){
	    if(stristr($ip, $badIP)){
		$this->lock = true;
		$this->HTTPdownUntil = date('D, d M Y H:i:s \G\M\T', strtotime($currentTime + 120000));
		$updateData["lock"] =  true;
		$this->sleepTime = 120000;
		$badIPfound  = true;
	    }
	}
	
	
	
	$select = $db->select()
             ->from("floodprotection")
	     ->where('ip = ?', $ip);
	     
	$stmt = $db->query($select);
	$result = $stmt->fetchAll();
	
	if($result){
	    
	    $this->lock = $result[0]["lock"];
	    $requestCount = $result[0]["count"];
	    $firstRequestTime = $result[0]["phpTime"];
	    
	    $currentTime = microtime(true);
	    $elapsedTime = $currentTime - $firstRequestTime;
	    $requestCount++; //increment up the request count by 1
	    
	    $updateData = array();
	    $updateData["count"] =  $requestCount;
	    $where = array();
	    $where[] = "ip = '".$this->ip."' ";
	    $updateData["rate"] = $elapsedTime / $requestCount;
	    
	    $OKips = $this->OKips;
	    if((stristr($this->userAgent, "google")) && !in_array($this->ip, $OKips) && !$badIPfound ){
		if($updateData["rate"] >= (self::request_second_limit + $this->addedRequests ) ){
		    $this->lock = true;
		    $this->HTTPdownUntil = date('D, d M Y H:i:s \G\M\T', strtotime($currentTime + self::keep_seconds));
		    $updateData["lock"] =  true;
		    $this->sleepTime = self::keep_seconds;
		}
	    }
	    elseif($badIPfound){
		$this->lock = true;
		$this->HTTPdownUntil = date('D, d M Y H:i:s \G\M\T', strtotime($currentTime + 12000));
		$updateData["lock"] =  true;
		$this->sleepTime = 12000;
	    }
	    
	    
	    $db->update("floodprotection", $updateData, $where);
	}
	else{
	    $this->register_new_ip(); // IP address not in database, add the IP
	}
	
	$db->closeConnection();
	
	*/
	
    }//end function
    
    
    
    //indicate the requester new IP address, the URL, and the time
    public function register_new_ip(){
	$db = $this->db;
	$ip = $this->ip;
	$url = $this->url;
	
	$data = array("ip" => $ip,
		      "phpTime" => microtime(true),
		      "count" => 1,
		      "lock" => false,
		      "url" => $url
		      );
	
	$outcome = true;
	try{
	    //$db->insert("floodprotection", $data);
	}
	catch(Exception $e){
	    $outcome = false;
	}
	return $outcome;
    }
    
    //delete IP address records older than the keep seconds time
    function clean_old_ip(){
	$db = $this->db;
	$where = array();
	$currentTime = microtime(true);
	$expiredRecord = $currentTime - self::keep_seconds;
	$where[] = "phpTime <= ".$expiredRecord;
	//$db->delete("floodprotection", $where);
    }
    

    function security_check($input){
        $badArray = array("DROP", "SELECT", "#", "--", "DELETE", "INSERT", "UPDATE", "ALTER", "=");
        foreach($badArray as $bad_word){
            if(stristr($input, $bad_word) != false){
                $input = str_ireplace($bad_word, "XXXXXX", $input);
            }
        }
        return $input;
    }
    
}
