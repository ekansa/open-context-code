<?php


class OpenContext_SubProject {
	
	const oc_ns_uri = "http://about.opencontext.org/schema/project_schema_v1.xsd"; // namespace uri for OC persons
	const arch_ns_uri = "http://ochre.lib.uchicago.edu/schema/Project/Project.xsd"; // namespace uri for archaeoml persons
	const atom_ns_uri = "http://www.w3.org/2005/Atom"; // namespace uri for Atom
	const dc_ns_uri = "http://purl.org/dc/elements/1.1/"; // namespace uri for Dublin Core
        const geo_ns_uri = "http://www.georss.org/georss"; // namespace uri for GeoRSS
        const xhtml_ns_uri = "http://www.w3.org/1999/xhtml"; //namespace for xhtml
        const kml_ns_uri = "http://www.opengis.net/kml/2.2"; //namespace for kml
        
        const path_to_class_icon = "http://www.opencontext.org/database/ui_images/oc_icons/"; // path to class icon, if missing
	
        const COINS_SPAN_CLASS = 'Z3988';
        const CTX_VER          = 'Z39.88-2004';
        const RFT_VAL_FMT      = 'info:ofi/fmt:kev:mtx:dc';
        const RFR_ID           = 'info:sid/opencontext.org:generator';

        
        
        public static function get_namespace_uri($type){
                
                $output = false;
                if($type == "arch"){
                        $output = self::arch_ns_uri;
                }
                if($type == "oc"){
                        $output = self::oc_ns_uri;
                }
                
                return $output;
        }
        
        
        
        //make unique ids
      public static function gen_uuid() {
                $rawid = strtoupper(md5(uniqid(rand(), true)));
                $workid = $rawid;
                $byte = hexdec( substr($workid,12,2) );
                $byte = $byte & hexdec("0f");
                $byte = $byte | hexdec("40");
                $workid = substr_replace($workid, strtoupper(dechex($byte)), 12, 2);
            
                // build a human readable version
                $rid = substr($rawid, 0, 8).'-'
                .substr($rawid, 8, 4).'-'
                .substr($rawid,12, 4).'-'
                .substr($rawid,16, 4).'-'
                .substr($rawid,20,12);
                    
                
                // build a human readable version
                $wid = substr($workid, 0, 8).'-'
                .substr($workid, 8, 4).'-'
                .substr($workid,12, 4).'-'
                .substr($workid,16, 4).'-'
                .substr($workid,20,12);
                 
                return $wid;
        }
        
        
        
        public static function deleteNode($node) {
                OpenContext_SubProject::deleteChildren($node);
                $parent = $node->parentNode;
                $oldnode = $parent->removeChild($node);
        }
            
