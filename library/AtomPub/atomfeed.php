<?php

require_once("appfilestore.php");
require_once("appentry.php");

require_once("appevents.php");
require_once("appuritemplate.php");

require_once("httpresponse.php");
require_once("httpexception.php");

require_once("feedserializer.php");

/**
 * A feed is a time ordered collection of entries.
 * A feed consists of multiple pages.
 * Pages are accessed by adding a "page" query parameter to the URI.
 * A feed has a name: currently the first path component after the base URI of the service.
 *
 * Feeds are HTTP resources. They implement the GET method.
 *
 * Feeds fire the following HTTP events:
 * - before_collection_get
 * - collection_get
 */
class Atom_Feed extends EventHTTPResource {
	
	public $base_uri;
	
	public $name;
	public $pagenr;
	
	public $page_length = 10;
	
	protected $service;
	
	public $atom_store; // ATOM_STORE_DIR
	public $list_store; // LIST_STORE_DIR
	public $feed_cache; // FEED_CACHE_DIR

    /**
     * Create a new Feed object with the given URI and belonging to the indicated service.
     *
     * @param URI $uri The URI of this feed.
     * @param App_Servicedoc $service The service document associated with this feed.
     */
	public function __construct($uri, $service) {
		parent::__construct($uri);
		
		$this->base_uri = $service->base_uri;

		$this->name = str_replace("/","",$this->uri->base_on($this->base_uri));
		$nameparts = split("\?",$this->name);
		if (is_array($nameparts)) {
			$this->name = $nameparts[0];
		}

		$this->pagenr = $uri->query_parameter("page");
		if ( $this->pagenr === "" ) {
			$this->pagenr = 1;
		}
		
		$this->service = $service;
                //var_dump($this->service)
		
		// create 3 "App_FileStore" objects:  "store," "lists," and "cache"
		if (!defined("ATOM_STORE_DIR")) { //define / defined:  code that defines a constant
			define("ATOM_STORE_DIR", "store");
		}
		$this->atom_store = new App_FileStore(ATOM_STORE_DIR, $this->base_uri);
		
		if (!defined("LIST_STORE_DIR")) {
			define("LIST_STORE_DIR", "lists");
		}
		$this->list_store = new App_FileStore(LIST_STORE_DIR, $this->base_uri);
		
		if (!defined("FEED_CACHE_DIR")) {
			define("FEED_CACHE_DIR", "cache");
		}
		$this->feed_cache = new App_FileStore(FEED_CACHE_DIR, $this->base_uri);
	}

    /**
     * Request a representation of this feed with the GET method.
     *
     * @param App_HTTPRequest $request The HTTP request object.
     *
     * @return App_HTTPResponse The HTTP response to the request.
     */
	public function http_GET($request) {
		$response = new App_HTTPResponse();
		
		$this->dispatchEvent( new HTTPEvent("before_collection_get", $request, $response) );
		
		// Caching
		$time = $this->last_modified();
		
		$etag = '"'.md5($time.json_encode($this->get_collection_list())).'"';
		$last_modified = $this->time_to_gmt($time);
		
		if ( $this->try_cache($request, $response, 
			array("ETag" => $etag, "Last-Modified" => $last_modified)) ) 
		{
			$this->dispatchEvent( new HTTPEvent("collection_get", $request, $response) );
			return $response;
		}

		// Not cached
		$data = $this->get_feed_page();
                //echo 'Get feed page... ';
                //var_dump($data);
                //echo '---------end feed page-------';
		$response->http_status = "200 Ok";
		$response->headers["Content-Type"] = "application/atom+xml;type=feed";
		$response->headers["ETag"] = $etag;
		$response->headers["Last-Modified"] = $last_modified;
		$response->response_body = $data;
		
		$this->dispatchEvent( new HTTPEvent("collection_get", $request, $response) );
		
		$this->try_gzip($request, $response);
		
		return $response;
	}
	
	/*
	 * Feed Generation
	 * Sarah VW:  This page gets the feed and all of the associated entries
	 * for the particular page (for the valid URLs specified in the service.xml document)
	 */
	 public function get_feed_page() {
		// Check if the collection exists
                //echo '    Atom_Feed::get_feed_page(): ' . $this->uri . '   '; 
		if ( !$this->service->collection_exists($this->uri) )
                {
                        //echo '   Couldn\'t find the collection.   ';
			throw new App_HTTPException("Collection does not exist.",404);
		}
		
		$key = $this->get_page_key($this->pagenr);
                //echo '____the key: ' . $key . '_____';
		if ( !isset($this->feed_cache) || !$this->feed_cache->exists($key))
                {
			$doc = $this->create_page();
			
			$fs = new FeedSerializer();
			$data = $fs->writeToString($doc);
			
                        //isset:  determines if variable is set and is not null
			if ( isset($this->feed_cache) ) { 
				$this->feed_cache->store($key, $data);
			
				$pages_list = $this->get_pages_list();
				$pages_list[] = $key;
				$this->save_pages_list($pages_list);
			}
			
			return $data;
		}
		
		return $this->feed_cache->get($key);
	}
	
