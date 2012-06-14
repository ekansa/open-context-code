<?php

class dbXML_dbSpace  {
    
    public $projectUUID;
    public $sourceID;
    public $itemUUID;
    public $label;
    
    /*
    Location / object specific
    */
    public $classID; //identifier for a class
    public $className; //name for a class
    public $largeClassIcon; //large icon for class
    public $smallClassIcon; //small icon for class
    
    public $contain_hash;
    public $children; //array of child items
    public $containment; //array of parent items, ranked top to lowest parent
    public $defaultContainOnly; //only look for 1 containment tree for parents
    
    public $observations; //array of observation data
    
    public $geoLat;
    public $geoLon;
    public $geoGML;
    public $geoKML;
    public $geoSource;
    public $geoSourceName;
    
    public $chronoArray; //array of chronological tags, handled differently from Geo because can have multiple
    
    
    public $dbName;
    public $dbPenelope;
    public $db;
    
    public function initialize($db = false){
        if(!$db){
            $db_params = OpenContext_OCConfig::get_db_config();
            $db = new Zend_Db_Adapter_Pdo_Mysql($db_params);
            $db->getConnection();
        }
        
        $this->db = $db;
        $this->children = false;
        $this->containment = false;
        $this->observations = false;
        $this->geoLat = false;
        $this->geoLon = false;
        $this->geoGML = false;
        $this->geoKML = false;
        $this->geoSource = false;
        $this->geoSourceName = false;
        $this->chronoArray = false;
    }
    
    public function getByID($id){
        
        $this->itemUUID = $id;
        $found = false;
        
        if($this->dbPenelope){
            $found = $this->pen_itemGet();
        }
        else{
            $found = $this->oc_itemGet();
        }
        
        return $found;
    }
    
    public function pen_itemGet(){
        $found = false;
        $db = $this->db;
        
        $sql = "SELECT *
        FROM w_space
        WHERE uuid = '".$this->itemUUID."' ";
        
        $result = $db->fetchAll($sql, 2);
        if($result){
            $this->projectUUID = $result[0]["fk_project_uuid"];
            $this->sourceID = $result[0]["tab_name"];
            $this->contain_hash = $result[0]["hash_fcntxt"];
            $this->classID = $result[0]["class_uuid"];
	    $this->label = $result[0]["space_label"];
            $this->classLabelGet($this->classID);
        }
        
        return $found;
    }
    
    public function oc_itemGet(){
        $found = false;
        $db = $this->db;
        
        $sql = "SELECT *
        FROM space
        WHERE uuid = '".$this->itemUUID."' ";
        
        $result = $db->fetchAll($sql, 2);
        if($result){
            $this->projectUUID = $result[0]["project_id"];
            $this->sourceID = $result[0]["source_id"];
            $this->contain_hash = $result[0]["contain_hash"];
            $this->classID = $result[0]["class_uuid"];
	    $this->label = $result[0]["space_label"];
            $this->classLabelGet($this->classID);
        }
        
        return $found;
    }
    
