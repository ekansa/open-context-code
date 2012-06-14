<?php

class OpenContext_unAPI {
		
	
	public static function get_space_metadata($spaceURI){
		$host = OpenContext_OCConfig::get_host_config();
		$itemUUID = str_replace($host."/subjects/", "", $spaceURI);
		
		$spaceItem = New Subject;
		$itemFound = $spaceItem->getByID($itemUUID);
		@$spatialItem = simplexml_load_string($spaceItem->newArchaeoML);
		if($spatialItem){
			$output = OpenContext_unAPI::spaceDCmetadata_to_array($spatialItem );
		}
		else{
			$output =  false;
		}
		return $output;
	}
	
	
	public static function spaceDCmetadata_to_array($spatialItem){
		
		// Register ArchaeoML namespace
		$spatialItem->registerXPathNamespace("arch", OpenContext_OCConfig::get_namespace("arch", "spatial"));
		
		// Register OpenContext namespace
		$spatialItem->registerXPathNamespace("oc", OpenContext_OCConfig::get_namespace("oc", "spatial"));
		
		// Register the Atom namespace
		$spatialItem->registerXPathNamespace("atom", OpenContext_OCConfig::get_namespace("atom"));
		
		
		foreach ($spatialItem->xpath("//arch:spatialUnit/@UUID") as $spUUID_xml){
			$spaceUUID = $spUUID_xml."";
		}
		
		$metadata = array();
		foreach ($spatialItem->xpath("//oc:metadata") as $metadataElement){
				
			$metadataElement->registerXPathNamespace("dc", OpenContext_OCConfig::get_namespace("dc"));
				
			foreach ($metadataElement->xpath("//dc:title") as $dcTitle_xml){
				if(!array_key_exists("title", $metadata)){
					$metadata["title"][] = $dcTitle_xml."";
				}
			}
			foreach ($metadataElement->xpath("//dc:identifier") as $dcMeta_xml){
				if(!array_key_exists("identifier", $metadata)){
					$metadata["identifier"][] = $dcMeta_xml."";
				}
			}
			foreach ($metadataElement->xpath("//dc:date") as $dcDate_xml){
				$metadata["date"][] = $dcDate_xml."";
			}
			foreach ($metadataElement->xpath("//dc:rights") as $dcMeta_xml){
				$metadata["rights"][] = $dcMeta_xml."";
			}
			foreach ($metadataElement->xpath("//dc:publisher") as $dcMeta_xml){
				$metadata["publisher"][] = $dcMeta_xml."";
			}
			foreach ($metadataElement->xpath("//dc:format") as $dcMeta_xml){
				$metadata["format"][] = $dcMeta_xml."";
			}
			foreach ($metadataElement->xpath("//dc:coverage") as $dcMeta_xml){
				$metadata["coverage"][] = $dcMeta_xml."";
			}
			foreach ($metadataElement->xpath("//dc:subject") as $dcMeta_xml){
				$metadata["subject"][] = $dcMeta_xml."";
			}
			foreach ($metadataElement->xpath("//dc:creator") as $dcMeta_xml){
				$metadata["creator"][] = $dcMeta_xml."";
			}
			foreach ($metadataElement->xpath("//dc:contributor") as $dcMeta_xml){
				$metadata["contributor"][] = $dcMeta_xml."";
			}
		}
		
		return $metadata;
	}
	
	
	public static function name_seperate($name){
		
		if($name){
			if(substr_count($name, ' ')>0){
				$nameArray = explode(' ', $name);
			}
			else{
				$nameArray = array();
				$nameArray[] = $name;
			}
			
			$namePartCount = count($nameArray);
			$lastPart = $namePartCount - 1;
			if($lastPart < 0){
				$lastPart = 0;	
			}
			$i = 0;
			$firstNames = "";
			while($i < $lastPart){
				if($i > 0){
					$firstNames .= " ";
				}
				$firstNames .= 	$nameArray[$i];
			$i++;
			}
			
			$output = array("given" => $firstNames, "family" => $nameArray[$lastPart]);
			
		}
		else{
			$output = false;
		}
		
		return $output;
	}
	
}//end class declaration

?>
