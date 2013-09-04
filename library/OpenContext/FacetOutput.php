<?php

class OpenContext_FacetOutput {
	
	/** creates a list of links so users can navigate back through the context hierarchy; i.e., Contained in: Turkey / Domuztepe / Survey
	*  It's used to help create 'remove filter' functionality. *
	*/
	
	public static function pipesOR($value){
		
		$value = str_replace("||", "<strong> OR </strong>", $value);
		return $value;
	}
	
	
	public static function gen_facet_removals($requestParams, $type = "xhtml"){
		
		//strip page parameter, strip callback
		if(isset($requestParams['page'])){
			unset($requestParams['page']);
		}
		if(isset($requestParams['callback'])){
			unset($requestParams['callback']);
		}
		if(isset($requestParams['comp'])){
			unset($requestParams['comp']);
		}
		
		$output = array();
		
		foreach ($requestParams as $key => $value) {
		    
			if ($key == 'controller' || $key == 'action') {  // we handle default context path separately
			    continue;
			}
			elseif($key == 'default_context_path' && $value){
				$remValue = OpenContext_FacetOutput::removals_path_parameter($requestParams, $key, $value, "/");
				$remLink = OpenContext_FacetOutput::removeParameter($requestParams, $key, false, false, $type);
				$facetRem = array("parameter" => $key,
						  "value" => $value,
						  "title" => "Contained in",
						  "remValue_XHTML" => $remValue["display"],
						  "remValues" => $remValue["remLinks"],
						  "simpleValues" => $remValue["simple"],
						  "remLink" => $remLink);
				$output[] = $facetRem;
			}
			elseif($key == 'q') {
			
				$remLink = OpenContext_FacetOutput::removeParameter($requestParams, $key, false, false, $type);
				$dis_value = stripslashes($value);
				$dis_value = "&#8220; ".$dis_value." &#8221;";
				$dis_value = str_replace('&#8220;"', "&#8220; '", $dis_value);
				$dis_value = str_replace('"', "'", $dis_value);
			
				$facetRem = array("parameter" => $key,
						  "value" => $value,
						  "title" => "Text search",
						  "remValue_XHTML" => $dis_value,
						  "remLink" => $remLink);
				$output[] = $facetRem;
			}
			elseif($key == 'rq') {
			
				$remLink = OpenContext_FacetOutput::removeParameter($requestParams, $key, false, false, $type);
				$dis_value = stripslashes($value);
				$dis_value = "&#8220; ".$dis_value." &#8221;";
				$dis_value = str_replace('&#8220;"', "&#8220; '", $dis_value);
				$dis_value = str_replace('"', "'", $dis_value);
			
				$facetRem = array("parameter" => $key,
						  "value" => $value,
						  "title" => "Term to reconcile",
						  "remValue_XHTML" => $dis_value,
						  "remLink" => $remLink);
				$output[] = $facetRem;
			}
			elseif($key == 'bBox' || $key == 'bbox' || $key == 'BBOX') {
				$remLink = OpenContext_FacetOutput::removeParameter($requestParams, $key, false, false, $type);
				$value_check = array();
				$value_check = OpenContext_FacetQuery::check_coodinates($value);
				$facetRem = array("parameter" => $key,
						  "value" => $value,
						  "title" => "Bound by the coordinates",
						  "remValue_XHTML" => $value_check["view"],
						  "remLink" => $remLink);
				$output[] = $facetRem;
			}
			elseif($key == 'cat') {
				$remLink = OpenContext_FacetOutput::removeParameter($requestParams, $key, false, false, $type);
				$remValue = OpenContext_FacetOutput::pipesOR(utf8_encode($value));
				$facetRem = array("parameter" => $key,
						  "value" => $value,
						  "title" => "Category",
						  "remValue_XHTML" => $remValue,
						  "remLink" => $remLink);
				$output[] = $facetRem;
			}
			elseif($key == 'projID') {
				$remLink = OpenContext_FacetOutput::removeParameter($requestParams, $key, false, false, $type);
				$remValue = OpenContext_FacetOutput::pipesOR(utf8_encode($value));
				$projObj = new Project;
				$remValue = $projObj->getNameByID($value);
				$facetRem = array("parameter" => $key,
						  "value" => $value,
						  "title" => "Project (by ID)",
						  "remValue_XHTML" => $remValue,
						  "remLink" => $remLink);
				$output[] = $facetRem;
			}
			elseif($key == 'geotile') {
				$remLink = OpenContext_FacetOutput::removeParameter($requestParams, $key, false, false, $type);
				$remValue = OpenContext_FacetOutput::pipesOR(utf8_encode($value));
				$zoomOutParams = $requestParams;
				if(strlen($requestParams[$key])>0){
					$zoomOutParams[$key] = substr($requestParams[$key], 0, (strlen($requestParams[$key])-1));
					$geoTileZoomOut = OpenContext_FacetOutput::generateFacetURL($zoomOutParams, false, false);
					$remValue .= " (<a href=\"".$geoTileZoomOut."\">Zoom out a tile level</a>)";
				}
				$facetRem = array("parameter" => $key,
						  "value" => $value,
						  "title" => "Geo-spatial Tile",
						  "remValue_XHTML" => $remValue,
						  "remLink" => $remLink);
				$output[] = $facetRem;
			}
			elseif($key == 'proj') {
				$remLink = OpenContext_FacetOutput::removeParameter($requestParams, $key, false, false, $type);
				$remValue = OpenContext_FacetOutput::pipesOR(OpenContext_UTF8::charset_encode_utf_8($value));
				$facetRem = array("parameter" => $key,
						  "value" => $value,
						  "title" => "Project",
						  "remValue_XHTML" => $remValue,
						  "remLink" => $remLink);
				$output[] = $facetRem;
			}
			elseif($key == 'tagger') {
				$remLink = OpenContext_FacetOutput::removeParameter($requestParams, $key, false, false, $type);
				$remValue = OpenContext_FacetOutput::pipesOR(OpenContext_UTF8::charset_encode_utf_8($value));
				$facetRem = array("parameter" => $key,
						  "value" => $value,
						  "title" => "Tagged by",
						  "remValue_XHTML" => $remValue,
						  "remLink" => $remLink);
				$output[] = $facetRem;
			}
			elseif($key == 'person') {
				$remLink = OpenContext_FacetOutput::removeParameter($requestParams, $key, false, false, $type);
				$remValue = OpenContext_FacetOutput::pipesOR(OpenContext_UTF8::charset_encode_utf_8($value));
				$remValue = str_replace("é", "&eacute;", $remValue);
				$facetRem = array("parameter" => $key,
						  "value" => $value,
						  "title" => "Related person",
						  "remValue_XHTML" => $remValue,
						  "remLink" => $remLink);
				$output[] = $facetRem;
			}
			elseif($key == 'creator') {
				$remLink = OpenContext_FacetOutput::removeParameter($requestParams, $key, false, false, $type);
				$remValue = OpenContext_FacetOutput::pipesOR(OpenContext_UTF8::charset_encode_utf_8($value));
				$remValue = str_replace("é", "&eacute;", $remValue);
				$facetRem = array("parameter" => $key,
						  "value" => $value,
						  "title" => "Director / Principle Person",
						  "remValue_XHTML" => $remValue,
						  "remLink" => $remLink);
				$output[] = $facetRem;
			}
			elseif($key == 'prop') {
				foreach ($value as $va_key => $va_value) {
					//echo "vakey: ".$va_key;
					if ($va_key && !$va_value) {
						$remLink = OpenContext_FacetOutput::removeParameter($requestParams, $key, $va_key, false, $type);
						//echo "remlink: ".$remLink;
						
						$remValue = utf8_encode($va_key);
						$facetRem = array("parameter" => $key,
							"varKey" => $va_key,
							"value" => $value,
							"title" => "Variable",
							"remValue_XHTML" => $remValue,
							"remLink" => $remLink);
					}
					elseif($va_key && $va_value != false){
						$remLink = OpenContext_FacetOutput::removeParameter($requestParams, $key, $va_key, $va_value, $type);
						
						$num_valA = str_replace("=", "", $va_value);
						$num_valA = str_replace(">", "", $num_valA);
						$num_valA = str_replace("<", "", $num_valA);
						$num_valA = str_replace(",", "", $num_valA);
						if(is_numeric($num_valA)){
							$va_value = str_replace(",", " to ", $va_value);
							$va_mess = "values ranging: ";
						}
						else{
							$va_mess = "value: ";
						}
							
						//$remValue = utf8_encode($va_key) . ' with '. $va_mess  .  OpenContext_FacetOutput::pipesOR(utf8_encode($va_value));
						$remValue = 'With '. $va_mess  .  OpenContext_FacetOutput::pipesOR(utf8_encode($va_value));
						
						$facetRem = array("parameter" => $key,
							"varKey" => $va_key,
							"value" => $va_value,
							"title" => "Variable '".utf8_encode($va_key)."'",
							"remValue_XHTML" => $remValue,
							"remLink" => $remLink);
					}
					$output[] = $facetRem;
				}//end loop through properties
			}
			elseif ($key == 'image') {
				$remLink = OpenContext_FacetOutput::removeParameter($requestParams, $key, false, false, $type);
				$remValue = "";
				$facetRem = array("parameter" => $key,
						  "value" => $value,
						  "title" => "Linked to images",
						  "remValue_XHTML" => $remValue,
						  "remLink" => $remLink);
				$output[] = $facetRem;
			}
			elseif ($key == 'diary') {
				$remLink = OpenContext_FacetOutput::removeParameter($requestParams, $key, false, false, $type);
				$remValue = "";
				$facetRem = array("parameter" => $key,
						  "value" => $value,
						  "title" => "Linked to documents",
						  "remValue_XHTML" => $remValue,
						  "remLink" => $remLink);
				$output[] = $facetRem;
			}
			elseif ($key == 'other') {
				$remLink = OpenContext_FacetOutput::removeParameter($requestParams, $key, false, false, $type);
				$remValue = "";
				$facetRem = array("parameter" => $key,
						  "value" => $value,
						  "title" => "Linked to other media",
						  "remValue_XHTML" => $remValue,
						  "remLink" => $remLink);
				$output[] = $facetRem;
			}
			elseif($key == "doctype"){
				$remLink = OpenContext_FacetOutput::removeParameter($requestParams, $key, false, false, $type);
				$dtypes = new DocumentTypes;
				if(stristr($value, "||")){
					$valArray = explode("||", $value);
					$remValue = "";
					$floop = true;
					foreach($valArray as $actVal){
						$dtypes->solrToOutside($actVal);
						if(!$floop){
							$remValue .= " OR ";
						}
						$remValue .= $dtypes->outsideTerm;
						$floop = false;
					}
				}
				else{
					$dtypes->solrToOutside($value);
					$remValue = $dtypes->outsideTerm;
				}
				
				$facetRem = array("parameter" => $key,
						  "value" => $value,
						  "title" => "Resource type",
						  "remValue_XHTML" => $remValue,
						  "remLink" => $remLink);
				$output[] = $facetRem;
				unset($dtypes);
			}
			elseif ($key == 'tag') {
				foreach ($value as $value) {
					$remLink = OpenContext_FacetOutput::removeParameter($requestParams, $key, false, $value, $type);
					$remValue = OpenContext_FacetOutput::pipesOR(utf8_encode($value));
					$facetRem = array("parameter" => $key,
							  "value" => $value,
							  "title" => "With the tag",
							  "remValue_XHTML" => $remValue,
							  "remLink" => $remLink);
					$output[] = $facetRem;
				}
			}
			elseif ($key == 'taxa') {
				foreach ($value as $value) {
						
						if(!stristr($value, "[[standard]]")){
								$remLink = OpenContext_FacetOutput::removeParameter($requestParams, $key, false, $value, $type);
								$remValue = OpenContext_FacetOutput::removals_path_parameter($requestParams, $key, $value, "::");
								$facetRem = array("parameter" => $key,
										  "value" => $value,
										  "title" => "Classified as",
										  "remValue_XHTML" => $remValue["display"],
										  "remValues" => $remValue["remLinks"],
										  "simpleValues" => $remValue["simple"],
										  "remLink" => $remLink);
								$output[] = $facetRem;
						}
						else{
								$remLink = OpenContext_FacetOutput::removeParameter($requestParams, $key, false, $value, $type);
								$remValue = OpenContext_FacetOutput::removals_path_parameter($requestParams, $key, $value, "::");
								$facetRem = array("parameter" => $key,
										  "value" => $value,
										  "title" => "With Measurement",
										  "remValue_XHTML" => $remValue["display"],
										  "remValues" => $remValue["remLinks"],
										  "simpleValues" => $remValue["simple"],
										  "remLink" => $remLink);
								$output[] = $facetRem;
						}
				}
			}
			elseif ($key == 'rel') {
				if(!is_array($value)){
					$value = array(0=>$value);
				}
				
				foreach ($value as $value) {
					$remLink = OpenContext_FacetOutput::removeParameter($requestParams, $key, false, $value, $type);
					$remValue = OpenContext_FacetOutput::removals_path_parameter($requestParams, $key, $value, "::");
					$facetRem = array("parameter" => $key,
							  "value" => $value,
							  "title" => "Linking Relation",
							  "remValue_XHTML" => $remValue["display"],
							  "remValues" => $remValue["remLinks"],
							  "simpleValues" => $remValue["simple"],
							  "remLink" => $remLink);
					$output[] = $facetRem;
				}
			}
			elseif ($key == 'targURI') {
				if(!is_array($value)){
					$value = array(0=>$value);
				}
				
				foreach ($value as $value) {
					$remLink = OpenContext_FacetOutput::removeParameter($requestParams, $key, false, $value, $type);
					$remValue = OpenContext_FacetOutput::removals_path_parameter($requestParams, $key, $value, "::");
					$facetRem = array("parameter" => $key,
							  "value" => $value,
							  "title" => "Linked Concept",
							  "remValue_XHTML" => $remValue["display"],
							  "remValues" => $remValue["remLinks"],
							  "simpleValues" => $remValue["simple"],
							  "remLink" => $remLink);
					$output[] = $facetRem;
				}
			}
			elseif($key == 'eol') {
				if(!is_array($value)){
					$value = array(0=>$value);
				}
				
				foreach ($value as $value) {
					$remLink = OpenContext_FacetOutput::removeParameter($requestParams, $key, false, $value, $type);
					if($value != "root"){
						$remValue = OpenContext_FacetOutput::removals_path_parameter($requestParams, $key, $value, "::");
						$remValue["display"] = $remValue["display"]."; includes more specific sub-taxa";
					}
					else{
						$remValue = array();
						$remValue["display"] = "All items classified with biological taxa";
						$remValue["remLinks"] = "";
						$remValue["simple"] = "All items classified with biological taxa";
					}
					$facetRem = array("parameter" => $key,
							  "value" => $value,
							  "title" => "Biological Taxa",
							  "remValue_XHTML" => $remValue["display"],
							  "remValues" => $remValue["remLinks"],
							  "simpleValues" => $remValue["simple"],
							  "remLink" => $remLink);
					$output[] = $facetRem;
				}
			}
			else{
				continue;
			}
		
			
		    
		}
		//end loop through search parameters
		
		if(isset($requestParams['t-start']) && isset($requestParams['t-end'])) {
			$date_clear_requestParams = $requestParams;
			unset($date_clear_requestParams['t-start']);
			unset($date_clear_requestParams['t-end']);
			$remLink = OpenContext_FacetOutput::generateFacetURL($date_clear_requestParams, null, null, false, false, $type);
			unset($date_clear_requestParams);
			
			$remValue = "No date range selected.";
			if((strlen($requestParams['t-start'])>0)||(strlen($requestParams['t-end'])>0)){
				$remValue = OpenContext_DateRange::bce_ce_note($requestParams['t-start']) . ' to ' . OpenContext_DateRange::bce_ce_note($requestParams['t-end']);
			}
			
			$facetRem = array("parameter" => "t-start,t-end",
					"value" => $requestParams['t-start'].",".$requestParams['t-end'],
					"title" => "Date Range",
					"remValue_XHTML" => $remValue,
					"remLink" => $remLink);
			$output[] = $facetRem;
		}
		
		
		return $output;
	}//end function
	
	

