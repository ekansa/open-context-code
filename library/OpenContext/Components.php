<?php

class OpenContext_Components {
	
	
	public static function find_page_components($pageID, $type){
		//page id is the page being displayed
		//type is either css or js 
		
		$componentArray = array(
			"home" => array("css" => array("/css/opencontext_style.css",
						       "/css/default_banner.css",
						       "/css/timemap.css",
						       "/css/home_page.css"
						       ),
					"js"=> array("/js/timemap/timemap_full.pack.js",
						     "/js/timemap/oc.js",
						     "/js/timemap/manipulation.js",
						     "/js/timemap/loaders/json.js")
				),
			"about" => array("css" => array(),
					"js"=> array()
				),
			"browse" => array("css" => array(
							/*
						       "/css/opencontext_style.css",
						       "/css/default_banner.css",
							*/
							"/css/oc-layout-rev2.css",
							"/css/bootstrap.css",
							"/css/bootstrap-responsive.css",
						       "/css/opencontext_browse.css",
						       "/css/atomresults.css",
						       "/css/rounded_corners.css"
						       ),
					"js"=> array("/js/advance_browse/advance_browse.js"
						     )
				),
			"lightbox" => array("css" => array("/css/opencontext_style.css",
						       "/css/default_banner.css",
						       "/css/opencontext_browse.css",
						       "/css/atomresults.css",
						       "/css/rounded_corners.css",
						       "/css/colorbox.css"
						       ),
					"js"=> array("/js/advance_browse/advance_browse.js",
						     "/js/colorbox/colorbox/jquery.colorbox.js"
						     )
				)
		);
	
	
		$components = false;
		
		if((substr_count($pageID, "timeline/")>0) && ($type=="js")){
			//this is a timeline javascript;
			$components = array();
			$components[] = "/js/".$pageID;
		}
		elseif((substr_count($pageID, "timeline/")>0) && ($type=="css")){
			$components = array();
			$components[] = "/js/".$pageID;
		}
		else{
			if(array_key_exists($pageID, $componentArray)){
				$pageArray = $componentArray[$pageID];
				unset($componentArray);
				if(array_key_exists($type, $pageArray)){
					$components = $pageArray[$type];
					unset($pageArray);
				}
			}
		}
		
		
		return $components;
	}
	
	
	//this function gets components for a page and consolidates into one big string
	public static function get_components($pageID, $type){
		$host = OpenContext_OCConfig::get_host_config();
		$output = "";
		$components = OpenContext_Components::find_page_components($pageID, $type);
		if($components != false){
		
			if($type == "css"){
				foreach($components as $actComponent){
					if(substr_count($actComponent, "http://")<1){
						$actComponent = $host.$actComponent;
					}
					
					$new_output = chr(13)." ".(file_get_contents($actComponent));
					$new_output = str_replace('@charset "utf-8";', ' ', $new_output);
					$output.= $new_output;
				}
				$output = preg_replace('/[^(\x20-\x7F)]*/','', $output);
			}
			elseif($type = "js"){
				foreach($components as $actComponent){
					if(substr_count($actComponent, "http://")<1){
						$actComponent = $host.$actComponent;
					}
					$output .= chr(13)." ".(file_get_contents($actComponent));
				}
			}
		}
		return $output;
	}
	
	
	public static function get_gzip_comp_data($pageID, $type){
		
		$frontendOptions = array(
			'lifetime' => 72000, // cache lifetime, measured in seconds, 7200 = 2 hours
			'automatic_serialization' => true
		);
			
		$backendOptions = array(
		    'cache_dir' => './comp_cache/' // Directory where to put the cache files
		);
			
		$cache = Zend_Cache::factory('Core',
				     'File',
				     $frontendOptions,
				     $backendOptions);
		
		
		$cache->clean(Zend_Cache::CLEANING_MODE_OLD); // clean old cache records
		$cache_id = "comp_".$type."_".md5($pageID);
		
		if(!$cache_result = $cache->load($cache_id)) {
		    $compString = OpenContext_Components::get_components($pageID, $type);
		    $GZIP_comp = gzcompress($compString, 9);
		    $cache->save($GZIP_comp, $cache_id);
		}
		else{
		    $GZIP_comp = $cache_result;
		}
		
		return $GZIP_comp;
		//return $compLink;
	}// end function






}//end class declaration

?>
