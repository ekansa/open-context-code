<?php

class OpenContext_OCConfig {
	
	const host_root = "opencontext.org"; //root host name
	const host = 'localhost'; // database host
	const username = ''; // database username
	const password = ''; //database username password
	const dbname = ''; //database name
	const charSet = 'UTF-8'; //database characterset
	
	const pubName = 'publish@opencontext.org'; //email account for publishing
	const pubPassword = ''; //email password for publishing
	
	const gmailName = 'opencontext.editor'; //gmail alternative email account
	const gmailPassword = ''; //gmail alternative password
	
	//const GoogleMapAPIkey = 'ABQIAAAAlNVJOUE47aImopmu4HFD1RTOaPAgc70iO5nHxAyySf8AGjxkshRkn8vKGWZ2b1nUrWYk39Mkw-UP1Q'; //google map API key
        const GoogleMapAPIkey = 'ABQIAAAA3-lTM9R7VNkgT0bRXy04ORR0QXlXjraO4EabbcoQ0DlGhWlZNRTRoLapS5f6nBWThoPzhBfoB8KcGA';
	
	const passwordSalt = ''; //this is a bit of extra security on passwords
	
	const maxTableExport = 5000; //maximum number of items users can export from table generation
	const updateCacheLife = 72000; //lifetime of the last update check
	const SpaceUpdateCache = "SpaceUpdateCache";
	const MediaUpdateCache = "MediaUpdateCache";
	
	const maxStringLength = 200000; //maximum string-length that can be saved in one database field
	const bigStringValue = "big value"; //place holder saying there's a big string to be expected
	
        //URIs for XML Schema referenced in Open Context
        const gmlURI = 'http://www.opengis.net/gml';//URI to GML namespace
        const dcURI = 'http://purl.org/dc/elements/1.1/';//URI to Dublin Core namespace
        const atomURI = "http://www.w3.org/2005/Atom"; // namespace uri for Atom
        const xhtmlURI = "http://www.w3.org/1999/xhtml";
        const georssURI = "http://www.georss.org/georss";
        const kmlURI = "http://www.opengis.net/kml/2.2";
        
        const archSpaceURI = 'http://ochre.lib.uchicago.edu/schema/SpatialUnit/SpatialUnit.xsd';//URI to ArchaeoML spatial Schema
        const archProjURI ='http://ochre.lib.uchicago.edu/schema/Project/Project.xsd';//URI to ArchaeoML project schema
        const archPersURI = "http://ochre.lib.uchicago.edu/schema/Person/Person.xsd"; //URI to the ArchaeoML person Schema
        const archPropURI = "http://ochre.lib.uchicago.edu/schema/Project/Variable.xsd"; //URI to the ArchaeoML person Schema
        const archMediaURI = "http://ochre.lib.uchicago.edu/schema/Resource/Resource.xsd";
        
        const ocSpaceURI = "http://opencontext.org/schema/space_schema_v1.xsd"; //URI to open context space schema
        const ocProjURI = 'http://opencontext.org/schema/project_schema_v1.xsd';//URI to open context project schema
        const ocPersURI = "http://opencontext.org/schema/person_schema_v1.xsd"; //URI to open context person Schema
        const ocPropURI = "http://opencontext.org/schema/property_schema_v1.xsd"; //URI to open context person Schema
        const ocMediaURI = "http://opencontext.org/schema/resource_schema_v1.xsd";
        
        const db_ocSpaceURI = "http://www.opencontext.org/database/schema/space_schema_v1.xsd"; //URI to open context space schema
        //javascript:showDetail('7465AE5C-32F3-49CF-F21B-02113DF705B3',%20'Locations%20/%20Objects')
        //key for testing.opencontext.org is:
        // ABQIAAAA3-lTM9R7VNkgT0bRXy04ORSZFp-Xk6HjLRFuBNDWMTkN7c1yLxSZUc39aSKvhbmbBZ_wxC7YSO2ZAg
        
	
	const ArkMinterURI = "http://noid.cdlib.org/nd/noidu_fake"; //URI to the CDL Ark-Noid minting service
	const PrimaryArchive = "University of California, California Digital Library";
	const PrimaryArchiveURI = "http://www.cdlib.org"; //URI to the archival institution
	
	
	
