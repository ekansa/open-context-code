<?php

class OpenContext_UserLogin {
	
	
	
	
	//make sure the email is new
	public static function isNewEmail($email){
	
	
		$PWsalt =  OpenContext_OCConfig::get_password_salt();
		$db_params = OpenContext_OCConfig::get_db_config();
		$db = new Zend_Db_Adapter_Pdo_Mysql($db_params);
				       
		$db->getConnection();
		
		$sql = "SELECT users.email FROM users WHERE users.email = '$email' ";
			
		$result = $db->fetchAll($sql, 2);
		$db->closeConnection();
		
		if($result){
			return false;	
		}
		else{
			return true;
		}
		
	}
	
	
	
	
	
	
	
	
}//end class declaration

?>
