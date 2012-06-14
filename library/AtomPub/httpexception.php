<?php

class App_HTTPException extends Exception {
	
	public $http_status;
	
	private $statuses = array(
		400 => "400 Bad Request",
		404 => "404 File Not Found",
		409 => "409 Conflict",
		412 => "412 Precondition Failed",
		415 => "415 Unsupported Media Type",
		500 => "500 Internal Server Error"
	);
	
    public function __construct($message, $statuscode, $code = 0) {
        parent::__construct($message, $code);
		
		$this->http_status = $this->statuses[$statuscode];
    }
}