        public static function deleteChildren($node) {
                while (isset($node->firstChild)) {
                    OpenContext_SubProject::deleteChildren($node->firstChild);
                    $node->removeChild($node->firstChild);
                }
        } 
        
        
        public static function project_class_exists($project_id, $class_name){
        
                $host = OpenContext_OCConfig::get_host_config();
                $projJSONstring = file_get_contents($host."/projects/".$project_id.".json");
                $projJSON = Zend_Json::decode($projJSONstring);
                $output = false;
                foreach($projJSON["categories"] as $actCategory){
                    
                        if($actCategory["name"] == $class_name){
                              $output = $actCategory["href"];
                              $output = str_replace("/sets/","/sets/facets/",$output);
                              $output = str_replace("/?",".atom?",$output);
                              $output = str_replace("facets.atom?","facets/.atom?",$output);
                        }
                        
                }
                
                return $output;
        
        }
        
        
        public static function make_new_sub_XML($project_id, $class_name, $classFacetURI){
                
                $host = OpenContext_OCConfig::get_host_config();
                $proj_atom_string = file_get_contents($host."/projects/".$project_id.".atom");
                $proj_dom = new DOMDocument("1.0", "utf-8");
                $proj_dom->loadXML($proj_atom_string);
                    
                    
                $xpath = new DOMXpath($proj_dom);
		
                    // Register OpenContext's namespace
                $xpath->registerNamespace("arch", self::arch_ns_uri);
                $xpath->registerNamespace("oc", self::oc_ns_uri);
                $xpath->registerNamespace("dc", self::dc_ns_uri);
                $xpath->registerNamespace("atom", self::atom_ns_uri);
                
                
                $classFacetObj = OpenContext_FacetAtom::atom_to_object($classFacetURI);
                $classRelatedPersons = $classFacetObj["related person"];
                $maxFacets = 0;
                
                foreach($classRelatedPersons as $actClassPerson){
                        if($actClassPerson["count"] > $maxFacets){
                                $maxFacets = $actClassPerson["count"];
                        }
                }
                
                
                
		$query = "//arch:project";
		$result_arch = $xpath->query($query, $proj_dom);
                if($result_arch != null){
			$arch_node = $result_arch->item(0);
                        $newDom = new DOMDocument;
                        $newDom->appendChild($newDom->importNode($arch_node,1));
                                                
                        $xpath_B = new DOMXpath($newDom);
                        $xpath_B->registerNamespace("oc", self::oc_ns_uri);
                        $xpath_B->registerNamespace("arch", self::arch_ns_uri);
                        $xpath_B->registerNamespace("dc", self::dc_ns_uri);
                        
                        $query = "//arch:project/@UUID";
                        $result_id = $xpath_B->query($query, $newDom);
                        if($result_id != null){
                                $new_UUID = OpenContext_SubProject::gen_uuid();
                                $result_id->item(0)->nodeValue = $new_UUID;
                        }
                        
                        $result_node = null;
                        $query = "//arch:project/arch:name/arch:string";
                        $result_node = $xpath_B->query($query, $newDom);
                        if($result_node != null){
                                $old_value = $result_node->item(0)->nodeValue;
                                $new_value = $old_value." ".$class_name." Data";
                                $result_node->item(0)->nodeValue = $new_value;
                                $new_title = $new_value; 
                        }
                        
                        $result_node = null;
                        $query = "//oc:person_links/oc:link/oc:name";
                        $result_nodes = $xpath_B->query($query, $newDom);
                        
                        $dc_creator_array = array();
                        $dc_contributor_array = array();
                        
                        if($result_nodes != null){
                                
                                $nodesToRemove = array();
                                $personIDsToRemove = array();
                                
                                foreach($result_nodes as $result_node){
                                        $keepLink = false;
                                        $old_value = $result_node->nodeValue;
                                        $dc_creator = false;
                                        $dc_contrib = false;
                                        foreach($classRelatedPersons as $actClassPerson){  
                                                if($old_value == $actClassPerson["name"]){
                                                        $keepLink = true;
                                                        if($actClassPerson["count"] >= ($maxFacets *.75)){
                                                                $dc_creator_array[] = $actClassPerson["name"];
                                                                $dc_creator = true;
                                                        }
                                                        if(($actClassPerson["count"] >= ($maxFacets*.50))
                                                           &&($actClassPerson["count"] < ($maxFacets*.75))){
                                                                $dc_contributor_array[] = $actClassPerson["name"];
                                                                $dc_contrib = true;
                                                        }
                                                }
                                        }
                                
                                        $act_link = $result_node->parentNode;
                                        if(!$keepLink){
                                                $nodesToRemove[] = $act_link;
                                                $personIDsToRemove[] = $act_link->getElementsByTagNameNS(self::oc_ns_uri, "id")->item(0)->nodeValue;
                                        }
                                        else{
                                                if($dc_creator){
                                                        $act_link->getElementsByTagNameNS(self::oc_ns_uri, "relation")->item(0)->nodeValue = "Principle Researcher";
                                                }
                                                if($dc_contrib){
                                                        $act_link->getElementsByTagNameNS(self::oc_ns_uri, "relation")->item(0)->nodeValue = "Contributing Researcher";
                                                }
                                        }
                                
                                }//end loop through person links
                                
                                
                                //now delete the people we don't want any more
                                foreach($nodesToRemove as $actRemove){
                                        OpenContext_SubProject::deleteNode($actRemove);
                                }//end deleting bad nodes
                                
                                $result_nodes = null;
                                $query = "//arch:links/arch:docID[@type='person']";
                                $result_nodes = $xpath_B->query($query, $newDom);
                                $KillPersonDocLinksNodes = array();
                                $total_space_views = 0; 
                                
                                if($result_nodes != null){                
                                        foreach($result_nodes as $result_node){
                                                $old_value = $result_node->nodeValue;
                                                if(in_array($old_value, $personIDsToRemove)){
                                                        $KillPersonDocLinksNodes[] = $result_node;        
                                                }
                                                else{
                                                        $total_space_views += OpenContext_SubProject::space_views_of_person($old_value);
                                                }
                                        }//end loop through person nodes
                                        
                                        //now delete the people we don't want any more
                                        foreach($KillPersonDocLinksNodes as $actRemove){
                                                OpenContext_SubProject::deleteNode($actRemove);
                                        }//end deleting bad nodes
                                        
                                }//end case with person links
                                
                        }//end case with person links
                        
                        
                        //fix new title
                        $result_nodes = null;
                        $query = "//oc:metadata/dc:title";
                        $result_nodes = $xpath_B->query($query, $newDom);
                        if($result_nodes != null){ 
                                $old_value = $result_nodes->item(0)->nodeValue;
                                $new_value = $new_title;
                                $result_nodes->item(0)->nodeValue = $new_value;
                                $metadata_node = $result_nodes->item(0)->parentNode;
                        }
                        
                        //fix identifier
                        $new_id = ($host."/projects/".$project_id."/".$class_name);
                        
                        $result_nodes = null;
                        $query = "//oc:metadata/dc:identifier";
                        $result_nodes = $xpath_B->query($query, $newDom);
                        if($result_nodes != null){ 
                                $old_value = $result_nodes->item(0)->nodeValue;
                                $old_identifier = urlencode($old_value);
                                $new_value = $new_id;
                                $result_nodes->item(0)->nodeValue = $new_value;
                        }
                        
                        
                        
                        
                        
                        $ExistingContributors = array();
                        $query = "//oc:metadata/dc:contributor";
                        $result_nodes = $xpath_B->query($query, $newDom);
                        $KillOldDCContributors = array();                
                        if($result_nodes != null){
                                
                                foreach($result_nodes as $result_node){
                                        $old_value = $result_node->nodeValue;
                                        $ExistingContributors[] = $old_value;
                                }
                        }
                        

                        $query = "//oc:metadata/dc:creator";
                        $result_nodes = $xpath_B->query($query, $newDom);
                        $KillOldDCcreators = array();
                        $ExistingCreators = array();
                        
                        if($result_nodes != null){
                                
                                foreach($result_nodes as $result_node){
                                        $creator_node = $result_node;
                                        $old_value = $result_node->nodeValue;
                                        
                                        if(!in_array($old_value, $dc_creator_array)){
                                                $KillOldDCcreators[] = $result_node;        
                                                if(!in_array($old_value, $dc_contributor_array)){
                                                        $dc_contributor_array[] = $old_value;
                                                }
                                        }
                                        else{
                                                $ExistingCreators[] = $old_value;
                                        }
                                        
                                }//end loop through person nodes
                                
                                foreach($dc_creator_array as $act_add_creator){
                                        if(!in_array($act_add_creator, $ExistingCreators)){
                                                $element = $newDom->createElementNS(self::dc_ns_uri,"creator", $act_add_creator);
                                                //$metadata_node->appendChild($element);
                                                $metadata_node->insertBefore($element, $creator_node);
                                        }
                                }
                                
                                foreach($dc_contributor_array as $act_add_contrib){
                                        if(!in_array($act_add_contrib, $ExistingContributors)){
                                                $element = $newDom->createElementNS(self::dc_ns_uri,"contributor", $act_add_contrib);
                                                //$metadata_node->appendChild($element);
                                                $metadata_node->insertBefore($element, $creator_node);
                                        }
                                }
                                
                                //now delete the people we don't want any more
                                foreach($KillOldDCcreators as $actRemove){
                                        OpenContext_SubProject::deleteNode($actRemove);
                                }//end deleting bad nodes
                                
                                
                                
                                
                        }//end case with person links
                        
                        
                        //add subject
                        $result_nodes = null;
                        $foundSubject = false;
                        $query = "//oc:metadata/dc:subject[. ='".$class_name."']";
                        $result_nodes = $xpath_B->query($query, $newDom);
                        if($result_nodes != null){ 
                                if($result_nodes->item(0)){
                                        $foundSubject = true;
                                }
                        }
                        $query = "//oc:metadata/dc:subject";
                        $result_nodes = $xpath_B->query($query, $newDom);
                        if($result_nodes != null){ 
                                $subjectsNode = $result_nodes->item(0);
                        }
                        if(!$foundSubject){
                                $element = $newDom->createElementNS(self::dc_ns_uri,"subject", ($class_name));
                                $metadata_node->insertBefore($element, $subjectsNode);
                        }
                        
                        $query = "//oc:metadata/dc:date";
                        $result_nodes = $xpath_B->query($query, $newDom); 
                        if($result_nodes != null){
                                foreach($result_nodes as $result_node){
                                        $pubDate = $result_node->nodeValue;
                                }
                        }
                        
                        $query = "//oc:social_usage";
                        $result_nodes = $xpath_B->query($query, $newDom); 
                        if($result_nodes != null){
                                if($result_nodes->item(0)){
                                        OpenContext_SubProject::deleteNode($result_nodes->item(0));
                                }
                        }
                        
                        $result_nodes = null;
                        $query = "//oc:metadata/oc:coins";
                        $result_nodes = $xpath_B->query($query, $newDom);
                        if($result_nodes != null){ 
                                $old_value = $result_nodes->item(0)->nodeValue;
                                $new_value = OpenContext_SubProject::makeCoins($newDom, $xpath_B);
                                $result_nodes->item(0)->nodeValue = $new_value;
                        }
                        
                        
                }//end case with archaeoML
                
                $archaeoML = $newDom->saveXML();
                
                
                $db_params = OpenContext_OCConfig::get_db_config();
                $db = new Zend_Db_Adapter_Pdo_Mysql($db_params);
                               
                $db->getConnection();
                
                $data = array(
                        'subprojid' => $new_UUID,
                        'project_id' => $project_id,
                        'sub_id' => $class_name,
                        'sub_name' => $new_title,
                        'published' => $pubDate,
                        'total_views' => $total_space_views,
                        'archaeoML' => $archaeoML
                    );
                
                //$db->insert('subprojects', $data);
                
                $db->closeConnection();
                
                return $archaeoML;
                
        }
        
