<?php

require_once("appentry.php");
require_once("appmimetype.php");

class App_MediaResource extends App_Entry {
	
	public $content;
	public $extension;
	
	public function __construct($uri, $collection) {
		parent::__construct($uri, $collection);
		
		if (!defined("ATOM_STORE_DIR")) {
			define("ATOM_STORE_DIR", "store/".$collection->name);
		}
		$this->store = new App_FileStore(ATOM_STORE_DIR, $collection->base_uri);
		
		$this->extension = $uri->get_extension();
	}
	
	public function get_content() {
		if ( !isset($this->content) ) {

			$data = $this->store->get($this->uri);
			if ($data == "") {
				throw new App_HTTPException("Error loading data.",500);
			}
			
			$this->content = $data;
		}
		return $data;
	}
	
	public function save() {
		$this->store->store($this->uri, $this->content);
	}
	public function update($content) {
		
		// get media link entry
		$link_uri = new URI(str_replace(".".$this->extension,".atomentry",$this->uri));
		$medialink = $this->collection->get_entry($link_uri);
		
		$medialink->update($medialink->get_document());
		
		$this->content = $content;
		
		$this->dispatchEvent( new APPEvent("entry_update", $this) );
		
		$this->save();
	}
	
	public function get_media_type() {
		return new App_Mimetype($this->extension);
	}
	
	public function http_GET($request) {
		$response = new App_HTTPResponse();
		
		$time = $this->last_modified();
		
		$etag = '"'.md5($time).'"';
		$last_modified = $this->time_to_gmt($time);
		
		if ( $this->try_cache($request, $response, 
			array("ETag" => $etag, "Last-Modified" => $last_modified)) ) 
		{
			return $response;
		}
		
		$mime_t = new App_Mimetype($this->extension);
		if ( $mime_t->type == "text" ) {
			$mime_t->parameters["charset"] = "utf-8";
		}
		
		$response->http_status = "200 Ok";
		$response->headers["Content-Type"] = $mime_t->to_string();
		$response->headers["ETag"] = $etag;
		$response->headers["Last-Modified"] = $last_modified;
		$response->response_body = $this->get_content();
		
		$this->dispatchEvent( new HTTPEvent("entry_get", $request, $response) );
		
		return $response;
	}
	
	public function http_PUT($request) {
		$response = new App_HTTPResponse();
		
		$time = $this->last_modified();
		$etag = '"'.md5($time).'"';
		
		/* Requires If-Match to match!!!*/
		if ( (!$request->header_exists('If-Match')) || 
					( $request->header_exists('If-Match') && 
						$etag != str_replace(";gzip","",$request->headers['If-Match']) ) ) {
			throw new App_HTTPException("Not the most recent version in cache.", 412);
		}
		if ( !$request->header_exists("Content-Type") ) {
			throw new App_HTTPException("No Content-Type header sent.", 400);
		}
		
		$content_type = $request->headers["Content-Type"];
		$mime_obj = new App_Mimetype($content_type);
		
		// convert to utf-8
		if ( $mime_obj->type == "text" && $mime_obj->parameter_exists("charset") ) {
			$charset = $mime_obj->parameters["charset"];
			
			$request->request_body = iconv($charset, "utf-8", $request->request_body);
		}
		
		$data = $request->request_body;
		
		$this->update($data);
		
		$this->response->http_status = "200 Ok";
		$this->response->response_body = "";
		
		$this->dispatchEvent( new HTTPEvent("entry_put", $request, $response) );
		
		return $response;
	}
	
	public function http_DELETE($request) {
		throw new App_HTTPException("Delete the media link entry instead!",400);
	}
}
