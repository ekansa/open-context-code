<?php

require_once("httpresponse.php");
require_once("httpresource.php");
require_once("httpexception.php");

require_once("appentry.php");
require_once("appmediaresource.php");
require_once("appmimetype.php");
require_once("appcleaner.php");
require_once("appmultipart.php");

require_once("atomfeed.php");
require_once("feedserializer.php");

class App_Collection extends Atom_Feed {
	
	public function __construct($uri, $service) {
		parent::__construct($uri, $service);
	}
	
	/*
	 * HTTP Methods
	 */
	public function http_POST($request) {
		$response = new App_HTTPResponse();
		
		$this->dispatchEvent( new HTTPEvent("before_collection_post", $request, $response) );
		
		if ( !$request->header_exists("Content-Type") ) {
			throw new App_HTTPException("No Content-Type header sent.", 400);
		}
		
		$name = $this->give_name($this->parse_slug($request));
		$content_type = new App_Mimetype($request->headers["Content-Type"]);
		
		$entry = $this->create_entry($name, $request->request_body, $content_type);
		
		$time = $entry->last_modified();
		
		$etag = '"'.md5($time).'"';
		$last_modified = $this->time_to_gmt($time);
		
		$response->http_status = "201 Created";
		$response->headers["Content-Type"] = "application/atom+xml;type=entry";
		$response->headers["Location"] = $entry->uri;
		$response->headers["Content-Location"] = $entry->uri;
		$response->headers["ETag"] = $etag;
		$response->headers["Last-Modified"] = $last_modified;
		
		$fs = new FeedSerializer();
		$response->response_body = $fs->writeToString($entry->get_document());
		
		$this->dispatchEvent( new HTTPEvent("collection_post", $request, $response) );
		
		$this->try_gzip($request, $response);
		
		return $response;
	}
	
	/*
	 * Collection methods
	 */
	public function get_entry($uri)
	{
		//echo 'getting entry...';
		//echo $uri . ' _____ ';
		if (!$this->atom_store->exists($uri)) {
			throw new App_HTTPException("Resource does not exist.",404);
		}
		
		if ($uri->get_extension() == "atomentry") {
			$entry = new App_Entry($uri, $this);
		} else {
			$entry = new App_MediaResource($uri, $this);
		}
		
		$this->attachEvents($entry);
		
		$this->dispatchEvent( new APPEvent("entry_open", $entry) );
		
		return $entry;
	}
	
	public function create_entry($name, $data, $content_type) {
		// Check if the collection exists
		//echo $this->uri . ' --- '; 
		if ( !$this->service->collection_exists($this->uri) ) {
			throw new App_HTTPException("Collection does not exist.", 404);
		}
		
		if ($content_type->type=="multipart") {
			if ($content_type->subtype=="related") {
				$entry = $this->create_multipart($name, $data, $content_type);
			} else if ($content_type->subtype=="form-data") {
				$entry = $this->create_formdata($name);
			} else {
				throw new App_HTTPException("Unsupported Media Type.",415);
			}
		} else {
			// Check if the collection accepts a given media type
			if ( !$this->is_supported_media_type($content_type) ) {
				throw new App_HTTPException("Unsupported Media Type.",415);
			}
			
			if ( $this->mimetype_is_atom($content_type) ) {
				// Add an atom entry
				// feeds also go down this path, but they will be filtered out later on
				$entry = $this->create_entry_resource($name, $data);
			} else {
				// Media entry
				$media_resource = $this->create_media_resource($name, $data, $content_type);
				$entry = $this->create_media_link_entry($name, $media_resource);
			}
		}
		
		$this->attachEvents($entry);
		
		$this->dispatchEvent( new APPEvent("entry_create", $entry) );
		
		$entry->save(); // save only if everything successful.
		
		return $entry;
	}
	
	public function is_supported_media_type($content_type) {
		return $this->service->mimetype_accepted($content_type, $this->uri);
	}
	
	/*
	 * To maintain collection state.
	 */
	public function add_entry($event) {
		$list = $this->get_collection_list();
		$entry = $event->entry;
		
		array_unshift($list, array("URI"=>$entry->uri->to_string(), "Edit"=>time()) );
		
		$this->save_collection_list($list);
		$this->update_pages();
	}
	
	public function update_entry($event) {
		$list = $this->get_collection_list();
		$entry = $event->entry;
		
		// find entry
		for ($i=0; $i<count($list); $i++) {
			if ($list[$i]["URI"] == $entry->uri->to_string() ) {
				$index = $i;
			}
		}
		
		// last edited -> first entry in collection
		if (isset($index)) {
			$item = array_splice($list,$index,1);
			$item[0]["Edit"] = time();
			array_unshift($list, $item[0]);
		}
		
		$this->save_collection_list($list);
		$this->update_pages();
	}
	
	public function remove_entry($event) {
		$list = $this->get_collection_list();
		$entry = $event->entry;
		
		for ($i=0; $i<count($list); $i++) {
			if ($list[$i]["URI"] == $entry->uri->to_string() ) {
				$index = $i;
			}
		}
		
		if (isset($index)) {
			array_splice($list,$index,1);
		}
		
		$this->save_collection_list($list);
		$this->update_pages();
	}
	
