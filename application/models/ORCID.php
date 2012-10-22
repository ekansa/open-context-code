<?php

/*
 Interacts with the ORCID API
 to request publication and grant information for ORCID users. 
*/

class ORCID {
 
//database content 
public $orcID; 
const APIuri = "http://api.orchid.org/"; //base uri API


//get an ORCID from an ORCID URI
function uriToORCID($orcidURI){
    $this->orcID = str_replace("http://orcid.org/", "", $orcidURI);
}



//this function gets a public profile
function getProfile(){

    $requestURI = self::APIuri.$this-orcID."/orcid-profile";
    
    $client = new Zend_Http_Client($userURI);
    //$client->setHeaders('Host', $this->requestHost);
    $client->setHeaders('Accept', 'application/json');
    
    $response = $client->request("get"); //send the request, using the POST method
    return $response;
}


}//end class

?>
