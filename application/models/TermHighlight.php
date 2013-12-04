<?php


//this class highlights search terms
class TermHighlight{
    
    public $snippet; //the output snippet, with search terms highlighted
    
    public $searchString; //original search string
    public $searchTermsSingPlural; //array of search terms, both singular and plural
    public $xmlString; //string of XML data where search terms are desired
    
    public $tokens; //array of cleaned tokens
    public $matchedTokens; //array of keys for tokens matching search terms
    public $exactMatchTokens; //array of exact matches
    
    public $addMarkup; //boolean, add markup for highlighted text or not
    
    public $goodAcroynms = array("nsf", "xml", "XML", "NSF", "csv", "CSV", "neh", "NEH", "ARK");
    
    const style = 'highlight'; //partial match
    const style_i = 'highlight_important'; //exact match


    public function XMLsnippet(){
	
		  $xmlString = $this->xmlString;
		  $this->searchTermArray($this->searchString); //make search string an array of singular and plurals
		  
		  $rawXMLTokens = $this->xml_to_array($xmlString);
		  $rawTokens = array();
		  foreach($rawXMLTokens as $xmlToken){
				
				$xmlToken = strip_tags($xmlToken);
				$tokenSplit = mb_split(" ", $xmlToken);
				foreach($tokenSplit as $token){
			  $rawTokens[] = $token;
				}
		  }
		  
		  $tokens = array();
		  $matchedTokens = array();
		  $exactMatchArray = array();
		  $i=0;
	
		  //echo print_r($this->searchTermsSingPlural);
		  
		  foreach($rawTokens as $token){
				$matchedTerm = false;
				foreach($this->searchTermsSingPlural as $term){
			  if(mb_strlen($term)>=4 || in_array($term, $this->goodAcroynms)){
					$doSubstrings = true;
			  }
			  else{
					$doSubstrings = false;
			  }
			  
			  if($doSubstrings){
					if(mb_stristr($token, $term) && !mb_stristr($token, "ctx_ver=Z39.88")){
				  
				  //if you already have matched the term, then ignore matchings in URLs
				  if(count($matchedTokens)>0 && mb_stristr($token, "http://")){
						$matchedTerm = false;
				  }
				  else{
						$matchedTerm = true;
				  }
					}
			  }
			  else{
					if($token == $term){
				  $matchedTerm = true;
					}
			  }
			  
			  if($token == $term){
					$exactMatchArray[] = $i;
			  }
			  
				}//end loop through search terms
				
				if($matchedTerm){
					 $tokens[] = $token;
					 $matchedTokens[] = $i;
					 $i++;
				}
				else{
					 $token = $this->cleanToken($token);
					 if($token != false){
						  $tokens[] = $token;
						  $i++;
					 }
				}
				
		  }//end loop through tokens
		  
		  $this->tokens = $tokens;
		  $this->matchedTokens = $matchedTokens;
		  $this->exactMatchTokens = $exactMatchArray;
	
    }//end function
    

    function generateSnippet(){
	
		  $tokens = $this->tokens;
		  $matchedTokens = $this->matchedTokens;
		  $exactMatchTokens = $this->exactMatchTokens;
		  
		  $tokenCount = count($tokens);
		  
		  //echo print_r($exactMatchTokens);
		  
		  if(count($exactMatchTokens)>0){
				$firstMatch = min($exactMatchTokens);
		  }
		  elseif(count($matchedTokens)>0){
				$firstMatch = min($matchedTokens);
		  }
		  else{
				$firstMatch = 0;
		  }
		  
		  $snippetStart = 0;
		  if($firstMatch - 20 >= 0){
				$snippetStart = $firstMatch - 20;
		  }
		  $snippetEnd = $firstMatch + 20;
		  if($snippetEnd >= $tokenCount){
				$snippetEnd = $tokenCount;
		  }
		  
		  $i = $snippetStart;
		  $snippetArray = array();
		  while($i < $snippetEnd){
		  
				$token = $tokens[$i];
				if($this->addMarkup){
					 if(in_array($i, $matchedTokens)){
						  $token = "<span class='".self::style."'>".$token."</span>";
					 }
				}
				$snippetArray[] = $token;
		  $i++;    
		  }
		  
		  $this->snippet = implode(" ", $snippetArray);
	
    }//end function





    public function searchTermArray($rawSearchTerm){
	
		  $rawSearchTerm = trim($rawSearchTerm);
		  $keywordArray = array();
		  //$rawSearchTerm = mb_eregi_replace('/[^a-zA-Z0-9-\s]/', ' ', $rawSearchTerm);
		  if(mb_strlen($rawSearchTerm)>1){
				$rawKeyWorldArray = mb_split(' ', $rawSearchTerm);
				
				foreach($rawKeyWorldArray as $string){
	  
					 if($string != " "){
						  $keywordArray[] = $string;
					 }
					 if($string{mb_strlen($string)-1} == 's'){
						  $keywordArray[] = mb_substr($string, 0, -1);
					 }
				}
		  }
		  
		  $this->searchTermsSingPlural = $keywordArray;
    }


    


    
    //use this to remove http, UUIDs from snippets
    public function cleanToken($token){
       
        if(mb_strlen($token)>35 && mb_substr_count($token, "-")>=3){
            $token = false;
        }
        elseif(mb_strlen($token)>20 && mb_substr_count($token, "/")>=2){
            $token = false;
        }
        elseif(mb_strlen($token)>15 && mb_stristr($token, "0000")){
            $token = false;
        }
        elseif(mb_substr_count($token, "_")>=2){
            $token = false;
        }
        elseif(mb_substr_count($token, "%")>=2){
            $token = false;
        }
        elseif(mb_substr_count($token, "+")>=2){
            $token = false;
        }
        elseif(mb_stristr($token, "http://")){
            $token = false;
        }
		  elseif(mb_stristr($token, "ctx_ver=Z39.88")){
            $token = false;
        }
		  return $token; 
    }//end function    

    //read xml string, put text nodes into an array
    public function xml_to_array($string){
		  $data = array();
		  @$xml = simplexml_load_string($string);
		  if($xml){
				unset($xml);
				$doc = new DOMDocument("1.0", "utf-8");
				$doc->strictErrorChecking = FALSE ;
				@$doc->loadXML($string, LIBXML_NOWARNING);
				
				$xpath = new DOMXpath($doc);
				$textnodes = $xpath->query('//text()');
				foreach($textnodes as $node){
			  $data[] =  $node->nodeValue;
				}
				unset($doc);
		  }
		  return $data; //returns empty array if xml isn't valid
    }//end function


	 function purgeBadEntities($string){
		  
		  $badArray("&ldquo;", "&rdquo;");
		  
		  
		  
	 }


}//end class