	public static function make_filters_html($requestParams, $host){
		$filterArray = OpenContext_FacetOutput::gen_facet_removals($requestParams, "xhtml");
		
		$output = "<em>No filters selected.</em>";
		
		if(count($filterArray)>0){
			//$output = "<div style='display:table; align:center;'>".chr(13);
			$dom = new DOMDocument("1.0", "utf-8");
			$dom->formatOutput = true;
			$root = $dom->createElement("div");
			$root->setAttribute("class", 'filter_list');
			$dom->appendChild($root);
			
			$output = "<div class='filter_list'>".chr(13);
			
			$i = 1;
			$doBorder = false;
			foreach($filterArray as $filter){
				
				if($doBorder){
					$doBorder = false;
					$rowClass = "filter_Brow";
					$nClass = "filter_BNcell";
				}
				else{
					$doBorder = true;
					$rowClass = "filter_row";
					$nClass = "filter_Ncell";
				}
				
				
				$output .= "<div class='".$rowClass."'>".chr(13);
				$elementA = $dom->createElement("div"); 
				$elementA->setAttribute("class", $rowClass);
				$root->appendChild($elementA);
				
				//filter title / type
				//$output .= "<div style='display:table-cell; width:30%; padding:4px;' class='bodyText'>".chr(13);
				$output .= "<div style='width:1%;'>".chr(13);
				$output .= "</div>".chr(13);
				
				$elementB = $dom->createElement("div"); 
				$elementB->setAttribute("style", "width:1%;");
				$elementBText = $dom->createTextNode("");
				$elementB->appendChild($elementBText);
				$elementA->appendChild($elementB);
				
				//filter number
				$output .= "<div class='".$nClass."'>".chr(13);
				$output .= "<span class='bodyText'>";
				$output .= "($i)";
				$output .= "</span>";
				$output .= "</div>".chr(13);
				
				$elementB = $dom->createElement("div"); 
				$elementB->setAttribute("class", $nClass);
				$elementA->appendChild($elementB);
				$elementC = $dom->createElement("span");
				$elementC->setAttribute("class", "bodyText");
				$elementCText = $dom->createTextNode("($i)");
				$elementC->appendChild($elementCText);
				$elementB->appendChild($elementC);
				
				//filter type
				$output .= "<div class='filter_Tcell'>".chr(13);
				$output .= "<span class='bodyText'>";
				$output .= $filter["title"].":";
				$output .= "</span>";
				$output .= "</div>".chr(13);
				
				$elementB = $dom->createElement("div"); 
				$elementB->setAttribute("class", 'filter_Tcell');
				$elementA->appendChild($elementB);
				$elementC = $dom->createElement("span");
				$elementC->setAttribute("class", "bodyText");
				$elementCText = $dom->createTextNode($filter["title"].":");
				$elementC->appendChild($elementCText);
				$elementB->appendChild($elementC);
				
				
				//fileter value
				//$output .= "<div style='display:table-cell; padding:4px;' class='bodyText'>".chr(13);
				$output .= "<div class='filter_Vcell'>".chr(13);
				$output .= "<span class='bodyText'>";
				$output .= $filter["remValue_XHTML"];
				$output .= "</span>";
				$output .= "</div>".chr(13);
				
				$elementB = $dom->createElement("div"); 
				$elementB->setAttribute("class", 'filter_Vcell');
				$elementA->appendChild($elementB);
				$elementC = $dom->createElement("span");
				$elementC->setAttribute("class", "bodyText");
				
				if(!stristr($filter["remValue_XHTML"], "href=")){
					$xhtml = $filter["remValue_XHTML"];
					$xhtml = str_replace("&#8220;", '“', $xhtml);
					$xhtml = str_replace("&#8221;", '”', $xhtml);
					$elementCText = $dom->createTextNode($xhtml);
					$elementC->appendChild($elementCText);
				}
				else{
					$xhtml = $filter["remValue_XHTML"];
					$xhtml = str_replace("&", "&amp;", $xhtml);
					$xhtml = str_replace("&amp;lt;", "&lt;", $xhtml);
					$xhtml = str_replace("&amp;gt;", "&gt;", $xhtml);
					
					
					
					$contentFragment = $dom->createDocumentFragment();
					$contentFragment->appendXML($xhtml);  // add note xml string
					$elementC->appendChild($contentFragment);
				}
				
				$elementB->appendChild($elementC);
				
				
				
				//fileter remove link
				//$output .= "<div style='display:table-cell; width:15%; padding:4px;' class='bodyText'>".chr(13);
				$output .= "<div class='filter_Rcell'>".chr(13);
				//$output .= "<span class='bodyText'>";
				$output .= "<a href='".$host.$filter["remLink"]."' title = 'Remove filter'>";
				$output .= "<img src='".$host."/images/fac_browse/remove_icon_grey.png' alt='Remove Link'/>";
				$output .= "</a>";
				//$output .= "</span>";
				$output .= "</div>".chr(13);
				
				$elementB = $dom->createElement("div"); 
				$elementB->setAttribute("class", 'filter_Rcell');
				$elementA->appendChild($elementB);
				$elementC = $dom->createElement("a");
				$elementC->setAttribute("href", $host.$filter["remLink"]);
				$elementC->setAttribute("title", "Remove filter");
				$elementD = $dom->createElement("img");
				$elementD->setAttribute("src", $host."/images/fac_browse/remove_icon_grey.png");
				$elementD->setAttribute("alt", "Remove Link");
				$elementC->appendChild($elementD);
				$elementB->appendChild($elementC);
				
				$output .= "</div>".chr(13);
			$i++;
			}
			$output .= "</div>".chr(13);
			
			$output = $dom->saveXML($root);
			
		}
		
		
		//yes it's a hack!
		$output = str_replace("&lt;strong&gt;", "<strong>", $output);
		$output = str_replace("&lt;/strong&gt;", "</strong>", $output);
		
		return $output;
	}


