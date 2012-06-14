<?php

class OpenContext_ReadResultAtom {
	
	
	//this function gets URIs from a paged feed
	public static function FeedGetURIs($atomURI){
		
		$host = OpenContext_OCConfig::get_host_config();
		
		$atomURI = str_replace(" ", "+", $atomURI);
		
		$atom_string = file_get_contents($atomURI);
		
		$atomXML = simplexml_load_string($atom_string);
        
		if($atomXML){
	
			$atomXML->registerXPathNamespace("default", "http://www.w3.org/2005/Atom");
			$atomXML->registerXPathNamespace("opensearch", "http://a9.com/-/spec/opensearch/1.1/");
			
			$resultCount = $atomXML->xpath("/default:feed/opensearch:totalResults");
			$resultCount = $resultCount[0];
			$startIndex = $atomXML->xpath("/default:feed/opensearch:startIndex");
			$itemsPerPage = $atomXML->xpath("/default:feed/opensearch:itemsPerPage");
			
			$first_PageURI = OpenContext_ResultAtom::get_xml_string($atomXML, "/default:feed/default:link[@rel='first']/@href");
			$last_PageURI = OpenContext_ResultAtom::get_xml_string($atomXML, "/default:feed/default:link[@rel='last']/@href");
			$next_PageURI = OpenContext_ResultAtom::get_xml_string($atomXML, "/default:feed/default:link[@rel='next']/@href");
			$prev_PageURI = OpenContext_ResultAtom::get_xml_string($atomXML, "/default:feed/default:link[@rel='previous']/@href");
			
			$last_PageURI = htmlentities($last_PageURI);
			$next_PageURI = htmlentities($next_PageURI);
			$prev_PageURI = htmlentities($prev_PageURI);
			
			$CountFoundURIs = 0;
			$entryURIs = array();
			while($CountFoundURIs < $resultCount){
				
				foreach ($atomXML->xpath("/default:feed/default:entry") as $all_entry) {
				
					$all_entry->registerXPathNamespace("default", "http://www.w3.org/2005/Atom");
					$entryURI = $all_entry->id;
					$entryURIs[] = $entryURI."";
					$CountFoundURIs++;
				}//end loop through all entries
				
				if($next_PageURI != false){
					unset($atomXML);
					$atom_string = file_get_contents($next_PageURI);
					$atomXML = simplexml_load_string($atom_string);
					$atomXML->registerXPathNamespace("default", "http://www.w3.org/2005/Atom");
					$atomXML->registerXPathNamespace("opensearch", "http://a9.com/-/spec/opensearch/1.1/");
					$next_PageURI = OpenContext_ResultAtom::get_xml_string($atomXML, "/default:feed/default:link[@rel='next']/@href");
				}
				else{
					if($CountFoundURIs < $resultCount){
						$output = array("error"=>"Only: $CountFoundURIs of $resultCount URIs found", "results"=>$entryURIs, "found" => $CountFoundURIs);
						$CountFoundURIs = $resultCount;
					}
					else{
						$output = array("error"=>false, "results"=>$entryURIs, "found" => $CountFoundURIs);
					}
				}
				
				if($CountFoundURIs >= $resultCount){
					$output = array("error"=>false, "results"=>$entryURIs, "found" => $CountFoundURIs);
				}
				
			}//end loop
		}
		else{
			$output = array("error"=>"bad feed xml", "results"=>false);
		}
		return $output;
	}//end function

	
}//end class declaration

?>
