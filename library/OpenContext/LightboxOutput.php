<?php

class OpenContext_LightboxOutput {

        const atom_ns_uri = "http://www.w3.org/2005/Atom"; // namespace uri for Atom
	const dc_ns_uri = "http://purl.org/dc/elements/1.1/"; // namespace uri for Dublin Core
        const geo_ns_uri = "http://www.georss.org/georss"; // namespace uri for GeoRSS
        const xhtml_ns_uri = "http://www.w3.org/1999/xhtml"; //namespace for xhtml
        const opensearch_ns_uri = "http://a9.com/-/spec/opensearch/1.1/"; //namespace for opensearch

	public static function atom_to_array($atom_string){
		
                $output = array();
                $media_dom = new DOMDocument("1.0", "utf-8");
                
                if(strlen($atom_string)>100){
                        $media_dom->loadXML($atom_string);
                            
                            
                        $xpath = new DOMXpath($media_dom);
                        
                            // Register OpenContext's namespace
                        $xpath->registerNamespace("dc", self::dc_ns_uri);
                        $xpath->registerNamespace("atom", self::atom_ns_uri);
                        $xpath->registerNamespace("xhtml", self::xhtml_ns_uri);
                        $xpath->registerNamespace("opensearch", self::opensearch_ns_uri);
                        
                        $query = "//opensearch:totalResults";
                        $result_total = $xpath->query($query, $media_dom);
                        if($result_total != null){
                                $totalFound = $result_total->item(0)->nodeValue;
                        }
                        
                        $query = "//opensearch:startIndex";
                        $result_startIndex = $xpath->query($query, $media_dom);
                        if($result_total != null){
                                $startIndex = $result_startIndex->item(0)->nodeValue;
                        }
                        
                        $query = "//opensearch:itemsPerPage";
                        $result_itemPage= $xpath->query($query, $media_dom);
                        if($result_total != null){
                                $itemsPerPage = $result_itemPage->item(0)->nodeValue;
                        }
                        
                        $links_array = array();
                        $links_array = OpenContext_LightboxOutput::atom_links_to_json('self', $links_array, $xpath, $media_dom);
                        $links_array = OpenContext_LightboxOutput::atom_links_to_json('first', $links_array, $xpath, $media_dom);
                        $links_array = OpenContext_LightboxOutput::atom_links_to_json('previous', $links_array, $xpath, $media_dom);
                        $links_array = OpenContext_LightboxOutput::atom_links_to_json('next', $links_array, $xpath, $media_dom);
                        $links_array = OpenContext_LightboxOutput::atom_links_to_json('last', $links_array, $xpath, $media_dom);
                        
                        $all_image_array = array();
                        $query = "//atom:entry";
                        $result_entries = $xpath->query($query, $media_dom);
                        if($result_entries != null){
                                foreach($result_entries as $act_entry){
                                        
                                        $resultNode = $act_entry;
                                        $newDom = new DOMDocument;
                                        $newDom->appendChild($newDom->importNode($resultNode,1));
                                                        
                                        $xpath_B = new DOMXpath($newDom);
                                        $xpath_B->registerNamespace("atom", self::atom_ns_uri);
                                        $xpath_B->registerNamespace("xhtml", self::xhtml_ns_uri); 
                                                                        
                                        $item_array = array();
                                        
                                        $query = "//xhtml:span[@class='item_label']";
                                        $result_name = $xpath_B->query($query, $newDom);
                                        $item_array["title"] = $result_name->item(0)->nodeValue;
                                        
                                        $query = "//xhtml:span[@class='item_cat']";
                                        $result_cat = $xpath_B->query($query, $newDom);
                                        $item_array["category"] = $result_cat->item(0)->nodeValue;
                                        
                                        $query = "//xhtml:a[@class='img_preview_link']/@href";
                                        $result_prev = $xpath_B->query($query, $newDom);
                                        $item_array["href_preview"] = $result_prev->item(0)->nodeValue;
                                        
                                        $query = "//xhtml:img/@src";
                                        $result_thumb = $xpath_B->query($query, $newDom);
                                        $item_array["href_thumb"] = $result_thumb->item(0)->nodeValue;
                                                        
                                        $query = "//atom:id";
                                        $result_id = $xpath_B->query($query, $newDom);
                                        $item_array["href"] = $result_id->item(0)->nodeValue;
                                        
                                        $all_image_array[] = $item_array;
                                }//end loop through entries
                        }//end case with entries
                        
                        $output = array("total"=> $totalFound,
                                        "startIndex" => $startIndex,
                                        "itemsPerPage" => $itemsPerPage,
                                        "links"=> $links_array,
                                        "items"=>$all_image_array);
                }//end case with atom
                else{
                        $output = array("total"=> 0);
                }
		return $output;
	}//end function

        public static function atom_links_to_json($link_type, $links_array, $xpath, $media_dom){
                $query = "/atom:feed/atom:link[@rel='".$link_type."']/@href";
                $result_l_act = null;
                $result_l_act = $xpath->evaluate($query, $media_dom);
                
                if($result_l_act != null){
                        foreach($result_l_act as $item){
                                //$act_link = $result_l_act->item(0)->nodeValue;
                                $act_link = $item->nodeValue;
                                $act_link = str_replace(".atom", ".json",  $act_link );
                                $links_array[$link_type] = $act_link;
                        }
                }
                return $links_array;
        }

}//end class declaration

?>
