<?php

class URI {

	private $uri;
	
	public $components;

	public function __construct($uri) {
		$this->uri = $uri;
		
		$this->components = $this->get_components();
	}
	
	// get_component($name) -> string
	// component_exists($name) -> bool
	// is_absolute() -> bool
	// absolutize(URI $base)
	// relativize(URI $base)
	// resolve(URI $base) -> string
	// base_on(URI $base) -> string
	// to_string() -> string
	// URI::resolve_node($node, $base) -> URI
	
	public function get_component($name) {
		if ( array_key_exists($name, $this->components) ) {
			return $this->components[$name];
		} else {
			return "";
		}
	}
	public function component_exists($name) {
		return array_key_exists($name, $this->components);
	}
	public function is_absolute() {
		return $this->component_exists("scheme");
	}
	
	public function absolutize($base) {
		$base = $this->uri_obj($base);
		
		$uri = $this->resolve($base);
		
		$this->components = $uri->components;
	}
	public function relativize($base) {
		$base = $this->uri_obj($base);
		
		$uri = $this->base_on($base);
		
		$this->components = $uri->components;
	}
	
	public function resolve($base_uri) {
		$base_obj = $this->uri_obj($base_uri);
		
		$T = array();
		$R = $this->components;
		$Base = $base_obj->components;
		
		//print_r($R);
		//print_r($Base);
		
		if ( array_key_exists("scheme",$R) ) {
			// absolute URI
			$T["scheme"] = $R["scheme"];
			
			if ( array_key_exists("authority",$R) ) {
				$T["authority"] = $R["authority"];
			}
			if ( array_key_exists("path",$R) ) {
				$T["path"] = $this->remove_dot_segments($R["path"]);
			}
			if ( array_key_exists("query",$R) ) {
				$T["query"] = $R["query"];
			}
		} else {
			if ( array_key_exists("authority",$R) ) {
				$T["authority"] = $R["authority"];

				if ( array_key_exists("path",$R) ) {
					$T["path"] = $this->remove_dot_segments($R["path"]);
				}
				if ( array_key_exists("query",$R) ) {
					$T["query"] = $R["query"];
				}
			} else {
				if ( !array_key_exists("path",$R) ) {
					if ( array_key_exists("path",$Base) ) {
						$T["path"] = $Base["path"];
					}
					
					if ( array_key_exists("query",$R) ) {
						$T["query"] = $R["query"];
					} else {
						if ( array_key_exists("query",$Base) ) {
							$T["query"] = $Base["query"];
						}
					}
				} else {
					if ( $R["path"][0] == "/") {
						$T["path"] = $this->remove_dot_segments($R["path"]);
					} else {
						$T["path"] = $this->merge($Base, $R);
						$T["path"] = $this->remove_dot_segments($T["path"]);
					}
					if ( array_key_exists("query",$R) ) {
						$T["query"] = $R["query"];
					}
				}
				if ( array_key_exists("authority",$Base) ) {
					$T["authority"] = $Base["authority"];
				}
			}
			if ( array_key_exists("scheme",$Base) ) {
				$T["scheme"] = $Base["scheme"];
			}
		}
		
		if ( array_key_exists("fragment",$R) ) {
			$T["fragment"] = $R["fragment"];
		}
		
		return new URI( $this->compose_parts($T) );
	}
	
	public static function resolve_node($node, $base) {
		if ( is_string($base) ) {
			$base = new URI($base);
		}
		$uri = new URI($node->textContent);
		
		if ( $uri->is_absolute() ) {
			return $node->textContent;
		}
		
		// walk tree
		if ( $node->nodeType == XML_ATTRIBUTE_NODE ) {
			$element = $node->ownerElement;
		} else {
			$element = $node;
		}

		$resolved = $uri->walk_tree($element);
		
		return $resolved->resolve($base);
	}
	public function walk_tree($element) {
		$NS = "http://www.w3.org/XML/1998/namespace";
		if( $element->nodeType != XML_ELEMENT_NODE ) {
			return $this;
		}
		if ( $element->hasAttributeNS($NS,"base") ) { // xml:base
			// resolve relative uri later on
			$base = new URI($element->getAttributeNS($NS,"base"));

			if ( $base->is_absolute() ) {
				return $this->resolve($base);
			}
			
			$new_uri_str = $this->merge($base->components, $this->components);
			
			$uri = new URI($new_uri_str);

			if ( $uri->is_absolute() ) {
				return $uri;
			} else {
				if ( isset($element->parentNode) ) {
					return $uri->walk_tree($element->parentNode);
				} else {
					return $uri;
				}
			}
		} else { // no xml:base -> walk tree
			if ( isset($element->parentNode) ) {
				return $this->walk_tree($element->parentNode);
			} else {
				return $this;
			}
		}
	}
	
	public function base_on($base) {
		$base = $this->uri_obj($base);
		
		// both must be absolute
		if( !$base->is_absolute() || !$this->is_absolute() ) {
			throw new Exception("URIs must be absolute");
		}
		
		$base_path = split("/",$base->components["path"]);
		$path = split("/",$this->components["path"]);
		
		// check if any of the parts matches
		$min = min(count($base_path), count($path));
		for ($i=1; $i<$min; $i++) {
			if ( $base_path[$i] == $path[$i] ) {
				$match = $i;
			}
		}
	
		if ( isset($match) ) {
			$str = "";
			// $basepath
			$back = count($base_path)-2 - $match;
			for ($i=0; $i<$back; $i++) {
				if ( $i == $back-1 ) {
					$str = $str."..";
				} else {
					$str = $str."../";
				}
			}
			
			for ($i=$match+1; $i<count($path); $i++) {
				if ( $str=="" ) {
					$str = $path[$i];
				} else {
					$str = $str."/".$path[$i];
				}
			}
		} else {
			$str = $this->components["path"];
		}
		
		if ( $this->component_exists("query") ) {
			$str = $str."?".$this->components["query"];
		}
		
		// if no dir in common -> return relative to root
		return new URI($str);
	}
	
