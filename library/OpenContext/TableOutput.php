<?php

class OpenContext_TableOutput {

        //turns a tableID/partID into a cacheID
        public static function tableURL_toCacheID($tableURL){
                
                if(stristr($tableURL, "/")){
                        $tabParts = explode("/", $tableURL);
                        $output = $tabParts[1]."_".$tabParts[0];
                }
                else{
                      $output =  $tableURL;
                }
                
                return $output;
        }
        

        //turns a cacheID for a multipart table into a nice looking URL (with slash between different parts)
        public static function tableID_toURL($tableID){
    
                if(stristr($tableID, "_")){
                  $tabParts = explode("_", $tableID);
                  $output = $tabParts[1]."/".$tabParts[0];
                }
                else{
                  $output = $tableID;
                }
                
                return $output;	
        }//end function
  
  
        //gets the cache_id for a table ID and Part ID passed from a GET request
        public static function tableID_part($tableID, $partID){
                if($partID != false && $partID != 1){
                        $tableID = $partID."_".$tableID;
                }
    
                return $tableID;
        }//end function


        public static function noid_check($tableId, $tableMetadata = false){
      
                $db_params = OpenContext_OCConfig::get_db_config();
                $db = new Zend_Db_Adapter_Pdo_Mysql($db_params);
                $db->getConnection();
                
                $tableId_url = OpenContext_TableOutput::tableID_toURL($tableId);
                  
                $sql = "SELECT noid FROM noid_bindings WHERE (itemUUID = '$tableId_url' OR  itemUUID = '$tableId') AND itemType = 'table' LIMIT 1; ";
                $resultB = $db->fetchAll($sql, 2);
                  
                if($resultB){
                  $noid = $resultB[0]['noid'];
                  if(!$tableMetadata){
                    $tableMetadata = $noid;
                  }
                  else{
                    $tableMetadata['noid'] = (string)$noid;
                  }
                }
                
                $db->closeConnection();
                return $tableMetadata;
        }



        //updates a table's metadata that a user supplied.
        public static function table_update($tableId, $table, $newTitle, $newDescription, $newTags){
                
                $doUpate = true;
                if(($table['meta']['table_name'] == $newTitle) && ($table['meta']['table_description'] == $newDescription) && ($table['meta']['tagstring'] == $newTags)){
                        $doUpate = false; //nothing changed, no need to update
                        $output = false;
                }
                
                if($doUpate){
                        $output = true;
                        $db_params = OpenContext_OCConfig::get_db_config();
                        $db = new Zend_Db_Adapter_Pdo_Mysql($db_params);
                        $db->getConnection();
                        
                        $table['meta']['table_name'] = $newTitle;
                        $table['meta']['table_description'] = $newDescription;
                        $table['meta']['tagstring'] = $newTags;
                        $tableMetadata = array();
                        $tableMetadata['meta'] = $table['meta'];
                        $jsonMetadata = Zend_Json::encode($tableMetadata);
                        $data = array('table_name' => $newTitle,
                                      'description' => $newDescription,
                                      'metadata' => $jsonMetadata);
                        $where = array();
                        $where[] = "cache_id = '$tableId'";
                        $n = $db->update('dataset', $data, $where);
                        
                }//end update
                
                return $output;
        }


