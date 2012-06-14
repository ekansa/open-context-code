<?php

class OpenContext_ResultAtom {
	
	
	public static function get_xml_string($xmlobject, $xpath){
		
		$output = "";
		
		if($xmlobject->xpath($xpath)){
			foreach ($xmlobject->xpath($xpath) AS $act_string){
				    
				$act_string .= "";
				$output = $act_string;
			}
		}
		else{
			$output = false;
		}
		
		//$output = str_replace(".org.org", ".org", $output);
		return $output;
	}
	
	
	
	
	public static function atom_to_html($atomURI, $atom_string = false){
		
		$host = OpenContext_OCConfig::get_host_config();
		
		$first_control = '<img src="/images/atom_results/left_button_last_deact.gif" alt="first page" border="0" />'.chr(13);
		$prev_control = '<img src="/images/atom_results/left_button_deact.gif" alt="first page" border="0" />'.chr(13);
		$next_control = '<img src="/images/atom_results/right_button_deact.gif" alt="previous page" border="0" />'.chr(13);	
		$next_control = '<img src="/images/atom_results/right_button_deact.gif" alt="previous page" border="0" />'.chr(13);
		$last_control = '<img src="/images/atom_results/right_button_last_deact.gif" alt="previous page" border="0" />'.chr(13);
		
		$file_get_uri = str_replace("&amp;","&",$atomURI);
		
		if(!$atom_string){
			$atom_string = file_get_contents($file_get_uri);
		}
		
		$atomXML = simplexml_load_string($atom_string);
        
		if($atomXML){
	
			$atomXML->registerXPathNamespace("default", "http://www.w3.org/2005/Atom");
			$atomXML->registerXPathNamespace("opensearch", "http://a9.com/-/spec/opensearch/1.1/");
			
			$resultCount = $atomXML->xpath("/default:feed/opensearch:totalResults");
			
			$resultSubTitle = OpenContext_ResultAtom::get_xml_string($atomXML, "/default:feed/default:subtitle");
			$first_PageURI = OpenContext_ResultAtom::get_xml_string($atomXML, "/default:feed/default:link[@rel='first']/@href");
			$last_PageURI = OpenContext_ResultAtom::get_xml_string($atomXML, "/default:feed/default:link[@rel='last']/@href");
			$next_PageURI = OpenContext_ResultAtom::get_xml_string($atomXML, "/default:feed/default:link[@rel='next']/@href");
			$prev_PageURI = OpenContext_ResultAtom::get_xml_string($atomXML, "/default:feed/default:link[@rel='previous']/@href");
			
			$last_PageURI = htmlentities($last_PageURI);
			$next_PageURI = htmlentities($next_PageURI);
			$prev_PageURI = htmlentities($prev_PageURI);
			
			if((!$first_PageURI)||(!$prev_PageURI)||($first_PageURI == $atomURI)||((substr_count($atomURI,"page=")<1))){
				$first_control = '<img src="/images/atom_results/left_button_last_deact.gif" alt="first page" border="0" />'.chr(13);
			}//case with deactivated first link
			else{
				$first_PageURI = str_replace(".atom", "", $first_PageURI);
				$first_PageURI = str_replace("sets/?page=1&", "sets/?", $first_PageURI);
				$first_PageURI = str_replace("?page=1", "", $first_PageURI);
				$first_PageURI = htmlentities($first_PageURI);
				//$first_PageURI = urlencode($first_PageURI);
				$first_control = '<a href="'.$first_PageURI.'">';
				$first_control .= '<img src="/images/atom_results/left_button_last.gif" alt="first page" border="0" />';
				$first_control .= '</a>';
			}//case with active first link
			
			if((!$prev_PageURI)||($prev_PageURI == $atomURI)||((substr_count($atomURI,"page=")<1))){
				$prev_control = '<img src="/images/atom_results/left_button_deact.gif" alt="first page" border="0" />'.chr(13);
			}//case with deactivated previous link
			else{
				$prev_PageURI = str_replace(".atom", "", $prev_PageURI);
				//$prev_PageURI = urlencode($prev_PageURI);
				$prev_control = '<a href="'.$prev_PageURI.'">';
				$prev_control .= '<img src="/images/atom_results/left_button.gif" alt="first page" border="0" />';
				$prev_control .= '</a>';
			}//case with active previous link
			
			if((!$next_PageURI)||($next_PageURI == $atomURI)){
				$next_control = '<img src="/images/atom_results/right_button_deact.gif" alt="previous page" border="0" />'.chr(13);
			}//case with deactivated previous link
			else{
				$next_PageURI = str_replace(".atom", "", $next_PageURI);
				//$next_PageURI = urlencode($next_PageURI);
				$next_control = '<a href="'.$next_PageURI.'">';
				$next_control .= '<img src="/images/atom_results/right_button.gif" alt="next page" border="0" />';
				$next_control .= '</a>';
			}//case with active previous link
	
			if((!$last_PageURI)||($last_PageURI == $atomURI)||(!$next_PageURI)){
				$last_control = '<img src="/images/atom_results/right_button_last_deact.gif" alt="previous page" border="0" />'.chr(13);
			}//case with deactivated previous link
			else{
				$last_PageURI = str_replace(".atom", "", $last_PageURI);
				//$last_PageURI = urlencode($last_PageURI);
				$last_control = '<a href="'.$last_PageURI.'">';
				$last_control .= '<img src="/images/atom_results/right_button_last.gif" alt="last page" border="0" />';
				$last_control .= '</a>';
			}//case with active previous link		
			
			$result_head = "";
			$result_head = '<div id="result_head">'.chr(13);
			$result_head .= '<table width="100%">'.chr(13);
			$result_head .= '<tr>';
			//$result_head .= '<td align="center" class="bodyText"><strong>Results:</strong></td>'.chr(13);
			//$result_head .= '<td width="40%">'.$resultSubTitle.'</td>'.chr(13);
			$result_head .= '<td align="center">'.$first_control.'</td>'.chr(13);
			$result_head .= '<td align="center">'.$prev_control.'</td>'.chr(13);
			$result_head .= '<td align="center" class="bodyText"><strong>Results: </strong>'.$resultSubTitle;
			$result_head .= '</td>'.chr(13);
			$result_head .= '<td align="center">'.$next_control.'</td>'.chr(13);
			$result_head .= '<td align="center">'.$last_control.'</td>'.chr(13);
			$result_head .= '</tr>'.chr(13);
			$result_head .= '</table>'.chr(13);
			$result_head .= '</div>'.chr(13);
			
			
			//$item_list = "";
			
			
			$eee = 0;
			foreach ($atomXML->xpath("/default:feed/default:entry") as $all_entry) {
				
				$all_entry->registerXPathNamespace("default", "http://www.w3.org/2005/Atom");
				$entryURI_all[$eee] = $all_entry->id;
				$eee++;
			}//end loop through all entries
			
			if($eee<1){
				$result_head = ''.chr(13);
			}
			
			$all_content = $result_head;
			$all_content .= '<div id="item_list">'.chr(13);
			
			$item_list = '<table id="result_tab" width = "100%">'.chr(13);
			
			$iii = 0;
			if($eee>0){
				foreach ($atomXML->xpath("/default:feed/default:entry/default:content") as $entry) {
				
					$entryURI = $entryURI_all[$iii];
					$entry->registerXPathNamespace("default", "http://www.w3.org/2005/Atom");
					$entry->registerXPathNamespace("xhtml", "http://www.w3.org/1999/xhtml");
					
					foreach ($entry->xpath("./xhtml:div") AS $act_content){
						$act_content->registerXPathNamespace("xhtml", "http://www.w3.org/1999/xhtml");
						foreach($act_content->xpath(".//xhtml:div[@class='project_name']") AS $projName){
							$projNameString = $projName."";
							$projNameArray = explode(" ", $projNameString);
							$longString = false;
							foreach($projNameArray AS $projNamePart){
								if(strlen($projNamePart)>= 12){
									$longString = true;
								}
							}
							if($longString){
								$projName->addAttribute('style', 'font-size:85%;');
							}
						}
						
						$act_content_string = $act_content->asXML();
						
						if($iii&1){
							$div_class = "result_row_shade";
							$row_class = "res_row_shade";
						}
						else{
							$div_class = "result_row";
							$row_class = "res_row";
						}
						
						$div_class = "result_row";
						$div_fix = 'class="'.$div_class.'"';
						//$div_fix = "";
						$missing_thumbnail = '<div class="item_thumb"/>';
						
						$fixed_thumbnail = '<div class="item_thumb"><a href="'.$entryURI.'"><img src="/images/atom_results/no_media_pict.jpg" class="thumbimage" alt="Thumbmail image" /></a></div>';
						if(substr_count($act_content_string, $missing_thumbnail )>0){
							$act_content_string = str_replace($missing_thumbnail, $fixed_thumbnail, $act_content_string);
						}
						
						$act_content_string = str_replace('xmlns="http://www.w3.org/1999/xhtml"', $div_fix, $act_content_string);
						$act_content_string .= chr(13);
						
						//$act_content_string = "<a href='".$entryURI."' style='text-decoration: none;'>".$act_content_string."</a>";
						//$item_list .= $act_content_string;
						$item_list .= '<tr class="'.$row_class.'">'.chr(13).'<td>'.chr(13).$act_content_string.'</td>'.chr(13).'</tr>'.chr(13);
					}
					
					$iii++;
				}//end loop through entries
			}//end case with entries
			$item_list .= '</table>'.chr(13);
			
			if($iii<1){
				$item_list =  OpenContext_ResultAtom::noResultsOptions($host);
			}
			
			$feed_link = '<div class="container_a">'.chr(13);
			$feed_link .= '<div class="container_l"><p class="tinyText">Get results as an Atom Feed: <br/><a href="'.$host.'/about/services">(About web services)</a></p></div>'.chr(13);
			$feed_link .= '<div class="container_r"><a href="'.str_replace("&" , "&amp;" ,$atomURI).'"><img border="0" src="http://validator.w3.org/feed/images/valid-atom.png" alt="[Valid Atom 1.0]" title="Atom 1.0 feed"/></a></div>'.chr(13);
			$feed_link .= '</div>'.chr(13);
			$item_list .= $feed_link.'<br/><br/><br/>'.chr(13);
			$all_content .= $item_list;
			$all_content .= '</div>'.chr(13);
		
		}//end case with atom content
		else{
			$result_head = "";
			$result_head = '<div id="result_head">'.chr(13);
			$result_head .= '<table width="100%">'.chr(13);
			$result_head .= '<tr><td align="center" class="bodyText"><strong>Results:</strong></td>'.chr(13);
			//$result_head .= '<td width="40%">'.$resultSubTitle.'</td>'.chr(13);
			$result_head .= '<td align="center">'.$first_control.'</td>'.chr(13);
			$result_head .= '<td align="center">'.$prev_control.'</td>'.chr(13);
			$result_head .= '<td align="center" class="bodyText">Your search returned no results';
			$result_head .= '</td>'.chr(13);
			$result_head .= '<td align="center">'.$next_control.'</td>'.chr(13);
			$result_head .= '<td align="center">'.$last_control.'</td>'.chr(13);
			$result_head .= '</tr>'.chr(13);
			$result_head .= '</table>'.chr(13);
			$result_head .= '</div>'.chr(13);
			$all_content = $result_head;
			
			$noResults =  OpenContext_ResultAtom::noResultsOptions($host);
			
			$all_content .= '<div id="item_list">'.$noResults.'</div>'.chr(13);
		}
		return $all_content;
	}//end function atom_to_html


