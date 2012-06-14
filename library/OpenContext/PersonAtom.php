<?php

class OpenContext_PersonAtom {
	
	const oc_ns_uri = "http://opencontext.org/schema/person_schema_v1.xsd"; // namespace uri for OC persons
	const arch_ns_uri = "http://ochre.lib.uchicago.edu/schema/Person/Person.xsd"; // namespace uri for archaeoml persons
	const atom_ns_uri = "http://www.w3.org/2005/Atom"; // namespace uri for Atom
	const geo_ns_uri = "http://www.georss.org/georss"; // namespace uri for GeoRSS
        const xhtml_ns_uri = "http://www.w3.org/1999/xhtml"; //namespace for xhtml
        const kml_ns_uri = "http://www.opengis.net/kml/2.2"; //namespace for kml
		
	public static function atom_entry_feed($atom_entry_string, $view_count, $sp_view_count, $rank){
		//$atom_entry_string is a string object of Atom XML data stored in the MySQL database
		
		//echo $atom_entry_string;
		
		//$atom_entry_string = str_replace("http://about.opencontext.org/schema/person_schema_v1.xsd", self::oc_ns_uri, $atom_entry_string);
		
		
	
		$host = OpenContext_OCConfig::get_host_config();
		
		$person_dom = new DOMDocument("1.0", "utf-8");
                $person_dom->loadXML($atom_entry_string);
                    
                    
                $xpath = new DOMXpath($person_dom);
		
                    // Register OpenContext's namespace
                $xpath->registerNamespace("arch", OpenContext_OCConfig::get_namespace("arch", "person"));
                $xpath->registerNamespace("oc", OpenContext_OCConfig::get_namespace("oc", "person"));
                $xpath->registerNamespace("dc", OpenContext_OCConfig::get_namespace("dc"));
                $xpath->registerNamespace("atom", OpenContext_OCConfig::get_namespace("atom"));
                          
                
		if(!stristr($atom_entry_string, "<arch:person")){
			$idURI = false;
			$query = "/atom:feed/atom:id";
			$result_id = $xpath->query($query, $person_dom);
			if($result_id != null){
				$idURI = $result_id->item(0)->nodeValue;
			}
			if($idURI != false){
				$idarray = explode("/", $idURI);
				$id = $idarray[count($idarray)-1];
				$itemObj = new Person; //start person class
				$itemXML_string = $itemObj->getItemXML($id);
				$itemXML_string = str_replace('<?xml version="1.0" encoding="utf-8"?>', '', $itemXML_string);
				$itemXML_string = str_replace('<?xml version="1.0"?>', '', $itemXML_string);
				
				unset($itemObj);
				$contentFragment = $person_dom->createDocumentFragment();
				$contentFragment->appendXML($itemXML_string);  // add note xml string
				$query = "/atom:feed/atom:entry";
				$result_entry = $xpath->query($query, $person_dom);
				if($result_id != null){
					$result_entry_node = $result_entry->item(0);
				}
				$result_entry_node->appendChild($contentFragment);
			}
			
		}
		
		
		
		
		$query = "//arch:person";
		$result_arch = $xpath->query($query, $person_dom);
                if($result_arch != null){
			$arch_node = $result_arch->item(0);
                }
		
		$query = "//oc:social_usage";
		$result_soc = $xpath->query($query, $person_dom);
		$resultCount = 0;
		foreach($result_soc as $node){
			$resultCount++;
			OpenContext_PersonAtom::remove_children($node);
			$social_node = $node;
		}
                if($resultCount>0){
		
                }
		else{
			$social_node = $person_dom->createElement("oc:social_usage");
			$arch_node->appendChild($social_node);
		}
		
			$spview_node = $person_dom->createElement("oc:item_views");
			$spview_node->setAttribute("type", "spatialCount");
			$spcount_node = $person_dom->createElement("oc:count");
			$spcount_node ->setAttribute("rank", $rank["rank"]);
			$spcount_node ->setAttribute("pop", $rank["pop"]);
			$spcount_node_val  = $person_dom->createTextNode($sp_view_count);
			$spcount_node->appendChild($spcount_node_val);
			$spview_node->appendChild($spcount_node);
			$social_node->appendChild($spview_node);
			
			$view_node = $person_dom->createElement("oc:item_views");
			$view_node->setAttribute("type", "self");
			$count_node = $person_dom->createElement("oc:count");
			$count_node_val  = $person_dom->createTextNode($view_count);
			$count_node->appendChild($count_node_val);
			$view_node->appendChild($count_node);
			$social_node->appendChild($view_node);
		
		$query = "/atom:feed";      
                $person_dom_root = $xpath->query($query, $person_dom);      
                            
                $query = "//arch:person/arch:name/arch:string";
                $result_title = $xpath->query($query, $person_dom);
                if($result_title != null){
			$pers_item_name = $result_title->item(0)->nodeValue;
                }
                    
                $person_query_name = urlencode(OpenContext_UTF8::charset_decode_utf_8($pers_item_name));
                
		
		    
                $uri_to_query = $host."/sets/facets/.atom?person=".$person_query_name;

                    
                $pers_feed_xml = file_get_contents($uri_to_query);
                                    
		@$xml = simplexml_load_string($pers_feed_xml);

		if($xml){
			$person_feed_dom = new DOMDocument("1.0", "utf-8");
			$person_feed_dom->loadXML($pers_feed_xml);
                
			$xpath_feed = new DOMXpath($person_feed_dom);
			
			// Register OpenContext's namespace
			$xpath_feed->registerNamespace("atom", OpenContext_OCConfig::get_namespace("atom"));
			$xpath_feed->registerNamespace("arch", OpenContext_OCConfig::get_namespace("arch", "person"));
			$xpath_feed->registerNamespace("oc", OpenContext_OCConfig::get_namespace("oc", "person"));
			$xpath_feed->registerNamespace("dc", OpenContext_OCConfig::get_namespace("dc"));
	
			$query = "/atom:feed/atom:entry";
			$result_entries = $xpath_feed->query($query, $person_feed_dom);
			$nodecount = 0;
			    
			foreach($result_entries AS $sum_entry){
				
				$entry_cat = $sum_entry->getElementsByTagNameNS(OpenContext_OCConfig::get_namespace("atom") , "category");
				//$entry_category = $entry_cat->item(0)->getAttributeNS("http://www.w3.org/2005/Atom" , "term");
				$entry_category = $entry_cat->item(0)->getAttribute("term");
				//$entry_category = $sum_entry->getAttribute("term");
				if($entry_category != "related person"){
					$new_node = $person_dom->importNode($sum_entry, true);
					
					if($entry_category == "category"){
						
						$class_label_nl = $sum_entry->getElementsByTagNameNS(OpenContext_OCConfig::get_namespace("atom"), "title");
						$class_name = $class_label_nl->item(0)->nodeValue;
						$class_icon = OpenContext_PersonAtom::class_icon_lookup($class_name);
						
						$class_node = $person_dom->createElementNS(OpenContext_OCConfig::get_namespace("oc", "person"), "oc:item_class");
						$class_label_node = $person_dom->createElementNS(OpenContext_OCConfig::get_namespace("oc", "person"), "oc:name");
						$class_label_node_val  = $person_dom->createTextNode($class_name);
						$class_label_node->appendChild($class_label_node_val);
						$class_node->appendChild($class_label_node);
						$class_icon_node = $person_dom->createElementNS(OpenContext_OCConfig::get_namespace("oc", "person"), "oc:iconURI");
						$class_icon_node_val = $person_dom->createTextNode($class_icon);
						$class_icon_node->appendChild($class_icon_node_val);
						$class_node->appendChild($class_icon_node);
						$new_node->appendChild($class_node);
					}
					
					$person_dom_root->item(0)->appendChild($new_node);
				}
				//$nodecount ++;
			}
		} 
		    
                $xml_string = $person_dom->saveXML();
                    
                $xml_string = str_replace("<default:", "<", $xml_string);
                $xml_string = str_replace("</default:", "</", $xml_string);
                $xml_string = str_replace('<entry xmlns:default="http://www.w3.org/1999/xhtml" xmlns:oc="http://about.opencontext.org/schema/person_schema_v1.xsd">', chr(13)."<entry>".chr(13), $xml_string);
		$xml_string = str_replace('<entry xmlns:default="http://www.w3.org/1999/xhtml">', chr(13)."<entry>".chr(13), $xml_string);

		    
		return $xml_string;
		
	}//end function
	