	public function last_modified() {
		return $this->list_last_modified();
	}
	
	protected function create_page() {
		$feed = $this->create_feed_dom();
		
		$list = $this->get_collection_list();
		
		$total_entries = count($list);
		$start = ($this->pagenr-1)*$this->page_length;
		$end = $start + $this->page_length;
		if ($end > $total_entries) {
			$end = $total_entries;
		}
		if ($start >= $total_entries && ($total_entries !== 0 || $this->pagenr > 1)) {
			throw new App_HTTPException("Page does not exist.",404);
		}
		if ( $this->pagenr == 0 ) {
			$start = 0;
			$end = $total_entries;
		}
		
		$this->add_paging_to_feed($feed, $this->pagenr, $total_entries);
		
		for ($i=$start; $i<$end; $i++) {
			$uri = new URI($list[$i]["URI"]);

			$entry = new App_Entry($uri, $this);

			$entry_doc = $entry->get_document();
			$entry_el = $feed->importNode($entry_doc->documentElement, true);
		
			$feed->documentElement->appendChild($entry_el);
		}
                
		//echo $feed->saveXML();
		return $feed;
	}
        
        /**
         * SV:  This function gets a blank xml template document from the templates
         * directory and appends 'title,' 'identifier,' and 'last updated by' nodes.
        */
	protected function create_feed_dom()
        {
                //echo 'create_feed_dom';
		$feed = $this->get_feed_template();
                //echo $feed->saveXML();
		$domain = $this->uri->components["authority"];
		
		$titles = $feed->getElementsByTagName("title");
		$ids = $feed->getElementsByTagName("id");
		$updates = $feed->getElementsByTagName("updated");
		
		// required elements
		if ( $titles->length == 0) {
			$title = $this->service->get_collection_title($this->uri);
			if ($title == "" || $title == FALSE) {
				$title = "$domain $this->name";
			}
		
			$title_el = $feed->createElementNS("http://www.w3.org/2005/Atom","title");
			$title_el->appendChild( $feed->createTextNode( htmlspecialchars($title) ) );
			$feed->documentElement->appendChild($title_el);
		}
		if ( $ids->length == 0) {
			$id = "tag:".$domain.",".date("Y").":".$this->name;
		
			$id_el = $feed->createElementNS("http://www.w3.org/2005/Atom","id");
			$id_el->appendChild( $feed->createTextNode( $id ) );
			$feed->documentElement->appendChild($id_el);
		}
		if ( $updates->length == 0 ) {
			$update_el = $feed->createElementNS("http://www.w3.org/2005/Atom","updated");
			$update_el->appendChild( $feed->createTextNode( date(DATE_ATOM,$this->last_modified()) ) );
			$feed->documentElement->appendChild($update_el);
		}
		//echo $feed->saveXML();
		return $feed;
	}

	private function add_paging_to_feed($feed, $pagenr, $total) {
		$nr_pages = (int)($total/$this->page_length);
		if ( $total % $this->page_length != 0 ) {
			$nr_pages = $nr_pages + 1;
		}
		
		if ( $pagenr == 0 ) {
			$complete = $feed->createElementNS("http://purl.org/syndication/history/1.0",
													"fh:complete");
			$feed->documentElement->appendChild($complete);
			$this->add_feed_link($feed, "self", $this->get_page_uri(0));
			return;
		}
		
		$this->add_feed_link($feed, "self", $this->get_page_uri($pagenr));
		$this->add_feed_link($feed, "first", $this->get_page_uri(1));
		if ( $nr_pages > 1 ) {
			$this->add_feed_link($feed, "last", $this->get_page_uri($nr_pages));
		}
		if ($pagenr > 1) {
			$this->add_feed_link($feed, "previous", $this->get_page_uri($pagenr-1));
		}
		if ($pagenr < $nr_pages && $nr_pages > 1) {
			$this->add_feed_link($feed, "next", $this->get_page_uri($pagenr+1));
		}
	}
	private function add_feed_link($feed, $rel, $href) {
		$link = $feed->createElementNS("http://www.w3.org/2005/Atom","link");
		$link->setAttribute("rel",$rel);
		$link->setAttribute("href",$href);
		$feed->documentElement->appendChild($link);
	}
	
	/*
	 * Collections list
	 * SV:  This function gets all of the entries for the given collection:
	 */
	protected function get_collection_list() {
		$key = $this->get_list_key();
                //echo '   get_collection_list(): ____'.$key.'_____';
		$js = $this->list_store->get($key);
                //var_dump($js);
		if ($js != "") {
			$entries = json_decode($js, TRUE);
		} else {
			$entries = json_decode("[]", TRUE);
		}
                //var_dump($entries);
		return $entries;
	}
	protected function save_collection_list($list) {
		$js = json_encode($list);
		$this->list_store->store($this->get_list_key(), $js);
	}
	protected function list_last_modified() {
		$key = $this->get_list_key();
		if ( $this->list_store->exists($key) ) {
			return $this->list_store->modified($key);
		} else {
			return time();
		}
	}
	/*
	 * Pages list
	 */
	protected function get_pages_list() {
		$key = $this->get_pagelist_key();
		$js = $this->list_store->get($key);
		if ($js != "") {
			$entries = json_decode($js, TRUE);
		} else {
			$entries = json_decode("[]", TRUE);
		}
		return $entries;
	}
	protected function save_pages_list($list) {
		$js = json_encode($list);
		$this->list_store->store($this->get_pagelist_key(), $js);
	}
	
