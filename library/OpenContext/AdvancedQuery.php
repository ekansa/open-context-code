<?php

class OpenContext_FacetQuery {

	public static function solrEscape($stringToEscape) {
	/**  In addition to the space character, solr requires that we escape the following characters because
	they're part of solr/lucene's query language: + - && || ! ( ) { } [ ] ^ " ~ * ? : \
	*/
    
		//characters we need to escape
		$search = array('\\', ' ', ':', '\'', '&&', '||', '(', ')', '+', '-', '!', '{', '}','[', ']', '^', '~', '*', '"', '?');
	       
		// escaped version of characters
		$replace = array('\\\\', '\ ', '\:', '\\\'', '\&\&', '\|\|', '\(', '\)', '\+', '\-', '\!', '\{', '\}', '\[', '\]', '\^', '\~', '\*', '\\"', '\?');
	    
		return str_replace($search, $replace, $stringToEscape);
	}//end function


	public static function clean_context_path($default_context_path){
		
		if ($default_context_path) {
			// escape problematic characters
			$default_context_path = OpenContext_FacetQuery::solrEscape($default_context_path);
			// solr expects default_context_path to end with a slash, so add it.
			$default_context_path = $default_context_path . "/";
		}
                else{
                        $default_context_path = false;  
                }
	
		return $default_context_path;
	}//end function


        public static function unfold_deep_parameters($requestParams, $slashCount){
                
                if(array_key_exists("proj", $requestParams)){
                        $proj = true; 
                }
                else{
                        $proj = false;    
                }
                if(array_key_exists("cat", $requestParams)){
                       $cat = true; 
                }
                else{
                        $cat = false;
                }
                
                if($cat || $proj || ($slashCount > 1)){
                        $output = true;
                }
                else{
                        $output = false;
                }
                
                return $output;        
        }//end function


        public static function test_param_key($key, $searcharray){
              if(array_key_exists($key, $searcharray)){
                       $output = $searcharray[$key]; 
                }
                else{
                        $output = false;
                }
                return $output;
        }//end function

        public static function check_coodinates($boundingBox){
                $bBox_valid = false;
                $bBox_array = array();
                $bBox_array = explode(",",$boundingBox);
                if(count($bBox_array) == 4){
                        $bBox_valid = true;
                        foreach ($bBox_array as $act_cord){
                                if((abs($act_cord)>180)||(!is_numeric($act_cord))){
                                       $bBox_valid = false; 
                                }
                        }//end loop through coordinates
                                
                        if($bBox_valid){
                               if($bBox_array[0] /*min long*/ > $bBox_array[2] /*max long*/){
                                    $bBox_valid = false;    
                                }
                                if($bBox_array[1] /*min lat*/ > $bBox_array[3] /*min lat*/){
                                    $bBox_valid = false;    
                                }
                        }
                        
                                
                }//end case of valid number of coordinates
                
                if($bBox_valid){
                        $view = $bBox_array[0]."E, ".$bBox_array[1]."N to ".$bBox_array[2]."E, ".$bBox_array[3]."N";
                }
                else{
                        $view = "So Sorry! Invalid search coordinates.";
                }
                
                $output =  array("valid"=>$bBox_valid, "vals"=>$bBox_array, "view"=>$view );
                return $output;
                
        }//end function