        //logs errors in exports
        public static function log_error_note($errorLocation, $errorNote){
                
                $status = false;
                $db_params = OpenContext_OCConfig::get_db_config();
                $db = new Zend_Db_Adapter_Pdo_Mysql($db_params);
                $db->getConnection();
                
                $data = array(  'location' => $errorLocation,
                                'note' => $errorNote
                        );
                
                try{
                        $db->insert('dataset_errors', $data);
                        $status = true;
                }catch (Zend_Exception $e){
                        $status = $e;
                }
                
                $db->closeConnection();
                
                return $status;
        }

        
        //this function saves a finished table into the database
        public static function save_finished_table($Final_cache_id, $UserName, $all_set_metadata){
                
                $tableName = $all_set_metadata["meta"]["table_name"];
                $tableDescription = $all_set_metadata["meta"]["table_description"];
                $metadata = Zend_Json::encode($all_set_metadata);
                
                $db_params = OpenContext_OCConfig::get_db_config();
                $db = new Zend_Db_Adapter_Pdo_Mysql($db_params);
                $db->getConnection();
                
                $data = array(  'tableID' => $Final_cache_id,
                                'name' => $tableName,
                                'creator' => $UserName,
                                'num_recs' => $all_set_metadata["meta"]["numFound"],
                                'set_uri' => $all_set_metadata["meta"]["setURI"],
                                'set_hash' => $all_set_metadata["meta"]["setHash"],
                                'metadata' => $metadata,
                                'notes' => $tableDescription
                        );
                
                
                $data["table_num"] =  $all_set_metadata["meta"]["table_segments"]["currentTab"];
                $data["recs_per_table"] =  $all_set_metadata["meta"]["table_segments"]["recsPerTable"]; 
                $data["total_tabs"] =  $all_set_metadata["meta"]["table_segments"]["totalTabs"];
                    
                try{
                        $db->insert('tables', $data);
                        $status = "ok";
                }catch (Zend_Exception $e){
                        $status = $e;
                }
                
                $db->closeConnection();
                
                return $status;
        }

