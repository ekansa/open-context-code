<?php
/** Zend_Controller_Action */
require_once 'Zend/Controller/Action.php';
ini_set("memory_limit", "2048M");

class fileController extends Zend_Controller_Action
{   
  public function downloadcsvAction(){
    $this->_helper->viewRenderer->setNoRender();
    
    $host = OpenContext_OCConfig::get_host_config();
    
    header("Content-type: application/octet-stream");
    header("Content-Disposition: attachment; filename=\"OpenContext_data.csv\"");
    $tableId = $this->_request->getParam('tableid');
    $tableId = str_replace(".csv", "", $tableId );
    $tableId = OpenContext_TableOutput::tableURL_toCacheID($tableId);
    
    
    $Final_frontendOptions = array('lifetime' => NULL,'automatic_serialization' => true );
    $Final_backendOptions = array('cache_dir' => './tablecache/' );
    $cache = Zend_Cache::factory('Core','File',$Final_frontendOptions,$Final_backendOptions);
    $data="";
    
    $cache_result = false;
    if($cache_result = $cache->load($tableId )){
      
    }
    else{
      
      $tableObj = new Table;
      $tableObj->getByID($tableId);
      $tableObj->get_jsonFile();
      if($tableObj->get_jsonFile()){
        $cache_result = $tableObj->jsonData;
      }
      unset($tableObj);
    }
    
    if($cache_result != false){
      $result = json_decode($cache_result, true);
     
     
      $data = "OpenContext URL,";
      
      foreach($result['table_fields'] as $fields){
        if($fields == "proj"){
          $fields = "Project";
        }
        elseif($fields == "person"){
            $fields = "Linked Persons";
        }
	elseif($fields == "label"){
            $fields = "Item Name";
        }
	elseif($fields== "pub_date"){
	  $fields= "Publication Date";
	}
	elseif($fields == "update"){
	    $fields = "Last Updated";
	}
  
        if(substr_count($fields, "def_context_")>0){
          $fields = str_replace("def_context_", "", $fields);
          $fields++;
          $fields = "Context (".$fields.")";
        }
        
        
        $data.= $this->clean_csv($fields).",";
        } 
      $data.="\n"; 
      foreach($result['records'] as $id => $record){
        $data.= $host."/subjects/".$id.",";
        foreach($record as $rec)
          //$data.= $this->clean_csv($rec).",";
          $data.= $this->escape_csv_value($rec).",";
        $data.="\n";
        }
      }
    echo $data; 
    }
    
    // clean_csv function
  //
  // * uses double-quotes as enclosure when necessary
  // * uses double double-quotes to escape double-quotes 
  // * uses CRLF as a line separator
  //
  private function clean_csv( $field )
  {
    
      if ( preg_match( '/\\r|\\n|,|"/', $field ) )
      {
        $field = '"' . str_replace( '"', '""', $field ) . '"';
      }
    
    return $field;
  }

  
  
  private function escape_csv_value($value) {
	$value = str_replace('"', '""', $value); // First off escape all " and make them ""
	$value = utf8_decode($value);
	if(preg_match('/,/', $value) or preg_match("/\n/", $value) or preg_match('/"/', $value)) { // Check if I have any commas or new lines
		return '"'.$value.'"'; // If I have new lines or commas escape them
	} else {
		return $value; // If no new lines or commas just return the value
	}
  }

    
    
    
    
    
    /*
   public function downloadcsvAction(){
    $this->_helper->viewRenderer->setNoRender();
    header("Content-type: application/octet-stream");
    header("Content-Disposition: attachment; filename=\"my-data.csv\"");
    $tableId = $this->_request->getParam('tableid');
    $Final_frontendOptions = array('lifetime' => NULL,'automatic_serialization' => true );
    $Final_backendOptions = array('cache_dir' => './tablecache/' );
    $cache = Zend_Cache::factory('Core','File',$Final_frontendOptions,$Final_backendOptions);
    $data="";
    if($cache_result = $cache->load($tableId )){
      #$this->view->JSONstring = $cache_result;
      $result = Zend_Json::decode($cache_result);
      foreach($result['table_fields'] as $fields){
        $data.=$fields.",";
        } 
      $data.="\n"; 
      foreach($result['records'] as $record){
        foreach($record as $rec)
          $data.=$rec.",";
        $data.="\n";
        }
      }
    echo $data; 
    } 
    
    */
}//end of class
