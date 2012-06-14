<?php
/**
 * Class definition of the App_HTTPRequest class
 * @package php-atompub-server
 */
 
require_once("appuri.php");

/**
 * The App_HTTPRequest class
 *
 * Fields and some utility methods for an HTTP request.
 * @package php-atompub-server
 */
class App_HTTPRequest {
	
	public $headers = array();
	
	public $method;
	public $request_uri;
	public $request_body;
	
	/**
	 * Check if a given header exists in the request
	 * @param string $name The name of the HTTP header
	 * @return boolean The existence of a header
	 */
	public function header_exists($name) {
		return array_key_exists($name, $this->headers);
	}
	
	/**
	 * Fill the fields with values from the server
	 */
	public function fill_from_server() {
		// Content-Type
		if ( array_key_exists("CONTENT_TYPE",$_SERVER) ) {
			$this->headers["Content-Type"] = $_SERVER["CONTENT_TYPE"];
		}
		// Slug
		if ( array_key_exists("HTTP_SLUG",$_SERVER) ) {
			$this->headers["Slug"] = $_SERVER["HTTP_SLUG"];
		}
		
		// If-none-match
		if ( array_key_exists("HTTP_IF_NONE_MATCH",$_SERVER) ) {
			$this->headers["If-None-Match"] = $_SERVER["HTTP_IF_NONE_MATCH"];
		}
		// If-Match
		if ( array_key_exists("HTTP_IF_MATCH",$_SERVER) ) {
			$this->headers["If-Match"] = $_SERVER["HTTP_IF_MATCH"];
		}
		// If-Modified-Since
		if ( array_key_exists("HTTP_IF_MODIFIED_SINCE",$_SERVER) ) {
			$this->headers["If-Modified-Since"] = $_SERVER["HTTP_IF_MODIFIED_SINCE"];
		}
		
		// Accept-Encoding
		if ( array_key_exists("HTTP_ACCEPT_ENCODING",$_SERVER) ) {
			$this->headers["Accept-Encoding"] = $_SERVER["HTTP_ACCEPT_ENCODING"];
		}
		
		// Request-URI
		if ( array_key_exists("REQUEST_URI",$_SERVER) ) {
			$this->request_uri = 
				new URI("http://".$_SERVER["HTTP_HOST"].$_SERVER['REQUEST_URI']);			
		}
		// Request
		$this->method = $_SERVER["REQUEST_METHOD"];
		
		if ( $this->method == "POST" && array_key_exists("HTTP_X_HTTP_METHOD_OVERRIDE",$_SERVER) ) {
			$this->method = $_SERVER["HTTP_X_HTTP_METHOD_OVERRIDE"];	
		}
		
		if ($this->method == "POST" || $this->method == "PUT") {
			$this->request_body = file_get_contents("php://input");
		}
	}
	
	/**
	 * Check if which of several encodings is preferred in the response.
	 *
	 * @param array $arr The server defined values, e.g. array("gzip"=>1,"identity"=>0.5)
	 * @return string  $pref The preferred encoding.
	 */
	public function preferred_encoding($arr) {
		if ( !$this->header_exists("Accept-Encoding") ) {
			return "identity";
		}
		$codings = $this->parse_accept_encoding();
	
		$keys = array_keys($arr);
		$matches = array();
		
		foreach ( $keys as $key ) {
			if ( array_key_exists($key, $codings) ) {
				$matches[$key] = $arr[$key] * $codings[$key];
			}
		}
		
		if ( count($matches) > 0 ) {
			arsort($matches);
			$sorted_keys = array_keys($matches);
			
			if ($matches[ $sorted_keys[0] ] > 0.0001) {
				return $sorted_keys[0];
			}
		}
		
		return "identity";
	}
	/**
	 * Parse the accept-encoding HTTP header.
	 * @return array the accepted encodings, with their q-values
	 */
	private function parse_accept_encoding() {
		$header = $this->headers["Accept-Encoding"];
		
		$encodings = explode(",", str_replace(" ","",strtolower($header)) );
		
		$codings = array();
		
		foreach ( $encodings as $encoding ) {
			$split = explode(";",$encoding);
			
			if ( is_array($split) && count($split) > 1 ) {
				$coding = $split[0];
				
				$qvalues = explode("=",$split[1]);
				$qvalue = $qvalues[1];
				
				$codings[$coding] = (float)$qvalue;
			} else {
				$coding = $split[0];
				$codings[$coding] = 1;
			}
		}
		
		return $codings;
	}
	
	/**
	 * Create from data
	 * data consists of headers and data
	 */
	function fill_from_data($data) {
		$eq = strpos($data,"\r\n\r\n"); // is this the same on Linux?
		$headers = substr($data, 0,$eq);
		$this->request_body = substr($data, $eq+4);
		
		$parts = split("\n", $headers);

		foreach($parts as $part) {
			$eq = strpos($part, ": ");
			$name = substr($part, 0,$eq);
			$value = str_replace("\r","",substr($part, $eq+2));
			
			$this->headers[$name] = $value;
		}
	}
}

?>