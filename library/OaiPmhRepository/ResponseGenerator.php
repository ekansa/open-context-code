<?php
/**
 * @package OaiPmhRepository
 * @subpackage Libraries
 * @author John Flatness, Yu-Hsun Lin
 * @copyright Copyright 2009 John Flatness, Yu-Hsun Lin
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 */

require_once('OaiXmlGeneratorAbstract.php');
require_once('OaiIdentifier.php');
require_once('XmlGeneratorAbstract.php');
require_once('OaiPmhRepositoryToken.php');

define('OC_OAI_PMH_BASE_URL', (OpenContext_OCConfig::get_host_config()."/oai/request"));


/**
 * OaiPmhRepository_ResponseGenerator generates the XML responses to OAI-PMH
 * requests recieved by the repository.  The DOM extension is used to generate
 * all the XML output on-the-fly.
 *
 * @package OaiPmhRepository
 * @subpackage Libraries
 */
class OaiPmhRepository_ResponseGenerator extends OaiPmhRepository_OaiXmlGeneratorAbstract
{
    /**
     * HTTP query string or POST vars formatted as an associative array.
     * @var array
     */
    private $query;
    
    /**
     * Array of all supported metadata formats.
     * $metdataFormats['metadataPrefix'] = ImplementingClassName
     * @var array
     */
    private $metadataFormats;

    /**
     * Constructor
     *
     * Creates the DomDocument object, and adds XML elements common to all
     * OAI-PMH responses.  Dispatches control to appropriate verb, if any.
     *
     * @param array $query HTTP POST/GET query key-value pair array.
     * @uses dispatchRequest()
     */
    public function __construct($query)
    {
        $this->error = false;
        $this->query = $query;
        $this->document = new DomDocument('1.0', 'UTF-8');
        
        //echo var_dump($query);
        
        //formatOutput makes DOM output "pretty" XML.  Good for debugging, but
        //adds some overhead, especially on large outputs.
        $this->document->formatOutput = true;
        $this->document->xmlStandalone = true;
        
        $root = $this->document->createElementNS(self::OAI_PMH_NAMESPACE_URI,
            'OAI-PMH');
        $this->document->appendChild($root);
        
        $root->setAttributeNS(self::XML_SCHEMA_NAMESPACE_URI, 'xsi:schemaLocation',
            self::OAI_PMH_NAMESPACE_URI.' '.self::OAI_PMH_SCHEMA_URI);
    
        $responseDate = $this->document->createElement('responseDate', 
            self::unixToUtc(time()));
        $root->appendChild($responseDate);
        
        $this->metadataFormats = $this->getFormats();
        
        $this->dispatchRequest();
    }
    
    /**
     * Parses the HTTP query and dispatches to the correct verb handler.
     *
     * Checks arguments for each verb type, and sets XML request tag.
     *
     * @uses checkArguments()
     */
    private function dispatchRequest()
    {
        $request = $this->document->createElement('request',
            OC_OAI_PMH_BASE_URL);
        $this->document->documentElement->appendChild($request);
        
        $requiredArgs = array();
        $optionalArgs = array();
        
        $verb = $this->query['verb'];
        
        if(array_key_exists('resumptionToken', $this->query)){
            $resumptionToken = $this->query['resumptionToken'];
        }
        else{
           $resumptionToken = false; 
        }
        
        if($resumptionToken)
            $requiredArgs = array('resumptionToken');
        else
            switch($verb)
            {
                case 'Identify':
                    break;
                case 'GetRecord':
                    $requiredArgs = array('identifier', 'metadataPrefix');
                    break;
                case 'ListRecords':
                    $requiredArgs = array('metadataPrefix');
                    $optionalArgs = array('from', 'until', 'set');
                    break;
                case 'ListIdentifiers':
                    $requiredArgs = array('metadataPrefix');
                    $optionalArgs = array('from', 'until', 'set');
                    break;                
                case 'ListSets':
                    break;
                case 'ListMetadataFormats':
                    $optionalArgs = array('identifier');
                    break;
                default:
                    $this->throwError(self::OAI_ERR_BAD_VERB);
            }
        
        $this->checkArguments($requiredArgs, $optionalArgs);
        
        if(!$this->error) {
            foreach($this->query as $key => $value)
                $request->setAttribute($key, $value);
                
            if($resumptionToken)
                $this->resumeListResponse($resumptionToken);
            /* ListRecords and ListIdentifiers use a common code base and share
               all possible arguments, and are handled by one function. */
            else if($verb == 'ListRecords' || $verb == 'ListIdentifiers')
                $this->initListResponse();
            else {
                /* This Inflector use means verb-implementing functions must be
                   the lowerCamelCased version of the verb name. */
                $functionName = strtolower($verb);
                
                //$functionName = Inflector::variablize($verb);
                $this->$functionName();
            }
        }
    }
    