         //this function saves a finished table into the database
        public static function save_data($Final_cache_id, $UserName, $all_set_metadata){
                
                $tableName = $all_set_metadata["meta"]["table_name"];
                $tableDescription=$all_set_metadata["meta"]["table_description"];
                $table_tags=explode(" ",$all_set_metadata["meta"]["tagstring"]);
                
                $metadata = Zend_Json::encode($all_set_metadata);
                
                $db_params = OpenContext_OCConfig::get_db_config();
                $db = new Zend_Db_Adapter_Pdo_Mysql($db_params);
                $db->getConnection();
                
                $data = array(  'cache_id' => $Final_cache_id,
                                'table_name' => $tableName,
                                'created_on' => date("Y-m-d H:i:s"),
                                'created_by' => $UserName,
                                'num_records' => $all_set_metadata["meta"]["numFound"],
                                'set_uri' => $all_set_metadata["meta"]["setURI"],
                                'set_hash' => $all_set_metadata["meta"]["setHash"],
                                'metadata' => $metadata,
                                'description' => $tableDescription
                        );
                
                $data["table_num"] =  $all_set_metadata["meta"]["table_segments"]["currentTab"];
                $data["recs_per_table"] =  $all_set_metadata["meta"]["table_segments"]["recsPerTable"]; 
                $data["total_tabs"] =  $all_set_metadata["meta"]["table_segments"]["totalTabs"];
                
                $db->insert('dataset', $data);
                $created_tableid=$db->lastInsertId();
                
                $projectIDs = OpenContext_ProjectTables::assign_project_links($Final_cache_id, $all_set_metadata["meta"], $db);
                $personIDs = OpenContext_ProjectTables::assign_person_links($Final_cache_id, $all_set_metadata["meta"], $projectIDs, $db);
                
                foreach($table_tags as $tag){
                  $data=array('tag_name'=>$tag,'table_id'=>$created_tableid);
                  $db->insert('tag',$data);
                }

                $db->closeConnection();
        }
        
        
        public static function  getContexts_CleanFacets($facet_counts){
                
                
                if(array_key_exists("person_link", $facet_counts)){
                        unset($facet_counts["person_link"]);  //remove the person_link data, we don't need them
                }
                
                if(array_key_exists("creator", $facet_counts)){
                        unset($facet_counts["creator"]);  //remove the creator facet, we don't need them
                }
                
                if(array_key_exists("contributor", $facet_counts)){
                        unset($facet_counts["contributor"]);  //remove the creator facet, we don't need them
                }
                
                if(array_key_exists("contributors", $facet_counts)){
                        unset($facet_counts["contributors"]);  //remove the creator facet, we don't need them
                }
                
                if(array_key_exists("pub_date", $facet_counts)){
                        unset($facet_counts["pub_date"]);  //remove the creator facet, we don't need them
                }
                
                if(array_key_exists("update", $facet_counts)){
                        unset($facet_counts["update"]);  //remove the creator facet, we don't need them
                }
                
                $proj_array = array();
                if(array_key_exists("project_name", $facet_counts)){
                        
                        foreach($facet_counts["project_name"] as $act_proj => $val){
                                $proj_array[] = $act_proj;
                        }
                        
                        unset($facet_counts["project_name"]);  //remove the creator facet, we don't need them
                }
                 
                $proj_id_array = array();
                $proj_limit = "";
                if(array_key_exists("project_id", $facet_counts)){
                        
                        foreach($facet_counts["project_id"] as $act_proj_id => $val){
                                $proj_id_array[] = $act_proj_id;
                        }
                        
                        if(count($proj_id_array) == 1){
                                $proj_limit = " AND variable_sort.project_id = '".$proj_id_array[0]."' ";
                        }
                        
                        unset($facet_counts["project_id"]);  //remove the creator facet, we don't need them
                }
                
                $numContexts = 10;
                $def_context_array = array();
                $i=0;
                while($i<  $numContexts ){
                     $act_context = "def_context_".$i;
                     
                     if(array_key_exists($act_context, $facet_counts)){
                        $context_array = $facet_counts[$act_context];
                        if(count($context_array)>0){
                                $def_context_array[] = $act_context;
                        }
                        
                        unset($facet_counts[$act_context]);  //remove the def_context fields, we don't need them
                     }
                     
                $i++;   
                }
                
                $db_params = OpenContext_OCConfig::get_db_config();
                $db = new Zend_Db_Adapter_Pdo_Mysql($db_params);
                                       
                $db->getConnection();
                
                $sort_var = array();
                $val_max = 0;
                foreach($facet_counts as $act_var_type){
                        
                        $lastClass = false;
                        $tinyIncrementer = 0;
                        $lastRank = 0;
                        foreach($act_var_type as $active_var => $val){
                                
                                if($val > $val_max){
                                        $val_max = $val;
                                }
                                
                                $rank_add = (($val_max - $val) / $val_max)*(5);
                                
                                
                                $sql = 'SELECT var_tab.variable_uuid, var_tab.var_sort, variable_sort.sort_order, variable_sort.class_name
                                        FROM var_tab
                                        JOIN projects ON var_tab.project_id = projects.project_id
                                        LEFT JOIN variable_sort ON (variable_sort.variable_uuid = var_tab.variable_uuid '.$proj_limit.' )
                                        WHERE var_tab.project_id != "2"
                                        AND var_tab.project_id != "0"
                                        AND var_tab.var_label = "'.$active_var.'" 
                                        ORDER BY var_tab.var_sort DESC, variable_sort.sort_order, variable_sort.class_name
                                        LIMIT 1
                                        ';
                                //echo $sql."<br/><br/>";
                                        
                                $results = $db->fetchAll($sql, 2);
                                
                                $act_varSort = 0;
                                $act_class = 0;
                                $act_rank = 0;
                                foreach($results as $result){
                                       $act_varSort = $result["var_sort"];
                                       $act_class = $result["class_name"];
                                       $act_rank = $result["sort_order"];
                                }
                                
                                if($act_varSort > 0){
                                        $act_rank =  $act_varSort;
                                        $lastRank = $act_varSort;
                                }
                                else{
                                        if($lastRank > 0){
                                                $act_varSort = $lastRank + .25; //the last field was ranked specifically, so this one is likely to be next
                                                $lastRank = 0;
                                        }
                                }
                                       
                                if(!$act_rank){
                                        $act_rank = 1000; 
                                }
                                       
                                       
                                if($act_varSort < 1){
                                        $act_class_num = crc32($act_class);
                                        $act_class_num = round($act_class_num,-3);
                                        $act_rank  = $act_class_num + round($act_rank,1) + $rank_add;
                                        $act_rank  = $act_class_num + round($act_rank,1) + $rank_add;
                                }
                                else{
                                        $act_rank = $act_varSort + $tinyIncrementer;
                                }
                                
                                
                                if(!array_key_exists($active_var, $sort_var)){
                                        $sort_var[$active_var] = $act_rank;
                                }
                                
                                $tinyIncrementer = $tinyIncrementer + .01;
                                
                                
                        }//end loop through variables
                }
                
                asort($sort_var);
                $ranked_vars = array();
                $var_counter = 0;
                foreach($sort_var as $sorted_var => $val){
                        $ranked_vars[$var_counter] = $sorted_var;
                $var_counter ++;
                }
                
                $db->closeConnection();
                return array("projects" => $proj_array, "contexts"=> $def_context_array, "table_vars"=> $facet_counts, "ranked_vars" =>$ranked_vars);
        }


