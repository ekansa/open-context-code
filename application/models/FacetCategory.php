<?php


/*
 this class is used to create individual facets,
 facet values, and links for GET queries to filder by a facet, and checkboxes for
 advanced search options on a facet
*/
class FacetCategory{
    
    public $facet_cat; // facet category  (project_name, cat, person_link, etc.)
    public $facet_category_label; //label for facet categories
    public $facet_category_feed; //category for the facet feed, json representation
   
   
    public $parameter; //parameter used for linking
    public $checkParameter; //parameter used for advanced search check box
    
    public $arrayParameter; //is the parrameter an array parameter? Eg: "tag[]=xxxx" ; "taxa[]=xxxx"
    public $extendLastArrayParameter; //is the parameter an array who's value is being extended? if so, the delimiter is passed here eg: taxa[]=xxx::yyy
    public $arrayKeyParameterValue; //value for the array key parameter, if set
    public $taxonType; // top_taxon or down_taxon

    public $tDescription;

    public $linkURLprefix;
    public $linkURLsuffix;

    public $getAltRepresentations; // Boolean get alternative representations (atom / json facet services)
    public $facAtom_linkURLprefix;
    public $facJSON_linkURLprefix;
    public $facKML_linkURLprefix;
    public $facAtom_linkURLsuffix;
    public $facJSON_linkURLsuffix;
    public $facKML_linkURLsuffix;
    public $resJSON_linkURLprefix;   
    public $resJSON_linkURLsuffix; 


