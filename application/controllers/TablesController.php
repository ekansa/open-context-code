<?php
/** Zend_Controller_Action */
require_once 'Zend/Controller/Action.php';
//ini_set("memory_limit", "1024M");

ini_set('memory_limit', '512M');
ini_set("max_execution_time", "0");
ini_set('default_socket_timeout',    240);


//session_start();
class tablesController extends Zend_Controller_Action
{
  

  
  
  
  public function indexAction(){
      OpenContext_SocialTracking::update_referring_link('tables', $this->_request->getRequestUri(), @$_SERVER['HTTP_USER_AGENT'], @$_SERVER['HTTP_REFERER']);
      $db_params = OpenContext_OCConfig::get_db_config();
      $db = new Zend_Db_Adapter_Pdo_Mysql($db_params);
      $db->getConnection();
      $result=$db->fetchAll("SELECT * FROM dataset ORDER BY created_on DESC");
      $page=$this->_getParam('page',1);
      @$paginator = Zend_Paginator::factory($result);
      if($paginator){
        $paginator->setItemCountPerPage(10);
        $paginator->setCurrentPageNumber($page);
      }
      $lastUpdate = strtotime(OpenContext_OCConfig::last_update());
      $lastUpdate = strtotime("2010-05-20 17:40:33");
      
      foreach($paginator as $table){
        $dataCurrent = true;
        if($table['num_records'] != 'no' ){  
          if($lastUpdate >=  strtotime($table['created_on'])){
            $dataCurrent = OpenContext_TableOutput::tableCurrentCheck($table['cache_id'], $table['set_uri'], $table['num_records']);
          }
        }
      }
      
      $this->view->all_tables=$paginator;
      $db->closeConnection();
  }
    
    
  public function atomAction(){
    
    //$this->_helper->viewRenderer->setNoRender();
    
    $resultsPerPage = 10;
    $db_params = OpenContext_OCConfig::get_db_config();
    $db = new Zend_Db_Adapter_Pdo_Mysql($db_params);
    $db->getConnection();
    $result=$db->fetchAll("SELECT * FROM dataset ORDER BY created_on DESC");
    
    $totalTableCount =count($result);
    $lastPage = round(($totalTableCount / $resultsPerPage),0);
    
    // if there's a remainder, add a page. For example, 13 items should result in two pages.
    if ($totalTableCount % $resultsPerPage) {
      $lastPage = $lastPage + 1;
    }
    
    $lastUpdate = $result[0]['created_on'];
    
    //echo $lastUpdate;
    
    $this->view->lastUpdated = $lastUpdate;
    
    $page=$this->_getParam('page',1);
    @$paginator = Zend_Paginator::factory($result);
    if($paginator){
      $paginator->setItemCountPerPage($resultsPerPage);
      $paginator->setCurrentPageNumber($page);
    }
    

    $this->view->page = $page;
    $this->view->lastPage = $lastPage;
    $this->view->tables = $paginator;
    $this->view->resultsPerPage = $resultsPerPage;
    $this->view->totalTableCount = $totalTableCount;
    
    $db->closeConnection();
  }
  
  
  
  
  public function helpAction(){
    OpenContext_SocialTracking::update_referring_link('tables', $this->_request->getRequestUri(), @$_SERVER['HTTP_USER_AGENT'], @$_SERVER['HTTP_REFERER']);
  }
  
  
      
