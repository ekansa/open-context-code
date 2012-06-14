<?php

class OpenContext_OCdatabasetoatom {
		
	public static function get_host_config($want_host = true){
		
		$host_root = "opencontext";
		//$host_root = "ishmael.ischool.berkeley.edu";
		$host = "http://".$host_root;
		
		if($want_host){
			return $host;
		}
		else{
			return $host_root;
		}
		
	}
	
        
        
        
        
}//end class declaration



?>