<?php

require_once("httpresponse.php");

class HTTPResource {
	
	public $uri;
	
	public function __construct($uri) {
		$this->uri = $uri;
	}
	
	public function method_allowed($name) {
		//echo  "http_$name<br />";
		return method_exists($this, "http_$name");
	}
	
	public function allowed_methods() {
	    $arr = get_class_methods(get_class($this));
		$methods = array();
		
		foreach ($arr as $method) {
			if (strstr($method,"http_")) {
				if ( $method == "http_HEAD" ) {
					if ( $this->method_allowed("GET") ) {
						$methods[] = str_replace("http_","",$method);
					}
				} else {
					$methods[] = str_replace("http_","",$method);
				}
			}
		}
		
		return $methods;
	}
	
	public function http_HEAD($request) {
		$response = new App_HTTPResponse();
		
		if ( $this->method_allowed("GET") ) {
			$response = $this->http_GET($request);
			$response->response_body = "";
		} else {
			$response->http_status = "405 Method Not Allowed";
			$response->headers["Content-Type"] = "text/plain";
			$response->headers["Allow"] = join($this->allowed_methods(),", ");
			$response->response_body = "Method not supported.";
		}
		
		return $response;
	}
	
	public function try_gzip($request, $response) {
		if ( $request->header_exists("Accept-Encoding") && function_exists("gzencode") ){
			
			$pref = $request->preferred_encoding( array("gzip"=>1,"identity"=>0.5) );
			if ($pref == "gzip") {
				$response->response_body = gzencode($response->response_body);
				$response->headers["Content-Encoding"] = "gzip";
				$response->headers["Vary"] = "Content-Encoding";
				
				if ( $response->header_exists("ETag") ) {
					$response->headers['ETag'] = 
						'"'.str_replace("\"","",$response->headers['ETag']).
						";".$response->headers["Content-Encoding"].'"';
				}
			}
		}
	}
	
	public function try_cache($request, $response, $cache) {
		if ( array_key_exists("ETag",$cache) ) {
			$etag = $cache["ETag"];
		}
		if ( array_key_exists("Last-Modified",$cache) ) {
			$last_modified = $cache["Last-Modified"];
		}
	
		if ( $request->header_exists("If-None-Match") && isset($etag) ) {
			$req_etag = str_replace(";gzip","",$request->headers['If-None-Match']);

			if ( $etag == $req_etag ) {
				
				$response->http_status = "304 Not Modified";
				
				if ( !$response->header_exists("Cache-Control") ) {
					$response->headers['Cache-Control'] = "must-revalidate";
				}
				
				// ETag for GZipped version should be different
				if ( $response->header_exists("Content-Encoding") ) {
					$etag = '"'.str_replace("\"","",$etag).";".$response->headers["Content-Encoding"].'"';
				}
				
				$response->headers['ETag'] = $etag;
				if (isset($last_modified)) {
					$response->headers['Last-Modified'] = $last_modified;
				}
				
				$response->response_body = "";
				return TRUE;
			}
		} else if ( $request->header_exists("If-Modified-Since") && isset($last_modified) ) {
			$req_mod = strtotime($request->headers["If-Modified-Since"]);
			
			if ( $req_mod >= strtotime($last_modified) ) {
				$response->http_status = "304 Not Modified";
				
				if ( !$response->header_exists("Cache-Control") ) {
					$response->headers['Cache-Control'] = "must-revalidate";
				}
				
				if (isset($etag)) {
					// ETag for GZipped version should be different
					if ( $response->header_exists("Content-Encoding") ) {
						$etag = '"'.str_replace("\"","",$etag).";".$response->headers["Content-Encoding"].'"';
					}
					
					$response->headers['ETag'] = $etag;
				}
				$response->headers['Last-Modified'] = $last_modified;
				
				$response->response_body = "";
				return TRUE;
			}
		}
		return FALSE;
	}
	
	protected function time_to_gmt($time) {
		return gmdate("D, d M Y H:i:s", (int)$time)." GMT";
	}
	
}