  public function viewAction(){
    //$this->_helper->viewRenderer->setNoRender(); 
    $tableID = $this->_request->getParam('tableid');
    
    
    if($tableID == '.atom'){
      return $this->_forward('atom'); //do the atom action
    }
    elseif($this->_request->getParam('edit')){
      return $this->_forward('edit'); //do the edit action
    }
    
    OpenContext_SocialTracking::update_referring_link('tables', $this->_request->getRequestUri(), @$_SERVER['HTTP_USER_AGENT'], @$_SERVER['HTTP_REFERER']);
    
    if(!$this->_request->getParam('partID')){
        $partID = false;
    }
    else{
        $partID = $this->_request->getParam('partID');
    }
    $this->view->partID = $partID;
    
    
    $exTableObj = new ExportTable;
    $found = $exTableObj->getByID($tableID);
    if($found){
      if(isset($_GET["page"])){
        $exTableObj->recPage = $_GET["page"];
      }
      $this->view->exTableObj = $exTableObj;
      return $this->render('tview');
    }
    
    
    
    
    $tableID = OpenContext_TableOutput::tableID_part($tableID, $partID);
    
    $Final_frontendOptions = array('lifetime' => NULL,'automatic_serialization' => true );
    $Final_backendOptions = array('cache_dir' => './tablecache/' );
    $Final_cache = Zend_Cache::factory('Core','File',$Final_frontendOptions,$Final_backendOptions);
    $result = false;
    if($cache_result = $Final_cache->load($tableID )){
      #$this->view->JSONstring = $cache_result;
      
      //save data to MySQL for redundancy, if not already in.
      //tableDataBaseSave($tableID, $cache_result);
      
      //$result = Zend_Json::decode($cache_result);
      $result = json_decode($cache_result, true);
    }
    else{
      
      unset($Final_cache);
      //echo "there";
      $tableObj = new Table;
      $tableObj->getByID($tableID);
      $tableObj->get_jsonFile();
      if($tableObj->get_jsonFile()){
        $jsonString = $tableObj->jsonData;
        $jsonString = (string)$jsonString;
        unset($tableObj);
        $result = json_decode($jsonString, true);
      }
      else{
        unset($tableObj);
      }
    }
    
    
    if($result != false){
      $page=$this->_getParam('page',1);
      @$paginator = Zend_Paginator::factory($result["records"]);
      if($paginator){
        $paginator->setItemCountPerPage(20);
        $paginator->setCurrentPageNumber($page);
        $this->view->table_data=$paginator;
      }
      $this->view->table_id=$tableID;
      $this->view->table_fields = $result["table_fields"];
      
      $tableMetadata = $this->get_table_metadata($tableID);
      if(!$tableMetadata){
        $tableMetadata = $result["meta"];
      }
      $tableMetadata = OpenContext_TableOutput::noid_check($tableID, $tableMetadata);
      
      $this->view->table_metadata = $tableMetadata; 
      $host = OpenContext_OCConfig::get_host_config();
      $this->view->google_link=generateAuthSubRequestLink($host."/tables/googleservice?tableid=".$tableID);
    }
    else{
      
      return $this->_helper->redirector('index', 'tables');
      
    }
    
    
    
  }
  
  
  public function tabatomAction(){
    
    $this->_helper->viewRenderer->setNoRender();
    $tableID = $this->_request->getParam('tableid');
    $tableID = OpenContext_TableOutput::tableURL_toCacheID($tableID);
    
    $tabObj = new Table;
    $entryXML = $tabObj->getItemEntry($tableID);
    header('Content-type: application/atom+xml', true);
    echo $entryXML;
    
  }
  
  
    
  public function tabjsonAction(){
    
    error_reporting(0);
    $this->_helper->viewRenderer->setNoRender();
    $tableID = $this->_request->getParam('tableid');
    $tableID = OpenContext_TableOutput::tableURL_toCacheID($tableID);

    $Final_frontendOptions = array('lifetime' => NULL,'automatic_serialization' => true );
    $Final_backendOptions = array('cache_dir' => './tablecache/' );
    $Final_cache = Zend_Cache::factory('Core','File',$Final_frontendOptions,$Final_backendOptions);
    $cache_result = false;
    if($cache_result = $Final_cache->load($tableID )){
      
    }
    else{
      
      $tableObj = new Table;
      $tableObj->getByID($tableID);
      $tableObj->get_jsonFile();
      if($tableObj->get_jsonFile()){
        $cache_result = $tableObj->jsonData;
      }
      unset($tableObj);
    }
    
    if($cache_result != false){
	$outJSON = (string)$cache_result;
        unset($cache_result);
        $offset = 60 * 60 * 60 * 60;
        $expire = "Expires: " . gmdate("D, d M Y H:i:s", time() + $offset). " GMT";
        if(isset($_SERVER['HTTP_ACCEPT_ENCODING'])){
          $encoding = $this->check_compress_ok($_SERVER['HTTP_ACCEPT_ENCODING']);
          $compressOK = true;
        }
        else{
          $encoding = 'gzip';
          $compressOK = false;
        }
        
        if($compressOK){
          if(!isset($_REQUEST["callback"])){
              header('Content-Type: application/json; charset=utf8');
              header ("Cache-Control:max-age=290304000, public");
              header ($expire);
              header('Content-Encoding: '.$encoding);
              print("\x1f\x8b\x08\x00\x00\x00\x00\x00");
              print gzcompress($outJSON, 9);
          }
          else{
              $outJSON = $_REQUEST["callback"]."(".$outJSON.")";
              header('Content-Type: application/json; charset=utf8');
              header ("Cache-Control:max-age=290304000, public");
              header ($expire);
              header('Content-Encoding: '.$encoding);
              print("\x1f\x8b\x08\x00\x00\x00\x00\x00");
              print gzcompress($outJSON, 9);
          }
        }
        else{
          header('Content-Type: application/json; charset=utf8');
          header ("Cache-Control:max-age=290304000, public");
          header ($expire);
          echo $outJSON;
        }
      
      
      
      }
    else{
      header("HTTP/1.0 404 Not Found");
      echo "Failure!";
    }
  }
    