	public static function updateNamespace($xml, $itemUUID, $field, $itemType){
		
		$nsArray = array(
			"project" => "http://about.opencontext.org/schema/project_schema_v1.xsd",  
			"person" => "http://about.opencontext.org/schema/person_schema_v1.xsd",
			"media" => "http://about.opencontext.org/schema/resource_schema_v1.xsd",
			"property" => "http://about.opencontext.org/schema/property_schema_v1.xsd"
				 );
		
		$tableArray = array(
			"project" => array("tab"=>"projects", "id" => "project_id"),	
			"property" => array("tab"=>"properties", "id" => "property_uuid"),
			"person" => array("tab"=>"persons", "id" => "person_uuid"),
			"media" => array("tab"=>"resource", "id" => "res_uuid") 
		);
		
		$actNS = $nsArray[$itemType];
		if(stristr($xml, $actNS)){
			$goodNS = OpenContext_OCConfig::get_namespace("oc", $itemType);
			$xml = str_replace($actNS, $goodNS, $xml);
			@$xmlCheck = simplexml_load_string($xml);
			if($xmlCheck){
				unset($xmlCheck);
				$db_params = OpenContext_OCConfig::get_db_config();
				$db = new Zend_Db_Adapter_Pdo_Mysql($db_params);
				$db->getConnection();
				$where = array();
				$idField = $tableArray[$itemType]['id'];
				$actTable = $tableArray[$itemType]['tab'];
				$where[] = $idField.' = ".$itemUUID."';
				$data = array($field => $xml);
				$db->update($actTable, $data, $where);
				$db->closeConnection();
			}
		}
		
		return $xml;
	}
	
	
	
	
	public static function get_maxStringLength(){
		return self::maxStringLength;
	}
	
	//this function checks to see if a string is large enough to require special treatment
	public static function need_bigString($string){
		$output = false;
		if(($string == self::bigStringValue) || (strlen($string) >= self::maxStringLength)){
			$output = true;
		}
		
		return $output;
	}
	
	public static function get_bigStringValue(){
		return self::bigStringValue;
	}
	
	public static function get_maxExport(){
		
		return self::maxTableExport;
		
	}
	
	
	public static function get_PublishUserName($google = false){
		if(!$google){
			return self::pubName;
		}
		else{
			return self::gmailName;
		}
	}
	
	public static function get_PublishPassword($google = false){
		if(!$google){
			return self::pubPassword;
		}
		else{
			return self::gmailPassword;
		}
	}
	
	public static function get_PrimaryArchive(){
		return self::PrimaryArchive;
	}
	
	public static function get_PrimaryArchiveURI(){
		return self::PrimaryArchiveURI;
	}
	
	public static function get_ArkMinterURI(){
		return self::ArkMinterURI;
	}
	
	
	//this is used for a bit of extra-security
	public static function get_password_salt(){
		return self::passwordSalt;
	}
	
	
        public static function get_namespace($nsType, $itemType = false){
                
                $output = "SOMETHINGHORRIBLEXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXx";
                
                if($nsType == "arch"){
                        switch ($itemType) {    
                                case "spatial":
                                        $output = self::archSpaceURI;
                                        break;
                                case "media":
                                        $output = self::archMediaURI;
                                        break;
                                case "project":
                                        $output = self::archProjURI;
                                        break;
                                case "person":
                                        $output = self::archPersURI;
                                        break;
                                case "property":
                                        $output = self::archPropURI;
                                        break;
                        }
                }
                elseif($nsType == "oc"){
                        switch ($itemType) {    
                                case "spatial":
                                        $output = self::ocSpaceURI;
                                        break;
                                case "media":
                                        $output = self::ocMediaURI;
                                        break;
                                case "project":
                                        $output = self::ocProjURI;
                                        break;
                                case "person":
                                        $output = self::ocPersURI;
                                        break;
                                case "property":
                                        $output = self::ocPropURI;
                                        break;
                        
                        }
                }
                elseif($nsType == "db_oc"){
                        switch ($itemType) {    
                                case "spatial":
                                        $output = self::db_ocSpaceURI;
                                        break;
                                case "project":
                                        $output = self::ocProjURI;
                                        break;
                                case "person":
                                        $output = self::ocPersURI;
                                        break;
                                case "property":
                                        $output = self::ocPropURI;
                                        break;
                        
                        }
                }
                elseif($nsType == "gml"){
                        $output = self::gmlURI;
                }
                elseif($nsType == "dc"){
                        $output = self::dcURI;
                }
                elseif($nsType == "atom"){
                        $output = self::atomURI;
                }
                elseif($nsType == "xhtml"){
                        $output = self::xhtmlURI;
                }
                elseif($nsType == "georss"){
                        $output = self::georssURI;
                }
                elseif($nsType == "kml"){
                        $output = self::kmlURI;
                }
                return $output;
        }
        
        
	public static function get_host_config($want_host = true){
		
		$host_root = self::host_root;
		//$host_root = "ishmael.ischool.berkeley.edu";
		$host = "http://".$host_root;
		
		if($want_host){
			return $host;
		}
		else{
			return $host_root;
		}
		
	}//end funciton
	