	protected function update_pages() {
		if (!isset($this->feed_cache)) return;
		
		$list = $this->get_pages_list();
		foreach ( $list as $key ) {
			$this->feed_cache->remove($key);
		}
		$list = array();
		$this->save_pages_list($list);
	}
	
	protected function get_feed_template() {
                $feed = new DOMDocument();
		if (!defined("FEED_TEMPLATE_DIR")) {
			define("FEED_TEMPLATE_DIR", "templates");
		}
                //echo '-----' . FEED_TEMPLATE_DIR."/feed_".$this->name.".xml --------";
		if ( file_exists(FEED_TEMPLATE_DIR."/feed_".$this->name.".xml") )
                {
			//$feed = DOMDocument::load(FEED_TEMPLATE_DIR."/feed_".$this->name.".xml");
			$feed->load(FEED_TEMPLATE_DIR."/feed_".$this->name.".xml");
		}
                else
                {
			//$feed = DOMDocument::load(FEED_TEMPLATE_DIR."/feed.xml");
			$feed->load(FEED_TEMPLATE_DIR."/feed.xml");
		}
                //echo '______THE FEED ';
		//echo $feed->saveXML();
                //echo '______';
		return $feed;
	}
	
	protected function base_name() {
		return $this->base_uri.$this->name."/";
	}
	protected function get_page_key($nr) {
		$key = $this->base_name()."pages/".$nr.".atom";
		
		return $key;
	}
	protected function get_list_key() {
		return $this->base_name()."list/list.json";
	}
	protected function get_pagelist_key() {
		return $this->base_name()."pages/list.json";
	}
	protected function get_page_uri($nr) {
		$temp = new URI($this->uri);
		$temp->components["query"] = "";
		if ( $nr == 1 ) {
			return $temp;
		} else {
			return $temp."?page=".$nr;
		}
	}
	
}

/**
 * CollectionManager manages all data of a collection.
 * It does not check the validity of the data. Neither does it test if the collection exists
 * at all.
 */
class CollectionManager {

    /*
     * Location of this collection and service.
     */
    private $uri;
    private $base_uri;

    /*
     * Simple key-value stores for lists and data
     */
    private $list_store;
    private $data_store;

    /*
     * The list that's being worked on.
     */
    private $working_list;

    /**
     * Creata a new collection manager.
     * @param URI $uri The URI of the collection.
     * @param URI $base_uri The base URI of the service.
     */
    public function __construct($uri, $base_uri) {
        $this->uri = $uri;
        $this->base_uri = $base_uri;

        if (!defined("ATOM_STORE_DIR")) {
            define("ATOM_STORE_DIR", "store");
        }
        if (!defined("LIST_STORE_DIR")) {
            define("LIST_STORE_DIR", "lists");
        }

        $this->data_store = new App_FileStore(ATOM_STORE_DIR, $base_uri);
        $this->list_store = new App_FileStore(LIST_STORE_DIR, $base_uri);
    }

    /**
     * Get a list of entries in this collection.
     * @return array A list [{URI, last modified}]
     */
    public function list_entries() {
    }

    /**
     * Add a new entry to the collection.
     * This method does not save the new state (use commit()).
     * @param URI $urli The URI of the entry
     */
    public function add_entry($uri) {

    }

    /**
     * Remove an entry from the collection.
     * This method does not save the new state (use commit()).
     * @param URI The URI of the entry.
     */
    public function remove_entry($uri) {
    }

    /**
     * Notify the collection of an update.
     * The feed ordering changes because of the update.
     * This method does not save the new state (use commit()).
     * @param URI The URI of the entry
     */
    public function update_entry($uri) {
    }

    /**
     * Commit all pending changes to the collection state.
     */
    public function commit() {
    }

    /**
     * Return the last modified date of the collection.
     * @return int The unix timestamp of the last modification.
     */
    public function last_modified() {
    }

    /**
     * Return the last modified date of an entry.
     * @param URI $uri The URI of the entry.
     * @return int The unix timestamp of the last modification.
     */
    public function entry_last_modified($uri) {
    }

    /**
     * Get the data at the given URI.
     * @param URI $uri The URI of the data.
     * @return String The data.
     */
    public function get_data($uri) {
    }

    /**
     * Immediately save the given data.
     * @param URI $uri The URI of the entry.
     * @param String $data The data.
     */
    public function save_data($uri, $data) {
    }
}