	public static function build_simple_parameters($requestParams){
                
                // start building the array of query parameters to send to solr
                $param_array = array();
               
                $param_array["facet"] = "true";
                $param_array["facet.mincount"] = "1";
                $param_array["fq"] = null; // initialize the fq paramter; otherwise we could get "Undefined index: fq" errors
                $param_array["facet.field"] = null;
        
        
                // get the project parameter
                $proj = OpenContext_FacetQuery::test_param_key("proj", $requestParams);
                if ($proj) {
                        $proj = utf8_encode($proj);
                        $proj = OpenContext_UTF8::charset_encode_utf_8($proj);
                        $proj = OpenContext_FacetQuery::solrEscape($proj);
                        
                        // if the user selects a project, filter results by that project
                        $param_array["fq"] = "project_name:" . $proj;
                }
        
                // get the category parameter. (note: "cat" maps to "item_class" in our solr schema)
                $cat = OpenContext_FacetQuery::test_param_key("cat", $requestParams);
                if ($cat) {
                    $cat = OpenContext_FacetQuery::solrEscape($cat);
                    if ($param_array["fq"]) {
                    $param_array["fq"] .= " && item_class:" . $cat;
                    } else {
                        $param_array["fq"] = "item_class:" . $cat;
                    }
                    
                }
        
                // get the full_text parameter
                $textSearch = OpenContext_FacetQuery::test_param_key("q", $requestParams);
                if ($textSearch) {
                    //$textSearch = OpenContext_FacetQuery::solrEscape($textSearch);
                    //$textQuery = "full_text:".$textSearch;
                    $textQuery = OpenContext_FacetQuery::solr_fulltext_terms($textSearch, "full_text");
                    if ($param_array["fq"]) {
                        //$param_array["fq"] .= " && full_text:" . $textSearch;
                        $param_array["fq"] .= " && ".$textQuery;
                    } else {
                        //$param_array["fq"] = "full_text:" . $textSearch;
                        $param_array["fq"] = $textQuery;
                    }
                /* Note: solr syntaxt for multiple query terms is ?q=full_text:taxon+full_text:ovis
                  q=full_text:"Distal diaphysis fragment"+full_text:Metapodial
                */
                
                }
        
                // "tagger" maps to "tag_creator_name" in our solr schema
                $tagger = OpenContext_FacetQuery::test_param_key("tagger", $requestParams);
                if ($tagger) {
                    $tagger = OpenContext_FacetQuery::solrEscape($tagger);
                    if ($param_array["fq"]) {
                        $param_array["fq"] .= " && tag_creator_name:" . $tagger;
                    } else {
                        $param_array["fq"] = "tag_creator_name:" . $tagger;
                    }
                }
        
                $image = OpenContext_FacetQuery::test_param_key("image", $requestParams);
                if ($image == 'true') {
                        
                    if ($param_array["fq"]) {
                        $param_array["fq"] .= " && image_media_count:[1 TO *]";
                    } else {
                        $param_array["fq"] = "image_media_count:[1 TO *]";
                    }
                }
        
                // other binary media
                $other = OpenContext_FacetQuery::test_param_key("other", $requestParams);
                 if ($other == 'true') {
                     
                     if ($param_array["fq"]) {
                         $param_array["fq"] .= " && other_binary_media_count:[1 TO *]";
                     } else {
                         $param_array["fq"] = "other_binary_media_count:[1 TO *]";
                     }
                 }
        
                // diary items
                $diary = OpenContext_FacetQuery::test_param_key("diary", $requestParams);
                if ($diary == 'true') {
                    
                    if ($param_array["fq"]) {
                        $param_array["fq"] .= " && diary_count:[1 TO *]";
                    } else {
                        $param_array["fq"] = "diary_count:[1 TO *]";
                    }
                }
        
                // person links
                $person = OpenContext_FacetQuery::test_param_key("person", $requestParams);
                if ($person) {
                    // handle non-ascii characters - encode enities as UTF8: &#199; becomes Ç 
                    $person = OpenContext_UTF8::charset_encode_utf_8($person);
                    $person = OpenContext_FacetQuery::solrEscape($person);
                    if ($param_array['fq']) {
                        $param_array['fq'] .= ' && person_link:' . $person; 
                    } else {
                        $param_array['fq'] = 'person_link:'. $person;
                    }
           
                }//end case with a person
        
                // get the time parameters
                $t_start = OpenContext_FacetQuery::test_param_key("t-start", $requestParams);
                $t_end = OpenContext_FacetQuery::test_param_key("t-end", $requestParams);
                
                // if there's a start and an end time
                if (is_numeric($t_start) && is_numeric($t_end)) {
                    if ($param_array['fq']) {
                        $param_array['fq'] .= ' && time_start:[' . $t_start . ' TO ' . $t_end . '] && time_end:['. $t_start . ' TO ' . $t_end . ']';
                    } else {
                        $param_array['fq'] = 'time_start:[' . $t_start . ' TO ' . $t_end . '] && time_end:['. $t_start . ' TO ' . $t_end . ']';
                    }
                
                // just a start time    
                } elseif (is_numeric($t_start) && (!$t_end)) {
                    if ($param_array['fq']) {
                        $param_array['fq'] .= ' && time_start:[' . $t_start . ' TO *] && time_end:['. $t_start . ' TO *]';
                    } else {
                        $param_array['fq'] = 'time_start:[' . $t_start . ' TO *] && time_end:['. $t_start . ' TO *]';
                    }
                
                // just an end time    
                }  elseif ((!$t_start) && is_numeric($t_end)) {
                    if ($param_array['fq']) {
                        $param_array['fq'] .= ' && time_start:[* TO ' . $t_end . '] && time_end:[* TO ' . $t_end . ']';
                    } else {
                        $param_array['fq'] = 'time_start:[* TO ' . $t_end . '] && time_end:[* TO ' . $t_end . ']';
                    }
                    
                }
        
                $boundingBox = OpenContext_FacetQuery::test_param_key("bBox", $requestParams);
                if($boundingBox){
                        /*bBox=-92.8,44.2,-88.9,46.0 is somewhere in North America
                         south-west corner: longitude,latitude X north-east corner: longitude, latitude
                       */
                        $bBox_check = array();
                        $bBox_check = OpenContext_FacetQuery::check_coodinates($boundingBox);                        
                        
                        if($bBox_check["valid"]){
                                $bBox_array = $bBox_check["vals"];
                                if ($param_array['fq']) {
                                       $param_array['fq'] .= ' && geo_long:[' . $bBox_array[0] . ' TO ' . $bBox_array[2] . '] && geo_lat:['. $bBox_array[1] . ' TO ' . $bBox_array[3] . ']';
                                } else {
                                       $param_array['fq'] = 'geo_long:[' . $bBox_array[0] . ' TO ' . $bBox_array[2] . '] && geo_lat:['. $bBox_array[1] . ' TO ' . $bBox_array[3] . ']';
                                }           
                        }//end case for valid query
                       
                }//end case for geo-spatial query
        
                //$param_array = "Smurf";
                return $param_array;
	}//end function



