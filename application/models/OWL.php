<?php

/*
Manages, parses, and uses OWL ontologies
*/

class OWL {
 
public $vocab; //name of the OWL ontology from the URL (slug)
public $concept; //name-identifier (slug) for a concept referenced in the request URL
public $OWLfile; //filename for the OWL ontology
public $xml; //simple xml of the ontology

public $created; //when was the ontology first created
public $updated; //when was the ontology last updated

public $db;

const ontologyDirectory = "C:\\GitHub\\oc-ontologies\\vocabularies\\";

    function getOntology($vocab, $concept = false){
        
        $db_params = OpenContext_OCConfig::get_db_config();
        $db = new Zend_Db_Adapter_Pdo_Mysql($db_params);
		  $db->getConnection();
		  $this->setUTFconnection($db);    
        
        $this->vocab = false;
        $vocab = $this->security_check($vocab);
        $sql = "SELECT * FROM vocabularies WHERE vocab = '$vocab' LIMIT 1; ";
        $result = $db->fetchAll($sql, 2);
        if($result){
            $this->OWLfile = $result[0]["filename"];
            $this->created = $result[0]["created"];
            $this->updated = $result[0]["updated"];
            $this->vocab = $vocab;
            
            $sFilename = self::ontologyDirectory.$this->OWLfile;
            @$xmlString = $this->loadFile($sFilename);
            if($xmlString != false){
                @$xml = simplexml_load_string($xmlString);
                if($xml != false){
                    $this->xml = $xml;
                }
            }
            
        }
        
    }
    
    
    function loadFile($sFilename, $sCharset = 'UTF-8'){
        
        if (!file_exists($sFilename)){
            return false;
        }
        $rHandle = fopen($sFilename, 'r');
        if (!$rHandle){
            return false;
        }
        $sData = '';
        while(!feof($rHandle)){
            $sData .= fread($rHandle, filesize($sFilename));
        }
        fclose($rHandle);
        
        if ($sEncoding = mb_detect_encoding($sData, 'auto', true) != $sCharset){
            $sData = mb_convert_encoding($sData, $sCharset, $sEncoding);
        }
        return $sData;
    }
    

    function security_check($input){
        $badArray = array("DROP", "SELECT", "#", "--", "DELETE", "INSERT", "UPDATE", "ALTER", "=");
        foreach($badArray as $bad_word){
            if(stristr($input, $bad_word) != false){
                $input = str_ireplace($bad_word, "XXXXXX", $input);
            }
        }
        return $input;
    }
    
    
    private function setUTFconnection($db){
	    $sql = "SET collation_connection = utf8_unicode_ci;";
	    $db->query($sql, 2);
	    $sql = "SET NAMES utf8;";
	    $db->query($sql, 2);
    } 
    

}//end class

?>
