<?php

class OpenContext_NavMenus {





public static function AboutNavMenu($activePage, $host = false){
    //this function generates the navigation menu for the about pages
    
    if(!$host){
        $host = OpenContext_OCConfig::get_host_config();
    }
    
   $pageArray = array("index"=> array("uri"=>"/about/", "name"=>"About", "title"=> "Introductory overview"),
                      "uses"=> array("uri"=>"/about/uses/", "name"=>"Uses", "title"=> "Research and instructional purposes"),
                      "publishing"=> array("uri"=>"/about/publishing", "name"=>"Publishing", "title"=> "Policies and instructions on how to publish with Open Context"),
                       "estimate"=> array("uri"=>"/about/estimate", "name"=>"Grant Seekers", "title"=> "Help with preparing grants, including the NSF Data Access Plan"),
                      "concepts"=> array("uri"=>"/about/concepts", "name"=>"Concepts", "title"=> "Ontologies and data models"),
                      "technology"=> array("uri"=>"/about/technology", "name"=>"Technology", "title"=> "Software applications and standards"),
                      "services"=> array("uri"=>"/about/services", "name"=>"Services", "title"=> "Machine-readable data and interoperability"),
                      "biblio"=> array("uri"=>"/about/bibliography", "name"=>"Bibliography", "title"=> "Publications related to Open Context"),
                      "privacy"=> array("uri"=>"/about/privacy", "name"=>"Privacy", "title"=> "Open Context's Privacy Policy"),
                      "ip"=> array("uri"=>"/about/intellectual-property", "name"=>"Intellectual Property", "title"=> "Open Context's IP Recommendations"),
                      "people"=> array("uri"=>"/about/people", "name"=>"People", "title"=> "Editors and software developers"),
                      "sponsors"=> array("uri"=>"/about/sponsors", "name"=>"Sponsors", "title"=> "Funding and organizational partners")
                      );
   
   $xmlString = '<ul class="bodyText"></ul>';
   $xml = simplexml_load_string($xmlString);
   
   foreach($pageArray as $page => $pageData){
        $listItem = $xml->addChild('li', '');
        if(  $page == $activePage ){
            
            $strongName = $listItem->addChild('strong', $pageData["name"]);
        }
        else{
            
            $linkItem = $listItem->addChild('a', $pageData["name"]);
            if(!stristr($pageData["uri"], "http://")){
                $linkItem->addAttribute('href', $host.$pageData["uri"]);
            }
            else{
               $linkItem->addAttribute('href', $pageData["uri"]); 
            }
            $linkItem->addAttribute('title', $pageData["title"]);
        }
   }
     
    $dom = dom_import_simplexml($xml)->ownerDocument;
    $dom->formatOutput = true;
    $output = str_replace('<?xml version="1.0"?>', '', $dom->saveXML());
    return $output; 
   
}//end early_late_range function



public static function GeneralNavMenu($activePage, $host = false){
    //this function generates the navigation menu for the about pages
    
    if(!$host){
        $host = OpenContext_OCConfig::get_host_config();
    }
    
   $pageArray = array("index"=> array("uri"=>"/",
                                      "name"=>"Home",
                                      "title"=> "Open Context Home Page, map and timeline interface",
                                      "active"=>true),
                      "about"=> array("uri"=>"/about/",
                                      "name"=>"About",
                                      "title"=> "Background, uses, guide for contributors, web services overview",
                                      "active"=>true),
                      "projects"=> array("uri"=>"/projects/",
                                      "name"=>"Projects",
                                      "title"=> "Summary of datasets in Open Context",
                                      "active"=>true),
                      "browse"=> array("uri"=>"/sets/",
                                       "name"=>"Browse",
                                       "title"=> "Search and browse through locations, contexts, finds, etc.",
                                       "active"=>true),
                      "lightbox"=> array("uri"=>"/lightbox/",
                                         "name"=>"Lightbox",
                                         "title"=> "Search and browse through images linked to Open Context records",
                                         "active"=>true),
                      "tables"=> array("uri"=>"/table-browse/",
                                       "name"=>"Tables",
                                       "title"=> "Tabular data formated for easy download",
                                       "active"=> true),
                      "search"=> array("uri"=>"/search/",
                                       "name"=> "Search",
                                       "title"=> "Global search of all content, policies, and other documentation",
                                        "active"=> true),
                      "details"=> array("uri"=>"/details/",
                                       "name"=> "Details",
                                       "title"=> "Use the Browse or Lightbox feature and select an item for detailed view",
                                        "active"=> false),
                      "accounts"=> array("uri"=>"/auth/",
                                       "name"=> "My Account",
                                       "title"=> "Manage your password and notification settings",
                                        "active"=> true)
                      );
   
   if($activePage == "search"){
        unset($pageArray["details"]);
   }
   elseif($activePage == "details"){
        unset($pageArray["search"]);
   }
   else{
        unset($pageArray["details"]); 
   }
   
   
   
   $output = '<ul class="bodyText">';
   foreach($pageArray as $page => $pageData){
        $output .= chr(12);
        if(  $page == $activePage ){
            $output .= '<li><strong>'.$pageData["name"].'</strong></li>'.chr(13);
        }
        else{
            $output .= '<li><a href="'.$host.$pageData["uri"].'" title="'.$pageData["title"].'">'.$pageData["name"].'</a></li>'.chr(13);
        }
   }
   $output .= '</ul>';
   
   
    $xmlString = "<div></div>";
    $xml = simplexml_load_string($xmlString);
    $xml->addAttribute('id', 'oc_main_nav');
    /*
    $sideL = $xml->addChild('div', '');
    $sideL->addAttribute('class', 'sides_l');
    */
    $i=0;
    foreach($pageArray as $page => $pageData){
        $sideL[$i] = $xml->addChild('div', '');
        $tab[$i] = $xml->addChild('div');
        $sideR[$i] = $xml->addChild('div', '');
        
        if(  $page == $activePage ){
            $tab[$i]->addAttribute('class', 'act_nav');
            $sideL[$i]->addAttribute('class', 'act_nav_l');
            $sideR[$i]->addAttribute('class', 'act_nav_r');
        }
        else{
            $tab[$i]->addAttribute('class', 'n_act_nav');
            $sideL[$i]->addAttribute('class', 'n_act_nav_l');
            $sideR[$i]->addAttribute('class', 'n_act_nav_r');
        }
        
        if($pageData["active"]){
            $tabCont[$i] = $tab[$i]->addChild('a', $pageData["name"]);
            $tabCont[$i]->addAttribute('href', $host.$pageData["uri"]);
        }
        else{
            $tabCont[$i] = $tab[$i]->addChild('span', $pageData["name"]);
        }
        
        $tabCont[$i]->addAttribute('title', $pageData["title"]);
        
        $i++;
    }
   
    $dom = dom_import_simplexml($xml)->ownerDocument;
    $dom->formatOutput = true;
    $output = str_replace('<?xml version="1.0"?>', '', $dom->saveXML());
    return $output; 
   
}//end early_late_range function






public static function socialMedia($activeURI = false){
    
    $socialMediaSites = array("facebook" => array("icon"=> "",
                                                  "uri"=> "http://www.facebook.com/pages/Open-Context/106644836049549",
                                                  "title"=> "Connect with Open Context on Facebook"),
                              "twitter" => array("icon"=> "",
                                                  "uri"=> "http://twitter.com/oc_sarah",
                                                  "title"=> "Follow Open Context news on Twitter"),
                              
                              );
    
    
    
    
    
}






}