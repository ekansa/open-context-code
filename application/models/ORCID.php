<?php

/*
 Interacts with the ORCID API
 to request publication and grant information for ORCID users. 
*/

class ORCID {
 
//database content 
public $orcID;
public $profileObj; //object from JSON response
public $APIresponse;
const APIuri = "http://pub.orcid.org/"; //base uri API


//get an ORCID from an ORCID URI
function uriToORCID($orcidURI){
    $this->orcID = str_replace("http://orcid.org/", "", $orcidURI);
}


function getProfile($orcidURI){
    $this->uriToORCID($orcidURI);
    $jsonString = false;
    return $this->APIgetProfile();
}


//this function gets a public profile
function APIgetProfile(){

    $requestURI = self::APIuri.$this->orcID."/orcid-profile";
   
    $client = new Zend_Http_Client($requestURI);
    $client->setHeaders('Accept', 'application/orcid+json');
    
    $response = $client->request("GET"); //send the request, using the POST method
    if($response){
        $this->APIresponse = $response;
        return $response;
    }
    else{
        return fals;
    }
}



function returnResponse($response){
    
    header("Content-Type: application/json; charset=utf8");
    echo $response->getBody();
}


}//end class

?>
