<?php
/** Zend_Controller_Action */
require_once 'Zend/Controller/Action.php';
ini_set("max_execution_time", "0");
//ini_set("memory_limit", "2212M");

class accessionController extends Zend_Controller_Action
{   
    
    public function indexAction(){
         
 
    }
    
    
    public function propsNotesAction(){
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
        
        $sql = "SET collation_connection = utf8_unicode_ci;";
        $db->query($sql, 2);
        $sql = "SET NAMES utf8;";
        $db->query($sql, 2);
        
        $sql = "SELECT properties.project_id, properties.source_id,
        properties.property_uuid, properties.value_uuid, properties.variable_uuid
        FROM properties
        GROUP BY properties.variable_uuid
        LIMIT $limit, 300000
        ";
        
        $result = $db->fetchAll($sql, 2);
        $propertyObj = new Property;
        //$propertyObj->db = $db;

        $nameSpaceArray  = $propertyObj->nameSpaces();
        foreach($result as $row){
           
            $projectID = $row["project_id"];
            $sourceID = $row["source_id"];
            $propUUID = $row["property_uuid"];
            $varUUID = $row["variable_uuid"];
           
            $sql = "SELECT prop_archaeoml AS xml FROM properties WHERE property_uuid = '$propUUID' LIMIT 1;";
            $resultB = $db->fetchAll($sql, 2);
            $xmlString = $resultB[0]["xml"];
            
            $varDescription = false;
            $propDescription = false;
            
            @$itemXML = simplexml_load_string($xmlString );
            if($itemXML){
                unset($xmlString);
               
                
                foreach($nameSpaceArray as $prefix => $uri){
                    @$itemXML->registerXPathNamespace($prefix, $uri);
                }
                
                //get variable description
                if($itemXML->xpath("/arch:property/arch:notes/arch:note[@type='var_des']/arch:string")){
                   foreach ($itemXML->xpath("/arch:property/arch:notes/arch:note[@type='var_des']/arch:string") as $xpathResult){
                      $varDescription = (string)$xpathResult;
                      if($varDescription == "This variable currently has no explanatory description."){
                        $varDescription = false;
                      }
                   }
                }
                
                //get property (variable:value) description
                if($itemXML->xpath("/arch:property/arch:notes/arch:note[@type='prop_des']/arch:string")){
                   foreach ($itemXML->xpath("/arch:property/arch:notes/arch:note[@type='prop_des']/arch:string") as $xpathResult){
                      $propDescription = (string)$xpathResult;
                      if($propDescription == "This property currently has no explanatory description."){
                         $propDescription = false;
                      }
                   }
                }
                unset($itemXML);
                
            }
            
            
            if( $varDescription != false){
                
                $where = "variable_uuid = '$varUUID' ";
                $data = array("var_des" => $varDescription);
                echo "<br/><br/><br/>PropID: <a href='../properties/".$propUUID."'>$propUUID</a> ($varUUID) ";
                echo "Added var note <em>$propDescription</em><br/>";
                $db->update("var_tab", $data, $where);
                
                $noteID = OpenContext_NewDocs::generateUUID();
                $data = array("hashID" => sha1($varUUID."_".$varDescription),
                              "project_id" => $projectID,
                              "source_id" => $sourceID,
                              "variable_uuid" => $varUUID,
                              "note_uuid" => $noteID,
                              "note_text" => $varDescription
                              );  
                
                try {
                    $db->insert("var_notes", $data);
                } catch (Exception $e) {
    
                }
               
            }
            
            if( $propDescription != false){
                $where = "property_uuid = '$propUUID' ";
                $data = array("note" => $propDescription);
                echo "<br/><br/><br/>PropID: <a href='../properties/".$propUUID."'>$propUUID</a> ";
                echo "Added prop note <em>$propDescription</em><br/>";
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
        WHERE noid_bindings.itemType = 'spatial'
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