	public static function archaeoML_to_array($table_fields, $archaeoML_string){
		
                $output = array();
                $item_array = array();
                $item_properties = array();
                $newFields = false; // if this item has additional variables not in table_fields, add them to this
                
                
                //echo $atom_string;
                
                $spatialItem = simplexml_load_string($archaeoML_string);
                        // Register OpenContext's namespace
                $spatialItem->registerXPathNamespace("arch", OpenContext_OCConfig::get_namespace("arch", "spatial"));
                $spatialItem->registerXPathNamespace("oc", OpenContext_OCConfig::get_namespace("oc", "spatial"));
                        
                foreach($spatialItem->xpath("//arch:spatialUnit/@UUID") as $spaceid) {
                    $item_array["uuid"] = $spaceid."";
                }
                        
                        // get the item_label
                foreach ($spatialItem->xpath("//arch:spatialUnit/arch:name/arch:string") as $item_label) {
                    $item_array["label"] = $item_label."";
                }//end loop for item labels
                        
                        foreach ($spatialItem->xpath("//arch:spatialUnit/oc:metadata/oc:project_name") as $project_name) {
                    $item_array["proj"] = $project_name."";
                }
                        
                foreach($spatialItem->xpath("//arch:spatialUnit/@ownedBy") as $projid) {
                    $item_array["proj_id"] = $projid."";
                }
                        
                        // get the item class
                foreach ($spatialItem->xpath("//arch:spatialUnit/oc:item_class/oc:name") as $item_class) {
                    $item_array["cat"] = $item_class."";
                }
                
                
                
                foreach($table_fields as $act_var){
                        $item_properties[$act_var] = NULL;
                        
                        if(stristr($act_var, "http://")){
                                //check for linked data properties, if there, add them
                                $propLinkURI = str_replace("Rel: ", "", $act_var); //strip the "Rel: " out
                                foreach ($spatialItem->xpath("//oc:linkedData/oc:relationLink[@href='$propLinkURI']/oc:targetLink/@href") as $linkedURI) {
                                        $item_properties[$act_var] = $linkedURI."";
                                }  
                        }
                        
                }
                
                
                
                
                
                        //get the item contributors
                        /*
                        if($spatialItem->xpath("//dc:contributor")){
                                foreach ($spatialItem->xpath("//dc:contributor") as $item_contributor) {
                                        $contributor = $item_contributor."";
                                        if(!in_array($contributor, $pers_array)){
                                                        $pers_array = $contributor;
                                        }
                                }
                        }
                        
                        //get the item creators
                        if($spatialItem->xpath("//dc:creator")){
                                foreach ($spatialItem->xpath("//dc:creator") as $item_creator) {
                                        $creator = $item_creator."";
                                        if(!in_array($creator, $pers_array)){
                                                        $pers_array = $creator;
                                        }
                                }
                        }
                        */
                        
                        // Verify that there are properties associated with this item.
                if ($spatialItem->xpath("//arch:spatialUnit/arch:observations/arch:observation/arch:properties/arch:property")) {
                                
                                if($spatialItem->xpath("//arch:spatialUnit/arch:observations/arch:observation/@obsNumber")){
                                        $obsNums = true;
                                }
                                else{
                                        $obsNums = false;
                                }
                                
                                //count number of observation nodes
                                $obsCount = 0;
                                if($obsNums){
                                        foreach ($spatialItem->xpath("//arch:spatialUnit/arch:observations/arch:observation[@obsNumber != 100]") as $obsNode){
                                            $obsCount++;    
                                        }
                                }
                                else{
                                      $obsCount = 1;  
                                }
                                
                                
                                $varCount=0;
                                $valCount = array();
                                
                                if($obsNums){
                                        $xpathProps = "//arch:spatialUnit/arch:observations/arch:observation[@obsNumber != 100]/arch:properties/arch:property/oc:var_label";
                                }
                                else{
                                        $xpathProps = "//arch:spatialUnit/arch:observations/arch:observation/arch:properties/arch:property/oc:var_label";                   
                                }
                                
                                foreach ($spatialItem->xpath($xpathProps) as $var_label) {
                                        
                                        $varText = $var_label."";
                                        
                                        foreach ($var_label->xpath("../oc:show_val") as $show_val) {
                                                $prop_val = $show_val."";        
                                        }
                                        
                                        if(array_key_exists($varText, $valCount)){
                                                if(array_key_exists($prop_val, $valCount[$varText])){
                                                        $valCount[$varText][$prop_val] = $valCount[$varText][$prop_val]+1;
                                                }
                                                else{
                                                        $valCount[$varText][$prop_val] = 1;
                                                }
                                        }
                                        else{
                                                 $valCount[$varText] = array($prop_val => 1);
                                        }
                                        
                                }
                                
                                
                                $varCountArrray = array();
                                foreach($valCount as $key => $actValCount){
                                        $varCountArrray[$key] = count($actValCount);
                                }
                                
                                //echo var_dump($varCountArrray);
                                
                                //echo var_dump($spatialItem);
                                
                                $first=true;
                                $obsNumber = 1;
                                if($obsNums){
                                        $xpathObs =  "//arch:spatialUnit/arch:observations/arch:observation[@obsNumber != 100]";       
                                }
                                else{
                                        $xpathObs =  "//arch:spatialUnit/arch:observations/arch:observation";
                                }
                                
                                foreach ($spatialItem->xpath($xpathObs) as $obsNode){
                                        
                                        
                                        foreach ($obsNode->xpath("arch:properties/arch:property/oc:var_label") as $var_label) {
                                        
                                                $act_var_label = $var_label."";
                                                $varCount = $varCountArrray[$act_var_label];
                                                $valNumber = 0;
                                                $valPrefix = "";
                                                
                                                $varValCount = 0;
                                                foreach ($var_label->xpath("../oc:show_val") as $show_val) {
                                                        $show_val = $show_val."";
                                                        $varValCount++;
                                                }
                                                
                                                foreach ($var_label->xpath("../oc:show_val") as $show_val) {
                                                        $prop_val = $show_val."";
                                                        if(is_numeric($prop_val)){
                                                                $prop_val = $prop_val +0;
                                                        }
                                                        
                                                        if($varCount>1){
                                                                if($obsCount>1){ //this variable has more than one value in different observations
                                                                        $valPrefix_obs = "Obs:".$obsNumber;
                                                                }
                                                                else{
                                                                        $valPrefix_obs = "";
                                                                }
                                                                
                                                                if($varValCount >1){ //this variable has more than one value in this same observation
                                                                        $varPrefix_val = chr(97 + $valNumber); //lower case a
                                                                }
                                                                else{
                                                                        $varPrefix_val = "";
                                                                }
                                                                
                                                                $valPrefix = "<--".$valPrefix_obs.$varPrefix_val."--> ";
                                                                if(!$first){
                                                                        $valPrefix = " ".$valPrefix;
                                                                }
                                                        }
                                                        
                                                        if(array_key_exists($act_var_label, $item_properties)){
                                                                if($item_properties[$act_var_label] != $valPrefix.$prop_val){   
                                                                        $item_properties[$act_var_label] .= $valPrefix.$prop_val;
                                                                }
                                                        }
                                                        else{
                                                                if($newFields != false){
                                                                        if(!in_array($act_var_label, $newFields)){
                                                                                $newFields[] = $act_var_label;
                                                                        }
                                                                }
                                                                else{
                                                                        $newFields = array();
                                                                        $newFields[] = $act_var_label;
                                                                }
                                                                $item_properties[$act_var_label] = $valPrefix.$prop_val;
                                                        }
                                                        
                                                $first=false;        
                                                $valNumber++;
                                                }//end loop through values
                                        
                                        }//end loop through variables
                                        
                                $obsNumber++;
                                }//end loop through observations
                                
                                
                                $item_array["props"] = $item_properties;
                                $item_array["newFields"] = $newFields;
                                
                        }//end case with properties
                        else{
                                foreach($table_fields as $act_var){
                                        $item_properties[$act_var] = NULL;
                                }
                                $item_array["props"] = $item_properties;
                        }//end case with no properties, or observations
                
		return $item_array;
	}//end function



        
        public static function create_new_table($table_props){
                
                $db_params = OpenContext_OCConfig::get_db_config();
                $db = new Zend_Db_Adapter_Pdo_Mysql($db_params);
		$db->getConnection();
                
                $tableID = OpenContext_OCConfig::gen_UUID();
		$name = $table_props["name"];
                $user = $table_props["user"];
                $setURI = $table_props["set_uri"];
                $setHash = md5($setURI);
                $num_recs = $table_props["num_recs"];
                $filters = $table_props["filters"];
                $notes = $table_props["notes"];
                
                $data = array("tableID" => $tableID,
                              "name" => $name,
                              "creator" => $user,
                              "num_recs" => $num_recs,
                              "set_uri" => $setURI,
                              "set_hash" => $setHash,
                              "filters" => $filters,
                              "notes" => $notes
                              );
                
                $db->insert('table_fields', $data);
                $db->closeConnection();
                
                return $tableID;
                
        }//end function