	public static function image_atom_to_html($atomURI){
		
		$host = OpenContext_OCConfig::get_host_config();
		
		$first_control = '<img src="/images/atom_results/left_button_last_deact.gif" alt="first page" border="0" />'.chr(13);
		$prev_control = '<img src="/images/atom_results/left_button_deact.gif" alt="first page" border="0" />'.chr(13);
		$next_control = '<img src="/images/atom_results/right_button_deact.gif" alt="previous page" border="0" />'.chr(13);	
		$next_control = '<img src="/images/atom_results/right_button_deact.gif" alt="previous page" border="0" />'.chr(13);
		$last_control = '<img src="/images/atom_results/right_button_last_deact.gif" alt="previous page" border="0" />'.chr(13);
		
		//$atomURI = str_replace("http://testing.opencontext.org", "", $atomURI);
		//ini_set ('allow_url_fopen', 1);
		//$atomURI = str_replace(".org.org", ".org", $atomURI );
		$file_get_uri = str_replace("&amp;", "&", $atomURI); //hack to make sure the escaped ampersands don't screw up retieval.
		
		$atom_string = file_get_contents($file_get_uri);
		//ob_start();
		//$atom_string = ob_get_contents($atomURI);
		//ob_end_clean();
		
		$image_count = 1;
                $per_row = 3;
		
		$atomXML = simplexml_load_string($atom_string);
        
		if($atomXML){
			
			$atomXML->registerXPathNamespace("default", "http://www.w3.org/2005/Atom");
			$atomXML->registerXPathNamespace("opensearch", "http://a9.com/-/spec/opensearch/1.1/");
			
			$resultCount = $atomXML->xpath("/default:feed/opensearch:totalResults");
			
			$resultSubTitle = OpenContext_ResultAtom::get_xml_string($atomXML, "/default:feed/default:subtitle");
			$first_PageURI = OpenContext_ResultAtom::get_xml_string($atomXML, "/default:feed/default:link[@rel='first']/@href");
			$last_PageURI = OpenContext_ResultAtom::get_xml_string($atomXML, "/default:feed/default:link[@rel='last']/@href");
			$next_PageURI = OpenContext_ResultAtom::get_xml_string($atomXML, "/default:feed/default:link[@rel='next']/@href");
			$prev_PageURI = OpenContext_ResultAtom::get_xml_string($atomXML, "/default:feed/default:link[@rel='previous']/@href");
			
			$last_PageURI = htmlentities($last_PageURI);
			$next_PageURI = htmlentities($next_PageURI);
			$prev_PageURI = htmlentities($prev_PageURI);
			
			if((!$first_PageURI)||($first_PageURI == $atomURI)||((substr_count($atomURI,"page=")<1))){
				$first_control = '<img src="/images/atom_results/left_button_last_deact.gif" alt="first page" border="0" />'.chr(13);
			}//case with deactivated first link
			else{
				$first_PageURI = str_replace(".atom", "", $first_PageURI);
				$first_PageURI = str_replace("slides/?page=1&", "slides/?", $first_PageURI);
				$first_PageURI = str_replace("?page=1", "", $first_PageURI);
				$first_PageURI = htmlentities($first_PageURI);
				//$first_PageURI = urlencode($first_PageURI);
				$first_control = '<a href="'.$first_PageURI.'">';
				$first_control .= '<img src="/images/atom_results/left_button_last.gif" alt="first page" border="0" />';
				$first_control .= '</a>';
			}//case with active first link
			
			if((!$prev_PageURI)||($prev_PageURI == $atomURI)||((substr_count($atomURI,"page=")<1))){
				$prev_control = '<img src="/images/atom_results/left_button_deact.gif" alt="first page" border="0" />'.chr(13);
			}//case with deactivated previous link
			else{
				$prev_PageURI = str_replace(".atom", "", $prev_PageURI);
				//$prev_PageURI = urlencode($prev_PageURI);
				$prev_control = '<a href="'.$prev_PageURI.'">';
				$prev_control .= '<img src="/images/atom_results/left_button.gif" alt="first page" border="0" />';
				$prev_control .= '</a>';
			}//case with active previous link
			
			if((!$next_PageURI)||($next_PageURI == $atomURI)){
				$next_control = '<img src="/images/atom_results/right_button_deact.gif" alt="previous page" border="0" />'.chr(13);
			}//case with deactivated previous link
			else{
				$next_PageURI = str_replace(".atom", "", $next_PageURI);
				//$next_PageURI = urlencode($next_PageURI);
				$next_control = '<a href="'.$next_PageURI.'">';
				$next_control .= '<img src="/images/atom_results/right_button.gif" alt="next page" border="0" />';
				$next_control .= '</a>';
			}//case with active previous link
	
			if((!$last_PageURI)||($last_PageURI == $atomURI)){
				$last_control = '<img src="/images/atom_results/right_button_last_deact.gif" alt="previous page" border="0" />'.chr(13);
			}//case with deactivated previous link
			else{
				$last_PageURI = str_replace(".atom", "", $last_PageURI);
				//$last_PageURI = urlencode($last_PageURI);
				$last_control = '<a href="'.$last_PageURI.'">';
				$last_control .= '<img src="/images/atom_results/right_button_last.gif" alt="last page" border="0" />';
				$last_control .= '</a>';
			}//case with active previous link		
			
			$result_head = "";
			$result_head = '<div id="result_head">'.chr(13);
			$result_head .= '<table width="100%">'.chr(13);
			$result_head .= '<tr>';
			//$result_head .= '<td align="center" class="bodyText"><strong>Results:</strong></td>'.chr(13);
			//$result_head .= '<td width="40%">'.$resultSubTitle.'</td>'.chr(13);
			$result_head .= '<td align="center">'.$first_control.'</td>'.chr(13);
			$result_head .= '<td align="center">'.$prev_control.'</td>'.chr(13);
			//$result_head .= '<td align="center" class="bodyText"><strong>Results: </strong>'.$resultSubTitle;
			$result_head .= '<td align="center" class="bodyText"><strong>Results: </strong>'.$resultSubTitle."<p>xxxxxxx</p>";
			$result_head .= '</td>'.chr(13);
			$result_head .= '<td align="center">'.$next_control.'</td>'.chr(13);
			$result_head .= '<td align="center">'.$last_control.'</td>'.chr(13);
			$result_head .= '</tr>'.chr(13);
			$result_head .= '</table>'.chr(13);
			$result_head .= '</div>'.chr(13);
			
			$all_content = $result_head;
			$all_content .= '<div id="item_list">'.chr(13);
			
			$item_list = '<table id="result_tab" width = "100%">'.chr(13);
			//$item_list = "";
			
			
			$eee = 0;
			foreach ($atomXML->xpath("/default:feed/default:entry") as $all_entry) {
				
				$all_entry->registerXPathNamespace("default", "http://www.w3.org/2005/Atom");
				$entryURI_all[$eee] = $all_entry->id;
				$eee++;
			}//end loop through all entries
			
			$iii = 0;
			$image_count = 1;
			$output = "";
			$hiddenOutput = "";
			foreach ($atomXML->xpath("/default:feed/default:entry") as $entry) {
				
				$entryURI = $entryURI_all[$iii];
				$entry->registerXPathNamespace("default", "http://www.w3.org/2005/Atom");
				$entry->registerXPathNamespace("xhtml", "http://www.w3.org/1999/xhtml");
				$entry->registerXPathNamespace("arch", "http://ochre.lib.uchicago.edu/schema/Resource/Resource.xsd");
				
				/*
				foreach ($entry->xpath(".//xhtml:img/@src") AS $thumbnailURI){
					$thumbnailURI = $thumbnailURI."";
				}
				
				foreach ($entry->xpath(".//xhtml:a[@class='img_preview_link']/@href") AS $previewURI){
					$previewURI = $previewURI."";
				}
				*/
				foreach ($entry->xpath(".//default:link[@rel='thumbnail']/@href") AS $thumbnailURI){
					$thumbnailURI = $thumbnailURI."";
				}
				
				foreach ($entry->xpath(".//.//default:link[@rel='preview']/@href") AS $previewURI){
					$previewURI = $previewURI."";
				}
				
				
				
				$imageTitle = $entry->title;
				$imageTitle = str_replace(" media item:", ":", $imageTitle);
				$imageTitle = str_replace(" (Image),", ",", $imageTitle);
				$imageTitle = str_replace(" linked with:", "", $imageTitle);
				$imageTitle = str_replace("Excavations", "Ex", $imageTitle);
				
				$hiddenOutput .= "<a href='".$previewURI."' rel='colorbox_prev' title='".$imageTitle."'></a>";
				
				if($image_count == 1){
					$new_row = true;
					$firstPreviewURI = $previewURI;
					$firstTitle = $imageTitle;
					$hiddenOutput = "";
				}
				    
				if($new_row){
					$rowPrefix = "<tr>".chr(13);
					$new_row = false;
				}
				else{
					$rowPrefix = "";    
				}
				
				$output .= $rowPrefix."<td style='width: 33%; text-align: center;'>".chr(13);
				//$output .= "<span id='".$previewURI."' class='colorbox_prev'>";
				$output .= "<a href='".$entryURI."' title='".$imageTitle."'>";
				$output .= "<img src='".$thumbnailURI."' alt='".$imageTitle."'/>";
				$output .= "</a>".chr(13);
				//$output .= "</span>".chr(13);
				$output .= "</td>".chr(13);
				    
				if ((($image_count % $per_row == 0))&&($image_count != 1)) {
					//echo "Number is divisible";
					$new_row = true;
					$output .= "</tr>".chr(13);
				} else {
					//echo "Not fully divisible";
				} 
				    
				$image_count++;
				
			$iii++;
			}//end loop through entries
			
			$all_content = str_replace("<p>xxxxxxx</p>",("<br/><a href='".$firstPreviewURI."' rel='colorbox_prev' title='".$firstTitle."'>Slideshow view</a>"),$all_content);
			$item_list = $item_list.$output;
			$item_list .= '</table>'.chr(13);
			$feed_link = '<div class="container_a">'.chr(13);
			$feed_link .= '<div class="container_l"><p class="tinyText">Get results as an Atom Feed: <br/><a href="'.$host.'/about/services">(About web services)</a></p></div>'.chr(13);
			$feed_link .= '<div class="container_r"><a href="'.str_replace("&", "&amp;", $atomURI).'"><img border="0" src="http://validator.w3.org/feed/images/valid-atom.png" alt="[Valid Atom 1.0]" title="Atom 1.0 feed"/></a></div>'.chr(13);
			$feed_link .= '</div>'.chr(13);
			$item_list .= $feed_link.'<br/><br/><br/>'.chr(13);
			$all_content .= $item_list;
			$all_content .= '</div>'.chr(13);
			
			$all_content .= '<div style="color:white; font-size:0px;">'.chr(13).$hiddenOutput.chr(13)."</div>".chr(13);
			//$all_content = str_replace("<p>xxxxxxx</p>",("<p><a href='".$firstPreviewURI."' rel='colorbox_prev' title='".$firstTitle."'>Slideshow view</a></p>"),$all_content);
		}//end case with atom content
		else{
			$firstPreviewURI = "";
			$firstTitle = "";
			$all_content = "";
			$result_head = "";
			$result_head = '<div id="result_head">'.chr(13);
			$result_head .= '<table width="100%">'.chr(13);
			$result_head .= '<tr><td align="center" class="bodyText"><strong>Results:</strong></td>'.chr(13);
			//$result_head .= '<td width="40%">'.$resultSubTitle.'</td>'.chr(13);
			$result_head .= '<td align="center">'.$first_control.'</td>'.chr(13);
			$result_head .= '<td align="center">'.$prev_control.'</td>'.chr(13);
			$result_head .= '<td align="center" class="bodyText">Your search returned no results';
			$result_head .= '</td>'.chr(13);
			$result_head .= '<td align="center">'.$next_control.'</td>'.chr(13);
			$result_head .= '<td align="center">'.$last_control.'</td>'.chr(13);
			$result_head .= '</tr>'.chr(13);
			$result_head .= '</table>'.chr(13);
			$result_head .= '</div>'.chr(13);
			$all_content = $result_head;
			$all_content .= '<div id="item_list"><p class="bodyText">Try a <a href="'.$host.'/sets/">new search</a> of Open Context. The categories and terms (facets) shown on the left can help guide your search.</p></div>'.chr(13);
		}
	
		return array("pict1"=>$firstPreviewURI, "title1"=>$firstTitle, "items"=>$all_content);
	}//end function atom_to_html

	
	
	
	//this function gets contents between two elements, for fixing problems
	public static function get_element_contents($atom_string, $start_tag, $end_tag){
	
	      $start_pos = strpos($atom_string, $start_tag);
	      
	      if($start_pos){
		    $end_pos = strpos($atom_string, $end_tag, $start_pos);
		    $value_len = $end_pos - $start_pos;
		    $use_div = substr($atom_string, $start_pos, $value_len);
	      
		    return $use_div;
	      }
	      else{
		    return false;
	      }
	}//end function
	
	
	
	
	
