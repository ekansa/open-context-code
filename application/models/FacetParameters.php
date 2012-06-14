<?php


/*
 this class is used to create individual facets,
 facet values, and links for GET queries to filder by a facet, and checkboxes for
 advanced search options on a facet
*/
class Facet {
    
    public $facet_cat; // facet category  (project_name, cat, person_link, etc.)  
    public $host; //host
    public $request_array; //request array from original GET query that generated these facets
    public $standard_link_html; //html of link, facet value, and facet count for using this facet
    public $link; //value used for link href attribute to get query based on this facet 
    public $parameter; //parameter used for advanced search check box
    public $linkQuery; //properly url encoded facet value needed for GET query 
    public $value_string; //facet value i.e. Domuztepe, Petra Great Temple, Bade Museum...

    //make facet
    function makeFacet($va_key, $va_value){
        
        if (preg_match('/^def_context_/', $this->facet_cat)) {
            $this->defaultContextFacet($va_key, $va_value);
        }
        
        
    }
    
    
    function defaultContextFacet($va_key, $va_value){
        $request_array = $this->request_array;
	$linkQuery = urlencode($va_key);
	$link = $this->host . $request_array[0] . $linkQuery  . $request_array[1];
	$value_out = '<a href="' .  $link  . '" > '. $va_key .'</a>' . ': ' . $va_value;
	$value_string = $va_key;
        
        $this->parameter = "default_context";
        $this->linkQuery;
        $this->link = $link;
        $this->value_string = $va_key;
    }
    
    
    //check to see if a doc exists
    function check_doc_exists($docID){
        $db_params = OpenContext_OCConfig::get_db_config();
        $db = new Zend_Db_Adapter_Pdo_Mysql($db_params);
	$db->getConnection();
        
        $sql = 'SELECT doc_id
                FROM documents
                WHERE documents.doc_id	= "'.$docID.'"
                LIMIT 1';
		
        $result = $db->fetchAll($sql, 2);
        $db->closeConnection();
        if($result){
            return true;
        }
        else{
            return false;
        }
    }
    
    //add a new doc it it doesn't exist, or update it if it does
    function add_update_doc($docID){
        if($this->check_doc_exists($docID)){
            $this->update_doc($docID);
        }
        else{
            $this->insert_doc($docID);
        }
    }
    
    function update_doc($docID){
        $db_params = OpenContext_OCConfig::get_db_config();
        $db = new Zend_Db_Adapter_Pdo_Mysql($db_params);
	$db->getConnection();
        
        $where = array();
        $where[] = "doc_id = '".$docID."'";
        
        $data = array("study_id" => $this->studyID,
                      "name" => $this->name,
                      "xml" => $this->XMLdata,
                      "atom" => $this->atomData);
        
        $db->update("documents", $data, $where);
        $db->closeConnection();
    }
    
    function insert_doc($docID){
        $db_params = OpenContext_OCConfig::get_db_config();
        $db = new Zend_Db_Adapter_Pdo_Mysql($db_params);
	$db->getConnection();

        $data = array("doc_id" => $docID,
                      "study_id" => $this->studyID,
                      "name" => $this->name,
                      'created' => date('Y-m-d H:i:s'),
                      "xml" => $this->XMLdata,
                      "atom" => $this->atomData);
        
        $db->insert("documents", $data);
        $db->closeConnection();
    }
    
    
}
