<?php

class AuthController extends Zend_Controller_Action 
{
    function init()
    {
        $this->initView();
        $this->view->baseUrl = $this->_request->getBaseUrl();
    }
        
    function indexAction(){
        //$this->_redirect('/projects');
	
	$auth = Zend_Auth::getInstance();
        if (!$auth->hasIdentity()){
            $this->view->displayNam = false;
        } 
	else{
	    $identity = $auth->getIdentity();
	    $this->view->displayName = $identity->name;
	}
	
    }//end index action
    
    
    function mediaAction(){
	
	$this->_helper->viewRenderer->setNoRender();
	$itemUUID = $_REQUEST["id"];
	$mediaItem = New Media;
	$mediaItem->getByID($itemUUID);
	$mediaItem->archaeoML_update($mediaItem->archaeoML);
	$fullAtom = $mediaItem->DOM_spatialAtomCreate($mediaItem->newArchaeoML);
	$mediaItem->update_atom_entry();
			
	header("Content-type: application/xml");
	//echo $mediaItem->newArchaeoML;
	echo $mediaItem->atomEntry;
	
    }
    
    
    
    
    public function getRegisterForm()
    {
        return new FKK_RegisterUserForm(array(
            'action' => '/auth/register-process',
            'method' => 'post',
        ));
    }

    public function getPasswordResetForm(){
        return new FKK_PasswordResetForm(array(
            'action' => '/auth/reset-process',
            'method' => 'post',
        ));
    }

    public function getUpdatePasswordForm(){
        return new FKK_UpdatePasswordForm(array(
            'action' => '/auth/update-password-process',
            'method' => 'post',
        ));
    }

    public function getAuthAdapter(array $params)
    {
        // Leaving this to the developer...
        // Makes the assumption that the constructor takes an array of
        // parameters which it then uses as credentials to verify identity.
        // Our form, of course, will just pass the parameters 'username'
        // and 'password'.
	$PWsalt =  OpenContext_OCConfig::get_password_salt();
	
	
	$auth = Zend_Auth::getInstance();
	$db_params = OpenContext_OCConfig::get_db_config();
	$db = new Zend_Db_Adapter_Pdo_Mysql($db_params);		       
	$db->getConnection();
		
	$authAdapter  = new Zend_Auth_Adapter_DbTable($db);

	$authAdapter->setTableName('users');
	$authAdapter->setIdentityColumn('email');
	$authAdapter->setCredentialColumn('password');
	
	// Set the input credential values to authenticate against
	//$authAdapter->setIdentity($params["username"])->setCredential($PWsalt.$params["password"]);
	return $authAdapter->setIdentity($params["username"])->setCredential($PWsalt.$params["password"]);	
    }
    
    /*
    public function  preDispatch()
    {
        if (Zend_Auth::getInstance()->hasIdentity()) {
            // If the user is logged in, we don't want to show the login form;
            // however, the logout action should still be available
            if ('logout' != $this->getRequest()->getActionName()) {
                $this->_helper->redirector('index', 'index');
            }
        } else {
            // If they aren't, they can't logout, so that action should
            // redirect to the login form
            if ('logout' == $this->getRequest()->getActionName()) {
                $this->_helper->redirector('index');
            }
        }
    }
    */
    
    
    function loginAction(){
        $this->view->message = '';
        if ($this->_request->isPost()) {
            // collect the data from the user
            Zend_Loader::loadClass('Zend_Filter_StripTags');
            $filter = new Zend_Filter_StripTags();
            $username = $filter->filter($this->_request->getPost('username'));
            $raw_password = $filter->filter($this->_request->getPost('password'));
            
            $user = new Users;
            $password = $user->encyptPassword($raw_password);
            
            
            if (empty($username)) {
                $this->view->message = 'Please provide a username.';
            } else {
                // setup Zend_Auth adapter for a database table
                Zend_Loader::loadClass('Zend_Auth_Adapter_DbTable');
                $db_params = OpenContext_OCConfig::get_db_config();
                $db = new Zend_Db_Adapter_Pdo_Mysql($db_params);
                $db->getConnection();
                $authAdapter = new Zend_Auth_Adapter_DbTable($db);
                $authAdapter->setTableName('users');
                $authAdapter->setIdentityColumn('email');
                $authAdapter->setCredentialColumn('password');
                
                // Set the input credential values to authenticate against
                $authAdapter->setIdentity($username);
                $authAdapter->setCredential($password);
                
                // do the authentication 
                $auth = Zend_Auth::getInstance();
                $result = $auth->authenticate($authAdapter);
                if ($result->isValid()) {
                    // success : store database row to auth's storage system
                    // (not the password though!)
                    $data = $authAdapter->getResultRowObject(null, 'password');
                    $auth->getStorage()->write($data);
                    
                    //Array to track authorized users:
                    //$authUsers = Zend_Registry::get('authUsers');
                    //$authUsers->append($username);
                    $auth = Zend_Auth::getInstance();
		    $identity = $auth->getIdentity();
		    $this->view->displayName = $identity->name;
		    
                    return $this->render('index');
                } else {
                    // failure: clear database row from session
                    $this->view->message = 'Login failed.';
                }
            }
        }
        
        $this->view->title = "Log in";
        
        
    }
    
    function logoutAction(){
        Zend_Auth::getInstance()->clearIdentity();
        return $this->render('logout');
    }
    
