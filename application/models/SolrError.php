<?php

/*
This class monitors IP addresses to limit requests to keep the
API from being over used
*/

class SolrError {
    
    public $db;
    public $requestParams;
    
    const publishEmail = "publish@opencontext.org";
    const waitSeconds =  7200 ; // 7200 number of seconds to wait between emails 7200 is 2 hours
   
    //initiallize the database
    public function initialize($requestParams, $db = false){
        
	$this->requestParams =  $requestParams;
	
	if(!$db){
            $db_params = OpenContext_OCConfig::get_db_config();
            $db = new Zend_Db_Adapter_Pdo_Mysql($db_params);
            $db->getConnection();
        }
        
        $this->db = $db;
		$this->record_error();
    }
    
    
    public function record_error(){
	$db = $this->db;
	$requestParams = $this->requestParams;
	
	$sql = "SELECT * FROM dataset_errors WHERE type = 'solr' ORDER BY time DESC LIMIT 1;";
	     
	$result = $db->fetchAll($sql, 2);
        if($result){
	
	    $lastPHPtime = $result[0]["phpTime"];
	    $currentTime = microtime(true);
	    
	    $elapsedTime = $currentTime - $lastPHPtime;
	   
	    if($elapsedTime >= self::waitSeconds){
		 $this->register_new_error();
	    }
	}
	else{
	    $this->register_new_error();
	}
	
	
	$db->closeConnection();
    }//end function
    
    
    
    public function restartSolr($solrDir = "/var/www/oc-solr-3-6-2/example"){
		  $commandString = "cd $solrDir/example;rm nohup.out;killall -9 java;nohup java -jar start.jar &;";
		  shell_exec($commandString);
    }
    
    
    
    
    //indicate the requester new IP address, the URL, and the time
    public function register_new_error(){
		
		$host = OpenContext_OCConfig::get_host_config();
		$db = $this->db;
		$requestParams = $this->requestParams;
		$JSONrequest = Zend_Json::encode($requestParams);
		
		$data = array("type" => "solr",
				  "phpTime" => microtime(true),
				  "note" => $JSONrequest
				  );
		
		$outcome = true;
		try{
			$db->insert("dataset_errors", $data);
		}
		catch(Exception $e){
			$outcome = false;
		}
		
		$mail = new Zend_Mail();
			
		try {
			
			$requestIP = false;
			if ( isset($_SERVER["REMOTE_ADDR"]) )    { 
				$requestIP = '' . $_SERVER["REMOTE_ADDR"] . ' '; 
			} elseif ( isset($_SERVER["HTTP_X_FORWARDED_FOR"]) )    { 
				$requestIP = '' . $_SERVER["HTTP_X_FORWARDED_FOR"] . ' '; 
			} elseif ( isset($_SERVER["HTTP_CLIENT_IP"]) )    { 
				$requestIP = '' . $_SERVER["HTTP_CLIENT_IP"] . ' '; 
			} 
			
			
			$emailBody = "CRAP! Solr is not responding to a ping from request: ".chr(13);
			$emailBody .= $host.$_SERVER["REQUEST_URI"];
			$emailBody .= chr(13).chr(13);
			$emailBody .= "Client IP address: ".$requestIP.chr(13).chr(13);
			$emailBody .= chr(13).chr(13);
			$emailBody .= $JSONrequest;
			
			$configMail = array('auth' => 'login',
				'username' => OpenContext_OCConfig::get_PublishUserName(true),
				'password' => OpenContext_OCConfig::get_PublishPassword(true), 'port' => 465, 'ssl' => 'ssl');
			
			$transport  = new Zend_Mail_Transport_Smtp('smtp.gmail.com', $configMail);
			
			$mail->setBodyText($emailBody);
			$mail->setFrom(((OpenContext_OCConfig::get_PublishUserName(true)).'@gmail.com'), 'Open Context Publishing');
			$mail->addHeader('Reply-To', 'publish@opencontext.org');
			$mail->addHeader('X-Mailer', 'PHP/' . phpversion());
			$mail->addTo("kansaeric@gmail.com", "Open Context Admin");
			$mail->addCc('skansa@alexandriaarchive.org', 'Sarah Kansa');
			$mail->addBcc('kansaeric@gmail.com', 'Eric Kansa');
			$mail->setSubject('Open Context Solr Search Down!');
			$mail->send($transport);
			$this->mailError = "";
		} catch (Zend_Exception $e) {
			//echo $e;
		} 
	
	
	
	
	return $outcome;
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
