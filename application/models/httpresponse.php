<?php

class App_HTTPResponse {
	
	public $http_status;
	
	public $headers = array();
	
	public $response_body;
	
	public $response_document;
	
	/**
	 * Check if a given header exists in the response
	 * @param string $name The name of the HTTP header
	 * @return boolean The existence of a header
	 */
	public function header_exists($name) {
		return array_key_exists($name, $this->headers);
	}
}

?>