    /**
     * Checks the argument list from the POST/GET query.
     *
     * Checks if the required arguments are present, and no invalid extra
     * arguments are present.  All valid arguments must be in either the
     * required or optional array.
     *
     * @param array requiredArgs Array of required argument names.
     * @param array optionalArgs Array of optional, but valid argument names.
     */
    private function checkArguments($requiredArgs = array(), $optionalArgs = array())
    {
        $requiredArgs[] = 'verb';
        
        /* Checks (essentially), if there are more arguments in the query string
           than in PHP's returned array, if so there were duplicate arguments,
           which is not allowed. */
        $len_1 = strlen(urldecode($_SERVER['QUERY_STRING']));
        $stringA = urldecode($_SERVER['QUERY_STRING']);
        $stringA = str_replace("&", "&amp;", $stringA);
        $len_1 = strlen($stringA);
        $len_2 = strlen(urldecode(http_build_query($this->query)));
        
        /*
        if($_SERVER['REQUEST_METHOD'] == 'GET' && ($stringA != urldecode(http_build_query($this->query))))
            $this->throwError(self::OAI_ERR_BAD_ARGUMENT, "Duplicate arguments in request. ".$len_1." is not ".$len_2 );
        */
        
        $keys = array_keys($this->query);
        
        foreach(array_diff($requiredArgs, $keys) as $arg)
            $this->throwError(self::OAI_ERR_BAD_ARGUMENT, "Missing required argument $arg.");
        foreach(array_diff($keys, $requiredArgs, $optionalArgs) as $arg)
            $this->throwError(self::OAI_ERR_BAD_ARGUMENT, "Unknown argument $arg.");
                
        
        $from = false;
        $until = false;
        if(array_key_exists('from', $this->query)){
            $from = $this->query['from'];
        }
        if(array_key_exists('until', $this->query)){
            $until = $this->query['until'];
        }
        
        $fromGran = self::getGranularity($from);
        $untilGran = self::getGranularity($until);
        
        if($from && !$fromGran)
            $this->throwError(self::OAI_ERR_BAD_ARGUMENT, "Invalid date/time argument.");
        if($until && !$untilGran)
            $this->throwError(self::OAI_ERR_BAD_ARGUMENT, "Invalid date/time argument.");
        if($from && $until && $fromGran != $untilGran)
            $this->throwError(self::OAI_ERR_BAD_ARGUMENT, "Date/time arguments of differing granularity.");
        
        $metadataPrefix = false;
        if(array_key_exists('from', $this->query)){        
            $metadataPrefix = $this->query['metadataPrefix'];
            if($metadataPrefix != "oai_dc"){
                $this->throwError(self::OAI_ERR_CANNOT_DISSEMINATE_FORMAT);
            }
        }
        
        if($metadataPrefix && !array_key_exists($metadataPrefix, $this->metadataFormats))
            $this->throwError(self::OAI_ERR_CANNOT_DISSEMINATE_FORMAT);
            
    }
    
    
    /**
     * Responds to the Identify verb.
     *
     * Appends the Identify element for the repository to the response.
     */
    public function identify()
    {
        if($this->error)
            return;
        
        /* according to the schema, this order of elements is required for the
           response to validate */
        $elements = array( 
            'repositoryName'    => 'Open Context',
            'baseURL'           => OC_OAI_PMH_BASE_URL,
            'protocolVersion'   => self::OAI_PMH_PROTOCOL_VERSION,
            'adminEmail'        => 'contact@opencontext.org',
            'earliestDatestamp' => self::unixToUtc(0),
            'deletedRecord'     => 'no',
            'granularity'       => self::OAI_GRANULARITY_STRING);
        $identify = $this->createElementWithChildren(
            $this->document->documentElement, 'Identify', $elements);
        
        $description = $this->document->createElement('description');
        $identify->appendChild($description);
        
        OaiPmhRepository_OaiIdentifier::describeIdentifier($description);
    }
    
