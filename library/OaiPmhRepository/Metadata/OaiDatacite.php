<?php
/**
 * @package OaiPmhRepository
 * @subpackage MetadataFormats
 * @author John Flatness, Yu-Hsun Lin
 * @copyright Copyright 2009 John Flatness, Yu-Hsun Lin
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 */

//require_once('Abstract.php');
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
    
    const datacite_schema_version = "3.1";
    const datacite_schema_ns = "http://datacite.org/schema/kernel-3";
    const datacite_schema_schema_loc = "http://schema.datacite.org/meta/kernel-3/metadata.xsd";
    public $doi = false;
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
                self::METADATA_NAMESPACE, 'oai_datacite:oai_datacite');
        $metadataElement->appendChild($datacite);

        /* Must manually specify XML schema uri per spec, but DOM won't include
         * a redundant xmlns:xsi attribute, so we just set the attribute
         */
        $datacite->setAttribute('xmlns:oai_datacite', self::METADATA_NAMESPACE);
        $datacite->setAttribute('xmlns:xsi', parent::XML_SCHEMA_NAMESPACE_URI);
        $datacite->setAttribute('xsi:schemaLocation', self::METADATA_NAMESPACE.' '.
            self::METADATA_SCHEMA);
        //add stuff from the datacite spec (http://schema.datacite.org/oai/oai-1.0/example/oai-sample-1.0.xml)
        $datacite_head = $this->document->createElementNS(self::METADATA_NAMESPACE, 'isReferenceQuality');
        $text = $this->document->createTextNode('true');
        $datacite_head->appendChild($text);
        $datacite->appendChild($datacite_head);
        $datacite_head = $this->document->createElementNS(self::METADATA_NAMESPACE, 'schemaVersion');
        $text = $this->document->createTextNode(self::datacite_schema_version);
        $datacite_head->appendChild($text);
        $datacite->appendChild($datacite_head);
        //now the payload
        $datapayload = $this->document->createElementNS(self::METADATA_NAMESPACE, 'payload');
        $datacite->appendChild($datapayload);
        $resource = $this->document->createElementNS(self::datacite_schema_ns, 'datacite:resource');
        $datapayload->appendChild($resource);
        $resource->setAttribute('xmlns:datacite', self::datacite_schema_ns);
        $resource->setAttribute('xmlns:xsi', parent::XML_SCHEMA_NAMESPACE_URI);
        $resource->setAttribute('xsi:schemaLocation', self::datacite_schema_ns.' '.self::datacite_schema_schema_loc);
        foreach($this->item->dc_metadata as $meta){
            if ($meta->element == "doi" && $meta->value != false){
                $id = $this->document->createElementNS(self::datacite_schema_ns, 'identifier');
                $id->setAttribute('identifierType', 'DOI');
                $text = $this->document->createTextNode($meta->value);
                $id->appendChild($text);
                $resource->appendChild($id);
                $doi = $meta->value;
                break;
            }
        }
        foreach($this->item->dc_metadata as $meta){
            if ($meta->element == "identifier" && $meta->value != false){
                $id = $this->document->createElementNS(self::datacite_schema_ns, 'alternateIdentifier');
                $id->setAttribute('alternateIdentifierType', 'URI');
                $text = $this->document->createTextNode($meta->value);
                $id->appendChild($text);
                $resource->appendChild($id);
                break;
            }
        }
        $creators = false;
        foreach($this->item->dc_metadata as $meta){
            if ($meta->element == "creator" && $meta->value != false){
                if(!$creators){
                    $creators = $this->document->createElementNS(self::datacite_schema_ns, 'creators');
                    $resource->appendChild($creators);
                }
                $creator = $this->document->createElementNS(self::datacite_schema_ns, 'creator');
                $creatorName = $this->document->createElementNS(self::datacite_schema_ns, 'creatorName');
                $text = $this->document->createTextNode($meta->value);
                $creatorName->appendChild($text);
                $creator->appendChild($creatorName);
                $creators->appendChild($creator);
            }
        }
        foreach($this->item->dc_metadata as $meta){
            if ($meta->element == "title" && $meta->value != false){
                $titles = $this->document->createElementNS(self::datacite_schema_ns, 'titles');
                $resource->appendChild($titles);
                $title = $this->document->createElementNS(self::datacite_schema_ns, 'title');
                $text = $this->document->createTextNode($meta->value);
                $title->appendChild($text);
                $titles->appendChild($title);
                break;
            }
        }
        foreach($this->item->dc_metadata as $meta){
            if ($meta->element == "publisher" && $meta->value != false){
                $pub = $this->document->createElementNS(self::datacite_schema_ns, 'publisher');
                $resource->appendChild($pub);
                $text = $this->document->createTextNode($meta->value);
                $pub->appendChild($text);
                break;
            }
        }
        foreach($this->item->dc_metadata as $meta){
            if ($meta->element == "date" && $meta->value != false){
                $date = $this->document->createElementNS(self::datacite_schema_ns, 'publicationYear');
                $resource->appendChild($date);
                $text = $this->document->createTextNode(date('Y',strtotime($meta->value)));
                $date->appendChild($text);
                break;
            }
        }
        $subjects = false;
        foreach($this->item->dc_metadata as $meta){
            if ($meta->element == "subject" && $meta->value != false){
                if(!$subjects){
                    $subjects = $this->document->createElementNS(self::datacite_schema_ns, 'subjects');
                    $resource->appendChild($subjects);
                }
                $subject = $this->document->createElementNS(self::datacite_schema_ns, 'subject');
                $text = $this->document->createTextNode($meta->value);
                $subject->appendChild($text);
                $subjects->appendChild($subject);
            }
        }
        $lang = $this->document->createElementNS(self::datacite_schema_ns, 'language');
        $resource->appendChild($lang);
        $text = $this->document->createTextNode('eng');
        $lang->appendChild($text);
        foreach($this->item->dc_metadata as $meta){
            if ($meta->element == "resourceType" && $meta->value != false){
                $restype = $this->document->createElementNS(self::datacite_schema_ns, 'resourceType');
                $restype->setAttribute('resourceTypeGeneral', $meta->value);
                $resource->appendChild($restype);
                $text = $this->document->createTextNode($meta->value);
                $restype->appendChild($text);
                break;
            }
        }
        $descriptions = false;
        foreach($this->item->dc_metadata as $meta){
            if ($meta->element == "description" && $meta->value != false){
                if(!$descriptions){
                    $descriptions = $this->document->createElementNS(self::datacite_schema_ns, 'descriptions');
                    $resource->appendChild($descriptions);
                }
                $description = $this->document->createElementNS(self::datacite_schema_ns, 'description');
                $description->setAttribute('descriptionType', 'Abstract');
                $text = $this->document->createTextNode($meta->value);
                $description->appendChild($text);
                $descriptions->appendChild($description);
            }
        }
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
