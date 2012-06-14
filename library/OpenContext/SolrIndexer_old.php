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
	
	
	public static function solrEscape($stringToEscape) {
		/**  In addition to the space character, solr requires that we escape the following characters because
		they're part of solr/lucene's query language: + - && || ! ( ) { } [ ] ^ " ~ * ? : \
		*/
	
		//characters we need to escape
		$search = array('\\', ' ', ':', '\'', '&&', '||', '(', ')', '+', '-', '!', '{', '}','[', ']', '^', '~', '*', '"', '?');
	   
		// escaped version of characters
		$replace = array('\\\\', '\ ', '\:', '\\\'', '\&\&', '\|\|', '\(', '\)', '\+', '\-', '\!', '\{', '\}', '\[', '\]', '\^', '\~', '\*', '\\"', '\?');
	    return str_replace($search, $replace, $stringToEscape);
	}  
	
	
	public static function update_view_count($spatialItem){
		
		// Register OpenContext's namespace
		$spatialItem->registerXPathNamespace("oc", "http://www.opencontext.org/database/schema/space_schema_v1.xsd");
		$spatialItem->registerXPathNamespace("default", "http://www.w3.org/2005/Atom");
		$spatialItem->registerXPathNamespace("arch", "http://ochre.lib.uchicago.edu/schema/SpatialUnit/SpatialUnit.xsd");
		
		foreach ($spatialItem->xpath("/default:feed/default:entry/arch:spatialUnit/oc:social_usage/oc:item_views/oc:count") as $item_views) {
			
			$new_item_view = $item_views[0] + 1;
			$item_views[0] = $new_item_view;
			//echo "view_count: " . $item_views;
			//echo "<br/>";
		}
		
		return $spatialItem;
		
	}//end update function
	
	
	public static function reindex_item($spatialItem){
		
		// increase the memory limit
		ini_set("memory_limit", "2048M");
	
	
		// Register OpenContext's namespace
		$spatialItem->registerXPathNamespace("oc", "http://www.opencontext.org/database/schema/space_schema_v1.xsd");
		
		// Register OpenContext's namespace
		$spatialItem->registerXPathNamespace("arch", "http://ochre.lib.uchicago.edu/schema/spatialUnit/SpatialUnit.xsd");
	
		// Register Dublin Core's namespace
		$spatialItem->registerXPathNamespace("dc", "http://purl.org/dc/elements/1.1/");
	
		// Register the GML namespace
		$spatialItem->registerXPathNamespace("gml", "http://www.opengis.net/gml");
		
		// Register the Atom namespace
		$spatialItem->registerXPathNamespace("atom", "http://www.w3.org/2005/Atom");
	
	
		if(!($spatialItem->xpath("/atom:feed/atom:entry"))){
			
			echo "CRAP!";	
			break;
		}
		
		$solr = new Apache_Solr_Service('localhost', 8983, '/solr');

		// test the connection to the solr server
		if ($solr->ping()) { // if we can ping the solr server...
			//echo "connected to the solr server...<br/><br/>";
		} else {
			die("unable to connect to the solr server. exiting...");
		}
		
	
		$note = false;
		$count_diary_links = 0;
		$self_geo_reference = false;
		$geo_polygon = false;
		
		// prepare a solr document to add to the solr index
		$solrDocument = new Apache_Solr_Document();
	
		// start adding fields to the solr document
	
		// get the item's UUID
		foreach($spatialItem->xpath("//arch:spatialUnit/@UUID") as $spaceid) {
		    $uuid = $spaceid."";
		}
		$solrDocument->uuid = $uuid; // add it to the solr document
	
		// get the publication date (the date items are added to Open Context).
		foreach($spatialItem->xpath("//arch:spatialUnit/oc:metadata/oc:pub_date") as $pub_date) {
		    // Format the date as UTC (Solr requires this) 
		    $pub_date = date("Y-m-d\TH:i:s\Z", strtotime($pub_date));
		    $solrDocument->pub_date = $pub_date;
		}
	
		// get the item_label
		foreach ($spatialItem->xpath("//arch:spatialUnit/arch:name/arch:string") as $item_label) {
		
			/* <!-- project name. the value for this element comes from ../oc:metadata/oc:project_name -->
			<field name=" project_name " type=" string " indexed=" true " stored=" true " required=" true " />
			*/
			
			foreach ($spatialItem->xpath("//arch:spatialUnit/oc:metadata/oc:project_name") as $project_name) {
				$solrDocument->project_name = $project_name;
			}
				
			foreach($spatialItem->xpath("//arch:spatialUnit/@ownedBy") as $projid) {
				$solrDocument->project_id = $projid."";
			}
					
			$solrDocument->item_label = $item_label;

		}//end loop for item labels
	
	
	
		// get the item class
		foreach ($spatialItem->xpath("//arch:spatialUnit/oc:item_class/oc:name") as $item_class) {
			$solrDocument->item_class = $item_class;
		}
	
		// get the context(s) - there may be more than one
		$contexts = $spatialItem->xpath("//arch:spatialUnit/oc:context/oc:tree/oc:parent/oc:name");
		// index the contexts, but remove duplicates
		foreach (array_unique($contexts) as $context) {
			$solrDocument->setMultiValue("context", $context);
		}
			
		
		// if there is no 'oc:context/oc:tree' element, this item *should* be a root-level item. set the default_context_path accordingly.
		// note: if this item is not a a root-level item, but does not have a tree element, labeling it as such should help us detect errors.*/
		if (!$spatialItem->xpath("//arch:spatialUnit/oc:context/oc:tree")) {
			$default_context_path = "ROOT";  // note: variable $default_context_path used later in abreviated Atom feed
			$solrDocument->default_context_path = $default_context_path;
		} 
		
		// For non-root-level items:
		// Get the default context path (there should only be one.)
		// Also index the hierarchy levels - def_context_*
		$j = 0; //used to generate 'def_context_*' fields in solr
		$default_context_path = "";
		if ($spatialItem->xpath("//arch:spatialUnit/oc:context/oc:tree[@id='default']")) {
			foreach ($spatialItem->xpath("//arch:spatialUnit/oc:context/oc:tree[@id='default']/oc:parent/oc:name") as $path) {
				$dynamicDefaultContextField = "def_context_" . $j;
				$solrDocument->$dynamicDefaultContextField = $path;
				$default_context_path .= $path . "/";
				$j++;
			}
			$solrDocument->default_context_path = $default_context_path;
		}//end condition with default context tree
		
		
		// Get the additional context paths
		// first check for the presence of additional paths
		if ($spatialItem->xpath("//arch:spatialUnit/oc:context/oc:tree[not(@id='default')]")) {
	
			// initialize $additional context path and make sure it's storing an empty string
			$additional_context_path = "";
			
			// get the contexts from each non-default tree
			foreach ($spatialItem->xpath("//arch:spatialUnit/oc:context/oc:tree[not(@id='default')]") as $non_default_tree) {
				//echo "additional_context_path: ";
				// iterate through the trees to build the context path(s)
				$jj = 0;
				foreach ($non_default_tree->xpath("oc:parent/oc:name") as $alt_path) {
					$dynamicAdditionalContextField = "add_context_" . $jj;
					$solrDocument->setMultiValue($dynamicAdditionalContextField, $alt_path);
					$additional_context_path .= $alt_path . "/";
					$jj++;
				}
	
				$solrDocument->setMultiValue("additional_context_path", $additional_context_path);
			}
		
		}//end condition with another context tree
	
		// Count image media <field name="image_media_count" type="sint" indexed="true" stored="false" required="true" multiValued="false" />
		$image_media = $spatialItem->xpath("//arch:spatialUnit/arch:observations/arch:observation/arch:links/oc:media_links/oc:link[oc:type='image']");  
		// if the xpath above returns either false or null... 
		if (!$image_media) {
			$image_media_count = 0;
			$solrDocument->image_media_count = 0;
		} else { // otherwise, the xpath above returns a non-empty array which we can count.
			$solrDocument->image_media_count = count($image_media);
			$image_media_count = count($image_media);
		}
		
		// Count other binary media <field name="other_binary_media_count" type="sint" indexed="true" stored="false" required="true" multiValued="false" />
		$other_binary_media = $spatialItem->xpath("//arch:spatialUnit/arch:observations/arch:observation/arch:links/oc:media_links/oc:link[oc:type!='image']");
		if (!$other_binary_media) { // if there are no other binary media, set the count to 0.
			$other_binary_media_count = 0;
			$solrDocument->other_binary_media_count = 0;
		} else { // otherwise, the xpath above returns a non-empty array which we can count.
			$solrDocument->other_binary_media_count = count($other_binary_media);
			$other_binary_media_count =  count($other_binary_media);
		}
		 
		 
		// Count diary items <field name="diary_count" type="sint" indexed="true" stored="false" required="true" multiValued="false" />
		$diary = $spatialItem->xpath("//arch:spatialUnit/arch:observations/arch:observation/arch:links/oc:diary_links/oc:link");
		if (!$diary) {
			$diary_count = 0;
			$solrDocument->diary_count = 0;
		} else { // otherwise, the xpath above returns a non-empty array which we can count.
			$diary_count = count($diary);
			$solrDocument->diary_count = $diary_count;
		}
		
			 
		 /*   <!-- tags: this field is a flattened version of user_tags/tag/value -->
		      <field name="user_tag" type="string" indexed="true" stored="true" required="false" multiValued="true"  />
		      
		      <!-- tag content creator. this is only present if there is a tag. the value for '*' comes from tag/value. the value of this element comes from creator_name. -->
		      <field name="tag_creater_name" type="string" indexed="true" stored="true" required="false" multiValued="true" />
		      
		      <!-- the label for the set of items carrying this tag. the value for '*' comes from tag/value. the value of this element comes from set_label. -->
		      <field name="tag_set_label" type="string" indexed="true" stored="true" required="true" multiValued="true" />
		      */
		$count_public_tags = 0; // value used to help calculate interest_score	
		if ($spatialItem->xpath("//arch:spatialUnit/oc:social_usage/oc:user_tags/oc:tag")) {
			foreach ($spatialItem->xpath("//arch:spatialUnit/oc:social_usage/oc:user_tags/oc:tag[@type='text' and @status='public']") as $tag) { // loop through and get the tags. 
				foreach ($tag->xpath("oc:name") as $user_tag) {
					$solrDocument->setMultiValue("user_tag", $user_tag); // add tag to the solr document
					// array of tags to be used later for Atom feed entry
					$user_tags[] .= $user_tag; // array of tags to be used later for Atom feed entry
				}
				foreach ($tag->xpath("oc:tag_creator/oc:creator_name") as $tag_creator_name) {
					$solrDocument->setMultiValue("tag_creator_name", $tag_creator_name); // add tag creator to the solr document
				}
				foreach ($tag->xpath("oc:tag_creator/oc:set_label") as $tag_set_label) {
					$solrDocument->setMultiValue("tag_set_label", $tag_set_label);
				}
			$count_public_tags++; // used to help calculate interest_score	
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
		    
		 */
		if ($spatialItem->xpath("//arch:spatialUnit/oc:social_usage/oc:user_tags/oc:tag")) {
			foreach ($spatialItem->xpath("//arch:spatialUnit/oc:social_usage/oc:user_tags/oc:tag[@type='text' and @status='private']") as $tag) { // loop through and get the tags. 
	
				foreach ($tag->xpath("oc:name") as $user_tag) {
					$solrDocument->setMultiValue("private_user_tag", $user_tag); // add tag to the solr document
				}
				foreach ($tag->xpath("oc:tag_creator/oc:creator_name") as $tag_creator_name) {
					$solrDocument->setMultiValue("private_tag_creator_name", $tag_creator_name); // add tag creator to the solr document
				}
				foreach ($tag->xpath("oc:tag_creator/oc:set_label") as $tag_set_label) {
					$solrDocument->setMultiValue("private_tag_set_label", $tag_set_label);
				}
	
			}
		}//end condition for tags
		
		
		// Properties and variables
		
		$number_properties = 0;  // used to help calculate the interest_score. if the item has no properties, set $number_properties to 0
		// Verify that there are properties associated with this item.
		if ($spatialItem->xpath("//arch:spatialUnit/arch:observations/arch:observation/arch:properties/arch:property")) {
			// get property count for the interest_score
			$number_properties = count($spatialItem->xpath("//arch:spatialUnit/arch:observations/arch:observation/arch:properties/arch:property"));
			// get the alphanumeric properties and index them as a 'notes' field. 
			foreach ($spatialItem->xpath("//arch:spatialUnit/arch:observations/arch:observation/arch:properties/arch:property/oc:var_label[@type='alphanumeric']") as $var_label) {
				foreach ($var_label->xpath(".") as $var_alpha) {
					$note = $var_alpha . " ";
					$solrDocument->setMultiValue("variable_alpha", $var_alpha);
				}
				foreach ($var_label->xpath("../oc:show_val") as $show_val) {
					$note .= $show_val;
					$dynamic_field_name = OpenContext_SolrIndexer::solrEscape($var_alpha) . "_var_alpha_val";
					$solrDocument->setMultiValue($dynamic_field_name, $show_val);
				}
				$solrDocument->setMultiValue("notes", $note);
			}//end loop through alpha_numeric variables
	
			foreach ($spatialItem->xpath("//arch:spatialUnit/arch:observations/arch:observation/arch:properties/arch:property/oc:var_label[@type='nominal']") as $var_label) {
				foreach ($var_label->xpath(".") as $var_nom) {
					$solrDocument->setMultiValue("variable_nominal", $var_nom);
				}
				foreach ($var_label->xpath("../oc:show_val") as $show_val) {
					$dynamic_field_name = OpenContext_SolrIndexer::solrEscape($var_nom) . "_var_nom_val";
					$solrDocument->setMultiValue($dynamic_field_name, $show_val);
				}
	
			}
	
			foreach ($spatialItem->xpath("//arch:spatialUnit/arch:observations/arch:observation/arch:properties/arch:property/oc:var_label[@type='calendar']") as $var_label) {
				foreach ($var_label->xpath(".") as $var_cal) {
					$solrDocument->setMultiValue("variable_calendric", $var_cal);
				}
				foreach ($var_label->xpath("../date") as $cal_val) {
					$dynamic_field_name = OpenContext_SolrIndexer::solrEscape($var_cal) . "_var_cal_val";
					$solrDocument->setMultiValue($dynamic_field_name, $cal_val);
				}
	
			}
	
			// check for integer variables 
			foreach ($spatialItem->xpath("//arch:spatialUnit/arch:observations/arch:observation/arch:properties/arch:property/oc:var_label[@type='integer']") as $var_label) {
				foreach ($var_label->xpath("../oc:show_val") as $show_val) {
					// test whether $show_val's value is non-null and really is an integer. It may be neither.
					if ($show_val && (intval($show_val) === $show_val)) {
						// $show_val is non-null and is an integer
						$solrDocument->setMultiValue("variable_integer", $var_label);
						
						$dynamic_field_name = OpenContext_SolrIndexer::solrEscape($var_label) . "_var_int_val";
						$solrDocument->setMultiValue($dynamic_field_name, $show_val);
						
						$dynamic_field_name_hr = OpenContext_SolrIndexer::solrEscape($var_label) . "_var_int_val_hr";
						$solrDocument->setMultiValue($dynamic_field_name_hr, $show_val);
						
					} else {
						// $show_val is not an intger
						$note = $var_label . " " . $show_val;
						$solrDocument->setMultiValue("notes", $note);
						
					}
				}
			}
	
			// get the decimal variable
			foreach ($spatialItem->xpath("//arch:spatialUnit/arch:observations/arch:observation/arch:properties/arch:property/oc:var_label[@type='decimal']") as $var_label) {
				foreach ($var_label->xpath("../oc:show_val") as $show_val) {
					// make sure the value is numeric (so it can be indexed as a float).
					if (is_numeric((string) ($show_val))) {
						$solrDocument->setMultiValue("variable_decimal", $var_label);
						
						// the value of decimal variable in non-human-readable format - useful for number comaparisons, etc.
						$dynamic_field_name = OpenContext_SolrIndexer::solrEscape($var_label) . "_var_dec_val";
						$solrDocument->setMultiValue($dynamic_field_name, $show_val);
						
						// store but don't index a human-readable version for display purposes
						$dynamic_field_name_hr = $var_label . "_var_dec_val_hr";
						$solrDocument->setMultiValue($dynamic_field_name_hr, $show_val);
						
					} else {
						// if the value does not validate as numeric, index it as a notes field
						$note = $var_label . " " . $show_val;
						$solrDocument->setMultiValue("notes", $note);
					}
	
				}
			}
	
			foreach ($spatialItem->xpath("//arch:spatialUnit/arch:observations/arch:observation/arch:properties/arch:property/oc:var_label[@type='ordinal']") as $var_label) {
				foreach ($var_label->xpath(".") as $var_ord) {
					$solrDocument->setMultiValue("variable_ordinal", $var_ord);
				}
				foreach ($var_label->xpath("../oc:show_val") as $show_val) {
					$dynamic_field_name = OpenContext_SolrIndexer::solrEscape($var_ord) . "_var_ord_val"; 
					$solrDocument->setMultiValue($dynamic_field_name, $show_val);
				}
	
			}
	
			foreach ($spatialItem->xpath("//arch:spatialUnit/arch:observations/arch:observation/arch:properties/arch:property/oc:var_label[@type='boolean']") as $var_label) {
				foreach ($var_label->xpath(".") as $var_bool) {
					$solrDocument->setMultiValue("variable_boolean", $var_bool);
				}
				foreach ($var_label->xpath("../oc:show_val") as $show_val) {
					$dynamic_field_name = OpenContext_SolrIndexer::solrEscape($var_bool) . "_var_bool_val";  // this escaping is probably not necessary but could help handle messy data
					$solrDocument->setMultiValue($dynamic_field_name, $show_val);
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
		$count_person_links = 0; // this value is used later to help calculate the interesting_score. 
		foreach ($spatialItem->xpath("//arch:spatialUnit/arch:observations/arch:observation/arch:links/oc:person_links/oc:link") as $person_link) {
			foreach ($person_link->xpath("oc:name") as $person_name) {
				$solrDocument->setMultiValue("person_link", $person_name);
				//echo "person_link: " . $person_name;
				//echo "<br/>";
			}
			foreach ($person_link->xpath("oc:relation") as $relation) {
				$dynamic_field_name = OpenContext_SolrIndexer::solrEscape($person_name) . "_person";
				$solrDocument->setMultiValue($dynamic_field_name, $relation);
				//echo $dynamic_field_name . ": " . $relation;
				//echo "<br/>";
			}
			$count_person_links++; // used later to help calculate the interest_score
		}
	
		/*
		<!-- external web references, pingbacks: the value of this element comes from '../outside_pings/pings/name' -->
		 <field name=" pings " type=" string " indexed=" true " stored=" true " required=" false " multiValued=" true "  /> 
		*/
		
		$count_ext_refs = 0; // used to help calculate interest_score
		if ($spatialItem->xpath("//arch:spatialUnit/oc:social_usage/oc:external_references")) {		
			foreach ($spatialItem->xpath("//arch:spatialUnit/oc:social_usage/oc:external_references/oc:reference/oc:name") as $external_reference) {
				$solrDocument->setMultiValue("external_reference", $external_reference);
				//echo "external_reference: " . $external_reference;
				//echo "<br/>";
				$count_ext_refs++;  // used to help calculate interest_score
			}
	
		}
	
		/*
		<!-- notes: this field is free form text about an item.  The value from this element comes from '//arch:spatialUnit/arch:observations/arch:observation/notes/note/string' -->
		<field name=" notes " type=" text_ws " indexed=" true " stored=" false " required=" false " multiValued=" true "  />
		*/
	
		if ($spatialItem->xpath("//arch:spatialUnit/arch:observations/arch:observation/arch:notes/arch:note")) {
			foreach ($spatialItem->xpath("//arch:spatialUnit/arch:observations/arch:observation/arch:notes/arch:note/arch:string") as $note) {
				$solrDocument->setMultiValue("notes", $note);
				//echo "notes: " . $note;
				//echo "<br/>";
			} 
		}
	
		/* Note: view_count removed from index in favor of interest_score.
		 * Now used to help calculate the interest_score */
		foreach ($spatialItem->xpath("//arch:spatialUnit/oc:social_usage/oc:item_views/oc:count") as $item_views) {
			//echo "view_count: " . $item_views;
			//echo "<br/>";
		}
		
		/*
		/*  <!-- georeferencing: this is not necessarily for the user interface, but could support a restful geographic web service.-->
		    <!-- latitude: The value for this element comes from ../geo_reference/geo_lat.  This is a decimal value. -->
		    <field name="geo_lat" type="double" indexed="true" stored="true" required="true" /> 
		*/
		
		// 
		foreach ($spatialItem->xpath("//arch:spatialUnit/oc:metadata/oc:geo_reference") as $geo_reference) {
			foreach ($geo_reference->xpath("oc:geo_lat") as $geo_lat) {
				$solrDocument->geo_lat = $geo_lat;
				//echo "geo_lat : " . $geo_lat;
				//echo "<br/>";
			}
			foreach ($geo_reference->xpath("oc:geo_long") as $geo_long) {
				$solrDocument->geo_long = $geo_long;
				//echo "geo_long: " . $geo_long;
				//echo "<br/>";
			}
		}
		
		// polygon 
		if ($spatialItem->xpath("//arch:spatialUnit/oc:metadata/oc:geo_reference/oc:metasource[@ref_type='self']")) {
			$self_geo_reference = true; // this value is used to calculate interesting_score.
			
			if ($spatialItem->xpath("//arch:spatialUnit/oc:metadata/oc:geo_reference/gml:Polygon/gml:exterior/gml:LinearRing/gml:posList")) {
				$geo_polygon = true; // this value is used to calculate interesting_score. and also in the Atom generation code
				foreach ($spatialItem->xpath("//arch:spatialUnit/oc:metadata/oc:geo_reference/gml:Polygon/gml:exterior/gml:LinearRing/gml:posList") as $polygon_pos_list ) {
					//echo "polygon_pos_list: " . $polygon_pos_list;
					//echo "<br/>";
				}
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
		
		if ($spatialItem->xpath("//arch:spatialUnit/oc:social_usage/oc:user_tags/oc:tag")) {
			foreach ($spatialItem->xpath("//arch:spatialUnit/oc:social_usage/oc:user_tags/oc:tag[@type='chronological' and @status='public']") as $chrono_tag) {
				foreach ($chrono_tag->xpath("oc:chrono/oc:time_start") as $time_start) {
					$solrDocument->setMultiValue("time_start", $time_start);
					//echo "time_start: " . $time_start;
					//echo "<br>";
					// store but don't index human-readable version of time_start
					$solrDocument->setMultiValue("time_start_hr", $time_start);
					//echo "time_start_hr: " . $time_start;
					//echo "<br>";
				}
				foreach ($chrono_tag->xpath("oc:chrono/oc:time_finish") as $time_end) {
					$solrDocument->setMultiValue("time_end", $time_end);
					//echo "time_end: " . $time_end;
					//echo "<br>";
					// store but don't index additional human-readable version of this field
					$solrDocument->setMultiValue("time_end_hr", $time_end);
					//echo "time_end_hr: " . $time_end;
					//echo "<br>";
				}
				foreach ($chrono_tag->xpath("oc:tag_creator/oc:creator_name") as $chrono_creator_name) {
					$solrDocument->setMultiValue("chrono_creator_name", $chrono_creator_name);
					//echo "chrono_creator_name: " . $chrono_creator_name;
					//echo "<br>";
				}
				foreach ($chrono_tag->xpath("oc:tag_creator/oc:set_label") as $chrono_set_label) {
					$solrDocument->setMultiValue("chrono_set_label", $chrono_set_label);
					//echo "chrono_set_label: " . $chrono_set_label;
					//echo "<br>";
				}
			}
		}
		
		// private chrono tags
		/*
		<!-- Private chronological Information  -->
		<!-- not required -->
		<!-- start  time_start / slong-->
		<field name="private_time_start" type="slong" indexed="true" stored="false" required="false" multiValued="true" />
		<!-- end    time_end  / long -->
		<field name="private_time_end" type="slong" indexed="true" stored="false" required="false" multiValued="true" />
	
		<!-- human-readable start  time_start / long-->
		<field name="private_time_start_hr" type="long" indexed="false" stored="true" required="false" multiValued="true" />
		<!-- human-readable end  time_end  / long -->
		<field name="private_time_end_hr" type="long" indexed="false" stored="true" required="false" multiValued="true" />
		
		<!-- Tag creator  multi -->
		<field name="private_chrono_creator_name" type="string" indexed="true" stored="true" required="false" multiValued="true" />
		<!-- tag set -->
		<field name="private_chrono_set_label" type="string" indexed="true" stored="true" required="false" multiValued="true" />
		 
		*/
		
			if ($spatialItem->xpath("//arch:spatialUnit/oc:social_usage/oc:user_tags/oc:tag")) {
			foreach ($spatialItem->xpath("//arch:spatialUnit/oc:social_usage/oc:user_tags/oc:tag[@type='chronological' and @status='private']") as $private_chrono_tag) {
				foreach ($private_chrono_tag->xpath("oc:chrono/oc:time_start") as $private_time_start) {
					$solrDocument->setMultiValue("private_time_start", $private_time_start);
					//echo "private_time_start: " . $private_time_start;
					//echo "<br>";
					// store but don't index human-readable version of time_start
					$solrDocument->setMultiValue("private_time_start_hr", $time_start);
					//echo "private_time_start_hr: " . $private_time_start;
					//echo "<br>";
				}
				foreach ($private_chrono_tag->xpath("oc:chrono/oc:time_finish") as $private_time_end) {
					$solrDocument->setMultiValue("private_time_end", $private_time_end);
					//echo "private_time_end: " . $private_time_end;
					//echo "<br>";
					// store but don't index additional human-readable version of this field
					$solrDocument->setMultiValue("private_time_end_hr", $private_time_end);
					//echo "private_time_end_hr: " . $private_time_end;
					//echo "<br>";
				}
				foreach ($private_chrono_tag->xpath("oc:tag_creator/oc:creator_name") as $private_chrono_creator_name) {
					$solrDocument->setMultiValue("private_chrono_creator_name", $private_chrono_creator_name);
					//echo "chrono_creator_name: " . $private_chrono_creator_name;
					//echo "<br>";
				}
				foreach ($private_chrono_tag->xpath("oc:tag_creator/oc:set_label") as $private_chrono_set_label) {
					$solrDocument->setMultiValue("private_chrono_set_label", $private_chrono_set_label);
					//echo "private_chrono_set_label: " . $private_chrono_set_label;
					//echo "<br>";
				}
			}
		}
		
		
		
		
	
		/*
		*<!-- dublin core creator. the value for this element comes from ../metadata/dc_Creator -->
		<field name="creator" type="string" indexed=" true " stored=" true " required=" false " multiValued=" true " />
		*/
		
		$creators = $spatialItem->xpath("//arch:spatialUnit/oc:metadata/dc:creator");
		foreach ($creators as $creator) {
			$solrDocument->setMultiValue("creator", $creator);
			//echo "creator: " . $creator;
			//echo "<br/>";
		}
	
		/*
		 * <!-- dublin core subject. the value for this element comes from ../metadata/dc_Subject -->
		<field name=" subject " type=" string " indexed=" true " stored=" true " required=" false " multiValued=" true " /> 
		 */
		
		foreach ($spatialItem->xpath("//arch:spatialUnit/oc:metadata/dc:subject") as $subject) {
			$solrDocument->setMultiValue("subject", $subject);
			//echo "subject: " . $subject;
			//echo "<br/>";
		}
	
		/*
		 * <!-- dublin core coverage. the value for this element comes from ../metadata/dc_Coverage -->
		<field name=" coverage " type=" string " indexed=" true " stored=" true " required=" false " multiValued=" true " />
		 */
		foreach ($spatialItem->xpath("//arch:spatialUnit/oc:metadata/dc:coverage") as $coverage) {
			$solrDocument->setMultiValue("coverage", $coverage);
			//echo "coverage: " . $coverage;
			//echo "<br/>";
		}
	
		/*<!-- copyright license URI. the value for this element comes from ../cc_lic/lic_url -->
		<!-- this license information would most likely only see use for a web service, not a human interface -->
		<field name=" license_uri " type=" string " indexed=" true " stored=" true " required=" true " multiValued=" false " />
		*/
		foreach ($spatialItem->xpath("//arch:spatialUnit/oc:metadata/oc:copyright_lic/oc:lic_URI") as $license_uri) {
			$solrDocument->license_uri = $license_uri;
			//echo "license_uri: " . $license_uri;
			//echo "<br/>";
		}
	
	
		/* interest_score */
		
		if (!$note) {
			$total_character_length_notes = 0;
		} else {
			$total_character_length_notes = strlen($note);
		}
	
		$count_media_links = $image_media_count + $other_binary_media_count;
			
		$interest_score = 0;
		$interest_score += $number_properties; 
		$interest_score += ($total_character_length_notes / 100); 
		$interest_score += ($count_media_links * 4); 
		$interest_score += ($count_diary_links * 2); 
		$interest_score += ($count_person_links * .5); 
	
		if($self_geo_reference){ 
			$interest_score += 4;
			if($geo_polygon){  
				$interest_score += 4;
			}
		}
		$interest_score += ($count_public_tags * 1.5); 
		$interest_score += ($count_ext_refs * 2); 
		$interest_score += ($item_views / 100); 
		
		$solrDocument->interest_score = $interest_score;
		
		foreach ($spatialItem->xpath("//atom:entry") as $entry) {
			$full_entry_string = $entry->asXML();
		}
		
		foreach ($spatialItem->xpath("//atom:entry/arch:spatialUnit") as $spatial_xml) {
			$spatial_string = $spatial_xml->asXML();
		}
		
		//remove full archaeoml record from the abbreviated atom entry
		$atom_entry = str_replace($spatial_string, "", $full_entry_string);
		
		//fix the abreviated atom entry to remove unwanted, error-casusing namespace details
		$atom_entry = str_replace('xmlns:xhtml', 'xmlns', $atom_entry);
		
		$good_entry_element = "<?xml version='1.0' encoding='utf-8'?>";
		$good_entry_element .= "<entry xmlns='http://www.w3.org/2005/Atom' xmlns:georss='http://www.georss.org/georss' xmlns:kml='http://www.opengis.net/kml/2.2' xmlns:gml='http://www.opengis.net/gml'>";
		
		$close_tag_pos = strpos($atom_entry, ">", 0);
		$entry_length = strlen($atom_entry);
		$bad_entry_element = substr($atom_entry,0,$close_tag_pos+1);
		$trunc_entry = substr($atom_entry, ($close_tag_pos+1), ($entry_length -$close_tag_pos-1));
		$atom_entry = $good_entry_element.$trunc_entry ;
		$atom_entry  = str_replace('<xhtml:', '<', $atom_entry);
		$atom_entry  = str_replace('</xhtml:', '</', $atom_entry);
		
		//$atom_entry_dom = new DOMDocument("1.0", "utf-8");
		//$atom_entry_dom->loadXML($atom_entry);
		//$atom_entry_dom->formatOutput = true;
		//$atom_entry = $atom_entry_dom->saveXML();
		
		// add the entry to the solr document
		$solrDocument->atom_abbreviated = $atom_entry;
		
		$atomFullDocString = $spatialItem->saveXML();
		$solrDocument->atom_full = $atomFullDocString;
	
		//return ($atom_entry);
		//echo var_dump($solrDocument);
		// once we've constructed our solr document, add it to the index
		//$solr->addDocument($solrDocument);
		// and commit it
		//$solr->commit();
		
		unset($solrDocument);
	}//end reindex function
	
	
	
}//end class declaration

?>