        public static function store_table_vars($tableID, $act_var, $var_count = 1){
                
                $db_params = OpenContext_OCConfig::get_db_config();
                $db = new Zend_Db_Adapter_Pdo_Mysql($db_params);
		$db->getConnection();
                
                $sql = 'SELECT table_fields.field_name, table_fields.fieldID, table_fields.use_count
                    FROM table_fields
                    WHERE table_fields.field_name = "'.$act_var.'"
                    AND table_fields.tableID = "'.$tableID.'"
                    AND table_fields.type = "var"
                    ';
		
                $result = $db->fetchAll($sql, 2);
		if($result){
                        $use_count = $result[0]["use_count"];
                        $field_id = $result[0]["fieldID"];
                        $use_count = $var_count + $use_count;
                        $data = array('use_count' => $use_count);
                        $where_term = 'fieldID = '.$field_id.'
                                AND tableID = "'.$tableID.'"';
			$n = $db->update('projects', $data, $where_term);
                }//end case to update old record
                else{
                       $sql = 'SELECT max(table_fields.fieldID) AS max_id
                       FROM table_fields
                       WHERE table_fields.tableID = "'.$tableID.'"
                       ';
                       $result = $db->fetchAll($sql, 2);
                       if($result){
                                $field_id = $result[0]["max_id"];
                                $field_id++;
                       }
                       else{
                                $field_id = 1;
                       }
                       
                       $data = array('tableID' => $tableID,
                                     'fieldID' => $field_id,
                                     'type' => $var,
                                     'field_name' => $act_var,
                                     'use_count' => $var_count
                                     );
                        
                        $db->insert('table_fields', $data);
                }//end case to make new record
                
                $db->closeConnection();
                
                
        }//end store_table_fields function




