<?php

class OpenContext_PropertyAtom {
		
	
	
	public static function update_view_count($media_dom){
		
		$xpath = new DOMXpath($media_dom);
		
		// Register OpenContext's namespace
		$xpath->registerNamespace("arch", OpenContext_OCConfig::get_namespace("arch", "media"));
                $xpath->registerNamespace("oc", OpenContext_OCConfig::get_namespace("oc", "media"));
                $xpath->registerNamespace("default", OpenContext_OCConfig::get_namespace("atom"));
		
		
		
		$query = "//oc:social_usage/oc:item_views/oc:count";
	
		$result = $xpath->query($query, $media_dom);
		
		if($result != null){
			$old_value = $result->item(0)->nodeValue;
			$old_value = $old_value + 1;
			$result->item(0)->nodeValue = $old_value;
		}
		//return $old_value;
		
	}//end update function
	
	
	public static function light_parseXMLcoding($string){
		if ( strlen($string) == 0 )
		    return $string;
		
		libxml_use_internal_errors(true);
		$test_string = "<test>".$string."</test>";
		$doc = simplexml_load_string($test_string);
		
		if(!($doc)){
		    // convert problematic characters to XML entities ('&' => '&amp;')
		    $string = htmlentities($string);
		    
		    // convert ISO-8859-1 entities to numerical entities ('&eacute;' => '&#233;')       
		    $mapping = array();
		    foreach (get_html_translation_table(HTML_ENTITIES, ENT_QUOTES) as $char => $entity){
			$mapping[$entity] = '&#' . ord($char) . ';';
		    }
		    $string = str_replace(array_keys($mapping), $mapping, $string);
		   
		    // encode as UTF-8
		    $string = utf8_encode($string);
		}
		//$string = str_replace("&amp;#", "&#", $string);
		//$string = str_replace("amp;#", "#", $string);
		return $string;       
	}//end function
	
	
	public static function make_archaeoml_atom($prop_id){
		
		$path_to_local_prop = "http://about.oc/octest/property.php?item=";
		$baseURI = "http://opencontext/properties/";
		//$path_to_local_prop = "http://www.opencontext.org/database/atom_scripts/property.php?item=";
		//$baseURI = "http://testing.opencontext.org/properties/";
		
		
		$prop_item_uri = $path_to_local_prop.$prop_id;
		$property_xml = file_get_contents($prop_item_uri);
		
		@$xml = simplexml_load_string($property_xml);
		
		if(!$xml ){
		    //echo $error;
		    //$all_errors .= $error;
		}
		else{
			unset($xml);
			$itemObj = new Property; //start property class
			$itemObj->archaeoML = $property_xml;
		
			$itemXML = simplexml_load_string($property_xml);
			$itemXML->registerXPathNamespace("arch", OpenContext_OCConfig::get_namespace("arch", "property"));
			$itemXML->registerXPathNamespace("oc", OpenContext_OCConfig::get_namespace("oc", "property"));
			$itemXML->registerXPathNamespace("dc", OpenContext_OCConfig::get_namespace("dc"));
		
			$itemObj = OpenContext_XMLtoItems::XMLpropertyBasic($itemObj, $itemXML);
			$itemObj->fullAtomCreate($property_xml); //requires a string, makes the full Atom representation needed for the person feed
			$updateInsertSuccess = $itemObj->createUpdate(false);
			
			
			return $itemObj->atomFull;
		}//makes archaeoml data and atom data
		
	}//end function makes archaeoml data and atom data
	
	
	
}//end class declaration

?>
