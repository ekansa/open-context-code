<?php

class OpenContext_FacetQuery {
   
	
	public static function blockProjectIDsForTesting($requestParams){
		
		$skipProjects = array("64013C33-4039-46C9-609A-A758CE51CA49" => "dinaa",
									 "81204AF8-127C-4686-E9B0-1202C3A47959" => "dinaa");
		$skipTerm = "";
		foreach($skipProjects as $projectID => $OKvalue){
			
			$actskip = " && NOT project_id:".$projectID;
			if(isset($requestParams["test"])){
				if($requestParams["test"] == $OKvalue){
					$actskip = "";
				}
			}
			
			$skipTerm .= $actskip; 
			
		}
		
		return $skipTerm;
	}

	public static function timeClean($dateString) {
	/*
	This function cleans dates to turn them into integers for searching.
	*/
		$dateClean = $dateString;
		if(!is_numeric($dateString)){
			$dateRead = strtotime($dateString);
			if($dateRead){
				$dateClean = date("o", $dateRead);
				$dateClean = $dateClean +0;
			}
			else{
				$dateClean = false;
			}
		}
	
		return $dateClean;
	}

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
		if(array_key_exists("rel", $requestParams)){
                       $rel = true; 
                }
                else{
                        $rel = false;
                }
                
                if($rel || $cat || $proj || ($slashCount > 1)){
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
				if(!is_numeric($act_cord)){
					$bBox_valid = false;
				}
				elseif((abs($act_cord)>180)){
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
                        $view = $bBox_array[0]."N, ".$bBox_array[1]."E to ".$bBox_array[2]."N, ".$bBox_array[3]."E";
                }
                else{
                        $view = "So Sorry! Invalid search coordinates.";
			if($boundingBox == null){
				$view = "No search coordinates selected.";
			}
                }
                
                $output =  array("valid"=>$bBox_valid, "vals"=>$bBox_array, "view"=>$view );
                return $output;
                
        }//end function