    function setParameter(){
        if (preg_match('/^def_context_/', $this->facet_cat)) {
            $this->parameter = "default_context_path";
            $this->checkParameter = "default_context_path";
            $this->facet_category_label = "Context";
            $this->facet_category_feed = "context";
            $this->arrayParameter = false;
            $this->extendLastArrayParameter = false;
        }
        elseif ($this->facet_cat == 'item_type') {
            $this->parameter = "doctype";
            $this->checkParameter = "doctype";
            $this->facet_category_label = "Resource Type";
            $this->facet_category_feed = "resource type";
            $this->arrayParameter = false;
            $this->extendLastArrayParameter = false;
        }
        elseif ($this->facet_cat == 'project_name') {
            $this->parameter = "proj";
            $this->checkParameter = "proj";
            $this->facet_category_label = "Project";
            $this->facet_category_feed = "project";
            $this->arrayParameter = false;
            $this->extendLastArrayParameter = false;
        }
        elseif ($this->facet_cat == 'item_class') {
            $this->parameter = "cat";
            $this->checkParameter = "cat";
            $this->facet_category_label = "Category";
            $this->facet_category_feed = "category";
            $this->arrayParameter = false;
            $this->extendLastArrayParameter = false;
        }
        elseif ($this->facet_cat == 'tag_creator_name') {
            $this->parameter = "tagger";
            $this->checkParameter = "tagger";
            $this->facet_category_label = "Tag Creator";
            $this->facet_category_feed = "tag creator";
            $this->arrayParameter = false;
            $this->extendLastArrayParameter = false;
        }
        elseif ($this->facet_cat == 'person_link') {
            $this->parameter = "person";
            $this->checkParameter = "person";
            $this->facet_category_label = "Related People";
            $this->facet_category_feed = "related person";
            $this->arrayParameter = false;
            $this->extendLastArrayParameter = false;
        }
        elseif ($this->facet_cat == 'creator') {
            $this->parameter = "creator";
            $this->checkParameter = "creator";
            $this->facet_category_label = "Principle Investigators / Directors";
            $this->facet_category_feed = "creator";
            $this->arrayParameter = false;
            $this->extendLastArrayParameter = false;
        }
        elseif ($this->facet_cat == 'contributor') {
            $this->parameter = "contributor";
            $this->checkParameter = "contributor";
            $this->facet_category_label = "Contributors";
            $this->facet_category_feed = "contributor";
            $this->arrayParameter = false;
            $this->extendLastArrayParameter = false;
        }
        elseif ($this->facet_cat == 'image_media_count') {
            $this->parameter = "images";
            $this->checkParameter = "images";
            $this->facet_category_label = "Image Media Count";
            $this->facet_category_feed = "image count";
            $this->arrayParameter = false;
            $this->extendLastArrayParameter = false;
        }
        elseif ($this->facet_cat == 'time_span') {
            $this->parameter = "time_span";
            $this->checkParameter = "time_span";
            $this->facet_category_label = "Date Range";
            $this->facet_category_feed = "date range";
            $this->arrayParameter = false;
            $this->extendLastArrayParameter = false;
        }
        elseif ($this->facet_cat == 'user_tag') {
            $this->parameter = "tag";
            $this->checkParameter = urlencode("tag[]");
            $this->facet_category_label = "User Tag";
            $this->facet_category_feed = "user tag";
            $this->arrayParameter = true;
            $this->extendLastArrayParameter = false;
        }
        elseif ($this->facet_cat == 'top_taxon') {
            $this->parameter = "taxa";
            $this->checkParameter = urlencode("taxa[]");
            $this->facet_category_label = "Descriptive Properties";
            $this->facet_category_feed = "classification";
            $this->taxonType = 'top_taxon';
            $this->arrayParameter = true;
            $this->extendLastArrayParameter = false;
        }
        elseif ($this->facet_cat == 'standard_taxon') {
            $this->parameter = "taxa";
            $this->checkParameter = urlencode("taxa[]");
            $this->facet_category_label = "Standard Measurement Units";
            $this->facet_category_feed = "standard_units";
            $this->taxonType = 'standard_taxon';
            $this->arrayParameter = true;
            $this->extendLastArrayParameter = false;
        }
        elseif (($this->facet_cat != 'top_taxon')&&(!stristr($this->facet_cat, "_lent_taxon"))&&(substr_count($this->facet_cat, "_taxon")>0)) {
            
            $this->parameter = "taxa";
            $this->checkParameter = urlencode("dtaxa[]");
            $this->facet_category_label = "Description";
            $this->facet_category_feed = "sub-classification";
            $this->taxonType = 'down_taxon';
            $this->arrayParameter = true;
            $this->extendLastArrayParameter = "::";
        }
        elseif ((stristr($this->facet_cat, "_lent_taxon")) && (!stristr($this->facet_cat, "_top_lent_taxon"))) {
            $this->parameter = "rel";
            $this->checkParameter = urlencode("drel[]");
            $this->facet_category_label = "Linking Relation Target";
            $this->facet_category_feed = "linking-relation-target";
            $this->taxonType = 'down_taxon';
            $this->arrayParameter = true;
            $this->extendLastArrayParameter = "::";
        }
        elseif (stristr($this->facet_cat, "_top_lent_taxon")) {
            $this->parameter = "rel";
            $this->checkParameter = urlencode("rel[]");
            $this->facet_category_label = "Linking Relation";
            $this->facet_category_feed = "linking-relation";
            $this->taxonType = 'top_taxon';
            $this->arrayParameter = true;
            $this->extendLastArrayParameter = "::";
        }
        elseif ($this->facet_cat == 'variable_NOB') {
            $this->parameter = "prop";
            $this->checkParameter = urlencode("prop[]");
            $this->facet_category_label = "Descriptive Variable";
            $this->arrayParameter = true;
            $this->extendLastArrayParameter = false;
            $this->arrayKeyParameterValue = "unset OC variable";
        } elseif (preg_match('/_var_NOB_val/', $this->facet_cat)) {
            $this->parameter = "prop";
            $parameter_key_value = str_replace('\\', '', $this->facet_cat);
            $parameter_key_value = substr($parameter_key_value, 0, strlen($parameter_key_value) -12 ); // remove trailing '_var_NOB_val'
            $this->checkParameter = urlencode("prop[".$parameter_key_value."]");
            $this->facet_category_label = "Value for ".$parameter_key_value;
            $this->arrayParameter = true;
            $this->extendLastArrayParameter = false;
            $this->arrayKeyParameterValue = $parameter_key_value;
        }
    }
    
    
    //this function prepares a facet URL
    function prepareFacetURL($requestParams){
	
        $explodeDelimiter = "xxblankxx";
	$valueBlank = $explodeDelimiter;
        
	$addArrayParam = false;
        
        if($this->arrayParameter){
             $addArrayParam = true; //paramater is an array parameter
	}
        
        if($this->parameter == "prop"){
            if($this->arrayKeyParameterValue == "unset OC variable"){
                $addArrayParam = $explodeDelimiter;
                $valueBlank = "";
            }
            else{
                $addArrayParam = $this->arrayKeyParameterValue; //key for property with variable already selected
            }
        }
        
        if($this->parameter){
            $urlTemplate = OpenContext_FacetOutput::generateFacetURL($requestParams, $this->parameter, $valueBlank, $addArrayParam, $this->extendLastArrayParameter, "xhtml");
                    
            $urlArray = explode($explodeDelimiter, $urlTemplate);
            if(count($urlArray)>1){
                $this->linkURLprefix = str_replace("//", "/",$urlArray[0]);
                $this->linkURLsuffix = $urlArray[1];
            }
            else{
                $this->linkURLprefix = str_replace("//", "/",$urlArray[0]);
                $this->linkURLsuffix = NULL;
            }
            
            
            //do this if alternate representations are needed
            if($this->getAltRepresentations){
                
                //facet atom
                $urlTemplate = OpenContext_FacetOutput::generateFacetURL($requestParams, $this->parameter, $valueBlank, $addArrayParam, $this->extendLastArrayParameter, "facets_atom");        
                $urlArray = explode($explodeDelimiter, $urlTemplate);
                if(count($urlArray)>1){
                    $this->facAtom_linkURLprefix = str_replace("//", "/",$urlArray[0]);
                    $this->facAtom_linkURLsuffix = $urlArray[1];
                }
                else{
                    $this->facAtom_linkURLprefix = str_replace("//", "/",$urlArray[0]);
                    $this->facAtom_linkURLsuffix = NULL;
                }
                
                //facet json
                $urlTemplate = OpenContext_FacetOutput::generateFacetURL($requestParams, $this->parameter, $valueBlank, $addArrayParam, $this->extendLastArrayParameter, "facets_json");        
                $urlArray = explode($explodeDelimiter, $urlTemplate);
                if(count($urlArray)>1){
                    $this->facJSON_linkURLprefix = str_replace("//", "/",$urlArray[0]);
                    $this->facJSON_linkURLsuffix = $urlArray[1];
                }
                else{
                    $this->facJSON_linkURLprefix = str_replace("//", "/",$urlArray[0]);
                    $this->facJSON_linkURLsuffix = NULL;
                }
                
                //facet kml
                $urlTemplate = OpenContext_FacetOutput::generateFacetURL($requestParams, $this->parameter, $valueBlank, $addArrayParam, $this->extendLastArrayParameter, "facets_kml");        
                $urlArray = explode($explodeDelimiter, $urlTemplate);
                if(count($urlArray)>1){
                    $this->facKML_linkURLprefix = str_replace("//", "/",$urlArray[0]);
                    $this->facKML_linkURLsuffix = $urlArray[1];
                }
                else{
                    $this->facKML_linkURLprefix = str_replace("//", "/",$urlArray[0]);
                    $this->facKML_linkURLsuffix = NULL;
                }
                
                //results JSON
                $urlTemplate = OpenContext_FacetOutput::generateFacetURL($requestParams, $this->parameter, $valueBlank, $addArrayParam, $this->extendLastArrayParameter, "results_json");        
                $urlArray = explode($explodeDelimiter, $urlTemplate);
                if(count($urlArray)>1){
                    $this->resJSON_linkURLprefix = str_replace("//", "/",$urlArray[0]);
                    $this->resJSON_linkURLsuffix = $urlArray[1];
                }
                else{
                    $this->resJSON_linkURLprefix = str_replace("//", "/",$urlArray[0]);
                    $this->resJSON_linkURLsuffix = NULL;
                }
                
            }//end case of making alternative representations
            
        }
        
    }
    
    
}