    /**
     * Responds to the GetRecord verb.
     *
     * Outputs the header and metadata in the specified format for the specified
     * identifier.
     */
    private function getRecord()
    {
        $identifier = $this->query['identifier'];
        $metadataPrefix = $this->query['metadataPrefix'];
        
        $itemId = OaiPmhRepository_OaiIdentifier::oaiIdToItem($identifier);
        
        if(!$itemId) {
            $this->throwError(self::OAI_ERR_ID_DOES_NOT_EXIST);
            return;
        }
        $all_Itentifiers = new AllIdentifiers();
        $all_Itentifiers->get_projects_categories("", "", "", $itemId);
        $all_items = $all_Itentifiers->Records;
        $item = $all_items[0];

        if(!$item) {
            $this->throwError(self::OAI_ERR_ID_DOES_NOT_EXIST);
        }

        if(!$this->error) {
            $getRecord = $this->document->createElement('GetRecord');
            $this->document->documentElement->appendChild($getRecord);
            $record = new $this->metadataFormats[$metadataPrefix]($item, $getRecord);
            $record->appendRecord();
        }
    }
    
    /**
     * Responds to the ListMetadataFormats verb.
     *
     * Outputs records for all of the items in the database in the specified
     * metadata format.
     *
     * @todo extend for additional metadata formats
     */
    private function listMetadataFormats()
    {
        $identifier = false;
        if(array_key_exists('identifier', $this->query)){
            $identifier = $this->query['identifier'];
        }
        /* Items are not used for lookup, simply checks for an invalid id */
        if($identifier) {
            $itemId = OaiPmhRepository_OaiIdentifier::oaiIdToItem($identifier);
        
            if(!$itemId) {
                $this->throwError(self::OAI_ERR_ID_DOES_NOT_EXIST);
                return;
            }
        }
        if(!$this->error) {
            $listMetadataFormats = $this->document->createElement('ListMetadataFormats');
            $this->document->documentElement->appendChild($listMetadataFormats);
            foreach($this->metadataFormats as $format) {
                $formatObject = new $format(null, $listMetadataFormats);
                $formatObject->declareMetadataFormat();
            }
        }
    }

    /**
     * Responds to the ListSets verb.
     *
     * Outputs setSpec and setName for all OAI-PMH sets (Omeka collections).
     *
     * @todo replace with Zend_Db_Select to allow use of limit or pageLimit
     */
    private function listSets()
    {
        
        $AllCollections = new AllCollections();
        //$AllCollections->getCollections();
        $AllCollections->getSets();
        $collections = $AllCollections->collections;
        
        if(count($collections) == 0)
            $this->throwError(self::OAI_ERR_NO_SET_HIERARCHY." ".var_dump($collections));
            
        $listSets = $this->document->createElement('ListSets');     

        if(!$this->error) {
            $this->document->documentElement->appendChild($listSets); 
            foreach ($collections as $collection) {
                $elements = array( 'setSpec' => $collection->id,
                                   'setName' => $collection->name );
                $this->createElementWithChildren($listSets, 'set', $elements);
            }
        }
    }
    
    /**
     * Responds to the ListIdentifiers and ListRecords verbs.
     *
     * Only called for the initial request in the case of multiple incomplete
     * list responses
     *
     * @uses listResponse()
     */
    private function initListResponse()
    {
        $from = $this->query['from'];
        $until = $this->query['until'];
        
        if($from)
            $fromDate = self::utcToDb($from);
        if($until)
            $untilDate = self::utcToDb($until);
        
        $this->listResponse($this->query['verb'], 
                            $this->query['metadataPrefix'],
                            0,
                            $this->query['set'],
                            $fromDate,
                            $untilDate);
    }
    
    /**
     * Returns the next incomplete list response based on the given resumption
     * token.
     *
     * @param string $token Resumption token
     * @uses listResponse()
     */
    private function resumeListResponse($token)
    {
        
        $db_params = OpenContext_OCConfig::get_db_config();
        $db = new Zend_Db_Adapter_Pdo_Mysql($db_params);
                       
        $db->getConnection();
        $tokenTable = new OaiPmhRepositoryTokenTable();
        $tokenTable->purgeExpiredTokens();
        
        $tokenObject = $tokenTable->find($token);
        
        $db->closeConnection();
        
        if(!$tokenObject || ($tokenObject->verb != $this->query['verb']))
            $this->throwError(self::OAI_ERR_BAD_RESUMPTION_TOKEN);
        else
            $this->listResponse($tokenObject->verb,
                                $tokenObject->metadata_prefix,
                                $tokenObject->cursor,
                                $tokenObject->set,
                                $tokenObject->from,
                                $tokenObject->until);
    }
    
