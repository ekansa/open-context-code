<?php


class OpenContext_ProjectAtomJson {
	
	const oc_ns_uri = "http://opencontext.org/schema/project_schema_v1.xsd"; // namespace uri for OC persons
	const arch_ns_uri = "http://ochre.lib.uchicago.edu/schema/Project/Project.xsd"; // namespace uri for archaeoml persons
	const atom_ns_uri = "http://www.w3.org/2005/Atom"; // namespace uri for Atom
	const dc_ns_uri = "http://purl.org/dc/elements/1.1/"; // namespace uri for Dublin Core
        const geo_ns_uri = "http://www.georss.org/georss"; // namespace uri for GeoRSS
        const xhtml_ns_uri = "http://www.w3.org/1999/xhtml"; //namespace for xhtml
        const kml_ns_uri = "http://www.opengis.net/kml/2.2"; //namespace for kml
        
        const path_to_class_icon = "http://www.opencontext.org/database/ui_images/oc_icons/"; // path to class icon, if missing
	
        
        
        public static function get_namespace_uri($type){
                
                $output = false;
                if($type == "arch"){
                        $output = OpenContext_OCConfig::get_namespace("arch", "project");
                }
                if($type == "oc"){
                        $output = OpenContext_OCConfig::get_namespace("oc", "project");
                }
                
                return $output;
        }
        
        
        
        	
	public static function project_atom_feed($proj_atom_string, $view_count, $sp_view_count, $rank){
		//$proj_atom_string is a string object of Atom XML data stored in the MySQL database
		
		$host = OpenContext_OCConfig::get_host_config();
		
		$proj_atom_string = mb_convert_encoding($proj_atom_string, "utf-8");
		$proj_dom = new DOMDocument("1.0", "utf-8");
		//$proj_dom = new DOMDocument("1.0");
		$proj_dom->loadXML($proj_atom_string);
			 
			 
		$xpath = new DOMXpath($proj_dom);

			 // Register OpenContext's namespace
		$xpath->registerNamespace("arch", OpenContext_OCConfig::get_namespace("arch", "project"));
		$xpath->registerNamespace("oc", OpenContext_OCConfig::get_namespace("oc", "project"));
		$xpath->registerNamespace("dc", OpenContext_OCConfig::get_namespace("dc"));
		$xpath->registerNamespace("atom", OpenContext_OCConfig::get_namespace("atom"));
					 
                
		
		$query = "//arch:project";
		$result_arch = $xpath->query($query, $proj_dom);
      if($result_arch != null){
			$arch_node = $result_arch->item(0);
      }
		
		$projectUUID = false;
		$query = "//arch:project/@UUID";
		$resultID = $xpath->query($query, $proj_dom);
      if($resultID != null){
			$projectUUID = $resultID->item(0)->nodeValue;
      }
		
		
		$query = "//oc:social_usage";
		$result_soc = $xpath->query($query, $proj_dom);
		$resultCount = 0;
		foreach($result_soc as $node){
			$resultCount++;
		}
                if($resultCount>0){
			
			/*
			$query = "//oc:item_views[@type='self']/oc:count";
			$result_views = $xpath->query($query, $proj_dom);
			if($result_views != null){
				$result_views->item(0)->nodeValue = $view_count;	
			}
			*/
                }
		else{
			$social_node = $proj_dom->createElement("oc:social_usage");
			$spview_node = $proj_dom->createElement("oc:item_views");
			$spview_node->setAttribute("type", "spatialCount");
			$spcount_node = $proj_dom->createElement("oc:count");
			$spcount_node ->setAttribute("rank", $rank["rank"]);
			$spcount_node ->setAttribute("pop", $rank["pop"]);
			$spcount_node_val  = $proj_dom->createTextNode($sp_view_count);
			$spcount_node->appendChild($spcount_node_val);
			$spview_node->appendChild($spcount_node);
			$social_node->appendChild($spview_node);
			
			$view_node = $proj_dom->createElement("oc:item_views");
			$view_node->setAttribute("type", "self");
			$count_node = $proj_dom->createElement("oc:count");
			$count_node_val  = $proj_dom->createTextNode($view_count);
			$count_node->appendChild($count_node_val);
			$view_node->appendChild($count_node);
			$social_node->appendChild($view_node);
			
			
			$arch_node->appendChild($social_node);
		}
		
		$query = "/atom:feed";      
                $proj_dom_root = $xpath->query($query, $proj_dom);      
                            
                $query = "//arch:project/arch:name/arch:string";
                $result_title = $xpath->query($query, $proj_dom);
                if($result_title != null){
			$proj_item_name = $result_title->item(0)->nodeValue;
                }
     
		//$relatedTabs = OpenContext_ProjectTables::temp_find_related_tables($proj_item_name);
		$relatedTabs = OpenContext_ProjectTables::find_related_tables($projectUUID);
		if($relatedTabs != false){
			$query = "//arch:links/oc:media_links";
			$mediaNodeList = $xpath->query($query, $proj_dom);
			$mediaReady = false;
			if($mediaNodeList != null){
				$mediaNode = $mediaNodeList->item(0);
				if($mediaNode != null){
					$mediaReady = true;
				}
				
			}
			
			if(!$mediaReady){
				$query = "//arch:links";
				$linksNodeList = $xpath->query($query, $proj_dom);
				$linksNode = $linksNodeList->item(0);
				$mediaNode = $proj_dom->createElement("oc:media_links");
				$linksNode->appendChild($mediaNode);
			}
			
			$mediaTest = $proj_dom->createElement("oc:media_test");
			foreach($relatedTabs as $relatedTab){
				
				$tableID = OpenContext_TableOutput::tableID_toURL($relatedTab["tableID"]); //convert multipart table to nice URL
				$tabLink = $host."/tables/".$tableID;
				$tabLinkNode = $proj_dom->createElement("oc:link");
				$tabLinkNode->setAttribute("href", $tabLink);
				
				$tabNameNode = $proj_dom->createElement("oc:name");
				$tabNameNode_val = $proj_dom->createTextNode($relatedTab["tabName"]);
				$tabNameNode->appendChild($tabNameNode_val);
				$tabLinkNode->appendChild($tabNameNode);
				
				$tabIDNode = $proj_dom->createElement("oc:id");
				$tabIDNode_val = $proj_dom->createTextNode($tableID);
				$tabIDNode->appendChild($tabIDNode_val);
				$tabLinkNode->appendChild($tabIDNode);
				
				$tabRelNode = $proj_dom->createElement("oc:relation");
				$tabRelNode_val = $proj_dom->createTextNode("Standard");
				$tabRelNode->appendChild($tabRelNode_val);
				$tabLinkNode->appendChild($tabRelNode);
				
				$tabTypeNode = $proj_dom->createElement("oc:type");
				$tabTypeNode_val = $proj_dom->createTextNode("csv");
				$tabTypeNode->appendChild($tabTypeNode_val);
				$tabLinkNode->appendChild($tabTypeNode);
				
				$tabThumbNode = $proj_dom->createElement("oc:thumbnailURI");
				$tabThumbNode_val = $proj_dom->createTextNode("http://static.alexandriaarchive.org/images/general/table_icon.png");
				$tabThumbNode->appendChild($tabThumbNode_val);
				$tabLinkNode->appendChild($tabThumbNode);
				
				$mediaNode->appendChild($tabLinkNode);
			}
			
		}
		
		
		
		
		
		
		
		
		
		
		$proj_root_path = "/";
		$query = "//oc:manage_info/oc:rootPath";
                $result_path = $xpath->query($query, $proj_dom);
                if($result_path != null){
			$proj_root_path = $result_path->item(0)->nodeValue;
                }
		    
                $proj_query_name = urlencode(OpenContext_UTF8::charset_decode_utf_8($proj_item_name));
                    
                $uri_to_query = $host."/sets/facets".$proj_root_path.".atom?proj=".$proj_query_name;

					if($projectUUID != false){
						$uri_to_query = $host."/sets/facets".$proj_root_path.".atom?projID=".$projectUUID ;
					}
					echo $uri_to_query;
                $proj_feed_xml = file_get_contents($uri_to_query);
                
		@$xml = simplexml_load_string($proj_feed_xml);
		
		if($xml){
			unset($xml);
			$proj_feed_dom = new DOMDocument("1.0", "utf-8");
			$proj_feed_dom->loadXML($proj_feed_xml);
			    
			$xpath_feed = new DOMXpath($proj_feed_dom);
			
			// Register OpenContext's namespace
			$xpath_feed->registerNamespace("arch", OpenContext_OCConfig::get_namespace("arch", "project"));
			$xpath_feed->registerNamespace("oc", OpenContext_OCConfig::get_namespace("oc", "project"));
			$xpath_feed->registerNamespace("dc", OpenContext_OCConfig::get_namespace("dc"));
			$xpath_feed->registerNamespace("atom", OpenContext_OCConfig::get_namespace("atom"));
	
			$query = "/atom:feed/atom:entry";
			$result_entries = $xpath_feed->query($query, $proj_feed_dom);
			$nodecount = 0;
			    
			foreach($result_entries AS $sum_entry){
				
				$entry_cat = $sum_entry->getElementsByTagNameNS(self::atom_ns_uri , "category");
				//$entry_category = $entry_cat->item(0)->getAttributeNS("http://www.w3.org/2005/Atom" , "term");
				$entry_category = $entry_cat->item(0)->getAttribute("term");
				//$entry_category = $sum_entry->getAttribute("term");
				if($entry_category != "project"){
					$new_node = $proj_dom->importNode($sum_entry, true);
					
					if($entry_category == "category"){
						
						$class_label_nl = $sum_entry->getElementsByTagNameNS(self::atom_ns_uri , "title");
						$class_name = $class_label_nl->item(0)->nodeValue;
						$class_icon = OpenContext_ProjectAtomJson::class_icon_lookup($class_name);
						
						$class_node = $proj_dom->createElementNS(self::oc_ns_uri, "oc:item_class");
						$class_label_node = $proj_dom->createElementNS(self::oc_ns_uri, "oc:name");
						$class_label_node_val  = $proj_dom->createTextNode($class_name);
						$class_label_node->appendChild($class_label_node_val);
						$class_node->appendChild($class_label_node);
						$class_icon_node = $proj_dom->createElementNS(self::oc_ns_uri, "oc:iconURI");
						$class_icon_node_val = $proj_dom->createTextNode($class_icon);
						$class_icon_node->appendChild($class_icon_node_val);
						$class_node->appendChild($class_icon_node);
						$new_node->appendChild($class_node);
					}
					
					if($entry_category == "context"){
						
						$item_label_nl = $sum_entry->getElementsByTagNameNS(self::atom_ns_uri , "title");
						$item_name = $item_label_nl->item(0)->nodeValue;
						
						$class_name = false;
						$query = "//arch:links/oc:space_links/oc:link[@project_root='".$item_name."']/oc:item_class/oc:name";
						$result_class = $xpath->query($query, $proj_dom);
						if($result_class != null){
							if(@$class_name = $result_class->item(0)->nodeValue){
								$class_name = $result_class->item(0)->nodeValue;
							}
						}
						
						if(!$class_name){
							$class_name = "Site"; //default
						}
						
						$class_icon = false;
						$query = "//arch:links/oc:space_links/oc:link[@project_root='".$item_name."']/oc:item_class/oc:iconURI";
						$result_class_icon = $xpath->query($query, $proj_dom);
						if($result_class_icon != null){
							if(@$class_icon  = $result_class_icon->item(0)->nodeValue){
								$class_icon = $result_class_icon->item(0)->nodeValue;
							}
							
							if(substr_count($class_icon, "http://")<1){
								$class_icon = (self::path_to_class_icon).$class_icon;
							}
							
						}
						
						if(!$class_icon){
							$class_icon = OpenContext_ProjectAtomJson::class_icon_lookup($class_name);
						}
						
						if(!stristr($class_icon, ".jpg")){
							$class_icon = "http://opencontext.org/database/ui_images/oc_icons/site_30x30.jpg"; //default
						}
						
						//echo "<br/>".$class_icon ;
						
						$class_node = $proj_dom->createElementNS(self::oc_ns_uri, "oc:item_class");
						$class_label_node = $proj_dom->createElementNS(self::oc_ns_uri, "oc:name");
						$class_label_node_val  = $proj_dom->createTextNode($class_name);
						$class_label_node->appendChild($class_label_node_val);
						$class_node->appendChild($class_label_node);
						$class_icon_node = $proj_dom->createElementNS(self::oc_ns_uri, "oc:iconURI");
						$class_icon_node_val = $proj_dom->createTextNode($class_icon);
						$class_icon_node->appendChild($class_icon_node_val);
						$class_node->appendChild($class_icon_node);
						$new_node->appendChild($class_node);
					}
					
					
					$proj_dom_root->item(0)->appendChild($new_node);
				}
				//$nodecount ++;
			}
		}//end case with good xml
		else{
			//echo $uri_to_query;
		}
		
		$xml_string = $proj_dom->saveXML();
                    
      $xml_string = str_replace("<default:", "<", $xml_string);
      $xml_string = str_replace("</default:", "</", $xml_string);
      $xml_string = str_replace('<entry xmlns:default="http://www.w3.org/1999/xhtml" xmlns:oc="http://about.opencontext.org/schema/person_schema_v1.xsd">', chr(13)."<entry>".chr(13), $xml_string);
		$xml_string = str_replace('<entry xmlns:default="http://www.w3.org/1999/xhtml" xmlns:oc="http://opencontext.org/schema/person_schema_v1.xsd">', chr(13)."<entry>".chr(13), $xml_string);
		$xml_string = str_replace('<entry xmlns:default="http://www.w3.org/1999/xhtml">', chr(13)."<entry>".chr(13), $xml_string);

		    
		return $xml_string;
		
	}//end function
	
	
        public static function all_project_atom_feed($proj_atom_string_array, $last_date){
		//$proj_atom_string_array is an array of string objects of Atom XML data stored in the MySQL database
		
		$host = OpenContext_OCConfig::get_host_config();
		
                $atomFullDoc = new DOMDocument("1.0", "utf-8");
                $root = $atomFullDoc->createElementNS("http://www.w3.org/2005/Atom", "feed");
	
                // add newlines and indent the output - this is at least useful for debugging and making the output easier to read
                $atomFullDoc->formatOutput = true;
                
                $root->setAttribute("xmlns:georss", "http://www.georss.org/georss");
                $root->setAttribute("xmlns:gml", "http://www.opengis.net/gml");
               
                $atomFullDoc->appendChild($root);
        
                // Feed Title 
                $feedTitle = $atomFullDoc->createElement("title");
                $feedTitleText = $atomFullDoc->createTextNode("Open Context Projects and Collections");
                $feedTitle->appendChild($feedTitleText);
                $root->appendChild($feedTitle);
                
                
                // Feed updated element (as opposed to the entry updated element)
                $feedUpdated = $atomFullDoc->createElement("updated");
                // Retrieve the current date and time. Format it in RFC 3339 format. Store it in a text node 
                $feedUpdatedText = $atomFullDoc->createTextNode(date("Y-m-d\TH:i:s\-07:00", $last_date));   
                // Append the text node the updated element
                $feedUpdated->appendChild($feedUpdatedText);
                // Append the updated node to the root element
                $root->appendChild($feedUpdated);
                
                $linkURI = $host . "/projects/.atom";
                // feed (self) link element
                $feedLink = $atomFullDoc->createElement("link");
                $feedLink->setAttribute("rel", "self");
                $feedLink->setAttribute("href", $linkURI);
                $root->appendChild($feedLink);
                
                // feed id
                $feedId = $atomFullDoc->createElement("id");
                $feedIdText = $atomFullDoc->createTextNode($linkURI);
                $feedId->appendChild($feedIdText);
                $root->appendChild($feedId);
                
                // feed author
                $feedAuthor = $atomFullDoc->createElement("author");
                $feedAuthorName = $atomFullDoc->createElement("name");
                $feedAuthorNameText = $atomFullDoc->createTextNode("Open Context Editors");
                $feedAuthorName->appendChild($feedAuthorNameText);
                $feedAuthor->appendChild($feedAuthorName);
                $root->appendChild($feedAuthor);
                        
                
                foreach($proj_atom_string_array AS $proj_atom_string){
                        $proj_dom = new DOMDocument("1.0", "utf-8");
                        
						/*
                        if(!@$proj_dom->loadXML($proj_atom_string)){
								$proj_atom_string = mb_convert_encoding($proj_atom_string, "utf-8");
								$proj_dom->loadXML($proj_atom_string);
						}
						*/
                        
						$proj_dom->loadXML($proj_atom_string);
						    
                            
                        $xpath = new DOMXpath($proj_dom);
                        
                            // Register OpenContext's namespace
                        $xpath->registerNamespace("arch", OpenContext_OCConfig::get_namespace("arch", "project"));
						$xpath->registerNamespace("oc", OpenContext_OCConfig::get_namespace("oc", "project"));
						$xpath->registerNamespace("dc", OpenContext_OCConfig::get_namespace("dc"));
						$xpath->registerNamespace("atom", OpenContext_OCConfig::get_namespace("atom"));          
                        
                        
                        $query = "//atom:entry";
                        $result_arch = $xpath->query($query, $proj_dom);
                        if($result_arch != null){
                                $proj_entry = $result_arch->item(0);
                                $new_node = $atomFullDoc->importNode($proj_entry, true);
                                $root->appendChild($new_node);
                        }//end case with entry
                        
                }//end loop through the projects
                
                
                    
                $xml_string = $atomFullDoc->saveXML();
                    
                $xml_string = str_replace("<default:", "<", $xml_string);
                $xml_string = str_replace("</default:", "</", $xml_string);
                //$xml_string = str_replace('<entry xmlns:default="http://www.w3.org/1999/xhtml" xmlns:oc="http://about.opencontext.org/schema/person_schema_v1.xsd">', chr(13)."<entry>".chr(13), $xml_string);
				//$xml_string = str_replace('<entry xmlns:default="http://www.w3.org/1999/xhtml" xmlns:oc="http://opencontext.org/schema/person_schema_v1.xsd">', chr(13)."<entry>".chr(13), $xml_string);
				$xml_string = str_replace('<entry xmlns:default="http://www.w3.org/1999/xhtml">', chr(13)."<entry>".chr(13), $xml_string);

		return $xml_string;
		
	}//end function        
        
        
        
        
	public static function class_icon_lookup($class_name){
		
		$class_icon_uri = false;
		if(is_array($class_name)){
			$name = $class_name[0];
			unset($class_name);
			$class_name = $name;
		}
		$UCclass_name = ucwords($class_name);
		
		$db_params = OpenContext_OCConfig::get_db_config();
      $db = new Zend_Db_Adapter_Pdo_Mysql($db_params);
		$db->getConnection();
                $sql = 'SELECT sp_classes.sm_class_icon
                    FROM sp_classes
                    WHERE sp_classes.class_label LIKE "'.$class_name.'"
							OR sp_classes.class_label LIKE "'.$UCclass_name.'"
                    LIMIT 1';
		
      $result = $db->fetchAll($sql, 2);
		$db->closeConnection();
		
		if($result){
         $class_icon_uri = $result[0]["sm_class_icon"];
		   $class_icon_uri = (self::path_to_class_icon).$class_icon_uri;
		}
		
		return $class_icon_uri;
	}//end function
	
        
        
