<?php
/** Zend_Controller_Action */
require_once 'Zend/Controller/Action.php';
ini_set("max_execution_time", "0");
    
class accessionController extends Zend_Controller_Action
{   
    
    public function indexAction(){
         
 
    }
    
    
    public function propsNumericAction(){
        $this->_helper->viewRenderer->setNoRender();
        if(isset($_GET["start"])){
            $limit = $_GET["start"];
        }
        else{
            $limit = 0;
        }
        
        $db_params = OpenContext_OCConfig::get_db_config();
		  $db = new Zend_Db_Adapter_Pdo_Mysql($db_params);
		  $db->getConnection();
        
        $sql = "SELECT properties.property_uuid,
        properties.val_num, properties.val_date, val_tab.val_text
        FROM properties
        LEFT JOIN val_tab ON properties.value_uuid = val_tab.value_uuid
        LIMIT $limit, 300000
        ";
        
        $result = $db->fetchAll($sql, 2);
        foreach($result as $row){
            $data = false;
            $propUUID = $row["property_uuid"];
            $where = "property_uuid = '$propUUID' ";
            $propDate = $row["val_date"];
            $propNum = $row["val_num"];
            $valText = $row["val_text"];
            if(is_numeric("0".$valText)){
                if($valText != 0 && $propNum ==0){
                    $valText = $valText + 0;
                    $data = array("val_num" => $valText);
                }
            }
            
            $tooLate = strtotime("2012-10-1");
            $propDateTime = strtotime($propDate);
            if(!$propDateTime || $propDateTime > $tooLate){
                $calendardTest = false;
                $cal_test_string = str_replace("/", "-", $valText);
                if (($timestamp = strtotime($cal_test_string)) === false) {
                    $calendardTest = false;
                }
                else{
                    $calendardTest = true;
                }
               
                if($calendardTest){
                    $valueDate = date("Y-m-d", strtotime($cal_test_string));
                    $data = array("val_date" => $valueDate, "val_num" => 0);
                }
            }
            
            if(is_array($data)){
                echo "<br/>Updating $valText in $propUUID ";
                $db->update("properties", $data, $where);
            }
            
        }
        
    }
    
    
    
    public function propsUpdateAction(){
        mb_internal_encoding( 'UTF-8' );
        $this->_helper->viewRenderer->setNoRender();
        
        $opts = array('http' =>
            array(
              'timeout' => 1440
            )
        );
                              
        $context  = stream_context_create($opts);
        
        if(isset($_GET["start"])){
            $limit = $_GET["start"];
        }
        else{
            $limit = 0;
        }
        
        $db_params = OpenContext_OCConfig::get_db_config();
		  $db = new Zend_Db_Adapter_Pdo_Mysql($db_params);
		  $db->getConnection();
        
        $sql = "SELECT properties.property_uuid, properties.variable_uuid
        FROM properties
        JOIN var_tab ON properties.variable_uuid = var_tab.variable_uuid
        WHERE var_tab.var_sum = ''
        GROUP BY properties.variable_uuid
        LIMIT $limit, 300000
        ";
		
        $result = $db->fetchAll($sql, 2);
        $i = $limit;
        $lastVar = false;
        $lastOK = true;
        foreach($result as $row){
            $varUUID = $row["variable_uuid"];
            $itemUUID = $row["property_uuid"];
            $url = "http://opencontext/properties/".$itemUUID.".xml";
            
            if($lastOK || ($varUUID != $lastVar)){
                echo "<br/>Starting Item ($i):<a href='$url'>$itemUUID</a>...";
                @$itemXMLstring = file_get_contents($url, false, $context);
                if($itemXMLstring != false){
                    $lastOK = true;
                    @$itemXML = simplexml_load_string($itemXMLstring);
                    if(!$itemXML){
                        echo " ...<strong>INVALID XML,FAILED!</strong>";
                        //break;
                    }
                    else{
                        echo " ...OK";
                    }    
                    unset($itemXML);
                }
                else{
                    $lastOK = false;
                    echo " ...<strong>Failed in getting content!</strong>";
                    //break;
                }
            }
            else{
                echo "<br/>Skipping Item ($i):<a href='$url'>$itemUUID</a>, bad Var:".$varUUID;
            }
            
            $lastVar = $varUUID;
            unset($itemXMLstring);
            $i++;
        }
    
    }
    
    public function propsAction(){
        mb_internal_encoding( 'UTF-8' );
        $this->_helper->viewRenderer->setNoRender();
        
        $typeLinks = array("spatial" => "subjects", "media" => "media", "person" => "persons", "document" => "documents", "project" => "projects");
        
        
        $db_params = OpenContext_OCConfig::get_db_config();
		  $db = new Zend_Db_Adapter_Pdo_Mysql($db_params);
		  $db->getConnection();
        
        $sql = "SELECT noid_bindings.itemUUID, noid_bindings.itemType
        FROM noid_bindings
        WHERE noid_bindings.itemType != 'table'
        AND noid_bindings.props = 0
        ";
		
        $propertyObj = new Property;
        $result = $db->fetchAll($sql, 2);
        foreach($result as $row){
            
            $itemType = $row["itemType"];
            $itemUUID = $row["itemUUID"];
            
            echo "<br/>Starting Item: <a href='../".$typeLinks[$itemType]."/".$itemUUID."'>$itemUUID</a> ($itemType)...";
            
            if($itemType == 'spatial'){
                $itemObj = New Subject;
                $itemXMLstring = $itemObj->getItemXML($itemUUID);
            }
            elseif($itemType == 'media'){
               $itemObj = New Media;
               $itemXMLstring = $itemObj->getItemXML($itemUUID);
            }
            elseif($itemType == 'person'){
               $itemObj = New Person;
               $itemXMLstring = $itemObj->getItemXML($itemUUID);
            }
            elseif($itemType == 'document'){
               $itemObj = New Document;
               $itemXMLstring = $itemObj->getItemXML($itemUUID);
            }
            elseif($itemType == 'project'){
               $itemObj = New Project;
               $itemXMLstring = $itemObj->getItemXML($itemUUID);
            }
            
            if($itemXMLstring != false){
                $nameSpaceArray = $itemObj->nameSpaces();
                $projectUUID = $itemObj->projectUUID;
                $sourceID = $itemObj->sourceID;
                $propertyObj->documentProperties($projectUUID, $sourceID, $itemUUID, $itemType, $itemXMLstring, $nameSpaceArray, $db);
            }
            
            echo " ...finished it, from Project: $projectUUID ";
            
            $data = array("props" => 1);
            $where = "itemUUID = '$itemUUID' ";
            $db->update("noid_bindings", $data, $where);
            
            unset($itemObj);
            unset($itemXMLstring);
        }//end loop
        
        
    } //end function   
    
    
    
    
    
}

