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
class OaiController extends Zend_Controller_Action
{    
    /**
     * Passes POST/GET variables over to response generator and results out to
     * view.
     */
    public function requestAction()
    {
        $db_params = OpenContext_OCConfig::get_db_config();
        $db = new Zend_Db_Adapter_Pdo_Mysql($db_params);
                               
	$db->getConnection();
                
        $sql = "
        CREATE TABLE IF NOT EXISTS `oai_pmh_repository_tokens` (
            `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
            `verb` ENUM('ListIdentifiers', 'ListRecords', 'ListSets') COLLATE utf8_unicode_ci NOT NULL,
            `metadata_prefix` TEXT COLLATE utf8_unicode_ci NOT NULL,
            `cursor` INT(10) UNSIGNED NOT NULL,
            `from` DATETIME DEFAULT NULL,
            `until` DATETIME DEFAULT NULL,
            `set` INT(10) UNSIGNED DEFAULT NULL,
            `expiration` DATETIME NOT NULL,
            PRIMARY KEY  (`id`),
            INDEX(`expiration`)
        ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
        $db->query($sql);

        $db->closeConnection();
        
        
        
        switch($_SERVER['REQUEST_METHOD'])
        {
            case 'GET': $query = &$_GET; break;
            case 'POST': $query = &$_POST; break;
            default: die('Error determining request type.');
        }
        
        $this->view->response = new OaiPmhRepository_ResponseGenerator($query);
    }
    
    
    
    
    public function oai2PhpAction()
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
