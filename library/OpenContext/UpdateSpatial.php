<?php

class OpenContext_SolrIndexer {
		
	
	/*eventrually this will perform some data validation functions*/ 
	public static function get_atom_archaeoml($item_uri, $do_validation = false){
		
		$atom_string = file_get_contents($item_uri);
		$spatialItem = simplexml_load_string($atom_string);
		
		if($do_validation){
			// Register OpenContext's namespace
			$spatialItem->registerXPathNamespace("oc", "http://www.opencontext.org/database/schema/space_schema_v1.xsd");
		
			// Register Dublin Core's namespace
			$spatialItem->registerXPathNamespace("dc", "http://dublincore.org/schemas/xmls/simpledc20020312.xsd");
		
			// Register the GML namespace
			$spatialItem->registerXPathNamespace("gml", "http://www.opengis.net/gml");
			
			$item_label = $spatialItem->name->string;
			$project_id = $spatialItem['ownedBy'];
			
			if(!$item_label||!$project_id){
				$spatialItem = false;
			}
			
		}
		
		return $spatialItem;
	}//end function
	
	
	public static function reindex_item($spatialItem, $host){
		
		// Register OpenContext's namespace
		$spatialItem->registerXPathNamespace("oc", "http://www.opencontext.org/database/schema/space_schema_v1.xsd");
	
		// Register Dublin Core's namespace
		$spatialItem->registerXPathNamespace("dc", "http://dublincore.org/schemas/xmls/simpledc20020312.xsd");
	
		// Register the GML namespace
		$spatialItem->registerXPathNamespace("gml", "http://www.opengis.net/gml");
	
		// prepare a solr document to add to the solr index
		$solrDocument = new Apache_Solr_Document();
	
		
		// start adding fields to the solr document
	
		// get the item's UUID
		$uuid = $spatialItem['UUID']; // save this as a variable - we use it again later to construct the Atom feed.
		$solrDocument->uuid = $uuid; // add it to the solr document
		
		// get the item_label
		$item_label = $spatialItem->name->string;
		$solrDocument->item_label = $item_label;
		
		// get the project_id
		$solrDocument->project_id = $spatialItem['ownedBy'];
	
		// get the class
		foreach ($spatialItem->xpath("/spatialUnit/oc:item_class/oc:name") as $item_class) {
			//echo "item_class: " . $item_class;
			$solrDocument->item_class = $item_class;
		}
		
		// get the context(s) - there may be more than one 
		foreach ($spatialItem->xpath("/spatialUnit/oc:context/oc:tree/oc:parent/oc:name") as $context) {
			$solrDocument->setMultiValue("context", $context);
		}
	
		/* if there is no 'oc:context/oc:tree' element, this item *should* be a root-level item. set the default_context_path accordingly.
		 if this item is not a a root-level item, but does not have a tree element, labeling it as such should help us detect errors.*/
		if (!$spatialItem->xpath("/spatialUnit/oc:context/oc:tree")) {
			$solrDocument->default_context_path = "[ROOT]";
			//echo "default_context_path: [ROOT]";
			//echo "<br/>";
		}
	
		// Get the default context path (there should only be one.)
		$default_context_path = "";
		//echo "default_context_path: ";
		// if there's a default context tree, get the context path
		if ($spatialItem->xpath("/spatialUnit/oc:context/oc:tree[@id='default']")) {
			foreach ($spatialItem->xpath("/spatialUnit/oc:context/oc:tree[@id='default']/oc:parent/oc:name") as $path) {
				$default_context_path .= $path . "/";
			}
			$solrDocument->default_context_path = $default_context_path;
			//echo $default_context_path;
			//echo "<br/>";
		}
		// Get the additional context paths
		// first check for the presence of additional paths
		if ($spatialItem->xpath("/spatialUnit/oc:context/oc:tree[not(@id='default')]")) {
	
			// initialize $additional context path and make sure it's storing an empty string
			$additional_context_path = "";
	
			// get the contexts from each non-default tree
			foreach ($spatialItem->xpath("/spatialUnit/oc:context/oc:tree[not(@id='default')]") as $non_default_tree) {
				//	echo "additional_context_path: ";
				// iterate through the trees to build the context path(s)
				foreach ($non_default_tree->xpath("oc:parent/oc:name") as $alt_path) {
					$additional_context_path .= $alt_path . "/";
	
				}
	
				//echo $additional_context_path;
				//echo "<br/>";
				$solrDocument->setMultiValue("additional_context_path", $additional_context_path);
				// clear the additional context path in preparation for iterating through the next tree
				$additional_context_path = "";
			}
		}
	
		// check for the presence of a default child tree (because not all items have children)
		if ($spatialItem->xpath("/spatialUnit/oc:children/oc:tree[@id='default']/oc:child")) {
			// loop through and get each default child
			foreach ($spatialItem->xpath("/spatialUnit/oc:children/oc:tree[@id='default']/oc:child") as $default_child) {
				// get the child's name and append a slash
				foreach ($default_child->xpath("oc:name") as $default_child_name) {
					$default_name_and_slash = $default_child_name . "/";
				}
				// get the child id and append it to the name and slash: $name/$id
				foreach ($default_child->xpath("oc:id") as $default_child_id) {
					//echo $child_id;
					$default_name_slash_id = $default_name_and_slash . $default_child_id;
					$solrDocument->setMultiValue("default_child", $default_name_slash_id);
					//echo "default_child: " . $default_name_slash_id;
					//echo "<br/>";
				}
			}
		}
	
		// check for non-default children; loop through and get their name and id.
		if ($spatialItem->xpath("/spatialUnit/oc:children/oc:tree[not(@id='default')]")) {
			foreach ($spatialItem->xpath("/spatialUnit/oc:children/oc:tree[not(@id='default')]/oc:child") as $non_default_child) {
				// get each child name and append a slash
				foreach ($non_default_child->xpath("oc:name") as $non_default_child_name) {
					$non_default_name_and_slash = $non_default_child_name . "/";
				}
				// append the id to the name and the slash
				foreach ($non_default_child->xpath("oc:id") as $non_default_id) {
					//echo $child_id;
					$non_default_name_slash_id = $non_default_name_and_slash . $non_default_id;
					$solrDocument->setMultiValue("non_default_child", $non_default_name_slash_id);
					//echo "non_default_child: " . $non_default_name_slash_id;
					//echo "<br/>";
				}
			}
		}
	
		// if there are no media and no diary links associated with this item, the value of 'media_type' is 'none'
		if (!$spatialItem->xpath("/spatialUnit/observations/observation/links/oc:media_links/oc:link/oc:type") && !$spatialItem->xpath("/spatialUnit/observations/observation/links/oc:diary_links/oc:link")) {
			$solrDocument->media_type = "none";
			//echo "media_type: none";
			//echo "<br>";
		}
	
		// if there are media associated with this item, create fields for unique media (there may be duplicate media type elements, i.e., multiple 'type' elements with the same value).
		if ($spatialItem->xpath("/spatialUnit/observations/observation/links/oc:media_links/oc:link/oc:type")) {
			// get an array of media types
			$types = $spatialItem->xpath("/spatialUnit/observations/observation/links/oc:media_links/oc:link/oc:type");
			// create fields for each media type, while removing duplicate types. (there might be multiple images for a single item.)
			foreach (array_unique($types) as $type) {
				$solrDocument->setMultiValue("media_type", $type);
				//echo "media_type: " . $type;
				//echo "<br>";
			}
		}
	
		// check whether there are diary links associated with this item.
		if ($spatialItem->xpath("/spatialUnit/observations/observation/links/oc:diary_links/oc:link")) {
			$solrDocument->setMultiValue("media_type", "diary/narrative"); // use setMultiValue() to avoid overwriting other media types for an item
			//echo "media_type: diary/narrative";
			//echo "<br>";
		}
	
		/*     <!-- tags: this field is a flattened version of user_tags/tag/value -->
		      <field name="user_tag" type="string" indexed="true" stored="true" required="false" multiValued="true"  />
		      
		      <!-- tag content creator. this is only present if there is a tag. the value for '*' comes from tag/value. the value of this element comes from creator_name. -->
		      <field name="tag_creater_name" type="string" indexed="true" stored="true" required="false" multiValued="true" />
		      
		      <!-- the label for the set of items carrying this tag. the value for '*' comes from tag/value. the value of this element comes from set_label. -->
		      <field name="tag_set_label" type="string" indexed="true" stored="true" required="true" multiValued="true" />
		      */
		if ($spatialItem->xpath("/spatialUnit/oc:user_tags/oc:tag")) {
			foreach ($spatialItem->xpath("/spatialUnit/oc:user_tags/oc:tag[@type='text' and @status='public']") as $tag) { // loop through and get the tags. 
				foreach ($tag->xpath("oc:name") as $user_tag) {
					$solrDocument->setMultiValue("user_tag", $user_tag); // add tag to the solr document
					//echo "user_tag: " . $user_tag;
					//echo "<br>";
					// array of tags to be used later for Atom feed entry
					$user_tags[] .= $user_tag; // array of tags to be used later for Atom feed entry
				}
				foreach ($tag->xpath("oc:tag_creator/oc:creator_name") as $tag_creator_name) {
					$solrDocument->setMultiValue("tag_creator_name", $tag_creator_name); // add tag creator to the solr document
					//echo "tag_creater_name: " . $tag_creator_name;
					//echo "<br>";
				}
				foreach ($tag->xpath("oc:tag_creator/oc:set_label") as $tag_set_label) {
					$solrDocument->setMultiValue("tag_set_label", $tag_set_label);
					//echo "tag_set_label: " . $tag_set_label;
					//echo "<br>";
				}
	
			}
		}
		/*
		 * 
		 *  <!-- private tags -->
			<!-- private tags: this field is a flattened version of user_tags/tag/value -->
		    <field name="private_user_tag" type="string" indexed="true" stored="true" required="false" multiValued="true"  />
		    
		    <!-- tag content creator. this is only present if there is a tag. the value for '*' comes from tag/value. the value of this element comes from creator_name. -->
		    <field name="private_tag_creator_name" type="string" indexed="true" stored="true" required="false" multiValued="true" />
		    
		    <!-- the label for the set of items carrying this tag. the value for '*' comes from tag/value. the value of this element comes from set_label. -->
		    <field name="private_tag_set_label" type="string" indexed="true" stored="true" required="false" multiValued="true" />
		    
		
		 * 
		 * 
		 * 
		 */
		if ($spatialItem->xpath("/spatialUnit/oc:user_tags/oc:tag")) {
			foreach ($spatialItem->xpath("/spatialUnit/oc:user_tags/oc:tag[@type='text' and @status='private']") as $tag) { // loop through and get the tags. 
	
				foreach ($tag->xpath("oc:name") as $user_tag) {
					$solrDocument->setMultiValue("private_user_tag", $user_tag); // add tag to the solr document
					//echo "user_tag: " . $user_tag;
					//echo "<br>";
	
				}
				foreach ($tag->xpath("oc:tag_creator/oc:creator_name") as $tag_creator_name) {
					$solrDocument->setMultiValue("private_tag_creator_name", $tag_creator_name); // add tag creator to the solr document
					//echo "tag_creater_name: " . $tag_creator_name;
					//echo "<br>";
				}
				foreach ($tag->xpath("oc:tag_creator/oc:set_label") as $tag_set_label) {
					$solrDocument->setMultiValue("private_tag_set_label", $tag_set_label);
					//echo "tag_set_label: " . $tag_set_label;
					//echo "<br>";
				}
	
			}
		}
		
	
		// Verify that there are properties associated with this item.
		if ($spatialItem->xpath("/spatialUnit/observations/observation/properties/property")) {
			// get the alphanumeric properties and index them as a 'notes' field. 
			foreach ($spatialItem->xpath("/spatialUnit/observations/observation/properties/property/oc:var_label[@type='alphanumeric']") as $var_label) {
				foreach ($var_label->xpath(".") as $var_alpha) {
					$note = $var_alpha . " ";
				}
				foreach ($var_label->xpath("../oc:show_val") as $show_val) {
					$note .= $show_val;
	
				}
	
				$solrDocument->setMultiValue("notes", $note);
			}
	
			foreach ($spatialItem->xpath("/spatialUnit/observations/observation/properties/property/oc:var_label[@type='nominal']") as $var_label) {
				foreach ($var_label->xpath(".") as $var_nom) {
					$solrDocument->setMultiValue("variable_nominal", $var_nom);
					//echo "variable_nominal: " . $var_nom;
					//echo "<br/>";
				}
				foreach ($var_label->xpath("../oc:show_val") as $show_val) {
					$dynamic_field_name = $var_nom . "_var_nom_val";
					$solrDocument->setMultiValue($dynamic_field_name, $show_val);
					//echo $dynamic_field_name . ": " . $show_val;
					//echo "<br/>";
				}
	
			}
	
			foreach ($spatialItem->xpath("/spatialUnit/observations/observation/properties/property/oc:var_label[@type='calendar']") as $var_label) {
				foreach ($var_label->xpath(".") as $var_cal) {
					$solrDocument->setMultiValue("variable_calendric", $var_cal);
			
				}
				foreach ($var_label->xpath("../date") as $cal_val) {
					$dynamic_field_name = $var_cal . "_var_cal_val";
					$solrDocument->setMultiValue($dynamic_field_name, $cal_val);
				}
	
			}
	
			// check for integer variables 
			foreach ($spatialItem->xpath("/spatialUnit/observations/observation/properties/property/oc:var_label[@type='integer']") as $var_label) {
				foreach ($var_label->xpath("../oc:show_val") as $show_val) {
					// test whether $show_val's value is non-null and really is an integer. It may not be either. 
					if ($show_val && (intval($show_val) === $show_val)) {
						// $show_val is non-null and is an integer
						$solrDocument->setMultiValue("variable_integer", $var_label);
						//echo "variable_integer: " . $var_label;
						//echo "<br/>";
						$dynamic_field_name = $var_label . "_var_int_val";
						$solrDocument->setMultiValue($dynamic_field_name, $show_val);
						//echo $dynamic_field_name . ": " . $show_val;
						//echo "<br/>";
						$dynamic_field_name_hr = $var_label . "_var_int_val_hr";
						$solrDocument->setMultiValue($dynamic_field_name_hr, $show_val);
						//echo $dynamic_field_name_hr . ": " . $show_val;
						//echo "<br/>";
					} else {
						// $show_val is not an intger
						$note = $var_label . " " . $show_val;
						$solrDocument->setMultiValue("notes", $note);
						//echo "notes: " . $note;
						//echo "<br/>";
	
					}
				}
			}
	
			// get the decimal variable
			foreach ($spatialItem->xpath("/spatialUnit/observations/observation/properties/property/oc:var_label[@type='decimal']") as $var_label) {
				foreach ($var_label->xpath("../oc:show_val") as $show_val) {
					// make sure the value is numeric (so it can be indexed as a float).
					if (is_numeric((string) ($show_val))) {
						$solrDocument->setMultiValue("variable_decimal", $var_label);
						//echo "variable_decimal: " . $var_label;
						//echo "<br/>";
						// the value of decimal variable in non-human-readable format - useful for number comaparisons, etc.
						$dynamic_field_name = $var_label . "_var_dec_val";
						$solrDocument->setMultiValue($dynamic_field_name, $show_val);
						//echo $dynamic_field_name . ": " . $show_val;
						//echo "<br/>";
						// store but don't index a human-readable version for display purposes
						$dynamic_field_name_hr = $var_label . "_var_dec_val_hr";
						$solrDocument->setMultiValue($dynamic_field_name_hr, $show_val);
						//echo $dynamic_field_name_hr . ": " . $show_val;
						//echo "<br/>";
					} else {
						// if the value does not validate as numeric, index it as a notes field
						$note = $var_label . " " . $show_val;
						$solrDocument->setMultiValue("notes", $note);
						//echo "notes: " . $note;
						//echo "<br/>";
					}
	
				}
			}
	
			foreach ($spatialItem->xpath("/spatialUnit/observations/observation/properties/property/oc:var_label[@type='ordinal']") as $var_label) {
				foreach ($var_label->xpath(".") as $var_ord) {
					$solrDocument->setMultiValue("variable_ordinal", $var_ord);
					//echo "variable_ordinal: " . $var_ord;
					//echo "<br/>";
				}
				foreach ($var_label->xpath("../oc:show_val") as $show_val) {
					$dynamic_field_name = $var_ord . "_var_ord_val";
					$solrDocument->setMultiValue($dynamic_field_name, $show_val);
					//echo $dynamic_field_name . ": " . $show_val;
					//echo "<br/>";
				}
	
			}
	
			foreach ($spatialItem->xpath("/spatialUnit/observations/observation/properties/property/oc:var_label[@type='boolean']") as $var_label) {
				foreach ($var_label->xpath(".") as $var_bool) {
					$solrDocument->setMultiValue("variable_ordinal", $var_bool);
					//echo "variable_boolean: " . $var_bool;
					//echo "<br/>";
				}
				foreach ($var_label->xpath("../oc:show_val") as $show_val) {
					$dynamic_field_name = $var_ord . "_var_bool_val";
					$solrDocument->setMultiValue($dynamic_field_name, $show_val);
					//echo $dynamic_field_name . ": " . $show_val;
					//echo "<br/>";
				}
	
			}
	
		}
	
		/*
		 * <!-- people links: this field is a flattened version of the value of this element comes from '../person_links/link/relation' -->
		<field name=" person_link " type=" string " indexed=" true " stored=" true " required=" false " multiValued=" true "  />
		 * <!-- the linked person. this is only present if there is a person link. the value for '*' comes from '../person_links/link/relation'. the value of this element comes from '../person_links/link/name'. -->
		<dynamicField name=" * _person " type=" string " indexed=" true " stored=" true " required=" false " multiValued=" true " />
		 */
	
		// person links
		foreach ($spatialItem->xpath("/spatialUnit/observations/observation/links/oc:person_links/oc:link") as $person_link) {
			foreach ($person_link->xpath("oc:name") as $person_name) {
				$solrDocument->setMultiValue("person_link", $person_name);
				//echo "person_link: " . $person_name;
				//echo "<br/>";
			}
			foreach ($person_link->xpath("oc:relation") as $relation) {
				$dynamic_field_name = $person_name . "_person";
				$solrDocument->setMultiValue($dynamic_field_name, $relation);
				//echo $dynamic_field_name . ": " . $relation;
				//echo "<br/>";
			}
		}
	
		/*
		<!-- external web references, pingbacks: the value of this element comes from '../outside_pings/pings/name' -->
		 <field name=" pings " type=" string " indexed=" true " stored=" true " required=" false " multiValued=" true "  /> 
		*/
	
		if ($spatialItem->xpath("/spatialUnit/oc:outside_pings")) {
			foreach ($spatialItem->xpath("/spatialUnit/oc:outside_pings/oc:ping/oc:name") as $ping_name) {
				$solrDocument->setMultiValue("pings", $ping_name);
				//echo "pings: " . $ping_name;
				//echo "<br/>";
			}
	
		}
	
		/*
		<!-- notes: this field is free form text about an item.  The value from this element comes from '/spatialUnit/observations/observation/notes/note/string' -->
		<field name=" notes " type=" text_ws " indexed=" true " stored=" false " required=" false " multiValued=" true "  />
		*/
	
		if ($spatialItem->observations->observation->notes->note) {
			foreach ($spatialItem->observations->observation->notes->note as $note) {
				$solrDocument->setMultiValue("notes", $note->string);
				//echo "notes: " . $note->string;
				//echo "<br/>";
			}
		}
	
		/*
		* <!-- view count: the number of times a specific item has been accessed. The value for this element comes from ../item_views/count. The value is an integer. -->
		<field name=" view_count " type=" integer " indexed=" true " stored=" true " required=" true " />
		<!-- georeferencing: this is not necessarily for the user interface, but could support a restful geographic web service.-->
		<!-- latitude: The value for this element comes from ../geo_reference/geo_lat.  This is a decimal value. -->
		<field name=" geo_lat " type=" double " indexed=" true " stored=" true " required=" true " />
		*/
	
		foreach ($spatialItem->xpath("/spatialUnit/oc:item_views/oc:count") as $item_views) {
			$solrDocument->view_count = $item_views;
			//echo "view_count: " . $item_views;
			//echo "<br/>";
		}
	
		/*  <!-- georeferencing: this is not necessarily for the user interface, but could support a restful geographic web service.-->
		    <!-- latitude: The value for this element comes from ../geo_reference/geo_lat.  This is a decimal value. -->
		    <field name="geo_lat" type="double" indexed="true" stored="true" required="true" /> 
		*/
		foreach ($spatialItem->xpath("/spatialUnit/oc:geo_reference") as $geo_reference) {
			foreach ($geo_reference->xpath("oc:geo_lat") as $geo_lat) {
				$solrDocument->geo_lat = $geo_lat;
				//echo  "geo_lat : " . $geo_lat;
				//echo  "<br/>";
			}
			foreach ($geo_reference->xpath("oc:geo_long") as $geo_long) {
				$solrDocument->geo_long = $geo_long;
				//echo  "geo_long: " . $geo_long;
				//echo  "<br/>";
			}
		}
	
		/*<!-- Chronological Information  -->
		<!-- not required -->
		<!-- start  time_start / long-->
		<field name=" time_start " type=" slong " indexed=" true " stored=" true " required=" false " multiValued=" true " />
		<!-- end    time_end  / long -->
		<field name=" time_end " type=" slong " indexed=" true " stored=" true " required=" false " multiValued=" true " />
		<!-- Tag creator  multi -->
		<field name=" chrono_creator_name " type=" string " indexed=" true " stored=" true " required=" false " multiValued=" true " />
		<!-- tag set -->
		<field name=" chrono_set_label " type=" string " indexed=" true " stored=" true " required=" false " multiValued=" true " />
		*/
		// Check for a tag element 
		if ($spatialItem->xpath("/spatialUnit/oc:user_tags/oc:tag")) {
			foreach ($spatialItem->xpath("/spatialUnit/oc:user_tags/oc:tag[@type='chronological' and @status='public']") as $chrono_tag) {
				foreach ($chrono_tag->xpath("oc:chrono/oc:time_start") as $time_start) {
					$solrDocument->setMultiValue("time_start", $time_start);
					//echo  "time_start: " . $time_start;
					//echo  "<br>";
					// store but don't index human-readable version of time_start
					$solrDocument->setMultiValue("time_start_hr", $time_start);
					//echo  "time_start_hr: " . $time_start;
					//echo  "<br>";
				}
				foreach ($chrono_tag->xpath("oc:chrono/oc:time_finish") as $time_end) {
					$solrDocument->setMultiValue("time_end", $time_end);
					//echo  "time_end: " . $time_end;
					//echo  "<br>";
					// store but don't index additional human-readable version of this field
					$solrDocument->setMultiValue("time_end_hr", $time_end);
					//echo  "time_end_hr: " . $time_end;
					//echo  "<br>";
				}
				foreach ($chrono_tag->xpath("oc:tag_creator/oc:creator_name") as $chrono_creator_name) {
					$solrDocument->setMultiValue("chrono_creator_name", $chrono_creator_name);
					//echo  "chrono_creator_name: " . $chrono_creator_name;
					//echo  "<br>";
				}
				foreach ($chrono_tag->xpath("oc:tag_creator/oc:set_label") as $chrono_set_label) {
					$solrDocument->setMultiValue("chrono_set_label", $chrono_set_label);
					//echo  "chrono_set_label: " . $chrono_set_label;
					//echo  "<br>";
				}
			}
		}
		/* <!-- project name. the value for this element comes from ../oc:metadata/oc:project_name -->
		<field name=" project_name " type=" string " indexed=" true " stored=" true " required=" true " />
		*/
		foreach ($spatialItem->xpath("/spatialUnit/oc:metadata/oc:project_name") as $project_name) {
			$solrDocument->project_name = $project_name;
			//echo  "project_name: " . $project_name;
			//echo  "<br/>";
		}
	
		/*
		*<!-- dublin core creator. the value for this element comes from ../metadata/dc_Creator -->
		<field name="creator" type="string" indexed=" true " stored=" true " required=" false " multiValued=" true " />
		*/
		$creators = $spatialItem->xpath("/spatialUnit/oc:metadata/dc:creator");
		foreach ($creators as $creator) {
			$solrDocument->setMultiValue("creator", $creator);
			//echo  "creator: " . $creator;
			//echo  "<br/>";
		}
	
		/*
		 * <!-- dublin core subject. the value for this element comes from ../metadata/dc_Subject -->
		<field name=" subject " type=" string " indexed=" true " stored=" true " required=" false " multiValued=" true " /> 
		 */
		foreach ($spatialItem->xpath("/spatialUnit/oc:metadata/dc:subject") as $subject) {
			$solrDocument->setMultiValue("subject", $subject);
			//echo  "subject: " . $subject;
			//echo  "<br/>";
		}
	
		/*
		 * <!-- dublin core coverage. the value for this element comes from ../metadata/dc_Coverage -->
		<field name=" coverage " type=" string " indexed=" true " stored=" true " required=" false " multiValued=" true " />
		 */
		foreach ($spatialItem->xpath("/spatialUnit/oc:metadata/dc:coverage") as $coverage) {
			$solrDocument->setMultiValue("coverage", $coverage);
			//echo  "coverage: " . $coverage;
			//echo  "<br/>";
		}
	
		/*<!-- copyright license URI. the value for this element comes from ../cc_lic/lic_url -->
		<!-- this license information would most likely only see use for a web service, not a human interface -->
		<field name=" license_uri " type=" string " indexed=" true " stored=" true " required=" true " multiValued=" false " />
		*/
		foreach ($spatialItem->xpath("/spatialUnit/oc:copyright_lic/oc:lic_url") as $lic_url) {
			$solrDocument->license_uri = $lic_url;
			//echo  "license_uri: " . $lic_url;
			//echo  "<br/>";
		}
	
		// full XML
		$solrDocument->full_xml = $spatialItem->asXML();
		//echo  "full_xml:<br/>" . $spatialItem->asXML();
	
		/*
		 * Atom 
		 */
	
		//echo  "<p>Atom Entry begins here:</p>";
	
		/*
		 * Package below in Atom
		
		class icon (uri)
		project name
		class label 
		item label 
		context (path)
		thumbnail (uri)
		latitude (geoRSS) - longitude (geoRSS)
		      <georss:point>45.256 -71.92</georss:point>
		user generated tags
		*/
		$item_uri = $host."/subject/".$uuid;
		$noThumbURI = $host."/images/atom_results/no_media_pict.jpg";
		
		// Atom 
		// opening entry element
		/*$atom_entry = "<?xml version='1.0' encoding='utf-8'?><entry xmlns='http://www.w3.org/2005/Atom' xmlns:georss='http://www.georss.org/georss' xmlns:kml='http://www.opengis.net/kml/2.2' xmlns:gml='http://www.opengis.net/gml'>";*/
		$atom_entry = "<entry>";
	
		// title of the Atom entry
		$atom_title = "<title>" . $project_name . ": " . $item_label . " (" . $item_class . ")" . "</title>";
		// append title to entry
		$atom_entry .= $atom_title;
	
		// uri of Open Context content
		$atom_entry .= "<link href=" . "'" . $item_uri . "'" . "/>";
	
		// id of entry
		$atom_entry .= "<id>" . $item_uri . "</id>";
	
		// required updated element - the code below uses the current timestamp.Q: is there are reliable way to get info about when the item was last updated? 
		$atom_entry .= "<updated>" . date("Y-m-d\TH:i:s\-07:00") . "</updated>";
	
		// append one or more author elements to the entry
		foreach ($creators as $creator) {
			$atom_entry .= "<author><name>" . $creator . "</name></author>";
		}
	
		// append one or more contributor elements to the entry.
		$contributors = $spatialItem->xpath("/spatialUnit/oc:metadata/dc:contributor");
		foreach ($contributors as $contributor) {
			$atom_entry .= "<contributor><name>" . $contributor . "</name></contributor>";
		}
	
		// append a category element
		$atom_entry .= "<category term='" . $item_class . "'/>";
	
		// Append a GeoRSS point
		$atom_entry .= "<georss:point>" . $geo_lat . " " . $geo_long . "</georss:point>";
	
		// if there's a GML polygon, add it to the entry
		foreach ($spatialItem->xpath("/spatialUnit/oc:geo_reference/gml:Polygon/gml:exterior/gml:LinearRing/gml:posList") as $posList) {
			$atom_entry .= "<georss:where><gml:Polygon><gml:exterior><gml:LinearRing><gml:posList>" . $posList . "</gml:posList></gml:LinearRing></gml:exterior></gml:Polygon></georss:where>";
		}
	
		// prepare elements to add to atom content element
	
		// open atom content element and the initial div 
		$atom_content = "<content type='xhtml'><div xmlns='http://www.w3.org/1999/xhtml'>";
	
		// open the item_lft_cont div  
		$atom_content .= "<div class='item_lft_cont'>";
	
		// append the class icon (uri)
		foreach ($spatialItem->xpath("/spatialUnit/oc:item_class/oc:iconURI") as $iconURI) {
			$class_icon_div = "<div class='class_icon'><img src='" . $iconURI . "' alt='" . $item_class . "'/></div>";
			$atom_content .= $class_icon_div;
		}
	
		// append the project name to the atom content element
		$project_name_div = "<div class='project_name'>" . $project_name . "</div>";
		$atom_content .= $project_name_div;
	
		// open the item_mid_cont div 
		$atom_content .= "<div class='item_mid_cont'>";
	
		// open the item_mid_up_cont div
		$atom_content .= "<div class='item_mid_up_cont'>";
	
		// class name
		$class_name_div = "<div class='class_name'>" . $item_class . "</div>";
		$atom_content .= $class_name_div;
	
		// item label
		$item_label_div = "<div class='item_label'><a href='".$item_uri."'>" . $item_label . "</a></div>";
		$atom_content .= $item_label_div;
	
		// close the item_mid_up_con div
		$atom_content .= "</div>";
	
		// open the context div
		$atom_content .= "<div class='context'>Context: ";
	
		// context (path)
		$display_context_path = substr($default_context_path, "", -1);
		$display_context_path = str_replace("/", "</span> / <span class='item_parent'>", $display_context_path);
		$display_context_path = "<span class='item_root_parent'>" . $display_context_path . "</span>";
		$atom_content .= $display_context_path;
	
		// close the context div
		$atom_content .= "</div>";
	
		// close the item_mid_cont div
		$atom_content .= "</div>";
	
		// user generated tags
		if ($user_tags) {
			$all_user_tags_div = "<div class='all_user_tags'>User Created Tags: ";
			foreach ($user_tags as $user_tag) {
				$user_tag_div = "<span class='user_tag'>" . $user_tag . "</span>, ";
				$all_user_tags_div .= $user_tag_div;
			}
			$user_tags = array (); // re-initalize the $user_tags array in preparation for the next spatial item
			$all_user_tags_div = substr($all_user_tags_div, "", -2);
			// close all_user_tags_div
			$all_user_tags_div .= "</div>";
			$atom_content .= $all_user_tags_div;
		}
	
		// close the item_lft_cont div 
		$atom_content .= "</div>";
	
		// thumbnail (uri) (note: since there may be more than one thumbnail image, we use the first one in the array)
		// if the item has one or more thumbnail images
		if ($spatialItem->xpath("/spatialUnit/observations/observation/links/oc:media_links/oc:link/oc:thumbnailURI")) {
			// store the image URIs in an array
			$thumbnailURIs = $spatialItem->xpath("/spatialUnit/observations/observation/links/oc:media_links/oc:link/oc:thumbnailURI");
			// display just the first image in the array.
			$item_thumb_div = "<div class='item_thumb'><a href='" . $item_uri . "'><img src='" . $thumbnailURIs[0] . "' alt='Thumbmail image'/></a></div>";
			$atom_content .= $item_thumb_div;
		}
		else{
			$item_thumb_div = "<div class='item_thumb'><a href='" . $item_uri . "'><img src='" . $noThumbURI . "' alt='No Thumbmail image'/></a></div>";
			$atom_content .= $item_thumb_div;
		}
	
		// close the atom content element
		$atom_content .= "</div></content>";
	
		// append the content element to the atom entry
		$atom_entry .= $atom_content;
	
		// close the atom entry
		$atom_entry .= "</entry>";
	
		// add the entry to the solr document
		$solrDocument->atom_entry = $atom_entry;
		//echo  $atom_entry;
	
		// once we've constructed our solr document, add it to the index
		$solr->addDocument($solrDocument);
		// and commit it
		$solr->commit();
		
	}//end reindex function
	
	
	
}//end class declaration

?>