	public static function atom_to_object($atomURI, $doAgain = true){
		
		$host = OpenContext_OCConfig::get_host_config();	
		$file_get_uri = str_replace("&amp;","&",$atomURI);
		
		$atom_string = file_get_contents($file_get_uri);
		
		@$atomXML = simplexml_load_string($atom_string);
        
		if($atomXML){
	
			$atomXML->registerXPathNamespace("default", "http://www.w3.org/2005/Atom");
			$atomXML->registerXPathNamespace("opensearch", "http://a9.com/-/spec/opensearch/1.1/");
			
			$resultCount = $atomXML->xpath("/default:feed/opensearch:totalResults");
			$resultCount = $resultCount[0]+0;
			
			$resultSubTitle = OpenContext_ResultAtom::get_xml_string($atomXML, "/default:feed/default:subtitle");
			$first_PageURI = OpenContext_ResultAtom::get_xml_string($atomXML, "/default:feed/default:link[@rel='first']/@href");
			$last_PageURI = OpenContext_ResultAtom::get_xml_string($atomXML, "/default:feed/default:link[@rel='last']/@href");
			$next_PageURI = OpenContext_ResultAtom::get_xml_string($atomXML, "/default:feed/default:link[@rel='next']/@href");
			$prev_PageURI = OpenContext_ResultAtom::get_xml_string($atomXML, "/default:feed/default:link[@rel='previous']/@href");
			
			/*
			$last_PageURI = htmlentities($last_PageURI);
			$next_PageURI = htmlentities($next_PageURI);
			$prev_PageURI = htmlentities($prev_PageURI);
			*/
			
			if((!$first_PageURI)||($first_PageURI == $atomURI)||((substr_count($atomURI,"page=")<1))){
				
			}//case with deactivated first link
			else{
				$first_PageURI = str_replace(".atom", "", $first_PageURI);
				$first_PageURI = str_replace("sets/?page=1&", "sets/?", $first_PageURI);
				$first_PageURI = str_replace("?page=1", "", $first_PageURI);
				//$first_PageURI = htmlentities($first_PageURI);
			}//case with active first link
			
			if((!$prev_PageURI)||($prev_PageURI == $atomURI)||((substr_count($atomURI,"page=")<1))){
				
			}//case with deactivated previous link
			else{
				$prev_PageURI = str_replace(".atom", "", $prev_PageURI);
			}//case with active previous link
			
			if((!$next_PageURI)||($next_PageURI == $atomURI)){
				
			}//case with deactivated previous link
			else{
				$next_PageURI = str_replace(".atom", "", $next_PageURI);
			}//case with active previous link
	
			if((!$last_PageURI)||($last_PageURI == $atomURI)){

			}//case with deactivated previous link
			else{
				$last_PageURI = str_replace(".atom", "", $last_PageURI);
			}//case with active previous link		
			
			
			$eee = 0;
			foreach ($atomXML->xpath("/default:feed/default:entry") as $all_entry) {
				
				$all_entry->registerXPathNamespace("default", "http://www.w3.org/2005/Atom");
				$entryID = $all_entry->id;
				$entryURI_all[$eee] = $entryID[0]."";
				$eee++;
			}//end loop through all entries
			
			$iii = 0;
			if($eee>0){
				$allResults = array();
				foreach ($atomXML->xpath("/default:feed/default:entry") as $AtomEntry) {
					$AtomEntry->registerXPathNamespace("default", "http://www.w3.org/2005/Atom");
					$AtomEntry->registerXPathNamespace("xhtml", "http://www.w3.org/1999/xhtml");
					$AtomEntry->registerXPathNamespace("georss", OpenContext_OCConfig::get_namespace("georss"));
					$AtomEntry->registerXPathNamespace("kml", OpenContext_OCConfig::get_namespace("kml"));
					
					$geoLat = false;
					$geoLon = false;
					foreach($AtomEntry->xpath("./georss:point") as $geoNode){
						$geoString = $geoNode."";
						
						if($geoString == "30 35"){
							$geoString = "30.3287 35.4421"; //petra rounding fix	
						}
						
						$geoArray = explode(" ", $geoString);
						$geoLat = $geoArray[0]+0;
						$geoLon = $geoArray[1]+0;
					}
					
					
					
					
					$kmlBegin = false;
					$kmlEnd = false;
					foreach($AtomEntry->xpath("./kml:TimeSpan/kml:begin") as $beginNode){
						$kmlBegin = ($beginNode."") + 0;
					}
					foreach($AtomEntry->xpath("./kml:TimeSpan/kml:end") as $endNode){
						$kmlEnd = ($endNode."") + 0;
					}
					
					$entryURI = $entryURI_all[$iii];
					if(!$entryURI){
						foreach($AtomEntry->xpath("./default:id") as $idNode){
							$entryURI = $idNode."";
						}
					}
					
					
					foreach ($AtomEntry->xpath("./default:content") as $entry) {
					
						
						$entry->registerXPathNamespace("default", "http://www.w3.org/2005/Atom");
						$entry->registerXPathNamespace("xhtml", "http://www.w3.org/1999/xhtml");
						
						foreach ($entry->xpath("./xhtml:div") AS $act_content){
							$act_content->registerXPathNamespace("xhtml", "http://www.w3.org/1999/xhtml");
							foreach($act_content->xpath(".//xhtml:div[@class='class_name']") as $act_class){
								$itemCat = $act_class."";
							}
							foreach($act_content->xpath(".//xhtml:div[@class='project_name']") as $act_proj){
								$itemProject = $act_proj."";
							}
							foreach($act_content->xpath(".//xhtml:div[@class='class_icon']/xhtml:img/@src") as $act_icon){
								$itemIcon = $act_icon."";
							}
							foreach($act_content->xpath(".//xhtml:div[@class='item_label']") as $act_label){
								$itemLabel = $act_label."";
							}
							foreach($act_content->xpath(".//xhtml:div[@class='item_label']") as $act_label){
								$itemLabel = $act_label."";
							}
							$itemThumb = false;
							if($act_content->xpath(".//xhtml:div[@class='item_thumb']//xhtml:img/@src")){
								foreach($act_content->xpath(".//xhtml:div[@class='item_thumb']//xhtml:img/@src") as $act_thumb){
									$itemThumb = $act_thumb."";
								}
							}
							if(!$itemThumb){
								$itemThumb = $itemIcon;
							}
							
						}
							
						
						
						
						$resultItem = array("uri"=>$entryURI,
								    "category"=>$itemCat,
								    "catIcon"=>$itemIcon,
								    "project"=>$itemProject,
								    "label"=>$itemLabel,
								    "thumbIcon"=>$itemThumb,
								    "geoTime" => array("geoLat" => $geoLat,
										       "geoLong" => $geoLon,
										       "timeBegin" => $kmlBegin,
										       "timeEnd" => $kmlEnd
										       ));
						
						
					
					}//end loop through entries
			
				$allResults[] = $resultItem;
				$iii++;
				}//Atom Entries
			}//end case with entries
			
		}//end case with atom content
		
		
		$resultObject = array("resultCount"=>$resultCount,
				      "firstURI"=>$first_PageURI,
				      "lastURI"=>$last_PageURI,
				      "prevURI"=>$prev_PageURI,
				      "nextURI"=>$next_PageURI,
				      "items"=>$allResults
				      );
		
		return $resultObject;
	}//end function atom_to_html
	
	
	public static function noResultsOptions($host = false){
		
		if(!$host){
			$host = OpenContext_OCConfig::get_host_config(); 
		}
		
		$jsonString = file_get_contents($host."/sets/.json");
		$jsonObj =  Zend_Json::decode($jsonString);
		
		$output = "<div style='padding:10px; min-height:700px;'>";
		$output .= "<p class='bodyText'>Sorry! The datasets published in Open Context contain no records that
		exactly match these search criteria. However, since Open Context has a total of <strong>".$jsonObj["numFound"]."</strong>
		records, you may find what you want by clicking a browse option below:</p>";
		
		//$output .= "<p class='bodyText'>These records are summarized below:</p><br/>";
		
		$output .= "<p class='bodyText'><strong>Items from these contexts:</strong></p><p class='bodyText'>";
		$i=0;
		foreach($jsonObj["facets"]["context"] as $actContext){
			
			$comma = ", ";
			if($i==0){
				$comma = "";
			}
			$output .= $comma."<a title='View items from this context' href='".$actContext["href"]."'>".$actContext["name"]."</a> (".$actContext["count"].")";
			$i++;
		}
		$output .= "</p><br/>";
		
		
		$output .= "<p class='bodyText'><strong>Items belonging to these general categories:</strong></p>";
		$output .= "<div style='margin-left:5px'>";
		$jj=0;
		foreach($jsonObj["facets"]["category"] as $actContext){
			$jj++;
		}
		
		$i=0;
		foreach($jsonObj["facets"]["category"] as $actContext){
			
			$comma = ", ";
			if($i==($jj-1)){
				$comma = "";
			}
			$comma = "";
			
			$actImage = OpenContext_ProjectAtomJson::class_icon_lookup($actContext["name"]);
			$output .= "<div style='float:left; width:40%; padding-top:4px;'><div style='float:left;'>";
			$output .= "<a title='View items of this type' href='".$actContext["href"]."'>";
			$output .= "<img src='".$actImage."' alt='".$actContext["name"]."'/></a>";
			$output .= "</div>";
			$output .= "<div style='padding:3px; float:left; margin-right:3px;'>";
			$output .= "<span class='bodyText'><a title='View items of this type' href='".$actContext["href"]."'>".$actContext["name"]."</a> (".$actContext["count"].")";
			$output .= $comma."</span>";
			$output .= "</div>";
			$output .= "</div>";
			$i++;
		}
		$output .= "</div>";
		$output .= "<div style='clear:both; width:90%'></div>";
		$output .= "<br/>";
		
		
		$output .= "<p class='bodyText'><strong>Items from these projects and collections:</strong></p><p class='bodyText'>";
		$i=0;
		foreach($jsonObj["facets"]["project"] as $actContext){
			
			$comma = ", ";
			if($i==0){
				$comma = "";
			}
			$output .= $comma."<a title='View items from this project' href='".$actContext["href"]."'>".$actContext["name"]."</a> (".$actContext["count"].")";
			$i++;
		}
		$output .= "</p><br/>";
		
		
		$output .= "</div>";
		
		return $output;
	}
	
	
	public static function atom_resultLinkAdding($atomString, $spatial = true){
		
		$entryItem = simplexml_load_string($atomString);
		$entryItem->registerXPathNamespace("atom", OpenContext_OCConfig::get_namespace("atom"));
		
		foreach ($entryItem->xpath("//atom:entry/atom:id") as $id_xml){
			$itemURI = $id_xml."";
		}
		
		
		if($spatial){
			$pubDate = OpenContext_ResultAtom::temp_atom_pubDate($itemURI);
		}
		else{
			$pubDate = OpenContext_ResultAtom::media_atom_pubDate($itemURI);
		}
		
		//$pubDate  = "December 10, 2010";
		
		foreach ($entryItem->xpath("//atom:entry") as $entryElement){
			
			if($pubDate != false){
				$atomPublishedXML = $entryElement->addChild("published", date("Y-m-d\TH:i:s\-07:00", strtotime($pubDate)));
			}
			
			$atomLinkXML = $entryElement->addChild("link", "");
			$atomLinkXML->addAttribute("href", $itemURI.".atom");
			$atomLinkXML->addAttribute("rel", "alternate");
			$atomLinkXML->addAttribute("type", "application/atom+xml");
			
			$xmlLinkXML = $entryElement->addChild("link", "");
			$xmlLinkXML->addAttribute("href", $itemURI.".xml");
			$xmlLinkXML->addAttribute("rel", "alternate");
			$xmlLinkXML->addAttribute("type", "application/xml");
			
			if(!$spatial){
				$xmlLinkXML = $entryElement->addChild("link", "");
				$xmlLinkXML->addAttribute("href", $itemURI);
				$xmlLinkXML->addAttribute("rel", "self");
				$xmlLinkXML->addAttribute("type", "application/xhtml+xml");
			}
			
		}
		$atomString = str_replace('<?xml version="1.0"?>', '', $entryItem->asXML());
		
		return $atomString;
	}
	
