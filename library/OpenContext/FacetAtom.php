<?php


class OpenContext_FacetAtom {
	
	
	const atom_ns_uri = "http://www.w3.org/2005/Atom"; // namespace uri for Atom
	const dc_ns_uri = "http://purl.org/dc/elements/1.1/"; // namespace uri for Dublin Core
        const geo_ns_uri = "http://www.georss.org/georss"; // namespace uri for GeoRSS
        const xhtml_ns_uri = "http://www.w3.org/1999/xhtml"; //namespace for xhtml
        const kml_ns_uri = "http://www.opengis.net/kml/2.2"; //namespace for kml
        
        const path_to_class_icon = "http://www.opencontext.org/database/ui_images/oc_icons/"; // path to class icon, if missing
	
        
        public static function get_namespace_uri($type){
                
                $output = false;
                if($type == "arch"){
                        $output = self::arch_ns_uri;
                }
                if($type == "oc"){
                        $output = self::oc_ns_uri;
                }
                
                return $output;
        }
        
        
        
        	
	public static function atom_to_object($facet_url){
		   
                $atom_feed_string = file_get_contents($facet_url);
                
		@$atomXML = simplexml_load_string($atom_feed_string);
        
		if($atomXML){
		   
			$atom_feed_dom = new DOMDocument("1.0", "utf-8");
			$atom_feed_dom->loadXML($atom_feed_string);
			    
			$xpath_feed = new DOMXpath($atom_feed_dom);
			
			// Register OpenContext's namespace
			$xpath_feed->registerNamespace("atom", self::atom_ns_uri);
			
			$query = "/atom:feed/atom:entry";
			$result_entries = $xpath_feed->query($query, $atom_feed_dom);
			$nodecount = 0;
			
			$facet_array = array();
			$last_type = false;
			
			foreach($result_entries as $entry){
				
				$entryNode = $entry->parentNode;
				$newDom = new DOMDocument;
				$newDom->appendChild($newDom->importNode($entry,1));
				
				$xpath_B = new DOMXpath($newDom);
			
				// Register OpenContext's namespace
				$xpath_B->registerNamespace("atom", self::atom_ns_uri);
				$xpath_B->registerNamespace("xhtml", self::xhtml_ns_uri);
				$xpath_B->registerNamespace("georss", self::geo_ns_uri);
				$xpath_B->registerNamespace("kml", self::kml_ns_uri);
				
				$query = "//atom:title";
				$result_name = $xpath_B->query($query, $newDom);
				if($result_name != null){
					$facetName = OpenContext_UTF8::charset_decode_utf_8($result_name->item(0)->nodeValue);
					//$facetName = ($result_name->item(0)->nodeValue);
				}
			
				$query = "//atom:link[@rel='alternate']/@href";
				$result_link = $xpath_B->query($query, $newDom);
				if($result_link != null){
					$setLink = $result_link->item(0)->nodeValue;
				}
				
				$query = "//atom:link[@rel='self']/@href";
				$result_link = $xpath_B->query($query, $newDom);
				if($result_link != null){
					$facetLink = $result_link->item(0)->nodeValue;
					$facetLink = str_replace(".atom", ".json", $facetLink);
				}
				
				$resultLink = false;
				$query = "//atom:link[@rel='results']/@href";
				$result_link = $xpath_B->query($query, $newDom);
				if($result_link != null){
					@$resultLink = $result_link->item(0)->nodeValue;
					$resultLink = str_replace(".atom", ".json", $resultLink);
				}
				
				if(!$resultLink){
					$resultLink = str_replace("sets/facets", "sets", $facetLink);
				}
				
				
				$query = "//xhtml:span[@class='facetcount']";
				$result_count = $xpath_B->query($query, $newDom);
				if($result_count != null){
					$facet_count = $result_count->item(0)->nodeValue;
					$facet_count = $facet_count + 0;
				}
				
				$result_link  = null;
				$query = "//atom:category/@term";
				$result_link = $xpath_B->query($query, $newDom);
				if($result_link != null){
					if($result_link->item(0)){
						$facet_type = $result_link->item(0)->nodeValue;
					}
				}
				
				
				if($facet_type != $last_type){
					if($last_type != false){
						$facet_array[$last_type] = $act_type_facets;
						unset($act_type_facets);
					}
					$last_type = $facet_type;
					$act_type_facets = array();
				}
				
				
				$facet = array("name"=>$facetName, "href"=>$setLink, "facet_href"=>$facetLink, "result_href"=>$resultLink, "count"=>$facet_count);
				
				if($facet_type == "context"){
					$query = "//georss:point";
					$result_link = $xpath_B->query($query, $newDom);
					if($result_link != null){
						if($result_link->item(0)){
							$geoPoint = $result_link->item(0)->nodeValue;
							$geoArray = explode(" ", $geoPoint);
							$geoLat = $geoArray[0]+0;
							$geoLong = $geoArray[1]+0;
						}
					}
					$query = "//kml:begin";
					$result_link = $xpath_B->query($query, $newDom);
					if($result_link != null){
						if($result_link->item(0)){
							$timeBegin = $result_link->item(0)->nodeValue;
							$timeBegin += 0;
						}
					}
					$query = "//kml:end";
					$result_link = $xpath_B->query($query, $newDom);
					if($result_link != null){
						if($result_link->item(0)){
							$timeEnd = $result_link->item(0)->nodeValue;
							$timeEnd += 0;
						}
					}
					$facet["geoTime"]=array("geoLat"=>$geoLat, "geoLong"=>$geoLong, "timeBegin"=>$timeBegin, "timeEnd"=>$timeEnd);
				}
				
				
				
				
				$act_type_facets[] = $facet;
				
				if($facet_type == "date range"){
					$facet_array[$facet_type] = $act_type_facets;
				}
				
				/*
				if($facet_type == "category"){
					$facet_array[$facet_type] = $act_type_facets;
				}
				*/
			}//end loop through entries
                
		}//end case with valid XML
		else{
			$facet_array = false;
		}
		
                //echo var_dump($facet_array);
                return $facet_array;
                        
        }//end atom to object function
        
	
		
	
        