    //same for both penelope and open context
    public function classLabelGet($classID){
        $db = $this->db;
        
        $sql = "SELECT *
        FROM sp_classes
        WHERE class_uuid = '".$classID."'
        OR class_label = '".$classID."'
        ";
        
        $result = $db->fetchAll($sql, 2);
        if($result){
            $this->className = $result[0]["class_label"];
            $this->largeClassIcon = $result[0]["class_icon"];
            $this->smallClassIcon = $result[0]["sm_class_icon"];
        }
    }
    
    
    public function getObs(){
        
        $db = $this->db;
        
        if($this->dbPenelope){
            $sql = "SELECT DISTINCT w_observe.obs_num, w_observe.tab_name AS sourceID, obs_metadata.obs_type, obs_metadata.obs_name, obs_metadata.obs_notes
            FROM w_observe
            LEFT JOIN obs_metadata ON (w_observe.obs_num = obs_metadata.obs_num AND w_observe.tab_name = obs_metadata.tab_name)
            WHERE w_observe.subject_uuid = '".$this->itemUUID."'
            ORDER BY w_observe.obs_num
            ";
        }
        else{
            $sql = "SELECT DISTINCT observe.obs_num, observe.source_id AS sourceID, 'primary' AS obs_type, 'public site' AS obs_name, 'public site' AS obs_notes
            FROM observe
            WHERE observe.subject_uuid = '".$this->itemUUID."'
            ORDER BY observe.obs_num";
        }
        
        $result = $db->fetchAll($sql, 2);
        if($result){
            $this->observations = $result;
        }
    }
    
    
    
    
    public function getGeo(){
        $db = $this->db;
        if(!is_array($this->containment)){
            $this->getParents();
        }
        
        $checkArray = array();
        if(is_array($this->containment)){
            foreach($this->containment as $key => $parentArray){
                foreach($parentArray as $parent){
                    $checkArray[] = $parent;
                }
            }
        }
        
        $checkArray[] = $this->itemUUID; //check to see if item has geo reference

        foreach($checkArray as $parent){
            
            if($this->dbPenelope){
                $sql = "SELECT *
                FROM geo_space
                JOIN w_space ON w_space.uuid = geo_space.uuid
                WHERE geo_space.uuid = '$parent' LIMIT 1";
            }
            else{
                $sql = "SELECT *
                FROM geo_space
                JOIN space ON space.uuid = geo_space.uuid
                WHERE geo_space.uuid = '$parent' LIMIT 1";
            }
            
            //echo "/n".$sql;
            $result = $db->fetchAll($sql, 2);
            if($result){
                $this->geoLat = $result[0]["latitude"];
                $this->geoLon = $result[0]["longitude"];
                
                if(strlen($result[0]["gml_data"])>1){
                    $this->geoGML = $result[0]["gml_data"];
                }
                else{
                    $this->geoGML = false;
                }
                
                if(strlen($result[0]["kml_data"])>1){
                    $this->geoKML = $result[0]["kml_data"];
                }
                else{
                    $this->geoKML = false;
                }
                
                $this->geoSourceName = $result[0]["space_label"];
                if($parent == $this->itemUUID){
                    $this->geoSource = "self";
                }
                else{
                    $this->geoSource = $parent;
                }
            }
        }
        
    } // end function
    
    
    //get chronological information
    public function getChrono(){
        $db = $this->db;
        if(!is_array($this->containment)){
            $this->getParents();
        }
        
        $checkArray = array();
        if(is_array($this->containment)){
            foreach($this->containment as $key => $parentArray){
                foreach($parentArray as $parent){
                    $checkArray[] = $parent;
                }
            }
        }
        
        $checkArray[] = $this->itemUUID; //check to see if item has geo reference

        foreach($checkArray as $parent){
            if($this->dbPenelope){
                $sql = "SELECT *
                FROM initial_chrono_tag
                JOIN w_space ON w_space.uuid = initial_chrono_tag.uuid
                WHERE initial_chrono_tag.uuid  = '$parent' ";
            }
            else{
                $sql = "SELECT *
                FROM initial_chrono_tag
                JOIN space ON space.uuid = initial_chrono_tag.uuid
                WHERE initial_chrono_tag.uuid  = '$parent' ";
            }
            
            $result = $db->fetchAll($sql, 2);
            //$result = false;
            if($result){
                $chronoArray = array();
                foreach($result as $row){
                    
                    $chronoTag = array();
                    $chronoTag["start_time"] = $row["start_time"] +0;
                    $chronoTag["end_time"] = $row["end_time"] +0;
                    $chronoTag["label"] = $row["label"];
                    $chronoTag["note_id"] = $row["note_id"];
                    $chronoTag["public"] = $row["public"] + 0;
                    $chronoTag["creator_uuid"] = $row["creator_uuid"];
                    $chronoTag["created"] = date("Y-m-d\TH:i:s\Z", strtotime($row["created"]));
                    if($row["creator_uuid"] == "oc"){
                        $chronoTag["taggerName"]  = "Open Context Editors";
                    }
                    else{
                        $chronoTag["taggerName"]  = $row["creator_uuid"];
                    }
                   
                   $chronoTag["sourceName"] = $row["space_label"];
                    if($parent == $this->itemUUID){
                        $chronoTag["chronoSource"]  = "self";
                    }
                    else{
                        $chronoTag["chronoSource"]  = $parent;
                    }
                    $chronoArray[] = $chronoTag;
                }//end loop through results
                
                $this->chronoArray = $chronoArray;
            }
        }
        
    }
    
    
    public function getChildren(){
        if($this->dbPenelope){
            //echo "here";
            $this->pen_getChildren();
        }
        else{
            //echo 'here';
            $this->oc_getChildren();
        }
    }
    
    public function pen_getChildren(){
        $db = $this->db;
        
        $parentID = $this->itemUUID;
        
        $sql = "SELECT w_space_contain.child_uuid as itemUUID, w_space.class_uuid as classID, w_space.space_label as label
        FROM w_space_contain
        JOIN w_space ON w_space.uuid = w_space_contain.child_uuid
        WHERE w_space_contain.parent_uuid = '".$parentID."'
        ORDER BY w_space.class_uuid, w_space.space_label
        ";
        
        $result = $db->fetchAll($sql, 2);
        if($result){
            $this->children = $result;
        }
        else{
            $this->children = false;
        }
    
    }
    
    public function oc_getChildren(){
        $db = $this->db;
        
        $parentID = $this->itemUUID;
        
        $sql = "SELECT space_contain.child_uuid as itemUUID, space.class_uuid as classID, space.space_label as label
        FROM space_contain
        JOIN space ON space.uuid = space_contain.child_uuid
        WHERE space_contain.parent_uuid = '".$parentID."'
        ORDER BY space.class_uuid, space.space_label
        ";
        
        $result = $db->fetchAll($sql, 2);
        if($result){
            $this->children = $result;
        }
        else{
            $this->children = false;
        }
    
    }
    
    
    public function getParents(){
        $this->getNextParent($this->itemUUID);
    }
    
    
    public function getNextParent($actChild){
        $db = $this->db;
        
        if($this->dbPenelope){
            $sql = "SELECT parent_uuid
            FROM w_space_contain
            WHERE child_uuid = '".$actChild."'
            ";
        }
        else{
            $sql = "SELECT parent_uuid
            FROM space_contain
            WHERE child_uuid = '".$actChild."'
            ";
        }
        
        $result = $db->fetchAll($sql, 2);
        if($result){
            $oldContain = $this->containment;
            $newContain = array();
            $i = 1;
            foreach($result as $row){
                $newParent = $row["parent_uuid"];
                
                if(!stristr($newParent, "root")){ //no root items in tree
                    if(is_array($oldContain)){
                        foreach($oldContain as $treeName => $treeItems){
                            $newContain[$treeName][] =  $newParent; //add the new parent, it's at index 0, the first in list
                            foreach($treeItems as $olderItem){
                                $newContain[$treeName][] = $olderItem; //add the older parents items after the new parent
                            }
                        }
                    }
                    else{
                        $treeName= "default";
                        $newContain[$treeName][0] =  $newParent;
                    } 
                    
                    $this->containment = $newContain;
                    $this->getNextParent($newParent);
                }
                
                $i++;
                if($i>1){
                    break;
                }
            }
        }//end case with parents
    }//end function
    
    
}  
