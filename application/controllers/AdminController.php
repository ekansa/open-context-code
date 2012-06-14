<?php
/** Zend_Controller_Action */
require_once 'Zend/Controller/Action.php';


class mediaController extends Zend_Controller_Action
{   
      
    public function indexAction()
    {
    
    }
    
    
    public function viewAction() {
		
		// get the space uuid from the uri
		$uuid_query = $this->_request->getParam('uuid');
		
                $db = new Zend_Db_Adapter_Pdo_Mysql(array(
                    'host'     => '127.0.0.1',
                    'username' => 'root',
                    'password' => 'swt1kmk2',
                    'dbname'   => 'opencontext'
                ));
                
		$db->getConnection();
                $sql = 'SELECT resource.res_archaeoml
                    FROM resource
                    WHERE resource.uuid = "'.$uuid_query.'" ';
		
                $result = $db->fetchAll($sql, 2);
            
                $this->view->query = $sql ;
                $this->view->result = $result;
                $res_arcbaeoml = $result[0]["res_archaeoml"];
                $this->view->res_arcbaeoml = $res_arcbaeoml;
                
                $media_dom = new DOMDocument("1.0", "utf-8");
                $media_dom->loadXML($res_arcbaeoml);
                OpenContext_MediaAtom::update_view_count($media_dom);
                
                $xml_string = $media_dom->saveXML();
                //$qxml_strong = addslashes($xml_string);
                
                $data = array("res_archaeoml" => $xml_string);
                $where[]= 'uuid = "'.$uuid_query.'" ';
                $db->update('resource', $data, $where);
                
                $this->view->xml_string = $xml_string;
                //$this->view->result = $result;
                
                $db->closeConnection();
                
	}
    
    
    
    /*this makes new atom XML from old Open Context MySQL database
    */
    public function genatomAction(){
        $uuid_query = $this->_request->getParam('uuid');        
        $this->view->uuid = $uuid_query;
        
        $base_hostname = OpenContext_OCConfig::get_host_config(false);
        $baseURI = OpenContext_OCConfig::get_host_config();
                
        $atomFullDoc = new DOMDocument("1.0", "utf-8");
                        
        $root = $atomFullDoc->createElementNS("http://www.w3.org/2005/Atom", "feed");
        $root->setAttribute("xmlns:georss", "http://www.georss.org/georss");
	$root->setAttribute("xmlns:gml", "http://www.opengis.net/gml");
	$atomFullDoc->appendChild($root);
        
        // Feed Title 
	$feedTitle = $atomFullDoc->createElement("title");
	$feedTitleText = $atomFullDoc->createTextNode("Open Context Query Results");
	$feedTitle->appendChild($feedTitleText);
	$root->appendChild($feedTitle);
        
        
    }//end function
    
    
}