        public static function atom_items_variable_sort($set_url, $atom_string, $used_uri_array){
		
		$atomFeed = simplexml_load_string($atom_string);
                // Register OpenContext's namespace
		$atomFeed->registerXPathNamespace("oc", "http://www.opencontext.org/database/schema/space_schema_v1.xsd");
		// Register OpenContext's namespace
		$atomFeed->registerXPathNamespace("arch", "http://ochre.lib.uchicago.edu/schema/spatialUnit/SpatialUnit.xsd");
		// Register Dublin Core's namespace
		$atomFeed->registerXPathNamespace("dc", "http://purl.org/dc/elements/1.1/");
		// Register the GML namespace
		$atomFeed->registerXPathNamespace("gml", "http://www.opengis.net/gml");
		// Register the Atom namespace
		$atomFeed->registerXPathNamespace("atom", "http://www.w3.org/2005/Atom");
                
                $atomFeed->registerXPathNamespace("opensearch", "http://a9.com/-/spec/opensearch/1.1/");
                
                foreach($atomFeed->xpath("//opensearch:totalResults") as $totalResults) {
                        $totalResults = $totalResults +0;
                }
                foreach($atomFeed->xpath("//opensearch:itemsPerPage") as $itemsPerPage) {
                        $itemsPerPage = $itemsPerPage +0;
                }
                $new_page_max = round(($totalResults / $itemsPerPage), 0);
                $new_page = round(rand(1,$new_page_max),0);
                
                if((rand(0,10)>5)&&($new_page > 1)){
                        //get another page
                        $new_set_url = $set_url."&page=".$new_page;
                        $atom_string = file_get_contents($new_set_url);
                        unset($atomFeed);
                        $atomFeed = simplexml_load_string($atom_string);
                        // Register OpenContext's namespace
                        $atomFeed->registerXPathNamespace("oc", "http://www.opencontext.org/database/schema/space_schema_v1.xsd");
                        // Register OpenContext's namespace
                        $atomFeed->registerXPathNamespace("arch", "http://ochre.lib.uchicago.edu/schema/spatialUnit/SpatialUnit.xsd");
                        // Register Dublin Core's namespace
                        $atomFeed->registerXPathNamespace("dc", "http://purl.org/dc/elements/1.1/");
                        // Register the GML namespace
                        $atomFeed->registerXPathNamespace("gml", "http://www.opengis.net/gml");
                        // Register the Atom namespace
                        $atomFeed->registerXPathNamespace("atom", "http://www.w3.org/2005/Atom");     
                }
                
                
                
                $host = OpenContext_OCConfig::get_host_config();
                $uri_array = array();
                foreach($atomFeed->xpath("//atom:entry/atom:id") as $spaceid) {
		    $act_uri = $spaceid.".atom";
                    if(!in_array($act_uri, $used_uri_array)){
                        $uri_array[] = $act_uri;
                        $used_uri_array[] = $act_uri;
                    }
		}
                
                unset($atomFeed);
                
                
                
                
                $var_array = array();
                foreach($uri_array as $act_uri){
                
                        $atom_archaeoml_string = file_get_contents($act_uri);
                        $spatialItem = simplexml_load_string($atom_archaeoml_string );
                        // Register OpenContext's namespace
                        $spatialItem->registerXPathNamespace("oc", "http://www.opencontext.org/database/schema/space_schema_v1.xsd");
                        // Register OpenContext's namespace
                        $spatialItem->registerXPathNamespace("arch", "http://ochre.lib.uchicago.edu/schema/spatialUnit/SpatialUnit.xsd");
                        // Register Dublin Core's namespace
                        $spatialItem->registerXPathNamespace("dc", "http://purl.org/dc/elements/1.1/");
                        // Register the GML namespace
                        $spatialItem->registerXPathNamespace("gml", "http://www.opengis.net/gml");
                        // Register the Atom namespace
                        $spatialItem->registerXPathNamespace("atom", "http://www.w3.org/2005/Atom");
                
                        
                        // get the item class
                        foreach ($spatialItem->xpath("//arch:spatialUnit/oc:item_class/oc:name") as $item_class) {
                                $item_class = $item_class."";
                        }
                        
                        // get the project id
                        foreach($spatialItem->xpath("//arch:spatialUnit/@ownedBy") as $projid) {
                                $project_id = $projid."";
                        }
                        
                        
                        // Verify that there are properties associated with this item.
                        if ($spatialItem->xpath("//arch:spatialUnit/arch:observations/arch:observation/arch:properties/arch:property")) {
                                
                                $db_params = OpenContext_OCConfig::get_db_config();
                                $db = new Zend_Db_Adapter_Pdo_Mysql($db_params);
                                $db->getConnection();
                                
                                $sort_rank = 0;
                                $previous_var = 0;
                                foreach ($spatialItem->xpath("//arch:spatialUnit/arch:observations/arch:observation/arch:properties/arch:property/oc:var_label") as $var_label) {
                        
                                        $act_var_label = $var_label."";
                                        if(!in_array($act_var_label, $var_array)){
                                                $var_array[] = $act_var_label;
                                        }
                                        
                                        foreach ($var_label->xpath("../arch:variableID") as $variableID) {
                                                $variableID = $variableID."";
                                        }
                                
                                        
                                        $act_hash_class_var = md5($project_id."_".$item_class."_".$variableID);
                                        
                                        
                                        $sql = 'SELECT variable_sort.count_samp, variable_sort.total_sort
                                        FROM variable_sort
                                        WHERE variable_sort.hash_class_var = "'.$act_hash_class_var.'"
                                        LIMIT 1
                                        ';
		
                                        $result = $db->fetchAll($sql, 2);    
                                        if($result){
                                                $old_count = $result[0]['count_samp'];
                                                $new_count = $old_count + 1;
                                                $old_total_sort = $result[0]['total_sort'];
                                                $new_total_sort = $old_total_sort + $sort_rank;
                                                $new_sort_order = $new_total_sort / $new_count;
                                                
                                                $data = array(
                                                        'sort_order' => $new_sort_order,
                                                        'count_samp' => $new_count,
                                                        'total_sort' => $new_total_sort
                                                    );
                                                
                                                $n = $db->update('variable_sort', $data, 'hash_class_var = "'.$act_hash_class_var.'"');
                                                
                                        }
                                        else{
                                        //add a new variable sort record
                                                $data = array(
                                                        'project_id' => $project_id,
                                                        'source_id' => 'computed',
                                                        'class_name' => $item_class,
                                                        'hash_class_var' => $act_hash_class_var,
                                                        'variable_uuid' => $variableID,
                                                        'sort_order' => $sort_rank,
                                                        'count_samp' => 1,
                                                        'total_sort' => $sort_rank
                                                    );
                                                
                                                $db->insert('variable_sort', $data);
                                        }
                                        
                                        $previous_var = $variableID;
                                        
                                $sort_rank ++;
                                
                                }//end loop through variables
                                
                                $db->closeConnection();
                        
                        }//end case with properties
        
                }
        
                return array("vars" => $var_array, "used" => $used_uri_array);
        
	}//end function



