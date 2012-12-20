<?php

class dbXML_dbMedia  {
    
    public $itemUUID;
    public $label;
    public $projectUUID;
    public $sourceID;
    
    /*
    Media Item Specific Data
    */ 
    public $archaeoMLtype; //archaeoML media resource type (image, internal documument, external document)
    public $MIMEtype; //mimtype for the file
    public $imageSize; //number of pixels
    public $fileSizeHuman; //human readable filesize
    public $fileSize; //size of the file (bytes)
    public $fileName; //name of the file
    public $fullURI; //URI to the full file version
    public $previewURI; //URI to the preview file version
    public $thumbURI; //URI to the thumbnail file version
    
    public $propertiesObj; //object for properties
    public $linksObj; // object for links
    public $metadataObj; //object for metadata
    
    public $mediaTypeArray = array(".jpg" => array("archaeoML" => "image",
						   "mime" => "image/jpeg"),
				   ".png" => array("archaeoML" => "image",
						   "mime" => "image/png"),
				   ".tif" => array("archaeoML" => "image",
						   "mime" => "image/tiff"),
				   ".tiff" => array("archaeoML" => "image",
						   "mime" => "image/tiff"),
				   ".pdf" => array("archaeoML" => "acrobat pdf",
						   "mime" => "application/pdf")
				  );
    
    
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
	
	$this->archaeoMLtype = false; //archaeoML media resource type (image, internal documument, external document)
	$this->MIMEtype = false; //mimtype for the file
	$this->imageSize = false; //number of pixels
	$this->fileSizeHuman = false;
	$this->fileSize = false; //size of the file (bytes)
	$this->fileName = false; //name of the file
	$this->fullURI = false; //URI to the full file version
	$this->previewURI = false; //URI to the preview file version
	$this->thumbURI = false; //URI to the thumbnail file version
	
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
        FROM resource
        WHERE uuid = '".$this->itemUUID."' ";
        
        $result = $db->fetchAll($sql, 2);
        if($result){
            $this->projectUUID = $result[0]["project_id"];
            $this->sourceID = $result[0]["source_id"];
	    $this->label = $result[0]["res_label"];
	    $this->fileName = $result[0]["res_filename"];
	    $this->archaeoMLtype = strtolower($result[0]["res_archml_type"]);
	    $this->MIMEtype = strtolower($result[0]["mime_type"]);
	    $this->imageSize = $result[0]["size"]+0;
	    $this->fileSize = $result[0]["filesize"]+0;
	    $this->thumbURI = str_replace(" ", "%20", $result[0]["ia_thumb"]);
	    $this->previewURI = str_replace(" ", "%20", $result[0]["ia_preview"]);
	    $this->fullURI= str_replace(" ", "%20", $result[0]["ia_fullfile"]);
	    
	    $this->archaeoMLClassify(); //classify mimetype and archaeoml file type if not done so
	    $this->checkUpdate_URIs_FileSize(); // check/validate URIs if filesize = 0, get filesize of full item
	    $this->fileSizeHuman = $this->formatBytes($this->fileSize); //make the human readable filesize
	    
	    if($this->imageSize == 0){
		$this->getImageData();
	    }
	
        }
        
