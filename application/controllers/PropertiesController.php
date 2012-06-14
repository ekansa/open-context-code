<?php
/** Zend_Controller_Action */
require_once 'Zend/Controller/Action.php';


class propertiesController extends Zend_Controller_Action
{   
      
    public function indexAction(){
    
    }
    
    
    public function viewAction() {
		
	// get the property uuid from the uri
	$uuid_query = $this->_request->getParam('property_uuid');

	//check for referring links
	OpenContext_SocialTracking::update_referring_link('property', $this->_request->getRequestUri(), @$_SERVER['HTTP_USER_AGENT'], @$_SERVER['HTTP_REFERER']);
	$propObj = new Property;
	$itemFound = $propObj->getByID($uuid_query);
	if($itemFound){
	    $this->view->xml_string = $propObj->atomFull;
	}
	else{
	    $this->view->requestURI = $this->_request->getRequestUri(); 
	    return $this->render('404error');
	}
    }
    
	/*
	public function viewAction() {
		
		// get the property uuid from the uri
		$uuid_query = $this->_request->getParam('property_uuid');
		
		//check for referring links
		OpenContext_SocialTracking::update_referring_link('property', $this->_request->getRequestUri(), @$_SERVER['HTTP_USER_AGENT'], @$_SERVER['HTTP_REFERER']);
		
                $db_params = OpenContext_OCConfig::get_db_config();
                $db = new Zend_Db_Adapter_Pdo_Mysql($db_params);
                               
		$db->getConnection();
                $sql = 'SELECT properties.prop_atom
                    FROM properties
                    WHERE properties.property_uuid = "'.$uuid_query.'"
                    LIMIT 1';
		
                $result = $db->fetchAll($sql, 2);
                 
                if($result){
                    $prop_atom = $result[0]["prop_atom"];
                    $prop_atom = OpenContext_OCConfig::updateNamespace($prop_atom, $uuid_query, "prop_atom", "property");
		    
                    if(strlen($prop_atom)<10){
                        $prop_atom = OpenContext_PropertyAtom::make_archaeoml_atom($uuid_query);
                        $where_term = 'property_uuid = "'.$uuid_query.'"';
                        $data = array('prop_atom' => $prop_atom);
                        $n = $db->update('properties', $data, $where_term);
                    }
                    
		    //$n = $db->update('projects', $data, $where_term);
                    $db->closeConnection();
                    $xml_string = $prop_atom;
                }
                else{
                    $xml_string = "no luck...";
                    $db->closeConnection();
                }
                
                
                $this->view->xml_string = $xml_string;
                
                //$this->view->result = $result;
                
	}
    */
    
    
    
    
        public function atomAction() {
                
                // get the property uuid from the uri
		$uuid_query = $this->_request->getParam('property_uuid');
                $db_params = OpenContext_OCConfig::get_db_config();
                $db = new Zend_Db_Adapter_Pdo_Mysql($db_params);
                               
		$db->getConnection();
                $sql = 'SELECT properties.prop_atom
                    FROM properties
                    WHERE properties.property_uuid = "'.$uuid_query.'"
                    LIMIT 1';
		
                $result = $db->fetchAll($sql, 2);
                 
                if($result){
                    $prop_atom = $result[0]["prop_atom"];
                    
                    if(strlen($prop_atom)<200){
                        $prop_atom = OpenContext_PropertyAtom::make_archaeoml_atom($uuid_query);
                        $where_term = 'property_uuid = "'.$uuid_query.'"';
                        $data = array('prop_atom' => $prop_atom);
                        $n = $db->update('properties', $data, $where_term);
                    }
                    
		    $prop_atom = OpenContext_OCConfig::updateNamespace($prop_atom, $uuid_query, "prop_atom", "property");
		    
		    //$n = $db->update('projects', $data, $where_term);
                    $db->closeConnection();
                    $xml_string = $prop_atom;
                }
                else{
                    $xml_string = "no luck...";
                    $db->closeConnection();
                }
                
                
                $this->view->xml_string = $xml_string;
                
                //$this->view->result = $result;
                
	}
        
    
    
    
}