	public static function active_filter_object($requestParams, $host){
		$filterArray = OpenContext_FacetOutput::gen_facet_removals($requestParams, "xhtml");
		$output = array();
		if(count($filterArray)>0){
			foreach($filterArray as $filter){
				if($filter["parameter"] != 'default_context_path' && $filter["parameter"] != 'taxa'){
					$value = str_replace("<strong>", "", $filter["remValue_XHTML"]);
					$value = str_replace("</strong>", "", $value);
				}
				else{
					$value = $filter['simpleValues'];
				}
				$filterItem = array('filter' => $filter["title"],
						    'value' => $value,
						    'remLink'=> $host.$filter["remLink"]);
				$output[] = $filterItem;
			}
		}
		
		return $output;
	}

	public static function get_project_settings($projectArray, $settingsDOM, $host){
	//this function gets checks to see if a project has its own facet settings
	//$settingsDOM is the default setting
	
	
		$localSettingsDOM = false;
		
		if(count($projectArray)==1){
			//only 1 project, check for project specific
			
			foreach($projectArray as $key => $value){
				$projectName = $key;
			}
			
			$xpath = new DOMXpath($settingsDOM);
			$query = "//projectFiles/project[@solrName='".$projectName."']";
			//echo $query;
		
			$result = $xpath->query($query, $settingsDOM);
			$field_node = null;
			if($result != null){
				$proj_node = $result->item(0);
			}
			if($proj_node != null){
				
				$query = "./@filename";
				$result = $xpath->query($query, $proj_node);
				$localSettingsFileName = $result->item(0)->nodeValue;
				
				$localSettingsFile = $host."".$localSettingsFileName;
				
				//echo $localSettingsFile;
				$localSettingsString = file_get_contents($localSettingsFile);
				$localSettingsDOM = new DOMDocument("1.0", "utf-8");
				$localSettingsDOM->loadXML($localSettingsString);
			}
			
		}
	
		return $localSettingsDOM;
	}



