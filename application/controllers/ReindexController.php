<?php
/** Zend_Controller_Action */
//require_once 'Zend/Controller/Action.php';
ini_set("memory_limit", "2048M");
// set maximum execution time to no limit
ini_set("max_execution_time", "0");


class reindexController extends Zend_Controller_Action {
    
    
    public function spaceAction() {
        
        $this->_helper->viewRenderer->setNoRender();
        
        $uuid = $this->_request->getParam('id');
        $type = "spatialunit";
        $output = "none";
        
        $host = OpenContext_OCConfig::get_host_config();
        
        $db_params = OpenContext_OCConfig::get_db_config();
        $db = new Zend_Db_Adapter_Pdo_Mysql($db_params);
                
	$db->getConnection();
        
        
        if(($type == "spatialunit")&&($uuid != false)){
            $sql = 'SELECT space.xml_full, space.uuid, space.space_label
                FROM space
                WHERE space.uuid = "'.$uuid.'" ';
        
            //echo $sql;
            $result = $db->fetchAll($sql, 2);
            
            if($result){
                $atom_string = $result[0]["xml_full"];
                $atom_string = str_replace('http://www.opencontext.org/database/space.php?item=', 'http://www.opencontext.org/subjects/', $atom_string);
                $atom_string = str_replace('http://ishmael.ischool.berkeley.edu/subjects/', 'http://www.opencontext.org/subjects/', $atom_string);
                $atom_string = str_replace('http://about.opencontext.org/schema/space_schema_v1.xsd', 'http://www.opencontext.org/database/schema/space_schema_v1.xsd', $atom_string);
                
                $atomXML = simplexml_load_string($atom_string);
                
                $solr = new Apache_Solr_Service('localhost', 8983, '/solr');
                
                $output = array("uuid"=>$result[0]["uuid"], "name"=>$result[0]["space_label"]);
                $output["error"] = false;
                
		// test the connection to the solr server
		if ($solr->ping()) { // if we can ping the solr server...
			//echo "connected to the solr server...<br/><br/>";
		} else {
			$output["error"] = "Solr down";
		}
                
                $solrDocument = OpenContext_SolrIndexer::reindex_item($atomXML);
                
                try{
                    $solr->addDocument($solrDocument);
                }
                catch (Exception $e) {
                    $output["error"] = $e;
                    return Zend_Json::encode($output);
                }
                
                try{
                    // and commit it
                    $solr->commit();
                }
                catch (Exception $e) {
                    $output["error_com"] = $e;
                    return Zend_Json::encode($output);
                }
                
                
            }//END CASE with data
        }//end check on spatial 
        
        $db->closeConnection();
        echo Zend_Json::encode($output);
    }//end funciton
    
    
    public function indexAction() {
    
        $uuid = $this->_request->getParam('id');
        $type = $this->_request->getParam('type');
        
        if(!$type){
            $type = "spatialunit";
        }
        
        $host = OpenContext_OCConfig::get_host_config();
        
        $db_params = OpenContext_OCConfig::get_db_config();
        $db = new Zend_Db_Adapter_Pdo_Mysql($db_params);
                
	$db->getConnection();
        $this->view->allResults = false;
        
        if(($type == "spatialunit")&&($uuid != false)){
            $sql = 'SELECT space.xml_full, space.uuid, space.space_label
                FROM space
                WHERE space.uuid = "'.$uuid.'" ';
        
            $result = $db->fetchAll($sql, 2);
            
            if($result){
                $atom_string = $result[0]["xml_full"];
                $atom_string = str_replace('http://www.opencontext.org/database/space.php?item=', 'http://www.opencontext.org/subjects/', $atom_string);
                $atom_string = str_replace('http://ishmael.ischool.berkeley.edu/subjects/', 'http://www.opencontext.org/subjects/', $atom_string);
                $atom_string = str_replace('http://about.opencontext.org/schema/space_schema_v1.xsd', 'http://www.opencontext.org/database/schema/space_schema_v1.xsd', $atom_string);
                
                $atomXML = simplexml_load_string($atom_string);
                
                $solr = new Apache_Solr_Service('localhost', 8983, '/solr');

		// test the connection to the solr server
		if ($solr->ping()) { // if we can ping the solr server...
			//echo "connected to the solr server...<br/><br/>";
		} else {
			die("unable to connect to the solr server. exiting...");
		}
                
                $solrDocument = OpenContext_SolrIndexer::reindex_item($atomXML);
                
                $solr->addDocument($solrDocument);
		// and commit it
		$solr->commit();
                
                $this->view->name = $result[0]["space_label"];
                $this->view->uuid = $result[0]["uuid"];
            }
        }
        
        if(($type == "resource")&&($uuid != false)){
            $sql = 'SELECT resource.res_atom,  resource.uuid,  resource.res_label
                FROM resource
                WHERE resource.uuid = "'.$uuid.'" ';
        
            $result = $db->fetchAll($sql, 2);
            
            if($result){
                $atom_string = $result[0]["res_atom"];
                $mediaItem = simplexml_load_string($atom_string);
                
                // Register OpenContext's namespace
		$mediaItem->registerXPathNamespace("oc", "http://about.opencontext.org/schema/resource_schema_v1.xsd");
		
		// Register OpenContext's namespace
		$mediaItem->registerXPathNamespace("arch", "http://ochre.lib.uchicago.edu/schema/Resource/Resource.xsd");
	
		// Register Dublin Core's namespace
		$mediaItem->registerXPathNamespace("dc", "http://purl.org/dc/elements/1.1/");
	
		// Register the GML namespace
		$mediaItem->registerXPathNamespace("gml", "http://www.opengis.net/gml");
		
		// Register the Atom namespace
		$mediaItem->registerXPathNamespace("atom", "http://www.w3.org/2005/Atom");
                
                $count_space = 0;
		$spatialItem = false;
                foreach($mediaItem->xpath("//arch:resource/arch:links/arch:docID[@type='spatialUnit']") as $linked_space) {
		    if($count_space ==0){
                        $linked_space_id = $linked_space."";
                        
                        $sql = 'SELECT space.xml_full, space.uuid, space.space_label
                        FROM space
                        WHERE space.uuid = "'.$linked_space_id.'" ';
        
                        $result_sp = $db->fetchAll($sql, 2);
            
                        if($result_sp){
                            $sp_atom_string = $result_sp[0]["xml_full"];
                            $sp_atom_string = str_replace('http://www.opencontext.org/database/space.php?item=', 'http://www.opencontext.org/subjects/', $sp_atom_string);
                            $sp_atom_string = str_replace('http://ishmael.ischool.berkeley.edu/subjects/', 'http://www.opencontext.org/subjects/', $sp_atom_string);
                
                            $spatialItem = simplexml_load_string($sp_atom_string);
                        }
                    
                    }//end case for the first spatial item
                    $count_space ++;
                }//end loop through related space items                    
                
                $solr = new Apache_Solr_Service('localhost', 8983, '/solr');

		// test the connection to the solr server
		if ($solr->ping()) { // if we can ping the solr server...
			//echo "connected to the solr server...<br/><br/>";
		} else {
			die("unable to connect to the solr server. exiting...");
		}
                
                $solrDocument = OpenContext_SolrIndexer::reindex_resource($mediaItem, $spatialItem);
                
                $solr->addDocument($solrDocument);
		// and commit it
		$solr->commit();
                
                $this->view->name = "[Media] ".($result[0]["res_label"]);
                $this->view->uuid = $result[0]["uuid"]." (Linked to: ".$linked_space_id.")";
            }
        }
        
        
        if(($type == "spatialunit")&&(strlen($uuid)<1)){
            
            $sql = "SELECT space.uuid 	
            FROM SPACE
            WHERE space.project_id != '0' AND space.project_id != '1' AND space.project_id != '2'
            ";
        
            $result = $db->fetchAll($sql, 2);
            $this->view->allResults = $result;
            
            echo "database queried";
        }
        
            
        $db->closeConnection();
                

    }//end index viewer
   

    
    
}//end class