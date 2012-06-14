<?php

class OpenContext_SocialTracking {
		
	public static function update_person_viewtracking($spatialItem, $nameSpaceArray){
		//spatial item is a simple xml object for an spatial item's ArchaeoML xml
		
		foreach($nameSpaceArray as $prefix => $uri){
			$spatialItem->registerXPathNamespace($prefix, $uri);
		}
		
		$array_person_uuids = array();
		
		// Count other binary media <field name="other_binary_media_count" type="sint" indexed="true" stored="false" required="true" multiValued="false" />
		if($spatialItem->xpath("//arch:links/arch:docID[@type='person']")){
			foreach ($spatialItem->xpath("//arch:links/arch:docID[@type='person']") as $link_id){
				$array_person_uuids[] = $link_id."";
			}
		}
		
		if(count($array_person_uuids)>0){
			$db_params = OpenContext_OCConfig::get_db_config();
			$db = new Zend_Db_Adapter_Pdo_Mysql($db_params);
			$db->getConnection();
			
			foreach($array_person_uuids AS $person_uuid){
			
				$where_term = 'person_uuid = "'.$person_uuid.'"';
			
				$sql = 'SELECT persons.sp_view_count
				FROM persons
				WHERE persons.'.$where_term.' 
				LIMIT 1';
		
				$result = $db->fetchAll($sql, 2);
				if($result){
					$view_count = $result[0]["sp_view_count"];
					$view_count++; // increment it up one.
					
					$data = array('sp_view_count' => $view_count);
					$n = $db->update('persons', $data, $where_term);
					
				}//end case with a result
				
			}//end loop
			
			
			$db->closeConnection();
		}
		
		//return $sql;
		
	}//end function
	
	
	public static function update_space_viewtracking($spatialItem, $nameSpaceArray){
		//spatial item is a simple xml object for an spatial item's ArchaeoML xml
		
		foreach($nameSpaceArray as $prefix => $uri){
			$spatialItem->registerXPathNamespace($prefix, $uri);
		}
		
		$db_params = OpenContext_OCConfig::get_db_config();
		$db = new Zend_Db_Adapter_Pdo_Mysql($db_params);
		$db->getConnection();
			
		foreach ($spatialItem->xpath("//arch:spatialUnit/@UUID") as $spUUID_xml){
			$spaceUUID = $spUUID_xml."";
		}
		
		$view_count = 0;
		//get the view count from the database	
		$sql = "SELECT space.view_count
		FROM space
		WHERE space.uuid = '$spaceUUID'
		LIMIT 1";
		
		$result = $db->fetchAll($sql, 2);
		if($result){
			$view_count = $result[0]["view_count"];
		}
		
		//get the view count from the XML doc
		foreach ($spatialItem->xpath("//oc:social_usage/oc:item_views/oc:count") as $viewCount_stored){
			$viewCountXML = ($viewCount_stored."")+0;
		}
		
		if($viewCountXML > $view_count){
			$view_count = $viewCountXML;
		}
		
		$view_count = $view_count + 1;
		
		//now update the database with the updated view count
		$where_term = "uuid = '$spaceUUID'";
		$data = array('view_count' => $view_count);
		$n = $db->update('space', $data, $where_term);	
		$db->closeConnection();
		
		//now change the XML to have the added view count
		foreach ($spatialItem->xpath("//oc:social_usage/oc:item_views/oc:count") as $viewCount_stored){
			$viewCount_stored[0] = $view_count;
		}
		
		return $spatialItem;
		
	}//end function
	
	
	public static function update_project_viewtracking($spatialItem, $nameSpaceArray){
		//spatial item is a simple xml object for an spatial item's ArchaeoML xml
		
		foreach($nameSpaceArray as $prefix => $uri){
			$spatialItem->registerXPathNamespace($prefix, $uri);
		}
		
		$project_id = false;
		
		foreach($spatialItem->xpath("//arch:spatialUnit/@ownedBy") as $proj_id) {
			$project_id = $proj_id."";
		}
		
		foreach($spatialItem->xpath("//arch:spatialUnit/oc:item_class/oc:name") as $item_class) {
			$class_name = $item_class."";
		}
		
		if($project_id){
			$db_params = OpenContext_OCConfig::get_db_config();
			$db = new Zend_Db_Adapter_Pdo_Mysql($db_params);
			$db->getConnection();
			
			$where_term = 'project_id = "'.$project_id.'"';
			
			$sql = 'SELECT projects.total_views
			FROM projects
			WHERE projects.'.$where_term.' 
			LIMIT 1';
		
			$result = $db->fetchAll($sql, 2);
			if($result){
				$view_count = $result[0]["total_views"];
				$view_count++; // increment it up one.
					
				$data = array('total_views' => $view_count); 
				$n = $db->update('projects', $data, $where_term);
					
			}//end case with a result
			
			
			$where_term = 'project_id = "'.$project_id.'"';
			$class_term = 'sub_id = "'.$item_class.'"';
			
			$where[] = $where_term;
			$where[] = $class_term;
			
			$where_term = $where_term." AND ".$class_term;
			
			$sql = 'SELECT subprojects.total_views
			FROM subprojects
			WHERE subprojects.'.$where_term.' 
			LIMIT 1';
		
			$result = $db->fetchAll($sql, 2);
			if($result){
				$view_count = $result[0]["total_views"];
				$view_count++; // increment it up one.
					
				$data = array('total_views' => $view_count); 
				$n = $db->update('subprojects', $data, $where);
					
			}//end case with a result
			
			
			$db->closeConnection();
		}
		
		//return $sql;
		
	}//end function
	
	
	