	public static function get_facet_settings($facet_cat, $settingsDOM){
	//this function gets settings for facets, either default for all of open context
	//or for a specific project. the settingsDOM is an XML DOM object for either default general settings or project-specific settings
		
		$xpath = new DOMXpath($settingsDOM);
		
		if(preg_match('/^def_context_/', $facet_cat)){
			$facet_cat = "default_context";	
		}
		elseif(preg_match('/_var_NOB_val/', $facet_cat)){
			$facet_cat = "var_NOB_val";	
		}
		elseif(preg_match('/_taxon/', $facet_cat) && ($facet_cat != 'top_taxon')){
			$facet_cat = "down_taxon";	
		}
		else{
			
		}
		if(($facet_cat == 'time_start')||($facet_cat == 'time_end')){
			$facet_cat = "date";	
		}
		
		$query = "//field[@solrName='".$facet_cat."']";
		//echo "<br/><br/><br/>".$query; 
		$result = $xpath->query($query, $settingsDOM);
		$field_node = null;
                if($result != null){
			$field_node = $result->item(0);
		}
		if($field_node != null){
			$query = ".//settings/collapse";
			$result = $xpath->query($query, $field_node);
			$collapse = $result->item(0)->nodeValue;
			if($collapse == "true"){
				$collapse = true;	
			}
			else{
				$collapse = false;
			}
			
			$query = ".//settings/showNum";
			$result = $xpath->query($query, $field_node);
			$showNum = ($result->item(0)->nodeValue)+0;
			
			$query = ".//settings/itemSort/@type";
			$result = $xpath->query($query, $field_node);
			$sortType = $result->item(0)->nodeValue;
			$itemArray = false;
			if($sortType == "organize"){
				
				$itemArray = array();
				$query = ".//settings/itemSort/subField";
				$result = $xpath->query($query, $field_node);
				foreach($result as $subField){
					$query = "./@view";
					$resultB = $xpath->query($query, $subField);
					$subField_view = $resultB->item(0)->nodeValue;
					
					$query = "./@showNum";
					$resultB = $xpath->query($query, $subField);
					$subShowNumNode = null;
					if($resultB != false){
						$subShowNumNode = $resultB->item(0);
					}
					if($subShowNumNode != null){
						$subShowNum = ($subShowNumNode->nodeValue)+0;
					}
					else{
						$subShowNum = 100;
					}
					
					$query = "./@collapse";
					$resultB = $xpath->query($query, $subField);
					$subCollapse = $resultB->item(0)->nodeValue;
					
					if($subCollapse == "true"){
						$subCollapse = true;	
					}
					else{
						$subCollapse = false;
					}
					
					$query = "./@showType";
					$resultB = $xpath->query($query, $subField);
					$subShowType = $resultB->item(0)->nodeValue;
					$showList = false;
					if($subShowType == "list"){
						$showList = array();
						$query = "./item";
						$resultB = $xpath->query($query, $subField);
						foreach($resultB as $actItem){
							
							$query = "./@solrName";
							$resultC = $xpath->query($query, $actItem);
							$solrName = $resultC->item(0)->nodeValue;
							
							$query = "./@view";
							$resultC = $xpath->query($query, $actItem);
							$viewItem = $resultC->item(0)->nodeValue;
							
							$showList[$solrName] = array("view"=>$viewItem);
						}
					}
					
					$itemArray[] = array("view" => $subField_view,
							     "collapse" => $subCollapse,
							     "showType"=> $subShowType,
							     "showNum"=> $subShowNum,
							     "list"=>$showList);
				}
			}
			
			$output = array("facet_cat"=>$facet_cat,
					"collapse"=>$collapse,
					"showNum"=> $showNum,
					"sortType" => $sortType,
					"items" => $itemArray);
			
			//echo var_dump($output);
		}
		else{
			$output = false;
		}
		
		return $output;
	}






	public static function make_html_facetCategory($facet_category_label, $facetXHTML, $advanced = false){
		//this is general code to make HTML for a facet category
		$facet_cat_id = str_replace(" ", "_", $facet_category_label);
		$facet_cat_id = str_replace("(", "_", $facet_cat_id);
		$facet_cat_id = str_replace(")", "_", $facet_cat_id);
		$facet_cat_id = str_replace("/", "_", $facet_cat_id);
		$facet_cat_id = str_replace(":", "_", $facet_cat_id);
		
		$facet_category_xhtml = $facetXHTML->createElement("li");
		$facet_category_xhtml->setAttribute("style", "margin-top:5px;");
		$facet_cat_style = $facetXHTML->createElement("span");
		$facet_cat_style->setAttribute("class", "facet_type");
		$facet_category_xhtml_val = $facetXHTML->createTextNode($facet_category_label);
		$facet_cat_style->appendChild($facet_category_xhtml_val);
		$facet_category_xhtml->appendChild($facet_cat_style);
		
		if($advanced){
			$facet_cat_advanced_p = $facetXHTML->createElement("br");
			$facet_cat_advanced = $facetXHTML->createElement("a");
			$facet_cat_advanced->setAttribute("class", "tinyText");
			$facet_cat_advanced->setAttribute("id", "l_".$facet_cat_id);
			$facet_cat_advanced->setAttribute("href", "javascript:doAdvanced('".$facet_cat_id."')");
			$facet_cat_advanced_text = $facetXHTML->createTextNode("Select Multiple");
			$facet_cat_advanced->appendChild($facet_cat_advanced_text);
			
			$facet_category_xhtml->appendChild($facet_cat_advanced_p);
			$facet_category_xhtml->appendChild($facet_cat_advanced);
		}
		
		$facet_category_xhtml->setAttribute("title", "Facet type- ".$facet_category_label);
		$facet_val_node = $facetXHTML->createElement("ul");
		$facet_val_node->setAttribute("id", $facet_cat_id); 
		$facet_category_xhtml->appendChild($facet_val_node);
		
		
		return array("cat_xhtml" => $facet_category_xhtml, "val_node"=>$facet_val_node);
	}
	


	public static function make_html_facet($link, $value_string, $va_value, $facet_category_label, $facetXHTML, $linkID = false, $linkStyle = false){
	//this is general code to make an HTML element to be a facet	
		
		$fac_val_node = $facetXHTML->createElement("li");
		
		if($linkStyle != false){
			$fac_val_node->setAttribute("style", $linkStyle);
		}
		
		
		$fac_val_link = $facetXHTML->createElement("a");
		$fac_val_link->setAttribute("href", $link);
		$fac_val_link->setAttribute("class", "facet_value");
		
		if($linkID != false){
			$fac_val_link->setAttribute("id", $linkID);	
		}
			

		$facet_val_node_link_val = $facetXHTML->createTextNode($value_string." ");
		$fac_val_link->appendChild($facet_val_node_link_val);
		$fac_val_node->appendChild($fac_val_link);
		$fac_val_fcount_style = $facetXHTML->createElement("span");
		$fac_val_fcount_style->setAttribute("class", "facet_count");
		$fac_val_fcount_style_val = $facetXHTML->createTextNode($va_value);
		$fac_val_fcount_style->appendChild($fac_val_fcount_style_val);
		//$fac_val_link->appendChild($fac_val_fcount_style);
		$fac_val_node->appendChild($fac_val_fcount_style);
		$fac_val_node->setAttribute("title", $facet_category_label."- ".$value_string);
		
		return $fac_val_node;
	}



	public static function downTaxaCombine($facet_fields, $requestParams){
		
		$act_taxa_field = false; // show active facet field for the next taxon 
                $taxa_array = OpenContext_FacetQuery::test_param_key("taxa", $requestParams);
                if ($taxa_array) {
		
			if(!is_array($taxa_array)){
				$taxa_array = array($taxa_array);
			}
			
			$fieldCounter = 0;
                        foreach ($taxa_array as $taxonomy) {
				if(substr_count($taxonomy, "||")>0){
					$spitTaxonomy = OpenContext_FacetOutput::ORsplitPaths($taxonomy);
					$combinedFacetValues = array();
					$firstLoop = true;
					foreach($spitTaxonomy as $splitTaxon){
						$actFacetField = sha1($splitTaxon)."_taxon";
						
						if(isset($facet_fields[$actFacetField])){ //only if it finds the facet field
							$actFacetValues = $facet_fields[$actFacetField];
							foreach($actFacetValues as $key => $value){
								if(array_key_exists($key, $combinedFacetValues)){
									$combinedFacetValues[$key] += $value; //if same facet value, add the facet count
								}
								else{
									$combinedFacetValues[$key] = $value; //add the new facet value (as key), add facet count as value
								}
							}
						}
						if($firstLoop){
							$keepFacetField = $actFacetField;
						}
						else{
							unset($facet_fields[$actFacetField]);
						}
					
					$firstLoop = false;
					}
					
					arsort($combinedFacetValues); //sort from high to low facets
					
					$facet_fields[$keepFacetField] = $combinedFacetValues;
					unset($combinedFacetValues);
				}
				
			$fieldCounter ++;
			}//loop through all the taxa fields in the requestParameters
		
		}//end case with taxonomy
		
		return $facet_fields;
		
	}

	//hierarchy combine
	public static function hierarchyCombine($facet_fields, $requestParams){
		
		
		$hierarchyObj = new Facets_Hierarchy ;
		$hierarchyObj->requestParams = $requestParams;
		$facet_fields = $hierarchyObj->consolidateRawHierachicFacets("eol", $facet_fields);
		return $facet_fields;
	}

