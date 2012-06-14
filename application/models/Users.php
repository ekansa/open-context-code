<?php


//this class interacts with the database for accessing documents
class Users {
    
    public $email;
    public $encryptPass;
    public $name;
    public $familyName;
    public $givenName;
    public $middle_name;
    public $createdTime;
    public $updatedTime;
    public $regDone;
    public $confirmCode; 
    
    const publishEmail = "publish@opencontext.org";
    
    //get User data from database
    function getUserByEmail($email){
        
        $email = $this->security_check($email);
        $output = false; //no user
        $db_params = OpenContext_OCConfig::get_db_config();
        $db = new Zend_Db_Adapter_Pdo_Mysql($db_params);
	$db->getConnection();
        
        $sql = 'SELECT *
                FROM users
                WHERE email = "'.$email.'"
                LIMIT 1';
		
        $result = $db->fetchAll($sql, 2);
        if($result){
            
            $this->email = $email;
            $this->encryptPass = $result[0]["password"];
            $this->name = $result[0]["name"];
            $this->familyName = $result[0]["family_name"];
            $this->givenName = $result[0]["given_name"];
            $this->middleName	 = $result[0]["middle_name"];
            $this->createdTime = $result[0]["created"];
            $this->updatedTime = $result[0]["updated"];
            $this->regDone = $result[0]["reg_done"];
            $this->confirmCode = $result[0]["confirm_code"];
            $output = true;
        }
        
	$db->closeConnection();
        
        return $output;
    }
    
    
    //check to see if a doc exists
    function checkConfirm($confirmCode){
        
        $confirmCode = $this->security_check($confirmCode);
        
        $db_params = OpenContext_OCConfig::get_db_config();
        $db = new Zend_Db_Adapter_Pdo_Mysql($db_params);
	$db->getConnection();
        
        $sql = 'SELECT *
                FROM users
                WHERE confirm_code = "'.$confirmCode.'"
                LIMIT 1';
		
        $result = $db->fetchAll($sql, 2);
        $db->closeConnection();
        if($result){
            $this->getUserByEmail($result[0]["email"]);
            $where = array();
            $where[] = "email = '".$this->email."'";
            $where[] = "confirm_code = '".$confirmCode."'";
            $data = array("reg_done" => true);
            $db->update("users", $data, $where);
            return true;
        
        }
        else{
            return false;
        }
    }
    
    //add user from the registration form
    function form_add_user($form){

        $db_params = OpenContext_OCConfig::get_db_config();
        $db = new Zend_Db_Adapter_Pdo_Mysql($db_params);
        $db->getConnection();
            
        $insertOK = false;        
        $email = $form->getValue("mail");
        $emailExists = $this->getUserByEmail($email);
        if(!$emailExists){

            $email = $this->security_check($email);
            $name = $this->security_check($form->getValue("displayName"));
            $encyptPassword = $this->encyptPassword($form->getValue("password"));
            $confirmCode = $this->makeConfirmCode($email);
            
            $data = array("email" => $email,
                      "name" => $name,
                      "password" => $encyptPassword,
                      'created' => date('Y-m-d H:i:s'),
                      "reg_done" => false,
                      "confirm_code" => $confirmCode);
        
            try{
                $db->insert("users", $data);
                $insertOK = true;
            } catch (Exception $e) {
                $insertOK = false;
            }    
        }//end case for new user
        elseif($emailExists && (!$this->regDone)){
            //can update a user that's not confirmed
            $email = $this->security_check($email);
            $name = $this->security_check($form->getValue("displayName"));
            $encyptPassword = $this->encyptPassword($form->getValue("password"));
            $confirmCode = $this->makeConfirmCode($email);
            $where = array();
            $where[] = "email = '".$email."' ";
            $data = array("name" => $name,
                      "password" => $encyptPassword,
                      'created' => date('Y-m-d H:i:s'),
                      "reg_done" => false,
                      "confirm_code" => $confirmCode);
            $db->update("users", $data, $where);
        }
        
        
        $db->closeConnection();
        
        if($insertOK){
            $this->emailConfirmation($name, $email, $confirmCode);
        }
    
        return $insertOK;
    }
    
    
    function emailConfirmation($name, $email, $confirmCode){
        
        $host = OpenContext_OCConfig::get_host_config();
        $emailBody = "Dear ".$name.":".chr(13);
        $emailBody .= "Thank you for registering as a user of Open Context. To confirm and complete your user-account registration, please follow the link below:".chr(13).chr(13);
        $emailBody .= $host."/auth/confirm?code=".$confirmCode.chr(13).chr(13);
        $emailBody .= "Sincerely,".chr(13);
        $emailBody .= "- Open Context Editors".chr(13);
        
        $mail = new Zend_Mail();
        try {
	    $configMail = array('auth' => 'login',
		'username' => OpenContext_OCConfig::get_PublishUserName(true),
		'password' => OpenContext_OCConfig::get_PublishPassword(true), 'port' => 465, 'ssl' => 'ssl');
		    //$transport  = new Zend_Mail_Transport_Smtp('mail.opencontext.org', $configMail);
	    $transport  = new Zend_Mail_Transport_Smtp('smtp.gmail.com', $configMail);
		    
	    $mail->setBodyText($emailBody);
	    $mail->setFrom(((OpenContext_OCConfig::get_PublishUserName(true)).'@gmail.com'), 'Open Context');
		    //$mail->setReplyTo('publishing@opencontext.org', 'Open Context Publishing');
		    //Reply-To
	    $mail->addHeader('Reply-To', self::publishEmail);
	    $mail->addHeader('X-Mailer', 'PHP/' . phpversion());
	    $mail->addTo($email, $name);
            $mail->addBCc('skansa@alexandriaarchive.org', 'Sarah Kansa');
		    
	    $mail->setSubject('Open Context Registration Confirmation');
	    $mail->send($transport);
            $mailOK = true;
	} catch (Zend_Exception $e) {
	    
            $mailOK = false;
	}
        
        return $mailOK;
    }
    
    
    function updatePassword($email, $newPassword){
	$db_params = OpenContext_OCConfig::get_db_config();
        $db = new Zend_Db_Adapter_Pdo_Mysql($db_params);
        $db->getConnection();
        $emailExists = $this->getUserByEmail($email);
        if($emailExists){
            $encyptPassword = $this->encyptPassword($newPassword);
            $this->encryptPass = $encyptPassword;
            $where = array();
            $where[] = "email = '".$email."' ";
            $data = array("password" => $encyptPassword);
            $db->update("users", $data, $where);
	    
	    
	    
	    
        }
        $db->closeConnection();
    }
    
    
    