	public static function crawlerDetect($USER_AGENT)
	{
		/*
	    $crawlers = array(
	    array('Google', 'Google'),
	    array('msnbot', 'MSN'),
	    array('Rambler', 'Rambler'),
	    array('Yahoo', 'Yahoo'),
	    array('AbachoBOT', 'AbachoBOT'),
	    array('accoona', 'Accoona'),
	    array('AcoiRobot', 'AcoiRobot'),
	    array('ASPSeek', 'ASPSeek'),
	    array('CrocCrawler', 'CrocCrawler'),
	    array('Dumbot', 'Dumbot'),
	    array('FAST-WebCrawler', 'FAST-WebCrawler'),
	    array('GeonaBot', 'GeonaBot'),
	    array('Gigabot', 'Gigabot'),
	    array('Lycos', 'Lycos spider'),
	    array('MSRBOT', 'MSRBOT'),
	    array('Scooter', 'Altavista robot'),
	    array('AltaVista', 'Altavista robot'),
	    array('IDBot', 'ID-Search Bot'),
	    array('eStyle', 'eStyle Bot'),
	    array('Scrubby', 'Scrubby robot'),
	    array('Twiceler', 'Cuil'),
	    array('accelobot', 'accelobot'),
	    array('heritrix', 'accelobot'),
	    array('Exabot', 'exalead'),
	    array('MJ12bot', 'majestic12'),
	    array('Yandex', 'yandex'),
	    array('Netvibes', 'wasabi'),
	    array('spider', 'spider'),
	    array('bot', 'bot'),
	     array('DotBot', 'DotBot')
	    );
	
	
		
	    $output = false;
	    foreach ($crawlers as $c)
	    {
		if (stristr($USER_AGENT, $c[0])){
		    $output = true;
		}
	    }
		*/
		
		$browserObj = new BrowserDetect;
		$browserObj->detect();
		if(!$browserObj->name){
			//bot!
			$output = true; //it's true, probable BOT!
		}
		else{
			$output = false;
		}
	
	
	    return $output;
	}
	
	
	
	
	public static function rank_person_viewcounts($person_uuid){
		//spatial item is a simple xml object for an spatial item's Atom xml
		$db_params = OpenContext_OCConfig::get_db_config();
		$db = new Zend_Db_Adapter_Pdo_Mysql($db_params);
		$db->getConnection();
		
		$rank = false;
			
		$sql = 'SELECT 1 + COUNT( * ) AS rank
			FROM persons AS p1
			JOIN persons AS p2 ON ( p1.sp_view_count > p2.sp_view_count
			AND p2.person_uuid =  "'.$person_uuid.'" ) 
			LIMIT 1';
		
		$result = $db->fetchAll($sql, 2);
		if($result){
			$rank_val = $result[0]["rank"];
			
			$query = 'SELECT COUNT(*) AS rowcount FROM persons';
			$result_b = $db->fetchAll($query, 2);
			$total_pop = $result_b[0]["rowcount"];
			
			$rank = array("rank"=>$rank_val, "pop"=>$total_pop);
			
		}//end case with a result
				
		$db->closeConnection();
		
		
		return $rank;
		
	}//end function
	
	
	