        /*This does the more involved and fancy operations to generate Solr query parameters
        from outide URL GET requests*/
        public static function build_complex_parameters($requestParams, $param_array, $extendedfacets, $context_depth, $facet_only){
                
                // array to store media facet query
                $param_array["facet.query"] = array("image_media_count:[1 TO *]", "other_binary_media_count:[1 TO *]", "diary_count:[1 TO *]");
                              
                // Descriptive properties/variables - we're using an array in the query string to express this: prop[key]=value
                
                // strings to store filter queries for solr
                $var_fq_string = null;
                $var_val_fq_string = null;
                // boolean value used to determine whether to unfold variables or to unfold variable values
                $unfold_vals = false;
                // array to store the variable values in the format that solr expects, i.e., *_var_nom_val, etc...
                $var_val_params = array();
                //$prop_array = $requestParams['prop'];
                $prop_array = OpenContext_FacetQuery::test_param_key("prop", $requestParams);
                if ($prop_array) {
                        foreach ($prop_array as $var => $val) {
                                if (isset($var)) {
                                    $var = OpenContext_FacetQuery::solrEscape($var); 
                                }
                                if (isset($val)) {
                                    $val = OpenContext_FacetQuery::solrEscape($val);
                                }
                                
                                // the $var_val_params array will be merged into the facet.field array later
                                $var_val_params = array($var . '_var_nom_val', $var . '_var_ord_val', $var . '_var_bool_val');    
                                   
                                // if the user has selected both a variable and a value    
                                if ($var && $val) {
                                   
                                    /* We need to do additional escpaping for searches like Element\\\ Certainty_var_nom_val:Certain because
                                    * the variable names includes special (to solr) characters.
                                    * we also need to properly escape parentheses that are part of variable namees, such as
                                    * Fusion\\\ \\\(Distal\\\)_var_nom_val:Fused
                                    * Yes, this is a lot of slashes, but it works. */ 
                                    $var = str_replace('\ ', '\\\\\ ', $var);
                                    $var = str_replace('\(', '\\\\\(', $var);
                                    $var = str_replace('\)', '\\\\\)', $var);
                                    
                                    $numeric_term = "";
                                    $num_vals = array();
                                    
                                        if(substr_count($val, ",")==1){
                                                $num_vals = explode(",", $val);
                                                $num_valA = str_replace("=", "", $num_vals[0]);
                                                $num_valA = str_replace(">", "", $num_valA);
                                                $num_valA = str_replace("<", "", $num_valA);       
                                                $num_valB = str_replace("=", "", $num_vals[1]);
                                                $num_valB = str_replace(">", "", $num_valB);
                                                $num_valB = str_replace("<", "", $num_valB);
                                                $numericA = is_numeric($num_valA);
                                                
                                                if($num_vals[0] == $num_vals[1]){
                                                        $numericB = true;
                                                        $num_valB = false;
                                                }
                                                else{
                                                        $numericB = is_numeric($num_valB);
                                                }
                                        }
                                        else{
                                                $num_vals[0] = $val;
                                                $num_valA = str_replace("=", "", $val);
                                                $num_valA = str_replace(">", "", $num_valA);
                                                $num_valA = str_replace("<", "", $num_valA);
                                                $num_valB = false;
                                                $numericA = is_numeric($num_valA);
                                                $numericB = true;
                                        }                 
                                    
                                    
                                                           
                                    if($numericA && $numericB){
                                        
                                        $max_limit_int = "*";
                                        $max_limit_dec = "*";
                                        $min_limit_int = "*";
                                        $min_limit_dec = "*";
                                        
                                        $equal_termA = "";
                                        $equal_termB = "";
                                        
                                        $numeric_term = ' || '.$var.'_var_int_val:'.round($num_valA,0).' || '.$var.'_var_dec_val:'.$num_valA.' ';
                                        
                                        $change_term = false;
                                        
                                        if(substr_count($num_vals[0], "=")>0){
                                                $equal_termA = $numeric_term;
                                        }
                                        
                                        if(substr_count($num_vals[0], ">")>0){
                                                $min_limit_int = round(($num_valA + 1),0); //add an integer
                                                $min_limit_dec = $num_valA + .00000001;
                                                $change_term = true;
                                        }
                                        
                                        if(substr_count($num_vals[0], "<")>0){
                                                $max_limit_int = round(($num_valA - 1),0); //add an integer
                                                $max_limit_dec = $num_valA - .00000001;
                                                $change_term = true;
                                        }
                                        
                                        //$num_valB = false;
                                        
                                        if($num_valB != false){
                                                
                                                $change_term = true;
                                                
                                                if($num_valB > $num_valA){
                                                       $max_limit =  $num_valB;
                                                       $min_limit =  $num_valA;
                                                }
                                                else{
                                                        $max_limit =  $num_valA;
                                                        $min_limit =  $num_valB;
                                                }
                                                
                                                $min_limit_int = round(($min_limit + 1),0); //add an integer
                                                $min_limit_dec = $min_limit + .00000001;
                                                $max_limit_int = round(($max_limit - 1),0); //add an integer
                                                $max_limit_dec = $max_limit - .00000001;
                                                
                                                if(substr_count($num_vals[1], "=")>0){
                                                        $equal_termB = ' || '.$var.'_var_int_val:'.round($num_valB,0).' || '.$var.'_var_dec_val:'.$num_valB.' ';
                                                }
                                        }
                                        
                                        if($change_term){
                                                $numeric_term = ' || '.$var.'_var_int_val:['.$min_limit_int.' TO '.$max_limit_int.'] || '.$var.'_var_dec_val:['.$min_limit_dec.' TO '.$max_limit_dec.']';
                                                $numeric_term .= $equal_termA.$equal_termB;
                                        }
                                        
                                        
                                    }//end case for numeric term
                                    
                                    $var_val_fq_string .= ' && (' . $var . '_var_nom_val:' . $val . ' || '  . $var . '_var_ord_val:' . $val . ' || ' . $var . '_var_bool_val:' . $val . $numeric_term. ')';    
                                
                                // if the user has selected a variable without a corresponding value     
                                } elseif ($var && !$val) {
                                    $unfold_vals = true;  // display values
                                    $var_fq_string .= ' && (variable_nominal:' . $var . ' || variable_ordinal:' . $var . ' || variable_boolean:' . $var . ')'; 
                                } 
                            
                        }//end loop through property array         
                }//end case with properties
                
                // array to store tags. we'll use this to help build the query for solr
                $tag = array();
                
                // string to store the tag filter query (fq) for solr
                $tag_fq_string = null;
                
                // TAGS
                //$tag_array = $param_array['tag'];
                $tag_array = OpenContext_FacetQuery::test_param_key("tag", $requestParams);
                if (($tag_array) && (is_array($tag_array))) {
                    foreach ($tag_array as $tag) {
                        $tag_fq_string .= " && user_tag:" . OpenContext_FacetQuery::solrEscape($tag);     
                    }         
                }//end case of tags
                
                // The facet fields to query for by default
                if($facet_only){
                        $param_array["facet.field"] = array($context_depth, "project_name", "item_class", "user_tag", "tag_creator_name", "time_start", "time_end", "geo_lat", "geo_long");
                }
                else{
                        $param_array["facet.field"] = array($context_depth, "project_name", "item_class", "user_tag", "tag_creator_name", "time_start", "time_end");
                }
                    
                // facets for variables - show these when def_context_* >= 2 or project_name = true or cat = true; hide when a variable is selected
                $variable_params = array("variable_nominal", "variable_ordinal", "variable_boolean");  
                
                // the user has selected a variable without a corresponding value, and has also selected category, or project or has navigated to an appropriate context depth, display variable values
                if (($unfold_vals) && ($extendedfacets)) { // if the user has selected a variable, $unfold_vals will be 'true.'
                    $param_array["facet.field"] = array_merge($param_array["facet.field"], $var_val_params);
                    // if a user has selected a variable, filter by that variable
                    if ($param_array["fq"]) {
                        if ($var_val_fq_string) {
                            $param_array["fq"] .= $var_val_fq_string . $var_fq_string;
                        } else {
                            $param_array["fq"] .= $var_fq_string;
                        }
                    } else {
                        if ($var_val_fq_string) {
                            $param_array["fq"] = substr($var_val_fq_string, 3) . $var_fq_string; // remove leading "&& " from the string since we're not appending it to an existing "fq."
                        } else { 
                            $param_array["fq"] = substr($var_fq_string, 3);
                        }
                    }
                    
                // the user has selected a category or project, or has navigated to an appropriate context depth, and has either selected no vars or has selected var/val pairs; unfold variables        
                } elseif ((!$unfold_vals) && ($extendedfacets)) {
                    //display variables
                    $param_array["facet.field"] = array_merge($param_array["facet.field"], $variable_params);
                    if ($var_val_fq_string) {
                        if ($param_array["fq"]) {
                            $param_array["fq"] .= $var_val_fq_string;
                        } else {
                            $param_array["fq"] = substr($var_val_fq_string, 3); // remove leading "&& " from the string since we're not appending it to an existing "fq."
                        }
                    }
                }
                        
                // for testing
                //$this->view->unfold_vals = $unfold_vals;
                //$this->view->param_array_fq = $param_array['fq'];
                
                // add person links
                $param_array["facet.field"] =  array_merge($param_array["facet.field"], array('person_link')); // add person links
                // if a user has selected a variable and value pair, filter by them and append any additional variable names without value pairs
                
                    
            
                // append tags to filter query
                if ($tag_fq_string && $param_array["fq"]) {
                    $param_array["fq"] .= $tag_fq_string;
                } elseif ($tag_fq_string && !$param_array["fq"]) {
                    $param_array["fq"] = substr($tag_fq_string, 3); //  if we're not appending to an existing $param_array['fq'] ,remove tje leading '&& '
                }
                
                //$param_array = "smuf";
                //$unfold_vals = "silly";
                
                $output = array("param_array" => $param_array, "unfold_vals" => $unfold_vals); 
                return $output;
        
        }//end function




