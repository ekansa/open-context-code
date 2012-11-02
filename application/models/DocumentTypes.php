<?php


/*this class manages translations between document types internal to solr
and document types visible to outside users
*/
class DocumentTypes{
    
    public $solrOutside = array("spatial" => array("term" => "Analytic data (subjects)", "linkVal" => "subject"),
				"image" => array("term" => "Image media", "linkVal" => "image"),
				"external" => array("term" => "Other Media (not images)", "linkVal" => "media"),
				"person" => array("term" => "Persons", "linkVal" => "person"),
				"document" => array("term" => "Project documents, logs, and diaries", "linkVal" => "document"),
				"project" => array("term" => "Projects or collections", "linkVal" => "project"),
				"table" => array("term" => "Data tables", "linkVal" => "table"),
				"site" => array("term" => "Open Context website documentation", "linkVal" => "site"),
				"study" => array("term" => "CLIC Participating Study", "linkVal" => "study"),
				"protocol_item" => array("term" => "CLIC Protocol Component", "linkVal" => "protocol-item"),
			       );
    
    
    public $outsideTerm; //found human readable term for solr document type; input value if not found
    public $linkVal; //found url query value for solr document type; input value if not found
    public $solrVal; //solr documtent value associated with the url query value; defaults to outside value if not found
    
    //turns an outside query value into a value solr can see
    function externalValueToSolr($outsideVal){
		  $linkKey = array();
		  $solrVal = $outsideVal;
		  foreach($this->solrOutside as $solrkey => $outsideArray){
				$outsideLink = $outsideArray["linkVal"];
				if($outsideVal == $outsideLink){
					 $solrVal = $solrkey; //found the solr value for the outside request value
				}
				
		  }
	
		  $this->solrVal = $solrVal;
		  return $solrVal;
    }
    
    function solrToOutside($solrValue){
		  $this->outsideTerm = $solrValue;
		  $this->linkVal = $solrValue;
		  if(array_key_exists($solrValue, $this->solrOutside)){
				$solrOutside = $this->solrOutside;
				$this->outsideTerm = $solrOutside[$solrValue]["term"];
				$this->linkVal = $solrOutside[$solrValue]["linkVal"];
		  }
    }
    
    
}//end class