    function updatePasswordAction(){
	//$this->_helper->viewRenderer->setNoRender();
	//echo "bang";
	
	
	$auth = Zend_Auth::getInstance();
        if (!$auth->hasIdentity()){
            $this->_redirect('auth/login');
            return;
	    //echo "bad";
        } 
	else{
	    //echo "good";
	    // success : store database row to auth's storage system
	    // (not the password though!)
	    $identity = $auth->getIdentity();
	    //echo var_dump($idenity);
	    //$user = new Users;
	    $this->view->displayName = $identity->name;
	    
	    $form = $this->getUpdatePasswordForm();
	    //$emailElement = $form->getElement('email');
	    //$emailElement->setValue($idenity->email);
	    $this->view->email = $identity->email;
	    $this->view->form = $form;
	   // return $this->render('update-password'); // render the update form
	}
	
    }
    
    function updatePasswordProcessAction(){
	$request = $this->getRequest();

        // Check if we have a POST request
        if (!$request->isPost()) {
            return $this->_helper->redirector('update-password');
        }
	
	$form = $this->getUpdatePasswordForm();
        $validForm = $form->isValid($request->getPost());
        if(!$validForm){
            $this->view->form = $form;
            return $this->render('update-password'); // re-render the update form
        }
	else{
	    $user = new Users;
	    $email = $_REQUEST["email"];
	    $user->getUserByEmail($email);
	    $checkEncyptPass = $user->encyptPassword($_REQUEST["passwordOld"]);
	    if($checkEncyptPass != $user->encryptPass){
		$this->view->form = $form;
		$this->view->email = $user->email;
		$this->view->displayName = $user->name;
		$this->view->oldPassFail = true;
		return $this->render('update-password'); // re-render the update form
	    }
	    else{
		
		$user->updatePassword($email, $form->getValue("password"));
		Zend_Auth::getInstance()->clearIdentity();
		
		Zend_Loader::loadClass('Zend_Auth_Adapter_DbTable');
                $db_params = OpenContext_OCConfig::get_db_config();
                $db = new Zend_Db_Adapter_Pdo_Mysql($db_params);
                $db->getConnection();
                $authAdapter = new Zend_Auth_Adapter_DbTable($db);
                $authAdapter->setTableName('users');
                $authAdapter->setIdentityColumn('email');
                $authAdapter->setCredentialColumn('password');
                
                // Set the input credential values to authenticate against
                $authAdapter->setIdentity($user->email);
                $authAdapter->setCredential($user->encryptPass);
                
                // do the authentication 
                $auth = Zend_Auth::getInstance();
                $result = $auth->authenticate($authAdapter);
                if ($result->isValid()) {
                    // success : store database row to auth's storage system
                    // (not the password though!)
                    $data = $authAdapter->getResultRowObject(null, 'password');
                    $auth->getStorage()->write($data);
		    $this->view->displayName = $user->name;
		    return $this->render('update-password-done'); // re-render the update form
		}
		
	    }
	    
	}
	
    }
    
    
    function passwordResetAction(){
        $this->view->form = $this->getPasswordResetForm();
    }
    
    function resetProcessAction(){
        $request = $this->getRequest();

        // Check if we have a POST request
        if (!$request->isPost()) {
            return $this->_helper->redirector('login');
        }
        $form = $this->getPasswordResetForm();
        $validForm = $form->isValid($request->getPost());
        if(!$validForm){
            $this->view->form = $form;
            return $this->render('password-reset'); // re-render the login form
        }
        else{
	    Zend_Auth::getInstance()->clearIdentity();
            $email = $form->getValue("mail");
            $user = new Users;
            $mailOK = $user->resetPassword($email);
            $this->view->mailOK = $mailOK;
            $this->view->email = $email;
            $this->view->displayName = $user->name;
            return $this->render('password-reset-sent'); // re-render the login form
        }
    }
    
    
    /*
    New User Registration functions 
    */ 
    public function registerAction(){
	
	Zend_Auth::getInstance()->clearIdentity();
	// Display the form
	$this->view->form = $this->getRegisterForm();
        $this->view->emailExists = false;
    }
    
    
    //processes registration form
    //if fails, then give user feedback about validation errors
    //if OK then give user 
    public function  registerProcessAction(){
        $request = $this->getRequest();

        // Check if we have a POST request
        if (!$request->isPost()) {
            return $this->_helper->redirector('register');
        }

        // Get our form and validate it
        $form = $this->getRegisterForm();
        $validForm = $form->isValid($request->getPost());
        $emailExists = false;
        
        if($validForm){
            //check email
            $user = new Users;
            $emailExists = $user->getUserByEmail($form->getValue("mail"));
            if($emailExists && $user->regDone){
                //no duplication of already registered users
                $validForm = false;
                $this->view->email = $form->getValue("mail");
            }
        }
        
        if (!$validForm) {
            // Invalid entries
            $this->view->form = $form;
            $this->view->emailExists = $emailExists;
            return $this->render('register'); // re-render the login form
        }
        else{
            // We're OK to be registered! Show Registration OK page
            
            $NewUser = new Users;
            $insertOK = $NewUser->form_add_user($form);
            if(!$insertOK){
                return $this->render('register'); // re-render the login form    
            }
            
            $this->view->form = $form;
            $this->view->email = $form->getValue("mail");
            $this->view->displayName = $form->getValue("displayName");
            return $this->render('registerOk');
        }
        
    }
    
    
    //checks to see if confirmation code was OK
    public function confirmAction(){
        
	Zend_Auth::getInstance()->clearIdentity();
	$confirmCode = $_REQUEST["code"];
        $user = new Users;
        $confirmOK = $user->checkConfirm($confirmCode);
        if($confirmOK){
            $this->view->name = $user->name;
            $this->view->email = $user->email;
        }
        else{
            $this->view->confirmCode = $confirmCode;
            $this->render('confirmFailed');
        }
        
    }
    
    
    
    
}//end contoller class declaration