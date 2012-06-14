<?php
/**
 * @package OaiPmhRepository
 * @author John Flatness, Yu-Hsun Lin
 * @copyright Copyright 2009 John Flatness, Yu-Hsun Lin
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 */
 
require_once('OaiPmhRepositoryTokenTable.php');

/**
 * Model class for resumption tokens.
 *
 * @package OaiPmhRepository
 * @subpackage Models
 */
class OaiPmhRepositoryToken extends OaiPmhRepositoryTokenTable
{
    public $id;
    public $verb;
    public $metadata_prefix;
    public $cursor;
    public $from;
    public $until;
    public $set;
    public $expiration;
    
    public function save(){
        
        
        
    }
    
}
