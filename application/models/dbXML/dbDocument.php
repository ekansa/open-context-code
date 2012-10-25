<?php

class dbXML_dbDocument  {
    
    public $itemUUID;
    public $label;
    public $projectUUID;
    public $sourceID;
    
    /*
    Document / Diary / Narrative Item Specific Data
    */ 
    public $archaeoMLtype; //archaeoML media resource type (image, internal documument, external document)
    public $documentText; //document text
    public $documentTextXMLvalid; //is the document text XML valid?
    public $docURI; //link to a document
    
    
    public $propertiesObj; //object for properties
    public $linksObj; // object for links
    public $metadataObj; //object for metadata
    
    public $dbName;
    public $dbPenelope;
    public $db;
    
    public $geoLat;
    public $geoLon;
    public $geoGML;
    public $geoKML;
    public $geoSource;
    public $geoSourceName;
    
    public $chronoArray; //array of chronological tags, handled differently from Geo because can have multiple
    
    
    
    public function initialize($db = false){
        if(!$db){
            $db_params = OpenContext_OCConfig::get_db_config();
            $db = new Zend_Db_Adapter_Pdo_Mysql($db_params);
            $db->getConnection();
        }
        
        $this->db = $db;
	
	$this->archaeoMLtype = "internalDocument"; //archaeoML media resource type (image, internal documument, external document)
	$this->documentText = false;
	$this->documentTextEscaped = false;
	$this->documentTextXMLvalid = false;
	
	$this->propertiesObj = false;
	$this->linksObj = false;
	$this->metadataObj = false;
    }
    
    public function getByID($id){
        
        $this->itemUUID = $id;
        $found = false;
        
        if($this->dbPenelope){
            $found = $this->pen_itemGet();
        }
        else{
            $found = $this->oc_itemGet();
        }
        
        return $found;
    }
    
    public function pen_itemGet(){
        $found = false;
        $db = $this->db;
        
        $sql = "SELECT *
        FROM diary
        WHERE uuid = '".$this->itemUUID."' ";
        
        $result = $db->fetchAll($sql, 2);
        if($result){
            $this->projectUUID = $result[0]["project_id"];
            $this->sourceID = $result[0]["source_id"];
	    $this->label = $result[0]["diary_label"];
	    $this->documentText = $result[0]["diary_text_original"];
	    @$xml = simplexml_load_string($this->documentText);
	    if($xml){
		$this->documentTextXMLvalid = true;
	    }
	    unset($xml);
	    
	    if(isset($result[0]["diaryURI"])){
		$this->docURI = $result[0]["diaryURI"];
		$this->wordToHTML();
	    }
        }
        
        return $found;
    }
    
    public function setDocumentText(){
	if($this->propertiesObj && !$this->documentText){
	    $properties = $this->propertiesObj;
	    if(isset($properties->notes)){
		$maxLengthNote = "";
		$noteValidXML = false;
		$allNotes = $properties->notes;
		$obsNotes = $allNotes[1];
		
		$noteToDelete = false;
		if(is_array($obsNotes)){
		    foreach($obsNotes as $noteKey => $noteArray){
			if(strlen($noteArray["noteText"]) > strlen($maxLengthNote)){
			    $maxLengthNote = $noteArray["noteText"];
			    $noteValidXML = $noteArray["validForXML"];
			    $noteToDelete = $noteKey;
			}
		    }
		}
		
		if(strlen($maxLengthNote)>1 && $noteToDelete != false){
		    $this->documentText = $maxLengthNote;
		    $this->documentTextXMLvalid = $noteValidXML;
		    unset($obsNotes[$noteToDelete]);
		    $allNotes[1] = $obsNotes;
		    $properties->notes = $allNotes;
		    $this->propertiesObj = $properties;
		}
		
		
	    }

	}
	
    }
    
    
    
    public function wordToHTML(){
	
	if(stristr($this->docURI, ".doc")){
	    // AbiWord --to=html archivo.doc
	    
	    $slashedFile = str_replace("\\", "\\\\",($this->docURI));	   
	    $command = "\"C:\\Program Files (x86)\\AbiWord\\bin\\AbiWord\" --to=html ".$slashedFile;
	    exec($command);
	}
	
    }
    

    
}//end class  
