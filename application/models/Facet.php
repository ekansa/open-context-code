<?php


/*
 this class is used to create individual facets,
 facet values, and links for GET queries to filder by a facet, and checkboxes for
 advanced search options on a facet
*/
class Facet {
    
   
    public $standard_link_html; //html of link, facet value, and facet count for using this facet
    public $link; //value used for link href attribute to get query based on this facet 
    public $linkQuery; //properly url encoded facet value needed for GET query, needed for check box 
    public $value_string; //facet value i.e. Domuztepe, Petra Great Temple, Bade Museum...
    public $checkParameter; //parameter used for advanced search check box
    public $va_value; // facet count
    public $skip_UTF8; //skip UTF-8 maddess


    function normalFacet($va_key, $va_value, $host, $linkURLprefix, $linkURLsuffix){
	
        if(!$linkURLprefix && !$linkURLsuffix){
            $this->standard_link_html = "";
            $this->value_string = "";
        }
        else{
	    //$this->skip_UTF8 = true;
	    if($this->skip_UTF8){
		$linkQuery = urlencode($va_key);
	    }
	    else{
		$linkQuery = urlencode(OpenContext_UTF8::charset_decode_utf_8($va_key));
	    }



            $link = $host.$linkURLprefix.$linkQuery.$linkURLsuffix;
            $value_out = '<a href="' . $link . '" > ' . $va_key . '</a>' . ': ' . $va_value;
                
            $this->linkQuery = $linkQuery;
            $this->link = $link;
            $this->standard_link_html = $value_out;
            $this->value_string = $va_key;
            $this->va_value = $va_value;
        }
        
    }//end function
    
        
    
    function make_html_facet($facet_category_label, $facetXHTML, $advanced = false, $linkID = false, $linkClass = false, $linkStyle = false){
    //this is general code to make an HTML element to be a facet	

        $checkParameter = $this->checkParameter;
        $linkQuery = $this->linkQuery;
        $link = $this->link;
        $value_string = $this->value_string;
        $va_value = $this->va_value;
        
	$fac_val_node = $facetXHTML->createElement("li");
		
	if($linkStyle != false){
	    $fac_val_node->setAttribute("style", $linkStyle);
	}
        
        if($linkClass != false){
            $fac_val_node->setAttribute("class", $linkClass);
        }
        else{
            $fac_val_node->setAttribute("class", "facetListItem");
        }
        
	$facetID = substr(sha1($checkParameter.$linkQuery),0,10);
	$fac_val_node->setAttribute("id", "f_".$facetID);
	
        if($advanced){
            $fac_input = $facetXHTML->createElement("input");
            $fac_input->setAttribute("type", "checkbox");
            $fac_input->setAttribute("class", "deActCheck");
            $fac_input->setAttribute("name", $checkParameter);
            $fac_input->setAttribute("value", $linkQuery);
            $fac_input->setAttribute("id", "in_".$facetID);
            $fac_val_node->appendChild($fac_input);
        }	
                
	$fac_val_link = $facetXHTML->createElement("a");
	$fac_val_link->setAttribute("href", $link);
	$fac_val_link->setAttribute("class", "facet_value");
		
	if(!$linkID){
	    $linkID = "l_".$facetID;	
	}
			
	$fac_val_link->setAttribute("id", $linkID);
	    
	$facet_val_node_link_val = $facetXHTML->createTextNode($value_string."");
        $fac_val_link->appendChild($facet_val_node_link_val);
	$fac_val_node->appendChild($fac_val_link);
	$fac_val_fcount_style = $facetXHTML->createElement("span");
        $fac_val_fcount_style->setAttribute("id", "fc_".$facetID);
	$fac_val_fcount_style->setAttribute("class", "facet_count");
	$fac_val_fcount_style_val = $facetXHTML->createTextNode($va_value);
	$fac_val_fcount_style->appendChild($fac_val_fcount_style_val);
	//$fac_val_link->appendChild($fac_val_fcount_style);
        $fac_val_node->appendChild($fac_val_fcount_style);
	$fac_val_node->setAttribute("title", $facet_category_label."- ".$value_string);
		
	return $fac_val_node;
    }
    
    
}
