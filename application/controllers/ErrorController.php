<?php
/** Zend_Controller_Action */
require_once 'Zend/Controller/Action.php';

class ErrorController extends Zend_Controller_Action
{
    
    public function notFoundAction(){
        $errors = $this->_getParam('error_handler');
        $host = $_SERVER['HTTP_HOST'];
        $requestURI = "http://".$host.$_SERVER["REQUEST_URI"];
        $this->view->requestURI =  $requestURI;
    }
    
    public function notAvailableAction(){
        $errors = $this->_getParam('error_handler');
        $host = $_SERVER['HTTP_HOST'];
        $requestURI = "http://".$host.$_SERVER["REQUEST_URI"];
        $this->view->requestURI =  $requestURI;
    }
    
    public function errorAction()
    {
        $errors = $this->_getParam('error_handler');
        
        switch ($errors->type) {
            case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_CONTROLLER:
            case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_ACTION:
                // Page not found (404) error
                $this->render('404error');
                break;
            default:
                // Application (500) error
                $this->render('500');
                break;
                
            
        }
        
        
    }
}