	public static function rank_project_viewcounts($proj_uuid){
		//spatial item is a simple xml object for an spatial item's Atom xml
		$db_params = OpenContext_OCConfig::get_db_config();
		$db = new Zend_Db_Adapter_Pdo_Mysql($db_params);
		$db->getConnection();
		
		$rank = false;
			
		$sql = 'SELECT 1 + COUNT( * ) AS rank
			FROM projects AS p1
			JOIN projects AS p2 ON ( p1.total_views > p2.total_views
			AND p2.project_id =  "'.$proj_uuid.'" ) 
			LIMIT 1';
		
		$result = $db->fetchAll($sql, 2);
		if($result){
			$rank_val = $result[0]["rank"];
			
			$query = 'SELECT COUNT(*) AS rowcount FROM projects';
			$result_b = $db->fetchAll($query, 2);
			$total_pop = $result_b[0]["rowcount"];
			
			$rank = array("rank"=>$rank_val, "pop"=>$total_pop);
			
		}//end case with a result
				
		$db->closeConnection();
		
		
		return $rank;
		
	}//end function
	
	
	public static function rank_subproject_viewcounts($subproj_uuid){
		//spatial item is a simple xml object for an spatial item's Atom xml
		$db_params = OpenContext_OCConfig::get_db_config();
		$db = new Zend_Db_Adapter_Pdo_Mysql($db_params);
		$db->getConnection();
		
		$rank = false;
			
		$sql = 'SELECT 1 + COUNT( * ) AS rank
			FROM subprojects AS p1
			JOIN subprojects AS p2 ON ( p1.total_views > p2.total_views
			AND p2.subprojid =  "'.$subproj_uuid.'" ) 
			LIMIT 1';
		
		$result = $db->fetchAll($sql, 2);
		if($result){
			$rank_val = $result[0]["rank"];
			
			$query = 'SELECT COUNT(*) AS rowcount FROM subprojects';
			$result_b = $db->fetchAll($query, 2);
			$total_pop = $result_b[0]["rowcount"];
			
			$rank = array("rank"=>$rank_val, "pop"=>$total_pop);
			
		}//end case with a result
				
		$db->closeConnection();
		
		return $rank;
		
	}//end function
	
	
	
	public static function update_referring_link($pageType, $pageURI, $USER_AGENT, $HTTP_REFERER){
		
		$isCrawler = OpenContext_SocialTracking::crawlerDetect($USER_AGENT);
		
		if(!$isCrawler){
			$db_params = OpenContext_OCConfig::get_db_config();
			$db = new Zend_Db_Adapter_Pdo_Mysql($db_params);
			$db->getConnection();
			
			$refHash = md5($HTTP_REFERER . $pageURI);
			
			$sql = 'SELECT refer_links.ref_count
				FROM refer_links
				WHERE refer_links.ref_hash = "'.$refHash.'" 
				LIMIT 1';
		
			$result = $db->fetchAll($sql, 2);
			if($result){
				$ref_count = $result[0]["ref_count"];
				$ref_count++; // increment it up one.
					
				$data = array('ref_count' => $ref_count);
				$n = $db->update('refer_links', $data, 'ref_hash = "'.$refHash.'"');
					
			}//end case with a result
			else{
				$date = Zend_Date::now();
				$dbDate = $date->toString('YYYY-MM-dd HH:mm:ss'); 
				
				$data = array('ref_hash'=> $refHash,
					      'ref_uri' => $HTTP_REFERER,
					      'page_uri' => $pageURI,
					      'page_type' => $pageType,
					      'ref_count' => 1,
					      'created' => $dbDate
					      );
				try{
					$n = $db->insert('refer_links', $data);
				}
				catch (Exception $e) {
					
				}
			}//end loop
			
			
			$db->closeConnection();
		}
		
		//return $sql;
		
	}//end function
	
	
	
	
	
}//end class declaration

?>