        public static function space_views_of_person($personUUID){
                $db_params = OpenContext_OCConfig::get_db_config();
                $db = new Zend_Db_Adapter_Pdo_Mysql($db_params);
                               
                $db->getConnection();
                
                $sql = "SELECT persons.sp_view_count
                FROM persons
                WHERE persons.person_uuid = '".$personUUID."'
                ";
                
                $result = $db->fetchAll($sql, 2);
                         
                if($result){
                        return $result[0]["sp_view_count"];
                }
                else{
                        return false;
                }
        }
        
        
        public static function makeCoins($newDom, $xpath_B){
                
                
                $coins = array();
                $coins['ctx_ver']     = self::CTX_VER;
                $coins['rft_val_fmt'] = self::RFT_VAL_FMT;
                $coins['rfr_id']      = self::RFR_ID;
                $coins['rft.type'] = "dataset";
                
                $query = "//oc:metadata/dc:title";
                $result_nodes = $xpath_B->query($query, $newDom); 
                if($result_nodes != null){
                        foreach($result_nodes as $result_node){
                                $old_value = $result_node->nodeValue;
                                //$coinSubjs .= "&amp;rft.subject=".urlencode($old_value);
                                $coins["rft.title"] = $old_value;
                        }
                }
                
                $coinCreators = array();
                $query = "//oc:metadata/dc:creator";
                $result_nodes = $xpath_B->query($query, $newDom); 
                if($result_nodes != null){
                        foreach($result_nodes as $result_node){
                                $old_value = $result_node->nodeValue;
                                //$coinSubjs .= "&amp;rft.subject=".urlencode($old_value);
                                $coinCreators[] = $old_value;
                                //$coinCreator[] = str_replace(" ", "%20", $old_value);
                                //$coinSubjs .= htmlentities("&rft.subject=".urlencode($old_value));
                        }
                }
                //$coins["rft.creator"] = $coinCreator;
                
                $coinContribs = array();
                $query = "//oc:metadata/dc:contributor";
                $result_nodes = $xpath_B->query($query, $newDom); 
                if($result_nodes != null){
                        foreach($result_nodes as $result_node){
                                $old_value = $result_node->nodeValue;
                                //$coinSubjs .= "&amp;rft.subject=".urlencode($old_value);
                                //$coinContribs[] = str_replace(" ", "%20", $old_value);
                                $coinContribs[] = $old_value;
                                //$coinSubjs .= htmlentities("&rft.subject=".urlencode($old_value));
                        }
                }
                //$coins["rft.contributor"] = $coinContribs;
                
                $coinSubjs = array();
                $query = "//oc:metadata/dc:subject";
                $result_nodes = $xpath_B->query($query, $newDom); 
                if($result_nodes != null){
                        foreach($result_nodes as $result_node){
                                $old_value = $result_node->nodeValue;
                                //$coinSubjs .= "&amp;rft.subject=".urlencode($old_value);
                                $coinSubjs[] = $old_value;
                                //$coinSubjs .= htmlentities("&rft.subject=".urlencode($old_value));
                        }
                }
                //$coins["rft.subject"] = $coinSubjs;
                
                $query = "//oc:metadata/dc:date";
                $result_nodes = $xpath_B->query($query, $newDom); 
                if($result_nodes != null){
                        foreach($result_nodes as $result_node){
                                $old_value = $result_node->nodeValue;
                                //$coinSubjs .= "&amp;rft.subject=".urlencode($old_value);
                                $coins["rft.date"] = $old_value;
                        }
                }
                
                $query = "//oc:metadata/dc:license";
                $result_nodes = $xpath_B->query($query, $newDom); 
                if($result_nodes != null){
                        foreach($result_nodes as $result_node){
                                $old_value = $result_node->nodeValue;
                                //$coinSubjs .= "&amp;rft.subject=".urlencode($old_value);
                                $coins["rft.license"] = $old_value;
                        }
                }
                
                $query = "//oc:metadata/dc:source";
                $result_nodes = $xpath_B->query($query, $newDom); 
                if($result_nodes != null){
                        foreach($result_nodes as $result_node){
                                $old_value = $result_node->nodeValue;
                                //$coinSubjs .= "&amp;rft.subject=".urlencode($old_value);
                                $coins["rft.source"] = $old_value;
                        }
                }
                
                $query = "//oc:metadata/dc:publisher";
                $result_nodes = $xpath_B->query($query, $newDom); 
                if($result_nodes != null){
                        foreach($result_nodes as $result_node){
                                $old_value = $result_node->nodeValue;
                                //$coinSubjs .= "&amp;rft.subject=".urlencode($old_value);
                                $coins["rft.pulisher"] = $old_value;
                        }
                }
                
                
                $query = "//oc:metadata/dc:identifier";
                $result_nodes = $xpath_B->query($query, $newDom); 
                if($result_nodes != null){
                        foreach($result_nodes as $result_node){
                                $old_value = $result_node->nodeValue;
                                //$coinSubjs .= "&amp;rft.subject=".urlencode($old_value);
                                $coins["rft.identifier"] = $old_value;
                        }
                }
                
                
                $allCoins = http_build_query($coins, "&") ;
                
                $coined_creators = OpenContext_SubProject::make_coins_from_array($allCoins, "rft.creator", $coinCreators);
                $coined_contribs = OpenContext_SubProject::make_coins_from_array($allCoins, "rft.contributor", $coinContribs);
                $coinded_subs = OpenContext_SubProject::make_coins_from_array($allCoins, "rft.subject", $coinSubjs);
                $coined_arrays = $coined_creators.$coined_contribs.$coinded_subs;
                $allCoins = str_replace("&amp;rft.date", $coined_arrays."&amp;rft.date", $allCoins);
                
                
                
                return $allCoins;
        }//end making coins function
        
        
        //making the right format for URL http query used in coins where an array is passed
        public static function make_coins_from_array($allCoins, $coins_field, $value_array){
                
                $addingMore = "";
                if(count($value_array)>0){
                        foreach($value_array as $act_value){
                                //$addingMore .=  "&amp;" . $coins_field . "=" . str_replace(" ", "%20", $act_value);
                                $addingMore .=  "&amp;" . $coins_field . "=" . urlencode($act_value);
                                //echo $coins_field . "=" . urlencode($act_value);
                        }
                }
                return $addingMore;
        }
        
        
        //make the first Atom feed
        public static function make_first_atom($archaeoML, $project_id, $class_name){
                
                
                $proj_dom = new DOMDocument("1.0", "utf-8");
                $proj_dom->loadXML($archaeoML);
                
                $xpath = new DOMXpath($proj_dom);
                        
                // Register OpenContext's namespace
                $xpath->registerNamespace("arch", self::arch_ns_uri);
                $xpath->registerNamespace("oc", self::oc_ns_uri);
                $xpath->registerNamespace("dc", self::dc_ns_uri);
                        
                $query = "/arch:project/arch:name/arch:string";
                $result_title = $xpath->query($query, $proj_dom);
                        
                if($result_title != null){
                    $proj_item_name = $result_title->item(0)->nodeValue;
                }
                
                $query = "//arch:notes/arch:note[@type='long_des']/arch:string";
                $result_des = $xpath->query($query, $proj_dom);
                        
                if($result_des != null){
                    $long_des = $result_des->item(0)->nodeValue;
                }
                
                $query = "//oc:metadata/oc:project_name";
                $result_proj = $xpath->query($query, $proj_dom);
                        
                if($result_proj != null){
                    $project_name = $result_proj->item(0)->nodeValue;
                }
                        
                $query = "//oc:metadata/dc:creator";
                $result_create = $xpath->query($query, $proj_dom);
                $author_array = array();	
                        
                foreach($result_create AS $res_creators){
                    $author_array[] = $res_creators->nodeValue;
                }
                
                $query = "//oc:metadata/dc:contributor";
                $result_contrib = $xpath->query($query, $proj_dom);	
                $contributor_array = array();
                        
                foreach($result_contrib AS $act_contrib){
                    $contributor_array[] = $act_contrib->nodeValue;
                }
        
                $query = "//oc:metadata/dc:title";
                $result_dctitle = $xpath->query($query, $proj_dom);	
                     
                if($result_dctitle!= null){
                    $project_title = $result_dctitle->item(0)->nodeValue;
                }
        
                $query = "//oc:metadata/dc:identifier";
                $result_nodes = $xpath->query($query, $proj_dom);	
                     
                if($result_nodes!= null){
                    $identifierURI = $result_nodes->item(0)->nodeValue;
                }
        
                $query = "//oc:manage_info/oc:mediaCount";
                $result_media = $xpath->query($query, $proj_dom);		
                     
                if($result_media!= null){
                    $mediaCount = $result_media->item(0)->nodeValue;
                }
        
                $query = "//oc:manage_info/oc:diaryCount";
                $result_diary = $xpath->query($query, $proj_dom);		
                     
                if($result_media!= null){
                    $diaryCount = $result_diary->item(0)->nodeValue;
                }
        
                $query = "//oc:manage_info/oc:projGeoPoly";
                $result_poly = $xpath->query($query, $proj_dom);		
                
                $proj_poly = null;
                     
                if($result_poly!= null){
                        if($result_poly->item(0)){    
                                 $proj_poly = $result_poly->item(0)->nodeValue;
                        }
                }
                
                $query = "//oc:manage_info/oc:projGeoPoint";
                $result_point = $xpath->query($query, $proj_dom);		
                     
                if($result_poly!= null){
                    $proj_point = $result_point->item(0)->nodeValue;
                }
                
        
        
                //done querying old xml version
                
                $proj_entry_title = $project_title;
                $proj_feed_title = "Open Context Sub-Project Record: ".$proj_item_name;
                
                //echo "<br/>".$proj_feed_title."<br/>".$proj_entry_title."<br/>";
                
                
                $atomFullDoc = new DOMDocument("1.0", "utf-8");
                
                $root = $atomFullDoc->createElementNS("http://www.w3.org/2005/Atom", "feed");
                
                // add newlines and indent the output - this is at least useful for debugging and making the output easier to read
                $atomFullDoc->formatOutput = true;
                
                $root->setAttribute("xmlns:georss", "http://www.georss.org/georss");
                $root->setAttribute("xmlns:gml", "http://www.opengis.net/gml");
               
                $atomFullDoc->appendChild($root);
        
                // Feed Title 
                $feedTitle = $atomFullDoc->createElement("title");
                $feedTitleText = $atomFullDoc->createTextNode($proj_feed_title);
                $feedTitle->appendChild($feedTitleText);
                $root->appendChild($feedTitle);
                
                // Feed updated element (as opposed to the entry updated element)
                $feedUpdated = $atomFullDoc->createElement("updated");
                // Retrieve the current date and time. Format it in RFC 3339 format. Store it in a text node 
                $feedUpdatedText = $atomFullDoc->createTextNode(date("Y-m-d\TH:i:s\-07:00"));   
                // Append the text node the updated element
                $feedUpdated->appendChild($feedUpdatedText);
                // Append the updated node to the root element
                $root->appendChild($feedUpdated);
                
                $linkURI = $identifierURI. ".atom";
                // feed (self) link element
                $feedLink = $atomFullDoc->createElement("link");
                $feedLink->setAttribute("rel", "self");
                $feedLink->setAttribute("href", $linkURI);
                $root->appendChild($feedLink);
                
                // feed id
                $feedId = $atomFullDoc->createElement("id");
                $feedIdText = $atomFullDoc->createTextNode($identifierURI);
                $feedId->appendChild($feedIdText);
                $root->appendChild($feedId);
                
                
                $feed_entry = $atomFullDoc->createElement("entry");
                $root->appendChild($feed_entry);
                
                $entryCat = $atomFullDoc->createElement("category");
                $entryCat->setAttribute("term", "subproject_overview");
                $feed_entry->appendChild($entryCat);
                
                
                $entry_title_el = $atomFullDoc->createElement("title");
                $entry_title_text = $atomFullDoc->createTextNode($proj_entry_title);
                $entry_title_el->appendChild($entry_title_text);
                $feed_entry->appendChild($entry_title_el);
                
                $entry_id_el = $atomFullDoc->createElement("id");
                $entry_id_text = $atomFullDoc->createTextNode($identifierURI);
                $entry_id_el->appendChild($entry_id_text);
                $feed_entry->appendChild($entry_id_el);
                
                // Feed updated element (as opposed to the entry updated element)
                $entryUpdated = $atomFullDoc->createElement("updated");
                // Retrieve the current date and time. Format it in RFC 3339 format. Store it in a text node 
                $entryUpdatedText = $atomFullDoc->createTextNode(date("Y-m-d\TH:i:s\-07:00"));   
                // Append the text node the updated element
                $entryUpdated->appendChild($entryUpdatedText);
                // Append the updated node to the root element
                $feed_entry->appendChild($entryUpdated);
                
                
                //create GeoRSS information for the item
                $entry_geo = $atomFullDoc->createElement("georss:point");
                $entry_geo_val = $atomFullDoc->createTextNode($proj_point);
                $entry_geo->appendChild($entry_geo_val);
                $feed_entry->appendChild($entry_geo);
                
                if($proj_poly != null){
                        $entry_geo_all_poly = $atomFullDoc->createElement("georss:where");
                        $entry_geo_poly = $atomFullDoc->createElement("gml:Polygon");
                        $entry_geo_ext = $atomFullDoc->createElement("gml:exterior");
                        $entry_geo_lr = $atomFullDoc->createElement("gml:LinearRing");
                        $entry_geo_pos = $atomFullDoc->createElement("gml:posList");
                        $entry_geo_poly_val = $atomFullDoc->createTextNode($proj_poly);
                        $entry_geo_pos->appendChild($entry_geo_poly_val);
                        $entry_geo_lr->appendChild($entry_geo_pos);
                        $entry_geo_ext->appendChild($entry_geo_lr);
                        $entry_geo_poly->appendChild($entry_geo_ext);
                        $entry_geo_all_poly->appendChild($entry_geo_poly);
                        $feed_entry->appendChild($entry_geo_all_poly);
                }
                
                
                foreach($author_array AS $act_creator){
                    $author_el = $atomFullDoc->createElement("author");
                    $name_el = $atomFullDoc->createElement("name");
                    $name_text = $atomFullDoc->createTextNode($act_creator);
                    $name_el->appendChild($name_text);
                    $author_el->appendChild($name_el);
                    $feed_entry->appendChild($author_el);
                }
                
                foreach($contributor_array AS $act_contrib){
                    $author_el = $atomFullDoc->createElement("contributor");
                    $name_el = $atomFullDoc->createElement("name");
                    $name_text = $atomFullDoc->createTextNode($act_creator);
                    $name_el->appendChild($name_text);
                    $author_el->appendChild($name_el);
                    $feed_entry->appendChild($author_el);
                }
                    
                $content_el = $atomFullDoc->createElement("content");
                $content_el->setAttribute("type", "xhtml");
                
                $content_div_text =
                '
                <div xmlns="http://www.w3.org/1999/xhtml">
                <h2>'.$proj_item_name.'</h2>
                <p>Number of associated media items: ('.$mediaCount.') Number of Associated Narrative Texts: ('.$diaryCount.')</p><br/>
                <p><strong>Description of this Project / Collection Component:</strong></p>
                '.$long_des.' 
                </div>
                ';
                
               // echo $content_div_text;
                
                // add the XHTML content string
                $contentFragment = $atomFullDoc->createDocumentFragment();
                $contentFragment->appendXML($content_div_text);  // $atom_content from short atom entry
                $content_el->appendChild($contentFragment);
                $feed_entry->appendChild($content_el);
                
                //now add ArchaeoML String
                $proj_archaeoML = str_replace('<?xml version="1.0"?>', "", $archaeoML);
                $arch_contentFragment = $atomFullDoc->createDocumentFragment();
                $arch_contentFragment->appendXML($proj_archaeoML);
                $feed_entry->appendChild($arch_contentFragment);
                
                $atom_xml_string = $atomFullDoc->saveXML();
                
                $atom_xml_string = str_replace("<default:", "<", $atom_xml_string);
                $atom_xml_string = str_replace("</default:", "</", $atom_xml_string);
                $atom_xml_string = str_replace('<content xmlns:default="http://www.w3.org/1999/xhtml"', "<content ", $atom_xml_string);
         
                
                
                
                $db_params = OpenContext_OCConfig::get_db_config();
                $db = new Zend_Db_Adapter_Pdo_Mysql($db_params);
                               
                $db->getConnection();
                
                $data = array(
                        'atom' => $atom_xml_string
                    );
                
                $where[] = 'project_id = "'.$project_id.'"';
                $where[] = 'sub_id = "'.$class_name.'"';
                
                
                $n = $db->update('subprojects', $data, $where);
                
                $db->closeConnection();
         
         
                return $atom_xml_string;
        }        
        
        
        
        	
	public static function project_atom_feed($proj_atom_string, $view_count, $sp_view_count, $rank, $class_name){
		//$proj_atom_string is a string object of Atom XML data stored in the MySQL database
		
		$host = OpenContext_OCConfig::get_host_config();
		
		$proj_dom = new DOMDocument("1.0", "utf-8");
                $proj_dom->loadXML($proj_atom_string);
                    
                    
                $xpath = new DOMXpath($proj_dom);
		
                    // Register OpenContext's namespace
                $xpath->registerNamespace("arch", self::arch_ns_uri);
                $xpath->registerNamespace("oc", self::oc_ns_uri);
                $xpath->registerNamespace("dc", self::dc_ns_uri);
                $xpath->registerNamespace("atom", self::atom_ns_uri);
                          
                
		
		$query = "//arch:project";
		$result_arch = $xpath->query($query, $proj_dom);
                if($result_arch != null){
			$arch_node = $result_arch->item(0);
                }
		
		$social_node = $proj_dom->createElement("oc:social_usage");
		$spview_node = $proj_dom->createElement("oc:item_views");
		$spview_node->setAttribute("type", "spatialCount");
		$spcount_node = $proj_dom->createElement("oc:count");
		$spcount_node ->setAttribute("rank", $rank["rank"]);
		$spcount_node ->setAttribute("pop", $rank["pop"]);
		$spcount_node_val  = $proj_dom->createTextNode($sp_view_count);
		$spcount_node->appendChild($spcount_node_val);
		$spview_node->appendChild($spcount_node);
		$social_node->appendChild($spview_node);
		
		$view_node = $proj_dom->createElement("oc:item_views");
		$view_node->setAttribute("type", "self");
		$count_node = $proj_dom->createElement("oc:count");
		$count_node_val  = $proj_dom->createTextNode($view_count);
		$count_node->appendChild($count_node_val);
		$view_node->appendChild($count_node);
		$social_node->appendChild($view_node);
		
		$arch_node->appendChild($social_node);
		
		$query = "/atom:feed";      
                $proj_dom_root = $xpath->query($query, $proj_dom);      
                            
                $query = "//arch:project/arch:name/arch:string";
                $result_title = $xpath->query($query, $proj_dom);
                if($result_title != null){
			$proj_item_name = $result_title->item(0)->nodeValue;
                }
                
		$proj_root_path = "/";
		$query = "//oc:manage_info/oc:rootPath";
                $result_path = $xpath->query($query, $proj_dom);
                if($result_path != null){
			$proj_root_path = $result_path->item(0)->nodeValue;
                }
                
                $query = "//oc:metadata/oc:project_name";
                $result_nodes = $xpath->query($query, $proj_dom);
                if($result_nodes != null){
			$proj_original_name = $result_nodes->item(0)->nodeValue;
                }
		    
                $proj_query_name = urlencode(OpenContext_UTF8::charset_decode_utf_8($proj_original_name));
                    
                $uri_to_query = $host."/sets/facets".$proj_root_path.".atom?proj=".$proj_query_name;
                $uri_to_query .= "&cat=".(urlencode(OpenContext_UTF8::charset_decode_utf_8($class_name)));
                
                //echo $uri_to_query;
                    
                $proj_feed_xml = file_get_contents($uri_to_query);
                    
                $proj_feed_dom = new DOMDocument("1.0", "utf-8");
                $proj_feed_dom->loadXML($proj_feed_xml);
                    
                $xpath_feed = new DOMXpath($proj_feed_dom);
		
                // Register OpenContext's namespace
                $xpath_feed->registerNamespace("atom", self::atom_ns_uri);
                $xpath_feed->registerNamespace("arch", self::arch_ns_uri);
                $xpath_feed->registerNamespace("oc", self::oc_ns_uri);
                $xpath_feed->registerNamespace("dc", "http://purl.org/dc/elements/1.1/");

                $query = "/atom:feed/atom:entry";
                $result_entries = $xpath_feed->query($query, $proj_feed_dom);
                $nodecount = 0;
                    
                foreach($result_entries AS $sum_entry){
			
			$entry_cat = $sum_entry->getElementsByTagNameNS(self::atom_ns_uri , "category");
			//$entry_category = $entry_cat->item(0)->getAttributeNS("http://www.w3.org/2005/Atom" , "term");
			$entry_category = $entry_cat->item(0)->getAttribute("term");
			//$entry_category = $sum_entry->getAttribute("term");
			if($entry_category != "project"){
				$new_node = $proj_dom->importNode($sum_entry, true);
				
				if($entry_category == "category"){
					
					$class_label_nl = $sum_entry->getElementsByTagNameNS(self::atom_ns_uri , "title");
					$class_name = $class_label_nl->item(0)->nodeValue;
					$class_icon = OpenContext_ProjectAtomJson::class_icon_lookup($class_name);
					
					$class_node = $proj_dom->createElementNS(self::oc_ns_uri, "oc:item_class");
					$class_label_node = $proj_dom->createElementNS(self::oc_ns_uri, "oc:name");
					$class_label_node_val  = $proj_dom->createTextNode($class_name);
					$class_label_node->appendChild($class_label_node_val);
					$class_node->appendChild($class_label_node);
					$class_icon_node = $proj_dom->createElementNS(self::oc_ns_uri, "oc:iconURI");
					$class_icon_node_val = $proj_dom->createTextNode($class_icon);
					$class_icon_node->appendChild($class_icon_node_val);
					$class_node->appendChild($class_icon_node);
					$new_node->appendChild($class_node);
                                
				}
				
				if($entry_category == "context"){
					
					$item_label_nl = $sum_entry->getElementsByTagNameNS(self::atom_ns_uri , "title");
					$item_name = $item_label_nl->item(0)->nodeValue;
					
					$query = "//arch:links/oc:space_links/oc:link[@project_root='".$item_name."']/oc:item_class/oc:name";
					$result_class = $xpath->query($query, $proj_dom);
					if($result_class != null){
						$class_name = $result_class->item(0)->nodeValue;
					}
					
					$query = "//arch:links/oc:space_links/oc:link[@project_root='".$item_name."']/oc:item_class/oc:iconURI";
					$result_class_icon = $xpath->query($query, $proj_dom);
					if($result_class_icon != null){
						$class_icon = $result_class_icon->item(0)->nodeValue;
						
						if(substr_count($class_icon, "http://")<1){
							$class_icon = (self::path_to_class_icon).$class_icon;
						}
						
					}
					
					$class_node = $proj_dom->createElementNS(self::oc_ns_uri, "oc:item_class");
					$class_label_node = $proj_dom->createElementNS(self::oc_ns_uri, "oc:name");
					$class_label_node_val  = $proj_dom->createTextNode($class_name);
					$class_label_node->appendChild($class_label_node_val);
					$class_node->appendChild($class_label_node);
					$class_icon_node = $proj_dom->createElementNS(self::oc_ns_uri, "oc:iconURI");
					$class_icon_node_val = $proj_dom->createTextNode($class_icon);
					$class_icon_node->appendChild($class_icon_node_val);
					$class_node->appendChild($class_icon_node);
					$new_node->appendChild($class_node);
				}
				
				
				$proj_dom_root->item(0)->appendChild($new_node);
			}
                        //$nodecount ++;
                }
                    
                $xml_string = $proj_dom->saveXML();
                    
                $xml_string = str_replace("<default:", "<", $xml_string);
                $xml_string = str_replace("</default:", "</", $xml_string);
                $xml_string = str_replace('<entry xmlns:default="http://www.w3.org/1999/xhtml" xmlns:oc="http://about.opencontext.org/schema/person_schema_v1.xsd">', chr(13)."<entry>".chr(13), $xml_string);
		$xml_string = str_replace('<entry xmlns:default="http://www.w3.org/1999/xhtml">', chr(13)."<entry>".chr(13), $xml_string);

		    
		return $xml_string;
		
	}//end function
	        
        
        