        public static function parseSearchTerms($searchString) {
                $searchTerms = array();
                $offset = 0;
                while (($startQuoteOffset = strpos($searchString, '"', $offset)) !== false) {
                    // If the startQuoteOffset is > than offset, then fetch previous search terms
                    if ($startQuoteOffset > $offset) {
                        $tmpTerms = explode(' ', trim(substr($searchString, $offset, ($startQuoteOffset - $offset))));
                        if (is_array($tmpTerms) && count($tmpTerms) > 0) {
                            foreach ($tmpTerms AS $term) {
                                if (trim($term) == '') continue;
                                $searchTerms[] = $term;
                            }
                        }
                    }
            
                    // Fetch the item(s) within the quotes
                    if (($endQuoteOffset = strpos($searchString, '"', $startQuoteOffset+1)) !== false) {
                        // We have an end quote
                        $searchTerms[] = trim(substr($searchString, $startQuoteOffset+1, ($endQuoteOffset-$startQuoteOffset-1)));
                        $offset = $endQuoteOffset + 1;
                    } else {
                        // There is no end quote... let's go to the end of the string
                        $searchTerms[] = trim(substr($searchString, $startQuoteOffset+1));
                        $offset = strlen($searchString);
                    }
                }
            
                if ($offset < strlen($searchString)) {
                    // We still have keywords to include
                    $tmpTerms = explode(' ', substr($searchString, $offset));
                    foreach ($tmpTerms AS $term) {
                        if (trim($term) == '') continue;
                        $searchTerms[] = $term;
                    }
                }
            
                if (!empty($searchString) && count($searchTerms) == 0) {
                    // No quotes were provided in the search string
                    $searchTerms = explode(' ', trim($searchString));
                }
            
                return $searchTerms;
        }//end function parse search terms




        public static function solr_fulltext_terms($textSearch, $solrField){
                
                $search_array = array();
                $output = "";
                $search_array = OpenContext_FacetQuery::parseSearchTerms($textSearch);
                foreach($search_array AS $term){
                        $term = OpenContext_FacetQuery::solrEscape($term);
                        $output .= $solrField.":".$term." + "; 
                }//end loop
                
                $output = substr($output,0,(strlen($output)-2));
                
                //$output .= $solrField.":".OpenContext_FacetQuery::solrEscape($textSearch);
                
                return $output;
        }//end function





       








}//end class declaration

?>
