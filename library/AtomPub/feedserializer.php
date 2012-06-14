<?php

class FeedSerializer {

	private $xmldecl = '<?xml version="1.0" encoding="utf-8"?>';
	private $result = array();
	
	private $knownspaces = array(
		"http://www.w3.org/XML/1998/namespace" => "xml",
		"http://www.w3.org/1999/xhtml" => "html",
		"http://www.w3.org/2005/Atom" => "atom",
		"http://www.w3.org/2007/app" => "app",
		"http://www.w3.org/2000/svg" => "svg",
		"http://www.w3.org/1999/xlink" => "xlink",
		"http://www.w3.org/1998/Math/MathML" => "math",
		"http://purl.org/syndication/history/1.0" => "fh",
		"http://purl.org/syndication/thread/1.0" => "thr"
	);
	private $namespaces = array();
	private $nscounter = 0;
	
	public function writeToString($doc) {
		$this->result = array();
		$this->namespaces= array();
	
		$this->result[] = $this->xmldecl;
		
		$this->serializeElement($doc->documentElement, "http://www.w3.org/2005/Atom");
		
		$nslist = ' xmlns="http://www.w3.org/2005/Atom"';
		foreach ( $this->namespaces as $ns=>$ns_prefix ) {
			if ( $ns !== "http://www.w3.org/XML/1998/namespace" ) {
				$nslist = $nslist. ' xmlns:'.$ns_prefix.'="'.$ns.'"';
			}
		}
		$this->result[1] = '<'.$doc->documentElement->localName.$nslist;
		
		return implode("",$this->result);
	}
	
	private function serializeElement($el, $default_ns) {
		$ns_uri = $el->namespaceURI;
		$prefix = $this->get_prefix($ns_uri);

		if ( $prefix == "" ) {
			$this->result[] = '<'.$el->localName;
			
			if ( $ns_uri == "http://www.w3.org/1999/xhtml" && $default_ns!=$ns_uri) {
				$default_ns = "http://www.w3.org/1999/xhtml";
				$this->result[] = ' xmlns="http://www.w3.org/1999/xhtml"';
			}
			if ( $ns_uri == "http://www.w3.org/2005/Atom" && $default_ns!=$ns_uri) {
				$default_ns = "http://www.w3.org/2005/Atom";
				$this->result[] = ' xmlns="http://www.w3.org/2005/Atom"';
			}
			if ( $ns_uri == "" && $default_ns!=$ns_uri) {
				$default_ns = "";
				$this->result[] = ' xmlns=""';
			}
			
		} else {
			$this->result[] = '<'.$prefix.':'.$el->localName;
		}
		
		if ($el->hasAttributes()) {
			$attrs = $el->attributes;
			for ($i=0; $i < $attrs->length; $i++) {
				$attr = $attrs->item($i);
				if ($attr->namespaceURI != 'http://www.w3.org/2000/xmlns/' ) {
					$atprefix = $this->get_prefix($attr->namespaceURI);
					
					if ($atprefix == "") {
						$atname =$attr->localName;
					} else {
						$atname = $atprefix.":".$attr->localName;
					}
					$this->result[] = " ".$atname.'="'.htmlspecialchars($attr->nodeValue).'"';
				}
			}
		}
		if ($el->childNodes->length === 0) {
			$this->result[]="/>";
			return;
		}
		
		$this->result[] = ">";
		
		for($i=0; $i < $el->childNodes->length; $i++) {
			$child = $el->childNodes->item($i);
			if ($child->nodeType == XML_ELEMENT_NODE) {
				$this->serializeElement($child, $default_ns);
			} else if ($child->nodeType == XML_TEXT_NODE || $child->nodeType == XML_CDATA_SECTION_NODE) {
				$this->result[] = htmlspecialchars($child->nodeValue);
			} else if ($child->nodeType == XML_COMMENT_NODE) {
				$this->result[] = "<!--".$child->nodeValue."-->";
			}
		}
			
		if ( $prefix == "" ) {
			$this->result[] = '</'.$el->localName.'>';
		} else {
			$this->result[] = '</'.$prefix.':'.$el->localName.'>';
		}
	}
	
	private function get_prefix($uri) {
		if ($uri == ""||$uri=="null") {
			return "";
		}
		if ($uri == "http://www.w3.org/2005/Atom" || $uri == "http://www.w3.org/1999/xhtml") {
			return "";
		}
		
		if ( !array_key_exists($uri, $this->namespaces) ) {
			if ( array_key_exists($uri, $this->knownspaces) ) {
				$this->namespaces[$uri] = $this->knownspaces[$uri];
			} else {
				$this->namespaces[$uri] = "ns".$this->nscounter;
				$this->nscounter++;
			}
		}
		return $this->namespaces[$uri];
	}
}