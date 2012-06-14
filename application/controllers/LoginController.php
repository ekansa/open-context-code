<?php

/** Zend_Controller_Action */
//require_once 'Zend/Controller/Action.php';
//require_once 'OpenContext/Controller/Action/Helper/SolrAccess.php';

error_reporting(E_ALL ^ E_NOTICE);

class LoginController extends Zend_Controller_Action
{
    public function getForm()
    {
        return new RegisterUserForm(array(
            'action' => '/login/process',
            'method' => 'post',
        ));
    }

    public function getRegisterForm()
    {
        return new FKK_RegisterUserForm(array(
            'action' => '/login/register-process',
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
	
	$PWsalt = "";
	$params["username"] = "ekansa@alexandriaarchive.org";
	$params["password"] = "bean";
	// Set the input credential values to authenticate against
	//$authAdapter->setIdentity($params["username"])->setCredential($PWsalt.$params["password"]);
	return $authAdapter->setIdentity($params["username"])->setCredential($PWsalt.$params["password"]);	
    }
    
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
    
    
    public function  indexAction()
    {
        $this->view->form = $this->getForm();
    }
    
    
    public function  processAction()
    {
        $request = $this->getRequest();

        // Check if we have a POST request
        if (!$request->isPost()) {
            return $this->_helper->redirector('index');
        }

        // Get our form and validate it
        $form = $this->getForm();
        if (!$form->isValid($request->getPost())) {
            // Invalid entries
            $this->view->form = $form;
            return $this->render('index'); // re-render the login form
        }

        // Get our authentication adapter and check credentials
        $adapter = $this->getAuthAdapter($form->getValues());
        $auth    = Zend_Auth::getInstance();
        $result  = $auth->authenticate($adapter);
	
	echo var_dump($result);
	
        if (!$result->isValid()) {
            // Invalid credentials
            $form->setDescription('Invalid credentials provided');
            $this->view->form = $form;
            return $this->render('index'); // re-render the login form
        }

        // We're authenticated! Redirect to the home page
        $this->_helper->redirector('index', 'index');
    }

	
	public function  logoutAction()
    {
        Zend_Auth::getInstance()->clearIdentity();
        $this->_helper->redirector('index'); // back to login page
    }
    
    
    
    public function registerAction(){
	
	Zend_Auth::getInstance()->clearIdentity();
	// Display the form
	$this->view->form = $this->getRegisterForm();
    }
    
    
    public function  registerProcessAction()
    {
        $request = $this->getRequest();

        // Check if we have a POST request
        if (!$request->isPost()) {
            return $this->_helper->redirector('index');
        }

        // Get our form and validate it
        $form = $this->getRegisterForm();;
        if (!$form->isValid($request->getPost())) {
            // Invalid entries
            $this->view->form = $form;
            return $this->render('register'); // re-render the login form
        }

        
        // We're registered! Redirect to the home page
        $this->_helper->redirector('index', 'index');
    }
    
    
    
    
    
    
    
    
    
    
        public function forgotPasswordAction()
    {
        
        //If the user's email address has been submitted, then make a new temp activation url and email it
        if (!empty($_POST)) {
            
            $email = $_POST['email'];
            
            if (!Zend_Validate::is($email, 'EmailAddress')) {
                return $this->flash('The email address you provided is invalid.  Please enter a valid email address.');
            }
            
            $ua = new UsersActivations;
            
            $user = $this->_table->findByEmail($email);
            
            
            if ($user) {
                //Create the activation url
                try {
                    $ua->user_id = $user->id;
                    $ua->save();
                    
                    $siteTitle = get_option('site_title');
                    
                    //Send the email with the activation url
                    $url   = "http://".$_SERVER['HTTP_HOST'].$this->getRequest()->getBaseUrl().'/users/activate?u='.$ua->url;
                    $body  = "Please follow this link to reset your password:\n\n";
                    $body .= $url."\n\n";
                    $body .= "$siteTitle Administrator";
                    
                    $admin_email = get_option('administrator_email');
                    $title       = "[$siteTitle] Reset Your Password";
                    $header      = 'From: '.$admin_email. "\n" . 'X-Mailer: PHP/' . phpversion();
                    
                    mail($email,$title, $body, $header);
                    $this->flash('Your password has been emailed');
                } catch (Exception $e) {
                      $this->flash('your password has already been sent to your email address');
                }
            
            } else {
                //If that email address doesn't exist
                $this->flash('The email address you provided does not correspond to an Omeka user.');
            }
        }
    }



public function activateAction()
    {
        $hash = $this->_getParam('u');
        $ua = $this->getTable('UsersActivations')->findBySql("url = ?", array($hash), true);
            
        if (!$ua) {
            return $this->_forward('error');
        }
        
        if (!empty($_POST)) {
            try {
                if ($_POST['new_password1'] != $_POST['new_password2']) {
                    throw new Exception('Password: The passwords do not match.');
                }
                $ua->User->password = $_POST['new_password1'];
                $ua->User->active = 1;
                $ua->User->forceSave();
                $ua->delete();
                $this->redirect->goto('login');
            } catch (Exception $e) {
                $this->flashError($e->getMessage());
            }
        }
        $user = $ua->User;
        $this->view->assign(compact('user'));
    }
    
    /**
     *
     * @return void
     **/
    public function addAction()
    {
        $user = new User();
        
        try {
            if($user->saveForm($_POST)) {
                
                //$user->email = $_POST['email'];
                $this->sendActivationEmail($user);
                
                $this->flashSuccess('User was added successfully!');
                                
                //Redirect to the main user browse page
                $this->redirect->goto('browse');
            }
        } catch (Omeka_Validator_Exception $e) {
            $this->flashValidationErrors($e);
        }
    }
    
    protected function sendActivationEmail($user)
    {
        $ua = new UsersActivations;
        $ua->user_id = $user->id;
        $ua->save();
        
        // send the user an email telling them about their new user account
        $siteTitle  = get_option('site_title');
        $from       = get_option('administrator_email');
        $body       = "Welcome!\n\n"
                    . "Your account for the $siteTitle archive has been created. Please click the following link to activate your account:\n\n"
                    . WEB_ROOT . "/admin/users/activate?u={$ua->url}\n\n"
                    . "(or use any other page on the site).\n\n"
                    . "Be aware that we log you out after 15 minutes of inactivity to help protect people using shared computers (at libraries, for instance).\n\n" 
                    ."$siteTitle Administrator";
        $subject    = "Activate your account with the ".$siteTitle." Archive";
        
        $entity = $user->Entity;
        
        $mail = new Zend_Mail();
        $mail->setBodyText($body);
        $mail->setFrom($from, "$siteTitle Administrator");
        $mail->addTo($entity->email, $entity->getName());
        $mail->setSubject($subject);
        $mail->addHeader('X-Mailer', 'PHP/' . phpversion());
        $mail->send();
    }
    
    
    public function changePasswordAction()
    {
        $user = $this->findById();

        try {
            //somebody is trying to change the password
            if (!empty($_POST['new_password1']) or !empty($_POST['new_password2'])) {
                $user->changePassword($_POST['new_password1'], $_POST['new_password2'], $_POST['old_password']);
                $user->forceSave();
                $this->flashSuccess('Password was changed successfully.');
            } else {
                $this->flashError('Password field must be properly filled out.');
            }
        } catch (Exception $e) {
            $this->flashError($e->getMessage());
        }
        
        $this->redirect->goto('edit', null, null, array('id'=>$user->id));
    }
    
    public function loginAction()
    {
        // If a user is already logged in, they should always get redirected back to the dashboard.
        if ($loggedInUser = Omeka_Context::getInstance()->getCurrentUser()) {
            $this->redirect->goto('index', 'index');
        }
        
        if (!empty($_POST)) {
            
            require_once 'Zend/Session.php';
            
            $session = new Zend_Session_Namespace;
            $result = $this->authenticate();
            
            if ($result->isValid()) {
                $this->redirect->gotoUrl($session->redirect);
            }
            $this->view->assign(array('errorMessage' => $this->getLoginErrorMessages($result)));
        }
    }
    
    /**
     * This encapsulates authentication through Omeka's login mechanism. This
     *  could be abstracted into a helper class or function or something, maybe.
     *  It'd probably be easier just to add a filter somewhere that would allow a
     *  plugin writer to switch out the Auth adapter with something else.
     * 
     * @param string
     * @return void
     **/
    public function authenticate()
    {
        $username = $_POST['username'];
        $password = $_POST['password'];
        $rememberMe = $_POST['remember'];
        $db = $this->getDb();
        $dbAdapter = $db->getAdapter();
        // Authenticate against the 'users' table in Omeka.
        $adapter = new Zend_Auth_Adapter_DbTable($dbAdapter, $db->User, 'username', 'password', 'SHA1(?) AND active = 1');
        $adapter->setIdentity($username)
                    ->setCredential($password);
        $result = $this->_auth->authenticate($adapter);
        if ($result->isValid()) {
            $storage = $this->_auth->getStorage();
            $storage->write($adapter->getResultRowObject(array('id', 'username', 'role', 'entity_id')));
            $session = new Zend_Session_Namespace($storage->getNamespace());
            if ($rememberMe) {
                // Remember that a user is logged in for the default amount of 
                // time (2 weeks).
                Zend_Session::rememberMe();
            } else {
                // If a user doesn't want to be remembered, expire the cookie as
                // soon as the browser is terminated.
                Zend_Session::forgetMe();
            }
        }
        return $result;
    }
    
    /**
     * This exists to customize the messages that people see when their attempt
     * to login fails. ZF has some built-in default messages, but it seems like
     * those messages may not make sense to a majority of people using the
     * software.
     * 
     * @param Zend_Auth_Result
     * @return string
     **/
    public function getLoginErrorMessages(Zend_Auth_Result $result)
    {
        $code = $result->getCode();
        switch ($code) {
            case Zend_Auth_Result::FAILURE_IDENTITY_NOT_FOUND:
                return "Username could not be found.";
                break;
            case Zend_Auth_Result::FAILURE_CREDENTIAL_INVALID:
                return "Invalid password.";
                break;
            case Zend_Auth_Result::FAILURE_IDENTITY_AMBIGUOUS:
                // There can never be ambiguous identities b/c the 'username'
                // field is unique in the database. Not sure what this message
                // would say.
            case Zend_Auth_Result::FAILURE_UNCATEGORIZED:
                // All other potential errors fall under this code.
            default:
                return implode("\n", $result->getMessages());
                break;
        }        
    }
    

    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
}