	public static function class_icon_lookup($class_name){
		
		$class_icon_uri = false;
		
		$db_params = OpenContext_OCConfig::get_db_config();
                $db = new Zend_Db_Adapter_Pdo_Mysql($db_params);
		$db->getConnection();
                $sql = 'SELECT sp_classes.sm_class_icon
                    FROM sp_classes
                    WHERE sp_classes.class_label LIKE "'.$class_name.'"
                    LIMIT 1';
		
                $result = $db->fetchAll($sql, 2);
		$db->closeConnection();
		
		if($result){
                    $class_icon_uri = $result[0]["sm_class_icon"];
		    $class_icon_uri = (self::path_to_class_icon).$class_icon_uri;
		}
		
		return $class_icon_uri;
	}//end function
	
        
        
        //this function is used to add find all the variables associated with a given class in this project
        public static function class_variable_summary($class_name, $proj_id){
               $db_params = OpenContext_OCConfig::get_db_config();
                $db = new Zend_Db_Adapter_Pdo_Mysql($db_params);
		$db->getConnection();
                
                $sql = 'SELECT var_tab.variable_uuid, var_tab.var_label, var_tab.var_type
                    FROM sp_classes
                    JOIN space ON sp_classes.class_uuid = space.class_uuid
                    JOIN observe ON space.uuid = observe.subject_uuid
                    JOIN properties ON observe.property_uuid = properties.property_uuid
                    JOIN var_tab ON properties.variable_uuid = var_tab.variable_uuid
                    WHERE sp_classes.class_label LIKE "'.$class_name.'"
                    AND space.project_id = "'.$proj_id.'"
                    GROUP BY var_tab.var_label';
		
                $result = $db->fetchAll($sql, 2);
		if($result){
                               
                }
                $db->closeConnection();
                
                return $result;
        }//end function
        
        
        
