<?php

class OpenContext_MediaAtom {
		
	
	
	public static function update_view_count($media_dom, $viewCount, $nameSpaceArray){
		
		$xpath = new DOMXpath($media_dom);
		
		// Register OpenContext's namespace
		foreach($nameSpaceArray as $prefix => $uri){
			$xpath->registerNamespace($prefix, $uri);
		}
		
		$query = "//oc:social_usage/oc:item_views/oc:count";
	
		$result = $xpath->query($query, $media_dom);
		
		if($result != null){
			$old_value = $result->item(0)->nodeValue;
			$old_value = $old_value + 1;
			$result->item(0)->nodeValue = $viewCount;
		}
		//return $old_value;
		
	}//end update function
	
	
	
	
	
	public static function make_atom($atomFullDoc, $mediaItem_dom, $host){
		

		
	}//end reindex function
	
	
	
}//end class declaration

?>