	public static function get_db_config(){
		$db_params = array(
                    'host'     => self::host,
                    'username' => self::username,
                    'password' => self::password,
                    'dbname'   => self::dbname,
		    'charset' => self::charSet);
	
		return $db_params;
	}
	
        public static function get_google_api(){
                $output = self::GoogleMapAPIkey;
                return $output;
        }
        
        
        
        
        public static function gen_UUID() {
       
       
        //adapted from here: http://us3.php.net/manual/en/function.uniqid.php#88023
        
         $pr_bits = null;
         $fp = @fopen('/dev/urandom','rb');
         if ($fp !== false) {
             $pr_bits .= @fread($fp, 16);
             @fclose($fp);
         } else {
             // If /dev/urandom isn't available (eg: in non-unix systems), use mt_rand().
             $pr_bits = "";
             for($cnt=0; $cnt < 16; $cnt++){
                 $pr_bits .= chr(mt_rand(0, 255));
             }
         }
        
         $time_low = bin2hex(substr($pr_bits,0, 4));
         $time_mid = bin2hex(substr($pr_bits,4, 2));
         $time_hi_and_version = bin2hex(substr($pr_bits,6, 2));
         $clock_seq_hi_and_reserved = bin2hex(substr($pr_bits,8, 2));
         $node = bin2hex(substr($pr_bits,10, 6));
        
         /**
          * Set the four most significant bits (bits 12 through 15) of the
          * time_hi_and_version field to the 4-bit version number from
          * Section 4.1.3.
          * @see http://tools.ietf.org/html/rfc4122#section-4.1.3
          */
         $time_hi_and_version = hexdec($time_hi_and_version);
         $time_hi_and_version = $time_hi_and_version >> 4;
         $time_hi_and_version = $time_hi_and_version | 0x4000;
        
         /**
          * Set the two most significant bits (bits 6 and 7) of the
          * clock_seq_hi_and_reserved to zero and one, respectively.
          */
         $clock_seq_hi_and_reserved = hexdec($clock_seq_hi_and_reserved);
         $clock_seq_hi_and_reserved = $clock_seq_hi_and_reserved >> 2;
         $clock_seq_hi_and_reserved = $clock_seq_hi_and_reserved | 0x8000;
        
         return sprintf('%08s-%04s-%04x-%04x-%012s',
             $time_low, $time_mid, $time_hi_and_version, $clock_seq_hi_and_reserved, $node);
    }//end function
       
       
       public static function hacking_check($request_param_value){
		$bad_array = array("http://", ":2082");	
		$hack_detect = false;
		foreach($bad_array as $bad_value){
			if(substr_count($request_param_value, $bad_array)>0){
				$hack_detect = true;
				break;
			}
		}
		
       }
       
       
       //this function determines the time of last update of the
       //site
       public static function last_update($type = "space"){
	
		$frontendOptions = array('lifetime' => self::updateCacheLife,'automatic_serialization' => true );
		$backendOptions = array('cache_dir' => './cache/' );
            
		$cache = Zend_Cache::factory('Core','File',$frontendOptions,$backendOptions);	
		$cache_id = self::SpaceUpdateCache;
		$sql = "SELECT MAX(itemUpdated) AS maxupdated
		FROM noid_bindings
		";
		
		if($type == "media"){
			$cache_id = self::MediaUpdateCache;
			$sql = "SELECT MAX(itemUpdated) AS maxupdated
			FROM noid_bindings
			WHERE itemType = 'media'
			";
		}
		
		$UpdateTime= null;
	
		if(!$cache_result = $cache->load($cache_id)) {
			$db_params = OpenContext_OCConfig::get_db_config();
			$db = new Zend_Db_Adapter_Pdo_Mysql($db_params);
			$db->getConnection();
			$result = $db->fetchAll($sql, 2);
			$UpdateTime = date("Y-m-d\TH:i:s\-07:00", strtotime($result[0]["maxupdated"]));
			$db->closeConnection();
			$cache->save($UpdateTime, $cache_id); //save intial results to the cache
		}
		else{
			$UpdateTime = $cache_result;
		}
		
		return $UpdateTime;
       }       



       public static function getSolrDownMessage(){
	//this returns a message when Solr is down.
		$start_date = time() - (2 * 24 * 60 * 60);
                $end_date = time() + (7 * 24 * 60 * 60);
		$output = "<h1>Sorry for the delayed access to archaeology!</h1>";
                $output .= "<br/>Open Context is undergoing a planned upgrade from ".date('F j, Y', $start_date)." - ".date('F j, Y', $end_date).".<br/>";
		return $output;
       }
       
       
        
}//end class declaration

?>
