<?php

require_once("atomfeed.php");

class App_Category extends Atom_Feed {

	public function __construct($uri, $service) {
		if ( is_string($uri) ) { // name
			$name = $uri;
			$nuri = new URI("-/".$name."/");
			$uri = $nuri->resolve($service->base_uri);
			
			$this->name = "-/".$name;
		}
	
		parent::__construct($uri, $service);
		
		// get name from URI
		$r_uri = $this->uri->base_on($this->base_uri);
		$nparts = split("/",$r_uri);
		$this->name = "-/".$nparts[1];
	}
	
	public function get_feed_page() {
		
		$key = $this->get_page_key($this->pagenr);
		if ( !$this->feed_cache->exists($key) ) {
			$doc = $this->create_page();
			
			$fs = new FeedSerializer();
			$data = $fs->writeToString($doc);
			
			$this->feed_cache->store($key, $data);
			
			$pages_list = $this->get_pages_list();
			$pages_list[] = $key;
			$this->save_pages_list($pages_list);
			
			return $data;
		}
		
		return $this->feed_cache->get($key);
	}
	
	public function add_entry($entry) {
		$list = $this->get_collection_list();
		
		array_unshift($list, array("URI"=>$entry->uri->to_string(), "Edit"=>time()) );
		
		$this->save_collection_list($list);
		$this->update_pages();
		
	}
	
	public function update_entry($entry) {
		$list = $this->get_collection_list();
		
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
	
	public function remove_entry($entry) {
		$list = $this->get_collection_list();
		
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
	
	protected function base_name() {
		return $this->base_uri.$this->name."/";
	}
}