    /**
     * Responds to the two main List verbs, includes resumption and limiting.
     *
     * @param string $verb OAI-PMH verb for the request
     * @param string $metadataPrefix Metadata prefix
     * @param int $cursor Offset in response to begin output at
     * @param mixed $set Optional set argument
     * @param string $from Optional from date argument
     * @param string $until Optional until date argument
     * @uses createResumptionToken()
     */
    private function listResponse($verb, $metadataPrefix, $cursor, $set, $from, $until) {
        //$listLimit = get_option('oaipmh_repository_list_limit');
        $listLimit = 500;
       
        if($metadataPrefix == "oai_dc"){ 
            $all_itentifiers = new AllIdentifiers();
            $all_itentifiers->get_projects_categories($set, $from, $until);
            $items = $all_itentifiers->Records;
            $rows = count($items);
            
            if(count($items) == 0)
                $this->throwError(self::OAI_ERR_NO_RECORDS_MATCH, 'No records match the given criteria');
    
            else {
                if($verb == 'ListIdentifiers')
                    $method = 'appendHeader';
                else if($verb == 'ListRecords')
                    $method = 'appendRecord';
                
                $verbElement = $this->document->createElement($verb);
                $this->document->documentElement->appendChild($verbElement);
                foreach($items as $item) {
                    $record = new $this->metadataFormats[$metadataPrefix]($item, $verbElement);
                    $record->$method();
                }
                if($rows > ($cursor + $listLimit)) {
                    $token = $this->createResumptionToken($verb,
                                                          $metadataPrefix,
                                                          $cursor + $listLimit,
                                                          $from,
                                                          $until,
                                                          $set);
    
                    $tokenElement = $this->document->createElement('resumptionToken', $token->id);
                    $tokenElement->setAttribute('expirationDate',
                        self::dbToUtc($token->expiration));
                    $tokenElement->setAttribute('completeListSize', $rows);
                    $tokenElement->setAttribute('cursor', $cursor);
                    $verbElement->appendChild($tokenElement);
                }
                else if($cursor != 0) {
                    $tokenElement = $this->document->createElement('resumptionToken');
                    $verbElement->appendChild($tokenElement);
                }
            }
        }
        else{
            $this->throwError(self::OAI_ERR_CANNOT_DISSEMINATE_FORMAT);
        }
    }
        
    /**
     * Stores a new resumption token record in the database
     *
     * @param string $verb OAI-PMH verb for the request
     * @param string $metadataPrefix Metadata prefix
     * @param int $cursor Offset in response to begin output at
     * @param mixed $set Optional set argument
     * @param string $from Optional from date argument
     * @param string $until Optional until date argument
     * @return OaiPmhRepositoryToken Token model object
     */
    private function createResumptionToken($verb, $metadataPrefix, $cursor, $set, $from, $until)
    {
        
        $db_params = OpenContext_OCConfig::get_db_config();
        $db = new Zend_Db_Adapter_Pdo_Mysql($db_params);
        
        //$tokenTable = get_db()->getTable('OaiPmhRepositoryToken');
        $tokenTable = $db->getTable('OaiPmhRepositoryToken');
        
        $resumptionToken = new OaiPmhRepositoryToken();
        $resumptionToken->verb = $verb;
        $resumptionToken->metadata_prefix = $metadataPrefix;
        $resumptionToken->cursor = $cursor;
        if($set)
            $resumptionToken->set = $set;
        if($from)
            $resumptionToken->from = $from;
        if($until)
            $resumptionToken->until = $until;
        $resumptionToken->expiration = self::unixToDb(
            time() + (get_option('oaipmh_repository_expiration_time') * 60 ) );
        $resumptionToken->save();
        
        return $resumptionToken;
    }
    
    
    /**
     * Builds an array of entries for all included metadata mapping classes.
     * Derived heavily from OaipmhHarvester's getMaps().
     *
     * @return array An array, with metadataPrefix => class.
     */
    
    
    private function getFormats()
    {
        
        /*
        $metadataFormats = array();
        $metadataFormats["oai_dc"] = "oai_dc"; 
        */
        //$dirLocation = OC_OAI_PMH_REPOSITORY_METADATA_DIRECTORY;
        $dirLocation = "metadata";
        $dir = new DirectoryIterator($dirLocation);
        $metadataFormats = array();
        foreach ($dir as $dirEntry) {
            if ($dirEntry->isFile() && !$dirEntry->isDot()) {
                $filename = $dirEntry->getFilename();
                $pathname = $dirEntry->getPathname();
                // Check for all PHP files, ignore the abstract class
                if(preg_match('/^(.+)\.php$/', $filename, $match) && $match[1] != 'Abstract') {
                    require_once($pathname);
                    $class = "OaiPmhRepository_Metadata_${match[1]}";
                    $object = new $class(null, null);
                    $metadataFormats[$object->getMetadataPrefix()] = $class;
                }
            }
        }
        
        return $metadataFormats;
    }
    
    /**
     * Outputs the XML response as a string
     *
     * Called once processing is complete to return the XML to the client.
     *
     * @return string the response XML
     */
    public function __toString()
    {
        return $this->document->saveXML();
    }
}