	/*
	 * Entry Creation
	 */
	protected function parse_slug($request) {
		// Get a name from the Slug header
		if ( $request->header_exists("Slug") ) {
			$name = rawurlencode(
						preg_replace(
							"/([\;\/\?\:\@\&\=\+\$\,\! ])/",
							"-",
							rawurldecode($request->headers["Slug"])
						)
			);
		} else {
			$name = rand();
		}
		if ($name == "") {
			$name = rand();
		}
		return utf8_encode($name);
	}
	
	protected function entry_exists($uri) {
		$list = $this->get_collection_list();
		
		foreach( $list as $item ) {
			if ($item["URI"] == $uri ) {
				return TRUE;
			}
		}
		
		return FALSE;
	}
	
	protected function mimetype_is_atom($content_type) {
		if ($content_type->type=="application" && $content_type->subtype=="atom+xml") {
			if ( $content_type->parameter_exists("type") ) {
				if ( $content_type->parameters["type"]=="feed" ) {
					return FALSE;
				}
			}
			return TRUE;
		}
		return FALSE;
	}
	protected function is_atom_entry($doc) {
		$cA = ($doc->documentElement->localName == "entry");
		$cB = ($doc->documentElement->namespaceURI == "http://www.w3.org/2005/Atom");
		return $cA && $cB;
	}
	
	protected function create_entry_resource($name, $data) {
		$uri = new URI($this->base_uri.$this->name."/".$name.".atomentry");
	
		if ( $this->entry_exists($uri) ) {
			throw new App_HTTPException("Entry already exists.", 409);
		}
		
		// test for well-formed XML
		$entry_doc = new DOMDocument();
		$entry_doc->loadXML($data, LIBXML_NOWARNING+LIBXML_NOERROR);
		//$entry_doc = DOMDocument::loadXML($data, LIBXML_NOWARNING+LIBXML_NOERROR);
		if( !isset($entry_doc) || $entry_doc == FALSE ) {
			throw new App_HTTPException("XML Parsing failed!",400);
		}
		
		if ( !$this->is_atom_entry($entry_doc) ) {
			// atom file, but no entry -> disallow
			throw new App_HTTPException("Adding feeds to a collection is undefined.",400);
		}
		
		// link[@rel='edit']
		$link = $entry_doc->createElementNS("http://www.w3.org/2005/Atom","link");
		$link->setAttribute("rel","edit");
		$link->setAttribute("href", $uri);
		$entry_doc->documentElement->appendChild($link);
		
		// app:edited
		$edit = $entry_doc->createElementNS("http://www.w3.org/2007/app","app:edited");
		$edit->appendChild( $entry_doc->createTextNode(date(DATE_ATOM,time())) );
		$entry_doc->documentElement->appendChild($edit);
		
		// clean up
		$cleaner = new App_Cleaner($uri, $this->base_uri);
		$cleaner->make_conforming($entry_doc);
		
		$entry = new App_Entry($uri, $this);
		$entry->doc = $entry_doc;
		
		return $entry;
	}
	
	protected function create_media_resource($name, $data, $content_type) {
		$media_link_uri = new URI($this->base_uri.$this->name."/$name.atomentry");
		$extension = $content_type->get_extension();
		$media_resource_uri = new URI($this->base_uri.$this->name."/$name.".$extension);
		
		if ( $this->entry_exists($media_link_uri) ) {
			throw new App_HTTPException("Entry already exists.", 409);
		}
		
		// convert to utf-8
		if ( $content_type->type == "text" ) {
			if ( $content_type->parameter_exists("charset") ) {
				$charset = $content_type->parameters["charset"];
				
				$data = iconv($charset, "utf-8", $data);
			}
		}
		
		// media resource
		$media_resource = new App_MediaResource($media_resource_uri, $this);
		$media_resource->content = $data;
		
		return $media_resource;
	}
	