        //this function is used to add find all the variables associated with a given class in this project
        public static function class_variable_summary($class_name, $proj_id){
               $db_params = OpenContext_OCConfig::get_db_config();
                $db = new Zend_Db_Adapter_Pdo_Mysql($db_params);
		$db->getConnection();
                
                $sql = 'SELECT var_tab.variable_uuid, var_tab.var_label, var_tab.var_type
                    FROM sp_classes
                    JOIN space ON sp_classes.class_uuid = space.class_uuid
                    JOIN observe ON space.uuid = observe.subject_uuid
                    JOIN properties ON observe.property_uuid = properties.property_uuid
                    JOIN var_tab ON properties.variable_uuid = var_tab.variable_uuid
                    WHERE sp_classes.class_label LIKE "'.$class_name.'"
                    AND space.project_id = "'.$proj_id.'"
                    GROUP BY var_tab.var_label';
		
                $result = $db->fetchAll($sql, 2);
		if($result){
                               
                }
                $db->closeConnection();
                
                return $result;
        }//end function
        
        
        
        public static function project_atom_to_json($project_atom_string){
		//$atom_entry_string is a string object of Atom XML data stored in the MySQL database
		
		$host = OpenContext_OCConfig::get_host_config();
		
		$project_dom = new DOMDocument("1.0", "utf-8");
                $project_dom->loadXML($project_atom_string);
                    
                    
                $xpath = new DOMXpath($project_dom);
		
                    // Register OpenContext's namespace
		$xpath->registerNamespace("arch", OpenContext_OCConfig::get_namespace("arch", "project"));
		$xpath->registerNamespace("oc", OpenContext_OCConfig::get_namespace("oc", "project"));
		$xpath->registerNamespace("dc", OpenContext_OCConfig::get_namespace("dc"));
		$xpath->registerNamespace("atom", OpenContext_OCConfig::get_namespace("atom"));
		$xpath->registerNamespace("georss", OpenContext_OCConfig::get_namespace("georss"));
		
		
        
                $query = "//arch:project/arch:name/arch:string";
                $result_title = $xpath->query($query, $project_dom);
                if($result_title != null){
			$project_name = $result_title->item(0)->nodeValue;
                }
                
                $query = "//arch:project/@UUID";
                $result_id = $xpath->query($query, $project_dom);
                if($result_id != null){
			$projectLink = $host."/projects/".($result_id->item(0)->nodeValue);
                }
                
                $query = "//arch:notes/arch:note[@type='short_des']/arch:string";
                $result_shortdes = $xpath->query($query, $project_dom);
                if($result_shortdes != null){
			$projectShortDes = $result_shortdes->item(0)->nodeValue;
                }
                
                $query = "//arch:notes/arch:note[@type='long_des']/arch:string";
                $result_longdes = $xpath->query($query, $project_dom);
                if($result_longdes != null){
			$projectLongDes = strip_tags(html_entity_decode($result_longdes->item(0)->nodeValue));
                }
                
                
                //$projectLongDes = "blank for now";
                $projectDes = array("short"=> $projectShortDes, "long"=> $projectLongDes);
                
                $query = "//oc:item_views[@type='spatialCount']/oc:count";
                $result_count = $xpath->query($query, $project_dom);
                if($result_count != null){
			$space_count = $result_count->item(0)->nodeValue;
                        $space_count = $space_count + 0;
                }
                
                $query = "//oc:item_views[@type='spatialCount']/oc:count/@rank";
                $result_rank = $xpath->query($query, $project_dom);
                if($result_rank != null){
			$space_rank = $result_rank->item(0)->nodeValue;
                        $space_rank = $space_rank + 0;
                }
                
                $query = "//oc:item_views[@type='spatialCount']/oc:count/@pop";
                $result_pop = $xpath->query($query, $project_dom);
                if($result_pop != null){
			$project_pop = $result_pop->item(0)->nodeValue;
                        $project_pop = $project_pop + 0;
                }
                
                
                $dc_creator_array = array();
                $query = "//oc:metadata/dc:creator";
                $result_creators = $xpath->query($query, $project_dom);
                if($result_creators != null){
			foreach($result_creators as $act_creator){
                                $act_dc_creator = $act_creator->nodeValue;
                                //echo $act_dc_creator;
                                
                                $query = "//oc:person_links/oc:link[oc:name='".$act_dc_creator."']";
                                $result_plinks = $xpath->query($query, $project_dom);
                                
                                
                                if($result_plinks != null){
                                
                                        foreach($result_plinks as $act_plink){
                                                
                                                $resultNode = $act_plink;
                                                $newDom = new DOMDocument;
                                                $newDom->appendChild($newDom->importNode($resultNode,1));
                                                
                                                $xpath_B = new DOMXpath($newDom);
                                                $xpath_B->registerNamespace("oc", OpenContext_OCConfig::get_namespace("oc", "project"));
                                                
						
						$query = "//oc:id";
                                                $result_name = $xpath_B->query($query, $newDom);
                                                $pers_uuid = $result_name->item(0)->nodeValue;
                                                
                                                $person_href = $host."/persons/".$pers_uuid; 
                                                $dc_creator_array[] = array("href"=>$person_href, "name"=>$act_dc_creator);
                                        
                                        }//end loop through person links
                                
                                }//end case with person links
                                
                        }//end loop     
                }//end conditional with creators                
                
                //get all dublin core metadata
                $dc_meta_array = OpenContext_ProjectAtomJson::dc_meta_to_array($project_dom, $xpath);
                //$dc_meta_array = $this->dc_meta_to_array($project_dom, $xpath);
                
                
                $query = "//atom:entry/atom:category[@term = 'context']";
                $all_result_contexts = $xpath->query($query, $project_dom);
                
                $context_array = array();
                if($all_result_contexts != null){
                        
                        foreach($all_result_contexts as $act_resultcontext){
                                
                                $resultNode = $act_resultcontext->parentNode;
                                $newDom = new DOMDocument;
                                $newDom->appendChild($newDom->importNode($resultNode,1));
                                
                                $xpath_B = new DOMXpath($newDom);
                        
                                // Register OpenContext's namespace
				
				$xpath_B->registerNamespace("xhtml", OpenContext_OCConfig::get_namespace("xhtml"));
				$xpath_B->registerNamespace("atom", OpenContext_OCConfig::get_namespace("atom"));
				$xpath_B->registerNamespace("georss", OpenContext_OCConfig::get_namespace("georss"));
				$xpath_B->registerNamespace("kml", OpenContext_OCConfig::get_namespace("kml"));
                                
                                $query = "//atom:title";
                                $result_name = $xpath_B->query($query, $newDom);
                                if($result_name != null){
                                        $context_name = $result_name->item(0)->nodeValue;
                                }
                                
                                $query = "//atom:link[@type='application/xhtml+xml']/@href";
                                $result_link = $xpath_B->query($query, $newDom);
                                if($result_link != null){
                                        $context_link = $result_link->item(0)->nodeValue;
                                }
                                
                                $query = "//xhtml:span[@class='facetcount']";
                                $result_count = $xpath_B->query($query, $newDom);
                                if($result_count != null){
                                        $facet_count = $result_count->item(0)->nodeValue;
                                        $facet_count = $facet_count + 0;
                                }
                                
                                $query = "//georss:point";
                                $result_geo = $xpath_B->query($query, $newDom);
                                if($result_geo != null){
                                        $geo_text = $result_geo->item(0)->nodeValue;
                                        $act_geo_array = explode(" ", $geo_text);
                                        $lat = $act_geo_array [0] + 0;
                                        $lon = $act_geo_array [1] + 0;
                                        $geo_array = array("lat" => $lat, "long" => $lon);
                                }
                                
                                $time_array = array();
                                $query = "//kml:TimeSpan/kml:begin";
                                $result_begin = $xpath_B->query($query, $newDom);
                                if($result_begin != null){
					if($result_begin->item(0)){
						$kml_begin = $result_begin->item(0)->nodeValue;
						$time_array["begin"] = $kml_begin + 0;
					}
                                }
                                $query = "//kml:TimeSpan/kml:end";
                                $result_end = $xpath_B->query($query, $newDom);
                                if($result_end != null){
                                        if($result_end->item(0)){
						$kml_end = $result_end->item(0)->nodeValue;
						$time_array["end"] = $kml_end + 0;
					}
                                }
                                
				//get dates from XHTML, if no KML
				if(count($time_array)<1){
					$query = "//xhtml:span[@class='timeBegin']";
					$result_begin = $xpath_B->query($query, $newDom);
					if($result_begin != null){
						if($result_begin->item(0)){
							$span_begin = $result_begin->item(0)->nodeValue;
							$time_array["begin"] = $span_begin + 0;
						}
					}
					$query = "//xhtml:span[@class='timeEnd']";
					$result_end = $xpath_B->query($query, $newDom);
					if($result_end != null){
						if($result_end->item(0)){
							$span_end = $result_end->item(0)->nodeValue;
							$time_array["end"] = $span_end + 0;
						}
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
                
                
                
                
                
                //project people
                $query = "//atom:entry/atom:category[@term = 'related person']";
                $all_result_projects = $xpath->query($query, $project_dom);
                
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
                                        $person_name = $result_name->item(0)->nodeValue;
                                }
                        
                                $query = "//atom:link[@type='application/xhtml+xml']/@href";
                                $result_link = $xpath_B->query($query, $newDom);
                                if($result_link != null){
					if($result_link->item(0)->nodeValue){
                                        $person_link = $result_link->item(0)->nodeValue;
					}
                                }
                                
                                $query = "//xhtml:span[@class='facetcount']";
                                $result_count = $xpath_B->query($query, $newDom);
                                if($result_count != null){
                                        $facet_count = $result_count->item(0)->nodeValue;
                                        $facet_count = $facet_count + 0;
                                }
                                
                                $project_array[] = array("name" => $person_name,
                                                         "href" => $person_link,
                                                         "item_count" => $facet_count
                                                         );
                        }//end loop through projects
                        
                }//end case with projects
                
                
                //categories
                $query = "//atom:entry/atom:category[@term = 'category']";
                $all_result_categories = $xpath->query($query, $project_dom);
                
                
                $category_array = array();
                if($all_result_categories != null){
                        
                        foreach($all_result_categories as $act_resultcategory){
                                
                                $resultNode = $act_resultcategory->parentNode;
                                $newDom = new DOMDocument;
                                $newDom->appendChild($newDom->importNode($resultNode,1));
                                
                                $xpath_B = new DOMXpath($newDom);
                        
                                // Register OpenContext's namespace
                                $xpath_B->registerNamespace("atom", OpenContext_OCConfig::get_namespace("atom"));
                                $xpath_B->registerNamespace("oc", OpenContext_OCConfig::get_namespace("oc", "project"));
                                $xpath_B->registerNamespace("xhtml", OpenContext_OCConfig::get_namespace("xhtml"));
                                
                                $query = "//atom:title";
                                $result_name = $xpath_B->query($query, $newDom);
                                if($result_name != null){
                                        $cat_name = $result_name->item(0)->nodeValue;
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
                                
                                $query = "//oc:iconURI";
                                $result_icon = $xpath_B->query($query, $newDom);
                                if($result_icon != null){
                                        $icon_link = $result_icon->item(0)->nodeValue;
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
                $all_result_media = $xpath->query($query, $project_dom);
                
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
                                                         "item_count" => $facet_count
                                                         );
                        }//end loop through categories
                        
                }//end case with categories
                
                
                $image_cat_array = array();
                //go through links for each category to see if there are images associated
		
		/*
                foreach($category_array AS $actCatagory){
                        $light_cat_link = str_replace("/sets/", "/lightbox/", $actCatagory["href"]);
                        $light_cat_link = str_replace("/?", ".json?", $light_cat_link);
                        $light_cat_link = str_replace("lightbox.json?", "lightbox/.json?", $light_cat_link);
                        $image_cat_string = file_get_contents($light_cat_link);
                        $image_cat_obj = Zend_Json::decode($image_cat_string);
                        if($image_cat_obj["total"] > 0){
                                
                                $act_image_cat = array("category"=> $actCatagory["name"],
                                                        "icon_href"=> $actCatagory["icon_href"],
                                                        "item_count"=> ($image_cat_obj["total"]+0),
                                                        "lightbox_links" => $image_cat_obj["links"],
                                                        "examples"=> $image_cat_obj["items"]);
                                $image_cat_array[] = $act_image_cat;
                        }
                        
                }//end loop through links of light box
		*/
                
                
                
                
                
                $output_array = array("name"=> $project_name,
				      //"ocURI" => stristr($project_atom_string, OpenContext_OCConfig::get_namespace("oc", "project")),
                                      "href"=> $projectLink,
                                      "item_view_count" => $space_count,
                                      "rank" => $space_rank,
                                      "of_pop" => $project_pop,
                                      "dc_metadata" => $dc_meta_array,
                                      "descriptions" => $projectDes,
                                      "contexts" => $context_array,
                                      "categories" => $category_array,
                                      "media" => $media_array,
                                      "images" => $image_cat_array,
                                      "main_people" => $dc_creator_array
                                      );
        
                $output = Zend_Json::encode($output_array);
                
                //return $output_array;
                return $output ;
        
        }//end function
        
        public static function dc_meta_to_array($project_dom, $xpath){
                
                $dc_meta_array = array();
                $dc_tags = array("creator",
                                "subject",
                                "coverage",
                                "contributor",
                                "date",
                                "description",
                                "format",
                                "identifier",
                                "language",
                                "rights",
                                "title",
                                "type"
                                ); //list of all the dublin core elemenets
                
                foreach($dc_tags AS $act_element){
                        $query = "//oc:metadata/dc:".$act_element;
                        $result_meta = $xpath->query($query, $project_dom);
                        if($result_meta != null){
                                foreach($result_meta as $act_value){
                                        $act_dc_value = $act_value->nodeValue;
                                        $dc_meta_array[] = array("element"=> $act_element, "value" => $act_dc_value);
                                }
                        }
                }
                
                return $dc_meta_array;
        }
        
}//end class declaration

?>