        public static function subproject_atom_to_json($project_atom_string){
		//$atom_entry_string is a string object of Atom XML data stored in the MySQL database
		
		$host = OpenContext_OCConfig::get_host_config();
		
		$project_dom = new DOMDocument("1.0", "utf-8");
                $project_dom->loadXML($project_atom_string);
                    
                    
                $xpath = new DOMXpath($project_dom);
		
                    // Register OpenContext's namespace
                $xpath->registerNamespace("arch", self::arch_ns_uri);
                $xpath->registerNamespace("oc", self::oc_ns_uri);
                $xpath->registerNamespace("dc", "http://purl.org/dc/elements/1.1/");
                $xpath->registerNamespace("atom", self::atom_ns_uri);
                $xpath->registerNamespace("georss", self::geo_ns_uri);
        
                $query = "//arch:project/arch:name/arch:string";
                $result_title = $xpath->query($query, $project_dom);
                if($result_title != null){
			$project_name = $result_title->item(0)->nodeValue;
                }
                
                $query = "//arch:project/@UUID";
                $result_id = $xpath->query($query, $project_dom);
                if($result_id != null){
			$projectLink = $host."/projects/".($result_id->item(0)->nodeValue);
                }
                
                $query = "//arch:notes/arch:note[@type='short_des']/arch:string";
                $result_shortdes = $xpath->query($query, $project_dom);
                if($result_shortdes != null){
			$projectShortDes = $result_shortdes->item(0)->nodeValue;
                }
                
                $query = "//arch:notes/arch:note[@type='long_des']/arch:string";
                $result_longdes = $xpath->query($query, $project_dom);
                if($result_longdes != null){
			$projectLongDes = strip_tags(html_entity_decode($result_longdes->item(0)->nodeValue));
                }
                
                
                //$projectLongDes = "blank for now";
                $projectDes = array("short"=> $projectShortDes, "long"=> $projectLongDes);
                
                $query = "//oc:item_views[@type='spatialCount']/oc:count";
                $result_count = $xpath->query($query, $project_dom);
                if($result_count != null){
			$space_count = $result_count->item(0)->nodeValue;
                        $space_count = $space_count + 0;
                }
                
                $query = "//oc:item_views[@type='spatialCount']/oc:count/@rank";
                $result_rank = $xpath->query($query, $project_dom);
                if($result_rank != null){
			$space_rank = $result_rank->item(0)->nodeValue;
                        $space_rank = $space_rank + 0;
                }
                
                $query = "//oc:item_views[@type='spatialCount']/oc:count/@pop";
                $result_pop = $xpath->query($query, $project_dom);
                if($result_pop != null){
			$project_pop = $result_pop->item(0)->nodeValue;
                        $project_pop = $project_pop + 0;
                }
                
                
                $dc_creator_array = array();
                $query = "//oc:metadata/dc:creator";
                $result_creators = $xpath->query($query, $project_dom);
                if($result_creators != null){
			foreach($result_creators as $act_creator){
                                $act_dc_creator = $act_creator->nodeValue;
                                //echo $act_dc_creator;
                                
                                $query = "//oc:person_links/oc:link[oc:name='".$act_dc_creator."']";
                                $result_plinks = $xpath->query($query, $project_dom);
                                
                                
                                if($result_plinks != null){
                                
                                        foreach($result_plinks as $act_plink){
                                                
                                                $resultNode = $act_plink;
                                                $newDom = new DOMDocument;
                                                $newDom->appendChild($newDom->importNode($resultNode,1));
                                                
                                                $xpath_B = new DOMXpath($newDom);
                                                $xpath_B->registerNamespace("oc", self::oc_ns_uri);
                                                
                                                $query = "//oc:id";
                                                $result_name = $xpath_B->query($query, $newDom);
                                                $pers_uuid = $result_name->item(0)->nodeValue;
                                                
                                                $person_href = $host."/persons/".$pers_uuid; 
                                                $dc_creator_array[] = array("href"=>$person_href, "name"=>$act_dc_creator);
                                        
                                        }//end loop through person links
                                
                                }//end case with person links
                                
                        }//end loop     
                }//end conditional with creators                
                
                //get all dublin core metadata
                $dc_meta_array = OpenContext_ProjectAtomJson::dc_meta_to_array($project_dom, $xpath);
                //$dc_meta_array = $this->dc_meta_to_array($project_dom, $xpath);
                
                
                $query = "//atom:entry/atom:category[@term = 'context']";
                $all_result_contexts = $xpath->query($query, $project_dom);
                
                $context_array = array();
                if($all_result_contexts != null){
                        
                        foreach($all_result_contexts as $act_resultcontext){
                                
                                $resultNode = $act_resultcontext->parentNode;
                                $newDom = new DOMDocument;
                                $newDom->appendChild($newDom->importNode($resultNode,1));
                                
                                $xpath_B = new DOMXpath($newDom);
                        
                                // Register OpenContext's namespace
                                $xpath_B->registerNamespace("atom", self::atom_ns_uri);
                                $xpath_B->registerNamespace("georss", self::geo_ns_uri);
                                $xpath_B->registerNamespace("xhtml", self::xhtml_ns_uri);
                                $xpath_B->registerNamespace("kml", self::kml_ns_uri);
                                
                                $query = "//atom:title";
                                $result_name = $xpath_B->query($query, $newDom);
                                if($result_name != null){
                                        $context_name = $result_name->item(0)->nodeValue;
                                }
                                
                                $query = "//atom:link[@rel='related']/@href";
                                $result_link = $xpath_B->query($query, $newDom);
                                if($result_link != null){
                                        $context_link = $result_link->item(0)->nodeValue;
                                }
                                
                                $query = "//xhtml:span[@class='facetcount']";
                                $result_count = $xpath_B->query($query, $newDom);
                                if($result_count != null){
                                        $facet_count = $result_count->item(0)->nodeValue;
                                        $facet_count = $facet_count + 0;
                                }
                                
                                $geo_array = array();
                                $query = "//georss:point";
                                $result_geo = $xpath_B->query($query, $newDom);
                                if($result_geo != null){
                                        $geo_text = $result_geo->item(0)->nodeValue;
                                        $act_geo_array = explode(" ", $geo_text);
                                        $lat = $act_geo_array [0] + 0;
                                        $lon = $act_geo_array [1] + 0;
                                        $geo_array[] = array("lat" => $lat, "long" => $lon);
                                }
                                
                                $time_array = array();
                                $query = "//kml:TimeSpan/kml:begin";
                                $result_begin = $xpath_B->query($query, $newDom);
                                if($result_begin != null){
                                        $kml_begin = $result_begin->item(0)->nodeValue;
                                        $time_array["begin"] = $kml_begin + 0;
                                }
                                $query = "//kml:TimeSpan/kml:end";
                                $result_end = $xpath_B->query($query, $newDom);
                                if($result_end != null){
                                        $kml_end = $result_end->item(0)->nodeValue;
                                        $time_array["end"] = $kml_end + 0;
                                }
                                
                                $context_array[] = array("name" => $context_name,
                                                         "href" => $context_link,
                                                         "item_count" => $facet_count,
                                                         "geopoint" => $geo_array,
                                                         "timespan" => $time_array
                                                         );
                        
                        }//end loop through contexts
                }//end contexts
                
                
                
                
                
                //project people
                $query = "//atom:entry/atom:category[@term = 'related person']";
                $all_result_projects = $xpath->query($query, $project_dom);
                
                $project_array = array();
                if($all_result_projects != null){
                        
                        foreach($all_result_projects as $act_resultproject){
                                
                                $resultNode = $act_resultproject->parentNode;
                                $newDom = new DOMDocument;
                                $newDom->appendChild($newDom->importNode($resultNode,1));
                                
                                $xpath_B = new DOMXpath($newDom);
                        
                                // Register OpenContext's namespace
                                $xpath_B->registerNamespace("atom", self::atom_ns_uri);
                                $xpath_B->registerNamespace("xhtml", self::xhtml_ns_uri);
                                
                                $query = "//atom:title";
                                $result_name = $xpath_B->query($query, $newDom);
                                if($result_name != null){
                                        $person_name = $result_name->item(0)->nodeValue;
                                }
                        
                                $query = "//atom:link[@rel='related']/@href";
                                $result_link = $xpath_B->query($query, $newDom);
                                if($result_link != null){
                                        $person_link = $result_link->item(0)->nodeValue;
                                }
                                
                                $query = "//xhtml:span[@class='facetcount']";
                                $result_count = $xpath_B->query($query, $newDom);
                                if($result_count != null){
                                        $facet_count = $result_count->item(0)->nodeValue;
                                        $facet_count = $facet_count + 0;
                                }
                                
                                $project_array[] = array("name" => $person_name,
                                                         "href" => $person_link,
                                                         "item_count" => $facet_count
                                                         );
                        }//end loop through projects
                        
                }//end case with projects
                
                
                //categories
                $query = "//atom:entry/atom:category[@term = 'category']";
                $all_result_categories = $xpath->query($query, $project_dom);
                
                
                $category_array = array();
                if($all_result_categories != null){
                        
                        foreach($all_result_categories as $act_resultcategory){
                                
                                $resultNode = $act_resultcategory->parentNode;
                                $newDom = new DOMDocument;
                                $newDom->appendChild($newDom->importNode($resultNode,1));
                                
                                $xpath_B = new DOMXpath($newDom);
                        
                                // Register OpenContext's namespace
                                $xpath_B->registerNamespace("atom", self::atom_ns_uri);
                                $xpath_B->registerNamespace("oc", self::oc_ns_uri);
                                $xpath_B->registerNamespace("xhtml", self::xhtml_ns_uri);
                                
                                $query = "//atom:title";
                                $result_name = $xpath_B->query($query, $newDom);
                                if($result_name != null){
                                        $cat_name = $result_name->item(0)->nodeValue;
                                }
                        
                                $query = "//atom:link[@rel='related']/@href";
                                $result_link = $xpath_B->query($query, $newDom);
                                if($result_link != null){
                                        $cat_link = $result_link->item(0)->nodeValue;
                                }
                                
                                $query = "//xhtml:span[@class='facetcount']";
                                $result_count = $xpath_B->query($query, $newDom);
                                if($result_count != null){
                                        $facet_count = $result_count->item(0)->nodeValue;
                                        $facet_count = $facet_count + 0;
                                }
                                
                                $query = "//oc:iconURI";
                                $result_icon = $xpath_B->query($query, $newDom);
                                if($result_icon != null){
                                        $icon_link = $result_icon->item(0)->nodeValue;
                                }
                                
                                $category_array[] = array("name" => $cat_name,
                                                         "href" => $cat_link,
                                                         "item_count" => $facet_count,
                                                         "icon_href" => $icon_link,
                                                         );
                        }//end loop through categories
                        
                }//end case with categories
                
                
                //items with media
                $query = "//atom:entry/atom:category[@term = 'items with media']";
                $all_result_media = $xpath->query($query, $project_dom);
                
                $media_array = array();
                if($all_result_media != null){
                        
                        foreach($all_result_media as $act_resultmedia){
                                
                                $resultNode = $act_resultmedia->parentNode;
                                $newDom = new DOMDocument;
                                $newDom->appendChild($newDom->importNode($resultNode,1));
                                
                                $xpath_B = new DOMXpath($newDom);
                        
                                // Register OpenContext's namespace
                                $xpath_B->registerNamespace("atom", self::atom_ns_uri);
                                $xpath_B->registerNamespace("xhtml", self::xhtml_ns_uri);
                                
                                $query = "//atom:title";
                                $result_name = $xpath_B->query($query, $newDom);
                                if($result_name != null){
                                        $cat_name = $result_name->item(0)->nodeValue;
                                        $cat_name = str_replace("Diary", "Textual documentation", $cat_name);
                                }
                        
                                $query = "//atom:link[@rel='related']/@href";
                                $result_link = $xpath_B->query($query, $newDom);
                                if($result_link != null){
                                        $cat_link = $result_link->item(0)->nodeValue;
                                }
                                
                                $query = "//xhtml:span[@class='facetcount']";
                                $result_count = $xpath_B->query($query, $newDom);
                                if($result_count != null){
                                        $facet_count = $result_count->item(0)->nodeValue;
                                        $facet_count = $facet_count + 0;
                                }
                                
                                
                                $media_array[] = array("name" => $cat_name,
                                                         "href" => $cat_link,
                                                         "item_count" => $facet_count
                                                         );
                        }//end loop through categories
                        
                }//end case with categories
                
                
                $image_cat_array = array();
                //go through links for each category to see if there are images associated
                foreach($category_array AS $actCatagory){
                        $light_cat_link = str_replace("/sets/", "/lightbox/", $actCatagory["href"]);
                        $light_cat_link = str_replace("/?", ".json?", $light_cat_link);
                        $light_cat_link = str_replace("lightbox.json?", "lightbox/.json?", $light_cat_link);
                        $image_cat_string = file_get_contents($light_cat_link);
                        $image_cat_obj = Zend_Json::decode($image_cat_string);
                        if($image_cat_obj["total"] > 0){
                                
                                $act_image_cat = array("category"=> $actCatagory["name"],
                                                        "icon_href"=> $actCatagory["icon_href"],
                                                        "item_count"=> ($image_cat_obj["total"]+0),
                                                        "lightbox_links" => $image_cat_obj["links"],
                                                        "examples"=> $image_cat_obj["items"]);
                                $image_cat_array[] = $act_image_cat;
                        }
                        
                }//end loop through links of light box
                
                
                
                
                
                
                $output_array = array("name"=> $project_name,
                                      "href"=> $projectLink,
                                      "item_view_count" => $space_count,
                                      "rank" => $space_rank,
                                      "of_pop" => $project_pop,
                                      "dc_metadata" => $dc_meta_array,
                                      "descriptions" => $projectDes,
                                      "contexts" => $context_array,
                                      "categories" => $category_array,
                                      "media" => $media_array,
                                      "images" => $image_cat_array,
                                      "main_people" => $dc_creator_array
                                      );
        
                $output = Zend_Json::encode($output_array);
                
                //return $output_array;
                return $output ;
        
        }//end function
        
        public static function dc_meta_to_array($project_dom, $xpath){
                
                $dc_meta_array = array();
                $dc_tags = array("creator",
                                "subject",
                                "coverage",
                                "contributor",
                                "date",
                                "description",
                                "format",
                                "identifier",
                                "language",
                                "rights",
                                "title",
                                "type"
                                ); //list of all the dublin core elemenets
                
                foreach($dc_tags AS $act_element){
                        $query = "//oc:metadata/dc:".$act_element;
                        $result_meta = $xpath->query($query, $project_dom);
                        if($result_meta != null){
                                foreach($result_meta as $act_value){
                                        $act_dc_value = $act_value->nodeValue;
                                        $dc_meta_array[] = array("element"=> $act_element, "value" => $act_dc_value);
                                }
                        }
                }
                
                return $dc_meta_array;
        }
        
}//end class declaration

?>