        return $found;
    }
    
    public function oc_itemGet(){
        $found = false;
        $db = $this->db;
        
        $sql = "SELECT *
        FROM space
        WHERE uuid = '".$this->itemUUID."' ";
        
        $result = $db->fetchAll($sql, 2);
        if($result){
            $this->projectUUID = $result[0]["project_id"];
            $this->sourceID = $result[0]["source_id"];
            $this->contain_hash = $result[0]["contain_hash"];
            $this->classID = $result[0]["class_uuid"];
	    $this->label = $result[0]["space_label"];
            $this->classLabelGet($this->classID);
        }
        
        return $found;
    }
    
    
    public function archaeoMLClassify(){
	
		  if(strlen($this->archaeoMLtype)<2 || strlen($this->MIMEtype)<2){
				$db = $this->db;
				
				if(strlen($this->fileName)>1){
					 $fileTest = $this->fileName;
				}
				else{
					 $fileTest = $this->fullURI;
				}
				
				foreach($this->mediaTypeArray as $extKey => $typeArray){
					 if(stristr($fileTest, $extKey)){
						  $this->archaeoMLtype = $typeArray["archaeoML"];
						  $this->MIMEtype = $typeArray["mime"];
						  if($this->dbPenelope){
								$where = array();
								$where[] = "uuid = '".$this->itemUUID."' ";
								$data = array("res_archml_type" => $this->archaeoMLtype,
											"mime_type" => $this->MIMEtype);
								$db->update("resource", $data, $where);
						  }
						  else{
								$where = array();
								$where[] = "uuid = '".$this->itemUUID."' ";
								$data = array("res_archml_type" => $this->archaeoMLtype,
											"mime_type" => $this->MIMEtype);
								$db->update("resource", $data, $where);
						  }
						  break;
					 }
				}
		  }
	
    }//end function
    
    
    //get some metadata about an image, it it's not found yet
    public function getImageData(){
	
	if(stristr($this->MIMEtype, "image") && $this->imageSize == 0){
	    //full uri has already been validated.
	    @$image_size_array = getimagesize($this->fullURI);
	    
	    if($image_size_array){
		$FileMime = $image_size_array["mime"];
		$ImageSize = ($image_size_array[0]*$image_size_array[1]);
		$this->imageSize = $ImageSize;
		$db = $this->db;
		if($this->dbPenelope){
		    $where = array();
		    $where[] = "uuid = '".$this->itemUUID."' ";
		    $data = array("size" => $ImageSize,
				"mime_type" => $FileMime );
		    $db->update("resource", $data, $where);
		}
		else{
		    $where = array();
		    $where[] = "uuid = '".$this->itemUUID."' ";
		    $data = array("size" => $ImageSize,
				"mime_type" =>  $FileMime );
		    $db->update("resource", $data, $where);
		}
	    }
	}
    }
    
    
    
    //get the filesize of a media file. doesn't have to be an image.
    public function checkUpdate_URIs_FileSize(){
	
	if($this->fileSize < 1){
	    $this->validateURIs();
	}
	
    }//end function
    
    
    //only do this of the filesize is zero, one verification of files is enough
    public function validateURIs(){
	
	$URIs = array("ia_thumb" => $this->thumbURI,
		"ia_preview" => $this->previewURI,
		"ia_fullfile" => $this->fullURI);
	
	foreach($URIs as $key => $uri){
	    $fileSize = $this->remote_filesize($uri);
	    if($fileSize < 1){
		$uriArray = explode(".", $uri);
		$dotCount = count($uriArray);
		$lastItem = $dotCount - 1;
		$lastExtension = ".".$uriArray[$lastItem];
		$lowerExtension = strtolower($lastExtension);
		$upperExtension = strtoupper($lastExtension);
		$lowerURI = str_replace($lastExtension, $lowerExtension, $uri);
		$upperURI = str_replace($lastExtension, $upperExtension, $uri);
		if($lowerURI != $uri){
		    $newURI = $lowerURI;
		    $fileSize = $this->remote_filesize($lowerURI);
		}
		elseif($upperURI != $uri){
		    $newURI = $upperURI;
		    $fileSize = $this->remote_filesize($upperURI);
		}
		
		if($fileSize>0){
		    $doUpdate = true;
		    $data[$key] = $newURI;
		    if($key == "ia_thumb"){
			$this->thumbURI = $newURI;
		    }
		    if($key == "ia_preview"){
			$this->previewURI = $newURI;
		    }
		    if($key == "ia_fullfile"){
			$this->fullURI = $newURI;
		    }
		}
	    }
	    
	    if($key == "ia_fullfile" && $fileSize>0){
		$this->fileSize = $fileSize;
		$data["filesize"] = $fileSize;
	    }
	
	}//end loop
	
	if($this->fileSize > 0){
	//update the data     
	    $db = $this->db;
	    if($this->dbPenelope){
		$where = array();
		$where[] = "uuid = '".$this->itemUUID."' ";
		$db->update("resource", $data, $where);
	    }
	    else{
		$where = array();
		$where[] = "uuid = '".$this->itemUUID."' ";
		$db->update("resource", $data, $where);
	    } 
	}
	
    }//end function
    
    
    
    
    
public function remote_filesize($uri,$user='',$pw=''){
    // start output buffering
    ob_start();
    // initialize curl with given uri
    $ch = curl_init($uri);
    // make sure we get the header
    curl_setopt($ch, CURLOPT_HEADER, 1);
    // make it a http HEAD request
    curl_setopt($ch, CURLOPT_NOBODY, 1);
    // if auth is needed, do it here
    if (!empty($user) && !empty($pw))
    {
        $headers = array('Authorization: Basic ' .  base64_encode($user.':'.$pw)); 
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    }
    $okay = curl_exec($ch);
    curl_close($ch);
    // get the output buffer
    $head = ob_get_contents();
    // clean the output buffer and return to previous
    // buffer settings
    ob_end_clean();
   
    // gets you the numeric value from the Content-Length
    // field in the http header
    $regex = '/Content-Length:\s([0-9].+?)\s/';
    $count = preg_match($regex, $head, $matches);
   
    // if there was a Content-Length field, its value
    // will now be in $matches[1]
    if (isset($matches[1]))
    {
        $size = $matches[1];
    }
    else
    {
        $size = false;
    }
   
    return $size;
}


public function ReadHeader( $socket )
{
    $i=0;
    $header = "";
    while( true && $i<20 )
    {
        // counter [$i] is used here to avoid deadlock while reading header string
        // it's limited by [20] here cause i really haven't ever met headers with string counter greater than 20
        // *
        $s = fgets( $socket, 4096 );
        $header .= $s;

        if( strcmp( $s, "\r\n" ) == 0 || strcmp( $s, "\n" ) == 0 )
            break;
        $i++;
    }
    if( $i >= 20 )
    {
        // suspicious header strings count was read
        // *
        return false;
    }

    return $header;
}

public function remote_filesize2( $ipAddress, $url )
{
    $socket = fsockopen( $ipAddress, 80 );
    if( !$socket )
    {
        // failed to open TCP socket connection
        // do something sensible here besides exit();
        // ...
        exit();
       
    }

    // just send HEAD request to server
    // *
    fwrite( $socket, "HEAD $url HTTP/1.0\r\nConnection: Close\r\n\r\n" );
   
    // read the response header
    // *
    $header = ReadHeader( $socket );
    if( !$header )
    {
        Header( "HTTP/1.1 404 Not Found" );
        exit();
    }

    // try to acquire Content-Length within the response
    // *
    $regex = '/Content-Length:\s([0-9].+?)\s/';
    $count = preg_match($regex, $header, $matches);
  
    // if there was a Content-Length field, its value
    // will now be in $matches[1]
    if( isset( $matches[1] ) )
    {
        $size = $matches[1];
    }
    else
    {
        $size = 0;
    }
  
    fclose( $socket );
    return $size;

}


//format filesize information as human readable.
public function formatBytes($bytes, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
     
    $bytes  = $bytes  +0;
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
  
    $bytes /= pow(1024, $pow);
  
    return round($bytes, $precision) . ' ' . $units[$pow];
}

    
    
    
}  