	public static function facet_object_timemap($facetObj){
		
		$itemsArray = array();
		$itemCount = 0;
		foreach($facetObj as $facetType=>$facets){
		    
			if($facetType == "context"){
                
				foreach($facets as $actFacet){
				    
				    $infoHtml = "<p class='bodyText'><strong>".$actFacet["name"]."</strong> (".$actFacet["count"]." items)</p>";
				    
				    
				    
				    $itemArray = array("start"=> ($actFacet["geoTime"]["timeBegin"])."",
						       "end"=> ($actFacet["geoTime"]["timeEnd"])."",
						       "point"=> array("lat" => $actFacet["geoTime"]["geoLat"], "lon" => $actFacet["geoTime"]["geoLong"]),
						       "title"=> $actFacet["name"],
						       "options"=> array("infoHtml" => $infoHtml)
						       );
				    
				    $itemsArray[] = $itemArray;
				}
			
			}
		}

        
		return $itemsArray;
		
	}
	
	
	
        
	public static function class_icon_lookup($class_name){
		
		$class_icon_uri = false;
		
		$db_params = OpenContext_OCConfig::get_db_config();
                $db = new Zend_Db_Adapter_Pdo_Mysql($db_params);
		$db->getConnection();
                $sql = 'SELECT sp_classes.sm_class_icon
                    FROM sp_classes
                    WHERE sp_classes.class_label LIKE "'.$class_name.'"
                    LIMIT 1';
		
                $result = $db->fetchAll($sql, 2);
		$db->closeConnection();
		
		if($result){
                    $class_icon_uri = $result[0]["sm_class_icon"];
		    $class_icon_uri = (self::path_to_class_icon).$class_icon_uri;
		}
		
		return $class_icon_uri;
	}//end function
	
        
	//this function removes query strings from variables
	public static function remove_querystring_var($url, $key) {
		$ampfix = false;
		if(substr_count($url, "&amp;")>0){
			$ampfix = true;
			$url = str_replace("&amp;", "&", $url);
		}
		
		$url = preg_replace('/(.*)(\?|&)' . $key . '=[^&]+?(&)(.*)/i', '$1$2$4', $url . '&');
		$url = substr($url, 0, -1);
		
		/*
		if($ampfix){
			$url = str_replace("&", "&amp;", $url);
			$url = str_replace("&amp;amp", "&amp;", $url);
		}
		*/
		
		return ($url);
	}
	
	  
        
}//end class declaration

?>