	public static function temp_atom_pubDate($itemURI, $itemUUID = false){
		
		if(!$itemUUID){
			$itemUUID = str_replace("http://opencontext.org/subjects/", "", $itemURI);
			$itemUUID = str_replace("http://opencontext/subjects/", "", $itemUUID);
		}
		
		$db_params = OpenContext_OCConfig::get_db_config();
                $db = new Zend_Db_Adapter_Pdo_Mysql($db_params);
                               
		$db->getConnection();
		$sql = "SELECT space.created, space.updated
		FROM space
		WHERE space.uuid = '".$itemUUID."'
		LIMIT 1; ";
		
		//echo $sql;
		
		$result = $db->fetchAll($sql, 2);
                $pubDate = false;
		     
                if($result){
			$createdDate = $result[0]["created"];
			$updatedDate = $result[0]["updated"];
			$pubDate = $createdDate;
			if(!$createdDate){
				$pubDate = $updatedDate;
			}
		}
		
		
		$db->closeConnection();
		return $pubDate;
	}
	
	
	public static function media_atom_pubDate($itemURI, $itemUUID = false){
		
		if(!$itemUUID){
			//$itemUUID = str_replace("http://opencontext.org/subjects/", "", $itemURI);
			//$itemUUID = str_replace("http://opencontext/subjects/", "", $itemUUID);
			$itemUUID = str_replace("http://opencontext.org/media/", "", $itemUUID);
			$itemUUID = str_replace("http://opencontext/media/", "", $itemUUID);
		}
		
		$db_params = OpenContext_OCConfig::get_db_config();
                $db = new Zend_Db_Adapter_Pdo_Mysql($db_params);
                               
		$db->getConnection();
		$sql = "SELECT resource.updated
		FROM resource
		WHERE resource.uuid = '".$itemUUID."'
		LIMIT 1; ";
		
		//echo $sql;
		
		$result = $db->fetchAll($sql, 2);
                $pubDate = false;
		     
                if($result){
			$updatedDate = $result[0]["updated"];
			$pubDate = $updatedDate;
		}
		
		
		$db->closeConnection();
		return $pubDate;
	}
	
	
	
	
	
}//end class declaration

?>