    function resetPassword($email){
        $db_params = OpenContext_OCConfig::get_db_config();
        $db = new Zend_Db_Adapter_Pdo_Mysql($db_params);
        $db->getConnection();
        $emailExists = $this->getUserByEmail($email);
        if($emailExists){
            $tempPassword = $this->generatePassword();
            $encyptPassword = $this->encyptPassword($tempPassword);
            $this->encryptPass = $encyptPassword;
            $where = array();
            $where[] = "email = '".$email."' ";
            $data = array("password" => $encyptPassword);
	    //$data["family_name"] = $tempPassword;
            $db->update("users", $data, $where);
            $this->emailPasswordReset($tempPassword);
        }
        $db->closeConnection();
    }
    
    
    function emailPasswordReset($tempPassword){
        
        $name = $this->name;
        $confirmCode = $this->confirmCode;
        $email = $this->email;
        
        $host = OpenContext_OCConfig::get_host_config();
        $emailBody = "Dear ".$name.":".chr(13);
        $emailBody .= "You (or someone claiming to be you) requested a password reset for your Open Context user account. Your temporary password is:".chr(13).chr(13);
        $emailBody .= $tempPassword.chr(13).chr(13);
        $emailBody .= "You can use this temporary password to login and change your password and other user account information by following the link below:".chr(13).chr(13);
        //$emailBody .= $host."/auth/update-account?code=".$confirmCode.chr(13).chr(13);
	$emailBody .= $host."/auth/login".chr(13).chr(13);
        $emailBody .= "Sincerely,".chr(13);
        $emailBody .= "- Open Context Editors".chr(13);
        
        $mail = new Zend_Mail();
        try {
	    $configMail = array('auth' => 'login',
		'username' => OpenContext_OCConfig::get_PublishUserName(true),
		'password' => OpenContext_OCConfig::get_PublishPassword(true), 'port' => 465, 'ssl' => 'ssl');
		    //$transport  = new Zend_Mail_Transport_Smtp('mail.opencontext.org', $configMail);
	    $transport  = new Zend_Mail_Transport_Smtp('smtp.gmail.com', $configMail);
		    
	    $mail->setBodyText($emailBody);
	    $mail->setFrom(((OpenContext_OCConfig::get_PublishUserName(true)).'@gmail.com'), 'Open Context');
		    //$mail->setReplyTo('publish@opencontext.org', 'Open Context Publishing');
		    //Reply-To
	    $mail->addHeader('Reply-To', self::publishEmail);
	    $mail->addHeader('X-Mailer', 'PHP/' . phpversion());
	    $mail->addTo($email, $name);
            //$mail->addBCc(self::publishEmail, 'Eric Kansa');
		    
	    $mail->setSubject('Open Context Password Reset');
	    $mail->send($transport);
            $mailOK = true;
	} catch (Zend_Exception $e) {
	    $this->view->mailError = "We had an email problem!
	    Please contact ".self::publishEmail." to let us know there's a bug! <br/><span class='tinyText>'".$e."</span>";
            $mailOK = false;
	}
        
        return $mailOK;
    }
    
    
    function encyptPassword($password){
        $PWsalt =  OpenContext_OCConfig::get_password_salt();
        return md5($PWsalt.$password);
    }
    
    function makeConfirmCode($email){
        $randSalt = time().rand(0,10000);
        $confirmCode = md5($randSalt."_".$email);
        return $confirmCode;
    }
    
    function generatePassword($length=9, $strength=0) {
	$vowels = 'aeuy';
	$consonants = 'bdghjmnpqrstvz';
	if ($strength & 1) {
		$consonants .= 'BDGHJLMNPQRSTVWXZ';
	}
	if ($strength & 2) {
		$vowels .= "AEUY";
	}
	if ($strength & 4) {
		$consonants .= '23456789';
	}
	if ($strength & 8) {
		$consonants .= '@#$%';
	}
 
	$password = '';
	$alt = time() % 2;
	for ($i = 0; $i < $length; $i++) {
		if ($alt == 1) {
			$password .= $consonants[(rand() % strlen($consonants))];
			$alt = 0;
		} else {
			$password .= $vowels[(rand() % strlen($vowels))];
			$alt = 1;
		}
	}
	return $password;
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
    
}