	//remove all child nodes
	public static function remove_children(&$node) {
		while ($node->firstChild) {
		  while ($node->firstChild->firstChild) {
		    OpenContext_PersonAtom::remove_children($node->firstChild);
		  }
		  $node->removeChild($node->firstChild);
		}
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
		    $class_icon_uri = "http://www.opencontext.org/database/ui_images/oc_icons/".$class_icon_uri;
		}
		
		return $class_icon_uri;
	}//end function
	
	
        public static function project_root_lookup($project_name){
		
		$output = false;
		
		$db_params = OpenContext_OCConfig::get_db_config();
                $db = new Zend_Db_Adapter_Pdo_Mysql($db_params);
		$db->getConnection();
                $sql = 'SELECT projects.project_id, projects.proj_atom
                    FROM projects
                    WHERE projects.proj_name LIKE "'.$project_name.'"
                    LIMIT 1';
		
                $result = $db->fetchAll($sql, 2);
		$db->closeConnection();
		
		if($result){
                        $project_uuid = $result[0]["project_id"];
                        $atom_string = $result[0]["proj_atom"];
                        $project_atom_string = new DOMDocument("1.0", "utf-8");
                        $project_dom->loadXML($project_atom_string);
                            
                        $xpath = new DOMXpath($project_dom);
                        
                        // Register Project OpenContext's namespace
			$xpath->registerNamespace("atom", OpenContext_OCConfig::get_namespace("atom"));
			$xpath->registerNamespace("arch", OpenContext_OCConfig::get_namespace("arch", "person"));
			$xpath->registerNamespace("oc", OpenContext_OCConfig::get_namespace("oc", "person"));
                
                        $query = "//arch:project/arch:links/oc:space_links/oc:link[@project_root]/oc:name";
                        $result_title = $xpath->query($query, $project_dom);
                        if($result_title != null){
                                $project_root_name = $result_title->item(0)->nodeValue;
                        }
                        
                        $query = "//arch:project/arch:links/oc:space_links/oc:link[@project_root]/oc:id";
                        $result_id= $xpath->query($query, $project_dom);
                        if($result_id != null){
                                $project_root_id = $result_id->item(0)->nodeValue;
                        }
                        $query = "//arch:project/arch:links/oc:space_links/oc:link[@project_root]/oc:id";
                        $result_id= $xpath->query($query, $project_dom);
                        if($result_id != null){
                                $project_root_id = $result_id->item(0)->nodeValue;
                        }
                        $query = "//arch:project/arch:links/oc:space_links/oc:link[@project_root]/oc:item_class/oc:name";
                        $result_cl_name = $xpath->query($query, $project_dom);
                        if($result_cl_name != null){
                                $project_root_cl_name = $result_cl_name->item(0)->nodeValue;
                        }
                        $query = "//arch:project/arch:links/oc:space_links/oc:link[@project_root]/oc:item_class/oc:iconURI";
                        $result_cl_icon = $xpath->query($query, $project_dom);
                        if($result_cl_icon!= null){
                                $project_root_cl_icon = $result_cl_icon->item(0)->nodeValue;
                                if(substr_count($project_root_cl_icon, "http://")<1){
                                        $project_root_cl_icon = "http://www.opencontext.org/database/ui_images/oc_icons/".$project_root_cl_icon;
                                }
                        }
                
                        $host = OpenContext_OCConfig::get_host_config();
                        
                }
		
		return $output;
	}//end function
        
        
        
        
        public static function atom_entry_to_json($atom_entry_string){
		//$atom_entry_string is a string object of Atom XML data stored in the MySQL database
		
		$host = OpenContext_OCConfig::get_host_config();
		
		$person_dom = new DOMDocument("1.0", "utf-8");
                $person_dom->loadXML($atom_entry_string);
                    
                    
                $xpath = new DOMXpath($person_dom);
		
                    // Register OpenContext's namespace
                $xpath->registerNamespace("arch", OpenContext_OCConfig::get_namespace("arch", "person"));
                $xpath->registerNamespace("oc", OpenContext_OCConfig::get_namespace("oc", "person"));
                $xpath->registerNamespace("dc", OpenContext_OCConfig::get_namespace("dc"));
                $xpath->registerNamespace("atom", OpenContext_OCConfig::get_namespace("atom"));
                $xpath->registerNamespace("georss", OpenContext_OCConfig::get_namespace("georss"));
        
                $query = "//arch:person/arch:name/arch:string";
                $result_title = $xpath->query($query, $person_dom);
                if($result_title != null){
			$pers_item_name = $result_title->item(0)->nodeValue;
                }
                
                $query = "//oc:item_views[@type='spatialCount']/oc:count";
                $result_count = $xpath->query($query, $person_dom);
                if($result_count != null){
			if(@$result_count->item(0)->nodeValue){
				$space_count = $result_count->item(0)->nodeValue;
				$space_count = $space_count + 0;
			}
			else{
				$space_count = 0;
			}
                }
                
                $query = "//oc:item_views[@type='spatialCount']/oc:count/@rank";
                $result_rank = $xpath->query($query, $person_dom);
                if($result_rank != null){
			
			if(@$result_rank->item(0)->nodeValue){
				$space_rank = $result_rank->item(0)->nodeValue;
				 $space_rank = $space_rank + 0;
			}
			else{
				$space_rank = 0;
			}
                }
                
                $query = "//oc:item_views[@type='spatialCount']/oc:count/@pop";
                $result_pop = $xpath->query($query, $person_dom);
                if($result_pop != null){
			if(@$result_pop->item(0)->nodeValue){
				$person_pop = $result_pop->item(0)->nodeValue;
				$person_pop = $person_pop + 0;
			}
			else{
				$person_pop = 0;
			}
                }
                
                //$query = "//atom:entry/atom:category[@term = 'context']/preceding-sibling::georss:point/text()";
                $query = "//atom:entry/atom:category[@term = 'context']";
                $all_result_contexts = $xpath->query($query, $person_dom);
                
                $context_array = array();
                if($all_result_contexts != null){
                        
                        foreach($all_result_contexts as $act_resultcontext){
                                
                                $resultNode = $act_resultcontext->parentNode;
                                $newDom = new DOMDocument;
                                $newDom->appendChild($newDom->importNode($resultNode,1));
                                
                                $xpath_B = new DOMXpath($newDom);
                        
                                // Register OpenContext's namespace
				$xpath_B->registerNamespace("atom", OpenContext_OCConfig::get_namespace("atom"));
                                $xpath_B->registerNamespace("georss", OpenContext_OCConfig::get_namespace("georss"));
                                $xpath_B->registerNamespace("xhtml", OpenContext_OCConfig::get_namespace("xhtml"));
                                $xpath_B->registerNamespace("kml", OpenContext_OCConfig::get_namespace("kml"));
                                
                                $query = "//atom:title";
                                $result_name = $xpath_B->query($query, $newDom);
                                if($result_name != null){
                                        $context_name = $result_name->item(0)->nodeValue;
                                }
                                
				$context_link = false;
                                $query = "//atom:link[@type='application/xhtml+xml']/@href";
                                $result_link = $xpath_B->query($query, $newDom);
                                if($result_link != null){
					if($result_link->item(0)){
						$context_link = $result_link->item(0)->nodeValue;
					}
                                }
                                
                                $query = "//xhtml:span[@class='facetcount']";
                                $result_count = $xpath_B->query($query, $newDom);
                                if($result_count != null){
                                        $facet_count = $result_count->item(0)->nodeValue;
                                        $facet_count = $facet_count + 0;
                                }
                                
                                $geo_array = array();
                                $query = "//georss:point";
                                $result_geo = $xpath_B->query($query, $newDom);
                                if($result_geo != null){
                                        foreach($result_geo as $item){
                                                $geo_text = $item->nodeValue;
						if(stristr($geo_text, " ")){
							$act_geo_array = explode(" ", $geo_text);
							$lat = $act_geo_array [0] + 0;
							$lon = $act_geo_array [1] + 0;
							$geo_array[] = array("lat" => $lat, "long" => $lon);
						}
                                        }//end loop through geo items
                                }
                                
                                $time_array = array();
                                $query = "//kml:TimeSpan/kml:begin";
                                $result_begin = $xpath_B->query($query, $newDom);
                                if($result_begin != null){
					if(@$result_begin->item(0)->nodeValue){
						$kml_begin = $result_begin->item(0)->nodeValue;
						$time_array["begin"] = $kml_begin + 0;
					}
                                }
                                $query = "//kml:TimeSpan/kml:end";
                                $result_end = $xpath_B->query($query, $newDom);
                                if($result_end != null){
					if(@$result_end->item(0)->nodeValue){
						$kml_end = $result_end->item(0)->nodeValue;
						$time_array["end"] = $kml_end + 0;
					}
                                }
                                
                                $context_array[] = array("name" => $context_name,
                                                         "href" => $context_link,
                                                         "item_count" => $facet_count,
                                                         "geopoint" => $geo_array,
                                                         "timespan" => $time_array
                                                         );
                        
                        }//end loop through contexts
                }//end contexts
                
                
                //projects
                $query = "//atom:entry/atom:category[@term = 'project']";
                $all_result_projects = $xpath->query($query, $person_dom);
                
                $project_array = array();
                if($all_result_projects != null){
                        
                        foreach($all_result_projects as $act_resultproject){
                                
                                $resultNode = $act_resultproject->parentNode;
                                $newDom = new DOMDocument;
                                $newDom->appendChild($newDom->importNode($resultNode,1));
                                
                                $xpath_B = new DOMXpath($newDom);
                        
                                // Register OpenContext's namespace
				
                                $xpath_B->registerNamespace("atom", OpenContext_OCConfig::get_namespace("atom"));
                                $xpath_B->registerNamespace("xhtml", OpenContext_OCConfig::get_namespace("xhtml"));
                                
                                $query = "//atom:title";
                                $result_name = $xpath_B->query($query, $newDom);
                                if($result_name != null){
                                        $project_name = $result_name->item(0)->nodeValue;
                                }
                        
				$project_link = false;
                                $query = "//atom:link[@type='application/xhtml+xml']/@href";
                                $result_link = $xpath_B->query($query, $newDom);
                                if($result_link != null){
					if($result_link->item(0)){
						$project_link = $result_link->item(0)->nodeValue;
					}
                                }
                                
                                $query = "//xhtml:span[@class='facetcount']";
                                $result_count = $xpath_B->query($query, $newDom);
                                if($result_count != null){
                                        $facet_count = $result_count->item(0)->nodeValue;
                                        $facet_count = $facet_count + 0;
                                }
                                
                                $project_array[] = array("name" => $project_name,
                                                         "href" => $project_link,
                                                         "item_count" => $facet_count
                                                         );
                        }//end loop through projects
                        
                }//end case with projects
                
                
                //categories
                $query = "//atom:entry/atom:category[@term = 'category']";
                $all_result_categories = $xpath->query($query, $person_dom);
                
                $category_array = array();
                if($all_result_categories != null){
                        
                        foreach($all_result_categories as $act_resultcategory){
                                
                                $resultNode = $act_resultcategory->parentNode;
                                $newDom = new DOMDocument;
                                $newDom->appendChild($newDom->importNode($resultNode,1));
                                
                                $xpath_B = new DOMXpath($newDom);
                        
                                // Register OpenContext's namespace
                                $xpath_B->registerNamespace("atom", OpenContext_OCConfig::get_namespace("atom"));
                                $xpath_B->registerNamespace("oc", OpenContext_OCConfig::get_namespace("oc", "person"));
                                $xpath_B->registerNamespace("xhtml", OpenContext_OCConfig::get_namespace("xhtml"));
                                
                                $query = "//atom:title";
                                $result_name = $xpath_B->query($query, $newDom);
                                if($result_name != null){
                                        $cat_name = $result_name->item(0)->nodeValue;
                                }
                        
                                $query = "//atom:link[@type='application/xhtml+xml']/@href";
				$cat_link = false;
                                $result_link = $xpath_B->query($query, $newDom);
                                if($result_link != null){
					if($result_link->item(0)){
						$cat_link = $result_link->item(0)->nodeValue;
					}
                                }
                                
                                $query = "//xhtml:span[@class='facetcount']";
                                $result_count = $xpath_B->query($query, $newDom);
                                if($result_count != null){
                                        $facet_count = $result_count->item(0)->nodeValue;
                                        $facet_count = $facet_count + 0;
                                }
                                
                                $query = "//oc:iconURI";
				$icon_link = false;
                                $result_icon = $xpath_B->query($query, $newDom);
                                if($result_icon != null){
					if($result_icon->item(0)){
						$icon_link = $result_icon->item(0)->nodeValue;
					}
                                }
				if(!$icon_link){
					$icon_link = OpenContext_PersonAtom::class_icon_lookup($cat_name);
				}
				
				
                                
                                $category_array[] = array("name" => $cat_name,
                                                         "href" => $cat_link,
                                                         "item_count" => $facet_count,
                                                         "icon_href" => $icon_link,
                                                         );
                        }//end loop through categories
                        
                }//end case with categories
                
                
                //items with media
                $query = "//atom:entry/atom:category[@term = 'items with media']";
                $all_result_media = $xpath->query($query, $person_dom);
                
                $media_array = array();
                if($all_result_media != null){
                        
                        foreach($all_result_media as $act_resultmedia){
                                
                                $resultNode = $act_resultmedia->parentNode;
                                $newDom = new DOMDocument;
                                $newDom->appendChild($newDom->importNode($resultNode,1));
                                
                                $xpath_B = new DOMXpath($newDom);
                        
                                // Register OpenContext's namespace
                                $xpath_B->registerNamespace("atom", OpenContext_OCConfig::get_namespace("atom"));
                                $xpath_B->registerNamespace("xhtml", OpenContext_OCConfig::get_namespace("xhtml"));
                                
                                $query = "//atom:title";
                                $result_name = $xpath_B->query($query, $newDom);
                                if($result_name != null){
                                        $cat_name = $result_name->item(0)->nodeValue;
                                        $cat_name = str_replace("Diary", "Textual documentation", $cat_name);
                                }
                        
                                $query = "//atom:link[@type='application/xhtml+xml']/@href";
                                $result_link = $xpath_B->query($query, $newDom);
                                if($result_link != null){
                                        $cat_link = $result_link->item(0)->nodeValue;
                                }
                                
                                $query = "//xhtml:span[@class='facetcount']";
                                $result_count = $xpath_B->query($query, $newDom);
                                if($result_count != null){
                                        $facet_count = $result_count->item(0)->nodeValue;
                                        $facet_count = $facet_count + 0;
                                }
                                
                                
                                $media_array[] = array("name" => $cat_name,
                                                         "href" => $cat_link,
                                                         "item_count" => $facet_count,
                                                         );
                        }//end loop through categories
                        
                }//end case with categories
                
                
                $output_array = array("name"=> $pers_item_name,
                                      "item_view_count" => $space_count,
                                      "rank" => $space_rank,
                                      "of_people" => $person_pop,
                                      "contexts" => $context_array,
                                      "projects" => $project_array,
                                      "categories" => $category_array,
                                      "media" => $media_array
                                      );
        
                $output = Zend_Json::encode($output_array);
                
                return $output;
        
        }//end function
        
	
	/*
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
	*/
        
        
}//end class declaration

?>
