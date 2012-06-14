<?php
/**
 * @package OaiPmhRepository
 * @subpackage Controllers
 * @author John Flatness, Yu-Hsun Lin
 * @copyright Copyright 2009 John Flatness, Yu-Hsun Lin
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 */

/**
 * Request page controller
 * 
 * The controller for the outward-facing segment of the repository plugin.  It 
 * processes queries, and produces the response in XML format.
 *
 * @package OaiPmhRepository
 * @subpackage Controllers
 * @uses OaiPmhRepository_ResponseGenerator
 */
class OaiPmhRepository_RequestController extends Zend_Controller_Action
{    
    /**
     * Passes POST/GET variables over to response generator and results out to
     * view.
     */
    public function indexAction()
    { 
        switch($_SERVER['REQUEST_METHOD'])
        {
            case 'GET': $query = &$_GET; break;
            case 'POST': $query = &$_POST; break;
            default: die('Error determining request type.');
        }
        
        $this->view->response = new OaiPmhRepository_ResponseGenerator($query);
    }
}
