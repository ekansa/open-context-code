<?php
/**
 * @package OaiPmhRepository
 * @author John Flatness, Yu-Hsun Lin
 * @copyright Copyright 2009 John Flatness, Yu-Hsun Lin
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 */
 
/**
 * Model class for resumption token table.
 *
 * @package OaiPmhRepository
 * @subpackage Models
 */
class OaiPmhRepositoryTokenTable 
{
    /**
     * Deletes the rows for expired tokens from the table.
     */
    
    public function find($id)
    {
        $db_params = OpenContext_OCConfig::get_db_config();
        $db = new Zend_Db_Adapter_Pdo_Mysql($db_params);
                       
        $select = $select = $db->select()
             ->from( 'oai_pmh_repository_tokens' )
              ->where('id = ?', $id)
              ->limit(0, 1);
        $output = $db->query($select)->fetchAll();
        $db->closeConnection();
        return $output;
    }

    
    
    public function purgeExpiredTokens()
    {
        /* This really should just use $this->_name, but that property only
           seems to be set sporadically, particularly for plugin tables.  For
           now, the table name is hardcoded. */
        $db_params = OpenContext_OCConfig::get_db_config();
        $db = new Zend_Db_Adapter_Pdo_Mysql($db_params);
                       
        $db->getConnection();
        $db->delete("oai_pmh_repository_tokens", 'expiration <= NOW()');
        $db->closeConnection();
        
    }
}