	public static function ORsplitPaths($taxonomy, $levelDeliminater = "::"){
		if(substr_count($taxonomy, $levelDeliminater)>0){
			$actTaxonomy_array = explode($levelDeliminater, $taxonomy);
			$numTaxaLevels = count($actTaxonomy_array); //total number of levels in given taxonomy
		}
		else{
			$actTaxonomy_array = array($taxonomy);
			$numTaxaLevels = 1; //total number of levels in given taxonomy
		}
		
		$orPathsArray = array();
		$firstPath = true;
		$workingPath = "";

		foreach($actTaxonomy_array as $actTaxon){
		
			$queryValue = null;
			$cleanUseTaxon_array = array();
			
			if(substr_count($actTaxon, "||")>0){
				$useTaxon_array = explode("||", $actTaxon); // user wants an "or" query
				$numVals_at_level = count($useTaxon_array);
						
				foreach($useTaxon_array as $act_useTaxon){
					$cleanUseTaxon_array[] = $act_useTaxon; //don't escape, neet to make hash
				}
				unset($useTaxon_array);
				
			}
			else{
				$cleanUseTaxon_array[] = $actTaxon; //don't escape, need to make hash
				$numVals_at_level = 1;
			}
			
			if($firstPath){
				foreach($cleanUseTaxon_array AS $act_checked_item){
					//first time through loop, make new path items from scratch
					$orPathsArray[] = $act_checked_item;
				}
			}
			else{
				$newOrPathsArray = array();
				foreach($orPathsArray as $oldPath){
					foreach($cleanUseTaxon_array AS $act_checked_item){
						$newOrPathsArray[] = $oldPath.$levelDeliminater.$act_checked_item; 	
					}
				}
				unset($orPathsArray);
				$orPathsArray = $newOrPathsArray;
				unset($newOrPathsArray);
			}
			
			$firstPath = false;
			unset($cleanUseTaxon_array);
		}//end loop
		
		return $orPathsArray;
		
	}//end function



