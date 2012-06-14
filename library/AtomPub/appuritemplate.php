<?php

class App_URITemplate {
	
	private $expr;
	
	public function __construct($expr) {
		//echo 'Expression: ' . $expr . ' --- ';
		$this->expr = new URI($expr);
	}
	
	private function add_slash_to_end($str)
	{
		$pos = strlen($str) - 1;
		if($str[$pos] != '/')
			return $str . '/';
		return $str;
	}
	
	public function matches($uri) {
		//echo $this->expr->components["path"] . '_____';
		//echo $uri->components["path"] . '_____';
		//echo $this->expr->components["path"] . ' ... ';
		//echo $uri->components["path"] . ' ... ';
		//return;
		$parts = split("/", $this->add_slash_to_end($this->expr->components["path"]));
		$uriparts = split("/", $this->add_slash_to_end($uri->components["path"]));
		
		//var_dump($parts);
		//var_dump($uriparts);
		
		//Zend_Debug::dump($parts);
		//Zend_Debug::dump($uriparts);
		
		if ( count($parts) != count($uriparts) ) {
			return FALSE;
		}
		
		$matches = array();
		for ($i=0; $i<count($parts); $i++) {
			if ( strpos($parts[$i], "{") !== FALSE ) { // variable
				$varname = substr($parts[$i],1,strlen($parts[$i])-2);
				
				$matches[$varname] = $uriparts[$i];
			} else {
				if ( $parts[$i] != $uriparts[$i] ) {
					return FALSE;
				}
			}
		}
		return $matches;
	}
	
}