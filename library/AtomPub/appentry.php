<?php

require_once("appevents.php");
require_once("httpresponse.php");

require_once("appcleaner.php");
require_once("feedserializer.php");

class App_Entry extends EventHTTPResource {
	
	public $collection;
	public $doc;
	public $name;
	
	public function __construct($uri, $collection) {
		parent::__construct($uri);
		
		$this->collection = $collection;
		
		$this->store = $collection->atom_store;
		
		$r_uri = new URI( $uri->base_on($collection->uri) );
		$this->name = str_replace(".".$r_uri->get_extension(),"",$r_uri);
	}
	
	public function save() {
		$fs = new FeedSerializer();
		$this->store->store($this->uri, $fs->writeToString($this->get_document()));
		
		if (isset($this->media_resource)) {
			$this->media_resource->save();
		}
	}
	
	public function get_document() {
		if ( !isset($this->doc) ) {

			$data = $this->store->get($this->uri);
			if ($data == "") {
				throw new App_HTTPException("Error loading document.",500);
			}
			$this->doc = new DOMDocument();
			$this->doc->loadXML($data);
			//$this->doc = DOMDocument::loadXML($data);
		}
		return $this->doc;
	}
	
	public function update($document) {
		$this->dispatchEvent( new APPEvent("before_entry_update", $this) );
		
		$cleaner = new App_Cleaner($this->uri, $this->collection->base_uri);
		$cleaner->make_conforming($document);
		$cleaner->check_edit_links($document, $this->get_document());
		
		// app:edited
		$edits = $document->getElementsByTagNameNS("http://www.w3.org/2007/app","edited");
		foreach ($edits as $edit) {
			$edits->item(0)->parentNode->removeChild($edits->item(0));
		}
		
		$edit = $document->createElementNS("http://www.w3.org/2007/app","app:edited");
		$edit->appendChild( $document->createTextNode(date(DATE_ATOM,time())) );
		$document->documentElement->appendChild($edit);

		
		$this->doc = $document;
		
		$this->dispatchEvent( new APPEvent("entry_update", $this) );
		
		$this->save();
	}
	
	public function delete() {
		
		$this->dispatchEvent( new APPEvent("entry_remove", $this) );
		
		if ( $this->is_media_link() ) {
			$content = $this->get_document()->
				getElementsByTagNameNS("http://www.w3.org/2005/Atom","content")->item(0);
				
			$mime_t = new App_Mimetype($content->getAttribute("type"));
			$extension = $mime_t->get_extension();
			
			$key = str_replace(".atomentry",".".$extension,$this->uri);
			$this->store->remove($key);
		}
		
		$this->store->remove($this->uri);
	}
	
	public function last_modified() {
		return $this->store->modified($this->uri);
	}
	
	public function is_media_link() {
		$links = $this->get_document()->documentElement->childNodes;
		
		foreach ( $links as $link ) {
			if( $link->nodeType == XML_ELEMENT_NODE 
						&& $link->localName == "link"
						&& $link->namespaceURI == "http://www.w3.org/2005/Atom") {
				if ( strcasecmp(trim($link->getAttribute("rel")), "edit-media") == 0) {
					return TRUE;
				}
			}
		}
		
		return FALSE;
	}
	public function get_media_link_uri() {
		$links = $this->get_document()->documentElement->childNodes;
		
		foreach ( $links as $link ) {
			if( $link->nodeType == XML_ELEMENT_NODE 
						&& $link->localName == "link"
						&& $link->namespaceURI == "http://www.w3.org/2005/Atom") {
				if ( strcasecmp(trim($link->getAttribute("rel")), "edit-media") == 0) {
					return URI::resolve_node($link->getAttributeNode("href"), $this->uri);
				}
			}
		}
		
		return FALSE;
	}
	
	/*
	 * HTTP Methods
	 */
	public function http_GET($request) {
		$response = new App_HTTPResponse();
		
		$this->dispatchEvent( new HTTPEvent("before_entry_get", $request, $response) );
		
		$time = $this->last_modified();
		
		$etag = '"'.md5($time).'"';
		$last_modified = $this->time_to_gmt($time);
		
		if ( $this->try_cache($request, $response, 
			array("ETag" => $etag, "Last-Modified" => $last_modified)) ) 
		{
			return $response;
		}
		
		$response->http_status = "200 Ok";
		$response->headers["Content-Type"] = "application/atom+xml;type=entry";
		$response->headers["ETag"] = $etag;
		$response->headers["Last-Modified"] = $last_modified;
		$response->response_body = $this->get_document()->saveXML();
		
		$this->dispatchEvent( new HTTPEvent("entry_get", $request, $response) );
		
		$this->try_gzip($request, $response);
		
		return $response;
	}
	
	public function http_PUT($request) {
		$response = new App_HTTPResponse();
		
		$this->dispatchEvent( new HTTPEvent("before_entry_put", $request, $response) );
		
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
		$doc = new DOMDocument();
		$doc->loadXML($request->request_body, LIBXML_NOWARNING+LIBXML_NOERROR);
		//$doc = DOMDocument::loadXML($request->request_body, LIBXML_NOWARNING+LIBXML_NOERROR);
		if( !isset($doc) || $doc == FALSE ) {
			throw new App_HTTPException("XML Parsing failed!",400);
		}
		
		$this->update($doc);
		
		$this->response->http_status = "200 Ok";
		$this->response->response_body = "";
		
		$this->dispatchEvent( new HTTPEvent("entry_put", $request, $response) );
		
		return $response;
	}
	
	public function http_DELETE($request) {
		$response = new App_HTTPResponse();
		
		$this->dispatchEvent( new HTTPEvent("before_entry_delete", $request, $response) );
		
		$this->delete();
		
		$response->http_status = "200 Ok";
		$response->headers["Content-Type"] = "text/plain";
		$response->headers["Cache-Control"]="no-cache";
		$response->response_body = "Resource Removed";
		
		$this->dispatchEvent( new HTTPEvent("entry_delete", $request, $response) );
		
		return $response;
	}
}