	public static function facets_available($extendfacets, $facet_fields, $facet_queries, $requestParams, $request_array, $host, $advanced = false){
		
		if(isset($requestParams["page"])){
			unset($requestParams["page"]);
		}
		if(isset($requestParams['comp'])){
			unset($requestParams['comp']);
		}
		
		$localSettings = false; 
		$defaultSettingsFile = $host."/browse_settings/default_tree.xml";
		$defaultSettingsString = file_get_contents($defaultSettingsFile);
		
		$settingsDOM = new DOMDocument("1.0", "utf-8");
                $settingsDOM->loadXML($defaultSettingsString);
		if(isset($facet_fields['project_name'])){
			$localSettingsDOM = OpenContext_FacetOutput::get_project_settings($facet_fields['project_name'], $settingsDOM, $host);
		}
		else{
			$localSettingsDOM = false;
		}
		
		$facetXHTML = new DOMDocument("1.0", "utf-8");
		$facetXHTML->formatOutput = true;
		
		$root = $facetXHTML->createElement("ul");
		$facetXHTML->appendChild($root);
		$root->setAttribute("id", "navimenu");
		$root->setAttribute("class", "treeview");
		
		// prefix to handle whether to use a '?' or a '&' in the query string; used for dates and media 
		$qprefix = '?';
		if ($request_array[1]) {
		    $qprefix = $request_array[1] .'&';
		}


		//add a new facet category for date ranges
		$date_range_array = array();
		if(isset($facet_fields["time_span"])){
			$date_range_array = OpenContext_DateRange::timeSpanFacetOutput($facet_fields["time_span"]);
			
			$dateCountPrefix = null;
			if(count($facet_fields["time_span"])>=10){
				$dateCountPrefix = "ca. ";
			}
			unset($facet_fields["time_span"]);
		}
		
		
		// Facet category labels...Project, Category, etc.
		$facet_category_label = "";
		$output = "";
		
		// booleans that help us display the "Variable" and "Value" labels just once for nom, ord, and bool values
		$var_displayed = false;
		$var_val_displayed = false;
		
		
		//combine facets from different fields if these came from an "OR" search
		$facet_fields = OpenContext_FacetOutput::downTaxaCombine($facet_fields, $requestParams);
		$facet_fields = OpenContext_FacetOutput::hierarchyCombine($facet_fields, $requestParams);
		
		//do advanced (multiple select) search options?
		$checkBoxFacets = true;
		//$checkBoxFacets = false;
		if(array_key_exists("advanced", $requestParams)){
			$checkBoxFacets = true;
		}
		
		foreach ($facet_fields as $facet_cat => $value_array) {
		    
			$facet_category_label = "";
			
			$linkURLprefix = false;
			$linkURLsuffix = null;
			
			if (count($value_array)) { // make sure there are facets before displaying the label; TODO: verify this is the behavior we want
				
			
				$localSettings = false;
				if($localSettingsDOM != false){
					$localSettings = OpenContext_FacetOutput::get_facet_settings($facet_cat, $localSettingsDOM);
				}
				
				if(!$localSettings){
					$localSettings = OpenContext_FacetOutput::get_facet_settings($facet_cat, $settingsDOM);
				}
				
				$facetCategory = new FacetCategory;
				$facetCategory->facet_cat = $facet_cat;
				$facetCategory->setParameter();
				$facetCategory->prepareFacetURL($requestParams);
				$facet_category_label = $facetCategory->facet_category_label;
				
				if(stristr($facet_category_label, "description")){
					$lastTaxonNum = count($requestParams["taxa"])-1;
					$lastTaxon = str_replace("::", " :: ", $requestParams["taxa"][$lastTaxonNum]);
					$facet_category_label = "Fliter(s) for ".str_replace("||", " OR ", $lastTaxon);
					$facetCategory->facet_category_label = $facet_category_label;
				}
				
				
				
				
				
				$checkParameter = $facetCategory->checkParameter;
				
				if(($facet_cat == 'person_link') && !$extendfacets){
					$facet_category_label = "";
				}
				
				if(strlen($facet_category_label)>2){
					
					$checkOptions = $checkBoxFacets;
					//if((count($value_array)<2)){
					if((count($value_array)<2)||($facet_category_label == "Descriptive Variable")){
						$checkOptions = false; // don't allow "OR" search options if there is only 1 option
					}
					//$checkOptions = false;
					
					$xhtml = OpenContext_FacetOutput::make_html_facetCategory($facet_category_label, $facetXHTML, $checkOptions);
					$facet_category_xhtml = $xhtml["cat_xhtml"];
					$facet_val_node = $xhtml["val_node"];
					$root->appendChild($facet_category_xhtml);
				}
				
			    /* Loop through facet values */
			}// end case with facet values available

				
			$value_out = "";

			$numValues = count($value_array);			
			$doSettings = false;
			$doMoreVal = false;
			if(($localSettings != false) && (count($value_array)>1)){
				if($localSettings["items"] != false){
					$doSettings = true;
					$arragedValues = array();
					$arragedMax = array();
					foreach($localSettings["items"] as $actSubField){
						$subfieldName = $actSubField["view"];
						$arragedValues[$subfieldName] = null;
						$arragedMax[$subfieldName] = $actSubField["showNum"];
					}
				}
				
				if($numValues > $localSettings["showNum"]){
					$doMoreVal = $localSettings["showNum"];
				}
				else{
					$doMoreVal = false;
				}
				
			}
			
			
			//echo var_dump($arragedMax);
			//echo $checkParameter." check here<br/>";
			$itemCount = 0;
			foreach ($value_array as $va_key => $va_value) {
								
				$link = null;
				$Facet = new Facet;
				
				
				if(stristr($facetCategory->facet_cat, "taxon")){
					$Facet->skip_UTF8 = true;
				}
				elseif(stristr($facetCategory->facet_cat, "context")){
					$Facet->skip_UTF8 = true;
				}
				//$Facet->skip_UTF8 = true;
				
				$Facet->checkParmater = $checkParameter;
				
				//echo $Facet->checkParmater." then here<br/>";
				
				$Facet->normalFacet($va_key, $va_value, $host, $facetCategory->linkURLprefix, $facetCategory->linkURLsuffix);
				$link = $Facet->link;
				$linkQuery = $Facet->linkQuery;
				$value_out = $Facet->standard_link_html;
				$value_string = $Facet->value_string;
				
				if(($facet_cat == 'person_link') && !$extendfacets){
					$value_out = "";
				}
				
				if($facetCategory->checkParameter == "doctype"){
					$dtypes = new DocumentTypes;
					$dtypes->solrToOutside($value_string);
					$value_string = $dtypes->outsideTerm;
					$Facet->value_string = $value_string;
					unset($dtypes);
				}
				
				if(stristr($value_string, "http://")){
					//may be linked data reference
					$linkedData = new LinkedDataRef;
					if($linkedData->lookup_refURI($value_string)){
						$value_string = $linkedData->refLabel." (".$linkedData->refVocabulary.")";
						$Facet->value_string = $value_string;
					}
					unset($linkedData);
				}
				
				if($doSettings){
					
					$valueFound = false;
					$remainderType = false;
					foreach($localSettings["items"] as $actSubField){
						$subfieldName = $actSubField["view"];
						if($actSubField["list"] != false){
							if(array_key_exists($value_string, $actSubField["list"])){
								$arragedValues[$subfieldName][] = array("link" => $link,
											"text" => $value_string,
											"facet_count"=> $va_value,
											"param"=> $checkParameter,
											"lQuery"=> $linkQuery);
								$valueFound = true;
								//echo "<br/>Found: ".$value_string;
							}
							else{
								//echo "<br/>Not found: ".$value_string;
							}
						}
						else{
							$remainderType = $subfieldName;
						}
						
					}
					
					if(!$valueFound){
						$arragedValues[$remainderType][] = array("link" => $link,
											"text" => $value_string,
											"facet_count"=> $va_value,
											"param"=> $checkParameter,
											"lQuery"=> $linkQuery);
					}
					
				}
				elseif(strlen($value_out)>0){
					// this outputs facet items in cases where there are no special
					// settings to organize lists of facets
					
					//for some reason checkparameter get's lost, reset it
					$Facet->checkParameter = $checkParameter;
					
					if($doMoreVal != false){
						if($doMoreVal == $itemCount){
							$idList = str_replace(" ", "", $facet_category_label);
							$idList = 'cl_'.$idList;
							$fac_val_node = OpenContext_FacetOutput::make_html_facet("javascript:openList('".$idList."')", "More...", "", $facet_category_label, $facetXHTML, ($idList."control"), "list-style-type:none; margin-left:-20px;");
							$facet_val_node->appendChild($fac_val_node);
								
							$fac_sub_node_more_list = $facetXHTML->createElement("ul");
							$fac_sub_node_more_list->setAttribute("class", "moreList");
							$fac_sub_node_more_list->setAttribute("style", "display:none;");
							$fac_sub_node_more_list->setAttribute("id", $idList);
							$fac_val_node->appendChild($fac_sub_node_more_list);
						}
						
						if($itemCount >= $doMoreVal){
							$fac_val_node = $Facet->make_html_facet($facet_category_label, $facetXHTML, $checkBoxFacets, false, false, "list-style-type:circle;");
							$fac_sub_node_more_list->appendChild($fac_val_node);
						}
						else{
							$fac_val_node = $Facet->make_html_facet($facet_category_label, $facetXHTML, $checkBoxFacets);
							$facet_val_node->appendChild($fac_val_node);
						}
						
						
					}
					else{
						$fac_val_node = $Facet->make_html_facet($facet_category_label, $facetXHTML, $checkBoxFacets);
						$facet_val_node->appendChild($fac_val_node);
					}
					
				}
			
			$itemCount++;	
			}//end loop through facet values
			
			
			
			if($doSettings){
				//this outputs html for facets where there are special
				//settings determined to order lists of individual facet items
				foreach($arragedValues as $subHeading => $itemList){
					
					if(count($itemList)>0){
						$fac_sub_node = $facetXHTML->createElement("li");
						//$fac_sub_node->setAttribute("style", "list-style-type:none; margin-left:-20px; margin-top:3px;");
						$fac_sub_node->setAttribute("class", "facetSub");
						$facet_sub_node_val = $facetXHTML->createTextNode($subHeading);
						$fac_sub_node->appendChild($facet_sub_node_val);
						$fac_sub_node_sub_list = $facetXHTML->createElement("ul");
						//$fac_sub_node_sub_list->setAttribute("style", "margin-left:-21px;");
						$fac_sub_node_sub_list->setAttribute("class", "facetSubList");
						$fac_sub_node->appendChild($fac_sub_node_sub_list);
						
						$itemCount = 0;
						foreach($itemList as $item){
							$link = $item["link"];
							$value_string = $item["text"];
							$va_value = $item["facet_count"];
							$checkParameter = $item["param"];
							$linkQuery = $item["lQuery"];
							
							$Facet = new Facet;
							$Facet->checkParameter = $item["param"];
							//echo $Facet->checkParmater." here! <br/>";
							$Facet->linkQuery = $item["lQuery"];
							$Facet->link = $item["link"];
							$Facet->value_string = $item["text"];
							$Facet->va_value = $item["facet_count"];
							
							
							if($itemCount == ($arragedMax[$subHeading])){
								
								$idList = str_replace(" ", "", $facet_category_label.$subHeading);
								$fac_val_node = OpenContext_FacetOutput::make_html_facet("javascript:openList('".$idList."')", "More...", "", $facet_category_label, $facetXHTML, ($idList."control"), "list-style-type:none; margin-left:-20px;");
								$fac_sub_node_sub_list->appendChild($fac_val_node);
								
								$fac_sub_node_more_list = $facetXHTML->createElement("ul");
								$fac_sub_node_more_list->setAttribute("style", "margin-left:-21px; display:none");
								$fac_sub_node_more_list->setAttribute("id", $idList);
								$fac_val_node->appendChild($fac_sub_node_more_list);
							}
							
							if($itemCount >= ($arragedMax[$subHeading])){
								
								$fac_val_node = $Facet->make_html_facet($facet_category_label, $facetXHTML, $checkBoxFacets, false, "subListItem", "list-style-type:circle;");
								$fac_sub_node_more_list->appendChild($fac_val_node);
							}
							else{
								$fac_val_node = $Facet->make_html_facet($facet_category_label, $facetXHTML, $checkBoxFacets, false, "subListItem");
								$fac_sub_node_sub_list->appendChild($fac_val_node);
							}
						
						$itemCount++;
						}
						$facet_val_node->appendChild($fac_sub_node);
					}
				
				}
				
				
			}
			
			
			
			
			
			//$output .= $end_of_fac_cat;
		}//end loop through facets categories

		
		/* Since we may need to modify $qprefix as we prepare the date uris, save it as the separate variable, $date_qprefix. Then we can use $qprefix later when we process uris for media */
		$date_qprefix = $qprefix;
		$num_date_ranges = count($date_range_array);
		
		$iii=0;
		if ($num_date_ranges) {
			$facet_category_label = "Date Range";
			$xhtml = OpenContext_FacetOutput::make_html_facetCategory($facet_category_label, $facetXHTML);
			$facet_category_xhtml = $xhtml["cat_xhtml"];
			$facet_val_node = $xhtml["val_node"];
			$root->appendChild($facet_category_xhtml);
		
			while($iii < $num_date_ranges){
				$act_range = $date_range_array[$iii];
				
				if (strrchr($date_qprefix, 't-start')) {  // if there's already a 't-start' in the uri, 
					$date_qprefix = preg_replace('/(\?|&)t-start=(\-)?(\d+)/', '', $date_qprefix); // remove it. Otherwise, date parameters get appended to existing parameters
				}
				if (strrchr($date_qprefix, 't-end')) {
					$date_qprefix = preg_replace('/(\?|&)t-end=(\-)?(\d+)/', '', $date_qprefix);
				}
				// make sure the query string starts with a '?'
				if (!preg_match('/^\?/', $date_qprefix)) {
					$date_qprefix = preg_replace('/^&/', '?', $date_qprefix);
				}
				// if the date range is already selected, don't display it as a hyperlink    
				if (strstr($request_array[1], $act_range['uri_param'])) {
					$value_out = $act_range["display"] . ': ca. ' . $act_range["count"];
				} else {
				
					// otherwise, display it is a link
					$link = $host . $request_array[0] . $date_qprefix . $act_range["uri_param"];
					
					$value_out = '<a href="' . $link .'">'.$act_range["display"].'</a>: ca. '.$act_range["count"];
				}
				
				if(strlen($value_out)>0){
					$fac_val_node = OpenContext_FacetOutput::make_html_facet($link,$act_range["display"], $dateCountPrefix.$act_range["count"], $facet_category_label, $facetXHTML, false, false);
					$facet_val_node->appendChild($fac_val_node);
				}
				$iii++;
			
			}//end loop through date facet values
			
			//$output .= $end_of_fac_cat;
		}	
		// make sure there are values for media facets before displaying the 'Items with Media' label and looping through keys and values
		if ($facet_queries['image_media_count:[1 TO *]']||$facet_queries['other_binary_media_count:[1 TO *]']||$facet_queries['diary_count:[1 TO *]']) {
			$facet_category_label = "Items with Media";
			$xhtml = OpenContext_FacetOutput::make_html_facetCategory($facet_category_label, $facetXHTML);
			$facet_category_xhtml = $xhtml["cat_xhtml"];
			$facet_val_node = $xhtml["val_node"];
			$root->appendChild($facet_category_xhtml);
		    
			foreach ($facet_queries as $key => $value) {
				$value_out = "";
				
			    if ($value > 0) { // only display the facet if its count is at least 1.
				if ($key == 'image_media_count:[1 TO *]') {
				    if (!in_array('image', array_keys($requestParams))) { // only make this a link if the user hasn't selected yet.
					$link = $host . $request_array[0] . $qprefix .'image=true';
					$display_value = "Images";
					$value_out = '<a href="' . $link. '" >' . 'Images</a>: ' . $value;
				    } else {
					$display_value = "Images";
					$value_out = "Images: " . $value;
				    }
				} elseif ($key == 'diary_count:[1 TO *]') {
				    if (!in_array('diary', array_keys($requestParams))) {
					$link = $host . $request_array[0] . $qprefix . 'diary=true';
					$display_value = "Diary";
					$value_out = '<a href="' . $link.'" >' . 'Diary</a>: ' . $value;
				    } else {
					$display_value = "Diary";
					$value_out = "Diary Links: " . $value;
				    }
				} elseif ($key == 'other_binary_media_count:[1 TO *]') {
				    if (!in_array('other', array_keys($requestParams))) {
					$link = $host . $request_array[0] . $qprefix . 'other=true';
					$display_value = "Other";
					$value_out = '<a href="' . $link. '" >' . 'Other</a>: ' . $value;
				    } else {
					$display_value = "Other Media";
					$value_out = "Other Media: " . $value;
				    }
				}
			    }//end case with values
			    
				if(strlen($value_out)>0){
					$fac_val_node = OpenContext_FacetOutput::make_html_facet($link, $display_value, $value, $facet_category_label, $facetXHTML, false, false);
					$facet_val_node->appendChild($fac_val_node);
				}
			}
			
			//$output .= $end_of_fac_cat;
		}//end case with media facets to display
		
		$output = $facetXHTML->saveXML();
		$output = str_replace ('<?xml version="1.0" encoding="utf-8"?>','',$output);
		//$output = str_replace ('é','&eacute;',$output);
		return $output;
	
	}//end function