	protected function create_media_link_entry($name, $media_resource) {
		$media_link_uri = new URI($this->base_uri.$this->name."/$name.atomentry");
		
		$media_resource_uri = $media_resource->uri;
		$content_type = $media_resource->get_media_type();
		
		if ( $this->entry_exists($media_link_uri) ) {
			throw new App_HTTPException("Entry already exists.", 409);
		}
		
		if (!defined("FEED_TEMPLATE_DIR")) {
			define("FEED_TEMPLATE_DIR", "templates");
		}
		$doc = new DOMDocument();
		$doc->load(FEED_TEMPLATE_DIR."/medialink.xml");
		//$doc = DOMDocument::load(FEED_TEMPLATE_DIR."/medialink.xml");
		
		// required fields
		$doc->getElementsByTagName("title")->item(0)->appendChild( 
				$doc->createTextNode( utf8_encode(rawurldecode($name)) ) );
		$doc->getElementsByTagName("updated")->item(0)->appendChild( 
				$doc->createTextNode( date(DATE_ATOM) ) );
		$doc->getElementsByTagName("published")->item(0)->appendChild( 
				$doc->createTextNode( date(DATE_ATOM) ) );
		$doc->getElementsByTagName("content")->item(0)->setAttribute("type",$content_type);
		$doc->getElementsByTagName("content")->item(0)->setAttribute("src",$media_resource_uri);
		$doc->getElementsByTagName("link")->item(0)->setAttribute("href",$media_resource_uri);
		$doc->getElementsByTagName("link")->item(1)->setAttribute("href",$media_link_uri);
		
		// clean up
		$cleaner = new App_Cleaner($media_link_uri, $this->base_uri);
		$cleaner->make_conforming($doc);
		
		// app:edited
		$edit = $doc->createElementNS("http://www.w3.org/2007/app","app:edited");
		$edit->appendChild( $doc->createTextNode(date(DATE_ATOM,time())) );
		$doc->documentElement->appendChild($edit);
		
		// media link entry
		$media_link = new App_Entry($media_link_uri, $this);
		$media_link->doc = $doc;
		
		$media_link->media_resource = $media_resource;
		
		return $media_link;
	}
	
	protected function create_multipart($name, $data, $content_type) {
		if ( !$content_type->parameter_exists("boundary") ) {
			throw new App_HTTPException("No boundary in multipart message.", 400);
		}
		
		$mp = new App_Multipart($this, $content_type->parameters["boundary"], $name, $data);
		
		$atom_part = $mp->get_entry_part();
		$media_part = $mp->get_media_part();
		
		if ( !$media_part->header_exists("Content-Type") ) {
			return new App_HTTPException("No Content-Type found.", 400);
		}
		$content_type = new App_Mimetype($media_part->headers["Content-Type"]);
		
		if ( !$this->is_supported_media_type($content_type) ) {
			throw new App_HTTPException("Unsupported Media Type.",415);
		}
		
		$entry = $this->create_entry_resource($name, $atom_part->request_body);
		$media_resource = 
			$this->create_media_resource($name, $media_part->request_body, $content_type);
		
		$entry->media_resource = $media_resource;
		
		// Content-ID
		if ( !$media_part->header_exists("Content-ID") ) {
			return new App_HTTPException("No Content-ID found.", 400);
		}
		$cid = $media_part->headers["Content-ID"];
		
		$this->add_mediadata($entry, $media_resource);
		
		return $entry;
	}
    
	protected function create_formdata($name) {
		
		if (!array_key_exists("file", $_FILES)) {
			throw new App_HTTPException("Form field \"file\" missing.", 400);
		}
		
		$data = file_get_contents($_FILES["file"]["tmp_name"]);
		$content_type = new App_Mimetype($_FILES["file"]["type"]);
		
		if ( !$this->is_supported_media_type($content_type) ) {
			throw new App_HTTPException("Unsupported Media Type.",415);
		}
		
		$media_resource = $this->create_media_resource($name, $data, $content_type);
		
		if (array_key_exists("entry", $_POST) && $_POST["entry"]!="") {
			if (get_magic_quotes_gpc()) {
				$entry_data = stripslashes($_POST["entry"]);
			} else {
				$entry_data = $_POST["entry"];
			}
			$entry = $this->create_entry_resource($name, $entry_data);
			$entry->media_resource = $media_resource;
			
			$this->add_mediadata($entry, $media_resource);
		} else {
			$entry = $this->create_media_link_entry($name, $media_resource);
		}
		
		return $entry;
	}
	
	protected function add_mediadata($entry, $media_resource) {
		$doc = $entry->get_document();
		
		$editm = $doc->createElementNS("http://www.w3.org/2005/Atom","link");
		$editm->setAttribute("rel","edit-media");
		$editm->setAttribute("href",$media_resource->uri);
		$doc->documentElement->appendChild($editm);
		
		$contents = $doc->getElementsByTagNameNS("http://www.w3.org/2005/Atom","content");
		if ($contents->length==0) {
			$content = $doc->createElementNS("http://www.w3.org/2005/Atom","content");
			$doc->documentElement->appendChild($content);
		} else {
			$content=$contents->item(0);
		}
		$content->setAttribute("src",$media_resource->uri);
		$content->setAttribute("type",$media_resource->get_media_type());
	}

	/*
	 * Extension methods
	 */
	protected function give_name($slug) {
		return $slug;
	}
	
	public function propagateEvent($event) {
		$this->dispatchEvent($event);
	}
	
	protected function attachEvents($entry) {
		$events = array(
			"before_entry_update",
			"entry_update",
			"entry_remove",
			"entry_get",
			"entry_put",
			"entry_delete",
			"before_entry_get",
			"before_entry_put",
			"before_entry_delete"
		);
		foreach ($events as $event) {
			$entry->addEventListener($event, $this, "propagateEvent");
		}
		
		$this->addEventListener("entry_create", $this, "add_entry");
		$entry->addEventListener("entry_update", $this, "update_entry");
		$entry->addEventListener("entry_remove", $this, "remove_entry");
	}
}