  public function editAction(){
    
    //$this->_helper->viewRenderer->setNoRender();
    //echo "here";
    $tableID = $this->_request->getParam('tableid');
    OpenContext_SocialTracking::update_referring_link('tables', $this->_request->getRequestUri(), @$_SERVER['HTTP_USER_AGENT'], @$_SERVER['HTTP_REFERER']);
    
    if(!$this->_request->getParam('partID')){
      $partID = false;
    }
    else{
      $partID = $this->_request->getParam('partID');
    }
    $this->view->partID = $partID;
    
    $tableID = OpenContext_TableOutput::tableID_part($tableID, $partID);

    $requestParams =  $this->_request->getParams();
    //echo var_dump($requestParams);
    
    $Final_frontendOptions = array('lifetime' => NULL,'automatic_serialization' => true );
    $Final_backendOptions = array('cache_dir' => './tablecache/' );
    $Final_cache = Zend_Cache::factory('Core','File',$Final_frontendOptions,$Final_backendOptions);
    if($cache_result = $Final_cache->load($tableID )){
      
      $result = Zend_Json::decode($cache_result);
      $page = $this->_getParam('page',1);
      @$paginator = Zend_Paginator::factory($result["records"]);
      if($paginator){
        $paginator->setItemCountPerPage(20);
        $paginator->setCurrentPageNumber($page);
        $this->view->table_data=$paginator;
      }
      $this->view->table_id=$tableID;
      $this->view->table_fields = $result["table_fields"];
      
      $tableMetadata = $this->get_table_metadata($tableID);
      if(!$tableMetadata){
        $tableMetadata = $result["meta"];
      }
      $this->view->table_metadata = $tableMetadata; 
        
      
      $goodToEdit = false;
      
      $auth = Zend_Auth::getInstance();
      if (!$auth->hasIdentity()){
        return $this->_helper->redirector('index'); 
      }
      else{
        $identity = $auth->getIdentity();
        $this->view->displayName = $identity->name;
        $this->view->email =  $identity->email;
        
        if($identity->name == "Open Context Editors"){
          $goodToEdit = true;
        }
        elseif($identity->name == $result["meta"]["TabCreator"]){
          $goodToEdit = true;
        }  
      }
      
      
      
      if(!$goodToEdit){
        return $this->_helper->redirector('index');
      }
      
    }
    else{
      return $this->_helper->redirector('index');
    }//end case where cache-id not found
    
    
  }//end action function
  
  
  //this updates a table. must be authorized to do so
  public function updateAction(){
    $auth = Zend_Auth::getInstance();
    if (!$auth->hasIdentity()){
      return $this->_helper->redirector('index'); 
    }
      
    $identity = $auth->getIdentity();
    $this->view->displayName = $identity->name;
    $this->view->email =  $identity->email;
    
    $tableID = $_REQUEST['tableId'];
    $newTitle = $_REQUEST['title'];
    $newDescription = $_REQUEST['description'];
    $newTags = $_REQUEST['tags'];
    
    $this->_helper->viewRenderer->setNoRender();
    //echo $tableID."<br/>";
    //echo $newTitle."<br/>";
    //echo $newDescription."<br/>";
    //echo $newTags."<br/>";
    $Final_frontendOptions = array('lifetime' => NULL,'automatic_serialization' => true );
    $Final_backendOptions = array('cache_dir' => './tablecache/' );
    $Final_cache = Zend_Cache::factory('Core','File',$Final_frontendOptions,$Final_backendOptions);
    if($cache_result = $Final_cache->load($tableID )){
      
      $goodToEdit = false;
      $result = Zend_Json::decode($cache_result);
      if($identity->name == "Open Context Editors"){
        $goodToEdit = true; //super user, with ultimate power to edit all
      }
      elseif($identity->name == $result["meta"]["TabCreator"]){
        $goodToEdit = true;
      }
      
      if($goodToEdit){
        //Table found, authorization is OK for edits.
        $editDone = OpenContext_TableOutput::table_update($tableID, $result, $newTitle, $newDescription, $newTags);
        $this->view->displayName = $identity->name;
        $this->view->email =  $identity->email;
        $this->view->editDone = $editDone;
        
        $page = $this->_getParam('page',1);
        @$paginator = Zend_Paginator::factory($result["records"]);
        if($paginator){
          $paginator->setItemCountPerPage(20);
          $paginator->setCurrentPageNumber($page);
          $this->view->table_data=$paginator;
        }
        $this->view->table_id=$tableID;
        $this->view->table_fields = $result["table_fields"];
        $tableMetadata = $this->get_table_metadata($tableID);
        if(!$tableMetadata){
          $tableMetadata = $result["meta"];
        }
        $this->view->table_metadata = $tableMetadata;  
      }
      else{
        //user is not authorized to make edits
        return $this->_helper->redirector('index');
      }
      
    }
    else{
      //table ID isn't found, need to handle error
      return $this->_helper->redirector('index');
    }
  
  }//end action function
  
  
  
  
  
    
  public function searchAction(){
    $table_tag=$this->_getParam('tag');
    $db_params = OpenContext_OCConfig::get_db_config();
    $db = new Zend_Db_Adapter_Pdo_Mysql($db_params);
    //$db->getConnection();
    $result=$db->fetchAll("SELECT d.table_name,d.description,d.created_by, d.created_on, d.num_records, d.set_uri
                          FROM dataset AS d
                          JOIN tag AS t ON t.table_id=d.id
                          WHERE t.tag_name LIKE '".$table_tag."' ");
    //$result=$db->fetchAll("SELECT * from tables");
    $page=$this->_getParam('page',1);
    $paginator = Zend_Paginator::factory($result);
    $paginator->setItemCountPerPage(10);
    $paginator->setCurrentPageNumber($page);
    $this->view->all_tables=$paginator;
    }

  public function deliciousAction(){
    $delicious = new Zend_Service_Delicious('gvaswani', 'no4uby6n');
    // create a new post and save it  (without method call chaining)
    $newPost = $delicious->createNewPost('Zend Framework','http://framework.zend.com');
    $newPost->setNotes('Open context Homepage');
    $newPost->save();
    }


  public function googleserviceAction(){
    $this->_helper->viewRenderer->setNoRender();
    $tableID = $_GET['tableid'];
    if (isset($_GET['token'])) 
      updateAuthSubToken($_GET['token']);
    else 
      echo 'error in receiving token without $_POST or $_GET variables set';
    
    
    $httpclient = getAuthSubHttpClient();
    
    $sp = new Zend_Gdata_Spreadsheets($httpclient);
    $sp_list=$sp->getSpreadsheetFeed();
    
    $db_params = OpenContext_OCConfig::get_db_config();
    $db = new Zend_Db_Adapter_Pdo_Mysql($db_params);
    $db->getConnection();
    $data = array('client' => Zend_Json::encode($httpclient),
                  'comment'=> Zend_Json::encode($sp_list));
    $db->insert('google_com', $data);
    $db->closeConnection();
    
    $spreadsheetId="";
    foreach ($sp_list as $entry) {
      //echo $entry->title->text;
      if($entry->title->text=="opencont"){
        $id=explode("/",$entry->id);
        $spreadsheetId=$id[count($id)-1];
        }
      }
    echo $spreadsheetId;
    $query = new Zend_Gdata_Spreadsheets_DocumentQuery();
    $query->setSpreadsheetKey($spreadsheetId);
    $feed = $sp->getWorksheetFeed($query);
    $worksheetId="";
    foreach($feed as $entry){
      if($entry->title->text=="opencont"){
        $id=explode("/",$entry->id);
        $worksheetId=$id[count($id)-1];
        }
      }
    echo $worksheetId;
    //$query->setWorksheetId($worksheetId);
    //$rowData=array('name'=>'sid','age'=>24,'sex'=>'male');
    $Final_frontendOptions = array('lifetime' => NULL,'automatic_serialization' => true );
    $Final_backendOptions = array('cache_dir' => './tablecache/' );
    $Final_cache = Zend_Cache::factory('Core','File',$Final_frontendOptions,$Final_backendOptions);
       if($cache_result = $Final_cache->load($tableID )){
         $result = Zend_Json::decode($cache_result);
         $count=0;
         //foreach($result['table_fields'] as $headerData){
         //  try
         //   {$sp->updateCell(1,++$count,$headerData,$spreadsheetId,$worksheetId);}
         //  catch (Zend_Gdata_App_Exception $e) 
         //   {$sp->updateCell(1,++$count,"NULL",$spreadsheetId,$worksheetId);}
         //  }
         $test_ro_data=Array("proj"=>"Iraq Heritage Program","person"=>"AAI Staff","defcontext0"=>"Iraq","defcontext1"=>"Ur","defcontext2"=>"Not given (Ur)","def_context_3"=>null,"category"=>"Small Find","label"=>"amulet","current_location"=>"BritishMuseum","culturalaffiliation"=>"Kassite","Excavator"=>null,"Museum_Link"=>null,"Width"=>null,"Length"=>null,"Height"=>null,"Material"=>"faience","Description"=>"frog (?)","Mass"=>null,"Period"=>"Middle-Late_Bronze_Age","Disclaimer"=>"Object description is in draft form, and is subject to revision","Object_type"=>"amulet","Date-early"=>1500,"Date-late"=>1150,"notes"=>null);
         //foreach($result['records'] as $UUID=>$rowData){
           try
            {$sp->insertRow($test_ro_data,$spreadsheetId,$worksheetId);}
           catch (Zend_Dom_Exception $e) 
            { echo "Not able to enter row data";}
          // }
      }
      else
        echo "Failure!";
       
    }
    
    
    private function get_table_metadata($tableID){
      $db_params = OpenContext_OCConfig::get_db_config();
      $db = new Zend_Db_Adapter_Pdo_Mysql($db_params);
      $db->getConnection();
      
      $sql = "SELECT metadata FROM dataset WHERE cache_id = '$tableID' LIMIT 1";
      $result = $db->fetchAll($sql, 2);
      
      if($result){
        $jsonString = $result[0]['metadata'];
        //$outputA = Zend_Json::decode($jsonString);
        $outputA = json_decode($jsonString, true);
        $output = $outputA['meta'];
      }
      else{
        $output = false;
      }
      
      $db->closeConnection();
      return $output;
    }
    
    
    
    
    private function check_compress_ok($HTTP_ACCEPT_ENCODING){
    
        if( headers_sent() ){
            $encoding = false;
        }elseif( strpos($HTTP_ACCEPT_ENCODING, 'x-gzip') !== false ){
            $encoding = 'x-gzip';
        }elseif( strpos($HTTP_ACCEPT_ENCODING,'gzip') !== false ){
            $encoding = 'gzip';
        }else{
            $encoding = false;
        }
        return $encoding; 
    }
    
    
    
    
    
    
    
  }//end of the controller class


/* Helper functions */

function tableDataBaseSave($tableID, $tableData){
  $db_params = OpenContext_OCConfig::get_db_config();
  $db = new Zend_Db_Adapter_Pdo_Mysql($db_params);
  $db->getConnection();
  
  $result=$db->fetchAll("SELECT alldata FROM dataset WHERE cache_id = '".$tableID."'" );
  if($result){
    if($result[0]["alldata"] == null){
    
      //need to compress the data, or big updates timeout. 
      $zip = new ZipArchive();
      $zip->open("./tablecache/temp.zip", ZipArchive::OVERWRITE); 
      $zip->addFromString($tableID.'.json', $tableData);
      $zip->close();
      $zipString = file_get_contents("./tablecache/temp.zip");
      $data = array('alldata' => $zipString);
      $db->update('dataset', $data, "cache_id = '".$tableID."'");
      unlink("./tablecache/temp.zip");
    }
  }
  //$db->closeConnection();
}


function tableZipOut($tableID){
  $db_params = OpenContext_OCConfig::get_db_config();
  $db = new Zend_Db_Adapter_Pdo_Mysql($db_params);
  $db->getConnection();
  
  $result=$db->fetchAll("SELECT alldata FROM dataset WHERE cache_id = '".$tableID."'" );
  if($result){
    $output =  $result[0]["alldata"];
  }
  $db->closeConnection();
  return $output;
}


function generateAuthSubRequestLink($nextUrl = null) {
    
  /* This makes a url like:
    https://www.google.com/accounts/AuthSubRequest?next=http%3A%2F%2Fopencontext%2Ftables%2Fgoogleservice%3Ftableid%3D3e9430ee5812af4ebd178e123b69345e&scope=http://spreadsheets.google.com/feeds/&secure=0&session=1
    https://www.google.com/accounts/AuthSubRequest?next=http%3A%2F%2Fopencontext%2Ftables%2Fgoogleservice%3Ftableid%3D3e9430ee5812af4ebd178e123b69345e&scope=http%3A%2F%2Fspreadsheets.google.com%2Ffeeds%2F&secure=0&session=1
        */
  
  $scope = urlencode('http://spreadsheets.google.com/feeds/');
  $secure = false;
  $session = true;
  $secure = 0;
  $session = 1;

  if (!$nextUrl) {
    generateUrlInformation();
    $nextUrl = $_SESSION['operationsUrl'];
    }

  $url = Zend_Gdata_AuthSub::getAuthSubTokenUri($nextUrl, $scope, $secure, $session);
  $url = str_replace("http://spreadsheets.google.com/feeds/", "http%3A%2F%2Fspreadsheets.google.com%2Ffeeds%2F", $url);
  $url = "";
  return '<a href="'.$url.'" title="Feature Coming Soon!"><strong>Authenticate with Google, and work with data in GoogleDocs</strong></a>';
  }

function generateUrlInformation()
{
    if (!isset($_SESSION['operationsUrl']) || !isset($_SESSION['homeUrl'])) {
        $_SESSION['operationsUrl'] = 'http://'. $_SERVER['HTTP_HOST']
                                   . $_SERVER['PHP_SELF'];
        $path = explode('/', $_SERVER['PHP_SELF']);
        $path[count($path)-1] = 'index.php';
        $_SESSION['homeUrl'] = 'http://'. $_SERVER['HTTP_HOST']
                             . implode('/', $path);
    }
}

function updateAuthSubToken($singleUseToken)
{
    try {
        $sessionToken = Zend_Gdata_AuthSub::getAuthSubSessionToken($singleUseToken);
    } catch (Zend_Gdata_App_Exception $e) {
        print 'ERROR - Token upgrade for ' . $singleUseToken
            . ' failed : ' . $e->getMessage();
        return;
    }

    $_SESSION['sessionToken'] = $sessionToken;
    //generateUrlInformation();
    //header('Location: ' . $_SESSION['homeUrl']);
}

function getAuthSubHttpClient()
{
    try {
        $httpClient = Zend_Gdata_AuthSub::getHttpClient($_SESSION['sessionToken']);
    } catch (Zend_Gdata_App_Exception $e) {

        print 'ERROR - Could not obtain authenticated Http client object. '
            . $e->getMessage();
        return;
    }
    
    $db_params = OpenContext_OCConfig::get_db_config();
        $db = new Zend_Db_Adapter_Pdo_Mysql($db_params);
        $db->getConnection();
        $data = array('client' => ($_SESSION['sessionToken']).' tried',
                      'comment' => Zend_Json::encode($httpclient));
        $db->insert('google_com', $data);
        $db->closeConnection();
    
    
    //$httpClient->setHeaders('X-GData-Key', 'key='. $_SESSION['developerKey']);
    return $httpClient;
}
