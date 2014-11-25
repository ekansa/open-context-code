<?php

class Datacite
{
    const datacite_schema_version = "3.1";
    const datacite_schema_ns = "http://datacite.org/schema/kernel-3";
	const xml_schema_namespace_uri = "http://www.w3.org/2001/XMLSchema-instance";
    const datacite_schema_schema_loc = "http://schema.datacite.org/meta/kernel-3/metadata.xsd";
	
	public $document;
	public $item;
	
	function make_datacite_record($projectID, $proj_date = false){
		
		$this->document = new DOMDocument('1.0', 'UTF-8');
		$this->document->formatOutput = true;
		$resource = $this->document->createElement('resource');
        $this->document->appendChild($resource);
        $resource->setAttribute('xmlns', self::datacite_schema_ns);
        $resource->setAttribute('xmlns:xsi', self::xml_schema_namespace_uri);
        $resource->setAttribute('xsi:schemaLocation', self::datacite_schema_ns.' '.self::datacite_schema_schema_loc);
		
		$ai = new AllIdentifiers;
		$this->item = $ai->make_proj_metaelements($projectID, $proj_date);
		if($this->item != false){
			foreach($this->item->dc_metadata as $meta){
				if ($meta->element == "doi" && $meta->value != false){
					$id = $this->document->createElementNS(self::datacite_schema_ns, 'identifier');
					$id->setAttribute('identifierType', 'DOI');
					$text = $this->document->createTextNode($meta->value);
					$id->appendChild($text);
					$resource->appendChild($id);
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
		
		return $this->document->saveXML();
	}
    
}
