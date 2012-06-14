<?php
/**
 * @package OaiPmhRepository
 * @subpackage MetadataFormats
 * @author John Flatness, Yu-Hsun Lin
 * @copyright Copyright 2009 John Flatness, Yu-Hsun Lin
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 */

require_once('Abstract.php');
require_once HELPERS;

/**
 * Class implmenting metadata output CDWA Lite.
 *
 * @link http://www.getty.edu/research/conducting_research/standards/cdwa/cdwalite.html
 * @package OaiPmhRepository
 * @subpackage Metadata Formats
 */
class OaiPmhRepository_Metadata_CdwaLite extends OaiPmhRepository_Metadata_Abstract
{
    /** OAI-PMH metadata prefix */
    const METADATA_PREFIX = 'cdwalite';    
    
    /** XML namespace for output format */
    const METADATA_NAMESPACE = 'http://www.getty.edu/CDWA/CDWALite';
    
    /** XML schema for output format */
    const METADATA_SCHEMA = 'http://www.getty.edu/CDWA/CDWALite/CDWALite-xsd-public-v1-1.xsd';
    
    /**
     * Appends CDWALite metadata. 
     *
     * Appends a metadata element, an child element with the required format,
     * and further children for each of the Dublin Core fields present in the
     * item.
     */
    public function appendMetadata() 
    {
        $metadataElement = $this->document->createElement('metadata');
        $this->parentElement->appendChild($metadataElement);   
        
        $cdwaliteWrap = $this->document->createElementNS(
            self::METADATA_NAMESPACE, 'cdwalite:cdwaliteWrap');
        $metadataElement->appendChild($cdwaliteWrap);

        /* Must manually specify XML schema uri per spec, but DOM won't include
         * a redundant xmlns:xsi attribute, so we just set the attribute
         */
        $cdwaliteWrap->setAttribute('xmlns:cdwalite', self::METADATA_NAMESPACE);
        $cdwaliteWrap->setAttribute('xmlns:xsi', self::XML_SCHEMA_NAMESPACE_URI);
        $cdwaliteWrap->setAttribute('xsi:schemaLocation', self::METADATA_NAMESPACE
            .' '.self::METADATA_SCHEMA);
            
        $cdwalite = $this->appendNewElement($cdwaliteWrap, 'cdwalite:cdwalite');
        /* Each of the 16 unqualified Dublin Core elements, in the order
         * specified by the oai_dc XML schema
         */
        /*$dcElementNames = array( 
                                 'publisher', 'contributor',
                                 'format', 'identifier', 'source', 'language',
                                 'relation', 'coverage', 'rights' );
                                 */
        /* ====================
         * DESCRIPTIVE METADATA
         * ====================
         */

        $descriptive = $this->appendNewElement($cdwalite, 'cdwalite:descriptiveMetadata');
        
        /* Type => objectWorkTypeWrap->objectWorkType 
         * Required.  Fill with 'Unknown' if omitted.
         */
        $types = $this->item->getElementTextsByElementNameAndSetName('Type', 'Dublin Core');
        $objectWorkTypeWrap = $this->appendNewElement($descriptive, 'cdwalite:objectWorkTypeWrap');
        if(count($types) == 0) $types[] = 'Unknown';
        foreach($types as $type)
        {
            $this->appendNewElement($objectWorkTypeWrap, 'cdwalite:objectWorkType', $type->text);
        }      
        
        /* Subject => classificationWrap->classification
         * Not required.
         */
        $subjects = $this->item->getElementTextsByElementNameAndSetName('Subject', 'Dublin Core');
        $classificationWrap = $this->appendNewElement($descriptive, 'cdwalite:classificationWrap');
        foreach($subjects as $subject)
        {
            $this->appendNewElement($classificationWrap, 'cdwalite:classification', $subject->text);
        }
        
        /* Title => titleWrap->titleSet->title
         * Required.  Fill with 'Unknown' if omitted.
         */        
        $titles = $this->item->getElementTextsByElementNameAndSetName('Title', 'Dublin Core');
        $titleWrap = $this->appendNewElement($descriptive, 'cdwalite:titleWrap');
        if(count($types) == 0) $types[] = 'Unknown';
        foreach($titles as $title)
        {
            $titleSet = $this->appendNewElement($titleWrap, 'cdwalite:titleSet');
            $this->appendNewElement($titleSet, 'cdwalite:title', $title->text);
        }
        
        /* Creator => displayCreator
         * Required.  Fill with 'Unknown' if omitted.
         * Non-repeatable, implode for inclusion of many creators.
         */
        $creators = $this->item->getElementTextsByElementNameAndSetName('Creator', 'Dublin Core');
        foreach($creators as $creator) $creatorTexts[] = $creator->text;
        $creatorText = count($creators) >= 1 ? implode(',', $creatorTexts) : 'Unknown';
        $this->appendNewElement($descriptive, 'cdwalite:displayCreator', $creatorText);
        
        /* Creator => indexingCreatorWrap->indexingCreatorSet->nameCreatorSet->nameCreator
         * Required.  Fill with 'Unknown' if omitted.
         * Also include roleCreator, fill with 'Unknown', required.
         */
        $indexingCreatorWrap = $this->appendNewElement($descriptive, 'cdwalite:indexingCreatorWrap');
        if(count($creators) == 0) $creators[] = 'Unknown';       
        foreach($creators as $creator) 
        {
            $indexingCreatorSet = $this->appendNewElement($indexingCreatorWrap, 'cdwalite:indexingCreatorSet');
            $nameCreatorSet = $this->appendNewElement($indexingCreatorSet, 'cdwalite:nameCreatorSet');
            $this->appendNewElement($nameCreatorSet, 'cdwalite:nameCreator', $creator->text);
            $this->appendNewElement($indexingCreatorSet, 'cdwalite:roleCreator', 'Unknown');
        }
        
        /* displayMaterialsTech
         * Required.  No corresponding metadata, fill with 'not applicable'.
         */
        $this->appendNewElement($descriptive, 'cdwalite:displayMaterialsTech', 'not applicable');
        
        /* Date => displayCreationDate
         * Required. Fill with 'Unknown' if omitted.
         * Non-repeatable, include only first date.
         */
        $dates = $this->item->getElementTextsByElementNameAndSetName('Date', 'Dublin Core');
        $dateText = count($dates) > 0 ? $dates[0]->text : 'Unknown';
        $this->appendNewElement($descriptive, 'cdwalite:displayCreationDate', $dateText);
        
        /* Date => indexingDatesWrap->indexingDatesSet
         * Map to both earliest and latest date
         * Required.  Fill with 'Unknown' if omitted.
         */
        $indexingDatesWrap = $this->appendNewElement($descriptive, 'cdwalite:indexingDatesWrap');   
        foreach($dates as $date)
        {
            $indexingDatesSet = $this->appendNewElement($indexingDatesWrap, 'cdwalite:indexingDatesSet');
            $this->appendNewElement($indexingDatesSet, 'cdwalite:earliestDate', $date->text);
            $this->appendNewElement($indexingDatesSet, 'cdwalite:latestDate', $date->text);
        }
        
        /* locationWrap->locationSet->locationName
         * Required. No corresponding metadata, fill with 'location unknown'.
         */
        $locationWrap = $this->appendNewElement($descriptive, 'cdwalite:locationWrap');
        $locationSet = $this->appendNewElement($locationWrap, 'cdwalite:locationSet');
        $this->appendNewElement($locationSet, 'cdwalite:locationName', 'location unknown');
        
        /* Description => descriptiveNoteWrap->descriptiveNoteSet->descriptiveNote
         * Not required.
         */
        $descriptions = $this->item->getElementTextsByElementNameAndSetName('Description', 'Dublin Core');
        if(count($descriptions) > 0)
        {
            $descriptiveNoteWrap = $this->appendNewElement($descriptive, 'cdwalite:descriptiveNoteWrap');
            foreach($descriptions as $description)
            {
                $descriptiveNoteSet = $this->appendNewElement($descriptiveNoteWrap, 'cdwalite:descriptiveNoteSet');
                $this->appendNewElement($descriptiveNoteSet, 'cdwalite:descriptiveNote', $description->text);
            }
        }
        
        /* =======================
         * ADMINISTRATIVE METADATA
         * =======================
         */
         
        $administrative = $this->appendNewElement($cdwalite, 'cdwalite:administrativeMetadata');
        
        /* Rights => rightsWork
         * Not required.
         */
        $rights = $this->item->getElementTextsByElementNameAndSetName('Rights', 'Dublin Core');
        foreach($rights as $right)
        {
            $this->appendNewElement($administrative, 'cdwalite:rightsWork', $right->text);
        }
        
        /* id => recordWrap->recordID
         * 'item' => recordWrap-recordType
         * Required.
         */     
        $recordWrap = $this->appendNewElement($descriptive, 'cdwalite:recordWrap');
        $this->appendNewElement($recordWrap, 'cdwalite:recordID', $this->item->id);
        $this->appendNewElement($recordWrap, 'cdwalite:recordType', 'item');
        $recordMetadataWrap = $this->appendNewElement($recordWrap, 'cdwalite:recordMetadataWrap');
        $recordInfoID = $this->appendNewElement($recordMetadataWrap, 'cdwalite:recordInfoID', OaiPmhRepository_OaiIdentifier::itemToOaiId($this->item->id));
        $recordInfoID->setAttribute('type', 'oai');
        
        /* file link => resourceWrap->resourceSet->linkResource
         * Not required.
         */
        if(get_option('oaipmh_repository_expose_files')) {
            $files = $this->item->getFiles();
            if(count($files) > 0) {
                $resourceWrap = $this->appendNewElement($administrative, 'cdwalite:resourceWrap');
                foreach($files as $file) 
                {
                    $resourceSet = $this->appendNewElement($resourceWrap, 'cdwalite:resourceSet');
                    $this->appendNewElement($resourceSet, 
                        'cdwalite:linkResource', $file->getWebPath('archive'));
                }
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