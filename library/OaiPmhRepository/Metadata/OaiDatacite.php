<?php
/**
 * @package OaiPmhRepository
 * @subpackage MetadataFormats
 * @author John Flatness, Yu-Hsun Lin
 * @copyright Copyright 2009 John Flatness, Yu-Hsun Lin
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 */

require_once('Abstract.php');
//require_once HELPERS;

/**
 * Class implmenting metadata output for the required oai_dc metadata format.
 * oai_dc is output of the 15 unqualified Dublin Core fields.
 *
 * @package OaiPmhRepository
 * @subpackage Metadata Formats
 */
class OaiPmhRepository_Metadata_OaiDatacite extends OaiPmhRepository_Metadata_Abstract
{
    /** OAI-PMH metadata prefix */
    const METADATA_PREFIX = 'oai_datacite';
    
    /** XML namespace for output format */
    const METADATA_NAMESPACE = "http://schema.datacite.org/oai/oai-1.0/";
    
    /** XML schema for output format */
    const METADATA_SCHEMA = "http://schema.datacite.org/oai/oai-1.0/";
    
    const METADATA_SCHEMA_SUFFIX = "oai_datacite.xsd";
    
    /**
     * Appends Data Cite metadata. 
     *
     * Appends a metadata element, an child element with the required format,
     * and further children for each of the Dublin Core fields present in the
     * item.
     */
    public function appendMetadata() 
    {
        $metadataElement = $this->document->createElement('metadata');
        $this->parentElement->appendChild($metadataElement);   
        
        $datacite = $this->document->createElementNS(
                self::METADATA_NAMESPACE, 'datacite:oai_datacite');
        $metadataElement->appendChild($datacite);

        /* Must manually specify XML schema uri per spec, but DOM won't include
         * a redundant xmlns:xsi attribute, so we just set the attribute
         */
        $datacite->setAttribute('xmlns:datacite', self::METADATA_NAMESPACE);
        $datacite->setAttribute('xmlns:xsi', parent::XML_SCHEMA_NAMESPACE_URI);
        $datacite->setAttribute('xsi:schemaLocation', self::METADATA_NAMESPACE.' '.
            self::METADATA_SCHEMA);

        
    }
    
    /**
     * Returns the OAI-PMH metadata prefix for the output format.
     *
     * @return string Metadata prefix
     */
    public function getMetadataPrefix()
    {
        return self::METADATA_PREFIX;
    }
    
    /**
     * Returns the XML schema for the output format.
     *
     * @return string XML schema URI
     */
    public function getMetadataSchema()
    {
        return self::METADATA_SCHEMA;
    }
    
    /**
     * Returns the XML namespace for the output format.
     *
     * @return string XML namespace URI
     */
    public function getMetadataNamespace()
    {
        return self::METADATA_NAMESPACE;
    }
}
