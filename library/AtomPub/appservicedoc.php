<?php

require_once("appmimetype.php");
require_once("appuri.php");

require_once("httpresponse.php");
require_once("httpresource.php");

class App_Servicedoc extends HTTPResource{
	
	private $filename;
	private $doc;
	public $base_uri;
	public $collection_specific_dir;
	
	public function __construct($filename, $base_uri) {
		$uri = $base_uri->resolve("service");
		parent::__construct($uri);
	
		$this->filename = $filename;
		$this->base_uri = $base_uri;
	}
	
	private function add_slash_to_end($str)
	{
		$pos = strlen($str) - 1;
		if($str[$pos] != '/')
			return $str . '/';
		return $str;
	}
	
	// $name is a full URI
	public function collection_exists($uri) {
		//echo $uri;
		$col = $this->find_collection($uri);
		//echo 'COLS 2: ' . Zend_Debug::dump($col);
		if ( $col !== FALSE ) {
			return TRUE;
		} else {
			return FALSE;
		}
	}
	
	public function get_servicedoc() {
		//echo $this->filename;
		if ( !isset($this->doc) )
		{
			$doc = new DOMDocument();
			$doc->load($this->filename);
			//for debugging:  to see if the service doc was found:
			//echo $doc->saveXML();
			$this->doc = $doc;
			
		}
		return $this->doc;
	}
	public function save_servicedoc() {
		$this->doc->save($this->filename);
	}
	
	public function mimetype_accepted($mimetype, $collection_uri) {
		if ( !isset($this->doc) ) {
			$this->get_servicedoc();
		}
		$cols = $this->doc->getElementsByTagNameNS("http://www.w3.org/2007/app","collection");
		
		foreach ( $cols as $col ) {
			if ( URI::resolve_node($col->getAttributeNode("href"),$this->uri) == $collection_uri ) {
				
				$accepts = $col->getElementsByTagNameNS("http://www.w3.org/2007/app","accept");
				
				// No accepts -> only application/atom+xml is allowed
				if ($accepts->length == 0) {
					$accepted_type = new App_Mimetype("application/atom+xml");
					if ( $mimetype->is_a($accepted_type) ) {
						return TRUE;
					}
				}
				
				// 1 empty accept -> nothing allowed
				if ($accepts->length == 1) {
					if (trim($accepts->item(0)->textContent) == "") {
						return FALSE;
					}
				}
				
				foreach ( $accepts as $accept ) {
					$accepted_text = $accept->textContent;
					
					$accepted_type = new App_Mimetype($accepted_text);
					
					if ( $mimetype->is_a($accepted_type) ) {
						return TRUE;
					}
				}
				
			}
		}
		
		return FALSE;
	}
	
	/**
	 * Check if a given term is allowed in a collection.
	 * Does not resolve external app:categories
	 *
	 * @param string $term The term.
	 * @param string $collection The URI of the collection.
	 *
	 * @return boolean Indicates whether a category is allowed.
	 */
	public function category_allowed($term, $collection_uri) {
		$col = $this->find_collection($collection_uri);

		if ( $col === FALSE ) {
			return FALSE;
		}

		$catlist = $col->getElementsByTagNameNS("http://www.w3.org/2007/app","categories");

		if ( $catlist->length > 0 ) {
			$list = $catlist->item(0);

			// if fixed==no -> allow
			if ( $list->hasAttribute("fixed") ) {
				if ( trim($list->getAttribute("fixed"))=="no" ) {
					return TRUE;
				} else {
					$cats = $list->getElementsByTagNameNS("http://www.w3.org/2005/Atom","category");

					foreach ( $cats as $cat ) {
						
						if (trim($cat->getAttribute("term"))==$term) {
							return TRUE;
						}
					}
					
					return FALSE;
				}
			} else {
				return TRUE;
			}
			
		} else { // no categories
			return TRUE;
		}

	}
	
	/**
	 * Get the title of a collection.
	 *
	 * @param string $uri The URI of the collection.
	 *
	 * @return string The title.
	 */
	public function get_collection_title($uri) {
		$col = $this->find_collection($uri);
		
		if ( $col === FALSE ) {
			return FALSE;
		}
		
		$title = $col->getElementsByTagNameNS("http://www.w3.org/2005/Atom","title");
		if ($title->length > 0) {
			return $title->item(0)->textContent;
		} else {
			return "";
		}
	}
	
	public function find_collection($uri) {
		//echo '   App_Servicedoc::find_collection   ';
		//echo 'find collection...' . $uri . '<br />';
		if ( !isset($this->doc) ) {
			$this->get_servicedoc();
		}
		
		//debug:  see if service doc exists
		//echo $this->doc->saveXML();
		
		/*foreach ($this->doc->getElementsByTagNameNS('http://www.w3.org/2007/app', 'collection') as $element) {
			echo 'local name: ', $element->localName, ', prefix: ', $element->prefix, "<br />";
		}*/
		
		
		
		$cols = $this->doc->getElementsByTagNameNS("http://www.w3.org/2007/app","collection");
		$parts = split("\?",$uri);
		if ( is_array($parts) ) {
			$uri = $parts[0];
		}
		$uri = $this->add_slash_to_end($uri);
		
		//echo '  trimmed URI:  ' . $uri . '    collections:   ';
		//echo ' ' . $uri;
		//echo '   Number of collection nodes found: ' . $cols->length . '   ';
		
		foreach ( $cols as $col )
		{
			//trying to resolve these URLs in the browser:
			//echo $this->uri, $uri, "<br />";
			//Zend_Debug::dump($col->getAttributeNode("href")->value);
			$collectionURI = URI::resolve_node($col->getAttributeNode("href"),$this->uri);
			//echo '   node: ' . $col->getAttribute("href") . '  +  ' . $collectionURI . '  +  ' . var_dump($uri);
			if ($collectionURI == $uri )
			{
				//if the requested URI is equal to one of the URIs in the service.xml
				//and the URI actually exists:
				return $col;
			}
		}
		
		return FALSE;
	}
	
	/*
	 * HTTP methods
	 */
	 
	 public function http_GET($request) {
	 	$response = new App_HTTPResponse();
		
		$etag = '"'.md5(filemtime($this->filename)).'"';
		
		if ( $this->try_cache($request, $response, array("ETag" => $etag)) ) {
			return $response;
		}
		
		$response->http_status = "200 Ok";
		$response->response_body = file_get_contents($this->filename);
		
		$response->headers["Content-Type"] = "application/atomsvc+xml";
		$response->headers['Cache-Control'] = "must-revalidate";
		$response->headers['ETag'] = $etag;
		
		$this->try_gzip($request, $response);
		
		return $response;
	 }
}

