<?php

class dbXML_dbLinks  {
    
   
    public $doRecip; //do reciprical links
   
    public $contain_hash;
    public $children; //array of child items
    public $containment; //array of parent items, ranked top to lowest parent
    public $defaultContainOnly; //only look for 1 containment tree for parents
    
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
        $this->doRecip = false;
    }
    
    public function getLinks($id){
        
        if($this->dbPenelope){
            $found = $this->pen_getFromOrigin($id);
        }
        else{
            $found = $this->oc_getFromOrigin($id);
        }
        
        return $found;
    }
    
    public function pen_getSpaceFromOrigin($id, $obs = false){
        $found = false;
        $db = $this->db;
        
        $sql = "SELECT *
        FROM w_links 
        JOIN w_space ON w_space.uuid = w_links.targ_uuid
        WHERE w_links.origin_uuid = '".$id."'
        ORDER BY 
        ";
        
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
    
    
    public function getChildren(){
        if($this->dbPenelope){
            $this->pen_getChildren();
        }
        else{
            $this->oc_getChildren();
        }
    }
    
    public function pen_getChildren(){
        $db = $this->db;
        
        $parentID = $this->itemUUID;
        
        $sql = "SELECT w_space_contain.child_uuid as itemUUID, w_space.class_uuid as classID, w_space.space_label as label
        FROM w_space_contain
        JOIN w_space ON space.uuid = w_space_contain.child_uuid
        WHERE parent_uuid = '".$parentID."'
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