	public static function removals_path_parameter($requestParams, $removeParam, $removeValue, $delimiter = "::"){
		
		$host = OpenContext_OCConfig::get_host_config(); 
		$output = false;
		
		//$removeValue = mb_convert_encoding($removeValue, 'UTF-8', 'HTML-ENTITIES');
		
		if(array_key_exists($removeParam, $requestParams)){
			if(strstr($removeValue, $delimiter)){
				$taxExplode = explode($delimiter, $removeValue);
			}
			else{
				$taxExplode = array($removeValue);
			}
			
			if(is_array($requestParams[$removeParam])){
				$newRequestParams = $requestParams;	
				$i =0;
				foreach($newRequestParams[$removeParam] as $requestedTaxa){
					if($requestedTaxa == $removeValue){
						unset($requestParams[$removeParam][$i]);
					}
				$i++;
				}
				unset($newRequestParams);
			}
			else{
				unset($requestParams[$removeParam]);
			}
			
			
			$firstLoop = true;
			$display_output = "";
			$simple_out = "";
			$remLinkOutput = array();
			$remURIvalue = null;
			foreach($taxExplode as $actTax){
				
				if((@!is_array($requestParams[$removeParam]))||($remURIvalue == null)){
					//for default context path, where the request parameter is not an array
					$remURL = OpenContext_FacetOutput::generateFacetURL($requestParams, $removeParam, $remURIvalue, false, false);
					if(strstr($remURL, "United%2BStates")){
						$remURL = str_replace("United%2BStates", "United+States", $remURL);
					}
				}
				else{
					//for taxa, where the request parameter IS an array
					$remURL = OpenContext_FacetOutput::generateFacetURL($requestParams, $removeParam, $remURIvalue, true, false);
				}
				
				
				//echo "<br/>Rem URL: ".$remURL.chr(13);
				$linkSuffix = "";
				$displayLink = false;
				if(stristr($actTax, "http://")){
					//likely linked data, check
					if(stristr($actTax, "||")){
						$checkLinkArray = explode("||", $actTax);
					}
					else{
						$checkLinkArray = array(0=> $actTax);
					}
					$firstLinkLoop = true;
					foreach($checkLinkArray as $actLinkCheck){
						$linkedData = new LinkedDataRef;
						if($linkedData->lookup_refURI($actLinkCheck)){
							$displayLink = true;
							if($firstLinkLoop){
								$actLinkSee = $linkedData->refLabel;
								$linkSuffix = " (<em>from <a href='".$linkedData->refVocabURI."'>".$linkedData->refVocabulary."</a></em>)";
							}
							else{
								$actLinkSee .= " OR ".$linkedData->refLabel;
							}
						}
						unset($linkedData);
						$firstLinkLoop = false;
					}
				}
				
				
				if($firstLoop){
					$remURIvalue = $actTax;
					$remSeeValue = "";
				}
				else{
					$remURIvalue .= $delimiter.$actTax;
					$remSeeValue = " ".$delimiter." ";
				}
				
				$actTax = OpenContext_FacetOutput::pipesOR($actTax);
				if(!$displayLink){
					
					$num_valA = str_replace("=", "", $actTax);
					$num_valA = str_replace(">", "", $num_valA);
					$num_valA = str_replace("<", "", $num_valA);
					$num_valA = str_replace(",", "", $num_valA);
					if(substr_count($num_valA, ".")<=2){
						$num_valA = str_replace(".", "", $num_valA);
					}
					
					if(is_numeric($num_valA)){
						$actTax = str_replace(",", " to ", $actTax);
						$actTax = "[Value Range] ".$actTax;
					}
					
						if(stristr($actTax, "[[standard]]")){
								$actTax = str_replace("[[standard]]", "Standard Unit", $actTax);
						}
					
					$testTax = "<div>".$actTax."</div>";
					@$txml =simplexml_load_string($testTax);
					if(!$txml){
						$display_output .= $remSeeValue."<a href='".$host.$remURL."'>".htmlentities($actTax)."</a>";
					}
					else{
						$display_output .= $remSeeValue."<a href='".$host.$remURL."'>".$actTax."</a>";
					}
				}
				else{
					$testTax = "<div>".$actLinkSee."</div>";
					@$txml =simplexml_load_string($testTax);
					if(!$txml){
						$display_output .= $remSeeValue."<a href='".$host.$remURL."'>".htmlentities($actLinkSee)."</a>".$linkSuffix;
					}
					else{
						$actLinkSee = mb_convert_encoding($actLinkSee, 'UTF-8');
						$display_output .= $remSeeValue."<a href='".$host.$remURL."'>".htmlentities($actLinkSee)."</a>".$linkSuffix;
					}
				}
				$remLinkOutput[] = array("remURL" => $remURL, "remValue" => $remURIvalue);
				
			
			$firstLoop = false;	
			}
			$simple_out = str_replace("||", " OR ", $remURIvalue);
			//$display_output = mb_convert_encoding($display_output, 'UTF-8', 'HTML-ENTITIES');
			$output = array("display" => $display_output, "simple" => $simple_out, "remLinks" => $remLinkOutput);
		}
		
		
		return $output;
	}