	public static function build_simple_parameters($requestParams, $docType){
                
		// start building the array of query parameters to send to solr
		$param_array = array();
	  
		$param_array["facet"] = "true";
		$param_array["facet.mincount"] = "1";
		$param_array["fq"] = null; // initialize the fq paramter; otherwise we could get "Undefined index: fq" errors
		$param_array["facet.field"] = null;


		// get the project parameter
		$proj = OpenContext_FacetQuery::test_param_key("proj", $requestParams);
		if ($proj) {
			$param_array["fq"] = OpenContext_FacetQuery::ORparser("project_name", $proj, false, true, true, true);
		}

		// get the category parameter. (note: "cat" maps to "item_class" in our solr schema)
		$cat = OpenContext_FacetQuery::test_param_key("cat", $requestParams);
		if ($cat) {
			if ($param_array["fq"]) {
				$param_array["fq"] .= OpenContext_FacetQuery::ORparser("item_class", $cat, true, true, true, false);
			} else {
				$param_array["fq"] = OpenContext_FacetQuery::ORparser("item_class", $cat, false, true, true, false);
			}
		}
        
		// get the dublin core creator parameter. 
		$creator = OpenContext_FacetQuery::test_param_key("creator", $requestParams);
		if ($creator) {
			if ($param_array["fq"]) {
				$param_array["fq"] .= OpenContext_FacetQuery::ORparser("creator", $creator, true, true, true, false);
			} else {
				$param_array["fq"] = OpenContext_FacetQuery::ORparser("creator", $creator, false, true, true, false);
			}
		}
		
		// get the projectID parameter. 
		$projID = OpenContext_FacetQuery::test_param_key("projID", $requestParams);
		if ( $projID) {
			if ($param_array["fq"]) {
				$param_array["fq"] .= OpenContext_FacetQuery::ORparser("project_id", $projID, true, true, true, false);
			} else {
				$param_array["fq"] = OpenContext_FacetQuery::ORparser("project_id", $projID, false, true, true, false);
			}
			 
		}			 
					 
		
		// get the dublin core contributor parameter. 
		$contrib = OpenContext_FacetQuery::test_param_key("contrib", $requestParams);
		if ($contrib) {
			if ($param_array["fq"]) {
				$param_array["fq"] .= OpenContext_FacetQuery::ORparser("contributor", $contrib, true, true, true, false);
			} else {
				$param_array["fq"] = OpenContext_FacetQuery::ORparser("contributor", $contrib, false, true, true, false);
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
         if ($param_array["fq"]) {
				$param_array["fq"] .= OpenContext_FacetQuery::ORparser("tag_creator_name", $tagger, true, true, true, false);
         } else {
            $param_array["fq"] = OpenContext_FacetQuery::ORparser("tag_creator_name", $tagger, false, true, true, false);
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
		
		
		// time-tiles
      $timetile = OpenContext_FacetQuery::test_param_key("trange", $requestParams);
      if ($timetile) {
			if(stristr($timetile, "recent")){
				$timetile = "*";
			}
			else{
				$timetile .= "*";
			}
         if ($param_array["fq"]) {
            $param_array["fq"] .= " && time_path:".$timetile;
         } else {
            $param_array["fq"] = "time_path:".$timetile;
         }
      }
		
		
        
      // person links
      $person = OpenContext_FacetQuery::test_param_key("person", $requestParams);
      if ($person) {
                    // handle non-ascii characters - encode enities as UTF8: &#199; becomes Ç
                    //echo $person;
		    /*
                    $person = OpenContext_UTF8::charset_encode_utf_8($person);
                    $person = OpenContext_FacetQuery::solrEscape($person);
                    $person_bad = substr_count($person, "O\\\\\'");
                    
                    if($person_bad>0){
                        $person = str_replace("O\\\\\'", "O*", $person);
                        //$bad_spot = strrpos($person, "O*");
                        //$person = substr($person, 0, ($bad_spot+2));
                    }
		   */
		    
		   $personQuery = OpenContext_FacetQuery::ORparser("person_link", $person, false, true, true, false);
			$person_bad = substr_count($personQuery, "O\\\\\'");
                    
                    if($person_bad>0){
                        $personQuery = str_replace("O\\\\\'", "O*", $personQuery);
                        //$bad_spot = strrpos($person, "O*");
                        //$person = substr($person, 0, ($bad_spot+2));
                    }
		    
                    //echo $person;
                    if ($param_array['fq']) {
                        $param_array['fq'] .= " && ".$personQuery;
                    } else {
                        $param_array['fq'] = $personQuery;
                    }
           
                }//end case with a person
        
                // get the time parameters
                $t_start = OpenContext_FacetQuery::test_param_key("t-start", $requestParams);
                $t_end = OpenContext_FacetQuery::test_param_key("t-end", $requestParams);
                
		$t_start = OpenContext_FacetQuery::timeClean($t_start);
		$t_end = OpenContext_FacetQuery::timeClean($t_end);
		 
		
                // if there's a start and an end time
		if((strlen($t_start)>0)||(strlen($t_end)>0)){
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
		}
		
      $boundingBox = OpenContext_FacetQuery::test_param_key("bBox", $requestParams);
		if(!$boundingBox){
			$boundingBox = OpenContext_FacetQuery::test_param_key("BBOX", $requestParams);
		}
		
		
                if($boundingBox){
                        /*bBox=-92.8,44.2,-88.9,46.0 is somewhere in North America
                         south-west corner: longitude,latitude X north-east corner: longitude, latitude
                       */
                        $bBox_check = array();
                        $bBox_check = OpenContext_FacetQuery::check_coodinates($boundingBox);                        
                        
                        if($bBox_check["valid"]){
                                $bBox_array = $bBox_check["vals"];
                                if ($param_array['fq']) {
                                       //$param_array['fq'] .= ' && geo_long:[' . $bBox_array[0] . ' TO ' . $bBox_array[2] . '] && geo_lat:['. $bBox_array[1] . ' TO ' . $bBox_array[3] . ']';
				       $param_array['fq'] .= ' && geo_lat:[' . $bBox_array[0] . ' TO ' . $bBox_array[2] . '] && geo_long:['. $bBox_array[1] . ' TO ' . $bBox_array[3] . ']';
                                } else {
                                       //$param_array['fq'] = 'geo_long:[' . $bBox_array[0] . ' TO ' . $bBox_array[2] . '] && geo_lat:['. $bBox_array[1] . ' TO ' . $bBox_array[3] . ']';
				       $param_array['fq'] = 'geo_lat:[' . $bBox_array[0] . ' TO ' . $bBox_array[2] . '] && geo_long:['. $bBox_array[1] . ' TO ' . $bBox_array[3] . ']';
                                }           
                        }//end case for valid query
                       
                }//end case for geo-spatial query
        
	
		
                //exclude project 0, open context
                if ($param_array["fq"]) {
                        $param_array["fq"] .= " && NOT project_id:0".OpenContext_FacetQuery::blockProjectIDsForTesting($requestParams);
                } else {
                        $param_array["fq"] = "NOT project_id:0".OpenContext_FacetQuery::blockProjectIDsForTesting($requestParams);
                }
		
        
                
		$dTypeParam = OpenContext_FacetQuery::test_param_key("doctype", $requestParams);
		if($dTypeParam){
			$dtypeQuery = OpenContext_FacetQuery::ORparser("item_type", $dTypeParam, false, true, true, false);
		}
		elseif(is_array($docType)){
			//search through a variety of document types
			$dtypeQuery = "";
			$firstLoop = true;
			
			$dtypes = new DocumentTypes;
			
			foreach($docType as $raw_dtype){
				
				$dtype = $dtypes->externalValueToSolr($raw_dtype);
				
				if($firstLoop){
					$actQuery = "(item_type:" . $dtype.")";
				}
				else{
					$actQuery = " || (item_type:" . $dtype.")";
				}
				$firstLoop = false;
				$dtypeQuery .= $actQuery;
			}
			$dtypeQuery = "( ".$dtypeQuery." )";
		}
		else{
			if(strlen($docType)>1){
				$dtypeQuery = "(item_type:" . $docType.")";
				
			}
		}
		
	
		
		//limit by document type
                if ($param_array["fq"]) {
                    $param_array["fq"] .= " && " . $dtypeQuery;
                } else {
                        $param_array["fq"] = $dtypeQuery;
                }
	
	

                $complexQuery = OpenContext_FacetQuery::test_param_key("cq", $requestParams);
                if($complexQuery){
                        
                        $complexQuery = OpenContext_FacetQuery::complex_query_param($complexQuery);
                        if ($param_array["fq"]) {
                                $param_array["fq"] .= " && ( ".$complexQuery." )";
                        } else {
                                $param_array["fq"] = "( ".$complexQuery." )";
                        }
                }

                return $param_array;
	}//end function



        /*This does the more involved and fancy operations to generate Solr query parameters
        from outide URL GET requests*/
    public static function build_complex_parameters($requestParams, $param_array, $context_depth){
				
		// array to store media facet query
		$param_array["facet.query"] = array("image_media_count:[1 TO *]", "other_binary_media_count:[1 TO *]", "diary_count:[1 TO *]");
			
		//"rq" means reconcile query. it's more precise than a "q" (full-text) search, because it tries to reconcile terms that were
		//explicitly linked. In the full-text search, one could get poor reconciliation results because the matching of terms would be less exact.
		//for instance, a full-text query for "ovis" would also find items with "ovis / capra" (with a different taxonomic meaning)
		$reconcile = OpenContext_FacetQuery::test_param_key("rq", $requestParams); 
		if($reconcile){
			$linkRelation = false;
			$linkedData = OpenContext_FacetQuery::test_param_key("rel", $requestParams);
			if($linkedData){
				if(!is_array($linkedData)){
					$linkedData = array($linkedData);
				}
				$linkRelation = $linkedData[count($linkedData)-1]; //get the last linked data term
			}
			if(!$linkRelation){
				$linkRelation = OpenContext_FacetQuery::test_param_key("relURI", $requestParams);
			}
			
			if($linkRelation != false){
				if(stristr($linkRelation, "||")){
					$linkURIs = explode("||", $linkRelation);
				}
				else{
					$linkURIs = array(0=>$linkRelation);
				}
				
				$linkedDataObj = new LinkedDataRef;
				$LinkedTaxa = false;
				$firstLT = true;
				foreach($linkURIs as $refURI){
					$linkedVars = $linkedDataObj->lookupVarNamesByRelURI($refURI); //get names of variables that relate to the URI for "predicate" relation
					if($linkedVars != false){
						if($firstLT){
							$LinkedTaxa = $linkedVars;
						}
						else{
							$LinkedTaxa .= "||".$linkedVars;
						}
						
					}
					$firstLT = false;
				}
				
				$reconTries = array(0=>$reconcile); //make an array of different capitalization variations
				if(!in_array(strtolower($reconcile), $reconTries)){
					$reconTries[] = strtolower($reconcile);
				}
				if(!in_array(strtoupper($reconcile), $reconTries)){
					$reconTries[] = strtoupper($reconcile);
				}
				if(!in_array(ucwords($reconcile), $reconTries)){
					$reconTries[] = ucwords($reconcile);
				}
				if(!in_array(ucfirst($reconcile), $reconTries)){
					$reconTries[] = ucfirst($reconcile);
				}
				$reconcileq = implode("||", $reconTries);
				
				if($LinkedTaxa != false){
					$requestParams["taxa"][] = $LinkedTaxa."::".$reconcileq; //now we have a new taxa request, with all the variables for the string to be reconciled
				}
				
			}
		}
			
			
			
	
			// strings to store filter queries for solr
			$var_fq_string = null;
			$var_val_fq_string = null;
		
			$taxa_fq_string = null; // string for solr queries of taxa
			$act_taxa_field = false; // show active facet field for the next taxon 
			$taxa_array = OpenContext_FacetQuery::test_param_key("taxa", $requestParams);
			if ($taxa_array) {
			
				if(!is_array($taxa_array)){
					$taxa_array = array($taxa_array);
				}
				
				//cleanup null or empty values for taxa, otherwise we get an error
				$cleanTaxa_array = array();
				foreach($taxa_array as $actTax){
					 if(strlen($actTax)>1 && $actTax != "::"){
								$cleanTaxa_array[] = $actTax;
					 }
				}
				unset($taxa_array);
				$taxa_array = $cleanTaxa_array;
				unset($cleanTaxa_array);
			
				foreach ($taxa_array as $taxonomy) {
				
					$taxonomy = OpenContext_FacetQuery::trimLastDelim($taxonomy); //trim trailing path delimiter if present
					if(substr_count($taxonomy, "::")>0){
						$actTaxonomy_array = explode("::", $taxonomy);
						$numTaxaLevels = count($actTaxonomy_array); //total number of levels in given taxonomy
					}
					else{
						$actTaxonomy_array = array($taxonomy);
						$numTaxaLevels = 1; //total number of levels in given taxonomy
					}
					
					$ActTaxonLevel = 0;
					$parent_query_fields = array();
					$parent_query_fields[] = "";
	
					foreach($actTaxonomy_array as $actTaxon){
						
						$queryValue = null;
						$cleanUseTaxon_array = array();
						if(substr_count($actTaxon, "||")>0){
							$useTaxon_array = explode("||", $actTaxon); // user wants an "or" query
							$numVals_at_level = count($useTaxon_array);
							
							foreach($useTaxon_array as $act_useTaxon){
								$cleanUseTaxon_array[] = $act_useTaxon; //don't escape, neet to make hash
								$act_useTaxon = OpenContext_FacetQuery::solrEscape($act_useTaxon);
								$queryValue .= " || (".$act_useTaxon.")"; // or search
							}
							unset($useTaxon_array);
							$queryValue = substr($queryValue, 3);
						}
						else{
							$cleanUseTaxon_array[] = $actTaxon; //don't escape, need to make hash
							$queryValue = OpenContext_FacetQuery::solrEscape($actTaxon);
							$numVals_at_level = 1;
						}
							
						if($ActTaxonLevel >= ($numTaxaLevels - 1)){
							$solrQueryFieldReady = true;
						}
						else{
							$solrQueryFieldReady = false;
							$new_parent_query_fields = array();
							foreach($parent_query_fields as $act_par_query_field){
								//echo "<br/>precious level parent ".$act_par_query_field;
								foreach($cleanUseTaxon_array as $act_clean_taxon){
									if($act_par_query_field != ""){
										$new_parent_query_fields[] = $act_par_query_field."::".$act_clean_taxon;
									}
									else{
										$new_parent_query_fields[] = $act_clean_taxon;
									}
									//echo "<br/>new field is ($ActTaxonLevel) ".$act_clean_taxon;
								}
							}
							unset($parent_query_fields);
							$parent_query_fields = $new_parent_query_fields;
						}
						
						if($solrQueryFieldReady){
							$act_taxa_field = array();
							$taxa_fq_string .= " && (";
							$parCount = 0;
							foreach($parent_query_fields as $parentTaxonomy){
								
								//echo $parentTaxonomy."<br/>";
								
								if($ActTaxonLevel == 0){
									$fieldHash = "top";
								}
								else{
									$fieldHash =sha1($parentTaxonomy);
									if($parentTaxonomy == "[[standard]]"){
										$fieldHash = "standard";
									}
								}
								
								
								if($parCount>0){
									//$taxa_fq_string .= " || ( ".$fieldHash."_taxon:".$queryValue." )";
									$taxa_fq_string .= " || (";
								}
								else{
									$taxa_fq_string .= " (";
								}
								
								$valCounter = 0;
								foreach($cleanUseTaxon_array as $act_clean_taxon){
									$numericDateTerm = OpenContext_FacetQuery::numericTaxon($fieldHash, $act_clean_taxon);
									
									$act_clean_taxon = OpenContext_FacetQuery::solrEscape($act_clean_taxon);
									if($valCounter>0){
										$taxa_fq_string .= " || ( ".$fieldHash."_taxon:".$act_clean_taxon.$numericDateTerm." )";
									}
									else{
										$taxa_fq_string .= "( ".$fieldHash."_taxon:".$act_clean_taxon.$numericDateTerm." )";	
									}
								$valCounter++;
								}//end loop through values
								
								$taxa_fq_string .= ") ";
								
								
								foreach($cleanUseTaxon_array as $act_clean_taxon){
									if($parentTaxonomy != ""){
										$taxa_field_for_facets = $parentTaxonomy."::".$act_clean_taxon;
									}
									else{
										$taxa_field_for_facets = $act_clean_taxon;
									}
									//echo $taxa_field_for_facets."<br/>";
									$act_taxa_field[] = (sha1($taxa_field_for_facets))."_taxon";
									/*
									if($taxa_field_for_facets != "[[standard]]"){
										//standard meaurments don't get a hash
										$act_taxa_field[] = (sha1($taxa_field_for_facets))."_taxon";
									}
									else{
										$act_taxa_field[] = "standard_taxon";
									}
									*/
									
								}
							$parCount++;
							}
							
							$taxa_fq_string .= ") ";
						}
						
					$ActTaxonLevel++;	
					}//end loop through the taxonomy terms
				
				
				}//end loop through multiple taxonomy        
											
			}//end case with taxonomies to query
				
		
			//add any taxonomy parameters to the facet query
			if ($taxa_fq_string && $param_array["fq"]) {
				$param_array["fq"] .= $taxa_fq_string;
			} elseif ($taxa_fq_string && !$param_array["fq"]) {
				$param_array["fq"] = substr($taxa_fq_string, 3); //  if we're not appending to an existing $param_array['fq'] ,remove the leading '&& '
			}
		
		
		
		
			// array to store tags. we'll use this to help build the query for solr
			$tag = array();
			
			// string to store the tag filter query (fq) for solr
			$tag_fq_string = null;
			
			// TAGS
			//$tag_array = $param_array['tag'];
			$tag_array = OpenContext_FacetQuery::test_param_key("tag", $requestParams);
			if (($tag_array) && (is_array($tag_array))) {
				foreach ($tag_array as $tag) {
					$tag_fq_string .= OpenContext_FacetQuery::ORparser("user_tag", $tag, true, true, true, false);
				}         
			}//end case of tags
				
		
		
			//Linked Data, search by URI of linking relationship (predicate)
			$linkRelation = OpenContext_FacetQuery::test_param_key("relURI", $requestParams);
			if($linkRelation){
				//treat as array of linked URIs (to use same code)
				$requestParams["rel"][]= $linkRelation;
			}
			
			//Linked Data search by target URI
			$linkTarget = OpenContext_FacetQuery::test_param_key("targURI", $requestParams);
			if($linkTarget){
				if ($param_array["fq"]) {
					$param_array["fq"] .= OpenContext_FacetQuery::ORparser("top_lent_taxon", $linkTarget, true, true, true, false);
				} else {
					$param_array["fq"] = OpenContext_FacetQuery::ORparser("top_lent_taxon", $linkTarget, false, true, true, false);
				}
			}
		
		//linkedData search by URI for relation, and target (if "::"), we'll skip or queries for now
		$act_relations_fields = false;
		$linkedData = OpenContext_FacetQuery::test_param_key("rel", $requestParams);
		if($linkedData){
			if(!is_array($linkedData)){
				$linkedData = array($linkedData);
			}
			$act_relations_fields = array();
			foreach($linkedData as $link){
				if(stristr($link, "::")){
					$act_relations_fields[] = sha1($link)."_lent_taxon";
					$relArray = explode("::", $link);
					$relNum = count($relArray);
					$relQueryVal = $relArray[$relNum -1]; //URI of target item
					unset($relArray[$relNum -1]);
					$relParents = implode("::", $relArray); //just get the parents
					$queryField = sha1($relParents)."_lent_taxon";
				}
				else{
					$act_relations_fields[] = sha1($link)."_lent_taxon";
					$relQueryVal = $link;
					$queryField = "top_lrel_taxon";
				}
			
				if ($param_array["fq"]) {
					$param_array["fq"] .= OpenContext_FacetQuery::ORparser($queryField, $relQueryVal, true, true, true, false);
				} else {
					$param_array["fq"] = OpenContext_FacetQuery::ORparser($queryField, $relQueryVal, false, true, true, false);
				}
			
			}//end loop through linking relations
			
		}
		
		
		
		//calculates facet statistical summaries for numeric and calendar fields
		$statsData = OpenContext_FacetQuery::test_param_key("stats", $requestParams);
		if($statsData){
			if(!is_array($statsData)){
				$statsData = array($statsData);
			}
			$param_array["stats"][] = "true";
			foreach($statsData as $statsField){
				$param_array["stats.field"][] = sha1($statsField)."_tax_dec";
				$param_array["stats.field"][] = sha1($statsField)."_tax_int";
				$param_array["stats.field"][] = sha1($statsField)."_tax_cal";
			}
		}
		
		
		
		//calculates facet ranges (histograms) for numeric and calendar fields
		$rangeData = OpenContext_FacetQuery::test_param_key("range", $requestParams);
		if($rangeData){
			if(!is_array($rangeData)){
				$rangeData = array($rangeData);
			}
			foreach($rangeData as $rangeFieldSettings){
				if(stristr($rangeFieldSettings, "::") && stristr($rangeFieldSettings, ",")){
					$rangeFieldParams = explode("::", $rangeFieldSettings);
					$rawSettings = $rangeFieldParams[count($rangeFieldParams)-1]; //the last item in the array
					$rangeField = str_replace("::".$rawSettings, "", $rangeFieldSettings); //the actual field name
					if(substr_count($rawSettings, ",") == 3){
						$settings = explode(",", $rawSettings);
						$fieldSuffix = "_tax_dec";
						if($settings[0] == "int"){
								$fieldSuffix = "_tax_int";
						}
						elseif($settings[0] == "cal"){
								$fieldSuffix = "_tax_cal";
						}
						$rangeField = sha1($rangeField).$fieldSuffix;
						if($fieldSuffix == "_tax_cal"){
							$settings[1] = date("Y-m-d\TH:i:s\Z", strtotime($settings[1]));
							$settings[2] = date("Y-m-d\TH:i:s\Z", strtotime($settings[2]));
						}
						$param_array["stats"][] = "true";
						$param_array["stats.field"][] = $rangeField;
						$param_array["facet.range"][] = $rangeField;
						$param_array["f.".$rangeField.".facet.range.start"] = $settings[1]; //1st number in the settings array as start
						$param_array["f.".$rangeField.".facet.range.end"] = $settings[2]; //2nd number in the settins array as end
						$param_array["f.".$rangeField.".facet.range.gap"] = $settings[3]; //3rd number in the settins array as gap
					}
				}
			}
		}
		
		
		$param_array["facet.field"] = array();
		
		//get facet values for taxon fields
		if($act_taxa_field != false){
			foreach($act_taxa_field as $taxa_field){
				$param_array["facet.field"][] = $taxa_field; // add facet field for active taxonomy
			}
		}
		
		
		//get facet values for relation fields
		if(is_array($act_relations_fields)){
			$addTopTaxon = false;
			if(count($param_array["facet.field"])<1){
				$addTopTaxon = true;
				//if you've got relations queried, make sure you display taxon search options, but do it AFTER the link relations
			}
			
			foreach($act_relations_fields as $rel_field){
				$param_array["facet.field"][] = $rel_field; // add facet field for active linking relation
			}
			
			if($addTopTaxon){
				$param_array["facet.field"][] = "top_taxon";
			}
		}
		
		
		$param_array["facet.field"][] = $context_depth;
		$param_array["facet.field"][] = "project_name";
		$param_array["facet.field"][] = "item_class";
		$param_array["facet.field"][] = "creator";
		//$param_array["facet.field"][] = "related_person";
		
		// append tags to filter query
		if ($tag_fq_string && $param_array["fq"]) {
			$param_array["fq"] .= $tag_fq_string;
		} elseif ($tag_fq_string && !$param_array["fq"]) {
			$param_array["fq"] = substr($tag_fq_string, 3); //  if we're not appending to an existing $param_array['fq'] ,remove the leading '&& '
		}
		
		//lots of request parameters, don't cache the results
		if(count($requestParams)>=4){
			$param_array["fq"] = "{!cache=false}".$param_array["fq"]; //no caching
		}
		
		return $param_array;
		
		}//end function



	public static function formatToUTC($passeddt) {
		// Get the default timezone
		if(!is_numeric ($passeddt)){
		  $passeddt = strtotime($passeddt);
		}

		$default_tz = date_default_timezone_get();
	
		// Set timezone to UTC
		date_default_timezone_set("UTC");
	
		// convert datetime into UTC
		$utc_format = date("Y-m-d\TG:i:s\Z", $passeddt);
	
		// Might not need to set back to the default but did just in case
		date_default_timezone_set($default_tz);

      return $utc_format;
	}




	//this function breaks apart a default context path looks for
	//OR || pipes
	//if it finds one, it generates multiple default context path values for OR queries 
	public static function defaultContextORparser($solrField, $rawDefaultPath, $andBefore = false){
		
		$queryString = "";
		$slashCount = 0;
		
		//this fixes a problem of a trailing "/" at the end of some requests for default contexts
		if(substr($rawDefaultPath, -1, 1) == "/"){
			$rawDefaultPath = substr($rawDefaultPath, 0, (strlen($rawDefaultPath)-1));
		}
	
		$rawDefaultPath = str_replace("//", "/", $rawDefaultPath);
		
		if ($rawDefaultPath) {
			
			//this fixes a problem of a trailing "/" at the end of some requests for default contexts
			if(substr($rawDefaultPath, -1, 1) == "/"){
				$rawDefaultPath = substr($rawDefaultPath, 0, (strlen($rawDefaultPath)-1));
			}
			
			$slashCount =  substr_count($rawDefaultPath, "/")+1; // note:  $slashCount is used later to determine whether or not to display properties
			
			//check to see if there is an OR query term
			if(substr_count($rawDefaultPath, "||")>0){
				
				if(substr_count($rawDefaultPath, "/")>0){
					$pathItems_array = explode("/",$rawDefaultPath);
				}
				else{
					$pathItems_array = array();
					$pathItems_array[] = $rawDefaultPath;
				}
			
				$orPathsArray = array();
				$firstPath = true;
				$workingPath = "";
				foreach($pathItems_array as $actRawContextItem){
					if(substr_count($actRawContextItem, "||")>0){
						$act_checked_item_array = explode("||", $actRawContextItem);
					}
					else{
						$act_checked_item_array = array();
						$act_checked_item_array[] =  $actRawContextItem;
					}
					
					$orPathsCount = count($orPathsArray);
					
					if($firstPath){
						foreach($act_checked_item_array AS $act_checked_item){
							//first time through loop, make new path items from scratch
							$orPathsArray[] = $act_checked_item;
						}
					}
					else{
						$newOrPathsArray = array();
						foreach($orPathsArray as $oldPath){
							foreach($act_checked_item_array AS $act_checked_item){
								$newOrPathsArray[] = $oldPath."/".$act_checked_item; 	
							}
						}
						unset($orPathsArray);
						$orPathsArray = $newOrPathsArray;
						unset($newOrPathsArray);
					}
					$firstPath = false;
					unset($act_checked_item_array);
				}
			
				//now finally make the query!
				$firstPath = true;
				$queryString = " (";
				foreach($orPathsArray as $actOrPath){
					$default_context_path = OpenContext_FacetQuery::solrEscape($actOrPath);
					$nslash_path = $default_context_path;
					$default_context_path = $default_context_path . "/";
					
					if($firstPath){
						$queryString .= " (".$solrField.":".$default_context_path."* )";
						$queryString .= " || (".$solrField.":".$nslash_path." )";
					}
					else{
						$queryString .= " || (".$solrField.":".$default_context_path."* )";
						$queryString .= " || (".$solrField.":".$nslash_path." )";
					}
				
					$firstPath = false;	
				}
				$queryString .= ") ";
			}
			else{
				
				// escape problematic characters
				//$default_context_path = OpenContext_FacetQuery::solrEscape($rawDefaultPath);
				// solr expects default_context_path to end with a slash, so add it.
				//$default_context_path = $default_context_path . "/";
				//$default_context_path = OpenContext_FacetQuery::clean_context_path($rawDefaultPath);
				$default_context_path = OpenContext_FacetQuery::context_solr_encoder($rawDefaultPath);
				$nslash_path = $default_context_path;
				$default_context_path .= "/";
				$queryString .= " (".$solrField.":".$default_context_path."* )";
				$queryString .= " || (".$solrField.":".$nslash_path." )";
			}
			
		}
		else{
			$queryString = "[* TO *]";
		}
		
		$context_field = "def_context_" . $slashCount;
		
		if($andBefore){
			$queryString = " && ".$queryString;
		}
                
		//echo $queryString;
		
		return array("query" => $queryString, "context_field" => $context_field);
		
	}//end function


	public static function context_solr_encoder($rawDefaultPath){
		if(substr_count($rawDefaultPath, "/")>0){
			$pathItems_array = explode("/",$rawDefaultPath);
		}
		else{
			$pathItems_array = array();
			$pathItems_array[] = $rawDefaultPath;
		}
		
		$starter = true;
		foreach($pathItems_array as $actPathItem){
			
			if(substr_count($actPathItem, "\ ")>0){
				$escapedItem = $actPathItem;
			}
			else{
				$escapedItem = OpenContext_FacetQuery::solrEscape($actPathItem);
			}
			
			if($starter){
				$default_context_path = $escapedItem ;
			}
			else{
				$default_context_path .= "/".$escapedItem ;
			}
			
			$starter = false;
		}
		
		return $default_context_path;
	}



	//this function parses values to generate 'OR' queries if needed
	public static function ORparser($solrField, $value, $andBefore = true, $SolrEscape = true, $UTF8_do = true, $UTF8_encodeFirst = false){
		
		//$value = html_entity_decode($value, ENT_QUOTES, 'UTF-8');
		
		if(substr_count($value, "||")>0){
			$useValue_array = explode("||", $value); // user wants an "or" query
			$queryValue = " (";
			$i = 0;
			
			foreach($useValue_array as $act_useValue){
				
				if($UTF8_encodeFirst){
					$act_useValue = utf8_encode($act_useValue);
				}
				if($UTF8_do){
					$act_useValue = OpenContext_UTF8::charset_encode_utf_8($act_useValue);
				}
				if($SolrEscape){
					$act_useValue = OpenContext_FacetQuery::solrEscape($act_useValue);
				}
				
				if($i>0){
					$queryValue .= " || (".$solrField.":".$act_useValue.")";
				}
				else{
					$queryValue .= " (".$solrField.":".$act_useValue.")";
				}
			$i++;				
			}//end loop
			
			$queryValue .= " ) ";
		}	
		else{
		//no OR pipes (||), just one query term to prepare
		
			$act_useValue = $value;
			if($UTF8_encodeFirst){
				$act_useValue = utf8_encode($act_useValue);
			}
			if($UTF8_do){
				$act_useValue = OpenContext_UTF8::charset_encode_utf_8($act_useValue);	
			}
			if($SolrEscape){			
				$act_useValue = OpenContext_FacetQuery::solrEscape($act_useValue);
			}
			
			if(stristr($act_useValue, "!=")){
				$act_useValue = str_replace("!=", "", $act_useValue);
				$queryValue = " NOT (".$solrField.":".$act_useValue.") ";
			}
			else{
				$queryValue = $solrField.":".$act_useValue." ";
			}
		}
		
		if($andBefore){
			//and && before the query
			$queryValue = " && ".$queryValue;
		}
		
		return $queryValue;
	}




	//this function checks for numerical and calendar and range queries, creates Solr query terms as needed
	
	//another check to make sure a date is really a date
		  public static function doubleCheckDate( $str ){ 
					 
					 if(is_numeric($str)){
								if($str < 1800){
										  return false; //no calendar date fields eariler than 1800.
								}
					 }
		  
					 if(substr_count($str, "-") < 2){
								//possible has one or less hyphens
								$checkDate = strtotime($str);
								$time = strtotime("-1 year", time());
								if($checkDate > $time){
										  //likely erroneous date of within the past year
										  return false;
								}
					 }
		  
					 $reg = "\d";
					 mb_ereg_search_init($str, $reg);
					 $r = mb_ereg_search();
					 if(!$r){
								return false; //no number, can't be a date
					 }
					 
					 $stamp = strtotime( $str ); 
					 if (!is_numeric($stamp)) {
						  return FALSE; 
					 }
					 $month = date( 'm', $stamp ); 
					 $day   = date( 'd', $stamp ); 
					 $year  = date( 'Y', $stamp ); 
					 if (checkdate($month, $day, $year)){ 
						  return TRUE;
					 }
					 return FALSE; 
		  }
	
	
	public static function numericTaxon($fieldHash, $val, $intSuffix = "_tax_int", $decSuffix = "_tax_dec"){
		
		if(is_numeric($val)){
		  if(strlen($val) == 4 && $val >= 1800){
					 //is likely a date value, expressed only as a year. so get the whole year
					 $topVal = $val + 1;
					 $Newval = ">=".$val.",<".$topVal ;
					 $val = $Newval;
		  }
		}
		
		if(strstr($val, ",")){
			$valArray = explode(",", $val);
		}
		else{
			$valArray = array($val);
		}
		
		$numeric_term = "";
		if(count($valArray)<=3){
			
			$intField = $fieldHash.$intSuffix;
		   $decField = $fieldHash.$decSuffix;
		   $calField = $fieldHash."_tax_cal";
			
			if($valArray[0] == "int" || $valArray[0] == "dec" || $valArray[0] == "cal"){
					 if($valArray[0] == "int"){
								$decField = false;
								$calField = false;
					 }
					 elseif($valArray[0] == "dec"){
								$intField = false;
								$calField = false;
					 }
					 elseif($valArray[0] == "cal"){
								$intField = false;
								$decField = false;
					 }
					 unset($valArray[0]);
			}
			
			
			
			$exps = array();
			foreach($valArray as $actVal){
				$cleanVal = str_replace(">", "", $actVal);
				$cleanVal = str_replace("<", "", $cleanVal);
				$cleanVal = str_replace("=", "", $cleanVal);
				
				$valOK = false;
				$cleanInteger = false;
					 $cleanDecimal = false;
				if(is_numeric("0".$cleanVal)){
					$cleanInteger = round($cleanVal, 0);
					$cleanDecimal = ("0".$cleanVal) + 0;
					$valOK = true;
				}
				
				$cal_test_string = str_replace("/", "-", $cleanVal);
				
				$cleanCalendar = false;
				if ((($timestamp = strtotime($cal_test_string)) === false) || (!OpenContext_FacetQuery::doubleCheckDate($cal_test_string))) {
					$cleanCalendar = false;
				}
				else{
					$valOK = true;
					$cleanCalendar = date("Y-m-d\TH:i:s\Z", strtotime($cal_test_string));
					$cleanCalendar = (string)$cleanCalendar;
					echo "bad date is: ".$cal_test_string." ($cleanCalendar) also: ".OpenContext_FacetQuery::doubleCheckDate($cal_test_string)." ";
				}
				
				
				if($valOK){
		  
					$actExpression = array("int" => $cleanInteger,
												  "dec" => $cleanDecimal,
												  "cal" => $cleanCalendar);
					
					//echo print_r($actExpression);
					
					$actExpression["comp"] = false;
					if(substr($actVal, 0, 1) == "<"){
						$actExpression["comp"] = "<";
					}
					elseif(substr($actVal, 0, 1) == ">"){
						$actExpression["comp"] = ">";
					}
					if($actExpression["comp"] != false){
						//we're doing a comparative expression
						if(substr($actVal, 1, 1) != "="){
							//we need to EXCLUDE the value!
							
							$excludes = array();
							$excludes[] = OpenContext_FacetQuery::addNumCalTerm($intField, $cleanInteger, "");
							$excludes[] = OpenContext_FacetQuery::addNumCalTerm($decField, $cleanDecimal, "");
							if($cleanCalendar != false && $calField != false){
								$excludes[] = $calField.":[".$cleanCalendar." TO ".$cleanCalendar."] ";
							}
					      $excludeAll = "";
							foreach($excludes as $exItem){
								if(strlen($exItem)>1){
										  if(strlen($excludeAll)>1){
												$exItem = " || ".$exItem;	 
										  }
										  $excludeAll .= $exItem;
								}
							}
							
							if(strlen($excludeAll) > 1){
								$actExpression["not"] = " && NOT(".$excludeAll.")";
							}
							else{
								$actExpression["not"] = "";
							}
							
						}
						else{
							//no exclusion (or =) is OK
							$actExpression["not"] = "";
						}
					}//end case with comparative opporation
					
					$exps[] = $actExpression;
				}//end case with a clean value
			}
			
			
			if(count($exps) == 2){
					 if($exps[0]["comp"] != false && $exps[1]["comp"] != false ){
								$rangeInt = OpenContext_FacetQuery::addNumCalRangeTerm($intField, $exps[0]["int"], $exps[1]["int"], "||");
								$rangeDec = OpenContext_FacetQuery::addNumCalRangeTerm($decField, $exps[0]["dec"], $exps[1]["dec"], "||");
								$rangeCal = OpenContext_FacetQuery::addNumCalRangeTerm($calField, $exps[0]["cal"], $exps[1]["cal"], "||");
								$numeric_term = $rangeInt.$rangeDec.$rangeCal.$exps[0]["not"].$exps[1]["not"]; //final expression of query
					 }
			}
			elseif(count($exps) == 1){
					 $rangeInt = "";
					 $rangeDec = "";
					 if($exps[0]["comp"] == ">"){
								$rangeInt = OpenContext_FacetQuery::addNumCalRangeTerm($intField, $exps[0]["int"], "*", "||");
								$rangeDec = OpenContext_FacetQuery::addNumCalRangeTerm($decField, $exps[0]["dec"], "*", "||");
								$rangeCal = OpenContext_FacetQuery::addNumCalRangeTerm($calField, $exps[0]["cal"], "*", "||");
								$numeric_term = $rangeInt.$rangeDec.$rangeCal.$exps[0]["not"]; //final expression of query
					 }
					 elseif($exps[0]["comp"] == "<"){
								$rangeInt = OpenContext_FacetQuery::addNumCalRangeTerm($intField, "*", $exps[0]["int"], "||");
								$rangeDec = OpenContext_FacetQuery::addNumCalRangeTerm($decField, "*", $exps[0]["dec"], "||");
								$rangeCal = OpenContext_FacetQuery::addNumCalRangeTerm($calField, "*", $exps[0]["cal"], "||");
								$numeric_term = $rangeInt.$rangeDec.$rangeCal.$exps[0]["not"]; //final expression of query
					 }
					 else{
								$termInt = OpenContext_FacetQuery::addNumCalTerm($intField, $exps[0]["int"], "||");
								$termDec = OpenContext_FacetQuery::addNumCalTerm($decField, $exps[0]["dec"], "||");
								$termCal = OpenContext_FacetQuery::addNumCalTerm($calField, $exps[0]["cal"], "||");
								$numeric_term = $rangeInt.$termDec.$termCal; //final expression of query
								//echo $numeric_term;
					 }
			}
		}
		return $numeric_term;
	}//end function
	
	
	
//makes a query term only if the query field is not false
 public static function addNumCalTerm($field, $actValA, $noneOrAnd){
		  $output = "";
		  if($field != false && $actValA != false){
					 $output = " ".$noneOrAnd." (".$field.":".$actValA.") ";
		  }
		  return $output;
 }

public static function addNumCalRangeTerm($field, $actValA, $actValB, $noneOrAnd){
		  $output = "";
		  if($field != false && $actValA != false && $actValB != false){
					 $output = " ".$noneOrAnd." (".$field.":[".$actValA." TO ".$actValB."]) ";
		  }
		  return $output;
 }


public static function trimLastDelim($string, $actDelim = "::"){
		  //this function removes a trailing end delimator, so that we don't end up making a request for an empty child in a path
		  $stringLen = strlen($string);
		  $delimLen = strlen($actDelim);
		  if(substr($string, ($stringLen - $delimLen)) == $actDelim){
					 $string = substr($string, 0, ($stringLen - $delimLen));
		  }
		  return $string;
}





        public static function OLDparseSearchTerms($searchString) {
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


			public static function parseSearchTerms($searchString) {
				preg_match_all('~(?|"([^"]+)"|(\S+))~', $searchString, $matches);
				$searchTerms = $matches[1];
				//echo print_r($searchTerms);
				//die;
				return $searchTerms;
        }//end function parse search terms

        public static function solr_fulltext_terms($textSearch, $solrField){
            $searchTerms = OpenContext_FacetQuery::parseSearchTerms($textSearch);    
            $output = false;
				foreach($searchTerms as $term){
					$escapeTerm = OpenContext_FacetQuery::solrEscape($term);
					if(strlen($escapeTerm)>3){
						$actTerm = "(".$solrField.":\"".$escapeTerm."\")";
						if(!$output){
							$output = $actTerm;
						}
						else{
							$output .= " AND ".$actTerm;
						}
					}
				}
				$output = "(".$output.")";
				return $output;
        }//end function



        //this function prepares the complex query parameter
        public static function complex_query_param($public_param_value){
                
                $public_param_value = OpenContext_FacetQuery::solrEscape($public_param_value);
                $param_mappings = array( "proj" => "project_name",
                                        "cat"=> "item_class",
                                        "person" => "person_link",
                                        "_prop"=> "_var_NOB_val");
                
                foreach($param_mappings as $pub_param => $solr_param){
                        $public_param_value = str_replace(($pub_param."\:"), ($solr_param.":"), $public_param_value);
                }
                
                $public_param_value = str_replace("\\\\\ ", "\ ", $public_param_value);
                $public_param_value = str_replace("\\\\(", "\\(", $public_param_value);
                $public_param_value = str_replace("\\\\)", "\\)", $public_param_value);
                
                $public_param_value = str_replace("\|\|", " || ", $public_param_value);
                $public_param_value = str_replace("\(", "(", $public_param_value);
                $public_param_value = str_replace("\)", ")", $public_param_value);
                //de-escape characters that need it
                //echo $public_param_value;
                
                return $public_param_value;
                
                
        }
       








}//end class declaration


?>
