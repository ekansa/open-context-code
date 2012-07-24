<?php


//this class highlights search terms
class SortOptions{
    
    public $requestParams; //the output snippet, with search terms highlighted
	 
    public $sortingArray = array(0 => array("value" => "context", "display" => "Context"),
                      1 => array("value" => "proj", "display" => "Project name"),
                      2 => array("value" => "label", "display" => "Item label"),
                      3 => array("value" => "updated", "display" => "Updated time"),
                      4 => array("value" => "created", "display" => "Publication time"),
                );
	 
	 
    public function makeSortLinks($defaultType = "Interest Score [Default]"){
		  $host = OpenContext_OCConfig::get_host_config();
		  $requestParams = $this->requestParams;
		  $sortingArray = $this->sortingArray;
		  if(isset($requestParams["sort"])){
				$newSort = array();
				$newSort[] = array("value" => false, "display" => $defaultType);
				$skip = 0;
				foreach($sortingArray as $sorter){
					 if(strstr($requestParams["sort"], $sorter["value"])){
						  $skip++;
					 }
					 if($skip != 1){
						  $newSort[] = $sorter;
					 }
					 else{
						  $skip = 2;
					 }
				}
				$sortingArray = $newSort;
				unset($newSort);
		  }
		  
		  
		  $dom = new DOMDocument("1.0", "utf-8");
		  $dom->formatOutput = true;
		  $root = $dom->createElement("div");
		  $root->setAttribute("id", "sort_tab");
		  $dom->appendChild($root);
		  
		  $elementRow = $dom->createElement("div");
		  $elementRow->setAttribute("id", "sort_row");
		  $root->appendChild($elementRow);
		  
		  $elementStart = $dom->createElement("div");
		  $elementStart->setAttribute("id", "sort_start");
		  $elementStartText = $dom->createTextNode("Sort By:");
		  $elementStart->appendChild($elementStartText);
		  $elementRow->appendChild($elementStart);
		  
		  foreach($sortingArray as $sorter){
            $sortLink = $host.OpenContext_FacetOutput::generateFacetURL($requestParams, "sort", $sorter["value"], false);
					
				$element = $dom->createElement("div");
				$element->setAttribute("class", "sort_cell");
				
				$elementB = $dom->createElement("a");
				$elementB->setAttribute("href", $sortLink );
				$elementB->setAttribute("title", "Sort results by ".$sorter["display"]);
				$elementBText = $dom->createTextNode($sorter["display"]);
				$elementB->appendChild($elementBText);
		  
				$element->appendChild($elementB);
				$elementRow->appendChild($element);
		  }
		  
		  return $dom->saveXML($root);
		  
	 }


	 public function makeFacetSortLinks(){
		  $requestParams = $this->requestParams;
		  $host = OpenContext_OCConfig::get_host_config();
		  
		  if(isset($requestParams["facetSort"])){
				$facetSort = "10,9,8 ↓";
				$facetSortMessage = "Sort facets by count:";
				unset($requestParams["facetSort"]);
				$facetSortLink = $host.OpenContext_FacetOutput::generateFacetURL($requestParams, false, false, false);
		  }
		  else{
				$facetSort = "A,B,C ↓";
				$facetSortMessage = "Sort facets alphabetically:";
				$facetSortLink = $host.OpenContext_FacetOutput::generateFacetURL($requestParams, "facetSort", true, false);
		  }
		  
		  $dom = new DOMDocument("1.0", "utf-8");
		  $dom->formatOutput = true;
		  $root = $dom->createElement("div");
		  $root->setAttribute("id", "facet_sort_tab");
		  $dom->appendChild($root);
		  
		  $elementRow = $dom->createElement("div");
		  $elementRow->setAttribute("id", "facet_sort_row");
		  $root->appendChild($elementRow);
		  
		  
		  $element = $dom->createElement("div");
		  $element->setAttribute("id", "facet_sort_message_cell");
		  $elementText = $dom->createTextNode($facetSortMessage);
		  $element->appendChild($elementText);
		  $elementRow->appendChild($element);
		  
		  $element = $dom->createElement("div");
		  $element->setAttribute("id", "facet_sort_control_cell");
		  $elementB = $dom->createElement("div");
		  $elementB->setAttribute("id", "facet_sort_control");
		  $elementC = $dom->createElement("a");
		  $elementC->setAttribute("href", $facetSortLink);
		  $elementC->setAttribute("title", "Change the order of the facet filters below");
		  $elementCText = $dom->createTextNode($facetSort);
		  $elementC->appendChild($elementCText);
		  $elementB->appendChild($elementC);
		  $element->appendChild($elementB);
		  $elementRow->appendChild($element);
		  
		  return $dom->saveXML($root);
	 }


}//end class