	//this function removes a parameter or part of the value of a parameter. It is used to generate
	//links to remove facets
	public static function removeParameter($requestParams, $remParameter, $remKey = false, $remValue = false, $type = "xhtml"){
		
		$ready_to_make_link = false;
		
		//simple case, just remove the whole query parameter
		if(!$remKey && !$remValue){
			if(array_key_exists($remParameter, $requestParams)){
				unset($requestParams[$remParameter]);
				$ready_to_make_link = true;
			}
		}
		elseif($remParameter == "prop" && !$remValue){
			//this is a case for removing properties where a variable is selected and the whole variable / value pair
			// is removed
			
			if(array_key_exists($remParameter, $requestParams)){
				if(array_key_exists($remKey, $requestParams[$remParameter])){
					unset($requestParams[$remParameter][$remKey]);
					$ready_to_make_link = true;
				}
			}
		}
		elseif($remParameter == "prop" && $remValue != false){
			//this is a case for removing properties where a variable is selected and only the value is removed
			
			if(array_key_exists($remParameter, $requestParams)){
				if(array_key_exists($remKey, $requestParams[$remParameter])){
					$requestParams[$remParameter][$remKey] = null;
					$ready_to_make_link = true;
				}
			}
		}
		elseif(($remParameter == "taxa" || $remParameter == "tag" || $remParameter == "rel") && $remValue != false){
			//this is a case for removing parameters that have some value. It's good for tags 
			//and taxa (provided you want to remove 1 entire taxa, but not all taxa)
			
			if(array_key_exists($remParameter, $requestParams)){
				$newRequestParams = $requestParams;
				$i =0;
				if(is_array($newRequestParams[$remParameter])){ //remove if the parameter to be removed is an array
						foreach($newRequestParams[$remParameter] as $requestedParameter){
							if($requestedParameter == $remValue ){
								unset($requestParams[$remParameter][$i]);
							}
						$i++;
						}
				}
				else{
						unset($requestParams[$remParameter]); //remove if the parameter to be removed is not an array, just a single value
				}
				unset($newRequestParams);
				$ready_to_make_link = true;
			}
		}
		
		if($ready_to_make_link){
			return OpenContext_FacetOutput::generateFacetURL($requestParams, null, null, false, false, $type);
		}
		else{
			return false;
		}
	}




	//this function generates a URL for a GET query for new facets
	public static function generateFacetURL($requestParams, $actParameter, $queryValue, $addArrayParam = false, $extendLastArrayParam = false, $type = "xhtml"){
		
		/*
		requestParams is the request parameters passed by Zend
		actParameter is the active parameter for the new query value
		queryValue is the pre-URL encoded query value for the active parameter
		addArrayParam is true/false OR a variable. if true, create a new array parameter for the Active Parameter
			if a variable, then a property is being added
		extendLastArrayParam is false OR the delimiter to add to the parameter value
			for the last member of the active parameter array 
			for example, "::" is the delimiter for the taxa parameter
		*/
		
		//echo "<br/><br/><br/><br/><br/><br/><br/>";
		//echo $queryValue;
		
		if(array_key_exists("rel", $requestParams)){
			if(!is_array($requestParams["rel"])){
				$relVal = $requestParams["rel"];
				$requestParams["rel"] = array();
				$requestParams["rel"][] =$relVal;
			}
		}
		
		$baseController = "/".$requestParams["controller"]."/";
		
		unset($requestParams["controller"]);
		unset($requestParams["action"]);
		unset($requestParams["module"]);	
		$activeKey = false;
		
		//echo $actParameter.$extendLastArrayParam."<br/>";
		
		if($extendLastArrayParam != false){
			$addArrayParam = false;
		}
		
		
		//if the add to the default context path if it already exists
		if($actParameter == "default_context_path"){
			if(array_key_exists("default_context_path", $requestParams)){
				$requestParams["default_context_path"] = $requestParams["default_context_path"]."/".$queryValue;
			}
			elseif(strlen($queryValue)>1 && !strstr($queryValue, "/")){
				$requestParams["default_context_path"] = urlencode($queryValue);
			}
			elseif(strlen($queryValue)>1 && strstr($queryValue, "/")){
				$requestParams["default_context_path"] = $queryValue;
			}
		}//case where the active parameter is the default context path
		else{
			
			if($actParameter == "prop"){
				if(array_key_exists("prop", $requestParams)){
					$old_propertyArray = $requestParams["prop"];
					if(array_key_exists($addArrayParam, $old_propertyArray)){
						unset($requestParams["prop"][$addArrayParam]);
					}
					$requestParams["prop"][$addArrayParam] = $queryValue; // $addArrayParam as the property variable
				}
				else{
					$requestParams["prop"] = array($addArrayParam => $queryValue);
				}
				
				$addArrayParam = false;
			}
			elseif($addArrayParam){
				if(array_key_exists($actParameter, $requestParams)){
					$requestParams[$actParameter][] = $queryValue;
					$activeKey = count($requestParams[$actParameter]) - 1;
				}
				else{
					$requestParams[$actParameter] = array(0=> $queryValue);
					$activeKey = 0;
				}
			}
			elseif($extendLastArrayParam != false){
				//$extendLastArrayParam is a value delimiter
				//echo $actParameter.$extendLastArrayParam."<br/>";
				
				if(array_key_exists($actParameter, $requestParams)){
					$activeKey = count($requestParams[$actParameter]) - 1;
					$requestParams[$actParameter][$activeKey] = $requestParams[$actParameter][$activeKey] . $extendLastArrayParam .  $queryValue;
				}
				else{
					$requestParams[$actParameter] = array(0=> $queryValue);
					$activeKey = 0;
				}
			}
			elseif($actParameter != null){
				$requestParams[$actParameter] = $queryValue;
			}
			
		}//case where the active parameter is NOT the default context path
		
		
		
		//$newURL = "/sets/"; //default for xhtml
		$newURL = $baseController;
		
		if($type == "facets_atom"){
			$newURL = $baseController."facets/";
			$extension = '.atom';
		}
		elseif($type == "facets_json"){
			$newURL = $baseController."facets/";
			$extension = '.json';
		}
		elseif($type == "facets_kml"){
			$newURL = $baseController."facets/";
			$extension = '.kml';
		}
		elseif($type == "results_atom"){
			$extension = '.atom';
		}
		elseif($type == "results_json"){
			$extension = '.json';
		}
		elseif($type == "results_kml"){
			$extension = '.kml';
		}
		elseif($type == "facets_geojson"){
			$extension = '.geojson';
		}
		else{
			$extension = ""; //default for xhtml
		}
		
		if(array_key_exists("default_context_path", $requestParams)){
			//echo "url dcontext: ".$requestParams["default_context_path"].chr(13);
			if(strlen($requestParams["default_context_path"])>=1){
				$newURL .= OpenContext_FacetOutput::url_encode_noDelim($requestParams["default_context_path"], "/");
			}
			unset($requestParams["default_context_path"]);
		}
		
		
		$newURL .= $extension;
		$parameterArgument = '?';
		
		//echo print_r($requestParams);
		
		foreach($requestParams as $parameter => $value){
			if(is_array($value)){
				if(count($value)>0){
					foreach($value as $paramKey => $paramValue){
						if(is_numeric($paramKey)){
							$paramKey = "";
						}
						else{
							$paramKey = urlencode($paramKey);
						}
						if(($paramValue != null)||(strlen($paramValue)>0)){
							$newURL .= $parameterArgument.$parameter."%5B".$paramKey."%5D=".urlencode($paramValue);	
						}
						else{
							$newURL .= $parameterArgument.$parameter."%5B".$paramKey."%5D";
						}
						if(stristr($newURL, "?")){
							$parameterArgument = '&';
						}
					}
				}
			}
			else{
				if(($value != null)||(strlen($value)>0)){
					$newURL .= $parameterArgument.$parameter."=".urlencode($value);
				}
			}
			
			if(stristr($newURL, "?")){
				$parameterArgument = '&';
			}
		}
        
		return $newURL;
	}

	//encodes parts of the URL, but not the delimitor, used for slashes in contexts.
	public static function url_encode_noDelim($string, $delim){
		if(substr_count($string, $delim)>0){
			$string_array = explode($delim, $string);
			$firstLoop = true;
			foreach($string_array as $act_item){
				if($firstLoop){
					$url_encoded_string = urlencode($act_item);
					$firstLoop = false;
				}
				else{
					$url_encoded_string .= $delim.(urlencode($act_item));
				}
			}
		}
		else{
			$url_encoded_string = urlencode($string);
		}
		
		//echo "<br/>".$url_encoded_string.chr(13);
		return $url_encoded_string;
	}


	public static function active_taxon_value($requestParams, $doTaxon = true){
		if($doTaxon == true){
			$actParameter = "taxa";
		}
		else{
			$actParameter = "rel";
		}
		
		if(array_key_exists($actParameter, $requestParams)){
			$activeKey = count($requestParams[$actParameter]) - 1;
			$active_taxa_value = $requestParams[$actParameter][$activeKey];
		}
		else{
			$active_taxa_value = null;
		}
		return urlencode($active_taxa_value);
	}


}//end class declaration

?>