        public static function tableCurrentCheck($cache_id, $set_uri, $originalCount){
                
                $host = OpenContext_OCConfig::get_host_config();
                $CheckCache_id = "tabChK_".($cache_id);
                $frontendOptions = array(
                        'lifetime' => 28800, // cache lifetime, measured in seconds, 7200 = 2 hours
                        'automatic_serialization' => true
                );
                
                $backendOptions = array(
                        'cache_dir' => './cache/' // Directory where to put the cache files
                );
                
                $cache = Zend_Cache::factory('Core',
                             'File',
                             $frontendOptions,
                             $backendOptions);
                
                if(!$cache_result = $cache->load($CheckCache_id)) {
                
                        if(substr_count($set_uri,"?")>0){
                                $atomURI = str_replace("?", ".atom?", $set_uri);
                                $atomURI .= "&recs=1";
                        }
                        else{
                                $atomURI = $set_uri.".atom";
                                $atomURI .= "?recs=1";
                        }
                        $atomURI = $host.$atomURI;
                        
                        $atom_string = file_get_contents($atomURI);
                        @$atomXML = simplexml_load_string($atom_string);
                        
                        if($atomXML){
                                
                                $cache->save($atom_string, $CheckCache_id); //save result to the cache
                                $atomXML->registerXPathNamespace("atom", "http://www.w3.org/2005/Atom");
                                $atomXML->registerXPathNamespace("opensearch", "http://a9.com/-/spec/opensearch/1.1/");
                                        
                                // get the item class
                                foreach ($atomXML->xpath("//opensearch:totalResults") as $queryTotal) {
                                        $queryTotal = ($queryTotal."") + 0;
                                }
                                
                                if($originalCount != $queryTotal){
                                        //data changed!
                                        $db_params = OpenContext_OCConfig::get_db_config();
                                        $db = new Zend_Db_Adapter_Pdo_Mysql($db_params);
                                        $db->getConnection();
                                        $data = array(
                                                'current' => 'no'
                                                );
                                                                
                                        $n = $db->update('dataset', $data, 'cache_id = "'.$cache_id.'"');
                                        $db->closeConnection();
                                        $dataCurrent = false; 
                                }
                                else{
                                        $dataCurrent = true;
                                }
                        }
                        else{
                                $dataCurrent = true;
                        }
                }
                else{
                       $dataCurrent = true; 
                }
                
                return $dataCurrent;
        }





}//end class declaration

?>
