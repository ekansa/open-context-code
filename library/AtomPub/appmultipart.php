<?php

require_once("httpexception.php");
require_once("httprequest.php");

require_once("appmimetype.php");

class App_Multipart {

	public $requests = array();

	public function __construct($collection, $boundary, $name, $data) {

		$parts = split("--".$boundary, $data);
		
		if ( count($parts)!=4 ) {
			throw new App_HTTPException("Unsupported multipart data.",400);
		}
		
		$this->requests[] = new App_HTTPRequest();
		$this->requests[0]->fill_from_data($parts[1]);
		
		$this->requests[] = new App_HTTPRequest();
		$this->requests[1]->fill_from_data($parts[2]);
	}
	
	public function get_entry_part() {
		foreach ($this->requests as $request) {
			if ( $request->header_exists("Content-Type") ) {
				$type = new App_Mimetype($request->headers["Content-Type"]);
				if ($type->type=="application" && $type->subtype=="atom+xml") {
					if ( $type->parameter_exists("type") ) {
						if ($type->parameters["type"]=="entry") {
							return $request;
						}
					} else {
						return $request;
					}
				}
			}
		}
		throw new App_HTTPException("No entry part in multipart.",400);
	}
	
	public function get_media_part() {
		foreach ($this->requests as $request) {
			if ( $request->header_exists("Content-Type") ) {
				$type = new App_Mimetype($request->headers["Content-Type"]);
				if ($type->type!="application" || $type->subtype!="atom+xml") {
					return $request;
				} else {
					if ( $type->parameter_exists("type") ) {
						if ($type->parameters["type"]=="feed") {
							return $request;
						}
					}
				}
			}
		}
		throw new App_HTTPException("No media part in multipart.",400);
	}
}
