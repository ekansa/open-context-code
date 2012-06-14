<?php

class App_FileStore {

	private $dir;
	private $base_uri;

	public function __construct($dir, $base_uri) {
		$this->base_uri = $base_uri;
		$this->dir = $dir;
		//These directories are created in the 'public' directory (within the Zend context)
		if (!file_exists($dir)) {
			//echo '   making directory ' . $dir . '   ';
			mkdir($dir);
		}
	}
	
	public function store($uri, $data) {
		//echo '   App_FileDtore::store   ';
		$uri = $this->get_key($uri);
		
		$parts = split("/",$uri);
		
		$filename = array_pop($parts);
		
		$path = $this->dir;
		for ($i=0; $i<count($parts); $i++ ) {
			$path = $path."/".$parts[$i];
			if ( !file_exists($path) ) {
				//echo '   making directory ' . $path . '   ';
				mkdir($path);
			}
		}
		
		file_put_contents($path."/".$filename, $data);
	}
	
	public function get($uri) {
		$uri = $this->get_key($uri);
		
		if ( file_exists($this->dir.$uri) ) {
			return file_get_contents($this->dir.$uri);
		} else {
			return "";
		}
	}
	
	public function modified($uri) {
		$uri = $this->get_key($uri);
		if ( file_exists($this->dir.$uri) ) {
			return filemtime($this->dir.$uri);
		} else {
			return 0;
		}
	}
	public function exists($uri) {
		$uri = $this->get_key($uri);

		return file_exists($this->dir.$uri);
	}
	
	public function remove($uri) {
		$uri = $this->get_key($uri);
		if ( file_exists($this->dir.$uri) ) {
			unlink($this->dir.$uri);
		}
	}
	
	private function get_key($uri) {
		$uri = new URI($uri);
		$uri = $uri->base_on($this->base_uri);
		$uri = new URI("/".$uri);
		if ( strpos($uri, "\.\.") !== FALSE ) {
			throw new Exception("Invalid uri passed to store.");
		}
		return $uri;
	}

}

?>