	public function to_string() {
		return $this->compose_parts($this->components);
	}
	public function __toString() {
		return $this->compose_parts($this->components);
	}
	public function get_extension() {
		$parts = split("\.",$this->components["path"]);
		return  $parts[count($parts)-1];
	}
	
	public function query_parameter($name) {
		if ( !$this->component_exists("query") ) {
			return "";
		}
		
		$parts = split("&", $this->components["query"]);
		foreach ( $parts as $part ) {
			$temp = split("=", $part);
			if ( is_array($temp) && $temp[0]==$name) {
				return $temp[1];
			}
		}
		
		return "";
	}
	
	private function uri_obj($test) {
		if ( is_string($test) ) {
			return new URI($test);
		} else {
			return $test;
		}
	}
	
	
	/**
	 * Return the components of an URI as defined in 
	 * {@link http://tools.ietf.org/html/rfc3986#section-3} RFC 3986}.
	 * 
	 * @param string $uri The URI.
	 * @return mixed An associative array, with the components in lowercase.
	 */
	private function get_components() {
		return $this->get_uri_components($this->uri);
	}
	private function get_uri_components($uri) {
		
		$ocomps = parse_url($uri);
		
		if ( $ocomps != FALSE ) {
			if ( array_key_exists("scheme",$ocomps) ) {
				$comps["scheme"] = $ocomps["scheme"];
			}
			if ( array_key_exists("host",$ocomps) ) {
				$comps["authority"] = $ocomps["host"];
			}
			if ( array_key_exists("path",$ocomps) ) {
				$comps["path"] = $ocomps["path"];
			}
			if ( array_key_exists("query",$ocomps) ) {
				$comps["query"] = $ocomps["query"];
			}
			if ( array_key_exists("fragment",$ocomps) ) {
				$comps["fragment"] = $ocomps["fragment"];
			}
		}
		
		return $comps;
	}

	private function remove_dot_segments($path) {
		$input = $path;
		$output = array();
		
		while( isset($input[0]) ) {
			if ( strpos($input,"../") === 0 || strpos($input,"./") === 0 ) {
				if (strpos($input,"../")===0) {
					$input = substr($input,3);
				} else {
					$input = substr($input,2);
				}
			} else if ( strpos($input,"/../")===0 ||
						(
							strpos($input, "/..")===0 && 
								( 
									(isset($input[3]) && $input[3] == "/") ||
									!isset($input[3])
								)
						) 
					) {
				if ( strpos($input, "/../") === 0) {
					$input = "/".substr($input, 4);
				} else {
					$input = "/".substr($input, 3);
				}
				
				if ( array_key_exists(count($output)-1, $output) ) {
					array_pop($output);
				}
				
			} else if ( strpos($input,"/./")===0 ||
						(
							strpos($input, "/.")===0 && 
								( 
									(isset($input[2]) && $input[2] == "/") ||
									!isset($input[2])
								)
						) 
					) {
				if ( strpos($input, "/./") === 0) {
					$input = "/".substr($input, 3);
				} else {
					$input = "/".substr($input, 2);
				}
			} else if ( $input === "." || $input === ".." ) {
				$input = "";
			} else {
				if ( strpos($input,"/") === 0 ) {
					$input = substr($input,1);
					
					if ( strpos($input,"/") === FALSE ) {
						array_push($output, "/".$input);
						$input = "";
					} else {
						array_push($output,"/".substr($input, 0, strpos($input, "/")));
						$input = substr($input, strpos($input,"/"));
					}
				} else {
					if ( strpos($input,"/") === FALSE ) {
						array_push($output, $input);
						$input = "";
					} else {
						array_push($output,substr($input, 0, strpos($input, "/")));
						$input = substr($input, strpos($input,"/"));
					}
				}
			}
		}
		
		return join($output, "");
	}
	
	private function merge($base, $rel) {
		if ( array_key_exists("authority",$base) && !array_key_exists("path",$base) ) {
			if ( array_key_exists("path",$rel) ) {
				return "/".$rel["path"];
			} else {
				return "/";
			}
		} else {
			
			if ( array_key_exists("path", $rel) ) {
				$slashindex = strpos($rel["path"],"/");
				if ($slashindex === 0) {
					return substr($rel["path"],0);
				} 
			}
			
			if ( array_key_exists("path", $base) ) {
				$slashindex = strrpos($base["path"],"/");
				if ($slashindex === FALSE) {
					if ( array_key_exists("path",$rel) ) {
						return $rel["path"];
					}
				} else {
					return substr($base["path"],0,$slashindex+1).$rel["path"];
				}
			}
		}
	}
	
	private function compose_parts($parts) {
		$uri = array();
		
		if ( array_key_exists("scheme",$parts) ) {
			array_push($uri, $parts["scheme"]."://");
		}
		if ( array_key_exists("authority",$parts) ) {
			array_push($uri, $parts["authority"]);
		}
		if ( array_key_exists("path",$parts) ) {
			array_push($uri, $parts["path"]);
		}
		if ( array_key_exists("query",$parts) && $parts["query"]!=="" ) {
			array_push($uri, "?".$parts["query"]);
		}
		if ( array_key_exists("fragment",$parts) ) {
			array_push($uri, $parts["fragment"]);
		}
		
		return join($uri, "");
	}
	

}
