<?php
/** Zend_Controller_Action */
require_once 'Zend/Controller/Action.php';


class widgetController extends Zend_Controller_Action
{   
      
    public function indexAction(){
	
		  if(isset($_GET['id'])){
				$id = $_GET['id'];
				$name = $_GET['name'];
				$type = $_GET['type'];
				$this->view->name = $name;
				$this->view->type = $type;
		  }
		  else{
				$id = false;
		  }
		  
		  $this->view->id = $id;
		
    }
    
    public function showAction() {
		  // get the space uuid from the uri
		  $pid = $_GET['id'];
		  $type=$_GET['type'];
		  $callback = $_GET['callback'];
						
		  $host = OpenContext_OCConfig::get_host_config();
		  if ($type=="person"){$url = $host.'/persons/' . $pid . '.json';}
		  else {$url = $host.'/projects/' . $pid . '.json';}
		  
		  $pjson=file_get_contents($url);
  
		  $response=$callback . '(' . $pjson. ');';
		  
		  //disable rendering
		  $this->_helper->viewRenderer->setNoRender();
		  //if using layout uncomment below
		  //$this->_helper->layout->disableLayout();
  
		  echo $response; 
    }
    
	 public function orcidAction(){
		  
		  $this->_helper->viewRenderer->setNoRender();
		  
		  $response = false;
		  $ORCID = New ORCID;
		  if(isset($_GET["uri"])){
				$orcidURI = $_GET["uri"];
				$response = $ORCID->getProfile($orcidURI);
		  }
		  
		  if(!$response){
				$output = array("error" => true);
				header("Content-Type: application/json; charset=utf8");
				echo Zend_Json::encode($output);
		  }
		  else{
				header("Content-Type: application/json; charset=utf8");
				echo $response->getBody();
		  }
		  
	 }


}

